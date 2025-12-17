<?php

namespace App\Http\Requests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
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
            "name" => ["required", "string", "max:50", "min:3"],
            "parent_id" => ['nullable', 'exists:groups,id'],
            'brands' => ['nullable', 'array'],
            'brands.*.fa_name' => ['nullable', 'string', 'max:50'],
            'brands.*.en_name' => ['nullable', 'string', 'max:50'],
            'image' => ['required','string'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => ' خطا اعتبارسنجی!',
            'statusCode' => 422,
            'errors' => [$validator->errors()->first()],
            'data' => null
        ], 422));
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام الزامی است.',
            'name.string' => 'نام باید رشته باشد.',
            'name.min' => 'تعداد حروف  نام باید حداقل سه عدد باشد.',
            'name.max' => 'تعداد حروف  نام باید حداکثر 25 عدد باشد.',
            
            'image.string' => 'تصویر باید رشته باشد',


        ];
    }
}
