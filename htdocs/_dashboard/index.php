<?php
$dist = __DIR__ . '/dist/index.html';
if (file_exists($dist)) {
    readfile($dist);
    exit;
}
http_response_code(200);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>FT-XAMPP Dashboard (Vue)</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:32px;max-width:860px;margin:0 auto;}
    code{background:rgba(128,128,128,.15); padding:2px 6px; border-radius:6px;}
    pre{background:rgba(128,128,128,.12); padding:12px; border-radius:12px; overflow:auto;}
  </style>
</head>
<body>
  <h1>FT-XAMPP Dashboard (Vue)</h1>
  <p>Das Dashboard-Build wurde noch nicht erstellt.</p>
  <ol>
    <li>Wechsle ins Dashboard-Verzeichnis: <code>/htdocs/_dashboard/vue</code></li>
    <li>Installiere Abhängigkeiten: <code>npm i</code></li>
    <li>Baue das Frontend: <code>npm run build</code></li>
    <li>Lade neu: <code>http://localhost/</code></li>
  </ol>
  <p>API Test: <a href="/_dashboard/api/projects.php">/_dashboard/api/projects.php</a></p>
</body>
</html>