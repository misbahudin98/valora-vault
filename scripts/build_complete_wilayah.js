import fs from 'node:fs';
import path from 'node:path';

async function main() {
  console.log('=== Step 1: Downloading Complete Official Kepmendagri Dataset ===');
  const res = await fetch('https://raw.githubusercontent.com/cahyadsn/wilayah/master/db/wilayah.sql');
  if (!res.ok) throw new Error(`HTTP ${res.status} fetching wilayah.sql`);
  const sqlText = await res.text();
  console.log(`Downloaded wilayah.sql (${(sqlText.length / 1024 / 1024).toFixed(2)} MB)`);

  // Load existing BPS bridging data if present
  let bpsProvinces = [];
  let bpsRegencies = [];
  let bpsDistricts = [];
  try {
    if (fs.existsSync('frontend/static/data/wilayah/provinces.json')) {
      bpsProvinces = JSON.parse(fs.readFileSync('frontend/static/data/wilayah/provinces.json', 'utf8'));
    }
    if (fs.existsSync('frontend/static/data/wilayah/regencies.json')) {
      bpsRegencies = JSON.parse(fs.readFileSync('frontend/static/data/wilayah/regencies.json', 'utf8'));
    }
    if (fs.existsSync('frontend/static/data/wilayah/districts.json')) {
      bpsDistricts = JSON.parse(fs.readFileSync('frontend/static/data/wilayah/districts.json', 'utf8'));
    }
  } catch (e) {
    console.warn('Could not read existing BPS data:', e.message);
  }

  // Create lookups for BPS codes
  const bpsProvMap = new Map();
  for (const p of bpsProvinces) {
    if (p.kode_dagri) bpsProvMap.set(p.kode_dagri, p);
    if (p.id) bpsProvMap.set(p.id, p);
  }

  const bpsRegMap = new Map();
  for (const r of bpsRegencies) {
    if (r.kode_dagri) bpsRegMap.set(r.kode_dagri, r);
    if (r.id) bpsRegMap.set(r.id, r);
  }

  const bpsDistMap = new Map();
  for (const d of bpsDistricts) {
    if (d.kode_dagri) bpsDistMap.set(d.kode_dagri, d);
    if (d.id) bpsDistMap.set(d.id, d);
  }

  console.log('=== Step 2: Parsing Wilayah SQL Database ===');
  const regex = /\('([^']+)',\s*'([^']+)'\)/g;
  let match;

  const provinces = [];
  const regencies = [];
  const districts = [];
  const villagesByProvince = new Map();
  const allVillages = [];

  let count = 0;
  while ((match = regex.exec(sqlText)) !== null) {
    count++;
    const code = match[1];
    const rawName = match[2].trim();
    const cleanId = code.replace(/\./g, '');
    const parts = code.split('.');

    if (parts.length === 1) {
      // Provinsi
      const bps = bpsProvMap.get(code) || bpsProvMap.get(cleanId);
      provinces.push({
        id: code,
        name: rawName.toUpperCase(),
        kode_bps: bps ? bps.kode_bps : code,
        kode_dagri: code,
        nama_bps: bps ? bps.nama_bps : rawName.toUpperCase(),
        nama_dagri: rawName.toUpperCase()
      });
    } else if (parts.length === 2) {
      // Kabupaten / Kota
      const provId = parts[0];
      const bps = bpsRegMap.get(code) || bpsRegMap.get(cleanId);
      regencies.push({
        id: cleanId,
        regency_id: cleanId,
        province_id: provId,
        name: rawName.toUpperCase(),
        kode_bps: bps ? bps.kode_bps : cleanId,
        kode_dagri: code,
        nama_bps: bps ? bps.nama_bps : rawName.toUpperCase(),
        nama_dagri: rawName.toUpperCase()
      });
    } else if (parts.length === 3) {
      // Kecamatan
      const provId = parts[0];
      const regId = parts[0] + parts[1];
      const bps = bpsDistMap.get(code) || bpsDistMap.get(cleanId);
      districts.push({
        id: cleanId,
        district_id: cleanId,
        regency_id: regId,
        province_id: provId,
        name: rawName.toUpperCase(),
        kode_bps: bps ? bps.kode_bps : cleanId,
        kode_dagri: code,
        nama_bps: bps ? bps.nama_bps : rawName.toUpperCase(),
        nama_dagri: rawName.toUpperCase()
      });
    } else if (parts.length === 4) {
      // Desa / Kelurahan
      const provId = parts[0];
      const distId = parts[0] + parts[1] + parts[2];
      const villageItem = {
        id: cleanId,
        village_id: cleanId,
        district_id: distId,
        province_id: provId,
        name: rawName.toUpperCase(),
        kode_dagri: code
      };

      if (!villagesByProvince.has(provId)) {
        villagesByProvince.set(provId, []);
      }
      villagesByProvince.get(provId).push(villageItem);
      allVillages.push(villageItem);
    }
  }

  console.log(`Parsed ${count} records:`);
  console.log(`- Provinces: ${provinces.length}`);
  console.log(`- Regencies: ${regencies.length}`);
  console.log(`- Districts: ${districts.length}`);
  console.log(`- Villages: ${allVillages.length} (across ${villagesByProvince.size} provinces)`);

  console.log('=== Step 3: Writing JSON Files to static and lib directories ===');
  const staticDir = path.resolve('frontend/static/data/wilayah');
  const staticVillagesDir = path.join(staticDir, 'villages');
  const libDir = path.resolve('frontend/src/lib/data');

  const starterStaticDir = path.resolve('D:/laragon/www/valora-vault/frontend/static/data/wilayah');
  const starterStaticVillagesDir = path.join(starterStaticDir, 'villages');
  const starterLibDir = path.resolve('D:/laragon/www/valora-vault/frontend/src/lib/data');

  fs.mkdirSync(staticVillagesDir, { recursive: true });
  fs.mkdirSync(libDir, { recursive: true });
  if (fs.existsSync('D:/laragon/www/valora-vault')) {
    fs.mkdirSync(starterStaticVillagesDir, { recursive: true });
    fs.mkdirSync(starterLibDir, { recursive: true });
  }

  // Write partitioned villages per province
  for (const [provId, vList] of villagesByProvince.entries()) {
    const jsonStr = JSON.stringify(vList);
    fs.writeFileSync(path.join(staticVillagesDir, `${provId}.json`), jsonStr);
    if (fs.existsSync('D:/laragon/www/valora-vault')) {
      fs.writeFileSync(path.join(starterStaticVillagesDir, `${provId}.json`), jsonStr);
    }
  }
  console.log(`Saved ${villagesByProvince.size} partitioned village files in static/data/wilayah/villages/`);

  // Write priority villages (Jawa Barat: 32, DKI: 31, Banten: 36, Jawa Tengah: 33, Jawa Timur: 35) to villages.json
  // Combined top pesantren provinces for instant single-file lookup
  const priorityVillages = [
    ...(villagesByProvince.get('32') || []),
    ...(villagesByProvince.get('31') || []),
    ...(villagesByProvince.get('36') || [])
  ];
  fs.writeFileSync(path.join(staticDir, 'villages.json'), JSON.stringify(priorityVillages, null, 2));
  if (fs.existsSync('D:/laragon/www/valora-vault')) {
    fs.writeFileSync(path.join(starterStaticDir, 'villages.json'), JSON.stringify(priorityVillages, null, 2));
  }

  // Write complete master provinces, regencies, districts
  const provStr = JSON.stringify(provinces, null, 2);
  const regStr = JSON.stringify(regencies, null, 2);
  const distStr = JSON.stringify(districts, null, 2);

  fs.writeFileSync(path.join(staticDir, 'provinces.json'), provStr);
  fs.writeFileSync(path.join(staticDir, 'regencies.json'), regStr);
  fs.writeFileSync(path.join(staticDir, 'districts.json'), distStr);

  fs.writeFileSync(path.join(libDir, 'provinces.json'), provStr);
  fs.writeFileSync(path.join(libDir, 'regencies.json'), regStr);

  // Mirror to starter repo
  if (fs.existsSync('D:/laragon/www/valora-vault')) {
    fs.writeFileSync(path.join(starterStaticDir, 'provinces.json'), provStr);
    fs.writeFileSync(path.join(starterStaticDir, 'regencies.json'), regStr);
    fs.writeFileSync(path.join(starterStaticDir, 'districts.json'), distStr);
    fs.writeFileSync(path.join(starterLibDir, 'provinces.json'), provStr);
    fs.writeFileSync(path.join(starterLibDir, 'regencies.json'), regStr);
  }

  // Metadata
  const metadata = {
    source: "Badan Pusat Statistik (BPS) Republik Indonesia & Kementerian Dalam Negeri",
    url: "https://sig.bps.go.id/bridging-kode/index",
    standards: [
      "Keputusan Menteri Dalam Negeri Nomor 300.2.2-2430 Tahun 2025",
      "BPS Geoportal SIG Bridging Kode 2026"
    ],
    updated_at: new Date().toISOString(),
    summary: {
      provinces: provinces.length,
      regencies: regencies.length,
      districts: districts.length,
      villages_total: allVillages.length,
      villages_partitioned_provinces: villagesByProvince.size
    }
  };

  const metaStr = JSON.stringify(metadata, null, 2);
  fs.writeFileSync(path.join(staticDir, 'metadata.json'), metaStr);
  if (fs.existsSync('D:/laragon/www/valora-vault')) {
    fs.writeFileSync(path.join(starterStaticDir, 'metadata.json'), metaStr);
  }

  console.log('=== All files successfully generated and mirrored! ===');
  console.log(metadata.summary);
}

main().catch(console.error);
