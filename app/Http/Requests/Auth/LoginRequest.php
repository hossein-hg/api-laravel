<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class LoginRequest extends FormRequest
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
                
                'phone' => 'required|string|regex:/^09[0-9]{9}$/',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false, 
            'message' => ' خطا اعتبارسنجی!',
            'statusCode' => 422,  
            'errors' => $validator->errors(),
            'data' => null 
        ], 422));
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره تلفن  الزامی است.',
            'phone.regex' => 'فرمت شماره اشتباه است.',            
        ];
    }
}
