<?php
namespace App\Controller;

use App\Entity\Post;
use App\Entity\Commentaire;
use App\Form\SearchPostType;
use App\Form\PostType;

use App\Form\CommentaireType;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostController extends AbstractController
{
  /*  #[Route('/post', name: 'app_post')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création d'un nouvel article
        $blogPost = new Post();
        $form = $this->createForm(PostType::class, $blogPost);
        $form->handleRequest($request);

        // Vérification si le formulaire d'ajout est soumis
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
      /*   $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $blogPost->setImage($newFilename);
            }

            $entityManager->persist($blogPost);
            $entityManager->flush();
            $this->addFlash('success', 'Article ajouté avec succès.');
            return $this->redirectToRoute('app_post');
        }

        // Récupération des articles existants
        $posts = $entityManager->getRepository(Post::class)->findAll();

        return $this->render('blog/blogfront.html.twig', [
            'form' => $form->createView(),
            'posts' => $posts,  
        ]);
    }*/


  #[Route('/postFront', name: 'app_post_front')]
    public function indexFront(ManagerRegistry $doctrine): Response
    {
        $postRepo = $doctrine->getRepository(Post::class);
        $posts = $postRepo->findAll();
    
        return $this->render('blog/blogfront.html.twig', [
            'posts' => $posts,
        ]);
    }
    
/*

    #[Route('/post/{id}', name: 'app_post_show', methods: ['GET', 'POST'])]
    public function show(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentaire = new Commentaire();
        $commentaire->setPost($post);
    
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }
    
        return $this->render('post/show.html.twig', [
            'post' => $post,
            'form' => $form->createView(), // 🔥 On passe la variable 'form' ici
        ]);
    }
    */
   /* #[Route('/post/{id}/comment', name: 'app_post_comment', methods: ['GET', 'POST'])]
    public function comment(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Lier le commentaire au post
            $commentaire->setPost($post);
            
            // Si l'utilisateur est connecté, on récupère son ID
           // $user = $this->getUser();
          /*  if ($user) {
                $commentaire->setIdUser($user->getId()); // Lier l'utilisateur
            } else {
                $commentaire->setIdUser(null); // Laisser l'ID utilisateur à null
            }*/
        
            // Sauvegarder le commentaire
         /*   $entityManager->persist($commentaire);
            $entityManager->flush();
        
            $this->addFlash('success', 'Commentaire ajouté avec succès.');
        
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }
        
        return $this->render('post/comment.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }
 */  
/* 
  #[Route('/post/search', name: 'app_post_search')]
    public function search(Request $request, PostRepository $postRepository): Response
    {
        $form = $this->createForm(SearchPostType::class);
        $form->handleRequest($request);
    
        $posts = [];
    
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $posts = $postRepository->searchPosts($criteria);
        }
    
        return $this->render('blog/search.html.twig', [
            'form' => $form->createView(),
            'posts' => $posts
        ]);
    }*/
    #[Route('/postViews/{id}', name: 'post_show')]
    public function show(Post $post, EntityManagerInterface $em): Response
    {
        $post->incrementViews();
        $em->flush();

        return $this->render('post/index.html.twig', [
            'post' => $post,
        ]);
    }
    #[Route('/postLikes/{id}/like', name: 'post_like', methods: ['POST'])]
    public function like(Post $post, EntityManagerInterface $em): Response
    {
        $post->incrementLikes();
        $em->flush();

        return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
    }

   


    #[Route('/post/search', name: 'app_post_search')]
public function searchPost(Request $request, PostRepository $postRepository): Response
{
    $form = $this->createForm(SearchPostType::class);
    $form->handleRequest($request);

    $posts = [];

    if ($form->isSubmitted() && $form->isValid()) {
        $criteria = $form->getData();
        dump($criteria); die; // Vérifier si 'contenu' a bien une valeur
        // Si les critères sont stockés dans un objet Post, tu peux convertir cet objet en tableau
        // par exemple, avec un tableau associatif contenant les données nécessaires pour la recherche
        $criteriaArray = [
            'contenu' => $criteria->getContenu(),
           
            // Ajoute d'autres critères ici selon ce que ton formulaire retourne
        ];

        // Passe le tableau des critères à la méthode de recherche
        $posts = $postRepository->searchPosts($criteriaArray);
    }

    return $this->render('blog/search.html.twig', [
        'form' => $form->createView(),
        'posts' => $posts,
    ]);
}


    
}
