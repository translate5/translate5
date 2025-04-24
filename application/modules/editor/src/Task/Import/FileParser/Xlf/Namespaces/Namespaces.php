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
use LogicException;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments as ImportComments;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\NamespaceRegistry;
use ReflectionException;

/**
 * XLF Namespace Handler
 */
class Namespaces extends AbstractNamespace
{
    /**
     * @var AbstractNamespace[]
     */
    private array $namespaces;

    /**
     * @throws ReflectionException
     */
    public function __construct(NamespaceRegistry $registry, XmlParser $xmlParser, ImportComments $comments)
    {
        parent::__construct($xmlParser, $comments);
        $this->namespaces = $registry->getImplementations($xmlParser, $comments);
    }

    public static function isApplicable(string $xliff): bool
    {
        throw new LogicException('It makes no sense to call that method on the general namespace class');
    }

    public static function getExportCls(): ?string
    {
        throw new LogicException('It makes no sense to call that method on the general namespace class');
    }

    public function transunitAttributes(array $attributes, SegmentAttributes $segmentAttributes): void
    {
        $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * calls the function in each namespace object, passes the arguments
     * @param array|bool|null $default optional, the default result if no namespace was found
     */
    protected function call(
        string $function,
        array $arguments,
        array|bool|null|string $default = null
    ): ContentConverter|array|bool|null|string {
        //it is slightly unusual that a XLF file has multiple namespaces, but still it can happen
        // we handle it, that if an empty result is produced, we proceed with the next namespace
        foreach ($this->namespaces as $namespace) {
            $result = call_user_func_array([$namespace, $function], $arguments);
            if ((is_array($result) && empty($result)) || is_null($result)) {
                //empty array or null means, check next namespace
                continue;
            }

            return $result;
        }

        //if no namespace was defined, or nothing was returned by them, we return the default result
        return $default;
    }

    public function currentSource(array $currentSourceTag, SegmentAttributes $segmentAttributes): void
    {
        $this->call(__FUNCTION__, func_get_args());
    }

    public function currentTarget(array $currentTargetTag, SegmentAttributes $segmentAttributes): void
    {
        $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @see AbstractNamespace::useTagContentOnly()
     */
    public function useTagContentOnly(): ?bool
    {
        //using null as explicit default value should trigger further investigation if the
        // tag content should be used or not (if the namespace did not provide information about it)
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function getContentConverter(
        editor_Models_Task $task,
        editor_Models_Import_FileParser_Xlf_ShortTagNumbers $shortTagNumbers,
        string $filename
    ): ContentConverter {
        //return the contentconverter given by namespace, or if none the default one
        return $this->call(__FUNCTION__, func_get_args())
            ?? parent::getContentConverter($task, $shortTagNumbers, $filename);
    }

    public function preProcessFile(string $xml): string
    {
        return $this->call(__FUNCTION__, func_get_args(), $xml);
    }
}
