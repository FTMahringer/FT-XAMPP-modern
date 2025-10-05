<?php
declare(strict_types=1);
require __DIR__.'/lib/common.php';

$env = [
    'REDIS_HOST'      => env_str('REDIS_HOST', env_str('REDIS_CONTAINER')),
    'REDIS_PORT'      => env_str('REDIS_PORT'),
    'REDIS_PASSWORD'  => getenv('REDIS_PASSWORD') ? '*** set ***' : '(empty)',
    'PHP_VERSION'     => PHP_VERSION,
];

$info = ['class_exists'=>class_exists('Redis')];
if ($info['class_exists']) {
    try {
        $r = new Redis();
        $host = env_str('REDIS_HOST', env_str('REDIS_CONTAINER','ftxampp_redis'));
        $port = (int) env_str('REDIS_PORT','6379');
        $pass = env_str('REDIS_PASSWORD', null);
        $t0 = microtime(true);
        $r->connect($host,$port,0.25);
        if ($pass) $r->auth($pass);
        $info['connected']=true;
        $info['ping']=$r->ping();
        $info['latency_ms']=round((microtime(true)-$t0)*1000,1);
    } catch (Throwable $e) {
        $info['connected']=false;
        $info['error']=$e->getMessage();
    }
}
json_ok(['env'=>$env,'redis'=>$info], ['time'=>date('c')]);
