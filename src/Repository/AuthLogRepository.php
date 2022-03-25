<?php

namespace App\Repository;

use App\Entity\AuthLog;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method AuthLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthLog[]    findAll()
 * @method AuthLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthLogRepository extends ServiceEntityRepository
{
    public const BLACK_LISTING_DELAY_IN_MINUTES = 15;
    public const MAX_FAILED_AUTH_ATTEMPTS = 5;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthLog::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function addFailedAuthAttempt(
        string $emailEntered,
        ?string $userIp,
        bool $isBlacklisted = false
    ): void
    {
        $authAttempt = (new AuthLog($emailEntered, $userIp))->setIsAuthSuccessful(false);

        if ($isBlacklisted) {
            $authAttempt->setStartOfBlacklisting(new DateTimeImmutable('now'))
                        ->setEndOfBlacklisting(new DateTimeImmutable(sprintf('+%d minutes', self::BLACK_LISTING_DELAY_IN_MINUTES)));
        }

        $this->_em->persist($authAttempt);

        $this->_em->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addSuccessfulAuthAttempt(
        string $emailEntered,
        ?string $userIp,
        bool $isRememberMeAuth = false
    ): void
    {
        $authAttempt = new AuthLog($emailEntered, $userIp);

        $authAttempt->setIsAuthSuccessful(true)
                    ->setIsRememberMeAuth($isRememberMeAuth);

        $this->_em->persist($authAttempt);

        $this->_em->flush();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws Exception
     */
    public function getRecentAuthAttemptFailure(
        string $emailEntered,
        ?string $userIp
    ): int
    {
        return $this->createQueryBuilder('af')
                    ->select('COUNT(af)')
                    ->where('af.authAttemptAt >= :datetime')
                    ->andWhere('af.emailEntered = :email_entered')
                    ->andWhere('af.isAuthSuccessful = false')
                    ->andWhere('af.userIp = :user_ip')
                    ->setParameters([
                        'datetime' => new DateTimeImmutable(sprintf('-%d minutes', self::BLACK_LISTING_DELAY_IN_MINUTES)),
                        'email_entered' => $emailEntered,
                        'user_ip' => $userIp,
                    ])
                    ->getQuery()
                    ->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function isBlacklistedWithThisAttemptFailure(
        string $emailEntered,
        ?string $userIp
    ): bool
    {
        return $this->getRecentAuthAttemptFailure($emailEntered, $userIp) >= self::MAX_FAILED_AUTH_ATTEMPTS - 2;
    }

    /**
     * @throws NonUniqueResultException
     * @throws Exception
     */
    public function getEmailAndIpOfBlacklistedUserIfExists(
        string $emailEntered,
        ?string $userIp
    ): ?AuthLog
    {
        return $this->createQueryBuilder('bl')
                    ->select('bl')
                    ->Where('bl.emailEntered = :email_entered')
                    ->andWhere('bl.endOfBlacklisting IS NOT NULL')
                    ->andWhere('bl.endOfBlacklisting >= :datetime')
                    ->andwhere('bl.userIp = :user_ip')
                    ->setParameters([
                        'email_entered' => $emailEntered,
                        'user_ip' => $userIp,
                        'datetime' => new DateTimeImmutable(sprintf('-%d minutes', self::BLACK_LISTING_DELAY_IN_MINUTES)),
                    ])
                    ->orderBy('bl.id', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getEndOfBlacklisting(
        string $emailEntered,
        ?string $userIp
    ): ?string
    {
        $blacklist = $this->getEmailAndIpOfBlacklistedUserIfExists($emailEntered, $userIp);

        if (!$blacklist || $blacklist->getEndOfBlacklisting() === null ) {
            return null;
        }

        return $blacklist->getEndOfBlacklisting()->add(new DateInterval('PT1M'))->format('H\hi');
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AuthLog $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(AuthLog $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
