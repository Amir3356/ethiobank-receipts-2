<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/receipts/banks', [ReceiptController::class, 'banks']);
Route::post('/receipts/extract', [ReceiptController::class, 'extract']);
