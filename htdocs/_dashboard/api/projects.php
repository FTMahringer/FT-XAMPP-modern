<?php
header('Content-Type: application/json; charset=utf-8');

$root = realpath(__DIR__ . '/../..'); // -> htdocs
$exclude = ['_dashboard', '.', '..'];

function findEntry($path) {
    $candidates = [
        '/public/index.php',
        '/dist/index.html',
        '/index.php',
        '/index.html',
    ];
    foreach ($candidates as $rel) {
        if (file_exists($path . $rel)) return $rel;
    }
    foreach (glob($path . '/*', GLOB_ONLYDIR) as $dir) {
        if (file_exists($dir . '/public/index.php')) {
            return str_replace($path, '', $dir) . '/public/index.php';
        }
    }
    return null;
}

function detectType($full, $entry) {
    if ($entry && str_starts_with($entry, '/public')) return 'Symfony App';
    if ($entry && str_starts_with($entry, '/dist')) return 'Vue App';
    if (file_exists($full . '/composer.json')) return 'PHP (composer)';
    if (file_exists($full . '/package.json')) return 'Node';
    return 'Unknown';
}

function htaccessFound($full, $entry) {
    if (!$entry) return false;
    // if entry is /public/... check public/.htaccess, else check project root
    $base = $full;
    if (str_starts_with($entry, '/public')) $base = $full . '/public';
    return file_exists($base . '/.htaccess');
}

$projects = [];
foreach (scandir($root) as $item) {
    if (in_array($item, $exclude)) continue;
    if ($item[0] === '.') continue;
    $full = $root . DIRECTORY_SEPARATOR . $item;
    if (is_dir($full)) {
        $entry = findEntry($full);
        $type  = detectType($full, $entry);
		if ($type === 'Unknown' && $entry === '/index.php') $type = 'Plain PHP';
        $url   = $entry ? '/' . $item . preg_replace('#^/index\.php$#','', $entry) : '/' . $item . '/';
        $mtime = filemtime($full);
        $projects[] = [
            'name' => $item,
            'entry' => $entry,
            'type'  => $type,
            'url'   => $url,
            'mtime' => $mtime,
            'createdAt' => date('d.m.Y H:i', $mtime),
            'htaccess' => htaccessFound($full, $entry)
        ];
    }
}

usort($projects, function($a,$b){ return strcmp(strtolower($a['name']), strtolower($b['name'])); });

echo json_encode([
    'ok' => true,
    'projects' => $projects,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);