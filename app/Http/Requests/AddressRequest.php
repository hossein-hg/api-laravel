<?php

namespace App\Http\Requests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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
            'firstName'=> ['required','string','min:3','max:25'],
            'lastName'=> ['required', 'string','min:3','max:25'],
            'province'=> ['required', 'string','min:2'],
            'city'=> ['required', 'string','min:2'],
            'address'=> ['required', 'string','min:5','max:100'],
            'mobile'=> ['required', 'string','regex:/^09[0-9]{9}$/'],
            'phone'=> ['required', 'string', 'regex:/^0\d{2}\d{8}$/'],
            'postalCode'=> ['required', 'string','regex:/^[0-9]{10}$/'],
            'email'=> ['required', 'email'],
            'description'=> ['nullable', 'string','min:5','max:100'],
        ];
    }


    public function messages(): array
    {
        return [
            'firstName.required' => 'نام الزامی است.',
            'firstName.string' => 'نام باید رشته باشد.',
            'firstName.min' => 'تعداد حروف  نام باید حداقل سه عدد باشد.',
            'firstName.max' => 'تعداد حروف  نام باید حداکثر 25 عدد باشد.',
            'lastName.required' => 'نام خانوادگی الزامی است.',
            'lastName.string' => 'نام خانوادگی باید رشته باشد.',
            'lastName.min' => 'تعداد حروف  نام خانوادگی باید حداقل سه عدد باشد.',
            'lastName.max' => 'تعداد حروف  نام خانوادگی باید حداکثر 25 عدد باشد.',
            'province.required' => 'استان الزامی است.',
            'province.string' => 'استان باید رشته باشد.',
            'province.min' => 'تعداد حروف  استان باید حداقل دو عدد باشد.',
            'city.required' => 'شهر الزامی است.',
            'city.string' => 'شهر باید رشته باشد.',
            'city.min' => 'تعداد حروف  شهر باید حداقل دو عدد باشد.',
            'address.required' => 'آدرس الزامی است.',
            'address.string' => 'آدرس باید رشته باشد.',
            'address.min' => 'تعداد حروف  آدرس باید حداقل پنج عدد باشد.',
            'address.max' => 'تعداد حروف  نام باید حداکثر 100 عدد باشد.',
            'phone.required' => ' تلفن ثابت الزامی است!.',
            'phone.regex' => 'فرمت  تلفن ثابت اشتباه است.',
            'phone.string' => '  تلفن ثابت باید رشته باشد.',
            'mobile.required' => ' تلفن همراه الزامی است!.',
            'mobile.regex' => 'فرمت  همراه ثابت اشتباه است.',
            'mobile.string' => '  تلفن همراه باید رشته باشد.',
            'postalCode.required' => ' کد پستی  الزامی است!.',
            'postalCode.regex' => 'کد پستی ثابت اشتباه است.',
            'postalCode.string' => ' کد پستی باید رشته باشد.',
            'email.required'=> 'ایمیل الزامی است',
            'email.email'=> 'فرمت ایمیل صحیح نیست',
            'description.string' => 'ایمیل باید رشته باشد.',
            'description.min' => 'تعداد حروف  ایمیل باید حداقل 5 عدد باشد.',
            'description.max' => 'تعداد حروف  ایمیل باید حداکثر 100 عدد باشد.',

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
}
