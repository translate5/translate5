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

use editor_Models_Import_FileParser_Xlf_LengthRestriction as XlfLengthRestriction;
use editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Abstract as AbstractSurroundingTagRemover;
use editor_Models_Import_FileParser_XmlParser as XmlParser;
use MittagQI\Translate5\ContentProtection\NumberProtection\Tag\NumberTag;
use MittagQI\Translate5\ContentProtection\NumberProtection\Tag\NumberTagRenderer;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\NamespaceRegistry;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Namespaces;

/**
 * Fileparsing for import of XLIFF 1.1 and 1.2 files
 */
class editor_Models_Import_FileParser_Xlf extends editor_Models_Import_FileParser
{
    public const IS_REIMPORTABLE = true;

    public const PREFIX_MRK = 'mrk-';

    public const PREFIX_SUB = 'sub-';

    public const MISSING_MRK = 'missing-mrk';

    /**
     * The XLF target states which are to be considered as pretranslated only, as defined in TRANSLATE-1643
     * @var array
     */
    public const PRE_TRANS_STATES = ['needs-adaption', 'needs-l10n'];

    /**
     * defines if the content parser should reparse the chunks
     * false default, better performance
     * true used in subclasses, sometimes thats needed because of changes done in the XML structure
     * @var boolean
     * @deprecated added for parsing ZendXlf files should never used for something else, since is for example not respected in parsing otherContent - which does not exist in ZendXlf.
     */
    public const XML_REPARSE_CONTENT = false;

    private $wordCount = 0;

    private $segmentCount = 1;

    /**
     * Helper to call namespace specfic parsing stuff
     */
    protected Namespaces $namespaces;

    /**
     * Stack of the group translate information
     * @var array
     */
    protected $groupTranslate = [];

    /**
     * true if the current segment should be processed
     * false if not
     * @var boolean
     */
    protected $processSegment = true;

    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser;

    /**
     * Container for the source segments found in the current transunit
     * @var array
     */
    protected $currentSource = [];

    /**
     * Container for the target segments found in the current transunit
     * @var array
     */
    protected $currentTarget = [];

    /**
     * Container for plain text content
     */
    protected editor_Models_Import_FileParser_Xlf_OtherContent $otherContent;

    /**
     * Contains the source keys in the order how they should be imported!
     * @var array
     */
    protected $sourceProcessOrder = [];

    /**
     * Pointer to the real <source>/<seg-source> tags of the current transunit,
     * needed for injection of missing target tags
     * @var array
     */
    protected $currentPlainSource = null;

    /**
     * Pointer to the real <target> tags of the current transunit,
     * needed for injection of missing target, mrk and our placeholder tags
     * @var array
     */
    protected $currentPlainTarget = null;

    /**
     * @var editor_Models_Import_FileParser_Xlf_ContentConverter
     */
    protected $contentConverter = null;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;

    /**
     * @var editor_Models_Segment
     */
    protected $segmentBareInstance;

    /**
     * contains the info from where current the source contet originates:
     * plain <source>, plain <seg-source> or <seg-source><mrk mtype="seg">
     * This info is important for preparing empty mrk tags with placeholders
     * @var integer
     */
    protected $sourceOrigin;

    protected $transUnitCnt = 0;

    /**
     * Defines the importance of the tags containing possible source content
     * @var array
     */
    protected $sourceOriginImportance = [
        'sub' => 0, //→ no importance, means also no change in the importance
        'source' => 1,
        'seg-source' => 2,
        'mrk' => 3,
    ];

    /**
     * @var ZfExtended_Log
     */
    protected $log;

    protected $matchRate = [];

    protected XlfLengthRestriction $lengthRestriction;

    protected AbstractSurroundingTagRemover $surroundingTags;

    private Comments $comments;

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions()
    {
        return ['xlf', 'xlif', 'xliff', 'mxliff', 'mqxliff'];
    }

