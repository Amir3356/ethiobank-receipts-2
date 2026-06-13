<?php

namespace App\Services\Extractors;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class TeleExtractor implements ExtractorInterface
{
    public function extract(string $urlOrId): array
    {
        if (empty($urlOrId)) {
            throw new \InvalidArgumentException('Telebirr receipt id or URL is required');
        }

        $url = str_starts_with($urlOrId, 'http')
            ? $urlOrId
            : "https://transactioninfo.ethiotelecom.et/receipt/{$urlOrId}";

        $client = new Client();
        $response = $client->get($url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);
        $html = (string) $response->getBody();

        $crawler = new Crawler($html);
        $data = [];

        $rows = $crawler->filter('table tr');
        $rows->each(function (Crawler $row) use (&$data) {
            $cells = $row->filter('td');
            if ($cells->count() < 2) return;
            $label = trim($cells->eq(0)->text());
            $value = trim($cells->eq(1)->text());

            if (preg_match('/Payer\s+Name/i', $label)) {
                $data['payer_name'] = $value;
            } elseif (preg_match('/Payer\s+telebirr/i', $label)) {
                $data['payer_number'] = $value;
            } elseif (preg_match('/Credited\s+Party\s+name/i', $label)) {
                $data['credited_party'] = $value;
            } elseif (preg_match('/Credited\s+party\s+account\s+no/i', $label)) {
                $data['credited_party_number'] = $value;
            } elseif (preg_match('/transaction\s+status/i', $label)) {
                $data['status'] = $value;
            } elseif (preg_match('/Total\s+Paid\s+Amount/i', $label)) {
                $data['total_paid'] = $value;
            }
        });

        if (empty($data)) {
            $crawler->filter('td, th, span, div, p')->each(function (Crawler $el) use (&$data) {
                $text = trim($el->text());
                if (empty($text)) return;

                $label = null;
                if (preg_match('/Payer\s*Name/i', $text)) $label = 'payer_name';
                elseif (preg_match('/Payer\s*telebirr/i', $text)) $label = 'payer_number';
                elseif (preg_match('/Credited\s*Party\s*name/i', $text)) $label = 'credited_party';
                elseif (preg_match('/Credited\s*party\s*account\s*no/i', $text)) $label = 'credited_party_number';
                elseif (preg_match('/transaction\s*status/i', $text)) $label = 'status';
                elseif (preg_match('/Total\s*Paid\s*Amount/i', $text)) $label = 'total_paid';

                if ($label) {
                    $next = $el->nextAll()->first();
                    if ($next->count()) {
                        $data[$label] = trim($next->text());
                    }
                }
            });
        }

        return $data;
    }
}
