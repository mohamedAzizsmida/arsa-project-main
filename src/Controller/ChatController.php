<?php

namespace App\Controller;

use App\Service\GeminiAIService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    private $gemini;

    public function __construct(GeminiAIService $gemini)
    {
        $this->gemini = $gemini;
    }

    #[Route('/chat/send', name: 'chat_send', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $userMessage = $data['message'] ?? '';

            if (empty($userMessage)) {
                throw new \Exception('Message cannot be empty');
            }

            error_log('Attempting to send message to Gemini: ' . $userMessage);
            
            $response = $this->gemini->askQuestion($userMessage);
            
            error_log('Received response from Gemini');

            return new JsonResponse([
                'success' => true,
                'message' => $response
            ]);

        } catch (\Exception $e) {
            error_log('ChatBot Error: ' . $e->getMessage());
            
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred. Please try again.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/chat/test', name: 'chat_test')]
    public function testApi(): JsonResponse
    {
        try {
            $response = $this->gemini->askQuestion('test');
            return new JsonResponse(['success' => true, 'message' => $response]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}