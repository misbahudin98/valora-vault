/**
 * VALORA VAULT - UNIVERSAL PROJECT SCOPING & SPECIFICATION STATE
 * Svelte 5 Runes ($state, $derived) Pure JavaScript
 * Standard: SDD-Enterprise-Deterministic v8.0.0
 * Invariants: Zero financial/budget metrics, Zero Man-Days, Lean Technical Architecture
 */

class ScopingManager {
  // 1. PROJECT & CLIENT METADATA
  project = $state({
    clientName: 'PT Valora Digital Solusindo',
    projectName: 'Valora Vault Starter Engine',
    slug: 'valora_vault_core',
    appType: 'webapp', // 'webapp' | 'cms_landing'
    authMechanism: 'login_only', // 'login_only' | 'login_register' | 'none'
    targetDeliveryDays: 14,
    status: 'Starter Kit Production-Ready'
  });

  // 1B. CASE STUDY & RAW INTAKE WITH SMART RECOMMENDER
  caseStudy = $state({
    rawBrief: 'Inisiasi fondasi clean starter kit berbasis Laravel 13 Sanctum Session, Svelte 5 Runes Pure JS, SQLite WAL, RBAC Policy defensif, dan sistem telemetri observabilitas terpadu.',
    objectives: 'Arsitektur deterministik, eliminasi connection exhaustion, kecepatan rendering sub-milidetik, dan kontrak spesifikasi presisi tanpa kode malas.',
    detectedDomain: 'Enterprise Starter Kit Engine & Governance Hub',
    confidenceScore: 98,
    recommendations: []
  });

  // 2. GRANULAR ROLES (CRUD ENABLED - CLEAN STARTER KIT INVARIANTS)
  roles = $state([
    {
      id: 'role-1',
      name: 'Super Administrator',
      slug: 'admin',
      description: 'Kendali penuh konfigurasi sistem, RBAC, audit trail, telemetri, dan session kill-switch.',
      color: '#4f46e5'
    },
    {
      id: 'role-2',
      name: 'Operator / Regular User',
      slug: 'user',
      description: 'Pengguna terotentikasi standar dengan akses fitur mandiri dan monitoring sesi aktif.',
      color: '#0d9488'
    }
  ]);

  // 3. GRANULAR MODULES, ERD ENTITIES & FEATURES (CRUD ENABLED - CLEAN STARTER KIT)
  modules = $state([
    {
      id: 'mod-1',
      roleId: 'role-1',
      name: 'Master Tata Kelola Akun & RBAC',
      code: '01',
      slug: 'governance_rbac',
      description: 'Manajemen kredensial pengguna, matriks peran (admin/user), status akun, dan reset sandi.',
      entity: {
        tableName: 'users',
        description: 'Tabel master akun pengguna, kredensial hash Argon2id, token version, dan peran akses.',
        fields: [
          { name: 'id', type: 'bigint', key: 'PK', label: 'ID Pengguna' },
          { name: 'name', type: 'string', key: '', label: 'Nama Lengkap', inputType: 'text' },
          { name: 'email', type: 'string', key: 'UK', label: 'Alamat Email', inputType: 'email' },
          { name: 'role', type: 'string', key: '', label: 'Peran Akses', inputType: 'select', options: ['Super Administrator', 'Operator / Regular User'] },
          { name: 'status', type: 'string', key: '', label: 'Status Akun', inputType: 'select', options: ['Aktif', 'Ditangguhkan'] }
        ],
        kpis: [
          { label: 'Total Pengguna', value: '42 User', trend: '+12% bln ini' },
          { label: 'Sesi Aktif', value: '18 Perangkat', trend: 'Normal' },
          { label: 'Keamanan Akun', value: '100% MFA', trend: 'Optimal' }
        ],
        mockStream: [
          { id: 1, name: 'Root Administrator', email: 'admin@valora-vault.test', role: 'Super Administrator', status: 'Aktif' },
          { id: 2, name: 'Lead Fullstack', email: 'iqbal@valora-vault.test', role: 'Lead Fullstack Engineer', status: 'Aktif' },
          { id: 3, name: 'Backend Architect', email: 'misbah@valora-vault.test', role: 'Database Architect', status: 'Aktif' }
        ]
      },
      features: [
        {
          id: 'feat-1',
          name: 'Manajemen Pengguna & Matriks Hak Akses',
          slug: 'account_management_rbac',
          complexity: 'crud', // 'crud' | 'custom_logic' | 'integration'
          purpose: 'Menyediakan CRUD akun pengguna, proteksi otorisasi Policy, dan penugasan peran RBAC.',
          views: [
            { type: 'dashboard_box', title: 'Statistik User Aktif', metric: '42 Pengguna' },
            { type: 'data_table', title: 'Tabel Master Pengguna & Peran', fieldsCount: 5 },
            { type: 'crud_form', title: 'Modal Tambah/Edit Pengguna', fieldsCount: 4 }
          ]
        },
        {
          id: 'feat-2',
          name: 'Self-Service Profil & Kredensial Pengguna',
          slug: 'user_profile_security',
          complexity: 'crud',
          purpose: 'Portal mandiri bagi pengguna untuk memperbarui nama, email, dan mengganti kata sandi.',
          views: [
            { type: 'crud_form', title: 'Formulir Pembaruan Sandi & Profil', fieldsCount: 3 },
            { type: 'dashboard_box', title: 'Status Keamanan Akun', metric: 'Terkonfirmasi' }
          ]
        }
      ]
    },
    {
      id: 'mod-2',
      roleId: 'role-1',
      name: 'Observabilitas Sesi Sanctum & Audit Perangkat',
      code: '02',
      slug: 'session_observability',
      description: 'Pemantauan sesi aktif berbasis file, pelacakan IP & user-agent, dan kill-switch darurat.',
      entity: {
        tableName: 'sessions',
        description: 'Tabel representasi metadata sesi aktif, identifier payload, IP remote, dan timestamp.',
        fields: [
          { name: 'id', type: 'string', key: 'PK', label: 'ID Sesi' },
          { name: 'user_id', type: 'bigint', key: 'FK', label: 'ID Pengguna' },
          { name: 'ip_address', type: 'string', key: '', label: 'Alamat IP', inputType: 'text' },
          { name: 'user_agent', type: 'string', key: '', label: 'Perangkat / Browser', inputType: 'text' },
          { name: 'last_activity', type: 'timestamp', key: '', label: 'Aktivitas Terakhir', inputType: 'text' }
        ],
        kpis: [
          { label: 'Sesi Aktif', value: '18 Perangkat', trend: 'Normal' },
          { label: 'IP Unik', value: '12 Alamat', trend: 'Aman' },
          { label: 'Session Driver', value: 'File (NVMe)', trend: 'Fast' }
        ],
        mockStream: [
          { id: 'sess_9f82a1', user_id: 1, ip_address: '127.0.0.1', user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64)', last_activity: '1 menit lalu' },
          { id: 'sess_3e41b8', user_id: 2, ip_address: '192.168.1.45', user_agent: 'Chrome/124.0 (macOS)', last_activity: '5 menit lalu' },
          { id: 'sess_7c19d4', user_id: 3, ip_address: '10.0.0.12', user_agent: 'Firefox/126.0 (Linux)', last_activity: '12 menit lalu' }
        ]
      },
      features: [
        {
          id: 'feat-3',
          name: 'Pemantauan Sesi Aktif & Kill-Switch Fisik',
          slug: 'session_monitor_killswitch',
          complexity: 'custom_logic',
          purpose: 'Monitoring konkurensi sesi real-time dan fungsi pemutusan sesi perangkat jarak jauh.',
          views: [
            { type: 'dashboard_box', title: 'Total Sesi Konkuren', metric: '18 Sesi' },
            { type: 'data_table', title: 'Stream Sesi Aktif & IP Address', fieldsCount: 5 }
          ]
        }
      ]
    },
    {
      id: 'mod-3',
      roleId: 'role-1',
      name: 'Telemetri Sistem, Cache & Health Check WAL',
      code: '03',
      slug: 'system_telemetry',
      description: 'Observabilitas kesehatan server SQLite WAL, status cache memory, dan log audit sistem.',
      entity: {
        tableName: 'system_telemetry_logs',
        description: 'Tabel log telemetri kesehatan runtime, latensi kueri SQLite, dan checkpoint WAL.',
        fields: [
          { name: 'id', type: 'bigint', key: 'PK', label: 'ID Log' },
          { name: 'component', type: 'string', key: '', label: 'Komponen Sistem', inputType: 'text' },
          { name: 'metric_name', type: 'string', key: '', label: 'Indikator Metrik', inputType: 'text' },
          { name: 'value', type: 'string', key: '', label: 'Nilai / Status', inputType: 'text' },
          { name: 'status', type: 'string', key: '', label: 'Status Kesehatan', inputType: 'select', options: ['Optimal', 'Warning', 'Critical'] }
        ],
        kpis: [
          { label: 'SQLite Mode', value: 'WAL Active', trend: 'Synchronous Normal' },
          { label: 'DB Latency', value: '0.42 ms', trend: '< 1ms Target' },
          { label: 'Uptime Sistem', value: '99.98%', trend: 'Layanan Stabil' }
        ],
        mockStream: [
          { id: 1, component: 'Database SQLite', metric_name: 'PRAGMA journal_mode', value: 'WAL', status: 'Optimal' },
          { id: 2, component: 'Session Store', metric_name: 'Driver I/O Latency', value: '0.12 ms', status: 'Optimal' },
          { id: 3, component: 'HTTP Server', metric_name: 'Average API Response', value: '18 ms', status: 'Optimal' }
        ]
      },
      features: [
        {
          id: 'feat-4',
          name: 'Inspeksi Kinerja & Telemetri Server',
          slug: 'server_telemetry_health',
          complexity: 'custom_logic',
          purpose: 'Menampilkan metrik performa backend, status PRAGMA SQLite, dan konsumsi memori PHP.',
          views: [
            { type: 'chart', title: 'Grafik Latensi Kueri SQLite', metric: '0.42 ms' },
            { type: 'data_table', title: 'Tabel Status Komponen Sistem', fieldsCount: 5 }
          ]
        },
        {
          id: 'feat-5',
          name: 'Audit Trail & Log Aktivitas Sistem',
          slug: 'audit_trail_logging',
          complexity: 'crud',
          purpose: 'Pencatatan aktivitas administratif sensitif, login, dan modifikasi data terproteksi.',
          views: [
            { type: 'data_table', title: 'Log Audit Keamanan Terenkripsi', fieldsCount: 5 },
            { type: 'dashboard_box', title: 'Total Log 24 Jam', metric: '1,240 Event' }
          ]
        }
      ]
    }
  ]);

