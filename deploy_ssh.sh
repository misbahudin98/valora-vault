#!/usr/bin/env bash
# =============================================================================
# HYPERION VAULT — Enterprise Production SSH Deployment Script
# Supports:
#   1. Hardened OWASP Architecture (Backend in ~/apps/... outside public web root)
#   2. Main Domain (~/public_html) or Subdomain (~/public_html/subdomain, ~/sub.domain.com)
#   3. Automated Symbolic Links (storage:link, persistent uploads)
#   4. High-Availability MySQL Provisioning, Pre-migration Snapshots & Rollback
#   5. Standard Architecture (Full project in single webroot / VPS Nginx)
#
# Usage on Server:
#   bash deploy_ssh.sh [--split | --standard] [--subdomain=sub] [--public-path=/path]
#
# Usage from Local Workstation:
#   php deploy_ssh.php
# =============================================================================

set -e # Exit immediately on error

echo "================================================================="
echo "   🛡️ HYPERION VAULT — ENTERPRISE SSH DEPLOYMENT ENGINE         "
echo "================================================================="

# Default Configuration
DEPLOY_MODE="${DEPLOY_MODE:-split}" # 'split' or 'standard'
APP_NAME="${APP_NAME:-hyperion-vault}"
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HOME_DIR="${HOME:-/home/$(whoami)}"

# Parse flags & options
for arg in "$@"; do
    case $arg in
        --split)
            DEPLOY_MODE="split"
            ;;
        --standard)
            DEPLOY_MODE="standard"
            ;;
        --subdomain=*)
            DEPLOY_SUBDOMAIN="${arg#*=}"
            ;;
        --public-path=*)
            CUSTOM_PUBLIC_PATH="${arg#*=}"
            ;;
        --backend-path=*)
            CUSTOM_BACKEND_PATH="${arg#*=}"
            ;;
        --db-conn=*)
            DEPLOY_DB_CONNECTION="${arg#*=}"
            ;;
        --db-host=*)
            DEPLOY_DB_HOST="${arg#*=}"
            ;;
        --db-port=*)
            DEPLOY_DB_PORT="${arg#*=}"
            ;;
        --db-name=*)
            DEPLOY_DB_DATABASE="${arg#*=}"
            ;;
        --db-user=*)
            DEPLOY_DB_USERNAME="${arg#*=}"
            ;;
        --db-pass=*)
            DEPLOY_DB_PASSWORD="${arg#*=}"
            ;;
        --test-mysql)
            TEST_MYSQL_ONLY="1"
            ;;
        --seed)
            RUN_SEED="1"
            ;;
        --help)
            echo "Penggunaan: bash deploy_ssh.sh [OPSI]"
            echo "  --split                 : Mode OWASP Hardened (backend di luar folder publik) [Default]"
            echo "  --standard              : Mode Webroot Standar (seluruh proyek di satu folder)"
            echo "  --subdomain=nama        : Konfigurasi target subdomain (misal: 'app', 'portal', 'api')"
            echo "  --public-path=/path/dir : Tentukan direktori publik secara eksplisit"
            echo "  --backend-path=/path/dir: Tentukan direktori backend secara eksplisit"
            echo "  --db-conn=mysql|sqlite  : Tentukan driver database produksi"
            echo "  --db-host=127.0.0.1     : Host database MySQL"
            echo "  --db-port=3306          : Port database MySQL"
            echo "  --db-name=nama_db       : Nama database MySQL"
            echo "  --db-user=nama_user     : Username database MySQL"
            echo "  --db-pass=rahasia       : Password database MySQL"
            echo "  --test-mysql            : Uji konektivitas MySQL saja tanpa menjalankan deployment penuh"
            echo "  --seed                  : Jalankan database seeders setelah migrasi berhasil"
            exit 0
            ;;
    esac
done

echo "Parameter Deployment:"
echo "  -> Mode Arsitektur : [${DEPLOY_MODE^^}]"
if [ -n "$DEPLOY_SUBDOMAIN" ]; then
    echo "  -> Target Subdomain: [${DEPLOY_SUBDOMAIN}]"
