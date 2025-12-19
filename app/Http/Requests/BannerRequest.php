<?php

namespace App\Http\Requests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:100'],
            'link' => ['required','string','max:200'],
            'type' => ['required','integer'],
            'showTime' => ['required','integer'],
            'image' => ['required','string'],
            
            
            

            
        ];
    }


    public function messages(): array
    {
        return [
            'required' => ':attribute الزامی است.',
            
            'string' => ':attribute باید رشته باشد.',
            'integer' => ':attribute باید عدد باشد.',
            'min' => ':attribute نباید کمتر از :min باشد.',
            'max' => ':attribute نباید بیشتر از :max کاراکتر باشد.',
           
        ];
    }

    // 👇 نام فارسی فیلدها
    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'link' => 'لینک',
            'type' => 'تایپ',
            'showTime' => 'نوبت نمایش',
            'image' => 'عکس',
            

           
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
