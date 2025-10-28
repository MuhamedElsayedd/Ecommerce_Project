<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    use ApiResponses;
    public function logout()
    {
        try {
            request()->user()?->currentAccessToken()?->delete();

            return $this->successResponse(
                message: 'User logged out successfully',
                statusCode: Response::HTTP_OK
            );
        } catch (\Throwable $e) {
            Log::error('Logout failed', ['exception' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'Logout failed',
                errors: config('app.debug') ? $e->getMessage() : 'Something went wrong, please try again later.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
