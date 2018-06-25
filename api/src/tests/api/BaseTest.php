<?php

namespace app\tests\api;

require __DIR__ . '/../../vendor/autoload.php';

use Codeception\Module\MultiDb;
use Codeception\TestCase\Test;
use Codeception\Util\Debug;
use Faker\Factory;
use PDO;
use PHPUnit\Runner\Exception;


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
        $this->clearUsersDb();
        $this->clearOrdersDb();
        $this->clearTransactionsDb();

        $memcache = memcache_connect('cache', 11211);
        memcache_flush($memcache);
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

    protected function getTransactionFromOrder($orderId)
    {
        $pdo = $this->_multiDb->connections['transactionDb'];
        $s   = $pdo->prepare('SELECT * FROM `transactions` WHERE order_id=:order_id');
        $s->execute([
            ':order_id' => $orderId,
        ]);

        return $s->fetch(PDO::FETCH_ASSOC);
    }

    protected function getOrder($id)
    {
        $pdo = $this->_multiDb->connections['orderDb'];
        $s   = $pdo->prepare('SELECT * FROM `orders` WHERE id=:id');
        $s->execute([
            ':id' => $id,
        ]);

        return $s->fetch(PDO::FETCH_ASSOC);
    }

    protected function getUser($id)
    {
        $pdo = $this->_multiDb->connections['userDb'];
        $s   = $pdo->prepare('SELECT * FROM `users` WHERE id=:id');
        $s->execute([
            ':id' => $id,
        ]);

        return $s->fetch(PDO::FETCH_ASSOC);
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