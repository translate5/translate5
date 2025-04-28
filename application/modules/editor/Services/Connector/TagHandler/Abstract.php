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
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\Segment\EntityHandlingMode;

/**
 * Abstract Tag Handler for internal tags in text to be send to language resources
 */
abstract class editor_Services_Connector_TagHandler_Abstract
{
    public const OPTION_KEEP_WHITESPACE_TAGS = 'keepWhitespaceTags';

    /**
     * This parser is used to restore whitespace tags
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser;

    /**
     * Flag if last restore call produced errors
     * @var boolean
     */
    protected $hasRestoreErrors = false;

    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $trackChange;

    /**
     * Counter how many real internal tags (excluding whitespace) the prepared query did contain
     * @var integer
     */
    protected $realTagCount = 0;

    protected $highestShortcutNumber = 0;

    protected array $shortcutNumberMap = [];

    /**
     * @var editor_Models_Segment_UtilityBroker
     */
    protected $utilities;

    /**
     * counter for internal tags
     * @var integer
     */
    protected $shortTagIdent = 1;

    /**
     * Contains the tag map of the prepared query
     * @var array
     */
    protected $map = [];

    /**
     * @var ZfExtended_Logger_Queued
     */
    public $logger;

    protected ContentProtector $contentProtector;

    protected int $sourceLang = 0;

    protected int $targetLang = 0;

    protected bool $handleIsInSourceScope = true;

    public bool $keepWhitespaceTags;

    /**
     * Segment content after segment is prepared and before is sent to the resource
     */
    protected string $querySegment;

    public function __construct(array $options = [])
    {
        $this->keepWhitespaceTags = (bool) ($options[self::OPTION_KEEP_WHITESPACE_TAGS] ?? false);
        $this->xmlparser = ZfExtended_Factory::get(editor_Models_Import_FileParser_XmlParser::class);
        $this->trackChange = ZfExtended_Factory::get(editor_Models_Segment_TrackChangeTag::class);
        $this->utilities = ZfExtended_Factory::get(editor_Models_Segment_UtilityBroker::class);
        $this->logger = ZfExtended_Factory::get(ZfExtended_Logger_Queued::class);
        $this->contentProtector = ContentProtector::create($this->utilities->whitespace);
        //we have to use the XML parser to restore whitespace, otherwise protectWhitespace would destroy the tags
        $this->xmlparser->registerOther(function ($textNode, $key) {
            //set shortTagIdent of the tagTrait to the next usable number if there are new tags
            $this->shortTagIdent = $this->highestShortcutNumber + 1;
            $textNode = $this->contentProtector->convertToInternalTagsWithShortcutNumberMap(
                $this->contentProtector->protect(
                    $textNode,
                    $this->handleIsInSourceScope,
                    $this->sourceLang,
                    $this->targetLang,
                    EntityHandlingMode::Restore,
                    NumberProtector::alias()
                ),
                $this->shortTagIdent,
                $this->shortcutNumberMap
            );
            $this->xmlparser->replaceChunk($key, $textNode);
        });
    }

    /**
     * protects the internal tags for language resource processing as defined in the class
     */
    abstract public function prepareQuery(string $queryString, bool $isSource = true): string;

    /**
     * protects the internal tags for language resource processing as defined in the class
     * @return string|null returns NULL on error
     */
    abstract public function restoreInResult(string $resultString, bool $isSource = true): ?string;

    /**
     * Returns true if last restoreInResult call had errors
     */
    public function hasRestoreErrors(): bool
    {
        return $this->hasRestoreErrors;
    }

    public function setLanguages(int $sourceLang, int $targetLang): void
    {
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
    }

    protected function convertQueryContent(string $queryString, bool $isSource = true): string
    {
        $this->highestShortcutNumber = 0;
        $this->shortcutNumberMap = [];

        //restore the whitespaces and numbers to real characters
        return $this->convertQuery(
            $this->utilities->internalTag->restore(
                $this->trackChange->removeTrackChanges($queryString),
                $this->getTagsForRestore(),
                $this->highestShortcutNumber,
                $this->shortcutNumberMap
            ),
            $isSource
        );
    }

    protected function convertQuery(string $queryString, bool $isSource): string
    {
        return $this->contentProtector->unprotect($queryString, $isSource);
    }

    /**
     * returns how many real internal tags (excluding whitespace tags) were contained by the prepared query
     * @return number
     */
    public function getRealTagCount()
    {
        return $this->realTagCount;
    }

    /**
     * returns the stored map of the internal tags
     */
    public function getTagMap(): array
    {
        return $this->map;
    }

    /**
     * set the stored map of the internal tags
     */
    public function setTagMap(array $map)
    {
        $this->map = $map;
    }

    /**
     * @return editor_Models_Segment_UtilityBroker|mixed
     */
    public function getUtilities(): editor_Models_Segment_UtilityBroker
    {
        return $this->utilities;
    }

    public function setCurrentSegment(editor_Models_Segment $segment): void
    {
        //empty function stub - to be implemented where needed
    }

    public function setInputTagMap(array $tagMap): void
    {
        //empty function stub - to be implemented where needed
    }

    public function setQuerySegment(string $querySegment): void
    {
        $this->querySegment = $querySegment;
    }

    public function getQuerySegment(): string
    {
        return $this->querySegment;
    }

    public function setKeepWhitespaceTags(bool $keepWhitespaceTags): void
    {
        $this->keepWhitespaceTags = $keepWhitespaceTags;
    }

    public function getTagsForRestore(): array
    {
        return $this->contentProtector->tagList();
    }
}
