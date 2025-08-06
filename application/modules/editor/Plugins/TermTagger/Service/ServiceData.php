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

namespace MittagQI\Translate5\Plugins\TermTagger\Service;

use editor_Models_Languages;
use editor_Models_Task;
use stdClass;
use Zend_Registry;
use ZfExtended_Factory;

/**
 * Temporary model used for transforming segnments to usable data for the termtagger
 */
class ServiceData
{
    /**
     * TBX file / hash
     * @var string
     */
    public $tbxFile = null;

    /**
     * @var string
     */
    public $sourceLang = null;

    /**
     * @var string
     */
    public $targetLang = null;

    /**
     * {
     *    "id": "123",
     *    "field": "target",
     *    "source": "SOURCE TEXT",
     *    "target": "TARGET TEXT"
     * },
     * { ... MORE SEGMENTS ... }
     * ],
     * @var array
     */
    public $segments = null;

    public int $debug = 0;

    public int $fuzzy = 0;

    public int $stemmed = 0;

    public int $fuzzyPercent = 0;

    public int $maxWordLengthSearch = 0;

    public int $minFuzzyStartLength = 0;

    public int $minFuzzyStringLength = 0;

    public int $targetStringMatch = 0;

    public string $task = '';

    /**
     * If $task is sumbitted, ServerCommunication is initialized with all required fields,
     * so after that all there has to be done is addSegment()
     */
    public function __construct(editor_Models_Task $task)
    {
        $config = Zend_Registry::get('config');
        $taggerConfig = $config->runtimeOptions->termTagger;
        $this->debug = (int) $taggerConfig->debug;
        $this->fuzzy = (int) $taggerConfig->fuzzy;
        $this->stemmed = (int) $taggerConfig->stemmed;
        $this->fuzzyPercent = (int) $taggerConfig->fuzzyPercent;
        $this->maxWordLengthSearch = (int) $taggerConfig->maxWordLengthSearch;
        $this->minFuzzyStartLength = (int) $taggerConfig->minFuzzyStartLength;
        $this->minFuzzyStringLength = (int) $taggerConfig->minFuzzyStringLength;

        $this->targetStringMatch = 0;

        $customerConfig = $task->getConfig();
        $customerConfig = $customerConfig->runtimeOptions->termTagger;

        $this->tbxFile = $task->meta()->getTbxHash();

        $langModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $langModel->load($task->getSourceLang());
        $this->sourceLang = $langModel->getRfc5646();
        $langModel->load($task->getTargetLang());
        $this->targetLang = $langModel->getRfc5646();
        $this->targetStringMatch = (int) in_array($this->targetLang, $customerConfig->targetStringMatch->toArray(), true);

        $this->task = $task->getTaskGuid();
    }

    /**
     * Adds a segment to the server-communication.
     *
     * @param string $id
     * @param string $field
     * @param string $source
     * @param string $target
     */
    public function addSegment($id, $field, $source, $target)
    {
        if ($this->segments == null) {
            $this->segments = [];
        }
        $segment = new stdClass();
        $segment->id = (string) $id;
        $segment->field = $field;
        $segment->source = $source;
        $segment->target = $target;

        $this->segments[] = $segment;
    }
}
