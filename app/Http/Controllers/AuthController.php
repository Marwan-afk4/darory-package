<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm() {
        if (Auth::check()) {
            return redirect()->route('home')->with('success', 'You are already logged in');
        }
        return view('auth.login');
    }

    public function login(Request $request) {
        $request->validate([
            'mobile' => [
                'required',
                'string',
                'regex:/^(010|011|012|015)\d{8}$/'
            ],
            'password' => 'required|string'
        ]);
    
        $user = User::where('mobile', $request->mobile)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['error' => 'Mobile number or password is incorrect'])->withInput();
        }
        
        Auth::login($user,true);
        $request->session()->regenerate();
        
        return redirect()->intended(route('home'))->with('success', 'Logged in successfully');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $user->forceFill(['remember_token' => null])->save();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerate();

        return redirect()->route('login')->with('message', 'Logged out successfully');
    }
}
