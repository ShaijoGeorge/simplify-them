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

    public function sendPremiumReminder(string $mobile, string $clientName, string $policyNo, string $dueDate, string $amount): bool
    {
        // Format Mobile (Remove +91 or 0, ensure 10 digits, then add Country Code if needed)
        // This logic depends on your specific SMS provider's requirements.
        $payload = [
            'countryCode' => '+91',
            'phoneNumber' => $mobile,
            'type' => 'Template',
            'template' => [
                'name' => 'premium_due_reminder', // Template approved in your WhatsApp Dashboard
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
            // For now, we will just LOG it so you can test without paying for an API
            $this->logger->info("------------------------------------------------");
            $this->logger->info("ðŸ“± WHATSAPP SENT TO: $mobile");
            $this->logger->info("MSG: Dear $clientName, Premium for LIC Policy $policyNo is due on $dueDate. Amount: Rs.$amount");
            $this->logger->info("------------------------------------------------");

            /*
            // Uncomment this when you have a real API Key
            $response = $this->client->request('POST', $this->apiUrl, [
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