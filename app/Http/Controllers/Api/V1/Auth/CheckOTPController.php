<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckOTPController extends Controller
{

    public function checkOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|digits:6',
        ]);


        $otpRecord = DB::table('password_otps')->where('email', $request->email)->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'No OTP found for this email.',
            ], 404);
        }

        if ($otpRecord->otp_code != $request->otp_code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP code.',
            ], 400);
        }

        if (Carbon::parse($otpRecord->expires_at)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.',
        ]);
    }
}
