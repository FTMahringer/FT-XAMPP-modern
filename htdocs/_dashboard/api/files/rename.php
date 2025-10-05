<?php

declare(strict_types=1);
require __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('POST only', 405);

$project = trim($_POST['project'] ?? '');
$oldRel = trim($_POST['oldPath'] ?? '');
$newRel = trim($_POST['newPath'] ?? '');

$projDir = assert_project($project);
if ($oldRel === '' || $newRel === '') json_error('Missing path', 400);

$old = resolve_path($projDir, $oldRel);
$new = resolve_path($projDir, $newRel);
if (!file_exists($old)) json_error('Source not found', 404);
if (file_exists($new)) json_error('Target exists', 409);

if (!@rename($old, $new)) json_error('Rename failed', 500);
json_ok(['renamed' => true, 'old' => $oldRel, 'new' => $newRel]);
