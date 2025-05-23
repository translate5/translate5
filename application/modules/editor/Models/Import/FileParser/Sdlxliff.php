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

use editor_Models_Import_FileParser_Sdlxliff_TagMappingConfig as TagMappingConfig;
use editor_Models_Import_FileParser_Sdlxliff_TransunitParser as TransunitParser;

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
 */
class editor_Models_Import_FileParser_Sdlxliff extends editor_Models_Import_FileParser
{
    public const SOURCE = 'source';

    public const TARGET = 'target';

    /**
     * Points to comment location/source. In this case it means that the comment is on transunit level, and it is not
     * segment specific.
     */
    public const TRANS_UNIT = 'transunit';

    public const USERGUID = 'sdlxliff-imported';

    protected array $_tagDefMapping = TagMappingConfig::TAG_DEF_MAPPING;

    protected array $_tagMapping = TagMappingConfig::DEFAULT_TAG_MAPPING;

    protected TransunitParser $transunitParser;

    /**
     * contains the collected comments out of tag cmt-defs
     * @var array
     */
    protected $comments;

    /**
     * @var ZfExtended_Logger
     */
    protected $logger;

    /**
     * Needed CXT meta Definitions in the SDLXLIFF file
     * @var array
     */
    protected $cxtDefinitions = [];

    /**
     * Container to collect previous origins per trans-unit
     * @var array
     */
    protected $previousOrigins;

    /**
     * @var array<string, array{author: string, date: DateTime, workflowStep: string}>
     */
    private array $revIdToUserDataMap = [];

    private bool $isTrackChangesPluginActive;

    /**
     * @var array<string, int>
     */
    private array $tagIdShortTagIdentMap = [];

    /**
     * @var array<string, true>
     */
    private array $quickInsertsList = [];

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions()
    {
        return ['sdlxliff'];
    }

