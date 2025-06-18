<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\CotiHotfolder\DTO;

use DateTime;
use editor_Models_Languages;
use MittagQI\Translate5\Plugins\CotiHotfolder\Exception\InvalidInstructionsXmlFileException;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\CotiLogEntry;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\CotiLogger;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\T5Logger;
use SimpleXMLElement;
use ZfExtended_Models_Entity_NotFoundException;

class InstructionsDTO
{
    public readonly int $sourceLang;

    public readonly ?ProjectDTO $project;

    public readonly bool $archive;

    public array $targetLang = [];

    public readonly int $logLevel;

    public ProjectFilesDTO $projectFiles;

    private const DEFAULT_LOG_LEVEL = 2;

    public function __construct(SimpleXMLElement $instructions, string $projectDir, T5Logger $logger, CotiLogger $cotiLogger)
    {
        $errors = $this->validateInstructions($instructions, $cotiLogger);
        if (! empty($errors)) {
            $cotiLogger->log(CotiLogEntry::CotiFileInvalid, $projectDir, __FILE__);

            throw new InvalidInstructionsXmlFileException($errors);
        }

        // validated already
        $sourceLang = (string) $instructions->project->translation->attributes()['source-language'];
        $this->sourceLang = $this->getT5LanguageIdFromRfc5646($sourceLang);

        try {
            $targetLang = (string) $instructions->project->translation->attributes()['target-language'];
            $this->targetLang[] = $this->getT5LanguageIdFromRfc5646($targetLang);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $targetLang = $targetLang ?? ''; // phpstan complains otherwise
            $cotiLogger->log(CotiLogEntry::UnknownLanguageError, $targetLang, __FILE__);
            $logger->languageNotFound($targetLang);
        }

        $this->projectFiles = new ProjectFilesDTO($instructions);

        $this->project = new ProjectDTO(
            property_exists($instructions, 'project') ? $instructions->project : null,
            $projectDir
        );

        $this->archive = property_exists($instructions, 'archive') && (string) $instructions->archive === 'yes';
        if (property_exists($instructions, 'loglevel')) {
            $logLevel = (int) $instructions->loglevel;
            $this->logLevel = ($logLevel >= 0 && $logLevel <= 5) ? $logLevel : self::DEFAULT_LOG_LEVEL;
        } else {
            $this->logLevel = self::DEFAULT_LOG_LEVEL;
        }
    }

    private function getT5LanguageIdFromRfc5646(string $lang): int
    {
        /** @phpstan-ignore-next-line */
        return (int) (new editor_Models_Languages())->getLangIdByRfc5646($lang);
    }

    private function validateInstructions(SimpleXMLElement $instructions, CotiLogger $cotiLogger): array
    {
        $errors = [];
        $sourceLang = $targetLang = '';
        if (property_exists($instructions, 'project')
            && property_exists($instructions->project, 'translation')
        ) {
            $attr = $instructions->project->translation->attributes();
            if (property_exists($attr, 'source-language')) {
                $sourceLang = (string) $attr->{'source-language'};
            }
            if (property_exists($attr, 'target-language')) {
                $targetLang = (string) $attr->{'target-language'};
            }
        }

        if (! empty($sourceLang)) {
            try {
                $this->getT5LanguageIdFromRfc5646($sourceLang);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $errors[] = 'source-language not found in DB: ' . $sourceLang;
                $cotiLogger->log(CotiLogEntry::UnknownLanguageFatal, $sourceLang, __FILE__);
            }
        } else {
            $errors[] = 'source-language not provided';
        }

        if (empty($targetLang)) {
            $errors[] = 'target-language not provided';
        }

        if (property_exists($instructions, 'project')) {
            if (property_exists($instructions->project->attributes(), 'due-date')) {
                try {
                    new DateTime((string) $instructions->project->attributes()->{'due-date'});
                } catch (\Throwable) {
                    $errors[] = 'Invalid project due-date format';
                }
            }
        }

        return $errors;
    }
}