fi

# Pre-flight Standalone Test MySQL jika Mode --test-mysql Dipanggil
if [ "${TEST_MYSQL_ONLY}" == "1" ]; then
    echo ""
    echo "================================================================="
    echo "   🔍 UJI KONEKTIVITAS & OTORISASI MYSQL REMOTE (PRE-FLIGHT)     "
    echo "================================================================="
    echo "  -> Target Basis Data: ${DEPLOY_DB_USERNAME}@${DEPLOY_DB_HOST}:${DEPLOY_DB_PORT:-3306}/${DEPLOY_DB_DATABASE}"
    MYSQL_PING_STATUS=$(php -r "
    try {
        \$pdo = new PDO('mysql:host=${DEPLOY_DB_HOST};port=${DEPLOY_DB_PORT:-3306};charset=utf8mb4', '${DEPLOY_DB_USERNAME}', '${DEPLOY_DB_PASSWORD}', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo 'AUTH_SUCCESS: Basis data terkoneksi (' . \$pdo->query('SELECT VERSION()')->fetchColumn() . ')';
    } catch (PDOException \$e) {
        echo 'AUTH_FAILED: ' . \$e->getMessage();
    }
    ")
    if [[ "$MYSQL_PING_STATUS" != "AUTH_SUCCESS"* ]]; then
        echo "  [FATAL ERROR] Koneksi ke MySQL server gagal diautentikasi!"
        echo "  Detail: ${MYSQL_PING_STATUS}"
        exit 1
    fi
    echo "  ✓ ${MYSQL_PING_STATUS}"
    echo "================================================================="
    echo "   🎉 UJI KONEKTIVITAS & OTORISASI MYSQL BERHASIL 100%!         "
    echo "================================================================="
    exit 0
fi

# Deteksi dan ekstrak paket payload pembaruan lokal jika tersedia
PAYLOAD_TAR="${HOME_DIR}/deploy_payload.tar.gz"
DEPLOY_TMP="${HOME_DIR}/deploy_tmp"
if [ -f "$PAYLOAD_TAR" ]; then
    echo "  -> Menemukan paket pembaruan lokal: ${PAYLOAD_TAR}..."
    rm -rf "${DEPLOY_TMP}"
    mkdir -p "${DEPLOY_TMP}"
    tar -xzf "$PAYLOAD_TAR" -C "${DEPLOY_TMP}"
    echo "  ✓ Paket pembaruan berhasil diekstrak ke staging."
fi

# Determine Directories
if [ "$DEPLOY_MODE" == "split" ]; then
    # Backend Directory Setup
    if [ -n "$CUSTOM_BACKEND_PATH" ]; then
        BACKEND_DIR="${CUSTOM_BACKEND_PATH}"
        APPS_DIR="$(dirname "${BACKEND_DIR}")"
    else
        APPS_DIR="${HOME_DIR}/apps/${APP_NAME}"
        BACKEND_DIR="${APPS_DIR}/backend"
    fi
    FRONTEND_DIR="${APPS_DIR}/frontend"

    # Public Directory Setup (Main Domain vs Subdomain vs Multi-Domain Hostinger/cPanel)
    if [ -n "$CUSTOM_PUBLIC_PATH" ]; then
        PUBLIC_DIR="${CUSTOM_PUBLIC_PATH}"
    elif [ -n "$DEPLOY_SUBDOMAIN" ]; then
        # Cek jika cPanel membuat folder standalone atau di dalam public_html
        if [ -d "${HOME_DIR}/${DEPLOY_SUBDOMAIN}" ]; then
            PUBLIC_DIR="${HOME_DIR}/${DEPLOY_SUBDOMAIN}"
        else
            PUBLIC_DIR="${HOME_DIR}/public_html/${DEPLOY_SUBDOMAIN}"
        fi
    elif [ -d "${HOME_DIR}/domains" ]; then
        # Deteksi otomatis struktur Hostinger / DirectAdmin: /home/user/domains/domain.id/public_html
        if [ -n "$DEPLOY_APP_DOMAIN" ]; then
            DOMAIN_MATCH=$(echo "$DEPLOY_APP_DOMAIN" | sed -E 's|https?://||' | sed -E 's|/.*||')
            if [ -d "${HOME_DIR}/domains/${DOMAIN_MATCH}/public_html" ]; then
                PUBLIC_DIR="${HOME_DIR}/domains/${DOMAIN_MATCH}/public_html"
            fi
        fi
        if [ -z "$PUBLIC_DIR" ]; then
            HOSTINGER_PUB=$(find "${HOME_DIR}/domains" -maxdepth 2 -type d -name "public_html" 2>/dev/null | head -n 1)
            PUBLIC_DIR="${HOSTINGER_PUB:-${HOME_DIR}/public_html}"
        fi
    else
        PUBLIC_DIR="${HOME_DIR}/public_html"
    fi

    # Pastikan direktori backend dan public_html terisolasi secara fisik
    mkdir -p "${APPS_DIR}"
    mkdir -p "${BACKEND_DIR}"
    mkdir -p "${PUBLIC_DIR}"

    # Sinkronisasi berkas backend mutakhir dari staging payload atau sumber server
    if [ -d "${DEPLOY_TMP}/backend" ]; then
        echo "  -> Menyinkronkan berkas backend mutakhir ke direktori aman: ${BACKEND_DIR}..."
        cp -a "${DEPLOY_TMP}/backend/." "${BACKEND_DIR}/"
    elif [ -d "${BASE_DIR}/backend" ] && [ ! -f "${BACKEND_DIR}/artisan" ] && [ "${BASE_DIR}/backend" != "${BACKEND_DIR}" ]; then
        echo "  -> Menyiapkan berkas backend awal ke direktori aman di luar publik: ${BACKEND_DIR}..."
        cp -a "${BASE_DIR}/backend/." "${BACKEND_DIR}/"
        if [ -f "${BASE_DIR}/backend/.env" ]; then
            cp "${BASE_DIR}/backend/.env" "${BACKEND_DIR}/.env"
        fi
    fi

    # Amankan pustaka vendor dari instalasi publik lama jika belum ada di BACKEND_DIR
    if [ ! -d "${BACKEND_DIR}/vendor" ] && [ -d "${PUBLIC_DIR}/backend/vendor" ]; then
        echo "  -> Menyalin pustaka vendor dari lokasi publik lama..."
        cp -r "${PUBLIC_DIR}/backend/vendor" "${BACKEND_DIR}/vendor"
    fi

    echo "  -> Backend Path    : ${BACKEND_DIR} (DI LUAR PUBLIC WEB ROOT - OWASP HARDENED)"
    echo "  -> Public Web Root : ${PUBLIC_DIR} (DI DALAM PUBLIC - FRONTEND & GATEWAY ONLY)"
else
    BACKEND_DIR="${BASE_DIR}/backend"
    PUBLIC_DIR="${BASE_DIR}"
    FRONTEND_DIR="${BASE_DIR}/frontend"

    if [ -d "${DEPLOY_TMP}/backend" ]; then
        echo "  -> Menyinkronkan berkas backend mutakhir ke ${BACKEND_DIR}..."
        cp -r "${DEPLOY_TMP}/backend/"* "${BACKEND_DIR}/"
    fi

    echo "  -> Project Root    : ${BASE_DIR}"
    echo "  -> Backend Dir     : ${BACKEND_DIR}"
fi

# Step 1: Pre-flight Verification
echo ""
echo "[1/6] Memeriksa kelayakan lingkungan server (PHP, Composer, Git)..."
if ! command -v php >/dev/null 2>&1; then
    echo "  [ERROR] PHP CLI tidak ditemukan di PATH server!"
    exit 1
fi

PHP_VER=$(php -r "echo PHP_VERSION;")
echo "  ✓ Versi PHP Server: ${PHP_VER}"

if ! command -v composer >/dev/null 2>&1; then
    echo "  [ERROR] Composer tidak ditemukan di server!"
    exit 1
fi
echo "  ✓ Composer terdeteksi."

# Step 2: Git Repository Update
echo ""
echo "[2/6] Memperbarui kode sumber repositori..."
if [ -d "${BASE_DIR}/.git" ]; then
    echo "  -> Menjalankan git pull..."
    git -C "${BASE_DIR}" pull origin main || echo "  [INFO] Git pull dilewati atau working tree bersih."
else
    echo "  [INFO] Repositori .git tidak ditemukan di root, melanjutkan dengan berkas yang ada."
fi

# Step 3: Backend Dependencies & Environment Setup
echo ""
echo "[3/6] Memasang dependensi backend & optimasi vendor..."
cd "${BACKEND_DIR}"

if [ ! -f ".env" ]; then
    echo "  [INFO] Berkas .env belum ada. Menyiapkan berkas .env produksi..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
    elif [ -f "${DEPLOY_TMP}/backend/.env.example" ]; then
        cp "${DEPLOY_TMP}/backend/.env.example" .env
    elif [ -f "${PUBLIC_DIR}/backend/.env" ]; then
        cp "${PUBLIC_DIR}/backend/.env" .env
    else
        touch .env
    fi
    php artisan key:generate --ansi --force
fi

# Fungsi Injeksi/Pembaruan Parameter .env Secara Aman Menggunakan PHP
update_remote_env_param() {
    local env_key="$1"
    local env_val="$2"
    if [ -z "$env_val" ]; then return 0; fi
    php -r "
    \$file = '.env';
    \$content = file_exists(\$file) ? file_get_contents(\$file) : '';
    \$key = '${env_key}';
    \$val = '${env_val}';
    \$pattern = '/^' . preg_quote(\$key, '/') . '=.*/m';
    \$replacement = \$key . '=\"' . addcslashes(\$val, '\"\\\\') . '\"';
    if (preg_match(\$pattern, \$content)) {
        \$content = preg_replace(\$pattern, \$replacement, \$content);
    } else {
        \$content .= \"\n\" . \$replacement;
    }
    file_put_contents(\$file, \$content);
    "
}

# Sinkronisasi Parameter Database Produksi jika Diteruskan dari Deploy Runner
if [ -n "$DEPLOY_DB_CONNECTION" ]; then
    echo "  -> Menyelaraskan DB_CONNECTION=\"${DEPLOY_DB_CONNECTION}\" pada .env remote..."
    update_remote_env_param "DB_CONNECTION" "$DEPLOY_DB_CONNECTION"
fi
if [ -n "$DEPLOY_DB_HOST" ]; then
    update_remote_env_param "DB_HOST" "$DEPLOY_DB_HOST"
fi
if [ -n "$DEPLOY_DB_PORT" ]; then
    update_remote_env_param "DB_PORT" "$DEPLOY_DB_PORT"
fi
if [ -n "$DEPLOY_DB_DATABASE" ]; then
    update_remote_env_param "DB_DATABASE" "$DEPLOY_DB_DATABASE"
fi
if [ -n "$DEPLOY_DB_USERNAME" ]; then
    update_remote_env_param "DB_USERNAME" "$DEPLOY_DB_USERNAME"
fi
if [ -n "$DEPLOY_DB_PASSWORD" ]; then
    update_remote_env_param "DB_PASSWORD" "$DEPLOY_DB_PASSWORD"
fi
if [ -n "$DEPLOY_APP_DOMAIN" ]; then
    echo "  -> Menyelaraskan konfigurasi akses frontend (APP_URL, FRONTEND_URL, Sanctum, CORS, Session)..."
    update_remote_env_param "APP_URL" "$DEPLOY_APP_DOMAIN"
    update_remote_env_param "FRONTEND_URL" "$DEPLOY_APP_DOMAIN"
    CLEAN_DOMAIN=$(echo "$DEPLOY_APP_DOMAIN" | sed -E 's|https?://||' | sed -E 's|/.*||')
    update_remote_env_param "SANCTUM_STATEFUL_DOMAINS" "$CLEAN_DOMAIN"
    if [[ "$DEPLOY_APP_DOMAIN" == https://* ]]; then
        update_remote_env_param "SESSION_SECURE_COOKIE" "true"
    else
        update_remote_env_param "SESSION_SECURE_COOKIE" "false"
    fi
    update_remote_env_param "SESSION_SAME_SITE" "lax"
fi
update_remote_env_param "SESSION_DRIVER" "file"
update_remote_env_param "APP_ENV" "production"
update_remote_env_param "APP_DEBUG" "false"

echo "  -> Memeriksa dependensi Composer backend..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
echo "  ✓ Dependensi Composer backend berhasil dioptimalkan."

# Bersihkan dan siapkan cache bootstrap & storage framework
rm -f bootstrap/cache/*.php
mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/backups storage/app/public

# Step 4: Database Provisioning, Safety Backup & Migrations
echo ""
echo "[4/6] Menyiapkan basis data, snapshot cadangan & migrasi produksi..."

DB_CONN=$(grep -E '^DB_CONNECTION=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | tr -d '\r' || echo "mysql")
DB_NAME=$(grep -E '^DB_DATABASE=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | tr -d '\r' || echo "hyperion_vault")
DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | tr -d '\r' || echo "127.0.0.1")
DB_PORT=$(grep -E '^DB_PORT=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | tr -d '\r' || echo "3306")
DB_USER=$(grep -E '^DB_USERNAME=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | tr -d '\r' || echo "")
DB_PASS=$(grep -E '^DB_PASSWORD=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'" | tr -d '\r' || echo "")

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
mkdir -p storage/app/backups

if [ "$DB_CONN" == "mysql" ]; then
    echo "  -> Terdeteksi konfigurasi MySQL: [${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}]"

    # Verifikasi Konektivitas MySQL via PHP PDO (Mandiri & Zero Dependency CLI)
    echo "  -> Menguji konektivitas & autentikasi MySQL remote..."
    MYSQL_PING_STATUS=$(php -r "
    try {
        \$dsn = 'mysql:host=${DB_HOST};port=${DB_PORT};charset=utf8mb4';
        \$pdo = new PDO(\$dsn, '${DB_USER}', '${DB_PASS}', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo 'AUTH_SUCCESS';
    } catch (PDOException \$e) {
        echo 'AUTH_FAILED: ' . \$e->getMessage();
    }
    ")

    if [[ "$MYSQL_PING_STATUS" != "AUTH_SUCCESS"* ]]; then
        echo "  [FATAL ERROR] Koneksi ke MySQL server gagal diautentikasi!"
        echo "  Detail: ${MYSQL_PING_STATUS}"
        echo "  Pastikan kredensial DB_HOST, DB_PORT, DB_USERNAME, dan DB_PASSWORD pada server sudah sesuai."
        exit 1
    fi
    echo "  ✓ Koneksi MySQL berhasil diautentikasi."

    # Inisiasi Otomatis Skema Database jika Belum Ada (Idempotent)
    php -r "
    try {
        \$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USER}', '${DB_PASS}');
        \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        echo '  ✓ Skema database \`${DB_NAME}\` siap digunakan.\n';
    } catch (PDOException \$e) {
        echo '  [INFO] CREATE DATABASE dilewati (menggunakan database yang telah dialokasikan hosting cPanel/Plesk).\n';
    }
    "

    # Jika Mode --test-mysql Saja Dipanggil, Akhiri dengan Sukses
    if [ "${TEST_MYSQL_ONLY}" == "1" ]; then
        echo ""
        echo "================================================================="
        echo "   🎉 UJI KONEKTIVITAS & OTORISASI MYSQL BERHASIL 100%!         "
        echo "================================================================="
        exit 0
    fi

    # Pre-migration snapshot via mysqldump jika tersedia di server
    if command -v mysqldump >/dev/null 2>&1; then
        BACKUP_FILE="storage/app/backups/pre_deploy_${TIMESTAMP}.sql.gz"
        echo "  -> Mengamankan snapshot database MySQL pra-migrasi..."
        MYSQL_PWD="$DB_PASS" mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" --single-transaction --quick "$DB_NAME" 2>/dev/null | gzip > "$BACKUP_FILE" || rm -f "$BACKUP_FILE"
        if [ -f "$BACKUP_FILE" ] && [ -s "$BACKUP_FILE" ]; then
            echo "  ✓ Snapshot MySQL pra-migrasi berhasil dibuat ($(du -h "$BACKUP_FILE" | cut -f1))."
        fi
    fi
elif [ "$DB_CONN" == "sqlite" ]; then
    echo "  -> Terdeteksi koneksi basis data SQLite."
    SQLITE_FILE="database/database.sqlite"
    if [ ! -f "$SQLITE_FILE" ]; then
        touch "$SQLITE_FILE"
        chmod 664 "$SQLITE_FILE"
        echo "  ✓ Berkas basis data SQLite berhasil diinisiasi."
    else
        cp "$SQLITE_FILE" "storage/app/backups/database_pre_deploy_${TIMESTAMP}.sqlite"
        echo "  ✓ Snapshot SQLite pra-migrasi berhasil diamankan."
    fi
fi

# Jalankan migrasi non-destruktif dengan deteksi kegagalan
echo "  -> Menjalankan migrasi skema tabel (php artisan migrate --force)..."
if ! php artisan migrate --force --no-interaction; then
    echo "  [FATAL ERROR] Migrasi basis data gagal!"
    if [ -n "$BACKUP_FILE" ] && [ -f "$BACKUP_FILE" ]; then
        echo "  [RECOVERY INSTRUCTION] Pulihkan data segera dari snapshot cadangan:"
        echo "  zcat $BACKUP_FILE | mysql -h$DB_HOST -P$DB_PORT -u$DB_USER -p $DB_NAME"
    fi
    exit 1
fi
echo "  ✓ Seluruh migrasi basis data berhasil dieksekusi."

# Jalankan Seeders jika Flag --seed Diaktifkan
if [ "${RUN_SEED}" == "1" ]; then
    echo "  -> Menjalankan database seeders (php artisan db:seed --force)..."
    php artisan db:seed --force --no-interaction
    echo "  ✓ Database seeders berhasil dieksekusi."
fi

# Artisan caches
php artisan optimize:clear --quiet
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Session Pruning (Anti-Disk Bloat)
if php artisan list | grep -q "session:prune-files"; then
    php artisan session:prune-files --grace=60 || true
    echo "  ✓ File sesi kadaluarsa dibersihkan."
fi

echo "  ✓ Seluruh cache aplikasi, rute, dan konfigurasi aktif."

# Step 5: Frontend Build, Distribution & Symbolic Links
echo ""
echo "[5/6] Menata dokumen publik, aset frontend & tautan simbolik (symlink)..."
if [ "$DEPLOY_MODE" == "split" ]; then
    mkdir -p "${PUBLIC_DIR}"

    # Prioritaskan pemakaian aset build yang sudah dikompilasi secara lokal (Zero-Node Fast Path)
    if [ -d "${DEPLOY_TMP}/frontend/build" ]; then
        echo "  -> Menggunakan aset build frontend terkompilasi dari payload (Fast Zero-Node Path)..."
        cp -a "${DEPLOY_TMP}/frontend/build/." "${PUBLIC_DIR}/"
    elif [ -d "${FRONTEND_DIR}/build" ]; then
        echo "  -> Menyalin aset build frontend dari direktori lokal ke ${PUBLIC_DIR}..."
        cp -a "${FRONTEND_DIR}/build/." "${PUBLIC_DIR}/"
    elif [ -d "${BASE_DIR}/frontend/build" ]; then
        echo "  -> Menyalin aset build frontend lokal ke ${PUBLIC_DIR}..."
        cp -a "${BASE_DIR}/frontend/build/." "${PUBLIC_DIR}/"
    elif command -v npm >/dev/null 2>&1 && [ -f "${FRONTEND_DIR}/package.json" ]; then
        echo "  -> Menjalankan kompilasi frontend cadangan di server via npm run build..."
        cd "${FRONTEND_DIR}"
        npm install --prefer-offline --no-audit --quiet
        npm run build
        cd "${BACKEND_DIR}"
        if [ -d "${FRONTEND_DIR}/build" ]; then
            cp -a "${FRONTEND_DIR}/build/." "${PUBLIC_DIR}/"
        fi
    fi

    # Salin .htaccess jika ada di payload
    if [ -f "${DEPLOY_TMP}/.htaccess" ]; then
        cp "${DEPLOY_TMP}/.htaccess" "${PUBLIC_DIR}/.htaccess"
    fi

    # Setup Hardened Gateway index.php in public web root
    echo "  -> Menyiapkan gateway index.php di ${PUBLIC_DIR}..."
    cat <<GATEWAY > "${PUBLIC_DIR}/index.php"
<?php
declare(strict_types=1);

define('LARAVEL_START', microtime(true));

// Security & Speed HTTP Headers (OWASP & ISO 27001)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

\$backendPath = '${BACKEND_DIR}';
\$publicDir = __DIR__;
\$uri = urldecode(parse_url(\$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

// 1. API, Sanctum, and Health Check -> Forward to Laravel backend
if (str_starts_with(\$uri, '/api/') || str_starts_with(\$uri, '/sanctum/') || \$uri === '/up') {
    require \$backendPath . '/public/index.php';
    exit;
}

// 2. Storage files -> Direct static serving via symlink or backend fallback
if (str_starts_with(\$uri, '/storage/')) {
    \$storageFile = \$publicDir . \$uri;
    if (file_exists(\$storageFile) && !is_dir(\$storageFile)) {
        header('Content-Type: ' . (mime_content_type(\$storageFile) ?: 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile(\$storageFile);
        exit;
    }
    \$directStorage = \$backendPath . '/storage/app/public/' . substr(\$uri, 9);
    if (file_exists(\$directStorage) && !is_dir(\$directStorage)) {
        header('Content-Type: ' . (mime_content_type(\$directStorage) ?: 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile(\$directStorage);
        exit;
    }
    require \$backendPath . '/public/index.php';
    exit;
}

// 3. Static files in public webroot
\$staticFile = \$publicDir . \$uri;
if (\$uri !== '/' && file_exists(\$staticFile) && !is_dir(\$staticFile)) {
    \$ext = strtolower(pathinfo(\$staticFile, PATHINFO_EXTENSION));
    \$mimes = [
        'js' => 'application/javascript', 'css' => 'text/css', 'json' => 'application/json',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'woff2' => 'font/woff2', 'txt' => 'text/plain', 'html' => 'text/html; charset=UTF-8'
    ];
    \$mime = \$mimes[\$ext] ?? (function_exists('mime_content_type') ? @mime_content_type(\$staticFile) : 'application/octet-stream');
    header("Content-Type: {\$mime}");

    if (str_contains(str_replace('\\\\', '/', \$staticFile), '_app/immutable/')) {
        header('Cache-Control: public, max-age=31536000, immutable');
    } elseif (\$ext === 'html' || str_ends_with(\$staticFile, 'version.json')) {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    } else {
        header('Cache-Control: public, max-age=86400');
    }

    readfile(\$staticFile);
    exit;
}

// 4. Production SPA Route Fallback -> Serve index.html with strictly no-cache
\$indexHtml = \$publicDir . '/index.html';
if (file_exists(\$indexHtml)) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile(\$indexHtml);
    exit;
}

// 5. Backend Fallback if index.html is missing
require \$backendPath . '/public/index.php';
GATEWAY

    # Copy Root Security .htaccess to public web root
    if [ -f "${BASE_DIR}/.htaccess" ]; then
        cp "${BASE_DIR}/.htaccess" "${PUBLIC_DIR}/.htaccess"
    fi

    # 🔗 MEKANISME SYMBOLIC LINK (STORAGE & PUBLIC ASSETS)
    echo "  -> Menyelaraskan symbolic link untuk berkas penyimpanan..."
    mkdir -p "${BACKEND_DIR}/storage/app/public"
    
    # Storage symlink: ${PUBLIC_DIR}/storage -> ${BACKEND_DIR}/storage/app/public
    if [ -L "${PUBLIC_DIR}/storage" ] || [ -d "${PUBLIC_DIR}/storage" ]; then
        # Jika berupa direktori fisik (bukan symlink), selamatkan berkas uploads yang ada ke backend storage
        if [ ! -L "${PUBLIC_DIR}/storage" ] && [ -d "${PUBLIC_DIR}/storage" ]; then
            echo "  [INFO] Menyelamatkan berkas uploads lama dari direktori fisik ke backend/storage/app/public..."
            cp -rn "${PUBLIC_DIR}/storage/"* "${BACKEND_DIR}/storage/app/public/" 2>/dev/null || true
        fi
        rm -rf "${PUBLIC_DIR}/storage"
    fi
    ln -sfn "${BACKEND_DIR}/storage/app/public" "${PUBLIC_DIR}/storage"
    
    if [ -L "${PUBLIC_DIR}/storage" ]; then
        echo "  ✓ Symbolic link terpasang: ${PUBLIC_DIR}/storage -> ${BACKEND_DIR}/storage/app/public"
    else
        echo "  [WARNING] Pembuatan symlink OS dibatasi server. Gateway index.php akan menggunakan fallback baca langsung."
    fi

    # Artisan storage:link fallback
    php artisan storage:link --force 2>/dev/null || true
else
    # Standard Mode
    if command -v npm >/dev/null 2>&1 && [ -f "${FRONTEND_DIR}/package.json" ]; then
        cd "${FRONTEND_DIR}"
        npm install --prefer-offline --no-audit --quiet
        npm run build
        cd "${BASE_DIR}"
    fi

    php artisan storage:link --force 2>/dev/null || true
fi

# Step 6: Hardened File Permissions & Isolation
echo ""
echo "[6/6] Menyelaraskan izin berkas, keamanan direktori & isolasi OWASP..."
chmod -R 775 "${BACKEND_DIR}/storage" "${BACKEND_DIR}/bootstrap/cache"
if [ -d "${BACKEND_DIR}/database" ]; then
    chmod 775 "${BACKEND_DIR}/database"
fi
if [ -f "${BACKEND_DIR}/database/database.sqlite" ]; then
    chmod 664 "${BACKEND_DIR}/database/database.sqlite"
fi

# Jika mode split aktif dan folder backend publik lama masih ada di public_html, hapus dari web root (OWASP Hardening)
if [ "$DEPLOY_MODE" == "split" ] && [ -d "${PUBLIC_DIR}/backend" ] && [ "${PUBLIC_DIR}/backend" != "${BACKEND_DIR}" ]; then
    echo "  -> Menghapus folder backend publik lama dari public_html (OWASP Hardening: Zero Exposure)..."
    rm -rf "${PUBLIC_DIR}/backend"
    echo "  ✓ Folder backend publik lama berhasil dibersihkan dari web root."
fi

# Bersihkan file payload sementara
if [ -d "${DEPLOY_TMP}" ]; then
    rm -rf "${DEPLOY_TMP}"
fi
if [ -f "${PAYLOAD_TAR}" ]; then
    rm -f "${PAYLOAD_TAR}"
fi

# Graceful Web Server Reload (if root/sudo available)
if command -v systemctl >/dev/null 2>&1 && [ "$(id -u)" -eq 0 ]; then
    systemctl reload php8.3-fpm 2>/dev/null || systemctl reload php-fpm 2>/dev/null || true
    systemctl reload nginx 2>/dev/null || systemctl reload apache2 2>/dev/null || true
    echo "  ✓ Layanan Web Server / PHP-FPM di-reload dengan aman."
fi

echo ""
echo "================================================================="
echo "   🎉 DEPLOYMENT SSH ENTERPRISE SELESAI DENGAN SUKSES!           "
echo "================================================================="
