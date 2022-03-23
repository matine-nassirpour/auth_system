<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/profile", name="app_user_profile_")
 */
class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="home", methods={"GET"})
     */
    public function home(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('user/index.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/add-ip", name="add_ip", methods={"GET"})
     */
    public function addUserIpToWhiteList(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$request->isXmlHttpRequest()) {
            throw new \HttpException('The header "X-Requested-With" is missing', 400);
        }

        $userIp = $request->getClientIp();
        /** @var User $user */ $user = $this->getUser();
        $user->setWhitelistedIpAddresses($userIp);

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Adresse IP a bien été ajoutée à la liste blanche',
            'user_ip' => $userIp
        ]);
    }

    /**
     * @Route("/toggle-checking-ip", name="toggle_checking_ip", methods={"POST"})
     */
    public function toggleGuardCheckingIp(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$request->isXmlHttpRequest()) {
            throw new \HttpException('The header "X-Requested-With" is missing', 400);
        }

        $switchValue = $request->getContent();

        if (!in_array($switchValue, ['true', 'false'], true)) {
            throw new \HttpException('Expected value is "true" or "false"', 400);
        }

        /** @var User $user */ $user = $this->getUser();

        $isSwitchOn = filter_var($switchValue, FILTER_VALIDATE_BOOLEAN);

        $user->setIsGuardCheckIp($isSwitchOn);

        $this->entityManager->flush();

        return $this->json([
            'isGuardCheckingIp' => $isSwitchOn
        ]);
    }
}
