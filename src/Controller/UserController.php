<?php

namespace App\Controller;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use JsonException;
use PHPUnit\Util\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    private BillingClient $billingClient;
    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    /**
     * @throws BillingException
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/profile', name: 'app_user_profile')]
    public function index(): Response
    {

        /** @var User $user */
        $user = $this->getUser();

        try {
            $response = $this->billingClient->getBillingUser($user->getToken());
        } catch (BillingUnavailableException|JsonException $e) {
            throw new Exception('Произошла ошибка во время получения данных профиля: ' . $e->getMessage());
        }
        if ($response['code'] !== 200) {
            throw new BillingException('Ошибка получения данных профиля. Пройдите авторизацию заново.');
        }
        $balance = $response['balance'];

        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'user' => $user,
            'balance' => $balance
        ]);
    }

    /**
     * @throws BillingException
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/transactions', name: 'app_user_transactions')]
    public function transactions(Request $request): Response
    {

        /** @var User $user */
        $user = $this->getUser();
        $filter = array();
        if ($request->query->get("type", null) !== null) {
            $filter['type'] = $request->query->get("type", null);
        }
        if ($request->query->get("course_code", null) !== null) {
            $filter['course_code'] = $request->query->get("course_code", null);
        }
        if ($request->query->get("skip_expired", null) !== null) {
            $filter['skip_expired'] = $request->query->get("skip_expired", null);
        }
        try {
            $response = $this->billingClient->getTransactions($user->getToken(), $filter);
        } catch (BillingUnavailableException|JsonException $e) {
            throw new Exception('Произошла ошибка во время получения данных о транзакциях: '
                . $e->getMessage());
        }
        if (isset($response['code'])) {
            throw new BillingException('Ошибка получения данных о транзакциях. Пройдите авторизацию заново.');
        }

        return $this->render('user/transactions.html.twig', [
            'controller_name' => 'UserController',
            'user' => $user,
            'transactions' => $response
        ]);
    }
}
