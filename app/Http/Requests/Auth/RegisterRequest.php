<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class RegisterRequest extends FormRequest
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
            'name' => 'required|string',
            'phone' => 'required|string|unique:users|regex:/^09[0-9]{9}$/',
            'gender' => 'required|in:0,1,2',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'خطا اعتبارسنجی !', 
            'statusCode' => 422,  
            'errors' => $validator->errors(), 
            'data' => null 
        ], 422));
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره تلفن  الزامی است.', 
            'phone.unique' => 'شماره تلفن از قبل وجود دارد!.', 
            'name.required' => 'نام  الزامی است.', 
            'gender.required' => 'جنسیت  الزامی است.', 
            'name.string' => 'نام باید رشته باشد.',
            'gender.in' => 'جنسیت باید مرد، زن یا سایر باشد.',
            'phone.regex' => 'فرمت شماره اشتباه است.',

        ];
    }
}
