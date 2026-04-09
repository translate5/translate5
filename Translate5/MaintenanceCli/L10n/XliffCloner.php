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

use MittagQI\Translate5\Export\Exception;

/**
 * Clones a ZXLIFF to a new file exchanging all targets with changed target-strings
 * The sources will not be changed, sources not present in the given new translations will be removed
 * Also provides an API to change the source-strings and leave the targets fixed
 */
class XliffCloner extends AbstractXliffProcessor
{
    public function __construct(
        string $absoluteFilePath,
        private readonly string $absoluteSourcePath,
        bool $prefillUntranslated = false,
        bool $markUntranslated = false,
    ) {
        parent::__construct($absoluteFilePath, $prefillUntranslated, $markUntranslated);
    }

    /**
     * @param array<string, string> $translations
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    public function clone(array $translations, bool $doWriteFile = false): void
    {
        $this->numUntranslated = 0;
        $this->numStrings = 0;
        $untranslated = [];
        $strings = $this->loadStringsToClone();
        sort($strings, SORT_NATURAL);

        foreach ($strings as $string) {
            if (array_key_exists($string, $translations)) {
                $this->addTransUnit($string, $translations[$string]);
                if (empty($translations[$string])) {
                    $this->numUntranslated++;
                }
            } elseif ($this->prefillUntranslated || $this->markUntranslated) {
                // when marking, we add an own section for untranslated strings
                $untranslated[] = $string;
                $this->numUntranslated++;
            } else {
                $this->addTransUnit($string, '');
                $this->numUntranslated++;
            }
            $this->numStrings = 0;
        }

        if (count($untranslated) > 0) {
            $this->body .= "\n" . '            ' . L10nConfiguration::UNTRANSLATED_SECTION;

            foreach ($untranslated as $string) {
                $this->addTransUnit($string, '');
            }
        }

        if ($doWriteFile) {
            $this->flush();
        }

        if (L10nConfiguration::DO_DEBUG) { // @phpstan-ignore-line
            error_log(
                "\n======================================\n" .
                'Clones ' . $this->absoluteFilePath . ' with ' . count($strings) .
                ' strings, for ' . count($untranslated) . ' strings no translation was found' .
                "\n--------------------------------------\n"
            );
        }
    }

    /**
     * @param array<string, string> $sourceMap
     * @throws Exception
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    public function exchange(array $sourceMap, array $exchangedStrings, bool $doWriteFile = false): void
    {
        $translations = [];

        foreach ($this->loadTranslations() as $source => $target) {
            if (array_key_exists($source, $exchangedStrings)) {
                if (array_key_exists($source, $sourceMap)) {
                    $translations[$sourceMap[$source]] = $target;
                } else {
                    throw new Exception(
                        'Source not found in sourceMap, file “' . $this->absoluteFilePath . '”, source: “' . $source . '”'
                    );
                }
            } else {
                // if not exchanged in the code, the translation stays as it is ...
                $translations[$source] = $target;
                error_log('Localized source-string was not exchanged: "' . $source . '"');
            }
        }

        $this->assemble(array_keys($translations), $translations);

        if ($doWriteFile) {
            $this->flush();
        }

        if (L10nConfiguration::DO_DEBUG) { // @phpstan-ignore-line
            error_log(
                "\n======================================\n" .
                'Exchanged sources in ' . $this->absoluteFilePath . ' with ' . count($translations) .
                ' strings and ' . $this->numUntranslated . ' untranslated strings' .
                "\n--------------------------------------\n"
            );
        }
    }

    /**
     * @return array<int, string>
     * @throws \ZfExtended_Exception
     */
    private function loadStringsToClone(): array
    {
        return array_keys($this->loadTranslations());
    }

    /**
     * @return array<string, string>
     * @throws \ZfExtended_Exception
     */
    private function loadTranslations(): array
    {
        $parser = new XliffParser($this->absoluteSourcePath);

        return $parser->getTranslations();
    }
}
