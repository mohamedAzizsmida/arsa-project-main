<?php
// src/Controller/GoogleLoginController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Google\Client as GoogleClient;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GoogleLoginController extends AbstractController
{
    private $googleClient;
    private $entityManager;
    private $passwordHasher;
    private $tokenStorage;
    private $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher,
        string $googleClientId,
        string $googleClientSecret,
        string $googleRedirectUri
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;

        // Initialize Google Client
        $this->googleClient = new GoogleClient();
        $this->googleClient->setClientId($googleClientId);
        $this->googleClient->setClientSecret($googleClientSecret);
        $this->googleClient->setRedirectUri($googleRedirectUri);
        $this->googleClient->addScope('email');
        $this->googleClient->addScope('profile');
    }

    #[Route('/login/google', name: 'app_login_google')]
    public function redirectToGoogle(): Response
    {
        $authUrl = $this->googleClient->createAuthUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/login/google/callback', name: 'app_login_google_callback')]
    public function handleGoogleCallback(Request $request): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'Google authentication failed.');
            return $this->redirectToRoute('app_login');
        }

        // Exchange the authorization code for an access token
        $this->googleClient->fetchAccessTokenWithAuthCode($code);
        $accessToken = $this->googleClient->getAccessToken();

        if (isset($accessToken['error'])) {
            $this->addFlash('error', 'Google authentication failed.');
            return $this->redirectToRoute('app_login');
        }

        // Get user info from Google
        $oauth2 = new \Google\Service\Oauth2($this->googleClient);
        $userInfo = $oauth2->userinfo->get();

        // Check if the user already exists in your database
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userInfo->email]);

        if (!$user) {
            // Create a new user
            $user = new User();
            $user->setEmail($userInfo->email);
            $user->setNom($userInfo->name);
            $user->setRoles(['ROLE_USER']);

            // Generate a random password (users can change it later)
            $password = bin2hex(random_bytes(8));
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        // Authenticate the user
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        // Fire the login event
        $event = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch($event, 'security.interactive_login');

        $this->addFlash('success', 'You have been logged in with Google.');
        return $this->redirectToRoute('app_home');
    }
}