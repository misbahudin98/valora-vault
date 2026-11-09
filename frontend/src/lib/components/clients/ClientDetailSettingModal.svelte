<script>
  import { api } from '$lib/api/client.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import Badge from '$lib/components/ui/Badge.svelte';
  import Modal from '$lib/components/ui/Modal.svelte';
  import ClientModal from '$lib/components/clients/ClientModal.svelte';

  let {
    show = false,
    projectId = null,
    onclose = () => {},
    onchange = () => {}
  } = $props();

  let project = $state(null);
  let loading = $state(true);
  let error = $state(null);

  // Active Role Tab
  let activeRoleId = $state(null);
  let activeRole = $derived(
    project?.roles?.find(r => r.id === activeRoleId) || project?.roles?.[0] || null
  );

  // Team members list for flexible PIC assignment
  const TEAM_MEMBERS = [
    'Muhammad Iqbal Murtadho',
    'Misbahudin'
  ];

  let usersList = $state([]);

  async function loadUsers() {
    try {
      const res = await api.request('/api/v1/admin/users?limit=100');
      usersList = res.data || [];
    } catch (err) {
      console.error('Failed to load users for PIC selection', err);
    }
  }

  // 10 Feature Capabilities master definition (Penanda Kapabilitas Fitur)
  const CAPABILITIES = [
    { key: 'has_create', label: 'Create', color: 'emerald' },
    { key: 'has_read', label: 'Read', color: 'blue' },
    { key: 'has_update', label: 'Update', color: 'amber' },
    { key: 'has_delete', label: 'Delete', color: 'rose' },
    { key: 'has_grafik', label: 'Grafik', color: 'indigo' },
    { key: 'has_box_dashboard', label: 'Box Dashboard', color: 'purple' },
    { key: 'has_import', label: 'Import (Excel, csv)', color: 'cyan' },
    { key: 'has_export', label: 'Export / Download (Excel, csv, pdf)', color: 'teal' },
    { key: 'has_filter', label: 'Filter data', color: 'slate' },
    { key: 'has_print', label: 'Print', color: 'stone' },
  ];

  // Nested Modals state
  let showEditProjectModal = $state(false);
  let submittingProjectEdit = $state(false);

  let showAddRoleModal = $state(false);
  let newRoleName = $state('');
  let submittingRole = $state(false);

  let showAddMenuModal = $state(false);
  let menuModalMode = $state('create'); // 'create' | 'edit'
  let targetMenuId = $state(null);
  let formMenuName = $state('');
  let formMenuDesc = $state('');
  let formMenuPic = $state('Muhammad Iqbal Murtadho');
  let formMenuPrice = $state(750000);
  let formCapabilities = $state({
    has_create: true,
    has_read: true,
    has_update: true,
    has_delete: true,
    has_grafik: false,
    has_box_dashboard: false,
    has_import: false,
    has_export: false,
    has_filter: true,
    has_print: false,
  });
  let submittingMenu = $state(false);

  let showAddTableModal = $state(false);
  let newTableName = $state('');
  let newTablePrice = $state(100000);
  let submittingTable = $state(false);

  let showEditFeaturePriceModal = $state(false);
  let targetFeatureKey = $state('landing');
  let targetFeatureLabel = $state('');
  let formFeaturePrice = $state(0);
  let formFeatureNote = $state('');
  let formFeatureCaps = $state({
    has_create: false,
    has_read: true,
    has_update: false,
    has_delete: false,
    has_grafik: false,
    has_box_dashboard: false,
    has_import: false,
    has_export: false,
    has_filter: false,
    has_print: false,
  });
  let submittingFeaturePrice = $state(false);

  // Master Billing Rates State (terhubung dengan Modul Master Tarif Standar)
  let masterRates = $state([]);

  let ratesMap = $state({
    landing: 2500000,
    login: 1000000,
    register: 1000000,
    menu_price: 750000,
    database_table_price: 100000,
    maintenance_menu_price: 150000,
  });
  let loadingRates = $state(false);

  async function loadMasterBillingRates() {
    try {
      loadingRates = true;
      const res = await api.request('/api/v1/admin/billing-rates');
      if (res?.rates_map) {
        ratesMap = {
          ...ratesMap,
          ...res.rates_map
        };
      }
      if (Array.isArray(res?.data)) {
        masterRates = res.data;
      }
    } catch (e) {
      console.warn('Gagal memuat tarif master:', e);
    } finally {
      loadingRates = false;
    }
  }

  // Watch show & projectId changes
  $effect(() => {
    if (show && projectId) {
      loadProjectDetail();
      loadMasterBillingRates();
    }
  });

  async function loadProjectDetail() {
    loading = true;
    error = null;
    try {
      loadUsers();
      const res = await api.request(`/api/v1/admin/clients/${projectId}`);
      if (res && res.data) {
        project = res.data;
        if (!activeRoleId && project.roles && project.roles.length > 0) {
          activeRoleId = project.roles[0].id;
        } else if (activeRoleId && !project.roles?.some(r => r.id === activeRoleId)) {
          activeRoleId = project.roles?.[0]?.id || null;
        }
      }
    } catch (e) {
      error = e?.message || 'Gagal memuat detail & setting proyek.';
      toastState.error(error, 'Koneksi Gagal');
    } finally {
      loading = false;
    }
  }

  // --- Project Edit Handler ---
  async function handleSaveProjectEdit(payload) {
    submittingProjectEdit = true;
    try {
      const res = await api.request(`/api/v1/admin/clients/${projectId}`, {
        method: 'PUT',
        body: JSON.stringify(payload)
      });
      toastState.success('Data proyek dan profil klien berhasil diperbarui.', 'Berhasil Disimpan');
      showEditProjectModal = false;
      await loadProjectDetail();
      onchange(res?.data || project);
    } catch (e) {
      toastState.error(e?.message || 'Gagal memperbarui data proyek.', 'Gagal');
    } finally {
      submittingProjectEdit = false;
    }
  }

  async function handleDeleteProjectFromModal() {
    if (!project) return;
    if (Number(project.paid_amount || 0) > 0) {
      toastState.error('Proyek yang sudah memiliki riwayat pembayaran tidak dapat dihapus.', 'Akses Dibatasi');
      return;
    }
    if (!confirm(`Apakah Anda yakin ingin menghapus proyek '${project.app_name}' secara permanen? Seluruh role dan menu terkait akan dihapus.`)) {
      return;
    }
    try {
      await api.request(`/api/v1/admin/clients/${projectId}`, { method: 'DELETE' });
      toastState.success(`Proyek ${project.app_name} berhasil dihapus.`, 'Terhapus');
      onclose();
      onchange(null);
    } catch (e) {
      toastState.error(e?.message || 'Gagal menghapus proyek.', 'Error');
    }
  }

  // --- Feature Pricing & Toggle Handlers ---
  async function handleToggleFeature(featureKey) {
    const newVal = !project[featureKey];
    project[featureKey] = newVal;
    try {
      const res = await api.request(`/api/v1/admin/clients/${projectId}`, {
        method: 'PUT',
        body: JSON.stringify({ [featureKey]: newVal })
      });
      await loadProjectDetail();
      toastState.success('Status komponen fitur proyek berhasil diperbarui.', 'Fitur Diperbarui');
      onchange(res?.data || project);
    } catch (e) {
      project[featureKey] = !newVal;
      toastState.error('Gagal memperbarui status fitur.', 'Error');
    }
  }

  async function handleToggleProjectFeatureCapability(featureKey, capKey) {
    const capsField = `${featureKey}_capabilities`;
    const currentCaps = { ...(project[capsField] || {}) };
    const newVal = !currentCaps[capKey];
    currentCaps[capKey] = newVal;
    project[capsField] = currentCaps;

    try {
      await api.request(`/api/v1/admin/clients/${projectId}`, {
        method: 'PUT',
        body: JSON.stringify({ [capsField]: currentCaps })
      });
      toastState.success(`Penanda kapabilitas "${capKey.replace('has_', '')}" pada ${featureKey} diperbarui.`, 'Kapabilitas Disimpan');
      onchange(project);
    } catch (e) {
      currentCaps[capKey] = !newVal;
      project[capsField] = currentCaps;
      toastState.error('Gagal memperbarui penanda kapabilitas fitur.', 'Error');
    }
  }

  function openEditFeaturePriceModal(key) {
    targetFeatureKey = key;
    const capsField = `${key}_capabilities`;
    const defaultCaps = {
      has_create: key !== 'landing',
      has_read: true,
      has_update: key === 'login',
      has_delete: false,
      has_grafik: false,
      has_box_dashboard: false,
      has_import: false,
      has_export: false,
      has_filter: false,
      has_print: false,
    };
    formFeatureCaps = { ...defaultCaps, ...(project?.[capsField] || {}) };

    if (key === 'landing') {
      targetFeatureLabel = 'Landing Page';
      formFeaturePrice = Number(project?.landing_price) || ratesMap.landing || 2500000;
      formFeatureNote = project?.landing_note || '';
    } else if (key === 'login') {
      targetFeatureLabel = 'Halaman Login';
      formFeaturePrice = Number(project?.login_price) || ratesMap.login || 1000000;
      formFeatureNote = project?.login_note || '';
    } else if (key === 'register') {
      targetFeatureLabel = 'Halaman Register';
      formFeaturePrice = Number(project?.register_price) || ratesMap.register || 1000000;
      formFeatureNote = project?.register_note || '';
    }
    showEditFeaturePriceModal = true;
  }

  async function handleSaveFeaturePrice(e) {
    e.preventDefault();
    submittingFeaturePrice = true;
    try {
      const payload = {};
      const capsField = `${targetFeatureKey}_capabilities`;
      payload[capsField] = formFeatureCaps;

      if (targetFeatureKey === 'landing') {
        payload.landing_price = Number(formFeaturePrice) || 0;
        payload.landing_note = formFeatureNote.trim() || null;
      } else if (targetFeatureKey === 'login') {
        payload.login_price = Number(formFeaturePrice) || 0;
        payload.login_note = formFeatureNote.trim() || null;
      } else if (targetFeatureKey === 'register') {
        payload.register_price = Number(formFeaturePrice) || 0;
        payload.register_note = formFeatureNote.trim() || null;
      }

      const res = await api.request(`/api/v1/admin/clients/${projectId}`, {
        method: 'PUT',
        body: JSON.stringify(payload)
      });
      toastState.success(`Tarif & kapabilitas ${targetFeatureLabel} berhasil disesuaikan.`, 'Tersimpan');
      showEditFeaturePriceModal = false;
      await loadProjectDetail();
      onchange(res?.data || project);
    } catch (e) {
      toastState.error('Gagal memperbarui pengaturan fitur.', 'Error');
    } finally {
      submittingFeaturePrice = false;
    }
  }

  // --- Role Handlers ---
  async function handleAddRole(e) {
    e.preventDefault();
    if (!newRoleName.trim()) return;
    submittingRole = true;
    try {
      const res = await api.request(`/api/v1/admin/projects/${projectId}/roles`, {
        method: 'POST',
        body: JSON.stringify({ role_name: newRoleName.trim() })
      });
      toastState.success(res.message, 'Role Ditambahkan');
      showAddRoleModal = false;
      newRoleName = '';
      await loadProjectDetail();
      if (res.data?.id) activeRoleId = res.data.id;
      onchange(project);
    } catch (e) {
      toastState.error(e?.message || 'Gagal menambahkan role.', 'Error');
    } finally {
      submittingRole = false;
    }
  }

  async function handleDeleteRole(roleId) {
    if (!confirm('Apakah Anda yakin ingin menghapus role ini beserta seluruh modul menunya?')) return;
    try {
      await api.request(`/api/v1/admin/roles/${roleId}`, { method: 'DELETE' });
      toastState.success('Role berhasil dihapus.', 'Dihapus');
      activeRoleId = null;
      await loadProjectDetail();
      onchange(project);
    } catch (e) {
      toastState.error(e?.message || 'Gagal menghapus role.', 'Error');
    }
  }

  // --- Menu Handlers ---
  function openCreateMenuModal() {
    if (!activeRole) {
      toastState.error('Pilih atau buat role terlebih dahulu.', 'Peringatan');
      return;
    }
    menuModalMode = 'create';
    targetMenuId = null;
    formMenuName = '';
    formMenuDesc = '';
    formMenuPic = project?.lead_pic || 'Muhammad Iqbal Murtadho';
    formCapabilities = {
      has_create: true,
      has_read: true,
      has_update: true,
      has_delete: true,
      has_grafik: false,
      has_box_dashboard: false,
      has_import: false,
      has_export: false,
      has_filter: true,
      has_print: false,
    };
    formMenuPrice = project?.project_type === 'maintenance'
      ? (ratesMap.maintenance_menu_price || 150000)
      : (ratesMap.menu_price || 750000);
    showAddMenuModal = true;
  }

  function openEditMenuModal(menu) {
    menuModalMode = 'edit';
    targetMenuId = menu.id;
    formMenuName = menu.menu_name;
    formMenuDesc = menu.menu_desc || '';
    formMenuPic = menu.pic || project?.lead_pic || 'Muhammad Iqbal Murtadho';
    formMenuPrice = Number(menu.price) || 750000;
    formCapabilities = {
      has_create: !!menu.has_create,
      has_read: !!menu.has_read,
      has_update: !!menu.has_update,
      has_delete: !!menu.has_delete,
      has_grafik: !!menu.has_grafik,
      has_box_dashboard: !!menu.has_box_dashboard,
      has_import: !!menu.has_import,
      has_export: !!menu.has_export,
      has_filter: !!menu.has_filter,
      has_print: !!menu.has_print,
    };
    showAddMenuModal = true;
  }

  async function handleSaveMenu(e) {
    e.preventDefault();
    if (!formMenuName.trim()) return;
    submittingMenu = true;
    try {
      const picUser = usersList.find(u => u.name === formMenuPic);
      const payload = {
        menu_name: formMenuName.trim(),
        menu_desc: formMenuDesc.trim() || null,
        pic: formMenuPic,
        pic_user_id: picUser?.id || null,
        price: Number(formMenuPrice) || 0,
        ...formCapabilities,
      };

      if (menuModalMode === 'create') {
        await api.request(`/api/v1/admin/roles/${activeRole.id}/menus`, {
          method: 'POST',
          body: JSON.stringify(payload)
        });
        toastState.success('Fitur menu baru berhasil ditambahkan.', 'Sukses');
      } else {
        await api.request(`/api/v1/admin/menus/${targetMenuId}`, {
          method: 'PUT',
          body: JSON.stringify(payload)
        });
        toastState.success('Fitur menu berhasil diperbarui.', 'Sukses');
      }
      showAddMenuModal = false;
      await loadProjectDetail();
      onchange(project);
    } catch (e) {
      toastState.error(e?.message || 'Gagal menyimpan menu.', 'Error');
    } finally {
      submittingMenu = false;
    }
  }

  async function handleQuickAssignPic(menu, newPic) {
    try {
      const picUser = usersList.find(u => u.name === newPic);
      const picUserId = picUser?.id || null;
      await api.request(`/api/v1/admin/menus/${menu.id}`, {
        method: 'PUT',
        body: JSON.stringify({ pic: newPic, pic_user_id: picUserId })
      });
      menu.pic = newPic;
      menu.pic_user_id = picUserId;
      toastState.success(`PIC modul '${menu.menu_name}' dialihkan ke ${newPic}.`, 'Penugasan PIC');
      onchange(project);
    } catch (e) {
      toastState.error('Gagal memperbarui penugasan PIC.', 'Error');
    }
  }

  async function handleToggleCapability(menu, capKey) {
    const newVal = !menu[capKey];
    menu[capKey] = newVal;
    try {
      await api.request(`/api/v1/admin/menus/${menu.id}`, {
        method: 'PUT',
        body: JSON.stringify({ [capKey]: newVal })
      });
    } catch (e) {
      menu[capKey] = !newVal; // rollback on error
      toastState.error('Gagal memperbarui kapabilitas fitur.', 'Error');
    }
  }

  async function handleToggleMenuDone(menu) {
    const isDone = !menu.is_done;
    try {
      const res = await api.request(`/api/v1/admin/menus/${menu.id}/toggle-done`, {
        method: 'POST',
        body: JSON.stringify({
          is_done: isDone,
          completed_date: isDone ? new Date().toISOString().split('T')[0] : null,
          pic: menu.pic
        })
      });
      menu.is_done = isDone;
      menu.completed_date = res.data?.completed_date;
      toastState.success(`Status ${menu.menu_name}: ${isDone ? 'Selesai' : 'Dalam Pengerjaan'}`, 'Status Menu');
      onchange(project);
    } catch (e) {
      toastState.error('Gagal mengubah status pengerjaan.', 'Error');
    }
  }

  async function handleDeleteMenu(menuId) {
    if (!confirm('Hapus fitur menu ini dari role?')) return;
    try {
      await api.request(`/api/v1/admin/menus/${menuId}`, { method: 'DELETE' });
      toastState.success('Fitur menu berhasil dihapus.', 'Dihapus');
      await loadProjectDetail();
      onchange(project);
    } catch (e) {
      toastState.error('Gagal menghapus menu.', 'Error');
    }
  }

  // --- Database Table Handlers ---
  async function handleAddTable(e) {
    e.preventDefault();
    if (!newTableName.trim()) return;
    submittingTable = true;
    try {
      await api.request(`/api/v1/admin/projects/${projectId}/database-tables`, {
        method: 'POST',
        body: JSON.stringify({
          table_name: newTableName.trim(),
          price: Number(newTablePrice) || 100000
        })
      });
      toastState.success('Tabel database baru berhasil ditambahkan.', 'Sukses');
      showAddTableModal = false;
      newTableName = '';
      await loadProjectDetail();
      onchange(project);
    } catch (e) {
      toastState.error('Gagal menambahkan tabel database.', 'Error');
    } finally {
      submittingTable = false;
    }
  }

  async function handleToggleTableDone(table) {
    const isDone = !table.is_done;
    try {
      await api.request(`/api/v1/admin/database-tables/${table.id}/toggle-done`, {
        method: 'POST',
        body: JSON.stringify({ is_done: isDone })
      });
      table.is_done = isDone;
      toastState.success(`Tabel ${table.table_name}: ${isDone ? 'Selesai' : 'Pending'}`, 'Status Tabel');
      onchange(project);
    } catch (e) {
      toastState.error('Gagal mengubah status tabel.', 'Error');
    }
  }

  async function handleDeleteTable(tableId) {
    if (!confirm('Hapus tabel ini dari skema proyek?')) return;
    try {
      await api.request(`/api/v1/admin/database-tables/${tableId}`, { method: 'DELETE' });
      toastState.success('Tabel database berhasil dihapus.', 'Dihapus');
      await loadProjectDetail();
      onchange(project);
    } catch (e) {
      toastState.error('Gagal menghapus tabel.', 'Error');
    }
  }

  function formatIDR(val) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      maximumFractionDigits: 0
    }).format(val || 0);
  }
