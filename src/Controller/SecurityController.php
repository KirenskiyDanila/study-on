<?php

namespace App\Controller;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Form\UserRegistrationFormType;
use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use JsonException;
use PhpParser\Builder\Property;
use PHPUnit\Util\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'security/login.html.twig',
            ['last_username' => $lastUsername, 'error' => $error]
        );
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     * @throws BillingException
     */
    #[Route(path: '/registration', name: 'app_registration')]
    public function registration(
        Request $request,
        UserAuthenticatorInterface $authenticator,
        BillingAuthenticator $formAuthenticator,
        BillingClient $billingClient
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }

        $form = $this->createForm(UserRegistrationFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form["email"]->getData();
            $password = $form["password"]->getData();

            $credentials = json_encode([
                'username' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR);


            try {
                $response = $billingClient->getToken($_ENV['BILLING_SERVER'], $credentials, true);
            } catch (BillingUnavailableException|JsonException $e) {
                throw new Exception('Произошла ошибка во время регистрации: ' . $e->getMessage());
            }
            if (isset($response['code'])) {
                foreach ($response['errors'] as $error) {
                    $form->addError(new FormError($error));
                }
            } else {
                $user = new User();
                $user->setToken($response['token']);
                $user->decodeToken();

                return $authenticator->authenticateUser(
                    $user,
                    $formAuthenticator,
                    $request
                );
            }
        }
        return $this->render(
            'security/registration.html.twig',
            ['registrationForm' => $form->createView()]
        );
    }
}
