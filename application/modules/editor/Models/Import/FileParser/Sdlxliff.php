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
 * Enthält Methoden zum Fileparsing für den Import von Sdlxliff-Dateien
 *
 * - Der Algorithmus geht davon aus, dass bereits mindestens ein Segment in Studio
 *   übersetzt wurde, denn dann sind innerhalb der <target>- und <seg-source>-Segmente
 *   <mrk>-Tags vorhanden - sonst nicht. Der Algorithmus geht davon aus, dass
 *   alle zu übersetzenden und übersetzten Inhalte immer innerhalb von <mrk>-Tags
 *   enthalten sind. Falls kein zu übersetzendes Segment
 *   gefunden wurde, wird ein Fehler geworfen und der Import bricht ab.
 * - Darüber hinaus get der Algorithmus davon aus, dass die Datei xliff 1.2 entspricht,
 *   inbesondere hinsichtlich der Verschachtelung von group und trans-unit-Tags sowie des
 *   translate-Attributs von group und trans-unit-Tags
 * - Ergebnis eines Performancetests einer Datei mit 9500 Segmenten, davon
 *   3001 zu übersetzende mit Inhalt im Source ergab (ohne Parsing der Tags) 2 min 44 sek
 *   Parsingzeit für den implementierten objektorientierten Algorithmus, 2 min 38 sek
 *   für mysqli mit prepared statements und 2 min 30 sek für mysqli::multi_query.
 *   Aufgrund des nur geringen zeitlichen Vorteils wurde sich für die db-neutrale
 *   ZF-basierte und objektorientierte Lösung mit 2 min 44 sek entschieden
 *   Ein Test mit der objektorientierten Lösung und auf Basis von DOM umgesetzten Tag-
 *   parsings im Header der Datei und inkl. img-Generierung dauerte 5 min 27 sek,
 *   wobei das Parsing des Tag-Headers davon ca. 2 min 20 sek in Anspruch nahm. Ohne
 *   Generierung der Tags (sprich die Tags waren alle schon da) dauerte es 5 min 18 sek
 *
 */
