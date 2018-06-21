<?php

namespace app\tests\api;

require __DIR__ . '/../../vendor/autoload.php';

use Codeception\Module\MultiDb;
use Codeception\TestCase\Test;
use Faker\Factory;
use PDO;


class BaseTest extends Test
{

    const ROLE_HIRER  = 1;
    const ROLE_WORKER = 2;

    /**
     * @var \ApiTester
     */
    protected $tester;

    /** @var MultiDb */
    protected $_multiDb;
    protected $_faker;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->_faker = Factory::create();

        parent::__construct($name, $data, $dataName);
    }

    protected function _before()
    {
        $this->_multiDb = $this->getModule('MultiDb');
    }

    protected function _after()
    {
        $this->clearUsersDb();
        $this->clearOrdersDb();
        $this->clearTransactionsDb();

        parent::_after();
    }


    protected function clearUsersDb()
    {
        /** @var PDO $pdo */
        $pdo = $this->_multiDb->connections['userDb'];
        $pdo->query('TRUNCATE `users`');
    }

    protected function clearOrdersDb()
    {
        $pdo = $this->_multiDb->connections['orderDb'];
        $pdo->query('TRUNCATE `orders`');
    }

    protected function clearTransactionsDb()
    {
        $pdo = $this->_multiDb->connections['transactionDb'];
        $pdo->query('TRUNCATE `transactions`');
    }

    protected function request($method, $params)
    {
        $params['method'] = $method;

        $this->tester->haveHttpHeader('Content-Type', 'application/json');
        $this->tester->sendPOST('', $params);
    }

    /**
     * @param $role
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getToken($role)
    {
        $login    = $this->_faker->name;
        $password = $this->_faker->password;

        $this->request('user.register', [
            'login'    => $login,
            'password' => $password,
            'type'     => $role,
        ]);

        $this->request('user.auth', [
            'login'    => $login,
            'password' => $password,
        ]);

        return $this->tester->grabDataFromResponseByJsonPath('$.token')[0];
    }


}