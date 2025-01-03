<?php

namespace App\Tests\Entity;

use App\Entity\Result;
use App\Entity\User;
use Faker\Factory as FakerFactoryAlias;
use Faker\Generator as FakerGeneratorAlias;
use PHPUnit\Framework\TestCase;

/**
 * Class ResultTest
 *
 * @package App\Tests\Entity
 *
 * @group   entities
 * @coversDefaultClass \App\Entity\Result
 */
class ResultTest extends TestCase
{
    private static FakerGeneratorAlias $faker;
    private static User $user;

    /**
     * Sets up the fixture.
     * This method is called before any test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        self::$faker = FakerFactoryAlias::create();
        self::$user = new User(
            self::$faker->email(),
            self::$faker->password(20),
            [ 'ROLE_USER' ]
        );
    }

    /**
     * Test the constructor.
     *
     * @return void
     */
    public function testConstructor(): void
    {
        $resultValue = self::$faker->randomFloat(2, 0, 100);
        $time = new \DateTime();

        $result = new Result(self::$user, $resultValue, $time);

        self::assertSame(self::$user, $result->getUser());
        self::assertSame($resultValue, $result->getResult());
        self::assertSame($time, $result->getTime());
    }

    /**
     * Test getId().
     *
     * @return void
     */
    public function testGetId(): void
    {
        $result = new Result(self::$user, 50.5, new \DateTime());
        self::assertNull($result->getId());
    }

    /**
     * Test setUser() and getUser().
     *
     * @return void
     */
    public function testSetGetUser(): void
    {
        $result = new Result(self::$user, 50.5, new \DateTime());
        $newUser = new User(
            self::$faker->email(),
            self::$faker->password(20),
            [ 'ROLE_ADMIN' ]
        );

        $result->setUser($newUser);
        self::assertSame($newUser, $result->getUser());
    }

    /**
     * Test setResult() and getResult().
     *
     * @return void
     */
    public function testSetGetResult(): void
    {
        $resultValue = self::$faker->randomFloat(2, 0, 100);
        $result = new Result(self::$user, $resultValue, new \DateTime());

        $newResult = self::$faker->randomFloat(2, 0, 100);
        $result->setResult($newResult);

        self::assertSame($newResult, $result->getResult());
    }

    /**
     * Test setTime() and getTime().
     *
     * @return void
     */
    public function testSetGetTime(): void
    {
        $time = new \DateTime();
        $result = new Result(self::$user, 50.5, $time);

        $newTime = new \DateTime('+1 day');
        $result->setTime($newTime);

        self::assertSame($newTime, $result->getTime());
    }

    /**
     * Test jsonSerialize().
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $resultValue = self::$faker->randomFloat(2, 0, 100);
        $time = new \DateTime();

        $result = new Result(self::$user, $resultValue, $time);

        $expected = [
            'Id' => $result->getId(),
            Result::RESULT_ATTR => $result->getResult(),
            Result::TIME_ATTR => $result->getTime()->format('c'),
        ];

        self::assertSame($expected, $result->jsonSerialize());
    }
}