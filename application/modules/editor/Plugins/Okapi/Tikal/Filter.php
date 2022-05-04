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
 * File Filter to for invoking Okapi Tikal to pre/post process files on import/export
 */
class editor_Plugins_Okapi_Tikal_Filter implements editor_Models_File_IFilter {
    protected $manager;
    protected $importConfig;
    /**
     * {@inheritDoc}
     * @see editor_Models_File_IFilter::initFilter()
     */
    public function initFilter(editor_Models_File_FilterManager $manager, $parentWorkerId, editor_Models_Import_Configuration $importConfig = null) {
        $this->manager = $manager;
        $this->importConfig = $importConfig;
    }
    
    /**
     * The problem of the tikal import filter is, that it runs after all files are already registered in LEK_files table, 
     * so changing names etc are not reflected in the LEK_files table then. 
     * This is problematic by design, fixing this would touch everything file related: The clumsy tree storage, missing path in the files table etc. 
     * FIXME: 
     *  remove filetree storage, just save files and their paths in the LEK_files table, then they could be easily renamed
     *  The trees are not read so often, so creating a tree from a given path list is also no problem.
     *  
     *  
     * {@inheritDoc}
     * @see editor_Models_File_IFilter::applyImportFilter()
     */
    public function applyImportFilter(editor_Models_Task $task, $fileId, $filePath, $parameters){
        $tikal = ZfExtended_Factory::get('editor_Plugins_Okapi_Tikal_Connector', [$task, $this->importConfig]);
        /* @var $tikal editor_Plugins_Okapi_Tikal_Connector */
        if($tikal->extract($filePath)) {
            $m = $this->manager;
            $this->manager->addFilter($m::TYPE_EXPORT, $task, $fileId, get_class($this));
            return $filePath.'.xlf';
        }
        return $filePath;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_File_IFilter::applyExportFilter()
     */
    public function applyExportFilter(editor_Models_Task $task, $fileId, $filePath, $parameters){
        $tikal = ZfExtended_Factory::get('editor_Plugins_Okapi_Tikal_Connector', [$task]);
        /* @var $tikal editor_Plugins_Okapi_Tikal_Connector */
        $tikal->merge($filePath);
    }
}