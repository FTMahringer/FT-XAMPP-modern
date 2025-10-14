<?php
declare(strict_types=1);
require __DIR__ . '/lib/common.php';

/**
 * Definiere hier zentral deine Endpunkte.
 */

function endpoints(): array {
    return [
        [
            'method' => 'GET',
            'path' => '/_dashboard/api/git_status.php',
            'summary' => 'Git-Infos für alle Projekte oder ein Projekt (?project=NAME).',
            'tags' => ['git'],
            'parameters' => [
                ['in'=>'query','name'=>'project','required'=>false,'description'=>'Projektname (Ordnername in htdocs)'],
                ['in'=>'query','name'=>'quick','required'=>false,'description'=>'Wenn 1: schneller Modus (ohne describe/log/status)'],
            ],
        ],
        [
            'method' => 'GET',
            'path' => '/_dashboard/api/projects.php',
            'summary' => 'Liste der Projekte aus htdocs.',
            'tags' => ['projects'],
            'parameters' => [
                ['in'=>'query','name'=>'q','required'=>false,'description'=>'Filter (Name/Typ/Pfad/URL)'],
                ['in'=>'query','name'=>'sort','required'=>false,'description'=>'name|mtime|type|createdAt'],
                ['in'=>'query','name'=>'order','required'=>false,'description'=>'asc|desc'],
                ['in'=>'query','name'=>'limit','required'=>false,'description'=>'1..500'],
                ['in'=>'query','name'=>'offset','required'=>false,'description'=>'Ab Offset'],
            ],
        ],
        [
            'method' => 'GET',
            'path' => '/_dashboard/api/ping.php',
            'summary' => 'Healthcheck/Ping.',
            'tags' => ['system'],
            'parameters' => [],
        ],
        [
            'method' => 'GET',
            'path' => '/_dashboard/api/services.php',
            'summary' => 'Service-Liste (TCP) und optional HTTP-Health aus config/services.json',
            'tags' => ['system'],
            'parameters' => [
                ['in'=>'query','name'=>'http','required'=>false,'description'=>'Wenn 1, prüfe HTTP-URLs aus config/services.json'],
            ],
        ]
    ];
}

function toInternalSchema(array $eps): array {
    return ['title'=>'FT-XAMPP Local API', 'version'=>API_VERSION, 'endpoints'=>$eps];
}

function toOpenAPI(array $eps): array {
    $paths = [];
    foreach ($eps as $ep) {
        $method = strtolower($ep['method']);
        $path = $ep['path'];
        $parameters = [];
        $requestBody = null;

        foreach ($ep['parameters'] as $p) {
            if (($p['in'] ?? '') === 'body') {
                $requestBody = [
                    'required' => $p['required'] ?? false,
                    'content' => [
                        'application/json' => ['schema' => ['type'=>'object']]
                    ]
                ];
            } else {
                $parameters[] = [
                    'in' => $p['in'],
                    'name' => $p['name'],
                    'required' => $p['required'] ?? false,
                    'description' => $p['description'] ?? '',
                    'schema' => [ 'type' => 'string' ],
                ];
            }
        }

        $op = [
            'summary' => $ep['summary'] ?? '',
            'tags' => $ep['tags'] ?? [],
            'parameters' => $parameters,
            'responses' => [
                '200' => [
                    'description' => 'OK',
                    'content' => ['application/json' => ['schema' => ['type'=>'object']]]
                ]
            ]
        ];
        if ($requestBody) $op['requestBody'] = $requestBody;
        $paths[$path][$method] = $op;
    }

    return [
        'openapi' => '3.1.0',
        'info' => ['title'=>'FT-XAMPP Local API', 'version'=>API_VERSION],
        'servers' => [['url'=>'/']],
        'paths' => $paths,
    ];
}

/** 60s-Cache + 304 */
try {
    $eps = endpoints();

    $format = ($_GET['format'] ?? '');
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

    if ($format === 'openapi' || str_contains($accept, 'profile=openapi')) {
        $doc = toOpenAPI($eps);
        $json = json_encode($doc, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $etag = substr(sha1($json),0,16);
        send_conditional_headers($etag, time());
        check_not_modified($etag, time());
        header('Cache-Control: max-age=60, must-revalidate');
        echo $json;
        exit;
    }

    $schema = toInternalSchema($eps);
    $payload = [
        'ok' => true,
        'data' => [ 'schema' => $schema ],
        'meta' => [ 'baseUrl' => app_base_url() ]   //  <-- NEU
    ];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    $etag = substr(sha1($json),0,16);
    send_conditional_headers($etag, time());
    check_not_modified($etag, time());
    header('Cache-Control: max-age=60, must-revalidate');
    echo $json;
} catch (Throwable $e) {
    json_error($e->getMessage(), 500);
}
