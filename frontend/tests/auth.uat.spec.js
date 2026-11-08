import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import path from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const BACKEND_DIR = process.env.BACKEND_DIR || path.resolve(__dirname, '../../backend');
const BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://hyperion-vault.test';

test.describe('UAT: Enterprise Sanctum & Svelte 5 Flow on hyperion-vault.test', () => {
  test.beforeEach(() => {
    execSync('php artisan db:seed --class=DatabaseSeeder', { cwd: BACKEND_DIR });
  });

  test('Audit Alur Penuh: CSRF -> 422 -> 200 Login -> Multiple Roles -> Signed JWT Cookie -> Dashboard -> Logout', async ({ page, context }) => {
    test.setTimeout(60000);
    page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
    page.on('pageerror', err => console.log('BROWSER ERROR:', err));
    await page.goto(`${BASE_URL}/login`);
    await page.waitForLoadState('networkidle');

    // 1. Verifikasi Validasi Input (HTTP 422)
    await page.fill('#email', 'format-salah');
    await page.fill('#password', '123');
    await page.click('#submit-btn');

    await expect(page.locator('text=The email field must be a valid email address.')).toBeVisible({ timeout: 5000 });

    // 2. Submit Kredensial Valid (HTTP 200)
    await page.fill('#email', 'admin@hyperion-vault.test');
    await page.fill('#password', 'password123');

    // Uji toggle visibilitas kata sandi (Eye Icon)
    await expect(page.locator('#password')).toHaveAttribute('type', 'password');
    await page.click('#toggle-password-visibility');
    await expect(page.locator('#password')).toHaveAttribute('type', 'text');
    await page.click('#toggle-password-visibility');
    await expect(page.locator('#password')).toHaveAttribute('type', 'password');

    await page.click('#submit-btn');

    // 3. Verifikasi Redirect Berhasil
    await expect(page).toHaveURL(`${BASE_URL}/admin/dashboard`, { timeout: 15000 });
    await expect(page.locator('h1')).toContainText('Admin Dashboard', { timeout: 15000 });

    // Verifikasi Multiple Roles Badge tampil di UI
    await expect(page.getByRole('main').getByText('admin', { exact: true })).toBeVisible();
    await expect(page.getByRole('main').getByText('manager', { exact: true })).toBeVisible();

    // 4. Verifikasi Keberadaan & Atribut Cookie
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name.includes('session'));
    const xsrfCookie = cookies.find(c => c.name === 'XSRF-TOKEN');
    const roleCookie = cookies.find(c => c.name === 'user_role');
    const jwtCookie = cookies.find(c => c.name === 'auth_jwt');

    expect(sessionCookie, 'Cookie session wajib ada').toBeDefined();
    expect(sessionCookie.httpOnly, 'Cookie session wajib HttpOnly').toBe(true);
    expect(sessionCookie.domain, 'Domain cookie wajib valid').toMatch(/\.(hyperion-vault\.test|template\.org)/);

    expect(xsrfCookie, 'Cookie XSRF-TOKEN wajib ada').toBeDefined();
    expect(xsrfCookie.httpOnly, 'Cookie XSRF-TOKEN wajib non-HttpOnly agar terbaca JS').toBe(false);

    // 4b. Verifikasi Cookie Role
    expect(roleCookie, 'Cookie user_role wajib ada').toBeDefined();
    expect(roleCookie.value, 'Cookie user_role bernilai admin').toBe('admin');
    expect(roleCookie.httpOnly, 'Cookie user_role wajib non-HttpOnly').toBe(false);

    // 4c. Verifikasi Signed JWT Cookie untuk Svelte 5
    expect(jwtCookie, 'Cookie auth_jwt wajib ada').toBeDefined();
    expect(jwtCookie.httpOnly, 'Cookie auth_jwt wajib non-HttpOnly agar terbaca Svelte 5').toBe(false);

    // Decode dan verifikasi payload JWT
    const [headerB64, payloadB64, signatureB64] = jwtCookie.value.split('.');
    expect(headerB64, 'JWT Header wajib ada').toBeDefined();
    expect(payloadB64, 'JWT Payload wajib ada').toBeDefined();
    expect(signatureB64, 'JWT Signature wajib ada').toBeDefined();

    const payloadJson = Buffer.from(payloadB64.replace(/-/g, '+').replace(/_/g, '/'), 'base64').toString('utf8');
    const claims = JSON.parse(payloadJson);

    expect(claims.name, 'Klaim nama dalam JWT wajib sesuai').toMatch(/(Admin|Hyperion Admin)/);
    expect(claims.roles, 'Klaim roles dalam JWT wajib memuat admin dan manager').toEqual(
      expect.arrayContaining(['admin', 'manager'])
    );
    expect(claims.exp, 'Expiration wajib lebih besar dari waktu saat ini').toBeGreaterThan(Date.now() / 1000);

    // 5. Verifikasi Tombol Logout & Redirect ke Login
    await page.click('#logout-btn');
    await expect(page).toHaveURL(`${BASE_URL}/login`);

    // 5b. Verifikasi Seluruh Cookie Auth Terhapus Pasca Logout
    const cookiesAfterLogout = await context.cookies();
    const roleCookieAfterLogout = cookiesAfterLogout.find(c => c.name === 'user_role');
    const jwtCookieAfterLogout = cookiesAfterLogout.find(c => c.name === 'auth_jwt');

    expect(roleCookieAfterLogout?.value).toBeFalsy();
    expect(jwtCookieAfterLogout?.value).toBeFalsy();
  });

  test('Audit Dynamic Menu Hiding, Route Guard, Admin User Management & Session Management', async ({ page, context }) => {
    test.setTimeout(60000);
    await context.clearCookies();
    // 1. Unauthenticated (Guest) check: landing page & login wajib standalone (tanpa sidebar)
    await page.goto(`${BASE_URL}/`);
    await page.waitForLoadState('networkidle');

    await expect(page.locator('#app-sidebar')).not.toBeVisible();
    await expect(page.locator('h1')).toContainText('Skalabilitas Enterprise');
    await expect(page.locator('#hero-cta-btn')).toBeVisible();

    // 2. Hard route guard: Akses langsung ke /admin/users saat unauthenticated wajib redirect ke /login
    await page.goto(`${BASE_URL}/admin/users`);
    await expect(page).toHaveURL(`${BASE_URL}/login`);
    await expect(page.locator('#app-sidebar')).not.toBeVisible();

    // 3. Login sebagai Regular User ('user@hyperion-vault.test')
    await page.fill('#email', 'user@hyperion-vault.test');
    await page.fill('#password', 'password123');
    await page.click('#submit-btn');

    await expect(page).toHaveURL(`${BASE_URL}/dashboard`);
    await expect(page.locator('h1')).toContainText('User Dashboard');

    // Menu regular user: Beranda dihapus dari sidebar, Dashboard tampil, menu admin tersembunyi
    await expect(page.locator('#main-navigation a:has-text("Beranda")')).not.toBeVisible();
    await expect(page.locator('#main-navigation a:has-text("Dashboard")')).toBeVisible();
    await expect(page.locator('#main-navigation a:has-text("Admin Panel")')).not.toBeVisible();
    await expect(page.locator('#main-navigation a:has-text("Kelola Pengguna")')).not.toBeVisible();
    await expect(page.locator('#main-navigation a:has-text("Manajemen Sesi")')).not.toBeVisible();

    // Regular user mencoba paksa akses /admin/users -> wajib redirect kembali ke /dashboard
    await page.goto(`${BASE_URL}/admin/users`);
    await expect(page).toHaveURL(`${BASE_URL}/dashboard`);

    // Regular user mencoba paksa akses /admin/sessions -> wajib redirect kembali ke /dashboard
    await page.goto(`${BASE_URL}/admin/sessions`);
    await expect(page).toHaveURL(`${BASE_URL}/dashboard`);

    // Logout regular user
    await page.click('#logout-btn');
    await expect(page).toHaveURL(`${BASE_URL}/login`);

    // 4. Login sebagai Admin ('admin@hyperion-vault.test')
    await page.fill('#email', 'admin@hyperion-vault.test');
    await page.fill('#password', 'password123');
    await page.click('#submit-btn');

    await expect(page).toHaveURL(`${BASE_URL}/admin/dashboard`);

    // Menu admin: Beranda dihapus dari sidebar, seluruh menu admin tampil lengkap
    await expect(page.locator('#main-navigation a:has-text("Beranda")')).not.toBeVisible();
    await expect(page.locator('#main-navigation a:has-text("Dashboard")')).toBeVisible();
    await expect(page.locator('#main-navigation a:has-text("Admin Panel")')).toBeVisible();
    await expect(page.locator('#main-navigation a:has-text("Kelola Pengguna")')).toBeVisible();
    await expect(page.locator('#main-navigation a:has-text("Manajemen Sesi")')).toBeVisible();

    // 5. Masuk ke halaman Kelola Pengguna
    await page.click('#main-navigation a:has-text("Kelola Pengguna")');
    await expect(page).toHaveURL(`${BASE_URL}/admin/users`);
    await expect(page.locator('h1')).toContainText('Manajemen Pengguna');

    // Verifikasi daftar pengguna ter-render di tabel
    await expect(page.getByText('Pengguna Regular')).toBeVisible({ timeout: 15000 });
    await expect(page.getByText('user@hyperion-vault.test')).toBeVisible({ timeout: 15000 });

    // 6. Uji Aksi: Tangguhkan Status Pengguna Regular
    const suspendBtn = page.locator('tr:has-text("user@hyperion-vault.test") button:has-text("Tangguhkan")');
    await suspendBtn.click();
    await expect(page.locator('text=Pengguna berhasil ditangguhkan.')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('tr:has-text("user@hyperion-vault.test") span:has-text("Ditangguhkan")')).toBeVisible({ timeout: 10000 });

    // 7. Uji Aksi: Cabut Sesi Pengguna Regular
    page.once('dialog', dialog => dialog.accept());
    const revokeBtn = page.locator('tr:has-text("user@hyperion-vault.test") button:has-text("Cabut Sesi")');
    await revokeBtn.click();
    await expect(page.locator('text=Seluruh sesi perangkat pengguna berhasil dicabut secara instan.')).toBeVisible({ timeout: 10000 });

    // 8. Kembalikan status ke Aktif
    const activateBtn = page.locator('tr:has-text("user@hyperion-vault.test") button:has-text("Aktifkan")');
    await activateBtn.click();
    await expect(page.locator('text=Pengguna berhasil diaktifkan kembali.')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('tr:has-text("user@hyperion-vault.test") span:has-text("Aktif")')).toBeVisible({ timeout: 10000 });

    // 9. Uji CRUD: Tambah Pengguna Baru via Modal
    await page.click('#add-user-btn');
    await expect(page.locator('text=Tambah Pengguna Baru')).toBeVisible();
    await page.fill('#form-name', 'Staf Baru Playwright');
    await page.fill('#form-email', 'staf_baru@hyperion-vault.test');
    await page.fill('#form-password', 'password123');

    // Uji toggle visibilitas kata sandi di modal pengguna (Eye Icon)
    await expect(page.locator('#form-password')).toHaveAttribute('type', 'password');
    await page.click('#toggle-user-password-visibility');
    await expect(page.locator('#form-password')).toHaveAttribute('type', 'text');
    await page.click('#toggle-user-password-visibility');
    await expect(page.locator('#form-password')).toHaveAttribute('type', 'password');

    await page.click('button[type="submit"]:has-text("Tambah Pengguna")');

    await expect(page.locator('text=Pengguna berhasil ditambahkan.')).toBeVisible();
    await expect(page.getByText('Staf Baru Playwright')).toBeVisible();
    await expect(page.getByText('staf_baru@hyperion-vault.test')).toBeVisible();

    // 10. Uji CRUD: Edit Pengguna via Modal
    const editBtn = page.locator('tr:has-text("staf_baru@hyperion-vault.test") button:has-text("Edit")');
    await editBtn.click();
    await expect(page.locator('text=Edit Data Pengguna')).toBeVisible();
    await page.fill('#form-name', 'Staf Senior Playwright');
    await page.click('button[type="submit"]:has-text("Simpan Perubahan")');

    await expect(page.locator('text=Data pengguna berhasil diperbarui.')).toBeVisible();
    await expect(page.getByText('Staf Senior Playwright')).toBeVisible();

    // 11. Uji CRUD: Hapus Pengguna
    page.once('dialog', dialog => dialog.accept());
    const deleteBtn = page.locator('tr:has-text("staf_baru@hyperion-vault.test") button:has-text("Hapus")');
    await deleteBtn.click();

    await expect(page.locator('text=Pengguna berhasil dihapus')).toBeVisible();
    await expect(page.getByText('staf_baru@hyperion-vault.test')).not.toBeVisible();

    // 12. Uji Halaman Manajemen Sesi Terpisah & Verifikasi Perangkat Nyata
    await page.click('#main-navigation a:has-text("Manajemen Sesi")');
    await expect(page).toHaveURL(`${BASE_URL}/admin/sessions`);
    await expect(page.locator('h1')).toContainText('Manajemen Sesi & Perangkat Aktif');
    await expect(page.getByText('admin@hyperion-vault.test')).toBeVisible();
    await expect(page.getByText('user@hyperion-vault.test')).not.toBeVisible();
    await expect(page.locator('button:has-text("Cabut Sesi Ini")').first()).toBeVisible();
    await expect(page.locator('button:has-text("Auto-Remove Sesi Kadaluarsa")')).toBeVisible();
  });
});
