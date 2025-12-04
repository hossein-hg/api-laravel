<?php

namespace App\Http\Requests\Admin\Order;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UploadCheckRequest extends FormRequest
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
            'check_image' => ['image','mimes:jpeg,png,jpg,webp','max:5240','required_without:check_submit_image'],
            'check_submit_image' => ['image','mimes:jpeg,png,jpg,webp','max:5240','required_without:check_image'],
            'order_id'=> ['required','exists:orders,id'],
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
            'check_image.image' => 'نوع فایل باید image باشد.',
            'check_image.max' => 'حداکثر حجم فایل باید کمتر از 4 مگابایت باشد',
            'check_submit_image.max' => 'حداکثر حجم فایل باید کمتر از 4 مگابایت باشد',
            'check_image.required_without' => 'آپلود یک فایل الزامی است.',
            'check_submit_image.required_without' => 'آپلود یک فایل الزامی است.',
            'check_submit_image.image' => 'نوع فایل باید image باشد.',
            'check_image.mimes' => 'نوع فایل باید یکی از تایپ های png, webp, jpec, jpg باشد',
            'check_submit_image.mimes' =>'نوع فایل باید یکی از تایپ های png, webp, jpec, jpg باشد',
            'order_id.required'=> 'آیدی سفارش الزامی است.',
            'order_id.exists'=> 'چنین سفارشی وجود ندارد.'
        ];
    }
}
