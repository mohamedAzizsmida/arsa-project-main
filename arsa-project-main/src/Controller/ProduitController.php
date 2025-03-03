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

        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('imagesProduit_directory'),
                $newFilename
            );
            $produit->setImage($newFilename);
        }

        $produit->setUser($user);
        $realQuantity = $form->get('quantite')->getData(); 
        $produit->setQuantiteReelle($realQuantity);

        $entityManager->persist($produit);
        $entityManager->flush();

        $this->addFlash('success', 'Produit modifié avec succès !');

        return $this->redirectToRoute('app_produit_details', [
            'id_produit' => $produit->getIdProduit()
        ]);
    }

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

    #[Route('/Entreprise', name: 'app_Entreprise_home')]
    public function Home(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'Entreprise@example.com']);
        
        // Fetching all products for the current user
        $produits = $entityManager->getRepository(Produit::class)->findBy(['user' => $user]);
    
        // Calculate the statistics
        $totalProducts = count($produits);  // Total number of products
        $totalQuantity = array_sum(array_map(function ($produit) {
            return $produit->getQuantiteReelle();
        }, $produits));  // Total quantity of all products
        $averageQuantity = $totalProducts > 0 ? $totalQuantity / $totalProducts : 0;  // Average quantity per product
    
        // Other statistics, e.g., number of categories
        $categorieRepository = $entityManager->getRepository(CategorieProduit::class);
        $totalCategories = count($categorieRepository->findAll());  // Total categories
    
        return $this->render('produit/Home.html.twig', [
            'produits' => $produits,
            'totalProducts' => $totalProducts,
            'totalQuantity' => $totalQuantity,
            'averageQuantity' => $averageQuantity,
            'totalCategories' => $totalCategories,
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
