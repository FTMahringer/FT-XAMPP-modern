<?php
declare(strict_types=1);
require __DIR__ . '/common.php';

set_error_handler(function($no,$str,$file,$line){
    error_log("[files_list][$no] $str @ $file:$line");
    json_error('Server error', 500);
    return true;
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('POST only', 405);

$project = trim($_POST['project'] ?? '');
$pathRel = trim($_POST['path'] ?? '');
$content = $_POST['content'] ?? null;

$projDir = assert_project($project);
if ($pathRel === '') json_error('Missing path', 400);
if (!is_string($content)) json_error('Missing content', 400);

$abs = resolve_path($projDir, $pathRel);
$dir = dirname($abs);
if (!is_dir($dir) && !@mkdir($dir, 0775, true)) json_error('Cannot create directory', 500);

// Deny writing obviously binary formats (simple ext check)
$denyExt = ['png','jpg','jpeg','gif','webp','pdf','zip','jar','exe','dll','so'];
$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
if (in_array($ext, $denyExt, true)) json_error('Binary/unsafe file type denied', 415);

// Optional: Backup .bak
@copy($abs, $abs.'.bak');

$ok = @file_put_contents($abs, $content);
if ($ok === false) json_error('Write failed', 500);

json_ok(['project'=>$project,'path'=>$pathRel,'written'=>strlen($content)]);
