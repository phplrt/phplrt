<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Testo\Core\Exception\SkipTest;
use Testo\Lifecycle\AfterTest;

/**
 * @property-read string $temp
 */
abstract class TestCase
{
    private const TEMP_DIRECTORY = __DIR__ . '/temp';

    private ?string $tempPathname = null;

    public function __get(string $property): mixed
    {
        return match ($property) {
            'temp' => $this->tempPathname ??= self::TEMP_DIRECTORY
                . \DIRECTORY_SEPARATOR
                . \uniqid('phplrt_test_', true) . '.txt',
            default => throw new \Error(\sprintf(
                'Undefined property %s::$%s',
                static::class,
                $property,
            )),
        };
    }

    protected function createNonSeekableResource(string $content = '')
    {
        $pair = @\stream_socket_pair(\STREAM_PF_INET, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);

        if ($pair === false) {
            throw new SkipTest('The platform does not support socket pairs');
        }

        [$read, $write] = $pair;

        \fwrite($write, $content);
        \fclose($write);

        return $read;
    }

    #[AfterTest]
    protected function tearDown(): void
    {
        if (\is_file($this->temp)) {
            \unlink($this->temp);
        }
    }
}
