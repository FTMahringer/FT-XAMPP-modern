<?php
declare(strict_types=1);
require __DIR__ . '/lib/common.php';

set_error_handler(function($no,$str){ error_log("[projects.php][$no] $str"); return true; });

$root = realpath(__DIR__ . '/../../'); // htdocs
if ($root === false) json_error('Root not found', 500);

/** Excludes (erweitert) */
$exclude = ['_dashboard', '.', '..', '.DS_Store', '.idea', '.vscode'];

/* Query */
$q      = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$sort   = $_GET['sort']  ?? 'name';   // name|mtime|type
$order  = $_GET['order'] ?? 'asc';    // asc|desc
$limit  = isset($_GET['limit'])  ? max(1, min(500, (int)$_GET['limit'])) : 500;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
if (!in_array($sort, ['name','mtime','type'], true)) $sort = 'name';
$order = ($order === 'desc') ? 'desc' : 'asc';

/* Cache */
$cacheKey = 'projects:list:' . md5(json_encode([$q,$sort,$order,$limit,$offset,$root]));
$r = redis_client();
if ($payload = cache_get($r, $cacheKey, 10)) {
    send_conditional_headers($payload['meta']['etag'] ?? null, $payload['meta']['lastModified'] ?? null);
    check_not_modified($payload['meta']['etag'] ?? null, $payload['meta']['lastModified'] ?? null);
    json_send($payload);
}

/* fresh scan */
$projects = [];
$latestMTime = 0;

foreach (scandir($root) ?: [] as $item) {
    if ($item === '' || $item[0] === '.') continue;
    if (in_array($item, $exclude, true)) continue;

    $full = $root . DIRECTORY_SEPARATOR . $item;
    if (!is_dir($full)) continue;

    $entry = find_entry($full);
    $type  = detect_type($full, $entry);
    $url   = build_url($item, $entry);
    $mtime = @filemtime($full) ?: time();
    if ($mtime > $latestMTime) $latestMTime = $mtime;

    // --- NEW: Meta laden (Datei + Redis Mirror) ---------------------------
    $metaFile = project_meta_load($full);
    $metaRedis = project_meta_redis_get($r, $item);
    // Merge-Strategie: Datei hat Priorität (ist die Quelle), Redis als Fallback
    $createdAt = $metaFile['createdAt'] ?? $metaRedis['createdAt'] ?? null;

    // Wenn kein createdAt vorhanden: initialisieren (auf mtime) und persistieren
    if (!$createdAt) {
        $createdAt = $mtime; // Initialwert (besser als nix)
        $save = ['createdAt' => $createdAt];
        // Wenn wir schon wissen, speichern wir gleich type/entry mit
        if ($entry) $save['entry'] = $entry;
        if ($type)  $save['type']  = $type;
        project_meta_save($full, ($metaFile + $save));
        project_meta_redis_set($r, $item, ($metaFile + $save), 3600); // 1h TTL ok
    } else {
        // Redis ggf. updaten, damit die Liste schnell rendern kann
        $mirror = $metaFile;
        if (!isset($mirror['createdAt'])) $mirror['createdAt'] = $createdAt;
        if (!isset($mirror['entry']) && $entry) $mirror['entry'] = $entry;
        if (!isset($mirror['type']) && $type)   $mirror['type']  = $type;
        project_meta_redis_set($r, $item, $mirror, 3600);
    }
    // ----------------------------------------------------------------------

    if (!any_icontains([$item, $type, $entry, $url], $q)) continue;

    $projects[] = [
        'name'      => $item,
        'entry'     => $entry,
        'type'      => $type,
        'url'       => $url,
        'mtime'     => $mtime,
        'createdAt' => (int)$createdAt, // NEW
    ];
}

/* Sortierung */
usort($projects, function($a,$b) use($sort,$order){
    $cmp = 0;
    if ($sort === 'name')       $cmp = strnatcasecmp($a['name'], $b['name']);
    elseif ($sort === 'type')   $cmp = strnatcasecmp($a['type'], $b['type']);
    elseif ($sort === 'mtime')  $cmp = ($a['mtime'] <=> $b['mtime']);
    else /* createdAt */        $cmp = ($a['createdAt'] <=> $b['createdAt']);
    return $order === 'desc' ? -$cmp : $cmp;
});

$total = count($projects);
$slice = array_slice($projects, $offset, $limit);

/* meta + conditional headers */
$etag    = substr(sha1(json_encode([$total,$q,$sort,$order,array_column($slice,'name')], JSON_UNESCAPED_SLASHES)), 0, 16);
$lastMod = $latestMTime ?: time();
send_conditional_headers($etag, $lastMod);

$payload = [
    'ok'      => true,
    'version' => API_VERSION,
    'data'    => ['projects' => $slice],
    'meta'    => [
        'query'        => ['q'=>$q,'sort'=>$sort,'order'=>$order,'limit'=>$limit,'offset'=>$offset],
        'total'        => $total,
        'count'        => count($slice),
        'lastModified' => $lastMod,
        'etag'         => $etag,
    ],
];

cache_set($r, $cacheKey, $payload, 10);
json_send($payload);
