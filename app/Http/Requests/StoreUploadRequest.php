<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please choose a CSV file to upload.',
            'csv_file.mimes' => 'Only .csv files are accepted.',
            'csv_file.max' => 'The CSV file may not be larger than 10MB.',
        ];
    }
}