    /**
     * Initiert Tagmapping
     */
    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task)
    {
        parent::__construct($path, $fileName, $fileId, $task);

        $this->isTrackChangesPluginActive = Zend_Registry::get('PluginManager')->isActive('TrackChanges');
        $this->checkForSdlChangeMarker();
        $this->prepareTagMapping();
        $this->readCxtMetaDefinitions();
        $this->logger = Zend_Registry::get('logger')->cloneMe('editor.import.fileparser.sdlxliff');
        $this->transunitParser = new TransunitParser(
            $this->config,
            $task,
            $this->isTrackChangesPluginActive
        );
        //diff export for this task can be used
        $this->task->setDiffExportUsable(1);
        //here would be the right place to set the import map,
        // since our values base on sdlxliff values,
        // nothing has to be done here at the moment
        //$this->matchRateType->setImportMap($map);
    }

    /**
     * Checks, if there are any change-markers in the sdlxliff.
     * If import is allowed do nothing, if not and change marks are contained: triggers an error
     */
    protected function checkForSdlChangeMarker()
    {
        if ($this->isTrackChangesPluginActive) {
            return;
        }

        if ($this->config->runtimeOptions->import->sdlxliff->applyChangeMarks) {
            return;
        }

        $added = strpos($this->_origFile, 'mtype="x-sdl-added"') !== false;
        $deleted = strpos($this->_origFile, 'mtype="x-sdl-deleted"') !== false;
        $refs = strpos($this->_origFile, '<rev-defs>') !== false;

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
     */
    protected function setLockedTagContent($tag, $tagId)
    {
        if (strstr($tag, 'xid=') === false) {
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
    protected function parse()
    {
        //benenne <bin-unit-Tags in <group-Tags um, um das Parsing zu vereinfachen
        // (wird unten rückgängig gemacht; für das Parsing sind bin-units völlig
        //analog zu group-Tags zu sehen, da auch sie translate-Attribut haben können
        //und gruppierende Eigenschaft haben
        $this->_origFile = str_replace(['<bin-unit', '</bin-unit>'], ['<group bin-unit ', '/bin-unit</group>'], $this->_origFile);
        $this->extractComments();
        $this->processRevDefs();
        $this->extractQuickInsertsList();
        //gibt die Verschachtelungstiefe der <group>-Tags an
        $groupLevel = 0;
        //array, in dem die Verschachtelungstiefe der Group-Tags in Relation zu ihrer
        //Einstellung des translate-Defaults festgehalten wird
        //der Default wird auf true gesetzt
        $translateGroupLevels = [
            $groupLevel - 1 => true,
        ];
        $groups = explode('<group', $this->_origFile);
        $counterTrans = 0;
        foreach ($groups as &$group) {
            //we assume that in one group all <sdl:cxt id="1"/> tags belong to the group and not to single trans units.
            $cxtGroupDefinitions = [];
            preg_match_all('#<sdl:cxt[^<>]+id="([^"]+)"#', $group, $cxtGroupDefinitions);
            //übernimm den Default-Wert für $translateGroupLevels von der einer Ebene niedriger
            $translateGroupLevels[$groupLevel] = $translateGroupLevels[$groupLevel - 1];
            //falls die Gruppe den translate-Default für trans-units auf no stellt
            //vermerke dies
            if (preg_match('"^[^<>]*translate=\"no\""i', $group)) {
                $translateGroupLevels = [
                    $groupLevel => false,
                ];
            } elseif (preg_match('"^[^<>]*translate=\"yes\""i', $group)) {
                $translateGroupLevels = [
                    $groupLevel => true,
                ];
            }
            $units = explode('<trans-unit', $group);
            $count = count($units);
            //falls bereits vor der ersten transunit die group wieder geschlossen wurde
            //reduziere
            $groupLevel = $groupLevel - substr_count($units[0], '</group>');
            for ($i = 1; $i < $count; $i++) {
                //TODO: find content with mrk tag in it!!!!
                $translate = $translateGroupLevels[$groupLevel];
                if (preg_match('"^[^<>]*translate=\"no\""i', $units[$i])) {
                    $translate = false;
                }
                //falls kein mrk-Tag mit Inhalt im Segment vorhanden ist, ist im Segment kein übersetzungsrelevanter Inhalt
                elseif (strstr($units[$i], '</mrk>') === false) {
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

                    //The transUnit contains sdl:cxt tags, but we assume that tags only in the group tag!
                    if (strpos($transUnit[0], '<sdl:cxt') !== false) {
                        throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1323', [
                            'task' => $this->task,
                            'filename' => $this->_fileName,
                            'transunit' => $transUnit,
                        ]);
                    }

                    $units[$i] = $this->extractSegment($transUnit[0] . '</trans-unit>', $cxtGroupDefinitions[1]) . $transUnit[1];
                }
            }
            $group = implode('<trans-unit', $units);
            //erhöhe groupLevel um eins, da jetzt die nächste Gruppe drankommt
            $groupLevel++;
        }

        if ($counterTrans === 0) {
            $this->logger->warn('E1291', 'The file "{filename}" did not contain any translation relevant content. Either all segments are set to translate="no" or the file was not segmented.', [
                'task' => $this->task,
                'filename' => $this->_fileName,
            ]);
        }
        $this->skeletonFile = implode('<group', $groups);
        $this->skeletonFile = str_replace(['<group bin-unit ', '/bin-unit</group>'], ['<bin-unit', '</bin-unit>'], $this->skeletonFile);
    }

    private function extractQuickInsertsList(): void
    {
        $regex = '#<value key="SDL:QuickInsertsList">\s*(.+)\s*</value>#Um';

        if (preg_match($regex, $this->_origFile, $matches)) {
            $quickInsertsList = explode(';q', trim($matches[1], 'q '));
            $this->quickInsertsList = array_combine($quickInsertsList, array_fill(0, count($quickInsertsList), true));
        }
    }

    /**
     * parses the given transunit array
     * @param array $transunit
     */
    protected function parseSegmentAttributes($transunit)
    {
        $start = strpos($transunit, '<sdl:seg-defs');
        $end = strpos($transunit, '</sdl:seg-defs>') + 15; //set end after the end tag

        if ($start === false || $end === false) {
            //<sdl:seg-defs was not found in the current transunit, nothing can be parsed, attributes are created on next usage
            return;
        }

        $this->previousOrigins = [];
        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */
        $xmlparser->registerElement('sdl:seg sdl:prev-origin', function ($tag, $tagAttributes) use ($xmlparser) {
            $this->previousOrigins[] = $tagAttributes;
        });
        $xmlparser->registerElement('sdl:seg', function () {
            $this->previousOrigins = [];
        }, function ($tag, $key, $opener) use ($xmlparser) {
            $tagAttributes = $opener['attributes'];
            $id = str_replace(' ', '_x0020_', $tagAttributes['id']);
            $attributes = $this->createSegmentAttributes($id);
            // if there is no attribute percent, the int-cast result 0, what is correct here
            $attributes->matchRate = (int) $xmlparser->getAttribute($tagAttributes, 'percent');

            $origin = $xmlparser->getAttribute($tagAttributes, 'origin');
            //check if there is no origin at all
            if ($origin) {
                //set original value here, conversion to translate5 syntax is done later
                $attributes->matchRateType = $origin;

                //if the direct origin is TM or MT we define that segment as status pretranslated
                $attributes->isPreTranslated = $this->matchRateType->isPretranslationType($origin);

                $originSystem = $xmlparser->getAttribute($tagAttributes, 'origin-system', '');
                if ($attributes->isPreTranslated && ! empty($originSystem)) {
                    $attributes->customMetaAttributes[$this->matchRateType::DATA_PREVIOUS_NAME] = str_replace(';', '_', $originSystem);
                    $this->setPreviousOriginSystemName($attributes, $originSystem);
                }
            }

            //we store also information about the previous origin, but currently only the first previous origin or the original origin if a pre-translation origin
            $previousOrigin = array_shift($this->previousOrigins);
            if (! empty($previousOrigin) && ! $attributes->isPreTranslated) {
                $prevOriginVal = $xmlparser->getAttribute($previousOrigin, 'origin');
                if ($this->matchRateType->isPretranslationType($prevOriginVal)) {
                    $attributes->customMetaAttributes[$this->matchRateType::DATA_PREVIOUS_ORIGIN] = $prevOriginVal;
                    $this->setPreviousOriginSystemName($attributes, $xmlparser->getAttribute($previousOrigin, 'origin-system', ''));
                }
            }

            $attributes->autopropagated = $origin === 'auto-propagated';
            $attributes->locked = (bool) $xmlparser->getAttribute($tagAttributes, 'locked');
        });
        $xmlparser->parse(substr($transunit, $start, $end - $start));
    }

    /**
     * sets the origin system name in the attributes
     */
    protected function setPreviousOriginSystemName(
        editor_Models_Import_FileParser_SegmentAttributes $attributes,
        string $originSystemName
    ) {
        $attributes->customMetaAttributes[$this->matchRateType::DATA_PREVIOUS_NAME] = str_replace(
            ';',
            '_',
            $originSystemName
        );
    }

    /**
     * Stellt Tags-Abschnitt im Header als DOM-Objekt bereit
     *
     * - befüllt $this->_tagMapping mit dem Wert des Returns
     * - kann mit mehreren Tag-Defs Abschnitten umgehen. Geht davon aus, dass die Tag IDs über alle Tag-Defs Ascbhnitte hinweg eindeutig sind
     *
     *  structure: array('tagId' => array('text' => string '',['eptName' => string '', 'eptText' => string '']),'tagId2' => ...)
     */
    protected function prepareTagMapping()
    {
        $file = preg_split('"<tag-defs[^>]*>"', $this->_origFile);

        //den ersten Teil ohne Tag-Defs rauswerfen.
        array_shift($file);

        while ($data = array_shift($file)) {
            $this->extractTags($data);
        }
    }

    protected function readCxtMetaDefinitions()
    {
        $startMeta = strpos($this->_origFile, '<cxt-defs ');
        $endMeta = strpos($this->_origFile, '</cxt-defs>') + 11; //add the length of the end tag itself
        if ($startMeta === false || $endMeta === false) {
            return;
        }
        $cxtDefs = substr($this->_origFile, $startMeta, $endMeta - $startMeta);
        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */

        //collect infos about the following cxt nodes
        $xmlparser->registerElement('cxt-def[type=x-tm-length-info], cxt-def[type=fieldlength], cxt-def[type=linelength], cxt-def[type=linecount]', function ($tag, $attributes) use ($xmlparser) {
            $id = $xmlparser->getAttribute($attributes, 'id');
            //since most info is in the attributes, we just save them as cxt entry, and add additional info as new fields later
            $this->cxtDefinitions[$id] = $attributes;
            //             <cxt-def id="2" type="fieldlength" code="FL" name="Fieldlength" descr="1500" purpose="Match">
            //             <cxt-def id="3" type="linelength" code="LL" name="Linelength" descr="1500" purpose="Match">
            //             <cxt-def id="4" type="linecount" code="LC" name="Linecount" descr="1" purpose="Match">
        });

        //add specific length info to x-tm-length-info node
        $xmlparser->registerElement('cxt-def[type=x-tm-length-info] props value', null, function ($tag, $key, $opener) use ($xmlparser) {
            $valKey = $xmlparser->getAttribute($opener['attributes'], 'key');
            $value = $xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true);

            $cxt = $xmlparser->getParent('cxt-def');
            $id = $xmlparser->getAttribute($cxt['attributes'], 'id');

            if ($valKey == 'length_type') {
                $this->cxtDefinitions[$id]['_prop_length_type'] = $value;
            }

            if ($valKey == 'length_max_value') {
                $this->cxtDefinitions[$id]['_prop_length_max_value'] = $value;
            }
        });

        $xmlparser->parse($cxtDefs);
    }

    /**
     * extrahiert die Tags aus den einzelnen Tag-Defs Abschnitten
     */
    protected function extractTags(string $data): void
    {
        $data = explode('</tag-defs>', $data);
        $tags = '<tag-defs>' . $data[0] . '</tag-defs>';
        unset($data);
        //alle unicode-entities herauswerfen, da DomDocument
        //mit bestimmten unicode-Entities in Attributwerten nicht umgehen kann
        //(z. B. &#x1;)
        $tags = preg_replace('"&#x[0-9A-Fa-f]+;"', 'UNICODE_ENTITY', $tags);
        $dom = new DomDocument();
        if (! $dom->loadXML($tags)) {
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

            if (! isset($this->_tagDefMapping[$name]) && ! empty($name)) {
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
     * @return string contains the replacement-Tags <lekTargetSeg id=""/> instead the content, where id is the DB
     *     segment ID
     */
    protected function extractSegment($transUnit, array $groupCxtIds)
    {
        $this->segmentData = [];
        $numSegmentsInTransUnit = 0;
        $result = $this->transunitParser->parse(
            '<trans-unit' . $transUnit,
            function ($mid, $source, $target, $comments) use ($groupCxtIds, &$numSegmentsInTransUnit) {
                if (strlen(trim(strip_tags($source))) === 0 && strlen(trim(strip_tags($target))) === 0) {
                    return null;
                }

                $sdlxliffConfig = $this->config->runtimeOptions->import?->sdlxliff;
                // trimming all whitespaces including unicode whitespaces (e.g. non-breaking space)
                $sourceHasContent = mb_strlen(strip_tags(preg_replace('/\s+/u', '', $source))) !== 0;

                if ($sourceHasContent && $sdlxliffConfig?->cleanUpTargetOnSourceWithContentAndTagWhitespaceOnlyTarget) {
                    $emptyTarget = $target === null ||
                        mb_strlen(strip_tags(preg_replace('/\s+/u', '', $target))) === 0;
                    $target = $emptyTarget ? '' : $target;
                }

                $numSegmentsInTransUnit++;
                $sourceName = $this->segmentFieldManager->getFirstSourceName();
                $targetName = $this->segmentFieldManager->getFirstTargetName();

                $transUnitHash = $this->transunitHash->create(
                    $this->sourceFileId,
                    $this->transunitParser->getTransunitId()
                );

                $this->setMid($mid);

                //after defining the MID segment we have the mid and can access the attributes object,
                // to set the length attributes
                $attributes = $this->processCxtMetaTagsForSegment($groupCxtIds);

                $attributes->transunitHash = $transUnitHash;
                $attributes->transunitId = $this->transunitParser->getTransunitId();
                $attributes->mrkMid = $mid;

                [$parsedSource, $parsedTarget] = $this->contentProtector->filterTags(
                    $this->parseSegment($source, true),
                    $this->parseSegment($target, false)
                );

                $this->tagIdShortTagIdentMap = [];

                $this->segmentData[$sourceName] = [
                    'original' => $parsedSource,
                ];
                $this->segmentData[$targetName] = [
                    'original' => $parsedTarget,
                ];
                $segmentId = $this->setAndSaveSegmentValues();
                $this->saveComments($segmentId, $comments);

                return $this->getFieldPlaceholder($segmentId, $targetName);
            },
            $this->revIdToUserDataMap
        );

        // we have to limit the number of segments per tarns-unit as this might compromises further processing
        // (-> sibling-data)
        if ($numSegmentsInTransUnit > editor_Models_Import_Configuration::MAX_SEGMENTS_PER_TRANSUNIT) {
            throw new editor_Models_Import_FileParser_Exception('E1523', [
                'max' => editor_Models_Import_Configuration::MAX_SEGMENTS_PER_TRANSUNIT,
                'amount' => $numSegmentsInTransUnit,
                'transunitId' => $this->transunitParser->getTransunitId(),
                'task' => $this->task,
            ]);
        }

        // add leading <trans-unit for parsing, then strip it again
        // (we got the $transUnit without it, so we return it without it)
        return substr($result, 11);
    }

    /**
     * calculates and sets segment attributes needed by us, this info doesnt exist directly in the segment.
     * These are currently: pretrans, editable, autoStateId
     * Parameters are given by the current segment
     */
    protected function processCxtMetaTagsForSegment(array $groupCxtIds): editor_Models_Import_FileParser_SegmentAttributes
    {
        $attributes = $this->createSegmentAttributes($this->_mid);
        if (empty($groupCxtIds)) {
            return $attributes;
        }
        foreach ($groupCxtIds as $cxtId) {
            $cxtDef = $this->cxtDefinitions[$cxtId] ?? null;
            if (empty($cxtDef)) {
                continue;
            }
            //currently we use only type x-tm-length-info for length restrictions.
            //the following are collection too, but currently we do not know how to use them:
            //  cxt-def[type=fieldlength], cxt-def[type=linelength], cxt-def[type=linecount]'

            if ($cxtDef['type'] == 'x-tm-length-info' && $cxtDef['_prop_length_type'] != 'chars') {
                $this->logger->info('E1322', 'A CXT tag type x-tm-length-info with a unknown prop type "{propType}" was found.', [
                    'propType' => $cxtDef['_prop_length_type'],
                    'task' => $this->task,
                    'filename' => $this->_fileName,
                ]);
            }
            if ($cxtDef['type'] == 'x-tm-length-info' && $cxtDef['_prop_length_type'] == 'chars' && $cxtDef['_prop_length_max_value'] > 1) {
                //                 CXT DEF: Array
                //                 (
                //                     [id] => 1
                //                     [type] => x-tm-length-info
                //                     [purpose] => Match
                //                     [_prop_length_type] => chars
                //                     [_prop_length_max_value] => 1500
                //                 )
                $attributes->sizeUnit = 'char';
                $attributes->maxWidth = $cxtDef['_prop_length_max_value'];
            }

            if ($cxtDef['type'] == 'linecount' && $cxtDef['descr'] > 1) {
                //                 CXT DEF: Array
                //                 (
                //                     [id] => 4
                //                     [type] => linecount
                //                     [code] => LC
                //                     [name] => Linecount
                //                     [descr] => 1
                //                     [purpose] => Match
                //                 )
                $attributes->maxNumberOfLines = (int) $cxtDef['descr'];
            }

            //also known DEFs, but currently unknown how and when to use (seems to be duplicating x-tm-length-info
            //                 CXT DEF: Array
            //                 (
            //                     [id] => 3
            //                     [type] => linelength
            //                     [code] => LL
            //                     [name] => Linelength
            //                     [descr] => 1500
            //                     [purpose] => Match
            //                 )
            //             CXT DEF: Array
            //             (
            //                 [id] => 2
            //                 [type] => fieldlength
            //                 [code] => FL
            //                 [name] => Fieldlength
            //                 [descr] => 1500
            //                 [purpose] => Match
            //             )
        }

        return $attributes;
    }

    /**
     * Save the found comments to the DB
     */
    protected function saveComments(int $segmentId, array $comments)
    {
        foreach ($comments as $mrkId => $mrkCommentMarker) {
            if (empty($this->comments[$mrkId])) {
                continue;
            }

            $selectedTextChunks = $mrkCommentMarker['text'] ?? true;

            foreach ($this->comments[$mrkId] as $cmtDef) {
                $comment = ZfExtended_Factory::get('editor_Models_Comment');
                /* @var $comment editor_Models_Comment */
                $comment->setSegmentId($segmentId);
                $comment->setTaskGuid($this->task->getTaskGuid());
                $comment->setUserName($cmtDef['user'] ?? '');
                $comment->setUserGuid(self::USERGUID);
                if ($selectedTextChunks !== true) {
                    $cmtDef['comment'] = 'annotates selection "' . join(' ', $selectedTextChunks) . '": ' . "\n" . $cmtDef['comment'];
                }
                if ($mrkCommentMarker['field'] == self::SOURCE) {
                    $cmtDef['comment'] = "(annotates source column)\n" . $cmtDef['comment'];
                }
                $comment->setComment($cmtDef['comment']);
                $date = date('Y-m-d H:i:s', strtotime($cmtDef['date']));
                $comment->setCreated($date);
                $comment->setModified($date);
                $comment->save();

                $meta = $comment->meta();
                $meta->setOriginalId($mrkId);
                $meta->setAffectedField($mrkCommentMarker['field']);
                $meta->setSeverity($cmtDef['severity'] ?? 'Medium');
                $meta->setVersion($cmtDef['version'] ?? '1.0');
                $meta->save();
            }
            //if there was at least one processed comment, we have to sync the comment contents to the segment
            if (! empty($comment)) {
                $segment = ZfExtended_Factory::get('editor_Models_Segment');
                /* @var $segment editor_Models_Segment */
                $segment->load($segmentId);
                $comment->updateSegment($segment, $this->task->getTaskGuid());
            }
        }
    }

    /**
     * Hilfsfunktion für parseSegment: Festlegung der tagId im JS
     *
     * @param string $tag enthält den Tag als String
     * @param string $tagName enthält den Tagnamen
     * @return string $id ID des Tags im JS
     */
    protected function parseSegmentGetTagId($tag, $tagName)
    {
        if ($tagName == 'mrk') {
            if (preg_match('"<mrk [^>]*[^/]>"', $tag)) {
                return TagMappingConfig::TAG_MRK_PAIRED;
            }

            return TagMappingConfig::TAG_MRK_SINGLE;
        }

        return preg_replace('"<.* id=\"([^\"]*)\".*>"', '\\1', $tag);
    }

    /**
     * prüft, ob ein Tagname $this->_tagDefMapping definiert ist
     *
     * @param string $tagName
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function verifyTagName($tagName, $data)
    {
        if (! in_array($tagName, $this->_tagDefMapping)) {
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
    protected function parseSegment($segment, $isSource): string
    {
        $segment = editor_Models_Segment_Utility::foreachSegmentTextNode($segment, function ($text) use ($isSource) {
            return $this->contentProtector->protect(
                $text,
                $isSource,
                $this->task->getSourceLang(),
                $this->task->getTargetLang()
            );
        });
        if (strpos($segment, '<') === false) {
            return $segment;
        }
        $segment = $this->parseSegmentUnifyInternalTags($segment);
        $data = new editor_Models_Import_FileParser_Sdlxliff_ParseSegmentData();
        $data->segment = preg_split('"(<[^>]*>)"', $segment, flags: PREG_SPLIT_DELIM_CAPTURE);
        $data->segmentCount = count($data->segment);

        if (! $isSource) {
            $data->j = ! empty($this->tagIdShortTagIdentMap) ? max(array_values($this->tagIdShortTagIdentMap)) + 1 : 1;
        }

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
    protected function parseSegmentUnifyInternalTags($segment)
    {
        $search = [
            '#(<g [^>]*) +(/>)#',
            '#(<g [^>]*) +(>)#',
            '#(<mrk [^>]*) +(/>)#',
            '#(<mrk [^>]*) +(>)#',
            '#(<x [^>]*) +(/>)#',
            '#(<x [^>]*) +(>)#',
        ];
        $replace = [
            '\\1\\2',
            '\\1\\2',
            '\\1\\2',
            '\\1\\2',
            '\\1\\2',
            '\\1\\2',
        ];
        $segment = preg_replace($search, $replace, $segment);

        return $segment;
    }

    /**
     * parsing von left-Tags für parseSegment (öffnenden Tags)
     *
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     * @return editor_Models_Import_FileParser_Sdlxliff_parseSegmentData  $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function parseLeftTag($data)
    {
        $tag = $data->segment[$data->i];
        $tagName = preg_replace('"<([^ ]*).*>"', '\\1', $tag);

        if (in_array($tagName, ['ins', 'del'], true)) {
            return $data;
        }

        $data->openCounter++;

        $this->verifyTagName($tagName, $data);
        $tagId = $this->parseSegmentGetTagId($tag, $tagName);

        if (! is_numeric($tagId) && ! isset($this->quickInsertsList[$tagId])) {
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1609', [
                'tagname' => $tagId,
                'filename' => $this->_fileName ?: 'not provided',
                'task' => $this->task,
            ]);
        }

        if (isset($this->quickInsertsList[$tagId])) {
            $formatName = $tagId;
            $tagId .= $data->i;
            $this->_tagMapping[$tagId]['originalId'] = $formatName;
            $this->_tagMapping[$tagId]['text'] = $formatName;
            $this->_tagMapping[$tagId]['eptText'] = $formatName;
        }

        $shortTagIdent = null;

        if (str_contains($this->_tagMapping[$tagId]['text'], 'ctype=&quot;x-html-')) {
            $shortTagIdent = $data->j++;
        } elseif (! isset($this->tagIdShortTagIdentMap[$tagId])) {
            $this->tagIdShortTagIdentMap[$tagId] = $data->j++;
        }

        $shortTagIdent = $shortTagIdent ?? $this->tagIdShortTagIdentMap[$tagId];

        if (str_contains($tagId, 'locked')) {
            //The opening tag $tagName contains a non valid tagId according to our reverse engineering
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1001', [
                'task' => $this->task,
                'filename' => $this->_fileName,
                'tagName' => $tagName,
                'tagId' => $tagId,
                'segment' => implode('', $data->segment),
            ]);
        }

        // if the tag is a start=false tag - that is phantom tag opener
        // we do not replace it with a tag for the editor
        if (! str_contains($tag, 'sdl:start="false"')) {
            $data->openTags[$data->openCounter]['tagName'] = $tagName;
            $data->openTags[$data->openCounter]['tagId'] = $tagId;
            $data->openTags[$data->openCounter]['nr'] = $shortTagIdent;

            //ersetzte gegen Tag für die Anzeige
            $tagObj = new editor_Models_Import_FileParser_Tag(editor_Models_Import_FileParser_Tag::TYPE_OPEN);
            $tagObj->originalContent = $tag;
            $tagObj->tagNr = $shortTagIdent;
            $tagObj->id = $this->_tagMapping[$tagId]['originalId'] ?? $tagId;
            $tagObj->text = $this->encodeTagsForDisplay($this->_tagMapping[$tagId]['text']);
            $data->segment[$data->i] = $tagObj->renderTag();
        } else {
            $data->segment[$data->i] = '';
        }

        return $data;
    }

    /**
     * parsing von right-Tags für parseSegment (schließenden Tags)
     *
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     * @return editor_Models_Import_FileParser_Sdlxliff_parseSegmentData  $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function parseRightTag($data)
    {
        $tagName = trim(str_replace(['<', '/', '>'], '', $data->segment[$data->i]));
        if (in_array($tagName, ['ins', 'del'], true)) {
            return $data;
        }

        $isTrackChangeClosing = strpos($tagName, 'sdl:start="false"');

        if (empty($data->openTags[$data->openCounter]) && ! $isTrackChangeClosing) {
            //Found a closing tag without an opening one!
            throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1002', [
                'task' => $this->task,
                'filename' => $this->_fileName,
                'mid' => $this->_mid,
                'data' => $data,
            ]);
        }

        $openTag = $isTrackChangeClosing ? $this->getOpenTag($tagName, $data) : $data->openTags[$data->openCounter];

        $mappedTag = $this->_tagMapping[$openTag['tagId']];

        //generate the html tag for the editor
        $tagObj = new editor_Models_Import_FileParser_Tag(editor_Models_Import_FileParser_Tag::TYPE_CLOSE);
        $tagObj->originalContent = $data->segment[$data->i];
        $tagObj->tagNr = $openTag['nr'];
        $tagObj->id = $mappedTag['originalId'] ?? $openTag['tagId'];
        $tagObj->text = $this->encodeTagsForDisplay($mappedTag['eptText']);
        $data->segment[$data->i] = $tagObj->renderTag();

        if (! empty($data->openTags[$data->openCounter])) {
            $data->openCounter--;
        }

        return $data;
    }

    private function getOpenTag(string $tagName, object $data): array
    {
        preg_match('#id="(\d+)"#', $tagName, $matches);
        $tagId = $matches[1];

        foreach ($data->openTags as $openTag) {
            if ($openTag['tagId'] === $tagId) {
                return $openTag;
            }
        }

        throw new editor_Models_Import_FileParser_Sdlxliff_Exception('E1002', [
            'task' => $this->task,
            'filename' => $this->_fileName,
            'mid' => $this->_mid,
            'data' => $data,
        ]);
    }

    /**
     * parsing von single-Tags für parseSegment (selfclosing oder placeholder Tags)
     *
     * @param editor_Models_Import_FileParser_Sdlxliff_parseSegmentData $data enthält alle für das Segmentparsen wichtigen Daten
     * @return editor_Models_Import_FileParser_Sdlxliff_parseSegmentData  $data enthält alle für das Segmentparsen wichtigen Daten
     */
    protected function parseSingleTag($data)
    {
        $tag = $data->segment[$data->i];
        $tagName = preg_replace('"<([^/ ]*).*>"', '\\1', $tag);

        if ($this->contentProtector->hasTagsToConvert($tag)) {
            //tag trait is working with shortTagIdent internally, so we have to feed it here
            $this->shortTagIdent = $data->j++;
            $data->segment[$data->i] = $this->contentProtector->convertToInternalTags($tag, $this->shortTagIdent);

            return $data;
        }

        $this->verifyTagName($tagName, $data);
        $tagId = $this->parseSegmentGetTagId($tag, $tagName);

        $shortTagIdent = null;

        if (TagMappingConfig::TAG_MRK_SINGLE === $tagId) {
            $shortTagIdent = $data->j;
        } elseif (! isset($this->tagIdShortTagIdentMap[$tagId])) {
            $this->tagIdShortTagIdentMap[$tagId] = $data->j++;
        }

        $shortTagIdent = $shortTagIdent ?? $this->tagIdShortTagIdentMap[$tagId];

        if (strpos($tagId, 'locked') !== false) {
            $this->setLockedTagContent($tag, $tagId);
            $shortTagIdent = 'locked' . $data->j;
        }

        if (strpos($tag, 'sdl:end="false"') !== false) {
            $data->openTags[$data->openCounter]['tagName'] = $tagName;
            $data->openTags[$data->openCounter]['tagId'] = $tagId;
            $data->openTags[$data->openCounter]['nr'] = $shortTagIdent;
        }

        $tagType = match (true) {
            str_contains($tag, 'sdl:end="false"') => editor_Models_Import_FileParser_Tag::TYPE_OPEN,
            str_contains($tag, 'sdl:start="false"') => editor_Models_Import_FileParser_Tag::TYPE_CLOSE,
            default => editor_Models_Import_FileParser_Tag::TYPE_SINGLE,
        };

        //generate the html tag for the editor
        $tagObj = new editor_Models_Import_FileParser_Tag($tagType);
        $tagObj->originalContent = $tag;
        $tagObj->tagNr = $shortTagIdent;
        $tagObj->id = $tagId;
        $tagObj->text = $this->encodeTagsForDisplay($this->_tagMapping[$tagId]['text']);
        $data->segment[$data->i] = $tagObj->renderTag();

        $data->j++;

        return $data;
    }

    protected function extractComments()
    {
        $startComments = strpos($this->_origFile, '<cmt-defs');
        $endComments = strpos($this->_origFile, '</cmt-defs>') + 11; //add the length of the end tag
        if ($startComments === false || $startComments >= $endComments) {
            return;
        }
        $comments = substr($this->_origFile, $startComments, $endComments - $startComments);
        if (empty($comments)) {
            return;
        }

        // if comments import is disabled we log a warning
        if (! $this->config->runtimeOptions->import->sdlxliff->importComments) {
            $this->logger->warn('E1000', 'The file "{filename}" has contained SDL comments, but comment import is disabled.', [
                'task' => $this->task,
                'filename' => $this->_fileName,
            ]);

            return;
        }

        // remove the cmt-defs block for the file. It will be generated on export again
        $this->_origFile = substr_replace($this->_origFile, '', $startComments, $endComments - $startComments);

        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */
        $xmlparser->registerElement('comment', null, function ($tag, $key, $opener) use ($xmlparser) {
            $cmtDef = $xmlparser->getParent('cmt-def');
            if (empty($cmtDef)) {
                return;
            }
            $id = $xmlparser->getAttribute($cmtDef['attributes'], 'id');
            if (empty($this->comments[$id])) {
                $this->comments[$id] = [];
            }
            $comment = $opener['attributes'];
            $comment['comment'] = $xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true);
            $this->comments[$id][] = $comment;
        });
        $xmlparser->parse($comments);
    }

    /**
     * Removes the rev-def(s) tags from the sdlxliff
     */
    protected function processRevDefs()
    {
        //checkForSdlChangeMarker throws an exception if import change mark feature is disabled in config
        $startRevs = strpos($this->_origFile, '<rev-defs');
        $endRevs = strpos($this->_origFile, '</rev-defs>') + 11; //add the length of the end tag

        if ($startRevs === false || $startRevs >= $endRevs) {
            return;
        }

        if ($this->isTrackChangesPluginActive) {
            $xmlParser = new editor_Models_Import_FileParser_XmlParser();
            $xmlParser->registerElement(
                'rev-def',
                null,
                function (string $tag, int $key, array $opener): void {
                    $this->revIdToUserDataMap[$opener['attributes']['id']] = [
                        'author' => trim(preg_replace('/\s{2,}/', ' ', $opener['attributes']['author'])),
                        'date' => DateTime::createFromFormat('m/d/Y H:i:s', $opener['attributes']['date']),
                        'workflowStep' => $this->task->getWorkflow() . $this->task->getWorkflowStep(),
                    ];
                }
            );
            $xmlParser->parse(substr($this->_origFile, $startRevs, $endRevs - $startRevs));
        }

        $this->_origFile = substr_replace($this->_origFile, '', $startRevs, $endRevs - $startRevs);
    }
}
