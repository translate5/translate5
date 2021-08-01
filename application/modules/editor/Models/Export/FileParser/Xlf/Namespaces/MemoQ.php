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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * XLF Fileparser Add On to parse MemoQ XLF specific stuff
 */
class editor_Models_Export_FileParser_Xlf_Namespaces_MemoQ extends editor_Models_Export_FileParser_Xlf_Namespaces_Abstract {
    protected $currentCommentsKey = null;
    protected $currentComments = [];

    /**
     * xmlWriter instance, reusable instance
     */
    protected static $xmlWriter;

    public function __construct() {
        if(empty(self::$xmlWriter)) {
            self::$xmlWriter = new XmlWriter();
            self::$xmlWriter->openMemory();
        }
    }

    /**
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser_Xlf_Namespaces_Abstract::registerParserHandler()
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        //a little bit hackish but the easiest way to get the task
        $task = func_get_arg(1);
        /* @var $task editor_Models_Task */

        $config =$task->getConfig();
        if(! $config->runtimeOptions->editor->export->exportComments) {
            //currently only the comment export feature is implemented in the memoQ XLF export namespace,
            // so if exporting comments is disabled we disable just the whole function
            return;
        }

        $xmlparser->registerElement('trans-unit mq:comments', null, function($tag, $key, $opener) use ($xmlparser){
            $this->currentCommentsKey = $key;
        });

        //must use another selector as in the Xlf Export itself. On using the same selector, the later one overwrites the first one
        $xmlparser->registerElement('trans-unit lekTargetSeg', null, function($tag, $key, $opener) use ($xmlparser, $task){
            $this->loadComments($opener['attributes'], $xmlparser, $task);
        });

        $xmlparser->registerElement('xliff trans-unit', null, function($tag, $key, $opener) use ($xmlparser){
            $commentString = $this->processComments();

            //if there is no <mq:comments> tag in the trans-unit we must create it, otherwise we have to reuse it
            if(empty($this->currentCommentsKey)) {
                $replacement = '<mq:comments>'.$commentString.'</mq:comments>'.$xmlparser->getChunk($key);
            }
            else {
                $key = $this->currentCommentsKey;
                $replacement = $commentString.$xmlparser->getChunk($key);
            }
            $xmlparser->replaceChunk($key, $replacement);

            $this->currentCommentsKey = null;
        });
    }

    /**
     * creates memoQ comments out of translate5 comments
     * @return string|mixed
     */
    protected function processComments() {
        if(empty($this->comments)) {
            return '';
        }

        foreach($this->comments as $comment) {
            //comments already imported from across are ignored
            if($comment['userGuid'] == editor_Models_Import_FileParser_Xlf_Namespaces_MemoQ::USERGUID) {
                continue;
            }
            self::$xmlWriter->startElement('mq:comment');
            self::$xmlWriter->writeAttribute('id', ZfExtended_Utils::uuid());
            self::$xmlWriter->writeAttribute('creatoruser', $comment['userName']);
            self::$xmlWriter->writeAttribute('time', gmdate('Y-m-d\TH:i:s\Z', strtotime($comment['modified'])));
            self::$xmlWriter->writeAttribute('deleted', 'false');
            self::$xmlWriter->writeAttribute('category', '0');
            self::$xmlWriter->writeAttribute('appliesto', 'Target');
            self::$xmlWriter->writeAttribute('origin', 'User');
            self::$xmlWriter->text($comment['comment']);
            self::$xmlWriter->endElement();
        }
        $this->comments = [];
        return self::$xmlWriter->flush();
    }
}
