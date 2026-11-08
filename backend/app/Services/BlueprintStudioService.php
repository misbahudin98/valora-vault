<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class BlueprintStudioService
{
    /**
     * List all feature specifications from the target features directory.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listModules(string $featuresDir): array
    {
        $modules = [];

        if (is_dir($featuresDir)) {
            $files = glob($featuresDir . DIRECTORY_SEPARATOR . '*.md');
            sort($files);

            foreach ($files as $filePath) {
                $filename = basename($filePath);
                $content = @file_get_contents($filePath) ?: '';

                preg_match('/^#\s+(.+)$/m', $content, $titleMatch);
                preg_match('/>\s*\*\*Modul:\*\*\s*(.+)$/m', $content, $moduleMatch);
                preg_match('/>\s*\*\*Aktor Utama:\*\*\s*(.+)$/m', $content, $actorMatch);
                preg_match('/>\s*\*\*Status:\*\*\s*(.+)$/m', $content, $statusMatch);

                $modules[] = [
                    'filename' => $filename,
                    'code' => explode('_', $filename)[0] ?? '00',
                    'title' => trim($titleMatch[1] ?? str_replace('.md', '', $filename)),
                    'module' => trim($moduleMatch[1] ?? 'General'),
                    'actor' => trim($actorMatch[1] ?? 'User'),
                    'status' => trim($statusMatch[1] ?? 'Draft'),
                    'size_bytes' => filesize($filePath),
                    'updated_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                ];
            }
        }

        return $modules;
    }

    /**
     * Save specification file and synchronize master catalog.
     *
     * @param array<string, mixed> $validated
     * @return array{message: string, file: string, updated_at: string}
     */
    public function saveSpec(array $validated, string $featuresDir, string $docPath): array
    {
        if (!is_dir($featuresDir)) {
            @mkdir($featuresDir, 0755, true);
        }

        $code = str_pad(preg_replace('/[^0-9]/', '', (string) $validated['code']), 2, '0', STR_PAD_LEFT);
        $filename = "{$code}_{$validated['slug']}.md";
        $targetFile = $featuresDir . DIRECTORY_SEPARATOR . $filename;

        // Path traversal defense
        $realTarget = realpath(dirname($targetFile));
        $realFeaturesDir = realpath($featuresDir);
        if ($realTarget !== false && $realFeaturesDir !== false && $realTarget !== $realFeaturesDir) {
            abort(403, 'Path traversal security error.');
        }

        $markdownContent = $validated['raw_markdown'] ?? $this->compileMarkdownSpec($validated);
        file_put_contents($targetFile, $markdownContent);

        $this->syncMasterCatalog($docPath, $filename, (string) $validated['title'], (string) $validated['actor']);

        return [
            'message' => "Spesifikasi fitur [{$filename}] berhasil disimpan secara deterministik.",
            'file' => "doc/features/{$filename}",
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Compile structured request payload into deterministic SDD Markdown format.
     *
     * @param array<string, mixed> $data
     */
    public function compileMarkdownSpec(array $data): string
    {
        $version = '6.31.0';
        $status = $data['status'] ?? 'Draft';
        $role = $data['user_story']['role'] ?? $data['actor'];
        $want = $data['user_story']['want'] ?? 'mengakses fungsionalitas modul ini';
        $soThat = $data['user_story']['so_that'] ?? 'tujuan operasional organisasi tercapai secara akuntabel';

        $md = "# [{$data['code']}]: {$data['title']} (v{$version})\n";
        $md .= "> **Modul:** {$data['module']}  \n";
        $md .= "> **Aktor Utama:** {$data['actor']}  \n";
        $md .= "> **Status:** {$status}  \n\n";
        $md .= "---\n\n";

        // 1. User Story
        $md .= "## 1. USER STORY\n";
        $md .= "- **Sebagai** {$role},  \n";
        $md .= "  **Saya ingin** {$want},  \n";
        $md .= "  **Sehingga** {$soThat}.\n\n";
        $md .= "---\n\n";

        // 2. MVP Scope & Boundaries
        $md .= "## 2. MVP SCOPE & BOUNDARIES\n";
        $md .= "- **In-Scope (Termasuk dalam Versi Ini):**\n";
        $md .= "  - Fungsionalitas inti pencatatan dan visualisasi interaktif.\n";
        $md .= "  - Validasi ketat sisi backend dan pembatasan kueri limit 1000.\n";
        $md .= "  - Notifikasi toast tactile dan tema universal (Daylight & Dark Obsidian).\n";
        $md .= "- **Out-of-Scope (Dilarang / Ditunda ke Versi Berikutnya):**\n";
        $md .= "  - Dilarang memicu mutasi database tanpa transaksi atomik.\n";
        $md .= "  - Dilarang kueri database tanpa batasan plafon memori.\n\n";
        $md .= "---\n\n";

        // 3. Acceptance Criteria
        $md .= "## 3. ACCEPTANCE CRITERIA (BDD GIVEN-WHEN-THEN)\n\n";
        if (!empty($data['acceptance_criteria'])) {
            foreach ($data['acceptance_criteria'] as $idx => $ac) {
                $n = $idx + 1;
                $title = $ac['title'] ?? "Skenario AC-{$n}";
                $given = $ac['given'] ?? 'Pengguna telah login dan memiliki wewenang sah';
                $when = $ac['when'] ?? 'Pengguna mengirimkan permintaan aksi';
                $then = $ac['then'] ?? 'Sistem memproses dengan status HTTP 200/201 dan mutasi UI reaktif';
                $md .= "### AC-{$n}: {$title}\n";
                $md .= "- **Given:** {$given}.\n";
                $md .= "- **When:** {$when}.\n";
                $md .= "- **Then:** {$then}.\n\n";
            }
        } else {
            $md .= "### AC-1: Happy Path Operasional\n";
            $md .= "- **Given:** Pengguna berwenang membuka antarmuka modul.\n";
            $md .= "- **When:** Pengguna mengisi formulir valid dan menekan tombol simpan.\n";
            $md .= "- **Then:** Data tersimpan atomik, notifikasi sukses hijau muncul, dan tabel reaktif diperbarui.\n\n";
        }
        $md .= "---\n\n";

        // 4. Database Schema & ERD
        $md .= "## 4. DATABASE SCHEMA & ERD\n\n";
        $md .= "```mermaid\nerDiagram\n";
        $entityName = strtoupper(Str::snake((string) $data['slug']));
        $md .= "    USERS ||--o{ {$entityName} : \"manages\"\n";
        $md .= "    {$entityName} {\n";
        $md .= "        bigint id PK\n";
        $md .= "        bigint user_id FK\n";
        $md .= "        string code UK\n";
        $md .= "        string title\n";
        $md .= "        string status\n";
        $md .= "        timestamp created_at\n";
        $md .= "    }\n```\n\n";
        $md .= "---\n\n";

        // 5. RESTful API Contract
        $md .= "## 5. RESTFUL API CONTRACT\n\n";
        $md .= "| Method | Endpoint URI | Middleware Guard | Deskripsi Fungsional |\n";
        $md .= "|---|---|---|---|\n";
        $slug = Str::slug(str_replace('_', '-', (string) $data['slug']));
        $md .= "| `GET` | `/api/v1/{$slug}` | `auth:sanctum, user.active` | Ambil daftar data (limit 1000) |\n";
        $md .= "| `POST` | `/api/v1/{$slug}` | `auth:sanctum, user.active` | Simpan data baru |\n";
        $md .= "| `PUT` | `/api/v1/{$slug}/{id}` | `auth:sanctum, user.active` | Perbarui data terdaftar |\n";
        $md .= "| `DELETE` | `/api/v1/{$slug}/{id}` | `auth:sanctum, user.active` | Hapus data (Soft/Hard) |\n\n";
        $md .= "---\n\n";

        // 6. Sequence Architecture Flow
        $md .= "## 6. SEQUENCE ARCHITECTURE FLOW\n\n";
        $md .= "```mermaid\nsequenceDiagram\n";
        $md .= "    autonumber\n";
        $md .= "    actor U as Pengguna (Browser)\n";
        $md .= "    participant FE as Svelte 5 Runes SPA\n";
        $md .= "    participant GW as Universal Gateway (index.php)\n";
        $md .= "    participant API as Controller (FastCGI)\n";
        $md .= "    participant DB as SQLite WAL / MySQL\n";
        $md .= "    U->>FE: Input & Submit Form\n";
        $md .= "    FE->>GW: POST /api/v1/{$slug}\n";
        $md .= "    GW->>API: Forward FastCGI\n";
        $md .= "    API->>API: Validasi Input & RBAC Guard\n";
        $md .= "    API->>DB: Atomic Insert Transaction (< 2ms)\n";
        $md .= "    DB-->>API: Saved\n";
        $md .= "    API-->>FE: HTTP 201 Created { message, data }\n";
        $md .= "    FE-->>U: Toast Sukses & Mutasi Array In-Memory\n";
        $md .= "```\n\n";

        return $md;
    }

    /**
     * Synchronize Master Catalog in doc/feature.md.
     */
    public function syncMasterCatalog(string $docPath, string $filename, string $title, string $actor): void
    {
        $featureMdPath = $docPath . DIRECTORY_SEPARATOR . 'feature.md';
        if (!file_exists($featureMdPath)) {
            return;
        }

        $content = file_get_contents($featureMdPath);
        if (str_contains($content, $filename)) {
            return;
        }

        $entry = "| [`features/{$filename}`](features/{$filename}) | **{$title}** | {$actor} | Ready |\n";
        $pos = strpos($content, '## 2. DAFTAR SPESIFIKASI FITUR TERSTRUKTUR');
        if ($pos !== false) {
            $tableEnd = strpos($content, "\n---", $pos);
            if ($tableEnd !== false) {
                $newContent = substr($content, 0, $tableEnd) . $entry . substr($content, $tableEnd);
                file_put_contents($featureMdPath, $newContent);
            }
        }
    }
}
