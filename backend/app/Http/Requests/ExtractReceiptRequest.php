<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtractReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank' => 'required|string|in:auto,cbe,dashen,awash,boa,zemen,tele,mpesa,cbe_birr',
            'url' => 'sometimes|string',
            'reference' => 'sometimes|string',
            'account' => 'sometimes|string',
            'phone' => 'sometimes|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->validated();
            $hasUrl = !empty($data['url']);
            $hasRef = !empty($data['reference']);

            if (!$hasUrl && !$hasRef) {
                $validator->errors()->add('url', 'Either URL or reference is required.');
            }
        });
    }
}
