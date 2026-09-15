<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Przykładowy test PHPUnit: sprawdza samo działanie asercji, nie logikę EventManagera.
 */
class ExampleTest extends TestCase
{
    /**
     * Przykład prostej asercji bez sprawdzania funkcji biznesowej.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
