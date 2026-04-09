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
 * Imports new targets for a ZXLIFF from an exported reviewed task.
 * Will renew all targets there were present in the passed import ZXLIFF
 */
class XliffImporter extends AbstractXliffProcessor
{
    /**
     * @throws \MittagQI\ZfExtended\FileWriteException
     * @throws \ZfExtended_Exception
     */
    public function import(string $importPath): void
    {
        $parser = new XliffParser($this->absoluteFilePath);
        $translations = $parser->getTranslations();
        $strings = array_keys($translations);
        $numOverridden = 0;
        $parser = new XliffParser($importPath);
        foreach ($parser->getTranslations() as $string => $newTranslation) {
            if (array_key_exists($string, $translations) && $translations[$string] !== $newTranslation) {
                $translations[$string] = $newTranslation;
                $numOverridden++;
            }
        }
        $this->assemble($strings, $translations);
        $this->flush();

        if (L10nConfiguration::DO_DEBUG) { // @phpstan-ignore-line
            error_log(
                "\n======================================\n" .
                'Imported ' . $this->absoluteFilePath . ', ' . $numOverridden . ' of' .
                count($strings) . ' have been updated.' .
                "\n--------------------------------------\n"
            );
        }
    }
}
