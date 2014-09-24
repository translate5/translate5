<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/* TODO: !!!
 * 
 * - Prüfung ob XLF-Datei dem Namespace der IBM-XLIFF entspricht, da evtl, andere Formate mit selber Dateiendung geladen werden können.
 *   ??? test ob folgende Zeichenkette enthalten: xmlns="urn:oasis:names:tc:xliff:document:1.1" ???
 *   
 *   => ??? wie Import abbrechen ???
 *      exit; geht nicht, da ja bei einer ZIP-Verabreitung die restlichen Dateien nicht berücksichtigt werden.
 *      return in parse() stoppt lediglich den Import der Segmente. Die Datei und damit der Task bleibt trotzdem bestehen.
 * 
 * - was mit in XLF-Datei enhaltenen Word-Counts machen ???
 * 
 */

/**
 * Enthält Methoden zum Fileparsing für den Import von IBM-XLIFF-Dateien
 *
 *
 */
class editor_Models_Import_FileParser_Xlf extends editor_Models_Import_FileParser
{
    private $ibmXliffNeedle = 'xmlns="urn:oasis:names:tc:xliff:document:1.1" xmlns:tmgr="http://www.ibm.com"';
    
    
    /**
     * Initiert Tagmapping
     */
    public function __construct(string $path, string $fileName, integer $fileId, boolean $edit100PercentMatches, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task)
    {
        $this->addXlfTagMappings();
        parent::__construct($path, $fileName, $fileId, $edit100PercentMatches, $sourceLang, $targetLang, $task);
        
        $this->removeEmtpyXmlns();
        $this->protectUnicodeSpecialChars();
    }

