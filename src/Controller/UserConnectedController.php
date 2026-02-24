<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserConnectedController extends AbstractController
{
    #[Route('/api/userConnected', name: 'userConnected', methods: ['GET'])]
    public function __invoke(): JsonResponse{
        $user = $this->getUser();

        return $this->json([
            'email' => $user?->getIdentifier(),
            'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
        ]);
    }
}