</script>

<Modal
  {show}
  title={project ? `Detail & Setting: ${project.app_name}` : 'Detail & Setting Proyek'}
  subtitle={project?.client ? `${project.client.name} • Ruang Lingkup, Hak Akses, & Valuasi Finansial` : 'Valora Agency OS'}
  maxWidth="max-w-6xl"
  {onclose}
>
  {#if loading}
    <div class="p-8 space-y-4 animate-pulse">
      <div class="h-6 w-1/3 bg-slate-200 dark:bg-slate-800 rounded"></div>
      <div class="h-24 w-full bg-slate-100 dark:bg-slate-900 rounded-2xl"></div>
      <div class="grid grid-cols-4 gap-3">
        <div class="h-20 bg-slate-100 dark:bg-slate-900 rounded-xl"></div>
        <div class="h-20 bg-slate-100 dark:bg-slate-900 rounded-xl"></div>
        <div class="h-20 bg-slate-100 dark:bg-slate-900 rounded-xl"></div>
        <div class="h-20 bg-slate-100 dark:bg-slate-900 rounded-xl"></div>
      </div>
    </div>
  {:else if error}
    <div class="p-6 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs text-center space-y-2">
      <p class="font-bold">{error}</p>
      <button type="button" onclick={loadProjectDetail} class="px-3 py-1.5 rounded-lg bg-rose-600 text-white font-bold cursor-pointer">
        Coba Lagi
      </button>
    </div>
  {:else if project}
    <div class="space-y-5">
      <!-- Client Profile & App Header Banner (Matching index.html) -->
      <div class="bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-3">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
          <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-cyan-500 text-white font-extrabold text-lg flex items-center justify-center shrink-0 shadow-md shadow-indigo-500/20">
              {(project.client?.name || 'V').substring(0, 2).toUpperCase()}
            </div>
            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight">{project.client?.name}</h2>
                {#if project.status === 'Aktif'}
                  <Badge variant="success" size="sm">Aktif</Badge>
                {:else}
                  <Badge variant="warning" size="sm">{project.status}</Badge>
                {/if}
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                  {project.project_type === 'maintenance' ? 'Paket Pemeliharaan (SLA)' : 'Pengembangan Baru (Development)'}
                </span>
                <button
                  type="button"
                  onclick={() => (showEditProjectModal = true)}
                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-bold transition shadow-xs cursor-pointer active:scale-95"
                  title="Edit Data Profil Proyek & Klien"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  <span>Edit Data Proyek</span>
                </button>
                {#if Number(project.paid_amount || 0) <= 0}
                  <button
                    type="button"
                    onclick={handleDeleteProjectFromModal}
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 text-[11px] font-bold transition shadow-xs cursor-pointer active:scale-95"
                    title="Hapus Proyek (Hanya untuk proyek yang belum dibayar)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Hapus Proyek</span>
                  </button>
                {/if}
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {project.client?.address || 'Alamat belum disetel'} • Kontak: {project.client?.contact_person || project.client?.phone || '-'}
              </p>
              <h3 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 pt-0.5">
                {project.app_name}
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed max-w-2xl">
                {project.app_desc || 'Belum ada ringkasan deskripsi proyek.'}
              </p>
            </div>
          </div>

          <!-- Total Valuasi Finansial Card -->
          <div class="text-right shrink-0 p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 space-y-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Valuasi Proyek</span>
            <p class="text-lg sm:text-xl font-black text-slate-900 dark:text-white font-mono">{formatIDR(project.grand_total)}</p>
            <div class="text-[10px] font-mono text-slate-500 space-x-1">
              <span>Bayar: <b class="text-emerald-600 dark:text-emerald-400">{formatIDR(project.paid_amount)}</b></span>
              <span>•</span>
              <span>Sisa: <b class="text-rose-500">{formatIDR(Math.max(0, project.grand_total - project.paid_amount))}</b></span>
            </div>
            {#if project.github_url}
              <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
                <a
                  href={project.github_url}
                  target="_blank"
                  rel="noreferrer"
                  class="inline-flex items-center gap-1 text-[10px] font-mono text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                  <span>Lihat GitHub Repo</span>
                </a>
              </div>
            {/if}
          </div>
        </div>
      </div>

      <!-- Section 1: Ringkasan Valuasi Finansial Proyek & Setting Fitur Standar -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span>Rincian Valuasi Paket & Fitur Standar</span>
          </h3>
          <div class="flex items-center gap-2">
            <a
              href="/admin/tagihan?tab=rates"
              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition"
              title="Buka pengaturan acuan tarif master dan riwayat"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
              </svg>
              <span>Master Tarif ↗</span>
            </a>
            <a
              href={`/admin/tagihan/invoice/${project.id}`}
              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition"
              title="Buka lembar invoice resmi cetak"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8h4" />
              </svg>
              <span>Faktur Invoice ↗</span>
            </a>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
          <!-- Card 1: Landing Page -->
          <div class="p-3 rounded-xl border transition-all {project.use_landing ? 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 shadow-2xs' : 'bg-slate-100/60 dark:bg-slate-900/40 border-dashed border-slate-200 dark:border-slate-800 opacity-60'}">
            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  checked={project.use_landing}
                  onchange={() => handleToggleFeature('use_landing')}
                  class="w-4 h-4 rounded text-indigo-600 accent-indigo-600 cursor-pointer"
                />
                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">Landing Page</span>
              </label>
              {#if project.use_landing}
                <button
                  type="button"
                  onclick={() => openEditFeaturePriceModal('landing')}
                  class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg transition-colors cursor-pointer"
                  title="Ubah tarif Landing Page"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </button>
              {/if}
            </div>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
              <span class="text-[10px] {project.use_landing ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'}">
                {project.use_landing ? 'Aktif Digunakan' : 'Tidak Dipakai'}
              </span>
              <span class="text-xs font-mono font-black text-slate-900 dark:text-white">
                {project.use_landing ? formatIDR(project.landing_price) : 'Rp 0'}
              </span>
            </div>
            {#if project.use_landing && project.landing_note}
              <div class="mt-1.5 text-[10px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 p-1.5 rounded-lg border border-amber-200/60 dark:border-amber-800/60">
                <span class="font-bold">Tarif Khusus:</span> {project.landing_note}
              </div>
            {/if}
            {#if project.use_landing}
              <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Penanda Kapabilitas:</span>
                <div class="flex flex-wrap gap-1">
                  {#each CAPABILITIES as cap}
                    {@const isActive = !!(project.landing_capabilities && project.landing_capabilities[cap.key])}
                    <button
                      type="button"
                      onclick={() => handleToggleProjectFeatureCapability('landing', cap.key)}
                      class="px-1.5 py-0.5 rounded text-[9.5px] font-bold transition border cursor-pointer {isActive ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 shadow-2xs' : 'bg-white dark:bg-slate-900 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-800 hover:text-slate-600'}"
                      title={`Klik untuk toggle penanda ${cap.label}`}
                    >
                      {cap.label}
                    </button>
                  {/each}
                </div>
              </div>
            {/if}
          </div>

          <!-- Card 2: Halaman Login -->
          <div class="p-3 rounded-xl border transition-all {project.use_login ? 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 shadow-2xs' : 'bg-slate-100/60 dark:bg-slate-900/40 border-dashed border-slate-200 dark:border-slate-800 opacity-60'}">
            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  checked={project.use_login}
                  onchange={() => handleToggleFeature('use_login')}
                  class="w-4 h-4 rounded text-indigo-600 accent-indigo-600 cursor-pointer"
                />
                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">Halaman Login</span>
              </label>
              {#if project.use_login}
                <button
                  type="button"
                  onclick={() => openEditFeaturePriceModal('login')}
                  class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg transition-colors cursor-pointer"
                  title="Ubah tarif Login"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </button>
              {/if}
            </div>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
              <span class="text-[10px] {project.use_login ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'}">
                {project.use_login ? 'Aktif Digunakan' : 'Tidak Dipakai'}
              </span>
              <span class="text-xs font-mono font-black text-slate-900 dark:text-white">
                {project.use_login ? formatIDR(project.login_price) : 'Rp 0'}
              </span>
            </div>
            {#if project.use_login && project.login_note}
              <div class="mt-1.5 text-[10px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 p-1.5 rounded-lg border border-amber-200/60 dark:border-amber-800/60">
                <span class="font-bold">Tarif Khusus:</span> {project.login_note}
              </div>
            {/if}
            {#if project.use_login}
              <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Penanda Kapabilitas:</span>
                <div class="flex flex-wrap gap-1">
                  {#each CAPABILITIES as cap}
                    {@const isActive = !!(project.login_capabilities && project.login_capabilities[cap.key])}
                    <button
                      type="button"
                      onclick={() => handleToggleProjectFeatureCapability('login', cap.key)}
                      class="px-1.5 py-0.5 rounded text-[9.5px] font-bold transition border cursor-pointer {isActive ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 shadow-2xs' : 'bg-white dark:bg-slate-900 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-800 hover:text-slate-600'}"
                      title={`Klik untuk toggle penanda ${cap.label}`}
                    >
                      {cap.label}
                    </button>
                  {/each}
                </div>
              </div>
            {/if}
          </div>

          <!-- Card 3: Halaman Register -->
          <div class="p-3 rounded-xl border transition-all {project.use_register ? 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 shadow-2xs' : 'bg-slate-100/60 dark:bg-slate-900/40 border-dashed border-slate-200 dark:border-slate-800 opacity-60'}">
            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  checked={project.use_register}
                  onchange={() => handleToggleFeature('use_register')}
                  class="w-4 h-4 rounded text-indigo-600 accent-indigo-600 cursor-pointer"
                />
                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">Halaman Register</span>
              </label>
              {#if project.use_register}
                <button
                  type="button"
                  onclick={() => openEditFeaturePriceModal('register')}
                  class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 rounded-lg transition-colors cursor-pointer"
                  title="Ubah tarif Register"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </button>
              {/if}
            </div>
            <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
              <span class="text-[10px] {project.use_register ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400'}">
                {project.use_register ? 'Aktif Digunakan' : 'Tidak Dipakai'}
              </span>
              <span class="text-xs font-mono font-black text-slate-900 dark:text-white">
                {project.use_register ? formatIDR(project.register_price) : 'Rp 0'}
              </span>
            </div>
            {#if project.use_register && project.register_note}
              <div class="mt-1.5 text-[10px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 p-1.5 rounded-lg border border-amber-200/60 dark:border-amber-800/60">
                <span class="font-bold">Tarif Khusus:</span> {project.register_note}
              </div>
            {/if}
            {#if project.use_register}
              <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Penanda Kapabilitas:</span>
                <div class="flex flex-wrap gap-1">
                  {#each CAPABILITIES as cap}
                    {@const isActive = !!(project.register_capabilities && project.register_capabilities[cap.key])}
                    <button
                      type="button"
                      onclick={() => handleToggleProjectFeatureCapability('register', cap.key)}
                      class="px-1.5 py-0.5 rounded text-[9.5px] font-bold transition border cursor-pointer {isActive ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 shadow-2xs' : 'bg-white dark:bg-slate-900 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-800 hover:text-slate-600'}"
                      title={`Klik untuk toggle penanda ${cap.label}`}
                    >
                      {cap.label}
                    </button>
                  {/each}
                </div>
              </div>
            {/if}
          </div>

          <!-- Card 4: Subtotal Modul & DB -->
          <div class="p-3 rounded-xl border bg-slate-50 dark:bg-slate-950/50 border-slate-200 dark:border-slate-800 shadow-2xs space-y-1.5">
            <div>
              <span class="text-[10px] text-slate-500 font-semibold block">Subtotal Modul Peran</span>
              <p class="text-xs font-bold text-slate-900 dark:text-white font-mono">
                {formatIDR(project.roles?.reduce((sum, r) => sum + (r.menus?.reduce((msum, m) => msum + Number(m.price || 0), 0) || 0), 0) || 0)}
              </p>
            </div>
            <div class="pt-1.5 border-t border-slate-200 dark:border-slate-800">
              <span class="text-[10px] text-slate-500 font-semibold block">Subtotal Skema Database</span>
              <p class="text-xs font-bold text-slate-900 dark:text-white font-mono">
                {formatIDR(project.database_tables?.reduce((sum, t) => sum + Number(t.price || 0), 0) || 0)}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Role Selector Tabs -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
          <div>
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
              <span>Daftar Role Login & Otorisasi</span>
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Total {project.roles?.length || 0} Peran Terdaftar</p>
          </div>

          <button
            type="button"
            onclick={() => (showAddRoleModal = true)}
            class="min-h-[32px] px-3 py-1 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            <span>+ Tambah Role</span>
          </button>
        </div>

        <div class="flex flex-wrap gap-1.5 pt-1">
          {#if project.roles && project.roles.length > 0}
            {#each project.roles as role}
              {@const isActive = activeRole?.id === role.id}
              {@const menuCount = role.menus?.length || 0}
              {@const doneCount = role.menus?.filter(m => m.is_done)?.length || 0}
              <div class="inline-flex items-center gap-1 rounded-xl p-0.5 {isActive ? 'bg-indigo-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700'}">
                <button
                  type="button"
                  onclick={() => (activeRoleId = role.id)}
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer {isActive ? 'text-white' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900'}"
                >
                  <span>{role.role_name}</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {isActive ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'}">
                    {doneCount}/{menuCount}
                  </span>
                </button>
                <button
                  type="button"
                  onclick={() => handleDeleteRole(role.id)}
                  class="p-1 rounded-md text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-950/70 transition cursor-pointer"
                  title="Hapus Role"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>
            {/each}
          {:else}
            <p class="text-xs text-slate-400 italic">Belum ada role terkonfigurasi pada proyek ini.</p>
          {/if}
        </div>
      </div>

      <!-- Section 3: Daftar Menu & Checklist 10 Kapabilitas -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
          <div>
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
              <span>Daftar Menu Fitur: <span class="text-indigo-600 dark:text-indigo-400">{activeRole?.role_name || '-'}</span></span>
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
              Total {activeRole?.menus?.length || 0} Modul Menu Terdaftar
            </p>
          </div>

          <button
            type="button"
            onclick={openCreateMenuModal}
            class="min-h-[32px] px-3 py-1 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            <span>+ Tambah Menu Fitur</span>
          </button>
        </div>

        {#if activeRole?.menus && activeRole.menus.length > 0}
          <div class="space-y-3">
            {#each activeRole.menus as menu}
              <div class="p-3.5 rounded-xl border transition-all {menu.is_done ? 'bg-emerald-50/20 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-800/60' : 'bg-slate-50/60 dark:bg-slate-950/40 border-slate-200 dark:border-slate-800'} space-y-3">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                  <div class="flex items-start gap-2.5">
                    <input
                      type="checkbox"
                      checked={menu.is_done}
                      onchange={() => handleToggleMenuDone(menu)}
                      class="w-4 h-4 mt-0.5 rounded text-emerald-600 accent-emerald-600 cursor-pointer"
                      title="Tandai menu selesai"
                    />
                    <div>
                      <div class="flex items-center gap-2">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white {menu.is_done ? 'line-through text-slate-400' : ''}">
                          {menu.menu_name}
                        </h4>
                        {#if menu.is_done}
                          <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                            Selesai {menu.completed_date || ''}
                          </span>
                        {/if}
                      </div>
                      {#if menu.menu_desc}
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{menu.menu_desc}</p>
                      {/if}
                    </div>
                  </div>

                  <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <!-- Flexible PIC Assignment Dropdown -->
                    <div class="flex items-center gap-1 bg-white dark:bg-slate-900 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs shadow-2xs">
                      <span class="text-[10px] font-bold text-slate-400 uppercase">PIC:</span>
                      <select
                        value={menu.pic || 'Muhammad Iqbal Murtadho'}
                        onchange={(e) => handleQuickAssignPic(menu, e.target.value)}
                        class="bg-transparent text-[11px] font-bold text-indigo-600 dark:text-indigo-400 focus:outline-none cursor-pointer"
                      >
                        {#if usersList.length === 0}
                          {#each TEAM_MEMBERS as member}
                            <option value={member}>{member}</option>
                          {/each}
                        {:else}
                          {#each usersList as user}
                            <option value={user.name}>{user.name} {user.jabatan ? `(${user.jabatan})` : ''}</option>
                          {/each}
                        {/if}
                      </select>
                    </div>

                    <span class="font-mono font-bold text-xs text-slate-900 dark:text-white px-2 py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs">
                      {formatIDR(menu.price)}
                    </span>

                    <button
                      type="button"
                      onclick={() => openEditMenuModal(menu)}
                      class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                      title="Edit Menu"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </button>
                    <button
                      type="button"
                      onclick={() => handleDeleteMenu(menu.id)}
                      class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer"
                      title="Hapus Menu"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </div>

                <!-- 10 Capability Badges Pill List -->
                <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800/80">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">Kapabilitas:</span>
                    {#each CAPABILITIES as cap}
                      {@const isActive = !!menu[cap.key]}
                      <button
                        type="button"
                        onclick={() => handleToggleCapability(menu, cap.key)}
                        class="px-2 py-0.5 rounded-md text-[10px] font-bold transition border cursor-pointer {isActive ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800' : 'bg-white dark:bg-slate-900 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-800 hover:text-slate-600'}"
                        title={`Klik untuk toggle kapabilitas ${cap.label}`}
                      >
                        {cap.label}
                      </button>
                    {/each}
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {:else}
          <div class="p-8 text-center bg-slate-50 dark:bg-slate-950/40 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
            <p class="text-xs text-slate-400">Belum ada fitur menu pada role ini.</p>
            <button
              type="button"
              onclick={openCreateMenuModal}
              class="mt-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 underline cursor-pointer"
            >
              + Tambah Menu Pertama
            </button>
          </div>
        {/if}
      </div>

      <!-- Section 4: Skema Tabel Basis Data Proyek -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-2xs space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
          <div>
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3" /></svg>
              <span>Daftar Tabel Database Project (@{formatIDR(ratesMap.database_table_price)} / tabel)</span>
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
              Total {project.database_tables?.length || 0} Tabel Terdaftar ({formatIDR(project.database_tables?.reduce((sum, t) => sum + Number(t.price || 0), 0) || 0)})
            </p>
          </div>

          <button
            type="button"
            onclick={() => { newTablePrice = ratesMap.database_table_price || 100000; showAddTableModal = true; }}
            class="min-h-[32px] px-3 py-1 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            <span>+ Tambah Tabel Database</span>
          </button>
        </div>

        {#if project.database_tables && project.database_tables.length > 0}
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
            {#each project.database_tables as table}
              <div class="flex items-center justify-between p-2.5 rounded-xl border {table.is_done ? 'bg-emerald-50/20 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-800/60' : 'bg-slate-50 dark:bg-slate-950/50 border-slate-200 dark:border-slate-800'} text-xs">
                <label class="flex items-center gap-2 cursor-pointer select-none min-w-0">
                  <input
                    type="checkbox"
                    checked={table.is_done}
                    onchange={() => handleToggleTableDone(table)}
                    class="w-3.5 h-3.5 rounded text-emerald-600 accent-emerald-600 cursor-pointer"
                  />
                  <span class="font-mono font-bold text-slate-800 dark:text-slate-200 truncate {table.is_done ? 'line-through text-slate-400' : ''}">
                    {table.table_name}
                  </span>
                </label>

                <div class="flex items-center gap-2 shrink-0">
                  <span class="font-mono text-[11px] text-slate-500">{formatIDR(table.price)}</span>
                  <button
                    type="button"
                    onclick={() => handleDeleteTable(table.id)}
                    class="p-1 rounded text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer"
                    title="Hapus Tabel"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                  </button>
                </div>
              </div>
            {/each}
          </div>
        {:else}
          <p class="text-xs text-slate-400 italic">Belum ada tabel database terdaftar pada proyek ini.</p>
        {/if}
      </div>
    </div>
  {/if}

  {#snippet footer()}
    <div class="flex items-center justify-between w-full">
      <div class="text-[11px] text-slate-500 dark:text-slate-400">
        Total {project?.roles?.length || 0} Role • {project?.roles?.reduce((sum, r) => sum + (r.menus?.length || 0), 0) || 0} Modul Menu • {project?.database_tables?.length || 0} Tabel Basis Data
      </div>
      <button
        type="button"
        onclick={onclose}
        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer"
      >
        Tutup
      </button>
    </div>
  {/snippet}
</Modal>

<!-- Sub-Modal 1: Tambah Role Baru -->
<Modal
  show={showAddRoleModal}
  title="Tambah Role Login Baru"
  subtitle="Daftarkan hak akses atau jenis pengguna aplikasi"
  maxWidth="max-w-md"
  zIndex="z-[60]"
  onclose={() => (showAddRoleModal = false)}
>
  <form id="add-role-form" onsubmit={handleAddRole} class="space-y-3">
    <div>
      <label for="new-role-name-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Role <span class="text-rose-500">*</span></label>
      <input
        id="new-role-name-input"
        type="text"
        bind:value={newRoleName}
        placeholder="Contoh: Petugas Lapangan, Kurir, Bendahara"
        class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
        required
      />
    </div>
  </form>

  {#snippet footer()}
    <div class="flex items-center justify-end gap-2">
      <button
        type="button"
        onclick={() => (showAddRoleModal = false)}
        disabled={submittingRole}
        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300"
      >
        Batal
      </button>
      <button
        type="submit"
        form="add-role-form"
        disabled={submittingRole}
        class="px-4 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 shadow-xs"
      >
        {submittingRole ? 'Menyimpan...' : 'Tambah Role'}
      </button>
    </div>
  {/snippet}
</Modal>

<!-- Sub-Modal 2: Tambah / Edit Menu Fitur -->
<Modal
  show={showAddMenuModal}
  title={menuModalMode === 'create' ? `Tambah Menu Baru (${activeRole?.role_name})` : `Edit Menu Fitur`}
  subtitle="Konfigurasi rincian kapabilitas Menu Aplikasi / Modul Kerja"
  maxWidth="max-w-xl"
  zIndex="z-[60]"
  onclose={() => (showAddMenuModal = false)}
>
  <form id="menu-feature-form" onsubmit={handleSaveMenu} class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label for="form-menu-name-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Nama Menu / Modul Kerja <span class="text-rose-500">*</span>
        </label>
        <input
          id="form-menu-name-input"
          type="text"
          bind:value={formMenuName}
          placeholder="Contoh: Kelola Tagihan & Invoice"
          class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-semibold"
          required
        />
      </div>

      <div>
        <label for="form-menu-pic-select" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Penugasan PIC Developer
        </label>
        <select
          id="form-menu-pic-select"
          bind:value={formMenuPic}
          class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-semibold"
        >
          {#if usersList.length === 0}
            {#each TEAM_MEMBERS as member}
              <option value={member}>{member}</option>
            {/each}
          {:else}
            {#each usersList as user}
              <option value={user.name}>{user.name} {user.jabatan ? `(${user.jabatan})` : ''}</option>
            {/each}
          {/if}
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <div class="flex items-center justify-between mb-1">
          <label for="form-menu-price-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
            Tarif Menu Modul (IDR)
          </label>
          <button
            type="button"
            onclick={() => (formMenuPrice = ratesMap.menu_price || 750000)}
            class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-mono font-bold cursor-pointer"
            title="Terapkan tarif acuan standar"
          >
            Acuan Standar: {formatIDR(ratesMap.menu_price || 750000)}
          </button>
        </div>
        <input
          id="form-menu-price-input"
          type="number"
          step="10000"
          bind:value={formMenuPrice}
          placeholder="750000"
          class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono font-semibold"
        />
      </div>

      <div>
        <label for="form-menu-desc-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Singkat</label>
        <input
          id="form-menu-desc-input"
          type="text"
          bind:value={formMenuDesc}
          placeholder="Deskripsi fungsional..."
          class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
        />
      </div>
    </div>

    <!-- 10 Feature Capabilities Checkboxes in Modal Form -->
    <div class="p-3 bg-slate-50 dark:bg-slate-950/70 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
      <div class="flex items-center justify-between">
        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
          Penanda 10 Kapabilitas Fitur Modul Kerja:
        </h4>
        <span class="text-[10px] font-mono font-bold text-indigo-600 dark:text-indigo-400">
          {Object.values(formCapabilities).filter(Boolean).length} / 10 Aktif
        </span>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        {#each CAPABILITIES as cap}
          <label class="flex items-center gap-1.5 p-2 rounded-lg border cursor-pointer transition {formCapabilities[cap.key] ? 'bg-indigo-50/70 dark:bg-indigo-950/40 border-indigo-300 dark:border-indigo-700 text-indigo-900 dark:text-indigo-200 font-semibold' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'} text-xs">
            <input
              type="checkbox"
              bind:checked={formCapabilities[cap.key]}
              class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer shrink-0"
            />
            <span class="truncate">{cap.label}</span>
          </label>
        {/each}
      </div>
    </div>
  </form>

  {#snippet footer()}
    <div class="flex items-center justify-end gap-2">
      <button
        type="button"
        onclick={() => (showAddMenuModal = false)}
        disabled={submittingMenu}
        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300"
      >
        Batal
      </button>
      <button
        type="submit"
        form="menu-feature-form"
        disabled={submittingMenu}
        class="px-4 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-500 shadow-xs"
      >
        {submittingMenu ? 'Menyimpan...' : (menuModalMode === 'create' ? 'Tambah Menu' : 'Simpan Perubahan')}
      </button>
    </div>
  {/snippet}
</Modal>

<!-- Sub-Modal 3: Tambah Tabel Database -->
<Modal
  show={showAddTableModal}
  title="Tambah Tabel Database Baru"
  subtitle={`Daftarkan nama tabel skema basis data proyek (@${formatIDR(ratesMap.database_table_price || 100000)})`}
  maxWidth="max-w-md"
  zIndex="z-[60]"
  onclose={() => (showAddTableModal = false)}
>
  <form id="add-table-form" onsubmit={handleAddTable} class="space-y-3">
    <div>
      <label for="new-table-name-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Tabel Database <span class="text-rose-500">*</span></label>
      <input
        id="new-table-name-input"
        type="text"
        bind:value={newTableName}
        placeholder="Contoh: inventory_mutations"
        class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono"
        required
      />
    </div>

    <div>
      <div class="flex items-center justify-between mb-1">
        <label for="new-table-price-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Tarif Tabel (IDR)</label>
        <button
          type="button"
          onclick={() => (newTablePrice = ratesMap.database_table_price || 100000)}
          class="text-[10px] text-cyan-600 dark:text-cyan-400 hover:underline font-mono cursor-pointer"
          title="Gunakan tarif master standar"
        >
          Acuan: {formatIDR(ratesMap.database_table_price || 100000)}
        </button>
      </div>
      <input
        id="new-table-price-input"
        type="number"
        step="25000"
        bind:value={newTablePrice}
        placeholder="100000"
        class="w-full text-xs px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono font-semibold"
      />
    </div>
  </form>

  {#snippet footer()}
    <div class="flex items-center justify-end gap-2">
      <button
        type="button"
        onclick={() => (showAddTableModal = false)}
        disabled={submittingTable}
        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300"
      >
        Batal
      </button>
      <button
        type="submit"
        form="add-table-form"
        disabled={submittingTable}
        class="px-4 py-1.5 rounded-lg text-xs font-bold bg-cyan-600 text-white hover:bg-cyan-500 shadow-xs"
      >
        {submittingTable ? 'Menyimpan...' : 'Tambah Tabel'}
      </button>
    </div>
  {/snippet}
</Modal>

<!-- Sub-Modal 4: Sesuaikan Tarif Fitur Standar -->
<Modal
  show={showEditFeaturePriceModal}
  title={`Sesuaikan Tarif: ${targetFeatureLabel}`}
  subtitle="Penetapan Tarif Khusus untuk Project Klien Ini"
  maxWidth="max-w-md"
  zIndex="z-[60]"
  onclose={() => (showEditFeaturePriceModal = false)}
>
  <form id="edit-feature-price-form" onsubmit={handleSaveFeaturePrice} class="space-y-4">
    <div>
      <div class="flex items-center justify-between mb-1">
        <label for="feature-price-input" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
          Nominal Tarif Proyek (IDR)
        </label>
        <button
          type="button"
          onclick={() => (formFeaturePrice = ratesMap[targetFeatureKey] || 0)}
          class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-mono cursor-pointer"
          title="Terapkan tarif master standar"
        >
          Acuan Standar: {formatIDR(ratesMap[targetFeatureKey] || 0)}
        </button>
      </div>
      <input
        id="feature-price-input"
        type="number"
        min="0"
        step="50000"
        bind:value={formFeaturePrice}
        class="w-full px-3 py-2 text-xs font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        required
      />
    </div>

    <div>
      <label for="feature-note-input" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
        Keterangan Alasan Perubahan Tarif (Opsional)
      </label>
      <input
        id="feature-note-input"
        type="text"
        bind:value={formFeatureNote}
        placeholder="Contoh: Diskon khusus paket bundling / negosiasi klien"
        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
      />
    </div>

    <!-- 10 Feature Capabilities Checkboxes for Landing, Login, or Register -->
    <div class="p-3 bg-slate-50 dark:bg-slate-950/70 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
          Penanda Kapabilitas Fitur ({targetFeatureLabel}):
        </span>
        <span class="text-[10px] font-mono font-bold text-indigo-600 dark:text-indigo-400">
          {Object.values(formFeatureCaps).filter(Boolean).length} / 10 Aktif
        </span>
      </div>
      <div class="grid grid-cols-2 gap-2">
        {#each CAPABILITIES as cap}
          <label class="flex items-center gap-1.5 p-2 rounded-lg border cursor-pointer transition {formFeatureCaps[cap.key] ? 'bg-indigo-50/70 dark:bg-indigo-950/40 border-indigo-300 dark:border-indigo-700 text-indigo-900 dark:text-indigo-200 font-semibold' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400'} text-xs">
            <input
              type="checkbox"
              bind:checked={formFeatureCaps[cap.key]}
              class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer shrink-0"
            />
            <span class="truncate">{cap.label}</span>
          </label>
        {/each}
      </div>
    </div>
  </form>

  {#snippet footer()}
    <div class="flex items-center justify-end gap-2">
      <button
        type="button"
        onclick={() => (showEditFeaturePriceModal = false)}
        disabled={submittingFeaturePrice}
        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
      >
        Batal
      </button>
      <button
        type="submit"
        form="edit-feature-price-form"
        disabled={submittingFeaturePrice}
        class="px-4 py-1.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition shadow-xs cursor-pointer disabled:opacity-50"
      >
        {submittingFeaturePrice ? 'Menyimpan...' : 'Simpan Tarif'}
      </button>
    </div>
  {/snippet}
</Modal>

<!-- Sub-Modal 5: Edit Proyek & Profil Klien Lengkap -->
<ClientModal
  show={showEditProjectModal}
  mode="edit"
  {project}
  zIndex="z-[60]"
  onclose={() => (showEditProjectModal = false)}
  onsave={handleSaveProjectEdit}
  submitting={submittingProjectEdit}
/>
