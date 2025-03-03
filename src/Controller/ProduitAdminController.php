<?php
namespace App\Controller;


use App\Repository\ProduitRepository; // Correct use statement
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Commande;
use App\Repository\CommandeRepository; 
use Symfony\Component\HttpFoundation\RedirectResponse;

final class ProduitAdminController extends AbstractController
{
    #[Route('/admin/produit', name: 'produit_admin')]
    public function index(EntityManagerInterface $entityManager, ProduitRepository $produitRepository): Response
    {



        // Fetch all commandes for the logged-in user
        $commandes = $entityManager->getRepository(Commande::class)->findAll();
   
   

    // Get statistics for the logged-in user
    $totalProduitsDonnes = $produitRepository->getTotalProduitsDonnesByUser(); // You should define this method if it's not already defined
    $produitsParMois = $produitRepository->getProduitsParMoisByUser();
    $produitsNonDistribues = $produitRepository->getProduitsNonDistribuesByUser();
    $donationsByCategory = $produitRepository->getDonationsByCategory(); 
    $totalProduitsDistribues = $produitRepository->getTotalProduitsDistribues();
       $totalQuantities = $produitRepository->getTotalQuantities(); 
    
     $topOrderedProducts = $produitRepository->getTopOrderedProducts();
     
     $topLeastOrderedProducts = $produitRepository->getTop5LeastOrderedProducts();
     
     $rawData = $produitRepository->getMonthlyDonationsByCategory();

    /* $variationDemandes = $produitRepository->getVariationDemandesProduits();

     // Obtenez une liste unique des mois
     $months = [];
     foreach ($variationDemandes as $row) {
         $months[] = $row['mois'];
     }
     $months = array_values(array_unique($months)); // Pour obtenir une liste unique et triée
     */
    $months = [];
    $categories = [];
    $categoryData = [];

    foreach ($rawData as $row) {
        $months[] = $row['mois'];
        $categories[$row['category']] = true;
    }

    $months = array_values(array_unique($months));
    sort($months);
    $categories = array_keys($categories);

    foreach ($categories as $category) {
        foreach ($months as $month) {
            $categoryData[$category][$month] = 0;
        }
    }

    foreach ($rawData as $row) {
        $categoryData[$row['category']][$row['mois']] = (int)$row['totalQuantity'];
    }

    $datasets = [];
    $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#FF9F40'];
    $i = 0;

    foreach ($categoryData as $category => $data) {
        $datasets[] = [
            'label' => $category,
            'data' => array_values($data),
            'backgroundColor' => $colors[$i % count($colors)],
        ];
        $i++;
    }

    
 
        return $this->render('produit_admin/index.html.twig', [
            'commandes' => $commandes,
            'totalProduitsDonnes' => $totalProduitsDonnes,
            'produitsParMois' => $produitsParMois,
            'produitsNonDistribues' => $produitsNonDistribues,
           'donationsByCategory' => $donationsByCategory,
            'totalProduitsDistribues' => $totalProduitsDistribues,
            'topOrderedProducts' => $topOrderedProducts,
            'topLeastOrderedProducts' => $topLeastOrderedProducts,
            'totalQuantities' => $totalQuantities,
         //   'variationDemandes' => $variationDemandes,
             // Chart data for monthly donations by category:
         'months' => $months,
            'datasets' => $datasets,
        ]);
    }


// Route to validate the order
#[Route('/admin/produit/Commande/validate_order/{id}', name: 'validate_order')]
public function validateOrder(int $id, EntityManagerInterface $entityManager, CommandeRepository $commandeRepository): RedirectResponse
{
    // Fetch the order by ID using the injected CommandeRepository
    $commande = $commandeRepository->find($id);

    // Check if the order exists
    if ($commande) {
        // Check if the order status is not already 1 (validated)
        if ($commande->getStatus() !== 1 && $commande->getStatus() !== "1") {
            // Set the order status to '1' (validated)
            $commande->setStatus(1); // Assuming 1 means 'Validated'

            // Iterate over each product in the order
            foreach ($commande->getCommandeProduits() as $commandeProduit) {
                $produit = $commandeProduit->getProduit();
                
                // Check if the product exists and has sufficient stock
                if ($produit && $produit->getQuantiteReelle() >= $commandeProduit->getQuantite()) {
                    // Decrease the real quantity based on the quantity ordered
                    $produit->setQuantiteReelle($produit->getQuantiteReelle() - $commandeProduit->getQuantite());
                } else {
                    // Handle the case where there isn't enough stock
                    $this->addFlash('error', "Stock insuffisant pour le produit: " . $produit->getNom());
                  
                    // Generate the URL with the fragment
                    $url = $this->generateUrl('produit_admin') . '#commandes';
                    return new RedirectResponse($url);
                }
            }

            // Save the changes to the database
            $entityManager->flush();

            // Add a flash message for success
            $this->addFlash('success', 'Commande validée avec succès!');
        } else {
            // If the order is already validated, display a message
            $this->addFlash('info', 'La commande a déjà été validée.');
        }

        // Generate the URL with the fragment
        $url = $this->generateUrl('produit_admin') . '#commandes';
        return new RedirectResponse($url);
    }

    // If the order doesn't exist, redirect back to the admin page
    $this->addFlash('error', 'Commande non trouvée!');
    
    // Generate the URL with the fragment
    $url = $this->generateUrl('produit_admin') . '#commandes';
    return new RedirectResponse($url);
}





}

