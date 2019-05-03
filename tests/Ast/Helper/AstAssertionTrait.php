<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Ast\Helper;

use Phplrt\Ast\Dumper\XmlDumper;
use Phplrt\Ast\NodeInterface;

/**
 * Trait AstAssertionTrait
 */
trait AstAssertionTrait
{
    /**
     * @param string $haystack
     * @param string|NodeInterface $needle
     * @return void
     */
    protected function assertAst(string $haystack, $needle): void
    {
        if (\is_string($needle)) {
            $this->assertAstString($haystack, (string)$needle);
            return;
        }

        $this->assertAstString($haystack, (new XmlDumper())->dump($needle));
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param string $message
     * @return void
     */
    protected function assertAstString(string $haystack, string $needle, string $message = ''): void
    {
        $this->assertSame(
            $this->normalizeAst(\trim($haystack)),
            $this->normalizeAst(\trim($needle)),
            $message
        );
    }

    /**
     * @param string $xml
     * @return string
     */
    private function normalizeAst(string $xml): string
    {
        return \preg_replace_callback('/^\h*(.*?)$/isum', static function (array $matches) {
            [,$code] = $matches;

            return $code;
        }, $xml) ?? $xml;
    }
}
