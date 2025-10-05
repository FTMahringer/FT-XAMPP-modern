<?php

declare(strict_types=1);
require __DIR__ . '/lib/common.php';

$r = redis_client();
$info = [];
$db = null;
$keys = 0;
$sample = [];

if ($r) {
    // DB auslesen (PING/INFO/KDB)
    $db = (int)env_str('REDIS_DB', '0');
    $info = $r->info();
    $keys = (int)($r->dbSize() ?? 0);
    // ein paar Keys mit ftx:-Prefix zeigen
    $it = NULL;
    $cnt = 0;
    while ($arr = $r->scan($it, FTX_PREFIX . '*', 50)) {
        foreach ($arr as $k) {
            $sample[] = $k;
            if (++$cnt >= 10) break 2;
        }
    }
}

json_ok([
    'connected' => (bool)$r,
    'db' => $db,
    'keys' => $keys,
    'prefix' => FTX_PREFIX,
    'sample' => $sample,
    'info' => [
        'redis_version' => $info['Server']['redis_version'] ?? null,
        'used_memory_human' => $info['Memory']['used_memory_human'] ?? null
    ]
]);
