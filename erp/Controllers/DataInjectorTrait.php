<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

trait erp_Controllers_DataInjectorTrait {
    
    //Months names used by excel export
    private $monthNames =[
            0=>"Januar",
            1=>"Februar",
            2=>"März",
            3=>"April",
            4=>"Mai",
            5=>"Juni",
            6=>"Juli",
            7=>"August",
            8=>"September",
            9=>"Oktober",
            10=>"November",
            11=>"Dezember"
    ];
    
    /**
     * Injects the needed userdata to the given pmId
     */
    protected function injectPmData() {
        unset($this->data->pmName);
        
        if(empty($this->data->pmId)) {
            return;
        }
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        
        try {
            $user->load($this->data->pmId);
            $this->data->pmName = $user->getUserName();
        }
        //im catch lediglich ein log von der Exception, aber den Code nicht abbrechen, das übernimmt additionalValidations
        catch(ZfExtended_Models_Entity_NotFoundException $exception)
        {
            $this->log->logError(__CLASS__.'->'.__FUNCTION__.'; PM-User can not be loaded. '.$exception->getErrors());
            $this->additionalErrors['pmId'] = 'Der zugewiesene PM-Benutzer konnte nicht gefunden werden.';
        }
    }
    
    /**
     * Injects the needed user data to the checkker fields if entity is marked as "checked"
     */
    protected function injectCheckerData() {
        unset($this->data->checkerName);
        
        if(!isset($this->data->checked)) {
            return;
        }
        
        if ($this->data->checked != true) {
            $this->data->checkerId = 0;
            $this->data->checkerName = '';
            $this->data->checked = 0;
            return;
        }
        
        try {
            $user = $this->loadAndGetSessionUser();
            $this->data->checked = 1;
            $this->data->checkerId = $user->getId();
            $this->data->checkerName = $user->getUserName();
        }
        //im catch lediglich ein log von der Exception, aber den Code nicht abbrechen, das übernimmt additionalValidations
        catch(ZfExtended_Models_Entity_NotFoundException $exception)
        {
            $this->log->logError(__CLASS__.'->'.__FUNCTION__.'; Checker-User can not be loaded. '.$exception->getErrors());
            $this->additionalErrors['checkerId'] = 'Der zugewiesene Prüf-Benutzer konnte nicht gefunden werden.';
        }
    }
    
    /**
     * Injects the needed user data to the editor fields
     */
    protected function injectEditorData() {
        unset($this->data->editorName);
        
        try {
            $user = $this->loadAndGetSessionUser();
            $this->data->editorId = $user->getId();
            $this->data->editorName = $user->getUserName();
            $this->data->modifiedDate = date('Y-m-d H:i:s');
        }
        catch(ZfExtended_Models_Entity_NotFoundException $exception)
        {
            $this->log->logError(__CLASS__.'->'.__FUNCTION__.'; Editor-User can not be loaded. '.$exception->getErrors());
            $this->additionalErrors['editorId'] = 'Der "letzter Bearbeiter"-Benutzer konnte nicht gefunden werden.';
        }
    }
    
    /**
     * @return ZfExtended_Models_User
     */
    protected function loadAndGetSessionUser() {
        $userSession = new Zend_Session_Namespace('user');
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->load($userSession->data->id);
        return $user;
    }
    
    /**
     * All value fields follow a fix naiming scheme: xxxNetValue, xxxTaxValue, xxxGrossValue
     * This method calculates the xxxTax and xxxGross Value by the given netValue and the stored taxPercentage
     * First parameters is the above xxx, the second the data field of the taxPercentage 
     * @param string $valueName
     * @param string $taxOriginName
     */
    protected function injectValueData($valueName, $taxOriginName) {
        $d = $this->data;
        $tax = lcfirst($valueName.'TaxValue');
        $gross = lcfirst($valueName.'GrossValue');
        $net = lcfirst($valueName.'NetValue');
        $get = function($name) {
            return 'get'.ucfirst($name);
        };
        
        unset($d->$tax);
        unset($d->$gross);
        
        // only end if no value is set AND taxPercent is empty.
        // taxPercent can change on changing a customer or vendor, so all money concerning fields must be recalculated !
        if (!isset($d->$net) && !isset($d->$taxOriginName)) {
            return;
        }
        
        $netValue = isset($d->$net) ? $d->$net : $this->entity->__call($get($net), array());
        $netValue = round($netValue, 2);
        $taxPercent = isset($d->$taxOriginName) ? $d->$taxOriginName : $this->entity->__call($get($taxOriginName), array());
        
        $d->$net = $netValue;
        $d->$tax = round($netValue * ($taxPercent/100), 2);
        $d->$gross = $d->$net + $d->$tax;
    }
    
    /**
     * The above injectors add additional error messages, which are evaluated here
     * @throws ZfExtended_ValidateException
     */
    protected function additionalValidations() {
        if(empty($this->additionalErrors)) {
            return;
        }
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */;;
        foreach ($this->additionalErrors as $id => $error) {
            $this->additionalErrors[$id] = $t->_($error);
        }

        $e = new ZfExtended_ValidateException();
        $e->setErrors($this->additionalErrors);
        throw $e;
    }
}