<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * Moses Connector
 *
 * The dummy CSV file must be:
 * , separated
 * " as enclosure
 * "" as escape
 * This should be the CSV defaults.
 * The first column must be an id, the second the source and the theird column the target values. Other columns are ignored.
 */
class editor_Plugins_TmMtIntegration_Services_DummyFileTm_Connector extends editor_Plugins_TmMtIntegration_Services_ConnectorAbstract {

    protected $tm;
    protected $uploadedFile;

    public function __construct() {
        $eventManager = Zend_EventManager_StaticEventManager::getInstance();
        $eventManager->attach('editor_Plugins_TmMtIntegration_TmmtController', 'afterPostAction', array($this, 'handleAfterTmmtSaved'));
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::addTm()
     */
    public function addTm(string $filename, editor_Plugins_TmMtIntegration_Models_TmMt $tm){
        $this->uploadedFile = $filename;
        $this->tm = $tm;
        //do nothing here, since we need the entity ID to save the TM
        return true;
    }

    /**
     * in our dummy file TM the TM can only be saved after the TM is in the DB, since the ID is needed for the filename
     */
    public function handleAfterTmmtSaved() {
        move_uploaded_file($this->uploadedFile, $this->getTmFile($this->tm->getId()));
    }

    protected function getTmFile($id) {
        return APPLICATION_PATH.'/../data/dummyTm_'.$id;
    }

    public function synchronizeTmList() {
        //read file list
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::open()
     */
    public function openForQuery(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        $this->tm = $tmmt;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::query()
     */
    public function query(string $queryString,string $segmentId) {

        if(stripos($this->tm->getName(), 'slow') !== false) {
            sleep(rand(5, 15));
        }
        
        $file = new SplFileInfo($this->getTmFile($this->tm->getId()));
        if(!$file->isFile() || !$file->isReadable()) {
            throw new ZfExtended_NotFoundException('requested TM file for dummy TM with the tmmtId '.$this->tm->getId().' not found!');
        }
        $file = $file->openFile();

        $result = array();
        $i = 0;
        while($line = $file->fgetcsv(",", '"', '"')) {
            if($i++ == 0 || empty($line) || empty($line[0]) || empty($line[1])){
                continue;
            }

            similar_text(strip_tags($queryString), strip_tags($line[1]), $percent);
            if($percent < 80) {
                continue;
            }
            $data = new stdClass();
            $data->source = strip_tags($line[1]);
            $data->target = strip_tags($line[2]);
            $data->matchrate = $percent;
            $data->attributes = 'Attributes: can be empty when service does not provide attributes. If not empty, then already preformatted for tooltipping!';
            $data->segmentId = $segmentId;
            $result[] = $data;
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::search()
     */
    public function search(string $searchString) {
        return $this->query($searchString);
    }

    //
    // Abstract Methods, to be implemented but not needed by this type of Service:
    //
/**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::open()
     */
    public function open(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        error_log("Opened Tmmt ".$tmmt->getName().' - '.$tmmt->getServiceName());
    }

    /**
     * (non-PHPdoc)
     * @see editor_Plugins_TmMtIntegration_Services_ConnectorAbstract::close()
     */
    public function close(editor_Plugins_TmMtIntegration_Models_TmMt $tmmt) {
        error_log("Closed Tmmt ".$tmmt->getName().' - '.$tmmt->getServiceName());

    }
}