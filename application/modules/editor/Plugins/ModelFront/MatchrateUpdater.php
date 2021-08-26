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

/***
 * Update the segment matchrate and add the modelfront matchrate type to the segments matchrate type.
 * With this class also analysis matchrate for a segment can be updated.
 *
 */
class editor_Plugins_ModelFront_MatchrateUpdater {
    
    const RISK_PREDICTION_MATCHRATETYPE=';risk-prediction;ModelFront';
    
    /***
     * 
     * @var editor_Models_Segment
     */
    protected $segment;
    
    /***
     * 
     * @var int
     */
    protected $matchRate;
    
    /***
     * Update segment matchrate
     * @param int $id
     * @param array $data
     */
    public function updateSegment(){
        $history = $this->segment->getNewHistoryEntity();
        $this->segment->setMatchRate($this->matchRate);
        $this->segment->setMatchRateType($this->formatMatchrateType($this->segment->getMatchRateType()));
        $history->save();
        $this->segment->setTimestamp(NOW_ISO);
        $this->segment->save();
    }
    
    /***
     * Update segments analysis matchrate for given languageresource
     * @param int $analysisId
     * @param int $languageResourcesId
     */
    public function updateAnalysis(int $analysisId,int $languageResourcesId){
        $analysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
        /* @var $analysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
        $analysis->db->update([
            'matchRate'=>$this->matchRate
        ], [
            'analysisId = ?'=>$analysisId,
            'segmentId = ?'=>$this->segment->getId(),
            'languageResourceid = ?'=>$languageResourcesId,
        ]);
    }
    
    /**
     * Add the prediction matchratetype in the given matchratre
     * @param string $current
     * @return string
     */
    public function formatMatchrateType(string $current) {
        return rtrim($current, self::RISK_PREDICTION_MATCHRATETYPE) . self::RISK_PREDICTION_MATCHRATETYPE;
    }
    
    public function setSegment(editor_Models_Segment $segment) {
        $this->segment=$segment;
    }
    
    public function setMatchRate(int $matchRate) {
        $this->matchRate=$matchRate;
    }
}