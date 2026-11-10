<script>
  import Modal from '$lib/components/ui/Modal.svelte';
  import { api } from '$lib/api/client.js';
  import { toastState } from '$lib/state/toast.svelte.js';

  let {
    show = false,
    mode = 'create', // 'create' | 'edit'
    project = null,
    existingProjects = [],
    existingClients = [],
    zIndex = 'z-50',
    onclose = () => {},
    onsave = () => {},
    onprojectready = () => {},
    submitting = false
  } = $props();

  // Helper konversi Proper Case (Ucwords)
  function toProperCase(str) {
    if (!str) return '';
    return str
      .toLowerCase()
      .split(' ')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  }

  const defaultRoleNames = [
    'Admin Utama',
    'Manager Operasional',
    'Staff Lapangan',
    'Auditor Keuangan',
    'Kepala Cabang',
    'Staf Kasir',
    'Supervisor',
    'Direksi'
  ];

  const teamMembers = [
    'Muhammad Iqbal Murtadho',
    'Misbahudin'
  ];

  let usersList = $state([]);
  let leadPicId = $state(null);
  let assignedWorkerIds = $state([]);

  async function loadUsers() {
    try {
      const res = await api.request('/api/v1/admin/users?limit=100');
      usersList = res.data || [];
    } catch (err) {
      console.error('Failed to load users for PIC assignment', err);
    }
  }

  // Mode intake saat create: 'new_app' | 'existing_app'
  let appTypeChoice = $state('new_app');

  // Fields untuk Aplikasi Baru
  let inputDate = $state(new Date().toISOString().split('T')[0]);
  let clientName = $state('');
  let clientContact = $state('');
  let clientAddress = $state('');
  let clientEmail = $state('');
  let clientPhone = $state('');
  let leadPic = $state('Muhammad Iqbal Murtadho');
  let githubUrl = $state('');
  let appName = $state('');
  let industry = $state('');
  let useLanding = $state(true);
  let useLogin = $state(true);
  let useRegister = $state(false);
  let useAppCore = $state(true);
  let appDesc = $state('');
  let roleType = $state('admin_only'); // 'admin_only' | 'multi_role'
  let roleCount = $state(2);
  let dynamicRoleNames = $state(['Admin Utama', 'Manager Operasional']);

  // Fields untuk Aplikasi Lama (Client Existing)
  let selectedExistingProjectId = $state('');
  let selectedProjectDetail = $state(null);
  let loadingProjectDetail = $state(false);
  let existingActionType = $state('add_role'); // 'add_role' | 'add_menu' | 'maintenance'

  // Sub-form 1: Tambah Role Baru
  let newRoleName = $state('');
  let newRoleMenu = $state('');

  // Sub-form 2: Tambah Menu Modul
  let targetRoleId = $state('');
  let newMenuName = $state('');
  let newMenuDesc = $state('');
  let newMenuPrice = $state(250000);
  let newMenuDone = $state(false);

  // Sub-form 3: Maintenance
  let selectedMaintMenus = $state([]);
  let maintDesc = $state('');
  let maintPic = $state('Misbahudin');
  let maintDate = $state(new Date().toISOString().split('T')[0]);
  let maintCost = $state(150000);

  // Fields saat Edit Mode
  let projectName = $state('');
  let projectType = $state('development');
  let status = $state('Aktif');
  let landingPrice = $state(500000);
  let loginPrice = $state(350000);
  let registerPrice = $state(350000);
  let paidAmount = $state(0);
  let roleTags = $state(['Super Admin', 'Staff Operasional']);
  let newRoleInput = $state('');

  // Form error tracking & internal submitting
  let errors = $state({});
  let internalSubmitting = $state(false);

  // Master Billing Rates State (terhubung dengan modul Tagihan & Master Tarif Standar)
  let ratesMap = $state({
    landing: 500000,
    login: 350000,
    register: 350000,
    menu_price: 250000,
    database_table_price: 150000,
    maintenance_menu_price: 150000
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
        // Sinkronisasi nilai default awal jika belum diset kustom
        if (mode === 'create') {
          newMenuPrice = ratesMap.menu_price || 250000;
          if (appTypeChoice === 'new_app') {
            landingPrice = ratesMap.landing || 500000;
            loginPrice = ratesMap.login || 350000;
            registerPrice = ratesMap.register || 350000;
          }
        }
      }
    } catch (e) {
      console.warn('Gagal memuat tarif acuan standar:', e);
    } finally {
      loadingRates = false;
    }
  }

  // Sinkronisasi state saat modal dibuka
  $effect(() => {
    if (show) {
      errors = {};
      internalSubmitting = false;
      loadMasterBillingRates();
      loadUsers().then(() => {
        if (mode === 'edit' && project) {
          leadPicId = project.lead_pic_id || (usersList.find(u => u.name === project.lead_pic)?.id || null);
          if (leadPicId && !leadPic) {
            leadPic = usersList.find(u => u.id === leadPicId)?.name || project.lead_pic || '';
          }
          assignedWorkerIds = Array.isArray(project.workers) && project.workers.length > 0
            ? project.workers.map(w => w.id)
            : (leadPicId ? [leadPicId] : []);
        } else if (mode === 'create') {
          if (!leadPicId && usersList.length > 0) {
            const defUser = usersList.find(u => u.name === 'Muhammad Iqbal Murtadho') || usersList[0];
            leadPicId = defUser.id;
            leadPic = defUser.name;
            assignedWorkerIds = [defUser.id];
          }
        }
      });

      if (mode === 'edit' && project) {
        clientName = project.client?.name || '';
        clientContact = project.client?.contact_person || '';
        clientPhone = project.client?.phone || '';
        clientEmail = project.client?.email || '';
        clientAddress = project.client?.address || '';
        industry = project.client?.industry || '';

        appName = project.app_name || '';
        projectName = project.project_name || '';
        projectType = project.project_type || 'development';
        leadPic = project.lead_pic || 'Muhammad Iqbal Murtadho';
        leadPicId = project.lead_pic_id || null;
        assignedWorkerIds = Array.isArray(project.workers) ? project.workers.map(w => w.id) : [];
        githubUrl = project.github_url || '';
        appDesc = project.app_desc || '';
        status = project.status || 'Aktif';

        useLanding = !!project.use_landing;
        landingPrice = project.landing_price !== undefined ? Number(project.landing_price) : 500000;
        useLogin = !!project.use_login;
        loginPrice = project.login_price !== undefined ? Number(project.login_price) : 350000;
        useRegister = !!project.use_register;
        registerPrice = project.register_price !== undefined ? Number(project.register_price) : 350000;
        useAppCore = !!project.use_app_core;
        paidAmount = project.paid_amount !== undefined ? Number(project.paid_amount) : 0;

        roleTags = Array.isArray(project.roles)
          ? project.roles.map(r => typeof r === 'string' ? r : r.role_name).filter(Boolean)
          : ['Super Admin'];
      } else {
        appTypeChoice = 'new_app';
        inputDate = new Date().toISOString().split('T')[0];
        clientName = '';
        clientContact = '';
        clientAddress = '';
        clientEmail = '';
        clientPhone = '';
        leadPic = 'Muhammad Iqbal Murtadho';
        githubUrl = '';
        appName = '';
        industry = '';
        useLanding = true;
        useLogin = true;
        useRegister = false;
        useAppCore = true;
        appDesc = '';
        roleType = 'admin_only';
        roleCount = 2;
        dynamicRoleNames = ['Admin Utama', 'Manager Operasional'];

        // Init existing projects dropdown if available
        if (existingProjects && existingProjects.length > 0) {
          selectedExistingProjectId = String(existingProjects[0].id);
          loadExistingProjectDetail(existingProjects[0].id);
        } else {
          selectedExistingProjectId = '';
          selectedProjectDetail = null;
        }

        existingActionType = 'add_role';
        newRoleName = '';
        newRoleMenu = '';
        newMenuName = '';
        newMenuDesc = '';
        newMenuPrice = 250000;
        newMenuDone = false;
        maintDesc = '';
        maintPic = 'Misbahudin';
        maintDate = new Date().toISOString().split('T')[0];
        maintCost = 150000;
        selectedMaintMenus = [];
      }
    }
  });

  // Handler memilih proyek lama untuk dimuat detailnya
  async function loadExistingProjectDetail(projectId) {
    if (!projectId) return;
    loadingProjectDetail = true;
    try {
      const res = await api.request(`/api/v1/admin/clients/${projectId}`);
      if (res?.data) {
        selectedProjectDetail = res.data;
        if (res.data.roles && res.data.roles.length > 0) {
          targetRoleId = String(res.data.roles[0].id);
        }

        // Auto-select all menus for maintenance checklist
        const allMenuKeys = [];
        (res.data.roles || []).forEach(role => {
          (role.menus || []).forEach(menu => {
            allMenuKeys.push(`${role.role_name}:::${menu.menu_name}`);
          });
        });
        selectedMaintMenus = allMenuKeys;
        maintCost = allMenuKeys.length * (ratesMap.maintenance_menu_price || 150000);
      }
    } catch {
      // Fallback ke objek dari existingProjects
      const found = existingProjects.find(p => String(p.id) === String(projectId));
      if (found) {
        selectedProjectDetail = found;
        if (found.roles && found.roles.length > 0) {
          targetRoleId = String(found.roles[0].id || '');
        }
      }
    } finally {
      loadingProjectDetail = false;
    }
  }

  function handleExistingProjectChange(e) {
    const id = e.target.value;
    selectedExistingProjectId = id;
    loadExistingProjectDetail(id);
  }

  // Hitung persentase progres aplikasi lama
  let existingProgress = $derived.by(() => {
    if (!selectedProjectDetail) return { percent: 0, totalMenus: 0, totalTables: 0 };
    let totalMenus = 0;
    let doneMenus = 0;
    (selectedProjectDetail.roles || []).forEach(r => {
      (r.menus || []).forEach(m => {
        totalMenus++;
        if (m.is_done) doneMenus++;
      });
    });
    const totalTables = (selectedProjectDetail.database_tables || selectedProjectDetail.databaseTables || []).length;
    const percent = totalMenus > 0 ? Math.round((doneMenus / totalMenus) * 100) : 0;
    return { percent, totalMenus, totalTables };
  });

  // Stepper jumlah role dinamis
  function updateRoleCount(val) {
    const count = Math.min(8, Math.max(2, parseInt(val, 10) || 2));
    roleCount = count;
    const current = [...dynamicRoleNames];
    while (current.length < count) {
      const idx = current.length;
      current.push(defaultRoleNames[idx] || `Role Pengguna ${idx + 1}`);
    }
    dynamicRoleNames = current.slice(0, count);
  }

  // Tag helper untuk role saat edit
  function addRoleTag() {
    const val = toProperCase(newRoleInput.trim());
    if (val && !roleTags.includes(val)) {
      roleTags = [...roleTags, val];
      newRoleInput = '';
    }
  }

  function removeRoleTag(idx) {
    roleTags = roleTags.filter((_, i) => i !== idx);
  }

  function handleRoleKeydown(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      addRoleTag();
    }
  }

  // Toggle checklist maintenance menu
  function toggleMaintMenuItem(key) {
    if (selectedMaintMenus.includes(key)) {
      selectedMaintMenus = selectedMaintMenus.filter(k => k !== key);
    } else {
      selectedMaintMenus = [...selectedMaintMenus, key];
    }
    maintCost = selectedMaintMenus.length * (ratesMap.maintenance_menu_price || 150000);
  }

  // Real-time estimated valuation untuk Aplikasi Baru
  let estimatedValuation = $derived.by(() => {
    let sum = 0;
    if (useLanding) sum += (ratesMap.landing || 500000);
    if (useLogin) sum += (ratesMap.login || 350000);
    if (useRegister) sum += (ratesMap.register || 350000);
    const roleCountVal = roleType === 'admin_only' ? 1 : dynamicRoleNames.length;
    sum += roleCountVal * 2 * (ratesMap.menu_price || 250000);
    sum += 3 * (ratesMap.database_table_price || 150000); // 3 tabel database standar
    return sum;
  });

  // Form submission handler
  async function handleSubmit(e) {
    e.preventDefault();
    errors = {};

    // 1. EDIT MODE
    if (mode === 'edit') {
      if (!clientName.trim()) errors.name = 'Nama instansi / klien wajib diisi';
      if (!appName.trim()) errors.app_name = 'Nama aplikasi / proyek wajib diisi';
      if (Object.keys(errors).length > 0) return;

      const payload = {
        name: toProperCase(clientName.trim()),
        client_name: toProperCase(clientName.trim()),
        contact_person: toProperCase(clientContact.trim()) || null,
        client_contact: toProperCase(clientContact.trim()) || null,
        phone: clientPhone.trim() || null,
        client_phone: clientPhone.trim() || null,
        email: clientEmail.trim() || null,
        client_email: clientEmail.trim() || null,
        address: toProperCase(clientAddress.trim()) || null,
        client_address: toProperCase(clientAddress.trim()) || null,
        industry: toProperCase(industry.trim()) || null,
        client_industry: toProperCase(industry.trim()) || null,
        app_name: toProperCase(appName.trim()),
        project_name: toProperCase(projectName.trim()) || toProperCase(appName.trim()),
        project_type: projectType,
        lead_pic: leadPic.trim() || 'Muhammad Iqbal Murtadho',
        lead_pic_id: leadPicId,
        worker_ids: assignedWorkerIds,
        github_url: githubUrl.trim() || null,
        app_desc: toProperCase(appDesc.trim()) || null,
        status: status,
        use_landing: useLanding,
        landing_price: Number(landingPrice) || 0,
        use_login: useLogin,
        login_price: Number(loginPrice) || 0,
        use_register: useRegister,
        register_price: Number(registerPrice) || 0,
        landing_capabilities: project?.landing_capabilities || null,
        login_capabilities: project?.login_capabilities || null,
        register_capabilities: project?.register_capabilities || null,
        use_app_core: useAppCore,
        paid_amount: Number(paidAmount) || 0,
        roles: roleTags
      };

      onsave(payload);
      return;
    }

    // 2. CREATE MODE: APLIKASI BARU
    if (appTypeChoice === 'new_app') {
      if (!clientName.trim()) errors.name = 'Nama client wajib diisi';
      if (!clientContact.trim()) errors.contact = 'Kontak client wajib diisi';
      if (!clientAddress.trim()) errors.address = 'Alamat client wajib diisi';
      if (!appName.trim()) errors.app_name = 'Nama aplikasi wajib diisi';
      if (!industry.trim()) errors.industry = 'Bidang aplikasi / industri wajib diisi';
      if (useAppCore && !appDesc.trim()) errors.app_desc = 'Deskripsi aplikasi wajib diisi';

      if (Object.keys(errors).length > 0) return;

      const resolvedRoles = roleType === 'admin_only'
        ? ['Admin']
        : dynamicRoleNames.map(r => toProperCase(r.trim())).filter(Boolean);

      const payload = {
        mode: 'new_client',
        name: toProperCase(clientName.trim()),
        contact_person: toProperCase(clientContact.trim()),
        phone: clientContact.trim(),
        email: clientEmail.trim() || null,
        address: toProperCase(clientAddress.trim()),
        industry: toProperCase(industry.trim()),
        app_name: toProperCase(appName.trim()),
        project_name: toProperCase(appName.trim()),
        project_type: 'development',
        lead_pic: leadPic,
        lead_pic_id: leadPicId,
        worker_ids: assignedWorkerIds,
        github_url: githubUrl.trim() || null,
        app_desc: toProperCase(appDesc.trim()) || 'Aplikasi Terpadu Berbasis Web Untuk Operasional Bisnis.',
        use_landing: useLanding,
        use_login: useLogin,
        use_register: useRegister,
        use_app_core: useAppCore,
        landing_price: useLanding ? (ratesMap.landing || 500000) : 0,
        login_price: useLogin ? (ratesMap.login || 350000) : 0,
        register_price: useRegister ? (ratesMap.register || 350000) : 0,
        landing_capabilities: { has_create: false, has_read: true, has_update: false, has_delete: false, has_grafik: false, has_box_dashboard: false, has_import: false, has_export: false, has_filter: false, has_print: false },
        login_capabilities: { has_create: true, has_read: true, has_update: true, has_delete: false, has_grafik: false, has_box_dashboard: false, has_import: false, has_export: false, has_filter: false, has_print: false },
        register_capabilities: { has_create: true, has_read: true, has_update: false, has_delete: false, has_grafik: false, has_box_dashboard: false, has_import: false, has_export: false, has_filter: false, has_print: false },
        status: 'Aktif',
        roles: resolvedRoles
      };

      onsave(payload);
      return;
    }

    // 3. CREATE MODE: APLIKASI LAMA (CLIENT EXISTING)
    if (appTypeChoice === 'existing_app') {
      if (!selectedExistingProjectId) {
        errors.existing_project = 'Pilih client / project lama terlebih dahulu';
        return;
      }

      internalSubmitting = true;

      try {
        // A. Tambah Role Baru
        if (existingActionType === 'add_role') {
          if (!newRoleName.trim()) {
            errors.new_role_name = 'Harap masukkan nama role baru';
            internalSubmitting = false;
            return;
          }

          const cleanRoleName = toProperCase(newRoleName.trim());
          const cleanMenuName = toProperCase(newRoleMenu.trim()) || `Dashboard ${cleanRoleName}`;

          const resRole = await api.request(`/api/v1/admin/projects/${selectedExistingProjectId}/roles`, {
            method: 'POST',
            body: { role_name: cleanRoleName }
          });

          if (resRole?.data?.id) {
            await api.request(`/api/v1/admin/roles/${resRole.data.id}/menus`, {
              method: 'POST',
              body: {
                menu_name: cleanMenuName,
                menu_desc: `Modul operasional aplikasi untuk ${cleanRoleName}.`,
                price: ratesMap.menu_price || 250000,
                is_done: false,
                pic: selectedProjectDetail?.lead_pic || 'Muhammad Iqbal Murtadho',
                pic_user_id: selectedProjectDetail?.lead_pic_id || (usersList.find(u => u.name === selectedProjectDetail?.lead_pic)?.id || null)
              }
            });
          }

          toastState.success(`Role "${cleanRoleName}" dan menu perdana ditambahkan.`, 'Role Baru Ditambahkan');
          onclose();
          onprojectready(selectedExistingProjectId);
          return;
        }

        // B. Tambah Menu Modul
        if (existingActionType === 'add_menu') {
          if (!newMenuName.trim()) {
            errors.new_menu_name = 'Harap masukkan nama menu baru';
            internalSubmitting = false;
            return;
          }
          if (!targetRoleId) {
            errors.target_role_id = 'Pilih role tujuan terlebih dahulu';
            internalSubmitting = false;
            return;
          }

          const cleanMenuName = toProperCase(newMenuName.trim());
          const cleanMenuDesc = toProperCase(newMenuDesc.trim()) || 'Modul operasional tambahan.';

          await api.request(`/api/v1/admin/roles/${targetRoleId}/menus`, {
            method: 'POST',
            body: {
              menu_name: cleanMenuName,
              menu_desc: cleanMenuDesc,
              price: Number(newMenuPrice) || ratesMap.menu_price || 250000,
              is_done: newMenuDone,
              completed_date: newMenuDone ? new Date().toISOString().split('T')[0] : null,
              pic: selectedProjectDetail?.lead_pic || 'Muhammad Iqbal Murtadho',
              pic_user_id: selectedProjectDetail?.lead_pic_id || (usersList.find(u => u.name === selectedProjectDetail?.lead_pic)?.id || null)
            }
          });

          toastState.success(`Menu "${cleanMenuName}" berhasil ditambahkan ke role.`, 'Menu Modul Ditambahkan');
          onclose();
          onprojectready(selectedExistingProjectId);
          return;
        }

        // C. Maintenance
        if (existingActionType === 'maintenance') {
          if (!maintDesc.trim()) {
            errors.maint_desc = 'Harap isi deskripsi rincian pekerjaan maintenance';
            internalSubmitting = false;
            return;
          }

          const targetProject = selectedProjectDetail || existingProjects.find(p => String(p.id) === String(selectedExistingProjectId));
          const cleanDesc = toProperCase(maintDesc.trim());

          const maintUser = usersList.find(u => u.name === maintPic);
          const maintPicId = maintUser?.id || null;

          const resMaint = await api.request('/api/v1/admin/clients', {
            method: 'POST',
            body: {
              mode: 'existing_client',
              client_id: targetProject?.client_id || targetProject?.client?.id,
              app_name: `${targetProject?.app_name || 'Aplikasi'} (Maintenance ${maintDate})`,
              project_name: `${targetProject?.app_name || 'Aplikasi'} (Maintenance ${maintDate})`,
              project_type: 'maintenance',
              lead_pic: maintPic,
              lead_pic_id: maintPicId,
              worker_ids: maintPicId ? [maintPicId] : [],
              app_desc: `Pekerjaan Pemeliharaan Sistem: ${cleanDesc}`,
              use_landing: false,
              use_login: false,
              use_register: false,
              use_app_core: true,
              landing_price: 0,
              login_price: 0,
              register_price: 0,
              status: 'Aktif',
              roles: ['Maintenance Tim']
            }
          });

          if (resMaint?.data?.id && selectedMaintMenus.length > 0) {
            const maintRoleId = resMaint.data.roles?.[0]?.id;
            if (maintRoleId) {
              const perMenuPrice = ratesMap.maintenance_menu_price || 150000;
              for (const itemKey of selectedMaintMenus) {
                const parts = itemKey.split(':::');
                const mName = parts[1] || parts[0];
                await api.request(`/api/v1/admin/roles/${maintRoleId}/menus`, {
                  method: 'POST',
                  body: {
                    menu_name: `Maint: ${mName}`,
                    menu_desc: cleanDesc,
                    price: perMenuPrice,
                    is_done: false,
                    pic: maintPic,
                    pic_user_id: maintPicId
                  }
                });
              }
            }
          }

          toastState.success(`Paket maintenance untuk ${targetProject?.client?.name || targetProject?.app_name} berhasil dibuat.`, 'Maintenance Dicatat');
          onclose();
          onprojectready(resMaint?.data?.id || selectedExistingProjectId);
          return;
        }
      } catch (err) {
        toastState.error(err?.message || 'Gagal menyimpan perubahan aplikasi lama.', 'Gagal');
      } finally {
        internalSubmitting = false;
      }
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
  title={mode === 'create' ? 'Tambah Client & Project Baru' : 'Edit Data Proyek & Klien'}
  subtitle="Semua input teks otomatis dikonversi ke format Proper Case (Ucwords)"
  maxWidth="max-w-2xl"
  {zIndex}
  {onclose}
>
  {#snippet header()}
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
      </div>
      <div>
        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
          {mode === 'create' ? 'Tambah Client & Project Baru' : 'Edit Data Proyek & Klien'}
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Semua input teks otomatis dikonversi ke format Proper Case (Ucwords)
        </p>
      </div>
    </div>
  {/snippet}

  <form id="client-project-main-form" onsubmit={handleSubmit} class="space-y-4">
    <!-- OPSI: APLIKASI BARU ATAU APLIKASI LAMA (Hanya saat create) -->
    {#if mode === 'create'}
      <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 space-y-2.5">
        <span class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
          Tipe Pendaftaran / Input Project:
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer shadow-xs transition-all select-none {appTypeChoice === 'new_app' ? 'border-indigo-500 bg-white dark:bg-slate-900 ring-1 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 hover:border-indigo-300'}">
            <input
              type="radio"
              name="modal-app-type-choice"
              value="new_app"
              checked={appTypeChoice === 'new_app'}
              onchange={() => (appTypeChoice = 'new_app')}
              class="w-4 h-4 text-indigo-600 accent-indigo-600"
            />
            <div>
              <span class="block text-xs font-bold text-slate-900 dark:text-white">Aplikasi Baru</span>
              <span class="text-[10px] text-slate-500 dark:text-slate-400">Pendaftaran project & client baru</span>
            </div>
          </label>

          <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer shadow-xs transition-all select-none {appTypeChoice === 'existing_app' ? 'border-indigo-500 bg-white dark:bg-slate-900 ring-1 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 hover:border-indigo-300'}">
            <input
              type="radio"
              name="modal-app-type-choice"
              value="existing_app"
              checked={appTypeChoice === 'existing_app'}
              onchange={() => {
                appTypeChoice = 'existing_app';
                if (!selectedProjectDetail && existingProjects.length > 0) {
                  selectedExistingProjectId = String(existingProjects[0].id);
                  loadExistingProjectDetail(existingProjects[0].id);
                }
              }}
              class="w-4 h-4 text-indigo-600 accent-indigo-600"
            />
            <div>
              <span class="block text-xs font-bold text-slate-900 dark:text-white">Aplikasi Lama (Client Existing)</span>
              <span class="text-[10px] text-slate-500 dark:text-slate-400">Tambah role, menu, atau maintenance</span>
            </div>
          </label>
        </div>
      </div>
    {/if}

    <!-- ============================================== -->
    <!-- PANEL 1: INPUT APLIKASI BARU (CREATE)          -->
    <!-- ============================================== -->
    {#if mode === 'create' && appTypeChoice === 'new_app'}
      <div class="space-y-4">
        <!-- Baris 1: Tanggal Input Project & Nama Client -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label for="new-input-date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Tanggal Input <span class="text-rose-500">*</span>
            </label>
            <input
              id="new-input-date"
              type="date"
              bind:value={inputDate}
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-colors font-medium"
            />
          </div>
          <div class="sm:col-span-2">
            <label for="new-client-name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Nama Client <span class="text-rose-500">*</span>
            </label>
            <input
              id="new-client-name"
              type="text"
              value={clientName}
              oninput={(e) => (clientName = toProperCase(e.currentTarget.value))}
              placeholder="Contoh: PT Valora Teknologi Nusantara"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors"
            />
            {#if errors.name}
              <p class="text-[11px] text-rose-500 mt-1">{errors.name}</p>
            {/if}
          </div>
        </div>

        <!-- Baris Tim Pengerja & Tipe Pengerjaan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="new-lead-pic" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Lead Engineer / PIC Leader <span class="text-rose-500">*</span>
            </label>
            <select
              id="new-lead-pic"
              bind:value={leadPicId}
              onchange={() => {
                const sel = usersList.find(u => u.id === Number(leadPicId));
                if (sel) {
                  leadPic = sel.name;
                  if (!assignedWorkerIds.includes(sel.id)) {
                    assignedWorkerIds = [...assignedWorkerIds, sel.id];
                  }
                }
              }}
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-semibold"
            >
              {#if usersList.length === 0}
                <option value={null}>{leadPic || 'Muhammad Iqbal Murtadho'}</option>
              {:else}
                {#each usersList as user}
                  <option value={user.id}>
                    {user.name} {user.jabatan ? `(${user.jabatan})` : ''}
                  </option>
                {/each}
              {/if}
            </select>
          </div>
          <div>
            <span class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Tipe Pengerjaan Project
            </span>
            <div class="px-3 py-2 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-500/30 text-xs font-bold text-indigo-700 dark:text-indigo-300 flex items-center gap-2">
              <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
              </svg>
              <span>Paket Development Baru</span>
            </div>
          </div>
        </div>

        <!-- Tim Pekerja / Assigned Workers (Multi-User Assignment) -->
        {#if usersList.length > 0}
          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <span>👥 Pekerja / Tim yang Ditugaskan</span>
                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-mono">(Tersambung Tabel User)</span>
              </span>
              <span class="text-[10px] text-slate-400 font-mono">{assignedWorkerIds.length} staf ditugaskan</span>
            </div>
            <div class="flex flex-wrap gap-1.5 pt-1">
              {#each usersList as user}
                {@const isAssigned = assignedWorkerIds.includes(user.id)}
                <button
                  type="button"
                  onclick={() => {
                    if (isAssigned) {
                      assignedWorkerIds = assignedWorkerIds.filter(id => id !== user.id);
                    } else {
                      assignedWorkerIds = [...assignedWorkerIds, user.id];
                    }
                  }}
                  class="text-xs px-2.5 py-1 rounded-lg border transition flex items-center gap-1.5 cursor-pointer {isAssigned ? 'bg-indigo-50 dark:bg-indigo-950/80 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 font-bold shadow-2xs' : 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-medium hover:border-slate-300'}"
                >
                  <span>{isAssigned ? '✓' : '+'}</span>
                  <span>{user.name}</span>
                  {#if user.jabatan}
                    <span class="text-[10px] font-normal text-slate-400">({user.jabatan})</span>
                  {/if}
                </button>
              {/each}
            </div>
          </div>
        {/if}

        <!-- Baris Kontak Client & Repo GitHub -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="new-client-contact" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Kontak Client (WA / Email) <span class="text-rose-500">*</span>
            </label>
            <input
              id="new-client-contact"
              type="text"
              value={clientContact}
              oninput={(e) => (clientContact = toProperCase(e.currentTarget.value))}
              placeholder="Contoh: 0812-3456-7890 / Info@client.com"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors"
            />
            {#if errors.contact}
              <p class="text-[11px] text-rose-500 mt-1">{errors.contact}</p>
            {/if}
          </div>
          <div>
            <label for="new-github-url" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Link Repository GitHub
            </label>
            <div class="relative">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4" />
                <path d="M9 18c-4.51 2-5-2-7-2" />
              </svg>
              <input
                id="new-github-url"
                type="url"
                bind:value={githubUrl}
                placeholder="https://github.com/valora/nama-project"
                class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors font-mono text-[11px]"
              />
            </div>
          </div>
        </div>

        <!-- Baris Alamat Client -->
        <div>
          <label for="new-client-address" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Alamat Client <span class="text-rose-500">*</span>
          </label>
          <input
            id="new-client-address"
            type="text"
            value={clientAddress}
            oninput={(e) => (clientAddress = toProperCase(e.currentTarget.value))}
            placeholder="Contoh: Jl. Sudirman Kav 21 Blok B, Jakarta Selatan"
            required
            class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors"
          />
          {#if errors.address}
            <p class="text-[11px] text-rose-500 mt-1">{errors.address}</p>
          {/if}
        </div>

        <!-- Baris Nama Aplikasi & Bidang Aplikasi -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="new-app-name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Nama Aplikasi <span class="text-rose-500">*</span>
            </label>
            <input
              id="new-app-name"
              type="text"
              value={appName}
              oninput={(e) => (appName = toProperCase(e.currentTarget.value))}
              placeholder="Contoh: Sistem POS Dan Inventaris Multi Cabang"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors"
            />
            {#if errors.app_name}
              <p class="text-[11px] text-rose-500 mt-1">{errors.app_name}</p>
            {/if}
          </div>
          <div>
            <label for="new-industry" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Bidang Aplikasi / Industri <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <input
                id="new-industry"
                type="text"
                value={industry}
                oninput={(e) => (industry = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Keuangan, Konstruksi, Notaris, dll."
                required
                class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
            {#if errors.industry}
              <p class="text-[11px] text-rose-500 mt-1">{errors.industry}</p>
            {/if}
          </div>
        </div>

        <!-- Baris Check List Fitur Project -->
        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 space-y-2.5">
          <div class="flex items-center justify-between">
            <span class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              Cakupan Fitur Project (Pilih yang Digunakan)
            </span>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-mono font-bold border border-indigo-200 dark:border-indigo-800">
              Est: {formatIDR(estimatedValuation)}
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
            <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 cursor-pointer select-none hover:border-indigo-300 transition">
              <input type="checkbox" bind:checked={useLanding} class="w-4 h-4 rounded text-indigo-600 accent-indigo-600" />
              <div class="flex-1">
                <span class="font-medium text-slate-800 dark:text-slate-200">Pakai Landing Page</span>
                <span class="block text-[10px] text-slate-400 font-mono">{formatIDR(ratesMap.landing)}</span>
              </div>
            </label>
            <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 cursor-pointer select-none hover:border-indigo-300 transition">
              <input type="checkbox" bind:checked={useLogin} class="w-4 h-4 rounded text-indigo-600 accent-indigo-600" />
              <div class="flex-1">
                <span class="font-medium text-slate-800 dark:text-slate-200">Pakai Halaman Login</span>
                <span class="block text-[10px] text-slate-400 font-mono">{formatIDR(ratesMap.login)}</span>
              </div>
            </label>
            <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 cursor-pointer select-none hover:border-indigo-300 transition">
              <input type="checkbox" bind:checked={useRegister} class="w-4 h-4 rounded text-indigo-600 accent-indigo-600" />
              <div class="flex-1">
                <span class="font-medium text-slate-800 dark:text-slate-200">Pakai Halaman Register</span>
                <span class="block text-[10px] text-slate-400 font-mono">{formatIDR(ratesMap.register)}</span>
              </div>
            </label>
            <label class="flex items-center gap-2.5 p-2.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 cursor-pointer select-none">
              <input type="checkbox" bind:checked={useAppCore} class="w-4 h-4 rounded text-indigo-600 accent-indigo-600" />
              <div class="flex-1">
                <span class="font-bold text-indigo-700 dark:text-indigo-300">Pakai Aplikasi (Ya)</span>
                <span class="block text-[10px] text-indigo-500/80 dark:text-indigo-400">Modul Operasional & Database</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Kondisional: Jika Pakai Aplikasi (Ya) -->
        {#if useAppCore}
          <div class="space-y-3.5 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800">
            <div>
              <label for="new-app-desc" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Deskripsi Aplikasi <span class="text-rose-500">*</span>
              </label>
              <textarea
                id="new-app-desc"
                rows="2"
                value={appDesc}
                oninput={(e) => (appDesc = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Aplikasi Pengelolaan Transaksi Penjualan Dan Pengawasan Stok Gudang Terintegrasi."
                required
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 transition-colors"
              ></textarea>
              {#if errors.app_desc}
                <p class="text-[11px] text-rose-500 mt-1">{errors.app_desc}</p>
              {/if}
            </div>

            <!-- Opsi Role: Hanya Role Admin atau Lebih Dari Admin -->
            <div>
              <span class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Struktur Role Pengguna Aplikasi
              </span>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer select-none transition {roleType === 'admin_only' ? 'bg-white dark:bg-slate-900 border-indigo-500 ring-1 ring-indigo-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700'}">
                  <input
                    type="radio"
                    name="modal-role-type"
                    value="admin_only"
                    checked={roleType === 'admin_only'}
                    onchange={() => (roleType = 'admin_only')}
                    class="w-4 h-4 text-indigo-600 accent-indigo-600"
                  />
                  <div>
                    <p class="font-bold text-slate-800 dark:text-white">Hanya Role Admin Saja</p>
                    <p class="text-[10px] text-slate-400">1 hak akses administrator tunggal</p>
                  </div>
                </label>

                <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer select-none transition {roleType === 'multi_role' ? 'bg-white dark:bg-slate-900 border-indigo-500 ring-1 ring-indigo-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700'}">
                  <input
                    type="radio"
                    name="modal-role-type"
                    value="multi_role"
                    checked={roleType === 'multi_role'}
                    onchange={() => (roleType = 'multi_role')}
                    class="w-4 h-4 text-indigo-600 accent-indigo-600"
                  />
                  <div>
                    <p class="font-bold text-slate-800 dark:text-white">Lebih Dari Admin (Multi-Role)</p>
                    <p class="text-[10px] text-slate-400">Beberapa role dengan menu berbeda</p>
                  </div>
                </label>
              </div>
            </div>

            <!-- Kondisional: Multi-Role Generator -->
            {#if roleType === 'multi_role'}
              <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between gap-4">
                  <div>
                    <label for="new-role-count" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                      Berapa Jumlah Role? (Angka 2 s/d 8)
                    </label>
                    <p class="text-[11px] text-slate-400">
                      Form input nama role akan otomatis bertambah sesuai angka di samping
                    </p>
                  </div>
                  <input
                    id="new-role-count"
                    type="number"
                    min="2"
                    max="8"
                    value={roleCount}
                    oninput={(e) => updateRoleCount(e.currentTarget.value)}
                    class="w-20 px-3 py-2 text-center rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
                  />
                </div>

                <div>
                  <span class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                    Tentukan Nama Setiap Role:
                  </span>
                  <div class="space-y-2">
                    {#each dynamicRoleNames as roleName, idx}
                      <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[11px] flex items-center justify-center shrink-0">
                          {idx + 1}
                        </span>
                        <input
                          type="text"
                          value={roleName}
                          oninput={(e) => {
                            dynamicRoleNames[idx] = toProperCase(e.currentTarget.value);
                          }}
                          placeholder="Nama Role ke-{idx + 1}"
                          class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-medium"
                        />
                      </div>
                    {/each}
                  </div>
                </div>
              </div>
            {/if}
          </div>
        {/if}
      </div>
    {/if}

    <!-- ============================================== -->
    <!-- PANEL 2: INPUT APLIKASI LAMA (CLIENT EXISTING) -->
    <!-- ============================================== -->
    {#if mode === 'create' && appTypeChoice === 'existing_app'}
      <div class="space-y-4">
        <!-- Pilih Client Existing -->
        <div class="space-y-1.5">
          <label for="select-existing-project" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            Pilih Client / Project Lama <span class="text-rose-500">*</span>
          </label>
          <select
            id="select-existing-project"
            value={selectedExistingProjectId}
            onchange={handleExistingProjectChange}
            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-semibold transition-colors"
          >
            {#each existingProjects as p}
              <option value={String(p.id)}>
                {p.client?.name || p.project_name} — {p.app_name} ({p.project_type === 'maintenance' ? 'Maintenance' : 'Development'})
              </option>
            {/each}
          </select>
          {#if errors.existing_project}
            <p class="text-[11px] text-rose-500 mt-1">{errors.existing_project}</p>
          {/if}
        </div>

        <!-- Preview Ringkasan Client Terpilih -->
        {#if selectedProjectDetail}
          <div class="p-3.5 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-500/20 space-y-2">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-bold text-slate-900 dark:text-white">
                  {selectedProjectDetail.client?.name || selectedProjectDetail.project_name}
                </p>
                <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">
                  {selectedProjectDetail.app_name} ({selectedProjectDetail.client?.industry || 'Umum'})
                </p>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                {existingProgress.percent}% Selesai
              </span>
            </div>
            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-indigo-500/15 text-[11px] text-slate-600 dark:text-slate-300 font-medium">
              <div><strong>Role:</strong> {(selectedProjectDetail.roles || []).length} Role</div>
              <div><strong>Menu:</strong> {existingProgress.totalMenus} Menu</div>
              <div><strong>Database:</strong> {existingProgress.totalTables} Tabel</div>
            </div>
          </div>
        {/if}

        <!-- Opsi Kebutuhan Aplikasi Lama -->
        <div class="space-y-1.5">
          <span class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            Pilih Opsi Kebutuhan Project:
          </span>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
            <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer select-none transition {existingActionType === 'add_role' ? 'bg-white dark:bg-slate-900 border-indigo-500 ring-1 ring-indigo-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700'}">
              <input
                type="radio"
                name="modal-existing-action-type"
                value="add_role"
                checked={existingActionType === 'add_role'}
                onchange={() => (existingActionType = 'add_role')}
                class="w-4 h-4 text-indigo-600 accent-indigo-600"
              />
              <div>
                <p class="font-bold text-slate-800 dark:text-white">Tambah Role Baru</p>
                <p class="text-[10px] text-slate-400">Akses user baru</p>
              </div>
            </label>

            <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer select-none transition {existingActionType === 'add_menu' ? 'bg-white dark:bg-slate-900 border-indigo-500 ring-1 ring-indigo-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700'}">
              <input
                type="radio"
                name="modal-existing-action-type"
                value="add_menu"
                checked={existingActionType === 'add_menu'}
                onchange={() => (existingActionType = 'add_menu')}
                class="w-4 h-4 text-indigo-600 accent-indigo-600"
              />
              <div>
                <p class="font-bold text-slate-800 dark:text-white">Tambah Menu Modul</p>
                <p class="text-[10px] text-slate-400">Fitur / modul baru</p>
              </div>
            </label>

            <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer select-none transition {existingActionType === 'maintenance' ? 'bg-white dark:bg-slate-900 border-indigo-500 ring-1 ring-indigo-500/20' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700'}">
              <input
                type="radio"
                name="modal-existing-action-type"
                value="maintenance"
                checked={existingActionType === 'maintenance'}
                onchange={() => (existingActionType = 'maintenance')}
                class="w-4 h-4 text-indigo-600 accent-indigo-600"
              />
              <div>
                <p class="font-bold text-slate-800 dark:text-white">Maintenance</p>
                <p class="text-[10px] text-slate-400">Pemeliharaan sistem</p>
              </div>
            </label>
          </div>
        </div>

        <!-- SUB-FORM 1: TAMBAH ROLE BARU -->
        {#if existingActionType === 'add_role'}
          <div class="space-y-3.5 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800">
            <div>
              <label for="existing-new-role-name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Nama Role Baru <span class="text-rose-500">*</span>
              </label>
              <input
                id="existing-new-role-name"
                type="text"
                value={newRoleName}
                oninput={(e) => (newRoleName = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Manager Operasional"
                required
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-medium"
              />
              {#if errors.new_role_name}
                <p class="text-[11px] text-rose-500 mt-1">{errors.new_role_name}</p>
              {/if}
            </div>
            <div>
              <label for="existing-new-role-menu" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Menu Awal Untuk Role Ini <span class="text-rose-500">*</span>
              </label>
              <input
                id="existing-new-role-menu"
                type="text"
                value={newRoleMenu}
                oninput={(e) => (newRoleMenu = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Dashboard Operasional & Approval"
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-medium"
              />
              <p class="text-[11px] text-slate-400 mt-1">Tarif menu standar otomatis diterapkan sesuai tarif tagihan aktif ({formatIDR(ratesMap.menu_price)}).</p>
            </div>
          </div>
        {/if}

        <!-- SUB-FORM 2: TAMBAH MENU MODUL -->
        {#if existingActionType === 'add_menu'}
          <div class="space-y-3.5 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800">
            <div>
              <label for="existing-target-role" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Pilih Role Tujuan <span class="text-rose-500">*</span>
              </label>
              <select
                id="existing-target-role"
                bind:value={targetRoleId}
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-semibold"
              >
                {#each (selectedProjectDetail?.roles || []) as r}
                  <option value={String(r.id)}>Role: {r.role_name}</option>
                {/each}
              </select>
              {#if errors.target_role_id}
                <p class="text-[11px] text-rose-500 mt-1">{errors.target_role_id}</p>
              {/if}
            </div>
            <div>
              <label for="existing-menu-name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Nama Menu / Fitur Baru <span class="text-rose-500">*</span>
              </label>
              <input
                id="existing-menu-name"
                type="text"
                value={newMenuName}
                oninput={(e) => (newMenuName = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Rekap Laporan Bulanan"
                required
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-medium"
              />
              {#if errors.new_menu_name}
                <p class="text-[11px] text-rose-500 mt-1">{errors.new_menu_name}</p>
              {/if}
            </div>
            <div>
              <label for="existing-menu-desc" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Keterangan / Deskripsi Menu
              </label>
              <input
                id="existing-menu-desc"
                type="text"
                value={newMenuDesc}
                oninput={(e) => (newMenuDesc = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Modul export PDF & Excel serta audit trail"
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-medium"
              />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label for="existing-menu-price" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                    Tarif Menu Baru (Rp)
                  </label>
                  <button
                    type="button"
                    onclick={() => (newMenuPrice = ratesMap.menu_price || 250000)}
                    class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-mono cursor-pointer"
                    title="Gunakan tarif acuan master standar"
                  >
                    Acuan: {formatIDR(ratesMap.menu_price)}
                  </button>
                </div>
                <input
                  id="existing-menu-price"
                  type="number"
                  bind:value={newMenuPrice}
                  step="50000"
                  class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono font-semibold"
                />
              </div>
              <div class="pt-4 sm:pt-6">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input type="checkbox" bind:checked={newMenuDone} class="w-4 h-4 rounded text-indigo-600 accent-indigo-600" />
                  <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Tandai langsung selesai dikerjakan</span>
                </label>
              </div>
            </div>
          </div>
        {/if}

        <!-- SUB-FORM 3: MAINTENANCE / PEMELIHARAAN -->
        {#if existingActionType === 'maintenance'}
          <div class="space-y-3.5 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800">
            <!-- Checklist Role & Menu Yang Di-Maintenance -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                  List Role Login & Menu Yang Di-Maintenance <span class="text-rose-500">*</span>
                </span>
                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold">Wajib Checklist Modul Target</span>
              </div>
              <div class="max-h-48 overflow-y-auto p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 space-y-2.5 text-xs">
                {#if selectedProjectDetail?.roles && selectedProjectDetail.roles.length > 0}
                  {#each selectedProjectDetail.roles as role}
                    <div class="space-y-1.5 pb-2 border-b border-slate-100 dark:border-slate-800 last:border-b-0">
                      <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">
                        Role: {role.role_name}
                      </span>
                      {#each (role.menus || []) as menu}
                        {@const key = `${role.role_name}:::${menu.menu_name}`}
                        <label class="flex items-start gap-2.5 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 cursor-pointer hover:border-indigo-500 transition-colors select-none">
                          <input
                            type="checkbox"
                            checked={selectedMaintMenus.includes(key)}
                            onchange={() => toggleMaintMenuItem(key)}
                            class="mt-0.5 w-4 h-4 rounded text-indigo-600 accent-indigo-600"
                          />
                          <div class="flex-1">
                            <span class="font-bold text-slate-800 dark:text-white text-xs">{menu.menu_name}</span>
                            <p class="text-[10px] text-slate-400 mt-0.5">{menu.menu_desc || 'Tanpa keterangan'}</p>
                          </div>
                        </label>
                      {/each}
                    </div>
                  {/each}
                {:else}
                  <p class="text-xs text-slate-400 italic">Belum ada menu yang dikonfigurasi pada client ini.</p>
                {/if}
              </div>
            </div>

            <!-- Deskripsi Maintenance -->
            <div>
              <label for="existing-maint-desc" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Deskripsi Rincian Pekerjaan Maintenance <span class="text-rose-500">*</span>
              </label>
              <textarea
                id="existing-maint-desc"
                rows="2"
                value={maintDesc}
                oninput={(e) => (maintDesc = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Pembaruan Patch Security, Pengawasan Query Indexing Database, dan Audit Hak Akses Role."
                required
                class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500 font-medium"
              ></textarea>
              {#if errors.maint_desc}
                <p class="text-[11px] text-rose-500 mt-1">{errors.maint_desc}</p>
              {/if}
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label for="existing-maint-pic" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                  Tim Yang Mengerjakan <span class="text-rose-500">*</span>
                </label>
                <select
                  id="existing-maint-pic"
                  bind:value={maintPic}
                  class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-semibold"
                >
                  {#if usersList.length === 0}
                    {#each teamMembers as member}
                      <option value={member}>{member}</option>
                    {/each}
                  {:else}
                    {#each usersList as user}
                      <option value={user.name}>{user.name} {user.jabatan ? `(${user.jabatan})` : ''}</option>
                    {/each}
                  {/if}
                </select>
              </div>
              <div>
                <label for="existing-maint-date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                  Tanggal Maintenance <span class="text-rose-500">*</span>
                </label>
                <input
                  id="existing-maint-date"
                  type="date"
                  bind:value={maintDate}
                  class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-semibold"
                />
              </div>
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label for="existing-maint-cost" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                    Biaya Maintenance (Rp) <span class="text-rose-500">*</span>
                  </label>
                  <button
                    type="button"
                    onclick={() => (maintCost = selectedMaintMenus.length * (ratesMap.maintenance_menu_price || 150000))}
                    class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-mono cursor-pointer"
                    title="Hitung berdasarkan tarif acuan master"
                  >
                    Acuan: {selectedMaintMenus.length} × {formatIDR(ratesMap.maintenance_menu_price)}
                  </button>
                </div>
                <input
                  id="existing-maint-cost"
                  type="number"
                  bind:value={maintCost}
                  step="50000"
                  class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 font-mono font-semibold"
                />
              </div>
            </div>
          </div>
        {/if}
      </div>
    {/if}

    <!-- ============================================== -->
    <!-- PANEL 3: EDIT DATA PROYEK & KLIEN (EDIT MODE)  -->
    <!-- ============================================== -->
    {#if mode === 'edit'}
      <div class="space-y-4">
        <!-- Informasi Klien / Mitra -->
        <div class="p-3.5 bg-slate-50 dark:bg-slate-950/70 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
          <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>Profil Klien / Instansi Mitra</span>
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="edit-client-name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Nama Klien / Instansi <span class="text-rose-500">*</span>
              </label>
              <input
                id="edit-client-name"
                type="text"
                value={clientName}
                oninput={(e) => (clientName = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: PT Nusantara Digital Prima"
                required
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
              />
              {#if errors.name}
                <p class="text-[11px] text-rose-500 mt-0.5">{errors.name}</p>
              {/if}
            </div>
            <div>
              <label for="edit-client-industry" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Sektor Industri / Bidang
              </label>
              <input
                id="edit-client-industry"
                type="text"
                value={industry}
                oninput={(e) => (industry = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Keuangan & ERP / Kesehatan"
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label for="edit-client-contact" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Nama PIC Klien
              </label>
              <input
                id="edit-client-contact"
                type="text"
                value={clientContact}
                oninput={(e) => (clientContact = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Bpk. Hendra"
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label for="edit-client-phone" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                WhatsApp / Telepon
              </label>
              <input
                id="edit-client-phone"
                type="text"
                bind:value={clientPhone}
                placeholder="0812-3456-7890"
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label for="edit-client-email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Email Resmi
              </label>
              <input
                id="edit-client-email"
                type="email"
                bind:value={clientEmail}
                placeholder="klien@perusahaan.com"
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label for="edit-client-address" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Alamat Lengkap Klien
            </label>
            <input
              id="edit-client-address"
              type="text"
              value={clientAddress}
              oninput={(e) => (clientAddress = toProperCase(e.currentTarget.value))}
              placeholder="Contoh: Jl. Sudirman No. 45, Jakarta Selatan"
              class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Spesifikasi Proyek -->
        <div class="p-3.5 bg-slate-50 dark:bg-slate-950/70 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
          <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>
            <span>Detail Aplikasi & Lingkup Pengerjaan</span>
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="edit-app-name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Nama Aplikasi / Proyek <span class="text-rose-500">*</span>
              </label>
              <input
                id="edit-app-name"
                type="text"
                value={appName}
                oninput={(e) => (appName = toProperCase(e.currentTarget.value))}
                placeholder="Contoh: Sistem ERP Multi-Cabang"
                required
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
              />
              {#if errors.app_name}
                <p class="text-[11px] text-rose-500 mt-0.5">{errors.app_name}</p>
              {/if}
            </div>
            <div>
              <label for="edit-project-type" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Tipe Kontrak
              </label>
              <select
                id="edit-project-type"
                bind:value={projectType}
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-semibold"
              >
                <option value="development">Pengembangan Baru (Development)</option>
                <option value="maintenance">Paket Pemeliharaan (Maintenance)</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label for="edit-lead-pic" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Lead PIC Proyek (Tersambung User)
              </label>
              <select
                id="edit-lead-pic"
                bind:value={leadPicId}
                onchange={() => {
                  const sel = usersList.find(u => u.id === Number(leadPicId));
                  if (sel) {
                    leadPic = sel.name;
                    if (!assignedWorkerIds.includes(sel.id)) {
                      assignedWorkerIds = [...assignedWorkerIds, sel.id];
                    }
                  }
                }}
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-semibold"
              >
                {#if usersList.length === 0}
                  <option value={null}>{leadPic || 'Muhammad Iqbal Murtadho'}</option>
                {:else}
                  {#each usersList as user}
                    <option value={user.id}>
                      {user.name} {user.jabatan ? `(${user.jabatan})` : ''}
                    </option>
                  {/each}
                {/if}
              </select>
            </div>
            <div>
              <label for="edit-project-status" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Status Proyek
              </label>
              <select
                id="edit-project-status"
                bind:value={status}
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-semibold"
              >
                <option value="Aktif">Aktif</option>
                <option value="Selesai">Selesai</option>
                <option value="Pending">Pending</option>
              </select>
            </div>
            <div>
              <label for="edit-paid-amount" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                DP / Terbayar (Rp)
              </label>
              <input
                id="edit-paid-amount"
                type="number"
                bind:value={paidAmount}
                step="100000"
                class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono"
              />
            </div>
          </div>

          <!-- Tim Pekerja Proyek (Assigned Workers) in Edit Mode -->
          {#if usersList.length > 0}
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-1.5">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                  <span>👥 Tim Pekerja yang Ditugaskan ke Proyek Ini</span>
                  <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-mono">(Tabel users)</span>
                </span>
                <span class="text-[10px] text-slate-400 font-mono">{assignedWorkerIds.length} pekerja aktif</span>
              </div>
              <div class="flex flex-wrap gap-1.5 pt-1">
                {#each usersList as user}
                  {@const isAssigned = assignedWorkerIds.includes(user.id)}
                  <button
                    type="button"
                    onclick={() => {
                      if (isAssigned) {
                        assignedWorkerIds = assignedWorkerIds.filter(id => id !== user.id);
                      } else {
                        assignedWorkerIds = [...assignedWorkerIds, user.id];
                      }
                    }}
                    class="text-xs px-2.5 py-1 rounded-lg border transition flex items-center gap-1.5 cursor-pointer {isAssigned ? 'bg-indigo-50 dark:bg-indigo-950/80 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 font-bold shadow-2xs' : 'bg-white dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-medium hover:border-slate-300'}"
                  >
                    <span>{isAssigned ? '✓' : '+'}</span>
                    <span>{user.name}</span>
                    {#if user.jabatan}
                      <span class="text-[10px] font-normal text-slate-400">({user.jabatan})</span>
                    {/if}
                  </button>
                {/each}
              </div>
            </div>
          {/if}

          <div>
            <label for="edit-github-url" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Repository GitHub
            </label>
            <input
              id="edit-github-url"
              type="text"
              bind:value={githubUrl}
              placeholder="https://github.com/valora-digital/repo"
              class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 font-mono text-[11px]"
            />
          </div>

          <div>
            <label for="edit-app-desc" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Deskripsi Singkat / Ringkasan Fitur
            </label>
            <textarea
              id="edit-app-desc"
              rows="2"
              value={appDesc}
              oninput={(e) => (appDesc = toProperCase(e.currentTarget.value))}
              placeholder="Ringkasan objektif bisnis dan modul utama yang dikerjakan..."
              class="w-full text-xs px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>
        </div>

        <!-- Paket Komponen Standar & Valuasi Dasar -->
        <div class="p-3.5 bg-slate-50 dark:bg-slate-950/70 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2.5">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            Paket Komponen Standar Valora
          </span>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <label class="p-2.5 rounded-xl border flex items-start gap-2.5 cursor-pointer transition {useLanding ? 'bg-indigo-50/50 dark:bg-indigo-950/30 border-indigo-300 dark:border-indigo-700' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 opacity-60'}">
              <input type="checkbox" bind:checked={useLanding} class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                  <p class="text-xs font-bold text-slate-900 dark:text-white">Landing Page</p>
                  <button
                    type="button"
                    onclick={(e) => { e.stopPropagation(); landingPrice = ratesMap.landing || 500000; }}
                    class="text-[9px] text-indigo-600 dark:text-indigo-400 hover:underline font-mono"
                    title="Gunakan tarif acuan master"
                  >
                    Acuan: {formatIDR(ratesMap.landing)}
                  </button>
                </div>
                <input
                  type="number"
                  bind:value={landingPrice}
                  step="50000"
                  class="mt-1 w-full text-[11px] font-mono px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                />
              </div>
            </label>

            <label class="p-2.5 rounded-xl border flex items-start gap-2.5 cursor-pointer transition {useLogin ? 'bg-indigo-50/50 dark:bg-indigo-950/30 border-indigo-300 dark:border-indigo-700' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 opacity-60'}">
              <input type="checkbox" bind:checked={useLogin} class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                  <p class="text-xs font-bold text-slate-900 dark:text-white">Autentikasi Login</p>
                  <button
                    type="button"
                    onclick={(e) => { e.stopPropagation(); loginPrice = ratesMap.login || 350000; }}
                    class="text-[9px] text-indigo-600 dark:text-indigo-400 hover:underline font-mono"
                    title="Gunakan tarif acuan master"
                  >
                    Acuan: {formatIDR(ratesMap.login)}
                  </button>
                </div>
                <input
                  type="number"
                  bind:value={loginPrice}
                  step="50000"
                  class="mt-1 w-full text-[11px] font-mono px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                />
              </div>
            </label>

            <label class="p-2.5 rounded-xl border flex items-start gap-2.5 cursor-pointer transition {useRegister ? 'bg-indigo-50/50 dark:bg-indigo-950/30 border-indigo-300 dark:border-indigo-700' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 opacity-60'}">
              <input type="checkbox" bind:checked={useRegister} class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                  <p class="text-xs font-bold text-slate-900 dark:text-white">Pendaftaran User</p>
                  <button
                    type="button"
                    onclick={(e) => { e.stopPropagation(); registerPrice = ratesMap.register || 350000; }}
                    class="text-[9px] text-indigo-600 dark:text-indigo-400 hover:underline font-mono"
                    title="Gunakan tarif acuan master"
                  >
                    Acuan: {formatIDR(ratesMap.register)}
                  </button>
                </div>
                <input
                  type="number"
                  bind:value={registerPrice}
                  step="50000"
                  class="mt-1 w-full text-[11px] font-mono px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
                />
              </div>
            </label>
          </div>
        </div>

        <!-- Dynamic Roles Tag Builder -->
        <div class="p-3.5 bg-slate-50 dark:bg-slate-950/70 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2.5">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            Konfigurasi Peran / Role Login Proyek
          </span>
          <div class="flex flex-wrap gap-1.5">
            {#each roleTags as role, idx}
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 text-xs font-semibold border border-indigo-200 dark:border-indigo-800">
                <span>{role}</span>
                <button
                  type="button"
                  onclick={() => removeRoleTag(idx)}
                  class="w-3.5 h-3.5 rounded-full hover:bg-indigo-200 dark:hover:bg-indigo-800 flex items-center justify-center text-indigo-500 cursor-pointer"
                  aria-label="Hapus role"
                >
                  &times;
                </button>
              </span>
            {/each}
          </div>
          <div class="flex gap-2">
            <input
              type="text"
              bind:value={newRoleInput}
              onkeydown={handleRoleKeydown}
              placeholder="Ketik nama peran lalu tekan Enter..."
              class="flex-1 text-xs px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
            />
            <button
              type="button"
              onclick={addRoleTag}
              class="px-3 py-1.5 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-xs font-semibold text-slate-800 dark:text-slate-200 cursor-pointer transition"
            >
              + Tambah Role
            </button>
          </div>
        </div>
      </div>
    {/if}
  </form>

  {#snippet footer()}
    <div class="flex items-center justify-end gap-3">
      <button
        type="button"
        onclick={onclose}
        disabled={submitting || internalSubmitting}
        class="min-h-[38px] px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
      >
        Batal
      </button>
      <button
        type="submit"
        form="client-project-main-form"
        disabled={submitting || internalSubmitting}
        class="min-h-[38px] px-5 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-indigo-600 via-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-md shadow-indigo-600/30 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
      >
        {#if submitting || internalSubmitting}
          <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>Menyimpan...</span>
        {:else}
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>
            {mode === 'edit'
              ? 'Simpan Perubahan'
              : 'Simpan & Buka Detail Project'}
          </span>
        {/if}
      </button>
    </div>
  {/snippet}
</Modal>
