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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @throws BillingException
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/profile', name: 'app_user_profile')]
    public function index(BillingClient $billingClient): Response
    {

        /** @var User $user */
        $user = $this->getUser();

        try {
            $response = $billingClient->getBillingUser($_ENV['BILLING_SERVER'], $user->getToken());
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
}
