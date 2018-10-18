<?php

namespace AndreyVasin\LaravelAuthBitrix\Controllers;

use App\Http\Controllers\Controller;

/**
 * Class LoginController
 *
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    /**
     * Переопределяем поле битрикса для email
     *
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'EMAIL';
    }
}
