<?php

namespace App\Controller;

use App\Entity\Result;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 */
#[Route(
    path: ApiResultsQueryInterface::RUTA_API,
    name: 'api_results_'
)]
class ApiResultsQueryController extends AbstractController
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @see ApiUsersQueryInterface::cgetAction()
     *
     * @throws JsonException
     */
    #[Route(
        path: ".{_format}/{sort?id}",
        name: 'cget',
        requirements: [
            'sort' => "id|result|time",
            '_format' => "json|xml"
        ],
        defaults: ['_format' => 'json',
            'sort' => 'id'
        ],
        methods: [Request::METHOD_GET],
    )]
    public function cgetAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format);
        }

        $order = strval($request->get('sort'));
        $user = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            $results = $this->entityManager->getRepository(Result::class)->findBy([], [$order => 'ASC']);
        } else {
            $results = $this->entityManager->getRepository(Result::class)->findBy(['user' => $user], [$order => 'ASC']);
        }

        if (empty($results)) {
            return Utils::errorMessage(
                Response::HTTP_NOT_FOUND,
                null, $format);
        }

        $etag = md5((string) json_encode($results, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(
                null,
                Response::HTTP_NOT_MODIFIED);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results' => array_map(fn($r) => ['result' => $r], $results)],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }
    /**
     * @see ApiResultsQueryInterface::optionsAction()
     */
    #[\Symfony\Component\Routing\Attribute\Route(
        path: "/{resultId}.{_format}",
        name: 'options',
        requirements: [
            'resultId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ 'resultId' => 0, '_format' => 'json' ],
        methods: [ Request::METHOD_OPTIONS ],
    )]
    public function optionsAction(int|null $resultId): Response
    {
        $methods = $resultId !== 0
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [ Request::METHOD_GET, Request::METHOD_POST ];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(',', $methods),
                self::HEADER_CACHE_CONTROL => 'public, immutable'
            ]
        );
    }
}