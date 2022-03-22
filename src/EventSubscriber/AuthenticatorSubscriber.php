<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Http\Event\DeauthenticatedEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class AuthenticatorSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $securityLogger;
    private RequestStack $requestStack;

    public function __construct(
        LoggerInterface $securityLogger,
        RequestStack $requestStack
    )
    {
        $this->securityLogger = $securityLogger;
        $this->requestStack = $requestStack;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onSecurityAuthenticationFailure',
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onSecurityAuthenticationSuccess',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            'Symfony\Component\Security\Http\Event\LogoutEvent' => 'onSecurityLogout',
            'security.logout_on_change' => 'onSecurityLogoutOnChange',
            SecurityEvents::SWITCH_USER => 'onSecuritySwitchUser'
        ];
    }

    public function onSecurityAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        ['user_ip' => $userIp] = $this->getRouteNameAndUserIp();

        $securityToken = $event->getAuthenticationToken();
        ['email' => $emailEntered] = $securityToken->getCredentials();

        $this->securityLogger->info('Un utilisateur ayant l\'adresse IP ' . $userIp . ' a tenté de s\'authentifier sans succès avec l\'email suivant : ' . $emailEntered . ' :-)');
    }

    public function onSecurityAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        [
            'route_name' => $routeName,
            'user_ip' => $userIp
        ] = $this->getRouteNameAndUserIp();

        if (empty($event->getAuthenticationToken()->getRoleNames())) {
            $this->securityLogger->info('OOOPS, un utilisateur anonyme ayant l\'adresse IP ' . $userIp . ' est apparu sur la route : ' . $routeName . ' :-)');
        } else {
            $securityToken = $event->getAuthenticationToken();

            $userEmail = $this->getUserEmail($securityToken);

            $this->securityLogger->info('Nouvelle connexion ! Un utilisateur ayant l\'adresse IP ' . $userIp . ' a évolué en entité User avec l\'email ' . $userEmail . ' :-)');
        }
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        ['user_ip' => $userIp] = $this->getRouteNameAndUserIp();

        $securityToken = $event->getAuthenticationToken();
        $userEmail = $this->getUserEmail($securityToken);

        $this->securityLogger->info('Connexion interactive ! Un utilisateur ayant l\'adresse IP ' . $userIp . ' a évolué en entité User avec l\'email ' . $userEmail . ' :-)');

    }

    public function onSecurityLogout(LogoutEvent $event): void
    {
        /** @var RedirectResponse|null $response */
        $response = $event->getResponse();

        /** @var TokenInterface|null $securityToken */
        $securityToken = $event->getToken();

        if (!$response || !$securityToken) {
            return;
        }

        ['user_ip' => $userIp] = $this->getRouteNameAndUserIp();

        $userEmail = $this->getUserEmail($securityToken);

        $targetUrl = $response->getTargetUrl();

        $this->securityLogger->info('L\'utilisateur ayant l\'adresse IP ' . $userIp . ' et l\'email ' . $userEmail . ' s\'déconnecté et a été redirigé vers l\'URL suivante : ' . $targetUrl . ' :-)');
    }

    public function onSecurityLogoutOnChange(DeauthenticatedEvent $event): void
    {
        // ...
    }

    public function onSecuritySwitchUser(SwitchUserEvent $event): void
    {
        // ...
    }

    /**
     * Returns the user's ip and the name of the route where the user arrived.
     *
     * @return array{user_ip: string|null, route_name: mixed}
     */
    private function getRouteNameAndUserIp(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return [
                'route_name' => 'Inconnue',
                'user_ip' => 'Inconnue'
            ];
        }

        return [
            'route_name' => $request->attributes->get('_route'),
            'user_ip' => $request->getClientIp() ?? 'Inconnue'
        ];
    }

    private function getUserEmail(TokenInterface $securityToken): string
    {
        /** @var User $user */
        $user = $securityToken->getUser();

        return $user->getEmail();
    }
}
