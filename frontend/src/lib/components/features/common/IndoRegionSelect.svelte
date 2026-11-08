<script>
  import { 
    getProvinces, 
    getRegencies, 
    getDistricts, 
    getVillages, 
    findProvinceByName, 
    findRegencyByName, 
    findDistrictByName,
    toTitleCase 
  } from '$lib/services/wilayah.js';

  let {
    provinsi = $bindable(''),
    kabupaten = $bindable(''),
    kecamatan = $bindable(''),
    desa = $bindable(''),
    disabled = false,
    prefixId = 'wilayah',
    provinsiLabel = 'Provinsi',
    kabupatenLabel = 'Kabupaten / Kota',
    kecamatanLabel = 'Kecamatan',
    desaLabel = 'Desa / Kelurahan',
    showDesa = true,
    required = false,
    useTitleCase = false
  } = $props();

  const provinces = getProvinces();

  let regencies = $state([]);
  let districts = $state([]);
  let villages = $state([]);
  let loadingDistricts = $state(false);
  let loadingVillages = $state(false);

  // Sync regencies whenever provinsi changes
  $effect(() => {
    if (!provinsi) {
      regencies = [];
      return;
    }
    const foundProv = findProvinceByName(provinsi);
    if (foundProv) {
      regencies = getRegencies(foundProv.id);
    } else {
      regencies = [];
    }
  });

  // Sync districts whenever kabupaten changes
  $effect(() => {
    if (!kabupaten) {
      districts = [];
      return;
    }
    loadDistricts(kabupaten, provinsi);
  });

  async function loadDistricts(kabName, provName) {
    loadingDistricts = true;
    try {
      const foundReg = findRegencyByName(kabName, provName);
      if (foundReg) {
        districts = await getDistricts(foundReg.id, provName);
      } else {
        districts = [];
      }
    } finally {
      loadingDistricts = false;
    }
  }

  // Sync villages whenever kecamatan changes
  $effect(() => {
    if (!showDesa || !kecamatan) {
      villages = [];
      return;
    }
    loadVillages(kecamatan, kabupaten, provinsi);
  });

  async function loadVillages(kecName, kabName, provName) {
    loadingVillages = true;
    try {
      villages = await getVillages(kecName, kabName, provName);
    } finally {
      loadingVillages = false;
    }
  }

  // Value normalization for robust matching with existing database records
  let selectedProvVal = $derived.by(() => {
    if (!provinsi) return '';
    const match = provinces.find(p => p.name.toLowerCase() === provinsi.toLowerCase() || p.id === provinsi);
    if (match) return useTitleCase ? toTitleCase(match.name) : match.name;
    return provinsi;
  });

  let selectedKabVal = $derived.by(() => {
    if (!kabupaten) return '';
    const match = findRegencyByName(kabupaten, provinsi);
    if (match) return useTitleCase ? toTitleCase(match.name) : match.name;
    return kabupaten;
  });

  let selectedKecVal = $derived.by(() => {
    if (!kecamatan) return '';
    const clean = kecamatan.trim().toLowerCase();
    const match = districts.find(d => d.name.toLowerCase() === clean || (d.nama_bps && d.nama_bps.toLowerCase() === clean));
    if (match) return useTitleCase ? toTitleCase(match.name) : match.name;
    return kecamatan;
  });

  let selectedDesaVal = $derived.by(() => {
    if (!desa) return '';
    const clean = desa.trim().toLowerCase();
    const match = villages.find(v => v.name.toLowerCase() === clean || (v.nama_bps && v.nama_bps.toLowerCase() === clean));
    if (match) return useTitleCase ? toTitleCase(match.name) : match.name;
    return desa;
  });

  function handleProvinsiChange(e) {
    const val = e.target.value;
    provinsi = val;
    kabupaten = '';
    kecamatan = '';
    desa = '';
  }

  function handleKabupatenChange(e) {
    const val = e.target.value;
    kabupaten = val;
    kecamatan = '';
    desa = '';
  }

  function handleKecamatanChange(e) {
    const val = e.target.value;
    kecamatan = val;
    desa = '';
  }

  function handleDesaChange(e) {
    const val = e.target.value;
    desa = val;
  }
