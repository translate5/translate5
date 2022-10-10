<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Task\Import\SegmentProcessor;

use editor_Models_File;
use editor_Models_Import_Configuration;
use editor_Models_Import_FileParser;
use editor_Models_Import_SegmentProcessor;
use editor_Models_Languages;
use editor_Models_Segment_WordCount;
use editor_Models_Task;
use MittagQI\Translate5\Segment\CharacterCount;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Factory;

/***
 *
 */
class Reimport extends editor_Models_Import_SegmentProcessor
{

    /**
     * @var Zend_Config
     */
    protected Zend_Config $taskConf;

    /**
     * @var editor_Models_Import_Configuration
     */
    protected editor_Models_Import_Configuration $importConfig;

    /**
     * @var editor_Models_Segment_WordCount
     */
    protected editor_Models_Segment_WordCount $wordCount;

    /***
     * @var CharacterCount
     */
    protected CharacterCount $characterCount;

    /**
     * @var int
     */
    protected int $segmentNrInTask = 0;

    /**
     * @param editor_Models_Task $task
     * @param editor_Models_Import_Configuration $config
     */
    public function __construct(editor_Models_Task $task, editor_Models_Import_Configuration $config)
    {
        parent::__construct($task);
        $this->importConfig = $config;
        $this->db = Zend_Registry::get('db');
        $this->taskConf = $this->task->getConfig();

        //init word counter
        $langModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var editor_Models_Languages $langModel */
        $langModel->load($task->getSourceLang());

        $this->wordCount = ZfExtended_Factory::get('editor_Models_Segment_WordCount', [
            $langModel->getRfc5646()
        ]);
        $this->characterCount = ZfExtended_Factory::get(CharacterCount::class);
    }

    public function process(editor_Models_Import_FileParser $parser)
    {

        $segmentId = 1;

        return $segmentId;
    }


    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser)
    {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($this->fileId);
        $file->saveSkeletonToDisk($parser->getSkeletonFile(), $this->task);

        $this->saveFieldWidth($parser);
    }

    /***
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId)
    {
        $this->calculateFieldWidth($parser);
    }
}