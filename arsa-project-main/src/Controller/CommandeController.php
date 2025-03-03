<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Commande;
use App\Entity\CommandeProduit;
use App\Entity\User;

final class CommandeController extends AbstractController
{
  
#[Route('/association/finaliser-commande', name: 'finaliser_commande')]
public function finaliserCommande(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'Association1@gmail.com']);
        
    $session = $request->getSession();
    

    $cart = $session->get('cart', []);

    if (empty($cart)) {
        $this->addFlash('error', 'Votre panier est vide.');
        return $this->redirectToRoute('cart_view');
    }

    // Créer une nouvelle commande
    $commande = new Commande();
    $commande->setUser($user); 
    $commande->setDateCommande(new \DateTime());
   // $commande->setStatus('En attente');

    foreach ($cart as $productId => $quantity) {
        $product = $entityManager->getRepository(\App\Entity\Produit::class)->find($productId);
        if ($product) {
            // Enregistrer chaque produit dans CommandeProduit
            $commandeProduit = new CommandeProduit();
            $commandeProduit->setCommande($commande);
            $commandeProduit->setProduit($product);
            $commandeProduit->setQuantite($quantity);

            $entityManager->persist($commandeProduit);
            
            // update la quantité du produit
            $newQuantity = $product->getQuantite() + $quantity;
            $product->setQuantite($newQuantity);
        }
    }

    $entityManager->persist($commande);
    $entityManager->flush();

    // Vider le panier après la commande
    $session->remove('cart');

    $this->addFlash('success', 'Commande enregistrée avec succès !');
    return $this->redirectToRoute('cart_view');
}

}
