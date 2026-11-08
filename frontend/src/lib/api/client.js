import { authState } from '$lib/state/auth.svelte.js';

const API_URL = import.meta.env.VITE_API_URL || (typeof window !== 'undefined' ? window.location.origin : 'http://hyperion-vault.test');

class ApiClient {
  constructor() {
    this.inFlight = new Map();
    this.memoryCache = new Map();
    this.defaultTtlMs = 30000; // 30 seconds fresh cache
  }

  clearCache(pattern = null) {
    if (!pattern) {
      this.memoryCache.clear();
      return;
    }
    for (const key of this.memoryCache.keys()) {
      if (key.includes(pattern)) {
        this.memoryCache.delete(key);
      }
    }
  }

  getCsrfToken() {
    if (typeof document === 'undefined') return null;
    const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
  }

  async getCsrfCookie() {
    try {
      await fetch(`${API_URL}/sanctum/csrf-cookie`, {
        method: 'GET',
        credentials: 'include',
        headers: { 'Accept': 'application/json' }
      });
    } catch {
      // Allow fallback if network glitch
    }
  }

  async request(endpoint, options = {}, retries = 0) {
    const finalOptions = { ...options };
    if (finalOptions.data !== undefined && finalOptions.body === undefined) {
      finalOptions.body = finalOptions.data;
    }
    if (finalOptions.body && typeof finalOptions.body === 'object' && !(finalOptions.body instanceof FormData)) {
      finalOptions.body = JSON.stringify(finalOptions.body);
    }

    const method = finalOptions.method?.toUpperCase() || 'GET';
    const isMutation = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

    // Invalidate cache immediately on data mutation
    if (isMutation) {
      this.clearCache();
    }

    // Handle GET caching & in-flight deduplication
    const isCacheable = method === 'GET' && !finalOptions.skipCache && !finalOptions.headers?.noCache;
    const cacheKey = `${method}:${endpoint}`;

    if (isCacheable) {
      const cached = this.memoryCache.get(cacheKey);
      if (cached && (Date.now() - cached.timestamp < (options.ttl || this.defaultTtlMs))) {
        return structuredClone(cached.data);
      }

      // In-flight deduplication (Request Collapsing)
      if (this.inFlight.has(cacheKey)) {
        return this.inFlight.get(cacheKey);
      }
    }

    const executionPromise = (async () => {
      // If mutation and no CSRF cookie exists yet, fetch it first
      if (isMutation && !this.getCsrfToken()) {
        await this.getCsrfCookie();
      }

      const headers = new Headers(finalOptions.headers || {});
      headers.set('Accept', 'application/json');
      if (!headers.has('Content-Type') && !(finalOptions.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
      }

      const token = this.getCsrfToken();
      if (isMutation && token) {
        headers.set('X-XSRF-TOKEN', token);
      }

      const bearer = typeof window !== 'undefined' ? localStorage.getItem('hyperion_token') : null;
      if (bearer && !headers.has('Authorization')) {
        headers.set('Authorization', `Bearer ${bearer}`);
      }

      const res = await fetch(`${API_URL}${endpoint}`, {
        ...finalOptions,
        headers,
        credentials: 'include'
      });

      if (res.status === 419 && retries < 2) {
        await this.getCsrfCookie();
        return this.request(endpoint, finalOptions, retries + 1);
      }

      if (res.status === 204) return null;

      if (finalOptions.returnRawResponse) {
        return res;
      }

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        if (res.status === 401) {
          const msg = String(errorData.message || '');
          if (endpoint.includes('/auth/me') || endpoint.includes('/auth/login') || msg.includes('dicabut') || msg.includes('Unauthenticated') || msg.includes('ditangguhkan')) {
            authState.clear();
          }
        }
        throw { status: res.status, ...errorData };
      }

      const json = await res.json();

      if (isCacheable) {
        this.memoryCache.set(cacheKey, {
          timestamp: Date.now(),
          data: json
        });
      }

      return json;
    })();

    if (isCacheable) {
      this.inFlight.set(cacheKey, executionPromise);
      try {
        const result = await executionPromise;
        return structuredClone(result);
      } finally {
        this.inFlight.delete(cacheKey);
      }
    }

    return executionPromise;
  }
}

export const api = new ApiClient();
