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

/**
 * Transfer translated terms back into their TermCollections
 */
class editor_Models_Export_Exported_TransferWorker extends editor_Models_Export_Exported_Worker {

    /**
     * @inheritdoc
     */
    protected function validateParameters($parameters = array()) {

        // Get logger
        $logger = Zend_Registry::get('logger')->cloneMe('editor.export');
        /* @var $logger ZfExtended_Logger */

        // If no folderToBeZipped-param given - log error and return false
        if (empty($parameters['folderToGetTbx'])) {
            $logger->error('E1144', 'Exported_Worker: No Parameter "folderToGetTbx" given for worker.');
            return false;
        }

        // Return true
        return true;
    }

    /**
     * @inheritdoc
     */
    public function setup($taskGuid = null, $parameters = []) {

        // Get config runtimeOptions
        $rop = Zend_Registry::get('config')->runtimeOptions;

        // Get worker server origin
        $workerServer = $rop->worker->server ?: $rop->server->protocol . $rop->server->name;

        // Init worker
        $this->init($taskGuid, [
            'folderToGetTbx' => $parameters['exportFolder'],
            'cookie' => $parameters['cookie'],
            'url' => $workerServer . APPLICATION_RUNDIR . '/editor/'
        ]);
    }

    /**
     * Get translated tbx file(s) for the task and import to the corresponsing TermCollection(s)
     *
     * @param editor_Models_Task $task
     */
    protected function doWork(editor_Models_Task $task) {

        // Get params
        $parameters = $this->workerModel->getParameters();

        // Get all exported tbx files
        $tbxA = glob($parameters['folderToGetTbx'] . DIRECTORY_SEPARATOR . 'TermCollection*.tbx');

        // Create API instance
        // TODO FIXME: we should never use Test-API code in productive code
        $api = new ZfExtended_Test_ApiHelper('ZfExtended_Test_ApiTestcase');
        $api->setAuthCookie($parameters['cookie']);
        $url = $parameters['url'];

        // Api request data
        $data = [
            'format' => 'jsontext',
            'deleteTermsLastTouchedOlderThan' => '',
            'deleteProposalsLastTouchedOlderThan' => '',
        ];

        // Responses
        $json = [];

        // Get target language rfc5646-code
        $targetLangId = $task->getTargetLang();
        $targetLangRfc = ZfExtended_Factory::get('editor_Models_Languages')->load($targetLangId)->rfc5646;

        // Foreach exported tbx file
        foreach ($tbxA as $idx => $tbx) {

            // If exported tbx file name does not match the pattern - skip
            if (!preg_match('~TermCollection_([0-9]+)_[0-9]+\.tbx$~', $tbx, $m)) {
                continue;
            }

            // Get raw tbx contents
            $raw = file_get_contents($tbx);

            // Spoof rfc5646-code of source language with target language one
            $raw = preg_replace('~(<langSet.+?xml:lang=")([^"]+)(".*?>)~', '$1' . $targetLangRfc . '$3', $raw);

            // Add file upload
            $api->addFilePlain('tmUpload', $raw, 'text/xml', $m[0]);

            // Make request to imitate language resource tbx import dialog submit
            $json[$idx] = $api->postJson($url.'languageresourceinstance/'.$m[1].'/import/', $data);
        }
    }
}