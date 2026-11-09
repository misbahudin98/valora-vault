<script>
  import './layout.css';
  import favicon from '$lib/assets/favicon.svg';
  import { goto, beforeNavigate } from '$app/navigation';
  import { page, updated } from '$app/state';
  import { api } from '$lib/api/client.js';
  import { authState } from '$lib/state/auth.svelte.js';
  import { themeState } from '$lib/state/theme.svelte.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import { canAccessPath } from '$lib/config/navigation.js';
  import AppSidebar from '$lib/components/layout/AppSidebar.svelte';
  import AppTopbar from '$lib/components/layout/AppTopbar.svelte';
  import MobileThumbDock from '$lib/components/layout/MobileThumbDock.svelte';
  import PageLoader from '$lib/components/ui/PageLoader.svelte';
  import ElementLoader from '$lib/components/ui/ElementLoader.svelte';
  import Toast from '$lib/components/ui/Toast.svelte';
  import { onMount } from 'svelte';

  let { children } = $props();
  let loggingOut = $state(false);
  let sidebarOpen = $state(false);

  // Standalone pages (Landing page & Login) without sidebar
  let isStandalonePage = $derived(page.url.pathname === '/' || page.url.pathname === '/login');

  // Route authorization evaluation
  let routeAccess = $derived(canAccessPath(page.url.pathname, authState));
  let isAuthorized = $derived(typeof routeAccess === 'boolean' ? routeAccess : !!routeAccess?.authorized);

  // Client-side route protection
  $effect(() => {
    if (authState.isLoaded && !isAuthorized && !isStandalonePage) {
      const target = routeAccess?.redirectTo || (authState.isAuthenticated ? '/dashboard' : '/login');
      if (page.url.pathname !== target) {
        goto(target);
      }
    }
  });

  // Seamless version upgrade on route transition
  beforeNavigate(({ willUnload, to }) => {
    if (!willUnload && to && updated.current) {
      // Force full page navigation to acquire newest hashed bundles
      location.href = to.url.href;
    }
  });

  // Background release notification
  $effect(() => {
    if (updated.current) {
      toastState.info(
        'Versi rilis baru sistem telah tersedia di server. Antarmuka akan diselaraskan otomatis saat Anda berpindah menu.',
        'Pembaruan Sistem'
      );
    }
  });

  onMount(async () => {
    themeState.syncDOM();
    authState.syncFromCookies();
    try {
      const res = await api.request('/api/v1/auth/me');
      if (res && res.data) {
        authState.setUser(res.data);
      }
    } catch (e) {
      if (e?.status === 401) {
        authState.clear();
      }
    } finally {
      authState.isLoaded = true;
    }
  });

  async function handleLogout() {
    loggingOut = true;
    try {
      await api.request('/api/v1/auth/logout', { method: 'POST' });
    } catch (e) {
      console.error('Logout error:', e);
    } finally {
      authState.clear();
      sidebarOpen = false;
      await goto('/login');
      loggingOut = false;
    }
  }

  function toggleSidebar() {
    sidebarOpen = !sidebarOpen;
  }
</script>

<svelte:head>
  <link rel="icon" href={favicon} />
</svelte:head>

<div class="min-h-screen bg-[#f8fafc] text-stone-800 dark:bg-[#090d16] dark:text-slate-100 font-sans antialiased selection:bg-indigo-500 selection:text-white relative overflow-x-clip transition-colors duration-200">
  <!-- Ambient Glow -->
  <div class="fixed top-0 left-1/4 w-96 h-96 bg-indigo-600/10 dark:bg-indigo-600/15 rounded-full blur-[128px] pointer-events-none -z-10"></div>
  <div class="fixed bottom-0 right-1/4 w-96 h-96 bg-cyan-500/10 dark:bg-cyan-500/15 rounded-full blur-[128px] pointer-events-none -z-10"></div>

  {#if authState.isLoaded && !isAuthorized && !isStandalonePage}
    <!-- Auto Reject Shield: Strictly blocks unauthorized document preview -->
    <div class="fixed inset-0 z-50 bg-white/95 dark:bg-[#090d16]/95 backdrop-blur-xl flex flex-col items-center justify-center p-6 text-center select-none" role="alert">
      <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-500/40 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-4 shadow-xl shadow-rose-950/20 animate-pulse">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>
      <div class="text-sm font-bold text-stone-900 dark:text-white mb-1">Akses Dibatasi</div>
      <div class="text-xs text-stone-600 dark:text-slate-400 max-w-xs mb-4">Mengalihkan peramban ke rute otorisasi yang sah...</div>
      <ElementLoader size="sm" variant="indigo" />
    </div>
  {:else if !authState.isLoaded && !isStandalonePage && (page.url.pathname.startsWith('/admin') || page.url.pathname.startsWith('/dashboard'))}
    <!-- Gentle Loading Viewport during initial session check -->
    <div class="fixed inset-0 z-50 bg-[#f8fafc] dark:bg-[#090d16] flex flex-col items-center justify-center p-6 space-y-3">
      <ElementLoader size="md" variant="indigo" />
      <span class="text-xs font-mono text-stone-500 dark:text-slate-400">Memverifikasi sesi aman...</span>
    </div>
  {:else if isStandalonePage}
    <!-- Standalone Viewport: Landing Page (/) & Login (/login) without Sidebar -->
    <main class="w-full min-h-screen">
      {@render children()}
    </main>
  {:else}
    <!-- App Workspace Viewport: Sidebar + Topbar + Content -->
    <div class="flex min-h-screen">
      <!-- Modular AppSidebar -->
      <AppSidebar
        isOpen={sidebarOpen}
        onclose={() => sidebarOpen = false}
      />

      <!-- Main App Content Area (Mobile-First Pro-Grade) -->
      <div class="flex-1 flex flex-col min-w-0 lg:pl-60">
        <!-- Modular AppTopbar with Theme Toggle & Quick Logout -->
        <AppTopbar
          ontoggle={toggleSidebar}
          onlogout={handleLogout}
          {loggingOut}
        />

        <!-- Page Viewport with safe bottom padding for Mobile Thumb Dock -->
        <main class="flex-1 p-2 sm:p-5 pb-16 sm:pb-5 bg-stone-50/50 dark:bg-slate-950/40">
          {@render children()}
        </main>

        <!-- Floating Thumb Action Dock (Mobile Only: 0-Scroll Instant Thumb Access) -->
        <MobileThumbDock ontoggle={toggleSidebar} />
      </div>
    </div>
  {/if}

  {#if loggingOut}
    <PageLoader fullscreen={true} size="lg" text="Mengamankan sesi dan keluar sistem..." variant="primary" />
  {/if}

  <!-- Universal Toast Notification Bus -->
  <Toast />
</div>
