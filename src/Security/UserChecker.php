<?php

namespace App\Security;

use App\Entity\User;
use App\Service\SendEmail;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    private RequestStack $requestStack;
    private SendEmail $sendEmail;

    public function __construct(
        RequestStack $requestStack,
        SendEmail $sendEmail
    )
    {
        $this->requestStack = $requestStack;
        $this->sendEmail = $sendEmail;
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->getIsVerified()) {
            throw new CustomUserMessageAccountStatusException('Votre compte n\'est pas actif, veuillez consulter vos e-mail pour l\'activer avant le ' . $user->getAccountMustBeVerifiedBefore()->format('d/m/Y à H\hi'));
        }

        if ($user->getIsGuardCheckIp() && !$this->isUserIpInWhiteListe($user)) {
            //  throw new CustomUserMessageAccountStatusException('Vous n\'êtes pas autorisé à vous identifier avec cette adresse IP, car elle ne se figure pas sur la liste blanche des adresses IP autorisées !');
            $this->sendEmail->send([
                'email_recipient' => $user->getEmail(),
                'subject' => 'Avertissement',
                'html_template' => 'email/invalid_ip.html.twig',
                'context' => [
                    'userIp' => $this->requestStack->getCurrentRequest()->getClientIp()
                ]
            ]);
        }
    }

    private function isUserIpInWhiteListe(User $user): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return false;
        }

        $userIp = $request->getClientIp();

        $userWhiteListIp = $user->getWhitelistedIpAddresses();

        return in_array($userIp, $userWhiteListIp, true);
    }
}
