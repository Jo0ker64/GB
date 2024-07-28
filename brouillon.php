<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginFormType;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class IndexController extends AbstractController
{
    private $passwordHasher;
    private $entityManager;

    // Constructeur pour injecter les services de hachage de mot de passe et le gestionnaire d'entités
    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->passwordHasher = $passwordHasher; // Initialisation du hachage de mot de passe
        $this->entityManager = $entityManager; // Initialisation du gestionnaire d'entités
    }

    // Route pour la page d'accueil
    #[Route('/', name: 'app_home')]
    public function index(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $user = new User(); // Création d'un nouvel utilisateur pour le formulaire d'inscription
        $registrationForm = $this->createForm(RegistrationFormType::class, $user); // Création du formulaire d'inscription
        $registrationForm->handleRequest($request); // Traitement de la requête pour le formulaire d'inscription

        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $registrationForm->get('plainPassword')->getData()
                )
            );

            $this->entityManager->persist($user); // Persistance de l'utilisateur dans la base de données
            $this->entityManager->flush(); // Sauvegarde des modifications dans la base de données

            return $this->redirectToRoute('app_login'); // Redirection vers la page de connexion après l'inscription réussie
        }

        $error = $authenticationUtils->getLastAuthenticationError(); // Récupération des erreurs de connexion
        $lastUsername = $authenticationUtils->getLastUsername(); // Récupération du dernier nom d'utilisateur utilisé

        $loginForm = $this->createForm(LoginFormType::class, [
            'email' => $lastUsername, // Pré-remplissage avec le dernier email utilisé
            'password' => '', // Champ mot de passe vide
        ]);

        return $this->render('index/index.html.twig', [
            'registrationForm' => $registrationForm->createView(), // Vue du formulaire d'inscription
            'loginForm' => $loginForm->createView(), // Vue du formulaire de connexion
            'error' => $error, // Erreurs de connexion
        ]);
    }

    // Route pour la page de profil utilisateur
    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser(); // Récupération de l'utilisateur connecté

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.'); // Vérification si l'utilisateur n'est pas connecté
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user, // Rendu de la vue de profil avec les données de l'utilisateur
        ]);
    }

    // Route pour la page de connexion
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError(); // Récupération des erreurs de connexion
        $lastUsername = $authenticationUtils->getLastUsername(); // Récupération du dernier nom d'utilisateur utilisé

        $form = $this->createForm(LoginFormType::class, [
            'email' => $lastUsername, // Pré-remplissage avec le dernier email utilisé
            'password' => '', // Champ mot de passe vide
        ]);

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, // Dernier nom d'utilisateur utilisé
            'error' => $error, // Erreurs de connexion
            'loginForm' => $form->createView(), // Vue du formulaire de connexion
        ]);
    }

    // Route pour la déconnexion
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall'); // Exception pour indiquer que cette méthode ne sera jamais exécutée
    }

    // Route pour l'inscription d'un nouvel utilisateur
    #[Route('/registration', name: 'app_registration')]
    public function registration(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $passwordHasher = $passwordHasher ?? $this->container->get(UserPasswordHasherInterface::class); // Vérifie si le service de hachage de mot de passe est nul

        $user = new User(); // Crée une nouvelle instance de l'entité User
        $form = $this->createForm(RegistrationFormType::class, $user); // Crée un formulaire de type RegistrationFormType avec l'entité User
        $form->handleRequest($request); // Gère la requête HTTP pour le formulaire

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user); // Persiste l'entité User dans le gestionnaire d'entités
            $entityManager->flush(); // Exécute les opérations de sauvegarde en base de données

            return $this->redirectToRoute('app_login'); // Redirige l'utilisateur vers la route 'app_login' après une inscription réussie
        }

        return $this->render('registration/index.html.twig', [
            'registrationForm' => $form->createView(), // Rendu du template Twig 'registration/index.html.twig' avec la vue du formulaire
        ]);
    }
}
