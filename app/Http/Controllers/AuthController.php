<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class AuthController extends Controller
{

 public function register(Request $request): JsonResponse
    {
        if(Auth::user()){ 
            
            return response()->json([
            'success' => false,
            'message' => 'Unauthorised',
            'error'   => 'You are already logged in'
            ], 400);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            ]);

        $validated['password'] = bcrypt($validated['password']);
        $user = User::create($validated);
        $request->session()->regenerate();
        Auth::login($user);

        return response()->json([
                        'success' => true,
                        'message' => 'Registered successfully',
                    ], 200);
    }


    public function login(Request $request) : JsonResponse
    {
        if($user =  Auth::user()){ 
            if($user->email === $request->email){
                return response()->json([
                        'success' => false,
                        'message' => 'You are already Logged In',
                    ], 400);
                }
           
            ;}

            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
            
            if(Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])){ 
            
            $request->session()->regenerate();
            $user = Auth::user();

            return  response()->json([
                        'success' => true,
                        'message' => 'Logged in successfully',
                    ], 200);
                }
                
        else{ 
            return response()->json([
                        'success' => false,
                        'message' => 'Unauthorised',
                        'error'   => 'Email or password are incorrect'
                    ], 400);
            
        } 


    }


     public function logout(Request $request): JsonResponse
    {
        
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        

        return response()->json([
                        'success' => true,
                        'message' => 'Logged out successfully',
                    ], 200);
        
    }
}