</script>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
  <!-- 1. Provinsi Select -->
  <div class="space-y-1">
    <label for="{prefixId}-provinsi" class="block text-xs font-bold text-stone-800 dark:text-stone-200">
      {provinsiLabel} {#if required}<span class="text-rose-500">*</span>{/if}
    </label>
    <select
      id="{prefixId}-provinsi"
      value={selectedProvVal}
      onchange={handleProvinsiChange}
      {disabled}
      class="w-full min-h-[40px] px-3 py-2 rounded-xl text-xs border border-stone-300 dark:border-stone-700 bg-white dark:bg-[#072419] text-stone-900 dark:text-white disabled:opacity-60 focus:outline-none focus:border-emerald-600 transition font-semibold"
    >
      <option value="">-- Pilih Provinsi --</option>
      {#if provinsi && !provinces.some(p => p.name.toLowerCase() === provinsi.toLowerCase())}
        <option value={provinsi}>{provinsi} (Tersimpan)</option>
      {/if}
      {#each provinces as p (p.id)}
        <option value={useTitleCase ? toTitleCase(p.name) : p.name}>
          {toTitleCase(p.name)}
        </option>
      {/each}
    </select>
  </div>

  <!-- 2. Kabupaten / Kota Select -->
  <div class="space-y-1">
    <label for="{prefixId}-kabupaten" class="block text-xs font-bold text-stone-800 dark:text-stone-200">
      {kabupatenLabel} {#if required}<span class="text-rose-500">*</span>{/if}
    </label>
    <select
      id="{prefixId}-kabupaten"
      value={selectedKabVal}
      onchange={handleKabupatenChange}
      disabled={disabled || !provinsi}
      class="w-full min-h-[40px] px-3 py-2 rounded-xl text-xs border border-stone-300 dark:border-stone-700 bg-white dark:bg-[#072419] text-stone-900 dark:text-white disabled:opacity-60 focus:outline-none focus:border-emerald-600 transition font-semibold"
    >
      <option value="">-- Pilih Kabupaten / Kota --</option>
      {#if kabupaten && !regencies.some(r => r.name.toLowerCase() === kabupaten.toLowerCase())}
        <option value={kabupaten}>{kabupaten} (Tersimpan)</option>
      {/if}
      {#each regencies as r (r.id)}
        <option value={useTitleCase ? toTitleCase(r.name) : r.name}>
          {toTitleCase(r.name)}
        </option>
      {/each}
    </select>
  </div>

  <!-- 3. Kecamatan Select -->
  <div class="space-y-1">
    <label for="{prefixId}-kecamatan" class="flex items-center justify-between text-xs font-bold text-stone-800 dark:text-stone-200">
      <span>{kecamatanLabel} {#if required}<span class="text-rose-500">*</span>{/if}</span>
      {#if loadingDistricts}
        <span class="text-[10px] font-normal text-emerald-600 dark:text-emerald-400 animate-pulse">Memuat...</span>
      {/if}
    </label>
    <select
      id="{prefixId}-kecamatan"
      value={selectedKecVal}
      onchange={handleKecamatanChange}
      disabled={disabled || !kabupaten || loadingDistricts}
      class="w-full min-h-[40px] px-3 py-2 rounded-xl text-xs border border-stone-300 dark:border-stone-700 bg-white dark:bg-[#072419] text-stone-900 dark:text-white disabled:opacity-60 focus:outline-none focus:border-emerald-600 transition font-semibold"
    >
      <option value="">-- Pilih Kecamatan --</option>
      {#if kecamatan && !districts.some(d => d.name.toLowerCase() === kecamatan.toLowerCase())}
        <option value={kecamatan}>{kecamatan} (Tersimpan)</option>
      {/if}
      {#each districts as d (d.id)}
        <option value={useTitleCase ? toTitleCase(d.name) : d.name}>
          {toTitleCase(d.name)}
        </option>
      {/each}
    </select>
  </div>

  <!-- 4. Desa / Kelurahan Select (100% Pure Select Element) -->
  {#if showDesa}
    <div class="space-y-1">
      <label for="{prefixId}-desa" class="flex items-center justify-between text-xs font-bold text-stone-800 dark:text-stone-200">
        <span>{desaLabel} {#if required}<span class="text-rose-500">*</span>{/if}</span>
        {#if loadingVillages}
          <span class="text-[10px] font-normal text-emerald-600 dark:text-emerald-400 animate-pulse">Memuat desa...</span>
        {/if}
      </label>
      <select
        id="{prefixId}-desa"
        value={selectedDesaVal}
        onchange={handleDesaChange}
        disabled={disabled || !kecamatan || loadingVillages}
        class="w-full min-h-[40px] px-3 py-2 rounded-xl text-xs border border-stone-300 dark:border-stone-700 bg-white dark:bg-[#072419] text-stone-900 dark:text-white disabled:opacity-60 focus:outline-none focus:border-emerald-600 transition font-semibold"
      >
        <option value="">{loadingVillages ? '-- Sedang Memuat Desa... --' : '-- Pilih Desa / Kelurahan --'}</option>
        {#if desa && !villages.some(v => v.name.toLowerCase() === desa.toLowerCase())}
          <option value={desa}>{desa} (Tersimpan)</option>
        {/if}
        {#each villages as v (v.id)}
          <option value={useTitleCase ? toTitleCase(v.name) : v.name}>
            {toTitleCase(v.name)}
          </option>
        {/each}
      </select>
    </div>
  {/if}
</div>
