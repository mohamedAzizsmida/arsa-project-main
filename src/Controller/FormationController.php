<?php

namespace App\Controller;

use DateTime;
use App\Entity\Formation;
use App\Entity\Cour;
use App\Repository\FormationRepository;
use App\Form\FormationType;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormRendererEngineInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[Route('/formation')]
class FormationController extends AbstractController
{
    #[Route('/user', name: 'app_formation_index_user')]
    public function showFormationFront(FormationRepository $formationRepository): Response
    {
        // Display all formations for the front view
        return $this->render('Formation/FormationFront.html.twig', [
            'formations' => $formationRepository->findAll(),
        ]);
    }

    #[Route('/', name: 'app_formation_index')]
    public function showFormation(FormationRepository $formationRepository): Response
    {
        // Display all formations in the admin view
        return $this->render('Formation/Formation.html.twig', [
            'formations' => $formationRepository->findAll(),
        ]);
    }

    #[Route('/create', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentDate = new DateTime();
        $formation = new Formation();
        $formation->setDateDebut($currentDate);  // Default start date

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

    #[Route('/join/{id}', name: 'app_formation_join')]
    public function joinFormation(
        $id, 
        FormationRepository $formationRepository, 
        UserRepository $userRep, 
        EntityManagerInterface $entityManager, 
        MailerInterface $mailer, 
        Environment $twig // Inject Twig Environment
    ): Response {
        // Get the formation by ID
        $formation = $formationRepository->find($id);

        if (!$formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        // Get the current logged-in user
        $user = $userRep->find(1); // Symfony will automatically get the logged-in user
        
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to join a formation.');
            return $this->redirectToRoute('app_login'); // Redirect to login if not logged in
        }

        // Check if user is already joined to this formation
        if ($formation->getUsers()->contains($user)) {
            $this->addFlash('info', 'You are already a member of this formation.');
            return $this->redirectToRoute('app_formation_index_user');
        }

        // Add the user to the formation
        $user->addFormation($formation);

        // Save the changes
        $entityManager->flush();

        // Render the email content using Twig
        $emailContent = $twig->render('email/email2.html.twig', [
            'user' => $user,
            'formation' => $formation,
        ]);

        // Create the email
        $email = (new Email())
            ->from('ecodorn@gmail.com')  // Replace with your sender email
            ->to($user->getEmail())
            ->subject('Successfully Joined Formation')
            ->html($emailContent);

        // Send the email
        $mailer->send($email);

        // Add a success flash message
        $this->addFlash('success', 'You have successfully joined the formation! A confirmation email has been sent.');

        // Redirect to the formation list or details
        return $this->redirectToRoute('app_formation_index_user');
    }



    #[Route('/edit/{id}', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit( EntityManagerInterface $entityManager, Request $request, $id, FormationRepository $repo): Response
    {
        $formation = $repo->find($id);

        if (!$formation) {
            throw $this->createNotFoundException('Formation not found');
        }

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

    #[Route('/delete/{id}', name: 'app_formation_delete')]
    public function delete($id, ManagerRegistry $manager, FormationRepository $repo): Response
    {
        $formation = $repo->find($id);

        if (!$formation) {
            throw $this->createNotFoundException('Formation not found');
        }

        $manager->getManager()->remove($formation);
        $manager->getManager()->flush();

        $this->addFlash('success', 'Formation successfully deleted');
        return $this->redirectToRoute('app_formation_index');
    }
}
