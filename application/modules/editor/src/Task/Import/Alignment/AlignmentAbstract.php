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

namespace MittagQI\Translate5\Task\Import\Alignment;

use editor_Models_Import_FileParser;
use editor_Models_Segment;
use ZfExtended_Factory;

abstract class AlignmentAbstract
{
    private array $errors = [];

    private editor_Models_Segment $segment;

    public function __construct()
    {
        $this->initSegment();
    }

    abstract public function findSegment(editor_Models_Import_FileParser $parser): ?editor_Models_Segment;

    public function addError(Error $error): void
    {
        if (! isset($this->errors[$error->getCode()])) {
            $this->errors[$error->getCode()] = new Error(
                $error->getCode(),
                $error->getMessage(),
                []
            );
        }
        $this->errors[$error->getCode()]->addExtra($error->getExtra());
    }

    /***
     * @param string $taskGuid
     * @return void
     */
    public function initSegment(string $taskGuid = ''): void
    {
        $this->segment = ZfExtended_Factory::get(editor_Models_Segment::class);
        if (! empty($taskGuid)) {
            $this->segment->init(
                [
                    'taskGuid' => $taskGuid,
                ]
            );
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSegment(): editor_Models_Segment
    {
        return $this->segment;
    }
}
