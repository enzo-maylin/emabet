<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $loginCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $loginCodeExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getLoginCode(): ?string
    {
        return $this->loginCode;
    }

    public function setLoginCode(?string $loginCode): static
    {
        $this->loginCode = $loginCode;

        return $this;
    }

    public function getLoginCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->loginCodeExpiresAt;
    }

    public function setLoginCodeExpiresAt(?\DateTimeImmutable $loginCodeExpiresAt): static
    {
        $this->loginCodeExpiresAt = $loginCodeExpiresAt;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Si tu stockes des données temporaires et sensibles sur l'utilisateur,
        // tu peux les effacer ici. Dans ton cas, ce n'est pas nécessaire de la remplir.
    }
}