<?php
/**
 * Minimal, dependency-free Razorpay client (Orders API + signature check).
 * Uses cURL only. When keys are not configured the app runs billing in DEMO
 * mode and never calls this class.
 */

declare(strict_types=1);

final class Razorpay
{
    public static function isConfigured(): bool
    {
        return RAZORPAY_KEY_ID !== '' && RAZORPAY_KEY_SECRET !== '';
    }

    /**
     * Create an order. $amount is the major-unit price (e.g. rupees); Razorpay
     * wants the minor unit (paise), so we multiply by 100.
     *
     * @return array{id:string,amount:int,currency:string}
     * @throws RuntimeException on API/transport failure
     */
    public static function createOrder(float $amount, string $receipt): array
    {
        $payload = [
            'amount'   => (int) round($amount * 100),
            'currency' => BILLING_CURRENCY,
            'receipt'  => $receipt,
        ];
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($res === false) {
            throw new RuntimeException('Payment gateway unreachable: ' . $err);
        }
        $data = json_decode((string) $res, true);
        if ($code >= 300 || empty($data['id'])) {
            $m = $data['error']['description'] ?? ('HTTP ' . $code);
            throw new RuntimeException('Razorpay order failed: ' . $m);
        }
        return ['id' => $data['id'], 'amount' => (int) $data['amount'], 'currency' => $data['currency']];
    }

    /** Verify the checkout callback signature (HMAC-SHA256 of "order_id|payment_id"). */
    public static function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
        return hash_equals($expected, $signature);
    }
}