    /**
     * Init tagmapping
     * @throws editor_Models_ConfigException
     */
    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task)
    {
        parent::__construct($path, $fileName, $fileId, $task);

        $this->xmlparser = ZfExtended_Factory::get(XmlParser::class, [[
            'preparsexml' => $this->config->runtimeOptions->import->xlf->preparse,
        ]]);
        $this->comments = ZfExtended_Factory::get(Comments::class, [$this->task]);
        $this->namespaces = NamespaceRegistry::getImportNamespace($this->_origFile, $this->xmlparser, $this->comments);

        $this->contentConverter = $this->namespaces->getContentConverter($this->task, $fileName);
        $this->internalTag = ZfExtended_Factory::get(editor_Models_Segment_InternalTag::class);
        $this->segmentBareInstance = ZfExtended_Factory::get(editor_Models_Segment::class);
        $this->log = ZfExtended_Factory::get(ZfExtended_Log::class);
        $this->lengthRestriction = ZfExtended_Factory::get(XlfLengthRestriction::class, [
            $this->task->getConfig(),
        ]);
        $this->surroundingTags = AbstractSurroundingTagRemover::factory($this->config);
        $this->otherContent = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_OtherContent', [
            $this->contentConverter, $this->segmentBareInstance, $this->task, $fileId,
        ]);
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
     * (non-PHPdoc)
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws editor_Models_Import_FileParser_Xlf_Exception
     * @see editor_Models_Import_FileParser::parse()
     */
    protected function parse()
    {
        $this->segmentCount = 0;

        $parser = $this->xmlparser;

        $this->registerStructural();
        $this->registerMeta();
        $this->registerContent();
        $this->registerNoteComments();

        $preserveWhitespaceDefault = $this->config->runtimeOptions->import->xlf->preserveWhitespace;

        try {
            $this->skeletonFile = $parser->parse($this->_origFile, $preserveWhitespaceDefault);
        } catch (editor_Models_Import_FileParser_InvalidXMLException $e) {
            $logger = Zend_Registry::get('logger')->cloneMe('editor.import.fileparser.xlf');
            //we log the XML error as own exception, so that the error is listed in task overview
            $e->addExtraData([
                'task' => $this->task,
            ]);
            /* @var $logger ZfExtended_Logger */
            $logger->exception($e);

            //'E1190' => 'The XML of the XLF file "{fileName} (id {fileId})" is invalid!',
            throw new editor_Models_Import_FileParser_Xlf_Exception('E1190', [
                'task' => $this->task,
                'fileName' => $this->_fileName,
                'fileId' => $this->_fileId,
            ], $e);
        }

        if ($this->segmentCount === 0) {
            // processing files with no segments should only log a warning.
            $logger = Zend_Registry::get('logger')->cloneMe('editor.import.fileparser.xlf');
            $logger->warn('E1191', 'The XLF file "{fileName} (id {fileId})" does not contain any translation relevant segments.', [
                'task' => $this->task,
                'fileName' => $this->_fileName,
                'fileId' => $this->_fileId,
            ]);
        }
    }

    /**
     * registers handlers for nodes with meta data
     */
    protected function registerMeta()
    {
        $this->xmlparser->registerElement('trans-unit count', function ($tag, $attributes, $key) {
            $this->addupSegmentWordCount($attributes);
        });
    }

    /**
     * registers handlers for source, seg-source and target nodes to be stored for later processing
     */
    protected function registerContent()
    {
        $sourceEndHandler = function ($tag, $key, $opener) {
            $this->handleSourceTag($tag, $key, $opener);
        };

        $sourceTag = 'trans-unit > source, trans-unit > seg-source, trans-unit > seg-source mrk[mtype=seg]';
        $sourceTag .= ', trans-unit > source sub, trans-unit > seg-source sub';

        $this->xmlparser->registerElement($sourceTag, function ($tag, $attributes) {
            $sourceImportance = $this->compareSourceOrigin($tag);
            //set the source origin where we are currently (mrk or sub or plain source or seg-source)
            $this->setSourceOrigin($tag);

            //source content with lower importance was set before, remove it
            if ($sourceImportance > 0) {
                $this->sourceProcessOrder = [];
                $this->currentSource = [];
            }
            $mid = $this->calculateMid([
                'tag' => $tag,
                'attributes' => $attributes,
            ], true);
            if ($sourceImportance >= 0) {
                //preset the source segment for sorting purposes
                // if we just add the content in the end handler,
                // sub tags are added before the surrounding text content,
                // but it is better if sub content is listed after the content of the corresponding segment
                // for that we just set the source indizes here in the startHandler, here the order is correct
                $this->sourceProcessOrder[] = $mid;
            }
        }, $sourceEndHandler);

        //register to seg-source directly to enable / disable the collection of other content
        $this->xmlparser->registerElement('xliff trans-unit > seg-source', null, function ($tag, $key, $opener) {
            //if we have a seg-source we probably have also mrks where no other content is allowed to be outside the mrks
            $this->otherContent->setSourceBoundary($opener['openerKey'], $key);
        });

        $this->xmlparser->registerElement('trans-unit > target', null, function ($tag, $key, $opener) {
            //if empty targets are given as Single Tags
            $this->currentPlainTarget = $this->getTargetMeta($tag, $key, $opener);
            if ($this->isEmptyTarget($opener, $key)) {
                return;
            }
            foreach ($this->currentTarget as $target) {
                if ($target['tag'] === 'mrk') {
                    //if there is already target content coming from mrk tags inside,
                    // do nothing at the end of the main target tag, but we need the target boundary
                    $this->otherContent->setTargetBoundary($opener['openerKey'], $key);

                    return;
                }
            }
            //add the main target tag to the list of processable targets,
            // needed only without mrk tags and if target is not empty
            $this->otherContent->initTarget(); //if we use the plainTarget (no mrks), the otherContent is the plainTarget and no further checks are needed
            $this->currentTarget[$this->calculateMid($opener, false)] = $this->currentPlainTarget;
        });

        //handling sub segment mrks and sub tags
        $this->xmlparser->registerElement(
            'trans-unit > target mrk[mtype=seg], trans-unit > target sub',
            null,
            function ($tag, $key, $opener) {
                $mid = $this->calculateMid($opener, false);
                if ($tag === 'mrk') {
                    //if we have a mrk we enable the content outside mrk check
                    $this->otherContent->addTarget(
                        $mid,
                        $opener['openerKey'],
                        $key
                    )
                    ; //add a new container for the content after the current mrk
                }
                $this->currentTarget[$mid] = $this->getTargetMeta($tag, $key, $opener);
            }
        );

        $this->xmlparser->registerElement('trans-unit alt-trans', function ($tag, $attributes) {
            $mid = $this->xmlparser->getAttribute($attributes, 'mid', 0); //defaulting to 0 for transunits without mrks
            $matchRate = $this->xmlparser->getAttribute($attributes, 'match-quality', false);
            if ($matchRate !== false) {
                $this->matchRate[$mid] = (int) trim($matchRate, '% '); //removing the percent sign
            }
        });
    }

    protected function registerNoteComments(): void
    {
        //handling sub segment mrks and sub tags
        $this->xmlparser->registerElement(
            'trans-unit > note',
            null,
            function ($tag, $key, $opener) {
                $attributes = [
                    'lang' => null,
                    'from' => null,
                    'priority' => null,
                    'annotates' => 'general',
                ];
                foreach ($attributes as $attribute => $default) {
                    $attributes[$attribute] = $this->xmlparser->getAttribute(
                        $opener['attributes'],
                        $attribute,
                        $default
                    );
                }
                $content = $this->xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true);
                $this->comments->addByNote($content, $attributes);
            }
        );
    }

    /**
     * puts the given target chunk in an array with additonal meta data
     */
    protected function getTargetMeta($tag, $key, $opener)
    {
        //is initialized with null to check if there is no target tag at all,
        // here in the target handler we have to convert the null to an empty array
        return [
            'tag' => $tag,
            'opener' => $opener['openerKey'],
            'closer' => $key,
            'openerMeta' => $opener,
        ];
    }

    /**
     * Stores the "source" content for further processing
     * "source" content is content of the
     *   <source>                       tag, if the <seg-source> does not exist
     *   <seg-source>                   tag, plain content or
     *   <seg-source> <mrk mtype="seg">  content of the mrk type=seg tags inside the seg-source
     * @param string $tag
     * @param int $key
     * @param array $opener
     */
    protected function handleSourceTag($tag, $key, $opener)
    {
        $source = [
            'tag' => $tag,
            'opener' => $opener['openerKey'],
            'closer' => $key,
            'openerMeta' => $opener,
            'unsegmentedSource' => null,
        ];

        if ($tag == 'source') {
            //set <source> only if no seg-source was set already,
            // seg-source can always be used, seg-source is more important as source tag
            if (empty($this->currentPlainSource)) {
                //point to the plain/real source tag, needed for <target> injection
                $this->currentPlainSource = $source;
            } else {
                //seg-source was set before, we just store the unsegmented source
                $this->currentPlainSource['unsegmentedSource'] = $source;
            }
        }

        //set <source> only if no seg-source was set already,
        // seg-source can always be used, seg-source is more important as source tag
        if ($tag === 'seg-source') {
            //source was set before, store it as unsegmentedSource in the plain source
            if (! empty($this->currentPlainSource)) {
                $source['unsegmentedSource'] = $this->currentPlainSource;
            }
            //point to the plain/real source tag, needed for <target> injection
            $this->currentPlainSource = $source;
        }

        $sourceImportance = $this->compareSourceOrigin($tag);

        $mid = $this->calculateMid($opener, true);

        if ($tag === 'mrk') {
            $this->otherContent->addSource(
                $mid,
                $opener['openerKey'],
                $key
            ); //add a new container for the content after the current mrk
        }

        //source content with heigher importance was set before, ignore current content
        // for the importance see $this->sourceOriginImportance
        if ($sourceImportance < 0) {
            return;
        }

        //$sourceImportance == 0, no importance change add each found content:
        $this->currentSource[$mid] = $source;
    }

    /**
     * calculates the MID for mapping source to target fragment (is NOT related to the segments MID)
     * @param bool $source defines for which column the content is calculated: true if source, false if target
     * @return string
     */
    protected function calculateMid(array $opener, $source)
    {
        //if the content was coming from a:
        // mrk tag, we have to track the mrks mids for target matching
        // sub tag, we have to uses the parent tags id to identify the sub element.
        //  This is important for alignment of the sub tags,
        //  if the parent tags have flipped positions in source and target
        $prefix = '';
        if ($opener['tag'] == 'sub') {
            $prefix = self::PREFIX_SUB;
            $validParents = ['ph[id]', 'it[id]', 'bpt[id]', 'ept[id]'];
            $parent = false;
            while (! $parent && ! empty($validParents)) {
                $parent = $this->xmlparser->getParent(array_shift($validParents));
                if ($parent) {
                    return $prefix . $parent['tag'] . '-' . $parent['attributes']['id'];
                }
            }
            $this->throwSegmentationException('E1070', [
                'field' => ($source ? 'source' : 'target'),
            ]);

            return '';
        }
        if ($opener['tag'] == 'mrk') {
            $prefix = self::PREFIX_MRK;
            if ($this->xmlparser->getAttribute($opener['attributes'], 'mid') === false) {
                $this->throwSegmentationException('E1071', [
                    'field' => ($source ? 'source' : 'target'),
                ]);
            }
        }
        if (! ($opener['tag'] == 'mrk' && $mid = $this->xmlparser->getAttribute($opener['attributes'], 'mid'))) {
            $toConsider = $source ? $this->currentSource : $this->currentTarget;
            $toConsider = array_filter(array_keys($toConsider), function ($item) {
                return is_numeric($item);
            });
            if (empty($toConsider)) {
                $mid = 0;
            } else {
                //instead of using the length of the array  we consider only the numeric keys, take the biggest one and increase it
                $mid = max($toConsider) + 1;
            }
        }

        return $prefix . $mid;
    }

    /**
     * Throws Xlf Exception
     * @param string $errorCode
     * @param string $data
     * @throws ZfExtended_Exception
     */
    protected function throwSegmentationException($errorCode, array $data)
    {
        if (! array_key_exists('transUnitId', $data)) {
            $data['transUnitId'] = $this->xmlparser->getParent('trans-unit')['attributes']['id'];
        }
        $data['task'] = $this->task;

        throw new editor_Models_Import_FileParser_Xlf_Exception($errorCode, $data);
    }

    /**
     * Sets the source origin importance
     * @see self::compareSourceOrigin
     * @param string $tag
     */
    protected function setSourceOrigin($tag)
    {
        $origin = $this->sourceOriginImportance[$tag];
        if ($origin === 0) {
            return;
        }
        if ($origin > $this->sourceOrigin) {
            $this->sourceOrigin = $origin;
        }
    }

    /**
     * compares the importance of source origin. lowest importance has the content of a source tag,
     *  more important is seg-source, with the most importance is seg-source>mrk
     *  The content with the highes importance is used
     * @param string $tag
     * @return integer return <0 if a higher important source was set already, >0 if a more important source is set now, and 0 if the importance was the same (with mrks and subs possible only)
     */
    protected function compareSourceOrigin($tag)
    {
        $origin = $this->sourceOriginImportance[$tag];
        if ($origin === 0) {
            return 0;
        }

        return $origin - $this->sourceOrigin;
    }

    /**
     * registers handlers for structural nodes (group, transunit)
     */
    protected function registerStructural()
    {
        //check for correct xlf version
        $this->xmlparser->registerElement('xliff', function ($tag, $attributes, $key) {
            $this->checkXliffVersion($attributes, $key);
        });

        $this->xmlparser->registerElement('file', function ($tag, $attributes, $key) {
            $this->sourceFileId = $attributes['original'] ?? '';
        });

        $this->xmlparser->registerElement('group', function ($tag, $attributes, $key) {
            $this->handleGroup($attributes);
        }, function () {
            array_pop($this->groupTranslate);
        });

        $this->xmlparser->registerElement('trans-unit', function ($tag, $attributes, $key) {
            $this->processSegment = $this->isTranslateable($attributes);
            $this->transUnitCnt++;
            $this->sourceOrigin = 0;
            $this->matchRate = [];
            $this->currentSource = [];
            $this->currentTarget = [];
            $this->sourceProcessOrder = [];
            $this->currentPlainSource = null;
            // set to null to identify if there is no a target at all
            $this->currentPlainTarget = null;
            $this->otherContent->initOnUnitStart($this->xmlparser);

            //From Globalese:
            //<trans-unit id="segmentNrInTask">
            //<source>Installation and Configuration</source>
            //<target state="needs-review-translation" state-qualifier="leveraged-mt" translate5:origin="Globalese">
            //Installation und Konfiguration
            //</target>
            //</trans-unit>
        }, function ($tag, $key, $opener) {
            try {
                $createdSegmentIds = $this->extractSegment($opener['attributes']);
                //we collect all created segmentIds fur further usage on export (if needed by namespace)
                $this->xmlparser->replaceChunk(
                    $key,
                    '<t5:unitSegIds ids="' . join(',', $createdSegmentIds) . '" />' . $this->xmlparser->getChunk($key)
                );
            } catch (ZfExtended_ErrorCodeException $e) {
                $e->addExtraData([
                    'trans-unit' => $opener['attributes'],
                ]);

                throw $e;
            } catch (Throwable $e) {
                $msg = $e->getMessage() . "\n" . 'In trans-unit ' . print_r($opener['attributes']);
                if ($e instanceof ZfExtended_Exception) {
                    $e->setMessage($msg, 1);

                    throw $e;
                }

                throw new ZfExtended_Exception($msg, 0, $e);
            }
            //leaving a transunit means disable segment processing
            $this->processSegment = false;
        });
    }

    /**
     * returns true if segment should be translated, considers also surrounding group tags
     * @param array $transunitAttributes
     */
    protected function isTranslateable($transunitAttributes)
    {
        if (! empty($transunitAttributes['translate'])) {
            return $transunitAttributes['translate'] === 'yes';
        }
        $reverse = array_reverse($this->groupTranslate);
        foreach ($reverse as $group) {
            if (is_null($group)) {
                continue; //if the previous group provided no information, loop up
            }

            return $group;
        }

        return true; //if not info given at all: translateable
    }

    /**
     * Checks if the given xliff is in the correct (supported) version
     * @param int $key
     * @throws ZfExtended_Exception
     */
    protected function checkXliffVersion($attributes, $key)
    {
        $validVersions = ['1.1', '1.2'];
        $version = $this->xmlparser->getAttribute($attributes, 'version');
        if (! in_array($version, $validVersions)) {
            // XLF Parser supports only XLIFF Version 1.1 and 1.2, but the imported xliff tag does not match that criteria: {tag}
            throw new editor_Models_Import_FileParser_Xlf_Exception('E1232', [
                'task' => $this->task,
                'tag' => $this->xmlparser->getChunk($key),
            ]);
        }
    }

    /**
     * Handles a group tag
     */
    protected function handleGroup(array $attributes)
    {
        if (empty($attributes['translate'])) {
            //we have to add also the groups without an translate attribute
            // so that array_pop works correct on close node
            $this->groupTranslate[] = null;

            return;
        }
        $this->groupTranslate[] = (strtolower($attributes['translate']) == 'yes');
    }

    /**
     * parses the TransUnit attributes
     * @param array $attributes transUnit attributes
     * @param string $mid MRK tag mid or 0 if no mrk mtype seg used
     * @throws editor_Models_Import_FileParser_Exception
     * @throws editor_Models_Import_MetaData_Exception
     */
    protected function parseSegmentAttributes(
        array $attributes,
        string $mid,
        array $currentSource = null,
        array $currentTarget = null
    ): editor_Models_Import_FileParser_SegmentAttributes {
        //build mid from id of segment plus segmentCount, because xlf-file can have more than one file in it with
        // repeatingly the same ids. And one trans-unit (where the id comes from) can contain multiple mrk type
        // seg tags, which are all converted into single segments. instead of using mid from the mrk type seg element,
        // the segmentCount as additional ID part is fine.
        $transunitId = $this->xmlparser->getAttribute($attributes, 'id', null);

        // increase the segment count
        ++$this->segmentCount;

        if (str_starts_with((string) $mid, self::PREFIX_SUB)) {
            // Add the $mid to the transunit hash calculation, so we can differ the sub-segment from the other segments
            // in this trans unit
            $transunitHash = $this->transunitHash->createForSub($this->sourceFileId, $transunitId, $mid);
        } else {
            // To make unique trans-unit identifier, the transunitHash is a hash value out of:
            // - the current fileId. This is the id of the current file in the LEK_files table
            // - the value of the original attribute from the file tag
            // - the id of the current trans-unit
            $transunitHash = $this->transunitHash->create($this->sourceFileId, $transunitId);
        }

        $this->setMidWithHash($transunitHash, $mid);

        $segmentAttributes = $this->createSegmentAttributes($this->_mid);

        $segmentAttributes->transunitId = $transunitId;

        $segmentAttributes->transunitHash = $transunitHash;

        $segmentAttributes->mrkMid = $mid;

        $segmentAttributes->sourceFileId = $this->sourceFileId;

        $this->calculateMatchRate($segmentAttributes);

        //trigger namespace handlers for specific handling of custom attributes in trans-unit
        // and in the source and target MRKs
        $this->namespaces->transunitAttributes($attributes, $segmentAttributes);

        if (is_array($currentSource)) {
            $this->namespaces->currentSource($currentSource, $segmentAttributes);
        }
        if (is_array($currentTarget)) {
            $this->namespaces->currentTarget($currentTarget, $segmentAttributes);
        }

        if (
            ! empty($this->currentPlainTarget) &&
            $state = $this->xmlparser->getAttribute($this->currentPlainTarget['openerMeta']['attributes'], 'state')
        ) {
            $segmentAttributes->targetState = $state;
            $segmentAttributes->isPreTranslated = in_array($state, self::PRE_TRANS_STATES);
        }

        if (! $this->processSegment) {
            //add also translate="no" segments but readonly and locked!
            $segmentAttributes->editable = false; //this is to mark the segment non editable in the application
            $segmentAttributes->locked = true; //this is to mark it explicitly locked (so that editable can not be changed)
        }

        try {
            $this->lengthRestriction->addAttributes($this->xmlparser, $attributes, $segmentAttributes);
        } catch (editor_Models_Import_MetaData_Exception $e) {
            $e->addExtraData([
                'task' => $this->task,
                'fileId' => $this->_fileId,
                'sourceFileId' => $this->sourceFileId,
                'rawTransUnitId' => $transunitId,
                'transunitHash' => $segmentAttributes->transunitHash,
            ]);

            throw $e;
        }

        return $segmentAttributes;
    }

    protected function calculateMatchRate(editor_Models_Import_FileParser_SegmentAttributes $attributes)
    {
        $mid = $attributes->mrkMid;
        if (strpos($mid, editor_Models_Import_FileParser_Xlf::PREFIX_MRK) === 0) {
            //remove the mrk prefix again to get numeric ids
            $mid = str_replace(editor_Models_Import_FileParser_Xlf::PREFIX_MRK, '', $mid);
        }
        if (isset($this->matchRate[$mid])) {
            $attributes->matchRate = $this->matchRate[$mid];
            $attributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_TM;
        }
    }

    /**
     * is called in the end of the transunit
     * extract source- and target-segment from a trans-unit element
     * and saves this segments into database
     *
     * @param array $transUnit In this class this are the trans-unit attributes only
     * @return array array of segmentIds created from that trans unit
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_Import_FileParser_Exception
     * @throws editor_Models_Import_MetaData_Exception
     */
    protected function extractSegment($transUnit): array
    {
        //define the fieldnames where the data should be stored
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();

        $placeHolders = [];

        //must be set before the loop, since in the loop the currentTarget is cleared on success
        $hasTargets = ! (empty($this->currentTarget));
        $sourceEdit = (bool) $this->task->getEnableSourceEditing();

        $hasNoTarget = is_null($this->currentPlainTarget);
        $hasTargetSingle = ! $hasNoTarget && $this->currentPlainTarget['openerMeta']['isSingle'];
        //$hasEmptyTarget includes $hasTargetSingle
        $hasEmptyTarget = ! $hasNoTarget && $this->isEmptyTarget($this->currentPlainTarget['openerMeta'], $this->currentPlainTarget['closer']);

        //for processSegment == false (translate="no") (which evaluates later to locked == true && editable == false) it may happen that
        // seg-source is segmented, but since it is translate="no" the target contains the content unsegmented.
        // In that case we have to ignore the seg-source content and just import source and target, for read-only access
        // and add no placeholders for export for such segments so that the original content is kept.
        if (! $this->processSegment && ! $hasNoTarget && ! $hasEmptyTarget && $this->isSourceSegmentedButTargetNot()) {
            $mid = $this->calculateMid([
                'tag' => 'source',
                'attributes' => $transUnit,
            ], true);
            $this->sourceProcessOrder = [$mid];
            $this->currentSource = [
                $mid => $this->currentPlainSource['unsegmentedSource'] ?? $this->currentPlainSource,
            ];
            $this->currentTarget = [
                $mid => $this->currentPlainTarget,
            ];
        }

        if ($hasNoTarget || $hasTargetSingle) {
            $preserveWhitespace = $this->currentPlainSource['openerMeta']['preserveWhitespace'];
        } else {
            $preserveWhitespace = $this->currentPlainTarget['openerMeta']['preserveWhitespace'];
        }
        $this->otherContent->initOnUnitEnd($hasNoTarget || $hasEmptyTarget, $preserveWhitespace);
        $this->otherContent->injectEditableOtherContent($this->sourceProcessOrder, $this->currentSource, $this->currentTarget);

        //find mrk mids missing in source and add them marked as missing
        $this->padSourceMrkTags();
        //find mrk mids missing in target and add them marked as missing
        $this->padTargetMrkTags();

        // we have to limit the number of segments per tarns-unit as this might compromises further processing (-> sibling-data)
        if (count($this->sourceProcessOrder) > editor_Models_Import_Configuration::MAX_SEGMENTS_PER_TRANSUNIT) {
            throw new editor_Models_Import_FileParser_Exception('E1523', [
                'max' => editor_Models_Import_Configuration::MAX_SEGMENTS_PER_TRANSUNIT,
                'amount' => count($this->sourceProcessOrder),
                'transunitId' => $this->xmlparser->getAttribute($transUnit, 'id', '-na-'),
                'task' => $this->task,
            ]);
        }

        $createdSegmentIds = [];
        foreach ($this->sourceProcessOrder as $mid) {
            if ($mid === '') {
                //if mid was empty string there was an error, ignore the segment, logging was already done
                unset($this->currentTarget[$mid]);

                continue;
            }
            $currentSource = $this->currentSource[$mid];
            $isSourceMrkMissing = ($currentSource == self::MISSING_MRK);

            if ($isSourceMrkMissing) {
                $sourceChunksOriginal = $sourceChunks = [];
            } elseif ($this->otherContent->isOtherContent($mid) && $currentSource instanceof editor_Models_Import_FileParser_Xlf_OtherContent_Data) {
                $sourceChunks = $currentSource->contentChunks;
                $sourceChunksOriginal = $currentSource->contentChunksOriginal;
            } else {
                //parse the source chunks
                $sourceChunksOriginal = $sourceChunks = $this->xmlparser->getRange($currentSource['opener'] + 1, $currentSource['closer'] - 1, static::XML_REPARSE_CONTENT);

                //due XML_REPARSE_CONTENT it can happen that $sourceChunksOriginal will be a string, so we just put it into an array for further processing
                if (! is_array($sourceChunksOriginal)) {
                    $sourceChunksOriginal = [$sourceChunksOriginal];
                }

                if (! $this->sourceValidation($mid, $currentSource, $sourceChunksOriginal, $placeHolders)) {
                    continue;
                }

                $sourceChunks = $this->contentConverter->convert(
                    $sourceChunks,
                    true,
                    $currentSource['openerMeta']['preserveWhitespace']
                );
                $sourceSegment = $this->xmlparser->join($sourceChunks);

                //if there is no source content, nothing can be done
                if (ZfExtended_Utils::emptyString($sourceSegment)) {
                    unset($this->currentTarget[$mid]);

                    continue;
                }
            }

            if (($sourceEdit && $isSourceMrkMissing) || ($hasTargets && (empty($this->currentTarget[$mid]) && $this->currentTarget[$mid] !== '0'))) {
                $this->throwSegmentationException('E1067', [
                    'transUnitId' => $this->xmlparser->getAttribute($transUnit, 'id', '-na-'),
                    'mid' => $mid,
                ]);
            }
            if (empty($this->currentTarget) || empty($this->currentTarget[$mid]) && $this->currentTarget[$mid] !== "0") {
                $targetChunksOriginal = $targetChunks = [];
                $currentTarget = '';
            } elseif ($this->otherContent->isOtherContent($mid) && $this->currentTarget[$mid] instanceof editor_Models_Import_FileParser_Xlf_OtherContent_Data) {
                $currentTarget = $this->currentTarget[$mid];
                $targetChunks = $currentTarget->contentChunks;
                $targetChunksOriginal = $currentTarget->contentChunksOriginal;
                unset($this->currentTarget[$mid]); // mark as processed
            } else {
                $currentTarget = $this->currentTarget[$mid];
                if ($currentTarget == self::MISSING_MRK) {
                    $targetChunksOriginal = $targetChunks = [];
                    if (! $sourceEdit) {
                        //remove the item only if sourceEditing is disabled.
                        // That results then in an missing MRK error if sourceEditing enabled!
                        unset($this->currentTarget[$mid]);
                    }
                } else {
                    //parse the target chunks, store the real chunks from the XLF separatly
                    $targetChunksOriginal = $targetChunks = $this->xmlparser->getRange($currentTarget['opener'] + 1, $currentTarget['closer'] - 1);

                    //if reparse content is enabled, we convert the chunks to a string, so reparsing is triggerd
                    if (static::XML_REPARSE_CONTENT) {
                        $targetChunks = $this->xmlparser->join($targetChunks);
                    }
                    //in targetChunks the content is converted (tags, whitespace etc)
                    $targetChunks = $this->contentConverter->convert(
                        $targetChunks,
                        false,
                        $currentTarget['openerMeta']['preserveWhitespace']
                    );
                    unset($this->currentTarget[$mid]);
                }
            }

<<<<<<< HEAD
            $this->contentProtector->filterTagsInChunks($sourceChunks, $targetChunks);
            
=======
>>>>>>> master
            $this->surroundingTags->calculate($preserveWhitespace, $sourceChunks, $targetChunks, $this->xmlparser);

            $this->segmentData = [];
            $this->segmentData[$sourceName] = [
                //for source column we dont have a place holder, so we just cut off the leading/trailing tags and import the rest as source
                'original' => $this->xmlparser->join($this->surroundingTags->sliceTags($sourceChunks)),
            ];

            //for target we have to do the same tag cut off on the converted chunks to be used,
            $targetChunksTagCut = $this->surroundingTags->sliceTags($targetChunks);
            $this->segmentData[$targetName] = [
                'original' => $this->xmlparser->join($targetChunksTagCut),
            ];

            //parse attributes for each found segment not only for the whole trans-unit
            // if the segment is plain text, or a OtherContent_Data we do not pass it
            $attributes = $this->parseSegmentAttributes(
                $transUnit,
                $mid,
                is_array($currentSource) ? $currentSource : null,
                is_array($currentTarget) ? $currentTarget : null
            );

            if ($currentTarget == self::MISSING_MRK) {
                $attributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_MISSING_TARGET_MRK;
            } elseif ($currentSource == self::MISSING_MRK) {
                $attributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_MISSING_SOURCE_MRK;
            }

            $emptyInitialTarget = empty($targetChunksOriginal);
<<<<<<< HEAD
            $hasCutTargetContent = empty($this->segmentData[$targetName]['original'])
                || $this->segmentData[$targetName]['original'] === "0";

            $targetHasTagsOnly = !$this->hasText($this->segmentData[$targetName]['original']);
            $sourceHasTagsOnly = !$this->hasText($this->segmentData[$sourceName]['original']);

            if (
                $sourceHasTagsOnly
                && preg_match(NumberTagRenderer::INTERNAL_TAG_REGEX, $this->segmentData[$sourceName]['original'])
            ) {
                // number tag is not considered as empty segment
                $this->segmentData[$targetName]['original'] = $this->segmentData[$sourceName]['original'];
                $sourceHasTagsOnly = false;
            }
            // if source contains tags only or is empty (and is no missing source)
            // then we are able to ignore non textual segments if target fulfills the given 3 criterias
            if (!$isSourceMrkMissing && $sourceHasTagsOnly && ($emptyInitialTarget || $hasCutTargetContent || $targetHasTagsOnly)) {
                // if empty target, we fill the target with the source content, and ignore the segment then in translation
=======
            $hasCutTargetContent = empty($this->segmentData[$targetName]['original']) || $this->segmentData[$targetName]['original'] === "0";
            $targetHasTagsOnly = ! $this->hasText($this->segmentData[$targetName]['original']);
            //if source contains tags only or is empty (and is no missing source) then we are able to ignore non textual segments if target fulfills the given 3 criterias
            if (! $isSourceMrkMissing && ! $this->hasText($this->segmentData[$sourceName]['original']) && ($emptyInitialTarget || $hasCutTargetContent || $targetHasTagsOnly)) {
                //if empty target, we fill the target with the source content, and ignore the segment then in translation
>>>>>>> master
                //  on reviewing and if target content was given, then it will be ignored too
                //  on reviewing needs $hasOriginalTarget to be true, which is the case by above if
                $placeHolders[$mid] = $this->xmlparser->join($emptyInitialTarget ? $sourceChunksOriginal : $targetChunksOriginal);
                // we add the length of the ignored segment to the additionalUnitLength
                $this->otherContent->addIgnoredSegmentLength($emptyInitialTarget ? $sourceChunks : $targetChunks, $attributes);

                continue;
            }
            $createdSegmentIds[] = $segmentId = $this->setAndSaveSegmentValues();
            //only with a segmentId (in case of ProofProcessor) we can save comments
            if ($segmentId !== false && is_numeric($segmentId)) {
                $this->comments->importComments((int) $segmentId);
            }
            if ($currentTarget !== self::MISSING_MRK) {
                //we add a placeholder if it is a real segment, not just a placeholder for a missing mrk
                $placeHolders[$mid] = $this->surroundingTags->getLeading() . $this->getFieldPlaceholder($segmentId, $targetName) . $this->surroundingTags->getTrailing();
            }
        }

        //normally we get at least one attributes object above, if we have none, no segment is saved, so we don't have to process the lengths
        if (! empty($attributes)) {
            $this->otherContent->updateAdditionalUnitLength($attributes);
        }

        if (! empty($this->currentTarget)) {
            $this->throwSegmentationException('E1068', [
                'transUnitId' => $this->xmlparser->getAttribute($transUnit, 'id', '-na-'),
                'mids' => join(', ', array_keys($this->currentTarget)),
            ]);
        }

        //if we dont find any usable segment or the segment is locked, we dont have to place the placeholder
        if (empty($placeHolders) || ! $this->processSegment) {
            return $createdSegmentIds;
        }

        foreach ($placeHolders as $mid => $placeHolder) {
            if (str_starts_with($mid, self::PREFIX_MRK)) {
                //remove the mrk prefix again to get numeric ids
                $usedMid = str_replace(self::PREFIX_MRK, '', $mid);
                $placeHolders[$mid] = '<mrk mtype="seg" mid="' . $usedMid . '">' . $placeHolder . '</mrk>';
            }
            if (str_starts_with($mid, self::PREFIX_SUB)) {
                unset($placeHolders[$mid]); //remove sub element place holders, for sub elements are some new placeholders inside the tags
            }
        }

        $placeHolder = $this->otherContent->mergeWithPlaceholders($placeHolders);

        //this solves TRANSLATE-879: sdlxliff and XLF import does not work with missing target
        //if there is no target at all:
        if ($hasNoTarget) {
            //currentPlainSource point always to the last used source or seg-source
            // the target tag should be added after the the latter of both
            $replacement = '</' . $this->currentPlainSource['tag'] . ">\n        <target>" . $placeHolder . '</target>';
            $this->xmlparser->replaceChunk($this->currentPlainSource['closer'], $replacement);
        }
        //if the XLF contains an empty (single tag) target:
        elseif ($hasTargetSingle) {
            $this->xmlparser->replaceChunk($this->currentPlainTarget['closer'], function ($index, $oldChunk) use ($placeHolder) {
                return '<target>' . $placeHolder . '</target>';
            });
        }
        //existing content in the target:
        else {
            //clean up target content to empty, we store only our placeholder in the skeleton file
            $start = $this->currentPlainTarget['opener'] + 1;
            $length = $this->currentPlainTarget['closer'] - $start;
            //empty content between target tags:
            $this->xmlparser->replaceChunk($start, '', $length);
            //add placeholder and ending target tag:
            $this->xmlparser->replaceChunk($this->currentPlainTarget['closer'], function ($index, $oldChunk) use ($placeHolder) {
                return $placeHolder . $oldChunk;
            });
        }

        return $createdSegmentIds;
    }

    /**
     * Method Stub: Possibility for additional source validations, return true to process as usual, return false to skip segment import
     */
    protected function sourceValidation(string $mid, array $currentSource, array $sourceChunks, array &$placeHolders): bool
    {
        return true;
    }

    /**
     * returns true if the source is segmented with MRKs, but target not, may happen with translate=no segments from acr*
     * DOES NOT CHECK IF TARGET IS EMPTY! MUST BE DONE BEFORE!
     */
    private function isSourceSegmentedButTargetNot(): bool
    {
        $isMrk = function ($id) {
            return strpos($id, 'mrk-') === 0;
        };
        $sourceSegmented = ! empty(array_filter($this->currentSource, $isMrk, ARRAY_FILTER_USE_KEY));
        $targetSegmented = ! empty(array_filter($this->currentTarget, $isMrk, ARRAY_FILTER_USE_KEY));

        return $sourceSegmented && ! $targetSegmented;
    }

    /**
     * It must be sure, that this code runs after all other attribute calculations!
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser::setCalculatedSegmentAttributes()
     */
    protected function setCalculatedSegmentAttributes()
    {
        $attributes = parent::setCalculatedSegmentAttributes();
        if ($attributes->editable && strpos($attributes->matchRateType, editor_Models_Segment_MatchRateType::TYPE_MISSING_TARGET_MRK) !== false) {
            $attributes->editable = false; //if its a missing target the segment is not editable
        }

        return $attributes;
    }

    /**
     * returns false if segment content contains only tags
     * @param string $segmentContent
     * @return boolean
     */
    protected function hasText($segmentContent)
    {
        return $this->internalTag->hasText($segmentContent);
    }

    /**
     * detects wordcount in a trans-unit element.
     * sums up wordcount for the whole file in $this->wordCount
     *
     * Sample of wordcount provided by a trans-unit: <count count-type="word count" unit="word">13</count>
     */
    protected function addupSegmentWordCount($attributes)
    {
        // <count count-type="word count" unit="word">7</count>
        //TODO: this count-type is not xliff 1.2!!! IBM specific? or 1.1?
        if ($this->processSegment && ! empty($attributes['count-type']) && $attributes['count-type'] == 'word count') {
            $this->wordCount += (int) trim($this->xmlparser->getNextChunk());
        }
    }

    /**
     * compares source and target mrk mids, adds missing mrks mids into the sourceProcessOrder
     * find all mrk-MIDs from $this->currentTarget which are missing in $this->sourceProcessOrder
     * adds them by natural sort order to $this->sourceProcessOrder and to the currentSource container as special segment (MISSING_MRK)
     */
    protected function padSourceMrkTags()
    {
        $isMrkMid = function ($item) {
            return strpos($item, self::PREFIX_MRK) === 0;
        };
        $targetMrkKeys = array_filter(array_keys($this->currentTarget), $isMrkMid);
        $mrkMissingInSource = array_diff($targetMrkKeys, $this->sourceProcessOrder);
        if (empty($mrkMissingInSource)) {
            return;
        }

        foreach ($mrkMissingInSource as $target) {
            $this->currentSource[$target] = self::MISSING_MRK;
        }

        natsort($mrkMissingInSource);
        $result = [];
        //get the first target to compare
        $target = array_shift($mrkMissingInSource);
        foreach ($this->sourceProcessOrder as $sourceMid) {
            //if there is no target anymore, or the source is no mrk source, we just add the source
            if (! $isMrkMid($sourceMid) || empty($target)) {
                $result[] = $sourceMid;

                continue;
            }

            //if the current sourceMid would be greater or equal to the current target,
            // then we add first the target, then the sourceMid
            if (strnatcmp($sourceMid, $target) >= 0) {
                $result[] = $target;
                //get the next target to compare
                $target = array_shift($mrkMissingInSource);
            }
            $result[] = $sourceMid;
        }
        //if the target could not added (all source mids were smaller) add it after the loop
        if (! empty($target)) {
            $result[] = $target;
        }
        //same for all other remaining targets
        if (! empty($mrkMissingInSource)) {
            $result = array_merge($result, $mrkMissingInSource);
        }
        //store the result back
        $this->sourceProcessOrder = $result;
    }

    /**
     * loop over $this->sourceProcessOrder and compare with $this->currentTarget,
     *   add missing entries to $this->currentTarget also as special segment (MISSING_MRK)
     */
    protected function padTargetMrkTags()
    {
        //if currentTarget is completely empty, there are no single mrks missing, but all.
        // This special case is handled otherwise.
        if (empty($this->currentTarget)) {
            return;
        }
        $isMrkMid = function ($item) {
            return strpos($item, self::PREFIX_MRK) === 0;
        };
        $targetMrkKeys = array_filter(array_keys($this->currentTarget), $isMrkMid);
        $sourceMrkKeys = array_filter($this->sourceProcessOrder, $isMrkMid);
        $mrkMissingInTarget = array_diff($sourceMrkKeys, $targetMrkKeys);
        if (empty($mrkMissingInTarget)) {
            return;
        }
        $mrkMissingInTarget = array_fill_keys($mrkMissingInTarget, self::MISSING_MRK);
        $this->currentTarget = array_merge($this->currentTarget, $mrkMissingInTarget);
    }

    /**
     * returns true if target is a single tag (<target/>) or is empty <target></target>, where whitespace between the both targets matters for emptiness depending on preserveWhitespace
     * @param int $closerKey
     * @return boolean
     */
    protected function isEmptyTarget(array $openerMeta, $closerKey)
    {
        if ($openerMeta['isSingle']) {
            return true;
        }
        $preserveWhitespace = $openerMeta['preserveWhitespace'];
        $content = $this->xmlparser->getRange($openerMeta['openerKey'] + 1, $closerKey - 1, true);

        return $preserveWhitespace ? (empty($content) && $content !== "0") : (strlen(trim($content)) === 0);
    }
}