    /**
     * Adds the ibm-xlif specific tagmappings
     * 
     * 
     */
    private function addXlfTagMappings()
    {
        $this->_tagMapping['hardReturn']['name'] = 'ph';
        $this->_tagMapping['softReturn']['name'] = 'ph';
        $this->_tagMapping['macReturn']['name'] = 'ph';
        
        $this->_tagMapping['ph'] = array('name' => 'DummiePH', 'text' => '&lt;DummiePH/&gt;', 'imgText' => '<DummiePH/>');
    }
    
    
    /**
     * übernimmt das eigentliche FileParsing
     *
     * - ruft untergeordnete Methoden für das Fileparsing auf, wie extractSegment, setSegmentAttribs
     */
    protected function parse()
    {
        $this->_skeletonFile = $this->_origFileUnicodeProtected;
        
        if (strpos($this->_origFileUnicodeProtected, $this->ibmXliffNeedle) === false)
        {
            error_log('Die Datei ' . $this->_fileName . ' ist keine gültige IBM-Xliff Datei! ('.$this->ibmXliffNeedle.' nicht enthalten)');
            return;
        } 
        
        //gibt die Verschachtelungstiefe der <group>-Tags an
        $groupLevel = 0;
        //array, in dem die Verschachtelungstiefe der Group-Tags in Relation zu ihrer
        //Einstellung des translate-Defaults festgehalten wird
        //der Default wird auf true gesetzt
        $translateGroupLevels = array($groupLevel - 1 => true);
        
        // TRANSLATE-284 SBE: Unterteilung in Gruppen bei IBM-XLIFF eigentlich nicht vorhanden, da es aber nicht stört verbleibt es hier.
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
                //print_r($unit); exit;
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
                    $tempUnitSkeleton = $this->extractSegment($unit);
                    $this->_skeletonFile = str_replace($unit[0], $tempUnitSkeleton, $this->_skeletonFile);
                }
            }
            $groupLevel++;
        }
        
        if ($counterTrans === 0) {
            error_log('Die Datei ' . $this->_fileName . ' enthielt keine übersetzungsrelevanten Segmente!');
        }
        
        //echo "Import-Skeleton: ".$this->_skeletonFile; exit;
    }
    
    
    /**
     * Sets $this->_editSegment, $this->_matchRateSegment and $this->_autopropagated
     * and $this->_pretransSegment and $this->_autoStateId for the segment currently worked on
     *
     * @param array transunit
     */
    protected function setSegmentAttribs($transunit)
    {
        $id = preg_replace('/.*id="(.*?)".*/i', '${1}', $transunit[1]);
        $matchRate = (int) preg_replace('/.*tmgr:matchratio="(.*?)".*/i', '${1}', $transunit[1]);
        $matchInfo = preg_replace('/.*tmgr:matchinfo="(.*?)".*/i', '${1}', $transunit[1]);
        //echo ".. ID: ".$id."<br />\n.. matchRate: ".$matchRate."<br />\n.. matchInfo: ".$matchInfo."<br />\n";
        
        $this->_matchRateSegment[$id] = $matchRate;
        $this->_autopropagated[$id] = strpos($matchInfo, 'AUTOSUBST')!==false;
        $this->setMid($id);
    }
    
    
    /**
     * Extrahiert aus einem durch parseFile erzeugten Code-Schnipsel mit genau einer trans-unit Quell-
     * und Zielsegmente
     *
     * - speichert die Segmente in der Datenbank
     * @param array $transUnit
     * TODO:
     * @return array $transUnit enthält anstelle der Segmente die Replacement-Tags <lekSourceSeg id=""/> und <lekTargetSeg id=""/>
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    protected function extractSegment($transUnit)
    {
        //print_r($transUnit[2]); exit;
        $this->segmentData = array();
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        
        $temp_source = preg_replace('/.*<source.*?>(.*)<\/source>.*/is', '${1}', $transUnit[2]);
        $temp_target = preg_replace('/.*<target.*?>(.*)<\/target>.*/is', '${1}', $transUnit[2]);
        
        //echo ("Source: ".$temp_source."<br />\n<br />\n<br />\nTarget: ".$temp_target."<br >/\n<br />\n<br />\n"); exit;
        
        $this->segmentData[$sourceName] = array(
                'original' => $this->parseSegment($temp_source, true),
                'originalMd5' => md5($temp_source)
        );
        
        $this->segmentData[$targetName] = array(
                'original' => $this->parseSegment($temp_target, false),
                'originalMd5' => md5($temp_target)
        );
        $segmentId = $this->setAndSaveSegmentValues();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        $tempTargetPlaceholder = $this->getFieldPlaceholder($segmentId, $targetName);
        //echo "Placeholder: ".$tempTargetPlaceholder."<br />\n";
        
        $temp_return = preg_replace('/(.*)<target(.*?)>.*<\/target>(.*)/is', '${1}<target${2}>'.$tempTargetPlaceholder.'</target>${3}', $transUnit[0]);
        //echo "Return: ".$temp_return."<br />\n";
        
        //print_r($transUnit); exit;
        
        
        return $temp_return;
    }
    
    
    /**
     * Konvertiert in einem Segment (bereits ohne umschließende Tags) die PH-Tags für ExtJs
     *
     *
     * @param string $segment
     * @return string $segment enthält anstelle der Tags die vom JS benötigten Replacement-Tags
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    protected function parseSegment($segment, $isSource)
    {
        $segment = $this->parseSegmentProtectWhitespace($segment);
        //echo "Segment: ".$segment."<br />\n";
        if (strpos($segment, '<')=== false) {
            return $segment;
        }
        $data = new editor_Models_Import_FileParser_Sdlxliff_ParseSegmentData();
        
        /*$data->segment = preg_split('/(<.*?>)/is', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);*/
        $data->segment = preg_split('/(<ph>.*?<\/ph>.*?)/is', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        
        $data->segmentCount = count($data->segment);
        //$openCountInTerm = 0;
        $this->shortTagIdent = 1;
        
        foreach($data->segment as &$subsegment)
        {
            //echo ".. Subsegment: ".$subsegment."<br />\n";
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
                //echo ".. Subsegment ersetzt: ".$subsegment."<br />\n";
            }
            else
            {
                // Other replacement
                $search = array(
                        '#<hardReturn />#',
                        '#<softReturn />#',
                        '#<macReturn />#',
                        '#<space ts="[^"]*"/>#',
                );
                
                //set data needed by $this->whitespaceTagReplacer
                $this->_segment = $subsegment;
                
                $subsegment = preg_replace_callback($search, array($this,'whitespaceTagReplacer'), $subsegment);
            }
        }
        
        //print_r($data); // exit;
        return implode('', $data->segment);
    }
}
