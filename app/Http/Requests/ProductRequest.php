<?php

namespace App\Http\Requests;

use App\Models\Admin\Product;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductRequest extends FormRequest
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
                "name"=> ['required','string','min:3','max:100', Rule::unique('products', 'name')],
                "en_name"=> ['required','string','min:3','max:100', Rule::unique('products', 'en_name')],
                'subCategory_id'=> ['required','integer','exists:groups,id'],
                'price'=> ['integer'],
                'description'=> ['required','string', 'min:5'],
                'inventory' => ['required_if:type,false','boolean'],
                'ratio' => ['required','integer'],
                'warehouseInventory' => ['required_if:type,false','integer'],
                'cover' => ['required','string'],
                'type' => ['required','boolean'],
                'images' => ['nullable','array'],
                'tags'=> ['nullable','array'],
                'shortDescription'=> ['required','string','max:500','min:5'],
                'additionalInformation'=> ['nullable','string','max:500','min:5'],
                'discount'=> ['nullable','integer'],
                'discount_end_time'=> ['required_with:discount'],
                'discount_start_time'=> ['required_with:discount'],
                'max_sell' => ['required', 'integer'],
                'accCode'=> ['required','unique:products,accCode']
            ];
        }
        else{
          
            return [
                "id"=> ['required','exists:products,id'],
                "name" => ['required', 'string', 'min:3', 'max:100', Rule::unique('products', 'name')->ignore($this->id, 'id')->whereNull('deleted_at')],
                "en_name" => ['required', 'string', 'min:3', 'max:100', Rule::unique('products', 'en_name')->ignore($this->id, 'id')->whereNull('deleted_at')],
                'subCategory_id' => ['required', 'integer', 'exists:groups,id'],
                'price' => ['integer'],
                'description' => ['required', 'string', 'min:5'],
                'inventory' => ['required_if:type,false','boolean'],
                'ratio' => ['required', 'integer'],
                'warehouseInventory' => ['required_if:type,false', 'integer'],
                'cover' => ['required','string'],
                'type' => ['required','boolean'],
                'images' => ['nullable', 'array'],
                'tags' => ['nullable', 'array'],
                'shortDescription' => ['required', 'string', 'max:500', 'min:5'],
                'additionalInformation' => ['nullable', 'string', 'max:500', 'min:5'],
                'discount' => ['nullable','integer'],
                'discount_end_time' => ['required_with:discount'],
                'discount_start_time' => ['required_with:discount'],
                'max_sell'=> ['required','integer'],
                'accCode'=> ['required','unique:products,accCode']

            ];
        }
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
            'name.max' => 'تعداد حروف  نام باید حداکثر 100 عدد باشد.',
            'name.unique' => 'این نام از قبل وجود دارد',

            'en_name.required' => 'نام انگلیسی الزامی است.',
            'en_name.string' => 'نام انگلیسی باید رشته باشد.',
            'en_name.min' => 'تعداد حروف  نام انگلیسی باید حداقل سه عدد باشد.',
            'en_name.max' => 'تعداد حروف  نام انگلیسی باید حداکثر 100 عدد باشد.',
            'en_name.unique' => 'این نام انگلیسی از قبل وجود دارد',

            'ratio.required' => 'ضریب الزامی است.',
            'ratio.integer' => 'ضریب باید عددی باشد.',

            'category_id.exists'=> 'مقدار category_id اشتباه است',

            'description.required' => 'توضیحات الزامی است.',
            'description.string' => 'توضیحات باید رشته باشد.',
            'description.min' => 'تعداد حروف  توضیحات باید حداقل سه عدد باشد.',
            'description.max' => 'تعداد حروف  توضیحات باید حداکثر 25 عدد باشد.',

            'shortDescription.required' => 'توضیحات کوتاه الزامی است.',
            'shortDescription.string' => 'توضیحات کوتاه باید رشته باشد.',
            'shortDescription.min' => 'تعداد حروف  توضیحات کوتاه باید حداقل 5 عدد باشد.',
            'shortDescription.max' => 'تعداد حروف  توضیحات کوتاه باید حداکثر 500 عدد باشد.',

           
            'additionalInformation.string' => 'توضیحات بیشتر باید رشته باشد.',
            'additionalInformation.min' => 'تعداد حروف  توضیحات بیشتر باید حداقل 5 عدد باشد.',
            'additionalInformation.max' => 'تعداد حروف  توضیحات بیشتر باید حداکثر 500 عدد باشد.',

            'cover.required' => 'تصویر الزامی است',
            'cover.string' => 'تصویر باید رشته باشد',

            'images.string' => 'تصاویر باید ارایه باشد',

            'tags.array'=> 'تگ ها باید آرایه باشند',

            'type.required'=> 'تایپ  الزامی است',
            'type.boolean'=> 'تایپ باید بولین باشد',

            'discount.integer'=> 'درصد تخفیف باید عدد باشد',
            'discount_end_time.required_with'=> 'تاریخ پایان الزامی است',
            'discount_start_time.required_with'=> 'تاریخ شروع الزامی است',

            'max_sell.required'=> 'حداکثر تعداد فروش الزامی است',
            'max_sell.integer'=> 'حداکثر تعداد فروش باید عددی باشد ',

            "id.required"=> 'آیدی محصول الزامی است',
            "id.exists"=> ' محصول  یافت نشد',


        ];
    }
}

