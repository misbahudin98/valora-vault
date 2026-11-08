// Cookie helpers for client-side state syncing and SSR/hydration persistence
export function getCookie(name) {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]+)'));
  return match ? decodeURIComponent(match[2]) : null;
}

export function setCookie(name, val, days = 7) {
  if (typeof document === 'undefined') return;
  const d = new Date();
  d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
  document.cookie = `${name}=${encodeURIComponent(val)};path=/;expires=${d.toUTCString()};SameSite=Lax`;
}

export function deleteCookie(name) {
  if (typeof document === 'undefined') return;
  const hostname = window.location.hostname;
  document.cookie = `${name}=;path=/;expires=Thu, 01 Jan 1970 00:00:00 GMT;SameSite=Lax`;
  if (hostname) {
    document.cookie = `${name}=;path=/;domain=.${hostname};expires=Thu, 01 Jan 1970 00:00:00 GMT;SameSite=Lax`;
    document.cookie = `${name}=;path=/;domain=${hostname};expires=Thu, 01 Jan 1970 00:00:00 GMT;SameSite=Lax`;
  }
}

/**
 * Decode JWT payload on the client without external libraries.
 */
export function parseJwt(token) {
  if (!token) return null;
  try {
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    let base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
    while (base64.length % 4 !== 0) {
      base64 += '=';
    }
    const jsonStr = decodeURIComponent(
      atob(base64)
        .split('')
        .map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    );
    const parsed = JSON.parse(jsonStr);
    // Check if token has expired
    if (parsed.exp && parsed.exp * 1000 < Date.now()) {
      deleteCookie('auth_jwt');
      return null;
    }
    return parsed;
  } catch (e) {
    console.error('Failed to parse JWT cookie:', e);
    return null;
  }
}

function getCachedUser() {
  if (typeof window === 'undefined') return null;
  try {
    const raw = localStorage.getItem('hyperion_user');
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

class AuthState {
  user = $state(getCachedUser());
  jwtClaims = $state(parseJwt(getCookie('auth_jwt')));
  cookieRole = $state(getCookie('user_role'));
  isLoaded = $state(false);

  syncFromCookies() {
    if (typeof document === 'undefined') return;
    this.jwtClaims = parseJwt(getCookie('auth_jwt'));
    this.cookieRole = getCookie('user_role');
  }

  // Derived properties with automatic reactivity in Svelte 5
  isAuthenticated = $derived(
    this.user !== null || (this.jwtClaims !== null && this.jwtClaims.exp * 1000 > Date.now())
  );

  name = $derived(
    this.user?.name || this.jwtClaims?.name || 'Administrator'
  );

  email = $derived(
    this.user?.email || this.jwtClaims?.email || ''
  );

  // Normalize roles to an array of strings
  roles = $derived.by(() => {
    if (this.user?.role) {
      return Array.isArray(this.user.role) ? this.user.role : [this.user.role];
    }
    if (this.jwtClaims?.roles) {
      return Array.isArray(this.jwtClaims.roles) ? this.jwtClaims.roles : [this.jwtClaims.roles];
    }
    if (this.cookieRole) {
      return this.cookieRole.split(',').map(r => r.trim());
    }
    return [];
  });

  primaryRole = $derived(this.roles[0] || 'user');

  isAdmin = $derived(this.hasRole('admin'));

  /**
   * Set user data after successful authentication.
   * Can ingest user object, and optionally consume (delete) the JWT cookie if client-side retention is preferred.
   */
  setUser(data, deleteCookieAfterIngest = false) {
    this.user = data;
    if (typeof window !== 'undefined' && data) {
      try {
        localStorage.setItem('hyperion_user', JSON.stringify(data));
      } catch (e) {
        console.warn('LocalStorage save failed:', e);
      }
    }
    const rawJwt = getCookie('auth_jwt');
    this.jwtClaims = parseJwt(rawJwt);

    if (deleteCookieAfterIngest) {
      this.consumeAuthCookie();
    }
    this.isLoaded = true;
  }

  /**
   * Allow Svelte 5 to clean up the JWT cookie from the browser after reading it into memory.
   */
  consumeAuthCookie() {
    deleteCookie('auth_jwt');
  }

  /**
   * Clear all auth state and remove cookies and storage items.
   */
  clear() {
    this.user = null;
    this.jwtClaims = null;
    this.cookieRole = null;
    if (typeof window !== 'undefined') {
      try {
        localStorage.removeItem('hyperion_user');
        localStorage.removeItem('hyperion_token');
        sessionStorage.clear();
      } catch (e) {}
    }
    deleteCookie('auth_jwt');
    deleteCookie('user_role');
    deleteCookie('XSRF-TOKEN');
    deleteCookie('laravel_session');
    this.isLoaded = true;
  }

  clearUser() {
    this.clear();
  }

  /**
   * Check if user holds any of the given roles.
   */
  hasRole(...requiredRoles) {
    if (requiredRoles.length === 0) return false;
    const flattened = requiredRoles.flat();
    return this.roles.some(r => flattened.includes(r));
  }

  /**
   * Check if user holds all of the given roles.
   */
  hasAllRoles(...requiredRoles) {
    const flattened = requiredRoles.flat();
    return flattened.every(r => this.roles.includes(r));
  }

  /**
   * Extensible client permissions derived from user object or signed JWT claims.
   * Admin role automatically resolves wildcard '*'.
   */
  permissions = $derived.by(() => {
    if (this.isAdmin) return ['*'];
    if (this.user?.permissions) {
      return Array.isArray(this.user.permissions) ? this.user.permissions : [this.user.permissions];
    }
    if (this.jwtClaims?.permissions) {
      return Array.isArray(this.jwtClaims.permissions) ? this.jwtClaims.permissions : [this.jwtClaims.permissions];
    }
    return [];
  });

  /**
   * Check if user holds any of the given fine-grained permissions.
   */
  hasPermission(...requiredPerms) {
    if (this.isAdmin) return true;
    if (requiredPerms.length === 0) return false;
    const flattened = requiredPerms.flat();
    return this.permissions.some(p => p === '*' || flattened.includes(p));
  }
}

export const authState = new AuthState();
