<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class AddUserRequest extends FormRequest
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
        
        if ($this->method() === "POST") {
            return [
                "name"=> ["required","string",'min:3','max:25'],
                "phone"=> ["required","string","regex:/^09[0-9]{9}$/", Rule::unique('users', 'phone')],
                "telephone"=> ["string","regex:/^0\d{2,3}-?\d{7}$/",'required_if:user_type,legal','nullable'],
                "gender"=> ["required","in:0,1,2"],
                "category_id"=> ["required","exists:user_categories,id"],
                "user_type"=> ["required","string", "in:regular,legal"],
                "company_name"=> ["required_if:user_type,legal","string",'nullable'],
                "national_code"=> ["required_if:user_type,legal","string",'nullable','max:10'],
                "economic_code"=> ["required_if:user_type,legal","string",'nullable','max:10'],
                "registration_number"=> ["required_if:user_type,legal","string",'nullable','max:7'],
                "is_active"=> ["boolean"],
                
            ];
        }
        else{
            
            return [
                "name" => ["required", "string", 'min:3', 'max:25'],
                "phone" => ["required", "string", "regex:/^09[0-9]{9}$/", Rule::unique('users', 'phone')->ignore($this->id, 'id')],
                "telephone" => ['nullable',"string", "regex:/^0\d{2,3}-?\d{7}$/",'required_if:user_type,legal'],
                "gender" => ["required", "in:0,1,2"],
                "category_id" => ["required", "exists:user_categories,id"],
                "user_type" => ["required", "string", "in:regular,legal"],
                "company_name" => ["required_if:user_type,legal", "string",'nullable'],
                "national_code" => ["required_if:user_type,legal", "string",'nullable','max:10'],
                "economic_code" => ["required_if:user_type,legal", "string",'nullable','max:10'],
                "registration_number" => ["required_if:user_type,legal", "string",'nullable','max:7'],
                "is_active" => ["boolean"],

            ];
        }
       
        
    }


    public function messages(): array
    {
        return [
            'name.required' => 'نام الزامی است.',
            'name.string' => 'نام باید رشته باشد.',
            'name.min' => 'تعداد حروف  نام باید حداقل سه عدد باشد.',
            'name.max' => 'تعداد حروف  نام باید حداکثر 25 عدد باشد.',
            'category_id.required'=> 'دسته بندی الزامی است',
            'category_id.exists'=> 'دسته بندی وجود ندارد',
            
            'phone.required' => ' شماره  الزامی است!.',
            'phone.regex' => 'فرمت  شماره  اشتباه است.',
            'phone.string' => '  شماره  باید رشته باشد.',
            'phone.unique' => 'این شماره از قبل ثبت شده است.',
            'user_type.required'=> 'نوع کاربر الزامی است',
            'user_type.in'=> 'نوع کاربر  باید حقیقی یا حقوقی باشد',

            'company_name.required_if'=> 'نام شرکت الزامی است',
            'national_code.required_if'=> 'شمتاسه ملی  الزامی است',
            'national_code.max'=> 'شناسه ملی حداکثر باید 10 رقم باشد ',
            'economic_code.required_if'=> ' کد اقتصادی  الزامی است',
            'economic_code.max'=> ' کد اقتصادی حداکثر باید 10 رقم باشد ',
            'registration_number.required_if'=> ' شناسه ثبت الزامی است',
            'registration_number.max'=> 'شناسه ثبت حداکثر باید 7 رقم باشد ',

           

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
