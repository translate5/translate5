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
 * FIXME: use DOMDocument or similar to create the XML to deal correctly with escaping of strings!
 */
class editor_Models_Converter_SegmentsToXliff2 extends editor_Models_Converter_SegmentsToXliffAbstract {
    /***
     * xlif2 segment state
     * @var string
     */
    const XLIFF2_SEGMENT_STATE_INITIAL='initial';

    /***
     * xlif2 segment state
     * @var string
     */
    const XLIFF2_SEGMENT_STATE_TRANSLATED='translated';

    /***
     * xlif2 segment state
     * @var string
     */
    const XLIFF2_SEGMENT_STATE_REVIEWED='reviewed';

    /***
     * xlif2 segment state
     * @var string
     */
    const XLIFF2_SEGMENT_STATE_FINAL='final';

    /**
     * addQm              = boolean, add segment QM, defaults to true
     * @var string
     */
    const CONFIG_ADD_QM = 'addQm';

    /***
     * Segment id xliff prefix
     * @var string
     */
    const SEGMENT_ID_PREFIX="seg";

    /***
     * Unit id xliff prefix
     * @var string
     */
    const UNIT_ID_PREFIX="unit";


    /***
     * Qm id xliff prefix
     * @var string
     */
    const QM_ID_PREFIX="QM_";

    /***
     * Value for 'its:person' argument in unit tag
     * @var string
     */
    protected $itsPerson=null;

    /***
     * Value for 'translate5:personGuid' argument in unit tag
     * @var string
     */
    protected $itsPersonGuid=null;

    /***
     * Value for 'its:revPerson' argument in unit tag
     * @var string
     */
    protected $itsRevPerson=null;

    /***
     * Value for 'revPersonGuid' argument in unit tag
     * @var string
     */
    protected $itsRevPersonGuid=null;

    /***
     * Unsupported configs:
     */
    protected $unsupportedConfigs=[
            self::CONFIG_ADD_RELAIS_LANGUAGE,
            self::CONFIG_ADD_ALTERNATIVES,
            self::CONFIG_PLAIN_INTERNAL_TAGS,
            self::CONFIG_ADD_PREVIOUS_VERSION,
            self::CONFIG_ADD_STATE_QM,
            self::CONFIG_ADD_DISCLAIMER
    ];

    /***
     * Finished workflow step
     *
     * @var string
     */
    protected $workflowStep = null;

    /***
      Mapping of translate5 autostates to xliff 2.x substate to xliff 2.x default segment state is as follows.

			Please note:
			- "auto-set" are status flags, that are set by the "translate5 repetition editor" (auto-propagate)
			- "untouched, auto-set" are status flags, that are changed automatically when a user finishes its job, because the finish means approval of everything, he did not touch manually.

			translate5 autostatus			->	xliff 2.x substate	->	mapped xliff status
			--------------------------------------------------------------------------------

		//before translate5 workflow starts
			not translated		 			->	not_translated				->	initial
			blocked							->	blocked						->	initial
			locked							->	locked						->	initial

		//1st default translate5 workflow step: set in translation step or initial status before review only workflow
			translated						->	translated					->	translated
			translated, auto-set			->	translated_auto				->	translated

		//2nd default translate5 workflow step: set in review workflow step
			reviewed					    ->	reviewed					->	reviewed
			reviewed, auto-set				->	reviewed_auto				->	reviewed
			reviewed, untouched, auto-set
				at finish of workflow step	->	reviewed_untouched			->	reviewed
			reviewed, unchanged				->	reviewed_unchanged			->	reviewed
			reviewed, unchanged, auto-set	->	reviewed_unchanged_auto		->	reviewed

		//3rd default translate5 workflow step: set during check of the review by the translator
			Review checked by translator	->	reviewed_translator			->	final
			Review checked by translator,
				auto-set					->	reviewed_translator_auto	->	final

		//Not part of the translate5 workflow - done by the PM at any time of the workflow
			PM reviewed						->	reviewed_pm					->	final
			PM reviewed, auto-set			->	reviewed_pm_auto			->	final
			PM reviewed, unchanged			->	reviewed_pm_unchanged		->	final
			PM reviewed, unchanged, auto-set->	reviewed_pm_unchanged_auto	->	final

       @var array
     */
    protected $segmentStateMap=[
            editor_Models_Segment_AutoStates::REVIEWED=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            editor_Models_Segment_AutoStates::NOT_TRANSLATED=>self::XLIFF2_SEGMENT_STATE_INITIAL,
            editor_Models_Segment_AutoStates::BLOCKED=>self::XLIFF2_SEGMENT_STATE_INITIAL,
            editor_Models_Segment_AutoStates::LOCKED=>self::XLIFF2_SEGMENT_STATE_INITIAL,

            editor_Models_Segment_AutoStates::TRANSLATED=>self::XLIFF2_SEGMENT_STATE_TRANSLATED,
            editor_Models_Segment_AutoStates::TRANSLATED_AUTO=>self::XLIFF2_SEGMENT_STATE_TRANSLATED,

            editor_Models_Segment_AutoStates::REVIEWED_AUTO=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_UNTOUCHED=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_UNCHANGED=>self::XLIFF2_SEGMENT_STATE_REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_UNCHANGED_AUTO=>self::XLIFF2_SEGMENT_STATE_REVIEWED,

            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR_AUTO=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_PM=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_PM_AUTO=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_PM_UNCHANGED=>self::XLIFF2_SEGMENT_STATE_FINAL,
            editor_Models_Segment_AutoStates::REVIEWED_PM_UNCHANGED_AUTO=>self::XLIFF2_SEGMENT_STATE_FINAL,
    ];

