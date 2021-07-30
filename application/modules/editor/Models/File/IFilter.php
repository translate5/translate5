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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 */
interface editor_Models_File_IFilter {
    /**
     * Setting the filter manager so that it can be used internally
     * @param editor_Models_File_FilterManager $manager
     * @param int $parentWorkerId
     * @param editor_Models_Import_Configuration $importConfig
     */
    public function initFilter(editor_Models_File_FilterManager $manager, $parentWorkerId, editor_Models_Import_Configuration $importConfig = null);
    
    /**
     * @param editor_Models_Task $task
     * @param int $fileId
     * @param string $filePath
     * @param array $parameters
     * @return string the filename of the file (can be changed internally for further processing)
     */
    public function applyImportFilter(editor_Models_Task $task, $fileId, $filePath, $parameters);
    
    /**
     * @param editor_Models_Task $task
     * @param int $fileId
     * @param string $filePath
     * @param array $parameters
     * @return string the filename of the file (can be changed internally for further processing)
     */
    public function applyExportFilter(editor_Models_Task $task, $fileId, $filePath, $parameters);
}