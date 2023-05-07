<?php

namespace App\Security;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use JsonException;
use PHPUnit\Util\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private BillingClient $billingClient;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator, BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    /**
     */
    public function authenticate(Request $request): SelfValidatingPassport
    {
        try {
            $email = $request->request->get('email', '');
            $password = $request->request->get('password', '');

            $credentials = json_encode([
                'username' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR);

            return new SelfValidatingPassport(
                new UserBadge($credentials, function ($credentials) {
                    try {
                        $response = $this->billingClient->auth($credentials);
                    } catch (BillingUnavailableException|JsonException $e) {
                        throw new Exception('Произошла ошибка во время авторизации: ' . $e->getMessage());
                    }
                    if (isset($response['code'])) {
                        throw new BillingException('Ошибка авторизации. Проверьте правильность данных!', 401);
                    }
                    $user = new User();
                    $user->setToken($response['token']);
                    $user->setRefreshToken($response['refresh_token']);
                    $user->decodeToken();
                    return $user;
                }),
                [
                    new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                    new RememberMeBadge(),
                ]
            );
        } catch (JsonException $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage());
        }
    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
