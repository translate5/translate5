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
abstract class editor_Test_VisualTest extends editor_Test_JsonTest
{
    protected static array $requiredPlugins = [
        'editor_Plugins_VisualReview_Init'
    ];
    /**
     * Retrieves the visual files structure as json
     * @param string $taskGuid
     * @param string $jsonFileName
     * @return mixed
     */
    protected function getVisualFilesJSON(string $taskGuid, string $jsonFileName){
        return static::api()->getJson('/editor/plugins_visualreview_visualreview/files?taskGuid='.urlencode($taskGuid), [], $jsonFileName);
    }
    /**
     * Retrieves the visuals HTML file for the given index
     * @param bool $isSplitFile: if given, this will be the split file (it does not always exist!)
     * @param int $index: index of the file, matches the fileOrder of the visual source file
     * @return false|string
     */
    protected function getVisualHtmlFile(bool $isSplitFile=false, int $index=0){
        return file_get_contents(static::api()->getTaskDataDirectory().editor_Plugins_VisualReview_Source_Files::FOLDER_REVIEW_DEPRICATED.'/'.$this->getVisualHtmlFileName($isSplitFile, $index));
    }
    /**
     * Retrieves the visuals HTML file name for the given index and if split
     * @param bool $isSplitFile: if given, this will be the split file (it does not always exist!)
     * @param int $index: index of the file, matches the fileOrder of the visual source file
     * @return string
     */
    protected function getVisualHtmlFileName(bool $isSplitFile=false, int $index=0){
        $indexMarker = ($index < 1) ? '' : '-'.$index;
        return ($isSplitFile) ? 'review'.$indexMarker.'.split.html' : 'review'.$indexMarker.'.html';
    }
    /**
     * Asserts whether the visual html file defined by $isSplitFile and $index contains the given text
     * @param string $text
     * @param bool $isSplitFile
     * @param int $index
     * @param string $message
     */
    public function assertVisualHtmlContains(string $text, bool $isSplitFile=false, int $index=0, string $message=''){
        $html = $this->getVisualHtmlFile($isSplitFile, $index);
        if(empty($html)){
            $this->assertTrue(!empty($html), $message.' [File '.$this->getVisualHtmlFileName($isSplitFile, $index).' was not found or had no contents]');
        } else {
            $this->assertTrue(str_contains($html, $text), $message.' [File '.$this->getVisualHtmlFileName($isSplitFile, $index).' did not contain "'.$text.'"]');
        }
    }
    /**
     * Asserts whether the visual html file defined by $isSplitFile and $index contains the given text
     * @param string $pattern
     * @param bool $isSplitFile
     * @param int $index
     * @param string $message
     */
    public function assertVisualHtmlMatches(string $pattern, bool $isSplitFile=false, int $index=0, string $message=''){
        $html = $this->getVisualHtmlFile($isSplitFile, $index);
        if(empty($html)){
            $this->assertTrue(!empty($html), $message.' [File '.$this->getVisualHtmlFileName($isSplitFile, $index).' was not found or had no contents]');
        } else {
            $this->assertTrue((preg_match($pattern, $html) === 1), $message.' [File '.$this->getVisualHtmlFileName($isSplitFile, $index).' did not match "'.$pattern.'"]');
        }
    }
    /**
     * Compares the visual files structure with the passed testfile and taskGuid
     * @param string $fileToCompare
     * @param string $message
     */
    public function assertVisualFiles(string $fileToCompare, string $message=''){
        $filesList = $this->getVisualFilesJSON(static::api()->getTask()->taskGuid, $fileToCompare);
        $this->assertModelsEqualsObjects('VisualSourceFile', static::api()->getFileContent($fileToCompare), $filesList, $message);
    }
}