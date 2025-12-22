<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\L10n;

class XliffUpdater extends AbstractXliffProcessor
{
    private array $existingStrings = [];

    public function __construct(
        string $absoluteFilePath,
        bool $untranslatedEmpty = false,
        bool $markUntranslated = false
    ) {
        parent::__construct($absoluteFilePath, $untranslatedEmpty, $markUntranslated);

        // find existing strings
        preg_replace_callback('~<trans-unit[^>]+>~', function ($matches) {
            if (preg_match('~\s+id\s*=\s*["\']{1}([^"\']+)["\']{1}~', $matches[0], $idMatch) === 1) {
                $this->existingStrings[] = base64_decode($idMatch[1]);
            }

            return '';
        }, $this->existingBody);
    }

    public function update(array $strings, array $translations, bool $doWriteFile = false): void
    {
        $numUntranslated = $this->assemble($strings, $translations);

        if ($doWriteFile) {
            $this->flush();
        }

        if (L10nConfiguration::DO_DEBUG) { // @phpstan-ignore-line
            $updated = (count($strings) - $numUntranslated);
            $obsolete = count($this->existingStrings) - $updated;
            if ($obsolete < 0) {
                $obsolete = 0;
            }

            error_log(
                "\n======================================\n" .
                'Updated ' . $this->absoluteFilePath . ' with ' . $updated .
                ' existing strings and ' . $numUntranslated . ' new/untranslated strings, ' .
                $obsolete . ' were obsolete and thus removed.'
            );
            if ($obsolete > 0) {
                $removed = [];
                foreach ($this->existingStrings as $string) {
                    if (! in_array($string, $strings)) {
                        $removed[] = "'" . str_replace(
                            ["\n", "\r", "\t", "'"],
                            ['\n', '\r', '\t', '\\\''],
                            $string
                        ) . "'";
                    }
                }
                error_log(
                    'The following strings have been removed:' .
                    "\n\n" . implode("\n", $removed) . "\n\n"
                );
            }
            error_log("\n--------------------------------------\n");
        }
    }
}
