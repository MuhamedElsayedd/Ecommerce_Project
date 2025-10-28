<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ForgetPasswordController extends Controller
{
    use ApiResponses;

    public function sendResetLinkEmail(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
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
            Log::error('Password reset link failed', ['exception' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'Failed to send reset link',
                errors: config('app.debug') ? $e->getMessage() : null,
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
