<?php

namespace AndreyVasin\LaravelAuthBitrix\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * Class RegisterController
 *
 * @package App\Http\Controllers\Auth
 */
class RegisterController extends Controller
{
    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'last_name'   => 'max:255',
            'name'        => 'required|string|max:255',
            'second_name' => 'max:255',
            'phone'       => 'max:15',
            'email'       => 'required|string|email|max:255|unique:b_user',
            'password'    => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     *
     * @return \App\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'NAME'        => $data['name'],
            'LAST_NAME'   => $data['last_name'],
            'SECOND_NAME' => $data['second_name'],
            'LOGIN'       => $data['email'],
            'EMAIL'       => $data['email'],
            'PASSWORD'    => $data['password'],
        ]);
        return $user;
    }
}
