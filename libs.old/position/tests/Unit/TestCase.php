<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests\Unit;

use Phplrt\Position\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public static function provider(): array
    {
        $result = [];

        for ($i = 1; $i < 10; ++$i) {
            $string = '';

            for ($j = 1, $len = \random_int(2, 10); $j < $len; ++$j) {
                $line = \base64_encode(\random_bytes(\random_int(1, $j)));
                $string .= \str_replace(['=', '+', '/'], '', $line) . "\n";
            }

            $result[\strlen($string) . ' bytes / ' . $len . ' lines'] = [$string, $j];
        }

        return $result;
    }
}
