<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStyleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'style.color'        => ['required','regex:/^#([0-9A-Fa-f]{6})$/'],
            'style.fontSize'     => ['required','integer','min:10','max:72'],
            'style.background'   => ['nullable','regex:/^#([0-9A-Fa-f]{6})$/'],
            'style.fontStyle'    => ['nullable','in:normal,bold,italic,bolditalic'],
        ];
    }
}
