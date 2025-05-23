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

use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\Segment\TransUnitHash;

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Contains Methods for Fileparsing on the Import
 *
 * - Child Classes must implement and use the abstract methods
 * - Every ChildClass in /FileParsers must be registered in MittagQI\Translate5\Task\FileTypeSupport::coreParsers
 */
abstract class editor_Models_Import_FileParser
{
    /**
     * @var string|null
     */
    protected $_origFile = null;

    protected string $skeletonFile = '';

    /**
     * @var string
     */
    protected $_fileName = null;

    /**
     * @var integer
     */
    protected $_fileId = null;

    /**
     * array containing all segment data parsed
     * @var array 2D array, first level has keys which map to the segment field names. Second Level array must be compliant to editor_Models_Db_SegmentDataRow
     */
    protected $segmentData = [];

    /**
     * @var string mid des aktuellen Segments
     */
    protected $_mid = null;

    /**
     * contains a SegmentAttributes object (value) per mid (key)
     * @var editor_Models_Import_FileParser_SegmentAttributes[]
     */
    protected $segmentAttributes = [];

    /**
     * @var editor_Models_Task
     */
    protected $task;

    /**
     * @var string taskGuid
     */
    protected $_taskGuid = null;

    /**
     * Contains a list of processors of the parsed segmentdata
     * @var [editor_Models_Import_SegmentProcessor]
     */
    protected $segmentProcessor = [];

    /**
     * all files with extensions listed here are converted to utf8. For details see method convert2utf8
     * @var array
     */
    protected $_convert2utf8 = ['csv'];

    /**
     * @var string path to the file in the encoding of the filesystem (runtimeOptions.fileSystemEncoding)
     */
    protected $_path;

    /**
     * Auto State Definer
     * @var editor_Models_Segment_AutoStates
     */
    protected $autoStates;

    /**
     * MatchRateType calculator / converter
     * @var editor_Models_Segment_MatchRateType
     */
    protected $matchRateType;

    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;

    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * @var editor_Models_Segment_UtilityBroker
     */
    protected $utilities;

    /**
     * counter for internal tags
     * @var integer
     */
    protected int $shortTagIdent = 1;

    protected bool $isReimport = false;

    /**
     * Flag if the current file parser supports re-import functionality
     */
    public const IS_REIMPORTABLE = false;

    /***
     * Identifier defined in one of the tags for the currently processed file.
     * For example for xlf file, this is the original value from the file tag.
     * ex: <file original="file1.html" source-language="de" target-language="en" datatype="html">
     * @var string
     */
    protected string $sourceFileId = '';

    protected TransUnitHash $transunitHash;

    protected ContentProtector $contentProtector;

    /**
     * returns the file extensions (in lower case) parsable by this fileparser
     * @return array;
     */
    public static function getFileExtensions()
    {
        throw new ZfExtended_Exception('Method must be overwritten in subclass!'); //with strict standards statics may not abstract!
    }

    /***
     * Get the export class out of the import class file name with replacing the Import with Export
     * folder name. It is expected that the import and export classes are in Import/Export folders accordingly
     */
    public static function getExportClass(): string
    {
        if (str_contains(static::class, '\\Import\\')) {
            return str_replace('\\Import\\', '\\Export\\', static::class);
        }

        return str_replace('_Import_', '_Export_', static::class);
    }

    /**
     * Basically we assume that each Parser is able to parse its exceptions, so this functions defaults to return true.
     * If this not the case forexample due multi purpose extensions like XML, the isParsable must be calculated in the
     *  concrete file parser.
     * @param string $fileHead the first 512 bytes of the file to be imported
     * @param string $errorMsg returning by reference a reason why its not parsable
     */
    public static function isParsable(string $fileHead, string &$errorMsg): bool
    {
        $errorMsg = '';

        return true;
    }

