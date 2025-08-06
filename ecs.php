<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveUselessDefaultCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Translate5\PHPCodeSniffer\EnumPascalCaseFixer;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/Translate5',
        __DIR__ . '/application',
        __DIR__ . '/library',
    ])

    // add a single rule
    ->withRules(rules: [
        NoUnusedImportsFixer::class,
        ArrayIndentationFixer::class,
        BlankLineBeforeStatementFixer::class,
        EnumPascalCaseFixer::class,
    ])

    // add sets - group of rules
    ->withPreparedSets(
        psr12: true,
        arrays: true,
        comments: true,
        docblocks: true,
        spaces: true,
        namespaces: true,
    )
    ->withSkip([
        BlankLineAfterOpeningTagFixer::class,
        RemoveUselessDefaultCommentFixer::class,
    ])
;
