<?php

/**
 * Given a csv of urls, hit the url and scrape the data.
 * 
 * Format of file:
 * [SCHOOL_NAME],[URL]
 */

use GuzzleHttp\Client;
use PHPHtmlParser\Dom;

set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/vendor/autoload.php');

$file = 'school_url.csv';
$client = new Client(['timeout' => 30]);
$allData = [];
$fp = fopen(__DIR__ . '/schools.json', 'w');

if (($handle = fopen(__DIR__ . '/' . $file, 'r')) !== false) {
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $school = [];
        $name = $data[0];
        $url = $data[1];

        if (empty($url)) {
            continue;
        }

        $response = $client->request('GET', $url);

        $dom = new Dom;
        $dom->loadStr($response->getBody(), []);

        $school = [
            'name' => $name
        ];

        if (preg_match('/\((\d+)\)/', $dom->find('.page-header')->innerHtml, $match)) {
            $school['id'] = $match[1];
        }

        $school['address'] = trim($dom->find('.page-header .small')->text);
        $school['students'] = 0;

        if (substr_count($dom->find('.text-left')->innerHtml, 'text-info') === 4) {
            preg_match_all('/class\=\"text-info\"\>(\d+)\</', $dom->find('.text-left')->innerHtml, $match);

            $school['students'] = (int) $match[1][1] + (int) $match[1][2];
        }

        foreach ($dom->find('.box-profile .list-group-item') as $content) {
            if (strpos($content->innerHtml, 'glyphicon-user') !== false) {
                $school['head_teacher'] = trim(str_replace('<i class="glyphicon glyphicon-user"></i>&nbsp;&nbsp;Kepala Sekolah :', '', $content->innerHtml));
            }
        }

        foreach ($dom->find('.#siswaagama td') as $idx => $content) {
            if ($idx < 4) {
                continue;
            }

            if ($idx % 2 === 0) {
                $religion = $content->text;
            } else {
                $school[$religion] = (int) $content->text;
            }
        }

        $allData[] = $school;
    }

    fwrite($fp, json_encode($allData, JSON_PRETTY_PRINT));

    fclose($handle);
}
