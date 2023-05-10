<?php

namespace App\Tests\Mock;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use DateInterval;
use DateTime;
use DateTimeImmutable;
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
                'balance' => 25000.0,
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

    public function getTransactions(string $token, array $filter = null): array
    {
        $transactions = [];
        $billingUser = $this->getBillingUser($token);
        if ($billingUser['code'] !== 200) {
            return ['code' => 401, 'message' => 'Invalid JWT Token'];
        }

        if ($billingUser['username'] === 'admin@gmail.com') {
            $transactions = [
                0 => [
                    'id' => 1,
                    "created_at" => "2023-05-13UTC08:08:33",
                    "type" => "payment",
                    "course_code" => "course-3",
                    "amount" => 25999.9
                ],
                1 => [
                    'id' => 2,
                    "created_at" => "2023-05-11UTC08:08:33",
                    "type" => "payment",
                    "course_code" => "course-2",
                    "expires_at" => "2023-05-18UTC08:08:33",
                    "amount" => 1999.9
                ],
                2 => [
                    'id' => 3,
                    "created_at" => "2023-05-10UTC08:08:33",
                    "type" => "deposit",
                    "amount" => 1000000
                ],
                3 => [
                    'id' => 4,
                    "created_at" => "2023-02-16UTC07:24:10",
                    "type" => "payment",
                    "course_code" => "course-2",
                    "expires_at" => "2023-02-23UTC07:24:10",
                    "amount" => 1999.9
                ],
                4 => [
                    'id' => 5,
                    "created_at" => "2023-05-10UTC08:08:33",
                    "type" => "deposit",
                    "amount" => 50000
                ]
            ];
        } else {
            $transactions = [
                0 => [
                    'id' => 6,
                    "created_at" => "2023-05-10UTC08:08:33",
                    "type" => "deposit",
                    "amount" => 25000
                ]
            ];
        }
        if (count($transactions) > 0) {
            if (isset($filter['course_code'])) {
                $filteredTransactions = [];
                foreach ($transactions as $transaction) {
                    if (isset($transaction['course_code'])) {
                        if ($transaction['course_code'] === $filter['course_code']) {
                            $filteredTransactions[] = $transaction;
                        }
                    }
                }
                $transactions = $filteredTransactions;
            }

            if (isset($filter['skip_expired'])) {
                $filteredTransactions = [];
                foreach ($transactions as $transaction) {
                    if (isset($transaction['expires_at'])) {
                        if (new DateTime($transaction['expires_at']) > new DateTime('now')) {
                            $filteredTransactions[] = $transaction;
                        }
                    } else {
                        $filteredTransactions[] = $transaction;
                    }
                }
                $transactions = $filteredTransactions;
            }
            if (isset($filter['type'])) {
                $filteredTransactions = [];
                foreach ($transactions as $transaction) {
                    if ($transaction['type'] === $filter['type']) {
                        $filteredTransactions[] = $transaction;
                    }
                }
                $transactions = $filteredTransactions;
            }
        }
        return $transactions;
    }

    public function addCourse(string $token, string $postFields): array
    {
        $billingUser = $this->getBillingUser($token);
        if ($billingUser['username'] !== 'admin@gmail.com') {
            return [
                'code' => 401,
                'message' => 'У вас недостаточно прав для проведения данной операции!'
            ];
        }
        $courseParams = json_decode($postFields, true, 512, JSON_THROW_ON_ERROR);
        $errors = array();
        if (!isset($courseParams['type'])) {
            $errors['type'] = 'Поле не должно быть пустым!';
        }
        if (!isset($courseParams['code'])) {
            $errors['code'] = 'Поле не должно быть пустым!';
        }
        if (!isset($courseParams['title'])) {
            $errors['title'] = 'Поле не должно быть пустым!';
        }
        if (count($errors) > 0) {
            return [
                'code' => 401,
                'errors' => $errors
            ];
        }
        if ($courseParams['type'] !== 'buy' && $courseParams['type'] !== 'rent' && $courseParams['type'] !== 'free') {
            $errors['type'] = 'Выберите существующий тип оплаты!';
        }
        if ($courseParams['price'] < 0) {
            $errors['price'] = 'Курс не может стоить меньше 0!';
        }
        if (strlen($courseParams['title']) < 3) {
            $errors['title'] = 'Название должно иметь минимум 3 символа!';
        }
        if (count($errors) > 0) {
            return [
                'code' => 401,
                'errors' => $errors
            ];
        }

        if ($courseParams['type'] === 'buy' || $courseParams['type'] === 'rent') {
            if (!isset($courseParams['price'])) {
                return  [
                    'code' => 401,
                    'message' => 'Измените курсу тип или добавьте цену!'
                ];
            }
        }
        if ($courseParams['code'] === 'course-1' ||
            $courseParams['code'] === 'course-2' ||
            $courseParams['code'] === 'course-3') {
            return  [
                'code' => 401,
                'errors' => [
                    "unique" => "Курс с таким кодом уже существует!"
                ]
            ];
        }
        return [
            'success' => true
        ];
    }

    public function editCourse(string $token, string $postFields, string $code): array
    {
        $billingUser = $this->getBillingUser($token);
        if ($billingUser['username'] !== 'admin@gmail.com') {
            return [
                'code' => 401,
                'message' => 'У вас недостаточно прав для проведения данной операции!'
            ];
        }
        if ($code !== 'course-1' && $code !== 'course-2' && $code !== 'course-3') {
            return  [
                'code' => 401,
                'message' => 'Не найден курс с данным кодом.'
            ];
        }
        $courseParams = json_decode($postFields, true, 512, JSON_THROW_ON_ERROR);
        $errors = array();
        if (!isset($courseParams['type'])) {
            $errors['type'] = 'Поле не должно быть пустым!';
        }
        if (!isset($courseParams['code'])) {
            $errors['code'] = 'Поле не должно быть пустым!';
        }
        if (!isset($courseParams['title'])) {
            $errors['title'] = 'Поле не должно быть пустым!';
        }
        if (count($errors) > 0) {
            return [
                'code' => 401,
                'errors' => $errors
            ];
        }
        if ($courseParams['type'] !== 'buy' && $courseParams['type'] !== 'rent' && $courseParams['type'] !== 'free') {
            $errors['type'] = 'Выберите существующий тип оплаты!';
        }
        if ($courseParams['price'] < 0) {
            $errors['price'] = 'Курс не может стоить меньше 0!';
        }
        if (strlen($courseParams['title']) < 3) {
            $errors['title'] = 'Название должно иметь минимум 3 символа!';
        }
        if (count($errors) > 0) {
            return [
                'code' => 401,
                'errors' => $errors
            ];
        }

        if ($courseParams['type'] === 'buy' || $courseParams['type'] === 'rent') {
            if (!isset($courseParams['price'])) {
                return  [
                    'code' => 401,
                    'message' => 'Измените курсу тип или добавьте цену!'
                ];
            }
        }
        if ($code !== $courseParams['code']) {
            if ($courseParams['code'] === 'course-1' ||
                $courseParams['code'] === 'course-2' ||
                $courseParams['code'] === 'course-3') {
                return [
                    'code' => 401,
                    'errors' => [
                        "unique" => "Курс с таким кодом уже существует!"
                    ]
                ];
            }
        }
        return [
            'success' => true
        ];
    }

    public function getCourse(string $code): array
    {
        if ($code === 'course-1') {
            return [
                'code' => 'course-1',
                'type' => 'free'
            ];
        }
        if ($code === 'course-2') {
            return [
                'code' => 'course-2',
                'type' => 'rent',
                'price' => 1999.9
            ];
        }
        if ($code === 'course-3') {
            return [
                'code' => 'course-3',
                'type' => 'buy',
                'price' => 25999.9
            ];
        }

        return [
            'code' => 401,
            'message' => 'Не найден курс с данным кодом.'
        ];
    }

    public function getCourses(): array
    {
        return [
            0 => [
                'code' => 'course-1',
                'type' => 'free'
            ],
            1 => [
                'code' => 'course-2',
                'type' => 'rent',
                'price' => 1999.9
            ],
            2 => [
                'code' => 'course-3',
                'type' => 'buy',
                'price' => 25999.9
            ]
        ];
    }

    public function buyCourse(string $token, string $code): array
    {
        $billingUser = $this->getBillingUser($token);
        if ($code !== 'course-1' &&
            $code !== 'course-2' &&
            $code !== 'course-3') {
            return  [
                'code' => 401,
                'message' => 'Не найден курс с данным кодом.'
            ];
        }

        $course = $this->getCourse($code);
        if ($course['type'] === 'free') {
            return [
                'code' => 406,
                'message' => 'Данный курс бесплатный.'
            ];
        }
        $transactions = $this->getTransactions($token, [
            'course_code' => $code,
            'skip_expired' => true
        ]);
        if (count($transactions) !== 0) {
            return [
                'code' => 406,
                'message' => 'Вы уже владете доступом к данному курсу.'
            ];
        }
        if ($billingUser['balance'] < $course['price']) {
            return [
                'code' => 406,
                'message' => 'На вашем счету недостаточно средств.'
            ];
        }
        $result = [
            'success' => true,
            'course_type' => $course['type']
        ];
        if ($course['type'] === 'rent') {
            $result['expires_at'] = (new DateTimeImmutable('now'))->add(new DateInterval('P7D'));
        }
        return $result;
    }
}
