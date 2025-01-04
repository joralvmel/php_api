<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\{Request, Response};
use App\Entity\{Result, User};

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsQueryController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;

    /** @var string $adminToken */
    private static string $adminToken;

    /**
     * Get admin token
     *
     * @return string
     */
    private function getAdminToken(): string
    {
        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'admin@example.com',
                'password' => 'admin_password',
            ])
        );

        $response = self::$client->getResponse();
        $data = json_decode($response->getContent(), true);

        return $data['token'];
    }

    /**
     * Test OPTIONS /results and /results/{id} 204 No Content
     *
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsAction204NoContent(): void
    {
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode(),
            'OPTIONS /results did not return 204 No Content'
        );
        self::assertNotEmpty(
            $response->headers->get('Allow'),
            'OPTIONS /results does not include Allow header'
        );

        $resultId = 1;
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . $resultId
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode(),
            'OPTIONS /results/{id} did not return 204 No Content'
        );
        self::assertNotEmpty(
            $response->headers->get('Allow'),
            'OPTIONS /results/{id} does not include Allow header'
        );
    }

    /**
     * Test POST /results 201 Created
     *
     * @covers ::postResult
     * @return array<string, string>
     */
    public function testPostResultAction201Created(): array
    {
        $userData = [
            User::EMAIL_ATTR => self::$faker->email(),
            User::PASSWD_ATTR => self::$faker->password(),
            User::ROLES_ATTR => [self::$faker->word()],
        ];

        self::$adminHeaders = $this->getTokenHeaders(
            self::$role_admin[User::EMAIL_ATTR],
            self::$role_admin[User::PASSWD_ATTR]
        );

        self::$client->request(
            Request::METHOD_POST,
            '/api/v1/users',
            [],
            [],
            self::$adminHeaders,
            json_encode($userData)
        );

        $userResponse = self::$client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $userResponse->getStatusCode(), 'User creation failed');
        self::assertTrue($userResponse->isSuccessful());
        self::assertNotNull($userResponse->headers->get('Location'));
        self::assertJson($userResponse->getContent());
        $user = json_decode($userResponse->getContent(), true)[User::USER_ATTR];
        $userId = $user['id'] ?? null;

        self::assertNotNull($userId, 'User ID is missing from the user creation response');

        $resultData = [
            'userId' => $userId,
            'result' => self::$faker->randomFloat(0, 0, 1000),
            'time' => self::$faker->dateTimeThisYear()->format('Y-m-d H:i:s'),
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            json_encode($resultData)
        );

        $response = self::$client->getResponse();
        self::assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $resultTime = (new \DateTime($result['time']))->format('Y-m-d H:i:s');

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode(), 'Result creation failed');
        self::assertArrayHasKey('id', $result, 'Result ID is missing');
        self::assertSame($resultData['result'], $result['result'], 'Result value mismatch');
        self::assertSame($resultData['time'], $resultTime, 'Result time mismatch');
        self::assertSame($resultData['userId'], $result['user']['id'], 'User ID mismatch');

        return $result;
    }

    /**
     * Test GET /results 200 Ok
     *
     * @depends testPostResultAction201Created
     *
     * @return string ETag header
     */

    public function testGetResultsAction200Ok(): string
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders
        );

        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            'GET /results did not return 200 OK'
        );

        self::assertJson($response->getContent(), 'Response content is not JSON');
        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data, 'Response data is not an array');
        self::assertNotEmpty($data, 'Response data is empty');
        $etag = $response->headers->get('ETag');
        self::assertNotEmpty($etag, 'ETag header is missing');
        return $etag;
    }

    /**
     * Test GET /results/{resultId} 200 Ok
     *
     * @param array<string,string> $result
     * @return string ETag header
     * @depends testPostResultAction201Created
     */
    public function testGetResultByIdAction200Ok(array $result): string
    {
        $resultId = $result['id'];
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            self::$adminHeaders
        );

        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            'GET /results/{resultId} did not return 200 OK'
        );

        self::assertJson($response->getContent(), 'Response content is not JSON');
        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data, 'Response data is not an array');
        self::assertNotEmpty($data, 'Response data is empty');
        self::assertArrayHasKey('result', $data, 'Result key is missing');
        $resultData = $data['result'];
        self::assertSame($resultId, $resultData['id'], 'Result ID mismatch');
        self::assertSame($result['result'], $resultData['result'], 'Result value mismatch');
        self::assertSame($result['time'], $resultData['time'], 'Result time mismatch');
        self::assertSame($result['user']['id'], $resultData['user']['id'], 'User ID mismatch');

        $etag = $response->headers->get('ETag');
        self::assertNotEmpty($etag, 'ETag header is missing');
        return $etag;
    }

    /**
     * Test PUT /results/{resultId} 200 Ok
     *
     * @param array<string,string> $result
     * @param string $etag
     * @return void
     * @depends testPostResultAction201Created
     * @depends testGetResultByIdAction200Ok
     */
    public function testPutResultByIdAction200Ok(array $result, string $etag): void
    {
        $resultId = $result['id'];
        $updatedResultData = [
            'userId' => $result['user']['id'],
            'result' => self::$faker->numberBetween(10, 5000),
            'time' => self::$faker->dateTimeThisDecade()->format('Y-m-d\TH:i:s.v\Z'),
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            array_merge(self::$adminHeaders, ['If-Match' => $etag]),
            json_encode($updatedResultData)
        );

        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            'PUT /results/{resultId} did not return 200 OK'
        );

        self::assertJson($response->getContent(), 'Response content is not JSON');
        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data, 'Response data is not an array');
        self::assertNotEmpty($data, 'Response data is empty');
        self::assertSame($resultId, $data['id'], 'Result ID mismatch');
        self::assertEquals($updatedResultData['result'], $data['result'], 'Result value mismatch');

        $expectedTime = (new \DateTime($updatedResultData['time']))->format('Y-m-d\TH:i:sP');
        $actualTime = (new \DateTime($data['time']))->format('Y-m-d\TH:i:sP');
        self::assertSame($expectedTime, $actualTime, 'Result time mismatch');

        self::assertSame($result['user']['id'], $data['user']['id'], 'User ID mismatch');
    }

    /**
     * Test DELETE /results/{resultId} 204 No Content
     *
     * @param array<string,string> $result
     * @return void
     * @depends testPostResultAction201Created
     */
    public function testDeleteResultByIdAction204NoContent(array $result): void
    {
        $resultId = $result['id'];

        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            self::$adminHeaders
        );

        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode(),
            'DELETE /results/{resultId} did not return 204 No Content'
        );

        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            self::$adminHeaders
        );

        $response = self::$client->getResponse();
        self::assertSame(
            Response::HTTP_NOT_FOUND,
            $response->getStatusCode(),
            'GET /results/{resultId} after DELETE did not return 404 Not Found'
        );
    }
}