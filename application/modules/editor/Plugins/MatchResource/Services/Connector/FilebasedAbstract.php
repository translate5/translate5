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
 * Abstract Base Connector for filebased Resources
 */
abstract class editor_Plugins_MatchResource_Services_Connector_FilebasedAbstract extends editor_Plugins_MatchResource_Services_Connector_Abstract {

    /**
     * @var string
     */
    const STATUS_IMPORT = 'import';
    
    /**
     * Adds the given file to the underlying system on TM creation
     * @param array $fileinfo as given from upload (tmp_name, name, type, size)
     * @return boolean
     */
    abstract public function addTm(array $fileinfo = null);
    
    /**
     * Adds the given file to the underlying system into an already existing TM
     * @param array $fileinfo as given from upload (tmp_name, name, type, size)
     * @return boolean
     */
    abstract public function addAdditionalTm(array $fileinfo = null);
    
    /**
     * Gets the TM file content from the underlying system
     * @param $mime the desired mimetype of the export
     * @return string
     */
    abstract public function getTm($mime);
    
    /**
     * Returns an associative array of filetypes which can be uploaded to the underlying system
     *  key: file extension
     *  value: mimetype
     */
    abstract public function getValidFiletypes();

    /**
     * Deletes the connected TM on the configured Resource
     */
    public function delete() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
}