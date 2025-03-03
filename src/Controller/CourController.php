<?php

namespace App\Controller;

use App\Entity\Cour;
use App\Repository\CourRepository;
use App\Form\CourType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
#[Route('/cour')]


final class CourController extends AbstractController
{
    #[Route('/user', name: 'app_cour_index_user')]
    public function showCourFront(CourRepository $courRepository): Response
    {
        // afficher template 
        return $this->render('cour/indexFront.html.twig', [
            'cours' =>  $courRepository->findAll(),
        ]);
    }
    #[Route('/', name: 'app_cour_index')]
    public function showCour(CourRepository $courRepository): Response
    {
        // afficher template 
        return $this->render('cour/index.html.twig', [
            'cours' =>  $courRepository->findAll(),
        ]);
    }
    
    #[Route('/create', name: 'app_cour_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        // nouveau objet
        $cour = new Cour();
        $form = $this->createForm(CourType::class, $cour);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($cour);
            $entityManager->flush();
            return $this->redirectToRoute('app_cour_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Cour/new.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }   
    #[Route('/edit/{id}', name: 'app_cour_edit', methods: ['GET', 'POST'])]
    public function edit(EntityManagerInterface $entityManager,Request $request, $id, CourRepository $repo): Response
    {
        $cour = $repo->find($id);
        $form = $this->createForm(CourType::class, $cour);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_cour_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('Cour/edit.html.twig', [
            'cour' => $cour,
            'form' => $form,
        ]);
    }


    #[Route('/delete/{id}', name: 'app_cour_delete')]
    public function delete($id, ManagerRegistry $manager, CourRepository $repo): Response
    {
        $cour = $repo->find($id);
        
        if (!$cour) {
            throw $this->createNotFoundException('Le Cour avec l\'id '.$id.' n\'existe pas.');
        }
    
        $manager->getManager()->remove($cour);
        $manager->getManager()->flush();
    
        $this->addFlash('success', 'Le cour a été supprimée avec succès.');
        return $this->redirectToRoute('app_cour_index');
    }
}
