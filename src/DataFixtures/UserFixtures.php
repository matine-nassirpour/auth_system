<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager): void
    {
        $firstUser = new User();
        $firstUser->setEmail('matine.nassirpour@chatelet.fr')
                  ->setPassword($this->passwordEncoder->encodePassword($firstUser, 'chatelet'))
                  ->setIsVerified(true)
                  ->setAccountVerifiedAt((new DateTimeImmutable('now'))->add(new DateInterval('PT7M')))
        ;

        $secondUser = new User();
        $secondUser->setEmail('olivier.bastin@chatelet.fr')
                   ->setPassword($this->passwordEncoder->encodePassword($secondUser, 'chatelet'))
        ;

        $thirdUser = new User();
        $thirdUser->setEmail('louis.morvan@chatelet.fr')
            ->setPassword($this->passwordEncoder->encodePassword($thirdUser, 'chatelet'))
        ;

        $manager->persist($firstUser);
        $manager->persist($secondUser);
        $manager->persist($thirdUser);

        $manager->flush();
    }
}
