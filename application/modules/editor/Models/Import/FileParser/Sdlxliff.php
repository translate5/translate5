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
    use editor_Models_Import_FileParser_TagTrait {
        getTagParams as protected traitGetTagParams;
    }
    
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
        'mrk' => 'mrk',
        );
    
    /**
     * defines the GUI representation of internal used tags  
     * @var array
     */
    protected $_tagMapping = [];

    /**
     * @var editor_Models_Import_FileParser_Sdlxliff_TransunitParser
     */
    protected $transunitParser;
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['sdlxliff'];
    }
    
    /**
     * Initiert Tagmapping
     */
    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task) {
        //add sdlxliff tagMapping
        $this->addSldxliffTagMappings();
        parent::__construct($path, $fileName, $fileId, $task);
        $this->initImageTags();
        $this->initHelper();
        $this->checkForSdlChangeMarker();
        $this->prepareTagMapping();
        
        $this->transunitParser = ZfExtended_Factory::get('editor_Models_Import_FileParser_Sdlxliff_TransunitParser');
        
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
     *     }
     * @var array array('tagId' => array('text' => string '',['eptName' => string '', 'eptText' => string '','imgEptText' => string '']),'tagId2' => ...)
     */
    private function addSldxliffTagMappings() {
        $this->_tagMapping['mrkSingle'] = array('text' => '&lt;InternalReference/&gt;');
        $this->_tagMapping['mrkPaired'] = array('text' => '&lt;InternalReference&gt;','eptName'=>'ept','eptText'=>'&lt;/InternalReference&gt;','imgEptText'  => '</InternalReference>');
    }
    
    /**
     * Checks, if there are any change-markers in the sdlxliff. If yes, triggers an error
     */
    protected function checkForSdlChangeMarker() {
        $added = strpos($this->_origFile, 'mtype="x-sdl-added"')!== false;
        $deleted = strpos($this->_origFile, 'mtype="x-sdl-deleted"')!== false;
        $refs = strpos($this->_origFile, '<rev-defs>')!== false;
        if ($added || $deleted || $refs) {
            //There are change Markers in the sdlxliff-file which are not supported!
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1003', [
                'task' => $this->task,
                'filename' => $this->_fileName,
            ]);
        }
    }
    
    /**
     * Setzt $this->_tagMapping[$tagId]['text']
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
            //Locked-tag-content was requested but tag does not contain a xid attribute.',
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1004', [
                'task' => $this->task,
                'filename' => $this->_fileName,
                'tagId' => $tagId,
                'tag' => $tag,
            ]);
        }
        $xid = preg_replace('"<.* xid=\"([^\"]*)\".*>"', '\\1', $tag);
        $xid = preg_replace('"<.* xid=\"([^\"]*)\".*>"', '\\1', $tag);
        $split = explode('id="' . $xid, $this->_origFile);
        $content = preg_replace('"^[^>]*>.*?<source(.*?)</source>.*"s', '\\1', $split[1]);
        if (substr($content, 0, 1) === '/') {
            $text = 'NO_TEXT';
        } else {
            $text = preg_replace('"^[^>]*>(.*)"', '\\1', $content);
        }
        $this->_tagMapping[$tagId]['text'] = $text;
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
        $this->_origFile = str_replace(array('<bin-unit', '</bin-unit>'), array('<group bin-unit ', '/bin-unit</group>'), $this->_origFile);
        //gibt die Verschachtelungstiefe der <group>-Tags an
        $groupLevel = 0;
        //array, in dem die Verschachtelungstiefe der Group-Tags in Relation zu ihrer
        //Einstellung des translate-Defaults festgehalten wird
        //der Default wird auf true gesetzt
        $translateGroupLevels = array($groupLevel - 1 => true);
        $groups = explode('<group', $this->_origFile);
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
                    //since </group> closing tags can be after the trans-unit we have to split them away and them to the parsed result again
                    $transUnit = explode('</trans-unit>', $units[$i]);
                    $units[$i] = $this->extractSegment($transUnit[0].'</trans-unit>').$transUnit[1];
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
     * parses the given transunit array
     * @param array $transunit
     */
    protected function parseSegmentAttributes($transunit) {
        $start = strpos($transunit, '<sdl:seg-defs');
        $end = strpos($transunit, '</sdl:seg-defs>') + 15; //set end after the end tag
        
        if ($start === false || $end === false) {
            //<sdl:seg-defs was not found in the current transunit
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1005', [
                'task' => $this->task,
                'filename' => $this->_fileName,
                'transunit' => $transunit,
            ]);
        }
        
        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */
        $xmlparser->registerElement('sdl:seg', function($tag, $tagAttributes) use ($xmlparser){
            $id = str_replace(' ', '_x0020_', $tagAttributes['id']);
            $attributes = $this->createSegmentAttributes($id);
            // falls kein percent gefunden wird, ergibt der int-cast 0, was passt
            $attributes->matchRate = (int) $xmlparser->getAttribute($tagAttributes, 'percent');

            $origin = $xmlparser->getAttribute($tagAttributes, 'origin');
            //check if there is no origin at all
            if($origin) {
                //set original value here, conversion to translate5 syntax is done later
                $attributes->matchRateType = $origin;
            }
            
            $attributes->autopropagated = $origin === 'auto-propagated';
            $attributes->locked = (bool) $xmlparser->getAttribute($tagAttributes, 'locked');
        });
        $xmlparser->parse(substr($transunit, $start, $end - $start));
    }

    /**
     * Stellt Tags-Abschnitt im Header als DOM-Objekt bereit
     *
     * - befüllt $this->_tagMapping mit dem Wert des Returns
     * - kann mit mehreren Tag-Defs Abschnitten umgehen. Geht davon aus, dass die Tag IDs über alle Tag-Defs Ascbhnitte hinweg eindeutig sind
     *
     *  structure: array('tagId' => array('text' => string '',['eptName' => string '', 'eptText' => string '']),'tagId2' => ...)
     */
    protected function prepareTagMapping() {
        $file = preg_split('"<tag-defs[^>]*>"', $this->_origFile);

        //den ersten Teil ohne Tag-Defs rauswerfen.
        array_shift($file);

        while ($data = array_shift($file)) {
            $this->extractTags($data);
        }
    }

    /**
     * extrahiert die Tags aus den einzelnen Tag-Defs Abschnitten
     *
     * @param string $data
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
            //loading the taginformation from the SDLXLIFF header has failed!
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1006', [
                'task' => $this->task,
                'filename' => $this->_fileName,
            ]);
        }
        $tagList = $dom->getElementsByTagName('tag');
        foreach ($tagList as $node) {
            $id = $node->getAttribute('id');
            $firstChild = $node->firstChild;
            $text = $firstChild->textContent;
            $name = $firstChild->tagName;
            $this->_tagMapping[$id]['text'] = htmlentities($text, ENT_QUOTES, 'utf-8');
            if ($name === 'bpt') {
                $eptList = $node->getElementsByTagName('ept');
                $ept = $eptList->item(0);
                $this->_tagMapping[$id]['eptName'] = $ept->tagName;
                $eptText = $ept->textContent;
                $this->_tagMapping[$id]['eptText'] = htmlentities($eptText, ENT_QUOTES, 'utf-8');
                $this->_tagMapping[$id]['imgEptText'] = $eptText;
            }
            if (!isset($this->_tagDefMapping[$name])) {
                //the tag is not defined in _tagDefMapping array
                throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1007', [
                    'task' => $this->task,
                    'filename' => $this->_fileName,
                    'tagname' => $name,
                ]);
            }
        }
    }
    
    /**
     * parse the given transunit and saves the segments 
     * 
     * @param string $transUnit
     * @return string contains the teplacement-Tags <lekTargetSeg id=""/> instead the content, where id is the DB segment ID
     */
    protected function extractSegment($transUnit) {
        $this->segmentData = array();
        $result = $this->transunitParser->parse('<trans-unit'.$transUnit, function($mid, $source, $target) {
            $sourceName = $this->segmentFieldManager->getFirstSourceName();
            $targetName = $this->segmentFieldManager->getFirstTargetName();
            $this->setMid($mid);
            $this->segmentData[$sourceName] = ['original' => $this->parseSegment($source,true)];
            $this->segmentData[$targetName] = ['original' => $this->parseSegment($target,true)];
            $segmentId = $this->setAndSaveSegmentValues();
            return $this->getFieldPlaceholder($segmentId, $targetName);
        });
        
        // add leading <trans-unit for parsing, then strip it again (we got the $transUnit without it, so we return it without it)
        return substr($result, 11);
    }

    /**
     * Hilfsfunktion für parseSegment: Festlegung der tagId im JS
     *
     * @param string $tag enthält den Tag als String
     * @param string $tagName enthält den Tagnamen
     * @return string $id ID des Tags im JS
     */
    protected function parseSegmentGetTagId($tag, $tagName) {
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
            //the tag in the segment was not defined in the tag mapping list
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1010', [
                'filename' => $this->_fileName,
                'task' => $this->task,
                'tagname' => $tagName,
                'segment' => implode('', $data->segment),
            ]);
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
     * @param bool isSource
     * @return string $segment enthält anstelle der Tags die vom JS benötigten Replacement-Tags
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    protected function parseSegment($segment,$isSource) {
        $segment = $this->parseSegmentProtectWhitespace($segment);
        if (strpos($segment, '<')=== false) {
            return $segment;
        }
        if(preg_match('/<mrk[^>]+mtype="x-sdl-comment"/', $segment)){
            //The file contains SDL comments which are currently not supported!
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1000', [
                'filename' => $this->_fileName,
                'task' => $this->task
            ]);
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
        }
        return implode('', $data->segment);
    }
    
    /**
     * For reason look at TRANSLATE-781 "different white space inside of internal tags leads to failures in relais import"
     * http://jira.translate5.net/browse/TRANSLATE-781
     * 
     * @param string $segment
     * @return string
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
            //The opening tag $tagName contains a non valid tagId according to our reverse engineering
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1001', [
                'task' => $this->task,
                'filename' => $this->_fileName,
                'tagName' => $tagName,
                'tagId' => $tagId,
                'segment' => implode('', $data->segment),
            ]);
        }
        $data->openTags[$data->openCounter]['tagName'] = $tagName;
        $data->openTags[$data->openCounter]['tagId'] = $tagId;
        $data->openTags[$data->openCounter]['nr'] = $data->j;
        
        //ersetzte gegen Tag für die Anzeige
        $p = $this->getTagParams($tag, $shortTagIdent, $tagId, $this->_tagMapping[$tagId]['text']);
        $tag = $this->_leftTag->getHtmlTag($p);
        
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
            //Found a closing tag without an opening one!
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1002', [
                'task' => $this->task,
                'filename' => $this->_fileName,
                'mid' => $this->_mid,
                'currentTag' => join('', $data->segment),
            ]);
        }
        $openTag = $data->openTags[$data->openCounter];
        $mappedTag = $this->_tagMapping[$openTag['tagId']];
        
        //generate the html tag for the editor
        $p = $this->getTagParams($data->segment[$data->i], $openTag['nr'], $openTag['tagId'], $mappedTag['eptText']);
        $data->segment[$data->i] = $this->_rightTag->getHtmlTag($p);
        
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
                
        $whitespaceTags = ['hardReturn' , 'softReturn', 'macReturn', 'space', 'char', 'tab'];
        if (in_array($tagName, $whitespaceTags)) {
            //tagtrait is working with shortTagIdent internally, so we have to feed it here
            $this->shortTagIdent = $data->j++;
            $tag = $this->whitespaceTagReplacer($tag);
            return $data;
        }
        $this->verifyTagName($tagName, $data);
        $tagId = $this->parseSegmentGetTagId($tag, $tagName);
        $shortTagIdent = $data->j;
        $locked = false;
        if (strpos($tagId, 'locked')!== false) {
            $this->setLockedTagContent($tag, $tagId);
            $shortTagIdent = 'locked' . $data->j;
            $locked = true;
        }
        
        //generate the html tag for the editor
        $p = $this->getTagParams($tag, $shortTagIdent, $tagId, $this->_tagMapping[$tagId]['text']);
        $tag = $this->_singleTag->getHtmlTag($p);

        $data->j++;
        return $data;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getTagParams()
     */
    protected function getTagParams($tag, $shortTag, $tagId, $text) {
        $data = $this->traitGetTagParams($tag, $shortTag, $tagId, $text);
        $data['text'] = $this->encodeTagsForDisplay($data['text']);
        return $data;
    }
}
