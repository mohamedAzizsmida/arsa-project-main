<?php
namespace App\Controller;

use App\Entity\User;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class ForgotPasswordController extends AbstractController
{
    private $entityManager;
    private $emailService;
    private $urlGenerator;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        EmailService $emailService,
        UserPasswordHasherInterface $passwordHasher // Updated type hint
    ) {
        $this->entityManager = $entityManager;
        $this->emailService = $emailService;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
public function index(Request $request, MailerInterface $mailer, Environment $twig): Response
{
    if ($request->isMethod('POST')) {
        $email = $request->request->get('email');

        // Find the user by email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            // Generate a new temporary password
            $newPassword = bin2hex(random_bytes(8)); // Generates a random 16-character password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);

            // Update the user's password
            $user->setPassword($hashedPassword);
            $this->entityManager->flush();

            // Generate the absolute URL for the login page
            // $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Render the email template
            $emailContent = $twig->render('email/password.html.twig', [
                'newPassword' => $newPassword,
                // 'loginUrl' => $loginUrl,
            ]);

            // Send the email
            $email = (new Email())
                ->from('ecodorn@gmail.com')  // Replace with your sender email
                ->to($user->getEmail())
                ->subject('Your New Password')
                ->html($emailContent);

            $mailer->send($email);

            $this->addFlash('success', 'A new password has been sent to your email.');
            return $this->redirectToRoute('app_login');
        } else {
            $this->addFlash('error', 'No user found with this email address.');
        }
    }

    return $this->render('security/forgot_password.html.twig');
}
}