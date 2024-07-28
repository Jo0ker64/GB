<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    // Route pour la page de profil
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser(); // Récupération de l'utilisateur connecté

        if (!$user) {
            throw $this->createNotFoundException('Vous devez être connecté pour accéder à cette page');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]); // Rendu de la vue de profil avec les données de l'utilisateur      
    }
}
