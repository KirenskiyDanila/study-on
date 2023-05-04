<?php

namespace App\Tests\Mock;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use JsonException;

class BillingClientMock extends BillingClient
{
    /**
     * @throws BillingException
     * @throws JsonException
     */
    public static function getToken(string $url, string $credentials, bool $register): array
    {
        $arrayedCredentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
        if ($register === false) {
            if (($arrayedCredentials['username'] === 'admin@gmail.com'
                    && $arrayedCredentials['password'] === 'password')
                || ($arrayedCredentials['username'] === 'user@gmail.com'
                    && $arrayedCredentials['password'] === 'password')
            ) {
                $token = base64_encode(json_encode([
                    'username' => $arrayedCredentials['username'],
                    'iat' => (new \DateTime('now'))->getTimestamp(),
                    'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
                    'roles' => $arrayedCredentials['username'] === 'admin@gmail.com' ?
                        ['ROLE_SUPER_ADMIN'] : ['ROLE_USER'],
                ], JSON_THROW_ON_ERROR));
                $response['token'] = "header." . $token . ".verifySignature";
                return $response;
            }
            $response['code'] = 401;
            return $response;
        }

        if ($arrayedCredentials['username'] !== 'admin@gmail.com'
            && $arrayedCredentials['username'] !== 'user@gmail.com'
        ) {
            $token = base64_encode(json_encode([
                'username' => $arrayedCredentials['username'],
                'iat' => (new \DateTime('now'))->getTimestamp(),
                'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
                'roles' => $arrayedCredentials['username'] === 'admin@gmail.com' ?
                    ['ROLE_SUPER_ADMIN'] : ['ROLE_USER'],
            ], JSON_THROW_ON_ERROR));
            $response['token'] = "header." . $token . ".verifySignature";
            return $response;
        }

        $response['code'] = 401;
        $response['errors']['unique'] = 'Пользователь с такой электронной почтой уже существует!';
        return $response;
    }

    /**
     * @throws JsonException
     */
    public static function getBillingUser(string $url, string $token): array
    {
        try {
            $parts = explode('.', $token);
            $payload = json_decode(base64_decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
            return [
                'balance' => 0,
                'roles' => $payload['roles'],
                'username' => $payload['username'],
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
