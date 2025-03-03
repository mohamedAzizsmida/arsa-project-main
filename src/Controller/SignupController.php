<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\SignupFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AuthAuthenticator;

class SignupController extends AbstractController
{
    #[Route('/register', name: 'app_signup')]
    public function signup(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AuthAuthenticator $authenticator
    ): Response {
        $user = new User();
        $form = $this->createForm(SignupFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Trim and sanitize inputs
            $user->setNom(trim($user->getNom()));
            $user->setEmail(trim($user->getEmail()));
            $user->setAdress($user->getAdress() ? trim($user->getAdress()) : null);

            // Hash the password securely
            $plainPassword = $form->get('password')->getData();
            if (!$plainPassword) {
                $this->addFlash('error', 'Password cannot be empty.');
                return $this->redirectToRoute('app_signup');
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Persist and flush to database
            $entityManager->persist($user);
            $entityManager->flush();

            // Automatically log in the user after registration
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('signup/index.html.twig', [
            'signupForm' => $form->createView(),
        ]);
    }
}