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

/**
 * Represents an MQM tag
 * this tag "really" represents two image tags wrapping the content
 * Example as sent from the Frontend:
 * OPENER: <img class="open minor qmflag ownttip qmflag-13" data-t5qid="633" data-comment="No Comment" src="/modules/editor/images/imageTags/qmsubsegment-13-left.png" />
 * CLOSER: <img class="close minor qmflag ownttip qmflag-13" data-t5qid="633" data-comment="No Comment" src="/modules/editor/images/imageTags/qmsubsegment-13-right.png" />
 * TEMPLATE: <img class="%1$s qmflag ownttip %2$s qmflag-%3$d" data-t5qid="%4$d" data-comment="%5$s" src="%6$s" />
 * this tag is represented by two image-tags in the markup which will be paired to a single tag in the unparsing process
 * In the rendering phase an instance will be cloned and act's as two seperate single image tags again !
 *
 * @method editor_Segment_Mqm_Tag createBaseClone()
 * @method editor_Segment_Mqm_Tag cloneForRendering()
 */
final class editor_Segment_Mqm_Tag extends editor_Segment_Tag
{
    /**
     * @var string
     */
    public const CSS_CLASS = 'qmflag';

    /**
     * @var string
     */
    public const CSS_CLASS_OPEN = 'open';

    /**
     * @var string
     */
    public const CSS_CLASS_CLOSE = 'close';

    /**
     * @var string
     */
    public const PATTERN_INDEX = '~qmsubsegment-([0-9]+)-(left|right)\.png~';

    /**
     * @var string
     */
    public const IMAGE_SRC = 'qmsubsegment-{0}-{1}.png';

    /**
     * Creates the category of a MQM tag out of it's category index (which will be saved seperately - what can be seen as a redundancy)
     */
    public static function createCategoryVal(int $categoryIndex): string
    {
        return editor_Segment_Tag::TYPE_MQM . '_' . strval($categoryIndex);
    }

    protected static $type = editor_Segment_Tag::TYPE_MQM;

    protected static $nodeName = 'img';

    protected static $identificationClass = self::CSS_CLASS;

    /**
     * COMPATIBILITY: historically, the quality-id was encoded as data-t5qid
     * @var string
     */
    protected static $historicDataNameQid = 'seq';

    /**
     * @var string
     */
    private static $imgSrcTemplate = null;

    /**
     * @return string
     */
    private static function createImageSrcTemplate()
    {
        if (self::$imgSrcTemplate == null) {
            $conf = Zend_Registry::get('config');
            self::$imgSrcTemplate = APPLICATION_RUNDIR . '/' . $conf->runtimeOptions->dir->tagImagesBasePath . '/' . self::IMAGE_SRC;
        }

        return self::$imgSrcTemplate;
    }

    private static function createImageSrc(int $categoryIndex, string $position): string
    {
        return str_replace('{0}', strval($categoryIndex), str_replace('{1}', $position, self::createImageSrcTemplate()));
    }

    /**
     * Renders a basic MQM tag out of the given properties
     *
     * @param int|string $qualityId
     */
    public static function renderTag($qualityId, bool $isOpen, int $categoryIndex, string $severity, string $comment): string
    {
        // we follow the original code here
        // <img class="%1$s qmflag ownttip %2$s qmflag-%3$d" data-t5qid="%4$d" data-comment="%5$s" src="%6$s" />
        $position = ($isOpen) ? 'left' : 'right';
        $posClass = ($isOpen) ? self::CSS_CLASS_OPEN : self::CSS_CLASS_CLOSE;
        $tag = editor_Tag::img(self::createImageSrc($categoryIndex, $position));
        $tag
            ->addClass($severity)
            ->addClass(self::CSS_CLASS)
            ->addClass(editor_Segment_Tag::CSS_CLASS_TOOLTIP)
            ->addClass($posClass)
            ->addClass(self::CSS_CLASS . '-' . strval($categoryIndex))
            ->setData(editor_Segment_Tag::DATA_NAME_QUALITYID, $qualityId)
            ->setData('comment', $comment);

        return $tag->render();
    }

    /**
     * @var bool
     */
    private $paired = false;

    /**
     * This flag will be set in the rendering-phase, then we act as a single image tag !
     * Can be NULL | left | right
     * @var string
     */
    private $rendering = null;

