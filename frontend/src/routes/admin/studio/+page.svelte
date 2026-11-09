<script>
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/state';
  import { api } from '$lib/api/client.js';
  import { toastState } from '$lib/state/toast.svelte.js';
  import { scopingState } from '$lib/state/scoping.svelte.js';
  import { tactileAudio } from '$lib/utils/audioFeedback.js';
  import Badge from '$lib/components/ui/Badge.svelte';
  import ElementLoader from '$lib/components/ui/ElementLoader.svelte';

  // Navigation tab state (5-Stage Two-Prompt Stepper + Utilities)
  let activeTab = $state('dashboard'); // 'dashboard' | 'prompt-a' | 'workspace' | 'compiler' | 'prompt-b' | 'provision' | 'modules'
  let workspaceSubMode = $state('canvas'); // 'canvas' | 'erd'
  let loadingModules = $state(false);
  let savingSpec = $state(false);
  let modulesList = $state([]);
  let designApproved = $state(false);

  // Raw Brief Recommender state
  let analyzingBrief = $state(false);
  let priorityFilter = $state('all'); // 'all' | 'P0' | 'P1'

  // Client & Project Edit Modal State
  let clientProjectModal = $state(false);
  let projectEditForm = $state({
    clientName: '',
    projectName: '',
    slug: '',
    appType: 'webapp',
    authMechanism: 'login_only',
    targetDeliveryDays: 25
  });

  // Role CRUD Modal State
  let roleModal = $state(false);
  let roleModalMode = $state('create'); // 'create' | 'edit'
  let roleForm = $state({
    id: '',
    name: '',
    slug: '',
    description: '',
    color: '#4f46e5'
  });

  // Module CRUD Modal State
  let moduleModal = $state(false);
  let moduleModalMode = $state('create'); // 'create' | 'edit'
  let moduleForm = $state({
    id: '',
    name: '',
    roleId: 'role-1',
    description: '',
    tableName: ''
  });

  // Feature CRUD Modal State
  let featureModal = $state(false);
  let featureModalMode = $state('create'); // 'create' | 'edit'
  let targetModuleForFeature = $state(null);
  let featureForm = $state({
    id: '',
    name: '',
    complexity: 'crud',
    purpose: ''
  });

  // Dynamic Live Client Sandbox Simulator Input State
  let simFormData = $state({});

  // Interactive Live Mockup CRUD Modal State
  let simModal = $state(false);
  let simModalMode = $state('create'); // 'create' | 'edit'
  let simEditingId = $state(null);
  let simRecordForm = $state({});

  // Tactical CRUD Live Mockup Controls (Standard Emas Pegawai PQTM)
  let simSearchQuery = $state('');
  let simFilterValue = $state('semua');
  let simPage = $state(1);
  let simPerPage = $state(10); // '5' | '10' | '15' | '25' | 'semua'
  let showDetailSimModal = $state(false);
  let selectedDetailSimRecord = $state(null);
  let showDeleteSimModal = $state(false);
  let selectedDeleteSimRecord = $state(null);
  let simFormTab = $state('utama'); // 'utama' | 'detail' (sub-tabs jika field > 6)

  // Otomatis deteksi field status/kategori untuk dropdown filter
  let simFilterConfig = $derived.by(() => {
    const mod = scopingState.activeModule;
    if (!mod || !mod.entity) return null;
    const fields = mod.entity.fields || [];
    const targetField = fields.find((f) => f.name === 'status' || f.name === 'role' || f.name === 'type' || f.name === 'category' || f.name === 'approval_status' || f.inputType === 'select');
    if (!targetField) return null;
    const stream = mod.entity.mockStream || [];
    const options = [...new Set(stream.map((item) => item[targetField.name]).filter(Boolean))];
    return {
      field: targetField.name,
      label: targetField.label,
      options
    };
  });

  let simNonPkFields = $derived.by(() => {
    return (scopingState.activeModule?.entity?.fields || []).filter((f) => f.key !== 'PK');
  });

  let simFormFieldsToShow = $derived.by(() => {
    const list = simNonPkFields;
    if (list.length <= 5) return list;
    const mid = Math.ceil(list.length / 2);
    return simFormTab === 'utama' ? list.slice(0, mid) : list.slice(mid);
  });

  // Pure Svelte 5 In-Memory Filtering (0 Server Request, < 0.1ms)
  let filteredSimData = $derived.by(() => {
    const stream = scopingState.activeModule?.entity?.mockStream || [];
    const query = simSearchQuery.trim().toLowerCase();
    const filterField = simFilterConfig?.field;

    return stream.filter((item) => {
      if (simFilterValue !== 'semua' && filterField) {
        if (String(item[filterField]) !== simFilterValue) return false;
      }
      if (!query) return true;
      return Object.values(item).some((val) => 
        val !== null && val !== undefined && String(val).toLowerCase().includes(query)
      );
    });
  });

  // Paginasi Client-Side
  let totalSimPages = $derived(
    simPerPage === 'semua' ? 1 : Math.max(1, Math.ceil(filteredSimData.length / Number(simPerPage)))
  );

  let paginatedSimData = $derived.by(() => {
    if (simPerPage === 'semua') return filteredSimData;
    const perPage = Number(simPerPage);
    const start = (simPage - 1) * perPage;
    return filteredSimData.slice(start, start + perPage);
  });

  // Dynamic Summary Stat Pills (Mirip Pegawai PQTM)
  let simSummaryPills = $derived.by(() => {
    const stream = scopingState.activeModule?.entity?.mockStream || [];
    const filterField = simFilterConfig?.field;
    const pills = [{ label: 'Total', count: stream.length, color: 'text-stone-700 dark:text-stone-300 font-bold' }];
    if (filterField && simFilterConfig?.options) {
      for (const opt of simFilterConfig.options.slice(0, 3)) {
        const count = stream.filter((item) => item[filterField] === opt).length;
        pills.push({ label: opt, count, color: 'text-indigo-600 dark:text-indigo-400 font-semibold' });
      }
    }
    return pills;
  });

  $effect(() => {
    // Reset ke halaman 1 saat pencarian, filter, per-page, atau fitur aktif berubah
    simSearchQuery; simFilterValue; simPerPage; scopingState.selectedFeatureId;
    simPage = 1;
  });

  // Provisioning Wizard Form state
  let provisionForm = $state({
    name: 'valora-vault',
    description: 'Agency ERP & Syariah Platform - Repositori Turunan Resmi',
    visibility: 'private',
    frontendPort: 5174,
    backendPort: 9005
  });

  let filteredRecommendations = $derived.by(() => {
    const recs = scopingState.caseStudy.recommendations || [];
    if (priorityFilter === 'P0') {
      return recs.filter((r) => r.priority.includes('P0'));
    }
    if (priorityFilter === 'P1') {
      return recs.filter((r) => r.priority.includes('P1'));
    }
    return recs;
  });

  let selectedRecommendationsCount = $derived.by(() => {
    return (scopingState.caseStudy.recommendations || []).filter((r) => r.selected).length;
  });

  let provisionCliCommand = $derived.by(() => {
    return `php scripts/provision_repo.php --name="${provisionForm.name}" --desc="${provisionForm.description}" --visibility=${provisionForm.visibility} --fe-port=${provisionForm.frontendPort} --be-port=${provisionForm.backendPort}`;
  });

  // Sync activeTab with URL search params ?tab=
  $effect(() => {
    const tabParam = page.url.searchParams.get('tab');
    const validTabs = ['dashboard', 'prompt-a', 'workspace', 'compiler', 'prompt-b', 'provision', 'modules'];
    if (tabParam && validTabs.includes(tabParam) && activeTab !== tabParam) {
      activeTab = tabParam;
    }
  });

  function setTab(tab) {
    tactileAudio.playTap();
    activeTab = tab;
    const currentUrl = new URL(page.url);
    if (currentUrl.searchParams.get('tab') !== tab) {
      currentUrl.searchParams.set('tab', tab);
      goto(currentUrl.toString(), { replaceState: true, noScroll: true, keepFocus: true });
    }
  }

  // Tactile CRUD Modal Actions for Live Workspace Simulation Records
  function openCreateSimRecord() {
    const mod = scopingState.activeModule;
    if (!mod || !mod.entity) return;
    simModalMode = 'create';
    simEditingId = null;
    simFormTab = 'utama';
    const initial = {};
    for (const f of (mod.entity.fields || [])) {
      if (f.key !== 'PK') {
        if (f.inputType === 'select' && f.options && f.options.length > 0) {
          initial[f.name] = f.options[0];
        } else {
          initial[f.name] = '';
        }
      }
    }
    simRecordForm = initial;
    simModal = true;
  }

  function openEditSimRecord(item) {
    const mod = scopingState.activeModule;
    if (!mod || !mod.entity) return;
    simModalMode = 'edit';
    simEditingId = item.id;
    simFormTab = 'utama';
    const data = {};
    for (const f of (mod.entity.fields || [])) {
      if (f.key !== 'PK') {
        data[f.name] = item[f.name] !== undefined ? item[f.name] : '';
      }
    }
    simRecordForm = data;
    simModal = true;
  }

  function openDetailSimRecord(item) {
    selectedDetailSimRecord = item;
    showDetailSimModal = true;
  }

  function openDeleteSimModal(item) {
    selectedDeleteSimRecord = item;
    showDeleteSimModal = true;
  }

  function confirmDeleteSimRecord() {
    const mod = scopingState.activeModule;
    if (!mod || !selectedDeleteSimRecord) return;
    scopingState.removeMockEntry(mod.id, selectedDeleteSimRecord.id);
    toastState.success(`Baris data #${selectedDeleteSimRecord.id} berhasil dihapus dari tabel simulasi.`);
    showDeleteSimModal = false;
    selectedDeleteSimRecord = null;
  }

  function handleExportSimCsv() {
    tactileAudio.playSuccess();
    const mod = scopingState.activeModule;
    if (!mod || !mod.entity) return;
    const fields = mod.entity.fields || [];
    const headers = fields.map((f) => `"${f.label.replace(/"/g, '""')}"`).join(',');
    const rows = filteredSimData.map((item) => {
      return fields.map((f) => `"${String(item[f.name] ?? '').replace(/"/g, '""')}"`).join(',');
    });
    const csvContent = [headers, ...rows].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `${mod.entity.tableName || 'data'}_export.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    toastState.success(`Ekspor CSV ${filteredSimData.length} baris data tabel [${mod.entity.tableName}] berhasil diunduh!`, 'Ekspor CSV');
  }

  function saveSimRecord() {
    const mod = scopingState.activeModule;
    if (!mod || !mod.entity) return;

    const hasValue = Object.values(simRecordForm).some((v) => String(v).trim().length > 0);
    if (!hasValue) {
      toastState.error('Isi minimal satu kolom formulir sebelum menyimpan data simulasi!');
      return;
    }

    if (simModalMode === 'create') {
      const entry = { ...simRecordForm };
      if (entry.code !== undefined && !entry.code) {
        const prefix = mod.slug.toUpperCase().slice(0, 3);
        entry.code = `${prefix}-${Math.floor(100 + Math.random() * 900)}`;
      }
      scopingState.addMockEntry(mod.id, entry);
      toastState.success(`Data baru berhasil ditambahkan ke tabel [${mod.entity.tableName}]!`, 'Data Ditambahkan');
    } else {
      scopingState.updateMockEntry(mod.id, simEditingId, simRecordForm);
      toastState.success(`Data record #${simEditingId} berhasil diperbarui di tabel [${mod.entity.tableName}]!`, 'Data Diperbarui');
    }
    simModal = false;
  }

  function deleteSimRecord(itemId) {
    const mod = scopingState.activeModule;
    if (!mod) return;
    const item = (mod.entity.mockStream || []).find((r) => r.id === itemId);
    if (item) {
      openDeleteSimModal(item);
    } else {
      scopingState.removeMockEntry(mod.id, itemId);
      toastState.success(`Baris data #${itemId} berhasil dihapus dari tabel simulasi.`);
    }
  }

  onMount(async () => {
    if ((scopingState.caseStudy.recommendations || []).length === 0) {
      scopingState.analyzeAndRecommendFeatures(scopingState.caseStudy.rawBrief);
    }
    initSimForm();
    await fetchExistingModules();
  });

  // Initialize Simulator Form based on active module entity fields
  function initSimForm() {
    const fields = scopingState.activeModule?.entity?.fields || [];
    const initial = {};
    for (const f of fields) {
      if (f.key !== 'PK') {
        if (f.inputType === 'select' && f.options && f.options.length > 0) {
          initial[f.name] = f.options[0];
        } else {
          initial[f.name] = '';
        }
      }
    }
    simFormData = initial;
  }

  function handleSelectFeature(featId) {
    scopingState.selectedFeatureId = featId;
    initSimForm();
  }

  function handleAnalyzeBrief() {
    analyzingBrief = true;
    try {
      const res = scopingState.analyzeAndRecommendFeatures(scopingState.caseStudy.rawBrief);
      toastState.success(
        `Analisis tuntas! Domain: ${res.domainName} (${res.confidence}% match). ${res.featuresCount} fitur terdeteksi.`,
        'Rekomendasi Siap'
      );
    } catch (e) {
      toastState.error('Gagal menganalisis deskripsi kebutuhan.');
    } finally {
      analyzingBrief = false;
    }
  }

  function handleApplyRecommendations() {
    const ok = scopingState.applyRecommendations();
    if (ok) {
      initSimForm();
      toastState.success(
        'Rekomendasi fitur berhasil diterapkan ke Matriks Modul & Klien!',
        'Matriks Terpasang'
      );
    } else {
      toastState.error('Pilih minimal satu fitur rekomendasi untuk diterapkan ke matriks.');
    }
  }

  async function fetchExistingModules() {
    loadingModules = true;
    try {
      const res = await api.request('/api/v1/admin/studio/modules');
      if (res && res.data) {
        modulesList = res.data;
      }
    } catch (e) {
      console.warn('Gagal memuat modul studio:', e);
    } finally {
      loadingModules = false;
    }
  }

  // Live Simulator Entry Action
  function handleSimulateEntry() {
    const mod = scopingState.activeModule;
    if (!mod || !mod.entity) return;

    // Build entry from simFormData
    const entry = { ...simFormData };
    const fields = mod.entity.fields.filter(f => f.key !== 'PK');

    // Basic validation: ensure at least one text field is filled
    const hasValue = Object.values(entry).some(v => String(v).trim().length > 0);
    if (!hasValue) {
      toastState.error('Isi formulir simulasi sebelum menekan tombol entri!');
      return;
    }

    // Auto-generate code if empty and code field exists
    if (entry.code !== undefined && !entry.code) {
      const prefix = mod.slug.toUpperCase().slice(0, 3);
      entry.code = `${prefix}-${Math.floor(100 + Math.random() * 900)}`;
    }

    scopingState.addMockEntry(mod.id, entry);
    toastState.success(`Simulasi entri berhasil dicatat ke tabel [${mod.entity.tableName}]!`, 'Simulasi Sukses');
    initSimForm();
  }

  function handleApproveDesign() {
    designApproved = true;
    scopingState.project.status = 'Approved (Design Frozen)';
    toastState.success('Desain living mockup canvas telah disetujui (Design Freeze Milestone tercapai).', 'Design Sign-Off');
    setTab('compiler');
  }

  async function handleSaveSpecification() {
    savingSpec = true;
    try {
      const payload = {
        code: scopingState.modules[0]?.code || '01',
        slug: scopingState.project.slug,
        title: scopingState.project.projectName,
        module: scopingState.modules[0]?.name || 'General Module',
        actor: scopingState.roles.map((r) => r.name).join(', '),
        status: designApproved ? 'Approved' : 'Draft',
        user_story: {
          role: scopingState.roles[0]?.name || 'Administrator',
          want: 'mengelola arsitektur sistem berbasis kontrak SDD deterministik',
          so_that: 'operasional bisnis berjalan akuntabel dan zero-hallucination'
        },
        raw_markdown: scopingState.compileSddMarkdown()
      };

      const res = await api.request('/api/v1/admin/studio/generate-spec', {
        method: 'POST',
        body: payload
      });

      toastState.success(res.message || `Kontrak SDD berhasil disinkronkan ke doc/features/`, 'Sinkronisasi SDD Sukses');
      await fetchExistingModules();
    } catch (e) {
      toastState.error(e?.data?.message || 'Gagal menyimpan spesifikasi.');
    } finally {
      savingSpec = false;
    }
  }

  function copyPromptStageA() {
    navigator.clipboard.writeText(scopingState.compilePromptStageA());
    toastState.info('Prompt Tahap A (UI Mockup & ERD) disalin ke clipboard!', 'Prompt A Ready');
  }

  function copyPromptStageB() {
    navigator.clipboard.writeText(scopingState.compilePromptStageB());
    toastState.info('Prompt Tahap B (Eksekusi 100% Produksi) disalin ke clipboard!', 'Prompt B Ready');
  }

  function copySddMarkdown() {
    navigator.clipboard.writeText(scopingState.compileSddMarkdown());
    toastState.info('Kontrak SDD Markdown disalin ke clipboard!', 'Tersalin');
  }

  function downloadSddMarkdown() {
    const md = scopingState.compileSddMarkdown();
    const blob = new Blob([md], { type: 'text/markdown;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${scopingState.project.slug}.sdd.md`;
    link.click();
    URL.revokeObjectURL(url);
    toastState.success(`Berkas ${scopingState.project.slug}.sdd.md berhasil diunduh.`, 'Download Berhasil');
  }

  function copyProvisionCommand() {
    navigator.clipboard.writeText(provisionCliCommand);
    toastState.info('Perintah CLI Provisioning disalin ke clipboard!', 'CLI Ready');
  }

  // Client & Project Modal Actions
  function openEditClientProject() {
    projectEditForm = {
      clientName: scopingState.project.clientName,
      projectName: scopingState.project.projectName,
      slug: scopingState.project.slug,
      appType: scopingState.project.appType,
      authMechanism: scopingState.project.authMechanism,
      targetDeliveryDays: scopingState.project.targetDeliveryDays
    };
    clientProjectModal = true;
  }

  function saveClientProject() {
    if (!projectEditForm.projectName.trim() || !projectEditForm.clientName.trim()) {
      toastState.error('Nama proyek dan nama klien wajib diisi!');
      return;
    }
    scopingState.updateProject(projectEditForm);
    toastState.success('Parameter klien dan proyek berhasil diperbarui!');
    clientProjectModal = false;
  }

  // Role CRUD Actions
  function openCreateRole() {
    roleModalMode = 'create';
    roleForm = { id: '', name: '', slug: '', description: '', color: '#4f46e5' };
    roleModal = true;
  }

  function openEditRole(role) {
    roleModalMode = 'edit';
    roleForm = { ...role };
    roleModal = true;
  }

  function saveRole() {
    if (!roleForm.name.trim()) {
      toastState.error('Nama peran wajib diisi!');
      return;
    }
    if (roleModalMode === 'create') {
      scopingState.addRole(roleForm);
      toastState.success(`Peran [${roleForm.name}] berhasil ditambahkan!`);
    } else {
      scopingState.updateRole(roleForm.id, roleForm);
      toastState.success(`Peran [${roleForm.name}] berhasil diperbarui!`);
    }
    roleModal = false;
  }

  function handleDeleteRole(roleId) {
    if (scopingState.roles.length <= 1) {
      toastState.error('Sistem membutuhkan minimal 1 peran aktif!');
      return;
    }
    if (confirm('Hapus peran ini? Modul terkait akan dialihkan ke peran lain.')) {
      scopingState.removeRole(roleId);
      toastState.success('Peran berhasil dihapus.');
    }
  }

  // Module CRUD Actions
  function openCreateModule() {
    moduleModalMode = 'create';
    moduleForm = {
      id: '',
      name: '',
      roleId: scopingState.roles[0]?.id || 'role-1',
      description: '',
      tableName: ''
    };
    moduleModal = true;
  }

  function openEditModule(mod) {
    moduleModalMode = 'edit';
    moduleForm = {
      id: mod.id,
      name: mod.name,
      roleId: mod.roleId,
      description: mod.description,
      tableName: mod.entity?.tableName || mod.slug
    };
    moduleModal = true;
  }

  function saveModule() {
    if (!moduleForm.name.trim()) {
      toastState.error('Nama modul wajib diisi!');
      return;
    }
    if (moduleModalMode === 'create') {
      scopingState.addModule(moduleForm);
      toastState.success(`Modul [${moduleForm.name}] berhasil ditambahkan!`);
    } else {
      scopingState.updateModule(moduleForm.id, moduleForm);
      toastState.success(`Modul [${moduleForm.name}] berhasil diperbarui!`);
    }
    initSimForm();
    moduleModal = false;
  }

  function handleDeleteModule(moduleId) {
    if (scopingState.modules.length <= 1) {
      toastState.error('Sistem membutuhkan minimal 1 modul aktif!');
      return;
    }
    if (confirm('Hapus modul ini beserta seluruh fiturnya?')) {
      scopingState.removeModule(moduleId);
      toastState.success('Modul berhasil dihapus.');
      initSimForm();
    }
  }

  // Feature CRUD Actions
  function openCreateFeature(mod) {
    targetModuleForFeature = mod;
    featureModalMode = 'create';
    featureForm = { id: '', name: '', complexity: 'crud', purpose: '' };
    featureModal = true;
  }

  function openEditFeature(mod, feat) {
    targetModuleForFeature = mod;
    featureModalMode = 'edit';
    featureForm = {
      id: feat.id,
      name: feat.name,
      complexity: feat.complexity,
      purpose: feat.purpose || ''
    };
    featureModal = true;
  }

  function saveFeature() {
    if (!featureForm.name.trim()) {
      toastState.error('Nama fitur wajib diisi!');
      return;
    }
    if (featureModalMode === 'create') {
      scopingState.addFeature(targetModuleForFeature.id, featureForm);
      toastState.success(`Fitur [${featureForm.name}] berhasil ditambahkan!`);
    } else {
      scopingState.updateFeature(targetModuleForFeature.id, featureForm.id, featureForm);
      toastState.success(`Fitur [${featureForm.name}] berhasil diperbarui!`);
    }
    featureModal = false;
  }

  function handleDeleteFeature(modId, featId) {
    const mod = scopingState.modules.find(m => m.id === modId);
    if (mod && mod.features.length <= 1) {
      toastState.error('Modul membutuhkan minimal 1 fitur aktif!');
      return;
    }
    if (confirm('Hapus fitur ini?')) {
      scopingState.removeFeature(modId, featId);
      toastState.success('Fitur berhasil dihapus.');
      initSimForm();
    }
  }
</script>

<div class="space-y-6 max-w-7xl mx-auto pb-20">
  <!-- Top Navigation Header (Apple Spatial Materials & Specular Chamfer) -->
  <div class="bg-white/85 dark:bg-[#090d16]/85 backdrop-blur-xl p-5 sm:p-6 rounded-2xl border border-stone-200/80 dark:border-white/10 shadow-xs space-y-4 relative overflow-hidden before:pointer-events-none before:absolute before:inset-x-0 before:top-0 before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/30 dark:before:via-white/15 before:to-transparent">
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
      <div class="space-y-1">
        <div class="flex items-center gap-3">
          <span class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-2xl shadow-xs">
            📐
          </span>
          <div>
            <div class="flex flex-wrap items-center gap-3">
              <h1 class="text-2xl font-black tracking-tight text-stone-900 dark:text-stone-100">
                Universal Project Scoping Studio
              </h1>
              <Badge variant="outline" class="text-xs bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border-indigo-300 font-semibold">
                SDD v8.0.0 Deterministic
              </Badge>
              <button
                type="button"
                onclick={openEditClientProject}
                class="px-3 py-1 rounded-lg border border-stone-300 dark:border-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800 text-xs font-semibold text-stone-700 dark:text-stone-300 flex items-center gap-1 cursor-pointer transition-all"
              >
                ✏️ Parameter Klien & Proyek
              </button>
            </div>
            <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-400 mt-0.5">
              Architect Studio: Analisis brief mentah, kelola CRUD modul & role, simulasikan living mockup sesuai kolom ERD, dan ekspor kontrak SDD.
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Cluster Status Indicator -->
      <div class="flex items-center gap-2 self-start xl:self-auto shrink-0">
        {#if activeTab === 'provision' || activeTab === 'modules'}
          <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800 flex items-center gap-1.5 shadow-2xs">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Mode: Utilitas & Generator
          </span>
        {:else}
          <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-800 flex items-center gap-1.5 shadow-2xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Mode: Pipeline Scoping SDD
          </span>
        {/if}
      </div>
    </div>

    <!-- Compartmentalized Dual-Cluster Navigation Tabs -->
    <div class="pt-3 border-t border-stone-100 dark:border-stone-800 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
      <!-- Kluster Utama: SDD Scoping Pipeline (5 Tahap Utama) -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-2">
        <span class="text-[10px] font-bold uppercase tracking-wider text-stone-600 dark:text-stone-400 font-mono shrink-0">
          Pipeline Scoping SDD:
        </span>
        <div class="flex flex-wrap items-center gap-1 bg-stone-100 dark:bg-stone-800 p-1 rounded-xl border border-stone-200 dark:border-stone-700">
          <button
            type="button"
            onclick={() => setTab('dashboard')}
            class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all {activeTab === 'dashboard' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900'}"
            aria-label="Tahap 1: Intake & Matriks"
          >
            1. 📝 Intake <span class="hidden sm:inline">& Matriks</span>
          </button>
          <button
            type="button"
            onclick={() => setTab('prompt-a')}
            class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all {activeTab === 'prompt-a' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900'}"
            aria-label="Tahap 2: Prompt Tahap A (UI Mockup & ERD)"
          >
            2. 🤖 Prompt A <span class="hidden sm:inline">(UI & ERD)</span>
          </button>
          <button
            type="button"
            onclick={() => setTab('workspace')}
            class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all {activeTab === 'workspace' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900'}"
            aria-label="Tahap 3: Live Workspace (Mockup & ERD)"
          >
            3. 🎨 <span class="hidden sm:inline">Live </span>Workspace
          </button>
          <button
            type="button"
            onclick={() => setTab('compiler')}
            class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all {activeTab === 'compiler' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900'}"
            aria-label="Tahap 4: Approval SDD (.md)"
          >
            4. 📄 Approval<span class="hidden sm:inline"> SDD</span>
          </button>
          <button
            type="button"
            onclick={() => setTab('prompt-b')}
            class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all {activeTab === 'prompt-b' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900'}"
            aria-label="Tahap 5: Prompt Tahap B (Eksekusi Produksi)"
          >
            5. ⚡ Prompt B <span class="hidden sm:inline">(Produksi)</span>
          </button>
        </div>
      </div>

      <!-- Kluster Terpisah: Ekosistem Generator & Repositori -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-2">
        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 font-mono shrink-0">
          Utilitas & Generator:
        </span>
        <div class="flex flex-wrap items-center gap-1 bg-amber-500/10 dark:bg-amber-950/20 p-1 rounded-xl border border-amber-200/80 dark:border-amber-800/60">
          <button
            type="button"
            onclick={() => setTab('provision')}
            class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all {activeTab === 'provision' ? 'bg-white dark:bg-stone-900 text-amber-700 dark:text-amber-400 shadow-xs font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900'}"
            aria-label="Tab Repository Provisioning Wizard"
          >
            🚀 Repo Wizard
          </button>
          <button
            type="button"
            onclick={() => setTab('modules')}
            class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all {activeTab === 'modules' ? 'bg-white dark:bg-stone-900 text-amber-700 dark:text-amber-400 shadow-xs font-bold' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900'}"
            aria-label="Tab Modul Terdaftar"
          >
            🗄️ Modul Aktif
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 1: VISUAL STUDIO DASHBOARD & INTAKE (RAW BRIEF & SMART RECOMMENDER) -->
  {#if activeTab === 'dashboard'}
    <div class="space-y-6">
      <!-- 1. Raw Brief Intake & Smart Feature Recommender -->
      <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-200 dark:border-stone-800 pb-3">
          <div>
            <h2 class="text-base font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
              📝 Tahap 1: Deskripsi Kebutuhan Mentah & Smart Feature Recommender
            </h2>
            <p class="text-xs text-stone-500">
              Klien/Developer cukup memasukkan deskripsi bebas, engine akan menganalisis domain dan menyusun rekomendasi fitur.
            </p>
          </div>
          <div class="flex items-center gap-2">
            {#if scopingState.caseStudy.detectedDomain}
              <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-700 dark:text-indigo-300 font-semibold">
                <span>🎯 {scopingState.caseStudy.detectedDomain}</span>
                <span class="text-[10px] bg-indigo-600 text-white px-1.5 py-0.5 rounded-full font-mono">
                  {scopingState.caseStudy.confidenceScore}% Match
                </span>
              </div>
            {/if}
          </div>
        </div>

        <!-- Raw Textarea Input -->
        <div class="space-y-3">
          <label for="raw-brief-input" class="block text-xs font-semibold text-stone-700 dark:text-stone-300">
            Deskripsi Kebutuhan Mentah (Freeform / Natural Language):
          </label>
          <textarea
            id="raw-brief-input"
            bind:value={scopingState.caseStudy.rawBrief}
            rows="3"
            class="w-full px-4 py-3 text-xs rounded-xl border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-100 focus:ring-2 focus:ring-indigo-500 leading-relaxed"
            placeholder="Contoh: Klien membutuhkan aplikasi web untuk mencatat seluruh proyek IT, manajemen klien, pencatatan keuangan transparan, audit multi-peran, dan dokumentasi aturan bisnis internal..."
          ></textarea>

          <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <button
              type="button"
              onclick={handleAnalyzeBrief}
              disabled={analyzingBrief}
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-2 cursor-pointer"
            >
              <span>{analyzingBrief ? '⏳ Menganalisis Domain...' : '💡 Analisis Kebutuhan & Rekomendasikan Fitur'}</span>
            </button>

            {#if (scopingState.caseStudy.recommendations || []).length > 0}
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  onclick={() => scopingState.toggleAllRecommendations(true)}
                  class="px-2.5 py-1 text-[11px] font-semibold rounded-lg border border-stone-200 dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800"
                >
                  Pilih Semua
                </button>
                <button
                  type="button"
                  onclick={() => scopingState.toggleAllRecommendations(false)}
                  class="px-2.5 py-1 text-[11px] font-semibold rounded-lg border border-stone-200 dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800"
                >
                  Kosongkan
                </button>
                <button
                  type="button"
                  onclick={handleApplyRecommendations}
                  class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                >
                  🚀 Terapkan ke Matriks ({selectedRecommendationsCount} Fitur Dipilih)
                </button>
              </div>
            {/if}
          </div>
        </div>

        <!-- Recommended Feature Cards Grid -->
        {#if (scopingState.caseStudy.recommendations || []).length > 0}
          <div class="pt-4 border-t border-stone-100 dark:border-stone-800 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-stone-300">
                Fitur yang Direkomendasikan untuk Anda:
              </span>
              <div class="flex items-center gap-1.5 text-xs">
                <button
                  type="button"
                  onclick={() => priorityFilter = 'all'}
                  class="px-2.5 py-1 rounded-md text-[11px] font-semibold {priorityFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-stone-100 dark:bg-stone-800 text-stone-600'}"
                >
                  Semua ({scopingState.caseStudy.recommendations.length})
                </button>
                <button
                  type="button"
                  onclick={() => priorityFilter = 'P0'}
                  class="px-2.5 py-1 rounded-md text-[11px] font-semibold {priorityFilter === 'P0' ? 'bg-indigo-600 text-white' : 'bg-stone-100 dark:bg-stone-800 text-stone-600'}"
                >
                  P0 Core MVP
                </button>
                <button
                  type="button"
                  onclick={() => priorityFilter = 'P1'}
                  class="px-2.5 py-1 rounded-md text-[11px] font-semibold {priorityFilter === 'P1' ? 'bg-indigo-600 text-white' : 'bg-stone-100 dark:bg-stone-800 text-stone-600'}"
                >
                  P1 Essential
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              {#each filteredRecommendations as rec}
                <div
                  onclick={() => scopingState.toggleRecommendation(rec.id)}
                  onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { scopingState.toggleRecommendation(rec.id); e.preventDefault(); } }}
                  role="button"
                  tabindex="0"
                  class="p-3.5 rounded-xl border text-left cursor-pointer transition-all flex flex-col justify-between gap-2 {rec.selected ? 'border-indigo-500 bg-indigo-50/40 dark:bg-indigo-950/20 ring-1 ring-indigo-500/20' : 'border-stone-200 dark:border-stone-800 opacity-60 hover:opacity-100'}"
                >
                  <div class="space-y-1.5">
                    <div class="flex items-center justify-between gap-2">
                      <span class="inline-flex items-center gap-1.5 text-xs font-bold {rec.selected ? 'text-indigo-900 dark:text-indigo-200' : 'text-stone-600 dark:text-stone-400'}">
                        <input
                          type="checkbox"
                          checked={rec.selected}
                          onchange={() => scopingState.toggleRecommendation(rec.id)}
                          onclick={(e) => e.stopPropagation()}
                          class="rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                        />
                        {rec.name}
                      </span>
                      <Badge variant="outline" class="text-[10px] {rec.priority.includes('P0') ? 'border-amber-300 text-amber-700 bg-amber-50 dark:bg-amber-950/40' : 'border-stone-300 text-stone-600'}">
                        {rec.priority}
                      </Badge>
                    </div>
                    <p class="text-[11px] text-stone-500 pl-5">
                      Modul: <strong>{rec.moduleName}</strong> ({rec.roleSlug})
                    </p>
                  </div>
                  <div class="flex items-center justify-between text-[11px] text-stone-500 pt-1 border-t border-stone-100 dark:border-stone-800 pl-5">
                    <span class="uppercase font-mono text-[10px]">{rec.complexity}</span>
                    <span class="font-medium text-stone-700 dark:text-stone-300">Siap Diterapkan</span>
                  </div>
                </div>
              {/each}
            </div>
          </div>
        {/if}
      </div>

      <!-- 2. Clean Architectural Metric Summary Boxes -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Fitur & Modul -->
        <div class="bg-white dark:bg-stone-900 p-5 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-2">
          <div class="flex items-center justify-between text-stone-500 text-xs font-semibold">
            <span>TOTAL FITUR & MODUL</span>
            <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-600">📦</span>
          </div>
          <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400">
            {scopingState.totalFeatures} <span class="text-sm font-semibold text-stone-500">Fitur</span>
          </div>
          <p class="text-xs text-stone-500">
            Tersebar di <strong>{scopingState.totalModules} Modul Arsitektur</strong>
          </p>
        </div>

        <!-- Metric 2: Hierarki Peran RBAC -->
        <div class="bg-white dark:bg-stone-900 p-5 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-2">
          <div class="flex items-center justify-between text-stone-500 text-xs font-semibold">
            <span>AKTOR SISTEM (RBAC)</span>
            <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600">👥</span>
          </div>
          <div class="text-3xl font-black text-stone-900 dark:text-stone-100">
            {scopingState.totalRoles} <span class="text-sm font-semibold text-stone-500">Peran Resmi</span>
          </div>
          <p class="text-xs text-stone-500">
            Mekanisme auth: <strong>{scopingState.project.authMechanism}</strong>
          </p>
        </div>

        <!-- Metric 3: Komposisi Rekayasa -->
        <div class="bg-white dark:bg-stone-900 p-5 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-2">
          <div class="flex items-center justify-between text-stone-500 text-xs font-semibold">
            <span>KOMPOSISI REKAYASA</span>
            <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600">⚙️</span>
          </div>
          <div class="text-2xl font-black text-stone-900 dark:text-stone-100">
            {scopingState.complexityComposition.crud.percentage}% <span class="text-xs font-medium text-stone-500">CRUD</span> / {scopingState.complexityComposition.customLogic.percentage}% <span class="text-xs font-medium text-stone-500">Logika</span>
          </div>
          <p class="text-xs text-stone-500">
            Integrasi API: <strong>{scopingState.complexityComposition.integration.percentage}%</strong>
          </p>
        </div>

        <!-- Metric 4: Status Kesiapan Kontrak SDD -->
        <div class="bg-white dark:bg-stone-900 p-5 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-2">
          <div class="flex items-center justify-between text-stone-500 text-xs font-semibold">
            <span>STATUS KONTRAK SDD</span>
            <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-600">🔒</span>
          </div>
          <div class="text-lg font-black {designApproved ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-900 dark:text-stone-100'}">
            {designApproved ? '✓ Design Frozen' : scopingState.project.status}
          </div>
          <p class="text-xs text-stone-500">
            Validasi: <strong>{designApproved ? 'Siap Eksekusi Produksi' : 'Menunggu Approval Mockup'}</strong>
          </p>
        </div>
      </div>

      <!-- 3. Granular Role CRUD Section -->
      <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-3">
          <div>
            <h3 class="text-base font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
              👥 Hierarki Peran & Hak Akses (Role RBAC)
            </h3>
            <p class="text-xs text-stone-500">Kelola peran sistem yang memiliki otoritas Policy dan tanggung jawab modul.</p>
          </div>
          <button
            type="button"
            onclick={openCreateRole}
            class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
          >
            ➕ Tambah Peran
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {#each scopingState.roles as r}
            <div class="p-4 rounded-xl border border-stone-200 dark:border-stone-800 bg-stone-50/50 dark:bg-stone-800/30 flex flex-col justify-between gap-3">
              <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <span class="inline-flex items-center gap-1.5 text-xs font-bold" style="color: {r.color};">
                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {r.color};"></span>
                    {r.name}
                  </span>
                  <span class="font-mono text-[10px] text-stone-400">({r.slug})</span>
                </div>
                <p class="text-xs text-stone-600 dark:text-stone-400 leading-relaxed">{r.description}</p>
              </div>
              <div class="flex items-center justify-end gap-1.5 pt-2 border-t border-stone-100 dark:border-stone-800">
                <button
                  type="button"
                  onclick={() => openEditRole(r)}
                  class="px-2 py-1 text-[11px] font-semibold rounded-md border border-stone-200 dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-600 dark:text-stone-300"
                >
                  ✏️ Edit
                </button>
                <button
                  type="button"
                  onclick={() => handleDeleteRole(r.id)}
                  class="px-2 py-1 text-[11px] font-semibold rounded-md border border-rose-200 dark:border-rose-900/50 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-rose-600 dark:text-rose-400"
                >
                  🗑️ Hapus
                </button>
              </div>
            </div>
          {/each}
        </div>
      </div>

      <!-- 4. Charts Row: Distribusi Fitur & Komposisi Rekayasa -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Distribusi Fitur per Role (Chart Batang Reaktif) -->
        <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-3">
            <div>
              <h3 class="text-base font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
                📊 Distribusi Fitur per Role
              </h3>
              <p class="text-xs text-stone-500">Proporsi fitur fungsional yang ditugaskan kepada setiap aktor sistem.</p>
            </div>
            <Badge variant="outline" class="text-xs">{scopingState.roles.length} Role</Badge>
          </div>

          <div class="space-y-4 pt-2">
            {#each scopingState.roleEffortBreakdown as item}
              <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs font-semibold">
                  <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: {item.color};"></span>
                    <span class="text-stone-800 dark:text-stone-200">{item.roleName}</span>
                    <span class="text-stone-400 font-mono text-[11px]">({item.roleSlug})</span>
                  </div>
                  <div class="text-right">
                    <span class="font-bold text-stone-900 dark:text-stone-100">{item.featureCount} Fitur</span>
                    <span class="text-stone-400 text-[11px]">({item.percentage}%)</span>
                  </div>
                </div>
                <div class="w-full h-3 bg-stone-100 dark:bg-stone-800 rounded-full overflow-hidden p-0.5 border border-stone-200 dark:border-stone-700">
                  <div
                    class="h-full rounded-full transition-all duration-500 ease-out"
                    style="width: {item.percentage}%; background-color: {item.color};"
                  ></div>
                </div>
              </div>
            {/each}
          </div>
        </div>

        <!-- Chart 2: Komposisi Kompleksitas Rekayasa -->
        <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-3">
            <div>
              <h3 class="text-base font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
                🍩 Komposisi Kompleksitas Rekayasa
              </h3>
              <p class="text-xs text-stone-500">Perbandingan fitur CRUD vs Logika Bisnis Khusus vs Integrasi API.</p>
            </div>
            <Badge variant="outline" class="text-xs">Formula Teknis</Badge>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <div class="p-3.5 rounded-xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-1 text-center">
              <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 block">CRUD Standar</span>
              <span class="text-2xl font-black text-emerald-700 dark:text-emerald-400">{scopingState.complexityComposition.crud.percentage}%</span>
              <p class="text-[11px] text-stone-500">{scopingState.complexityComposition.crud.count} fitur</p>
            </div>

            <div class="p-3.5 rounded-xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/50 dark:bg-indigo-950/20 space-y-1 text-center">
              <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 block">Logika Khusus</span>
              <span class="text-2xl font-black text-indigo-700 dark:text-indigo-400">{scopingState.complexityComposition.customLogic.percentage}%</span>
              <p class="text-[11px] text-stone-500">{scopingState.complexityComposition.customLogic.count} fitur</p>
            </div>

            <div class="p-3.5 rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/50 dark:bg-amber-950/20 space-y-1 text-center">
              <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 block">Integrasi API</span>
              <span class="text-2xl font-black text-amber-700 dark:text-amber-400">{scopingState.complexityComposition.integration.percentage}%</span>
              <p class="text-[11px] text-stone-500">{scopingState.complexityComposition.integration.count} fitur</p>
            </div>
          </div>

          <!-- Visual Bar Proportion -->
          <div class="space-y-1.5 pt-2">
            <div class="w-full h-4 bg-stone-100 dark:bg-stone-800 rounded-full overflow-hidden flex border border-stone-200 dark:border-stone-700">
              <div style="width: {scopingState.complexityComposition.crud.percentage}%; background-color: #10b981;" title="CRUD"></div>
              <div style="width: {scopingState.complexityComposition.customLogic.percentage}%; background-color: #4f46e5;" title="Custom Logic"></div>
              <div style="width: {scopingState.complexityComposition.integration.percentage}%; background-color: #f59e0b;" title="Integration"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-stone-500 px-1">
              <span>🟩 CRUD: {scopingState.complexityComposition.crud.percentage}%</span>
              <span>🟦 Custom: {scopingState.complexityComposition.customLogic.percentage}%</span>
              <span>🟨 Integrasi: {scopingState.complexityComposition.integration.percentage}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. Tabel Interaktif Matriks Data Modul & Klien -->
      <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-100 dark:border-stone-800 pb-3">
          <div>
            <h3 class="text-base font-bold text-stone-900 dark:text-stone-100">
              📋 Matriks Modul & Entitas Database ({scopingState.project.clientName})
            </h3>
            <p class="text-xs text-stone-500">Daftar modul fungsional yang terhubung dengan entitas database ERD.</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              onclick={openCreateModule}
              class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
              ➕ Tambah Modul
            </button>
            <button
              type="button"
              onclick={() => setTab('prompt-a')}
              class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
              Lanjut ke Tahap 2: Prompt Tahap A ➔
            </button>
          </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-stone-200 dark:border-stone-800">
          <table class="w-full text-left text-xs text-stone-700 dark:text-stone-300">
            <thead class="bg-stone-50 dark:bg-stone-800/60 font-semibold text-stone-900 dark:text-stone-100 border-b border-stone-200 dark:border-stone-800">
              <tr>
                <th class="p-3">Kode</th>
                <th class="p-3">Nama Modul</th>
                <th class="p-3">Tabel Database ERD</th>
                <th class="p-3">Aktor Penanggung Jawab</th>
                <th class="p-3 text-center">Jumlah Fitur</th>
                <th class="p-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
              {#each scopingState.modules as mod}
                {@const role = scopingState.roles.find(r => r.id === mod.roleId)}
                <tr class="hover:bg-stone-50/50 dark:hover:bg-stone-800/30 transition-colors">
                  <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">[{mod.code}]</td>
                  <td class="p-3">
                    <span class="font-bold text-stone-900 dark:text-stone-100">{mod.name}</span>
                    <p class="text-[11px] text-stone-500">{mod.description}</p>
                  </td>
                  <td class="p-3 font-mono">
                    <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 font-bold">
                      🗄️ {mod.entity?.tableName || mod.slug}
                    </span>
                  </td>
                  <td class="p-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-medium" style="background-color: {role ? role.color + '15' : '#f3f4f6'}; color: {role ? role.color : '#374151'};">
                      ● {role ? role.name : 'All Roles'}
                    </span>
                  </td>
                  <td class="p-3 text-center font-bold">{mod.features.length}</td>
                  <td class="p-3 text-right space-x-1">
                    <button
                      type="button"
                      onclick={() => { handleSelectFeature(mod.features[0]?.id); setTab('workspace'); }}
                      class="px-2.5 py-1 text-[11px] font-semibold rounded-md border border-stone-200 dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800"
                    >
                      Uji Canvas
                    </button>
                    <button
                      type="button"
                      onclick={() => openCreateFeature(mod)}
                      class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100"
                    >
                      + Fitur
                    </button>
                    <button
                      type="button"
                      onclick={() => openEditModule(mod)}
                      class="px-2 py-1 text-[11px] font-semibold rounded-md border border-stone-200 dark:border-stone-700 hover:bg-stone-100 text-stone-600"
                    >
                      ✏️
                    </button>
                    <button
                      type="button"
                      onclick={() => handleDeleteModule(mod.id)}
                      class="px-2 py-1 text-[11px] font-semibold rounded-md border border-rose-200 dark:border-rose-900 hover:bg-rose-50 text-rose-600"
                    >
                      🗑️
                    </button>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  {/if}

  <!-- TAHAP 2: COMPILER PROMPT TAHAP A (MOCKUP & ERD PREVIEW) -->
  {#if activeTab === 'prompt-a'}
    <div class="space-y-6">
      <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-200 dark:border-stone-800 pb-3">
          <div>
            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
              🤖 Tahap 2: Compiler Prompt Tahap A (UI Mockup & ERD Preview)
            </h2>
            <p class="text-xs text-stone-500">
              Prompt deterministik untuk meminta AI Agent merancang UI Interactive Mockup Canvas dan Mermaid ERD sebelum menulis kode backend.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              onclick={copyPromptStageA}
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
              📋 Salin Prompt Tahap A
            </button>
            <button
              type="button"
              onclick={() => setTab('workspace')}
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
              🎨 Buka Live Workspace ➔
            </button>
          </div>
        </div>

        <div class="p-4 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/60 text-xs space-y-2 text-stone-800 dark:text-stone-200">
          <p class="font-bold text-indigo-700 dark:text-indigo-300">Cara Kerja Tahap A (Tactile Validation & Kontrak 1:1):</p>
          <p>1. Salin prompt di bawah ini ke AI Agent (Antigravity / Claude / Gemini).</p>
          <p>2. AI Agent akan merancang komponen Svelte 5 living mockup canvas di memori browser dan skema Mermaid ERD yang selaras dengan kolom-kolom entitas.</p>
          <p>3. Uji antarmuka taktil bersama klien di Tab 3 (Live Workspace) untuk memvalidasi alur sebelum mengunci desain.</p>
          <p>4. Tampilan yang divalidasi di canvas ini merepresentasikan <strong>kontrak presisi 1:1</strong> yang akan diwujudkan pada saat implementasi kode produksi.</p>
        </div>

        <pre class="p-4 rounded-xl bg-stone-900 text-stone-100 font-mono text-xs overflow-x-auto max-h-[500px] leading-relaxed border border-stone-800">{scopingState.compilePromptStageA()}</pre>
      </div>
    </div>
  {/if}

  <!-- TAHAP 3: LIVE STUDIO WORKSPACE (INTERACTIVE CANVAS & ERD DYNAMIC ENTITY) -->
  {#if activeTab === 'workspace'}
    <div class="space-y-6">
      <!-- Sub-mode Selector & Design Freeze Action -->
      <div class="flex items-center justify-between bg-stone-100 dark:bg-stone-800/80 p-2 rounded-xl border border-stone-200 dark:border-stone-700">
        <div class="flex items-center gap-2">
          <button
            type="button"
            onclick={() => workspaceSubMode = 'canvas'}
            class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all {workspaceSubMode === 'canvas' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-stone-600 dark:text-stone-400'}"
          >
            🎨 Interactive Mockup Canvas
          </button>
          <button
            type="button"
            onclick={() => workspaceSubMode = 'erd'}
            class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all {workspaceSubMode === 'erd' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-stone-600 dark:text-stone-400'}"
          >
            🗂️ Visual ERD Explorer
          </button>
        </div>

        <div class="flex items-center gap-3">
          {#if designApproved}
            <Badge class="bg-emerald-600 text-white font-semibold">✓ Design Frozen (Approved)</Badge>
          {:else}
            <button
              type="button"
              onclick={handleApproveDesign}
              class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
              🔒 Setujui Desain (Design Freeze Milestone)
            </button>
          {/if}
        </div>
      </div>

      {#if workspaceSubMode === 'canvas'}
        <!-- Two Column: Sidebar Feature Navigator + Live Mockup Canvas -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <!-- Left: Dedicated Feature Sidebar -->
          <div class="lg:col-span-1 bg-white dark:bg-stone-900 p-4 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-3 h-fit">
            <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-2">
              <h4 class="text-xs font-bold uppercase tracking-wider text-stone-500">
                Fitur Terdaftar
              </h4>
              <Badge variant="outline" class="text-[10px]">{scopingState.totalFeatures} Fitur</Badge>
            </div>
            <div class="space-y-1.5 max-h-[550px] overflow-y-auto pr-1">
              {#each scopingState.allFeatures as f}
                <button
                  type="button"
                  onclick={() => handleSelectFeature(f.id)}
                  class="w-full text-left p-2.5 rounded-xl border text-xs transition-all cursor-pointer {scopingState.selectedFeatureId === f.id ? 'border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 font-semibold shadow-xs' : 'border-stone-200/60 dark:border-stone-800 text-stone-600 dark:text-stone-400 hover:bg-stone-50'}"
                >
                  <div class="font-bold">{f.name}</div>
                  <div class="text-[10px] text-stone-400 font-mono mt-0.5">
                    Modul [{f.moduleCode}] • {f.complexity.toUpperCase()}
                  </div>
                </button>
              {/each}
            </div>
          </div>

          <!-- Right: Interactive Mockup Canvas Area (Synchronized with Active Module Entity) -->
          <div class="lg:col-span-3 space-y-6">
            <!-- Active Feature Header Card -->
            <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <Badge variant="outline" class="text-xs text-indigo-700 border-indigo-300 bg-indigo-50 dark:bg-indigo-950/40">
                      Live Client Sandbox
                    </Badge>
                    <span class="text-xs font-mono text-stone-400">[{scopingState.activeFeature?.slug}]</span>
                    <span class="text-xs font-mono font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded">
                      Tabel: `{scopingState.activeModule?.entity?.tableName || 'data'}`
                    </span>
                    {#if scopingState.activeFeature?.complexity === 'crud'}
                      <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 px-2 py-0.5 rounded-md">
                        Tactile CRUD Enabled
                      </span>
                    {/if}
                  </div>
                  <h3 class="text-xl font-bold text-stone-900 dark:text-stone-100 mt-1">
                    {scopingState.activeFeature?.name || 'Pilih Fitur'}
                  </h3>
                  <p class="text-xs text-stone-500 mt-0.5">
                    Modul: <strong>{scopingState.activeModule?.name}</strong> | Aktor: <strong>{scopingState.activeRole?.name}</strong>
                  </p>
                  {#if scopingState.activeFeature?.purpose}
                    <p class="text-xs text-stone-600 dark:text-stone-300 mt-1 italic">
                      🎯 Tujuan: {scopingState.activeFeature.purpose}
                    </p>
                  {/if}
                </div>
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    onclick={() => openEditFeature(scopingState.activeModule, scopingState.activeFeature)}
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-stone-200 dark:border-stone-700 hover:bg-stone-100"
                  >
                    ✏️ Edit Fitur
                  </button>
                </div>
              </div>

              <!-- View Breakdown Mockups Rendering -->
              <div class="pt-4 border-t border-stone-100 dark:border-stone-800 space-y-5">
                <!-- 1. Dynamic Metric KPI Summary Box based on Module Entity -->
                {#if scopingState.activeModule?.entity?.kpis}
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {#each scopingState.activeModule.entity.kpis as kpi}
                      <div class="p-4 rounded-xl border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800/50 space-y-1">
                        <span class="text-[11px] font-semibold text-stone-500">{kpi.label}</span>
                        <div class="text-xl font-black text-stone-900 dark:text-stone-100">
                          {kpi.value}
                        </div>
                        <span class="text-[10px] text-emerald-600 font-medium">● {kpi.trend}</span>
                      </div>
                    {/each}
                  </div>
                {/if}

                <!-- 2. Dynamic Real-Time Tactical CRUD Data Stream (Standard Emas Pegawai PQTM) -->
                <div class="space-y-3 pt-2">
                  <!-- Header Action Bar with Summary Count Pills -->
                  <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-stone-50/90 dark:bg-stone-800/60 p-3 sm:p-4 rounded-xl border border-stone-200 dark:border-stone-700">
                    <div class="space-y-1">
                      <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]"></span>
                        <h4 class="text-sm sm:text-base font-bold text-stone-900 dark:text-stone-100">
                          Data Tabel Real-Time: <code class="text-indigo-600 dark:text-indigo-400 font-mono text-xs">[{scopingState.activeModule?.entity?.tableName || 'data'}]</code>
                        </h4>
                      </div>
                      <p class="text-[11px] text-stone-500 dark:text-stone-400">
                        Simulasi tabel mandiri in-memory berbasis skema entitas ERD.
                      </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                      <!-- Summary Stat Pills (Mirip Pegawai PQTM) -->
                      <div class="hidden sm:flex items-center gap-2 bg-white dark:bg-stone-900 px-3 py-1.5 rounded-lg border border-stone-200 dark:border-stone-700 text-[11px] font-mono shadow-2xs">
                        {#each simSummaryPills as pill, idx}
                          {#if idx > 0}<span class="text-stone-300 dark:text-stone-600">•</span>{/if}
                          <span class={pill.color}>{pill.label}: {pill.count}</span>
                        {/each}
                      </div>

                      {#if scopingState.activeFeature?.complexity === 'crud'}
                        <button
                          type="button"
                          onclick={openCreateSimRecord}
                          class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer hover:scale-[1.01] min-h-[36px]"
                        >
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                          </svg>
                          <span>Tambah Data</span>
                        </button>
                      {/if}

                      <button
                        type="button"
                        onclick={handleExportSimCsv}
                        class="px-3 py-1.5 bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800 transition-all cursor-pointer flex items-center gap-1.5 min-h-[36px] shadow-2xs"
                        title="Unduh dataset aktif sebagai file CSV"
                      >
                        <svg class="w-3.5 h-3.5 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Ekspor CSV</span>
                      </button>
                    </div>
                  </div>

                  <!-- In-Memory Search & Multi-Filter Controls Bar -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5 bg-stone-50/60 dark:bg-stone-800/40 p-3 rounded-xl border border-stone-200/80 dark:border-stone-800">
                    <div>
                      <label for="sim-search" class="block text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-0.5">
                        Cari Data Instan
                      </label>
                      <input
                        id="sim-search"
                        type="text"
                        bind:value={simSearchQuery}
                        placeholder="Ketik kata kunci pencarian..."
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-900 dark:text-stone-100 focus:ring-1 focus:ring-indigo-500"
                      />
                    </div>

                    {#if simFilterConfig && simFilterConfig.options.length > 0}
                      <div>
                        <label for="sim-filter" class="block text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-0.5">
                          Filter {simFilterConfig.label}
                        </label>
                        <select
                          id="sim-filter"
                          bind:value={simFilterValue}
                          class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-900 dark:text-stone-100"
                        >
                          <option value="semua">Semua {simFilterConfig.label}</option>
                          {#each simFilterConfig.options as opt}
                            <option value={opt}>{opt}</option>
                          {/each}
                        </select>
                      </div>
                    {:else}
                      <div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-stone-400 mb-0.5">Status Filter</span>
                        <div class="text-xs text-stone-400 py-1.5 px-2 bg-stone-100 dark:bg-stone-800 rounded-lg">Semua Kolom Aktif</div>
                      </div>
                    {/if}

                    <div>
                      <label for="sim-per-page" class="block text-[10px] font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-0.5">
                        Tampilkan per Halaman
                      </label>
                      <select
                        id="sim-per-page"
                        bind:value={simPerPage}
                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-900 dark:text-stone-100"
                      >
                        <option value={5}>5 Baris</option>
                        <option value={10}>10 Baris</option>
                        <option value={15}>15 Baris</option>
                        <option value={25}>25 Baris</option>
                        <option value="semua">Semua Baris</option>
                      </select>
                    </div>

                    <div class="flex items-end">
                      {#if simSearchQuery || simFilterValue !== 'semua'}
                        <button
                          type="button"
                          onclick={() => { simSearchQuery = ''; simFilterValue = 'semua'; }}
                          class="w-full px-2.5 py-1.5 text-xs font-semibold rounded-lg border border-rose-200 dark:border-rose-800 bg-rose-50/60 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 hover:bg-rose-100 transition min-h-[34px]"
                        >
                          ✕ Reset Filter
                        </button>
                      {:else}
                        <div class="text-[11px] text-stone-400 py-1.5 flex items-center gap-1 font-mono">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                          <span>In-Memory Filter &lt;0.1ms</span>
                        </div>
                      {/if}
                    </div>
                  </div>

                  <!-- Precision Data Table (Contained Zero-Horizontal Scroll Wrapper) -->
                  <div class="w-full overflow-x-auto rounded-xl border border-stone-200 dark:border-stone-700 bg-white/80 dark:bg-stone-900 shadow-xs">
                    {#if filteredSimData.length === 0}
                      <!-- Empty State View -->
                      <div class="p-8 text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-400 mx-auto flex items-center justify-center text-lg">
                          🔍
                        </div>
                        <p class="text-xs font-bold text-stone-800 dark:text-stone-200">Tidak ada baris data ditemukan</p>
                        <p class="text-[11px] text-stone-500 max-w-sm mx-auto">
                          {#if simSearchQuery || simFilterValue !== 'semua'}
                            Tidak ada data yang sesuai dengan kata kunci pencarian atau filter aktif saat ini.
                          {:else}
                            Tabel simulasi masih kosong. Gunakan tombol di bawah untuk menambahkan entri pertama.
                          {/if}
                        </p>
                        {#if simSearchQuery || simFilterValue !== 'semua'}
                          <button
                            type="button"
                            onclick={() => { simSearchQuery = ''; simFilterValue = 'semua'; }}
                            class="mt-2 px-3 py-1 text-xs font-semibold bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 rounded-lg hover:bg-stone-200 cursor-pointer"
                          >
                            Reset Filter Pencarian
                          </button>
                        {:else if scopingState.activeFeature?.complexity === 'crud'}
                          <button
                            type="button"
                            onclick={openCreateSimRecord}
                            class="mt-2 px-3.5 py-1.5 text-xs font-bold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 cursor-pointer shadow-xs"
                          >
                            + Tambah Data Pertama
                          </button>
                        {/if}
                      </div>
                    {:else}
                      <!-- Populated Data Table -->
                      <table class="w-full text-left text-xs text-stone-700 dark:text-stone-300">
                        <thead class="bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-semibold border-b border-stone-200 dark:border-stone-700">
                          <tr>
                            {#each (scopingState.activeModule?.entity?.fields || []) as field}
                              <th class="py-2.5 px-3 whitespace-nowrap">
                                <span>{field.label}</span>
                                {#if field.key}
                                  <span class="ml-1 text-[9px] px-1 py-0.2 rounded font-mono {field.key === 'PK' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' : field.key === 'UK' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'}">
                                    {field.key}
                                  </span>
                                {/if}
                              </th>
                            {/each}
                            <th class="py-2.5 px-3 text-right whitespace-nowrap">Aksi</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                          {#each paginatedSimData as item (item.id)}
                            <tr class="hover:bg-stone-50/70 dark:hover:bg-stone-800/50 transition-colors">
                              {#each (scopingState.activeModule?.entity?.fields || []) as field}
                                <td class="py-2.5 px-3 {field.key === 'PK' || field.key === 'UK' ? 'font-mono font-bold text-indigo-600 dark:text-indigo-400' : ''}">
                                  {#if field.name === 'status' || field.name === 'approval_status'}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                      {item[field.name]}
                                    </span>
                                  {:else if field.name === 'role'}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                      {item[field.name]}
                                    </span>
                                  {:else}
                                    <span class="line-clamp-2">{item[field.name] !== undefined ? item[field.name] : '-'}</span>
                                  {/if}
                                </td>
                              {/each}
                              <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                  <button
                                    type="button"
                                    onclick={() => openDetailSimRecord(item)}
                                    class="px-2 py-1 bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:hover:bg-stone-700 text-stone-700 dark:text-stone-300 rounded text-[11px] font-semibold transition-all cursor-pointer flex items-center gap-1 min-h-[30px]"
                                    title="Lihat rincian lengkap record"
                                  >
                                    👁️ Detail
                                  </button>
                                  {#if scopingState.activeFeature?.complexity === 'crud'}
                                    <button
                                      type="button"
                                      onclick={() => openEditSimRecord(item)}
                                      class="px-2 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded text-[11px] font-semibold transition-all cursor-pointer flex items-center gap-1 min-h-[30px]"
                                      title="Ubah data baris simulasi"
                                    >
                                      ✏️ Ubah
                                    </button>
                                  {/if}
                                  <button
                                    type="button"
                                    onclick={() => openDeleteSimModal(item)}
                                    class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded text-[11px] font-semibold transition-all cursor-pointer flex items-center gap-1 min-h-[30px]"
                                    title="Hapus baris simulasi"
                                  >
                                    🗑️ Hapus
                                  </button>
                                </div>
                              </td>
                            </tr>
                          {/each}
                        </tbody>
                      </table>
                    {/if}
                  </div>

                  <!-- Client-Side Pagination & Row Counter Bar -->
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs pt-1">
                    <div class="text-stone-500 dark:text-stone-400 font-mono text-[11px]">
                      Menampilkan {filteredSimData.length > 0 ? (simPage - 1) * Number(simPerPage === 'semua' ? filteredSimData.length : simPerPage) + 1 : 0} - {Math.min(simPage * Number(simPerPage === 'semua' ? filteredSimData.length : simPerPage), filteredSimData.length)} dari {filteredSimData.length} baris
                      {#if filteredSimData.length !== (scopingState.activeModule?.entity?.mockStream?.length || 0)}
                        <span class="text-indigo-600 dark:text-indigo-400 font-bold">(difilter dari total {scopingState.activeModule?.entity?.mockStream?.length || 0})</span>
                      {/if}
                    </div>

                    {#if totalSimPages > 1}
                      <div class="flex items-center gap-1">
                        <button
                          type="button"
                          disabled={simPage === 1}
                          onclick={() => simPage = Math.max(1, simPage - 1)}
                          class="px-2.5 py-1 rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-800 disabled:opacity-40 disabled:cursor-not-allowed text-stone-700 dark:text-stone-300 font-semibold hover:bg-stone-100 cursor-pointer"
                        >
                          &larr; Prev
                        </button>
                        <span class="px-2 font-mono text-[11px] text-stone-600 dark:text-stone-400">
                          Hal {simPage} dari {totalSimPages}
                        </span>
                        <button
                          type="button"
                          disabled={simPage === totalSimPages}
                          onclick={() => simPage = Math.min(totalSimPages, simPage + 1)}
                          class="px-2.5 py-1 rounded-lg border border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-800 disabled:opacity-40 disabled:cursor-not-allowed text-stone-700 dark:text-stone-300 font-semibold hover:bg-stone-100 cursor-pointer"
                        >
                          Next &rarr;
                        </button>
                      </div>
                    {/if}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      {:else}
        <!-- Visual ERD Explorer (Synchronized with Module Schemas) -->
        <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-3">
            <div>
              <h3 class="text-base font-bold text-stone-900 dark:text-stone-100">
                🗂️ Visual ERD Explorer & Graph Skema Database
              </h3>
              <p class="text-xs text-stone-500">Visualisasi relasi skema entitas hasil kompilasi matriks yang selaras 1:1 dengan canvas dan backend.</p>
            </div>
            <button
              type="button"
              onclick={() => { navigator.clipboard.writeText(scopingState.compileSddMarkdown()); toastState.info('Diagram Mermaid ERD disalin!'); }}
              class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700 hover:bg-stone-50 cursor-pointer"
            >
              📋 Salin Mermaid ERD
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- Core USERS Table -->
            <div class="p-4 rounded-xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/30 dark:bg-indigo-950/20 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block font-mono">USERS</span>
                <span class="text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-bold">CORE</span>
              </div>
              <ul class="text-xs space-y-1 text-stone-600 dark:text-stone-300 font-mono">
                <li>• id (bigint, <span class="text-indigo-600 font-bold">PK</span>)</li>
                <li>• email (string, <span class="text-amber-600 font-bold">UK</span>)</li>
                <li>• password (string)</li>
                <li>• role (json array)</li>
                <li>• is_active (boolean)</li>
                <li>• created_at (timestamp)</li>
              </ul>
            </div>

            <!-- Core USER_DEVICES Table -->
            <div class="p-4 rounded-xl border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-800/50 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block font-mono">USER_DEVICES</span>
                <span class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-bold">SESSION</span>
              </div>
              <ul class="text-xs space-y-1 text-stone-600 dark:text-stone-300 font-mono">
                <li>• id (bigint, <span class="text-indigo-600 font-bold">PK</span>)</li>
                <li>• user_id (bigint, <span class="text-emerald-600 font-bold">FK</span>)</li>
                <li>• session_id (string, <span class="text-amber-600 font-bold">UK</span>)</li>
                <li>• ip_address (string)</li>
                <li>• expires_at (timestamp)</li>
              </ul>
            </div>

            <!-- Dynamic Module Entity Tables -->
            {#each scopingState.modules as mod}
              {@const entity = mod.entity}
              <div class="p-4 rounded-xl border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-800/50 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider block font-mono">
                    {(entity?.tableName || mod.slug).toUpperCase()}
                  </span>
                  <span class="text-[10px] bg-stone-200 dark:bg-stone-700 text-stone-700 dark:text-stone-300 px-1.5 py-0.5 rounded font-bold font-mono">
                    [{mod.code}]
                  </span>
                </div>
                <p class="text-[10px] text-stone-400">{entity?.description || mod.description}</p>
                <ul class="text-xs space-y-1 text-stone-600 dark:text-stone-300 font-mono pt-1 border-t border-stone-200 dark:border-stone-700">
                  {#if entity?.fields}
                    {#each entity.fields as f}
                      <li>
                        • {f.name} ({f.type}
                        {#if f.key}
                          , <span class="{f.key === 'PK' ? 'text-indigo-600' : f.key === 'UK' ? 'text-amber-600' : 'text-emerald-600'} font-bold">{f.key}</span>
                        {/if}
                        )
                      </li>
                    {/each}
                  {:else}
                    <li>• id (bigint, PK)</li>
                    <li>• code (string, UK)</li>
                    <li>• title (string)</li>
                    <li>• status (string)</li>
                  {/if}
                </ul>
              </div>
            {/each}
          </div>
        </div>
      {/if}

      <!-- Design Freeze Action Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-xs">
        <div>
          <span class="font-bold text-emerald-800 dark:text-emerald-300 block">Milestone Design Freeze (Validasi Tampilan Selesai):</span>
          <span class="text-stone-600 dark:text-stone-400">Jika klien telah menyepakati simulasi visual & relasi ERD di atas, kunci desain untuk menyusun kontrak SDD resmi.</span>
        </div>
        <button
          type="button"
          onclick={() => { designApproved = true; setTab('compiler'); toastState.success('Desain disetujui! Lanjut ke kompilasi SDD.'); }}
          class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all self-start sm:self-auto cursor-pointer"
        >
          ✅ Setujui Desain & Lanjut ke Approval SDD ➔
        </button>
      </div>
    </div>
  {/if}

  <!-- TAHAP 4: SDD COMPILER & SPECIFICATION APPROVAL -->
  {#if activeTab === 'compiler'}
    <div class="space-y-6">
      <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-200 dark:border-stone-800 pb-3">
          <div>
            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
              📄 Tahap 4: SDD Compiler & Dokumen Kontrak (.md)
            </h2>
            <p class="text-xs text-stone-500">Kompilasi otomatis spesifikasi deterministik siap ekspor dan siap simpan ke doc/features/.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              onclick={copySddMarkdown}
              class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800 cursor-pointer"
            >
              📋 Salin SDD
            </button>
            <button
              type="button"
              onclick={downloadSddMarkdown}
              class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800 cursor-pointer"
            >
              📥 Unduh .md
            </button>
            <button
              type="button"
              onclick={handleSaveSpecification}
              disabled={savingSpec}
              class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
              {savingSpec ? 'Menyimpan...' : '💾 Simpan ke doc/features/'}
            </button>
            <button
              type="button"
              onclick={() => setTab('prompt-b')}
              class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
              ⚡ Lanjut ke Tahap 5: Prompt Eksekusi ➔
            </button>
          </div>
        </div>

        <pre class="p-4 rounded-xl bg-stone-900 text-stone-100 font-mono text-xs overflow-x-auto max-h-[500px] leading-relaxed border border-stone-800">{scopingState.compileSddMarkdown()}</pre>
      </div>
    </div>
  {/if}

  <!-- TAHAP 5: COMPILER PROMPT TAHAP B (EKSEKUSI PRODUKSI 100%) -->
  {#if activeTab === 'prompt-b'}
    <div class="space-y-6">
      <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-200 dark:border-stone-800 pb-3">
          <div>
            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
              ⚡ Tahap 5: Compiler Prompt Tahap B (Eksekusi 100% Kode Produksi)
            </h2>
            <p class="text-xs text-stone-500">
              Prompt deterministik berstandar internasional untuk mengarahkan AI Agent mengeksekusi kode utuh tanpa celah asumsi dengan fidelitas 1:1 terhadap Mockup & ERD.
            </p>
          </div>
          <button
            type="button"
            onclick={copyPromptStageB}
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
          >
            ⚡ Salin Prompt Tahap B
          </button>
        </div>

        <div class="p-4 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/60 text-xs space-y-2 text-stone-800 dark:text-stone-200">
          <p class="font-bold text-indigo-700 dark:text-indigo-300">Protokol Eksekusi AI Human-in-the-Loop:</p>
          <p>1. Salin prompt di bawah ini ke AI Agent (Antigravity / Gemini / Claude).</p>
          <p>2. AI Agent akan membaca spesifikasi resmi di <code>doc/features/</code> dan mengeksekusi <strong>HANYA 1 Fitur untuk 1 Role</strong> per giliran kerja.</p>
          <p>3. Kode yang dihasilkan 100% utuh (Migration, Model, Policy, Controller, Test, dan Svelte 5 view) mematuhi seluruh Golden Invariants & Anti-Slop, dan <strong>1:1 persis sama</strong> dengan tampilan Interactive Mockup Canvas dan ERD preview yang telah divalidasi bersama klien.</p>
        </div>

        <pre class="p-4 rounded-xl bg-stone-900 text-stone-100 font-mono text-xs overflow-x-auto max-h-[500px] leading-relaxed border border-stone-800">{scopingState.compilePromptStageB()}</pre>
      </div>
    </div>
  {/if}

  <!-- TAB: REPOSITORY PROVISIONING WIZARD -->
  {#if activeTab === 'provision'}
    <div class="space-y-6">
      <div class="bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs space-y-6">
        <div class="border-b border-stone-200 dark:border-stone-800 pb-4">
          <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
            🚀 Universal Repository Provisioning Wizard
          </h2>
          <p class="text-xs text-stone-500">
            Generator repositori baru otomatis: Clone template bersih, sanitasi git, inisialisasi SQLite terisolasi, set port Laragon, dan suntikkan universal invariants.
          </p>
        </div>

        <!-- 4 Step Workflow Parameters -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <h3 class="text-sm font-bold text-stone-800 dark:text-stone-200">Parameter Proyek Baru:</h3>
            <div>
              <label for="prov-name-input" class="block text-xs font-semibold text-stone-600 dark:text-stone-400 mb-1">Nama Proyek / Slug Direktori</label>
              <input
                id="prov-name-input"
                type="text"
                bind:value={provisionForm.name}
                class="w-full px-3 py-2 text-xs rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 font-mono"
              />
              <p class="text-[10px] text-stone-400 mt-1">Akan dibuat di: <code>D:\laragon\www\{provisionForm.name}</code></p>
            </div>

            <div>
              <label for="prov-desc-input" class="block text-xs font-semibold text-stone-600 dark:text-stone-400 mb-1">Deskripsi Repositori</label>
              <input
                id="prov-desc-input"
                type="text"
                bind:value={provisionForm.description}
                class="w-full px-3 py-2 text-xs rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="prov-visibility-select" class="block text-xs font-semibold text-stone-600 dark:text-stone-400 mb-1">Visibilitas GitHub</label>
                <select
                  id="prov-visibility-select"
                  bind:value={provisionForm.visibility}
                  class="w-full px-3 py-2 text-xs rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800"
                >
                  <option value="private">Private (Tertutup)</option>
                  <option value="public">Public (Terbuka)</option>
                </select>
              </div>
              <div>
                <label for="prov-fe-port-input" class="block text-xs font-semibold text-stone-600 dark:text-stone-400 mb-1">Port Frontend (Vite)</label>
                <input
                  id="prov-fe-port-input"
                  type="number"
                  bind:value={provisionForm.frontendPort}
                  class="w-full px-3 py-2 text-xs rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 font-mono"
                />
              </div>
            </div>

            <div>
              <label for="prov-be-port-input" class="block text-xs font-semibold text-stone-600 dark:text-stone-400 mb-1">Port Backend FastCGI</label>
              <input
                id="prov-be-port-input"
                type="number"
                bind:value={provisionForm.backendPort}
                class="w-full px-3 py-2 text-xs rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 font-mono"
              />
            </div>
          </div>

          <!-- Wizard Workflow Steps Visual -->
          <div class="space-y-3 bg-stone-50 dark:bg-stone-800/40 p-4 rounded-xl border border-stone-200 dark:border-stone-700">
            <h3 class="text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-stone-400">
              Tahapan Otomasi Provisioning:
            </h3>
            <ol class="space-y-2 text-xs text-stone-700 dark:text-stone-300 list-decimal list-inside">
              <li><strong>Validasi Parameter:</strong> Cek keunikan slug folder lokal.</li>
              <li><strong>GitHub Remote:</strong> Eksekusi <code>gh repo create</code> jika akun terautentikasi.</li>
              <li><strong>Clean Cloning:</strong> Salin core valora-vault (abaikan git, vendor, node_modules).</li>
              <li><strong>Git Sanitization:</strong> Hapus git lawas, jalankan <code>git init -b main</code>.</li>
              <li><strong>Bootstrapping & Isolation:</strong> Generate APP_KEY baru, SQLite baru, atur port Vite & FastCGI.</li>
              <li><strong>Rule Injection:</strong> Salin <code>universal_invariants.md</code>, <code>GEMINI.md</code>, dan <code>llms.txt</code>.</li>
              <li><strong>Migrasi Awal:</strong> Jalankan <code>php artisan migrate --force</code>.</li>
            </ol>
          </div>
        </div>

        <!-- CLI Runner Command Generator -->
        <div class="p-4 rounded-xl bg-stone-900 border border-stone-800 text-stone-100 space-y-2">
          <div class="flex items-center justify-between text-xs font-semibold text-stone-400">
            <span>Perintah CLI Provisioning 1-Baris:</span>
            <button
              type="button"
              onclick={copyProvisionCommand}
              class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold cursor-pointer"
            >
              📋 Salin Perintah
            </button>
          </div>
          <code class="block font-mono text-xs text-emerald-400 overflow-x-auto py-1">
            {provisionCliCommand}
          </code>
        </div>
      </div>
    </div>
  {/if}

  <!-- TAB: MODUL TERDAFTAR DI DOC/FEATURES/ -->
  {#if activeTab === 'modules'}
    <div class="space-y-4 bg-white dark:bg-stone-900 p-6 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs">
      <div class="flex items-center justify-between border-b border-stone-200 dark:border-stone-800 pb-3">
        <div>
          <h3 class="text-base font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
            🗄️ Modul Aktif Terpasang di doc/features/
          </h3>
          <p class="text-xs text-stone-500">Berkas spesifikasi fitur yang terindeks resmi pada katalog Valora Vault.</p>
        </div>
        <button
          type="button"
          onclick={fetchExistingModules}
          class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700 hover:bg-stone-50 cursor-pointer"
        >
          🔄 Refresh Katalog
        </button>
      </div>

      {#if loadingModules}
        <ElementLoader label="Memuat modul aktif..." />
      {:else if modulesList.length === 0}
        <div class="text-center py-8 space-y-2">
          <p class="text-xs text-stone-500">Belum ada modul tambahan di doc/features/.</p>
        </div>
      {:else}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {#each modulesList as mod}
            <div class="p-4 rounded-xl border border-stone-200 dark:border-stone-800 bg-stone-50/50 dark:bg-stone-800/40 text-xs space-y-2">
              <div class="flex items-center justify-between">
                <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">[{mod.code}]</span>
                <Badge variant="outline" class="text-[10px]">{mod.status}</Badge>
              </div>
              <h4 class="font-bold text-stone-900 dark:text-stone-100 text-sm">{mod.title}</h4>
              <p class="text-[11px] text-stone-500">Aktor: {mod.actor}</p>
              <div class="flex items-center justify-between pt-1 border-t border-stone-100 dark:border-stone-700 text-[10px] text-stone-400 font-mono">
                <span>{mod.filename}</span>
                <span>{mod.updated_at}</span>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>
  {/if}
</div>

<!-- Modal Dialog: Parameter Klien & Proyek -->
{#if clientProjectModal}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xl max-w-lg w-full p-6 space-y-4">
      <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-2">
        <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100">Parameter Klien & Proyek</h3>
        <button type="button" onclick={() => clientProjectModal = false} class="text-stone-400 hover:text-stone-600">✕</button>
      </div>

      <div class="space-y-3 text-xs">
        <div>
          <label for="edit-proj-client-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Nama Klien / Lembaga</label>
          <input id="edit-proj-client-input" type="text" bind:value={projectEditForm.clientName} class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800" />
        </div>
        <div>
          <label for="edit-proj-name-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Nama Proyek / Aplikasi</label>
          <input id="edit-proj-name-input" type="text" bind:value={projectEditForm.projectName} class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800" />
        </div>
        <div>
          <label for="edit-proj-slug-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Kode Slug Unik</label>
          <input id="edit-proj-slug-input" type="text" bind:value={projectEditForm.slug} class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 font-mono" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="edit-proj-app-select" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Klasifikasi Arsitektur</label>
            <select id="edit-proj-app-select" bind:value={projectEditForm.appType} class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800">
              <option value="webapp">Full Web Application</option>
              <option value="cms_landing">Landing Page / CMS</option>
            </select>
          </div>
          <div>
            <label for="edit-proj-auth-select" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Mekanisme Autentikasi</label>
            <select id="edit-proj-auth-select" bind:value={projectEditForm.authMechanism} class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800">
              <option value="login_only">Login-Only</option>
              <option value="login_register">Self-Register</option>
              <option value="none">Tanpa Login</option>
            </select>
          </div>
        </div>
        <div>
          <label for="edit-proj-days-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Target Durasi Rilis (Hari Kerja)</label>
          <input id="edit-proj-days-input" type="number" bind:value={projectEditForm.targetDeliveryDays} class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800" />
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button type="button" onclick={() => clientProjectModal = false} class="px-4 py-2 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700">Batal</button>
        <button type="button" onclick={saveClientProject} class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Simpan Perubahan</button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Dialog: Role CRUD (Tambah / Edit Peran) -->
{#if roleModal}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xl max-w-md w-full p-6 space-y-4">
      <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-2">
        <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100">
          {roleModalMode === 'create' ? 'Tambah Peran (RBAC) Baru' : 'Edit Peran (RBAC)'}
        </h3>
        <button type="button" onclick={() => roleModal = false} class="text-stone-400 hover:text-stone-600">✕</button>
      </div>

      <div class="space-y-3 text-xs">
        <div>
          <label for="role-name-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Nama Peran</label>
          <input id="role-name-input" type="text" bind:value={roleForm.name} placeholder="Contoh: Manajer Operasional" class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800" />
        </div>
        <div>
          <label for="role-slug-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Slug Peran (Kode Otoritas)</label>
          <input id="role-slug-input" type="text" bind:value={roleForm.slug} placeholder="Contoh: manager" class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 font-mono" />
        </div>
        <div>
          <label for="role-color-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Warna Aksen Badge</label>
          <div class="flex items-center gap-2">
            <input id="role-color-input" type="color" bind:value={roleForm.color} class="w-10 h-8 rounded border border-stone-300 dark:border-stone-700 cursor-pointer" />
            <input type="text" bind:value={roleForm.color} class="w-full px-3 py-1.5 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 font-mono" />
          </div>
        </div>
        <div>
          <label for="role-desc-textarea" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Deskripsi Tanggung Jawab</label>
          <textarea id="role-desc-textarea" bind:value={roleForm.description} rows="3" placeholder="Deskripsi hak akses..." class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800"></textarea>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button type="button" onclick={() => roleModal = false} class="px-4 py-2 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700">Batal</button>
        <button type="button" onclick={saveRole} class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Simpan Peran</button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Dialog: Module CRUD (Tambah / Edit Modul) -->
{#if moduleModal}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xl max-w-md w-full p-6 space-y-4">
      <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-2">
        <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100">
          {moduleModalMode === 'create' ? 'Tambah Modul Arsitektur Baru' : 'Edit Modul Arsitektur'}
        </h3>
        <button type="button" onclick={() => moduleModal = false} class="text-stone-400 hover:text-stone-600">✕</button>
      </div>

      <div class="space-y-3 text-xs">
        <div>
          <label for="mod-name-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Nama Modul</label>
          <input id="mod-name-input" type="text" bind:value={moduleForm.name} placeholder="Contoh: Manajemen Kas & Pembukuan" class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800" />
        </div>
        <div>
          <label for="mod-table-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Nama Tabel Database ERD</label>
          <input id="mod-table-input" type="text" bind:value={moduleForm.tableName} placeholder="Contoh: cash_transactions" class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 font-mono" />
        </div>
        <div>
          <label for="mod-role-select" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Peran Penanggung Jawab (RBAC)</label>
          <select id="mod-role-select" bind:value={moduleForm.roleId} class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800">
            {#each scopingState.roles as r}
              <option value={r.id}>{r.name} ({r.slug})</option>
            {/each}
          </select>
        </div>
        <div>
          <label for="mod-desc-textarea" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Deskripsi Cakupan Bisnis</label>
          <textarea id="mod-desc-textarea" bind:value={moduleForm.description} rows="3" placeholder="Deskripsi fungsionalitas modul..." class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800"></textarea>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button type="button" onclick={() => moduleModal = false} class="px-4 py-2 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700">Batal</button>
        <button type="button" onclick={saveModule} class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Simpan Modul</button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Dialog: Feature CRUD (Tambah / Edit Fitur) -->
{#if featureModal}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xl max-w-md w-full p-6 space-y-4">
      <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-2">
        <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100">
          {featureModalMode === 'create' ? `Tambah Fitur ke [${targetModuleForFeature?.name}]` : 'Edit Fitur'}
        </h3>
        <button type="button" onclick={() => featureModal = false} class="text-stone-400 hover:text-stone-600">✕</button>
      </div>

      <div class="space-y-3 text-xs">
        <div>
          <label for="feat-name-input" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Nama Fitur</label>
          <input id="feat-name-input" type="text" bind:value={featureForm.name} placeholder="Contoh: Cetak Bukti Pembayaran ke PDF" class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800" />
        </div>
        <div>
          <label for="feat-comp-select" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Tingkat Kompleksitas</label>
          <select id="feat-comp-select" bind:value={featureForm.complexity} class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800">
            <option value="crud">CRUD Standar (Data Entry & Tabel)</option>
            <option value="custom_logic">Custom Logic / State Machine / Workflow</option>
            <option value="integration">Integrasi Eksternal / API / Hardware</option>
          </select>
        </div>
        <div>
          <label for="feat-purpose-textarea" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">Tujuan & Kontrak Fungsional</label>
          <textarea id="feat-purpose-textarea" bind:value={featureForm.purpose} rows="3" placeholder="Jelaskan tujuan bisnis dan apa yang ingin dicapai fitur ini..." class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800"></textarea>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button type="button" onclick={() => featureModal = false} class="px-4 py-2 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700">Batal</button>
        <button type="button" onclick={saveFeature} class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Simpan Fitur</button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Dialog 1: Simulasi Record CRUD (Tambah / Ubah Data Simulasi) with Tab Support -->
{#if simModal}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xl max-w-lg w-full p-6 space-y-4">
      <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-2">
        <div class="space-y-0.5">
          <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
            <span>{simModalMode === 'create' ? '➕ Tambah Data Baru' : '✏️ Ubah Data Record'}</span>
            <span class="text-xs font-mono font-normal text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded">
              [{scopingState.activeModule?.entity?.tableName}]
            </span>
          </h3>
          <p class="text-[11px] text-stone-500">
            Formulir in-memory tactile simulator sesuai skema kolom ERD modul aktif.
          </p>
        </div>
        <button type="button" onclick={() => simModal = false} class="text-stone-400 hover:text-stone-600 cursor-pointer">✕</button>
      </div>

      {#if simNonPkFields.length > 5}
        <!-- Sub-form Navigation Pills (Mirip Pegawai PQTM) -->
        <div class="flex items-center gap-1.5 p-1 bg-stone-100 dark:bg-stone-800 rounded-lg text-xs font-semibold">
          <button
            type="button"
            onclick={() => simFormTab = 'utama'}
            class="flex-1 py-1 px-2.5 rounded-md transition-all cursor-pointer {simFormTab === 'utama' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-2xs' : 'text-stone-500 hover:text-stone-900 dark:hover:text-stone-200'}"
          >
            1. Data Utama
          </button>
          <button
            type="button"
            onclick={() => simFormTab = 'detail'}
            class="flex-1 py-1 px-2.5 rounded-md transition-all cursor-pointer {simFormTab === 'detail' ? 'bg-white dark:bg-stone-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-2xs' : 'text-stone-500 hover:text-stone-900 dark:hover:text-stone-200'}"
          >
            2. Atribut Tambahan
          </button>
        </div>
      {/if}

      <div class="space-y-3 text-xs max-h-[60vh] overflow-y-auto pr-1">
        {#each simFormFieldsToShow as field}
          <div>
            <label for="modal-sim-{field.name}" class="block font-semibold text-stone-700 dark:text-stone-300 mb-1">
              {field.label} {field.key ? `(${field.key})` : ''}
            </label>
            {#if field.inputType === 'select' && field.options}
              <select
                id="modal-sim-{field.name}"
                bind:value={simRecordForm[field.name]}
                class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-100"
              >
                {#each field.options as opt}
                  <option value={opt}>{opt}</option>
                {/each}
              </select>
            {:else if field.inputType === 'number'}
              <input
                id="modal-sim-{field.name}"
                type="number"
                bind:value={simRecordForm[field.name]}
                placeholder="Contoh: 1500000"
                class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-100"
              />
            {:else if field.inputType === 'date'}
              <input
                id="modal-sim-{field.name}"
                type="date"
                bind:value={simRecordForm[field.name]}
                class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-100"
              />
            {:else}
              <input
                id="modal-sim-{field.name}"
                type={field.inputType || 'text'}
                bind:value={simRecordForm[field.name]}
                placeholder="Masukkan {field.label.toLowerCase()}..."
                class="w-full px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-100"
              />
            {/if}
          </div>
        {/each}
      </div>

      <div class="flex justify-between items-center pt-3 border-t border-stone-100 dark:border-stone-800">
        {#if (scopingState.activeModule?.entity?.fields || []).filter(f => f.key !== 'PK').length > 5}
          {#if simFormTab === 'utama'}
            <button
              type="button"
              onclick={() => simFormTab = 'detail'}
              class="px-3 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
            >
              Lanjut ke Tab 2 &rarr;
            </button>
          {:else}
            <button
              type="button"
              onclick={() => simFormTab = 'utama'}
              class="px-3 py-1.5 text-xs font-semibold text-stone-500 hover:underline cursor-pointer"
            >
              &larr; Kembali ke Tab 1
            </button>
          {/if}
        {:else}
          <div></div>
        {/if}

        <div class="flex gap-2">
          <button
            type="button"
            onclick={() => simModal = false}
            class="px-4 py-2 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            onclick={saveSimRecord}
            class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-xs transition-all cursor-pointer"
          >
            {simModalMode === 'create' ? 'Tambah ke Tabel' : 'Simpan Perubahan'}
          </button>
        </div>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Dialog 2: Rincian Lengkap Record (Detail Modal) -->
{#if showDetailSimModal && selectedDetailSimRecord}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xl max-w-lg w-full p-6 space-y-4">
      <div class="flex items-center justify-between border-b border-stone-100 dark:border-stone-800 pb-2">
        <div class="space-y-0.5">
          <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100 flex items-center gap-2">
            <span>👁️ Rincian Lengkap Data Record</span>
            <span class="text-xs font-mono font-normal text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded">
              #{selectedDetailSimRecord.id}
            </span>
          </h3>
          <p class="text-[11px] text-stone-500">
            Tabel: <code>{scopingState.activeModule?.entity?.tableName}</code>
          </p>
        </div>
        <button type="button" onclick={() => showDetailSimModal = false} class="text-stone-400 hover:text-stone-600 cursor-pointer">✕</button>
      </div>

      <div class="space-y-2.5 max-h-[60vh] overflow-y-auto pr-1">
        {#each (scopingState.activeModule?.entity?.fields || []) as field}
          <div class="flex items-start justify-between p-2 rounded-lg bg-stone-50 dark:bg-stone-800/60 border border-stone-200/60 dark:border-stone-700/60 text-xs">
            <span class="font-medium text-stone-500 dark:text-stone-400">{field.label}:</span>
            <span class="font-semibold text-stone-900 dark:text-stone-100 text-right max-w-[60%] break-words {field.key === 'PK' || field.key === 'UK' ? 'font-mono text-indigo-600 dark:text-indigo-400' : ''}">
              {selectedDetailSimRecord[field.name] !== undefined && selectedDetailSimRecord[field.name] !== '' ? selectedDetailSimRecord[field.name] : '-'}
            </span>
          </div>
        {/each}
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button
          type="button"
          onclick={() => showDetailSimModal = false}
          class="px-4 py-2 text-xs font-semibold rounded-lg bg-stone-100 hover:bg-stone-200 dark:bg-stone-800 dark:hover:bg-stone-700 text-stone-700 dark:text-stone-300 cursor-pointer"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
{/if}

<!-- Modal Dialog 3: Konfirmasi Hapus Destruktif (Destructive Delete Modal) -->
{#if showDeleteSimModal && selectedDeleteSimRecord}
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xl max-w-md w-full p-6 space-y-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg shrink-0">
          ⚠️
        </div>
        <div>
          <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100">
            Konfirmasi Hapus Data Record
          </h3>
          <p class="text-xs text-stone-500 mt-0.5">
            Tindakan ini bersifat destruktif dan akan menghapus baris dari simulasi.
          </p>
        </div>
      </div>

      <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 text-xs text-rose-800 dark:text-rose-300 space-y-1">
        <p class="font-semibold">Apakah Anda yakin ingin menghapus baris data berikut?</p>
        <p class="font-mono text-[11px] text-stone-600 dark:text-stone-400">
          ID: #{selectedDeleteSimRecord.id} • Tabel: [{scopingState.activeModule?.entity?.tableName}]
        </p>
      </div>

      <div class="flex justify-end gap-2 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button
          type="button"
          onclick={() => showDeleteSimModal = false}
          class="px-4 py-2 text-xs font-semibold rounded-lg border border-stone-300 dark:border-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800 cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          onclick={confirmDeleteSimRecord}
          class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-xs transition-all cursor-pointer flex items-center gap-1.5"
        >
          <span>🗑️</span>
          <span>Ya, Hapus Data</span>
        </button>
      </div>
    </div>
  </div>
{/if}
