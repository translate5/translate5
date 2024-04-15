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

use editor_Models_Segment_InternalTag as InternalTag;
use MittagQI\Translate5\ContentProtection\ContentProtector;

/**
 * Fileparsing for Csv-files
 *
 * - parent class should ensure, that file already is utf-8-encoded
 */
class editor_Models_Import_FileParser_Csv extends editor_Models_Import_FileParser_Csv_Base
{
    /**
     * string "source" as defined in application.ini column definition
     * @var string
     */
    public const CONFIG_COLUMN_SOURCE = 'source';

    /**
     * string "mid" as defined in application.ini column definition
     * @var string
     */
    public const CONFIG_COLUMN_MID = 'mid';

    /**
     * @var array order of the columns - which column is mid, source and target
     */
    protected $colOrder = [];

    /**
     * @var string line break chars of the csv file
     */
    protected $break;

    /**
     * @var string
     */
    protected $_delimiter;

    /**
     * @var string
     */
    protected $_enclosure;

    /**
     * @var array
     */
    protected $replaceRegularExpressionsBeforeTagParsing = [];

    /**
     * @var array
     */
    protected $replaceRegularExpressionsAfterTagParsing = [];

    /**
     * @var string regex describing the structure of translate5 internal tags
     */
    protected string $regexInternalTags = editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS;

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions()
    {
        return ['csv'];
    }

    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task)
    {
        parent::__construct($path, $fileName, $fileId, $task);

        $this->_delimiter = $this->config->runtimeOptions->import->csv->delimiter;
        $this->_enclosure = $this->config->runtimeOptions->import->csv->enclosure;

        $options = $this->config->runtimeOptions->import->fileparser->csv->options;
        if (isset($options->regexes->beforeTagParsing->regex)) {
            $this->addReplaceRegularExpression($options->regexes->beforeTagParsing->regex, 'replaceRegularExpressionsBeforeTagParsing');
        }
        if (isset($options->regexes->afterTagParsing->regex)) {
            $this->addReplaceRegularExpression($options->regexes->afterTagParsing->regex, 'replaceRegularExpressionsAfterTagParsing');
        }
    }

    /**
     * @param mixed $regExes object or string
     * @param string $regexArrayName
     */
    protected function addReplaceRegularExpression($regExes, $regexArrayName)
    {
        if (empty($regExes)) {
            return;
        }
        if (is_string($regExes)) {
            $regExes = [$regExes];
        } else {
            $regExes = $regExes->toArray();
        }
        foreach ($regExes as $regEx) {
            try {
                preg_match($regEx, 'hello world !!!');
            } catch (Exception $e) {
                $log = ZfExtended_Factory::get('ZfExtended_Log');
                /* @var $log ZfExtended_Log */
                $message = (__CLASS__ . ' -> ' . __FUNCTION__ . '; CSV-import: invalid regularExpression ' . $regEx);
                $log->logError('invalid regular expression', $message);

                continue;
            }
            if (preg_match($regEx, $this->placeholderPrefix) === 1) {
                throw new editor_Models_Import_FileParser_Csv_Exception('E1017', [
                    'regex' => $regEx,
                    'placeholder' => $this->placeholderPrefix,
                    'task' => $this->task,
                ]);
            }
            $regexArray = &$this->$regexArrayName;
            $regexArray[] = $regEx;
        }
    }

    /**
     * returns the csv content of one line, or false if line was empty / or eof reached
     * @return array $line or boolean false if nothing found in line
     */
    protected function prepareLine(SplTempFileObject $csv)
    {
        $line = $csv->fgetcsv($this->_delimiter, $this->_enclosure);
        //empty lines or eof trigger false
        if (count($line) === 1 && empty($line[0]) || is_null($line)) {
            return false;
        }

        if ($line === false) {
            //Error on parsing a line of CSV. Current line is: "{line}". Error could also be in previous line!
            throw new editor_Models_Import_FileParser_Csv_Exception('E1075', [
                'line' => $csv->current(),
                'task' => $this->task,
            ]);
        }
        if (! isset($line[2])) {
            //In the line "{line}" there is no third column.
            throw new editor_Models_Import_FileParser_Csv_Exception('E1076', [
                'line' => implode($this->_enclosure . $this->_delimiter . $this->_enclosure, $line),
                'task' => $this->task,
            ]);
        }

        return $line;
    }

    /**
     * does the FileParsing
     *
     * - split file into lines, regardless of linebreak-char
     * - skip first line (because of column headers)
     * - integrate extractSegment
     * - join file again for saving of skeleton file"
     */
    protected function parse()
    {
        //@todo is the following link a improvement? http://stackoverflow.com/questions/11066857/detect-eol-type-using-php
        if (preg_match('"\r\n"', $this->_origFile)) {
            $this->break = "\r\n";
        } elseif (preg_match('"\n"', $this->_origFile)) {
            $this->break = "\n";
        } elseif (preg_match('"\r"', $this->_origFile)) {
            $this->break = "\r";
        } else {
            //no linebreak found in CSV: file
            throw new editor_Models_Import_FileParser_Csv_Exception('E1077', [
                'file' => $this->_fileName,
                'task' => $this->task,
            ]);
        }

        $csv = new SplTempFileObject();
        //we skip empty lines in the CSV files
        $csv->fwrite($this->_origFile);
        $csv->rewind();
        unset($this->_origFile); //save memory, is not needed anymore.

        //check header and column order
        $csvSettings = $this->config->runtimeOptions->import->csv->fields->toArray();
        //$csvSettings quelle => source, mid => mid
        $header = $this->prepareLine($csv);
        if ($header === false) {
            //no header column found in CSV: file
            throw new editor_Models_Import_FileParser_Csv_Exception('E1078', [
                'file' => $this->_fileName,
                'task' => $this->task,
            ]);
        }
        $skel = [$this->str_putcsv($header, $this->_delimiter, $this->_enclosure, $this->break)];

        $missing = array_diff($csvSettings, $header);
        if (! empty($missing)) {
            // in application.ini configured column-header(s) "headers" not found in CSV: file
            throw new editor_Models_Import_FileParser_Csv_Exception('E1079', [
                'headers' => join(';', $missing),
                'file' => $this->_fileName,
                'task' => $this->task,
            ]);
        }
        if (count($header) < 3) {
            // source and mid given but no more data columns found in CSV: file
            throw new editor_Models_Import_FileParser_Csv_Exception('E1080', [
                'file' => $this->_fileName,
                'task' => $this->task,
            ]);
        }
        $i = 0;
        $csvSettings = array_flip($csvSettings);
        $foundHeader = [];
        foreach ($header as $colHead) {
            $type = false;

            //we ignore empty colHeads on import, so we have to track their col position
            if (empty($colHead) && $colHead !== "0") {
                $i++; //increase the col index, but do nothing else!

                continue;
            }

            //get type and editable state of the field
            if (! isset($csvSettings[$colHead]) || strlen($csvSettings[$colHead]) === 0) {
                //if no column is configured, its a target
                $type = editor_Models_SegmentField::TYPE_TARGET;
                $editable = true;
            } elseif ($csvSettings[$colHead] == self::CONFIG_COLUMN_SOURCE) {
                $type = editor_Models_SegmentField::TYPE_SOURCE;
                $editable = (bool) $this->task->getEnableSourceEditing();
            } elseif ($csvSettings[$colHead] == self::CONFIG_COLUMN_MID) {
                $this->colOrder[self::CONFIG_COLUMN_MID] = $i++;

                continue;
            }

            //we ensure that columns with the same name in one csv file are made unique
            // this is needed by addfield to map fields between different files
            // if mid exists multiple times in the header, only the last one is used.
            if (isset($foundHeader[$colHead]) && strlen($foundHeader[$colHead]) > 0) {
                $colHead .= '_' . ($foundHeader[$colHead]++);
            } else {
                $foundHeader[$colHead] = 1;
            }
            $name = $this->segmentFieldManager->addField($colHead, $type, $editable);

            $this->colOrder[$name] = $i++;
        }
        while (! $csv->eof()) {
            $line = $this->prepareLine($csv);
            //ignore empty lines:
            if ($line === false) {
                $skel[] = $this->break;

                continue;
            }
            $extracted = $this->extractSegment($line);
            $skel[] = $this->str_putcsv($extracted, $this->_delimiter, $this->_enclosure, $this->break);
        }
        $this->skeletonFile = join('', $skel);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::initDefaultSegmentFields()
     */
    protected function initDefaultSegmentFields()
    {
        //CSV adds all fields automatically, so omit default fields here.
    }

    /**
     * extracts the segment and saves them to db
     *
     * @return array $transUnit
     */
    protected function extractSegment($lineArr)
    {
        $this->segmentData = [];
        foreach ($this->colOrder as $name => $idx) {
            if ($name == self::CONFIG_COLUMN_MID) {
                $this->setMid($lineArr[$idx]);

                continue;
            }

            $field = $this->segmentFieldManager->getByName($name);
            $isSource = $field->type == editor_Models_SegmentField::TYPE_SOURCE;

            if (empty($lineArr[$idx]) && $lineArr[$idx] !== "0") {
                $original = '';
            } else {
                $original = $lineArr[$idx];
            }

            $this->segmentData[$name] = [
                'original' => $this->parseSegment($original, $isSource),
            ];
        }

        [$source, $target] = $this->contentProtector->filterTags(
            $this->segmentData[editor_Models_SegmentField::TYPE_SOURCE]['original'],
            $this->segmentData[editor_Models_SegmentField::TYPE_TARGET]['original']
        );

        $this->segmentData[editor_Models_SegmentField::TYPE_SOURCE]['original'] = $this->convertSegmentToInternalTags($source);
        $this->segmentData[editor_Models_SegmentField::TYPE_TARGET]['original'] = $this->convertSegmentToInternalTags($target);

        //just create a segment attributes object with default values
        $this->createSegmentAttributes($this->_mid);

        $segmentId = $this->setAndSaveSegmentValues();
        foreach ($this->colOrder as $name => $idx) {
            $field = $this->segmentFieldManager->getByName($name);
            if ($field && $field->editable) {
                $lineArr[$idx] = $this->getFieldPlaceholder($segmentId, $name);
            }
        }

        return $lineArr;
    }

    private function convertSegmentToInternalTags(string $segment): string
    {
        $this->shortTagIdent = 1;
        $segment = $this->contentProtector->convertToInternalTags($segment, $this->shortTagIdent);
        $segment = $this->parseSegmentInsertPlaceholders($segment, InternalTag::REGEX_INTERNAL_TAGS);

        return $this->parseSegmentReplacePlaceholders($segment);
    }
    
    /**
     * @param mixed $segment
     * @param bool isSource
     * @return string $segment
     */
    protected function parseSegment($segment, $isSource)
    {
        //check, if $this->placeholderPrefix is present in segment - this must lead to error
        if (strpos($segment, $this->placeholderPrefix) !== false) {
            throw new editor_Models_Import_FileParser_Csv_Exception('E1018', [
                'placeholder' => $this->placeholderPrefix,
                'task' => $this->task,
            ]);
        }

        //at first protect MQM - they will be converted to MQM-tags later by MqmParser
        //for performance reasons only do this, if escaping MQM is necessary for later protections
        if (strpos($segment, '<mqm:') !== false) {
            $segment = $this->parseSegmentInsertPlaceholders($segment, '#(<mqm:startIssue[^>]+/>)#');
            $segment = $this->parseSegmentInsertPlaceholders($segment, '#(<mqm:endIssue[^>]+/>)#');
        }

        // protect regExes before tag parsing
        $segment = $this->parseSegmentRegEx($segment, $this->replaceRegularExpressionsBeforeTagParsing);

        // add tag-protection if enabled
        if ($this->config->runtimeOptions->import->fileparser->options->protectTags ?? false) {
            // because of the replaceRegularExpressionsAfterTagParsing we can only replace tags here but no whitespace so far!
            $segment = $this->utilities->tagProtection->protectTags($segment, false);
        }

        // protect regExes after tag parsing
        $segment = $this->parseSegmentRegEx($segment, $this->replaceRegularExpressionsAfterTagParsing);

        // now all whitespace and remaining entities are encoded
        // if there are now internal tags added by the whitespace protection we have to protect them locally too
        return $this->contentProtector->protect(
            $segment,
            $isSource,
            $this->task->getSourceLang(),
            $this->task->getTargetLang(),
            ContentProtector::ENTITY_MODE_KEEP
        );
    }

    /**
     * Mask all regular expressions $this->replaceRegularExpressions with internal tag <regex ...>
     *
     * @param string $text
     * @param array $regexToUse $replaceRegularExpressionsBeforeTagParsing | $replaceRegularExpressionsAfterTagParsing
     * @return string
     */
    private function parseSegmentRegEx($text, &$regexToUse)
    {
        if (empty($regexToUse)) {
            return $text;
        }
        $mask = function ($matches) {
            $tag = $matches[0];
            //if there already is a protected string inside this match, don't protect it
            if (strpos($tag, $this->placeholderPrefix) !== false) {
                return $tag;
            }
            $tagObj = new editor_Models_Import_FileParser_Tag(editor_Models_Import_FileParser_Tag::TYPE_SINGLE, false);
            $tagObj->originalContent = $tag;
            $tagObj->tagNr = $this->shortTagIdent++;
            $tagObj->id = InternalTag::TYPE_REGEX;
            $tagObj->text = $this->encodeTagsForDisplay($tag);

            return $tagObj->renderTag();
        };
        foreach ($regexToUse as $regEx) {
            $text = preg_replace_callback($regEx, $mask, $text);
        }

        return $this->parseSegmentInsertPlaceholders($text, InternalTag::REGEX_INTERNAL_TAGS);
    }

    /**
     * counterpart of str_getcsv, because there is no php-func like that
     * (function taken from php.net-comments)
     * @param array $array
     * @param string $delimiter
     * @param string $enclosure
     * @param string $terminator
     * @return string
     */
    protected function str_putcsv($array, $delimiter = ',', $enclosure = '"', $terminator = "\n")
    {
        # First convert associative array to numeric indexed array
        foreach ($array as $value) {
            $workArray[] = $value;
        }

        $returnString = '';                 # Initialize return string
        $arraySize = count($workArray);     # Get size of array

        for ($i = 0; $i < $arraySize; $i++) {
            # Nested array, process nest item
            if (is_array($workArray[$i])) {
                $returnString .= str_putcsv($workArray[$i], $delimiter, $enclosure, $terminator);
            } else {
                switch (gettype($workArray[$i])) {
                    # Manually set some strings
                    case "NULL":     $_spFormat = '';

                        break;
                    case "boolean":  $_spFormat = ($workArray[$i]) ? 'true' : 'false';

                        break;
                        # Make sure sprintf has a good datatype to work with
                    case "integer":  $_spFormat = '%i';

                        break;
                    case "double":   $_spFormat = '%0.2f';

                        break;
                    case "string":   $_spFormat = '%s';

                        break;
                        # Unknown or invalid items for a csv - note: the datatype of array is already handled above, assuming the data is nested
                    case "object":
                    case "resource":
                    default:         $_spFormat = '';

                        break;
                }
                $workArray[$i] = str_replace($enclosure, $enclosure . $enclosure, $workArray[$i]);
                $returnString .= sprintf('%2$s' . $_spFormat . '%2$s', $workArray[$i], $enclosure);
                $returnString .= ($i < ($arraySize - 1)) ? $delimiter : $terminator;
            }
        }

        # Done the workload, return the output information
        return $returnString;
    }
}
