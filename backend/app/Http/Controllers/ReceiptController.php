<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExtractReceiptRequest;
use App\Services\ExtractorService;
use Illuminate\Http\JsonResponse;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly ExtractorService $extractorService
    ) {}

    public function banks(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->extractorService->getSupportedBanks(),
        ]);
    }

    public function extract(ExtractReceiptRequest $request): JsonResponse
    {
        try {
            $bank = strtolower($request->input('bank'));
            $url = $request->input('url');
            $reference = $request->input('reference');
            $account = $request->input('account');
            $phone = $request->input('phone');

            if ($bank === 'auto') {
                $input = $url ?? $reference;
                $detected = $this->extractorService->detectBank($input);
                if (!$detected) {
                    return response()->json([
                        'error' => 'Could not auto-detect bank from URL. Please specify the bank manually.',
                    ], 400);
                }
                $bank = $detected;
            }

            $data = null;

            if ($bank === 'cbe' && $reference && $account) {
                $data = $this->extractorService->extractCbeFromFt($reference, $account);
            } elseif ($bank === 'mpesa') {
                $data = ['reference' => $reference];
            } elseif ($bank === 'cbe_birr') {
                $data = ['reference' => $reference, 'phone' => $phone];
            } else {
                $extractUrl = $bank === 'tele' ? ($reference ?? $url) : $url;
                $data = $this->extractorService->extract($bank, $extractUrl);
            }

            $normalized = $this->extractorService->normalize($bank, $data);

            return response()->json([
                'success' => true,
                'data' => array_merge(['bank' => $bank], $normalized),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
