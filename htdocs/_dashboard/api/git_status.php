<?php
declare(strict_types=1);
require __DIR__ . '/lib/common.php';

$root = realpath(__DIR__ . '/../../'); // htdocs
if ($root === false) json_error('Root not found', 500);
$exclude = ['_dashboard', '.', '..'];

$project = isset($_GET['project']) ? trim((string)$_GET['project']) : '';
if ($project !== '' && !preg_match('/^[A-Za-z0-9._-]+$/', $project)) json_error('Invalid project');

$quick = isset($_GET['quick']) && ($_GET['quick'] === '1' || $_GET['quick'] === 'true');

function has_git(string $dir): bool { return is_dir($dir.'/.git'); }

function git_bin(): ?string {
    if (!function_exists('shell_exec')) return null;
    $p = @shell_exec('command -v git 2>/dev/null');
    $p = $p ? trim($p) : '';
    return $p !== '' ? $p : null;
}

function git_c(?string $git, string $dir, string $cmd): ?string {
    if (!$git) return null;
    $out = @shell_exec('cd '.escapeshellarg($dir).' && '.escapeshellcmd($git).' '.$cmd.' 2>/dev/null');
    return $out !== null ? trim($out) : null;
}

function parse_head(string $dir): array {
    $f = $dir.'/.git/HEAD';
    if (!is_file($f)) return [];
    $head = trim((string)@file_get_contents($f));
    if (str_starts_with($head,'ref:')) {
        $ref = trim(substr($head,4));
        $branch = basename($ref);
        $hash = @file_get_contents($dir.'/.git/'.trim($ref));
        return ['branch'=>$branch ?: 'HEAD','commit'=>$hash ? substr(trim($hash),0,7) : null];
    }
    return ['branch'=>'HEAD','commit'=>substr($head,0,7)];
}

/** ahead/behind gegenüber origin/<branch> (wenn vorhanden) */
function git_ahead_behind(?string $git, string $dir, string $branch): ?array {
    if (!$git || $branch === '' || $branch === 'HEAD') return null;
    $remote = git_c($git,$dir,'rev-parse --abbrev-ref --symbolic-full-name @{u}');
    if (!$remote) {
        // Versuch: origin/<branch> (falls es existiert)
        $has = git_c($git,$dir,'show-ref --verify --quiet refs/remotes/origin/'.escapeshellarg($branch)) !== null;
        if (!$has) return null;
        $remote = 'origin/'.$branch;
    }
    $line = git_c($git,$dir,'rev-list --left-right --count '.escapeshellarg($remote).'...'.escapeshellarg($branch));
    if (!$line) return null;
    // Format: "<behind> <ahead>" wenn remote...local, aber je nach Reihenfolge:
    // rev-list --left-right --count A...B -> left sind Commits nur in A, right nur in B.
    [$behind,$ahead] = array_map('intval', preg_split('/\s+/', $line));
    return ['ahead'=>$ahead,'behind'=>$behind];
}

function git_status_dir(string $dir, bool $quick = false): array {
    $exists = has_git($dir);
    $git = git_bin();
    $res = [
        'is_git'      => $exists,
        'branch'      => null,
        'commit'      => null,
        'describe'    => null,
        'last_commit' => null,
        'dirty'       => false,
        'changes'     => ['modified'=>0,'untracked'=>0],
        'remote'      => null,
        'divergence'  => null, // ['ahead'=>x,'behind'=>y]
    ];
    if (!$exists) return $res;

    if ($git) {
        $inside = git_c($git,$dir,'rev-parse --is-inside-work-tree');
        if ($inside === 'true') {
            $res['branch'] = git_c($git,$dir,'rev-parse --abbrev-ref HEAD') ?: 'HEAD';
            $res['commit'] = git_c($git,$dir,'rev-parse --short=7 HEAD');

            if (!$quick) {
                $res['describe'] = git_c($git,$dir,'describe --always --dirty --abbrev=7');
                $logh = git_c($git,$dir,'log -1 --pretty=%h%x09%ct%x09%an%x09%s');
                if ($logh) {
                    [$h,$ts,$an,$sub] = array_pad(preg_split('/\t/', $logh, 4), 4, null);
                    $res['last_commit'] = ['hash'=>$h,'time'=>(int)$ts,'author'=>$an,'subject'=>$sub];
                }
                $st = git_c($git,$dir,'status --porcelain=1');
                if ($st !== null) {
                    $mod=0; $un=0;
                    foreach (preg_split('/\r?\n/',$st) as $line) {
                        if ($line==='') continue;
                        if (str_starts_with($line,'??')) $un++; else $mod++;
                    }
                    $res['changes']=['modified'=>$mod,'untracked'=>$un];
                    $res['dirty']=($mod+$un)>0;
                }
                $rv = git_c($git,$dir,'remote -v');
                if ($rv) foreach (preg_split('/\r?\n/',$rv) as $l) if (str_contains($l,'(fetch)')) { $res['remote']=$l; break; }
            }

            // immer versuchen, ahead/behind zu liefern (schnell genug)
            $div = git_ahead_behind($git, $dir, $res['branch'] ?? 'HEAD');
            if ($div) $res['divergence'] = $div;

            return $res;
        }
    }
    // Fallback ohne git binary
    $head = parse_head($dir);
    $res['branch']=$head['branch'] ?? 'HEAD';
    $res['commit']=$head['commit'] ?? null;
    return $res;
}

function projects_map(string $root, array $exclude): array {
    $out = [];
    foreach (scandir($root) ?: [] as $item) {
        if ($item==='' || $item[0]==='.') continue;
        if (in_array($item,$exclude,true)) continue;
        $full = $root . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) $out[$item]=$full;
    }
    ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
    return $out;
}

try {
    if ($project !== '') {
        $dirs = projects_map($root,$exclude);
        if (!isset($dirs[$project])) json_error('Project not found',404);
        json_ok(['project'=>$project,'status'=>git_status_dir($dirs[$project], $quick)]);
    } else {
        $dirs = projects_map($root,$exclude);
        $items = [];
        foreach ($dirs as $name=>$dir) $items[] = ['project'=>$name] + git_status_dir($dir, $quick);
        json_ok(['items'=>$items], ['count'=>count($items)]);
    }
} catch (Throwable $e) {
    json_error('Server error: '.$e->getMessage(), 500);
}
