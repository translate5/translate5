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
 */

/**
 * Enthält Methoden zum Fileparsing für den Import von IBM-XLIFF-Dateien
 *
 *
 */
class editor_Models_Import_FileParser_Xlf extends editor_Models_Import_FileParser {
    /**
     * @var array mappt alle Tag-Referenzen im Header der sdlxliff-Datei innerhalb von
     *      <tag-defs><tag></tag></tag-defs> auf die Tags in Segmenten des sdlxliff
     *      die auf sie verweisen. Die Referenz ist gemäß der sdlxliff-Logik
     *      immer der firstchild von tag
     *      wird auch zur Prüfung verwendet, ob in dem Segmenten oder im Header
     *      Tagnamen verwendet werden, die von diesem sdlxliff-Fileparser nicht
     *      berücksichtigt werden
     */
    protected $_tagDefMapping = array(
        //'ph' => 'ph', 
        'unicodePrivateUseArea' => 'unicodePrivateUseArea', 
        'hardReturn' => 'hardReturn',
        'softReturn' => 'softReturn',
        'macReturn' => 'macReturn',
        'space' => 'space'
        );
    
    /**
     * @var string Zeichenketten für das Bodyparsing durch parseFile vorbereitet mit protectUnicodeSpecialChars
     */
    //protected $_origFileUnicodeProtected = NULL;
    
    /**
     * @var string Zeichenketten für das tag-Parsing durch prepareTagMapping vorbereitet mit protectUnicodeSpecialChars
     */
    //protected $_origFileUnicodeSpecialCharsRemoved = NULL;

    /**
     * Initiert Tagmapping
     */
    public function __construct(string $path, string $fileName, integer $fileId, boolean $edit100PercentMatches, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task) {
        //echo ".. lala editor_Models_Import_FileParser_Xlf"; exit;
        //add XLF tagMapping
        $this->addXlfTagMappings();
        //echo '.. $path: '.$path."<br />\n";
        //echo '.. $task: '.print_r(json_decode($task));
        parent::__construct($path, $fileName, $fileId, $edit100PercentMatches, $sourceLang, $targetLang, $task);
        //echo "Datei: ".$this->_origFile;
        //$this->checkForSdlChangeMarker(); // TRANSLATE-284 SBE: kann entfernt werden !!??!!
        $this->removeEmtpyXmlns();
        $this->protectUnicodeSpecialChars();
        //$this->prepareTagMapping();
        //echo "<br />\n<br />\n.. fin"; exit;
    }

    /**
     * Adds the sdlxliff specific tagmappings
     * Mapping von tagId zu Name und anzuzeigendem Text fuer den Nutzer
     *
     * - kann in der Klassenvar-Def. bereits Inhalte enthalten, die für spezielle
     *   Zwecke benötigt werden und nicht dynamisch aus der sdlxliff-Datei kommen.
     *
     *   Beispiel bpt:
     *   [1192]=>
     *    array(6) {
     *      ["name"]=>
     *      string(3) "bpt"
     *      ["text"]=>
     *      string(44) "&lt;cf style=&quot;z_AS_disclaimer&quot;&gt;"
     *      ["imgText"]=>
     *      string(28) "<cf style="z_AS_disclaimer">"
     *      ["eptName"]=>
     *      string(3) "ept"
     *      ["eptText"]=>
     *      string(11) "&lt;/cf&gt;"
     *      ["imgEptText"]=>
     *      string(5) "</cf>"
     *    }
     *   Beispiel ph:
     *    [0]=>
     *     array(3) {
     *       ["name"]=>
     *       string(2) "ph"
     *       ["text"]=>
     *       string(58) "&lt;format type=&quot;&amp;lt;fullPara/&amp;gt;&quot;/&gt;"
     *       ["imgText"]=>
     *       string(34) "<format type="&lt;fullPara/&gt;"/>"
     *     }
     * @var array array('tagId' => array('name' => string '', 'text' => string '','imgText' => string '', ['eptName' => string '', 'eptText' => string '','imgEptText' => string '']),'tagId2' => ...)
     */
    private function addXlfTagMappings() {
        
        $this->_tagMapping['hardReturn']['name'] = 'ph';
        $this->_tagMapping['softReturn']['name'] = 'ph';
        $this->_tagMapping['macReturn']['name'] = 'ph';
        
        // SBE: deaktiviert da in IBM-XLIF nicht vorhanden ???
        //$this->_tagMapping['unicodePrivateUseArea'] = array('name' => 'ph', 'text' => '&lt;SpecialChar/&gt;', 'imgText' => '<SpecialChar/>');
        //$this->_tagMapping['mrkSingle'] = array('name' => 'ph', 'text' => '&lt;InternalReference/&gt;', 'imgText' => '<InternalReference/>');
        //$this->_tagMapping['mrkPaired'] = array('name' => 'bpt', 'text' => '&lt;InternalReference&gt;', 'imgText' => '<InternalReference>', 'eptName'=>'ept', 'eptText'=>'&lt;/InternalReference&gt;', 'imgEptText'=>'</InternalReference>');
        
        //$this->_tagMapping['ph'] = array('name' => 'bpt', 'text' => '&lt;lala_1/&gt;', 'imgText' => '<lala_2/>', 'eptName' => 'ept', 'eptText' => '&lt;/lala_3&gt;', 'imgEptText' => '</lala_4>');
        $this->_tagMapping['ph'] = array('name' => 'DummiePH', 'text' => '&lt;DummiePH/&gt;', 'imgText' => '<DummiePH/>');
        //$this->_tagMapping['ph']['name'] = 'ph';
    }
    
