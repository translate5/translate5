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

class erp_Models_VendorNotification {

    /**
     * @var ZfExtended_Mail
     */
    private $mailer;

    /***
     * Vendor object
     * @var stdClass
     */
    private $vendor;

    /***
     * Is the send mail called from update po
     * @var boolean
     */
    private $isUpdate;
    
    /***
     * Pdf file name
     * 
     * @var string
     */
    private $fileName;

    /***
     * E-mail address to the recipient
     * 
     * @var string
     */
    private $toMail;
    
    /***
     * Name address to the recipient
     * @var name1
     */
    private $toName;

    /***
     * The sender of the mail
     * @var string
     */
    private $fromMail;
    
    /***
     * Sender name
     * @var string
     */
    private $fromName;
    
    /***
     * Mail title
     * 
     * @var string
     */
    private $mailTitle;

    /***
    * The name of the attachment
    * @var string
    */
    private $attachmentName;
    
    /**
     * creates the Notification
     * @param array $parameters
     */
    public function createNotification(array $parameters,$isGermanTemplate) {
        $this->mailer = ZfExtended_Factory::get('ZfExtended_TemplateBasedMail');
        $this->mailer->setParameters($parameters);
        $this->mailer->setTemplate($isGermanTemplate ? 'vendorNotificationDe.phtml': 'vendorNotification.phtml');
        $this->mailer->setContentByTemplate();

        $this->initMail();
        $this->setAttachment();
    }

    /***
     * Init the sender of the mail,titile and the sender name
     */
    private function initMail(){
        $this->mailer->setFrom($this->getFromMail(), $this->getFromName());
        $this->mailer->setSubject($this->getMailTitle());
    }

    /***
     * Set the file attachment (pdf)
     */
    private function setAttachment(){
        $content = file_get_contents($this->getFileName());
        $attachment=[];
        $attachment['body']=$content;
        $attachment['mimeType']= 'application/pdf';
        $attachment['disposition']=Zend_Mime::DISPOSITION_ATTACHMENT;
        $attachment['encoding']=Zend_Mime::ENCODING_BASE64;
        $attachment['filename']= $this->getAttachmentName();

        $this->mailer->setAttachment([$attachment]);   
    }
    /**
     * send the latest created notification to the set vendor
     */
    public function notify() {
        $this->mailer->send($this->getToMail(),$this->getToName());
    }

    /***
     * Is the send mail called from update po
     * 
     * @param boolean $isUpdate
     */
    public function setIsUpdate($isUpdate){
        $this->isUpdate = $isUpdate;
    }
    
    /***
     * Set the name of the file
     * 
     * @param string $filename
     */
    public function setFileName($filename){
        $this->fileName = $filename;
    }
    
    /***
     * Get the filename
     * 
     * @return string
     */
    public function getFileName(){
        return $this->fileName;
    }
    
    /**
     * Set the vendor
     * 
     * @param stdClass $vendor
     */
    public function setVendor($vendor){
        $this->vendor=$vendor;
    }
    /***
     * Get the vendors
     * 
     * @return stdClass
     */
    public function getVendor(){
        return $this->vendor;
    }
    
    /***
     * Set the receiver of the mail
     * 
     * @param string $toMail
     */
    public function setToMail($toMail){
        $this->toMail=$toMail;
    }
    
    /***
     * Get receiver of the mail
     * 
     * @return string
     */
    public function getToMail(){
        return $this->toMail;
    }
    
    /***
     * Set mail receiver name
     * 
     * @param string $toName
     */
    public function setToName($toName){
        $this->toName=$toName;
    }
    
    /***
     * Get mail receiver name
     * 
     * @return string
     */
    public function getToName(){
        return $this->toName;
    }
    
    /***
     * Set the sender of the mail
     * 
     * @param string $toMail
     */
    public function setFromMail($fromMail){
        $this->fromMail=$fromMail;
    }
    
    /***
     * Get the sender of the mail
     * 
     * @return string
     */
    public function getFromMail(){
        return $this->fromMail;
    }
    
    /***
     * Set mail sender name
     * 
     * @param string $toName
     */
    public function setFromName($fromName){
        $this->fromName=$fromName;
    }
    
    /***
     * Get mail sender name
     * 
     * @return string
     */
    public function getFromName(){
        return $this->fromName;
    }
    
    /***
     * Set mail title
     * @param string $mailTitle
     */
    public function setMailTitle($mailTitle){
        $this->mailTitle=$mailTitle;
    }
    
    /***
     * Get the mail title
     */
    public function getMailTitle(){
        return $this->mailTitle;
    }
    
    /***
    * Set the name of the attachment
    */
    public function setAttachmentName($attachmentname){
        $this->attachmentName = $attachmentname;
    }

    /***
    * Get the name of the attachment
    */
    public function getAttachmentName(){
        return $this->attachmentName;
    }
}
