<?php

namespace App\Services\Extractors;

use App\Services\PdfDownloader;
use Smalot\PdfParser\Parser;

class CbeExtractor implements ExtractorInterface
{
    public function extract(string $url): array
    {
        $pdfPath = PdfDownloader::download($url);
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $patterns = [
            'customer_name' => '/Customer Name[:\s]*([A-Za-z\s.]+)/',
            'branch' => '/Branch[:\s]*([A-Za-z\s.]+)/',
            'region_city' => '/Region[:\s]*([A-Za-z\s.]+)/',
            'payment_date' => '/Payment Date\s*(?:&|and)?\s*Time[:\s]*([\d\/,:APMapm\s]+)/i',
            'reference_no' => '/Reference\s*No[:\s]*(FT[A-Z0-9]+)/i',
            'payer' => '/Payer[:\s]*([A-Za-z\s.]+?)(?=\s*\nAccount|\s*$)/m',
            'payer_account' => '/Payer[:\s]*[A-Za-z\s.]+?[:\s]*\n?Account[:\s]*([\d*]+)/',
            'receiver' => '/Receiver[:\s]*([A-Za-z\s.]+?)(?=\s*\nAccount|\s*$)/m',
            'receiver_account' => '/Receiver[:\s]*[A-Za-z\s.]+?[:\s]*\n?Account[:\s]*([\d*]+)/',
            'service' => '/Reason\s*\/?\s*Type of service[:\s]*([A-Za-z\s]+)/i',
            'transferred_amount' => '/Transferred Amount[:\s]*([\d,.]+)\s*ETB/i',
            'commission' => '/Commission or Service Charge[:\s]*([\d,.]+)\s*ETB/i',
            'vat_on_commission' => '/15%\s*VAT on Commission[:\s]*([\d,.]+)\s*ETB/i',
            'total_debited' => '/Total amount debited from customers account[:\s]*([\d,.]+)\s*ETB/i',
            'amount_in_words' => '/Amount in Word\s*ETB[:\s]*([A-Za-z\s\/\d\-]+)/i',
        ];

        $result = [];
        foreach ($patterns as $key => $pattern) {
            preg_match($pattern, $text, $matches);
            $result[$key] = $matches[1] ?? null;
            if (is_string($result[$key])) {
                $result[$key] = trim($result[$key]);
            }
        }

        if ($result['payment_date']) {
            $timestamp = strtotime($result['payment_date']);
            if ($timestamp !== false) {
                $result['payment_date'] = date('c', $timestamp);
            }
        }

        @unlink($pdfPath);
        return $result;
    }

    public static function extractFromFt(string $ftNumber, string $accountLast8OrFull): array
    {
        $ft = strtoupper(preg_replace('/\s+/', '', $ftNumber));
        $digits = preg_replace('/\D/', '', $accountLast8OrFull);

        if (strlen($digits) < 8) {
            throw new \InvalidArgumentException('Account number must contain at least 8 digits');
        }

        $last8 = substr($digits, -8);
        $url = "https://apps.cbe.com.et:100/?id={$ft}{$last8}";

        return (new self())->extract($url);
    }
}
