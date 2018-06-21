<?php

namespace app\tests\api;

require_once __DIR__ . '/BaseTest.php';

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

    public function testNegativeMultiRegister()
    {
        $this->request('user.register', [
            'login'    => 'test',
            'password' => 'testpassword',
            'type'     => 1,
        ]);
        $this->request('user.register', [
            'login'    => 'test',
            'password' => 'password',
            'type'     => 1,
        ]);
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 403,
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

    public function testNegativeLoginInvalidPassword()
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
            'password' => 'wrongpassword',
        ]);
        $this->tester->seeResponseContainsJson([
            'meta' => [
                'code' => 404,
            ],
        ]);
    }
}