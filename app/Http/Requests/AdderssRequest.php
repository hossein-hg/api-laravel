<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdderssRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code'=> 'required|regex:/^\d{10}$/',
            'user_id'=> 'required|exists:users,id',
            'province'=> 'required|string|max:100',
            'city'=> 'required|string|max:100',
            'address'=> 'required|string|max:500',
            'phone'=> 'required|regex:/^0\d{2,3}-?\d{7}$/',
        ];
    }
}
