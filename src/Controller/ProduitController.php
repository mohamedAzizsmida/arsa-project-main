<?php

namespace App\Controller;
use App\Entity\Produit;  // ✅ Import de l'entité Produit
use App\Form\AddProduitType;  // ✅ Import du formulaire ProduitType
use Doctrine\ORM\EntityManagerInterface;  // ✅ Import de l'EntityManager
use App\Entity\CategorieProduit;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\User;
use Symfony\Component\Form\FormError;

use App\Entity\CommandeProduit;
use App\Repository\CommandeProduitRepository;
use App\Repository\ProduitRepository;

final class ProduitController extends AbstractController
{

    #[Route('/Entreprise/produit/details/{id_produit}', name: 'app_produit_details')]
    public function showProduitDetails(
        Produit $produit, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
    
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'Entreprise@example.com']);
        
        $categorieRepository = $entityManager->getRepository(CategorieProduit::class);
        $categories = $categorieRepository->findAll();
     
        // Handle the delete image action if it's submitted
        if ($request->get('action') === 'delete_image') {
            if ($produit->getImage()) {
                $oldImagePath = $this->getParameter('imagesProduit_directory') . '/' . $produit->getImage();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);  // Delete the image file
                }
                $produit->setImage(null);  // Remove the image from the entity
                $entityManager->persist($produit);
                $entityManager->flush();
                
                $this->addFlash('success', 'Image supprimée avec succès.');
    
                return $this->redirectToRoute('app_produit_details', [
                    'id_produit' => $produit->getIdProduit()
                ]);
            }
        }
    
        // Process form submission (edit product)
        $form = $this->createForm(AddProduitType::class, $produit);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
    
            // Handle the image upload
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('imagesProduit_directory'),
                    $newFilename
                );
                $produit->setImage($newFilename);
            }
            
            
    // Get the sum of ordered quantities from CommandeProduitRepository
    $commandeProduitRepository = $entityManager->getRepository(CommandeProduit::class);
    $orderedQuantity = $commandeProduitRepository->getSumOfOrderedQuantity($produit->getIdProduit());

            // Get the modified quantity from the form
            $modifiedQuantity = $form->get('quantite')->getData();
    
    
            if ($modifiedQuantity < $orderedQuantity) {
                // Add a form error if the modified quantity is not greater than (quantite - quantiteReelle)
                $form->get('quantite')->addError(new FormError('La quantité modifiée doit être supérieure à '.$orderedQuantity));
                
                // Return the form with the error
                return $this->render('produit/EditProduit.html.twig', [
                    'form' => $form->createView(),
                    'produit' => $produit,
                    'categories' => $categories,
                ]);
            }
    else{
            // Update the product with the modified quantity (set the real quantity)
            $produit->setQuantiteReelle($modifiedQuantity-$orderedQuantity);
            $produit->setUser($user); // Associate user with the product
    
            // Persist the product changes
            $entityManager->persist($produit);
            $entityManager->flush();
    
            $this->addFlash('success', 'Produit modifié avec succès !');
    
            return $this->redirectToRoute('app_produit_details', [
                'id_produit' => $produit->getIdProduit()
            ]);}
        }
    
        // If the form was not submitted or is invalid, render the edit form again
        return $this->render('produit/EditProduit.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
            'categories' => $categories,
        ]);
    }
    

    #[Route('/Entreprise/produit/ShowAll', name: 'app_produit_show_all')]
    public function ShowAllProduit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'Entreprise@example.com']);
        
        $produits = $entityManager->getRepository(Produit::class)->findBy(['user' => $user]);

        return $this->render('produit/ShowAllproduit.html.twig', [
            'produits' => $produits,
        ]); 
    }
    #[Route('/produit/delete/{id_produit}', name: 'app_produit_delete')]
    public function deleteProduit($id_produit, EntityManagerInterface $entityManager): Response
    {
        $produit = $entityManager->getRepository(Produit::class)->find($id_produit);
        
        if ($produit) {
            // Check if quantite equals quantiteReelle before allowing deletion
            if ($produit->getQuantite() == $produit->getQuantiteReelle()) {
                $entityManager->remove($produit);
                $entityManager->flush();
                // Add a flash message to notify the user
                $this->addFlash('success', 'Produit supprimé avec succès.');
            } else {
                // Add a flash message for when the quantities do not match
                $this->addFlash('error', 'La quantité et la quantité réelle ne correspondent pas. Suppression impossible.');
            }
        } else {
            $this->addFlash('error', 'Produit introuvable.');
        }
    
        return $this->redirectToRoute('app_produit_show_all');
    }
    


