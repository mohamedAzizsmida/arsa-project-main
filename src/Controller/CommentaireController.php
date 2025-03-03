<?php

namespace App\Controller;
use App\Entity\Commentaire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\Request;
class CommentaireController extends AbstractController
{
    private $commentaireRepository; // Déclarez la propriété

    public function __construct(CommentaireRepository $commentaireRepository)
    {
        $this->commentaireRepository = $commentaireRepository; // Initialisez la propriété
    }
   

    #[Route('/commentaire/{id}', name: 'app_commentaire_index', methods: ['GET'])]
public function index(int $id): Response
{
    // Récupérer les commentaires pour un post spécifique
    $commentaires = $this->commentaireRepository->findCommentairesByPostId($id);

    return $this->render('commentaire/index.html.twig', [
        'commentaires' => $commentaires,
        'postId' => $id,
    ]);
}
#[Route('/commentaireBack/{id}', name: 'app_commentaire_back', methods: ['GET'])]
public function comment(int $id): Response
{
    // Récupérer les commentaires pour un post spécifique
    $commentaires = $this->commentaireRepository->findCommentairesByPostId($id);

    return $this->render('post/showAssociation.html.twig', [
        'commentaires' => $commentaires,
        'postId' => $id,
    ]);
}
   
#[Route('/new/{postId}', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    CommentaireRepository $commentaireRepository,
    int $postId
): Response {
    $commentaire = new Commentaire();

    // ✅ Récupérer le post depuis son ID
    $post = $entityManager->getRepository(Post::class)->find($postId);

    if (!$post) {
        throw $this->createNotFoundException('Post non trouvé avec l\'ID ' . $postId);
    }

    // ✅ Lier le post au commentaire
    $commentaire->setPost($post);

    $form = $this->createForm(CommentaireType::class, $commentaire);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($commentaire);
        $entityManager->flush();

        // ✅ Récupérer l'ID du post pour la redirection
        return $this->redirectToRoute('app_commentaire_index', ['id' => $post->getId()]);
    }

    return $this->render('commentaire/new.html.twig', [
        'commentaire' => $commentaire,
        'form' => $form->createView(),
    ]);
}
#[Route('/{id}/edit', name: 'app_commentaire_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
{
    // Création du formulaire de modification
    $form = $this->createForm(CommentaireType::class, $commentaire);
    $form->handleRequest($request);

    // Si le formulaire est soumis et valide
    if ($form->isSubmitted() && $form->isValid()) {
        // Sauvegarde des modifications dans la base de données
        $entityManager->flush();

        // Redirection vers la liste des commentaires du post après modification
        return $this->redirectToRoute('app_commentaire_back', [
            'id' => $commentaire->getPost()->getId()  // Récupère l'ID du post associé au commentaire
        ]);
    }

    // Affichage du formulaire d'édition
    return $this->render('commentaire/edit.html.twig', [
        'commentaire' => $commentaire,
        'form' => $form->createView(),
    ]);
}

/*
#[Route('/{id}/edit', name: 'app_commentaire_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Commentaire $commentaire, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(CommentaireType::class, $commentaire);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        return $this->redirectToRoute('app_commentaire_back');
    }

    return $this->render('commentaire/edit.html.twig', [
        'commentaire' => $commentaire,
        'form' => $form->createView(),
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
    
/*#[Route('/new', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, CommentaireRepository $commentaireRepository): Response
{
    $commentaire = new Commentaire();
    $form = $this->createForm(CommentaireType::class, $commentaire);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($commentaire);
        $entityManager->flush();

        // ✅ Récupération du postId via le repository
        $postId = $commentaireRepository->findPostIdByCommentaire($commentaire);

        

        return $this->redirectToRoute('app_commentaire_index', ['id' => $postId]);
    }

    return $this->render('commentaire/new.html.twig', [
        'commentaire' => $commentaire,
        'form' => $form->createView(),
    ]);
}


  /* #[Route('/new', name: 'app_commentaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();
 
  // Rediriger vers la route 'app_commentaire_index' avec l'ID du post
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
 /*#[Route('/commentaire', name: 'app_commentaire')]
    public function index(): Response
    {
        return $this->render('commentaire/index.html.twig', [
            'controller_name' => 'CommentaireController',
        ]);
    }*/

  
   /* #[Route('/commentaire/{id}', name: 'app_commentaire_index', methods: ['GET'])]
public function index(int $id, CommentaireRepository $commentaireRepository): Response
{
    // Récupérer les commentaires pour un post spécifique
    $commentaires = $commentaireRepository->findBy(['post' => $id]);

    return $this->render('commentaire/index.html.twig', [
        'commentaires' => $commentaires,
        'postId' => $id
    ]);
}
*/
    


   
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
