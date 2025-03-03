<?php

namespace App\Controller;
use App\Entity\Commentaire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
class CommentaireController extends AbstractController
{
    /*#[Route('/commentaire', name: 'app_commentaire')]
    public function index(): Response
    {
        return $this->render('commentaire/index.html.twig', [
            'controller_name' => 'CommentaireController',
        ]);
    }*/

   /* #[Route('/commentaire', name: 'app_commentaire_index', methods: ['GET'])]
    public function index(CommentaireRepository $commentaireRepository): Response
    {
        return $this->render('commentaire/index.html.twig', [
            'commentaires' => $commentaireRepository->findAll(),
        ]);
    }*/
    #[Route('/commentaire/{id}', name: 'app_commentaire_index', methods: ['GET'])]
public function index(int $id, CommentaireRepository $commentaireRepository): Response
{
    // Récupérer les commentaires pour un post spécifique
    $commentaires = $commentaireRepository->findBy(['post' => $id]);

    return $this->render('commentaire/index.html.twig', [
        'commentaires' => $commentaires,
        'postId' => $id
    ]);
}


    #[Route('/new', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_commentaire_index');
        }

        return $this->render('commentaire/new.html.twig', [
            'commentaire' => $commentaire,
            'form' => $form->createView(),
        ]);
    }

  /*  #[Route('/{id}', name: 'app_commentaire_show', methods: ['GET'])]
    public function show(Commentaire $commentaire): Response
    {
        return $this->render('commentaire/show.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }*/

    #[Route('/commentaire/{id}/show', name: 'app_commentaire_show')]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $commentaire = $entityManager->getRepository(Commentaire::class)->find($id);
    
        if (!$commentaire) {
            throw $this->createNotFoundException("Le commentaire avec l'ID $id n'existe pas.");
        }
    
        return $this->render('commentaire/show.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }
    


    #[Route('/{id}/edit', name: 'app_commentaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_commentaire_index');
        }

        return $this->render('commentaire/edit.html.twig', [
            'commentaire' => $commentaire,
            'form' => $form->createView(),
        ]);
    }
   /* #[Route('/{id}', name: 'app_commentaire_delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_commentaire_index');
    }
*/

   /* #[Route('/commentaire/{id}/delete', name: 'app_commentaire_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentaire = $entityManager->getRepository(Commentaire::class)->find($id);
    
        if (!$commentaire) {
            throw $this->createNotFoundException("Le commentaire avec l'ID $id n'existe pas.");
        }
    
        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }
    
        return $this->redirectToRoute('app_commentaire_index');
    }*/
    #[Route('/commentaire/{id}/delete', name: 'app_commentaire_delete', methods: ['POST'])]
public function delete(int $id, Request $request, EntityManagerInterface $entityManager, CommentaireRepository $commentaireRepository): Response
{
    $commentaire = $commentaireRepository->find($id);
    if (!$commentaire) {
        throw $this->createNotFoundException('Commentaire introuvable');
    }

    // ✅ Récupérer l'ID du post avant suppression
    $postId = $commentaire->getPost()->getId();

    if ($this->isCsrfTokenValid('delete'.$commentaire->getId(), $request->request->get('_token'))) {
        $entityManager->remove($commentaire);
        $entityManager->flush();
    }

    // ✅ Redirection en passant l'ID du post
    return $this->redirectToRoute('app_commentaire_index', ['id' => $postId]);
}


    
    


}
