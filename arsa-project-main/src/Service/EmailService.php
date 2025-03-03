<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendDonationConfirmation(array $donation)
    {
        $email = (new TemplatedEmail())
            ->from(new Address('haffar.sadok@gmail.com', 'ARSA Project'))
            ->to(new Address($donation['email'], $donation['firstName'] . ' ' . $donation['lastName']))
            ->subject('Thank You for Your Donation!')
            ->htmlTemplate('email/email.html.twig')
            ->context([
                'donation' => $donation
            ]);

        $this->mailer->send($email);
    }
    public function sendSubscriptionConfirmation(string $subscriberEmail)
    {
        $email = (new TemplatedEmail())
            ->from(new Address('haffar.sadok@gmail.com', 'EcoDon'))
            ->to(new Address($subscriberEmail))
            ->subject('Welcome to EcoDon Newsletter!')
            ->htmlTemplate('email/subscription.html.twig')
            ->context([
                'subscriberEmail' => $subscriberEmail,
                'date' => new \DateTime()
            ]);

        $this->mailer->send($email);
    }

    
    public function sendEventUpdateEmail(string $email, array $data)
    {
        $email = (new TemplatedEmail())
            ->from(new Address('haffar.sadok@gmail.com', $data['associationName']))
            ->to(new Address($email))
            ->subject('Update about ' . $data['eventName'])
            ->htmlTemplate('email/event_update.html.twig')
            ->context([
                'eventName' => $data['eventName'],
                'donorName' => $data['donorName'],
                'associationName' => $data['associationName'],
                'customMessage' => $data['customMessage'] // Add this line
            ]);

        $this->mailer->send($email);
    }
        
}