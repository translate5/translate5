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
 * Connector to Globalese
 * One Connector Instance can contain one Globalese Project
 * 
 * FIXME errorhandling: throwing meaningful exceptions here on connection problems should be enough. Test it!
 *       for error handling: either you distinguish here between critical (stops processing in the Worker) or non critical (Worker can proceed) errors
 *       or you always throw here exceptions and you decide in the worker if the exceptions is critical or not
 */
class editor_Plugins_GlobalesePreTranslation_Connector {
    
    /**
     * FIXME implement me
     * @param editor_Models_Task $task
     * @return integer
     */
    public function createProject(editor_Models_Task $task) {
        //the project name can be: "Translate5 ".$taskGuid I dont see any need to transfer our real taskname
        //save task internally for getting the languages from
        //save the projectId internally for further processing
        return 123; //returns the new created project id
    }
    
    /**
     * FIXME implement me
     * @param integer $projectId
     */
    public function removeProject() {
        
    }
    
    /**
     * FIXME implement me
     * 
     * @param string $filename
     * @param string $xliff the xliff content as plain string
     * @return integer the fileid of the generated file
     */
    public function upload($filename, $xliff) {
        //throw an error if internal projectId is empty
        
        //creates file in Globalese
        //uploads file to Globalese
        //starts translation in Globalese
        $this->dummyXliff = str_replace('<target state="needs-translation"></target>', '<target state="needs-review-translation" state-qualifier="leveraged-mt" translate5:origin="Globalese">Dummy translated Text</target>', $xliff); 
        $this->dummyFileId = rand();
        return $this->dummyFileId; //fileid
    }
    
    /**
     * returns the first found translated fileid, 
     * @return mixed fileId of found file, null when there are pending files but non finished, false if there are no more files
     */
    public function getFirstTranslated() {
        //FIXME implement me and test me with all possible results
        //loops over all results and logs and deletes files with "failed" status 
        //returns the first found translated fileid, null if none found
        return $this->dummyFileId;
    }
    
    /**
     * gets the file content to the given fileid 
     * @param integer $fileId
     * @param boolean $remove default true, if true removes the fetched file immediatelly from Globalese project
     * @return string
     */
    public function getFileContent($fileId, $remove = true) {
        //FIXME implement me
        $this->dummyFileId = false;
        return $this->dummyXliff;
    }
}