<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ResetPasswordController extends Controller
{
    use ApiResponses;

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:6|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse(
                    message: __($status),
                    statusCode: Response::HTTP_OK
                );
            }

            return $this->errorResponse(
                message: __($status),
                statusCode: Response::HTTP_BAD_REQUEST
            );
        } catch (\Throwable $e) {
            Log::error('Password reset failed', ['exception' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'Password reset failed',
                errors: config('app.debug') ? $e->getMessage() : null,
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
