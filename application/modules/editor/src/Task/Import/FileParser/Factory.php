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

namespace MittagQI\Translate5\Task\Import\FileParser;

use editor_Models_Import_FileParser as FileParser;
use editor_Models_Import_FileParser_NoParserException;
use editor_Models_Import_SupportedFileTypes;
use editor_Models_SegmentFieldManager as SegmentFieldManager;
use editor_Models_Task;
use SplFileInfo;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;

/**
 *
 */
class Factory
{

    private editor_Models_Import_SupportedFileTypes $supportedFiles;

    /**
     * @param editor_Models_Task $task
     * @param SegmentFieldManager $segmentFieldManager
     */
    public function __construct(private editor_Models_Task $task, private SegmentFieldManager $segmentFieldManager)
    {
        $this->supportedFiles = ZfExtended_Factory::get(editor_Models_Import_SupportedFileTypes::class);
    }

    /***
     * Get the file parser for given fileId and file path
     * @param int $fileId
     * @param string $filePath
     * @return FileParser|null
     * @throws Zend_Exception
     */
    public function getFileParserByExtension(int $fileId, string $filePath): ?FileParser
    {
        $file = new SplFileInfo($filePath);

        try {
            $parserClass = $this->lookupFileParserCls($file->getExtension(), $file);
        } catch (editor_Models_Import_FileParser_NoParserException $e) {
            Zend_Registry::get('logger')->exception($e, ['level' => ZfExtended_Logger::LEVEL_WARN]);
            return null;
        }

        return $this->getFileParserInstance($parserClass, $fileId, $file);
    }

    public function getFileParserInstance(string $parserClass, int $fileId, SplFileInfo $file): ?FileParser
    {
        if (! is_subclass_of($parserClass, FileParser::class)) {
            return null;
        }
        $parser = ZfExtended_Factory::get($parserClass, [
            $file->getPathname(),
            $file->getBasename(),
            $fileId,
            $this->task
        ]);
        /* @var FileParser $parser */
        $parser->setSegmentFieldManager($this->segmentFieldManager);
        return $parser;
    }

    /**
     * Looks for a suitable file parser and returns the corresponding file parser cls
     * @param string $extension
     * @param SplFileInfo $file
     * @return string
     * @throws editor_Models_Import_FileParser_NoParserException
     */
    protected function lookupFileParserCls(string $extension, SplFileInfo $file): string
    {
        $errorMessages = [];
        $parserClass = $this->supportedFiles->hasSupportedParser($extension, $file, $errorMessages);

        if (!is_null($parserClass)) {
            return $parserClass;
        }

        //'For the given fileextension no parser is registered.'
        throw new editor_Models_Import_FileParser_NoParserException('E1060', [
            'file' => $file->getPathname(),
            'task' => $this->task,
            'extension' => $extension,
            'errorMessages' => $errorMessages,
            'availableParsers' => $this->supportedFiles->getSupportedExtensions(),
        ]);
    }
}
