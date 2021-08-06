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

/**
 * Segment TermTagTrackChange Helper Class
 *
 * Helper for removing and re-inserting TrackChange-Nodes in Term-tagged texts.
 * Needed before and after texts are sent to the TermTag-Server that finds the terms.
 * - First step (before): Store all TrackChange-Nodes and their positions; then remove them from the text.
 * - Second step (after): Re-insert the stored TrackChange-Nodes at the stored positions in the text that now includes the TermTags, too.
 *
 * Nothing in here really relates to TrackChange-Stuff itself, only the regular expressions for finding the nodes.
 * Feel free to rename it for general use and extend it to other nodes.
 *
 * The Service Class of Plugin "TermTagger" (editor_Plugins_TermTagger_Service) uses these methods
 * no matter if the TrackChange-Plugin is activated or not.
 * That's why we need this in the core-Code, not in the Plugin-Code.
 *
 * ********************* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! *********************
 * NO MULTIBYTE_PROBLEMS occur at the moment - although we use strlen and substr. This is because we just 
 * walk through the string step by step, and it does not matter that these steps are bytes, not characters. 
 * USING mb_strlen and mb_substr however WILL PRODUCE WRONG RESULTS because we loose the offsets from PREG_OFFSET_CAPTURE 
 * in preg_match_all.
 * ********************* !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! *********************
 * 
 */
class editor_Models_Segment_TermTagTrackChange {
    
    const TAG_INS = editor_Models_Segment_TrackChangeTag::NODE_NAME_INS;
    const TAG_TERM = 'div';
    const START_INS = '<'.self::TAG_INS;
    const END_INS = '</'.self::TAG_INS.'>';
    const START_TERM = '<'.self::TAG_TERM;
    const END_TERM = '</'.self::TAG_TERM.'>';
    
    protected $partlyCorrected;
    protected $insStack = [];
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xml;
    
