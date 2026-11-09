/**
 * Dynamic Navigation Schema
 * Role-based and Authentication-based Menu and Route Guard Configuration.
 */

export const navigationItems = [
  {
    id: 'home',
    label: 'Beranda',
    href: '/',
    hideInSidebar: true, // Constraint 7: Hilangkan menu beranda di sidebar
  },
  {
    id: 'dashboard',
    label: 'Dashboard',
    href: '/dashboard',
    auth: true,
    group: 'Utama',
    icon: 'dashboard',
  },
  {
    id: 'admin-studio',
    label: 'Blueprint Studio',
    href: '/admin/studio',
    auth: true,
    roles: ['admin'],
    group: 'Utama',
    icon: 'studio',
  },
  {
    id: 'admin-clients',
    label: 'Client & Proyek',
    href: '/admin/clients',
    auth: true,
    roles: ['admin'],
    group: 'Operasional',
    icon: 'folder',
  },
  {
    id: 'admin-progres',
    label: 'Progres Project',
    href: '/admin/progres',
    auth: true,
    roles: ['admin'],
    group: 'Operasional',
    icon: 'activity',
  },
  {
    id: 'admin-tagihan',
    label: 'Tagihan & Master Tarif',
    href: '/admin/tagihan',
    auth: true,
    roles: ['admin'],
    group: 'Operasional',
    icon: 'receipt',
  },
  {
    id: 'admin-dashboard',
    label: 'Admin Panel',
    href: '/admin/dashboard',
    auth: true,
    roles: ['admin'],
    group: 'Administrator',
    icon: 'shield',
    hideInSidebar: true, // Terkonsolidasi ke portal adaptif /dashboard
  },
  {
    id: 'admin-users',
    label: 'Kelola Pengguna',
    href: '/admin/users',
    auth: true,
    roles: ['admin'],
    group: 'Administrator',
    icon: 'users',
  },
  {
    id: 'admin-sessions',
    label: 'Manajemen Sesi',
    href: '/admin/sessions',
    auth: true,
    roles: ['admin'],
    group: 'Administrator',
    icon: 'key',
  },
  {
    id: 'admin-system',
    label: 'Sistem & Profil Agensi',
    href: '/admin/system',
    auth: true,
    roles: ['admin'],
    group: 'Administrator',
    icon: 'server',
  },
];

/**
 * Filter menu items according to user auth state, roles, and allowed_features configuration
 */
export function getVisibleNavItems(state) {
  return navigationItems.filter(item => {
    // Sembunyikan item yang ditandai hideInSidebar
    if (item.hideInSidebar) {
      return false;
    }
    // Jika rute butuh autentikasi dan user belum login
    if (item.auth && !state.isAuthenticated) {
      return false;
    }
    // Jika rute butuh role spesifik
    if (item.roles && item.roles.length > 0) {
      if (!state.isAuthenticated) return false;
      if (!state.hasRole(...item.roles)) return false;
    }

    // Saring berdasarkan Hak Akses Fitur Aplikasi Yang Boleh Dilihat (allowed_features)
    const allowed = state.user?.allowed_features;
    if (Array.isArray(allowed) && allowed.length > 0 && !allowed.includes('*')) {
      if (!allowed.includes(item.id)) {
        return false;
      }
    }

    return true;
  });
}

/**
 * Route Guard validator: verifies if a pathname is authorized.
 */
export function canAccessPath(pathname, state) {
  // If route is under /admin/*
  if (pathname.startsWith('/admin')) {
    if (!state.isAuthenticated) {
      return { authorized: false, reason: 'unauthenticated', redirectTo: '/login' };
    }
    if (!state.hasRole('admin')) {
      return { authorized: false, reason: 'forbidden', redirectTo: '/dashboard' };
    }

    // Cek batasan hak akses fitur aplikasi yang boleh dilihat
    const allowed = state.user?.allowed_features;
    if (Array.isArray(allowed) && allowed.length > 0 && !allowed.includes('*')) {
      const matchedItem = navigationItems.find(item => item.href === pathname || (item.href !== '/' && pathname.startsWith(item.href)));
      if (matchedItem && !allowed.includes(matchedItem.id)) {
        return { authorized: false, reason: 'forbidden_feature', redirectTo: '/dashboard' };
      }
    }

    return { authorized: true };
  }

  // If route is /dashboard
  if (pathname.startsWith('/dashboard')) {
    if (!state.isAuthenticated) {
      return { authorized: false, reason: 'unauthenticated', redirectTo: '/login' };
    }
    return { authorized: true };
  }

  return { authorized: true };
}
