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

class XliffCloner extends AbstractXliffProcessor
{
    public function __construct(
        string $absoluteFilePath,
        private readonly string $absoluteSourcePath,
        bool $untranslatedEmpty = false,
        bool $markUntranslated = false
    ) {
        parent::__construct($absoluteFilePath, $untranslatedEmpty, $markUntranslated);
    }

    public function clone(array $translations, bool $doWriteFile = false): void
    {
        $untranslated = [];
        $strings = $this->loadStringsToClone();
        sort($strings, SORT_NATURAL);

        foreach ($strings as $string) {
            if (array_key_exists($string, $translations)) {
                $this->addTransUnit($string, $translations[$string]);
            } else {
                $untranslated[] = $string;
            }
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

    private function loadStringsToClone(): array
    {
        $parser = new XliffParser($this->absoluteSourcePath);

        return array_keys($parser->getTranslations());
    }
}
