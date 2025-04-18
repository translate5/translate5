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

namespace MittagQI\Translate5\Test;

use editor_Plugins_VisualReview_Init;
use MittagQI\Translate5\Plugins\VisualReview\Source\SourceFiles;
use stdClass;

/**
 * Extends the main Test Class for some convenience methods regarding the Visual Plugin
 * This Class Expects one Task to be setup & imported !
 */
abstract class VisualTestAbstract extends JsonTestAbstract
{
    protected static array $requiredPlugins = [
        editor_Plugins_VisualReview_Init::class,
    ];

    /**
     * Retrieves the visual files structure as json
     * @return array|stdClass
     */
    protected function getVisualFilesJson(string $jsonFileName)
    {
        $taskId = static::getTask()->getId();

        return static::api()->getJson('/editor/plugins_visualreview_sourcefile/?taskId=' . $taskId, [], $jsonFileName);
    }

    /**
     * Retrieves the visuals HTML file for the given index
     * @param bool $isSplitFile : if given, this will be the split file (it does not always exist!)
     * @param int $index : index of the file, matches the fileOrder of the visual source file
     * @return false|string
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    protected function getVisualHtmlFile(bool $isSplitFile = false, int $index = 0)
    {
        $fileName = static::getTask()->getDataDirectory()
            . SourceFiles::FOLDER_REVIEW_DEPRECATED
            . '/' . $this->getVisualHtmlFileName($isSplitFile, $index);
        $this->assertFileExists($fileName);

        return file_get_contents($fileName);
    }

    /**
     * Retrieves the visuals HTML file name for the given index and if split
     * @return string
     */
    protected function getVisualHtmlFileName(bool $isSplitFile = false, int $index = 0)
    {
        $indexMarker = ($index < 1) ? '' : '-' . $index;

        return ($isSplitFile) ? 'review' . $indexMarker . '.split.html' : 'review' . $indexMarker . '.html';
    }

    /**
     * Asserts whether the visual html file defined by $isSplitFile and $index contains the given text
     */
    public function assertVisualHtmlContains(string $text, bool $isSplitFile = false, bool $doStripTags = false, int $index = 0, string $message = '')
    {
        $html = $this->getVisualHtmlFile($isSplitFile, $index);
        if ($doStripTags) {
            $html = strip_tags($html);
        }
        if (empty($html)) {
            $this->fail($message . ' [File ' . $this->getVisualHtmlFileName($isSplitFile, $index) . ' was not found or had no contents]');
        } else {
            $this->assertTrue(str_contains($html, $text), $message . ' [File ' . $this->getVisualHtmlFileName($isSplitFile, $index) . ' did not contain "' . $text . '"]');
        }
    }

    /**
     * Asserts whether the visual html file defined by $isSplitFile and $index exists and does not contain the given text
     */
    public function assertVisualHtmlNotContains(string $text, bool $isSplitFile = false, bool $doStripTags = false, int $index = 0, string $message = '')
    {
        $html = $this->getVisualHtmlFile($isSplitFile, $index);
        if ($doStripTags) {
            $html = strip_tags($html);
        }
        if (empty($html)) {
            $this->fail($message . ' [File ' . $this->getVisualHtmlFileName($isSplitFile, $index) . ' was not found or had no contents]');
        } else {
            $this->assertFalse(str_contains($html, $text), $message . ' [File ' . $this->getVisualHtmlFileName($isSplitFile, $index) . ' did not contain "' . $text . '"]');
        }
    }

    /**
     * Asserts whether the visual html file defined by $isSplitFile and $index contains the given text
     */
    public function assertVisualHtmlMatches(string $pattern, bool $isSplitFile = false, int $index = 0, string $message = '')
    {
        $html = $this->getVisualHtmlFile($isSplitFile, $index);
        if (empty($html)) {
            $this->fail($message . ' [File ' . $this->getVisualHtmlFileName($isSplitFile, $index) . ' was not found or had no contents]');
        } else {
            $this->assertTrue((preg_match($pattern, $html) === 1), $message . ' [File ' . $this->getVisualHtmlFileName($isSplitFile, $index) . ' did not match "' . $pattern . '"]');
        }
    }

    /**
     * Compares the visual files structure with the passed testfile and taskGuid
     */
    public function assertVisualFiles(string $fileToCompare, string $message = '')
    {
        $filesList = $this->getVisualFilesJson($fileToCompare);
        $this->assertModelsEqualsObjects('VisualSourceFile', static::api()->getFileContent($fileToCompare), $filesList, $message);
    }

    /**
     * Checks if a resource in the visual rootfolder of a task exists
     * $subPath: a sub-path or filename that is expected to exist in the visual folder, e.g. "images/someimage.jpg"
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    protected function assertVisualResourceExists(string $subPath, string $message = '')
    {
        $filePath = static::getTask()->getDataDirectory()
            . SourceFiles::FOLDER_REVIEW_DEPRECATED
            . '/' . ltrim($subPath, '/');
        $this->assertFileExists($filePath, $message);
    }
}
