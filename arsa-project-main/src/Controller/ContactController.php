<?php

namespace App\Controller;

use App\Entity\Association;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\BlogPost;

class ContactController extends AbstractController
{
    #[Route('/contact-us', name: 'app_contact')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $associations = $entityManager->getRepository(Association::class)->findAll();

        return $this->render('page/contact.html.twig', [
            'contact_active' => true,
            'associations' => $associations
        ]);
    }

    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $mediaFile = $request->files->get('mediaFile');
        
        if (!$mediaFile) {
            $this->addFlash('error', 'Please upload a file');
            return $this->redirectToRoute('app_contact');
        }

        $originalFilename = pathinfo($mediaFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$mediaFile->guessExtension();

        try {
            $mediaFile->move(
                $this->getParameter('blog_uploads_directory'),
                $newFilename
            );

            $blogPost = new BlogPost();
            $blogPost->setName($request->request->get('name'));
            $blogPost->setEmail($request->request->get('email'));
            $blogPost->setMessage($request->request->get('message'));
            $blogPost->setMediaFile($newFilename);

            $entityManager->persist($blogPost);
            $entityManager->flush();

            $this->addFlash('success', 'Your message has been sent successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'There was an error sending your message');
        }

        return $this->redirectToRoute('app_contact');
    }
}