<?php

declare(strict_types=1);

@ini_set('display_errors', '1');
@ini_set('log_errors', '1');

const API_VERSION = '1.3.0';

function app_env(string $key, ?string $fallback=null): ?string {
    $v = getenv($key);
    return ($v === false || $v === '') ? $fallback : $v;
}

/** always JSON */
function json_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    // Security (harmlos, hilft im Dashboard)
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function json_ok(array $data, array $meta = [], int $status = 200): void
{
    json_send(['ok' => true, 'version' => API_VERSION, 'data' => $data, 'meta' => $meta], $status);
}

function json_error(string $msg, int $status = 400, array $extra = []): void
{
    json_send(['ok' => false, 'version' => API_VERSION, 'error' => $msg] + $extra, $status);
}

function app_base_url(): string {
    $u = app_env('URL', '');
    if ($u) {
        // falls nur hostname in .env steht, protokoll ergänzen
        if (!str_starts_with($u, 'http://') && !str_starts_with($u, 'https://')) $u = 'http://' . $u;
        return rtrim($u, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function env_str(string $key, ?string $fallback = null): ?string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $fallback : $v;
}

/* ---------- Redis + Cache ---------- */
function redis_client() {
    static $r = null;
    if ($r !== null) return $r;
    $host = getenv('REDIS_HOST') ?: 'redis';
    $port = (int)(getenv('REDIS_PORT') ?: 6379);
    $pass = getenv('REDIS_PASSWORD') ?: null;
    $db   = (int)(getenv('REDIS_DB') ?: 0);
    try {
        $redis = new Redis();
        $redis->connect($host, $port, 1.5);
        if ($pass) $redis->auth($pass);
        if ($db) $redis->select($db);
        $r = $redis;
        return $r;
    } catch (Throwable $e) {
        error_log('[redis] connect failed: '.$e->getMessage());
        return null;
    }
}


function cache_get(?Redis $r, string $key, int $ttl = 10): ?array
{
    if ($r) {
        try {
            $raw = $r->get($key);
            if ($raw) {
                $d = json_decode($raw, true);
                if (is_array($d)) return $d;
            }
        } catch (Throwable $e) {
            error_log('[api][redis get] ' . $e->getMessage());
        }
    }
    $dir = sys_get_temp_dir() . '/ftx_cache';
    @mkdir($dir, 0777, true);
    $f = $dir . '/' . md5($key) . '.json';
    if (is_file($f) && (time() - (int)@filemtime($f) <= $ttl)) {
        $s = @file_get_contents($f);
        if ($s !== false) {
            $d = json_decode($s, true);
            if (is_array($d)) return $d;
        }
    }
    return null;
}

function cache_set(?Redis $r, string $key, array $val, int $ttl = 10): void
{
    $json = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($r) {
        try {
            $r->setex($key, $ttl, $json);
            return;
        } catch (Throwable $e) {
            error_log('[api][redis set] ' . $e->getMessage());
        }
    }
    $dir = sys_get_temp_dir() . '/ftx_cache';
    @mkdir($dir, 0777, true);
    @file_put_contents($dir . '/' . md5($key) . '.json', $json);
}

/** Optional: einfacher HTTP-HEAD Check (für Services/Web-URLs) */
function http_head_ok(string $url, float $timeout = 2.5): array
{
    $ctx = stream_context_create([
        'http' => ['method' => 'HEAD', 'timeout' => $timeout, 'ignore_errors' => true]
    ]);
    $t0 = microtime(true);
    $fp = @fopen($url, 'rb', false, $ctx);
    $dt = round((microtime(true) - $t0) * 1000, 1);
    if ($fp) {
        @fclose($fp);
        return ['ok' => true, 'latency_ms' => $dt];
    }
    return ['ok' => false, 'latency_ms' => $dt];
}

/** Hilfsfilter (du hast icontains schon – gut!) */
function any_icontains(array $fields, string $q): bool
{
    if ($q === '') return true;
    $q = mb_strtolower($q);
    foreach ($fields as $f) {
        if ($f !== null && $f !== '' && str_contains(mb_strtolower((string)$f), $q)) {
            return true;
        }
    }
    return false;
}

/* ---------- Conditional GET ---------- */
function send_conditional_headers(?string $etag, ?int $lastMod): void
{
    if ($etag) header('ETag: "' . $etag . '"');
    if ($lastMod) header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastMod) . ' GMT');
}

