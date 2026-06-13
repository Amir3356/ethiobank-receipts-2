<?php

namespace App\Console\Commands;

use App\Services\ExtractorService;
use App\Services\Extractors\CbeExtractor;
use App\Services\Extractors\UrlDetector;
use Illuminate\Console\Command;

class ExtractReceipt extends Command
{
    protected $signature = 'receipt:extract
        {bank? : Bank code or URL (auto-detected if omitted)}
        {url? : Receipt URL or ID}
        {--ft= : FT number for CBE}
        {--account= : Account number for CBE}
        {--reference= : Receipt reference}';

    protected $description = 'Extract receipt data from Ethiopian bank receipts';

    public function handle(ExtractorService $extractorService): int
    {
        $bank = $this->argument('bank');
        $url = $this->argument('url');
        $ft = $this->option('ft');
        $account = $this->option('account');
        $reference = $this->option('reference');

        try {
            if ($ft) {
                if (!$account) {
                    $this->error('--account is required when using --ft for CBE');
                    return Command::FAILURE;
                }
                $result = $extractorService->extractCbeFromFt($ft, $account);
            } elseif ($bank && $this->looksLikeUrl($bank)) {
                $detected = $extractorService->detectBank($bank);
                if (!$detected) {
                    $this->error('Could not auto-detect bank from URL. Please specify the bank.');
                    return Command::FAILURE;
                }
                $this->line("Auto-detected bank: {$detected}", 'fg=yellow');
                $result = $extractorService->extract($detected, $bank);
            } else {
                if (!$bank) {
                    $this->error('Bank or URL is required.');
                    return Command::FAILURE;
                }
                if (!$url && !$reference) {
                    $this->error('URL or reference is required.');
                    return Command::FAILURE;
                }
                $input = $url ?? $reference;
                $result = $extractorService->extract($bank, $input);
            }

            $normalized = $extractorService->normalize($result['bank'] ?? $bank ?? 'unknown', $result);

            foreach ($normalized as $key => $value) {
                $this->line("{$key}: " . ($value ?? 'N/A'));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function looksLikeUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }
}
