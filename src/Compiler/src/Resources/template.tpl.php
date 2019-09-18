<?php declare(strict_types=1);
/** @var $this \Phplrt\Compiler\Generator\ZendGenerator */
echo '<?php';
?>

/**
 * This is an automatically generated file, which should not be manually edited.
 *
 * @created <?=\date(\DateTime::RFC3339); ?>

 *
 * @see https://github.com/phplrt/phplrt
 * @see https://github.com/phplrt/phplrt/blob/master/LICENSE.md
 */
declare(strict_types=1);

<?php if ($this->namespace): ?>
namespace <?=$this->namespace; ?>;
<?php endif; ?>

/**
 * The main class of the generated parser.
 *
 * @package <?=$this->fqn; ?>

 * @generator \<?=static::class; ?>

 */
class <?=$this->class; ?> extends \Phplrt\Parser\Parser implements
    \Phplrt\Parser\Builder\BuilderInterface
{
<?php foreach ($this->getTokens() as $name => $value): ?>
<?php if (\is_int($name)) {
    continue;
} ?>

    /** @var string */
    public const <?=$this->constantName($name); ?> = <?=$this->value($name); ?>;
<?php endforeach; ?>

    /**
     * @var string[]
     */
    private const LEXER_TOKENS = [
<?php foreach ($this->getTokens() as $name => $value): ?>
<?php if (\is_int($name)): ?>
        <?=$this->value($name); ?> => <?=$this->value($value); ?>,
<?php else: ?>
        self::<?=$this->constantName($name); ?> => <?=$this->value($value); ?>,
<?php endif; ?>
<?php endforeach; ?>
    ];

    /**
     * @var string[]
     */
    private const LEXER_SKIPS = [
<?php foreach ($this->analyzer->skip as $name => $value): ?>
        <?=$this->value($value); ?>,
<?php endforeach; ?>
    ];

    /**
     * A generated lexer instance.
     * @var \Phplrt\Contracts\Lexer\LexerInterface|\Phplrt\Lexer\Lexer
     */
    private $lexer;

    /**
     * @var array|\Closure[]
     */
    public $reducers = [];

    /**
     * <?=$this->class; ?> class constructor.
     */
    public function __construct()
    {
        $this->lexer = new \Phplrt\Lexer\Lexer(self::LEXER_TOKENS, self::LEXER_SKIPS);

        parent::__construct($this->lexer, $this->grammar(), [
            self::CONFIG_INITIAL_RULE   => <?=$this->value($this->analyzer->initial); ?>,
            self::CONFIG_AST_BUILDER    => $this,
        ]);
    }

    /**
     * @return array|\Phplrt\Parser\Rule\RuleInterface[]
     */
    private function grammar(): array
    {
        return [
<?php foreach ($this->getRules() as $id => $rule): ?>
            <?=$this->value($id); ?> => <?=$this->rule($rule); ?>,
<?php endforeach; ?>
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function build(\Phplrt\Parser\Rule\RuleInterface $rule, \Phplrt\Contracts\Lexer\TokenInterface $token, $state, $children)
    {
        $offset = $token->getOffset();
<?php if (\count($this->analyzer->reducers)): ?>
        try {
            switch ((string)$state) {
<?php foreach ($this->analyzer->reducers as $id => $code): ?>
                case <?=$this->value((string)$id); ?>:
                    <?=$code; ?>

                    break;
<?php endforeach; ?>
            }
        } catch (\Throwable $e) {
            $message = \sprintf('Error while reducing "%s" rule: ' . $e->getMessage(), $state);
            throw new \Phplrt\Parser\Exception\ParserRuntimeException($message, $token, $e);
        }
<?php endif; ?>

        if (isset($this->reducers[$state])) {
            return $this->reducers[$state]($children, $offset, $state);
        }

        return null;
    }
}
