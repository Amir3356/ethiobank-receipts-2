<?php

namespace App\Services\Extractors;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class BoaExtractor implements ExtractorInterface
{
    public function extract(string $url): array
    {
        $client = new Client();
        $response = $client->get($url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);
        $html = (string) $response->getBody();

        $crawler = new Crawler($html);
        $data = [];

        $crawler->filter('table tr')->each(function (Crawler $row) use (&$data) {
            $cells = $row->filter('td');
            if ($cells->count() === 2) {
                $key = trim($cells->eq(0)->text(), ": \t\n\r\0\x0B");
                $value = trim($cells->eq(1)->text());
                $data[$key] = $value;
            }
        });

        return [
            'Source Account' => $data['Source Account'] ?? null,
            'Source Account Name' => $data['Source Account Name'] ?? null,
            "Receiver's Account" => $data["Receiver's Account"] ?? null,
            "Receiver's Name" => $data["Receiver's Name"] ?? null,
            'Transferred Amount' => $data['Transferred amount'] ?? null,
            'Service Charge' => $data['Service Charge'] ?? null,
            'VAT' => $data['VAT (15%)'] ?? null,
            'Total Amount' => $data['Total Amount'] ?? null,
            'Transaction Type' => $data['Transaction Type'] ?? null,
            'Transaction Date' => $data['Transaction Date'] ?? null,
            'Transaction Reference' => $data['Transaction Reference'] ?? null,
            'Narrative' => $data['Narrative'] ?? null,
        ];
    }
}
