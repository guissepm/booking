<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

/*This a file that while created automatcly by php artisan make:auth L7*/
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /* L58 */
    use AuthenticatesUsers {
        
    login as protected webLogin;
    
    }

    /*use AuthenticatesUsers;*/

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    /*Hear we wille change protected protected properties redirectTo to admin L7*/
    protected $redirectTo = '/admin'; 

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /* L58,59,61*/
    public function login(Request $request)
    {
        
        if ($request->ajax())
        {
            $credentials = $request->only('email', 'password');

            try {
                // verify the credentials and create a token for the user
                if (!$token = \Tymon\JWTAuth\Facades\JWTAuth::attempt($credentials))
                {
                    return response()->json(['error' => 'invalid_credentials'], 401);
                }
            } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
                // something went wrong
                return response()->json(['error' => 'could_not_create_token'], 500);
            }

            $user = Auth::user(); 
            $role = $user->roles[0];

            // if no errors are encountered we can return a JWT
            return response()->json(compact('token','role'));
        }
        else
        return $this->webLogin($request);
    }
    
}
