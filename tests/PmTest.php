<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PmTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testOne()
    {
        //$this->seed('DatabaseSeeder');

        $this
            ->visit('/pay/pm')
            ->seeJsonStructure([
                'wallets' => [
                    0,
                    1,
                    2
                ],
                'history'
            ]);
    }
}
