<?php
/** @var $this \Phplrt\Compiler\Generator\ZendGenerator */

require __DIR__ . '/header.tpl.php';
?>
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * The main AST Builder class.
 *
 * @package <?=$fqn . "\n"?>
 * @generator \<?=static::class . "\n"?>
 */
final class <?=$class?> implements <?=$this->hashIfImported(Phplrt\Parser\Builder\BuilderInterface::class) . "\n"?>
{
    /**
     * @var \Closure|null
     */
    private $onError;

    /**
     * @var \Closure|null
     */
    private $after;

    /**
     * JsonBuilder constructor.
     *
     * @param \Closure|null $onError
     * @param \Closure|null $after
     */
    public function __construct(\Closure $onError = null, \Closure $after = null)
    {
        $this->after = $after ?? static function ($file, $rule, $token, $state, $children) {
            return $children;
        };

        $this->onError = $onError ?? static function (\Throwable $error): \Throwable {
            return $error;
        };
    }

    /**
     * {@inheritDoc}
     */
    public function build(ReadableInterface $file, RuleInterface $rule, TokenInterface $token, $state, $children)
    {
        try {
            $result = $this->reduce($file, $rule, $token, $state, $children);

            return $result ?? ($this->after)($file, $rule, $token, $state, $children);
        } catch (\Throwable $error) {
            throw (($this->onError)($error, $file, $rule, $token, $state, $children)) ?? $error;
        }
    }

    /**
     * @see <?=$class?>::build()
     */
    private function reduce(ReadableInterface $file, RuleInterface $rule, TokenInterface $token, $state, $children)
    {
<?php if (\count(\array_filter(\array_keys($this->analyzer->reducers), '\\is_int'))): ?>
        if (\is_int($state)) {
<?php if (\count($this->analyzer->reducers)) : ?>
            switch (true) {
<?php foreach ($this->analyzer->reducers as $id => $code) : ?>
<?php if (! \is_int($id)) { continue; } ?>
                case $state === <?=$this->value($id)?>:
<?=$this->prefixed(4, $code) . "\n"?>
                    break;
<?php endforeach; ?>
            }
<?php endif; ?>
        }
<?php endif; ?>
<?php if (\count($this->analyzer->reducers)) : ?>
        switch ($state) {
<?php foreach ($this->analyzer->reducers as $id => $code) : ?>
<?php if (\is_int($id)) { continue; } ?>
            case <?=$this->value($id)?>:
<?=$this->prefixed(3, $code) . "\n"?>
                break;
<?php endforeach; ?>
        }
<?php endif; ?>

        return null;
    }
}
