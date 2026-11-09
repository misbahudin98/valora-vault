<script>
  import LoginForm from '$lib/components/features/auth/LoginForm.svelte';
</script>

<svelte:head>
  <title>Masuk Akun — {import.meta.env.VITE_APP_NAME || 'Valora Vault'}</title>
  <meta name="description" content="Masuk ke portal aman Valora Vault dengan otentikasi sesi Sanctum dan isolasi perangkat multi-role." />
  <meta name="robots" content="index, follow" />
</svelte:head>

<div class="min-h-[85vh] w-full flex items-center justify-center p-6">
  <LoginForm />
</div>
