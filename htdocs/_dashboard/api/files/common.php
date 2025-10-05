<?php
declare(strict_types=1);
require __DIR__ . '/../lib/common.php';

/** Base: /var/www/html (htdocs) */
function base_root(): string {
    // drei Ebenen hoch: /_dashboard/api/files -> /var/www/html
    $root = realpath(dirname(__DIR__, 3));
    if ($root === false) json_error('Root not found', 500);
    return $root;
}

/** Nur alphanum.-Ordnernamen (Projektordner) */
function assert_project(string $name): string {
    if ($name === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $name)) json_error('Invalid project', 400);
    $dir = base_root() . DIRECTORY_SEPARATOR . $name;
    if (!is_dir($dir)) json_error('Project not found', 404);
    return realpath($dir) ?: $dir;
}

/** Pfad innerhalb eines Projekts sicher auflösen */
function resolve_path(string $projectDir, string $rel): string {
    $rel = str_replace('\\', '/', $rel);
    if (str_starts_with($rel, '/')) $rel = ltrim($rel, '/');
    if (strpos($rel, '..') !== false) json_error('Invalid path', 400);
    $abs = realpath($projectDir . DIRECTORY_SEPARATOR . $rel);
    if ($abs === false) $abs = $projectDir . DIRECTORY_SEPARATOR . $rel; // anlegen erlaubt
    $base = realpath($projectDir) ?: $projectDir;
    if (!str_starts_with($abs, $base)) json_error('Access denied', 403);
    return $abs;
}

/** Binär-Heuristik */
function is_binary_content(string $data): bool {
    if ($data === '') return false;
    if (strpos($data, "\0") !== false) return true;
    $nonPrintable = preg_match_all('/[^\x09\x0A\x0D\x20-\x7E]/', $data) ?: 0;
    $ratio = $nonPrintable > 0 ? ($nonPrintable / max(1, strlen($data))) : 0;
    return $ratio > 0.30;
}

/** Dir-Tree */
function list_dir_tree(string $dir, array $opts = []): array {
    $maxDepth = (int)($opts['maxDepth'] ?? 6);
    $ignore = $opts['ignore'] ?? ['.git', 'node_modules', 'vendor', '.idea', '.vscode', '.DS_Store'];

    $res = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        $depth = $it->getDepth();
        if ($depth > $maxDepth) continue;
        $name = $file->getFilename();
        if ($file->isDir() && in_array($name, $ignore, true)) { $it->next(); continue; }
        if (!$file->isDir() && in_array($name, $ignore, true)) continue;

        $rel = ltrim(str_replace($dir, '', $file->getPathname()), DIRECTORY_SEPARATOR);
        $res[] = [
            'path'  => $rel,
            'type'  => $file->isDir() ? 'dir' : 'file',
            'size'  => $file->isDir() ? 0 : $file->getSize(),
            'mtime' => $file->getMTime(),
        ];
    }
    usort($res, function($a,$b){
        if ($a['type'] !== $b['type']) return $a['type']==='dir' ? -1 : 1;
        return strnatcasecmp($a['path'], $b['path']);
    });
    return $res;
}
