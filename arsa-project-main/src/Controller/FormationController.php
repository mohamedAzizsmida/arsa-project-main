<?php

namespace App\Controller;

use DateTime;
use App\Entity\Formation;
use App\Entity\Cour;
use App\Repository\FormationRepository;
use App\Form\FormationType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
#[Route('/formation')]


class FormationController extends AbstractController
{
    #[Route('/user', name: 'app_formation_index_user')]
    public function showFormationFront(FormationRepository $formationRepository): Response
    {
        // afficher template 
        return $this->render('Formation/FormationFront.html.twig', [
            'formations' =>  $formationRepository->findAll(),
        ]);
    }
    // Route pour afficher la page des formations
    #[Route('/', name: 'app_formation_index')]
    public function showFormation(FormationRepository $formationRepository): Response
    {
        // afficher template 
        return $this->render('Formation/Formation.html.twig', [
            'formations' =>  $formationRepository->findAll(),
        ]);
    }
    
    // Route pour créer une nouvelle formation
    #[Route('/create', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentDate = new DateTime();
        // nouveau objet
        $formation = new Formation($currentDate);
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formation);
            $entityManager->flush();
            return $this->redirectToRoute('app_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('Formation/new.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // Route pour mettre à jour une formation
    #[Route('/edit/{id}', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit( EntityManagerInterface $entityManager,Request $request, $id, FormationRepository $repo): Response
    {
        $formation = $repo->find($id);
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_formation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Formation/edit.html.twig', [
            'formation' => $formation,
            'form' => $form,
        ]);
    }

    // Route pour supprimer une formation
    #[Route('/delete/{id}', name: 'app_formation_delete')]
    public function delete($id, ManagerRegistry $manager, FormationRepository $repo): Response
    {
        $formation = $repo->find($id);
        
        if (!$formation) {
            throw $this->createNotFoundException('La formation avec l\'id '.$id.' n\'existe pas.');
        }

        $manager->getManager()->remove($formation);
        $manager->getManager()->flush();
    
        $this->addFlash('success', 'La formation a été supprimée avec succès.');
        return $this->redirectToRoute('app_formation_index');
    }
}
