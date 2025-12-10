<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $user = User::where('phone',$this->phone)->first();
        if ($user) {
            return [
                "name"=> ["required","string"],
                "phone"=> ["required","string","regex:/^09[0-9]{9}$/"],
                "telephone"=> ["string","regex:/^0\d{2,3}-?\d{7}$/"],
                "gender"=> ["required","in:0,1,2"],
                "category_id"=> ["required","exists:user_categories,id"],
                "user_type"=> ["required","string", "in:regular,legal"],
                "company_name"=> ["required_if:user_type,legal","string"],
                "national_code"=> ["required_if:user_type,legal","string"],
                "economic_code"=> ["required_if:user_type,legal","string"],
                "registration_number"=> ["required_if:user_type,legal","string"],
                "is_active"=> ["boolean"],
                
            ];
        }
        else{
            return [
                "name" => ["required", "string"],
                "phone" => ["required", "string", Rule::unique('users', 'phone')->ignore($this->id, 'id'), "regex:/^09[0-9]{9}$/"],
                "telephone" => [ "string", "regex:/^0\d{2,3}-?\d{7}$/"],
                "gender" => ["required", "in:0,1,2"],
                "category_id" => ["required", "exists:user_categories,id"],
                "user_type" => ["required", "string", "in:regular,legal"],
                "company_name" => ["required_if:user_type,legal", "string"],
                "national_code" => ["required_if:user_type,legal", "string"],
                "economic_code" => ["required_if:user_type,legal", "string"],
                "registration_number" => ["required_if:user_type,legal", "string"],

            ];
        }
        
    }
}
