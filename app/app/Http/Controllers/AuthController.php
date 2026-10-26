<?php
namespace App\Http\Controllers;

use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate(['email'=>'required|email|max:255','password'=>'required|string|max:255']);
        $key = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) return back()->withErrors(['email'=>'Too many sign-in attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.'])->onlyInput('email');
        if (! Auth::attempt([...$credentials, 'active'=>true])) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['email'=>'The email or password is incorrect, or the account is inactive.'])->onlyInput('email');
        }
        RateLimiter::clear($key); $request->session()->regenerate(); Access::audit('Signed in','authentication');
        return redirect()->intended('/');
    }
    public function logout(Request $request)
    {
        Access::audit('Signed out','authentication'); Auth::logout();
        $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect('/login');
    }
    public function password(Request $request)
    {
        $data = $request->validate(['current_password'=>['required','current_password'], 'password'=>['required','confirmed',Password::min(12)->mixedCase()->numbers()->symbols()]]);
        $request->user()->update(['password'=>$data['password']]);
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id',auth()->id())->where('id','!=',$request->session()->getId())->delete();
        $request->session()->regenerate(); Access::audit('Changed password','authentication');
        return back()->with('success','Your password has been updated.');
    }
}
