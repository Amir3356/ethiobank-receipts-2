<?php

namespace App\Services;

use App\Services\Extractors\AwashExtractor;
use App\Services\Extractors\BoaExtractor;
use App\Services\Extractors\CbeExtractor;
use App\Services\Extractors\DashenExtractor;
use App\Services\Extractors\ExtractorInterface;
use App\Services\Extractors\TeleExtractor;
use App\Services\Extractors\UrlDetector;
use App\Services\Extractors\ZemenExtractor;

class ExtractorService
{
    private const SUPPORTED_BANKS = ['cbe', 'dashen', 'awash', 'boa', 'zemen', 'tele', 'mpesa', 'cbe_birr'];

    private array $extractors;

    public function __construct()
    {
        $this->extractors = [
            'cbe' => app(CbeExtractor::class),
            'dashen' => app(DashenExtractor::class),
            'awash' => app(AwashExtractor::class),
            'boa' => app(BoaExtractor::class),
            'zemen' => app(ZemenExtractor::class),
            'tele' => app(TeleExtractor::class),
        ];
    }

    public function getSupportedBanks(): array
    {
        return self::SUPPORTED_BANKS;
    }

    public function getExtractor(string $bank): ?ExtractorInterface
    {
        return $this->extractors[$bank] ?? null;
    }

    public function detectBank(string $input): ?string
    {
        return UrlDetector::detect($input);
    }

    public function extract(string $bank, string $url): array
    {
        $bank = strtolower($bank);

        if ($bank === 'auto') {
            $bank = $this->detectBank($url);
            if (!$bank) {
                throw new \RuntimeException('Could not auto-detect bank from URL. Please specify the bank manually.');
            }
        }

        if ($bank === 'mpesa') {
            return ['message' => 'M-Pesa verification - receipt reference received'];
        }

        if ($bank === 'cbe_birr') {
            return ['message' => 'CBE Birr verification - receipt and phone received'];
        }

        $extractor = $this->getExtractor($bank);
        if (!$extractor) {
            throw new \InvalidArgumentException("Unsupported bank. Supported: " . implode(', ', self::SUPPORTED_BANKS));
        }

        $input = $bank === 'tele' ? $url : $url;
        return $extractor->extract($input);
    }

    public function extractCbeFromFt(string $ftNumber, string $account): array
    {
        return CbeExtractor::extractFromFt($ftNumber, $account);
    }

    public function normalize(string $bankCode, ?array $raw): array
    {
        $base = [
            'payer_name' => null,
            'payer_account' => null,
            'receiver_name' => null,
            'receiver_account' => null,
            'amount' => null,
            'currency' => 'ETB',
            'date' => null,
            'reference' => null,
            'status' => 'SUCCESS',
        ];

        if (empty($raw)) return $base;

        return match ($bankCode) {
            'cbe' => array_merge($base, [
                'payer_name' => $raw['payer'] ?? null,
                'payer_account' => $raw['payer_account'] ?? null,
                'receiver_name' => $raw['receiver'] ?? null,
                'receiver_account' => $raw['receiver_account'] ?? null,
                'amount' => isset($raw['transferred_amount']) ? (float) str_replace(',', '', $raw['transferred_amount']) : null,
                'date' => $raw['payment_date'] ?? null,
                'reference' => $raw['reference_no'] ?? null,
            ]),
            'dashen' => array_merge($base, [
                'payer_name' => $raw['sender_name'] ?? null,
                'receiver_name' => $raw['beneficiary_name'] ?? null,
                'receiver_account' => $raw['beneficiary_account'] ?? null,
                'amount' => isset($raw['amount']) ? (float) str_replace(',', '', $raw['amount']) : null,
                'date' => $raw['transaction_date'] ?? null,
                'reference' => $raw['transfer_reference'] ?? $raw['transaction_reference'] ?? null,
            ]),
            'awash' => array_merge($base, [
                'payer_name' => $raw['Sender Name'] ?? null,
                'payer_account' => $raw['Sender Account'] ?? null,
                'receiver_name' => $raw['Beneficiary name'] ?? null,
                'receiver_account' => $raw['Beneficiary Account'] ?? null,
                'amount' => isset($raw['Amount']) ? (float) str_replace(',', '', $raw['Amount']) : null,
                'date' => $raw['Transaction Time'] ?? null,
                'reference' => $raw['Transaction ID'] ?? null,
            ]),
            'boa' => array_merge($base, [
                'payer_name' => $raw['Source Account Name'] ?? null,
                'payer_account' => $raw['Source Account'] ?? null,
                'receiver_name' => $raw["Receiver's Name"] ?? null,
                'receiver_account' => $raw["Receiver's Account"] ?? null,
                'amount' => isset($raw['Transferred Amount']) ? (float) str_replace(',', '', $raw['Transferred Amount']) : null,
                'date' => $raw['Transaction Date'] ?? null,
                'reference' => $raw['Transaction Reference'] ?? null,
            ]),
            'zemen' => array_merge($base, [
                'payer_name' => $raw['Payer Name'] ?? null,
                'payer_account' => $raw['Payer Account No'] ?? null,
                'receiver_name' => $raw['Recipient Name'] ?? null,
                'receiver_account' => $raw['Recipient Account No'] ?? null,
                'amount' => isset($raw['Settled Amount']) ? (float) str_replace(',', '', str_replace('ETB ', '', $raw['Settled Amount'])) : null,
                'date' => $raw['Date'] ?? null,
                'reference' => $raw['Reference No'] ?? $raw['Invoice No'] ?? null,
                'status' => $raw['Transaction Status'] ?? 'SUCCESS',
            ]),
            'tele' => array_merge($base, [
                'payer_name' => $raw['payer_name'] ?? null,
                'payer_account' => $raw['payer_number'] ?? null,
                'receiver_name' => $raw['credited_party'] ?? null,
                'receiver_account' => $raw['credited_party_number'] ?? null,
                'amount' => isset($raw['total_paid']) ? (float) str_replace(',', '', $raw['total_paid']) : null,
                'status' => $raw['status'] ?? 'SUCCESS',
            ]),
            default => array_merge($base, $raw ?? []),
        };
    }
}
