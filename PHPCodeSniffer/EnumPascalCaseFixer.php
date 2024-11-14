<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace Translate5\PHPCodeSniffer;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Utils;

final class EnumPascalCaseFixer implements FixerInterface
{
    public function isCandidate(Tokens $tokens): bool
    {
        // Check if file contains an enum definition
        return $tokens->isTokenKindFound(T_ENUM);
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Ensures enum constants are in PascalCase (UpperCamelCase).',
            []
        );
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $enumFound = false;
        for ($index = 0; $index < $tokens->count(); $index++) {
            $token = $tokens[$index];

            // Detect if we're inside an enum
            if ($token->isGivenKind(T_ENUM)) {
                $enumFound = true;
            }

            if ($enumFound && $token->isGivenKind(T_STRING)) {
                $constantName = $token->getContent();

                // If the string is uppercase with underscores, it's likely a constant
                if ($this->isEnumConstant($constantName)) {
                    $pascalCaseName = $this->convertToPascalCase($constantName);
                    $tokens[$index] = new Token([T_STRING, $pascalCaseName]);
                }
            }

            // Exit the enum block
            if ($token->equals('}')) {
                $enumFound = false;
            }
        }
    }

    public function getName(): string
    {
        $nameParts = explode('\\', EnumPascalCaseFixer::class);
        $name = substr(end($nameParts), 0, -strlen('Fixer'));

        return Utils::camelCaseToUnderscore($name);
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }

    private function isEnumConstant(string $name): bool
    {
        // Checks if the name is in the typical constant format (all caps with underscores)
        return (bool) preg_match('/^[A-Z0-9_]+$/', $name);
    }

    private function convertToPascalCase(string $constantName): string
    {
        // Split the constant name by underscores, capitalize each part, and concatenate
        return str_replace('_', '', ucwords(strtolower($constantName), '_'));
    }
}