class editor_Models_Import_FileParser_Sdlxliff extends editor_Models_Import_FileParser {
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
        'bpt' => 'g', 
        'ph' => 'x', 
        'st' => 'x', 
        'unicodePrivateUseArea' => 'unicodePrivateUseArea', 
        'mrk' => 'mrk',
        'hardReturn' => 'hardReturn',
        'softReturn' => 'softReturn',
        'macReturn' => 'macReturn',
        'space' => 'space'
        );
    
    /**
     * @var string Zeichenketten für das Bodyparsing durch parseFile vorbereitet mit protectUnicodeSpecialChars
     */
    protected $_origFileUnicodeProtected = NULL;
    
    /**
     * @var string Zeichenketten für das tag-Parsing durch prepareTagMapping vorbereitet mit protectUnicodeSpecialChars
     */
    protected $_origFileUnicodeSpecialCharsRemoved = NULL;

    /**
     * Initiert Tagmapping
     */
    public function __construct(string $path, string $fileName, integer $fileId, editor_Models_Task $task) {
        //add sdlxliff tagMapping
        $this->addSldxliffTagMappings();
        parent::__construct($path, $fileName, $fileId, $task);
        $this->checkForSdlChangeMarker();
        $this->protectUnicodeSpecialChars();
        $this->prepareTagMapping();
        
        //here would be the right place to set the import map, 
        // since our values base on sdlxliff values, 
        // nothing has to be done here at the moment
        //$this->matchRateType->setImportMap($map);
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
    private function addSldxliffTagMappings() {
        $this->_tagMapping['hardReturn']['name'] = 'ph';
        $this->_tagMapping['softReturn']['name'] = 'ph';
        $this->_tagMapping['macReturn']['name'] = 'ph';
        $this->_tagMapping['hardReturn']['name'] = 'ph';
        $this->_tagMapping['unicodePrivateUseArea'] = array('name' => 'ph', 'text' => '&lt;SpecialChar/&gt;', 'imgText' => '<SpecialChar/>');
        $this->_tagMapping['mrkSingle'] = array('name' => 'ph', 'text' => '&lt;InternalReference/&gt;', 'imgText' => '<InternalReference/>');
        $this->_tagMapping['mrkPaired'] = array('name' => 'bpt', 'text' => '&lt;InternalReference&gt;', 'imgText' => '<InternalReference>','eptName'=>'ept','eptText'=>'&lt;/InternalReference&gt;','imgEptText'  => '</InternalReference>');
    }
    
    /**
     * Checks, if there are any change-markers in the sdlxliff. If yes, triggers an error
     */
    protected function checkForSdlChangeMarker() {
        if (strpos($this->_origFile, 'mtype="x-sdl-added"')!== false or 
                strpos($this->_origFile, 'mtype="x-sdl-deleted"')!== false or 
                strpos($this->_origFile, '<rev-defs>')!== false) {
            trigger_error('There are change Markers in the sdlxliff-file "'.
                    $this->_fileName.' Task: '.$this->task->getTaskName().' ('.$this->task->getTaskGuid().') '.
                    '". Please clear them first and then try to check in the file again.',
                    E_USER_ERROR);
        }
    }
    
    /**
     * Setzt $this->_tagMapping[$tagId]['imgText'] und $this->_tagMapping[$tagId]['text']
     * bei Tags, die auf einen gesperrten Text verweisen
     *
     * Beispiel für eine transunit, in der der gesperrte Text enthalten ist und
     * auf die verwiesen wird:
     *
     * <trans-unit id="lockTU_14067931-b56a-45f6-a7f7-ccbef74442be" translate="no" sdl:locktype="Manual">
     * <source>; Schälfestigkeit 17 N/cm</source>
     * </trans-unit>
     *
     * Berücksichtigte Flexibilität in dieser Transunit: Leerer Source-Tag (<source/>) und <source>-Tag mit weiteren Attributen
     *
     * @param string tag
     * @param string tagId Id des im Param tag übergebenen Tags
     *
     */
    protected function setLockedTagContent($tag, $tagId) {
        if (strstr($tag, 'xid=')=== false) {
            trigger_error('Locked-Tag-Inhalt wurde angefordert, aber Tag enthält keine xid', E_USER_ERROR);
        }
        $xid = preg_replace('"<.* xid=\"([^\"]*)\".*>"', '\\1', $tag);
        $xid = preg_replace('"<.* xid=\"([^\"]*)\".*>"', '\\1', $tag);
        $split = explode('id="' . $xid, $this->_origFileUnicodeProtected);
        $content = preg_replace('"^[^>]*>.*?<source(.*?)</source>.*"s', '\\1', $split[1]);
        if (substr($content, 0, 1) === '/') {
            $text = 'NO_TEXT';
        } else {
            $text = preg_replace('"^[^>]*>(.*)"', '\\1', $content);
        }
        $this->_tagMapping[$tagId]['imgText'] = html_entity_decode($text, ENT_QUOTES, 'utf-8');
        $this->_tagMapping[$tagId]['text'] = $text;
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
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::parse()
     */
    protected function parse() {
        //benenne <bin-unit-Tags in <group-Tags um, um das Parsing zu vereinfachen
        // (wird unten rückgängig gemacht; für das Parsing sind bin-units völlig
        //analog zu group-Tags zu sehen, da auch sie translate-Attribut haben können
        //und gruppierende Eigenschaft haben
        $this->_origFileUnicodeProtected = str_replace(array('<bin-unit', '</bin-unit>'), array('<group bin-unit ', '/bin-unit</group>'), $this->_origFileUnicodeProtected);
        //gibt die Verschachtelungstiefe der <group>-Tags an
        $groupLevel = 0;
        //array, in dem die Verschachtelungstiefe der Group-Tags in Relation zu ihrer
        //Einstellung des translate-Defaults festgehalten wird
        //der Default wird auf true gesetzt
        $translateGroupLevels = array($groupLevel - 1 => true);
        $groups = explode('<group', $this->_origFileUnicodeProtected);
        $counterTrans = 0;
        foreach ($groups as &$group) {
            //übernimm den Default-Wert für $translateGroupLevels von der einer Ebene niedriger
            $translateGroupLevels[$groupLevel] = $translateGroupLevels[$groupLevel - 1];
            //falls die Gruppe den translate-Default für trans-units auf no stellt
            //vermerke dies
            if (preg_match('"^[^<>]*translate=\"no\""i', $group)) {
                $translateGroupLevels = array($groupLevel => false);
            } elseif (preg_match('"^[^<>]*translate=\"yes\""i', $group)) {
                $translateGroupLevels = array($groupLevel => true);
            }
            $units = explode('<trans-unit', $group);
            $count = count($units);
            //falls bereits vor der ersten transunit die group wieder geschlossen wurde
            //reduziere
            $groupLevel = $groupLevel - substr_count($units[0], '</group>');
            for ($i = 1; $i < $count; $i++) {
                $translate = $translateGroupLevels[$groupLevel];
                if (preg_match('"^[^<>]*translate=\"no\""i', $units[$i])) {
                    $translate = false;
                }
                //falls kein mrk-Tag mit Inhalt im Segment vorhanden ist, ist im Segment kein übersetzungsrelevanter Inhalt
                elseif (strstr($units[$i], '</mrk>')=== false) {
                    $translate = false;
                } elseif (preg_match('"^[^<>]*translate=\"yes\""i', $units[$i])) {
                    $translate = true;
                }
                //reduziere den Grouplevel um die Zahl der schließenden group-Tags
                //die vor dem nächsten trans-unit-Tag aber nach den Übersetzungs
                //einheiten des aktuellen trans-unit-Tags vorkommen (innerhalb
                //einer trans-unit erlaubt die Spez. keinen group-Tag, daher
                //kann die Reduzierung nach Abschluss der Bearbeitung
                //der aktuellen trans-unit erfolgen
                $groupLevel = $groupLevel - substr_count($units[$i], '</group>');
                if ($translate) {
                    $counterTrans++;
                    $this->parseSegmentAttributes($units[$i]);
                    $units[$i] = $this->extractSegment($units[$i]);
                }
            }
            $group = implode('<trans-unit', $units);
            //erhöhe groupLevel um eins, da jetzt die nächste Gruppe drankommt
            $groupLevel++;
        }
        
        if ($counterTrans === 0) {
            error_log('Die Datei ' . $this->_fileName . ' enthielt keine übersetzungsrelevanten Segmente!');
        }
        $this->_skeletonFile = implode('<group', $groups);
        $this->_skeletonFile = str_replace(array('<group bin-unit ', '/bin-unit</group>'), array('<bin-unit', '</bin-unit>'), $this->_skeletonFile);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::parseSegmentAttributes()
     */
    protected function parseSegmentAttributes($transunit) {
        $transunit = explode('<sdl:seg ', $transunit);
        //falls überhaupt Information zu Matchwerten vorhanden ist
        $count = count($transunit);
        if ($count == 0) {
            $transunit = implode('<sdl:seg ', $transunit);
            trigger_error('<sdl:seg wurde kein Mal in der folgenden transunit gefunden: ' . $transunit, E_USER_ERROR);
            return;
        }
        $i = $count > 1 ? 1 : 0;
        for (; $i < $count; $i++) {
            $id = preg_replace('"^[^<>]*id=\"([^\"]*)\".*"s', '\\1', $transunit[$i]);
            $id = str_replace(' ', '_x0020_', $id);
            
            $attributes = $this->createSegmentAttributes($id);
            
            //falls kein percent gefunden wird, ergibt der int-cast 0, was passt
            $attributes->matchRate = (int) preg_replace('"^[^><]* percent=\"(\d*)\".*"', '\\1', $transunit[$i]);

            //check if there is no origin at all
            if(strpos($transunit[$i], 'origin="') !== false) {
                //trimming here, since regex does not remove \n from content
                //set original value here, conversion to translate5 syntax is done later
                $attributes->matchRateType = preg_replace('/^[^><]* origin="([^"]*)".*/', '\\1', trim($transunit[$i]));
            }
            
            //FIXME can lead to errors if auto-propagated was in nested <sdl:prev-origin tags
            $attributes->autopropagated = strpos($transunit[$i], 'origin="auto-propagated"') !== false;
            $attributes->locked = strpos($transunit[$i], ' locked="true"') !== false;
        }
    }

    /**
     * Stellt Tags-Abschnitt im Header als DOM-Objekt bereit
     *
     * - befüllt $this->_tagMapping mit dem Wert des Returns
     * - kann mit mehreren Tag-Defs Abschnitten umgehen. Geht davon aus, dass die Tag IDs über alle Tag-Defs Ascbhnitte hinweg eindeutig sind
     *
     * @return array array('tagId' => array('name' => string '', 'text' => string '',['eptName' => string '', 'eptText' => string '']),'tagId2' => ...)
     */
    protected function prepareTagMapping() {
        $file = preg_split('"<tag-defs[^>]*>"', $this->_origFileUnicodeSpecialCharsRemoved);

        //den ersten Teil ohne Tag-Defs rauswerfen.
        array_shift($file);

        while ($data = array_shift($file)) {
            $this->extractTags($data);
        }

        return $this->_tagMapping;
    }

    /**
     * extrahiert die Tags aus den einzelnen Tag-Defs Abschnitten
     *
     * @param string $data
     * @return multitype:multitype:string
     */
    protected function extractTags($data) {
        $data = explode('</tag-defs>', $data);
        $tags = '<tag-defs>' . $data[0] . '</tag-defs>';
        unset($data);
        //alle unicode-entities herauswerfen, da DomDocument
        //mit bestimmten unicode-Entities in Attributwerten nicht umgehen kann
        //(z. B. &#x1;)
        $tags = preg_replace('"&#x[0-9A-Fa-f]+;"', 'UNICODE_ENTITY', $tags);
        $dom = new DomDocument();
        if (!$dom->loadXML($tags)) {
            trigger_error('Das Laden der Taginformationen aus dem Header der sdlxliff-Datei schlug fehl', E_USER_ERROR);
        }
        $tagList = $dom->getElementsByTagName('tag');
        foreach ($tagList as $node) {
            $id = $node->getAttribute('id');
            $firstChild = $node->firstChild;
            $text = $firstChild->textContent;
            $name = $firstChild->tagName;
            $this->_tagMapping[$id]['name'] = $name;
            $this->_tagMapping[$id]['text'] = htmlentities($text, ENT_QUOTES, 'utf-8');
            $this->_tagMapping[$id]['imgText'] = $text;
            if ($name === 'bpt') {
                $eptList = $node->getElementsByTagName('ept');
                $ept = $eptList->item(0);
                $this->_tagMapping[$id]['eptName'] = $ept->tagName;
                $eptText = $ept->textContent;
                $this->_tagMapping[$id]['eptText'] = htmlentities($eptText, ENT_QUOTES, 'utf-8');
                $this->_tagMapping[$id]['imgEptText'] = $eptText;
            }
            if (!isset($this->_tagDefMapping[$name])) {
                trigger_error('Der Tag ' . $name . ' ist nicht im Array _tagDefMapping definiert', E_USER_ERROR);
            }
            if (strpos($id, '-')!== false) {
                trigger_error('Die Tag-Id ' . $id . ' enthielt einen Bindestrich - dies ist nicht erlaubt, da die Syntax für das JS-Frontend auf Bindestriche als Trennzeichen setzt', E_USER_ERROR);
            }
        }
        return $this->_tagMapping;
    }
    
    /**
     * Extrahiert aus einem durch parseFile erzeugten Code-Schnipsel mit genau einer trans-unit Quell-
     * und Zielsegmente
     *
     * - speichert die Segmente in der Datenbank
     * - setzt voraus, dass sich das target immer nach dem seg-source befindet
     * @param string $transUnit
     * @return string $transUnit enthält anstelle der Segmente die Replacement-Tags <lekSourceSeg id=""/> und <lekTargetSeg id=""/>
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    protected function extractSegment($transUnit) {
        $this->segmentData = array();
        //extrahiere das Zielsegment
        $targetExp = explode('<target', $transUnit);
        $targetExp[1] = explode('</target>', $targetExp[1]);
        $targetExp[1][0] = preg_split('"(<mrk[^>]*mtype=\"seg\"[^>]*>)"', $targetExp[1][0], NULL, PREG_SPLIT_DELIM_CAPTURE);
        $countTargetMrk = count($targetExp[1][0]);
        //extrahiere das Quellsegment
        if (strpos($targetExp[0], '<seg-source')!== false) {
            $sourceExp = $targetExp[0];
        } else {
            $sourceExp = $targetExp[1][1];
        }
        $sourceExp = explode('<seg-source', $sourceExp);
        $sourceExp[1] = explode('</seg-source>', $sourceExp[1]);
        $sourceExp[1][0] = preg_split('"(<mrk[^>]*mtype=\"seg\"[^>]*>)"', $sourceExp[1][0], NULL, PREG_SPLIT_DELIM_CAPTURE);

        if ($countTargetMrk !== count($sourceExp[1][0])) {
            trigger_error(
                    'Die Anzahl der Zielsegmente entsprach nicht der Zahl der Quellsegmente in der transunit ' .
                    $transUnit, E_USER_ERROR);
        }
        //füge target-Segmente in einem String wieder für den kompletten Rückzusammen-
        //bau der transunit wieder zusammen. Beginne mit den für die Segmentextraktion
        //nicht benötigten Teilen
        $targetMrkString = $targetExp[1][0][0];
        //gehe in schleife für alle Segmente (eine transunit kann mehrere Segmente enthalten)
        for ($i = 2; $i < $countTargetMrk; $i++) {//die ersten beiden Array-Elemente sind irrelevant, da vor dem ersten Segment und Delimiter
            $h = $i - 1;
            //setzte allgemeine Segmenteigenschaften
            $this->setMid(preg_replace('".*mid=\"([^\"]*)\".*"', '\\1', $sourceExp[1][0][$h]));
            //extrahiere das sourcesegment
            $sourceExp[1][0][$i] = explode('</mrk>', $sourceExp[1][0][$i]);
            array_pop($sourceExp[1][0][$i]);
            $sourceOrig = implode('</mrk>', $sourceExp[1][0][$i]);
            
            //FIXME getFieldPlaceholder einbauen wenn source = editable und Marc ein Rückspeichern wünscht
            // → Marc sagt OK, allerdings ist hier die Einbau Logik doch erheblich umfangreicher als zunächst gedacht!
            // Daher bei SDLXLIFF zunächst kein Rückspeichern der editierten Sources. 
            $sourceName = $this->segmentFieldManager->getFirstSourceName();
            $this->segmentData[$sourceName] = array(
                     'original' => $this->parseSegment($sourceOrig,true)
            );

            //extrahiere das targetsegment
            $targetExp[1][0][$i] = explode('</mrk>', $targetExp[1][0][$i]);
            //falls das Zielsegment eine Übersetzung enthält
            $targetName = $this->segmentFieldManager->getFirstTargetName();
            if ($targetExp[1][0][$i]>1) {
                $afterTargetTag = array_pop($targetExp[1][0][$i]);
                $targetOrig = implode('</mrk>', $targetExp[1][0][$i]);
                $this->segmentData[$targetName] = array(
                     'original' => $this->parseSegment($targetOrig,false)
                );
                
                $segmentId = $this->setAndSaveSegmentValues();
                $targetExp[1][0][$i] = $this->getFieldPlaceholder($segmentId, $targetName).'</mrk>'.$afterTargetTag;
            } else {
                $this->segmentData[$targetName] = array(
                     'original' => NULL
                );
                $segmentId = $this->setAndSaveSegmentValues();
                $targetExp[1][0][$i] = $this->getFieldPlaceholder($segmentId, $targetName).'</mrk>'.$targetExp[1][0][$i][0];
            }
            $targetMrkString.= $targetExp[1][0][$h] . $targetExp[1][0][$i];
            $i++; //überspringe den delimiter
        }
        $targetMrkString = preg_replace('"/>\s*<lekTargetSeg"', '><lekTargetSeg', $targetMrkString);
        //Segmenteigenschaften bei mehreren Segmenten in einer transunit korrekt extrahieren
        //der parent-Klasse abstrakte Methoden für die wichtigen Methoden hinzufügen
        //füge trans-unit vollständig zusammen
        return $targetExp[0] .
                '<target' .
                $targetMrkString .
                '</target>' .
                $targetExp[1][1];
    }

    /**
     * Hilfsfunktion für parseSegment: Festlegung der tagId im JS
     *
     * @param string $tag enthält den Tag als String
     * @param string $tagName enthält den Tagnamen
     * @return string $id ID des Tags im JS
     */
    protected function parseSegmentGetTagId($tag, $tagName) {
        $whitespaceTags = ['unicodePrivateUseArea', 'hardReturn' , 'softReturn', 'macReturn', 'space'];
        if (in_array($tagName, $whitespaceTags)) {
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
    protected function parseSegment($segment,$isSource) {
        $segment = $this->parseSegmentProtectWhitespace($segment);
        if (strpos($segment, '<')=== false) {
            return $segment;
        }
        $segment = $this->parseSegmentUnifyInternalTags($segment);
        $data = ZfExtended_Factory::get('editor_Models_Import_FileParser_Sdlxliff_ParseSegmentData');
        $data->segment = preg_split('"(<[^>]*>)"', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $data->segmentCount = count($data->segment);

        //parse nur die ungeraden Arrayelemente, den dies sind die Rückgaben von PREG_SPLIT_DELIM_CAPTURE
        for ($data->i = 1; $data->i < $data->segmentCount; $data->i++) {
            if (preg_match('"^<[^/].*[^/]>$"', $data->segment[$data->i]) > 0) {//öffnender Tag (left-tag)
                $data = $this->parseLeftTag($data);
            } elseif (preg_match('"^</"', $data->segment[$data->i]) > 0) {//schließender Tag (right-tag)
                    $data = $this->parseRightTag($data);
            } else {//in sich geschlossener Tag (single-tag)
                $data = $this->parseSingleTag($data);
            }
            $data->i++; //parse nur die ungeraden Arrayelemente, den dies sind die Rückgaben von PREG_SPLIT_DELIM_CAPTURE
            $this->_tagCount++;
        }
        return implode('', $data->segment);
    }
    
    /**
     * For reason look at TRANSLATE-781 "different white space inside of internal tags leads to failures in relais import"
     * http://jira.translate5.net/browse/TRANSLATE-781
     * 
     * @param string $segment
     * @return type
     */
    protected function parseSegmentUnifyInternalTags($segment) {
        $search = array(
            '#(<g [^>]*) +(/>)#',
            '#(<g [^>]*) +(>)#',
            '#(<mrk [^>]*) +(/>)#',
            '#(<mrk [^>]*) +(>)#',
            '#(<x [^>]*) +(/>)#',
            '#(<x [^>]*) +(>)#'
            );
        $replace = array(
            '\\1\\2',
            '\\1\\2',
            '\\1\\2',
            '\\1\\2',
            '\\1\\2',
            '\\1\\2'
        );
        $segment = preg_replace($search, $replace, $segment);
        return $segment;
    }

    /**
     * parsing von left-Tags für parseSegment (öffnenden Tags)
     *
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     * @return editor_Models_Import_FileParser_Sdlxliff_parseSegmentData  $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function parseLeftTag($data) {
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
            trigger_error('Schließender Tag ohne einen öffnenden gefunden. Aktuelle Segment Mid: '.$this->_mid.'. Aktueller Tag:  ' .join('', $data->segment), E_USER_ERROR);
        }
        $openTag = $data->openTags[$data->openCounter];
        $mappedTag = $this->_tagMapping[$openTag['tagId']];
        $fileNameHash = md5($mappedTag['imgEptText']);
        
        //generate the html tag for the editor
        $p = $this->getTagParams($data->segment[$data->i], $openTag['nr'], $openTag['tagId'], $fileNameHash, $mappedTag['eptText']);
        $data->segment[$data->i] = $this->_rightTag->getHtmlTag($p);
        
        $this->_rightTag->createAndSaveIfNotExists($mappedTag['imgEptText'], $fileNameHash);
        $data->openCounter--;
        return $data;
    }

    /**
     * parsing von single-Tags für parseSegment (selfclosing oder placeholder Tags)
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
            $this->setLockedTagContent($tag, $tagId);
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
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getTagParams()
     */
    protected function getTagParams($tag, $shortTag, $tagId, $fileNameHash, $text = false) {
        $data = parent::getTagParams($tag, $shortTag, $tagId, $fileNameHash, $text);
        $data['text'] = $this->encodeTagsForDisplay($data['text']);
        return $data;
    }
    

}
