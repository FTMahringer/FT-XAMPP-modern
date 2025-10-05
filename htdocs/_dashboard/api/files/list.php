<?php
declare(strict_types=1);
require __DIR__ . '/common.php';

set_error_handler(function($no,$str,$file,$line){
    error_log("[files_list][$no] $str @ $file:$line");
    json_error('Server error', 500);
    return true;
});

$project = trim($_GET['project'] ?? '');
$projDir = assert_project($project);

$maxDepth = isset($_GET['depth']) ? max(1, min(12, (int)$_GET['depth'])) : 12;

/** flat scan */
$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$flat = [];
$baseLen = strlen(rtrim($projDir, DIRECTORY_SEPARATOR)) + 1;

foreach ($rii as $spl) {
    $depth = substr_count(str_replace('\\','/',$spl->getPathname()), '/') - substr_count(str_replace('\\','/',$projDir), '/');
    if ($depth > $maxDepth) continue;

    $rel = substr($spl->getPathname(), $baseLen);
    $rel = str_replace('\\','/',$rel);
    $type = $spl->isDir() ? 'dir' : 'file';

    $flat[] = [
        'path'  => $rel,
        'type'  => $type,
        'size'  => $type === 'file' ? $spl->getSize() : 0,
        'mtime' => $spl->getMTime(),
    ];
}

/** sort: dirs first, then files; by name (case-insensitive) */
usort($flat, function($a,$b){
    if ($a['type'] !== $b['type']) return $a['type'] === 'dir' ? -1 : 1;
    return strcasecmp(basename($a['path']), basename($b['path']));
});

/** optional nested structure */
function build_nested(array $flat): array {
    $root = [];
    $index = [];

    $getParent = function(string $path): ?string {
        $p = str_contains($path, '/') ? substr($path, 0, strrpos($path, '/')) : '';
        return $p === '' ? null : $p;
    };

    foreach ($flat as $it) {
        $segments = array_values(array_filter(explode('/', $it['path'])));
        // ensure all parent dirs are present in index
        $walk = '';
        for ($i=0;$i<count($segments)-($it['type']==='file'?1:0);$i++) {
            $walk = $walk === '' ? $segments[$i] : "$walk/{$segments[$i]}";
            if (!isset($index[$walk])) {
                $node = [
                    'name' => $segments[$i],
                    'path' => $walk,
                    'type' => 'dir',
                    'size' => 0,
                    'mtime'=> 0,
                    'children' => []
                ];
                $index[$walk] = $node;
            }
        }
    }

    // attach dirs to parents / root
    foreach ($index as $p => &$node) {
        $parent = $getParent($p);
        if ($parent && isset($index[$parent])) {
            $index[$parent]['children'][] = &$node;
        } else {
            $root[] = &$node;
        }
    }
    unset($node);

    // add files
    foreach ($flat as $it) {
        if ($it['type'] === 'dir') continue; // dirs already exists
        $segments = explode('/', $it['path']);
        $name = array_pop($segments);
        $parent = implode('/', $segments);
        $fileNode = [
            'name' => $name,
            'path' => $it['path'],
            'type' => 'file',
            'size' => $it['size'],
            'mtime'=> $it['mtime'],
        ];
        if ($parent !== '' && isset($index[$parent])) {
            $index[$parent]['children'][] = $fileNode;
        } else {
            $root[] = $fileNode;
        }
    }

    // sort recursively
    $sortFn = function(&$list) use (&$sortFn) {
        usort($list, function($a,$b){
            if ($a['type'] !== $b['type']) return $a['type']==='dir' ? -1 : 1;
            return strcasecmp($a['name'], $b['name']);
        });
        foreach ($list as &$n) if (isset($n['children'])) $sortFn($n['children']);
    };
    $sortFn($root);

    return $root;
}

$nested = build_nested($flat);

$meta = [
    'count' => count($flat),
    'files' => count(array_filter($flat, fn($x) => $x['type']==='file')),
    'dirs'  => count(array_filter($flat, fn($x) => $x['type']==='dir')),
    'version' => '1.4.0'
];

json_ok(['project' => $project, 'tree' => $flat, 'nested' => $nested], $meta);
