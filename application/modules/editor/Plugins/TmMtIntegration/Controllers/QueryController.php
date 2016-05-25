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
 * Resources are no valid Models/Entitys, we support only a generated Resource listing
 * One Resource is one available configured connector, Languages and Title can be customized in the TM Overview List
 * 
 * 
 * 
 * - each TM/concordance query per TM creates one own request.
- extends the default WorkerController
- enables post- and getAction since these are needed by the GUI
- prefill and initialise the Worker Object with all Translationmemory related stuff, so that from GUI must only come the segmentId or the segmentContent, the requested TM ID and if it is a search or a MT request, this results in the following JSON:
{
 type: concordance|mtmatch,
 segmentId: segment Id for reference, 
 query: string to query, if omitted load above segment and get content from there
 result: contains the matches / search results as list
}
- if answer cannot not be created directly (200) but is created to be retrieved later (202), the result field should contain one single row which holds the „loading...“ record to be rendered in the grid. This prevents an implementation in Javascript to create a dummy „loading...“ entry there.
- putAction can be disabled
- getAction shall return the above saved worker results
- Paging: if provided by used connector, pass through to the connector
 * 
 */
class editor_Plugins_TmMtIntegration_QueryController extends ZfExtended_RestController {
    protected $entityClass = 'ZfExtended_Models_Worker';
    
    /**
     * @var ZfExtended_Models_Worker
     */
    protected $entity;
    
    public function indexAction() {
        //enable listing of tasks tmmt workers
    }
    
    public function getAction() {
        //enable getting tasks tmmt workers
    }
    
    public function postAction() {
        error_log(print_r($_POST,1));
        $session = new Zend_Session_Namespace();
        $this->decodePutData();
        $worker = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Models_Worker');
        /* @var $worker editor_Plugins_TmMtIntegration_Models_Worker */
        $worker->init($session->taskGuid, (array) $this->data);
        //enable posting tmmt workers tasks tmmt workers
        $worker->run();
        
        //FIXME make the result
        $this->view->rows = $this->entity->getDataObject();
    }
}

