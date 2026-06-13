<?php

namespace App\Services\Extractors;

use App\Services\PdfDownloader;
use Smalot\PdfParser\Parser;

class ZemenExtractor implements ExtractorInterface
{
    public function extract(string $url): array
    {
        try {
            $pdfPath = PdfDownloader::download($url);
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = str_replace("\n", ' ', $pdf->getText());

            $patterns = [
                'Invoice No' => '/Invoice No\.?:\s*(\d+)/',
                'Date' => '/Date[:\s]+([0-9]{1,2}-[A-Za-z]{3}-[0-9]{4})/',
                'Payer Name' => '/Payer name:\s*([A-Z\s]+)/',
                'Payer Account No' => '/Payer account no\.?:\s*([\d*()X]+)/',
                'Recipient Name' => '/Recipient name:\s*([A-Za-z\s.]+)/',
                'Recipient Account No' => '/Recipient account no\.?:\s*([\d*]+)/',
                'Reference No' => '/Reference No:\s*([A-Z0-9]+)/',
                'Transaction Status' => '/Transaction status:\s*(\w+)/',
                'Transaction Detail' => '/Transaction Detail\s+([A-Za-z\s\-]+)\s+ETB/',
                'Settled Amount' => '/ATM CASH WITHDRAWAL ETB\s*([\d,]+\.\d{2})/',
                'Service Charge' => '/Service Charge ETB\s*([\d,]+\.\d{2})/',
                'VAT' => '/VAT 15% ETB\s*([\d,]+\.\d{2})/',
                'Total Amount Paid' => '/Total Amount Paid ETB\s*([\d,]+\.\d{2})/',
                'Amount in Words' => '/Total amount in word:\s*([A-Z\s\-]+CENT\(S\))/',
            ];

            $result = [];
            foreach ($patterns as $field => $pattern) {
                preg_match($pattern, $text, $matches);
                if (isset($matches[1])) {
                    $value = trim($matches[1]);
                    if (str_contains($field, 'Amount') || str_contains($field, 'Charge') || str_contains($field, 'VAT')) {
                        $value = "ETB {$value}";
                    }
                    $result[$field] = $value;
                }
            }

            @unlink($pdfPath);
            return $result;
        } catch (\Exception $e) {
            report($e);
            return [];
        }
    }
}
