<?php
declare(strict_types=1);

class InstallValidationException extends RuntimeException {}

function databaseFailureMessage(Throwable $exception): string
{
    // Display only structured codes and fixed guidance, never the raw driver message.
    for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
        if (!$cause instanceof PDOException) continue;
        $info = $cause->errorInfo ?? [];
        $state = (string) ($info[0] ?? $cause->getCode());
        $driverCode = (string) ($info[1] ?? '');
        if (!preg_match('/^[0-9]{1,5}$/D', $driverCode)) $driverCode = '';
        if (!preg_match('/^[A-Z0-9]{5}$/D', $state)) $state = '';
        $label = ($driverCode !== '' ? 'MySQL '.$driverCode : 'PDO')
            .($state !== '' ? ' / SQLSTATE '.$state : '');
        $help = match ((int) $driverCode) {
            1045 => 'Autentikasi ditolak. Cocokkan DB_USERNAME dan DB_PASSWORD dengan Database User CloudPanel; akun MySQL juga harus diizinkan dari host koneksi.',
            1044 => 'User tidak mempunyai akses ke database ini. Periksa pasangan user/database dan permissions di CloudPanel.',
            1049 => 'Database tidak ditemukan. Cocokkan DB_DATABASE dengan nama database di CloudPanel.',
            2002, 2003 => 'Koneksi ke layanan MySQL gagal. Periksa DB_HOST, DB_PORT, serta status layanan MySQL di server.',
            2005 => 'Host database tidak dapat ditemukan. Periksa DB_HOST pada .env.',
            2026 => 'Koneksi TLS MySQL gagal. Periksa konfigurasi TLS database dengan pengelola server.',
            default => 'Koneksi database gagal. Sampaikan kode ini untuk pemeriksaan berikutnya; jangan kirim password atau seluruh isi .env.',
        };
        return '['.$label.'] '.$help;
    }
    return 'Koneksi database gagal tanpa kode PDO. Periksa Logs CloudPanel; jangan kirim password atau seluruh isi .env.';
}

