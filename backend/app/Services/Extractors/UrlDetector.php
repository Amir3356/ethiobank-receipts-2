<?php

namespace App\Services\Extractors;

class UrlDetector
{
    private const DOMAIN_MAP = [
        'apps.cbe.com.et' => 'cbe',
        'transactioninfo.ethiotelecom.et' => 'tele',
    ];

    public static function detect(string $url): ?string
    {
        if (empty($url)) return null;

        foreach (self::DOMAIN_MAP as $domain => $bank) {
            if (str_contains($url, $domain)) return $bank;
        }

        return null;
    }
}
