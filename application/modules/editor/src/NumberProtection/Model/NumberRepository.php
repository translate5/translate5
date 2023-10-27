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

namespace MittagQI\Translate5\NumberProtection\Model;

use editor_Models_Languages;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class NumberRepository
{
    /**
     * @return iterable<NumberFormatDto>
     */
    public function getAll(editor_Models_Languages $sourceLang): iterable
    {
        $dbMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbNumberRecognition = ZfExtended_Factory::get(NumberRecognition::class)->db;
        $numberRecognitionTable = $dbNumberRecognition->info($dbNumberRecognition::NAME);

        $ids = [$sourceLang->getId()];

        if ($sourceLang->getMajorRfc5646() !== $sourceLang->getRfc5646()) {
            $major = ZfExtended_Factory::get(editor_Models_Languages::class);
            $major->loadByRfc5646($sourceLang->getMajorRfc5646());

            $ids[] = $major->getId();
        }

        $select = $dbMapping->select()
            ->setIntegrityCheck(false)
            ->from(['mapping' => $dbMapping->info($dbMapping::NAME)], [])
            ->join(
                ['recognition' => $numberRecognitionTable],
                'recognition.id = mapping.numberRecognitionId',
                ['recognition.*']
            )
            ->where('mapping.languageId IN (?)', $ids)
            ->where('recognition.enabled = true')
            ->order('priority desc')
        ;

        foreach ($dbMapping->fetchAll($select) as $formatData) {
            yield NumberFormatDto::fromRow($formatData);
        }
    }

    public function getNumberRecognitionForOutputMappingForm(): array
    {
        $dbMapping = ZfExtended_Factory::get(InputMapping::class)->db;
        $dbNumberRecognition = ZfExtended_Factory::get(NumberRecognition::class)->db;
        $numberRecognitionTable = $dbNumberRecognition->info($dbNumberRecognition::NAME);

        $select = $dbMapping->select()
            ->setIntegrityCheck(false)
            ->from(['mapping' => $dbMapping->info($dbMapping::NAME)], [])
            ->join(
                ['recognition' => $numberRecognitionTable],
                'recognition.id = mapping.numberRecognitionId',
                ['recognition.id', 'recognition.type', 'recognition.name']
            )
            ->order('type asc')
        ;

        return $dbMapping->fetchAll($select)->toArray();
    }

    public function getNumberRecognition(string $type, string $name): NumberRecognition
    {
        $numberRecognition = ZfExtended_Factory::get(NumberRecognition::class);
        $numberRecognition->loadBy($type, $name);

        return $numberRecognition;
    }

    public function findOutputFormat(
        editor_Models_Languages $targetLang,
        string $type,
        string $name
    ): ?string {
        $numberRecognition = ZfExtended_Factory::get(NumberRecognition::class);
        try {
            $numberRecognition->loadBy($type, $name);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }

        $mapping = $this->findOutputMappingBy((int) $targetLang->getId(), (int) $numberRecognition->getId());
        // if not found - try to look by major lang
        if (null === $mapping) {
            $major = ZfExtended_Factory::get(editor_Models_Languages::class);
            $major->loadByRfc5646($targetLang->getMajorRfc5646());

            $mapping = $this->findOutputMappingBy((int) $major->getId(), (int) $numberRecognition->getId());
        }

        return $mapping?->getFormat();
    }

    public function findOutputMappingBy(int $langId, int $numberRecognitionId): ?OutputMapping
    {
        $mapping = ZfExtended_Factory::get(OutputMapping::class);
        try {
            $mapping->loadBy($langId, $numberRecognitionId);

            return $mapping;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }
}