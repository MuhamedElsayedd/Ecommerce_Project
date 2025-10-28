<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponses;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    use ApiResponses;

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Force role to 'user' (prevent malicious role assignment)
            $validated['role'] = 'user';
            $validated['password'] = Hash::make($validated['password']);

            $userData = DB::transaction(function () use ($validated) {
                $user = User::create($validated);

                if (! $user || ! $user->exists) {
                    throw new \Exception('Failed to create user.');
                }

                $token = $user->createToken('authToken')->plainTextToken;

                if (! $token) {
                    throw new \Exception('Failed to generate token.');
                }

                return [
                    'user' => $user,
                    'token' => $token,
                ];
            });

            return $this->successResponse(
                data: [
                    'user'  => new UserResource($userData['user']),
                    'token' => $userData['token'],
                ],
                message: 'User registered successfully',
                statusCode: Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            Log::error('User registration failed', ['exception' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'Registration failed',
                errors: config('app.debug') ? $e->getMessage() : 'Something went wrong, please try again later.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
