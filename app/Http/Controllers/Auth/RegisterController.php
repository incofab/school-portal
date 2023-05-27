<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\UserHelper;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'digits:11', 'unique:users'],
            'username' => ['required', 'alpha_dash', 'unique:users', new \App\Rules\NotEntirelyDigits()],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'username' => $data['username'],
        ]);
    }
    
    function apiRegister(Request $request, UserHelper $userHelper) {
        
        $val = $this->validator($request->all());
        
        if($val->fails())
        {
            $message = 'Validation failed: '.getFirstValue($val->errors()->toArray());
            
            return $this->apiRes(false, $message, ['errors' => $val->errors()->toArray()]);
        }
        
        $user = $this->create($request->all());
        
        $this->guard()->login($user);
        
        $userIndex = $userHelper->indexPage($user);
        $userIndex['user'] = $user;
        $userIndex['token'] = $user->createLoginToken();
                
        return $this->apiRes(true, 'Registration successful', $userIndex);
    }
    
}
