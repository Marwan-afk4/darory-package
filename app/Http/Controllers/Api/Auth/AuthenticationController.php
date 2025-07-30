<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helper\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('mobile', $request->mobile)->first();

        if ($user && Auth::attempt($request->only('mobile', 'password'))) {
            $user->tokens()->delete();

            $access_token = $user->createToken('ACCESS-TOKEN')->plainTextToken;

            return ApiResponse::sendResponse(200, 'Success', 'Logged in', [
                'user' => new UserResource($user),
                'access_token' => $access_token,
            ]);
        }

        return ApiResponse::sendResponse(401, 'Error', 'The provided credentials are incorrect');
    }

    public function logout()
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::sendResponse(200, 'Success', 'Logged out');
    }
}
