<?php

namespace App\Services\Extractors;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class AwashExtractor implements ExtractorInterface
{
    public function extract(string $url): array
    {
        $client = new Client();
        $response = $client->get($url);
        $html = (string) $response->getBody();

        $crawler = new Crawler($html);
        $data = [];

        $crawler->filter('table.info-table tr')->each(function (Crawler $row) use (&$data) {
            $cells = $row->filter('td');
            if ($cells->count() === 3) {
                $key = trim($cells->eq(0)->text(), ": \t\n\r\0\x0B");
                $value = trim($cells->eq(2)->text());
                $data[$key] = $value;
            }
        });

        $keysOfInterest = [
            'Transaction Time',
            'Transaction Type',
            'Amount',
            'Charge',
            'VAT',
            'Sender Name',
            'Sender Account',
            'Beneficiary name',
            'Beneficiary Account',
            'Beneficiary Bank',
            'Reason',
            'Transaction ID',
        ];

        $result = [];
        foreach ($keysOfInterest as $k) {
            $result[$k] = $data[$k] ?? null;
        }

        return $result;
    }
}