    /**
     * @var Zend_Config
     */
    protected $config;

    /***
     * Id of the ins and dels mrk tags. The number will be autoincremented
     * after ins or del mrk tag is produced.
     */
    protected $insDelTagId;

    /**
     * Id of the ins and dels mrk tags. The number will be autoincremented
     * after ins or del mrk tag is produced.
     */
    protected $termId = 1;

    /**
     * Array with taskUserTracking-data of our task
     * @var array
     */
    protected $trackingData = [];

    /***
     * Applied xliff comments (some of them need to exist only once in the file)
     * @var array
     */
    private $xliffCommentsApplied = [];

    /**
     * @param array $config the configuration for the xliff converter, see the CONFIG_ flags
     * @param string $workflowstep the current workflow step
     */
    public function __construct(array $config = [], $workflowstep){
        $this->workflowStep = $workflowstep;
        $this->insDelTagId=1;
        parent::__construct($config);
    }

    /**
     * For the options see the constructor
     * @see self::__construct
     * @param array $config
     */
    public function setOptions(array $config) {
        parent::setOptions($config);

        if(!empty($this->options)){
            foreach ($this->options as $op=>$value){
                if(in_array($op, $this->unsupportedConfigs)){
                    error_log("The config variable ".$value." is unsupported in xliff v 2.1 and it will take no effect over generated xliff");
                }
            }
        }
        //flags defaulting to false
        $defaultsToFalse = [
                self::CONFIG_INCLUDE_DIFF,
                //if this is active, track changes are exported as mrk tag, if not we remove the track changes content
                self::CONFIG_ADD_TERMINOLOGY,
        ];
        foreach($defaultsToFalse as $key){
            settype($this->options[$key], 'bool');
        }

        //flags defaulting to true; if nothing given, empty is the falsy check
        $defaultsToTrue = [
                self::CONFIG_ADD_COMMENTS,
                self::CONFIG_ADD_QM
        ];
        foreach($defaultsToTrue as $key){
            $this->options[$key] = !(array_key_exists($key, $config) && empty($config[$key]));
        }

        if($this->options[self::CONFIG_ADD_TERMINOLOGY]) {
            $this->initTagHelper();
        }
    }

