<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    use ApiResponses;

    public function __invoke(LoginRequest $request)
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->errorResponse(
                    message: 'Invalid credentials',
                    statusCode: Response::HTTP_UNAUTHORIZED
                );
            }

            $token = $user->createToken('authToken')->plainTextToken;

            if (!$token) {
                throw new \Exception('Token creation failed.');
            }

            return $this->successResponse(
                data: [
                    'user'  => new UserResource($user),
                    'role'  => $user->role,
                    'token' => $token,
                ],
                message: 'User logged in successfully',
                statusCode: Response::HTTP_OK
            );
        } catch (\Throwable $e) {
            Log::error('User login failed', ['exception' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'Login failed',
                errors: config('app.debug') ? $e->getMessage() : 'Something went wrong, please try again later.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
