<?php
declare(strict_types=1);

// Read-only checks: never migrate, seed, rewrite configuration or modify wallet data.
$root=$argv[1] ?? '';
if(!$root || !is_file($root.'/.env'))throw new RuntimeException('Production configuration missing.');
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(!$app->environment('production') || !config('app.key'))throw new RuntimeException('Production environment/key required.');
Illuminate\Support\Facades\DB::select('SELECT 1');
foreach(['users','orders','exam_catalog','exam_attempts'] as $table)if(!Illuminate\Support\Facades\Schema::hasTable($table))throw new RuntimeException('Required existing database table missing.');
if(!Illuminate\Support\Facades\Route::has('admin.exams.update-all'))throw new RuntimeException('Updated application routes missing.');
if(!function_exists('imagecreatefromjpeg'))throw new RuntimeException('GD required.');
if(!is_readable($root.'/resources/exams/questions.json') || !is_readable($root.'/resources/exams/manifest.json'))throw new RuntimeException('Private question bank missing.');
echo "Production bootstrap, database, routes and private material verified.\n";
