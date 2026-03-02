<?php
header('Content-Type: application/json');
header('Cache-Control: public, max-age=86400'); // Cache 24h

$q = trim($_GET['q'] ?? '');
if (!$q) { echo '[]'; exit; }

$url = 'https://nominatim.openstreetmap.org/search?q=' . urlencode($q) . '&format=json&limit=1&accept-language=fr';

$ctx = stream_context_create(['http' => [
    'method'  => 'GET',
    'header'  => "User-Agent: Yokoso/1.0 (localhost)\r\nAccept-Language: fr\r\n",
    'timeout' => 5,
]]);

$result = @file_get_contents($url, false, $ctx);
echo $result !== false ? $result : '[]';
