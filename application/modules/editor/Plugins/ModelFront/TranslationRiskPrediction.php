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

/***
 * Calculates segment matchrate based on model front risk prediction results for each mt pretranslated segment,
 * or also can be used for single source/target translation matchrate validator/checker
 *
 */
class editor_Plugins_ModelFront_TranslationRiskPrediction {

    /***
     * 
     * @var editor_Plugins_ModelFront_HttpApi
     */
    protected editor_Plugins_ModelFront_HttpApi $api;
    
    /***
     * 
     * @var editor_Models_Task
     */
    protected editor_Models_Task $task;
    
    /***
     * 
     * @var editor_Plugins_ModelFront_MatchrateUpdater
     */
    protected $updater;
    
    public function __construct(editor_Models_Task $task) {
        $this->task=$task;
        $this->updater=ZfExtended_Factory::get('editor_Plugins_ModelFront_MatchrateUpdater');
        $this->initApi();
    }
    
    /**
     * Init the model front api class and set the required api params
     */
    protected function initApi(){
        $this->api=ZfExtended_Factory::get('editor_Plugins_ModelFront_HttpApi');
        $languages=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages */
        
        $languages->load($this->task->getSourceLang());
        $this->api->setSourceLangRfc($languages->getRfc5646());
        
        $languages->load($this->task->getTargetLang());
        $this->api->setTargetLangRfc($languages->getRfc5646());
    }
    
    /***
     * Update segment matchrate from modelfront results.
     * If analysis and language resource is provided, the analysis matchrate will be updated to.
     * 
     * @param editor_Models_Segment $segment
     * @param int $analysisId
     * @param int $languageResourceId
     * @throws editor_Plugins_ModelFront_Exception
     */
    public function updateSegmentMatchrate(editor_Models_Segment $segment,int $analysisId=null,int $languageResourceId=null) {

        $errors=[];
        
        /* @var $segment editor_Models_Segment */
        $original=$segment->get('sourceEditToSort') ?? $segment->get('sourceToSort');
        $translation=$segment->get('targetEditToSort') ?? $segment->get('targetToSort');
        
        $matchRate=null;
        try {
            $matchRate=$this->riskToMatchrate($original, $translation);
        } catch (editor_Plugins_ModelFront_Exception $e) {
            //if there are errors on the api request for the segment display them
            $errors[]=[
                'segmentId'=>$segment->getId(),
                'error'=>$this->api->getErrors()
            ];
            throw new editor_Plugins_ModelFront_Exception('E1269',['errors'=>print_r($errors,1)]);
        }
        
        if($matchRate<0){
            return;
        }
        $this->updater->setSegment($segment);
        $this->updater->setMatchRate(round($matchRate));
        $this->updater->updateSegment();
        //update the analysis matchrate if the language resource and the analysis are set
        if(isset($analysisId) && isset($languageResourceId)){
            $this->updater->updateAnalysis($analysisId, $languageResourceId);
        }
    }
    

    /***
     * Get translation risk for given original and translation text and convert the risk to translate5 matchrate value.
     * @param string $original
     * @param string $translation
     * @throws editor_Plugins_ModelFront_Exception
     * @return array
     */
    public function riskToMatchrate(string $original, string $translation) {
        $data=[
            'original'=>$original,
            'translation'=>$translation
        ];
        $this->api->predictRisk($data);
        if(!empty($this->api->getErrors())){
            throw new editor_Plugins_ModelFront_Exception('E1269',['errors'=>print_r($this->api->getErrors(),1)]);
        }
        $result=$this->api->getResult();
        $result=$result[0]['risk'] ?? null;
        if(empty($result)){
            return -1;
        }
        //A risk is a floating point number with a value from 0.0 to 1.0
        //where 1 is bad and 0 is perfect
        $risk=(float) $result;
        //convert the risk to matchrate
        //in translate5 100% is perfect 0% is bad
        return 100*(1-$risk);
    }
}