<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;

class CurlMaker
{

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    public static function post(string $uri, string $postFields = null, $token = null) : array
    {
        $curl = curl_init($uri);
        $options = [
            'Content-Type: application/json',
            'Accept: application/json'];
        if ($token !== null) {
            $options[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $options);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($postFields !== null) {
            curl_setopt(
                $curl,
                CURLOPT_POSTFIELDS,
                $postFields
            );
        }


        $response = curl_exec($curl);
        curl_close($curl);

        if ($response !== false) {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        }
        throw new BillingUnavailableException('Сервис временно не доступен! Попытайтесь чуть позже!');
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    public static function get(string $uri, string $token = null) : array
    {
        $curl = curl_init($uri);

        $options = [
            'Content-Type: application/json',
            'Accept: application/json'];
        if ($token !== null) {
            $options[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $options);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response !== false) {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        }
        throw new BillingUnavailableException('Сервис временно не доступен! Попытайтесь чуть позже!');
    }

}
