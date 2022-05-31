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
 *
 * REST Endpoint Controller to serve a Bconfs Filter List for the Bconf-Management in the Preferences
 *
 */
class editor_Plugins_Okapi_BconfFilterController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Plugins_Okapi_Models_BconfFilter';

    /** @var editor_Plugins_Okapi_Models_BconfFilter $entity */
    protected $entity;

    /**
     * sends all bconf filters as JSON
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function getdefaultfiltersAction() {
        $bconffilter = new editor_Plugins_Okapi_Models_DefaultBconfFilter();
        $default_fprms = $bconffilter->loadAll();

        //
        $rows = [];
        foreach($default_fprms as &$fprm){
            unset($fprm['id']);
            unset($fprm['extensions']);
            $rows[$fprm['okapiId']] = &$fprm;
        }
        unset($fprm);

        $dataDir = editor_Plugins_Okapi_Init::getBconfStaticDataDir();
        chdir($dataDir . 'fprm/translate5/');
        $t5_fprms = glob("*@translate5*.fprm");
        foreach($t5_fprms as $fprm){
            $okapiId = substr($fprm, 0, -5); // remove .fprm
            // okf_xml@translate5-AndroidStrings -> okf_xml-AndroidStrings
            $parentId = str_replace('@translate5', '', $okapiId);
            $row = @$rows[$parentId];
            if($row){
                $row['okapiId'] = $okapiId;
                $rows[$parentId] = $row;
            } else {
                // okf_xml@translate5-tbx-translate-definitions-setup-ITS.fprm -> okf_xml
                $t5Pos = strpos($okapiId, '@translate5-');
                $parentId = substr($okapiId, 0, $t5Pos);
                $name = substr($okapiId, $t5Pos + 12);
                $row = $rows[$parentId];
                $row['okapiId'] = $okapiId;
                if($name){
                    $row['name'] = ucfirst($name);
                }
                $rows[$okapiId] = $row;
            }
        }

        $this->view->rows = array_values($rows); // remove named indexes
        $this->view->total = count($rows);

    }

    public function indexAction() {
        $db = $this->entity->db;
        $bconfId = $this->getParam('bconfId');
        $bconf = new editor_Plugins_Okapi_Models_Bconf();

        $s = $db->select();
        $s->from($db, ['okapiId', 'name', 'description']);
        $s->where('bconfId = ?', $bconfId);
        $this->view->rows = $db->fetchAll($s)->toArray();
        $this->view->total = count($this->view->rows);

        if(!$this->view->metaData){
            $this->view->metaData = new stdClass();
        }
        $this->view->metaData->{'extensions-mapping'}
            = file_get_contents($bconf->getFilePath($bconfId, 'extensions-mapping.txt'));
    }

    protected function entityLoad() {
        $id = $this->getRequest()->getParam('id');
        if(gettype($id) != 'array'){
            $path = $this->getRequest()->getPathInfo();
            // /editor/plugins_okapi_bconffilter/8-okf_odf%40translate5.test
            $id = explode('-.-',urldecode(basename($path)));
        }
        $this->entity->load($id[0], $id[1]);
    }


}