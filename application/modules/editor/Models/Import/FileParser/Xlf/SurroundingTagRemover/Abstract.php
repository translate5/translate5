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
 * abstract for calculating and ignoring leading and trailing paired and special single tags in import
 */
abstract class editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Abstract {

    /**
     * @var boolean
     */
    protected bool $preserveWhitespace;

    protected int $startShiftCount = 0;
    protected int $endShiftCount = 0;

    protected string $leadingTags = '';
    protected string $trailingTags = '';

    protected editor_Models_Import_FileParser_XmlParser $xmlParser;

    /**
     * factory: creates the desired TagRemover instance based on the config
     * @param Zend_Config $config
     * @return static
     */
    public static function factory(Zend_Config $config): self {
        $cls = match ($config->runtimeOptions->import->xlf->ignoreFramingTags ?? '') {
            'none' => 'editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_None',
            'paired' => 'editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Paired',
            //all is default:
            default => 'editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_All'
        };
        return ZfExtended_Factory::get($cls);
    }

    /**
     * calculates the tags to be cut off
     * @param boolean $preserveWhitespace
     * @param array $sourceChunks
     * @param array $targetChunks
     * @param editor_Models_Import_FileParser_XmlParser $xmlparser
     */
    public function calculate(bool $preserveWhitespace, array $sourceChunks, array $targetChunks, editor_Models_Import_FileParser_XmlParser $xmlparser)
    {
        $this->leadingTags = '';
        $this->trailingTags = '';
        $this->xmlParser = $xmlparser;
        $this->preserveWhitespace = $preserveWhitespace;

        //if target is empty, we assume the target = source so that the feature can be used at all
        // 1. because it checks for same tags in source and target
        // 2. because we need the tags from source to be added as leading / trailing in target
        $target = $xmlparser->join($targetChunks);
        if (empty($target) && $target !== "0") {
            $targetChunks = $sourceChunks;
        }

        //reset start/end shift count.
        // the counts are set by the concrete implementation
        // then the start/end offset where the placeHolder is placed is shifted
        // to exclude tags leading and trailing tags in the segment
        $this->startShiftCount = 0;
        $this->endShiftCount = 0;

        //if preserveWhitespace is enabled, hasSameStartAndEndTags should not hide tags,
        // since potential whitespace tags does matter then in the content
        //since we are calling the leading/trailing tag stuff on the already fetched source segments,
        // we have no ability here to conserve content outside the mrk tags - which also should not be on preserveWhitespace
        if(!$this->_calculate($sourceChunks, $targetChunks)) {
            //if there is just leading/trailing whitespace but no tags we reset the counter
            // since then we dont want to cut off something
            //if there is whitespace between or before the leading / after the trailing tags,
            // this whitespace is ignored depending the preserveWhitespace setting.
            // above $sourceChunks $targetChunks does not contain any irrelevant whitespace (only empty chunks)
            $this->startShiftCount = 0;
            $this->endShiftCount = 0;
            return;
        }

        $prevMode = editor_Models_Import_FileParser_Tag::setMode(editor_Models_Import_FileParser_Tag::RETURN_MODE_ORIGINAL);
        //we get and store the leading target tags for later insertion
        $this->leadingTags = $this->xmlParser->join(array_slice($targetChunks, 0, $this->startShiftCount));

        //we get and store the trailing target tags for later insertion
        if($this->endShiftCount > 0) {
            $this->trailingTags = $this->xmlParser->join(array_slice($targetChunks, -$this->endShiftCount));
        }
        editor_Models_Import_FileParser_Tag::setMode($prevMode);
    }

    /**
     * calculates the tags to be cut off
     * @param array $sourceChunks
     * @param array $targetChunks
     */
    abstract protected function _calculate(array $sourceChunks, array $targetChunks): bool;

    /**
     * removes the leading and trailing tags as calculated before
     * @param array $chunks
     * @return array
     */
    public function sliceTags(array $chunks): array {
        return array_slice($chunks, $this->startShiftCount, count($chunks) - $this->startShiftCount - $this->endShiftCount);
    }

    /**
     * get the leading cut of tags in original import format as string
     * @return string
     */
    public function getLeading(): string {
        return $this->leadingTags;
    }

    /**
     * get the trailing cut of tags in original import format as string
     * @return string
     */
    public function getTrailing(): string {
        return $this->trailingTags;
    }
}