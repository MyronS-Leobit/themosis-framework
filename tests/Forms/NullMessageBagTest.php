<?php

namespace Themosis\Tests\Forms;

use Illuminate\Contracts\Support\MessageBag;
use PHPUnit\Framework\TestCase;
use Themosis\Forms\NullMessageBag;

class NullMessageBagTest extends TestCase
{
    public function testItSatisfiesTheIlluminateMessageBagContract()
    {
        $this->assertInstanceOf(MessageBag::class, new NullMessageBag());
    }

    public function testForgetReturnsTheBagForFluentChaining()
    {
        // forget() was added to the MessageBag contract in Laravel 10; the null
        // object must implement it and stay chainable like its other mutators.
        $bag = new NullMessageBag();

        $this->assertSame($bag, $bag->forget('field'));
        $this->assertFalse($bag->has('field'));
        $this->assertTrue($bag->isEmpty());
    }
}
