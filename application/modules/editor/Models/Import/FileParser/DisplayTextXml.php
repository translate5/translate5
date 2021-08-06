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
 * Fileparsing for import of customer specific XML
 */
class editor_Models_Import_FileParser_DisplayTextXml extends editor_Models_Import_FileParser {
    use editor_Models_Import_FileParser_TagTrait;
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser;
    
    /**
     * the current segment is considered readonly
     * @var boolean
     */
    protected $currentIsReadOnly = false;
    
    /**
     * the current font when parsing meta data
     * @var boolean
     */
    protected $currentFont;
    
    /**
     * container for the pixel width of each inset
     * @var array
     */
    protected $insetWidths = [];
    
    /**
     * container for the pixel width of each inset
     * @var array
     */
    protected $lengthDefinitions = [];
    
    /**
     * The comments of the current segment
     * @var array
     */
    protected $currentComments = [];
    
    /**
     * The segmentId of the current saved segment
     * @var integer
     */
    protected $currentSegmentId = null;
    
    /**
     * @var integer
     */
    protected $segmentCount = 0;
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['xml'];
    }
    
    /**
     * returns true if the given file is XLF and parsable by this parser
     *
     * @param string $fileHead the first 512 bytes of the file to be imported
     * @param string $errorMsg returning by reference a reason why its not parsable
     * @return boolean
     */
    public static function isParsable(string $fileHead, string &$errorMsg): bool {
        $errorMsg = '';
        // check here the loaded XML content, if it is XLF everything is ok, since we extend the Xlf parser
        // if it is another (not supported) XML type we throw an exception
        if(strpos($fileHead,'<!DOCTYPE Book SYSTEM "TRANSLATE_DISPLAYTEXTS.dtd">') === false) {
            $errorMsg = 'File is no doctype Book SYSTEM TRANSLATE_DISPLAYTEXTS.dtd!';
            return false;
        }
        return true;
    }
    
    /**
     */
    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task) {
        parent::__construct($path, $fileName, $fileId, $task);
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
        $this->initImageTags();
    }
    
    /**
     * This function return the number of words of the source-part in the imported xlf-file
     *
     * @return: (int) number of words
     */
    public function getWordCount()
    {
        return 0;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::parse()
     */
    protected function parse() {
        $this->xmlparser = $parser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $parser editor_Models_Import_FileParser_XmlParser */
        
        $this->registerMeta();
        $this->registerContent();
        
        try {
            $this->_skeletonFile = $parser->parse($this->_origFile, false);
        }
        catch(editor_Models_Import_FileParser_InvalidXMLException $e) {
            $logger = Zend_Registry::get('logger')->cloneMe('editor.import.fileparser.displayTextXml');
            //we log the XML error as own exception, so that the error is listed in task overview
            $e->addExtraData(['task' => $this->task]);
            /* @var $logger ZfExtended_Logger */
            $logger->exception($e);
            
            //'E1273' => 'The XML of the display text XML file "{fileName} (id {fileId})" is invalid!',
            throw new editor_Models_Import_FileParser_DisplayTextXml_Exception('E1273', [
                'task' => $this->task,
                'fileName' => $this->_fileName,
                'fileId' => $this->_fileId,
            ], $e);
        }
        
         if ($this->segmentCount === 0) {
           // 'E1274' => 'The DisplayText XML file "{fileName} (id {fileId})" does not contain any translation relevant segments.',
             throw new editor_Models_Import_FileParser_DisplayTextXml_Exception('E1274', [
                 'task' => $this->task,
                 'fileName' => $this->_fileName,
                 'fileId' => $this->_fileId,
             ]);
         }
    }
    
    /**
     * collect meta (fonts and widths) data
     * @throws Exception
     */
    protected function registerMeta() {
        $pxMap = ZfExtended_Factory::get('editor_Models_PixelMapping');
        /* @var $pxMap editor_Models_PixelMapping */
        
        /**
         * Font parsing
         */
        $this->xmlparser->registerElement('Fonts Font', function($tag, $attributes) use ($pxMap) {
            $this->currentFont = $this->xmlparser->getAttribute($attributes, 'id');
            $fontSize = $this->fontSizeFromName($this->currentFont);
            
            //Since linebreak (10) is mostly not defined to have a width in the XML we have to define it manually with width 0
            // if there is defined one, it just overwrites the value here
            $pxMap->insertPixelMappingRow($this->task->getTaskGuid(), $this->_fileId, $this->currentFont, $fontSize, 10, 0);
        });
        
        $this->xmlparser->registerElement('Fonts Font Token', function($tag, $attributes) use ($pxMap) {
            $fontSize = $this->fontSizeFromName($this->currentFont);
            $char = $this->xmlparser->getAttribute($attributes, 'unicode');
            $pixelWidth = $this->xmlparser->getAttribute($attributes, 'pxwidth');
            $pxMap->insertPixelMappingRow($this->task->getTaskGuid(), $this->_fileId, $this->currentFont, $fontSize, $char, $pixelWidth);
        });
        
        /**
         * save the inset widths
         * <InsetType ID="insetLAST30DAYS_DHWELECTRICITY_1" Type="Pixel" PxWidth="28"/>
         */
        $this->xmlparser->registerElement('Insets InsetType', function($tag, $attributes) {
            $id = $this->xmlparser->getAttribute($attributes, 'id');
            $type = strtolower($this->xmlparser->getAttribute($attributes, 'type'));
            if(editor_Models_Segment_PixelLength::SIZE_UNIT_FOR_PIXELMAPPING !== $type) {
                //Element Inset with ID {id} has the invalid type {type}, only type "pixel" is supported!
                throw new editor_Models_Import_FileParser_DisplayTextXml_Exception("E1275", [
                    'id' => $id,
                    'type' => $type,
                    'task' => $this->task,
                ]);
            }
            $this->insetWidths[$id] = $this->xmlparser->getAttribute($attributes, 'pxwidth');
        });
        
        /**
         * save the inset widths
         * <Lengths> <Len ID="lenPopup" Type="Pixel" Font="NSC_24px" Lines="3" MaxPxWidth="300"/>
         */
        $this->xmlparser->registerElement('Lengths Len', function($tag, $attributes) {
            $id = $this->xmlparser->getAttribute($attributes, 'id');
            $type = strtolower($this->xmlparser->getAttribute($attributes, 'type'));
            if(editor_Models_Segment_PixelLength::SIZE_UNIT_FOR_PIXELMAPPING !== $type) {
                //Element Len with ID {id} has the invalid type {type}, only type "pixel" is supported!
                throw new editor_Models_Import_FileParser_DisplayTextXml_Exception("E1276", [
                    'id' => $id,
                    'type' => $type,
                    'task' => $this->task,
                ]);
            }
            $this->lengthDefinitions[$id] = [
                'font' => $this->xmlparser->getAttribute($attributes, 'font'),
                'lines' => $this->xmlparser->getAttribute($attributes, 'lines'),
                'maxWidth' => $this->xmlparser->getAttribute($attributes, 'maxpxwidth'),
            ];
        });
        
    }
    
    /**
     * handles the content of the to be imported XML
     */
    protected function registerContent() {
        $this->xmlparser->registerElement('string', null, function($tag, $key, $opener) {
            //<Comment Language="german"> Allgemeiner Anzeigetext<Linefeed/> <Linefeed/>Nähere Erklärung: 'Dashes will be shown for the invalid data' </Comment>
            if(!empty($this->currentComments)) {
                foreach($this->currentComments as $comment) {
                    /* @var $comment editor_Models_Comment */
                    $comment->setTaskGuid($this->task->getTaskGuid());
                    $comment->setSegmentId($this->currentSegmentId);
                    $comment->save();
                }
                //if there was at least one processed comment, we have to sync the comment contents to the segment
                if(!empty($comment)){
                    $segment = ZfExtended_Factory::get('editor_Models_Segment');
                    /* @var $segment editor_Models_Segment */
                    $segment->load($this->currentSegmentId);
                    $comment->updateSegment($segment, $this->task->getTaskGuid());
                }
            }
            //save comments, reset segmentId
            $this->currentSegmentId = null;
            $this->currentComments = [];
        });
        
        //we just replace the linefeed tags with a raw newline. So it is replaced as new line tag then
        $this->xmlparser->registerElement('comment linefeed, displaymessage linefeed', function($tag, $attributes, $key) {
            $this->xmlparser->replaceChunk($key, "\n");
        });
        
        //segments containing translockdt are considered to be complete readonly
        $this->xmlparser->registerElement('translockdt', null, function($tag, $key, $opener) {
            $this->currentIsReadOnly = true;
        });
        
        $this->xmlparser->registerElement('displaymessage', function($tag){
            //reset is readonly
            $this->currentIsReadOnly = false;
            $this->shortTagIdent = 1;
        }, function($tag, $key, $opener) {
            //save comments, reset segmentId
            $this->extractSegment($opener, $key);
        });
        
        $this->xmlparser->registerElement('comment', null, function($tag, $key, $opener) {
            $this->currentComments[] = $currentComment = ZfExtended_Factory::get('editor_Models_Comment');
            /* @var $currentComment editor_Models_Comment */
            //$currentComment->
            $currentComment->setUserName($this->xmlparser->getAttribute($opener['attributes'], 'language'),);
            $currentComment->setUserGuid('imported');
            $currentComment->setComment($this->xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true));
        });
        
        $this->xmlparser->registerElement('displaymessage inset', null, function($tag, $key, $opener) {
            //we get the whole inset tag
            $chunk = $this->xmlparser->getRange($opener['openerKey'], $key, true);
            //save te inset tag as internal tag
            $this->xmlparser->replaceChunk($opener['openerKey'], $this->createTag($tag, $chunk, $opener['attributes']));
            //reset the additional non needed chunks to empty
            $this->xmlparser->replaceChunk($opener['openerKey'] + 1, '', $key - $opener['openerKey']);
        });
        
        $this->xmlparser->registerElement('*', function($tag) {
            switch ($tag) {
                    
                //handeld or known:
                case '?xml':
                case '?xml-stylesheet':
                case '!doctype':
                case 'book':
                case 'timestamp':
                case 'errordb':
                case 'languagepart':
                case 'fonts':
                case 'token':
                case 'lengths':
                case 'Len':
                case 'insets':
                case 'textid':
                case 'comment':
                case 'xml-comment':
                case 'linefeed':
                case 'displaytexts':
                case 'displaymessage':
                case 'translockdt':
                case 'string':
                case 'inset':
                    return;
            }
            $logger = Zend_Registry::get('logger')->cloneMe('editor.import.fileparser.displayTextXml');
            $logger->warn('E1277', 'Unknown XML tags "{tag}" discovered in file "{fileName} (id {fileId})"!', [
                'task' => $this->task,
                'fileName' => $this->_fileName,
                'fileId' => $this->_fileId,
            ]);
        });
    }
    
    protected function extractSegment(array $opener, $closerKey) {
        
        /*
        <String>
        <!-- Unit.label() is appened to numeric values and used standalone in SLIDER_UNIT-->
        <TextID>STR_UNITS_POWER_KW_UNIT</TextID>
        <DisplayMessage ID="STR_UNITS_POWER_KW_UNIT" Len="lenTypeUnit"> <TransLockDT>kW</TransLockDT> </DisplayMessage>
        <Comment Language="german"> Physikalische Einheit eines angezeigten Werts. Die Einheit wird an den sichtbaren Zahlenwert im LCD angehängt. </Comment>
        <Comment Language="english"> Physical unit of displayed values. Appened to numberic values visible in the LCD. </Comment>
        </String>
        
        The file structure should be converted as follows:

The texts to translate are within the "DisplayMessage" tags and must be extracted.

    <String>
    <!-- errorCode.errorDescription() is used at individual places. The Menu DSL needs to give the reference to the GUI label -->
    <TextID>STR_CAUSECODE_9001</TextID>
    <DisplayMessage ID="STR_CAUSECODE_9001" Len="lenErrorMessage">Störung an der Brennstoffzelle.<Linefeed/>Werkskundendienst informieren</DisplayMessage>
    <Comment Language="german"> Störungsmeldung<Linefeed/> DisplayCode=FC, CauseCode=9001<Linefeed/> Störung an der Brennstoffzelle.<Linefeed/>Werkskundendienst informieren </Comment>
    <Comment Language="english"> error message<Linefeed/> DisplayCode=FC, CauseCode=9001<Linefeed/> Number of Controlled shutdown has been exceeded </Comment>
    </String>

The ID of the DisplayMessage-Tag must be used as ID for the xliff trans-unit.

The segment length in pixel is defined by the "len"-attribute of the DisplayMessage tag, which references to the head of the file for details. Which character stands for how many pixels for which font is also defined in the head of the file.

Linefeed-tags must be removed on import. Translate5 will add the needed line-breaks while editing, those must be exported as linefeed-tags again.

The German and the English Comment tag of the string must be imported as comments for the segment. Comments should be reexported to the xliff (to complete the comment cycle for xliff) but should not be inserted in the final string-xml-file.
         */
        
        $segment = $this->xmlparser->getRange($opener['openerKey'] + 1, $closerKey - 1, true);
        
        //we have to protect internal tags before whitespace handling
        $segment = $this->utilities->internalTag->protect($segment);
        
        //since there are no other tags we can just take the string and protect whitespace there (no tag protection needed!)
        $segment = $this->utilities->whitespace->protectWhitespace($segment);
        $segment = $this->replacePlaceholderTags($segment);
        
        $segment = $this->utilities->internalTag->unprotect($segment);
        
        //define the fieldnames where the data should be stored
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        
        $textId = $this->xmlparser->getAttribute($opener['attributes'], 'id');
        $lengthId = $this->xmlparser->getAttribute($opener['attributes'], 'len');

        $this->segmentData = [];
        $this->segmentData[$sourceName] = array(
            'original' => $segment
        );
        $this->segmentData[$targetName] = array(
            'original' => '',
        );
            
        $this->parseSegmentAttributes($textId, $lengthId);
        $this->currentSegmentId = $this->setAndSaveSegmentValues();
        $this->segmentCount++;
        
        //If segment is readonly, we do not place placeholders, so that no reconversion is needed here
        if($this->currentIsReadOnly) {
            return;
        }
        
        //replace the displaymessage content with the placeholder
        $start = $opener['openerKey'] + 1;
        $length = $closerKey - $start;
        
        //empty content between displaymessage tags:
        $this->xmlparser->replaceChunk($start, '', $length);
        
        //add placeholder
        $this->xmlparser->replaceChunk($start, $this->getFieldPlaceholder($this->currentSegmentId, $targetName));
    }
    
    /**
     * creates the segment attributes object
     * @param string $textId
     * @param string $lengthId
     * @return editor_Models_Import_FileParser_SegmentAttributes
     */
    protected function parseSegmentAttributes($textId, $lengthId): editor_Models_Import_FileParser_SegmentAttributes {
        $segmentAttributes = $this->createSegmentAttributes($textId);
        $this->setMid($textId);
        $segmentAttributes->transunitId = $textId;
        $segmentAttributes->editable = !$this->currentIsReadOnly;
        
        if(array_key_exists($lengthId, $this->lengthDefinitions)) {
            $segmentAttributes->maxWidth = $this->lengthDefinitions[$lengthId]['maxWidth'];
            $segmentAttributes->sizeUnit = editor_Models_Segment_PixelLength::SIZE_UNIT_FOR_PIXELMAPPING;
            $segmentAttributes->maxNumberOfLines = $this->lengthDefinitions[$lengthId]['lines'];
            $segmentAttributes->font = $this->lengthDefinitions[$lengthId]['font'];
            $segmentAttributes->fontSize = $this->fontSizeFromName($segmentAttributes->font);
        }
        
        return $segmentAttributes;
    }
    
    /**
     * In this import format the fontSize is implicit given in the font name.
     * So for distinction of fonts the name would be completly Ok,
     * so if we could not get a size from the name, we just assume Deep Thoughts 42.
     *
     * @param string $fontName
     * @return int
     */
    protected function fontSizeFromName(string $fontName): int {
        $match = null;
        if(preg_match('/([0-9]+)px$/', $fontName, $match)) {
            return (int) $match[1];
        }
        return 42;
    }
    
    /**
     * creates an internal tag out of an inset
     * @param string $tag
     * @param string  $chunk
     * @param array $attributes
     * @return string
     */
    protected function createTag(string $tag, string $chunk, array $attributes): string {
        $insetType = $this->xmlparser->getAttribute($attributes, 'insettype');
        $p = $this->getTagParams($chunk, $this->shortTagIdent++, $insetType, htmlentities($chunk));
        //we get the width from the Insets list
        if(array_key_exists($insetType, $this->insetWidths)) {
            $p['length'] = $this->insetWidths[$insetType];
        } else {
            $p['length'] = 0;
        }
        return $this->_singleTag->getHtmlTag($p);
    }
}
