<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Overview;

use editor_Models_Segment_AutoStates;
use editor_Models_Segment_Utility;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\Segment\SegmentFieldManagerFactory;
use Zend_Db_Table;
use ZfExtended_Zendoverwrites_Translate;

class SegmentDataProviderFactory
{
    public function __construct(
        private readonly SegmentFieldManagerFactory $segmentFieldManagerFactory,
        private readonly ZfExtended_Zendoverwrites_Translate $translate,
        private readonly SegmentRepository $segmentRepository,
        private readonly editor_Models_Segment_AutoStates $segmentAutoStates,
        private readonly editor_Models_Segment_Utility $segmentUtility,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(?ZfExtended_Zendoverwrites_Translate $translate = null): self
    {
        return new self(
            new SegmentFieldManagerFactory(),
            $translate ?? ZfExtended_Zendoverwrites_Translate::getInstance(),
            new SegmentRepository(Zend_Db_Table::getDefaultAdapter()),
            new editor_Models_Segment_AutoStates(),
            new editor_Models_Segment_Utility(),
        );
    }

    /**
     * @param iterable<callable> $segmentFormatters
     */
    public function getProvider(iterable $segmentFormatters): SegmentDataProvider
    {
        return new SegmentDataProvider(
            $this->segmentFieldManagerFactory,
            $this->translate,
            $this->segmentRepository,
            $this->segmentAutoStates,
            $this->segmentUtility,
            $segmentFormatters,
        );
    }
}
