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

use MittagQI\Translate5\Task\Import\Alignment\Source;

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 *
 * /**
 * Stellt Methoden zur Verarbeitung der vom Parser ermittelteten Segment Daten bereit
 * speichert die ermittelten Segment Daten in die Relais Spalte des entsprechenden Segments
 */
class editor_Models_Import_SegmentProcessor_Relais extends editor_Models_Import_SegmentProcessor
{
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;

    /**
     * Relais Field
     * @var Zend_Db_Table_Row_Abstract
     */
    protected $relaisField;

    protected Source $alignment;

    /**
     * @param editor_Models_Task $task
     * @param editor_Models_SegmentFieldManager $sfm receive the already inited sfm
     */
    public function __construct(editor_Models_Task $task, editor_Models_SegmentFieldManager $sfm)
    {
        parent::__construct($task);
        //relais is forced non editable (last parameter)
        $relais = $sfm->addField($sfm::LABEL_RELAIS, editor_Models_SegmentField::TYPE_RELAIS, false);
        $this->relaisField = $sfm->getByName($relais);
        $this->sfm = $sfm;

        $this->alignment = ZfExtended_Factory::get(Source::class);
    }

    /**
     * Verarbeitet ein einzelnes Segment und gibt die ermittelte SegmentId zurück
     * @return integer|false MUST return the segmentId or false
     */
    public function process(editor_Models_Import_FileParser $parser): bool|int
    {
        $segment = $this->alignment->findSegment($parser);
        if (is_null($segment)) {
            return false;
        }
        try {
            $data = $parser->getFieldContents();
            $target = $this->sfm->getFirstTargetName();
            $segment->addFieldContent($this->relaisField, $this->fileId, $parser->getMid(), $data[$target]);
            return $segment->getId();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->alignment->addError(new \MittagQI\Translate5\Task\Import\Alignment\Error(
                'E1022',
                'Source-content of processing file "{fileName}" is identical with
             source of original file, but still original segment not found in the database.﻿ See Details.',
                [$e->getMessage()]
            ));
            return false;
        }
    }

    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     * @throws Zend_Exception
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser)
    {
        $this->saveFieldWidth($parser);
        $this->logInfo();
    }

    /***
     * @return void
     * @throws Zend_Exception
     */
    private function logInfo(): void
    {
        $errors = $this->alignment->getErrors();
        if (empty($errors)) {
            return;
        }

        $logger = Zend_Registry::get('logger');
        /* @var ZfExtended_Logger $logger */

        foreach ($errors as $error){
            /* @var \MittagQI\Translate5\Task\Import\Alignment\Error $error */

            $logger->warn($error->getCode(),$error->getMessage(),
                array_merge(
                    [
                        'task' => $this->task,
                        'fileName' => $this->fileName,
                    ],
                    $error->getExtra()
                )
            );
        }
    }

    /**
     * (non-PHPdoc)
     * @throws ZfExtended_Exception
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId): void
    {
        $this->calculateFieldWidth($parser, [$this->sfm->getFirstTargetName() => 'relais']);
    }
}