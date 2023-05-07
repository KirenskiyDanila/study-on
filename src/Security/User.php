<?php

namespace App\Security;

use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private ?string $email;

    private ?string $refresh_token;

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->refresh_token;
    }

    /**
     * @param string|null $refresh_token
     */
    public function setRefreshToken(?string $refresh_token): self
    {
        $this->refresh_token = $refresh_token;

        return $this;
    }

    private ?DateTime $exp;

    /**
     * @return DateTime|null
     */
    public function getExp(): ?DateTime
    {
        return $this->exp;
    }

    /**
     * @param DateTime|null $exp
     */
    public function setExp(int $exp): self
    {
        $this->exp = (new DateTime())->setTimestamp($exp);

        return $this;
    }
    private ?string $token;

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @throws \JsonException
     */
    public function decodeToken(): self
    {
        $parts = explode('.', $this->token);
        $payload = json_decode(base64_decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
        $this->setEmail($payload['email']);
        $this->setRoles($payload['roles']);
        $this->setExp($payload['exp']);
        return $this;
    }

    private array $roles = [];

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
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
        return (string) $this->email;
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

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
