<?php
declare(strict_types=1);
require __DIR__ . '/files/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('POST only', 405);

$name = trim($_POST['name'] ?? '');
$type = trim($_POST['type'] ?? 'php-plain'); // php-plain | symfony | vue-basic | vue-npx (später)
$dry  = isset($_POST['dryRun']) && ($_POST['dryRun']==='1' || $_POST['dryRun']==='true');

if ($name === '' || !preg_match('/^[A-Za-z0-9._-]{2,64}$/', $name)) json_error('Invalid name', 400);
$root = base_root();
$dest = $root . DIRECTORY_SEPARATOR . $name;
if (file_exists($dest)) json_error('Target already exists', 409);

function run_cmd(string $cmd, ?string $cwd=null): array {
    if (!function_exists('shell_exec')) return [1, 'shell_exec disabled'];
    $full = $cmd;
    if ($cwd) $full = 'cd '.escapeshellarg($cwd).' && ' . $cmd;
    $out = shell_exec($full . ' 2>&1');
    return [0, trim((string)$out)];
}

if ($dry) json_ok(['dryRun'=>true,'name'=>$name,'type'=>$type,'target'=>$dest]);

if (!@mkdir($dest, 0775, true)) json_error('Cannot create project directory', 500);

if ($type === 'php-plain') {
    @file_put_contents($dest.'/index.php', "<?php echo 'Hello from {$name}!';\n");
    project_meta_save($dest, ['createdAt'=>time(), 'entry'=>'index.php', 'type'=>'PHP']);
    json_ok(['created'=>true,'name'=>$name,'entry'=>'/index.php','type'=>'PHP']);
    exit;
}

if ($type === 'symfony') {
    [$c1,$o1] = run_cmd('composer --version');
    if ($c1 !== 0 || !str_contains($o1,'Composer')) json_error('Composer not available in container', 500);
    [$c2,$o2] = run_cmd('composer create-project symfony/skeleton .', $dest);
    if ($c2 !== 0) json_error('Composer create-project failed: '.$o2, 500);
    project_meta_save($dest, ['createdAt'=>time(),'entry'=>'public/index.php','type'=>'Symfony']);
    json_ok(['created'=>true,'name'=>$name,'entry'=>'/public/index.php','type'=>'Symfony','log'=>$o2]);
    exit;
}

/** vue-basic: minimaler Vite+Vue Skeleton ohne npx (sofort lauffähig) */
if ($type === 'vue-basic') {
    $pkg = [
        'name' => $name,
        'private' => true,
        'version' => '0.0.1',
        'type' => 'module',
        'scripts' => [
            'dev' => 'vite',
            'build' => 'vite build',
            'preview' => 'vite preview'
        ],
        'dependencies' => [ 'vue' => '^3.4.0' ],
        'devDependencies' => [ 'vite' => '^5.0.0', '@vitejs/plugin-vue' => '^5.0.0' ]
    ];
    @mkdir($dest.'/src', 0775, true);
    @mkdir($dest.'/public', 0775, true);
    @file_put_contents($dest.'/index.html', <<<HTML
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{$name}</title>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
HTML);
    @file_put_contents($dest.'/src/App.vue', <<<VUE
<script setup>
</script>
<template>
  <h1 style="font-family: system-ui;">Hello from {$name} (Vue + Vite)</h1>
</template>
<style scoped>
h1 { font-weight: 800; }
</style>
VUE);
    @file_put_contents($dest.'/src/main.js', <<<JS
import { createApp } from 'vue'
import App from './App.vue'
createApp(App).mount('#app')
JS);
    @file_put_contents($dest.'/vite.config.js', <<<JS
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
export default defineConfig({ plugins: [vue()] })
JS);
    @file_put_contents($dest.'/package.json', json_encode($pkg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    project_meta_save($dest, ['createdAt'=>time(),'entry'=>'index.html','type'=>'Vue']);
    json_ok(['created'=>true,'name'=>$name,'entry'=>'/index.html','type'=>'Vue']);
    exit;
}

json_error('Unknown type', 400);
