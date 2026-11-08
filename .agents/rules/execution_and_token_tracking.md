# Execution Autonomy & Token Tracking Policy

## 1. Autonomous Execution Policy (Skip Permissions)
- Eksekusi perintah terminal, manipulasi berkas, dan aksi alat (tools) secara proaktif dan otonom dalam menyelesaikan unit kerja yang diminta (1 Fitur untuk 1 Role / Single Feature Turn).
- Jangan meminta konfirmasi atau izin manual untuk tugas-tugas pengembangan standar, pembacaan berkas, pembaruan kode, pembuatan skrip, migrasi, atau pengujian lokal. Selesaikan seluruh rantai instruksi secara mandiri hingga tuntas (Feature Test & Build lulus 100%).
- Setelah seluruh langkah unit kerja tuntas, sajikan ringkasan perubahan melalui formulir Review Gate resmi dan tunggu persetujuan pengguna sebelum melangkah ke fitur baru.

## 2. Token & Resource Consumption Reporting
- Di setiap akhir rentetan aksi / eksekusi alat atau di bagian penutup respons, AI WAJIB menyertakan metrik konsumsi token dan rincian langkah eksekusi dalam format ringkas dan terstruktur:

---
📊 **Metrik Eksekusi & Konsumsi Token**:
- **Model**: [Nama Model Aktif, contoh: Gemini 3.8 Flash (High)]
- **Rentetan Aksi / Tool Calls**: [Jumlah tool calls yang dieksekusi pada rentetan ini]
- **Estimasi Input Tokens**: ~[Perkiraan token konteks]
- **Estimasi Output & Thinking Tokens**: ~[Perkiraan token output]
- **Total Estimasi Token Rentetan Ini**: ~[Total perkiraan token]
---
