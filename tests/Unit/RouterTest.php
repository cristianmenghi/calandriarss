<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utils\Router;

class RouterTest extends TestCase
{
    public function test_it_registers_and_resolves_routes()
    {
        $router = new Router();
        $executed = false;

        $router->get('/test', function () use (&$executed) {
            $executed = true;
            return "ok";
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';

        $result = $router->resolve();

        $this->assertTrue($executed);
        $this->assertEquals("ok", $result);
    }
}
