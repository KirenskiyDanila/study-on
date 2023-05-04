<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use JsonException;

class BillingClient
{
    /**
     * @throws JsonException
     * @throws BillingUnavailableException
     */
    public static function getToken(string $url, string $credentials, bool $register) : array
    {
        if ($register === true) {
            $uri = $url . 'api/v1/register';
        } else {
            $uri = $url . 'api/v1/auth';
        }
        $curl = curl_init($uri);

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            $credentials
        );


        $response = curl_exec($curl);
        curl_close($curl);

        if ($response !== false) {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        }
        throw new BillingUnavailableException('Сервис временно не доступен! Попытайтесь чуть позже!');
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public static function getBillingUser(string $url, string $token): array
    {

        $uri = $url . 'api/v1/users/current';
        $curl = curl_init($uri);

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '.$token]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response !== false) {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        }
        throw new BillingUnavailableException('Сервис временно не доступен! Попытайтесь чуть позже!');
    }
}
