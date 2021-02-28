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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
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
abstract class editor_Services_Connector_FilebasedAbstract extends editor_Services_Connector_Abstract {

    
    /***
     * 100% match value
     * @var integer
     */
    const EXACT_MATCH_VALUE=100;
    
    /***
     * Exact-exact match percent value.
     * An exact-exact match is a 100% match, that has the same document name as the currently translated document.
     * @var integer
     */
    const EXACT_EXACT_MATCH_VALUE=101;
    
    /***
     * Context match percent value.
     * A context match is an exact-exact match,
     * that in addition has the same context set in TM as in the document - usally an ID,
     * which often is the line number or segment-id (depends on what the import does)
     * @var integer
     */
    const CONTEXT_MATCH_VALUE=103;
    
    /***
     * A repetition is a segment, that already showed up with the same words and tag order further above in the same task
     * @var integer
     */
    const REPETITION_MATCH_VALUE=102;
    
    /***
     * Matches from term collections are highest rated
     * @var integer
     */
    const TERMCOLLECTION_MATCH_VALUE = 104;
    
    /**
     * @var string
     */
    const STATUS_IMPORT = 'import';
    
    
    /**
     * Adds the given file to the underlying system on TM creation
     * @param array $fileinfo as given from upload (tmp_name, name, type, size)
     * @return boolean
     */
    abstract public function addTm(array $fileinfo = null,array $params=null);
    
    /**
     * Adds the given file to the underlying system into an already existing TM
     * @param array $fileinfo as given from upload (tmp_name, name, type, size)
     * @return boolean
     */
    abstract public function addAdditionalTm(array $fileinfo = null,array $params=null);
    
    /**
     * Gets the TM file content from the underlying system
     * @param string $mime the desired mimetype of the export
     * @return string
     */
    abstract public function getTm($mime);
    
    /**
     * Returns an associative array of filetypes which can be uploaded to the underlying system.
     * 'Accept' in the header can take multiple types; to provide this we use an array with strings for the values.
     *  key: (string) file extension
     *  value: (string[]) mimetype(s)
     * @return array
     */
    abstract public function getValidFiletypes();
    
    /**
     * Returns an associative array of filetypes which can be exported by the underlying system.
     * 'Content-Type' in the header takes a single media-type only => value MUST BE A SINGLE STRING; not an array with strings.
     *  key: (string) file extension
     *  value: (string) mimetype
     * @return array
     */
    abstract public function getValidExportTypes();

    /**
     * Deletes the connected TM on the configured Resource
     */
    public function delete() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
}