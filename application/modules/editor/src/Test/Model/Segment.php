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

namespace MittagQI\Translate5\Test\Model;

/**
 * defines the compared & sanitized data for a segment
 */
class Segment extends AbstractModel
{
    //FIXME make a black list instead a whitelist here!!!
    protected array $whitelist = [
        'segmentNrInTask',
        'mid',
        'userGuid',
        'userName',
        'editable',
        'pretrans',
        'matchRate',
        'stateId',
        'autoStateId',
        'fileOrder',
        'workflowStepNr',
        'workflowStep',
        'isRepeated',
        'sourceMd5',
        'sourceToSort',
        'targetMd5',
        'targetToSort',
        'targetEditToSort',
        'isWatched',
        'segmentUserAssocId',
        'matchRateType',
        'isFirstofFile'
    ];

    protected array $sanitized = [
        'source' => 'fieldtext',
        'sourceEdit' => 'fieldtext',
        'target' => 'fieldtext',
        'targetEdit' => 'fieldtext',
        'comments' => 'comment',
        'metaCache' => 'metacache'
    ];

    protected string $messageField = 'segmentNrInTask';
}
