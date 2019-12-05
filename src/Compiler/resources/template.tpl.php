<?php

declare(strict_types=1);

/** @var $this \Phplrt\Compiler\Generator\ZendGenerator */
echo '<?php';
?>

/**
 * This is an automatically generated file, which should not be manually edited.
 *
 * @created <?=\date(\DateTime::RFC3339)?>

 *
 * @see https://github.com/phplrt/phplrt
 * @see https://github.com/phplrt/phplrt/blob/master/LICENSE.md
 */

declare(strict_types=1);

<?php if ($this->namespace) : ?>
namespace <?=$this->namespace?>;

<?php endif; ?>
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Source\ReadableInterface;


<?php
$this->preload(Phplrt\Grammar\Rule::class);
$this->preload(Phplrt\Grammar\Production::class);
$this->preload(Phplrt\Grammar\Terminal::class);

foreach ($this->getRules() as $id => $rule) {
    $this->preload(\get_class($rule));
}

foreach ($this->getImports() as $fqn => $import):?>

/**
 * Note: This class was automatically imported from <?=$fqn?>

 * @created <?=\date(\DateTime::RFC3339)?>

 */
<?=$import?>

<?php endforeach; ?>

/**
 * The main class of the generated parser.
 *
 * @package <?=$this->fqn?>

 * @generator \<?=static::class?>

 */
class <?=$this->class?> extends <?=$this->hashIfImported(Phplrt\Parser\Parser::class)?>

{
<?php foreach ($this->getTokens() as $name => $value) :
    if (\is_int($name)) {
        continue;
    }
?>

    /** @var string */
    public const <?=$this->constantName($name)?> = <?=$this->value($name)?>;
<?php endforeach; ?>

    /**
     * @var string[]
     */
    private const LEXER_TOKENS = [
<?php foreach ($this->getTokens() as $name => $value) : ?>
<?php if (\is_int($name)) : ?>
        <?=$this->value($name)?> => <?=$this->value($value)?>,
<?php else : ?>
        self::<?=$this->constantName($name)?> => <?=$this->value($value)?>,
<?php endif; ?>
<?php endforeach; ?>
    ];

    /**
     * @var string[]
     */
    private const LEXER_SKIPS = [
<?php foreach ($this->analyzer->skip as $name => $value) : ?>
        <?=$this->value($value)?>,
<?php endforeach; ?>
    ];

    // --------------- Extra Constants -----------------------------------------
<?php foreach ($this->constants as $const) : ?>
    <?=$const?>

<?php endforeach; ?>

    // --------------- Extra Properties ----------------------------------------
<?php foreach ($this->properties as $property) : ?>
    <?=$property?>

<?php endforeach; ?>

    /**
     * <?=$this->class?> class constructor.
     */
    public function __construct()
    {
        $lexer = new <?=$this->hashIfImported(Phplrt\Lexer\Lexer::class)?>(self::LEXER_TOKENS, self::LEXER_SKIPS);

        parent::__construct($lexer, $this->grammar(), [
            self::CONFIG_INITIAL_RULE   => <?=$this->value($this->analyzer->initial)?>,
            self::CONFIG_AST_BUILDER    => new class implements <?=$this->hashIfImported(Phplrt\Parser\Builder\BuilderInterface::class)?>

            {
                /** {@inheritDoc} */
                public function build(ReadableInterface $file, RuleInterface $rule, TokenInterface $token, $state, $children)
                {
                    $offset = $token->getOffset();

<?php if (\count($this->analyzer->reducers)) : ?>
                    switch (true) {
<?php foreach ($this->analyzer->reducers as $id => $code) : ?>
                        case $state === <?=$this->value($id)?>:
                            <?=$code?>
                        break;
<?php endforeach; ?>
                    }
<?php endif; ?>

                    return null;
                }
            },
        ]);
    }

    /**
     * @return array|RuleInterface[]
     */
    private function grammar(): array
    {
        return [
<?php foreach ($this->getRules() as $id => $rule) : ?>
            <?=$this->value($id)?> => <?=$this->rule($rule)?>,
<?php endforeach; ?>
        ];
    }

    // --------------- Extra Methods -------------------------------------------
<?php foreach ($this->methods as $method) : ?>
    <?=$method?>

<?php endforeach; ?>
}
