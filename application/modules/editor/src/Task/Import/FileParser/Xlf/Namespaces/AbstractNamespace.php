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

use editor_Models_Import_FileParser_SegmentAttributes as SegmentAttributes;
use editor_Models_Import_FileParser_Xlf_ContentConverter as ContentConverter;
use editor_Models_Import_FileParser_Xlf_ShortTagNumbers;
use editor_Models_Import_FileParser_XmlParser as XmlParser;
use editor_Models_Task;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments;
use ReflectionException;

/**
 * XLF Fileparser Add On abstract class
 */
abstract class AbstractNamespace
{
    public function __construct(
        protected XmlParser $xmlparser,
        protected Comments $comments,
    ) {
    }

    /**
     * returns true if the current Namespace class is applicable for the given XLF string
     */
    abstract public static function isApplicable(string $xliff): bool;

    abstract public static function getExportCls(): ?string;

    /**
     * Provides an invocation for parsing custom trans-unit attributes
     * @param string[] $attributes
     */
    public function transunitAttributes(array $attributes, SegmentAttributes $segmentAttributes): void
    {
        //method stub
    }

    public function currentSource(array $currentSourceTag, SegmentAttributes $segmentAttributes): void
    {
        //method stub
    }

    public function currentTarget(array $currentTargetTag, SegmentAttributes $segmentAttributes): void
    {
        //method stub
    }

    public function preProcessFile(string $xml): string
    {
        //method stub
        return $xml;
    }

    /**
     * returns if the used XLIFF derivate must or must not use the plain tag content as internal tag text,
     *   or null if should depend on the tag
     * @return boolean|null
     */
    abstract public function useTagContentOnly(): ?bool;

    /**
     * @throws ReflectionException
     */
    public function getContentConverter(
        editor_Models_Task $task,
        editor_Models_Import_FileParser_Xlf_ShortTagNumbers $shortTagNumbers,
        string $filename
    ): ContentConverter {
        return new ContentConverter(
            $this,
            $task,
            $shortTagNumbers,
            $filename,
        );
    }
}
