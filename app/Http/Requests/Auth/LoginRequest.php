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
                
                'phone' => 'required|string|max:11',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,  // فیلد custom
            'message' => 'Validation failed!',  // پیام custom
            'statusCode' => 422,  // اضافه کردن کد وضعیت
            'errors' => $validator->errors(),  // errors اصلی رو نگه دار
            'data' => null  // اگر بخوای data خالی اضافه کنی
        ], 422));
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره تلفن  الزامی است.',  // فارسی یا هر زبانی
           
        ];
    }
}
