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

/** #@+
 * @author Marc Mittag
 * @package translate5
 * @version 0.7
 *
 */

/**
 * @todo sicherstellen, dass error-log keine mails verschickt
 * @todo: alle Fehler provozieren und Meldungen prüfen
 */
class Erp_ImportController extends ZfExtended_Controllers_Action {
    
    /**
     * @var erp_Models_Order
     */
    protected $order;

    /**
     * @var erp_Models_Customer
     */
    protected $customer;

    /**
     * @var erp_Models_Vendor
     */
    protected $vendor;
    
    /**
     * @var ZfExtended_Models_User
     */
    protected $user;
    /**
     * @var ZfExtended_Models_User
     */
    protected $systemUser;
    
    /**
     * @var erp_Models_Comment_OrderComment
     */
    protected $comment;
    
    
    /**
     * @var erp_Models_Comment_PurchaseOrderComment
     */
    protected $poComment;
    
    /**
     * @var erp_Models_PurchaseOrder
     */
    protected $purchaseOrder;
    
    /**
     * @var string
     */
    protected $pNumber;
    
    /**
     * @var string
     */
    protected $poNumber;
    
    
    
    /**
     * @var array
     */
    protected $monthMap = array(
        'Januar'=> 1,
        'Februar'=> 2,
        'März'=> 3,
        'April'=> 4,
        'Mai'=> 5,
        'Juni'=> 6,
        'Juli'=> 7,
        'August'=> 8,
        'September'=> 9,
        'Oktober'=> 10,
        'November'=> 11,
        'Dezember'=> 12
    );
    
    /**
     * @var array
     */
    protected $statusMap = array(
        'Proforma'=> 'proforma',
        'Tender'=> 'proforma',//lt. Andreas soll der auch auf Proforma stehen
        'angeboten'=> 'offered',
        'abgelehnt'=> 'declined',
        'beauftragt'=> 'ordered',
        'storno'=> 'cancelled',
        'billed'=> 'billed',//billed existiert nicht und wird vom Importer anhand status beauftrags und gesetztem Rechnungsdatum gesetzt
        'bezahlt'=> 'paid'
    );
    
    public function init() {
        parent::init();
        $this->order = ZfExtended_Factory::get('erp_Models_Order');
        $this->customer = ZfExtended_Factory::get('erp_Models_Customer');
        $this->vendor = ZfExtended_Factory::get('erp_Models_Vendor');
        $this->user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $this->comment = ZfExtended_Factory::get('erp_Models_Comment_OrderComment');
        $this->poComment = ZfExtended_Factory::get('erp_Models_Comment_PurchaseOrderComment');
        $this->purchaseOrder = ZfExtended_Factory::get('erp_Models_PurchaseOrder');
        $this->systemUser = $this->getSystemUser();
    }
    
    protected function log(string $message) {
        file_put_contents(APPLICATION_PATH.'/../data/import.log', date('Y-m-d H:i:s').': '.$message."\r\n", FILE_APPEND);
    }

    public function indexAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        require_once APPLICATION_PATH . '/../library/querypath/src/qp.php';
        
        $data = file_get_contents(APPLICATION_PATH.'/../data/import.xml');

        $qp = qp($data, ':root>projekt',array('encoding'=>'UTF-8','use_parser'=>'xml'));
        
