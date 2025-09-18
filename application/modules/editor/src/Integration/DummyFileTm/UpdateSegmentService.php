<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Integration\DummyFileTm;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as Segment;
use MittagQI\Translate5\Integration\Contract\UpdateSegmentInterface;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use Zend_Config;
use Zend_Db_Adapter_Abstract;

class UpdateSegmentService implements UpdateSegmentInterface
{
    public function __construct(
        private readonly SegmentUpdateDtoFactory $dtoFactory,
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    public static function create(): UpdateSegmentInterface
    {
        return new self(
            SegmentUpdateDtoFactory::create(),
            \Zend_Db_Table::getDefaultAdapter(),
        );
    }

    public function supports(LanguageResource $languageResource): bool
    {
        return \editor_Services_DummyFileTm_Service::SERVICE_NAME === $languageResource->getServiceName();
    }

    public function update(
        LanguageResource $languageResource,
        Segment $segment,
        Zend_Config $config,
        ?UpdateOptions $updateOptions = null,
    ): void {

        if (null === $updateOptions) {
            $updateOptions = new UpdateOptions(
                useSegmentTimestamp: true,
                saveToDisk: false,
                saveDifferentTargetsForSameSource: false,
                recheckOnUpdate: false,
            );
        }

        $dto = $this->dtoFactory->getUpdateDTO($languageResource, $segment, $config, $updateOptions);

        $this->updateWithDTO(
            $languageResource,
            $segment,
            $dto,
            $config,
            $updateOptions,
        );
    }

    public function updateWithDTO(
        LanguageResource $languageResource,
        Segment $segment,
        UpdateSegmentDTO $dto,
        Zend_Config $config,
        UpdateOptions $updateOptions,
    ): void {
        $s = $this->db->select()
            ->from(\editor_Services_DummyFileTm_Db::NAME, ['id'])
            ->where('source = ?', $dto->source);

        if (! $updateOptions->saveToDisk) {
            $s->where('internalFuzzy = ?', 1);
        }

        $id = $this->db->fetchOne($s);

        if ($id) {
            $this->db->update(
                \editor_Services_DummyFileTm_Db::NAME,
                [
                    'target' => $dto->target,
                ],
                [
                    'id => ?' => $id
                ],
            );
        } else {
            $this->db->insert(
                \editor_Services_DummyFileTm_Db::NAME,
                [
                    'languageResourceId' => $languageResource->getId(),
                    'mid' => $segment->getMid(),
                    'internalFuzzy' => (int) (! $updateOptions->saveToDisk),
                    'source' => $dto->source,
                    'target' => $dto->target,
                ],
            );
        }
    }
}