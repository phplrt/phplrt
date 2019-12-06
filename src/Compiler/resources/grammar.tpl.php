<?php
/** @var $this \Phplrt\Compiler\Generator\ZendGenerator */

require __DIR__ . '/header.tpl.php';
require __DIR__ . '/imports.tpl.php';
?>

/**
 * The main generated grammar class.
 *
 * @package <?=$fqn . "\n"?>
 * @generator \<?=static::class . "\n"?>
 */
final class <?=$class . "\n"?>
{
<?php foreach ($this->getTokens() as $name => $value) :
    if (\is_int($name)) {
        continue;
    }
?>
    /**
     * A lexical token name
     * @see <?=$class?>::$lexemes
     * @var string
     */
    public const <?=$this->constantName($name)?> = <?=$this->value($name)?>;

<?php endforeach; ?>
<?php foreach ($this->getRules() as $id => $rule) :
    if (\is_int($id)) { continue; }
?>
    /**
     * A parser's rule name
     * @see <?=$class?>::$rules
     * @see <?=$class?>::__construct
     * @var string
     */
    public const <?=$this->constantName($id)?> = <?=$this->value($id)?>;

<?php endforeach; ?>
    /**
     * @var string|int
     */
    public $initial = self::<?=$this->constantName($this->analyzer->initial)?>;

    /**
     * @var array|string[]
     */
    public $lexemes = [
<?php foreach ($this->getTokens() as $name => $value) : ?>
<?php if (\is_int($name)) : ?>
        <?=$this->value($name)?> => <?=$this->value($value)?>,
<?php else : ?>
        self::<?=$this->constantName($name)?> => <?=$this->value($value)?>,
<?php endif; ?>
<?php endforeach; ?>
    ];

    /**
     * @var array|string[]
     * @psalm-var array<string>
     */
    public $skips = [
<?php foreach ($this->analyzer->skip as $name => $value) : ?>
        <?=$this->value($value)?>,
<?php endforeach; ?>
    ];

    /**
     * @var array|\Phplrt\Contracts\Grammar\RuleInterface[]
     * @psalm-var array<\Phplrt\Contracts\Grammar\RuleInterface>
     */
    public $grammar = [];

<?php if (\count($this->analyzer->rules)): ?>
    /**
     * <?=$class?> constructor.
     */
    final public function __construct()
    {
        $this->grammar = [
<?php foreach ($this->getRules() as $id => $rule) : ?>
<?php if (\is_int($id)): ?>
            <?=$this->value($id)?> => <?=$this->rule($rule)?>,
<?php else: ?>
            self::<?=$this->constantName($id)?> => <?=$this->rule($rule)?>,
<?php endif; ?>
<?php endforeach; ?>
        ];
    }
<?php endif; ?>
}

