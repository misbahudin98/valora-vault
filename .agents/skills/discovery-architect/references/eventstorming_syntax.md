# Notasi Semantik EventStorming Ringkas (Alberto Brandolini)
> **Referensi:** Alberto Brandolini, *Introducing EventStorming: An Agile Modeling Method* (Leanpub, 2019).

---

## 1. BLOK PENYUSUN ARSITEKTUR EVENT-DRIVEN

EventStorming memecah kompleksitas domain menjadi 4 elemen utama yang diurutkan secara kronologis (dari kiri ke kanan):

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  🟦 COMMAND   │ ──> │ 🟨 AGGREGATE │ ──> │ 🟧 DOMAIN EV │ ──> │ 🟩 READ MODL │
│  (Aksi Aktor)│     │ (Model/Tabel)│     │ (Masa Lampau)│     │ (Tampilan UI)│
└──────────────┘     └──────────────┘     └──────────────┘     └──────────────┘
```

### A. 🟧 Domain Event (Oranye)
- **Definisi:** Sesuatu yang telah terjadi dalam domain bisnis dan tidak dapat diubah (fakta historis).
- **Format:** Kata kerja bentuk lampau (*Past Tense*).
- *Contoh:* `MutasiStokTelahDiterbitkan`, `HafalanSantriTelahDisetorkan`, `SesiSholatSelesaiDicatat`, `Sp1ApprovedByMudir`.

### B. 🟦 Command (Biru)
- **Definisi:** Keputusan atau instruksi yang dipicu oleh aktor pengguna atau sistem otomatis.
- **Format:** Kata kerja perintah imperatif (*Present Tense*).
- *Contoh:* `SubmitTransferOrder`, `SimpanSetoranHafalan`, `ApproveSp1`, `KunciNilaiSemester`.

### C. 🟨 Aggregate / Entity (Kuning)
- **Definisi:** Kluster objek bisnis dan tabel database yang bertugas menjaga aturan invarian agar konsisten secara transaksional (`DB::transaction`).
- *Contoh:* `MutasiStok`, `TahfidzJurnalSetoran`, `AsramaKedisiplinanSp`, `PkbmRaportCatatan`.

### D. 🟩 Read Model / View Proyeksi (Hijau)
- **Definisi:** Representasi data terstruktur yang dibutuhkan aktor pengguna untuk mengambil keputusan sebelum mengeksekusi Command berikutnya.
- *Contoh:* `TabelStokKritis`, `DashboardHafalanSantri`, `DaftarPersetujuanMudir`, `RaporSisipanSTS`.

---

## 2. CONTOH IMPLEMENTASI DOMAIN KEPESANTRENAN

```
[Wali Kamar] 
    │
    ▼ (🟦 SubmitPresensiSholat)
┌───────────────────────────────┐
│ 🟨 AsramaPresensiSholat (Agg) │
└───────────────────────────────┘
    │
    ▼
(🟧 PresensiSholatRecorded)
    │
    ├──> (Sistem hitung akumulasi alpa >= 6x)
    │        │
    │        ▼ (🟧 Sp1ThresholdReached)
    │            │
    │            ▼
    │        ┌───────────────────────────────┐
    │        │ 🟨 AsramaKedisiplinanSp (Agg) │
    │        └───────────────────────────────┘
    │            │
    │            ▼
    │        [Mudir] melihat (🟩 AntreanApprovalSp)
    │            │
    │            ▼ (🟦 ApproveSp1)
    │        (🟧 Sp1OfficiallyIssued)
```
