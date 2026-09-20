<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'token' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset($validatedData, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Invalid or expired password reset token.',
            ], 422);
        }

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }
}
