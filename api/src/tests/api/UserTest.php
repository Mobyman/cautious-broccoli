<?php

namespace app\tests\api;

use Codeception\Module\MultiDb;
use Codeception\TestCase\Test;
use Codeception\Util\Debug;

class UserTest extends BaseTest
{
    public function testRegister()
    {
        $this->request('user.register', [
            'login'    => 'test',
            'password' => 'testpassword',
            'type'     => 1,
        ]);

        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 200,
            ],
        ]);
    }

    public function testLogin()
    {
        $this->request('user.register', [
            'login'    => 'test',
            'password' => 'testpassword',
            'type'     => 1,
        ]);
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 200,
            ],
        ]);

        $this->request('user.auth', [
            'login'    => 'test',
            'password' => 'testpassword',
        ]);
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 200,
            ],
        ]);
        $this->tester->seeResponseJsonMatchesXpath('token');
    }
}