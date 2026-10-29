<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$destination=$argv[1] ?? '';
$commit=$argv[2] ?? '';
if (!preg_match('/^[a-f0-9]{40}$/D',$commit) || !$destination || file_exists($destination)) throw new RuntimeException('Use a new release directory and full commit SHA.');
mkdir($destination,0755,true);
$paths=['app','config','routes','database/migrations','database/seeders','database/factories','resources/views','resources/css','resources/js','public/assets','vendor',
    'bootstrap/app.php','bootstrap/providers.php','artisan','composer.json','composer.lock','package.json','vite.config.js','public/index.php','public/.htaccess','public/robots.txt','public/favicon.ico','public/favicon-32.png','public/favicon-192.png','public/apple-touch-icon.png'];
foreach($paths as $path){
    $files=is_dir($root.'/'.$path)?new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$path,FilesystemIterator::SKIP_DOTS)):[$root.'/'.$path];
    foreach($files as $file){
        $source=(string)$file;
        if(is_link($source)||!is_file($source))throw new RuntimeException('Only regular release files are accepted.');
        $relative=str_replace('\\','/',substr($source,strlen($root)+1));
        $target=$destination.'/'.$relative;
        if(!is_dir(dirname($target)))mkdir(dirname($target),0755,true);
        if(!copy($source,$target))throw new RuntimeException('Release copy failed.');
    }
}
$installed=json_decode(file_get_contents($destination.'/vendor/composer/installed.json'),true,512,JSON_THROW_ON_ERROR);
if($installed['dev']!==false)throw new RuntimeException('Release vendor must exclude dev dependencies.');
mkdir($destination.'/bootstrap/cache',0755,true);
file_put_contents($destination.'/bootstrap/cache/.gitignore',"*\n!.gitignore\n");
copy(__DIR__.'/verify-release.php',$destination.'/verify-release.php');
file_put_contents($destination.'/release.json',json_encode(['commit'=>$commit,'paths'=>array_merge($paths,['bootstrap/cache'])],JSON_PRETTY_PRINT));
file_put_contents($destination.'/public/assets/release.json',json_encode(['commit'=>$commit]));
echo "Production release prepared without configuration, storage or question bank.\n";