#[Route('/Entreprise', name: 'app_Entreprise_home')]
public function Home(EntityManagerInterface $entityManager, ProduitRepository $produitRepository): Response
{
    // Retrieve the user (e.g., the entreprise)
    $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'Entreprise@example.com']);

    // If the user is not found, handle the error
    if (!$user) {
        throw $this->createNotFoundException('Entreprise not found!');
    }

    // Get statistics for the logged-in user
    $totalProduitsDonnes = $produitRepository->getTotalProduitsDonnesByUser($user); // You should define this method if it's not already defined
    $produitsParMois = $produitRepository->getProduitsParMoisByUser($user);
    $produitsNonDistribues = $produitRepository->getProduitsNonDistribuesByUser($user);
    $donationsByCategory = $produitRepository->getDonationsByCategory($user); 
    $totalProduitsDistribues = $produitRepository->getTotalProduitsDistribues($user);
       $totalQuantities = $produitRepository->getTotalQuantities($user); 
    
     $topOrderedProducts = $produitRepository->getTopOrderedProducts($user);
     
     $topLeastOrderedProducts = $produitRepository->getTop5LeastOrderedProducts($user);
     
     $rawData = $produitRepository->getMonthlyDonationsByCategory($user);

    $variationDemandes = $produitRepository->getVariationDemandesProduits($user);


     // Obtenez une liste unique des mois
    /* $months = [];
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

    
    
    // Return the data to the view
    return $this->render('produit/Home.html.twig', [
        'totalProduitsDonnes' => $totalProduitsDonnes,
        'produitsParMois' => $produitsParMois,
        'produitsNonDistribues' => $produitsNonDistribues,
       'donationsByCategory' => $donationsByCategory,
        'totalProduitsDistribues' => $totalProduitsDistribues,
        'topOrderedProducts' => $topOrderedProducts,
        'topLeastOrderedProducts' => $topLeastOrderedProducts,
        'totalQuantities' => $totalQuantities,
         // Chart data for monthly donations by category:
     'months' => $months,
        'datasets' => $datasets,
    ]);
}

    

    #[Route('/Entreprise/produit/Ajout', name: 'app_produit')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'Entreprise@example.com']);
        
         // Fetch categories from the database
         $categorieRepository = $entityManager->getRepository(CategorieProduit::class);
         $categories = $categorieRepository->findAll();
         
        $form = $this->createForm(AddProduitType::class, $produit);
 
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                
                // Générer un nom unique pour l'image
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                // Déplacer l'image dans le dossier public/uploads
                $imageFile->move(
                    $this->getParameter('imagesProduit_directory'), // Paramètre dans config/services.yaml
                    $newFilename
                );

                // Enregistrer le chemin dans l'entité
                $produit->setImage($newFilename);
            }
            $produit->setUser($user);
              // Set the real quantity (assuming you have the field in your form)
             $realQuantity = $form->get('quantite')->getData(); // Assuming the form has a quantiteReelle field
             $produit->setQuantiteReelle($realQuantity);
            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit ajouté avec succès !');

            return $this->redirectToRoute('app_produit');

        }
        return $this->render('produit/AjoutProduit.html.twig', [
            'form' => $form->createView(), // ✅ Passer le formulaire à Twig
            'errors' => $form->getErrors(true, false),  // Get all form errors
        ]);
    }
}
