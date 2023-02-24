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

/**
 * File Filter to for invoking Okapi post process files on export
 */
class editor_Plugins_Okapi_FileFilter implements editor_Models_File_IFilter {
    protected editor_Models_File_FilterManager $manager;
    protected editor_Models_File_FilterConfig $config;

    /**
     * {@inheritDoc}
     * @see editor_Models_File_IFilter::initFilter()
     */
    public function initFilter(editor_Models_File_FilterManager $manager, editor_Models_File_FilterConfig $config) {
        $this->config = $config;
        $this->manager = $manager;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_File_IFilter::applyImportFilter()
     */
    public function applyImportFilter(editor_Models_Task $task, $fileId, $filePath, $parameters): string
    {
        //renames the original file to original.xlf so that our fileparsers can import them 
        return $filePath.editor_Plugins_Okapi_Connector::OUTPUT_FILE_EXTENSION;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_File_IFilter::applyExportFilter()
     */
    public function applyExportFilter(editor_Models_Task $task, $fileId, $filePath, $parameters): string
    {
        if ($this->config->context === editor_Models_Export::EXPORT_PACKAGE) {
            //we do not re-export with okapi package export but return the filename with XLF extension
            return $this->applyImportFilter($task, $fileId, $filePath, $parameters);
        }

        $worker = ZfExtended_Factory::get(editor_Plugins_Okapi_Worker::class);
        $params = [
            'type' => editor_Plugins_Okapi_Worker::TYPE_EXPORT,
            'fileId'=>$fileId,
            'file'=>$filePath
        ];
        
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), $params)) {
            return $filePath;
        }
        $worker->queue($this->config->parentWorkerId);
        return $filePath;
    }
}