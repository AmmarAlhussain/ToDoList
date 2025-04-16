<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function loginPage()
    {
        return view("login");
    }

    public function handleLogin(Request $request)
    {
        $validated = $request->validate(["email" => "required|email", "password" => "required|min:8"]);

        if (Auth::attempt(["email" => $validated["email"], "password" => $validated["password"]])) {
            return redirect()->route("home");
        } else {
            return back()->withErrors(["email"=>"Your email or your password incorrect"])->withInput();
        }
    }
}
