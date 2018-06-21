<?php

namespace app\tests\api;

use Codeception\Module\MultiDb;
use Codeception\TestCase\Test;
use Codeception\Util\Debug;
use PDO;

class BaseTest extends Test
{

    /**
     * @var \ApiTester
     */
    protected $tester;

    /** @var MultiDb */
    protected $_multiDb;

    protected function _before() {
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
        $this->tester->haveHttpHeader('Content-Type', 'application/json');
        $params['method'] = $method;

        $this->tester->sendPOST('', $params);
    }

}