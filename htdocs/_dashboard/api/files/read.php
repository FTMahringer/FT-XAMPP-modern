<?php

declare(strict_types=1);
require __DIR__ . '/common.php';

set_error_handler(function($no,$str,$file,$line){
    error_log("[files_list][$no] $str @ $file:$line");
    json_error('Server error', 500);
    return true;
});

$project = trim($_GET['project'] ?? '');
$pathRel = trim($_GET['path'] ?? '');
$projDir = assert_project($project);
if ($pathRel === '') json_error('Missing path', 400);

$abs = resolve_path($projDir, $pathRel);
if (!is_file($abs)) json_error('Not a file', 404);
if (filesize($abs) > 2_000_000) json_error('File too large (>2MB)', 413);

$raw = (string)@file_get_contents($abs);
$bin = is_binary_content($raw);

json_ok([
    'project' => $project,
    'path' => $pathRel,
    'binary' => $bin,
    'content' => $bin ? null : $raw
], ['size' => strlen($raw)]);
