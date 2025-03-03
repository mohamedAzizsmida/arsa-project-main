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
           // $newQuantity = $product->getQuantite() + $quantity;
            //$product->setQuantite($newQuantity);
        }
    }

    $entityManager->persist($commande);
    $entityManager->flush();

    // Vider le panier après la commande
    $session->remove('cart');

    $this->addFlash('success', 'Commande enregistrée avec succès !');
    return $this->redirectToRoute('historique_commandes');
}

#[Route('/association/historique-commandes', name: 'historique_commandes')]
public function historiqueCommandes(EntityManagerInterface $entityManager): Response
{
    $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'Association1@gmail.com']);


    // Fetch all commandes for the logged-in user
    $commandes = $entityManager->getRepository(Commande::class)->findBy(['user' => $user]);

    return $this->render('produitAs/historiqueCommandes.html.twig', [
        'commandes' => $commandes,
    ]);
}
#[Route('/association/commande/{id}', name: 'commande_details')]
public function commandeDetails($id, EntityManagerInterface $entityManager): Response
{
    // Find the commande by its ID
    $commande = $entityManager->getRepository(Commande::class)->find($id);

    // Check if the commande exists
    if (!$commande) {
        throw $this->createNotFoundException('Commande non trouvée');
    }

    return $this->render('produitAs/commande_details.html.twig', [
        'commande' => $commande,  // Pass the commande to the template
    ]);
}

}
