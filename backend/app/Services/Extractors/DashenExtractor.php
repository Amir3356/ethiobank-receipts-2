<?php

namespace App\Services\Extractors;

use App\Services\PdfDownloader;
use Smalot\PdfParser\Parser;

class DashenExtractor implements ExtractorInterface
{
    public function extract(string $url): array
    {
        $pdfPath = PdfDownloader::download($url);
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $patterns = [
            'sender_name' => '/Account Holder Name:\s*(.+?)\n/',
            'channel' => '/Transaction Channel:\s*(.+?)\n/',
            'service_type' => '/Service Type:\s*(.+?)\n/',
            'narrative' => '/Narrative:\s*(.+?)\n/',
            'beneficiary_name' => '/Account Holder Name:\s*(.+?)\n/',
            'beneficiary_account' => '/Account Number:\s*(\d+)/',
            'beneficiary_bank' => '/Institution Name:\s*(.+?)\n/',
            'transfer_reference' => '/Transfer Reference:\s*(.+?)\n/',
            'transaction_reference' => '/Transaction Ref:\s*(.+?)\n/',
            'transaction_date' => '/Date:\s*(.+?)\n/',
            'amount' => '/Transaction Amount\s*([\d,.]+) ETB/',
            'total' => '/Total\s*([\d,.]+) ETB/',
            'amount_in_words' => '/Amount in words:\s*(.+?)\n/',
        ];

        $result = [];
        foreach ($patterns as $key => $pattern) {
            preg_match($pattern, $text, $matches);
            $result[$key] = $matches[1] ?? null;
            if (is_string($result[$key])) {
                $result[$key] = trim($result[$key]);
            }
        }

        if ($result['transaction_date']) {
            $timestamp = strtotime($result['transaction_date']);
            if ($timestamp !== false) {
                $result['transaction_date'] = date('c', $timestamp);
            }
        }

        @unlink($pdfPath);
        return $result;
    }
}
