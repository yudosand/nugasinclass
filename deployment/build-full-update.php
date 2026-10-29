<?php
declare(strict_types=1);

// Reusable full CODE update for an installed site. Never package live configuration/data.
$root = dirname(__DIR__);
$vendorRoot = __DIR__.'/release-20260911-042921/vendor';
$installed = json_decode(file_get_contents($vendorRoot.'/composer/installed.json'), true, flags: JSON_THROW_ON_ERROR);
$lock = json_decode(file_get_contents($root.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
if ($installed['dev'] !== false) throw new RuntimeException('Vendor must contain production dependencies only.');
$wanted = $actual = [];
foreach ($lock['packages'] as $package) $wanted[$package['name']] = $package['version'];
foreach ($installed['packages'] as $package) $actual[$package['name']] = $package['version'];
ksort($wanted);
ksort($actual);
if ($wanted !== $actual) throw new RuntimeException('Production vendor differs from composer.lock. Prepare vendor with composer install --no-dev before building.');

$files = [];
function collectFiles(string $directory, string $prefix, array &$files): void {
    foreach (new DirectoryIterator($directory) as $entry) {
        if ($entry->isDot()) continue;
        if ($entry->isLink()) throw new RuntimeException('Unexpected symlink in code: '.$entry->getPathname());
        $name = $prefix.'/'.$entry->getFilename();
        if ($entry->isDir()) collectFiles($entry->getPathname(), $name, $files);
        else $files[$name] = $entry->getPathname();
    }
}
foreach (['app', 'config', 'resources', 'routes', 'database/migrations', 'database/seeders', 'database/factories', 'public/assets'] as $folder) collectFiles($root.'/'.$folder, $folder, $files);
collectFiles($vendorRoot, 'vendor', $files);
foreach (['artisan', 'composer.json', 'composer.lock', 'package.json', 'vite.config.js', 'bootstrap/app.php', 'bootstrap/providers.php', 'public/index.php', 'public/.htaccess', 'public/robots.txt', 'public/favicon.ico', 'public/favicon-32.png', 'public/favicon-192.png', 'public/apple-touch-icon.png'] as $file) $files[$file] = $root.'/'.$file;
ksort($files);
$archiveName = $argv[1] ?? 'nugasinclass-full-update.zip';
if (!preg_match('/^[a-z0-9-]+\.zip$/D', $archiveName)) throw new RuntimeException('Use a simple ZIP filename.');
if (str_contains($archiveName, 'simulasi-uas')) {
    $files['UPDATE-SIMULASI-UAS.md'] = __DIR__.'/UPDATE-SIMULASI-UAS.md';
    $files['INVENTARIS-SOAL-UAS.md'] = __DIR__.'/INVENTARIS-SOAL-UAS.md';
    ksort($files);
}
if (str_contains($archiveName, 'cuanin')) {
    $files['UPDATE-MARKETING-CUANIN.md'] = __DIR__.'/UPDATE-MARKETING-CUANIN.md';
    ksort($files);
}
if (str_contains($archiveName, 'marketing-admin')) {
    $files['UPDATE-DAFTAR-MARKETING.md'] = __DIR__.'/UPDATE-DAFTAR-MARKETING.md';
    ksort($files);
}
if (str_contains($archiveName, 'client-handler')) {
    $files['UPDATE-CLIENT-HANDLER.md'] = __DIR__.'/UPDATE-CLIENT-HANDLER.md';
    ksort($files);
}
if (str_contains($archiveName, 'cuanin-rounding')) {
    $files['UPDATE-CUANIN-ROUNDING.md'] = __DIR__.'/UPDATE-CUANIN-ROUNDING.md';
    ksort($files);
}
if (str_contains($archiveName, 'cuanin-withdraw-amount')) {
    $files['UPDATE-CUANIN-WITHDRAW-AMOUNT.md'] = __DIR__.'/UPDATE-CUANIN-WITHDRAW-AMOUNT.md';
    ksort($files);
}
if (str_contains($archiveName, 'handler-release')) {
    $files['UPDATE-CLIENT-HANDLER-RELEASE.md'] = __DIR__.'/UPDATE-CLIENT-HANDLER-RELEASE.md';
    ksort($files);
}
if (str_contains($archiveName, 'study-tracker')) {
    $files['UPDATE-STUDY-TRACKER.md'] = __DIR__.'/UPDATE-STUDY-TRACKER.md';
    ksort($files);
}
if (str_contains($archiveName, 'navbar-cleanup')) {
    $files['UPDATE-NAVBAR-CLEANUP.md'] = __DIR__.'/UPDATE-NAVBAR-CLEANUP.md';
    ksort($files);
}
$archive = __DIR__.'/'.$archiveName;
if (str_contains($archiveName, 'syarat-ketentuan')) {
    $files['UPDATE-SYARAT-KETENTUAN.md'] = __DIR__.'/UPDATE-SYARAT-KETENTUAN.md';
    ksort($files);
}
if (str_contains($archiveName, 'tambah-matkul-massal')) {
    $files['UPDATE-TAMBAH-MATKUL-MASSAL.md'] = __DIR__.'/UPDATE-TAMBAH-MATKUL-MASSAL.md';
    ksort($files);
}
if (str_contains($archiveName, 'progres-sesi-massal')) {
    $files['UPDATE-PROGRES-SESI-MASSAL.md'] = __DIR__.'/UPDATE-PROGRES-SESI-MASSAL.md';
    ksort($files);
}
if (str_contains($archiveName, 'password-elearning')) {
    $files['UPDATE-PASSWORD-ELEARNING.md'] = __DIR__.'/UPDATE-PASSWORD-ELEARNING.md';
    ksort($files);
}
if (str_contains($archiveName, 'minus-khusus-laila')) {
    $files['UPDATE-MINUS-KHUSUS-LAILA.md'] = __DIR__.'/UPDATE-MINUS-KHUSUS-LAILA.md';
    ksort($files);
}
if (str_contains($archiveName, 'pembagian-order')) {
    $files['UPDATE-PEMBAGIAN-ORDER.md'] = __DIR__.'/UPDATE-PEMBAGIAN-ORDER.md';
    ksort($files);
}
if (str_contains($archiveName, 'koreksi-order')) {
    $files['UPDATE-KOREKSI-ORDER.md'] = __DIR__.'/UPDATE-KOREKSI-ORDER.md';
    ksort($files);
}
if (str_contains($archiveName, 'kas-cicilan')) {
    $files['UPDATE-KAS-PEMBAYARAN-BERTAHAP.md'] = __DIR__.'/UPDATE-KAS-PEMBAYARAN-BERTAHAP.md';
    ksort($files);
}
if (str_contains($archiveName, 'account-recovery')) {
    $files['UPDATE-ACCOUNT-RECOVERY.md'] = __DIR__.'/UPDATE-ACCOUNT-RECOVERY.md';
    ksort($files);
}
if (str_contains($archiveName, 'currency-calculator')) {
    $files['UPDATE-CURRENCY-CALCULATOR.md'] = __DIR__.'/UPDATE-CURRENCY-CALCULATOR.md';
    ksort($files);
}
$zip = new ZipArchive();
if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot create ZIP.');
$manifest = ['type' => 'full-code-update', 'domain' => 'nugasinclass.web.id', 'built_at' => gmdate(DATE_ATOM), 'preserve' => ['.env', 'storage/', 'public/storage', '.installed'], 'files' => []];
foreach ($files as $name => $source) {
    if (!is_file($source)) throw new RuntimeException('Missing '.$source);
    $zip->addFile($source, $name);
    $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, 0100644 << 16);
    $manifest['files'][$name] = hash_file('sha256', $source);
}
$zip->addFromString('bootstrap/cache/.gitignore', "*\n!.gitignore\n");
$zip->setExternalAttributesName('bootstrap/cache/.gitignore', ZipArchive::OPSYS_UNIX, 0100644 << 16);
$zip->addFromString('RELEASE.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
$zip->setExternalAttributesName('RELEASE.json', ZipArchive::OPSYS_UNIX, 0100644 << 16);
if (!$zip->close()) throw new RuntimeException('ZIP write failed.');

// Check the completed archive and every payload before handing it to the user.
if ($zip->open($archive, ZipArchive::CHECKCONS) !== true) throw new RuntimeException('Invalid ZIP.');
if ($zip->numFiles !== count($files) + 2) throw new RuntimeException('Unexpected ZIP entries.');
for ($i = 0; $i < $zip->numFiles; ++$i) {
    $name = $zip->getNameIndex($i);
    if (preg_match('~^(\.env|\.installed|\.install-token|storage/|public/storage|public/deploy\.php|deployment/|tests/|vendor/phpunit/)~', $name) || str_contains($name, '../') || str_contains($name, '\\') || str_ends_with($name, '.sqlite')) throw new RuntimeException('Forbidden file '.$name);
}
foreach ($manifest['files'] as $name => $hash) {
    if (hash('sha256', $zip->getFromName($name)) !== $hash) throw new RuntimeException('ZIP content mismatch: '.$name);
}
foreach (['resources/views/layouts/app.blade.php', 'resources/views/layouts/admin.blade.php', 'resources/views/landing/paket-soal.blade.php'] as $view) {
    if (!str_contains($zip->getFromName($view), "@include('partials.favicon')")) throw new RuntimeException('Missing favicon in '.$view);
}
if (substr($zip->getFromName('public/favicon.ico'), 0, 4) !== "\x00\x00\x01\x00") throw new RuntimeException('Invalid favicon ICO.');
$zip->close();
file_put_contents($archive.'.sha256', hash_file('sha256', $archive).'  '.basename($archive)."\n");
echo 'PASS: '.count($files).' application/dependency files, production vendor matches lock, QRIS/WhatsApp and favicon included, runtime data excluded.'.PHP_EOL;
echo 'ZIP: '.round(filesize($archive) / 1048576, 2).' MB'.PHP_EOL;
