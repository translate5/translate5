<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */


/**
 * Fileparsing for import of IBM-XLIFF files
 *
 */
class editor_Models_Import_FileParser_Xlf extends editor_Models_Import_FileParser
{
    private $ibmXliffNeedle = 'xmlns:tmgr="http://www.ibm.com"';
    private $wordCount = 0;
    private $segmentCount = 1;
    
    
    /**
     * Init tagmapping
     */
    public function __construct(string $path, string $fileName, integer $fileId, editor_Models_Task $task)
    {
        $this->addXlfTagMappings();
        parent::__construct($path, $fileName, $fileId, $task);
        
        $this->protectUnicodeSpecialChars();
    }
    
    
    /**
     * This function return the number of words of the source-part in the imported xlf-file
     * 
     * @return: (int) number of words
     */
    public function getWordCount()
    {
        return $this->wordCount;
    }
    
    /**
     * Adds the ibm-xliff specific tagmappings
     * There exist just one: "ph"
     */
    private function addXlfTagMappings()
    {
        $this->_tagMapping['hardReturn']['name'] = 'ph';
        $this->_tagMapping['softReturn']['name'] = 'ph';
        $this->_tagMapping['macReturn']['name'] = 'ph';
        
        $this->_tagMapping['ph'] = array('name' => 'DummiePH', 'text' => '&lt;DummiePH/&gt;', 'imgText' => '<DummiePH/>');
    }
    
    
    /**
     * Global parsing method.
     * Calls sub-methods to do the job.
     */
    protected function parse()
    {
        $this->_skeletonFile = $this->_origFileUnicodeProtected;
        
        if (strpos($this->_origFileUnicodeProtected, $this->ibmXliffNeedle) === false)
        {
            $msg = 'Die Datei ' . $this->_fileName . ' ist keine gültige IBM-Xliff Datei! ('.$this->ibmXliffNeedle.' nicht enthalten)';
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError($msg);
            return;
        } 
        
        // contains depth of <group>-tags
        $groupLevel = 0;
        // array, in dem die Verschachtelungstiefe der Group-Tags in Relation zu ihrer
        // Einstellung des translate-Defaults festgehalten wird
        // der Default wird auf true gesetzt
        // ??? what does this mean (in english) ???
        $translateGroupLevels = array($groupLevel - 1 => true);
        
        // TRANSLATE-284: Separation in groups does not exist in ibm-xliff. While it does not interfere, it stays here for analogie to sdl-xliff import. 
        $groups = explode('<group', $this->_origFileUnicodeProtected);
        $counterTrans = 0;
        
        foreach ($groups as &$group)
        {
            $translateGroupLevels[$groupLevel] = $translateGroupLevels[$groupLevel - 1];
            
            $units = array();
            $match_expression = "/<trans-unit(.*?)>(.*?)<\/trans-unit>/is";
            preg_match_all($match_expression, $group, $units, PREG_SET_ORDER);
            $groupLevel = $groupLevel - substr_count($units[0][0], '</group>');
            
            if (empty($units))
            {
                $groupLevel++;
                continue;
            }
            
            foreach($units as &$unit)
            {
                $translate = $translateGroupLevels[$groupLevel];
                if (preg_match('/translate="no"/i', $unit[1]))
                {
                    $translate = false;
                }
                elseif (preg_match('/translate="yes"/i', $unit[1]))
                {
                    $translate = true;
                }
                
                $groupLevel = $groupLevel - substr_count($unit[0], '</group>');
                if ($translate)
                {
                    $counterTrans++;
                    $this->setSegmentAttribs($unit);
                    $this->addupSegmentWordCount($unit);
                    $tempUnitSkeleton = $this->extractSegment($unit);
                    $this->_skeletonFile = str_replace($unit[0], $tempUnitSkeleton, $this->_skeletonFile);
                }
            }
            $groupLevel++;
        }
        
        if ($counterTrans === 0) {
            error_log('Die Datei ' . $this->_fileName . ' enthielt keine übersetzungsrelevanten Segmente!');
        }
    }
    
    
    /**
     * Sets $this->_matchRateSegment and $this->_autopropagated
     * for the segment currently worked on
     *
     * @param array transunit
     */
    protected function setSegmentAttribs($transunit)
    {
        //build mid from id of segment plus segmentCount, because xlf-file can have more than one file in it with repeatingly the same ids.
        //mid is unique per imported xliff-file
        $id = preg_replace('/.* id="(.*?)".*/i', '${1}', $transunit[1]).'_'.$this->segmentCount++;
        $matchRate = (int) preg_replace('/.*tmgr:matchratio="(.*?)".*/i', '${1}', $transunit[1]);
        
        $this->_matchRateSegment[$id] = $matchRate;
        $this->_autopropagated[$id] = false;
        $this->_lockedInFile[$id] = false;
        $this->setMid($id);
    }
    
    
    /**
     * sub-method of parse();
     * extract source- and target-segment from a trans-unit element
     * adn saves this segements into databse
     *
     * @param array $transUnit
     * @return array $transUnit contains replacement-tags <lekSourceSeg id=""/> and <lekTargetSeg id=""/>
     *          instead of the original segment content. attribut id contains the is of db-table LEK_segments
     */
    protected function extractSegment($transUnit)
    {
        $this->segmentData = array();
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        
        $temp_source = preg_replace('/.*<source.*?>(.*)<\/source>.*/is', '${1}', $transUnit[2]);
        $temp_target = preg_replace('/.*<target.*?>(.*)<\/target>.*/is', '${1}', $transUnit[2]);
        
        $this->segmentData[$sourceName] = array(
            'original' => $this->parseSegment($temp_source, true),
            'originalMd5' => md5($temp_source)
        );
        
        $this->segmentData[$targetName] = array(
            'original' => $this->parseSegment($temp_target, false),
            'originalMd5' => md5($temp_target)
        );
        $segmentId = $this->setAndSaveSegmentValues();
        $tempTargetPlaceholder = $this->getFieldPlaceholder($segmentId, $targetName);
        
        $temp_return = preg_replace('/(.*)<target(.*?)>.*<\/target>(.*)/is', '${1}<target${2}>'.$tempTargetPlaceholder.'</target>${3}', $transUnit[0]);
        
        return $temp_return;
    }
    
