<script>
  import { onMount, onDestroy } from 'svelte';
  import { api } from '$lib/api/client.js';
  import { authState } from '$lib/state/auth.svelte.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import { goto } from '$app/navigation';
  import gsap from 'gsap';

  let formContainer = $state(null);
  let ctx = null;

  onMount(() => {
    // Pre-warm CSRF cookie and session before user submits credentials
    api.getCsrfCookie();
  });

  $effect(() => {
    if (formContainer && typeof window !== 'undefined') {
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (prefersReducedMotion) return;

      ctx = gsap.context(() => {
        gsap.fromTo(
          '.gsap-login-card',
          { y: 16, opacity: 0, scale: 0.98 },
          { y: 0, opacity: 1, scale: 1, duration: 0.45, ease: 'power2.out', clearProps: 'transform,opacity' }
        );
      }, formContainer);
    }
  });

  onDestroy(() => {
    if (ctx) ctx.revert();
  });

  let email = $state('admin@hyperion-vault.test');
  let password = $state('password123');
  let showPassword = $state(false);
  let errors = $state({});
  let loading = $state(false);

  const testAccounts = [
    { role: 'Super Admin', email: 'admin@hyperion-vault.test', badge: 'Admin' },
    { role: 'Pengguna', email: 'user@hyperion-vault.test', badge: 'User' },
  ];

  function selectAccount(acc) {
    email = acc.email;
    password = 'password123';
    errors = {};
  }

  async function handleLogin(e) {
    e.preventDefault();
    errors = {};

    loading = true;
    try {
      const res = await api.request('/api/v1/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password })
      });

      if (res.token && typeof window !== 'undefined') {
        try {
          localStorage.setItem('hyperion_token', res.token);
        } catch {}
      }

      authState.setUser(res.user);
      toastState.success(`Selamat datang, ${res.user?.name || 'Pengguna'}!`);
      goto('/dashboard');
    } catch (err) {
      if (err.status === 422 && err.errors) {
        errors = {
          email: err.errors.email?.[0],
          password: err.errors.password?.[0],
        };
      } else {
        errors = { general: err.message || 'Login gagal.' };
      }
    } finally {
      loading = false;
    }
  }
</script>

<div bind:this={formContainer} class="relative w-full max-w-[420px] sm:max-w-[460px]">
  <form
    onsubmit={handleLogin}
    action="javascript:void(0);"
    class="gsap-login-card w-full p-6 sm:p-7 bg-white/80 dark:bg-[#0c1322]/80 bg-gradient-to-br from-indigo-500/[0.04] via-transparent to-cyan-500/[0.03] dark:from-indigo-500/[0.08] dark:to-cyan-500/[0.04] backdrop-blur-2xl border border-white/60 dark:border-white/10 rounded-3xl shadow-xl dark:shadow-2xl flex flex-col gap-4 text-slate-800 dark:text-slate-200 relative overflow-hidden before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/45 dark:before:via-white/20 before:to-transparent"
  >
    <!-- Header -->
    <div class="text-center space-y-1 mb-1">
      <div class="w-12 h-12 mx-auto rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-400 flex items-center justify-center text-white mb-2 shadow-lg shadow-indigo-500/30">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>
      <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight">Login Portal Sistem</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Autentikasi Multi-Role Sanctum & Signed JWT</p>
    </div>

    <!-- 1-Click Role Quick Switcher -->
    <div class="p-2.5 bg-slate-100/90 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-xl">
      <div class="text-[10px] font-bold text-slate-600 dark:text-indigo-400 mb-1.5 uppercase tracking-wider font-mono flex items-center justify-between">
        <span>Akun Demo Cepat (1-Klik)</span>
        <span class="text-slate-400 dark:text-slate-500 font-normal">pass: password123</span>
      </div>
      <div class="grid grid-cols-2 gap-2">
        {#each testAccounts as acc}
          <button
            type="button"
            onclick={() => selectAccount(acc)}
            class="px-2.5 py-1.5 text-xs font-bold rounded-lg border transition-all text-left flex flex-col justify-between truncate cursor-pointer active:scale-95 {email === acc.email ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm dark:bg-indigo-700 dark:text-white dark:border-indigo-500' : 'bg-white hover:bg-slate-50 dark:bg-slate-950 dark:hover:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-800'}"
            title="{acc.role} ({acc.email})"
          >
            <span class="font-bold truncate">{acc.role}</span>
            <span class="text-[9px] opacity-75 font-mono truncate">{acc.badge}</span>
          </button>
        {/each}
      </div>
    </div>

    {#if errors.general}
      <div id="login-error" class="text-xs text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/50 p-3 rounded-xl flex items-center gap-2.5">
        <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{errors.general}</span>
      </div>
    {/if}

    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label for="email" class="text-xs font-bold text-slate-800 dark:text-slate-200">Email Pengguna</label>
        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900">* Wajib Diisi</span>
      </div>
      <input
        id="email"
        type="text"
        bind:value={email}
        placeholder="admin@hyperion-vault.test"
        class="w-full px-3.5 py-2.5 min-h-[44px] bg-slate-50 dark:bg-slate-900/90 border border-slate-300 dark:border-slate-700 focus:border-indigo-500 rounded-xl text-base sm:text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition shadow-xs dark:shadow-inner"
      />
      {#if errors.email}
        <span class="text-[11px] text-rose-500 dark:text-rose-400 mt-1.5 block font-medium">{errors.email}</span>
      {/if}
    </div>

    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label for="password" class="text-xs font-bold text-slate-800 dark:text-slate-200">Kata Sandi</label>
        <span class="text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/60 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900">* Wajib Diisi</span>
      </div>
      <div class="relative">
        <input
          id="password"
          type={showPassword ? 'text' : 'password'}
          bind:value={password}
          placeholder="••••••••"
          class="w-full pl-3.5 pr-12 py-2.5 min-h-[44px] bg-slate-50 dark:bg-slate-900/90 border border-slate-300 dark:border-slate-700 focus:border-indigo-500 rounded-xl text-base sm:text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition shadow-xs dark:shadow-inner"
        />
        <button
          type="button"
          id="toggle-password-visibility"
          onclick={() => (showPassword = !showPassword)}
          aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
          class="min-h-[44px] min-w-[44px] absolute inset-y-0 right-0 pr-3 flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition focus:outline-none cursor-pointer"
        >
          {#if showPassword}
            <!-- Eye Off Icon -->
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
          {:else}
            <!-- Eye Icon -->
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          {/if}
        </button>
      </div>
      {#if errors.password}
        <span class="text-[11px] text-rose-500 dark:text-rose-400 mt-1.5 block font-medium">{errors.password}</span>
      {/if}
    </div>

    <!-- Explicit Tactile Primary Submit Button -->
    <button
      id="submit-btn"
      type="submit"
      disabled={loading}
      class="w-full mt-2 min-h-[44px] py-2.5 px-4 bg-gradient-to-r from-indigo-600 via-indigo-500 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white font-bold text-xs sm:text-sm rounded-xl shadow-lg shadow-indigo-600/30 hover:scale-[1.01] active:scale-[0.98] border border-indigo-400/30 transition duration-150 disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
    >
      {#if loading}
        <div class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
        <span>Memverifikasi...</span>
      {:else}
        <span>Masuk Sistem</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
      {/if}
    </button>

    <div class="text-center pt-1">
      <a href="/" class="text-[11px] text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition inline-flex items-center gap-1.5 font-medium px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 border border-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-slate-800">
        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>Kembali ke Halaman Utama</span>
      </a>
    </div>
  </form>
</div>
