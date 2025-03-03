<?php

namespace App\Service;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const DONATION_ITEMS = [
        'money' => [
            'name' => 'Money Donation',
            'price' => 10.00,
            'image' => 'img/categories/catmoney.jpg',
            'type' => 'Money Donation'
        ],
        'clothes' => [
            'name' => 'Clothes Donation',
            'price' => 30.00,
            'image' => 'img/categories/catclothes.jpg',
            'type' => 'Clothes Donation'
        ],
        'vegetables' => [
            'name' => 'Vegetables Donation',
            'price' => 15.00,
            'image' => 'img/categories/cat-3.jpg',
            'type' => 'Vegetables Donation'
        ],
        'ramadan' => [
            'name' => 'Ramadan Pack',
            'price' => 100.00,
            'image' => 'img/categories/catramadan.png',
            'type' => 'Ramadan Pack'
        ],
        'meat' => [
            'name' => 'Meat Donation',
            'price' => 40.00,
            'image' => 'img/categories/cat-5.jpg',
            'type' => 'Meat Donation'
        ],
        'palastine' => [
            'name' => 'palastine Donation',
            'price' => 100.00,
            'image' => 'img/featured/palastine.jpg',
            'type' => 'palastine Donation'
        ],
        'event' => [
            'name' => 'Event Access Donation',
            'price' => 10.00,
            'image' => 'img/categories/cat-5.jpg',
            'type' => 'Event Access Donation'
        ]
    ];

    private $requestStack;
    private $container;

    public function __construct(RequestStack $requestStack, ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->container = $container;
    }

    public function add(string $type, ?int $id = null): void
{
    $session = $this->requestStack->getSession();
    $cart = $session->get('cart', []);

    $key = $id ? $type . '_' . $id : $type;

    if (!isset($cart[$key])) {
        if ($type === 'event' && $id) {
            $entityManager = $this->container->get('doctrine')->getManager();
            $event = $entityManager->getRepository(Event::class)->find($id);
            
            if ($event) {
                $cart[$key] = [
                    'item' => [
                        'name' => $event->getName(),
                        'price' => $event->getPrice(),
                        'image' => 'uploads/events/' . $event->getImageFilename(),
                        'type' => 'Event: ' . $event->getName(),
                        'event_id' => $event->getId() 
                    ],
                    'quantity' => 1
                ];
            }
        } elseif (isset(self::DONATION_ITEMS[$type])) {
            $cart[$key] = [
                'item' => self::DONATION_ITEMS[$type],
                'quantity' => 1
            ];
        }
    } else {
        $cart[$key]['quantity']++;
    }

    $session->set('cart', $cart);
}
    public function addStandardDonation(): void
{
    $session = $this->requestStack->getSession();
    $cart = $session->get('cart', []);

    $cart = [];
    
    $cart['standard'] = [
        'item' => [
            'name' => 'Standard Donation',
            'price' => 10.00,
            'image' => 'img/categories/catmoney.jpg',
            'type' => 'Standard Donation'
        ],
        'quantity' => 1
    ];

    $session->set('cart', $cart);
}

    public function update(string $type, int $quantity): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (isset($cart[$type])) {
            $cart[$type]['quantity'] = max(1, $quantity);
            $session->set('cart', $cart);
        }
    }

    public function remove(string $type): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('cart', []);

        if (isset($cart[$type])) {
            unset($cart[$type]);
            $session->set('cart', $cart);
        }
    }

    public function getCart(): array
{
    $session = $this->requestStack->getSession();
    $cart = $session->get('cart', []);
    return is_array($cart) ? $cart : [];
}

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $total += $item['item']['price'] * $item['quantity'];
        }
        return $total;
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove('cart');
    }
    public function getCartSummary(): array
{
    $cart = $this->getCart();
    $items = [];
    $total = 0;

    foreach ($cart as $type => $item) {
        $items[] = [
            'type' => $item['item']['type'],
            'quantity' => $item['quantity'],
            'price' => $item['item']['price'],
            'subtotal' => $item['item']['price'] * $item['quantity']
        ];
        $total += $item['item']['price'] * $item['quantity'];
    }

    return [
        'items' => $items,
        'total' => $total,
        'type' => count($items) > 1 ? 'Multiple Donations' : $items[0]['type']
    ];
}
}