<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymobService;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymobService $paymobService;

    public function __construct(PaymobService $paymobService)
    {
        $this->paymobService = $paymobService;
    }

    public function paymentProcess(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'method'   => 'required|in:card,wallet,cod',
        ]);

        $order = Order::findOrFail($request->order_id);


        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id'  => $order->user_id,
            'amount'   => $order->total_amount,
            'method'   => $request->method,
            'status'   => 'pending',
        ]);


        $paymentData = [
            'amount' => $payment->amount,
            'payment_id' => $payment->id,
            'method' => $payment->method,
            'user' => [
                'name' => $order->user->name ?? 'Unknown',
                'email' => $order->user->email ?? 'noemail@example.com',
                'phone' => $order->user->phone ?? '0000000000',
            ]
        ];

        $response = $this->paymobService->sendPayment($paymentData);

        return response()->json($response);
    }

    public function callBack(Request $request)
    {
        $isSuccessful = $this->paymobService->callBack($request);

        if ($isSuccessful) {
            return redirect()->route('payment.success');
        }

        return redirect()->route('payment.failed');
    }

    public function success()
    {
        return view('payment-success');
    }

    public function failed()
    {
        return view('payment-failed');
    }
}
