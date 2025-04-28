<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
class RegisterController extends Controller
{
    public function RegisterPage()  {
        return view("register");
    }

    public function handleRegister(Request $request) {
        $request->validate(["name"=> "String|required|max:255|unique:users,name","email"=>"email|required|unique:users,email","password"=> "required|min:6|confirmed"]
        ,['email.unique' => 'This email is already in use, please choose another.','name.unique'=> 'This username is already in use, please choose another.']);
        $user = User::create(['name'=>$request->name,'email'=>$request->email,'password'=>Hash::make($request->password)]);
        Auth::login($user);
        return redirect()->route('home');
    }
}