    /***
     * Init the needed data for xliff 2.1 convertion
     * {@inheritDoc}
     * @see editor_Models_Converter_SegmentsToXliffAbstract::initConvertionData()
     */
    protected function initConvertionData(){
        parent::initConvertionData();

        //load the associated users to the task
        $assocModel=ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $assocModel editor_Models_TaskUserAssoc */
        $assocUsers=$assocModel->loadByTaskGuidList([$this->task->getTaskGuid()]);
        $this->data['assocUsers']=[];

        $tmpUser=[];
        foreach($assocUsers as $user){
            if(!$user['role'] || $user['role']===editor_Workflow_Default::ROLE_VISITOR || $user['role']===''){
                continue;
            }
            $this->data['assocUsers'][$user['role']][]=$user;
        }

        //map the user data for the assoc users
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $allUsers=$userModel->loadAll();
        array_map(function($usr){
            $this->data['users'][$usr['userGuid']]=$usr;

        },$allUsers);

        // prepare tracking-data of this task
        $taskUserTracking = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        /* @var $taskUserTracking editor_Models_TaskUserTracking */
        $taskUserTrackingData = $taskUserTracking->getByTaskGuid($this->task->getTaskGuid());
        foreach ($taskUserTrackingData as $value) {
            $this->trackingData[$value['id']] = (object)['userGuid' => $value['userGuid'],'userName' => $value['userName']];
        }

        //init the manual status
        $this->config = Zend_Registry::get('config');
        $rop = $this->config->runtimeOptions;
        $manualStates = $rop->segments->stateFlags->toArray();
        $this->data['manualStatus']=$manualStates;
    }
    /**
     * Helper function to create the XML Header
     */
    protected function createXmlHeader() {
        $headParams = array('xliff', 'version="2.1"');

        $headParams[] = 'xmlns="urn:oasis:names:tc:xliff:document:2.0"';

        $languagesModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languagesModel editor_Models_Languages */
        $sourceLang=$languagesModel->loadLangRfc5646($this->task->getSourceLang());
        $targetLang=$languagesModel->loadLangRfc5646($this->task->getTargetLang());
        $headParams[] = 'srcLang="'.$this->escape($sourceLang).'"';
        $headParams[] = 'trgLang="'.$this->escape($targetLang).'"';

        $headParams[] = 'xmlns:its="https://www.w3.org/2005/11/its/"';
        $this->enabledNamespaces['its'] = 'its';

        $headParams[] = 'its:version="2.0"';

        $headParams[] = 'xmlns:translate5="http://www.translate5.net/"';
        $this->enabledNamespaces['translate5'] = 'translate5';

        $headParams[] = 'translate5:taskguid="'.$this->escape($this->task->getTaskGuid()).'"';
        $headParams[] = 'translate5:taskname="'.$this->escape($this->task->getTaskName()).'"';
        $this->result[] = '<'.join(' ', $headParams).'>';

        $this->result[] = '<!-- For attributes or elements in translate5 that have no matching xliff 2 representation are the translate5 namespace is used -->';
        $this->result[] = '<!-- The file id reflects the fileid in LEK_segments table of translate5. The translate5:filenmae reflects the fileName as in LEK_files table in translate5 -->';
    }


    /**
     * process and convert all segments to xliff
     * @param string $filename
     * @param array $segmentsOfFile
     */
    protected function processAllSegments($filename, Traversable $segmentsOfFile) {
        if(empty($segmentsOfFile)) {
            return;
        }
        $segmentsOfFile->rewind();
        $first = $this->unifySegmentData($segmentsOfFile->current());

        $file = '<file id="%s" translate5:filename="%s">';
        $this->result[] = sprintf($file,$first['fileId'],$this->escape($filename));

        $this->addComments('unitComment');

        foreach($segmentsOfFile as $segment) {
            $this->processSegmentsOfFile($this->unifySegmentData($segment));
        }

        $this->result[] = '</file>';
    }

    /**
     * process and convert the segments of one file to xliff
     * @param array $segment
     */
    protected function processSegmentsOfFile($segment) {

        if(!empty($this->data['assocUsers'])){
            $this->initItsPersonGuid($segment);
            $this->initItsRefPersonGuid($segment);
        }
        //reset term mrk id to 1 per trans-unit
        $this->termId = 1;

        $unitTag[]='unit';
        $unitTag[]='id="'.self::UNIT_ID_PREFIX.$segment['segmentNrInTask'].'"';

        //set the person attributes in unit tag
        if($this->itsPerson){
            $unitTag[]='its:person="'.$this->escape($this->itsPerson).'"';
        }

        if($this->itsPersonGuid){
            $unitTag[]='translate5:personGuid="'.$this->escape($this->itsPersonGuid).'"';
        }

        if($this->itsRevPerson){
            $unitTag[]='its:revPerson="'.$this->escape($this->itsRevPerson).'"';
        }

        if($this->itsRevPersonGuid){
            $unitTag[]='translate5:revPersonGuid="'.$this->escape($this->itsRevPersonGuid).'"';
        }

        //state start
        if(isset($this->data['manualStatus'][$segment['stateId']])) {
            $stateText =  $this->data['manualStatus'][$segment['stateId']];
            $unitTag[]='translate5:manualStatus="'.$this->escape($stateText).'" '.'translate5:manualStatusId="'.$this->escape($segment['stateId']).'"';
        }

        $this->result[] = '<'.join(' ', $unitTag).'>';

        //segment QM states
        $qualityData = [];
        if($this->options[self::CONFIG_ADD_QM]) {
            $qualityData = $this->processQmQualities($segment);
        }

        //add the comments
        if(!empty($segment['comments']) && $this->options[self::CONFIG_ADD_COMMENTS]) {
            $this->processComment($segment);
        }

        //add the comment only once
        $this->addComments('autostateMapComment');

        //according to the spec, the prefix "translate5Autostate:" must always be shown in the subState value.
        $stateText="";
        if(isset($this->data['autostates'][$segment['autoStateId']])) {
            $stateText =  $this->data['autostates'][$segment['autoStateId']];
        }

        $this->result[] = '<segment id="'.$this->escape(self::SEGMENT_ID_PREFIX.$segment['segmentNrInTask']).'" translate5:matchRate="'.$this->escape($segment['matchRate']).'" state="'.$this->segmentStateMap[$segment['autoStateId']].'" subState="translate5Autostate:'.$this->escape($stateText).'">';

        //add the comment only once
        $this->addComments('translate5:matchRate');

        //add the comment only once
        $this->addComments('mrkTagComment');

        //add the source text
        $this->result[] = '<source>'.$this->prepareText($segment[$this->data['firstSource']]).'</source>';


        //add the comment only once
        $this->addComments('qmAndTrachChangesComment');

        $fields = $this->sfm->getFieldList();
        foreach($fields as $field) {
            $this->processSegmentField($field, $segment, $qualityData);
        }

        $this->result[] = '</segment>';
        $this->result[] = '</unit>';
    }

