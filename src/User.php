<?php

namespace AndreyVasin\LaravelAuthBitrix;

use App\BusinessLayer\Instance\Anketa\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

/**
 * Class User
 *
 * @package app\BusinessLayer\Bitrix\Users
 */
class User extends Authenticatable
{
    protected $table = 'b_user';
    const TABLE_NAME = 'b_user';

    const CREATED_AT = 'DATE_REGISTER';
    const UPDATED_AT = 'TIMESTAMP_X';

    protected $primaryKey = 'ID';

    public $rememberTokenName = 'REMEMBER_TOKEN'; // Должно быть соответствующее поле в таблице b_user

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'EMAIL', 'PASSWORD', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_PHONE'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'PASSWORD', 'REMEMBER_TOKEN',
    ];

    private $id = null;

    /**
     * @var array
     */
    static $usersDataCache = [];

    /**
     * Устанавливаем пользователя для работы с моделью
     * В конструкторе объявить не вышло так как конструктор наследуется
     *
     * @param int $userId Идентификатор пользователя
     *
     * @return $this
     */
    public function setId(int $userId = null)
    {
        $this->id = $userId;
        return $this;
    }

    /**
     * Устанавливаем как кодируется пароль
     *
     * @param $password
     */
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

    /**
     * Перебиваем стандартное название пароля
     *
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->PASSWORD;
    }

    /**
     * Проверка битрикс сессии
     *
     * @return bool
     */
    public function checkSession()
    {
        return isset($_SESSION['SESS_AUTH']['USER_ID']);
    }

    /**
     * @return int|null Идентификатор из битрикс-сессии
     */
    public static function getSessionId()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['SESS_AUTH']['USER_ID'])) {
            return $_SESSION['SESS_AUTH']['USER_ID'];
        } else {
            return null;
        }
    }

    /**
     * Получить данные пользователя
     *
     * @param bool $property - вытаскивать "property_" значения
     *
     * @return array Данные пользователя
     */
    public function getData($property = false)
    {
        $id = $this->id;
        if (isset(User::$usersDataCache[$this->id])) {
            return User::$usersDataCache[$id];
        }
        # Если в сессии нет, лезем смотрим в базу. Пока что без групп
        User::$usersDataCache[$id] = $this->find($id)->toArray();

        if ($property) {
            $ar = [];
            $res = DB::table('b_uts_user')
                ->select(['*'])
                ->where('VALUE_ID', '=', $id)
                ->get();
            if ($res->count() == 1) {
                foreach ($res as $value) {
                    $ar = (array)$value;
                }
                foreach ($ar as &$v) {
                    # Смотрим не сериализованные ли данные
                    if ($v[0] == 'a' && $v[1] == ':') {
                        $v = unserialize($v);
                    }
                }
                if(!empty($ar)){
                    User::$usersDataCache[$id] = array_merge(User::$usersDataCache[$id], $ar);
                }
            }
        }
        return User::$usersDataCache[$id];
    }

    //

    /**
     * @param string $group Код группы, на которые хотим проверить. код регистронезависимый
     *
     * @return bool
     */
    public function issetInGroup($group)
    {
        return isset($this->getUserGroups()[mb_strtoupper($group)]);
    }

    /**
     * @return array Возвращает массив всех групп пользоваетля
     */
    public function getUserGroups()
    {
        $ret = [];
        $res = DB::table('b_user_group')
            ->join('b_group', 'b_group.ID', '=', 'b_user_group.GROUP_ID')
            ->where('USER_ID', '=', $this->id)->get()->all();

        foreach ($res as $resAr) {
            $code = !empty($resAr->STRING_ID) ? mb_strtoupper($resAr->STRING_ID) : $resAr->ID;
            $ret[$code] = $resAr;
        }
        return $ret;
    }

    /**
     * @param array $groupIds
     *
     * @return array
     */
    public function getUsersByGroups(array $groupIds): array
    {
        $ret = [];
        $rsUsers = DB::table('b_user_group')->select(['USER_ID'])->whereIn('GROUP_ID', $groupIds)->get()->toArray();
        foreach ($rsUsers as $data) {
            $ret[$data->GROUP_ID] = $data->GROUP_ID;
        }
        return $ret;
    }

    /**
     * На случай, если из ларавела захочется залогиниться в админку битрикса и подхватить его сессию
     *
     * @param Request $request
     * @param Client  $client
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginAdmin(Request $request, Client $client) : JsonResponse
    {
        # Используем битрикс чтобы залогиниться
        $url = env('BITRIX_URL') . '/bitrix/admin/index.php?login=yes';

        $bitrixSessId = '';
        $result = $client->post($url, [
            'form_params' => [
                'AUTH_FORM'     => 'Y',
                'TYPE'          => 'AUTH',
                'USER_LOGIN'    => $request->json('email'),
                'USER_PASSWORD' => $request->json('password'),
                'Login'         => '',
                'captcha_sid'   => '',
                'captcha_word'  => '',
                'sessid'        => $bitrixSessId,
            ],
        ]);

        $ret = $result->getBody()->getContents();

        if (false !== strpos($ret, 'setAuthResult({')) {
            $res = explode('setAuthResult({', $ret)[1];
            $res = explode('});', $res)[0];
            $res = json_decode('{' . strip_tags(str_replace("'", '"', $res)) . '}', true);
            return response()->json([
                "errors"  => $res['TYPE'] == 'ERROR' ? [$res['MESSAGE']] : '',
                "message" => $res['MESSAGE'],
            ], 422);
        } else {

            # Так как сессия создаётся и генерируется битриксом, берём её номер для ларавела
            $sessId = str_replace('PHPSESSID=', '', explode('; ', $result->getHeader('Set-Cookie')[0])[0]) ;

            session_id($sessId); # Выставляем полученную сессию. В ней все данные о правильном

            if (session_status() == PHP_SESSION_NONE) {
                session_start(); # Запускаем сессию только когда получили правильную авторизацию. Иначе не имеет смысла запускать
            }
            return response()->json([
                "status"  => true,
                "errors"  => [],
                "records" => ['email' => $request->json('email')],
            ]);
        }
    }


}
