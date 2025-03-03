<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CategorieProduit;
use App\Entity\Produit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Repository\ProduitRepository;
final class ProduitASController extends AbstractController
{
    #[Route('/Association/produit', name: 'app_association')]
    public function index(EntityManagerInterface $entityManager, Request $request, ProduitRepository $produitRepository): Response
    {

        $categorieId = $request->query->get('categorie_id'); // Récupère l'ID de la catégorie filtrée
        $categories = $entityManager->getRepository(CategorieProduit::class)->findAll();
 
          if ($categorieId) {
            // Filtrer les produits par catégorie
            $produits = $entityManager->getRepository(Produit::class)->findBy(['categorie' => $categorieId]);
        } else {
            // Récupérer tous les produits si aucune catégorie n'est sélectionnée
            $produits = $entityManager->getRepository(Produit::class)->findAll();
        }
        
        // Fetch latest products (last 5)
        $latestProducts = $produitRepository->findLatestProducts(6);
        return $this->render('produitas/index.html.twig', [
            'categories' => $categories,
            'produits' => $produits,
            'latestProducts' => $latestProducts, 
        ]);
    }
    #[Route('/Association/produit/{id}/quantity', name: 'select_quantity')]
    public function selectQuantity(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = $entityManager->getRepository(Produit::class)->find($id);
    
        if (!$produit) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }
    
        // Create a form for selecting the quantity
        $form = $this->createFormBuilder()
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => [
                      'id'=>'quantity',
                    'min' => 1,
               
                 'max' => $produit->getQuantiteReelle()],
                'data' => 1
                // Default quantity
            ])
            ->getForm();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the selected quantity from the form
            $quantity = $form->getData()['quantity'];
    
            // Check if the selected quantity exceeds the available stock
            if ($quantity > $produit->getQuantiteReelle()) {
                // Add an error flash message if quantity is greater than available stock
                $this->addFlash('error', 'La quantité demandée dépasse le stock disponible.');
    
                // Re-render the form so the user can try again
                return $this->render('produitas/AjoutProduitToCart.html.twig', [
                    'produit' => $produit,
                    'form' => $form->createView(),
                ]);
            } else {
                // Redirect to the add to cart route with the selected quantity
                return $this->redirectToRoute('add_to_cart', [
                    'id' => $id,
                    'quantity' => $quantity
                ]);
            }
        }
    
        return $this->render('produitas/AjoutProduitToCart.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/Association/add-to-cart/{id}', name: 'add_to_cart')]
    public function addToCart(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = $entityManager->getRepository(Produit::class)->find($id);
    
        if (!$produit) {
            throw $this->createNotFoundException('Le produit n\'existe pas.');
        }
    
        // Retrieve the quantity from the form submission
        $formData = $request->query->all('form'); 
        $quantity = isset($formData['quantity']) ? (int) $formData['quantity'] : 1;
    
        // Get the session cart
        $session = $request->getSession();
        $cart = $session->get('cart', []);
    
        // Check if the product is already in the cart and get the current quantity
        $currentCartQuantity = $cart[$id] ?? 0;
    
        // Calculate the total quantity after adding
        $totalQuantity = $currentCartQuantity + $quantity;
    
        // Check if the total quantity exceeds available stock
        if ($totalQuantity > $produit->getQuantiteReelle()) {
            $cart[$id] =  $produit->getQuantiteReelle();
           /* $this->addFlash('error', 'La quantité totale demandée dépasse le stock disponible.');
            return $this->redirectToRoute('app_association');*/
        }
    else{
        // Add or update the product quantity in the cart
        $cart[$id] = $totalQuantity;
    }
        // Save the updated cart in session
        $session->set('cart', $cart);
    
        $this->addFlash('success', 'Produit ajouté au panier avec succès.');
    
        return $this->redirectToRoute('cart_view');
    }
    
#[Route('Association/cart', name: 'cart_view')]
public function viewCart(Request $request, EntityManagerInterface $entityManager): Response
{
    // Get the cart from the session
    $cart = $request->getSession()->get('cart', []);

    // Get product details for each item in the cart
    $products = [];
    foreach ($cart as $productId => $quantity) {
        $product = $entityManager->getRepository(Produit::class)->find($productId);
        if ($product) {
            $products[] = ['product' => $product, 'quantity' => $quantity];
        }
    }

    return $this->render('ProduitAs/ViewCart.html.twig', [
        'products' => $products,
    ]);
}
#[Route('/Association/remove-from-cart/{id}', name: 'remove_from_cart')]
public function removeFromCart(int $id, Request $request): Response
{
    $session = $request->getSession();
    $cart = $session->get('cart', []);

    // Vérifier si le produit est dans le panier
    if (isset($cart[$id])) {
        unset($cart[$id]); // Supprimer le produit du panier
        $session->set('cart', $cart); // Mettre à jour la session
    }

    // Rediriger vers la vue du panier après suppression
    return $this->redirectToRoute('cart_view');
}
#[Route('/Association/update-cart', name: 'update_cart', methods: ['POST'])]
public function updateCart(Request $request, EntityManagerInterface $entityManager): Response
{
    $session = $request->getSession();
    $cart = $session->get('cart', []);

    $updatedQuantities = $request->request->all();
    $errorMessages = [];

    foreach ($updatedQuantities['quantities'] as $id => $quantity) {
        $product = $entityManager->getRepository(Produit::class)->find($id);
        
        if (!$product) {
            continue; // Skip if the product does not exist
        }

        $availableStock = $product->getQuantiteReelle(); // Get available stock

        if ($quantity > $availableStock) {
            $cart[$id] = $availableStock; // Set the maximum available stock
            $errorMessages[] = "La quantité demandée pour '{$product->getNom()}' dépasse le stock disponible.";
        } else {
            $cart[$id] = max(1, (int) $quantity); // Prevent negative or zero quantity
        }
    }

    $session->set('cart', $cart); // Update the cart session

    if (!empty($errorMessages)) {
        foreach ($errorMessages as $message) {
            $this->addFlash('error', $message);
        }
    } else {
        $this->addFlash('success', 'Le panier a été mis à jour avec succès.');
    }

    return $this->redirectToRoute('cart_view');
}



}
