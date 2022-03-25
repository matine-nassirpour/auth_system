<?php

namespace App\Security;

use App\Repository\AuthLogRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\RequestStack;

class BruteForceChecker
{
    private AuthLogRepository $authLogRepository;
    private RequestStack $requestStack;

    public function __construct(
        AuthLogRepository $authLogRepository,
        RequestStack $requestStack
    )
    {
        $this->authLogRepository = $authLogRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function addFailedAuthAttempt(
        string $emailEntered,
        ?string $userIp
    ): void
    {
        if ($this->authLogRepository->isBlacklistedWithThisAttemptFailure($emailEntered, $userIp)) {
            $this->authLogRepository->addFailedAuthAttempt($emailEntered, $userIp, true);
        } else {
            $this->authLogRepository->addFailedAuthAttempt($emailEntered, $userIp);
        }
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getEndOfBlacklisting(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $emailEntered = $request->get('email');

        $userIp = $request->getClientIp();

        return $this->authLogRepository->getEndOfBlacklisting($emailEntered, $userIp);
    }
}