    /**
     * @var int
     */
    private $categoryIndex = -1;

    /**
     * @var string
     */
    private $severity = '';

    /**
     * @var string
     */
    private $comment = '';

    /**
     * Holds the order of our closer in the phase of serialization
     * @var int
     */
    public $rightOrder = -1;

    public function getCategoryIndex(): int
    {
        return $this->categoryIndex;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * MQM tags can not be Splitted as they are explicitly allowed to overlap
     * {@inheritDoc}
     * @see editor_Segment_Tag::isSplitable()
     */
    public function isSplitable(): bool
    {
        return false;
    }

    /* Overwritten API */

    /**
     * A MQM tag will act as singular tag in the unparsig phase, wil be turned to a real non-singular tag when pairing in the consolidation phase and act as a singular tag again in the rendering phase
     * {@inheritDoc}
     * @see editor_Tag::isSingular()
     */
    public function isSingular(): bool
    {
        return ! $this->paired;
    }

    public function getCategory(): string
    {
        return self::createCategoryVal($this->categoryIndex);
    }

    public function setCategory(string $category): editor_Segment_Tag
    {
        // we do not want to have this API used with MQM Tags
        throw new ZfExtended_Exception('Calling setCategory on a MQM Tag is forbidden since the category is represented by the category index. use setCategoryIndex instead!');
    }

    protected function isQualityGenerallyEqual(editor_Models_Db_SegmentQualityRow $quality): bool
    {
        // checking our additional contents is mandatory ...
        return ($this->getType() === $quality->type && $this->getCategoryIndex() === $quality->categoryIndex && $this->getSeverity() == $quality->severity && $this->getComment() == $quality->comment);
    }

    /* Overwritten API to reflect pairing */

    /**
     * Special API to set the rendering props in the rendering phase
     * @return editor_Segment_Mqm_Tag
     */
    private function setRendering(string $renderingMode, int $order, int $parentOrder)
    {
        $this->rendering = $renderingMode;
        $this->paired = false; // crucial to avoid nonsense in the rendering model
        $this->order = $order;
        $this->parentOrder = $parentOrder;
        if ($this->rendering == 'left') {
            $this->endIndex = $this->startIndex;
        } else {
            $this->startIndex = $this->endIndex;
        }

        return $this;
    }

    /**
     * Overwritten to add two clones acting as image tags instead of a single Tag.
     * That's the main "trick" why MQM-tags are handled as mates but as independent tags when rendering leading to overlaps being rendered as such
     * The nesting of the MQM tags already was corrected in the consolidation phase by manipulating order & right order
     * {@inheritDoc}
     * @see editor_Segment_Tag::addRenderingClone()
     */
    public function addRenderingClone(array &$renderingQueue)
    {
        $renderingQueue[] = $this->cloneForRendering()->setRendering('left', $this->order, -1);
        $renderingQueue[] = $this->cloneForRendering()->setRendering('right', $this->rightOrder, -1);
    }

    /**
     * Overwritten since we act as a single image tag in the rendering phase
     * {@inheritDoc}
     * @see editor_Segment_Tag::render()
     */
    public function render(array $skippedTypes = null): string
    {
        if ($this->rendering == null) {
            return parent::render($skippedTypes);
        }
        if ($this->rendering == 'left') {
            return $this->renderImageTag(true);
        }

        return $this->renderImageTag(false);
    }

    /**
     * Normally not used, but who knows if we are ever rendered outside the rendering phase
     * {@inheritDoc}
     * @see editor_Tag::renderStart()
     */
    protected function renderStart(bool $withDataAttribs = true): string
    {
        if ($this->paired) {
            return $this->renderImageTag(true);
        }

        return parent::renderStart($withDataAttribs);
    }

    /**
     * Normally not used, but who knows if we are ever rendered outside the rendering phase
     * {@inheritDoc}
     * @see editor_Tag::renderStart()
     */
    protected function renderEnd(): string
    {
        if ($this->paired) {
            return $this->renderImageTag(false);
        }

        return parent::renderEnd();
    }

    /**
     * @param boolean $isOpener
     */
    private function renderImageTag(bool $isOpener): string
    {
        $tag = $this->cloneProps(editor_Tag::img(), true);
        $position = ($isOpener) ? 'left' : 'right';
        $className = ($isOpener) ? self::CSS_CLASS_OPEN : self::CSS_CLASS_CLOSE;
        $tag->setSource(self::createImageSrc($this->categoryIndex, $position));
        // to resemble the otherwise used structure we prepend this class. In General, CSS-classes should be independent from position ...
        $tag->prependClass($className);

        return $tag->render();
    }

    /* Consolidation API */

    public function isPaired(): bool
    {
        return $this->paired;
    }

    public function isPairedOpener(): bool
    {
        return ($this->hasClass(self::CSS_CLASS_OPEN));
    }

    public function isPairedCloser(): bool
    {
        return ($this->hasClass(self::CSS_CLASS_CLOSE));
    }

    public function isObsolete(): bool
    {
        // we discard any invalid mqm tags, e.g. those spanning no text
        return (! $this->paired || $this->startIndex == $this->endIndex || $this->categoryIndex == -1);
    }

    public function onConsolidationRemoval()
    {
        // we don't want exceptions on empty mqm tags (which do not make sense but also are no real error and will be removed silently) or on unit tests
        if ($this->startIndex == $this->endIndex || defined('APPLICATION_UNITTEST')) {
            return;
        }
        // tags spanning no text will be removed silently
        if ($this->getQualityId() == null) {
            throw new Zend_Exception('MQM Tag found, but no quality-id (data-t5qid) was set in: ' . $this->renderStart());
        } elseif ($this->categoryIndex == -1) {
            throw new Zend_Exception('MQM Tag found, but no type index was set in: ' . $this->renderStart());
        }
    }

    /**
     * The passed $tag is a tag if the same type and is a paired closer
     * This is the place where a un-paired tag changes to a "real" paired MQM Tag
     * {@inheritDoc}
     * @see editor_Segment_Tag::pairWith()
     */
    public function pairWith(editor_Segment_Tag $tag): bool
    {
        if ($this->getQualityId() == null || $tag->getQualityId() != $this->getQualityId()) {
            return false;
        }
        if (editor_TagSequence::VALIDATION_MODE && $this->endIndex != $this->startIndex || $tag->endIndex != $tag->startIndex) {
            error_log("\n##### MQM TAG: INVALID INDEXES FOUND [open: (" . $this->startIndex . "|" . $this->endIndex . ") close: (" . $tag->startIndex . "|" . $tag->endIndex . ")] #####\n");
        }
        $this->paired = true;
        $this->endIndex = $tag->startIndex;
        $this->rightOrder = $tag->order;
        $this->removeClass(self::CSS_CLASS_OPEN)->removeClass(self::CSS_CLASS_OPEN);
        $src = $this->getAttribute('src');
        $matches = [];
        if (preg_match(self::PATTERN_INDEX, $src, $matches)) {
            $this->categoryIndex = intval($matches[1]);
        }
        $this->comment = htmlspecialchars_decode($this->getData('comment'));

        return true;
    }

    public function finalize(editor_TagSequence $tags, editor_Models_task $task)
    {
        // finds our severity in our cclasses via the tasks MQM configuration
        $this->severity = editor_Segment_Mqm_Configuration::instance($task)->findMqmSeverity($this->classes, '');
    }

    /**
     * @return editor_Segment_Mqm_Tag|editor_Segment_Tag
     */
    public function clone(bool $withDataAttribs = false, bool $withId = false)
    {
        $clone = parent::clone($withDataAttribs, $withId);
        /* @var $clone editor_Segment_Mqm_Tag */
        $data = new stdClass();
        $this->furtherSerialize($data);
        $clone->furtherUnserialize($data);

        return $clone;
    }

    protected function furtherSerialize(stdClass $data)
    {
        $data->paired = $this->paired;
        $data->categoryIndex = $this->categoryIndex;
        $data->severity = $this->severity;
        $data->comment = $this->comment;
        $data->rightOrder = $this->rightOrder;
    }

    protected function furtherUnserialize(stdClass $data)
    {
        $this->paired = $data->paired;
        $this->categoryIndex = $data->categoryIndex;
        $this->severity = $data->severity;
        $this->comment = $data->comment;
        $this->rightOrder = $data->rightOrder;
    }
}
