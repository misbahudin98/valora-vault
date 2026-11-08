import fs from 'node:fs';
import path from 'node:path';

async function fetchWithRetry(url, retries = 3) {
  for (let i = 0; i < retries; i++) {
    try {
      const res = await fetch(url, {
        headers: {
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Yayasan-PQTM/1.0',
          'Accept': 'application/json'
        }
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      if (i === retries - 1) throw err;
      await new Promise(r => setTimeout(r, 600));
    }
  }
}

async function pMap(array, fn, concurrency = 6) {
  const results = [];
  let index = 0;
  async function worker() {
    while (index < array.length) {
      const i = index++;
      results[i] = await fn(array[i], i);
    }
  }
  const workers = Array.from({ length: Math.min(concurrency, array.length) }, () => worker());
  await Promise.all(workers);
  return results;
}

async function main() {
  console.log('=== Step 1: Loading / Fetching Provinces ===');
  const provRaw = await fetchWithRetry('https://sig.bps.go.id/rest-bridging-dagri/getwilayah?level=provinsi&parent=0');
  const provinces = provRaw.map(p => ({
    id: p.kode_dagri,
    name: p.nama_dagri.toUpperCase(),
    kode_bps: p.kode_bps,
    kode_dagri: p.kode_dagri,
    nama_bps: p.nama_bps.toUpperCase(),
    nama_dagri: p.nama_dagri.toUpperCase()
  }));
  console.log(`Provinces: ${provinces.length}`);

  console.log('=== Step 2: Fetching Regencies ===');
  const allRegencies = [];
  await pMap(provinces, async (prov) => {
    try {
      const regRaw = await fetchWithRetry(`https://sig.bps.go.id/rest-bridging-dagri/getwilayah?level=kabupaten&parent=${prov.kode_dagri}`);
      for (const r of regRaw) {
        allRegencies.push({
          id: r.kode_dagri.replace('.', ''),
          regency_id: r.kode_dagri.replace('.', ''),
          province_id: prov.kode_dagri,
          name: r.nama_dagri.toUpperCase(),
          kode_bps: r.kode_bps,
          kode_dagri: r.kode_dagri,
          nama_bps: r.nama_bps.toUpperCase(),
          nama_dagri: r.nama_dagri.toUpperCase()
        });
      }
    } catch (e) {
      console.error(`Error regency for ${prov.name}:`, e.message);
    }
  }, 5);
  console.log(`Total Regencies: ${allRegencies.length}`);

  console.log('=== Step 3: Fetching Districts (Kecamatan) for all 514 Regencies ===');
  const allDistricts = [];
  let completed = 0;
  await pMap(allRegencies, async (reg) => {
    try {
      const distRaw = await fetchWithRetry(`https://sig.bps.go.id/rest-bridging-dagri/getwilayah?level=kecamatan&parent=${reg.kode_dagri}`);
      for (const d of distRaw) {
        allDistricts.push({
          id: d.kode_dagri.replace(/\./g, ''),
          district_id: d.kode_dagri.replace(/\./g, ''),
          regency_id: reg.id,
          province_id: reg.province_id,
          name: d.nama_dagri.toUpperCase(),
          kode_bps: d.kode_bps,
          kode_dagri: d.kode_dagri,
          nama_bps: d.nama_bps.toUpperCase(),
          nama_dagri: d.nama_dagri.toUpperCase()
        });
      }
    } catch (e) {
      console.error(`Error district for ${reg.name}:`, e.message);
    }
    completed++;
    if (completed % 50 === 0 || completed === allRegencies.length) {
      console.log(`Progress: ${completed}/${allRegencies.length} regencies processed (${allDistricts.length} districts)...`);
    }
  }, 8);
  console.log(`Total Districts: ${allDistricts.length}`);

  console.log('=== Step 4: Fetching Priority Villages (Jawa Barat: 32) ===');
  // Jawa Barat is primary domicile for Yayasan PQTM
  const jabarDistricts = allDistricts.filter(d => d.province_id === '32');
  console.log(`Fetching villages for ${jabarDistricts.length} districts in Jawa Barat...`);
  const allVillages = [];
  let vCompleted = 0;
  await pMap(jabarDistricts, async (dist) => {
    try {
      const vRaw = await fetchWithRetry(`https://sig.bps.go.id/rest-bridging-dagri/getwilayah?level=desa&parent=${dist.kode_dagri}`);
      for (const v of vRaw) {
        allVillages.push({
          id: v.kode_dagri.replace(/\./g, ''),
          village_id: v.kode_dagri.replace(/\./g, ''),
          district_id: dist.id,
          name: v.nama_dagri.toUpperCase(),
          kode_bps: v.kode_bps,
          kode_dagri: v.kode_dagri,
          nama_bps: v.nama_bps.toUpperCase(),
          nama_dagri: v.nama_dagri.toUpperCase()
        });
      }
    } catch (e) {
      // silent
    }
    vCompleted++;
    if (vCompleted % 100 === 0 || vCompleted === jabarDistricts.length) {
      console.log(`Village progress: ${vCompleted}/${jabarDistricts.length} (${allVillages.length} villages)...`);
    }
  }, 10);
  console.log(`Total Jawa Barat Villages: ${allVillages.length}`);

  // Create output directories
  const staticDir = path.resolve('frontend/static/data/wilayah');
  const libDir = path.resolve('frontend/src/lib/data');
  const starterStaticDir = path.resolve('D:/laragon/www/hyperion-vault/frontend/static/data/wilayah');
  const starterLibDir = path.resolve('D:/laragon/www/hyperion-vault/frontend/src/lib/data');

  fs.mkdirSync(staticDir, { recursive: true });
  fs.mkdirSync(libDir, { recursive: true });
  if (fs.existsSync('D:/laragon/www/hyperion-vault')) {
    fs.mkdirSync(starterStaticDir, { recursive: true });
    fs.mkdirSync(starterLibDir, { recursive: true });
  }

  // Save metadata
  const metadata = {
    source: "Badan Pusat Statistik (BPS) Republik Indonesia & Kementerian Dalam Negeri",
    url: "https://sig.bps.go.id/bridging-kode/index",
    downloaded_at: new Date().toISOString(),
    summary: {
      provinces: provinces.length,
      regencies: allRegencies.length,
      districts: allDistricts.length,
      villages: allVillages.length
    }
  };

  const provStr = JSON.stringify(provinces, null, 2);
  const regStr = JSON.stringify(allRegencies, null, 2);
  const distStr = JSON.stringify(allDistricts, null, 2);
  const vilStr = JSON.stringify(allVillages, null, 2);
  const metaStr = JSON.stringify(metadata, null, 2);

  fs.writeFileSync(path.join(staticDir, 'provinces.json'), provStr);
  fs.writeFileSync(path.join(staticDir, 'regencies.json'), regStr);
  fs.writeFileSync(path.join(staticDir, 'districts.json'), distStr);
  fs.writeFileSync(path.join(staticDir, 'villages.json'), vilStr);
  fs.writeFileSync(path.join(staticDir, 'metadata.json'), metaStr);

  // In-memory lightweight synchronous files
  fs.writeFileSync(path.join(libDir, 'provinces.json'), provStr);
  fs.writeFileSync(path.join(libDir, 'regencies.json'), regStr);

  // Mirror to starter repo if exists
  if (fs.existsSync('D:/laragon/www/hyperion-vault')) {
    fs.writeFileSync(path.join(starterStaticDir, 'provinces.json'), provStr);
    fs.writeFileSync(path.join(starterStaticDir, 'regencies.json'), regStr);
    fs.writeFileSync(path.join(starterStaticDir, 'districts.json'), distStr);
    fs.writeFileSync(path.join(starterStaticDir, 'villages.json'), vilStr);
    fs.writeFileSync(path.join(starterStaticDir, 'metadata.json'), metaStr);
    fs.writeFileSync(path.join(starterLibDir, 'provinces.json'), provStr);
    fs.writeFileSync(path.join(starterLibDir, 'regencies.json'), regStr);
  }

  console.log('=== All files successfully generated and mirrored! ===');
}

main().catch(console.error);
