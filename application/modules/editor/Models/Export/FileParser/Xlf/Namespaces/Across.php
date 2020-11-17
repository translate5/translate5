<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * XLF Fileparser Add On to parse Across XLF specific stuff
 */
class editor_Models_Export_FileParser_Xlf_Namespaces_Across extends editor_Models_Export_FileParser_Xlf_Namespaces_Abstract{
    protected $currentPropertiesKey = null;
    protected $currentErrorInfosKey = null;
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

        $config = $task->getConfig();
        if(! $config->runtimeOptions->editor->export->exportComments) {
            //currently only the comment export feature is implemented in the across XLF,
            // so if exporting comments is disabled we disable just the whole function
            return;
        }

        $xmlparser->registerElement('trans-unit ax:named-properties', null, function($tag, $key, $opener) use ($xmlparser){
            $this->currentPropertiesKey = $key;
        });
        $xmlparser->registerElement('trans-unit ax:analysisResult ax:errorInfos', null, function($tag, $key, $opener) use ($xmlparser){
            $this->currentErrorInfosKey = $key;
        });

        //must use another selector as in the Xlf Export itself. On using the same selector, the later one overwrites the first one
        $xmlparser->registerElement('trans-unit lekTargetSeg', null, function($tag, $key, $opener) use ($xmlparser, $task){
            $this->loadComments($opener['attributes'], $xmlparser, $task);
        });

        $xmlparser->registerElement('xliff trans-unit', null, function($tag, $key, $opener) use ($xmlparser){
            /*
            The following code adds translate5 comments as reals across comments.
            The reading of such comments is not implemented in across yet.
            So we disable it here and add the comments as analysis result
            $commentString = $this->processComments();
            if(empty($this->currentPropertiesKey)) {
                $replacement = '<ax:named-properties>'.$commentString.'</ax:named-properties>'.$xmlparser->getChunk($key);
            }
            else {
                $key = $this->currentPropertiesKey;
                $replacement = $commentString.$xmlparser->getChunk($key);
            }
            $xmlparser->replaceChunk($key, $replacement);
            */

            $keyToReplace = $this->currentErrorInfosKey;
            $this->currentPropertiesKey = null;
            $this->currentErrorInfosKey = null;

            $commentString = $this->processCommentsAsAnalysisResult();
            if(empty($commentString) && $commentString !== '0') {
                return;
            }
            if(empty($keyToReplace)) {
                $keyToReplace = $key; //inject analysisResult before transunit
                $replacement = '<ax:analysisResult><ax:errorInfos>'.$commentString.'</ax:errorInfos></ax:analysisResult>'.$xmlparser->getChunk($key);
            }
            else {
                //inject content before </ax:errorInfos>
                $replacement = $commentString.$xmlparser->getChunk($keyToReplace);
            }
            $xmlparser->replaceChunk($keyToReplace, $replacement);
        });
    }

    /**
     * creates real across comments out of translate5 comments
     * @return string|mixed
     */
    protected function processComments() {
        if(empty($this->comments)) {
            return '';
        }

        foreach($this->comments as $comment) {
            //comments already imported from across are ignored
            if($comment['userGuid'] == editor_Models_Import_FileParser_Xlf_Namespaces_Across::USERGUID) {
                continue;
            }
            self::$xmlWriter->startElement('ax:named-property');
            self::$xmlWriter->writeAttribute('name', 'Comment');
            $this->addNamedValue('Author', $comment['userName']);
            $this->addNamedValue('Created', date('m/d/Y H:i:s', strtotime($comment['modified'])));
            $this->addNamedValue('Annotates', 'General');
            $this->addNamedValue('Title', 'exported from translate5');
            $this->addNamedValue('Text', $comment['comment']);
            self::$xmlWriter->endElement();
        }
        $this->comments = [];
        return self::$xmlWriter->flush();
    }

    /**
     * creates a named value entry
     * @param string $name
     * @param string $value
     */
    protected function addNamedValue($name, $value) {
        self::$xmlWriter->startElement('ax:named-value');
        self::$xmlWriter->writeAttribute('ax:name', $name);
        self::$xmlWriter->text($value);
        self::$xmlWriter->endElement();
    }

    /**
     * returns the segment comments as across analysisresult
     * @return string|mixed
     */
    protected function processCommentsAsAnalysisResult() {
        if(empty($this->comments)) {
            return '';
        }

        foreach($this->comments as $comment) {
            //comments already imported from across are ignored:
            if($comment['userGuid'] == editor_Models_Import_FileParser_Xlf_Namespaces_Across::USERGUID) {
                continue;
            }
            self::$xmlWriter->startElement('ax:errorInfo');
            self::$xmlWriter->startElement('ax:description');

            self::$xmlWriter->startElement('ax:type');
            self::$xmlWriter->text('Comment');
            self::$xmlWriter->endElement();
            self::$xmlWriter->startElement('ax:title');
            $date = date('m/d/Y H:i:s', strtotime($comment['modified']));
            self::$xmlWriter->text('Comment from '.$comment['userName'].' ('.$date.') in translate5');
            self::$xmlWriter->endElement();
            self::$xmlWriter->startElement('ax:explanation');
            self::$xmlWriter->text($comment['comment']);
            self::$xmlWriter->endElement();
            self::$xmlWriter->startElement('ax:instruction');
            self::$xmlWriter->endElement();
            self::$xmlWriter->startElement('ax:examples');
            self::$xmlWriter->endElement();

            self::$xmlWriter->endElement(); //end ax:description
            self::$xmlWriter->endElement(); //end ax:errorInfo
        }
        $this->comments = [];
        return self::$xmlWriter->flush();
    }
}
