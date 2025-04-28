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
 * protects the translate5 internal tags as XLIFF for language resource processing
 */
class editor_Services_Connector_TagHandler_Xliff extends editor_Services_Connector_TagHandler_Abstract
{
    protected const ALLOWED_TAGS = '<x><x/><bx><bx/><ex><ex/><g>';

    /**
     * @var integer
     */
    protected $mapCount = 0;

    /**
     * Counter for additional tags in one segment content block
     * @var integer
     */
    protected $additionalTagCount = 1;

    /**
     * Flag if bx/ex tags should be paired to g tags or if bx/ex should be kept
     */
    protected bool $gTagPairing = true;

    /**
     * Valid options are: gTagPairing bool en/disables if bx/ex bpt/ept tags should be paired to g tags or not
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        //en/disable gTagPairing
        if (array_key_exists('gTagPairing', $options)) {
            $this->gTagPairing = (bool) $options['gTagPairing'];
        }

        //replace unusable <ph|it etc> tags with usable <x|bx etc> tags
        $this->xmlparserUnusableTags = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        $this->xmlparserUnusableTags->registerElement(
            't5xliffresult > it,t5xliffresult > ph,t5xliffresult > ept,t5xliffresult > bpt',
            null,
            function ($tag, $key, $opener) {
                switch ($tag) {
                    case 'bpt':
                        $replace = '<bx';

                        break;
                    case 'ept':
                        $replace = '<ex';

                        break;
                    default:
                        $replace = '<x';

                        break;
                }
                $this->xmlparserUnusableTags->replaceChunk(
                    $opener['openerKey'],
                    '',
                    $opener['isSingle'] ? 1 : ($key - $opener['openerKey'] + 1)
                );

                $replace .= ' mid="additional-' . ($this->additionalTagCount++);

                if ($tag === 'bpt' || $tag === 'ept') {
                    $replace .= '" rid="' . ($opener['attributes']['i'] ?? $this->additionalTagCount);
                }

                $replace .= '" />';

                $this->xmlparserUnusableTags->replaceChunk($opener['openerKey'], $replace);
            }
        );
    }

    /**
     * protects the internal tags as xliff tags x,bx,ex and g pair
     *
     * calculates and sets map and mapCount internally
     */
    public function prepareQuery(string $queryString, bool $isSource = true): string
    {
        $this->handleIsInSourceScope = $isSource;
        $this->realTagCount = 0;
        //$map is set by reference
        $this->map = [];
        $queryString = $this->convertQueryContent($queryString, $isSource);

        $this->realTagCount = $this->utilities->internalTag->count($queryString);

        $queryString = $this->processXliffTags($queryString);

        $this->mapCount = count($this->map);

        // after the segment content is converted, store the value for latter use abd reference
        $this->setQuerySegment($queryString);

        return $queryString;
    }

    protected function processXliffTags(string $queryString): string
    {
        if ($this->gTagPairing) {
            return $this->utilities->internalTag->toXliffPaired($queryString, replaceMap: $this->map);
        }

        return $this->utilities->internalTag->toXliff($queryString, replaceMap: $this->map);
    }

    /**
     * protects the internal tags for language resource processing as defined in the class
     * @return string|null NULL on error
     */
    public function restoreInResult(string $resultString, bool $isSource = true): ?string
    {
        $this->handleIsInSourceScope = $isSource;
        $this->hasRestoreErrors = false;
        //strip other then x|ex|bx|g|/g
        $resultString = strip_tags($this->replaceTagsWithContent($resultString), static::ALLOWED_TAGS);

        //since protectWhitespace should run on plain text nodes we have to call it before the internal tags are reapplied,
        // since then the text contains xliff tags and the xliff tags should not contain affected whitespace
        // this is triggered here with the parse call
        try {
            $target = $this->xmlparser->parse($resultString);
        } catch (editor_Models_Import_FileParser_InvalidXMLException $e) {
            $this->logger->exception($e, [
                'level' => $this->logger::LEVEL_WARN,
            ]);
            //See previous InvalidXMLException
            $this->logger->warn('E1302', 'The LanguageResource did contain invalid XML, all tags were removed. See also previous InvalidXMLException in Log.', [
                'givenContent' => $resultString,
            ]);
            $this->hasRestoreErrors = true;

            return strip_tags($resultString);
        }

        $target = $this->utilities->internalTag->reapply2dMap($target, $this->map);

        return $this->replaceAdditionalTags($target, $this->mapCount);
    }

    /**
     * If the XLF result from a TM contains <it> ,<ph>,<bpt> and <ept> tags, they could not be replaced by the sent <x|bx|ex|g> tags in the source,
     *   so they have to be considered as additional tags then
     */
    protected function replaceTagsWithContent(string $content): string
    {
        //just concat source and target to check both:
        if (preg_match('#<(it|ph|ept|bpt)[^>]*>#', $content)) {
            //surround the content with tmp tags(used later as selectors)
            $content = $this->xmlparserUnusableTags->parse('<t5xliffresult>' . $content . '</t5xliffresult>');

            //remove the helper tags
            return strtr($content, [
                '<t5xliffresult>' => '',
                '</t5xliffresult>' => '',
            ]);
        }

        return $content;
    }

    /**
     * replace additional tags from the TM to internal tags which are ignored in the frontend then
     * @param int $mapCount used as start number for the short tag numbering
     */
    protected function replaceAdditionalTags(string $segment, int $mapCount): ?string
    {
        $addedTags = false;
        $shortTagNr = $mapCount;
        $replaceMap = [];

        $result = preg_replace_callback(
            '#<(x|ex|bx|g|\/g)([^>]*rid="(\d+)")?[^>]*>#',
            function (array $matches) use (&$shortTagNr, &$addedTags, &$replaceMap) {
                $addedTags = true;
                $type = $matches[1] === 'bx' ? 'open' : ($matches[1] === 'ex' ? 'close' : 'single');

                if (isset($matches[3])) {
                    if (! isset($replaceMap[$matches[3]])) {
                        $replaceMap[$matches[3]] = ++$shortTagNr;
                    }

                    $number = $replaceMap[$matches[3]];
                } else {
                    $number = ++$shortTagNr;
                }

                return $this->utilities->internalTag->makeAdditionalHtmlTag($number, $type);
            },
            $segment
        );

        if ($addedTags) {
            // FOR NOW WE DO NOT LOG THIS AT IT UNNECCESSARILY FILLS THE LOG WITH THOUSANDS OF ENTRIES IN SOME TASKS
            // logging as debug only, since in GUI they are removed. FIXME whats with pretranslation?
            // $this->logger->debug('E1300', 'The LanguageResource answer did contain additional tags which were added to the segment, starting with Tag Nr {nr}.',[
            //     'nr' => $mapCount,
            //    'givenContent' => $segment,
            // ]);
        }

        return $result;
    }

    /**
     * sets the tag inputMap and converts it from xlftag => array format to xlftag => internal tag format
     * @see editor_Models_Segment_InternalTag::setInputTagMap
     */
    public function setInputTagMap(array $tagMap): void
    {
        foreach ($tagMap as $key => $value) {
            $tagMap[$key] = $value[1];
        }
        $this->utilities->internalTag->setInputTagMap($tagMap);
    }
}
