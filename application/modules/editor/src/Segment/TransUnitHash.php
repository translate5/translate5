<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Segment;

use Zend_Config;

class TransUnitHash
{
    public function __construct(
        protected Zend_Config $config,
        protected int $fileId
    ) {
    }

    public function createForSub(string $sourceFileId, string $transunitId, string $subId): string
    {
        $includeSubInLength = (bool) $this->config->runtimeOptions->import->xlf->includedSubElementInLengthCalculation;
        if (! $includeSubInLength) {
            // Make a different tu hash if sub elements should not be included in the total transunit length.
            $transunitId .= $subId;
        }

        return $this->create($sourceFileId, $transunitId);
    }

    /**
     * Generating unique transunitHash out of the provided arguments
     */
    public function create(string $sourceFileId, string $transunitId): string
    {
        return md5(implode('_', [$this->fileId, $sourceFileId, $transunitId]));
    }
}
