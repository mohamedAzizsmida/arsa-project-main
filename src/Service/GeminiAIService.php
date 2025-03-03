<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiAIService
{
    private $client;
    private $apiKey;
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->apiKey = $_ENV['GEMINI_API_KEY'];
    }

    public function askQuestion(string $question): string
    {
        $context = "You are an AI assistant for EcoDon, an eco-friendly donation platform. " .
                  "Our mission is to facilitate donations of clothes, food, and other items while promoting environmental sustainability. " .
                  "We accept various types of donations including clothes, food, furniture, and electronics. " .
                  "Our platform connects donors and associations with those in need while minimizing waste and environmental impact. " .
                  "Please provide helpful, friendly responses related to donations, environmental impact, and our services. " .
                  "Question: " . $question;

        try {
            $response = $this->client->request('POST', self::API_URL . '?key=' . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $context]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 250, 
                        'topK' => 40,
                        'topP' => 0.95,
                    ],
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_HARASSMENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ]
                    ]
                ]
            ]);

            $data = $response->toArray();
            $response = $data['candidates'][0]['content']['parts'][0]['text'];

            $response = str_replace('Question:', '', $response);
            return trim($response);

        } catch (\Exception $e) {
            error_log('Gemini AI Error: ' . $e->getMessage());
            throw $e;
        }
    }
}