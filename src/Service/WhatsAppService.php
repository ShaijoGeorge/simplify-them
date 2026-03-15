<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WhatsAppService
{
    private string $apiUrl = 'https://api.interakt.ai/v1/public/message/';
    private string $apiKey = 'YOUR_API_KEY_HERE';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Format a mobile number for WhatsApp (Indian format).
     * Strips spaces, dashes, leading +91 or 0, then prepends 91.
     */
    private function formatMobile(string $mobile): string
    {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $mobile);

        // Strip leading country code (91) if present and number is > 10 digits
        if (strlen($digits) > 10 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        // Strip leading 0 if present
        $digits = ltrim($digits, '0');

        // Prepend country code
        return '91' . $digits;
    }

    /**
     * Build the reminder message text.
     */
    private function buildReminderMessage(string $clientName, string $policyNo, string $dueDate, string $amount): string
    {
        return "Dear $clientName, your premium for LIC Policy $policyNo is due on $dueDate. Amount: ₹$amount. Please ignore if already paid.";
    }

    /**
     * Generate a free WhatsApp click-to-chat URL (wa.me link).
     * Opens WhatsApp with a pre-filled message — zero cost.
     */
    public function generateWhatsAppUrl(string $mobile, string $clientName, string $policyNo, string $dueDate, string $amount): string
    {
        $phone = $this->formatMobile($mobile);
        $message = $this->buildReminderMessage($clientName, $policyNo, $dueDate, $amount);

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

    /**
     * Send a premium reminder (logs to console; uncomment API call when ready).
     */
    public function sendPremiumReminder(string $mobile, string $clientName, string $policyNo, string $dueDate, string $amount): bool
    {
        $payload = [
            'countryCode' => '+91',
            'phoneNumber' => $mobile,
            'type' => 'Template',
            'template' => [
                'name' => 'premium_due_reminder',
                'languageCode' => 'en',
                'bodyValues' => [
                    $clientName, // {{1}}
                    $policyNo,   // {{2}}
                    $dueDate,    // {{3}}
                    $amount      // {{4}}
                ]
            ]
        ];

        try {
            // Log the message (works without any API key)
            $message = $this->buildReminderMessage($clientName, $policyNo, $dueDate, $amount);
            $this->logger->info("📱 WHATSAPP → $mobile: $message");

            /*
            // Uncomment this when you have a real API Key
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey),
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);
            return $response->getStatusCode() === 200;
            */

            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to send WhatsApp: " . $e->getMessage());
            return false;
        }
    }
}