    /**
     * process and convert the segment comments
     * @param array $segment
     */
    protected function processComment(array $segment) {
        $comments = $this->comment->loadBySegmentAndTaskPlain((int)$segment['id'], $this->task->getTaskGuid());


        //add the comment only once
        $this->addComments('commentsComment');

        $note = '<note id="%1$s" translate5:userGuid="%2$s" translate5:username="%3$s" translate5:created="%4$s" translate5:modified="%5$s">%6$s</note>';
        $this->result[] = '<notes>';
        foreach($comments as $comment) {
            $modified = new DateTime($comment['modified']);
            $created  = new DateTime($comment['created']);
            //if the +0200 at the end makes trouble use the following
            //gmdate('Y-m-d\TH:i:s\Z', $modified->getTimestamp());
            $modified = $modified->format($modified::ATOM);
            $created= $created->format($created::ATOM);
            $this->result[] = sprintf($note,
                $this->escape($comment['id']),
                $this->escape($comment['userGuid']),
                $this->escape($comment['userName']),
                $created,
                $modified,
                $this->escape($comment['comment']));
        }
        $this->result[] = '</notes>';
    }

    /**
     * process and convert the segments of one file to xliff
     * @param Zend_Db_Table_Row $field
     * @param array $segment
     */
    protected function processSegmentField(Zend_Db_Table_Row $field, array $segment, array $qualityData = []) {
        if($field->type == editor_Models_SegmentField::TYPE_SOURCE) {
            return; //handled before
        }
        if($field->type == editor_Models_SegmentField::TYPE_RELAIS && $this->data['relaisLang'] !== false) {
            return;
        }
        if($field->type != editor_Models_SegmentField::TYPE_TARGET) {
            return;
        }


        $lang = $this->data['targetLang'];
        if($this->data['firstTarget'] == $field->name) {
            $altTransName = $field->name;
            $targetEdit = $this->prepareText($segment[$this->sfm->getEditIndex($this->data['firstTarget'])]);

            $this->result[] = '<target>';

            //if there are qms for the segment add the mrk tag
            if(!empty($qualityData)){
                $this->result[] ='<mrk id="'.$this->escape(self::QM_ID_PREFIX.implode('_',array_keys($qualityData))).'" its:type="generic" translate="yes" its:locQualityIssuesRef="'.$this->escape(self::QM_ID_PREFIX.implode('_', array_keys($qualityData))).'">';
            }
            //add the target edit text
            $this->result[] = $targetEdit;

            //if there are qms for this segment close the mark tag
            if(!empty($qualityData)){
                $this->result[] = '</mrk>';
            }

            $this->result[] = '</target>';
        }
    }

