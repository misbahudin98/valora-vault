/**
 * SvelteKit Client-Side Global Error Handler & Observability Telemetry
 * Traps uncaught client exceptions, strips PII, logs to console, and sends telemetry to backend.
 * Compatible with development mode and production static builds (@sveltejs/adapter-static).
 */

const reportedErrors = new Set();

/**
 * Sanitize strings to eliminate PII/secrets before network transmission (OWASP / GDPR compliance).
 */
function sanitizeMessage(str) {
    if (!str || typeof str !== 'string') return '';
    return str
        .replace(/(bearer\s+)[a-zA-Z0-9_\-\.]+/gi, '$1[REDACTED]')
        .replace(/(password|secret|token|api_key|auth)=[^&\s]+/gi, '$1=[REDACTED]');
}

/**
 * Dispatch non-blocking telemetry payload to backend.
 */
function sendTelemetry({ errorId, message, stack, url, type = 'sveltekit_error' }) {
    const cleanMsg = sanitizeMessage(message);
    const signature = `${type}:${cleanMsg}:${stack ? stack.substring(0, 100) : ''}`;

    if (reportedErrors.has(signature)) return;
    reportedErrors.add(signature);
    if (reportedErrors.size > 50) {
        reportedErrors.clear();
    }

    try {
        const payload = JSON.stringify({
            error_id: errorId,
            message: `[${type}] ${cleanMsg}`,
            stack: stack || null,
            url: sanitizeMessage(url || (typeof window !== 'undefined' ? window.location.href : '')),
            user_agent: typeof navigator !== 'undefined' ? navigator.userAgent : '',
            timestamp: new Date().toISOString(),
        });

        if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
            const blob = new Blob([payload], { type: 'application/json' });
            navigator.sendBeacon('/api/v1/telemetry/errors', blob);
        } else if (typeof fetch !== 'undefined') {
            fetch('/api/v1/telemetry/errors', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true,
            }).catch(() => {});
        }
    } catch {
        // Never crash error handler
    }
}

/**
 * Detect dynamic import failure caused by new deployment / stale chunks.
 */
function isDynamicImportError(err) {
    if (!err) return false;
    const msg = (err?.message || String(err)).toLowerCase();
    return (
        msg.includes('failed to fetch dynamically imported module') ||
        msg.includes('importing a module script failed') ||
        msg.includes('error loading dynamically imported module') ||
        msg.includes('is not a valid javascript mime type') ||
        msg.includes('expected a javascript module script') ||
        msg.includes('load failed')
    );
}

/**
 * Perform safe single-attempt auto-reload on stale chunk / version mismatch.
 * Uses sessionStorage guard to prevent infinite refresh loops if offline.
 */
function attemptStaleChunkRecovery(err) {
    if (typeof window === 'undefined') return;
    if (!isDynamicImportError(err)) return;

    try {
        const key = '__valora_chunk_reload_ts';
        const lastReload = sessionStorage.getItem(key);
        const now = Date.now();
        // Allow auto-reload only once per 15 seconds to prevent loops
        if (!lastReload || (now - parseInt(lastReload, 10)) > 15000) {
            sessionStorage.setItem(key, String(now));
            console.warn('[Valora Vault] Berkas chunk versi lama terdeteksi setelah deployment. Memuat ulang antarmuka terbaru...');
            window.location.reload();
        }
    } catch {
        // sessionStorage might be restricted (private mode)
        window.location.reload();
    }
}

/**
 * SvelteKit 2+ client init hook: attaches global window error & rejection listeners.
 * Runs once upon client hydration / app initialization.
 */
export async function init() {
    if (typeof window === 'undefined') return;

    window.addEventListener('unhandledrejection', (event) => {
        const error = event.reason;
        attemptStaleChunkRecovery(error);
        const errorId = crypto?.randomUUID ? crypto.randomUUID() : 'err-' + Math.random().toString(36).substring(2, 9);
        sendTelemetry({
            errorId,
            message: error?.message || String(error) || 'Unhandled Promise Rejection',
            stack: error?.stack || null,
            type: 'unhandledrejection'
        });
    });

    window.addEventListener('error', (event) => {
        // Ignore cross-origin script error noise with zero diagnostic info
        if (event.message === 'Script error.' && !event.filename) return;

        attemptStaleChunkRecovery(event.error);
        const errorId = crypto?.randomUUID ? crypto.randomUUID() : 'err-' + Math.random().toString(36).substring(2, 9);
        sendTelemetry({
            errorId,
            message: event.message || 'Window Global Error',
            stack: event.error?.stack || `${event.filename || 'unknown'}:${event.lineno || 0}:${event.colno || 0}`,
            type: 'window_error'
        });
    });
}

/**
 * SvelteKit global handleError hook: triggered on SvelteKit routing & component render exceptions.
 */
export async function handleError({ error, event, status, message }) {
    attemptStaleChunkRecovery(error);
    const errorId = crypto?.randomUUID ? crypto.randomUUID() : 'err-' + Math.random().toString(36).substring(2, 9);
    const errorMessage = error?.message || String(error) || 'Kesalahan tampilan klien.';
    const errorStack = error?.stack || null;
    const url = typeof window !== 'undefined' ? window.location.href : '';

    sendTelemetry({
        errorId,
        message: errorMessage,
        stack: errorStack,
        url,
        type: 'sveltekit_error'
    });

    return {
        message: message || 'Terjadi kesalahan teknis pada tampilan. Silakan muat ulang halaman.',
        errorId,
    };
}
