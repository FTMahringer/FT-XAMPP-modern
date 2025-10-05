<?php

declare(strict_types=1);
require __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('POST only', 405);

$project = trim($_POST['project'] ?? '');
$pathRel = trim($_POST['path'] ?? '');
$trash = isset($_POST['trash']) && ($_POST['trash'] === '1' || $_POST['trash'] === 'true'); // .bak statt hard delete

$projDir = assert_project($project);
if ($pathRel === '') json_error('Missing path', 400);

$abs = resolve_path($projDir, $pathRel);
if (!file_exists($abs)) json_error('Not found', 404);

if ($trash && is_file($abs)) {
    if (!@copy($abs, $abs . '.bak')) json_error('Trash copy failed', 500);
}

$ok = is_dir($abs) ? @rmdir($abs) : @unlink($abs);
if (!$ok) json_error('Delete failed (not empty?)', 400);

json_ok(['deleted' => true, 'path' => $pathRel, 'trashed' => $trash]);
