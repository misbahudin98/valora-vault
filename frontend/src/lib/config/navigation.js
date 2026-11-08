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
    label: 'Sistem & Log',
    href: '/admin/system',
    auth: true,
    roles: ['admin'],
    group: 'Administrator',
    icon: 'server',
  },
];

/**
 * Filter menu items according to user auth state and sidebar display rules
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
      return state.hasRole(...item.roles);
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
