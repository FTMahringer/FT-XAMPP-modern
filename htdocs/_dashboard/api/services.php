<?php
declare(strict_types=1);
require __DIR__ . '/lib/common.php';

function tcp_check(string $host, int $port, float $timeout = 0.25): array {
    $start = microtime(true);
    $fp = @fsockopen($host, $port,$errno,$errstr,$timeout);
    $ms = (microtime(true) - $start) * 1000.0;
    if ($fp) { fclose($fp); return ['ok'=>true,'latency_ms'=>round($ms,1)]; }
    return ['ok'=>false, 'error'=>"$errno $errstr"];
}

$env = [
    'MARIADB_VERSION' => env_str('MARIADB_VERSION',''),
    'REDIS_VERSION'   => env_str('REDIS_VERSION',''),
    'PHP_VERSION'     => PHP_VERSION,
];

$targets = [
    ['name'=>'mariadb','host'=>env_str('MARIADB_CONTAINER','ftxampp_mariadb'),'port'=>3306],
    ['name'=>'web','host'=>env_str('APACHE_CONTAINER','ftxampp_apache'),  'port'=>80],
    ['name'=>'redis',  'host'=>env_str('REDIS_CONTAINER','ftxampp_redis'),    'port'=>6379],
];
usort($targets, fn($a,$b)=>strcmp($a['name'],$b['name']));

$results=[];
foreach ($targets as $t) $results[] = array_merge($t, tcp_check($t['host'], (int)$t['port']));

/** Optional: HTTP-Health aus config/services.json */
$withHttp = isset($_GET['http']) && ($_GET['http']==='1' || $_GET['http']==='true');
$web = [];
if ($withHttp) {
    $cfg = __DIR__ . '/../config/services.json';
    if (is_file($cfg)) {
        $list = json_decode((string)@file_get_contents($cfg), true);
        if (is_array($list)) {
            foreach ($list as $svc) {
                $name = (string)($svc['name'] ?? 'service');
                $url  = (string)($svc['url']  ?? '');
                if ($url === '') continue;
                $res = http_head_ok($url, 2.0);
                $web[] = ['name'=>$name,'url'=>$url] + $res + ['tags'=>$svc['tags'] ?? []];
            }
        }
    }
}

json_ok(['env'=>$env,'services'=>$results,'web'=>$web], [
    'count'=>count($results),
    'http_count'=>count($web),
    'timestamp'=>date('c')
]);
