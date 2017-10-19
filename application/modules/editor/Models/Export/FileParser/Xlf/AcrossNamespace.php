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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
class editor_Models_Export_FileParser_Xlf_AcrossNamespace extends editor_Models_Export_FileParser_Xlf_AbstractNamespace{
    const ACROSS_XLIFF_NAMESPACE = 'xmlns:ax="AcrossXliff"';
    
    /**
     * @var array
     */
    protected $comments = [];
    
    protected $currentPropertiesKey = null;
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
     * @see editor_Models_Import_FileParser_Xlf_AbstractNamespace::registerParserHandler()
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        //a little bit hackish but the easiest way to get the task
        $task = func_get_arg(1);
        
        $config = Zend_Registry::get('config');
        if(! $config->runtimeOptions->editor->export->exportComments) {
            //currently only the comment export feature is implemented in the across XLF, 
            // so if exporting comments is disabled we disable just the whole function
            //FIXME return;
        }
        
        $xmlparser->registerElement('trans-unit ax:named-properties', null, function($tag, $key, $opener) use ($xmlparser){
            $this->currentPropertiesKey = $key;
        });
        //must use another selector as in the Xlf Export itself. On using the same selector, the later one overwrites the first one
        $xmlparser->registerElement('trans-unit lekTargetSeg', null, function($tag, $key, $opener) use ($xmlparser, $task){
            $attributes = $opener['attributes'];
            if(empty($attributes['id'])) {
                throw new Zend_Exception('Missing id attribute in '.$xmlparser->getChunk($key));
            }
            $comment = ZfExtended_Factory::get('editor_Models_Comment');
            /* @var $comment editor_Models_Comment */
            $comments = $comment->loadBySegmentAndTaskPlain((int) $attributes['id'], $task->getTaskGuid());
            if(empty($comments)) {
                return;
            }
            $this->comments = array_merge($comments, $this->comments);
            
        });
        $xmlparser->registerElement('xliff trans-unit', null, function($tag, $key, $opener) use ($xmlparser){
            $commentString = $this->commentsToXml($this->comments);
            if(empty($this->currentPropertiesKey)) {
                $replacement = '<ax:named-properties>'.$commentString.'</ax:named-properties>'.$xmlparser->getChunk($key);
            }
            else {
                $key = $this->currentPropertiesKey;
                $replacement = $commentString.$xmlparser->getChunk($key);
            }
            $xmlparser->replaceChunk($key, $replacement);
            $this->currentPropertiesKey = null;
        });
    }
    
    /**
     * creates a comment
     * @param array $comments
     * @return string|mixed
     */
    protected function commentsToXml(array $comments) {
        if(empty($comments)) {
            return '';
        }

        foreach($comments as $comment) {
            if($comment['userGuid'] == editor_Models_Import_FileParser_Xlf_AcrossNamespace::USERGUID) {
                continue;
            }
            self::$xmlWriter->startElement('ax:named-property');
            self::$xmlWriter->writeAttribute('name', 'Comment');
            $this->addNamedValue('Author', $comment['userName']);
            $this->addNamedValue('Created', $comment['modified']); //FIXME format!
            $this->addNamedValue('Annotates', 'General');
            $this->addNamedValue('Title', 'exported from translate5');
            $this->addNamedValue('Text', $comment['comment']);
            self::$xmlWriter->endElement();
        }
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
}
