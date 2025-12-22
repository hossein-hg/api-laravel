<?php

namespace App\Http\Requests;

use App\Models\Admin\CompanyStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddFeatureRequest extends FormRequest
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
            'id' => ['required', 'exists:products,id'],

            'rows' => ['required', 'array', 'min:1'],

            'rows.*.color' => ['nullable', 'array'],
            'rows.*.color.name' => ['nullable', 'string', 'max:50'],
            'rows.*.color.code' => ['nullable', 'string', 'max:20'],

            'rows.*.size' => ['nullable', 'string', 'max:20'],
            'rows.*.brand' => ['required', 'array'],
            'rows.*.brand.name' => ['nullable', 'string', 'max:50'],
            'rows.*.brand.id' => ['nullable', 'exists:brands,id'],
            'rows.*.warranty' => ['nullable', 'string', 'max:20'],
            'rows.*.price' => ['required', 'string', 'max:20'],
            'rows.*.count' => ['required', 'integer', 'min:0'],
            'rows.*.accCode' => ['required', 'integer'],
        ];
    }


    public function messages(): array
    {
        return [
            'required' => ':attribute الزامی است.',
            'array' => ':attribute باید آرایه باشد.',
            'string' => ':attribute باید رشته باشد.',
            'integer' => ':attribute باید عدد باشد.',
            'min' => ':attribute نباید کمتر از :min باشد.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
            'exists' => ':attribute معتبر نیست.',
            'unique' => ':attribute  از قبل وجود دارد.',
        ];
    }

    // 👇 نام فارسی فیلدها
    public function attributes(): array
    {
        return [
            'id' => 'محصول',

            'rows' => 'ردیف‌ها',

            'rows.*.color' => 'رنگ',
            'rows.*.color.name' => 'نام رنگ',
            'rows.*.color.code' => 'کد رنگ',

            'rows.*.size' => 'سایز',
            'rows.*.brand' => 'برند',
            'rows.*.warranty' => 'گارانتی',
            'rows.*.price' => 'قیمت',
            'rows.*.count' => 'تعداد',
            'rows.*.accCode' => 'کد حسابداری',
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
}
