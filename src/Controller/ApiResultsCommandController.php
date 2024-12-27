<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\User;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(
    path: ApiResultsQueryInterface::RUTA_API,
    name: 'api_results_'
)]
class ApiResultsCommandController extends AbstractController
{
    private const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @see ApiResultsCommandInterface::postAction()
     *
     * @throws JsonException
     */
    #[Route(
        path: ".{_format}",
        name: 'post',
        requirements: ['_format' => "json|xml"],
        defaults: ['_format' => null],
        methods: [Request::METHOD_POST],
    )]
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                'Unauthorized: Invalid credentials.',
                $format);
        }

        $authenticatedUser = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;

        if ((!$this->isGranted(self::ROLE_ADMIN)) && $authenticatedUser->getId() !== $userId) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                "You don't have permission to create results for other users.",
                $format);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['result'], $data['time'], $data['userId'])) {
            return Utils::errorMessage(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Unprocessable Entity: Missing data.',
                $format);
        }

        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return Utils::errorMessage(
                Response::HTTP_NOT_FOUND,
                'Not Found: User not found.',
                $format);
        }

        $result = new Result($data['result'], new \DateTime($data['time']), $user);

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_CREATED, $result, $format);
    }

    /**
     * @see ApiResultsCommandInterface::putAction()
     *
     * @throws JsonException
     */
    #[Route(
        path: "/{resultId}.{_format}",
        name: 'put',
        requirements: [
            'resultId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: ['_format' => null],
        methods: [Request::METHOD_PUT],
    )]
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                'Unauthorized: Invalid credentials.',
                $format);
        }

        $user = $this->getUser();
        $result = $this->entityManager->getRepository(Result::class)->find($resultId);

        if (!$result) {
            return Utils::errorMessage(
                Response::HTTP_NOT_FOUND,
                'Not Found: Result not found.',
                $format);
        }

        if ($result->getUser()->getId() !== $user->getId() && !$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                'Forbidden: You don\'t have permission to access.',
                $format);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['result'])) {
            $result->setResult($data['result']);
        }
        if (isset($data['time'])) {
            $result->setTime(new \DateTime($data['time']));
        }

        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_OK, $result, $format);
    }

    /**
     * @see ApiResultsCommandInterface::deleteAction()
     *
     * @throws JsonException
     */
    #[Route(
        path: "/{resultId}.{_format}",
        name: 'delete',
        requirements: [
            'id' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: ['_format' => null],
        methods: [Request::METHOD_DELETE],
    )]
    public function deleteAction(int $resultId, Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                'Unauthorized: Invalid credentials.',
                $format);
        }

        $user = $this->getUser();
        $result = $this->entityManager->getRepository(Result::class)->find($resultId);

        if (!$result) {
            return Utils::errorMessage(
                Response::HTTP_NOT_FOUND,
                'Not Found: Result not found.',
                $format);
        }

        if ($result->getUser()->getId() !== $user->getId() && !$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                'Forbidden: You don\'t have permission to access.',
                $format);
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * @see ApiResultsCommandInterface::deleteAllAction()
     *
     * @throws JsonException
     */
    #[Route(
        path: "/user/{userId}.{_format}",
        name: 'delete_all',
        requirements: [
            'userId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: ['_format' => null],
        methods: [Request::METHOD_DELETE],
    )]
    public function deleteAllAction(int $userId, Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                'Unauthorized: Invalid credentials.',
                $format);
        }

        $user = $this->getUser();
        if ($user->getId() !== $userId && !$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                'Forbidden: You don\'t have permission to delete results for this user.',
                $format);
        }

        $results = $this->entityManager->getRepository(Result::class)->findBy(['user' => $userId]);

        if (!$results) {
            return Utils::errorMessage(
                Response::HTTP_NOT_FOUND,
                'Not Found: No results found for this user.',
                $format);
        }

        foreach ($results as $result) {
            $this->entityManager->remove($result);
        }
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }
}