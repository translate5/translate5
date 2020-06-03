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

class editor_Plugins_ModelFront_TranslationRiskPrediction {

    const RISK_PREDICTION_MATCHRATETYPE=';risk-prediction;ModelFront';
    
    /***
     * 
     * @var editor_Plugins_ModelFront_HttpApi
     */
    protected $api;
    
    /***
     * 
     * @var editor_Models_Task
     */
    protected $task;
    
    /***
     *
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;
    
    public function __construct(editor_Models_Task $task) {
        $this->task=$task;
        $this->sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
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
     * Query the ModelFront api for translation risk prediction for each mt-pretranslated segment
     */
    public function riskToMatchrate() {
        //get all pretranslated segments
        $segments=$this->loadSegments();
        if(empty($segments)){
            return;
        }
        $errors=[];
        //TODO: split the calculate logic so it can be called for segment set from outside
        foreach ($segments as $chunk){
            $data=[
                'original'=>$chunk['sourceEditToSort'],
                'translation'=>$chunk['targetEditToSort']
            ];
            
            $this->api->predictRisk($data);
            //if there are erros on the api request for the segment, collect the errors and display them at the end
            if(!empty($this->api->getErrors())){
                $errors[]=[
                    'segmentId'=>$chunk['id'],
                    'error'=>$this->api->getErrors()
                ];
                continue;
            }
            
            $data=$this->api->getResult();
            if(empty($data)){
                continue;
            }
            foreach ($data as $d){
                $this->updateSegment($chunk['id'],$d);
            }
        }
        if(!empty($errors)){
            throw new editor_Plugins_ModelFront_Exception('E1269',['errors'=>print_r($errors,1)]);
        }
    }
    
    /***
     * Update segment matchrate from the ModelFront risk results.
     * @param int $id
     * @param array $data
     */
    protected function updateSegment(int $id,array $data){
        if(empty($data) || !isset($data['risk'])){
            return;
        }
        $segment=ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($id);
        
        $history = $segment->getNewHistoryEntity();
        
        //A risk is a floating point number with a value from 0.0 to 1.0
        //where 1 is bad and 0 is perfect
        $risk=(float) $data['risk'];
        //convert the risk to matchrate
        //in translate5 100% is perfect 0% is bad
        $matchRate=100*(1-$risk);
        $segment->setMatchRate(round($matchRate));
        $segment->setMatchRateType($this->formatMatchrateType($segment->getMatchRateType()));
        $history->save();
        $segment->setTimestamp(NOW_ISO);
        $segment->save();
    }
    
    /***
     * Load all mt pretranslated segments
     * @return array
     */
    protected function loadSegments(){
        $segment=ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        return $segment->loadMtPretranslated($this->task->getTaskGuid());
    }
    
    /**
     * Add the prediction matchratetype in the given matchratre
     * @param string $current
     * @return string
     */
    protected function formatMatchrateType(string $current) {
        return rtrim($current, self::RISK_PREDICTION_MATCHRATETYPE) . self::RISK_PREDICTION_MATCHRATETYPE;
    }
}