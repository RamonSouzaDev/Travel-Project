<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $guard;

    public function __construct()
    {
        $this->guard = Auth::guard('api'); // <- FORÇA O JWT
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $this->guard->login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário criado com sucesso',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        $token = $this->guard->attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'user' => $this->guard->user(),
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    public function logout()
    {
        $this->guard->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => $this->guard->user(),
            'authorization' => [
                'token' => $this->guard->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
}