function check_not_modified(?string $etag, ?int $lastMod): void
{
    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    $ifModSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
    if ($etag && $ifNoneMatch === '"' . $etag . '"') {
        http_response_code(304);
        exit;
    }
    if ($lastMod && strtotime($ifModSince ?: '') >= $lastMod) {
        http_response_code(304);
        exit;
    }
}

/* ---------- Projects scan ---------- */
function find_entry(string $path): ?string
{
    foreach (['/public/index.php', '/dist/index.html', '/index.php', '/index.html'] as $rel) {
        if (is_file($path . $rel)) return $rel;
    }
    foreach (glob($path . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        if (is_file($dir . '/public/index.php')) return substr($dir, strlen($path)) . '/public/index.php';
    }
    return null;
}

function detect_type(string $full, ?string $entry): string
{
    if ($entry && str_starts_with($entry, '/public')) return 'Symfony/Laravel (public)';
    if ($entry && str_starts_with($entry, '/dist')) return 'Frontend Build (dist)';
    if (is_file($full . '/artisan')) return 'Laravel';
    if (is_file($full . '/symfony.lock')) return 'Symfony';
    if (is_file($full . '/composer.json')) return 'PHP (Composer)';
    if (is_file($full . '/package.json')) return 'Node';
    if ($entry === '/index.php') return 'Plain PHP';
    return 'Unknown';
}

function build_url(string $item, ?string $entry): string
{
    if (!$entry || $entry === '/index.php') return '/' . $item . '/';
    if ($entry === '/dist/index.html') return '/' . $item . '/dist/';
    if (str_ends_with($entry, '/index.php') || str_ends_with($entry, '/index.html')) {
        $dir = rtrim(dirname($entry), '/\\');
        return '/' . $item . ($dir === '/' || $dir === '.' ? '/' : $dir . '/');
    }
    return '/' . $item . $entry;
}

function icontains(string $s, string $q): bool
{
    return $q === '' || str_contains(mb_strtolower($s), mb_strtolower($q));
}

// === Project meta helpers ================================================

const FTX_PREFIX = 'ftx:'; // Redis-Prefix

function project_meta_path(string $projectDir): string {
    return rtrim($projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.ftx_meta.json';
}

function project_meta_load(string $projectDir): array {
    $f = project_meta_path($projectDir);
    if (!is_file($f)) return [];
    $raw = @file_get_contents($f);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function project_meta_save(string $projectDir, array $meta): bool {
    $f = project_meta_path($projectDir);
    // nur relevante Felder speichern
    $allowed = ['createdAt','entry','type','notes'];
    $clean = array_intersect_key($meta, array_flip($allowed));
    $json = json_encode($clean, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    return @file_put_contents($f, $json) !== false;
}

function project_redis_key(string $name): string {
    return FTX_PREFIX . 'project:' . $name;
}

function project_meta_redis_set($redis, string $name, array $meta, int $ttl = 0): void {
    if (!$redis) return;
    $key = project_redis_key($name);
    // speichere als Hash: HSET
    $fields = [];
    foreach (['createdAt','entry','type','notes'] as $k) {
        if (array_key_exists($k, $meta) && $meta[$k] !== null) {
            $fields[$k] = (string)$meta[$k];
        }
    }
    if ($fields) {
        $redis->hMSet($key, $fields);
        if ($ttl > 0) $redis->expire($key, $ttl);
    }
}

function project_meta_redis_get($redis, string $name): array {
    if (!$redis) return [];
    $key = project_redis_key($name);
    if (!$redis->exists($key)) return [];
    $arr = $redis->hGetAll($key);
    if (!is_array($arr)) return [];
    $out = [];
    foreach ($arr as $k=>$v) {
        if ($k === 'createdAt') $out[$k] = (int)$v; else $out[$k] = $v;
    }
    return $out;
}
