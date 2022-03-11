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
 * Extends the main Test Class for some convenience methods regarding the Visual Plugin
 */
class editor_Test_VisualTest extends editor_Test_JsonTest
{
    const VISUAL_DIR = '';
    /**
     * Retrieves the visual files structure as json
     * @param string $taskGuid
     * @param string $jsonFileName
     * @return mixed
     */
    protected function getVisualFilesJSON(string $taskGuid, string $jsonFileName){
        return $this->api()->getJson('/editor/plugins_visualreview_visualreview/files?taskGuid='.urlencode($taskGuid), [], $jsonFileName);
    }
    /**
     * Retrieves the visuals HTML file for the given index
     * @param int $index
     * @return false|string
     */
    protected function getVisualHtmlFile(int $index=0){
        $htmlFileName = ($index < 1) ? 'review.html' : 'review-'.$index.'.html';
        return file_get_contents($this->api()->getTaskDataDirectory().editor_Plugins_VisualReview_Source_Files::FOLDER_REVIEW_DEPRICATED.'/'.$htmlFileName);
    }
    /**
     * Retrieves the visuals split HTML file for the given index
     * @param int $index
     * @return false|string
     */
    protected function getVisualSplitHtmlFile(int $index=0){
        $htmlFileName = ($index < 1) ? 'review.split.html' : 'review-'.$index.'.split.html';
        return file_get_contents($this->api()->getTaskDataDirectory().editor_Plugins_VisualReview_Source_Files::FOLDER_REVIEW_DEPRICATED.'/'.$htmlFileName);
    }
    /**
     * Compares the visual files structure with the passed testfile and taskGuid
     * @param string $fileToCompare
     * @param string $message
     */
    public function assertVisualFiles(string $fileToCompare, string $message=''){
        $filesList = $this->getVisualFilesJSON($this->api()->getTask()->taskGuid, $fileToCompare);
        $this->assertModelsEqualsObjects('VisualSourceFile', self::$api->getFileContent($fileToCompare), $filesList, $message);
    }
}