    /**
     * Entfernt vom TermTagger eingefügte leerer xmlns-Attribute
     */
    protected function removeEmtpyXmlns() {
        $this->_origFile = preg_replace('"(\s*)xmlns=\"\"\s*"s', '\\1', $this->_origFile);
    }
    
    
    /**
     * Das Leerzeichen (U+0020)
     * Schützt Zeichenketten, die im sdlxliff enthalten sind und aus einer
     * Unicode Private Use Area oder bestimmten schutzwürdigen Whitespaces oder
     * von mssql nicht verkrafteten Zeichen stammen mit einem Tag
     *
     */
    protected function protectUnicodeSpecialChars() {
        $this->_origFileUnicodeProtected = preg_replace_callback(
                array('"\p{Co}"u', //Alle private use chars
            '"\x{2028}"u', //Hex UTF-8 bytes or codepoint 	E2 80 A8//schutzbedürftiger Whitespace + von mssql nicht vertragen
            '"\x{2029}"u', //Hex UTF-8 bytes 	E2 80 A9//schutzbedürftiger Whitespace + von mssql nicht vertragen
            '"\x{201E}"u', //Hex UTF-8 bytes 	E2 80 9E //von mssql nicht vertragen
            '"\x{201C}"u'), //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
                function ($match) {
                    return '<unicodePrivateUseArea ts="' . implode(',', unpack('H*', $match[0])) . '"/>';
                }, $this->_origFile);
        $this->_origFileUnicodeSpecialCharsRemoved = preg_replace_callback(
                array('"\p{Co}"u', //Alle private use chars
            '"\x{2028}"u', //Hex UTF-8 bytes 	E2 80 A8//schutzbedürftiger Whitespace + von mssql nicht vertragen
            '"\x{2029}"u', //Hex UTF-8 bytes 	E2 80 A9//schutzbedürftiger Whitespace + von mssql nicht vertragen
            '"\x{201E}"u', //Hex UTF-8 bytes 	E2 80 9E //von mssql nicht vertragen
            '"\x{201C}"u'), //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
                function ($match) {
                    return '';
                }, $this->_origFile);
    }

