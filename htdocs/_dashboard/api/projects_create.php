<?php
// htdocs/_dashboard/api/projects_create.php
declare(strict_types=1);

use JetBrains\PhpStorm\NoReturn;

header('Content-Type: application/json; charset=utf-8');

#[NoReturn]
function fail(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['ok'=>false, 'error'=>$msg], JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed');

// Body lesen (JSON oder Form)
$raw = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (!is_array($body)) { $body = $_POST; }

$name    = trim((string)($body['name'] ?? ''));
$type    = (string)($body['type'] ?? '');
$options = is_array($body['options'] ?? null) ? $body['options'] : [];

if ($name === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $name) || $name === '_dashboard') {
    fail(400, 'Invalid project name');
}
$allowed = ['plain-php','plain-html','symfony','vue'];
if (!in_array($type, $allowed, true)) fail(400, 'Invalid type');

// Root = htdocs (2 Ebenen hoch von _dashboard/api/)
$root = realpath(__DIR__ . '/../../');
if ($root === false) fail(500, 'Root not found');
$target = $root . DIRECTORY_SEPARATOR . $name;
if (file_exists($target)) fail(409, 'Project already exists');

// Pfad zu den htaccess-Templates (_dashboard/config/htaccess-files)
$htFilesDir = realpath(dirname(__DIR__) . '/config/htaccess-files');
if ($htFilesDir === false) fail(500, 'htaccess templates dir not found');

// --- Helpers ---
function mkdirp(string $dir): void {
    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
        fail(500, 'Cannot create directory: '.$dir);
    }
}
function put(string $path, string $content): void {
    if (@file_put_contents($path, $content) === false) fail(500, 'Cannot write file: '.$path);
    @chmod($path, 0644);
}
function copyFromTemplate(string $templatePath, string $destPath): void {
    if (!is_file($templatePath)) {
        fail(500, 'Template missing: '.$templatePath);
    }
    $content = @file_get_contents($templatePath);
    if ($content === false) {
        fail(500, 'Cannot read template: '.$templatePath);
    }
    // Zielordner sicherstellen
    $dir = dirname($destPath);
    if (!is_dir($dir)) mkdirp($dir);
    put($destPath, $content);
}
function run(array $cmd, string $cwd, ?string $logName = null, bool $quiet = true): array {
    $env = array_merge($_ENV, [
        'HOME' => '/tmp',
        'COMPOSER_HOME' => '/tmp/composer',
        'COMPOSER_ALLOW_SUPERUSER' => '1',
        'COMPOSER_MEMORY_LIMIT' => '-1',
        'COMPOSER_PROCESS_TIMEOUT' => '2000',
        'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        'LC_ALL' => 'C',
    ]);
    @mkdir('/tmp/composer', 0777, true);
    $logDir = '/tmp/dashboard-create';
    @mkdir($logDir, 0777, true);

    if ($quiet) {
        $cmd = array_merge($cmd, ['-q', '--no-ansi', '--no-progress', '--no-interaction']);
    }

    $logPath = $logName ? ($logDir.'/'.preg_replace('/[^A-Za-z0-9._-]/','_',$logName).'.log') : '/dev/null';
    $desc = [
        1 => ['file', $logPath, 'a'],
        2 => ['file', $logPath, 'a'],
    ];

    $proc = proc_open($cmd, $desc, $pipes, $cwd, $env);
    if (!is_resource($proc)) return [1, '', 'cannot start process'];
    $code = proc_close($proc);
    return [$code, '', $logPath];
}

// --- Create by type ---
$initGit   = !empty($options['initGit']);
$addReadme = !empty($options['addReadme']);

switch ($type) {
    case 'plain-php':
        mkdirp($target);
        put($target.'/index.php', "<?php echo 'Hello from $name!';");
        // .htaccess aus Template
        copyFromTemplate($htFilesDir.'/.htaccess', $target.'/.htaccess');
        break;

    case 'plain-html':
        mkdirp($target);
        put($target.'/index.html', "<!doctype html><meta charset=utf-8><title>$name</title><h1>$name</h1><p>Hello!</p>");
        copyFromTemplate($htFilesDir.'/html.htaccess', $target.'/.htaccess');
        break;

    case 'symfony':
        $preset  = (string)($options['symfonyPreset'] ?? 'webapp'); // webapp|api|minimal
        $verIn   = trim((string)($options['symfonyVersion'] ?? '')); // z.B. "7.2"
        $package = 'symfony/skeleton';
        if ($verIn !== '') {
            $constraint = preg_match('/^\d+\.\d+$/', $verIn) ? '^'.$verIn : $verIn;
            $package .= ':' . $constraint;
        }

        // 1) create-project
        [$code,, $log] = run(['composer','create-project',$package,$name], $root, "symfony-create-$name");
        if ($code !== 0) fail(500, 'Composer error (create-project). See log: '.$log);

        $target = $root . DIRECTORY_SEPARATOR . $name;

        // 2) Presets
        if ($preset === 'webapp') {
            [$code,, $log] = run(['composer','require','symfony/webapp-pack'], $target, "symfony-webapp-pack-$name");
            if ($code !== 0) fail(500, 'Composer error (webapp-pack). See log: '.$log);
        } elseif ($preset === 'api') {
            [$code,, $log] = run(['composer','require','api'], $target, "symfony-api-pack-$name");
            if ($code !== 0) fail(500, 'Composer error (api-pack). See log: '.$log);
        }

        // 3) Apache-Pack (optional – viele mögen die Flex-Defaults)
        [$code,, $log] = run(['composer','require','symfony/apache-pack'], $target, "symfony-apache-pack-$name");
        if ($code !== 0) fail(500, 'Composer error (apache-pack). See log: '.$log);

        // 4) Rechte + .htaccess aus Template (erzwingt unser optimiertes File)
        @mkdir($target.'/var/cache', 0775, true);
        @mkdir($target.'/var/log', 0775, true);
        @chmod($target.'/var/cache', 0775);
        @chmod($target.'/var/log', 0775);

        // Immer unsere Variante nach public/.htaccess legen (überschreibt ggf. die vom apache-pack)
        copyFromTemplate($htFilesDir.'/symfony.htaccess', $target.'/public/.htaccess');
        break;

    case 'vue':
        // (Noch kein Scaffold hier – bleibt wie bisher)
        mkdirp($target);
        put($target.'/.gitkeep', '');
        copyFromTemplate($htFilesDir.'/vue.htaccess', $target.'/.htaccess');
        break;

    default:
        break;
}

// README + Git
if ($addReadme) put($target.'/README.md', "# $name\nCreated via Dashboard\nType: `$type`\n");
if ($initGit) {
    run(['git','init'], $target);
    put($target.'/.gitignore', ".env\n/vendor/\n/node_modules/\n/dist/\n");
    run(['git','add','.'], $target);
    run(['git','commit','-m','Initial scaffold'], $target);
}

echo json_encode(['ok'=>true,'project'=>['name'=>$name,'path'=>$target,'type'=>$type]], JSON_UNESCAPED_SLASHES);
