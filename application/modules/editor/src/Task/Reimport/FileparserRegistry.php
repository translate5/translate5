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
declare(strict_types = 1);

namespace MittagQI\Translate5\Task\Reimport;

use editor_Models_Import_FileParser as AbstractFileparser;
use editor_Models_Import_FileParser_Xlf;
use editor_Models_Import_FileParser_Xml;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\ContentBase;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\Xliff;
use ZfExtended_Exception;
use ZfExtended_Factory;

/**
 * List of file types and file type handler class for segment content processor
 */
class FileparserRegistry
{
    private static FileparserRegistry $singleton;

    private array $fileparserReimporterMap = [
        editor_Models_Import_FileParser_Xlf::class => Xliff::class,
        editor_Models_Import_FileParser_Xml::class => Xliff::class,
    ];
    private array $supportedFileExtensions = [];

    /**
     * @return FileparserRegistry
     */
    public static function getInstance(): self
    {
        if (empty(self::$singleton)) {
            self::$singleton = new self();
        }
        return self::$singleton;
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function __construct()
    {
        //add default file extensions
        foreach ($this->fileparserReimporterMap as $fileparser => $reimporter) {
            $this->mergeSupportedExtensions($fileparser);
        }
    }

    /**
     * @param string $fileparserClass
     * @param string $reimporterClass
     * @return void
     * @throws ZfExtended_Exception
     */
    public function addFileparser(string $fileparserClass, string $reimporterClass): void
    {
        if (is_a($fileparserClass, AbstractFileparser::class) && is_a($reimporterClass, ContentBase::class)) {
            $this->fileparserReimporterMap[$fileparserClass] = $reimporterClass;
            $this->mergeSupportedExtensions($fileparserClass);
        }
    }

    /**
     * @return array|string[]
     */
    public function getSupportedFileTypes(): array
    {
        return array_keys($this->supportedFileExtensions);
    }

    /***
     * @param AbstractFileparser $fileparser
     * @param array $arguments
     * @return mixed|string
     */
    public function getReimporterInstance(AbstractFileparser $fileparser, array $arguments): ?ContentBase
    {
        if ($this->isSupported($fileparser::class)) {
            // check the class hierarchy of the current fileparser if one has as a reimporter configured.
            $class = get_class($fileparser);
            do {
                if (isset($this->fileparserReimporterMap[$class])) {
                    return ZfExtended_Factory::get($this->fileparserReimporterMap[$class], $arguments);
                }
            } while (($class = get_parent_class($class)) !== false);
        }
        return null;
    }

    /**
     * @param string $fileparser
     * @return void
     * @throws ZfExtended_Exception
     */
    private function mergeSupportedExtensions(string $fileparser): void
    {
        if (is_a($fileparser, AbstractFileparser::class)) {
            $this->supportedFileExtensions = array_unique(array_merge(
                $this->supportedFileExtensions,
                $fileparser::getFileExtensions()
            ));
        }
    }

    /**
     * returns true if the given file parser has re-import functionality
     * @param string $fileparser
     * @return bool
     */
    public function isSupported(string $fileparser): bool
    {
        return is_subclass_of($fileparser, AbstractFileparser::class) && $fileparser::IS_REIMPORTABLE;
    }

    /**
     * @return string[]
     */
    public function getRegisteredFileparsers(): array
    {
        return array_keys($this->fileparserReimporterMap);
    }
}