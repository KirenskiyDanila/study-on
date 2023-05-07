<?php

namespace App\Tests\Mock;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use JsonException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BillingClientMock extends BillingClient
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        parent::__construct();
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @throws JsonException
     */
    public function auth(string $credentials): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
        if (($arrayedCredentials['username'] === 'admin@gmail.com'
                && $arrayedCredentials['password'] === 'password')
            || ($arrayedCredentials['username'] === 'user@gmail.com'
                && $arrayedCredentials['password'] === 'password')
        ) {
            $token = base64_encode(json_encode([
                'email' => $arrayedCredentials['username'],
                'iat' => (new \DateTime('now'))->getTimestamp(),
                'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
                'roles' => $arrayedCredentials['username'] === 'admin@gmail.com' ?
                    ['ROLE_SUPER_ADMIN'] : ['ROLE_USER'],
            ], JSON_THROW_ON_ERROR));
            $response['token'] = "header." . $token . ".verifySignature";
            $response['refresh_token'] = 'refresh_token';
            return $response;
        }
        $response['code'] = 401;
        return $response;
    }

    public function register(string $credentials): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
        if ($arrayedCredentials['username'] !== 'admin@gmail.com'
            && $arrayedCredentials['username'] !== 'user@gmail.com'
        ) {
            $token = base64_encode(json_encode([
                'email' => $arrayedCredentials['username'],
                'iat' => (new \DateTime('now'))->getTimestamp(),
                'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
                'roles' => ['ROLE_USER']
            ], JSON_THROW_ON_ERROR));
            $response['token'] = "header." . $token . ".verifySignature";
            $response['refresh_token'] = 'refresh_token';
            return $response;
        }

        $response['code'] = 401;
        $response['errors']['unique'] = 'Пользователь с такой электронной почтой уже существует!';
        return $response;
    }

    public function refresh(string $refreshToken): array
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $token = base64_encode(json_encode([
            'email' => $user->getUserIdentifier(),
            'iat' => (new \DateTime('now'))->getTimestamp(),
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
            'roles' => ['ROLE_USER'],
        ], JSON_THROW_ON_ERROR));
        $response['token'] = "header." . $token . ".verifySignature";
        return $response;
    }

    /**
     * @throws JsonException
     */
    public function getBillingUser(string $token): array
    {
        try {
            $parts = explode('.', $token);
            $payload = json_decode(base64_decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
            return [
                'balance' => 0,
                'ROLES' => $payload['roles'],
                'username' => $payload['email'],
                'code' => 200
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException(json_encode(
                ['code' => 401, 'message' => 'Invalid JWT Token'],
                JSON_THROW_ON_ERROR
            ));
        }
    }
}