    /***
     * Init itsPerson and itsPersonGuid variables
     * @param array $segment
     */
    protected function initItsPersonGuid($segment){
        $assocUsers=$this->data['assocUsers'];
        //check if user with role translator or translator-check is assigned to the task
        if(!isset($assocUsers[editor_Workflow_Default::ROLE_TRANSLATOR]) && !isset($assocUsers[editor_Workflow_Default::ROLE_TRANSLATORCHECK])){
            return;
        }

        $tmpTranslatorArray=[];
        $this->itsPerson=null;
        $this->itsPersonGuid=null;

        $isTranslatorSet=isset($assocUsers[editor_Workflow_Default::ROLE_TRANSLATOR]);
        
        //if only one translator
        if($isTranslatorSet && count($assocUsers[editor_Workflow_Default::ROLE_TRANSLATOR])==1){
            $this->itsPersonGuid=$assocUsers[editor_Workflow_Default::ROLE_TRANSLATOR][0]['userGuid'];
        }else if(!$isTranslatorSet || count($assocUsers[editor_Workflow_Default::ROLE_TRANSLATOR])==0){//if the there is no translator, check for translatorCheck
            
            //if only one translatorCheck
            if(count($assocUsers[editor_Workflow_Default::ROLE_TRANSLATORCHECK])==1){
                $this->itsPersonGuid=$assocUsers[editor_Workflow_Default::ROLE_TRANSLATORCHECK][0]['userGuid'];
            }else if(count($assocUsers[editor_Workflow_Default::ROLE_TRANSLATORCHECK])>1){//more than one tanslator check
                $tmpTranslatorArray=$assocUsers[editor_Workflow_Default::ROLE_TRANSLATORCHECK];
            }
        }else{//more than one translator
            $tmpTranslatorArray=$assocUsers[editor_Workflow_Default::ROLE_TRANSLATOR];
        }

        if($this->itsPersonGuid){
            $usr=$this->data['users'][$this->itsPersonGuid];
            $this->itsPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }

        if(empty($tmpTranslatorArray)){
            return;
        }

        //check last editor
        foreach ($tmpTranslatorArray as $translator){
            if($translator['userGuid']===$segment['userGuid']){
                $this->itsPersonGuid=$segment['userGuid'];
                break;
            }
        }

        if($this->itsPersonGuid){
            $usr=$this->data['users'][$this->itsPersonGuid];
            $this->itsPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        //it is no user that is assigned to the task, it can be pm
        if($segment['userGuid'] === $this->task->getPmGuid()){
            //If the workflow step that is currently finishd is translation or translator-check, the PM is used for its:person.
            //If the current workflow step is review, than the project manager is used for its:revPerson
            if($this->workflow->isStepOfRole($this->workflowStep, [editor_Workflow_Default::ROLE_TRANSLATOR, $this->workflowStep===editor_Workflow_Default::ROLE_TRANSLATORCHECK])){
                $this->itsPersonGuid = $this->task->getPmGuid();
            }

            if(!empty($segment['workflowStep']) && $this->workflow->isStepOfRole($segment['workflowStep'], [editor_Workflow_Default::ROLE_REVIEWER])){
                $this->revPersonGuid = $this->task->getPmGuid();
            }
        }

        if($this->itsPersonGuid){
            $usr=$this->data['users'][$this->itsPersonGuid];
            $this->itsPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        //if nothing is found set to undefined
        $this->itsPersonGuid='undefined';
        $this->itsPerson='undefined';
    }

    /***
     * Set the itsRevPerson and itsRevPersonGuid variable
     * @param Array $segment
     */
    protected function initItsRefPersonGuid($segment){
        $assocUsers=$this->data['assocUsers'];
        $tmpReviewerArray=[];
        $this->itsRevPerson=null;
        $this->itsRevPersonGuid=null;

        //check if there is a reviewer assigned to the task
        if(!isset($assocUsers[editor_Workflow_Default::ROLE_REVIEWER])){
            return;
        }

        //check if no lectors are assigned
        if(count($assocUsers[editor_Workflow_Default::ROLE_REVIEWER])==0){
            return;
        }

        //if only one reviewer
        if(count($assocUsers[editor_Workflow_Default::ROLE_REVIEWER])==1){
            $this->itsRevPersonGuid=$assocUsers[editor_Workflow_Default::ROLE_REVIEWER][0]['userGuid'];
            
            $usr=$this->data['users'][$this->itsRevPersonGuid];
            $this->itsRevPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        //there are multiple reviewer to the task
        $tmpReviewerArray=$assocUsers[editor_Workflow_Default::ROLE_REVIEWER];
        
        //check last editor
        foreach ($tmpReviewerArray as $reviewer){
            if($reviewer['userGuid']===$segment['userGuid']){
                $this->itsRevPersonGuid=$segment['userGuid'];
                break;
            }
        }

        if($this->itsRevPersonGuid){
            $usr=$this->data['users'][$this->itsRevPersonGuid];
            $this->itsRevPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }

        //it is no user that is assigned to the task, it can be pm
        if($segment['userGuid'] === $this->task->getPmGuid()){
            if($this->workflow->isStepOfRole($this->task->getWorkflowStepName(), [editor_Workflow_Default::ROLE_REVIEWER])){
                $this->itsRevPersonGuid=$this->task->getPmGuid();
            }
        }

        if($this->itsRevPersonGuid){
            $usr=$this->data['users'][$this->itsRevPersonGuid];
            $this->itsRevPerson=$usr['surName'].' '.$usr['firstName'];
            return;
        }
        $this->itsRevPersonGuid='undefined';
        $this->itsRevPerson='undefined';
    }

    /**
     * prepares segment text parts for xml
     * @param string $text
     * @return string
     */
    protected function prepareText($text) {
        //if active, track changes are exported as mrk tag, if not we remove the track changes content
        if($this->options[self::CONFIG_INCLUDE_DIFF]){
            $text=$this->trackChangesAsMrk($text);
        }else{
            $text=$this->taghelperTrackChanges->removeTrackChanges($text);
        }

        // 1. toXliff converts the internal tags to xliff 2 tags
        // 2. remove MQM tags
        //TODO MQM tags are just removed and not supported by our XLIFF exporter so far!

        //local id calculation leads to invalid XLIFF2, since semantical same tags in source and target must have the same id.
        // using null here disables local calculation, fallback is then the original ID which is fine
        $tagId = null;
        $text = $this->taghelperInternal->toXliff2Paired($text, false, $this->tagMap, $tagId);
        $text = $this->handleTerminology($text, false); //internaltag replacment not needed, since already converted
        $text = $this->taghelperMqm->remove($text);
        return $text;
    }

    /**
     */
    protected function handleTerminology($text, $protectInternalTags) {
        if(!$this->options[self::CONFIG_ADD_TERMINOLOGY]){
            return $this->taghelperTerm->remove($text);
        }
        $termStatus = editor_Models_Terminology_Models_TermModel::getAllStatus();
        $transStatus = [
                editor_Models_Terminology_Models_TermModel::TRANSSTAT_FOUND => 'found',
                editor_Models_Terminology_Models_TermModel::TRANSSTAT_NOT_FOUND => 'notfound',
                editor_Models_Terminology_Models_TermModel::TRANSSTAT_NOT_DEFINED => 'undefined',
        ];
        return $this->taghelperTerm->replace($text, function($wholeMatch, $tbxId, $classes) use ($termStatus, $transStatus) {
            //TODO: to get the definition value we need the title:
            //currentyl the title is not applied by termtagger->TERMTAGGER-33: term-definition is not passed in tagged return
            //if no title or the title is empty, do not add the value field
            $status = '';
            $translation = '';
            foreach($classes as $class) {
                if($class == editor_Models_Terminology_Models_TermModel::CSS_TERM_IDENTIFIER) {
                    continue;
                }
                if(in_array($class, $termStatus)) {
                    $status = $class;
                    continue;
                }

                if(!empty($transStatus[$class])) {
                    $translation = ' translate5:translated="'.$this->escape($transStatus[$class]).'"';
                }
            }

            return '<mrk id="term-'.($this->termId++).'" type="term" translate5:termid="'.$this->escape($tbxId).'" translate5:status="'.$this->escape($status).'"'.$translation.'>';
        }, '</mrk>', $protectInternalTags);
    }

    /***
     * Convert the trach changes tags to xliff 2.1 mrk tags
     * @param string $text
     * @return string
     */
    protected function trackChangesAsMrk($text){
        $insOpenTag='/<ins[^>]*>/i';
        $delOpenTag='/<del[^>]*>/i';
        $attributesRegex='/(data-userguid|data-username|data-usertrackingid|data-timestamp)=("[^"]*")/i';
        //parameter map between ins/del tags and the new mrk ins/del tags
        $paramMap=[
            'data-userguid'=>'translate5:userGuid', // keep userGuid and username for tasks with TrackChange-Tags before anonymizing was implemented
            'data-username'=>'translate5:username',
            'data-usertrackingid'=>'translate5:username', // NOT userName, must be the same as before from data-username
            'data-timestamp'=>'translate5:date'
        ];

        //find the ins or delete tags
        $mrkConverter=function($inputText,$regex,$attributesRegex,$paramMap,$trackChangeType){

            //for each match find the ins del tags arguments
            $inputText=preg_replace_callback($regex, function($match) use ($attributesRegex,$paramMap,$trackChangeType){

                $retVal=$match[0];
                $buildTag=[];

                //for each argument match, get the value and key
                $retVal=preg_replace_callback($attributesRegex, function($match2) use (&$buildTag,$paramMap,$trackChangeType){
                    $argValue=$match2[2];
                    $argName=$paramMap[$match2[1]];

                    //convert the date
                    if($argName==='translate5:date'){
                        $argValue= str_replace('"','', $argValue);
                        //check if the date is 13 digit timestamp
                        if(is_numeric($argValue) && strlen((string)$argValue)===13){
                            //convert the timestamp to ISO 8601 date
                            $argValue=date('c',$argValue/1000);

                        }
                        $argValue='"'.$argValue.'"';
                    }

                    //convert the username and userGuid from data-usertrackingid ...
                    if($argName==='translate5:username'){
                        $userTrackingId = str_replace('"','', $argValue);
                        // ... but only when from data-usertrackingid (= e.g "90"), not from data-username (= e.g. "Project Manager")
                        // (in other words: if a taskUserTracking-entry exists; the data-username might be "1001" and thus numeric, too)
                        if(array_key_exists($userTrackingId, $this->trackingData)) {
                            $tracked = $this->trackingData[$userTrackingId];
                            $argValue='"'.$tracked->userName.'" translate5:userGuid="'.$tracked->userGuid.'"';
                        }
                    }

                    $buildTag[$argName]=$argValue;

                    return "";
                }, $retVal);

                //for each argument map, add the argument + value in a string
                $mrkString='';
                foreach($buildTag as $key=>$item) {
                    $mrkString.= $key.'='.$item.' ';
                }
                $translate=$trackChangeType=== 'ins' ? 'yes' :'no';
                $mrkString='<mrk id="'.$this->insDelTagId.'" translate="'.$translate.'" translate5:trackChanges="'.$trackChangeType.'" '.$mrkString.'>';

                $this->insDelTagId++;

                return $mrkString;

            }, $inputText);
            return $inputText;
        };

        //ins to mrk
        $text= $mrkConverter($text,$insOpenTag,$attributesRegex,$paramMap,'ins');
        //del to mrk
        $text= $mrkConverter($text,$delOpenTag,$attributesRegex,$paramMap,'del');

        //clean the closing ins and del tags
        $text= str_replace('</ins>','</mrk>', $text);
        $text= str_replace('</del>','</mrk>', $text);

        return $text;
    }


    /**
     * process and convert the segment QM states
     * TODO FIXME: the processing of the qualities works with QMs only at the moment. If more qualities are included, the processing needs to change as not all qualities have (unique) categoryIndices
     * @param array $segment
     */
    protected function processQmQualities(array $segment) {
        $qualityData = $this->fetchQualityData($segment['id']);
        if(empty($qualityData)) {
            return [];
        }
        $qmData = [];
        foreach($qualityData as $item){
            $qmData[$item['categoryIndex']] = $item['text'];
        }
        $this->addComments('qmComment');
        $this->result[] = '<its:locQualityIssues xml:id="'.$this->escape(self::QM_ID_PREFIX.implode('_', array_keys($qmData))).'">';
        $qmXml = '<its:locQualityIssue locQualityIssueType="%1$s" />';
        foreach ($qmData as $qmIndex => $qmName) {
            $this->result[] = sprintf($qmXml, $qmName);
        }
        $this->result[]='</its:locQualityIssues>';

        return $qmData;
    }

    /****
     * Add coments to the xliff file based on the comment type(key).
     * The comments will be added only once per occurrence
     * @param string $commentType
     */
    private function addComments($commentType){
        //add the comment only once
        if(in_array($commentType, $this->xliffCommentsApplied)){
            return;
        }
        $unitComment=[];
        switch ($commentType){
            case 'unitComment':
                $unitComment[]='<!-- unit id is the segmentNrInTask of LEK_segment in translate5 with the prefix "unit"; Since we only use on segment per unit in translate5 changes xliff, the segment id is the same, but with the prefix "seg";
its:person is the translator name, if assigned in translate5; if no translator is assigned, it is the user of the translator-check;
translate5:personGuid is the corresponding userGuid of the person-attribute
its:revPerson is the reviewer, if assigned in translate5;
translate5:revPersonGuid is the userGuid of the reviewer;
- if more than one reviewer or translator is assigned, the above attributes refer to the translator or reviewer
  that edited the segment the last time / set the autostatus flag the last time;
- if the last editor is no person, that is assigned to the task, it may be the project manager. If it is the project manager,
  the project manager of the task is used in the following way: If the workflow step that is currently finishd is translation
  or translator-check, the PM is used for its:person. If the current workflow step is reviewing,
  than the project manager is used for its:revPerson
- if the last editor of a segment is no assigned user and not the PM, but we have more than one user assigned for a
  role, than we use the value "undefined".
- if no user is assigned for a role, we omit the attribute (be it its:person or its:revPerson).
translate5:manualStatus is omitted, if empty
-->';
                break;
            case 'autostateMapComment':
                $unitComment[]='<!--
translate5 autostates show the segment state more in detail, than xliff 2 is able to. To reflect the Autostates the substate attribute of xliff 2 is used. Autostates are mapped to xliff 2 segment state as best as possible

Mapping of translate5 autostates to xliff 2.x substate to xliff 2.x default segment state is as follows.

Please note:
- "auto-set" are status flags, that are set by the "translate5 repetition editor" (auto-propagate)
- "untouched, auto-set" are status flags, that are changed automatically when a user finishes its job, because the finish means approval of everything, he did not touch manually.

translate5 autostatus			->	xliff 2.x substate	->	mapped xliff status
===============================================================================

//before translate5 workflow starts
not translated		 			->	not_translated				->	initial
blocked							->	blocked						->	initial
locked							->	locked						->	initial

//1st default translate5 workflow step: set in translation step or initial status before review only workflow
translated						->	translated					->	translated
translated, auto-set			->	translated_auto				->	translated

//2nd default translate5 workflow step: set in review workflow step
reviewed					    ->	reviewed					->	reviewed
reviewed, auto-set				->	reviewed_auto				->	reviewed
reviewed, untouched, auto-set
	at finish of workflow step	->	reviewed_untouched			->	reviewed
reviewed, unchanged				->	reviewed_unchanged			->	reviewed
reviewed, unchanged, auto-set	->	reviewed_unchanged_auto		->	reviewed

//3rd default translate5 workflow step: set during check of the review by the translator
Review checked by translator	->	reviewed_translator			->	final
Review checked by translator,
	auto-set					->	reviewed_translator_auto	->	final

//Not part of the translate5 workflow - done by the PM at any time of the workflow
PM reviewed						->	reviewed_pm					->	final
PM reviewed, auto-set			->	reviewed_pm_auto			->	final
PM reviewed, unchanged			->	reviewed_pm_unchanged		->	final
PM reviewed, unchanged, auto-set->	reviewed_pm_unchanged_auto	->	final
		
-->';
                break;
            case 'mrkTagComment':
                $unitComment[]='<!-- The translate5:translated attribut on mrk-tags of type="term" shows, if the marked term has been found as translated in the corresponding target segment or not.
 The allowed values are "found", "notfound" and "undefined". Undefined usually means, that no term has been defined in the target language of the terminology.
 The translate5:translated attribute can only occur inside of the source-tag.
	
 The translate5:status  attribut on mrk-tags of type="term" shows the term classification of the term in the terminology.
 Its values can be one of the following: preferredTerm, admittedTerm, legalTerm, regulatedTerm, standardizedTerm, deprecatedTerm, supersededTerm.
 translate5:status can occur in mrk tags inside of source AND target tags.
 translate5:termid contains the term id used for that term in translate5.
   -->';
                break;
            case 'qmAndTrachChangesComment':
                $unitComment[]='<!--
If there is a QM flag on the entire segment in translate5, an mrk-tag surrounding the entire segment content with the attribute its:locQualityIssuesRef is used.
The value of the its:locQualityIssuesRef attribute contains the translate5-specific ids of all qm flags, that are added to the entire segment.
The ids are separated by underscore (this means the id e. g. looks like "1_3_5", if three QM flags have been selected for the segment and they have the ids 1, 3 and 5 in translate5).
The actual values of the qm-flags are listed in the its:locQualityIssues tag above.
-->
	
<!--
mrk-tags with the translate5:trackChanges attribute show, where changes have been made inside of the segment.
translate5:trackChanges="ins" reflect inserted strings and translate5:trackChanges="del" show deleted strings.
The other attributes of these mrk tags are self-explaining. The value of the id is random, since inside of translate5 there is no id for these tags.
The value of translate5:date is in the format  "2017-12-06 13:12:34"
-->';
                break;
            case 'commentsComment':
                $unitComment[]='<!-- the note id reflects the id in LEK_comments table of translate5 -->';
                break;
            case 'qmComment':
                $unitComment[]='<!--  The attribute its:locQualityIssueType holds as value the qm flag value text from translate5. To be valid xliff 2.1, the used qm flags in translate5 must be ITS localization quality issues as listed at https://www.w3.org/TR/its20/#lqissue-typevalues -->';
                break;
            case 'translate5:matchRate':
                $unitComment[]='<!-- The translate5:matchRate attribute contains the current matchrate for the segment. -->';
                break;

        }

        $this->result[] =join("\n", $unitComment);
        array_push($this->xliffCommentsApplied, $commentType);
    }
}
