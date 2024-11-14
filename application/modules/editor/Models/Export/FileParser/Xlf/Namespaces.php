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

declare(strict_types=1);

use editor_Models_Import_FileParser_XmlParser as XmlParser;
use MittagQI\Translate5\Task\Export\FileParser\Xlf\Comments;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\NamespaceRegistry;

/**
 * XLF Namespace Handler
 */
class editor_Models_Export_FileParser_Xlf_Namespaces extends editor_Models_Export_FileParser_Xlf_Namespaces_Abstract
{
    /**
     * @var editor_Models_Export_FileParser_Xlf_Namespaces_Abstract[]
     */
    protected array $namespaces = [];

    public function __construct(
        NamespaceRegistry $registry,
        XmlParser $xmlparser,
        Comments $comments
    ) {
        parent::__construct($xmlparser, $comments);
        $this->namespaces = $registry->getImplementations($xmlparser, $comments);
    }

    protected function call(
        string $function,
        array $arguments,
        string $default = null
    ): string {
        // it is slightly unusual that a XLF file has multiple namespaces, but still it can happen
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

    public function postProcessFile(string $xml): string
    {
        return $this->call(__FUNCTION__, func_get_args(), $xml);
    }
}
