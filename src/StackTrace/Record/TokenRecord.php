<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\StackTrace\Record;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class TokenRecord
 */
class TokenRecord extends Record
{
    /**
     * @var TokenInterface
     */
    public $token;

    /**
     * NodeRecord constructor.
     *
     * @param string $pathname
     * @param TokenInterface $token
     */
    public function __construct(string $pathname, TokenInterface $token)
    {
        $this->token = $token;

        parent::__construct($pathname, $token->getOffset());
    }
}
