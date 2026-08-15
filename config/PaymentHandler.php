<?php
/**
 * Razorpay Payment Integration Handler
 * Secure payment processing for fee collection
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';

class RazorpayPaymentHandler
{
    private string $keyId;
    private string $keySecret;
    private string $apiUrl = 'https://api.razorpay.com/v1';

    public function __construct()
    {
        $this->keyId = env('RAZORPAY_KEY_ID', '');
        $this->keySecret = env('RAZORPAY_KEY_SECRET', '');

        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new Exception('Razorpay credentials not configured');
        }
    }

    /**
     * Create payment order
     */
    public function createOrder(float $amount, string $invoiceId, string $description = ''): array
    {
        $payload = [
            'amount' => (int)($amount * 100), // Convert to paise
            'currency' => 'INR',
            'receipt' => $invoiceId,
            'description' => $description,
            'notes' => [
                'invoice_id' => $invoiceId
            ]
        ];

        $response = $this->makeRequest('POST', '/orders', $payload);

        return [
            'success' => isset($response['id']),
            'order_id' => $response['id'] ?? null,
            'amount' => $response['amount'] ?? null,
            'currency' => $response['currency'] ?? 'INR',
            'error' => $response['error'] ?? null
        ];
    }

    /**
     * Verify payment signature
     */
    public function verifyPayment(string $orderId, string $paymentId, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', "$orderId|$paymentId", $this->keySecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails(string $paymentId): array
    {
        $response = $this->makeRequest('GET', "/payments/$paymentId");
        return $response;
    }

    /**
     * Refund payment
     */
    public function refundPayment(string $paymentId, float $amount = null): array
    {
        $payload = [];
        if ($amount !== null) {
            $payload['amount'] = (int)($amount * 100);
        }
        $payload['notes'] = [
            'refund_reason' => 'Fee waiver or cancellation'
        ];

        $response = $this->makeRequest('POST', "/payments/$paymentId/refund", $payload);

        return [
            'success' => isset($response['id']),
            'refund_id' => $response['id'] ?? null,
            'amount' => isset($response['amount']) ? $response['amount'] / 100 : null,
            'error' => $response['error'] ?? null
        ];
    }

    /**
     * Get order details
     */
    public function getOrderDetails(string $orderId): array
    {
        return $this->makeRequest('GET', "/orders/$orderId");
    }

    /**
     * Make API request to Razorpay
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[Razorpay API Error] cURL: $error");
            return ['error' => $error];
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            error_log("[Razorpay API Error] HTTP $httpCode: " . json_encode($decoded));
            return ['error' => $decoded['description'] ?? 'Payment processing failed'];
        }

        return $decoded ?: [];
    }
}

/**
 * Payment Status Helper
 */
class PaymentStatus
{
    const PENDING = 'pending';
    const PROCESSING = 'processing';
    const COMPLETED = 'completed';
    const FAILED = 'failed';
    const REFUNDED = 'refunded';

    public static function isValid(string $status): bool
    {
        return in_array($status, [
            self::PENDING,
            self::PROCESSING,
            self::COMPLETED,
            self::FAILED,
            self::REFUNDED
        ]);
    }
}

/**
 * Invoice Payment Processor
 */
class InvoicePaymentProcessor
{
    private RazorpayPaymentHandler $razorpay;
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        try {
            $this->razorpay = new RazorpayPaymentHandler();
        } catch (Exception $e) {
            error_log("[Payment] Razorpay not configured: " . $e->getMessage());
            $this->razorpay = null;
        }
    }

    /**
     * Process invoice payment
     */
    public function processPayment(string $invoiceId, float $amount): array
    {
        // Check if Razorpay is available
        if ($this->razorpay === null) {
            return [
                'success' => false,
                'error' => 'Payment gateway not configured',
                'demo_mode' => true
            ];
        }

        try {
            $order = $this->razorpay->createOrder($amount, $invoiceId, "Invoice Payment - $invoiceId");

            if (!$order['success']) {
                return ['success' => false, 'error' => $order['error'] ?? 'Failed to create order'];
            }

            // Store pending payment in database
            $stmt = $this->pdo->prepare('
                INSERT INTO fee_invoices (id, amount, status, payment_order_id, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE status = ?, payment_order_id = ?
            ');

            $stmt->execute([
                $invoiceId,
                $amount,
                PaymentStatus::PROCESSING,
                $order['order_id'],
                PaymentStatus::PROCESSING,
                $order['order_id']
            ]);

            return [
                'success' => true,
                'order_id' => $order['order_id'],
                'amount' => $amount,
                'key_id' => env('RAZORPAY_KEY_ID')
            ];
        } catch (Exception $e) {
            error_log("[Payment Processing Error] " . $e->getMessage());
            return ['success' => false, 'error' => 'Payment processing failed'];
        }
    }

    /**
     * Verify and complete payment
     */
    public function completePayment(string $orderId, string $paymentId, string $signature): array
    {
        try {
            // Verify signature
            if (!$this->razorpay->verifyPayment($orderId, $paymentId, $signature)) {
                return ['success' => false, 'error' => 'Payment verification failed'];
            }

            // Get payment details
            $details = $this->razorpay->getPaymentDetails($paymentId);

            if ($details['status'] !== 'captured') {
                return ['success' => false, 'error' => 'Payment not captured'];
            }

            // Update invoice status
            $stmt = $this->pdo->prepare('
                UPDATE fee_invoices 
                SET status = ?, payment_id = ?, payment_date = NOW()
                WHERE payment_order_id = ?
            ');

            $stmt->execute([
                PaymentStatus::COMPLETED,
                $paymentId,
                $orderId
            ]);

            return ['success' => true, 'message' => 'Payment completed successfully'];
        } catch (Exception $e) {
            error_log("[Payment Verification Error] " . $e->getMessage());
            return ['success' => false, 'error' => 'Verification failed'];
        }
    }
}
