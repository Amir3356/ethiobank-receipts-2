<?php

namespace App\Services\Extractors;

interface ExtractorInterface
{
    public function extract(string $input): array;
}
