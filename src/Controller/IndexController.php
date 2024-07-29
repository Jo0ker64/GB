<?php

namespace App\Controller;

use App\Entity\User; // Import de l'entité User
use App\Form\LoginFormType; // Import du formulaire de connexion
use App\Form\RegistrationFormType; // Import du formulaire d'inscription
use Doctrine\ORM\EntityManagerInterface; // Import du gestionnaire d'entités Doctrine
use Symfony\Component\HttpFoundation\Request; // Import de la classe Request
use Symfony\Component\HttpFoundation\Response; // Import de la classe Response
use Symfony\Component\Routing\Annotation\Route; // Import de l'annotation Route
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Import du contrôleur abstrait de Symfony
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // Import de l'interface de hachage de mot de passe
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils; // Import de l'utilitaire d'authentification

class IndexController extends AbstractController
{
    private $passwordHasher; // Déclaration de la propriété pour le hachage de mot de passe
    private $entityManager; // Déclaration de la propriété pour le gestionnaire d'entités

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
        $user = new User();// Création d'un nouvel utilisateur pour le formulaire d'inscription
        $registrationForm = $this->createForm(RegistrationFormType::class, $user); // Création du formulaire d'inscription
        $registrationForm->handleRequest($request);// Traitement de la requête pour le formulaire d'inscription


        // Vérification si le formulaire est soumis et valide
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            // Hachage du mot de passe de l'utilisateur
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $registrationForm->get('plainPassword')->getData()
                )
            );

            $this->entityManager->persist($user); // Persistance de l'utilisateur dans la base de données
            $this->entityManager->flush();  // Sauvegarde des modifications dans la base de données

            return $this->redirectToRoute('app_login');  // Redirection vers la page de connexion après l'inscription réussie
        }

        // Récupération des erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        // Récupération du dernier nom d'utilisateur utilisé
        $lastUsername = $authenticationUtils->getLastUsername();

        // Création du formulaire de connexion
        $loginForm = $this->createForm(LoginFormType::class, [
            'email' => $lastUsername, // Pré-remplissage avec le dernier email utilisé
            'password' => '', // Champ mot de passe vide
        ]);

        // Rendu de la vue avec les formulaires d'inscription et de connexion
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
         // Récupération de l'utilisateur connecté
         $user = $this->getUser();
 
         // Vérification si l'utilisateur n'est pas connecté
         if (!$user) {
             // Lancer une exception d'accès refusé si l'utilisateur n'est pas connecté
             throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
         }
 
         // Rendu de la vue de profil avec les données de l'utilisateur
         return $this->render('profile/index.html.twig', [
             'user' => $user, // Données de l'utilisateur
         ]);
     }
 
     // Route pour la page de connexion
     #[Route('/login', name: 'app_login')]
     public function login(AuthenticationUtils $authenticationUtils): Response
     {
         // Récupération des erreurs de connexion
         $error = $authenticationUtils->getLastAuthenticationError();
         // Récupération du dernier nom d'utilisateur utilisé
         $lastUsername = $authenticationUtils->getLastUsername();
 
         // Création du formulaire de connexion
         $form = $this->createForm(LoginFormType::class, [
             'email' => $lastUsername, // Pré-remplissage avec le dernier email utilisé
             'password' => '', // Champ mot de passe vide
         ]);
 
         // Rendu de la vue avec le formulaire de connexion et les erreurs potentielles
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
         // Lancer une exception pour indiquer que cette méthode ne sera jamais exécutée
         throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
     }

     
        // Route pour l'inscription d'un nouvel utilisateur
     #[Route('/registration', name: 'app_registration')] 
     public function registration(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
     {
         // Vérifie si le service de hachage de mot de passe est nul, sinon utilise le conteneur de services pour l'obtenir
         $passwordHasher = $passwordHasher ?? $this->container->get(UserPasswordHasherInterface::class);
         
         // Crée une nouvelle instance de l'entité User
         $user = new User(); 
         // Crée un formulaire de type RegistrationFormType avec l'entité User
         $form = $this->createForm(RegistrationFormType::class, $user);
         // Gère la requête HTTP pour le formulaire (parsing des données de la requête)
         $form->handleRequest($request);
     
         // Vérifie si le formulaire a été soumis et est valide
         if ($form->isSubmitted() && $form->isValid()) {
             // Hache le mot de passe de l'utilisateur à partir des données du formulaire
             $user->setPassword(
                 $passwordHasher->hashPassword(
                     $user,
                     $form->get('plainPassword')->getData()
                 )
             );
     
             // Persiste l'entité User dans le gestionnaire d'entités
             $entityManager->persist($user);
             // Exécute les opérations de sauvegarde en base de données
             $entityManager->flush();
     
             // Redirige l'utilisateur vers la route 'app_login' après une inscription réussie
             return $this->redirectToRoute('app_login');
         }
     
         // Rendu du template Twig 'registration/index.html.twig' avec la vue du formulaire
         return $this->render('registration/index.html.twig', [
             'registrationForm' => $form->createView(), // Passe la vue du formulaire à la vue Twig
         ]);
     }

   
}
