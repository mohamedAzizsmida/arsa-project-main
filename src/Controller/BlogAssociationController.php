<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twilio\Rest\Client;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\PostType;
use App\Repository\PostRepository;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use DateTime;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\Request;

#[Route('/blog')]

class BlogAssociationController extends AbstractController
{
   /* #[Route('/association', name: 'app_association')]
    public function index(): Response
    {
        return $this->render('association/index.html.twig', [
            'controller_name' => 'AssociationController',
        ]);
    }*/
   /* #[Route('/association', name: 'app_post_front')]
    public function listAuth(ManagerRegistry $doctrine): Response
    {   
       /* $authors = $doctrine->getRepository(Author::class)->findAll();*/
          
      /*     $postRepo=$doctrine->getRepository(Post::class);
            $blogPosts = $postRepo->findAll();
            
        return $this->render('blog/blogback.html.twig', [
            'blogPosts' => $blogPosts,
        ]);
    } */

    #[Route('/post/{id}', name: 'app_post_show_qr')]
    public function show(Post $post): Response
    {
        return $this->render('post/showPost.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/post/{id}/qrcode', name: 'app_post_qrcode')]
    public function qrcode(Post $post): Response
    {
        $url = $this->generateUrl('app_post_show_qr', ['id' => $post->getId()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $content = $post->getContenu(); // Get the post's content

        
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($content)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Low) // Correct usage
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin) // Correct usage
            ->build();

        $dataUri = $result->getDataUri();

        return $this->render('post/qrcode.html.twig', [
            'qrcode' => $dataUri,
            'post' => $post,
        ]);
    }

    #[Route('/association', name: 'app_association_blog')]
    public function association(Request $request, EntityManagerInterface $entityManager, PostRepository $postRepository): Response
    {
        $blogPost = new Post(new DateTime());
        $form = $this->createForm(PostType::class, $blogPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
        
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $blogPost->setImage($newFilename); // ✅ Lier le fichier au Post
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image : ' . $e->getMessage());
                }
            }
            $entityManager->persist($blogPost);
            $entityManager->flush();

            return $this->redirectToRoute('app_association_blog');
        }

        $sort = $request->query->get('sort', 'desc'); // Default to 'desc'
        $blogPosts = $postRepository->findAllSortedByDate($sort);

        return $this->render('blog/blogback.html.twig', [
            'form' => $form->createView(),
            'blogPosts' => $blogPosts,
            'sort' => $sort,
        ]);
    }


  #[Route('/association/{id}/edit', name: 'app_association_edit')]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
          $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $post->setImage($newFilename);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Article modifié avec succès.');
            return $this->redirectToRoute('app_association_blog');
        }

        return $this->render('blog/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }



    #[Route('/association/{id}/delete', name: 'app_association_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le token CSRF pour sécuriser la suppression
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            // Récupérer les commentaires liés au post
            $comments = $post->getCommentaires();
    
            // Supprimer les commentaires liés au post
            foreach ($comments as $comment) {
                $entityManager->remove($comment); // Supprimer chaque commentaire
            }
    
            // Ensuite, supprimer le post
            $entityManager->remove($post);
            $entityManager->flush();
    
            $this->addFlash('success', 'Article supprimé avec succès, ainsi que ses commentaires.');
        }
    
        return $this->redirectToRoute('app_association_blog');
    }
    
 
    /*#[Route('/association/{id}/delete', name: 'app_association_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Article supprimé avec succès.');
        }

        return $this->redirectToRoute('app_association');
    }
*/



}
