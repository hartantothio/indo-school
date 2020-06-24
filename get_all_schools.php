<?php

/**
 * Get all schools with the urls
 */

use GuzzleHttp\Client;
use PHPHtmlParser\Dom;

set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/vendor/autoload.php');

$url = 'http://sekolah.data.kemdikbud.go.id/chome/pagingpencarian';

$params = [
    'status' => 'semua',
    'akreditasi' => 'semua',
];

$client = new Client();
$fileAppender = 0;

$fileAppender = 251;
$page = 1;

while (true) {
    if ($page === 1 || $page % 250 === 0) {
        $fileAppender++;
        $fp = fopen('schools' . $fileAppender . '.csv', 'w');
    }


    $res = $client->request('POST', $url, [
        'form_params' => array_merge($params, ['page' => $page]),
    ]);

    $response = $res->getBody();

    $dom = new Dom;
    $dom->loadStr($res->getBody(), []);

    foreach ($dom->find('a.text-info') as $content) {
        fputcsv($fp, [
            'name' => trim(substr($content->text, 3)),
            'url' => 'http://sekolah.data.kemdikbud.go.id' . $content->getAttribute('href'),
        ]);
    }

    if ($dom->outerHtml === '<div class="row"> </div>') {
        break;
    }

    $page++;
}

fclose($fp);
