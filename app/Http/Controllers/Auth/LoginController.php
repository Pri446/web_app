<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class LoginController extends Controller
{


    /**
     * Display login page.
     * 
     * @return Renderable
     */
    public function show()
    {
        
        return  redirect('/');
    }

    /**
     * Handle account login request
     * 
     * @param LoginRequest $request
     * 
     * @return \Illuminate\Http\Response
     */
    public function login(LoginRequest $request)
    {
        $login = trim($request->input('username'));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::whereRaw('LOWER(' . $field . ') = ?', [mb_strtolower($login)])->first();

        if (
            !$user
            || (int) $user->status !== 1
            || !Hash::check($request->input('password'), $user->password)
        ) {
            return redirect()->back()
                ->withErrors(trans('auth.failed'));
        }
    
        // Check for not allowed roles
        $notallowedRoles = [
            'Municipality - Building Surveyor',
            'Service Provider - Emptying Operator',
            'Municipality - Building Surveyor (Ward)'
        ];
    
        if ($user->roles()->whereIn('name', $notallowedRoles)->exists()) {
            return redirect()->back()
                ->withErrors(['login' => 'You are not allowed to log in with your current role.']);
        }
    
        Auth::login($user, $request->get('remember'));
    
        return $this->authenticated($request, $user);
    }
    

    /**
     * Handle response after user authenticated
     * 
     * @param Request $request
     * @param Auth $user
     * 
     * @return \Illuminate\Http\Response
     */
    protected function authenticated(Request $request, $user) 
    {
        return redirect('/');
    }
}
