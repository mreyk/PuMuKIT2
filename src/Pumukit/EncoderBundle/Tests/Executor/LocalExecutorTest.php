<?php

namespace Pumukit\EncoderBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\EncoderBundle\Executor\LocalExecutor;

class LocalExecutorTest extends WebTestCase
{
    public function setUp()
    {
        $options = array('environment' => 'test');
        static::bootKernel($options);
    }

    public function testSimple()
    {
        $executor = new LocalExecutor();
        $out = $executor->execute('sleep 1 && echo a');
        $this->assertEquals("a\n\n", "$out");
    }
}
