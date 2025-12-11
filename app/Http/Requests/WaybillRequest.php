<?php

namespace App\Http\Requests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class WaybillRequest extends FormRequest
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
            'order_id'=> ['required','exists:orders,id'],
            'name'=> ['required','string','min:3','max:25'],
            'plate'=>['required', 'string'],
            'description'=>['required', 'string','min:5','max:100'],
            'mobile'=> ['required','string','regex:/^09[0-9]{9}$/']
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
            'order_id.exists' => 'سفارش یافت نشد.',
            'mobile.required' => ' تلفن همراه الزامی است.',
            'mobile.regex' => 'فرمت تلفن همراه اشتباه است.',
            'mobile.string' => ' تلفن همراه باید رشته باشد.',
            'description.required'=>"توضیحات الزامی است",
            'description.string'=>"توضیحات باید رشته باشد",
            'description.min'=>"حداقل تعداد حروف توضیحات باید 5 عدد باشد",
            'description.max'=>"حداکثر تعداد حروف توضیحات باید 100 عدد باشد",
        ];
    }
}
