<?php

namespace App\Controller;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Service\BillingClient;
use App\Utils\FilterFormer;
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
    private FilterFormer $filterFormer;
    public function __construct(BillingClient $billingClient, FilterFormer $filterFormer)
    {
        $this->billingClient = $billingClient;
        $this->filterFormer= $filterFormer;
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
        $filter = $this->filterFormer->formFilter($request);
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
