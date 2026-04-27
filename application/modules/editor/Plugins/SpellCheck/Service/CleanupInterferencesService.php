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

namespace MittagQI\Translate5\Plugins\SpellCheck\Service;

use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class CleanupInterferencesService
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter()
        );
    }

    public function cleanupForTask(string $taskGuid): void
    {
        $fieldManager = \editor_Models_SegmentFieldManager::getForTaskGuid($taskGuid);

        $fields = array_keys($fieldManager->getFieldList());

        if (empty($fields)) {
            return;
        }

        $table = \editor_Models_Db_SegmentQuality::TABLE_NAME;
        $termType = \editor_Plugins_TermTagger_QualityProvider::qualityType();
        $spellCheckType = \editor_Plugins_SpellCheck_QualityProvider::qualityType();

        $quotedFields = implode(',', array_map([$this->db, 'quote'], $fields));

        // Delete spellcheck qualities that are completely contained within a term quality range in the same field
        $sql = sprintf(
            'DELETE sc FROM %1$s sc
             INNER JOIN %1$s term
                 ON sc.taskGuid = term.taskGuid
                 AND sc.field = term.field
                 AND sc.startIndex >= term.startIndex
                 AND sc.endIndex <= term.endIndex
                 AND sc.segmentId = term.segmentId
             WHERE sc.taskGuid = %2$s
               AND sc.type = %3$s
               AND term.type = %4$s
               AND sc.field IN (%5$s)',
            $this->db->quoteIdentifier($table),
            $this->db->quote($taskGuid),
            $this->db->quote($spellCheckType),
            $this->db->quote($termType),
            $quotedFields
        );

        $this->db->query($sql);
    }
}