    /**
     * merge together the original content with ins and del tags with the same content coming from the term tagger without ins/del but with terms
     * 
     * Examples see Translate1475Test Test
     * 
     * @param string $target
     * @param string $tagged
     * @return string
     */
    public function mergeTermsAndTrackChanges($target, $tagged) {
        $target = preg_split('/(<[^>]+>)|(&#[^;]+;)|(.)/u', $target, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $tagged = preg_split('/(<[^>]+>)|(&#[^;]+;)|(.)/u', $tagged, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        
        $diff = ZfExtended_Factory::get('ZfExtended_Diff');
        /* @var $diff ZfExtended_Diff */
        $diffRes = $diff->process($target, $tagged);
        foreach($diffRes as $idx => $item) {
            if(is_array($item)) {
                $diffRes[$idx] = join('', $this->mergeTagHunks($item['d'], $item['i']));
            }
        }
        return $this->checkAndRepairXml(join('', $diffRes));
    }
    
    /**
     * merge together the content with term tags and the content with ins del tags
     * @param array $termTags
     * @param array $insDelTags
     * @return array
     */
    protected function mergeTagHunks(array $termTags, array $insDelTags) {
        //since textual content can be different before and after retrieving it from the termtagger
        // we dismiss the before text content and use the after text content.
        // for example &39; are converted to real ' characters by the termtagger. Such characters will be in the diff to.
        // on the termTags side we have to keep them
        // in the $insDelTags we can just remove them
        $insDelTags = array_filter($insDelTags, function($item) {
            //it is save to check for just the first < character, since this is always a tag then, since single <> characters are always encoded  
            return strpos($item, '<') === 0;
        });
            
        //if the one side is empty, we just use the other side
        if(empty($insDelTags)) {
            return $termTags;
        }
        if(empty($termTags)) {
            return $insDelTags;
        }
        
        $result = [];
        $termTag = array_shift($termTags);
        $insDelTag = array_shift($insDelTags);
        while(!is_null($termTag) || !is_null($insDelTag)) {
            if(is_null($insDelTag)) {
                $result[] = $termTag;
                $termTag = array_shift($termTags);
                continue;
            }
            if(is_null($termTag)) {
                $result[] = $insDelTag;
                $insDelTag = array_shift($insDelTags);
                continue;
            }
            if($this->useTermBeforeInsDel($termTag, $insDelTag)) {
                $result[] = $termTag;
                $termTag = array_shift($termTags);
            }
            else {
                $result[] = $insDelTag;
                $insDelTag = array_shift($insDelTags);
            }
        }
        return $result;
    }
    
    /**
     * Decide if the termTag or the given insDelTag has a higher precedence
     * @param string $termTag
     * @param string $insDelTag
     * @return boolean
     */
    protected function useTermBeforeInsDel($termTag, $insDelTag) {
        // the sort order for comparing <ins></ins><del placeholder /> with <div class="term"> </div> tags, is listed here:
        /*
         //$termTag's → </t><t> | </t> | <t>
         //$insDelTag's → </i><d/><i> | <d/><i> | </i><d/> | <i> | </i> | <d/>
         
         Matrix:
                  ||   </i><d/><i>         ||  <d/><i>        ||   </i><d/>        ||  <i>        ||   </i>         ||   <d/>
         ===========================================================================================================================
         </t><t>  ||   </i></t><d/><t><i>  ||  </t><d/><t><i> ||   </i></t><d/><t> ||  </t><t><i> ||   </i></t><t>  ||   </t><d/><t>
         </t>     ||   </i></t><d/><i>     ||  </t><d/><i>    ||   </i></t><d/>    ||  </t><i>    ||   </i></t>     ||   </t><d/>
         <t>      ||   </i><d/><t><i>      ||  <d/><t><i>     ||   </i><d/><t>     ||  <t><i>     ||   </i><t>      ||   <d/><t>
         
         This results in the following mapping to numbers to use integers for comparison
         
         </i> => 1
         </t> => 2
         <d/> => 3
         <t> => 4
         <i> => 5
         */
        
        if($termTag === self::END_TERM) {
            $termTag = 2;
        }
        elseif (strpos($termTag, self::START_TERM.' ') === 0) {
            $termTag = 4;
        }
        else {
            //all other is text coming from the TermTagger for example a converted &39; to a '
            // so in $insDelTag was &39; and in $termTag the ', we keep just the content coming from termTagger
            return true;
        }
        
        if($insDelTag === self::END_INS) {
            $insDelTag = 1;
        }
        elseif (strpos($insDelTag, self::START_INS) === 0) {
            $insDelTag = 5;
        }
        else {
            //these are the <segment:del placeholders
            $insDelTag = 3;
        }
        
        return $insDelTag > $termTag;
    }
    
    /**
     * Checks the given XML string if it is well formed and repairs if needed by cutting the INS tags into several peaces 
     * @param string $text
     * @return string
     */
    protected function checkAndRepairXml($text) {
        do {
            $this->partlyCorrected = null;
            try {
                $this->innerCheckAndRepairXml($text);
            }
            catch(editor_Models_Segment_TermTagTrackChangeStopException $e) {
                //if there was an error, rerun the check / parse with the applied fixes
            }
            $text = $this->partlyCorrected;
        }
        while(!empty($this->partlyCorrected));
        //error_log("Final step: ".print_r($this->xml->getChunks(0, null),1));
        return (string) $this->xml;
    }
    
    /**
     * Real repair call, called in a loop until all errors are gone
     * We assume that del and ins are not nested (this is not allowed in the Frontend), 
     *  that results in the fact that there is no need to handle the del tags here, 
     *  since we are always in the start or end node of a ins pair. Inside there is no del tag. So we don't have to care about them. 
     * 
     * @param string $text
     */
    protected function innerCheckAndRepairXml($text) {
        $this->insStack = [];
        $xml = $this->xml = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xml editor_Models_Import_FileParser_XmlParser */
        $xml->registerError(function($opener, $tag, $key){
            return $this->handleXmlError($opener, $tag, $key);
        });
        $xml->registerElement(self::TAG_INS,
            function($tag, $attributes, $key) {
                $this->insStack[] = $this->xml->getChunk($key);
            },
            function($tag, $key, $opener) {
                array_pop($this->insStack);
            }
        );
        $xml->parse($text);
    }
    
    /**
     * Handler for XML structure Errors
     * @param array $opener
     * @param string $tag
     * @param int $key
     * @throws editor_Models_Segment_TermTagTrackChangeStopException
     * @throws ZfExtended_Exception
     * @return string|boolean
     */
    protected function handleXmlError($opener, $tag, $key) {
        if($opener['tag'] == self::TAG_INS && $tag == self::TAG_TERM) {
            $insertStart = end($this->insStack);
            if(!empty($insertStart)) {
                $this->xml->replaceChunk($key, '</ins></'.$tag.'>'.$insertStart);
            }
            
            //save the partly corrected string
            $this->partlyCorrected = $this->xml->__toString();
            throw new editor_Models_Segment_TermTagTrackChangeStopException;
            
            return self::END_INS;
        }
        if($opener['tag'] == self::TAG_TERM && $tag == self::TAG_INS && strpos($this->xml->getAttribute($opener['attributes'], 'class', ''), 'term') !== false) {
            $insertStart = end($this->insStack);
            if(empty($insertStart)) {
                throw new ZfExtended_Exception('Missing starting insert!');
            }
            $this->xml->replaceChunk($opener['openerKey'], self::END_INS.$this->xml->getChunk($opener['openerKey']).$insertStart);
            
            //save the partly corrected string
            $this->partlyCorrected = $this->xml->__toString();
            throw new editor_Models_Segment_TermTagTrackChangeStopException;
            
            return false; //we may not leave the current term node, from the DOM viewpoint we are still in it
        }
    }
}

class editor_Models_Segment_TermTagTrackChangeStopException extends Exception {
    
}