<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use JsonException;

class BillingClient
{
    private string $url;
    public function __construct()
    {
        $this->url = $_ENV['BILLING_SERVER'];
    }

    /**
     * @throws JsonException
     * @throws BillingUnavailableException
     */
    public function auth(string $credentials) : array
    {
        $uri = $this->url . 'api/v1/auth';

        return CurlMaker::post($uri, $credentials);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function register(string $credentials) : array
    {
        $uri = $this->url . 'api/v1/register';

        return CurlMaker::post($uri, $credentials);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function getTransactions(string $token, array $filter = null) : array
    {
        $uri = $this->url . 'api/v1/transactions?' . http_build_query($filter);
        return CurlMaker::get($uri, $token);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function getCourse(string $code) : array
    {
        $uri = $this->url . 'api/v1/courses/' . $code;
        return CurlMaker::get($uri);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function getCourses() : array
    {
        $uri = $this->url . 'api/v1/courses';
        return CurlMaker::get($uri);
    }


    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function getBillingUser(string $token): array
    {

        $uri = $this->url . 'api/v1/users/current';
        return CurlMaker::get($uri, $token);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function refresh(string $refreshToken): array
    {
        $uri = $this->url . 'api/v1/token/refresh';
        return CurlMaker::post($uri, $refreshToken);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function buyCourse(string $token, string $code): array
    {
        $uri = $this->url . 'api/v1/courses/' . $code . '/pay';
        return CurlMaker::post($uri, null, $token);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function addCourse(string $token, string $postFields): array
    {
        $uri = $this->url . 'api/v1/courses/';
        return CurlMaker::post($uri, $postFields, $token);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function editCourse(string $token, string $postFields, string $code): array
    {
        $uri = $this->url . 'api/v1/courses/' . $code;
        return CurlMaker::post($uri, $postFields, $token);
    }
}