  // Active feature selection for Live Mockup Workspace
  selectedFeatureId = $state('feat-1');

  // ==========================================
  // COMPUTED METRICS & DERIVED VALUES
  // ==========================================

  get totalModules() {
    return this.modules.length;
  }

  get totalRoles() {
    return this.roles.length;
  }

  get allFeatures() {
    const list = [];
    for (const mod of this.modules) {
      for (const feat of mod.features) {
        list.push({
          ...feat,
          moduleCode: mod.code,
          moduleName: mod.name,
          moduleSlug: mod.slug,
          roleId: mod.roleId,
          entity: mod.entity
        });
      }
    }
    return list;
  }

  get totalFeatures() {
    return this.allFeatures.length;
  }

  // Chart 1: Distribusi Fitur per Role (Berdasarkan jumlah fitur)
  get roleEffortBreakdown() {
    const total = this.totalFeatures || 1;
    return this.roles.map((role) => {
      const roleModules = this.modules.filter((m) => m.roleId === role.id);
      let featureCount = 0;
      for (const m of roleModules) {
        featureCount += m.features.length;
      }
      const percentage = Math.round((featureCount / total) * 100);
      return {
        roleId: role.id,
        roleName: role.name,
        roleSlug: role.slug,
        color: role.color,
        featureCount,
        percentage
      };
    });
  }

  // Chart 2: Komposisi Kompleksitas Rekayasa (Berdasarkan jumlah fitur)
  get complexityComposition() {
    let crudCount = 0;
    let customCount = 0;
    let integrationCount = 0;

    for (const f of this.allFeatures) {
      if (f.complexity === 'crud') {
        crudCount++;
      } else if (f.complexity === 'custom_logic') {
        customCount++;
      } else {
        integrationCount++;
      }
    }

    const total = this.totalFeatures || 1;
    return {
      crud: {
        count: crudCount,
        percentage: Math.round((crudCount / total) * 100),
        color: '#10b981',
        label: 'Operasi CRUD Standar'
      },
      customLogic: {
        count: customCount,
        percentage: Math.round((customCount / total) * 100),
        color: '#4f46e5',
        label: 'Logika Bisnis & Workflow'
      },
      integration: {
        count: integrationCount,
        percentage: Math.round((integrationCount / total) * 100),
        color: '#f59e0b',
        label: 'Integrasi Eksternal / API'
      }
    };
  }

  // Active selected feature object
  get activeFeature() {
    return this.allFeatures.find((f) => f.id === this.selectedFeatureId) || this.allFeatures[0] || null;
  }

  get activeModule() {
    if (!this.activeFeature) return null;
    return this.modules.find((m) => m.features.some((f) => f.id === this.activeFeature.id)) || this.modules[0] || null;
  }

  get activeRole() {
    if (!this.activeModule) return null;
    return this.roles.find((r) => r.id === this.activeModule.roleId) || this.roles[0] || null;
  }

