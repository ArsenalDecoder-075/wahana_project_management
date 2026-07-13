<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'media_*' => 'nullable|file|max:51200', // 50MB
            'text_*' => 'nullable|string',
            'link_*' => 'nullable|url',
        ];
    }

    public function messages()
    {
        return [
            'media_*.max' => 'Ukuran file maksimal 50MB.',
            'media_*.file' => 'File yang diupload tidak valid.',
            'link_*.url' => 'Format link tidak valid.',
        ];
    }
}