/**
 * Wilayah Indonesia Service
 * Sumber Resmi: Keputusan Menteri Dalam Negeri (Kepmendagri) Nomor 300.2.2-2430 Tahun 2025 & BPS RI
 * Geoportal SIG Bridging Kode (https://sig.bps.go.id/bridging-kode/index)
 * 38 Provinsi, 514 Kabupaten/Kota, 7.265 Kecamatan, 83.345 Desa/Kelurahan
 */
import provincesData from '$lib/data/provinces.json';
import regenciesData from '$lib/data/regencies.json';

// In-memory caches for instant sub-second retrieval
let cachedDistricts = null;
const cachedProvinceVillages = new Map();
let cachedPriorityVillages = null;

export function toTitleCase(str) {
  if (!str) return '';
  return String(str)
    .toLowerCase()
    .split(' ')
    .map(word => {
      if (word.length === 0) return '';
      if (['dki', 'di', 'ri', 'ktp', 'rt', 'rw', 'nrp', 'nip'].includes(word)) return word.toUpperCase();
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(' ');
}

export function getProvinces() {
  return provincesData;
}

export function findProvinceByName(name) {
  if (!name) return null;
  const clean = String(name).trim().toLowerCase().replace(/^provinsi\s+/i, '');
  return provincesData.find(p => {
    const pName = p.name.toLowerCase().replace(/^provinsi\s+/i, '');
    return pName === clean || 
           (p.nama_bps && p.nama_bps.toLowerCase() === clean) ||
           (p.nama_dagri && p.nama_dagri.toLowerCase() === clean) ||
           p.id === clean ||
           p.kode_bps === clean ||
           p.kode_dagri === clean ||
           clean.includes(pName) ||
           pName.includes(clean);
  }) || null;
}

export function getRegencies(provinceIdOrName) {
  if (!provinceIdOrName) return regenciesData;
  let provId = String(provinceIdOrName).trim();
  const provObj = findProvinceByName(provId);
  if (provObj) {
    provId = provObj.id;
  }
  return regenciesData.filter(r => String(r.province_id) === provId);
}

function normalizeRegName(str) {
  return String(str || '')
    .trim()
    .toLowerCase()
    .replace(/^(kabupaten|kota|kab\.?)\s+/i, '')
    .trim();
}

export function findRegencyByName(regencyName, provinceIdOrName = null) {
  if (!regencyName) return null;
  const clean = normalizeRegName(regencyName);
  const raw = String(regencyName).trim().toLowerCase();
  const list = provinceIdOrName ? getRegencies(provinceIdOrName) : regenciesData;
  return list.find(r => {
    const norm = normalizeRegName(r.name);
    return norm === clean ||
           r.name.toLowerCase() === raw ||
           (r.nama_bps && normalizeRegName(r.nama_bps) === clean) ||
           (r.nama_dagri && normalizeRegName(r.nama_dagri) === clean) ||
           r.id === raw ||
           r.kode_bps === raw ||
           r.kode_dagri === raw;
  }) || null;
}

export async function getDistricts(regencyIdOrName, provinceIdOrName = null) {
  if (!cachedDistricts) {
    try {
      const res = await fetch('/data/wilayah/districts.json');
      if (res.ok) {
        cachedDistricts = await res.json();
      } else {
        cachedDistricts = [];
      }
    } catch (e) {
      console.warn('Gagal memuat districts.json:', e);
      cachedDistricts = [];
    }
  }

  if (!regencyIdOrName) return cachedDistricts;

  let regId = String(regencyIdOrName).trim();
  const regObj = findRegencyByName(regId, provinceIdOrName);
  if (regObj) {
    regId = regObj.id;
  }

  return cachedDistricts.filter(d => String(d.regency_id) === regId);
}

function normalizeDistName(str) {
  return String(str || '')
    .trim()
    .toLowerCase()
    .replace(/^kecamatan\s+/i, '')
    .trim();
}

export async function findDistrictByName(districtName, regencyIdOrName = null) {
  if (!districtName) return null;
  const clean = normalizeDistName(districtName);
  const raw = String(districtName).trim().toLowerCase();
  const list = await getDistricts(regencyIdOrName);
  return list.find(d => {
    const norm = normalizeDistName(d.name);
    return norm === clean ||
           d.name.toLowerCase() === raw ||
           (d.nama_bps && normalizeDistName(d.nama_bps) === clean) ||
           (d.nama_dagri && normalizeDistName(d.nama_dagri) === clean) ||
           d.id === raw ||
           d.kode_bps === raw ||
           d.kode_dagri === raw;
  }) || null;
}

/**
 * Fetch all villages for a district
 * Supports 100% of 83,345 Indonesian villages via province-partitioned JSON files
 */
export async function getVillages(districtIdOrName, regencyIdOrName = null, provinceIdOrName = null) {
  if (!districtIdOrName) return [];

  let distId = String(districtIdOrName).trim();
  let distObj = await findDistrictByName(distId, regencyIdOrName);
  if (distObj) {
    distId = distObj.id;
  }

  let provId = distObj ? distObj.province_id : null;
  if (!provId && provinceIdOrName) {
    const provObj = findProvinceByName(provinceIdOrName);
    if (provObj) provId = provObj.id;
  }
  if (!provId && distId.length >= 2) {
    provId = distId.slice(0, 2);
  }

  // 1. Check if province's villages already in memory cache
  if (provId && cachedProvinceVillages.has(provId)) {
    const list = cachedProvinceVillages.get(provId);
    return list.filter(v => String(v.district_id) === distId);
  }

  // 2. Fetch province partitioned file (/data/wilayah/villages/{provId}.json)
  if (provId) {
    try {
      const res = await fetch(`/data/wilayah/villages/${provId}.json`);
      if (res.ok) {
        const provVillages = await res.json();
        cachedProvinceVillages.set(provId, provVillages);
        return provVillages.filter(v => String(v.district_id) === distId);
      }
    } catch (e) {
      console.warn(`Gagal memuat partitioned villages/${provId}.json:`, e);
    }
  }

  // 3. Fallback to cachedPriorityVillages (/data/wilayah/villages.json)
  if (!cachedPriorityVillages) {
    try {
      const res = await fetch('/data/wilayah/villages.json');
      if (res.ok) {
        cachedPriorityVillages = await res.json();
      } else {
        cachedPriorityVillages = [];
      }
    } catch (e) {
      cachedPriorityVillages = [];
    }
  }

  return cachedPriorityVillages.filter(v => String(v.district_id) === distId);
}
