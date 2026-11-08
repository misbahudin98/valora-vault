# Trigger Tag Protocol: @prompt-architect, @prompt, & @pa
> **Standard:** Antigravity Skill Tag Trigger Protocol

---

## ATURAN REAKTIF TRIGGER TAG

Ketika pengguna menyertakan tag `@prompt`, `@pa`, atau `@prompt-architect` (atau tag pagar `#prompt`, `#pa`, `#audit-prompt`) pada instruksi:

1. **Wajib Mengaktifkan Skill `prompt-architect`**:
   - Agen WAJIB segera mengaktifkan dan mematuhi tata cara di `.agents/skills/prompt-architect/SKILL.md`.
2. **Pola Respons Deterministik**:
   - Jika pengguna menyertakan teks prompt untuk diperiksa:
     - Jalankan evaluasi 6 Dimensi Kualitas Internasional (Rubrik 0-100 poin).
     - Deteksi *Prompt Smells* (kata sifat subjektif, ketiadaan batasan negatif, bulk sprawl).
     - Sajikan tabel skor diagnostik.
     - Sajikan prompt versi rekayasa ulang (*Refactored Prompt*) berstandar internasional (XML-Delimited, 100/100 Tier 1).
   - Jika pengguna meminta pembuatan prompt baru:
     - Gunakan Arketipe XML Delimited dengan menginjeksi 7 Invariant SDD secara otomatis.
3. **Eksekusi Cepat**:
   - Dilarang memberikan jawaban bertele-tele; langsung sajikan tabel diagnostik dan prompt siap pakai.
