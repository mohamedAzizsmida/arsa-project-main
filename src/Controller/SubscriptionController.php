<?php

namespace App\Controller;

use App\Entity\Subscriber;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    #[Route('/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request, 
        EmailService $emailService,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email address');
            }

            // Check if email already exists
            $existingSubscriber = $entityManager->getRepository(Subscriber::class)
                ->findOneBy(['email' => $email]);

            if ($existingSubscriber) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'This email is already subscribed to our newsletter!'
                ], 400);
            }

            // Create new subscriber
            $subscriber = new Subscriber();
            $subscriber->setEmail($email);

            // Save to database
            $entityManager->persist($subscriber);
            $entityManager->flush();

            // Send confirmation email
            $emailService->sendSubscriptionConfirmation($email);

            return new JsonResponse([
                'success' => true,
                'message' => 'Thank you for subscribing!'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}