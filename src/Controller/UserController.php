<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\LoginCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth', name: 'api_auth_')]
class UserController extends AbstractController
{

    #[Route('/request-code', name: 'request_code', methods: ['POST'])]
    public function requestCode(
        Request $request,
        UserRepository $userRepository,
        LoginCodeService $loginCodeService,
        MailerInterface $mailer
    ): JsonResponse {
        // On récupère les données JSON de la requête
        $data = $request->toArray();
        $emailAddress = $data['email'] ?? null;

        if (!$emailAddress || !preg_match('/@(etu\.mines-ales\.fr|mines-ales\.org)$/', $emailAddress)) {
            return $this->json(['error' => 'Email invalide'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $emailAddress]);

        // Pour des raisons de sécurité (éviter la fuite d'informations), 
        // on renvoie toujours un succès même si l'utilisateur n'existe pas.
        if ($user) {
            // 1. Générer le code
            $code = $loginCodeService->generateAndSaveCode($user);

            // 2. Envoyer l'email
            $email = (new Email())
                ->from('noreply@tonprojet.com')
                ->to($user->getEmail())
                ->subject('Ton code de connexion')
                ->text("Voici ton code de connexion temporaire : $code. Il expire dans 15 minutes.");

            $mailer->send($email);
        }

        return $this->json([
            'message' => 'Si un compte existe avec cet email, un code a été envoyé.'
        ]);
    }

    #[Route('/verify-code', name: 'verify_code', methods: ['POST'])]
    public function verifyCode(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = $request->toArray();
        $emailAddress = $data['email'] ?? null;
        $submittedCode = $data['code'] ?? null;

        if (!$emailAddress || !$submittedCode) {
            return $this->json(['error' => 'Email ou code manquant'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $emailAddress]);

        if (!$user) {
            return $this->json(['error' => 'Identifiants invalides'], Response::HTTP_UNAUTHORIZED);
        }

        // 1. Vérifier si le code correspond
        if ($user->getLoginCode() !== $submittedCode) {
            return $this->json(['error' => 'Code invalide'], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Vérifier si le code est expiré
        if ($user->getLoginCodeExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'Code expiré, veuillez en demander un nouveau'], Response::HTTP_UNAUTHORIZED);
        }

        // 3. Le code est bon ! On le supprime pour qu'il ne soit plus réutilisable (Usage unique / OTP)
        $user->setLoginCode(null);
        $user->setLoginCodeExpiresAt(null);
        $entityManager->flush();

        // 4. On génère le token JWT
        $token = $jwtManager->create($user);

        // 5. On retourne le token au client (le front-end)
        return $this->json([
            'token' => $token,
            'message' => 'Connexion réussie'
        ]);
    }
}