        $this->log('Achtung: Allgemeiner Hinweis: Projektkommentare und POs werden nur für Projekte evaluiert, die bereits erfolgreich importiert wurden. Das heißt, erst wenn die Projekte beim Import keine Fehler mehr erzeugen, lässt sich sagen, dass auch bei POs oder Projektkommentaren keine Fehler mehr vorliegen. Ähnliches gilt bei den Rechnungen: Wenn doppelte Rechnungen bei einem Projekt vorliegen, wird das Projekt nicht für den Import evaluiert sondern gleich übersprungen.');
        foreach ($qp as $p) {
            $continue = false;
            $this->order->init();
            if(!$this->checkXmlAttrExists('nummer',$p)){
                continue;
            }
            $this->pNumber = $p->attr('nummer');
            $r = $p->find('rechnung');
            $rCount = count($r);
            if($rCount>1){
                $this->log('Bei Projekt '.$this->pNumber.' war mehr als eine Rechnung vorhanden. Projekt übersprungen.');
                continue;
            }
            if($rCount === 1){
                if(!$this->handleInvoicePreProject($r)){//muss vor handleProject stehen, damit Rechnungsdatumm befüllt
                    $continue = true;
                }
            }
            if(!$this->handleProject($p)){//muss vor handleProject stehen, damit Rechnungsdatumm befüllt
                $continue = true;
            }
            if($rCount === 1){
                if(!$this->handleInvoicePostProject($r)){//muss vor handleProject stehen, damit Rechnungsdatumm befüllt
                    $continue = true;
                }
            }
            if($continue){
                continue;
            }
            $this->order->save();
            
            $this->handleOrderComments($p);

            $this->handlePOs($p);
        }
    }
    
    protected function handleOrderComments(\QueryPath\DOMQuery $p) {
        $k = $p->find('projekt > kommentar');
        $kCount = count($k);
        if($kCount>1){
            $this->log('Bei Projekt '.$this->pNumber.' war mehr als ein Auftrags-Kommentar vorhanden. Kommentar übersprungen.');
            return;
        }
        if($kCount === 1){
            $this->handleComment('comment','order',$k);
        }
        $kr = $p->find('projekt > rechnung > kommentar');
        $krCount = count($kr);
        if($krCount>1){
            $this->log('Bei Rechnung zum Projekt'.$this->pNumber.' war mehr als ein Kommentar vorhanden. Kommentar übersprungen.');
            return;
        }
        if($krCount === 1){
            $this->handleComment('comment','order',$kr);
        }
    }
    
    protected function handlePOs(\QueryPath\DOMQuery $p) {
        $pos = $p->find('po');
        foreach ($pos as $po) {
            /* @var $po \QueryPath\DOMQuery  */
            $continue = false;
            if(!$this->checkPoDateConsistency($po)){
                $continue = true;
            }
            $this->purchaseOrder->init();
            $this->purchaseOrder->setOrderId($this->order->getId());
            $this->purchaseOrder->calculateNumber();
            if(!$this->setEntityAttr('purchaseOrder',$po, 'erstellungsdatum', 'creationDate','date')) {
                $continue = true;
            }
            $this->purchaseOrder->setCustomerName($this->order->getCustomerName());
            if(!$this->setPmBasedValues('purchaseOrder', $po)){
                $continue = true;
            }
            if(!$this->setVendorValues($po)){
                $continue = true;
            }
            if(!$this->setEntityAttr('purchaseOrder',$po, 'netto', 'netValue','currency')) {
                $continue = true;
            }
            if(!$this->setEntityAttr('purchaseOrder',$po, 'brutto', 'grossValue','currency')) {
                $continue = true;
            }
            $this->purchaseOrder->setTaxValue($this->purchaseOrder->getGrossValue()-$this->purchaseOrder->getNetValue());
            if(!$this->setEntityAttr('purchaseOrder',$po, 'UStSatz', 'taxPercent','percent')) {
                $continue = true;
            }
            $w = $po->attr('vendorwaehrung');
            if(!in_array(strtoupper($w), array('EUR','€'))){
                if(!$this->setOriginalCurrencyValues($po)){
                    $continue = true;
                }
            }
            $this->setPoStatus($po);
            if(!empty($po->attr('datumrechnung'))){
                if(!$this->setEntityAttr('purchaseOrder',$po, 'datumrechnung', 'billDate','date')) {
                    $continue = true;
                }
            }
            if(!empty($po->attr('eingangsdatumrechnung'))){
                if(!$this->setEntityAttr('purchaseOrder',$po, 'eingangsdatumrechnung', 'billReceivedDate','date')) {
                    $continue = true;
                }
            }
            if(!empty($po->attr('datumbezahlt'))){
                if(!$this->setEntityAttr('purchaseOrder',$po, 'datumbezahlt', 'paidDate','date')) {
                    $continue = true;
                }
            }
            if(!$this->setEntityAttr('purchaseOrder',$po, 'zahlungsfrist', 'paymentTerm','zahlungsfrist')) {
                $continue = true;
            }
            if(!$this->setPoCheckerValues($po)){
                $continue = true;
            }
            
            $this->purchaseOrder->setEditorId($this->order->getEditorId());
            $this->purchaseOrder->setEditorName($this->order->getEditorName());
            $this->purchaseOrder->setModifiedDate($this->order->getModifiedDate());

            if($continue){
                continue;
            }
            $this->purchaseOrder->save();
            
            $k = $po->find('po > kommentar');

            $kCount = count($k);

            if($kCount>1){
                $this->log('Bei PO zum Projekt'.$this->pNumber.' war mehr als ein Kommentar vorhanden. Kommentar übersprungen.');
            }
            elseif ($kCount ===1) {
                $this->handleComment('poComment','purchaseOrder',$k);
            }
        }
    }
    
    protected function handleComment(string $entity,string $foreignEntity, \QueryPath\DOMQuery $k) {
        $kommentar = strip_tags($k->text());
        if(empty($kommentar)||  preg_match('"^\s+$"', $kommentar)){
            $this->log ('Bei Projekt '.$this->pNumber.' war ein Kommentartag vorhanden, aber leer. Projekt trotzdem importiert, aber kein Kommentar angelegt.');
            return true;
        }
        $this->$entity->init();
        $this->$entity->setUserId($this->systemUser->getId());
        ob_start();
        var_dump($this->pNumber,$kommentar,$foreignEntity,$this->$foreignEntity->getId());
        error_log(ob_get_clean());       
        $idSetter = 'set'.ucfirst($foreignEntity).'Id';
        $this->$entity->$idSetter($this->$foreignEntity->getId());
        $this->$entity->setComment($kommentar);
        //by pass validation for values setted by system
        $now = date('Y-m-d H:i:s');
        $this->$entity->setModified($now);
        $this->$entity->setCreated($now);
        $this->$entity->setUserName($this->systemUser->getUserName());
        $this->$entity->validate();

        $this->$entity->save();
        $updateMethod = 'update'.ucfirst($foreignEntity);
        $this->$entity->$updateMethod((int)$this->$foreignEntity->getId());
        return true;
    }
    
    
    protected function handleInvoicePreProject(\QueryPath\DOMQuery $r) {
        $return = true;
        if(!$this->setEntityAttr('order',$r, 'datumrechnung', 'billDate','date')) {
            $return = false;
        }
        if(!$this->setEntityAttr('order',$r, 'name', 'customerOrder', 'string')){
            $return = false; //"Rechnungsname" enthält die bisherige Rechnungsnummer. Nach Rücksprache mit Katrin sollten wir die als "Bestellnr. Kunde" importieren.
        } 
        if(!$this->setEntityAttr('order',$r, 'rechnungnetto', 'billNetValue','currency')){
            $return = false;  
        }
        if(!$this->setEntityAttr('order',$r, 'rechnungbrutto', 'billGrossValue','currency')){
            $return = false;  
        }
        if(!$this->setEntityAttr('order',$r, 'UStSatz', 'taxPercent','percent')) {
            $return = false;  
        }
        $this->order->setBillTaxValue($this->order->getBillGrossValue()-$this->order->getBillNetValue());
        return $return;
        //endgültige Marge bleibt auf Anweisung von Andreas leer
    }
    
    protected function handleInvoicePostProject(\QueryPath\DOMQuery $r) {
        $return = true;
        if($this->order->getState() === 'paid' && empty($r->attr('datumbezahlt'))){
            ##auskommentiert 
$this->log ('Bei Projekt '.$this->pNumber.' war das Feld "datumbezahlt" nicht befüllt, obwohl der Status "bezahlt" ist. Projekt übersprungen.');
            return false;
        }
        if(!$this->setEntityAttr('order',$r, 'datumbezahlt', 'paidDate','date')) {
            $return = false;
        }
        return $return;
    }
    
    protected function handleProject(\QueryPath\DOMQuery $p) {
        $return = true;
        $this->order->setDebitNumber($this->pNumber);
        
        if(!$this->setEntityAttr('order',$p, 'status', 'state','status')) {
            $return = false;
        }
        if(!$this->setEntityAttr('order',$p, 'name', 'name','string')) {
            $return = false;
        }
        if(!$this->setEntityAttr('order',$p, 'datumangebot', 'offerDate','date')) {
            $return = false;
        }
        $this->order->setModifiedDate(date("Y-m-d H:i:s"));
        
        if(!$this->setConversionDates($p)) {
            $return = false;
        }
        //lt. Andreas wird keyaccount nicht gesetzt - wird später per Hand gemacht
        
        if(!$this->setEntityAttr('order',$p, 'marge', 'offerMargin','percent')) {
            $return = false;
        }
        $this->setEditor();
        
        if(!$this->setPmBasedValues('order',$p)) {
            $return = false;
        }
        
        if(!$this->setReleaseDate($p)){
            return false;
        }
        
        if(!$this->setEntityAttr('order',$p, 'wertangebot', 'offerNetValue','currency')) {
            $return = false;
        }
        if(!$this->setCustomerValues($p)) {
            return false;
        }
        $taxMultiplier = ((float)$this->customer->getTaxPercent()/100)+1;
        $this->order->setOfferGrossValue(round($this->order->getOfferNetValue()*$taxMultiplier),2);
        $this->order->setOfferTaxValue($this->order->getOfferGrossValue()-$this->order->getOfferNetValue());

        return $return;
    }
    
    protected function setConversionDates(\QueryPath\DOMQuery $p) {
        if(($this->order->getState() === 'paid' || $this->order->getState() === 'billed')){
            if(empty($p->attr('umsatzjahr'))){
                $p->attr('umsatzjahr','2015');//according to Andrea Kunze set umsatzjahr to 2015 if empty
            }
            if(empty($p->attr('umsatzmonat'))){
                $this->log ('Bei Projekt '.$this->pNumber.' war das Feld "umsatzmonat" nicht befüllt, obwohl der Status "bezahlt" oder "berechnet" ist. Projekt übersprungen.');
                return false;
            }
            if(!$this->setEntityAttr('order',$p, 'umsatzmonat', 'conversionMonth','month')){
                return false;
            }
            if(!$this->setEntityAttr('order',$p, 'umsatzjahr', 'conversionYear','year4')) {
                return false;
            }
        }
        return true;
    }
    protected function setPoCheckerValues(\QueryPath\DOMQuery $po) {
        if(!$this->checkXmlAttrExists('geprueft', $po) || ($po->attr('geprueft') !== 'Ja'&&$po->attr('geprueft') !== 'Nein')){
            $this->log ('Bei Projekt '.$this->pNumber.' war bei einem PO das Feld "geprueft" leer oder nicht Ja oder Nein. PO übersprungen.');
            return false;
        }
        if($po->attr('geprueft')==='Ja'){
            if(empty($po->attr('geprueftvon'))){
                $this->log ('Bei Projekt '.$this->pNumber.' war bei einem PO das Feld "geprueft"="Ja", aber das Feld "geprueftvon" leer. Das ist inkonsistent. PO übersprungen.');
                return false;
            }
            if(!$this->setPmBasedValues('purchaseOrder', $po,'geprueftvon','checker')){
                return false;
            }
            $this->purchaseOrder->setChecked(true);
        }
        return true;
    }
    protected function checkPoDateConsistency(\QueryPath\DOMQuery $po) {
        if(empty($po->attr('datumrechnung')) && (!empty($po->attr('eingangsdatumrechnung')) || !empty($po->attr('datumbezahlt')))){
            $this->log ('Bei Projekt '.$this->pNumber.' war bei einem PO das Feld "datumrechnung" leer, aber das Feld "eingangsdatumrechnung" oder das Feld "datumbezahlt" befüllt. Das ist inkonsistent. PO übersprungen.');
            return false;
        }
        if(empty($po->attr('eingangsdatumrechnung')) && !empty($po->attr('datumbezahlt'))){
            $this->log ('Bei Projekt '.$this->pNumber.' war bei einem PO das Feld "datumrechnung" leer, aber das Feld "datumbezahlt" befüllt. Das ist inkonsistent. PO übersprungen.');
            return false;
        }
        return true;
    }
    
    protected function setPoStatus(\QueryPath\DOMQuery $po) {
        if($po->attr('storno')==='true'){
            $this->purchaseOrder->setState('cancelled');
            return true;
        }
        if(!empty($po->attr('datumbezahlt'))){
            $this->purchaseOrder->setState('paid');
            return true;
        }
        if(empty($po->attr('datumbezahlt')) && !empty($po->attr('datumrechnung'))){
            $this->purchaseOrder->setState('billed');
            return true;
        }
        $this->purchaseOrder->setState('created');
        return true;
    }
    
    protected function setOriginalCurrencyValues(\QueryPath\DOMQuery $p) {
        $c = $p->attr('vendorfremdbrutto');
        if(empty($c)){
            $this->log ('Bei Projekt '.$this->pNumber.' war bei einem PO das Feld "vendorfremdbrutto" nicht befüllt, obwohl der eine Fremdwährung gesetzt war. PO übersprungen.');
            return false;
        }
        $chars = str_split($c);
        $cNew = '';
        foreach($chars as $char){
            if(preg_match('"\d|,|\."', $char)){
                $cNew .= $char;
            }
        }
        if(!preg_match('"^\d*\.?\d+,?\d?\d?$"',$cNew)){
            $this->log ('Bei Projekt '.$this->pNumber.' war bei einem PO das Feld "vendorfremdbrutto" nicht mit einem sinnvollen Wert befüllt, obwohl der eine Fremdwährung gesetzt war. PO übersprungen.');
            return false;
        }
        $p->attr('vendorfremdbrutto',$cNew.' €');//fake an Euro currency to make be able to use the setEntityAttr method
        if(!$this->setEntityAttr('purchaseOrder',$p, 'vendorfremdbrutto', 'originalGrossValue','currency')) {
            return false;
        }
        $tax = ((float)$this->purchaseOrder->getTaxPercent()/100)+1;
        $this->purchaseOrder->setOriginalNetValue(round($this->purchaseOrder->getOriginalGrossValue()/$tax,2));
        $this->purchaseOrder->setOriginalTaxValue($this->purchaseOrder->getOriginalGrossValue()-$this->purchaseOrder->getOriginalNetValue());
        return true;
    }
    
    protected function setReleaseDate($p) {
        if($this->order->getState() !== 'offered' && $this->order->getState() !== 'Proforma' && $this->order->getState() !== 'declined'){
            if(empty($p->attr('datumfreigabe'))){
                ##auskommentiert 
$this->log ('Bei Projekt '.$this->pNumber.' war das Feld "datumfreigabe" nicht befüllt, obwohl der Status nicht "angeboten" und nicht "Proforma" und nicht "abgelehnt" ist. Projekt übersprungen.');
                return false;
            }
            if(!$this->setEntityAttr('order',$p, 'datumfreigabe', 'releaseDate','date')) {
                return false;
            }
        }
        return true;
    }
    
    protected function setVendorValues($p) {
        if(!$this->checkXmlAttrExists('vendor',$p)){
            return false;
        }
        $vName = explode(' ',$p->attr('vendor'));
        $s = $this->vendor->db->select()->from($this->vendor->dbConfig['dbtable']);
        $firstname = $vName[0];
        unset($vName[0]);
        $surname = implode(' ', $vName);
        $s->where('Vorname = ?', $firstname);
        $s->where('Nachname = ?', $surname);
        $row = $this->vendor->db->fetchRow($s);
        if(!$row){
            $vName = explode(', ',$p->attr('vendor'));
            $s = $this->vendor->db->select()->from($this->vendor->dbConfig['dbtable']);
            $surname = $vName[0];
            unset($vName[0]);
            $firstname = implode(', ', $vName);
            $s->where('Vorname = ?', $firstname);
            $s->where('Nachname = ?', $surname);
            $row = $this->vendor->db->fetchRow($s);
            if(!$row){
                $s = $this->vendor->db->select()->from($this->vendor->dbConfig['dbtable']);
                $s->where('Firma = ?', $p->attr('vendor'));
                $row = $this->vendor->db->fetchRow($s);
                if(!$row){
                    $this->log ('Bei Projekt '.$this->pNumber.' existierte in einem seiner POs der Vendor '.$p->attr('vendor').' nicht in der Datenbank. PO übersprungen.');
                    return false;
                }
            }
        }
        $this->purchaseOrder->setVendorId($row['Id']);
        $this->purchaseOrder->setVendorNumber($row['Lieferantennummer']);
        $this->purchaseOrder->setVendorName($p->attr('vendor'));
        return true;
    }
    
    
    protected function setPmBasedValues(string $entityType, $p, $xmlAttr = 'pm', $dbField = 'pm') {
        if(!$this->checkXmlAttrExists($xmlAttr,$p)){
            return false;
        }
        $pmName = explode(' ',$p->attr($xmlAttr));
        
        $s = $this->user->db->select();
        
        if(count($pmName)!==2){
            $this->log ('Bei Projekt '.$this->pNumber.' oder in einem seiner POs konnte bei PM '.$p->attr($xmlAttr).' nicht Vorname und Nachname ermittelt werden. Projekt übersprungen.');
            return false;
        }
        $s->where('firstName = ?', $pmName[0]);
        $s->where('surName = ?', $pmName[1]);
        try {
            $row = $this->user->loadRowBySelect($s);
        } catch (ZfExtended_Models_Entity_NotFoundException $exc) {
            $this->log ('Bei Projekt '.$this->pNumber.' oder in einem seiner POs existierte der PM '.$p->attr($xmlAttr).' nicht in der Datenbank. Projekt übersprungen.');
            return false;
        }

        if(is_null($row)){
            $this->log ('Bei Projekt '.$this->pNumber.' oder in einem seiner POs existierte der PM '.$p->attr($xmlAttr).' nicht in der Datenbank. Projekt übersprungen.');
            return false;
        }
        /* @var $user ZfExtended_Models_User */
        $setterId = 'set'.$dbField.'Id';
        $setterName = 'set'.$dbField.'Name';
        $this->$entityType->$setterId($this->user->getId());
        $this->$entityType->$setterName($p->attr($xmlAttr));
        return true;
    }
    
    protected function getSystemUser() {
        $s = $this->user->db->select();
        $s->where('login = ?', 'system');
        try {
            $row = $this->user->loadRowBySelect($s);
        } catch (ZfExtended_Models_Entity_NotFoundException $exc) {
            $this->log ('Bei Projekt '.$this->pNumber.' existierte der Systembenutzer nicht in der Datenbank. Projekt übersprungen.');
            return false;
        }
        if(is_null($row)){
            $this->log ('Bei Projekt '.$this->pNumber.' existierte der Systembenutzer nicht in der Datenbank. Projekt übersprungen.');
            return false;
        }
        return clone $this->user;
    }
    protected function setEditor() {
        /* @var $user ZfExtended_Models_User */
        $this->order->setEditorId($this->systemUser->getId());
        $this->order->setEditorName($this->systemUser->getFirstName().' '.$this->systemUser->getSurName());
        return true;
    }
    
    protected function setCustomerValues($p) {
        if(!$this->checkXmlAttrExists('kunde',$p)){
            return false;
        }
        $customerName = $p->attr('kunde');
        $s = $this->customer->db->select();
        $s->where('name = ?', $customerName);
        try {
            $row = $this->customer->loadRowBySelect($s);
        } catch (ZfExtended_Models_Entity_NotFoundException $exc) {
            ##auskommentiert 
$this->log ('Bei Projekt '.$this->pNumber.' existierte der Kunde '.$customerName.' nicht in der Datenbank. Projekt übersprungen.');
            return false;
        }

        if(is_null($row)){
            ##auskommentiert 
$this->log ('Bei Projekt '.$this->pNumber.' existierte der Kunde '.$customerName.' nicht in der Datenbank. Projekt übersprungen.');
            return false;
        }
        
        $this->order->setCustomerId($this->customer->getId());
        $this->order->setCustomerName($this->customer->getName());
        $this->order->setCustomerNumber($this->customer->getNumber());
        return true;
    }
    
    protected function checkXmlAttrExists($attrXmlName,\QueryPath\DOMQuery $p) {
        if(!$p->hasAttr($attrXmlName) || empty($p->attr($attrXmlName))){
            //if($attrXmlName !== 'marge'){##auskommentiert für marge
                $this->log('Bei Projekt '.$this->pNumber.' oder einem seiner POs war das Attribut '.$attrXmlName.' nicht gesetzt. Projekt oder PO übersprungen.');
            //}
            return false;
        }
        return true;
    }


    protected function setEntityAttr(string $entityType, \QueryPath\DOMQuery $p, string $attrXmlName, string $attrDbField, string $type) {
        if(!$this->checkXmlAttrExists($attrXmlName,$p)){
            return false;
        }
        $setter = 'set'.ucfirst($attrDbField);
        $value = $p->attr($attrXmlName);
        switch ($type) {
            case 'date':
                if(!preg_match('"^\d\d\.\d\d.\d\d\d\d$"', $value)){
                    $this->log ('Bei Projekt '.$this->pNumber.' war der Wert '.$attrXmlName.' kein gültiges Datum. Projekt übersprungen.');
                    return false;
                }
                $date = DateTime::createFromFormat('d.m.Y', $value);
                $value = $date->format('Y-m-d');
                break;
            case 'percent':
                $value = str_replace(',', '.', $value);
                if(!preg_match('"^\d+\.?\d*%$"', $value)){
                    $this->log ('Bei Projekt '.$this->pNumber.' war der Wert '.$attrXmlName.' keine gültige Prozentzahl. Projekt übersprungen.');
                    return false;
                }
                $value = (float)str_replace('%', '', $value);
                break;
            case 'string':
                break;
            case 'year4':
                if(!preg_match('"^\d\d\d\d$"', $value)){
                    $this->log ('Bei Projekt '.$this->pNumber.' war bei '.$attrXmlName.' kein vierstelliges Jahr hinterlegt. Projekt übersprungen.');
                    return false;
                }
                $value = (int)$value;
                break;
            case 'zahlungsfrist':
                if(!in_array($value, array('30','45','15'))){
                    $this->log ('Bei Projekt '.$this->pNumber.' war bei '.$attrXmlName.' kein erlaubter Wert (15,30 oder 45) hinterlegt. PO übersprungen.');
                    return false;
                }
                $value = (int)$value;
                break;
            case 'month':
                if(!array_key_exists($value, $this->monthMap)){
                    $this->log ('Bei Projekt '.$this->pNumber.' war der Monat '.$value.' gesetzt, der nicht existiert. Projekt übersprungen.');
                    return false;
                }
                $value = $this->monthMap[$value];
                break;
            case 'status':
                if(!array_key_exists($value, $this->statusMap)){
                    $this->log ('Bei Projekt '.$this->pNumber.' war der Status '.$value.' gesetzt, der nicht existiert. Projekt übersprungen.');
                    return false;
                }
                $value = $this->statusMap[$value];
                if($value === 'ordered' && !empty($this->order->getPaidDate())){//@todo prüfen, ob das klappt
                    $value = 'billed';
                }
                break;
            case 'currency':
                if(!preg_match('"^\d*\.?\d+,\d\d €$"', $value)){
                    $this->log ('Bei Projekt '.$this->pNumber.' war bei '.$attrXmlName.' kein korrekter Preis hinterlegt. Projekt übersprungen.');
                    return false;
                }
                $value = str_replace(' €', '', $value);
                $value = str_replace('.', '', $value);
                $value = (float)str_replace(',', '.', $value);
                $value = round($value,2);
                break;
            default:
                $this->log('Bei Projekt '.$this->pNumber.' war in der Methode setEntityAttr der string-type nicht gesetzt. Projekt übersprungen.');
                return false;
        }
        $this->$entityType->$setter($value);
        return true;
    }
    
}