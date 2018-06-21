<?php

namespace app\tests\api;

require_once __DIR__ . '/BaseTest.php';

class OrderTest extends BaseTest
{
    public function testCreate()
    {
        $token = $this->getToken(self::ROLE_HIRER);
        $this->request('order.create', [
            'token' => $token,
            'cost' => $this->_faker->numberBetween(100, 10000),
            'title' => 'title',
            'description' => 'description'
        ]);
        $this->tester->seeResponseJsonMatchesXpath('order_id');
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 200,
            ],
        ]);
    }


    public function testCreateAndAssign()
    {
        $hirerToken = $this->getToken(self::ROLE_HIRER);
        $workerToken = $this->getToken(self::ROLE_WORKER);

        $this->request('order.create', [
            'token' => $hirerToken,
            'cost' => $this->_faker->numberBetween(100, 10000),
            'title' => 'title',
            'description' => 'description'
        ]);
        $this->tester->seeResponseJsonMatchesXpath('order_id');
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 200,
            ],
        ]);
        $orderId = $this->tester->grabDataFromResponseByJsonPath('$.order_id')[0];

        $this->request('order.assign', [
            'token' => $workerToken,
            'order_id' => $orderId,
        ]);
        $this->tester->seeResponseJsonMatchesXpath('order_id');
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 200,
            ],
        ]);
    }

    public function testNegativeCreateFromInvalidRole()
    {
        $hirerToken = $this->getToken(self::ROLE_HIRER);
        $workerToken = $this->getToken(self::ROLE_WORKER);

        $this->request('order.create', [
            'token' => $workerToken,
            'cost' => $this->_faker->numberBetween(100, 10000),
            'title' => 'title',
            'description' => 'description'
        ]);
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 403,
            ],
        ]);

        $this->request('order.create', [
            'token' => $hirerToken,
            'cost' => $this->_faker->numberBetween(100, 10000),
            'title' => 'title',
            'description' => 'description'
        ]);
        $orderId = $this->tester->grabDataFromResponseByJsonPath('$.order_id')[0];
        $this->request('order.assign', [
            'token' => $hirerToken,
            'order_id' => $orderId,
        ]);
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 403,
            ],
        ]);
    }

    public function testList()
    {
        $token = $this->getToken(self::ROLE_HIRER);

        $this->request('order.create', [
            'token' => $token,
            'cost' => $this->_faker->numberBetween(100, 10000),
            'title' => implode(' ', $this->_faker->words(5)),
            'description' => $this->_faker->paragraph(2)
        ]);

        $this->request('order.list', [
            'token' => $token,
            'page' => 1,
        ]);
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 200,
            ],
        ]);
    }

}