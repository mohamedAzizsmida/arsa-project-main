<?php

// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/home')]
class HomeController extends AbstractController
{
    //Association
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('baseMain.html.twig'); // Renders home page template
    }
    #[Route('/admin', name: 'app_home_admin')]
    public function admin(): Response
    {
        return $this->render('baseMain.html.twig'); // Renders home page template
    }
    #[Route('/entreprise', name: 'app_home_entreprise')]
    public function entreprise(): Response
    {
        return $this->render('baseMain.html.twig'); // Renders home page template
    }
    #[Route('/association', name: 'app_home_association')]
    public function association(): Response
    {
        return $this->render('baseMain.html.twig'); // Renders home page template
    }
    #[Route('/user', name: 'app_home_user')]
    public function user(): Response
    {
        return $this->render('baseMain.html.twig'); // Renders home page template
    }

}