// One-time installation through PHP itself; never execute shell commands.
ini_set('display_errors', '0');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
$root = dirname(__DIR__);
if (is_file($root.'/.installed') || !is_file($root.'/.install-token')) {
    http_response_code(404);
    exit('Installer tidak aktif.');
}
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    http_response_code(403);
    exit('Buka melalui HTTPS setelah SSL/TLS CloudPanel aktif.');
}
foreach (['storage/framework/sessions', 'storage/framework/views', 'storage/framework/cache/data', 'storage/app/public', 'storage/logs', 'bootstrap/cache'] as $directory) {
    if (!is_dir($root.'/'.$directory) || !is_writable($root.'/'.$directory)) {
        http_response_code(503);
        exit('Folder '.$directory.' harus tersedia dan writable oleh Site User. Periksa Permissions di File Manager.');
    }
}
session_save_path($root.'/storage/framework/sessions');
session_name('nugasin_install');
session_start(['cookie_secure' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Strict', 'use_strict_mode' => true]);
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
    $token = is_string($_POST['token'] ?? null) ? $_POST['token'] : '';
    if (!hash_equals($_SESSION['csrf'], $csrf) || !hash_equals(trim((string) file_get_contents($root.'/.install-token')), hash('sha256', $token))) {
        http_response_code(403);
        $error = 'Token installer atau sesi tidak valid. Muat ulang halaman dan coba lagi.';
    } else {
        $mutex = fopen($root.'/storage/framework/install-running.lock', 'c');
        if (!$mutex || !flock($mutex, LOCK_EX | LOCK_NB)) {
            http_response_code(409);
            $error = 'Instalasi sedang berjalan. Tunggu hingga selesai.';
        } else {
            $phase = 'pemeriksaan konfigurasi';
            try {
                if (is_file($root.'/.installed')) throw new InstallValidationException('Instalasi sudah selesai.');
                if (!is_file($root.'/.env') || !is_file($root.'/vendor/autoload.php')) throw new InstallValidationException('File .env atau vendor belum tersedia.');
                foreach (['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pcre', 'pdo', 'pdo_mysql', 'session', 'tokenizer', 'xml'] as $extension) {
                    if (!extension_loaded($extension)) throw new InstallValidationException('Ekstensi PHP belum aktif: '.$extension);
                }
                if (is_file($root.'/bootstrap/cache/config.php')) throw new InstallValidationException('Hapus bootstrap/cache/config.php melalui File Manager dahulu.');
                require $root.'/vendor/autoload.php';
                $app = require $root.'/bootstrap/app.php';
                $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();
                if (config('app.debug') || config('app.env') !== 'production' || !config('app.key')) throw new InstallValidationException('Periksa APP_ENV=production, APP_DEBUG=false, dan APP_KEY pada .env.');
                if (config('database.connections.mysql.password') === 'ISI_PASSWORD_DATABASE') throw new InstallValidationException('Isi DB_PASSWORD pada .env menggunakan password database CloudPanel.');
                $phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
                $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
                if (!preg_match('/^[0-9]{9,15}$/D', $phone) || strlen($password) < 12 || strlen($password) > 72) throw new InstallValidationException('Nomor HP harus 9–15 angka. Password admin harus 12–72 byte.');
                $phase = 'koneksi database';
                Illuminate\Support\Facades\DB::connection()->getPdo();
                $phase = 'tautan storage';
                $target = $root.'/storage/app/public';
                $link = $root.'/public/storage';
                if (is_link($link)) {
                    if (realpath($link) !== realpath($target)) throw new InstallValidationException('Tautan public/storage mengarah ke folder yang salah.');
                } elseif (file_exists($link)) {
                    throw new InstallValidationException('public/storage sudah berupa folder/file biasa. Pindahkan sebagai backup melalui File Manager sebelum mencoba lagi.');
                } elseif (!function_exists('symlink') || !@symlink($target, $link)) {
                    throw new InstallValidationException('Aktifkan fungsi symlink di PHP Settings CloudPanel untuk public/storage.');
                }
                $phase = 'migrasi database';
                if ($kernel->call('migrate', ['--force' => true, '--no-interaction' => true]) !== 0) throw new InstallValidationException('Migrasi gagal; periksa log Laravel.');
                $phase = 'data awal';
                Illuminate\Support\Facades\DB::transaction(function () use ($phone, $password) {
                    if (!App\Models\User::where('role', 'admin')->exists()) {
                        if (App\Models\User::where('nomor_hp', $phone)->exists()) throw new InstallValidationException('Nomor HP tersebut sudah digunakan akun lain.');
                        App\Models\User::create(['name' => 'Admin Nugasin Class', 'nomor_hp' => $phone, 'password' => $password, 'role' => 'admin', 'status' => 'approved']);
                    }
                    if (!App\Models\Package::exists()) (new Database\Seeders\PackageSeeder())->run();
                });
                $phase = 'pembersihan cache';
                foreach (['route:clear', 'view:clear'] as $command) {
                    if ($kernel->call($command) !== 0) throw new InstallValidationException('Pembersihan cache gagal.');
                }
                $phase = 'penguncian installer';
                if (file_put_contents($root.'/.installed', gmdate(DATE_ATOM), LOCK_EX) === false) throw new InstallValidationException('Tidak dapat menulis .installed. Periksa permission folder proyek.');
                @unlink($root.'/.install-token');
                $_SESSION = [];
                session_destroy();
                $success = true;
            } catch (Throwable $exception) {
                http_response_code(500);
                // Do not expose PDO errors, SQL, credentials, or stack traces.
                $safe = $exception instanceof InstallValidationException;
                $detail = $safe ? $exception->getMessage()
                    : ($phase === 'koneksi database' ? databaseFailureMessage($exception) : 'Periksa konfigurasi .env, versi PHP, dan Logs CloudPanel.');
                $error = 'Gagal pada '.$phase.'. '.$detail;
                error_log('Nugasin installer failed at '.$phase.' ('.get_class($exception).')');
            } finally {
                flock($mutex, LOCK_UN);
                fclose($mutex);
            }
        }
    }
}
function esc(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalasi Nugasin Class</title>
<style>body{font:16px/1.6 system-ui,sans-serif;background:#f4f6fb;color:#18253c;margin:0;padding:36px 20px}main{max-width:600px;margin:auto;background:white;padding:32px;border-radius:16px}h1{line-height:1.2}label{display:block;font-weight:600;margin-top:18px}input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #a8b3c4;border-radius:8px;font:inherit}button{margin-top:24px;background:#2859cf;color:white;padding:14px 20px;border:0;border-radius:8px;font:inherit;cursor:pointer}.error{background:#fff0ed;padding:16px;color:#942d20}.ok{background:#eafaed;padding:16px}small{color:#5a687c}</style></head><body><main>
<h1>Instalasi Nugasin Class</h1>
<?php if ($success): ?>
<p class="ok">Instalasi berhasil. Installer sudah terkunci.</p>
<p>Migrasi, tautan storage, paket awal, dan admin sudah siap. Akun admin yang sudah ada tetap dipakai.</p>
<p>Hapus <strong>public/deploy.php</strong> dan ZIP unggahan melalui File Manager.</p>
<p><a href="/login">Masuk ke Nugasin Class</a></p>
<?php else: ?>
<p>Isi koneksi database di file <strong>.env</strong> terlebih dahulu. Form ini menjalankan migrasi dan menyiapkan admin pertama.</p>
<?php if ($error): ?><p class="error"><?= esc($error) ?></p><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf" value="<?= esc($_SESSION['csrf']) ?>">
<label for="token">Token installer</label><input id="token" name="token" type="password" required autocomplete="off">
<small>Salin dari file TOKEN-INSTALLER.txt di komputer Anda.</small>
<label for="phone">Nomor HP admin</label><input id="phone" name="phone" inputmode="numeric" pattern="[0-9]{9,15}" required maxlength="15" placeholder="08xxxxxxxxxx">
<label for="password">Password admin baru</label><input id="password" name="password" type="password" minlength="12" maxlength="72" required autocomplete="new-password">
<small>Gunakan 12–72 karakter ASCII. Jika admin sudah ada, akun tersebut dipertahankan; password lama tidak diganti.</small>
<button type="submit">Jalankan instalasi</button></form>
<?php endif; ?></main></body></html>
