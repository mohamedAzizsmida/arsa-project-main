<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Entity\Event;
use App\Entity\Association;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    private $cartService;
    private $entityManager;

    public function __construct(CartService $cartService, EntityManagerInterface $entityManager)
    {
        $this->cartService = $cartService;
        $this->entityManager = $entityManager;
    }

    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $associations = $this->entityManager->getRepository(Association::class)->findAll();

        $parameters['cartItemCount'] = count($this->cartService->getCart());
        $parameters['cartTotal'] = $this->cartService->getTotal();
        $parameters['associations'] = $associations;
        return parent::render($view, $parameters, $response);
    }

    #[Route('/user', name: 'app_eventspage')]
    public function index(): Response
    {
        $blogPosts = $this->entityManager->getRepository(BlogPost::class)
            ->findBy([], ['createdAt' => 'DESC']); 

        $events = $this->entityManager->getRepository(Event::class)
            ->findBy([], ['eventDate' => 'DESC']);
        $associations = $this->entityManager->getRepository(Association::class)->findAll();

        return $this->render('page/index.html.twig', [
            'blogPosts' => $blogPosts,
            'events' => $events,
            'associations' => $associations
        ]);
    }

    #[Route('/quick-donate', name: 'app_quick_donate')]
    public function quickDonate(): Response
    {
        $this->cartService->addStandardDonation();
        return $this->redirectToRoute('app_checkout');
    }

    #[Route('/add-to-cart/{type}/{id}', name: 'app_add_to_cart')]
    public function addToCart(string $type, ?int $id = null): Response
    {
        $this->cartService->add($type, $id);
        return $this->redirectToRoute('app_panier');
    }

    #[Route('/contact-us', name: 'app_contact')]
    public function contact(): Response
    {
        $associations = $this->entityManager->getRepository(Association::class)->findAll();

        return $this->render('page/contact.html.twig', [
            'associations' => $associations
        ]);
    }

    #[Route('/Test', name: 'app_test')]
    public function test(): Response
    {
        $associations = $this->entityManager->getRepository(Association::class)->findAll();

        return $this->render('page/test.html.twig', [
            'associations' => $associations
        ]);
    }

    #[Route('/event/{id}', name: 'event_details', methods: ['GET'])]
    public function getEventDetails(Event $event): JsonResponse
    {
        return new JsonResponse([
            'id' => $event->getId(),
            'name' => $event->getName(),
            'price' => $event->getPrice(),
            'type' => $event->getType(),
            'eventDate' => $event->getEventDate()->format('Y-m-d'),
            'location' => [
                'name' => $event->getLocation()->getName(),
                'country' => $event->getLocation()->getCountry(),
                'youtubeUrl' => $event->getLocation()->getYoutubeEmbedUrl()
            ],
            'association' => [
                'name' => $event->getAssociation()->getName()
            ]
        ]);
    }

    #[Route('/panier', name: 'app_panier')]
    public function panier(): Response
    {
        return $this->render('page/panier.html.twig', [
            'cart' => $this->cartService->getCart(),
            'total' => $this->cartService->getTotal()
        ]);
    }

    #[Route('/update-cart/{type}/{quantity}', name: 'app_update_cart')]
    public function updateCart(string $type, int $quantity): JsonResponse
    {
        $this->cartService->update($type, $quantity);
        return $this->json([
            'total' => $this->cartService->getTotal()
        ]);
    }

    #[Route('/remove-from-cart/{type}', name: 'app_remove_from_cart')]
    public function removeFromCart(string $type): Response
    {
        $this->cartService->remove($type);
        return $this->redirectToRoute('app_panier');
    }
}