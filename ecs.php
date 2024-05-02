<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

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
;
