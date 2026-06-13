<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class PdfDownloader
{
    public static function download(string $url): string
    {
        $client = new Client();
        $response = $client->get($url, [
            RequestOptions::VERIFY => false,
            RequestOptions::STREAM => false,
        ]);
        $tmpPath = sys_get_temp_dir() . '/receipt_' . uniqid() . '.pdf';
        file_put_contents($tmpPath, (string) $response->getBody());
        return $tmpPath;
    }
}