    /**
     * übernimmt das eigentliche FileParsing
     *
     * - ruft untergeordnete Methoden für das Fileparsing auf, wie extractSegment, setSegmentAttribs
     */
    protected function parse()
    {
        $this->_skeletonFile = $this->_origFileUnicodeProtected;
        
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
            
            if (!empty($units))
            {
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
     * Konvertiert in einem Segment (bereits ohne umschließende Tags) die Tags für ExtJs
     *
     * - die id des <div>-Tags, der als Container-Tag für das JS zurückgeliefert wird,
     *   wird - so gesetzt - als Speichercontainer für Inhalte verwendet, die für
     *   diesen Tag für die Rückkonvertierung geschützt werden müssen. So z. B.
     *   der Wert des mid-Attributs eines ein Subsegment referenzierenden mrk-Tags
     *   Achtung: Hier dürfen aber nur Werte übergeben werden, die unkritisch sind
     *   hinsichtlich potentieller Zerstörung im Browser - also z. B. GUIDs (die rein
     *   alphanumerisch sind), aber keine Freitexte.
     * - die id des innerhalb des <div>-Tags liegenden span-Tags dient als Referenz-ID
     *   für die Rückkonvertierung und den Bezug zu den tagMappings im sdlxliff-header
     *
     * @param string $segment
     * @param boolean isSource
     * @return string $segment enthält anstelle der Tags die vom JS benötigten Replacement-Tags
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    protected function parseSegment($segment,$isSource)
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
                $this->_tagMapping[$tagName]['text'] = $temp_content;
                $fileNameHash = md5($this->_tagMapping[$tagName]['imgText']);
                
                //generate the html tag for the editor
                //$p = $this->getTagParams($tag, $this->shortTagIdent++, $tagName, $fileNameHash);
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
    
    
    /**
     * callback for replace method in parseSegment
     * @param array $match
     * @return string
     */
    protected function whitespaceTagReplacer(array $match) {
        //$replacer = function($match) use ($segment, $shortTagIdent, $map) {
        $tag = $match[0];
        $tagName = preg_replace('"<([^/ ]*).*>"', '\\1', $tag);
        if(!isset($this->_tagMapping[$tagName])) {
            trigger_error('The used tag ' . $tagName .' is undefined! Segment: '.$this->_segment, E_USER_ERROR);
        }
        $fileNameHash = md5($this->_tagMapping[$tagName]['imgText']);

        //generate the html tag for the editor
        $p = $this->getTagParams($tag, $this->shortTagIdent++, $tagName, $fileNameHash);
        $tag = $this->_singleTag->getHtmlTag($p);
        $this->_singleTag->createAndSaveIfNotExists($this->_tagMapping[$tagName]['imgText'], $fileNameHash);
        $this->_tagCount++;
        return $tag;
    }
    
    
    protected function parseSegment_BACKUP($segment,$isSource)
    {
        $segment = $this->parseSegmentProtectWhitespace($segment);
        if (strpos($segment, '<')=== false) {
            return $segment;
        }
        $data = new editor_Models_Import_FileParser_Sdlxliff_ParseSegmentData();
        $data->segment = preg_split('"(<[^>]*>)"', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $data->segmentCount = count($data->segment);
        $openCountInTerm = 0;
        print_r($data);// exit;
        
        //parse nur die ungeraden Arrayelemente, den dies sind die Rückgaben von PREG_SPLIT_DELIM_CAPTURE
        for ($data->i = 1; $data->i < $data->segmentCount; $data->i++) {
            if (preg_match('"^<[^/].*[^/]>$"', $data->segment[$data->i]) > 0) {//öffnender Tag (left-tag)
                if (strpos($data->segment[$data->i], 'mtype="x-term')!== false) {//der öffnende Tag leitet einen Term ein
                    //per Definition erzeugt der Term-Tagger keinen Term innerhalb eines bereits
                    //ausgezeichneten Terms. Auch müssen alle innerhalb eines
                    //ausgezeichneten Terms geöffneten Tags innerhalb dieses Terms
                    //wieder geschlossen werden
                    $openCountInTerm++;
                    $data->currentTermIndex = $data->i;
                }
                else {
                    $data = $this->parseLeftTag($data);
                    if(!is_null($data->currentTermIndex))$openCountInTerm++;
                }
            } elseif (preg_match('"^</"', $data->segment[$data->i]) > 0) {//schließender Tag (right-tag)
                if($openCountInTerm === 1 and $data->segment[$data->i] === '</mrk>' and !is_null($data->currentTermIndex)){
                    $data = $this->parseSegmentReplaceTermSlicesByHtmlTermString($data,$isSource);
                    $openCountInTerm = 0;
                    $data->currentTermIndex = NULL;
                }
                else{
                    if(!is_null($data->currentTermIndex)){
                        $openCountInTerm--;
                    }
                    $data = $this->parseRightTag($data);
                }
            } else {//in sich geschlossener Tag (single-tag)
                //echo "<br />\nparseSingleTag()<br />\n"; print_r($data); // SBE:
                $data = $this->parseSingleTag($data);
            }
            $data->i++; //parse nur die ungeraden Arrayelemente, den dies sind die Rückgaben von PREG_SPLIT_DELIM_CAPTURE
            $this->_tagCount++;
        }
        //print_r($data); exit;
        return implode('', $data->segment);
    }
    
    
    /**
     * protects whitespace inside a segment with a tag
     *
     * @param string $segment
     * @param integer $count optional, variable passed by reference stores the replacement count
     * @return string $segment
     */
    protected function parseSegmentProtectWhitespace($segment, &$count = 0) {
        $segment = parent::parseSegmentProtectWhitespace($segment, $count);
        $res = preg_replace_callback(
                array(
                    '"\x{0009}"u', //Hex UTF-8 bytes or codepoint of horizontal tab
                    '"\x{000B}"u', //Hex UTF-8 bytes or codepoint of vertical tab
                    '"\x{000C}"u', //Hex UTF-8 bytes or codepoint of page feed
                    '"\x{0085}"u', //Hex UTF-8 bytes or codepoint of control sign for next line
                    '"\x{00A0}"u', //Hex UTF-8 bytes or codepoint of protected space
                    '"\x{1680}"u', //Hex UTF-8 bytes or codepoint of Ogam space
                    '"\x{180E}"u', //Hex UTF-8 bytes or codepoint of mongol vocal divider
                    '"\x{202F}"u', //Hex UTF-8 bytes or codepoint of small protected space
                    '"\x{205F}"u', //Hex UTF-8 bytes or codepoint of middle mathematical space
                    '"\x{3000}"u', //Hex UTF-8 bytes or codepoint of ideographic space
                    '"[\x{2000}-\x{200A}]"u', //Hex UTF-8 bytes or codepoint of eleven different small spaces, Haarspatium and em space
                    ), //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
                        function ($match) {
                            return '<space ts="' . implode(',', unpack('H*', $match[0])) . '"/>';
                        }, 
            $segment, -1, $replaceCount);
        $count += $replaceCount;
        return $res;
    }
    
    
    /**
     * parsing von left-Tags für parseSegment (öffnenden Tags)
     *
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     * @return editor_Models_Import_FileParser_Sdlxliff_parseSegmentData  $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function parseLeftTag($data)
    {
        $tag = &$data->segment[$data->i];
        $data->openCounter++;
        $tagName = preg_replace('"<([^ ]*).*>"', '\\1', $tag);
        $this->verifyTagName($tagName, $data);
        $tagId = $this->parseSegmentGetTagId($tag, $tagName);
        $shortTagIdent = $data->j;
        if (strpos($tagId, 'locked')!== false) {
            trigger_error('Der öffnende Tag ' . $tagName .
                    ' enthielt die tagId ' . $tagId . ', was lt. bisherigen
                            Erfahrungen durch sdlxliff nicht vorgesehen ist.
                            Das betroffene Segment war: ' .
                    implode('', $data->segment), E_USER_ERROR);
        }
        $fileNameHash = md5($this->_tagMapping[$tagId]['imgText']);
        $data->openTags[$data->openCounter]['tagName'] = $tagName;
        $data->openTags[$data->openCounter]['tagId'] = $tagId;
        $data->openTags[$data->openCounter]['nr'] = $data->j;
        
        //ersetzte gegen Tag für die Anzeige
        $p = $this->getTagParams($tag, $shortTagIdent, $tagId, $fileNameHash);
        $tag = $this->_leftTag->getHtmlTag($p);
        
        $this->_leftTag->createAndSaveIfNotExists($this->_tagMapping[$tagId]['imgText'], $fileNameHash);
        $data->j++;
        
        echo "parseLeftTag()<br >/\n";
        print_r($data);
        echo "<br >/\n";
        
        return $data;
    }

    /**
     * parsing von right-Tags für parseSegment (schließenden Tags)
     *
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     * @return editor_Models_Import_FileParser_Sdlxliff_parseSegmentData  $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function parseRightTag($data) {
        if(empty($data->openTags[$data->openCounter])){
            trigger_error('Schließender Tag ohne einen öffnenden gefunden. Aktuelle Segment Mid: '.$this->_mid.'. Aktueller Term-Tag:  ' .join('', $data->segment), E_USER_ERROR);
        }
        $openTag = $data->openTags[$data->openCounter];
        $mappedTag = $this->_tagMapping[$openTag['tagId']];
        $fileNameHash = md5($mappedTag['imgEptText']);
        
        //generate the html tag for the editor
        $p = $this->getTagParams($data->segment[$data->i], $openTag['nr'], $openTag['tagId'], $fileNameHash, $mappedTag['eptText']);
        $data->segment[$data->i] = $this->_rightTag->getHtmlTag($p);
        
        $this->_rightTag->createAndSaveIfNotExists($mappedTag['imgEptText'], $fileNameHash);
        $data->openCounter--;
        
        echo "parseRightTag()<br >/\n";
        print_r($data);
        echo "<br >/\n";
        
        return $data;
    }

    /**
     * parsing von left-Tags für parseSegment (öffnenden Tags)
     *
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     * @return editor_Models_Import_FileParser_Sdlxliff_parseSegmentData  $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function parseSingleTag($data) {
        $tag = &$data->segment[$data->i];
        $tagName = preg_replace('"<([^/ ]*).*>"', '\\1', $tag);
        $this->verifyTagName($tagName, $data);
        $tagId = $this->parseSegmentGetTagId($tag, $tagName);
        $shortTagIdent = $data->j;
        $locked = false;
        if (strpos($tagId, 'locked')!== false) {
            //$this->setLockedTagContent($tag, $tagId);
            $shortTagIdent = 'locked' . $data->j;
            $locked = true;
        }
        $fileNameHash = md5($this->_tagMapping[$tagId]['imgText']);
        
        //generate the html tag for the editor
        $p = $this->getTagParams($tag, $shortTagIdent, $tagId, $fileNameHash);
        $tag = $this->_singleTag->getHtmlTag($p);

        $this->_singleTag->createAndSaveIfNotExists($this->_tagMapping[$tagId]['imgText'], $fileNameHash);
        $data->j++;
        return $data;
    }
    
    /**
     * Hilfsfunktion für parseSegment: Festlegung der tagId im JS
     *
     * @param string $tag enthält den Tag als String
     * @param string $tagName enthält den Tagnamen
     * @return string $id ID des Tags im JS
     */
    protected function parseSegmentGetTagId($tag, $tagName) {
        if ($tagName == 'unicodePrivateUseArea'||$tagName == 'hardReturn'||$tagName == 'softReturn'||$tagName == 'macReturn'||$tagName == 'space'||$tagName == 'ph') {
            return $tagName;
        }
        if ($tagName == 'mrk') {
            if(preg_match('"<mrk [^>]*[^/]>"', $tag)){
                return 'mrkPaired';
            }
            return 'mrkSingle';
        }
        return preg_replace('"<.* id=\"([^\"]*)\".*>"', '\\1', $tag);
    }
    
    
    /**
     * prüft, ob ein Tagname $this->_tagDefMapping definiert ist
     *
     * @param string $tagName
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function verifyTagName($tagName,$data) {
         if (!in_array($tagName, $this->_tagDefMapping)) {
            trigger_error('Der Tag ' . $tagName .
                    ' kam im folgenden Segment vor, ist aber nicht definiert: ' .
                    implode('', $data->segment), E_USER_ERROR);
        }
    }
    
    
    
    /**
     * ersetzt alle Array-Indizes von $data->segment, die sich innerhalb des Term-Tags
     * befinden, der mit $data->segment[$data->currentTermIndex] startet durch einen
     * einzigen Index, der bereits den gesamten Termtag mit allen in ihm enthaltenen
     * normalen Tags enthält
     *
     * - vorausgesetzt wird strpos($data->segment[$data->i], 'mtype="x-term') !== false
     * - befüllt auch $this->_terms2save mit mit editor_Models_TermTagData-Objekten als Werten
     *   mittels $this->getTermTagDataWhileParsingSegment()
     *
     * @param editor_Models_Import_FileParser_Sdlxliff_ParseSegmentData $data
     * @param boolean isSource
     * @return editor_Models_Import_FileParser_Sdlxliff_ParseSegmentData $data
     */
    protected function parseSegmentReplaceTermSlicesByHtmlTermString($data,$isSource) {
        $termTagData = $this->getTermTagDataWhileParsingSegment($data, $isSource);
        $term = array();
        $termStart = $data->currentTermIndex+1;
        $openCount = 0;//counts open term-tags
        for ($k = $termStart; $k < $data->segmentCount; $k++) {
            $term[] = $data->segment[$k];//füge Text hinzu
            unset($data->segment[$k]);
            $k++; //gehe zum nächsten Tag
            if(strpos($data->segment[$k], '<mrk') !== false){
                if(strpos($data->segment[$k], 'x-term') !== false){
                    trigger_error('Term-Tag inside of Term-Tag - this is not supported so far' .
                            $data->segment[$data->currentTermIndex], E_USER_ERROR);
                }
                $openCount++;
            } elseif ($data->segment[$k]=== '</mrk>') {
                if($openCount == 0){
                    unset($data->segment[$k]);
                    $termTagData->term = implode('', $term);
                    $this->_terms2save[] = $termTagData;
                    $data->segment[$data->currentTermIndex] = $this->segmentTermTag->getGeneratedTermTag($termTagData);
                    return $data;
                }
                $openCount--;
            }
            $term[] = $data->segment[$k];//füge hinzu
            unset($data->segment[$k]);
        }
        trigger_error('kein schließender </mrk>-Tag gefunden. Aktueller Term-Tag:  ' .
                            $data->segment[$data->currentTermIndex], E_USER_ERROR);
    }

    /**
     * Befüllt ein editor_Models_TermTagData-Objekt abgesehen von ->term
     * @param editor_Models_Import_FileParser_Sdlxliff_ParseSegmentData $data
     * @param boolean isSource
     * @return editor_Models_TermTagData $termTagData
     */
    protected function getTermTagDataWhileParsingSegment($data,$isSource) {
         $tag = $data->segment[$data->currentTermIndex];
         $termTagData = new editor_Models_TermTagData();
         $termTagData->mid = preg_replace('"<.* mid=\"([^\"]*)\".*>"', '\\1', $tag);
         $termTagData->transFound = (strpos($tag, 'transNotFound')!== false?false:true);
         $termTagData->status = preg_replace('"<.* mtype=\"x-term-([^\"-]*)-trans[^\"]*\".*>"', '\\1', $tag);
         $termTagData->used = true;
         $termTagData->projectTerminstanceId = $this->_projectTerminstanceId;
         $this->_projectTerminstanceId++;
         $termTagData->isSource = $isSource;
         if(!isset($this->_terms[$termTagData->mid])){
             $term = ZfExtended_Factory::get('editor_Models_Term');
             /* @var $term editor_Models_Term */
             $s = $term->db->select();
             $s->where('mid = ?',$termTagData->mid)
               ->where('taskGuid = ?',$this->_taskGuid);
             $term->loadRowBySelect($s);
             $this->_terms[$termTagData->mid] = $term;
         }
         $termTagData->id = $this->_terms[$termTagData->mid]->getId();
         $termTagData->definition = $this->_terms[$termTagData->mid]->getDefinition();
         return $termTagData;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getTagParams()
     */
    protected function getTagParams($tag, $shortTag, $tagId, $fileNameHash, $text = false) {
        $data = parent::getTagParams($tag, $shortTag, $tagId, $fileNameHash, $text);
        $data['text'] = $this->encodeTagsForDisplay($data['text']);
        return $data;
    }
    
    /**
     * encodes special chars to entities for display in title-Attributs and text of tags in the segments
     * because studio sometimes writes tags in the description of tags (i.e. in locked tags)
     *
     * @param string text
     * @return string text
     */
    protected function encodeTagsForDisplay($text) {
        return str_replace(array('"',"'",'<','>'),array('&quot;','&#39;','&lt;','&gt;'),$text);
    }

}
