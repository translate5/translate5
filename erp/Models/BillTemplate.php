<?php
/*
 START LICENSE AND COPYRIGHT

This file is part of ZfExtended library

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


class erp_Models_BillTemplate  {

    /**
     * @var ZfExtended_Export_PdfExport
     */
    private $pdf;
    
    
    /***
     * Zfextended configuration
     * 
     * @var object
     */
    private $config;
    
    /**
     * This data is displayes in the table
     *  
     * @var stdClass
     */
    private $poData;

    /***
     * Project information for this Purchase Order
     * 
     * @var erp_Models_Order
     */
    private $project;

    /***
     * Vendor information for this Purchase Order
     * 
     * @var stdClass
     */
    private $vendor;

    /***
     * Fields which value is changed on po-edit
     * 
     * @var array
     */
    private $dirtyFields;
    
    /***
     * translations used key/values used in po pdf layout
     * 
     * @var array
     */
    protected $translationKeys = array();

    /***
     * Vendor salutation
     * 
     * @var array
     */
    protected $salutationKeys = array();
    
    /***
     * Starting y point where the next item should be render
     * 
     * @var int
     */
    private $yPointerLocation;

    /***
     * The name of the generated file
     * 
     * @var string
     */
    private $outputFileName;

    /***
     * If true the file is only for showing
     * 
     * @var bool
     */
    private $isPreview=false;
    
    /***
     * The name of the file
     * 
     * @var string
     */
    private $fileName;

    /**
     * Shift dirty field icon by x and y.
     * 
     * @var array
     */
    protected $dirtyFieldsIconShift;
    
    /***
     * Base currency
     * 
     * @var string
     */
    private $baseCurrency='EUR';
    
    
    /***
     * is the currently used template German
     * 
     * @var string
     */
    protected $isGermanTemplate=false;
    
    public function __construct(){
        
        //NOTE: In translate5 this directory is removed while build (to save disk space). It has to be included via dependencies if we want to use it there!
        define("FPDF_FONTPATH",APPLICATION_PATH.'/../library/ZfExtended/ThirdParty/font');

        $this->pdf=ZfExtended_Factory::get('ZfExtended_Export_PdfExport');
        //get company data from config
        $this->config=Zend_Registry::get('config');
    }
    /**
    *   Loads the template file so it can be ready for editing
    */
    public function init(){
        // add a page
        $this->pdf->AddPage();
        //load the fonts
        $this->pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
        $this->pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
        //$this->pdf->AddFont('DejaVu','B','helveticaSansCondensed-Bold.ttf',true);
    }
    
    /***
     *  Display (render) the data to the pdf file
     */
    public function render(){
        $this->renderLogo();
        $this->renderVendorInfo();
        $this->renderCompanyInfo();
        $this->renderTitle();
        
        $this->yPointerLocation=110;
        $this->renderPoInfo();
        
        $this->yPointerLocation=125;
        $this->renderPmAndCurrency();

        $this->yPointerLocation=135;
        $this->renderTable();

        $this->renderFieldsBelowTable();

        $this->renderInfoText();

        $this->renderFooter();
    }

    /***
     * Render the logo
     */
    public function renderLogo(){
        $this->pdf->Image($this->config->runtimeOptions->server->pathToIMAGES.'/transmission_logo_web.gif',130,10);
    }

    /***
     * Display(render) the table where the prices,amounts etc. are listed
     */
    public function renderTable(){
        //the y location of the table
        $this->pdf->SetY($this->yPointerLocation);
        
        //field widths
        $w = array(90,20,20,30,30);

        //Header Colors, line width and bold font
        $this->pdf->SetFillColor(161,29,1);

        $this->pdf->SetTextColor(255);
        $this->pdf->SetDrawColor(128,0,0);
        $this->pdf->SetLineWidth(.1);
        $this->pdf->SetFont('DejaVu','B',9);

        //table header text
        $header = array(
                        $this->getTranslation('tblDescription'),
                        $this->getTranslation('tblAmount'),
                        $this->getTranslation('tblUnit'),
                        $this->getTranslation('tblPrice'),
                        $this->getTranslation('tblSubTotal'));
        for($i=0;$i<count($header);$i++){
            $this->pdf->Cell($w[$i],7,$header[$i],1,0,'C',true);
        }
        $this->pdf->Ln();

        $this->pdf->SetFillColor(237,237,237);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont('DejaVu','',9);
        
        $data = $this->poData;
        
        $fill=false;
        //display the row only if there is valid data for it
        if(isset($data->wordsCount) && $data->wordsCount>0){

            $this->addIconIfDirty(($this->pdf->getX()+$w[0])-3, $this->pdf->getY()+3,'wordsDescription');
            
            $this->pdf->Cell($w[0],6,$data->wordsDescription,'LR',0,'L',$fill);

            $this->addIconIfDirty(($this->pdf->getX()+$w[1])-3, $this->pdf->getY()+3,'wordsCount');
            $this->pdf->Cell($w[1],6,number_format($data->wordsCount,2,',','.'),'LR',0,'C',$fill);
            
            $this->pdf->Cell($w[2],6,'WWC','LR',0,'C',$fill);
            
            $this->addIconIfDirty(($this->pdf->getX()+3) , $this->pdf->getY()+3,'perWordPrice');
            $this->pdf->Cell($w[3],6,$this->formatAsCurrency($data->perWordPrice,true,4),'LR',0,'R',$fill);
            
            $this->pdf->Cell($w[4],6,$this->formatAsCurrency(($data->wordsCount*$data->perWordPrice),true),'LR',0,'R',$fill);
            
            $this->pdf->Ln();
            $this->pdf->Cell(array_sum($w),0,'','B');
            $this->pdf->Ln();
            
            
            //move the y pointer for the height of one cell
            $this->yPointerLocation+=6;
        }

        //display the row only if there is valid data for it
        if(isset($data->hoursCount) && $data->hoursCount>0){
            
            $this->addIconIfDirty(($this->pdf->getX()+$w[0])-3, $this->pdf->getY()+3,'hoursDescription');
            $this->pdf->Cell($w[0],6,$data->hoursDescription,'LR',0,'L',$fill);

            $this->addIconIfDirty(($this->pdf->getX()+$w[1])-3, $this->pdf->getY()+3,'hoursCount');
            $this->pdf->Cell($w[1],6,number_format($data->hoursCount,2,',','.'),'LR',0,'C',$fill);
            
            $this->pdf->Cell($w[2],6,$this->getTranslation('unit'),'LR',0,'C',$fill);
            
            $this->addIconIfDirty(($this->pdf->getX()+3) , $this->pdf->getY()+3,'perHourPrice');
            $this->pdf->Cell($w[3],6,$this->formatAsCurrency($data->perHourPrice,true),'LR',0,'R',$fill);
            
            $this->pdf->Cell($w[4],6,$this->formatAsCurrency(($data->hoursCount*$data->perHourPrice),true),'LR',0,'R',$fill);
            
            $this->pdf->Ln();
            $this->pdf->Cell(array_sum($w),0,'','B');
            $this->pdf->Ln();
            

            //move the y pointer for the height of one cell
            $this->yPointerLocation+=6;
        }

        if(isset($data->additionalCount) && $data->additionalCount>0){
            
            $this->addIconIfDirty(($this->pdf->getX()+$w[0])-3, $this->pdf->getY()+3,'additionalDescription');
            $this->pdf->Cell($w[0],6,$data->additionalDescription,'LR',0,'L',$fill);
            
            $this->addIconIfDirty(($this->pdf->getX()+$w[1])-3, $this->pdf->getY()+3,'additionalCount');
            $this->pdf->Cell($w[1],6,number_format($data->additionalCount,2,',','.'),'LR',0,'C',$fill);
            
            $this->addIconIfDirty(($this->pdf->getX()+$w[2])-3, $this->pdf->getY()+3,'additionalUnit');
            $this->pdf->Cell($w[2],6,$data->additionalUnit,'LR',0,'C',$fill);
            
            $this->addIconIfDirty(($this->pdf->getX()+3) , $this->pdf->getY()+3,'perAdditionalUnitPrice');
            $this->pdf->Cell($w[3],6,$this->formatAsCurrency($data->perAdditionalUnitPrice,true,3),'LR',0,'R',$fill);
            
            $this->addIconIfDirty(($this->pdf->getX()+3) , $this->pdf->getY()+3,'additionalPrice');
            $this->pdf->Cell($w[4],6,$this->formatAsCurrency($data->additionalPrice,true),'LR',0,'R',$fill);
            
            $this->pdf->Ln();
            $this->pdf->Cell(array_sum($w),0,'','B');
            $this->pdf->Ln();


            //move the y pointer for the height of one cell
            $this->yPointerLocation+=6;
        }
        $this->pdf->Cell(array_sum($w),0,'','T');

        $this->yPointerLocation+=6;
        //set the pointer with the new value
        $this->pdf->SetY($this->yPointerLocation);

        $this->pdf->SetTextColor(0);
        $this->pdf->SetDrawColor(255,255,255);
        $this->pdf->SetFont('DejaVu','B',9);
        
        if($data->vendorCurrency=='EUR'){
            $totalSum= $data->netValue;
            $this->addIconIfDirty(($this->pdf->getX()+($w[0]+$w[1]+$w[2]+$w[3])), $this->pdf->getY()+3,'netValue');
        }else{
            $totalSum= $data->originalNetValue;
            $this->addIconIfDirty(($this->pdf->getX()+($w[0]+$w[1]+$w[2]+$w[3])), $this->pdf->getY()+3,'originalNetValue');
        }
        //display totalSum
        $this->pdf->Cell($w[0]+$w[1]+$w[2]+$w[3],10,$this->getTranslation('total'),'LR',0,'R',false);
        $this->pdf->Cell($w[4],10,$this->formatAsCurrency($totalSum,true),'LR',0,'R',false);
        $this->pdf->Ln();
        
        $this->pdf->Cell(array_sum($w),0,'','T');

        $this->yPointerLocation+=20;
    }

    /***
     * Display(render) the vendor information (left top corner) 
     */
    public function renderVendorInfo(){
        $vendorText = $this->vendor->FirstName.' '.$this->vendor->LastName;
        if($this->vendor->IsCompany){
            $vendorText = $this->vendor->Company;
        }
        $this->write(10,55,$vendorText,10,true,'vendorId');
        $this->write(10,60,$this->vendor->Address1,10,true);
        
        //show secound address if exist
        $offset=0;
        if(isset($this->vendor->Address2) && !empty($this->vendor->Address2)){
            $this->write(10,65,$this->vendor->Address2,10,true);
            $offset=5;
        }
        
        //show post code if exist
        $postCode="";
        if($this->vendor->PostCode || !empty($this->vendor->PostCode)){
            $postCode=$this->vendor->PostCode." ";
        }
        $this->write(10,(65+$offset),$postCode.$this->vendor->City,10,true);
        $this->write(10,(70+$offset), $this->vendor->Country,10,true);
    }

    /***
     *  Display(render) the company information (right top corner below logo)
     */
    public function renderCompanyInfo(){
        $companyData = $this->config->runtimeOptions->company;
        
        $this->write(130,55,$companyData->fullName,10,true);
        $this->write(130,60,$companyData->address,8);
        $this->write(130,65,$companyData->postCode.' '.$companyData->city,8);
        $this->write(130,70,'Tel. '.$companyData->telephone,8);
        $this->write(130,75,'Fax. '.$companyData->fax,8);
    }

    /***
     * Display(render) the document title
     */
    public function renderTitle(){
        //reset the pointer so we can render in the center of the screan
        $this->pdf->SetXY(0,0);

        $this->pdf->SetFont('DejaVu','B',18);
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(0,190,$this->getTranslation('title'),0,1,'C',false,'');
    }

    /***
     * Display(render) the document (PurchaseOrder) information (over the table, left)
     */
    public function renderPoInfo(){
        //datum label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('creationDate'),10,true);
        //datum field
        $this->write(40,$this->yPointerLocation,date("d.m.Y", strtotime($this->poData->creationDate)),8);

        $this->yPointerLocation+=5;

        //Projektnummer label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('orderId'),10,true);
        //Projektnummer field
        $this->write(40,$this->yPointerLocation,$this->poData->orderId,8);

        $this->yPointerLocation+=5;

        //Bestellnummer label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('poNumber'),10,true);
        //Bestellnummer field
        $this->write(40,$this->yPointerLocation,(isset($this->poData->number) && $this->poData->number > 0) ?($this->poData->orderId.'-'.$this->poData->number): 'n/a',8);

        $this->yPointerLocation+=5;

        //Projektname label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('projectName'),10,true);
        
        //Projektname field
        //if the project name contains more than 70 characters, use multiline cell so the text appears in more than 1 line
        if(strlen($this->project->getName())<=70){
            $this->write(40,$this->yPointerLocation,$this->project->getName(),8);
            $this->yPointerLocation+=5;
        }else{
            $this->pdf->SetFont('DejaVu','',8);
            $this->pdf->SetXY(40,$this->yPointerLocation-2);
            $this->pdf->Multicell(90,3,$this->project->getName(),0,'L'); 
    
            $this->yPointerLocation+=8;
        }
        //VendorNumber label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('vendorNumber'),10,true);
        //VendorNumber field
        $this->write(40,$this->yPointerLocation,$this->poData->vendorNumber,8);
    }

    /***
     * Display(render) project manager and currency (over the table, right)
     */
    public function renderPmAndCurrency(){
        
        $pmUser = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pmUser ZfExtended_Models_User */
        $pmUserEntity = $pmUser->load($this->poData->pmId);

        $pmFullName = $pmUserEntity['firstName'].' '.$pmUserEntity['surName'];

        //Projektname label
        $this->write(130,$this->yPointerLocation,$this->getTranslation('pmName'),10,true,'pmId');
        //Projektnummer field

        //if the full name is bigger than 20 characters, use multicell so the name fit
        if(strlen($pmFullName)<=20){
            $this->write(170,$this->yPointerLocation,$pmFullName,8);
            $this->yPointerLocation+=5;
        }else{
            $this->pdf->SetFont('DejaVu','',8);
            $this->pdf->SetXY(170,$this->yPointerLocation-2);
            $this->pdf->Multicell(40,3,$pmFullName,0,'L'); 
    
            $this->yPointerLocation+=8;
        }

        //Currency label
        $this->write(130,$this->yPointerLocation,$this->getTranslation('vendorCurrency'),10,true);
        //Currency field
        $this->write(170,$this->yPointerLocation,$this->poData->vendorCurrency,8);
    }

    /***
     * Display(render) other available informations (below the table)
     */
    public function renderFieldsBelowTable(){

        $this->pdf->SetXY(10,$this->yPointerLocation);
        $this->pdf->Multicell(0,10,$this->getTranslation('additionalInfo'));
        $this->addIconIfDirty(7,$this->yPointerLocation, 'additionalInfo');
        
        $this->yPointerLocation+=2;
        
        //additional info field
        $this->pdf->SetFont('DejaVu','',8); 
        $this->pdf->SetXY(55,$this->yPointerLocation);
        $this->pdf->Multicell(0,6,$this->poData->additionalInfo);
        
        $this->yPointerLocation+=25;

        //Projektnummer label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('transmissionPath'),10,true,'transmissionPath');
        //Projektnummer field
        $this->write(55,$this->yPointerLocation,$this->poData->transmissionPath,8);

        $this->yPointerLocation+=5;

        //Bestellnummer label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('deliveryDate'),10,true,'deliveryDate');
        //Bestellnummer field
        $this->write(55,$this->yPointerLocation,isset($this->poData->deliveryDate)? date("d.m.Y", strtotime($this->poData->deliveryDate)):'',8);

        $this->yPointerLocation+=5;

        //Projektname label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('termsOfPayment'),10,true);
        //Projektname field
        $this->write(55,$this->yPointerLocation,$this->config->runtimeOptions->termsOfPayment,8);
    }

    /***
     * Display(render) the info text
     */
    public function renderInfoText(){
        
        $this->yPointerLocation+=25;
        
        //datum label
        $this->write(10,$this->yPointerLocation,$this->getTranslation('infoText'),8);
    }

    /***
     * Display(render) the footer
     */
    public function renderFooter(){
        // Go to 1.5 cm from bottom
        $this->pdf->SetY(-42);
        // Select DejaVu italic 8
        $this->pdf->SetFont('DejaVu','',8);
        
        $this->pdf->Multicell(0,5,$this->config->runtimeOptions->billPdfFooter,0,'C'); 
    }

    /***
     * Write the text to the pdf page
     * 
     * @param int $x ( x position of the starting point of the text ) 
     * @param int $y ( y position of the starting point of the text )
     * @param string $text ( text to be rendered )
     * @param int $fontSize ( font size )
     * @param bool $bold ( bold text, default false )
     */
    public function write($x,$y,$text,$fontSize,$bold=false,$fieldName=null){
        $this->pdf->SetXY($x,$y);
        $this->pdf->SetFont('DejaVu',$bold?'B':'',$fontSize);
        $this->pdf->SetTextColor(0);
        $this->pdf->Write(0,$text);
        
        $this->addIconIfDirty($x-3, $y, $fieldName);
    }
    
    /***
     * Add megaphone pencile icon if the field is dirty
     * 
     * @param int $x (x location on the pdf)
     * @param int $y (y location on the pdf)
     * @param string $fieldName (the neame of the field)
     */
    public function addIconIfDirty($x,$y,$fieldName){
        if(!is_array($this->dirtyFields)){
            return;
        }
        if(!$fieldName || !in_array($fieldName, $this->dirtyFields)){
            return;
        }
        
        if(isset($this->dirtyFieldsIconShift) && array_key_exists($fieldName, $this->dirtyFieldsIconShift)){
            $x+=$this->dirtyFieldsIconShift[$fieldName]['x'];
            $y+=$this->dirtyFieldsIconShift[$fieldName]['y'];
        }
        
        $this->pdf->Image('modules/erp/resources/megaphone--pencil-8x8.png',$x,$y);
    }

    /***
     *  Returns translated string for given key.
     *  The keys are defined in translationKeys array
     *  
     * @param string $key
     * @return string
     */
    public function getTranslation($key){
        return $this->translationKeys[$key];
    }

    /***
     * Format number as currency with 2 decimal places as defualt.
     * 
     * @param float $number
     * @param bool $showVendorCurrency
     * @param integer $decimals
     * 
     * @return string
     */
    public function formatAsCurrency($number,$showVendorCurrency = false,$decimals=2){
        if($this->isGermanTemplate){
            //arg3=>dec_point, arg4=>thousands_sep
            $retVal = number_format($number,$decimals,',','.');
        }else{
            //arg3=>dec_point, arg4=>thousands_sep
            $retVal = number_format($number,$decimals,'.',',');
        }
        $currency = $this->baseCurrency;
        if($showVendorCurrency){
            $currency=$this->poData->vendorCurrency;
        }
        return $currency.' '.$retVal;
    }
    
    /***
    * Set po data
    * 
    * @param stdClass
    */
    public function setPoData(stdClass $poData){
        $this->poData = $poData;
    }
    /***
    * Set project data
    * 
    * @param erp_Models_Order
    */
    public function setProject(erp_Models_Order $project){
        $this->project = $project;
    }
    /***
    * Set vendor data
    * 
    * @param stdClass
    */
    public function setVendor(stdClass $vendor){
        $this->vendor = $vendor;
    }
    /***
     * Set preview status
     * 
     * @param bool
     */
    public function isPreview(bool $isPreview){
        $this->isPreview=$isPreview;
    }

    /***
     * Generate random name for the temporary file name
     */
    public function generateFileName() {
        $poModel = ZfExtended_Factory::get('erp_Models_PurchaseOrder');
        /* @var $poModel erp_Models_PurchaseOrder */
        $poModel->load($this->poData->id);

        $filePath = $poModel->getPdfFilePath();
        $filename = $poModel->getPdfFileName($this->vendor);
        if (!is_dir($filePath)) {
            mkdir($filePath);
        }
        
        return $filePath.$filename; 
    }
    /***
     * Update the salutation on vendor
     * 
     * @param stdClass $vendor
     */
    public function updateVendorSalutation($vendor){
        $vendor->Salutation = $this->salutationKeys[$vendor->Salutation];
    }
    
    public function setDirtyFields($df){
        if(!is_array($df) && $df!=""){
            $df = explode(',',$df);
        }
        $this->dirtyFields=$df;
    }
    
    /***
     * Show the file only in preview mode
     */
    public function preview(){
        ob_clean();
        $this->pdf->Output();
    }
    /***
    * Create the pdf file
    */
    public function export($type,$fileName){
        $dirname=dirname($fileName);
        //create folder if doesn't exist
        if (!is_dir($dirname)) {
            mkdir($dirname);
        }
        $this->pdf->Output($fileName,$type);
    }
}