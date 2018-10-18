# laravel-auth-bitrix
Авторизация ларавела, использующая битрикс структуру
## Установка
Перед установкой пакета должна быть выполнена стандартная установка 
авторизации Laravel

Добавьте пакет в свой composer 
```
composer require andrey-vasin/laravel-auth-bitrix
```
Пакет имеет зависомость от andrey-vasin/laravel-hashing-bitrix, поэтому надо 
исправить конфигурационный файл hashing.php,
```
'driver' => 'bitrix',
```
Этим мы эмулируем работу хеширования битрикса

В файле app\User.php выставить расширение 

```
class User extends \AndreyVasin\LaravelAuthBitrix\User
```

В файле app\Http\Controllers\Auth\LoginController.php выставить расширение

```
class LoginController extends \AndreyVasin\LaravelAuthBitrix\Controllers\LoginController
```

В файле app\Http\Controllers\Auth\RegisterController.php выставить расширение

```
class RegisterController extends \AndreyVasin\LaravelAuthBitrix\Controllers\RegisterController
```

В конфиге app\config\app.php заменить провайдер
```    
    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        //Illuminate\Auth\AuthServiceProvider::class,
        AndreyVasin\LaravelAuthBitrix\AuthServiceProvider::class,
```