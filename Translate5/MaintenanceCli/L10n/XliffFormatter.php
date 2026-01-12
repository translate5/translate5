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

/**
 * Formats an existing localization-XLIFF: Sorts the keys naturally & normalizes the whitespace
 * Provides functionality to add/remove strings
 */
class XliffFormatter extends AbstractXliffProcessor
{
    public function format(): void
    {
        $this->process([], [], [], 'Formatted');
    }

    public function add(string $string, string $primaryTranslation = ''): int
    {
        return $this->process([
            $string => $primaryTranslation,
        ], [], [], 'Added');
    }

    public function remove(string $string): int
    {
        return $this->process([], [$string], [], 'Removed');
    }

    public function replace(string $string, string $replacement): int
    {
        return $this->process([], [], [$string, $replacement], 'Replaced');
    }

    private function process(array $added, array $removed, array $replaced, string $process): int
    {
        $parser = new XliffParser($this->absoluteFilePath);
        $translations = $parser->getTranslations();
        $strings = array_keys($translations);
        $numStrings = count($strings);
        $numAdded = $numRemoved = $numReplaced = 0;

        if (! empty($added)) {
            $translations = array_merge($translations, $added);
            $strings = array_merge($strings, array_keys($added));
            $numAdded = count($added);
            $numStrings += $numAdded;
        }

        if (! empty($removed)) {
            $strings = array_values(array_diff($strings, $removed));
            $numRemoved = $numStrings - count($strings);
            $numStrings = count($strings);
        }

        if (! empty($replaced) && array_key_exists($replaced[0], $translations)) {
            $translations[$replaced[1]] = $translations[$replaced[0]];
            unset($translations[$replaced[0]]);
            $strings = array_keys($translations);
            $numReplaced = 1;
        }

        $numUntranslated = $this->assemble($strings, $translations);
        $this->flush();

        if (L10nConfiguration::DO_DEBUG) { // @phpstan-ignore-line
            $add = '';
            if ($numAdded > 0) {
                $add = ', added ' . $numAdded . ' strings';
            }
            if ($numRemoved > 0) {
                $add = ', removed ' . $numRemoved . ' strings';
            }
            if ($numReplaced > 0) {
                $add = ', replaced ' . $numReplaced . ' strings';
            }
            if ($numUntranslated > 0) {
                $add = ', ' . $numUntranslated . ' strings are untranslated';
            }

            error_log(
                "\n======================================\n" .
                $process . ' ' . $this->absoluteFilePath . ', contains ' . $numStrings . ' translations' . $add . '.' .
                "\n--------------------------------------\n"
            );
        }

        return $numAdded + $numRemoved + $numReplaced;
    }
}
