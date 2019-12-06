<?php
/** @var $this \Phplrt\Compiler\Generator\ZendGenerator */

require __DIR__ . '/header.tpl.php';
require __DIR__ . '/imports.tpl.php';
?>

if (! \class_exists(<?=$this->hashIfImported(Phplrt\Parser\Parser::class)?>::class)) {
    throw new \LogicException('You need to set up the parser runtime (phplrt/parser) dependency using Composer');
}

if (! \class_exists(<?=$this->hashIfImported(Phplrt\Lexer\Lexer::class)?>::class)) {
    throw new \LogicException('You need to set up the lexer runtime (phplrt/lexer) dependency using Composer');
}

if (! \class_exists(<?=$this->hashIfImported(Phplrt\Source\File::class)?>::class)) {
    throw new \LogicException('You need to set up the source (phplrt/source) dependency using Composer');
}


/**
 * The main generated lexer class.
 *
 * @package <?=$fqn . "\n"?>
 * @generator \<?=static::class . "\n"?>
 */
final class <?=$this->classNameHash($class)?>Lexer extends <?=$this->hashIfImported(Phplrt\Lexer\Lexer::class)?>
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct([
<?php foreach ($this->getTokens() as $name => $value) : ?>
<?php if (\is_int($name)) : ?>
            <?=$this->value($name)?> => <?=$this->value($value)?>,
<?php else : ?>
            <?=$class?>::<?=$this->constantName($name)?> => <?=$this->value($value)?>,
<?php endif; ?>
<?php endforeach; ?>
        ], [
<?php foreach ($this->analyzer->skip as $name => $value) : ?>
            <?=$this->value($value)?>,
<?php endforeach; ?>
        ]);
    }
}

/**
 * The main AST Builder class.
 *
 * @package <?=$fqn . "\n"?>
 * @generator \<?=static::class . "\n"?>
 */
final class <?=$this->classNameHash($class)?>Builder extends <?=$this->hashIfImported(Phplrt\Lexer\Lexer::class)?> implements
    <?=$this->hashIfImported(Phplrt\Parser\Builder\BuilderInterface::class) . "\n"?>
{
    /** {@inheritDoc} */
    public function build(
        \Phplrt\Contracts\Source\ReadableInterface $file,
        \Phplrt\Contracts\Grammar\RuleInterface $rule,
        \Phplrt\Contracts\Lexer\TokenInterface $token,
        $state,
        $children
    ) {
        if (\is_int($state)) {
<?php if (\count($this->analyzer->reducers)) : ?>
            switch (true) {
<?php foreach ($this->analyzer->reducers as $id => $code) : ?>
<?php if (! \is_int($id)) { continue; } ?>
                case $state === <?=$this->value($id)?>:
                    return $this->state·<?=$id?>($file, $token->getOffset(), $children);
<?php endforeach; ?>
            }
<?php endif; ?>
        }

<?php if (\count($this->analyzer->reducers)) : ?>
        switch ($state) {
<?php foreach ($this->analyzer->reducers as $id => $code) : ?>
<?php if (\is_int($id)) { continue; } ?>
            case <?=$this->value($id)?>:
                return $this->state·<?=$id?>($file, $token->getOffset(), $children);
<?php endforeach; ?>
        }
<?php endif; ?>

        return null;
    }

<?php foreach ($this->analyzer->reducers as $id => $code) : ?>
    /**
     * @param \Phplrt\Contracts\Source\ReadableInterface $file
     * @param int $offset
     * @param array|mixed $children
     * @return mixed|void
     */
    private function state·<?=$id?>(\Phplrt\Contracts\Source\ReadableInterface $file, int $offset, $children)
    {
        <?=$code?>

    }

<?php endforeach; ?>
}

/**
 * The main generated parser class.
 *
 * @package <?=$fqn . "\n"?>
 * @generator \<?=static::class . "\n"?>
 */
class <?=$class?> extends <?=$this->hashIfImported(Phplrt\Parser\Parser::class)?>

{
<?php foreach ($this->getTokens() as $name => $value) :
    if (\is_int($name)) {
        continue;
    }
?>

    /** @var string */
    public const <?=$this->constantName($name)?> = <?=$this->value($name)?>;
<?php endforeach; ?>

    // --------------- Extra Constants -----------------------------------------
<?php foreach ($this->constants as $const) : ?>
    <?=$const?>

<?php endforeach; ?>

    // --------------- Extra Properties ----------------------------------------
<?php foreach ($this->properties as $property) : ?>
    <?=$property?>

<?php endforeach; ?>

    /**
     * <?=$class?> class constructor.
     */
    public function __construct()
    {
        parent::__construct(new <?=$this->classNameHash($class)?>Lexer(), $this->grammar(), [
            self::CONFIG_INITIAL_RULE => <?=$this->value($this->analyzer->initial)?>,
            self::CONFIG_AST_BUILDER  => new <?=$this->classNameHash($class)?>Builder()
        ]);
    }

    /**
     * @return array|\Phplrt\Contracts\Grammar\RuleInterface[]
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
