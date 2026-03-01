<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LoginCodeService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Génère un code à 4 chiffres, l'assigne à l'utilisateur et sauvegarde.
     */
    public function generateAndSaveCode(User $user): string
    {
        // 1. Génération du code de manière sécurisée
        // random_int(0, 9999) génère un nombre. 
        // str_pad s'assure qu'il y a toujours 4 caractères, en ajoutant des '0' au début si besoin (ex: "0492")
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // 2. Définition de la date d'expiration (ex: valable 15 minutes)
        $expiresAt = new \DateTimeImmutable('+15 minutes');

        // 3. Mise à jour de l'entité User
        $user->setLoginCode($code);
        $user->setLoginCodeExpiresAt($expiresAt);

        // 4. Sauvegarde en base de données
        // Note : On ne fait pas de persist() car l'utilisateur existe déjà en base, 
        // Doctrine détecte automatiquement les changements sur l'entité.
        $this->entityManager->flush();

        return $code;
    }
}