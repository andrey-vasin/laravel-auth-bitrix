<?php

namespace AndreyVasin\LaravelAuthBitrix;


/**
 * Class AuthServiceProvider
 *
 * @package App\CoreExtensions\AuthBitrix
 */
class AuthServiceProvider extends \Illuminate\Auth\AuthServiceProvider
{
    /**
     * Register the authenticator services.
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app['auth.loaded'] = true;

            return new AuthManager($app); // переопределяется стандартный
        });

        $this->app->singleton('auth.driver', function ($app) {
            return $app['auth']->guard();
        });
    }
}