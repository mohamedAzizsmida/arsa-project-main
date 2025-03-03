<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\Association; 
use App\Service\CartService; 
use App\Entity\Event; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EmailService;

class DonationController extends AbstractController
{
    private $requestStack;
    private $cartService;
    private $emailService;
    private $entityManager;

    public function __construct(RequestStack $requestStack, CartService $cartService, EmailService $emailService, EntityManagerInterface $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->cartService = $cartService;
        $this->emailService = $emailService;
        $this->entityManager = $entityManager;
    }

    private function checkDonationComplete(): bool
    {
        $session = $this->requestStack->getSession();
        return $session->has('last_donation');
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cart = $this->cartService->getCart();
        
        if (!$cart || count($cart) === 0) {
            $this->addFlash('error', 'Your cart is empty');
            return $this->redirectToRoute('app_eventspage');
        }

        try {
            $cartSummary = $this->cartService->getCartSummary();
            $donationAmount = $cartSummary['total'];
            $donationType = $cartSummary['type'];
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error processing cart data');
            return $this->redirectToRoute('app_eventspage');
        }

        $associations = $this->entityManager->getRepository(Association::class)->findAll();

        if ($request->isMethod('POST')) {
            $requiredFields = ['first_name', 'last_name', 'email', 'payment_method'];
            foreach ($requiredFields as $field) {
                if (empty($request->request->get($field))) {
                    $this->addFlash('error', 'Please fill in all required fields');
                    return $this->render('page/checkout.html.twig', [
                        'donation_amount' => $donationAmount,
                        'donation_type' => $donationType,
                        'cart' => $cart,
                        'last_data' => $request->request->all(),
                        'associations' => $associations
                    ]);
                }
            }

            try {
                $donation = new Donation();
                $donation
                    ->setFirstName($request->request->get('first_name'))
                    ->setLastName($request->request->get('last_name'))
                    ->setCountry($request->request->get('country'))
                    ->setAddress($request->request->get('address'))
                    ->setApartment($request->request->get('apartment', ''))
                    ->setTown($request->request->get('town'))
                    ->setState($request->request->get('state'))
                    ->setPostalCode($request->request->get('postal_code'))
                    ->setPhone($request->request->get('phone'))
                    ->setEmail($request->request->get('email'))
                    ->setOrderNotes($request->request->get('order_notes', ''))
                    ->setDonationAmount($donationAmount)
                    ->setDonationType($donationType)
                    ->setPaymentMethod($request->request->get('payment_method'))
                    ->setCreatedAt(new \DateTimeImmutable());

                foreach ($cart as $key => $item) {
                    if (strpos($key, 'event_') === 0 && isset($item['item']['event_id'])) {
                        $event = $entityManager->getRepository(Event::class)->find($item['item']['event_id']);
                        if ($event) {
                            $donation->setEvent($event);
                        }
                    }
                }

                $entityManager->persist($donation);
                $entityManager->flush();

                $session = $this->requestStack->getSession();
                $session->set('last_donation', [
                    'amount' => $donationAmount,
                    'type' => $donationType,
                    'payment_method' => $request->request->get('payment_method'),
                    'items' => $cartSummary['items'],
                    'event' => $donation->getEvent() ? [
                        'name' => $donation->getEvent()->getName(),
                        'association' => $donation->getEvent()->getAssociation()->getName()
                    ] : null
                ]);

                try {
                    $this->emailService->sendDonationConfirmation([
                        'firstName' => $request->request->get('first_name'),
                        'lastName' => $request->request->get('last_name'),
                        'email' => $request->request->get('email'),
                        'amount' => $donationAmount,
                        'type' => $donationType,
                        'paymentMethod' => $request->request->get('payment_method'),
                        'items' => $cartSummary['items'],
                        'event' => $donation->getEvent() ? $donation->getEvent()->getName() : null
                    ]);
                } catch (\Exception $e) {
                    error_log('Failed to send donation confirmation email: ' . $e->getMessage());
                }

                $this->cartService->clear();
                return $this->redirectToRoute('app_donation_success');

            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while processing your donation. Please try again.');
                return $this->render('page/checkout.html.twig', [
                    'donation_amount' => $donationAmount,
                    'donation_type' => $donationType,
                    'cart' => $cart,
                    'last_data' => $request->request->all(),
                    'associations' => $associations
                ]);
            }
        }

        return $this->render('page/checkout.html.twig', [
            'donation_amount' => $donationAmount,
            'donation_type' => $donationType,
            'cart' => $cart,
            'last_data' => [],
            'associations' => $associations
        ]);
    }

    #[Route('/donation/success', name: 'app_donation_success')]
    public function success(): Response
    {
        $session = $this->requestStack->getSession();
        $lastDonation = $session->get('last_donation');
        
        if (!$lastDonation) {
            return $this->redirectToRoute('app_eventspage');
        }

        $session->remove('last_donation');

        return $this->render('page/donation_success.html.twig', [
            'donation' => $lastDonation,
            'associations' => $this->entityManager->getRepository(Association::class)->findAll()
        ]);
    }
}