  // ==========================================
  // CLIENT & PROJECT CRUD
  // ==========================================
  updateProject(data) {
    if (data.clientName) this.project.clientName = data.clientName;
    if (data.projectName) this.project.projectName = data.projectName;
    if (data.slug) this.project.slug = data.slug.toLowerCase().replace(/[^a-z0-9_]/g, '_');
    if (data.appType) this.project.appType = data.appType;
    if (data.authMechanism) this.project.authMechanism = data.authMechanism;
    if (data.targetDeliveryDays) this.project.targetDeliveryDays = Number(data.targetDeliveryDays) || 20;
  }

  // ==========================================
  // ROLE CRUD
  // ==========================================
  addRole(role) {
    const newId = `role-${Date.now()}`;
    this.roles.push({
      id: newId,
      name: role.name || 'Peran Baru',
      slug: (role.slug || 'peran_baru').toLowerCase().replace(/[^a-z0-9_]/g, '_'),
      description: role.description || 'Deskripsi hak akses dan tanggung jawab peran.',
      color: role.color || '#6366f1'
    });
    return newId;
  }

  updateRole(roleId, data) {
    const r = this.roles.find((item) => item.id === roleId);
    if (!r) return false;
    if (data.name) r.name = data.name;
    if (data.slug) r.slug = data.slug.toLowerCase().replace(/[^a-z0-9_]/g, '_');
    if (data.description !== undefined) r.description = data.description;
    if (data.color) r.color = data.color;
    return true;
  }

  removeRole(roleId) {
    if (this.roles.length <= 1) return false;
    // Reassign affected modules to first remaining role
    const fallbackRoleId = this.roles.find((r) => r.id !== roleId)?.id || 'role-1';
    for (const m of this.modules) {
      if (m.roleId === roleId) {
        m.roleId = fallbackRoleId;
      }
    }
    this.roles = this.roles.filter((r) => r.id !== roleId);
    return true;
  }

  // ==========================================
  // MODULE CRUD
  // ==========================================
  addModule(data) {
    const code = String(this.modules.length + 1).padStart(2, '0');
    const slug = (data.name || 'modul_baru').toLowerCase().replace(/[^a-z0-9]/g, '_');
    const tableName = (data.tableName || slug).toLowerCase().replace(/[^a-z0-9]/g, '_');
    const roleId = data.roleId || this.roles[0]?.id || 'role-1';

    const newMod = {
      id: `mod-${Date.now()}`,
      roleId,
      name: data.name || `Modul ${code}`,
      code,
      slug,
      description: data.description || 'Deskripsi cakupan modul fungsional.',
      entity: {
        tableName,
        description: `Tabel data entitas untuk ${data.name || slug}.`,
        fields: [
          { name: 'id', type: 'bigint', key: 'PK', label: 'ID' },
          { name: 'code', type: 'string', key: 'UK', label: 'Kode Entitas', inputType: 'text' },
          { name: 'title', type: 'string', key: '', label: 'Nama / Judul', inputType: 'text' },
          { name: 'status', type: 'string', key: '', label: 'Status', inputType: 'select', options: ['Aktif', 'Pending', 'Selesai'] }
        ],
        kpis: [
          { label: `Total Data`, value: '12 Data', trend: 'Stabil' },
          { label: `Data Aktif`, value: '8 Data', trend: 'Normal' },
          { label: `Kinerja`, value: '100%', trend: 'Optimal' }
        ],
        mockStream: [
          { id: 1, code: `${slug.toUpperCase().slice(0, 3)}-01`, title: 'Entri Data Simulasi 1', status: 'Aktif' },
          { id: 2, code: `${slug.toUpperCase().slice(0, 3)}-02`, title: 'Entri Data Simulasi 2', status: 'Pending' }
        ]
      },
      features: [
        {
          id: `feat-${Date.now()}`,
          name: `Kelola Data ${data.name || code}`,
          slug: `${slug}_management`,
          complexity: 'crud',
          purpose: `Manajemen data dan alur kerja utama untuk modul ${data.name}.`,
          views: [
            { type: 'data_table', title: `Tabel Data ${data.name}`, fieldsCount: 4 },
            { type: 'crud_form', title: `Formulir Entri ${data.name}`, fieldsCount: 3 }
          ]
        }
      ]
    };

    this.modules.push(newMod);
    this.selectedFeatureId = newMod.features[0].id;
    return newMod;
  }

  updateModule(moduleId, data) {
    const mod = this.modules.find((m) => m.id === moduleId);
    if (!mod) return false;
    if (data.name) mod.name = data.name;
    if (data.roleId) mod.roleId = data.roleId;
    if (data.description !== undefined) mod.description = data.description;
    if (data.tableName && mod.entity) mod.entity.tableName = data.tableName.toLowerCase().replace(/[^a-z0-9_]/g, '_');
    return true;
  }

  removeModule(moduleId) {
    if (this.modules.length <= 1) return false;
    this.modules = this.modules.filter((m) => m.id !== moduleId);
    if (!this.modules.some((m) => m.features.some((f) => f.id === this.selectedFeatureId))) {
      this.selectedFeatureId = this.allFeatures[0]?.id || '';
    }
    return true;
  }

  // ==========================================
  // FEATURE CRUD
  // ==========================================
  addFeature(moduleId, feature) {
    const mod = this.modules.find((m) => m.id === moduleId);
    if (!mod) return false;
    const newFeatId = `feat-${Date.now()}`;
    mod.features.push({
      id: newFeatId,
      name: feature.name || 'Fitur Baru',
      slug: (feature.name || 'fitur_baru').toLowerCase().replace(/[^a-z0-9]/g, '_'),
      complexity: feature.complexity || 'crud',
      purpose: feature.purpose || `Fungsionalitas fitur untuk ${feature.name}.`,
      views: feature.views || [
        { type: 'data_table', title: 'Tabel Data Utama', fieldsCount: 5 }
      ]
    });
    this.selectedFeatureId = newFeatId;
    return newFeatId;
  }

  updateFeature(moduleId, featureId, data) {
    const mod = this.modules.find((m) => m.id === moduleId);
    if (!mod) return false;
    const feat = mod.features.find((f) => f.id === featureId);
    if (!feat) return false;
    if (data.name) feat.name = data.name;
    if (data.complexity) feat.complexity = data.complexity;
    if (data.purpose !== undefined) feat.purpose = data.purpose;
    return true;
  }

  removeFeature(moduleId, featureId) {
    const mod = this.modules.find((m) => m.id === moduleId);
    if (!mod || mod.features.length <= 1) return false;
    mod.features = mod.features.filter((f) => f.id !== featureId);
    if (this.selectedFeatureId === featureId) {
      this.selectedFeatureId = this.allFeatures[0]?.id || '';
    }
    return true;
  }

  // ==========================================
  // LIVE SIMULATION DATA MUTATION
  // ==========================================
  addMockEntry(moduleId, entryData) {
    const mod = this.modules.find((m) => m.id === moduleId);
    if (!mod || !mod.entity) return null;
    const newId = mod.entity.mockStream.length + 1;
    const newRecord = { id: newId, ...entryData };
    mod.entity.mockStream = [newRecord, ...mod.entity.mockStream];
    return newRecord;
  }

