<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    use ApiResponses;

    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->errorResponse(
                message: 'Email already verified.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->successResponse(
            message: 'Verification link sent to your email.'
        );
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = \App\Models\User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->errorResponse(
                message: 'Invalid verification link.',
                statusCode: Response::HTTP_BAD_REQUEST
            );
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(
                message: 'Email already verified.'
            );
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return $this->successResponse(
            message: 'Email verified successfully.'
        );
    }
}
