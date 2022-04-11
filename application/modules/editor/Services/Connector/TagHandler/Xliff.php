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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * protects the translate5 internal tags as XLIFF for language resource processing
 */
class editor_Services_Connector_TagHandler_Xliff extends editor_Services_Connector_TagHandler_Abstract {

    /**
     * @var integer
     */
    protected $mapCount = 0;
    
    /**
     * Counter for additional tags in one segment content block
     * @var integer
     */
    protected $additionalTagCount = 1;
    
    public function __construct() {
        parent::__construct();
        
        //replace unusable <ph|it etc> tags with usable <x|bx etc> tags
        $this->xmlparser->registerElement(
            't5xliffresult > it,t5xliffresult > ph,t5xliffresult > ept,t5xliffresult > bpt',
            null,
            function($tag, $key, $opener){
                switch ($tag) {
                    case 'bpt':
                        $tag = '<bx';
                        break;
                    case 'ept':
                        $tag = '<ex';
                        break;
                    default:
                        $tag = '<x';
                        break;
                }
                $this->xmlparser->replaceChunk($opener['openerKey'], '', $opener['isSingle'] ? 1 : ($key-$opener['openerKey']+1));
                $this->xmlparser->replaceChunk($opener['openerKey'], $tag.' mid="additional-'.($this->additionalTagCount++).'" />');
            }
        );
    }
    
    /**
     * protects the internal tags as xliff tags x,bx,ex and g pair
     *
     * calculates and sets map and mapCount internally
     *
     * @param string $queryString
     * @param int $segmentId
     * @return string
     */
    public function prepareQuery(string $queryString, int $segmentId=-1): string {
        $this->realTagCount = 0;
        $queryString = $this->restoreWhitespaceForQuery($queryString);
        
        //$map is set by reference
        $this->map = [];
        $this->realTagCount = $this->utilities->internalTag->count($queryString);
        $queryString = $this->utilities->internalTag->toXliffPaired($queryString, true, $this->map);
        $this->mapCount = count($this->map);
        return $queryString;
    }
    
    /**
     * protects the internal tags for language resource processing as defined in the class
     * @param string $queryString
     * @param int $segmentId
     * @return string|NULL NULL on error
     */
    public function restoreInResult(string $resultString, int $segmentId=-1): ?string {
        $this->hasRestoreErrors = false;
        //strip other then x|ex|bx|g|/g
        $resultString = strip_tags($this->replaceTagsWithContent($resultString), '<x><x/><bx><bx/><ex><ex/><g>');
        //since protectWhitespace should run on plain text nodes we have to call it before the internal tags are reapplied,
        // since then the text contains xliff tags and the xliff tags should not contain affected whitespace
        // this is triggered here with the parse call
        try {
            $target = $this->xmlparser->parse($resultString);
        }
        catch (editor_Models_Import_FileParser_InvalidXMLException $e) {
            $this->logger->exception($e, ['level' => $this->logger::LEVEL_WARN]);
            //See previous InvalidXMLException
            $this->logger->warn('E1302', 'The LanguageResource did contain invalid XML, all tags were removed. See also previous InvalidXMLException in Log.',[
                'givenContent' => $resultString,
            ]);
            $this->hasRestoreErrors = true;
            return strip_tags($resultString);
        }
        $target = $this->utilities->internalTag->reapply2dMap($target, $this->map);
        return $this->replaceAdditionalTags($target, $this->mapCount);
    }
    
    /**
     * If the XLF result from a TM contains <it> ,<ph>,<bpt> and <ept> tags, they could not be replaced by the sent <x|bx|ex|g> tags in the source,
     *   so they have to be considered as additional tags then
     * @param string $segmentContent
     */
    protected function replaceTagsWithContent(string $content): string {
        //just concat source and target to check both:
        if(preg_match('#<(it|ph|ept|bpt)[^>]*>#', $content)) {
            //surround the content with tmp tags(used later as selectors)
            $content = $this->xmlparser->parse('<t5xliffresult>'.$content.'</t5xliffresult>');
            
            //remove the helper tags
            return strtr($content, [
                '<t5xliffresult>'=>'',
                '</t5xliffresult>'=>''
            ]);
        }
        return $content;
    }
    
    /**
     * replace additional tags from the TM to internal tags which are ignored in the frontend then
     * @param string $segment
     * @param int $mapCount used as start number for the short tag numbering
     * @return string
     */
    protected function replaceAdditionalTags(string $segment, int $mapCount): ?string {
        $addedTags = false;
        $shortTagNr = $mapCount;
        
        $result = preg_replace_callback('#<(x|ex|bx|g|/g)[^>]*>#', function() use (&$shortTagNr, &$addedTags) {
            $addedTags = true;
            return $this->utilities->internalTag->makeAdditionalHtmlTag(++$shortTagNr);
        }, $segment);
        
        if($addedTags) {
            //logging as debug only, since in GUI they are removed. FIXME whats with pretranslation?
            $this->logger->debug('E1300', 'The LanguageResource answer did contain additional tags which were added to the segment, starting with Tag Nr {nr}.',[
                'nr' => $mapCount,
                'givenContent' => $segment,
            ]);
        }
        return $result;
    }
}
