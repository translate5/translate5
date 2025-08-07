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

namespace MittagQI\Translate5\LanguageResource\Adapter;

use editor_Models_Segment as SegmentModel;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;

interface UpdatableAdapterInterface
{
    public const RECHECK_ON_UPDATE = 'recheckOnUpdate';

    public const USE_SEGMENT_TIMESTAMP = 'useSegmentTimestamp';

    public const SAVE_TO_DISK = 'saveToDisk';

    public const SAVE_DIFFERENT_TARGETS_FOR_SAME_SOURCE = 'saveDifferentTargetsForSameSource';

    public function update(SegmentModel $segment, ?UpdateOptions $updateOptions = null): void;

    public function checkUpdatedSegment(SegmentModel $segment): void;

    public function getUpdateDTO(SegmentModel $segment, UpdateOptions $updateOptions): UpdateSegmentDTO;

    public function updateWithDTO(UpdateSegmentDTO $dto, UpdateOptions $updateOptions, SegmentModel $segment): void;
}
