<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Remove all old term collection tbx cache folders from the disc.
 * Only folders for non-existing term-collections in the translate5 will be removed.
 */
set_time_limit(0);

/* @var ZfExtended_Models_Installer_DbUpdater $this */

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '394-TRANSLATE-3383-newlines-in-internal-tags.php';

class FixTranslate3383_ContentConverter
{
    public const DEV_MODE = false;

    private Zend_Db_Adapter_Abstract $db;

    private PDOStatement|Zend_Db_Statement $segmentDataStmt;

    private array $updatedTasks;

    public function __construct()
    {
        $this->db = Zend_Db_Table::getDefaultAdapter();
        $this->segmentDataStmt = $this->db->prepare('UPDATE LEK_segment_data SET original = :original, edited = :edited WHERE id = :id');
    }

    public function run(): void
    {
        $this->updatedTasks = [];
        $result = $this->db->query("SELECT * FROM `LEK_segment_data` WHERE original LIKE '%\n%' OR edited LIKE '%\n%'"); // Find all fields with newlines
        $counter = 0;
        foreach ($result->fetchAll(Zend_Db::FETCH_ASSOC) as $row) {
            $this->processSegmentDataRow($row);
            $counter++;
        }
        if ($counter > 0) {
            $this->output('Newlines in ' . $counter . ' segments had to be converted.');
        }
        // update the materialized views
        $this->updateMaterializedViews();
    }

    private function processSegmentDataRow(array $row): void
    {
        // id, taskGuid, segmentId, mid, original, edited
        $original = $this->processSegmentField($row['original']);
        $edited = $this->processSegmentField($row['edited']);

        if (self::DEV_MODE) {
            $msg = '';
            if (str_contains($original, '↵')) {
                $msg .= "\n" . '  original: ' . $original;
            }
            if (str_contains($edited, '↵')) {
                $msg .= "\n" . '  edited: ' . $edited;
            }
            if ($msg === '') {
                error_log(
                    "\n\n"
                    . '########################### ERROR ##########################'
                    . "\n"
                    . '  No Newlines converted in Segment ' . $row['id'] . '.'
                );
            } else {
                error_log(
                    "\n\n"
                    . '*** Converted Segment ' . $row['id'] . ' ***'
                    . $msg
                );
            }
        } else {
            $this->segmentDataStmt->execute([
                ':original' => $original,
                ':edited' => $edited,
                ':id' => $row['id'],
            ]);
        }
        // memorize which segments were updated by task
        if (! array_key_exists($row['taskGuid'], $this->updatedTasks)) {
            $this->updatedTasks[$row['taskGuid']] = [];
        }
        $this->updatedTasks[$row['taskGuid']][] = intval($row['segmentId']);
        usleep(10);
    }

    private function processSegmentField(?string $segment): string
    {
        return preg_replace_callback(
            editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS,
            function ($matches) {
                return editor_Utils::visualizeNewlines($matches[0]);
            },
            $segment
        );
    }

    /**
     * Updates the affected materialized views
     */
    private function updateMaterializedViews(): void
    {
        $processsedTasks = 0;
        $processsedSegments = 0;

        foreach ($this->updatedTasks as $taskGuid => $segmentIds) {
            try {
                $segmentFieldManager = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);
                $segmentFieldManager->initFields($taskGuid);
                $materializedView = $segmentFieldManager->getView();

                if ($materializedView->exists()) {
                    foreach ($segmentIds as $segmentId) {
                        try {
                            $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
                            $segment->load($segmentId);
                            $materializedView->updateSegment($segment);
                            $processsedSegments++;
                            usleep(10);
                        } catch (Throwable $e) {
                            $this->output('ERROR processing segment ' . $segmentId . ': ' . $e->getMessage());
                        }
                    }
                    $processsedTasks++;
                }
            } catch (Throwable $e) {
                $this->output('ERROR processing task ' . $taskGuid . ': ' . $e->getMessage());
            }
        }
        if ($processsedSegments > 0) {
            $this->output('Newlines in ' . $processsedSegments . ' segment-views in ' . $processsedTasks . ' tasks had to be adjusted.');
        }
    }

    private function output(string $msg): void
    {
        if (self::DEV_MODE) {
            error_log(
                "\n\n"
                . '************************************************************'
                . "\n"
                . $msg
            );
        } else {
            echo $msg . "\n";
        }
    }
}

$fixer = new FixTranslate3383_ContentConverter();
$fixer->run();
