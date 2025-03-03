<?php
// src/Service/MessageGenerator.php
namespace App\Service;

use Twilio\Rest\Client;

class SmsGenerator
{
    
    public function SendSms(string $number, string $id, string $text)
    {
        
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'];  //Identifiant du compte twilio
        $authToken = $_ENV['TWILIO_AUTH_TOKEN']; //Token d'authentification
        $fromNumber = $_ENV['TWILIO_FROM_NUMBER']; // Numéro de test d'envoie sms offert par twilio

        $toNumber = $number; // Le numéro de la personne qui reçoit le message
        $message = ' le post sous ID '.$id.' a franchi'.' '.$text.''; //Contruction du sms

        //Client Twilio pour la création et l'envoie du sms
        $client = new Client($accountSid, $authToken);

        $client->messages->create(
            $toNumber,
            [
                'from' => $fromNumber,
                'body' => $message,
            ]
        );


    }
}