    /**
     * FIXME change first Parameter to SplFileInfo!
     * @param string $path pfad zur Datei in der Kodierung des Filesystems (also runtimeOptions.fileSystemEncoding)
     * @param string $fileName Dateiname utf-8 kodiert
     * @throws ReflectionException
     * @throws editor_Models_ConfigException
     * @throws editor_Models_Import_FileParser_Exception
     */
    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task)
    {
        $this->config = $task->getConfig();

        $this->loadOriginalFile($path);
        $this->_path = $path;
        $this->_fileName = $fileName;
        $this->_fileId = $fileId;
        $this->transunitHash = ZfExtended_Factory::get(TransUnitHash::class, [$this->config, $fileId]);
        $this->task = $task;
        $this->_taskGuid = $task->getTaskGuid();
        $this->autoStates = ZfExtended_Factory::get(editor_Models_Segment_AutoStates::class);
        $this->matchRateType = ZfExtended_Factory::get(editor_Models_Segment_MatchRateType::class);
        $this->updateFile(get_class($this));

        $this->utilities = ZfExtended_Factory::get(editor_Models_Segment_UtilityBroker::class);

        $this->contentProtector = ContentProtector::create($this->utilities->whitespace);
    }

    /**
     * Loads the file into the fileparser
     */
    protected function loadOriginalFile(string $path)
    {
        $this->_origFile = file_get_contents($path);
    }

    /**
     * Prototyp-function for getting word-count while import process.
     * This function is (or is not) overwritten by typ-specific import-parser
     */
    public function getWordCount()
    {
        return false;
    }

    public function addSegmentProcessor(editor_Models_Import_SegmentProcessor $proc)
    {
        $this->segmentProcessor[] = $proc;
    }

    /**
     * set the shared instance of the segmentFieldManager
     * @param $sfm editor_Models_SegmentFieldManager
     */
    public function setSegmentFieldManager(editor_Models_SegmentFieldManager $sfm)
    {
        $this->segmentFieldManager = $sfm;
        $this->initDefaultSegmentFields();
    }

    /**
     * encodes special chars to entities for display in title-Attributs and text of tags in the segments
     * because studio sometimes writes tags in the description of tags (i.e. in locked tags)
     *
     * @param string text
     * @return string text
     */
    protected function encodeTagsForDisplay($text)
    {
        return str_replace(['"', "'", '<', '>'], ['&quot;', '&#39;', '&lt;', '&gt;'], $text);
    }

    /**
     * returns the internally used SegmentFieldManager
     * @return editor_Models_SegmentFieldManager
     */
    public function getSegmentFieldManager()
    {
        return $this->segmentFieldManager;
    }

    /**
     * Gibt den Inhalt das erzeugte Skeleton File zurück
     */
    public function getSkeletonFile(): string
    {
        return $this->skeletonFile;
    }

    /**
     * does the fileparsing
     * - calls extractSegment, parseSegmentAttributes
     */
    abstract protected function parse();

    /**
     * initiates the default fields source and target, should be overwritten if fields differ.
     */
    protected function initDefaultSegmentFields()
    {
        $sfm = $this->segmentFieldManager;
        $sourceEdit = (bool) $this->task->getEnableSourceEditing();
        $sfm->addField($sfm::LABEL_SOURCE, editor_Models_SegmentField::TYPE_SOURCE, $sourceEdit);
        $sfm->addField($sfm::LABEL_TARGET, editor_Models_SegmentField::TYPE_TARGET);
    }

    /**
     * Does the fileparsing
     * FIXME replace the pre and post parse handlers with events
     */
    public function parseFile()
    {
        foreach ($this->segmentProcessor as $p) {
            $p->preParseHandler($this);
        }
        $this->parse();
        $this->matchRateType->logErrors($this->_fileName, $this->task);
        foreach ($this->segmentProcessor as $p) {
            $p->postParseHandler($this);
        }
    }

    /**
     * Speichert das Segment in die Datenbank
     * FIXME replace the post process handlers with events
     *
     * @param mixed transunit
     * @return integer segmentId
     */
    protected function setAndSaveSegmentValues()
    {
        $this->setCalculatedSegmentAttributes();
        $result = false;

        foreach ($this->segmentData as &$field) {
            //preset the md5 field with the plain string
            //the different processors have then the ability to modify it
            //the final segment processor creates then the hash before storing it into the DB
            $field['originalMd5'] = $field['original'] ?? null;
        }

        foreach ($this->segmentProcessor as $p) {
            /* @var $p editor_Models_Import_SegmentProcessor */
            $r = $p->process($this);
            if ($r !== false) {
                $result = $r;
            }
        }
        foreach ($this->segmentProcessor as $p) {
            $p->postProcessHandler($this, $result);
        }

        return $result;
    }

    /**
     * creates a new (or returns the already existing) segment attributes object, and stores it internally to the given mid
     * @param string $forMid
     */
    protected function createSegmentAttributes($forMid): editor_Models_Import_FileParser_SegmentAttributes
    {
        if (isset($this->segmentAttributes[$forMid])) {
            return $this->segmentAttributes[$forMid];
        }
        $segAttr = ZfExtended_Factory::get('editor_Models_Import_FileParser_SegmentAttributes');

        /* @var $segAttr editor_Models_Import_FileParser_SegmentAttributes */
        return $this->segmentAttributes[$forMid] = $segAttr;
    }

    /**
     * returns a already existing segment attributes object
     * @param string $forMid
     * @return editor_Models_Import_FileParser_SegmentAttributes
     */
    public function getSegmentAttributes($forMid)
    {
        //just call the create call, since it does what we want,
        //  but the method name is misleading here
        return $this->createSegmentAttributes($forMid);
    }

    /**
     * returns a placeholder for reexport the edited content
     * @param int $segmentId
     * @param string $name
     */
    protected function getFieldPlaceholder($segmentId, $name)
    {
        return '<lekTargetSeg id="' . $segmentId . '" field="' . $name . '" />';
    }

    /**
     * checks the encoding of the file and saves the encoding to the file-table
     * - only saves encoding for formats listed in this->_convert2utf8
     * @throws editor_Models_Import_FileParser_Exception
     */
    protected function updateFile(string $usedParser)
    {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($this->_fileId);
        $file->setFileParser($usedParser);

        $convert = false;
        foreach ($this->_convert2utf8 as $format) {
            if (preg_match('"\.' . $format . '$"i', $this->_fileName) === 1) {
                $convert = true;
            }
        }
        if ($convert) {
            $enc = $this->checkAndConvert2utf8();
            if (! $enc) {
                //'The encoding of the file "{fileName}" is none of the encodings utf-8, iso-8859-1 and win-1252.'
                throw new editor_Models_Import_FileParser_Exception('E1083', [
                    'fileName' => $this->_fileName,
                    'task' => $this->task,
                ]);
            }
            $file->setEncoding($enc);
        }
        $file->save();
    }

    /**
     * converts all fileformats listed in $this->_convert2utf8 to utf8, if possible
     * - calls saveEncoding, catches exeption
     * - converts Latin1 and windows-1252-encoded file to utf-8, even if part of
     *   file is in utf8 already. In any other case a user_error is triggered
     * - if exception is caught, saveEncoding is called after conversion
     *   again and this time a caught exception triggers a user_error
     * @return string encoding | false if encoding is none of utf-8, 'iso-8859-1', 'windows-1251'
     */
    protected function checkAndConvert2utf8()
    {
        if (mb_detect_encoding($this->_origFile, 'UTF-8', true)) {
            return 'UTF-8';
        }

        $list = ['iso-8859-1', 'windows-1251'];

        foreach ($list as $item) {
            $sample = iconv($item, $item, $this->_origFile);
            if (md5($sample) == md5($this->_origFile)) {
                $this->_origFile = iconv($item, 'UTF-8', $this->_origFile);

                return $item;
            }
        }

        return false;
    }

    /**
     * checks and sets the given MID internally
     * Because of DB reasons we only accept a 60chars long MID. If the given value was longer we trigger an error.
     * @param string $mid
     */
    protected function setMid($mid)
    {
        if (mb_strlen($mid) > 1000) {
            //Given MID was to long (max 1000 chars)
            throw new editor_Models_Import_FileParser_Exception('E1084', [
                'mid' => $mid,
                'task' => $this->task,
            ]);
        }
        $this->_mid = $mid;
    }

    /**
     * Generate the mid value out of the transunitHash and the mid
     * @throws editor_Models_Import_FileParser_Exception
     */
    protected function setMidWithHash(string $transunitHash, string $mid): void
    {
        $this->setMid($transunitHash . '_' . $mid);
    }

    /**
     * calculates and sets segment attributes needed by us, this info doesnt exist directly in the segment.
     * These are currently: pretrans, editable, autoStateId
     * Parameters are given by the current segment
     * @return editor_Models_Import_FileParser_SegmentAttributes
     */
    protected function setCalculatedSegmentAttributes()
    {
        $attributes = $this->getSegmentAttributes($this->_mid);

        $isAutoprop = $attributes->autopropagated;
        $isLocked = $attributes->locked && (bool) $this->task->getLockLocked();

        $isFullMatch = ($attributes->matchRate >= 100 && $attributes->isPreTranslated);
        $attributes->isTranslated = $this->isTranslated();

        //calculate isEditable only if it was not explicitly set
        if (! isset($attributes->editable)) {
            //$isFullMatch implies that the source was a TM (for imported data)
            $attributes->editable = (! $isFullMatch || (bool) $this->task->getEdit100PercentMatch() || $isAutoprop) && ! $isLocked;
        }

        $attributes->autoStateId = $this->autoStates->calculateImportState($attributes);

        //if there was a matchRateType from the imported segment, then the original value was stored
        $attributes->matchRateType = $this->matchRateType->parseImport($attributes, $this->_mid);

        return $attributes;
    }

    /**
     * returns true if at least one target has a translation set
     */
    protected function isTranslated()
    {
        foreach ($this->segmentData as $name => $data) {
            $field = $this->segmentFieldManager->getByName($name);
            if ($field->type !== editor_Models_SegmentField::TYPE_TARGET) {
                continue;
            }
            if (! (empty($data['original']) && $data['original'] !== "0")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gibt die MID des aktuellen Segments zurück
     * @return string
     */
    public function getMid()
    {
        return $this->_mid;
    }

    /**
     * returns a reference to the array with all parsed data fields
     * The reference enables the ability to manipulate the parsed data in the segmentprocessors (see MqmParser)
     * @see editor_Models_Import_SegmentProcessor_MqmParser
     */
    public function &getFieldContents()
    {
        return $this->segmentData;
    }

    /***
     * @return string|null
     */
    public function getFileName()
    {
        return $this->_fileName;
    }

    /**
     * Set internal isReimport flag!
     */
    public function setIsReimport(bool $isReimport = true)
    {
        $this->isReimport = $isReimport;
    }

    /***
     * @return editor_Models_Task
     */
    public function getTask(): editor_Models_Task
    {
        return $this->task;
    }

    public function getFileId(): ?int
    {
        return $this->_fileId;
    }
}
