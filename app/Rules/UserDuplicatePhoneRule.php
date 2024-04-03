<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UserDuplicatePhoneRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $phone = User::where(['phone' => preg_replace("/[\s-]+/", "", $value)])
                    ->where(function ($query) {
                        $query->where('id', '!=', auth()->id());
                })->exists();
        return $phone ? false : true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __("The phone number has already been taken!");
    }
}