    /**
     * sub-method of parse();
     * detects wordcount in a trans-unit element.
     * sums up wordcount for the whole file in $this->wordCount
     * 
     * Sample of wordcount provided by a trans-unit: <count count-type="word count" unit="word">13</count>
     *
     * @param array $transUnit
     */
    protected function addupSegmentWordCount($transUnit)
    {
        $tempCount = preg_replace('/.*<count.*?count-type="word count".*?>(.*)<\/count>.*/is', '${1}', $transUnit[2]);
        $this->wordCount += $tempCount;
    }
    
    
    /**
     * sub-method of extractSegment();
     * convert ph-tags to ExtJs-compatible tags in a segment
     *
     * @param string $segment
     * @return string $segment with replaced (ExtJs-compatible) tags
     */
    protected function parseSegment($segment, $isSource)
    {
        $segment = $this->parseSegmentProtectWhitespace($segment);
        
        if (strpos($segment, '<')=== false) {
            return $segment;
        }
        $data = ZfExtended_Factory::get('editor_Models_Import_FileParser_Sdlxliff_ParseSegmentData');
        
        $data->segment = preg_split('/(<ph>.*?<\/ph>.*?)/is', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        
        $data->segmentCount = count($data->segment);
        $this->shortTagIdent = 1;
        
        foreach($data->segment as &$subsegment)
        {
            if (strpos($subsegment, "<ph>") !== false)
            {
                $tagName = "ph";
                if(!isset($this->_tagMapping[$tagName])) {
                    trigger_error('The used tag ' . $tagName .' is undefined! Segment: '.$this->_segment, E_USER_ERROR);
                }
                $temp_content = preg_replace('/<ph.*?>(.*)<\/ph>/is', '${1}', $subsegment);
                $temp_content = html_entity_decode($temp_content, ENT_QUOTES, 'utf-8');
                $this->_tagMapping[$tagName]['text'] = htmlentities($temp_content, ENT_QUOTES, 'utf-8');
                $this->_tagMapping[$tagName]['imgText'] = $temp_content;
                $fileNameHash = md5($this->_tagMapping[$tagName]['imgText']);
                
                //generate the html tag for the editor
                $p = $this->getTagParams($subsegment, $this->shortTagIdent++, $tagName, $fileNameHash);
                $tag = $this->_singleTag->getHtmlTag($p);
                $this->_singleTag->createAndSaveIfNotExists($this->_tagMapping[$tagName]['imgText'], $fileNameHash);
                $this->_tagCount++;
                
                $subsegment = $tag;
            }
            else
            {
                // Other replacement
                $search = array(
                        '#<hardReturn/>#',
                        '#<softReturn/>#',
                        '#<macReturn/>#',
                        '#<space ts="[^"]*"/>#',
                );
                
                //set data needed by $this->whitespaceTagReplacer
                $this->_segment = $subsegment;
                
                $subsegment = preg_replace_callback($search, array($this,'whitespaceTagReplacer'), $subsegment);
            }
        }
        
        return implode('', $data->segment);
    }
}
