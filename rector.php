<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__ . '/libs/contracts/*/src',
        __DIR__ . '/libs/*/src'
    ]);

    $config->sets([
        LevelSetList::UP_TO_PHP_74,
        SetList::TYPE_DECLARATION,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
    ]);

    $config->skip([
        //
        // Do not replace classic properties to promoted eq. These are
        // completely different statements.
        //
        ClassPropertyAssignToConstructorPromotionRector::class,

        //
        // This rector can break the Doctrine that replaces implementations
        // with proxies, like:
        //  - private Collection $relation;          // OK This can be replaced with a proxy
        //  + private readonly Collection $relation; // FAIL
        //
        ReadOnlyPropertyRector::class,

        // Totally pointless "improvements"
        CatchExceptionNameMatchingTypeRector::class,
        SplitDoubleAssignRector::class,
        FinalizePublicClassConstantRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        ReturnBinaryOrToEarlyReturnRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
        CallableThisArrayToAnonymousFunctionRector::class,
        ClosureToArrowFunctionRector::class,
        RemoveNonExistingVarAnnotationRector::class,
    ]);
};
