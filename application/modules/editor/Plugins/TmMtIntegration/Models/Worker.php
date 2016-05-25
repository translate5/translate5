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
class editor_Plugins_TmMtIntegration_Models_Worker extends ZfExtended_Worker_Abstract {
    const TYPE_QUERY = 'query'; //tm match or mt query
    const TYPE_SEARCH = 'search'; //concordance search

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        $workerData = $parameters['workerData'];
        $toCheck = ['query','resourceId', 'service', 'type', 'tmmtId'];
        foreach($toCheck as $field) {
            if(empty($workerData->$field)) {
                error_log('Missing Parameter "'.$field.'" in '.__CLASS__);
                return false;
            }
        }
        if(!in_array($workerData->type, [self::TYPE_QUERY, self::TYPE_SEARCH])) {
            error_log('Wrong value '.$workerData->type.' for parameter "type" in '.__CLASS__);
            return false;
        }
        return true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        $res = parent::run();
        if(!empty($this->workerException)) {
            throw $this->workerException;
        }
        return $res;
    }

    /**
     * (non-PHPdoc)
     *
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {

        $parameters = $this->workerModel->getParameters();
        $workerData = $parameters['workerData'];
        /* @var $workerData editor_Plugins_TmMtIntegration_Models_QueryData */

        $manager = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Services_Manager');
        /* @var $manager editor_Plugins_TmMtIntegration_Services_Manager */
        $resource = $manager->getResourceById($workerData->service, $workerData->resourceId);

        $connector = $manager->getConnector($workerData->service, $resource);
        /* @var $connector editor_Plugins_TmMtIntegration_Services_ConnectorAbstract */

        $tmmt = ZfExtended_Factory::get('editor_Plugins_TmMtIntegration_Models_TmMt');
        /* @var $tmmt editor_Plugins_TmMtIntegration_Models_TmMt */
        $tmmt->load($workerData->tmmtId);

        //set the tmmt to be queried
        $connector->openForQuery($tmmt);

        switch ($workerData->type) {
            case self::TYPE_QUERY:
                $this->result = $connector->query((string) $workerData->query);
                break;
            case self::TYPE_SEARCH:
                $this->result = $connector->search((string) $workerData->query);
                break;
            default:
                $this->log->logError('Wrong connector query type given');
                return false;
        }

        return true;
    }
}