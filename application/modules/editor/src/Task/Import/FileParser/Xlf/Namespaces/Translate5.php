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

namespace MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces;

use editor_Models_Export_FileParser_Xlf_Namespaces_Translate5;
use editor_Models_Import_FileParser_SegmentAttributes as SegmentAttributes;
use editor_Models_Import_FileParser_Xlf_ContentConverter as OrigContentConverter;
use editor_Models_Import_FileParser_XmlParser as XmlParser;
use editor_Models_Task;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments;
use ZfExtended_Factory;

/**
 * XLF Fileparser Add On to parse Translate5 XLF specific stuff
 */
class Translate5 extends AbstractNamespace
{
    public const TRANSLATE5_XLIFF_NAMESPACE = 'xmlns:translate5="http://www.translate5.net/"';

    private Translate5\ContentConverter $contentConverter;

    public function __construct(XmlParser $xmlparser, Comments $comments)
    {
        parent::__construct($xmlparser, $comments);
        $this->registerParserHandler($xmlparser);
    }

    public static function isApplicable(string $xliff): bool
    {
        return str_contains($xliff, self::TRANSLATE5_XLIFF_NAMESPACE);
    }

    public static function getExportCls(): ?string
    {
        return editor_Models_Export_FileParser_Xlf_Namespaces_Translate5::class;
    }

    /**
     * Internal tagmap
     */
    protected array $tagMap = [];

    /**
     * @see AbstractNamespace::transunitAttributes()
     */
    public function transunitAttributes(array $attributes, SegmentAttributes $segmentAttributes): void
    {
        //TODO parse:
        //trans-unit id="7" translate5:autostateId="4" translate5:autostateText="not_translated">
        if (array_key_exists('translate5:maxNumberOfLines', $attributes)) {
            $segmentAttributes->maxNumberOfLines = (int) $attributes['translate5:maxNumberOfLines'];
        } else {
            $segmentAttributes->maxNumberOfLines = null;
        }
    }

    protected function registerParserHandler(XmlParser $xmlparser): void
    {
        //FIXME translate5 comment attributes are currently omitted, since if really import the user with its guid
        // then the comment is exported additionally, since user guid does not contain xlf-note-imported anymore.

        $xmlparser->registerElement('translate5:tagmap', null, function ($tag, $key, $opener) use ($xmlparser) {
            //get the content between the tagmap tags:
            $storedTags = $xmlparser->getRange($opener['openerKey'] + 1, $key - 1, true);
            $givenTagMap = unserialize(base64_decode($storedTags));
            unset($storedTags);
            foreach ($givenTagMap as $bptKey => $data) {
                $gTag = $data[0];
                $originalTag = $data[1];
                //we convert the tagMap to:
                // $this->tagMap[<g id="123">] = [<internalOpener>,<internalCloser>];
                // $this->tagMap[<x id="321">] = [<internalSingle>];
                if (! empty($data[2])) {
                    $closer = $data[2];
                    $this->contentConverter->setInTagMap($gTag, [$originalTag, $givenTagMap[$closer][1]]);
                } else {
                    $this->contentConverter->setInTagMap($gTag, [$originalTag]);
                }
            }
        });
    }

    /**
     * Translate5 uses x,g and bx ex tags only. So the whole content of the tags incl. the tags must be used.
     * {@inheritDoc}
     * @see AbstractNamespace::useTagContentOnly()
     */
    public function useTagContentOnly(): ?bool
    {
        return false;
    }

    public function getContentConverter(editor_Models_Task $task, string $filename): OrigContentConverter
    {
        $this->contentConverter = ZfExtended_Factory::get(Translate5\ContentConverter::class, [
            $this,
            $task,
            $filename,
        ]);
        $this->contentConverter->resetTagMap();

        return $this->contentConverter;
    }
}
