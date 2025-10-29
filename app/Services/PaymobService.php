<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobService
{
    protected string $base_url;
    protected string $api_key;
    protected array $headers;
    protected array $integrations_id;

    public function __construct()
    {
        $this->base_url = env("PAYMOB_BASE_URL", "https://accept.paymob.com");
        $this->api_key = env("PAYMOB_API_KEY");
        $this->headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $this->integrations_id = [
            'card'   => 5115746,
        ];
    }

    protected function generateToken(): ?string
    {
        $response = Http::withHeaders($this->headers)
            ->post("{$this->base_url}/api/auth/tokens", [
                'api_key' => $this->api_key,
            ]);

        return $response->json('token');
    }

    protected function buildRequest(string $method, string $endpoint, array $data)
    {
        return Http::withHeaders($this->headers)
            ->send($method, "{$this->base_url}{$endpoint}", ['json' => $data]);
    }

    public function sendPayment(array $data): array
    {
        try {
            $authToken = $this->generateToken();
            if (!$authToken) {
                return ['success' => false, 'message' => 'Failed to generate token'];
            }

            $orderData = [
                "auth_token" => $authToken,
                "delivery_needed" => false,
                "amount_cents" => intval($data['amount'] * 100),
                "currency" => "EGP",
                "merchant_order_id" => $data['payment_id'],
                "items" => []
            ];

            $orderResponse = $this->buildRequest('POST', '/api/ecommerce/orders', $orderData);
            $orderId = $orderResponse->json('id');

            if (!$orderId) {
                return ['success' => false, 'message' => 'Failed to create Paymob order'];
            }


            $integrationId = $this->integrations_id[$data['method']] ?? $this->integrations_id['card'];

            $paymentKeyData = [
                "auth_token" => $authToken,
                "amount_cents" => intval($data['amount'] * 100),
                "expiration" => 3600,
                "order_id" => $orderId,
                "billing_data" => [
                    "apartment" => "NA",
                    "email" => $data['user']['email'] ?? "noemail@example.com",
                    "floor" => "NA",
                    "first_name" => $data['user']['name'] ?? "User",
                    "street" => "NA",
                    "building" => "NA",
                    "phone_number" => $data['user']['phone'] ?? "01111111111",
                    "shipping_method" => "NA",
                    "postal_code" => "NA",
                    "city" => "Cairo",
                    "country" => "EG",
                    "last_name" => "NA",
                    "state" => "NA"
                ],
                "currency" => "EGP",
                "integration_id" => $integrationId,
            ];

            $paymentKeyResponse = $this->buildRequest('POST', '/api/acceptance/payment_keys', $paymentKeyData);
            $paymentToken = $paymentKeyResponse->json('token');

            if (!$paymentToken) {
                return ['success' => false, 'message' => 'Failed to generate payment key'];
            }


            $iframeId = env('PAYMOB_IFRAME_ID');
            $paymentUrl = "{$this->base_url}/api/acceptance/iframes/{$iframeId}?payment_token={$paymentToken}";


            Payment::where('id', $data['payment_id'])->update([
                'transaction_id' => $orderId,
                'status' => 'pending'
            ]);

            return [
                'success' => true,
                'payment_url' => $paymentUrl,
                'payment_id' => $data['payment_id']
            ];
        } catch (\Exception $e) {
            Log::error('Paymob sendPayment error', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function callBack(Request $request): bool
    {
        Log::info('Paymob Callback', $request->all());

        $isSuccess = $request->input('success') === 'true';
        $merchantOrderId = $request->input('merchant_order_id');

        if ($merchantOrderId) {
            $payment = Payment::where('id', $merchantOrderId)->first();

            if ($payment) {
                $payment->update([
                    'status' => $isSuccess ? 'paid' : 'failed',
                    'paid_at' => $isSuccess ? now() : null,
                ]);
            }
        }

        return $isSuccess;
    }
}
