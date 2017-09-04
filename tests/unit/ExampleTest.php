<?php
use PHPUnit\Framework\TestCase;

/**
 * @covers Email
 */
final class EmailTest extends TestCase
{
    public function testCanBeCreatedFromValidEmailAddress()
    {
        $this->assertTrue(true);
    }
    public function testError()
    {
        $this->assertTrue(false);
    }
}