  removeMockEntry(moduleId, entryId) {
    const mod = this.modules.find((m) => m.id === moduleId);
    if (!mod || !mod.entity) return false;
    mod.entity.mockStream = mod.entity.mockStream.filter((item) => item.id !== entryId);
    return true;
  }

  updateMockEntry(moduleId, entryId, updatedData) {
    const mod = this.modules.find((m) => m.id === moduleId);
    if (!mod || !mod.entity) return false;
    const index = mod.entity.mockStream.findIndex((item) => item.id === entryId);
    if (index === -1) return false;
    mod.entity.mockStream[index] = { ...mod.entity.mockStream[index], ...updatedData };
    return true;
  }

  // ==========================================
  // SMART RAW BRIEF & FEATURE RECOMMENDER
  // ==========================================
  analyzeAndRecommendFeatures(rawText) {
    const text = (rawText || this.caseStudy.rawBrief || '').toLowerCase();

    // Domain Profiling Patterns
    const domains = [
      {
        key: 'enterprise_starter',
        name: 'Enterprise Starter Kit Engine & Governance Hub',
        keywords: ['starter', 'kit', 'auth', 'rbac', 'admin', 'user', 'session', 'telemetri', 'system', 'governance', 'clean', 'valora', 'dashboard'],
        preset: {
          clientName: 'PT Valora Digital Solusindo',
          projectName: 'Valora Vault Starter Engine',
          slug: 'valora_vault_core',
          appType: 'webapp',
          authMechanism: 'login_only',
          targetDeliveryDays: 14,
          roles: [
            { id: 'role-1', name: 'Super Administrator', slug: 'admin', description: 'Kendali penuh konfigurasi sistem, RBAC, dan audit trail.', color: '#4f46e5' },
            { id: 'role-2', name: 'Operator / Regular User', slug: 'user', description: 'Pengguna terotentikasi standar dengan akses profil mandiri.', color: '#0d9488' }
          ],
          features: [
            { id: 'rec-1', name: 'Manajemen Akun & Matriks Role (RBAC)', moduleName: 'Master Tata Kelola Akun & RBAC', roleSlug: 'admin', complexity: 'crud', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Daftar Pengguna' }, { type: 'crud_form', title: 'Modal Akun' }] },
            { id: 'rec-2', name: 'Observabilitas Sesi & Pemutusan Fisik', moduleName: 'Observabilitas Sesi Sanctum', roleSlug: 'admin', complexity: 'custom_logic', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Stream Perangkat & IP' }] },
            { id: 'rec-3', name: 'Telemetri SQLite WAL & Server Health', moduleName: 'Telemetri Sistem & Health Check', roleSlug: 'admin', complexity: 'custom_logic', priority: 'P1 Essential', views: [{ type: 'chart', title: 'Grafik Latensi Kueri' }, { type: 'data_table', title: 'Status Komponen' }] },
            { id: 'rec-4', name: 'Self-Service Profil & Ubah Sandi', moduleName: 'Master Tata Kelola Akun & RBAC', roleSlug: 'user', complexity: 'crud', priority: 'P0 Core MVP', views: [{ type: 'crud_form', title: 'Formulir Profil' }] }
          ]
        }
      },
      {
        key: 'agency_erp',
        name: 'Agency ERP & IT Operations Platform',
        keywords: ['proyek', 'project', 'agency', 'erp', 'it', 'klien', 'client', 'keuangan', 'kas', 'akad', 'billing', 'invoice', 'aturan bisnis', 'pencatatan'],
        preset: {
          clientName: 'Valora Global Solusindo',
          projectName: 'Agency ERP & IT Project Tracker',
          slug: 'valora_agency_erp',
          appType: 'webapp',
          authMechanism: 'login_only',
          targetDeliveryDays: 25,
          roles: [
            { id: 'role-1', name: 'Super Administrator', slug: 'admin', description: 'Kendali konfigurasi sistem, RBAC, dan audit trail.', color: '#4f46e5' },
            { id: 'role-2', name: 'Project & Operations Manager', slug: 'manager', description: 'Persetujuan milestone, SLA proyek, dan alokasi tim.', color: '#0d9488' },
            { id: 'role-3', name: 'Lead Engineer & Staff', slug: 'staff', description: 'Pencatatan progres repositori, tugas harian, dan log kas.', color: '#f59e0b' },
            { id: 'role-4', name: 'Klien / Stakeholder Eksternal', slug: 'client', description: 'Portal mandiri cek milestone proyek dan verifikasi.', color: '#ec4899' }
          ],
          features: [
            { id: 'rec-1', name: 'Manajemen Akun & Matriks Role (RBAC)', moduleName: 'Master Tata Kelola & RBAC', roleSlug: 'admin', complexity: 'crud', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Daftar Pengguna' }, { type: 'crud_form', title: 'Modal Akun' }] },
            { id: 'rec-2', name: 'Observabilitas Sesi & Pemutusan Fisik', moduleName: 'Master Tata Kelola & RBAC', roleSlug: 'admin', complexity: 'custom_logic', priority: 'P1 Essential', views: [{ type: 'data_table', title: 'Stream Perangkat & IP' }] },
            { id: 'rec-3', name: 'Katalog Proyek & Tracking SLA', moduleName: 'Pencatatan Proyek & Milestone', roleSlug: 'manager', complexity: 'custom_logic', priority: 'P0 Core MVP', views: [{ type: 'chart', title: 'Grafik Deliverables' }, { type: 'data_table', title: 'Portofolio Proyek' }] },
            { id: 'rec-4', name: 'Approval Milestone & Sign-Off Klien', moduleName: 'Pencatatan Proyek & Milestone', roleSlug: 'manager', complexity: 'custom_logic', priority: 'P1 Essential', views: [{ type: 'data_table', title: 'Milestone Menunggu Review' }, { type: 'crud_form', title: 'Modal Berita Acara' }] },
            { id: 'rec-5', name: 'Buku Kas Masuk/Keluar Proyek', moduleName: 'Operasional Keuangan & Buku Kas', roleSlug: 'staff', complexity: 'crud', priority: 'P0 Core MVP', views: [{ type: 'dashboard_box', title: 'Saldo Kas' }, { type: 'data_table', title: 'Buku Kas Harian' }] },
            { id: 'rec-6', name: 'Portal Deliverables & Verifikasi Dokumen', moduleName: 'Portal Stakeholder & Transparansi Klien', roleSlug: 'client', complexity: 'integration', priority: 'P1 Essential', views: [{ type: 'dashboard_box', title: 'Status Deliverables' }, { type: 'data_table', title: 'Histori Dokumen' }] }
          ]
        }
      },
      {
        key: 'yayasan_ngo',
        name: 'Sistem Informasi Lembaga Yayasan, ZIS & Sosial',
        keywords: ['yayasan', 'donasi', 'zakat', 'infaq', 'sedekah', 'santri', 'mustahik', 'muzakki', 'zis', 'pqtm', 'pesantren', 'sosial', 'wakaf'],
        preset: {
          clientName: 'Yayasan Peduli Quran & Yatim Mandiri',
          projectName: 'Sistem Lembaga Yayasan & Transparansi ZIS',
          slug: 'yayasan_pqtm_core',
          appType: 'webapp',
          authMechanism: 'login_register',
          targetDeliveryDays: 20,
          roles: [
            { id: 'role-1', name: 'Dewan Pengurus & Super Admin', slug: 'admin', description: 'Kendali operasional yayasan, audit kas ZIS, dan legalitas.', color: '#4f46e5' },
            { id: 'role-2', name: 'Petugas Amil & Lapangan', slug: 'staff', description: 'Pencatatan donasi langsung, penyaluran sembako/beasiswa.', color: '#0d9488' },
            { id: 'role-3', name: 'Muzakki & Donatur Publik', slug: 'donor', description: 'Portal mandiri cek bukti setor donasi dan laporan penyaluran.', color: '#10b981' },
            { id: 'role-4', name: 'Mustahik & Penerima Manfaat', slug: 'beneficiary', description: 'Data verifikasi penerima santunan dan riwayat bantuan.', color: '#f59e0b' }
          ],
          features: [
            { id: 'rec-1', name: 'Master Data Muzakki & Mustahik Asnaf', moduleName: 'Master Tata Kelola & Entitas', roleSlug: 'admin', complexity: 'crud', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Katalog Mustahik & Muzakki' }, { type: 'crud_form', title: 'Formulir Asnaf' }] },
            { id: 'rec-2', name: 'Pencatatan Donasi Masuk & Kwitansi Otomatis', moduleName: 'Fundraising & Donasi Masuk', roleSlug: 'staff', complexity: 'custom_logic', priority: 'P0 Core MVP', views: [{ type: 'dashboard_box', title: 'Total Donasi Himpunan' }, { type: 'data_table', title: 'Buku Penerimaan ZIS' }] },
            { id: 'rec-3', name: 'Distribusi Penyaluran & Dokumentasi Bukti', moduleName: 'Program Penyaluran & Mustahik', roleSlug: 'staff', complexity: 'custom_logic', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Laporan Penyaluran Bantuan' }, { type: 'crud_form', title: 'Berita Acara Penyerahan' }] },
            { id: 'rec-4', name: 'Laporan Transparansi Keuangan Real-Time', moduleName: 'Audit & Akuntabilitas Publik', roleSlug: 'admin', complexity: 'integration', priority: 'P1 Essential', views: [{ type: 'chart', title: 'Grafik Saldo per Rekening Asnaf' }, { type: 'data_table', title: 'Neraca Kas Publik' }] },
            { id: 'rec-5', name: 'Portal Donatur & Unduh Bukti Setor ZIS', moduleName: 'Portal Donatur Mandiri', roleSlug: 'donor', complexity: 'crud', priority: 'P1 Essential', views: [{ type: 'dashboard_box', title: 'Akumulasi Donasi Anda' }, { type: 'data_table', title: 'Riwayat Transaksi' }] }
          ]
        }
      },
      {
        key: 'ecommerce_retail',
        name: 'E-Commerce & Supply Chain Inventory Platform',
        keywords: ['produk', 'toko', 'keranjang', 'checkout', 'ongkir', 'pesanan', 'order', 'gudang', 'stok', 'supplier', 'marketplace', 'katalog'],
        preset: {
          clientName: 'Retail Commerce Network',
          projectName: 'Digital Store & Omni-Channel Inventory',
          slug: 'omni_retail_store',
          appType: 'webapp',
          authMechanism: 'login_register',
          targetDeliveryDays: 22,
          roles: [
            { id: 'role-1', name: 'Store Owner & Administrator', slug: 'admin', description: 'Kendali katalog produk, diskon, dan analitik penjualan.', color: '#4f46e5' },
            { id: 'role-2', name: 'Staf Gudang & Logistik', slug: 'warehouse', description: 'Pengecekan stok fisik, packing, dan input nomor resi kurir.', color: '#f59e0b' },
            { id: 'role-3', name: 'Customer & Pembeli Terdaftar', slug: 'customer', description: 'Belanja produk, lacak status paket, dan histori pembayaran.', color: '#10b981' }
          ],
          features: [
            { id: 'rec-1', name: 'Katalog Produk & Manajemen Varian Stok', moduleName: 'Master Produk & Inventori', roleSlug: 'admin', complexity: 'crud', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Daftar SKU Produk' }, { type: 'crud_form', title: 'Form Produk' }] },
            { id: 'rec-2', name: 'Keranjang Belanja & Checkout Multi-Kurir', moduleName: 'Transaksi Penjualan & Checkout', roleSlug: 'customer', complexity: 'custom_logic', priority: 'P0 Core MVP', views: [{ type: 'crud_form', title: 'Form Alamat & Kurir' }, { type: 'data_table', title: 'Ringkasan Keranjang' }] },
            { id: 'rec-3', name: 'Fulfillment Pesanan & Cetak Label Pengiriman', moduleName: 'Logistik & Ekspedisi Gudang', roleSlug: 'warehouse', complexity: 'custom_logic', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Antrean Packing Pesanan' }] },
            { id: 'rec-4', name: 'Rekonsiliasi Pembayaran Gateway & Mutasi', moduleName: 'Keuangan & Settlement Pembayaran', roleSlug: 'admin', complexity: 'integration', priority: 'P1 Essential', views: [{ type: 'dashboard_box', title: 'Omset Hari Ini' }, { type: 'data_table', title: 'Log Transaksi Gateway' }] }
          ]
        }
      },
      {
        key: 'healthcare_clinic',
        name: 'Sistem Informasi Rekam Medis & Klinik Rawat Jalan',
        keywords: ['klinik', 'dokter', 'pasien', 'obat', 'rekam medis', 'antrean', 'resep', 'diagnosa', 'medis', 'farmasi', 'laboratorium'],
        preset: {
          clientName: 'Klinik Medika Sejahtera',
          projectName: 'Sistem Rekam Medis Elektronik (RME) & Antrean',
          slug: 'klinik_rme_system',
          appType: 'webapp',
          authMechanism: 'login_only',
          targetDeliveryDays: 24,
          roles: [
            { id: 'role-1', name: 'Kepala Klinik & Administrator', slug: 'admin', description: 'Pengaturan master tarif, jadwal dokter, dan laporan audit.', color: '#4f46e5' },
            { id: 'role-2', name: 'Dokter Pemeriksa', slug: 'doctor', description: 'Input anamnesa, diagnosis ICD-10, dan e-resep pasien.', color: '#0d9488' },
            { id: 'role-3', name: 'Resepsionis & Loket Pendaftaran', slug: 'frontdesk', description: 'Pendaftaran pasien baru, antrean poli, dan cetak kartu.', color: '#f59e0b' },
            { id: 'role-4', name: 'Apoteker / Farmasi Kasir', slug: 'pharmacy', description: 'Penyerahan obat, cetak etiket, dan pembayaran tindakan.', color: '#ec4899' }
          ],
          features: [
            { id: 'rec-1', name: 'Pendaftaran Pasien & Tiket Antrean Poli', moduleName: 'Frontdesk & Registrasi Pasien', roleSlug: 'frontdesk', complexity: 'crud', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Antrean Pasien Hari Ini' }, { type: 'crud_form', title: 'Registrasi Pasien' }] },
            { id: 'rec-2', name: 'Rekam Medis Elektronik (Anamnesa & SOAP)', moduleName: 'Poli Medis & Pemeriksaan Dokter', roleSlug: 'doctor', complexity: 'custom_logic', priority: 'P0 Core MVP', views: [{ type: 'crud_form', title: 'Formulir Pemeriksaan SOAP' }, { type: 'data_table', title: 'Histori Kunjungan Pasien' }] },
            { id: 'rec-3', name: 'E-Resep & Dispensing Obat Farmasi', moduleName: 'Apotek & Depo Farmasi', roleSlug: 'pharmacy', complexity: 'custom_logic', priority: 'P0 Core MVP', views: [{ type: 'data_table', title: 'Antrean Resep Farmasi' }, { type: 'crud_form', title: 'Penyerahan Obat' }] },
            { id: 'rec-4', name: 'Kasir Pembayaran & Cetak Kwitansi', moduleName: 'Billing & Kasir Klinik', roleSlug: 'pharmacy', complexity: 'crud', priority: 'P1 Essential', views: [{ type: 'dashboard_box', title: 'Total Pendapatan Harian' }, { type: 'data_table', title: 'Buku Kasir Klinik' }] }
          ]
        }
      }
    ];

    let bestDomain = domains[0];
    let maxMatches = 0;

    for (const d of domains) {
      let matches = 0;
      for (const kw of d.keywords) {
        if (text.includes(kw)) matches++;
      }
      if (matches > maxMatches) {
        maxMatches = matches;
        bestDomain = d;
      }
    }

    const confidence = maxMatches > 0 ? Math.min(98, 70 + maxMatches * 6) : 60;
    this.caseStudy.detectedDomain = bestDomain.name;
    this.caseStudy.confidenceScore = confidence;

    this.caseStudy.recommendations = bestDomain.preset.features.map((f, idx) => ({
      ...f,
      id: `rec-${idx + 1}`,
      selected: true,
      domainPreset: bestDomain.key
    }));

    this._currentMatchedPreset = bestDomain.preset;

    return {
      domainName: bestDomain.name,
      confidence,
      featuresCount: this.caseStudy.recommendations.length
    };
  }

  toggleRecommendation(id) {
    const item = this.caseStudy.recommendations.find((r) => r.id === id);
    if (item) {
      item.selected = !item.selected;
    }
  }

  toggleAllRecommendations(selected) {
    for (const item of this.caseStudy.recommendations) {
      item.selected = selected;
    }
  }

  applyRecommendations() {
    if (!this._currentMatchedPreset) {
      this.analyzeAndRecommendFeatures(this.caseStudy.rawBrief);
    }

    const preset = this._currentMatchedPreset;
    if (!preset) return false;

    this.project.projectName = preset.projectName;
    this.project.clientName = preset.clientName;
    this.project.slug = preset.slug;
    this.project.appType = preset.appType;
    this.project.authMechanism = preset.authMechanism;
    this.project.targetDeliveryDays = preset.targetDeliveryDays;
    this.project.status = 'Matriks Disepakati (Pre-Design)';

    this.roles = preset.roles.map((r) => ({ ...r }));

    const selectedRecs = this.caseStudy.recommendations.filter((r) => r.selected);
    if (selectedRecs.length === 0) return false;

    const moduleGroups = {};
    for (const rec of selectedRecs) {
      const modName = rec.moduleName;
      if (!moduleGroups[modName]) {
        const matchedRole = this.roles.find((r) => r.slug === rec.roleSlug) || this.roles[0];
        moduleGroups[modName] = {
          name: modName,
          roleId: matchedRole.id,
          features: []
        };
      }

      moduleGroups[modName].features.push({
        id: `feat-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
        name: rec.name,
        slug: rec.name.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 30),
        complexity: rec.complexity,
        purpose: `Fitur operasional untuk ${rec.name}.`,
        views: rec.views || [
          { type: 'data_table', title: `Tabel ${rec.name}`, fieldsCount: 5 },
          { type: 'crud_form', title: `Form ${rec.name}`, fieldsCount: 4 }
        ]
      });
    }

    const newModules = [];
    let modIndex = 1;
    for (const modName in moduleGroups) {
      const g = moduleGroups[modName];
      const code = String(modIndex).padStart(2, '0');
      const slug = modName.toLowerCase().replace(/[^a-z0-9]/g, '_').slice(0, 25);
      newModules.push({
        id: `mod-${modIndex}`,
        roleId: g.roleId,
        name: g.name,
        code,
        slug,
        description: `Cakupan fitur operasional untuk ${g.name}.`,
        entity: {
          tableName: slug,
          description: `Tabel data utama entitas ${g.name}.`,
          fields: [
            { name: 'id', type: 'bigint', key: 'PK', label: 'ID' },
            { name: 'code', type: 'string', key: 'UK', label: 'Kode', inputType: 'text' },
            { name: 'title', type: 'string', key: '', label: 'Nama / Judul', inputType: 'text' },
            { name: 'status', type: 'string', key: '', label: 'Status', inputType: 'select', options: ['Aktif', 'Pending', 'Selesai'] }
          ],
          kpis: [
            { label: 'Total Rekord', value: '24 Data', trend: 'Stabil' },
            { label: 'Status Valid', value: '100%', trend: 'Terverifikasi' },
            { label: 'Efisiensi', value: 'Tinggi', trend: 'Optimal' }
          ],
          mockStream: [
            { id: 1, code: `${slug.toUpperCase().slice(0, 3)}-101`, title: `${g.name} Record 1`, status: 'Aktif' },
            { id: 2, code: `${slug.toUpperCase().slice(0, 3)}-102`, title: `${g.name} Record 2`, status: 'Pending' }
          ]
        },
        features: g.features
      });
      modIndex++;
    }

    this.modules = newModules;
    this.selectedFeatureId = this.allFeatures[0]?.id || 'feat-1';
    return true;
  }

  // ==========================================
  // SDD COMPILER & EXPORT CONTRACT (NO MAN-DAYS, NO BUDGET)
  // ==========================================
  compileSddMarkdown() {
    const p = this.project;
    const dateStr = new Date().toISOString().split('T')[0];

    let md = `# SPESIFIKASI PROYEK & KONTRAK SDD (Specification-Driven Development)\n`;
    md += `> **Proyek:** ${p.projectName} (\`${p.slug}\`)  \n`;
    md += `> **Klien:** ${p.clientName}  \n`;
    md += `> **Klasifikasi Arsitektur:** ${p.appType === 'webapp' ? 'Full Web Application' : 'Landing Page / CMS Berita'}  \n`;
    md += `> **Mekanisme Autentikasi:** ${p.authMechanism}  \n`;
    md += `> **Total Fitur Terdaftar:** ${this.totalFeatures} Fitur (${this.totalModules} Modul)  \n`;
    md += `> **Waktu Rilis Target:** ${p.targetDeliveryDays} Hari Kerja | **Tanggal Audit:** ${dateStr}  \n\n`;
    md += `---\n\n`;

    // 1. Matriks Peran & Hak Akses
    md += `## 1. MATRIKS PERAN (RBAC HIERARCHY)\n\n`;
    md += `| Role ID | Nama Peran | Kode Slug | Deskripsi Tanggung Jawab |\n`;
    md += `|---|---|---|---|\n`;
    for (const r of this.roles) {
      md += `| \`${r.id}\` | **${r.name}** | \`${r.slug}\` | ${r.description} |\n`;
    }
    md += `\n---\n\n`;

    // 2. Rincian Modul & Fitur
    md += `## 2. BREAKDOWN MODUL, FITUR & ENTITAS DATABASE\n\n`;
    for (const m of this.modules) {
      const role = this.roles.find((r) => r.id === m.roleId);
      const tableName = m.entity?.tableName || m.slug;
      md += `### Modul [${m.code}]: ${m.name} (\`${m.slug}\`)\n`;
      md += `- **Penanggung Jawab Utama:** ${role ? role.name : 'All Roles'} (\`${role ? role.slug : 'global'}\`)\n`;
      md += `- **Tabel Database Utama:** \`${tableName}\`\n`;
      md += `- **Cakupan Bisnis:** ${m.description}\n\n`;

      md += `| Kode Fitur | Nama Fitur | Tingkat Kompleksitas | Tujuan & Kontrak Fungsional |\n`;
      md += `|---|---|---|---|\n`;
      for (const f of m.features) {
        md += `| \`${f.slug}\` | **${f.name}** | \`${f.complexity}\` | ${f.purpose || 'Operasional fitur.'} |\n`;
      }
      md += `\n`;
    }
    md += `---\n\n`;

    // 3. Entity Relational Diagram (ERD Mermaid)
    md += `## 3. ENTITY RELATIONSHIP DIAGRAM (MERMAID ERD)\n\n`;
    md += `\`\`\`mermaid\nerDiagram\n`;
    md += `    USERS ||--o{ USER_DEVICES : "authenticates"\n`;
    md += `    USERS ||--o{ AUDIT_LOGS : "triggers"\n`;
    for (const m of this.modules) {
      const entity = (m.entity?.tableName || m.slug).toUpperCase();
      if (entity !== 'USERS') {
        md += `    USERS ||--o{ ${entity} : "operates"\n`;
      }
    }
    md += `\n`;
    md += `    USERS {\n`;
    md += `        bigint id PK\n`;
    md += `        string email UK\n`;
    md += `        text role "JSON Array Roles"\n`;
    md += `        boolean is_active\n`;
    md += `    }\n`;
    for (const m of this.modules) {
      const entity = (m.entity?.tableName || m.slug).toUpperCase();
      if (entity !== 'USERS') {
        md += `    ${entity} {\n`;
        if (m.entity?.fields) {
          for (const f of m.entity.fields) {
            md += `        ${f.type} ${f.name} ${f.key ? f.key : ''}\n`;
          }
        } else {
          md += `        bigint id PK\n`;
          md += `        bigint user_id FK\n`;
          md += `        string code UK\n`;
          md += `        string status\n`;
        }
        md += `    }\n`;
      }
    }
    md += `\`\`\`\n\n`;
    md += `---\n\n`;

    // 4. Security & Compliance Criteria
    md += `## 4. SECURITY & RBAC ACCEPTANCE CRITERIA (MANDATORI)\n\n`;
    md += `- **[INV-SEC-01] Kebijakan Otorisasi:** Setiap endpoint akses data privat wajib dilindungi Laravel Policy (\`$this->authorize()\`). Mencegah Broken Object Level Authorization (BOLA).\n`;
    md += `- **[INV-SEC-02] Sanitasi Kueri:** Nol raw SQL string concatenation. Seluruh kueri wajib menggunakan PDO parameter binding.\n`;
    md += `- **[INV-SEC-03] Isolasi Sesi:** \`SESSION_DRIVER=file\` mutlak (NVMe SSD storage, 0 database connection overhead).\n`;
    md += `- **[INV-SEC-04] Validasi FormRequest:** Seluruh payload HTTP mutasi wajib divalidasi via Form Request ketat dengan regex dan rules eksplisit.\n`;
    md += `- **[INV-SEC-05] Standar RFC 7807:** Respons kesalahan API wajib mengikuti format Problem Details for HTTP APIs tanpa membocorkan trace sensitif.\n\n`;
    md += `---\n\n`;

    // 5. Test Verification Contract
    md += `## 5. TEST VERIFICATION CONTRACT (PHPUNIT MIN 3 SCENARIOS)\n\n`;
    md += `Setiap endpoint fitur di atas wajib diverifikasi dengan minimal 3 pengujian Feature Test:\n`;
    md += `1. **Happy Path (200 OK / 201 Created):** Mutasi data valid tersimpan dengan transaksi atomik.\n`;
    md += `2. **Form Validation (422 Unprocessable Entity):** Masukan data kosong atau tidak memenuhi format ditolak.\n`;
    md += `3. **Authorization Guard (403 Forbidden):** Permintaan dari peran yang tidak sah ditolak seketika oleh Policy.\n`;

    return md;
  }

  // ==========================================
  // TWO-PROMPT PIPELINE COMPILERS
  // ==========================================
  compilePromptStageA() {
    const p = this.project;
    const modulesSummary = this.modules
      .map((m) => `- Modul [${m.code}] ${m.name} (Tabel: \`${m.entity?.tableName || m.slug}\`): ${m.features.map((f) => f.name).join(', ')}`)
      .join('\n');
    const rolesList = this.roles.map((r) => r.name).join(', ');

    return `<role>
Principal UI/UX Architect & Svelte 5 Specialist.
Kamu bertindak sebagai sistem perancang visual dan arsitektur data awal untuk proyek web enterprise.
Fokusmu adalah menghasilkan Pratinjau Living Interactive Mockup Canvas dan Skema Visual Database ERD yang persis dan merepresentasikan kontrak final aplikasi.
</role>

<context>
- Proyek: ${p.projectName} (${p.slug})
- Klien: ${p.clientName}
- Klasifikasi: ${p.appType === 'webapp' ? 'Full Web Application' : 'Landing Page / CMS'}
- Studi Kasus & Kebutuhan Klien: ${this.caseStudy.rawBrief}
- Target Pengguna (Roles): ${rolesList}
- Matriks Modul & Fitur Terpilih:
${modulesSummary}
</context>

<system_invariants>
1. [INV-01] LIVING_SANDBOX: Komponen visual Svelte 5 wajib memiliki state reaktif in-memory ($state) yang bisa diklik dan diuji interaksinya secara visual oleh klien & developer.
2. [INV-02] ZERO_BACKEND_MUTATION: Dilarang memicu query HTTP atau mutasi database pada tahap ini. Seluruh simulasi berjalan 100% di memori browser.
3. [INV-03] MERMAID_ERD: Relasi skema data wajib dimodelkan dengan diagram Mermaid ERD yang valid, memuat entitas, atribut PK, FK, UK, dan relasi antar tabel yang sesuai dengan kolom-kolom modul.
4. [INV-04] CONTRACT_EXACTNESS: Tampilan UI mockup (KPI box, formulir dinamis, stream table) dan skema ERD yang dihasilkan pada tahap ini adalah KONTRAK VISUAL & STRUKTURAL PRESISI yang akan diwujudkan 1:1 saat Prompt Final dijalankan.
</system_invariants>

<task>
Rancang pratinjau visual interaktif dan relasi data untuk divalidasi bersama klien (Tahap A):
1. **Interactive UI Mockup (Svelte 5 Runes):** Bangun prototipe tampilan living mockup canvas yang mencakup:
   - Metric / KPI summary cards dengan nilai reaktif sesuai tujuan modul.
   - Interactive tactile form simulator untuk entri data in-memory dengan field sesuai kolom tabel ERD.
   - Real-time data stream table dengan status pills interaktif dan kolom sesuai skema database.
2. **Visual ERD (Mermaid):** Rancang diagram relasi entitas Mermaid komprehensif yang memetakan USERS, USER_DEVICES, AUDIT_LOGS, dan seluruh tabel entitas modul.
</task>

<output_contract>
Sajikan hasil dalam 2 blok terpisah:
1. Diagram Mermaid ERD (\`\`\`mermaid ... \`\`\`).
2. Komponen Svelte 5 Runes untuk Living Mockup Canvas yang siap dirender di Live Workspace.
</output_contract>`;
  }

  compilePromptStageB() {
    const p = this.project;
    const firstMod = this.modules[0] || { code: '01', name: 'Core Feature', slug: p.slug };
    const specPath = `doc/features/${firstMod.code}_${firstMod.slug || p.slug}.md`;
    const targetRole = this.roles.find((r) => r.id === firstMod.roleId)?.name || 'Administrator';

    return `<role>
Principal Fullstack Enterprise Architect & Lead Senior Engineer Laravel 13 & Svelte 5.
Kamu mematuhi standar PER Coding Style 2.0, PSR-12, OWASP ASVS Top 10, WCAG 2.2 AAA, dan RFC 7807.
Kamu bersikap defensif, menolak penulisan kode separuh jalan, dan selalu menghasilkan kode 100% utuh tanpa placeholder.
</role>

<context>
- Workspace Proyek: ${p.projectName} (${p.slug})
- Single Source of Truth (SSoT): ${specPath}
- Status Desain: DESIGN FREEZE APPROVED (Telah divalidasi pada tahap Living Mockup Canvas & Visual ERD)
- KONTRAK PRESISI: Seluruh antarmuka UI/UX dan struktur database yang kamu bangun WAJIB 1:1 PERSIS dengan tampilan Interactive Mockup Canvas dan Skema ERD yang telah disepakati pada Tahap A.
- Backend Stack: Laravel 13, PHP 8.3+, SQLite WAL Mode (PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL;)
- Frontend Stack: 100% Pure JavaScript (ES2024+), Svelte 5 Runes SAJA ($state, $derived, $props), Tailwind CSS v4
- Auth & Session: Laravel Sanctum Session-Based (SESSION_DRIVER=file mandatory, zero database connection for sessions)
- Error Handling: RFC 7807 (Problem Details for HTTP APIs)
- Safe Query Ceilings: Setiap kueri dibatasi maksimal limit(1000)
</context>

<system_invariants>
1. [INV-01] NO_PLACEHOLDER: Kode WAJIB 100% utuh, tuntas, lulus pengujian, siap produksi. DILARANG '// TODO', '/* rest of code */', atau fungsi stub kosong.
2. [INV-02] SINGLE_FEATURE_TURN: Eksekusi kode HANYA 1 Fitur untuk 1 Role per turn kerja (${firstMod.name} untuk ${targetRole}).
3. [INV-03] FULL_STATE_UI: Setiap antarmuka data WAJIB menangani 4 visual state: Loading, Empty, Error, dan Success.
4. [INV-04] DEFENSIVE_MUTATION: Mutasi multi-tabel WAJIB dibungkus DB::transaction(fn() => ...).
5. [INV-05] PURE_JS_SVELTE_5: Dilarang berkas .ts atau TypeScript; gunakan Svelte 5 Runes murni.
6. [INV-06] POLICY_GUARD: Otorisasi Policy wajib diterapkan pada setiap resource untuk mencegah BOLA (Broken Object Level Authorization).
7. [INV-07] UI_ERD_FIDELITY: Tampilan UI (tabel, form, KPI box) dan skema tabel/kolom database WAJIB persis 1:1 mencerminkan desain yang disetujui di Living Mockup Canvas dan Visual ERD Explorer.
</system_invariants>

<task>
Lakukan implementasi 100% kode produksi untuk Modul [${firstMod.code}]: ${firstMod.name} untuk Role ${targetRole} sesuai spesifikasi resmi pada ${specPath} dan pratinjau canvas yang telah disetujui.
</task>

<output_contract>
Sajikan kode utuh 100% per berkas dengan path eksplisit:
1. Migration database lengkap dengan B-Tree composite index dan foreign key constraint sesuai skema ERD.
2. Eloquent Model lengkap dengan $fillable, casts, dan relationships.
3. Form Request untuk validasi input ketat terhadap seluruh kolom tabel.
4. Laravel Policy otorisasi pencegah BOLA.
5. Thin Controller / Action Class dengan transaksi atomik DB::transaction.
6. Feature Test PHPUnit dengan minimal 3 skenario: 200/201 Happy Path, 422 Validation Error, 403 Forbidden.
7. Svelte 5 Runes View dengan 4 visual states (Loading, Empty, Error, Success) yang identik dengan Living Mockup Canvas.
</output_contract>

<verification_protocol>
Sebelum menyatakan tugas selesai:
1. Jalankan php artisan test dan pastikan seluruh test 100% pass.
2. Jalankan npm run build dan pastikan 0 error / 0 a11y warning.
3. Sajikan Review Gate Form resmi:
   "Implementasi Modul [${firstMod.code}] ${firstMod.name} untuk Role ${targetRole} selesai. Seluruh test lulus 100% dan UI menangani 4 visual state persis dengan mockup canvas yang disetujui. Apakah ada perbaikan/revisi spesifik sebelum melangkah ke fitur berikutnya?"
</verification_protocol>`;
  }
}

export const scopingState = new ScopingManager();
