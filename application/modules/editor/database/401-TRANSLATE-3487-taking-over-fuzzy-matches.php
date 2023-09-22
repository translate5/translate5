<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2023 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Migration script to fix broken tags in segments where the target was taken from TM memory.
 *  This script will try to find the correct tag by tag number from the tags in the source segment and replace it in
 * the target segment (target edit).
 * The script will only fix translation tasks. The broken segments in the review tasks are not repairable and will only
 * be logged in special file in data/logs folder
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '401-TRANSLATE-3487-taking-over-fuzzy-matches.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = ZfExtended_Factory::get(editor_Models_Db_SegmentData::class);

$s = $db->select()
    ->setIntegrityCheck(false)
    ->from([
        'source' => $db->info($db::NAME)
    ],
        [
            'source.segmentId',
            'source.taskGuid',
            'source.segmentId',
            'source.original AS sourceOriginal',
            'source.taskGuid'
        ]
    )
    ->join(['target' => $db->info($db::NAME)], 'source.segmentId = target.segmentId', [
        'target.edited as targetEdited',
        'target.id as targetDataId'
    ])
    ->join(['ls' => 'LEK_segments'], 'source.segmentId = ls.id', [])
    ->join([
        'task' => 'LEK_task'
    ],
        'source.taskGuid = task.taskGuid',['emptyTargets']
    )
    ->where('target.edited REGEXP ?', '<div\\s*class="(open|close|single)\\s+([gxA-Fa-f0-9]*)[^"]*"\\s*.*?(?!</div>)<span[^>]*data-originalid="([^"]*).*?(?!</div>).</div>')
    ->where('ls.pretrans != 1')
    ->where('task.modified > "2023-06-01 00:00:00"')
    ->group('source.segmentId');

$result = $db->fetchAll($s)->toArray();

$tag = ZfExtended_Factory::get(editor_Models_Segment_InternalTag::class);

$reviewData = [];
$whiteSpacesData = [];
$replaceInfo = [];
$mappingData = [];

foreach ($result as $item) {

    if(!isset($mappingData[$item['targetDataId']])){
        $mappingData[$item['targetDataId']] = $item['targetEdited'];
    }

    $isReview = (bool) $item['emptyTargets'] === false;


    $source =  html_entity_decode($item['sourceOriginal'], ENT_QUOTES | ENT_XML1);
    $target =  html_entity_decode($item['targetEdited'], ENT_QUOTES | ENT_XML1);

    $sourceTags = $tag->getRealTags($source);
    $targetTags = $tag->getRealTags($target);

    $tagWhitespace = ZfExtended_Factory::get(editor_Models_Segment_Whitespace::class);
    $whiteSpaces = $tagWhitespace->get($item['targetEdited']);

    checkWhitespaces($whiteSpaces,$item['targetEdited']);

    $diff = $tag->diffArray($targetTags,$sourceTags);

    if(empty($diff)){
        continue;
    }

    if($isReview)
    {

        $tmpData = [];
        $tmpData['source'] = $source;
        $tmpData['target'] = $target;
        $tmpData['diff'] = $diff;

        if(!isset($reviewData[$item['taskGuid']])){
            $reviewData[$item['taskGuid']] = [];
            $reviewData[$item['taskGuid']][$item['segmentId']] = [];
        }
        $reviewData[$item['taskGuid']][$item['segmentId']][] = $tmpData;

        continue;
    }

    $sourceTags = $tag->getRealTags($item['sourceOriginal']);

    $targetTags = $tag->getRealTags($item['targetEdited']);


    $diff = $tag->diffArray($targetTags,$sourceTags);

    foreach ($diff as $brokenTag){

        $replace = findCorrectTag($sourceTags,$brokenTag);

        if(!empty($replace)){
            // this array contains the
            // id field in LEK_segment_data
            // the broken tag (what we should replace)
            // the correct tag (what we use for replace)
            $replaceInfo[$item['targetDataId']][] = [$brokenTag,$replace,$item['segmentId']];
        }
    }
}

/**
 * Collect the whitespaces where the css class has different content as expected
 * @param array $tags
 * @param string $target
 * @return void
 */
function checkWhitespaces(array $tags, string $target): void
{

    // collected invalid tags from the segment
    $invalid = [];

    foreach ($tags as $tag){
        $matches = null;
        preg_match_all(editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS, $tag, $matches);

        if(!empty($matches)){
            $class = $matches[2][0];
            $type = $matches[3][0];
            $decoded= pack('H*', $class);


            $typeInClass = explode(' ',$decoded);
            $typeInClass = $typeInClass[0] ?? '';
            $typeInClass = str_replace('/','',$typeInClass);

            if($type !== $typeInClass){
                $invalid[] = [
                    'regexMatches' => $matches,
                    'segment' => $target
                ];
            }
        }
    }

    if(!empty($invalid)){
        error_log('Wrong whitespace tags found:'.count($invalid));
    }
}

/**
 * This function will try to find the correct tag by searching for the tag number of the broken tag in the correctTags
 * @param array $correctTags
 * @param string $brokenTag
 * @return mixed|string
 */
function findCorrectTag(array $correctTags, string $brokenTag): mixed
{

    $pattern = '/<span class="short" title="([^"]*)">(.*?)<\/span>/i';

    preg_match($pattern, $brokenTag, $matches);

    if(empty($matches)){
        return '';
    }

    $shortTag = $matches[2];

    foreach ($correctTags as $tag){

        // generate the short tag regex for matching
        // in addition escape the forward slashes because in some cases they can not be correct
        $pattern = '/<span class="short" title="([^"]*)">'.str_replace('/','\/',$shortTag).'<\/span>/i';
        if( preg_match($pattern,$tag,$newMatch)){
            return $tag;
        }
    }

    return '';
}

if(!empty($reviewData)){
    error_log('Broken segments in review tasks where found and those will not be repaired automatically. Check '.APPLICATION_PATH.'/../data/logs/BrokenSegmentsInReviewTask.log file for more info');
    file_put_contents(APPLICATION_PATH.'/../data/logs/BrokenSegmentsInReviewTask.log', print_r($reviewData,true),FILE_APPEND);
}

if(!empty($whiteSpacesData)){
    error_log('Broken whitespaces where found and those will not be repaired automatically. Check '.APPLICATION_PATH.'/../data/logs/BrokenSegmentsInReviewTask.log file for more info');
    file_put_contents(APPLICATION_PATH.'/../data/logs/BrokenSegmentsInReviewTask.log', print_r($whiteSpacesData,true),FILE_APPEND);
}

if(file_exists(APPLICATION_PATH.'/../data/logs/BrokenSegmentsInReviewTask.log')){
    $config = \Zend_Registry::get('config');
    /* @var $config \Zend_Config */

    $mail = new \ZfExtended_Mailer('utf-8');
    $mail->setSubject('Translate5 TRANSLATE-3487 E-Mail - from '.$config->runtimeOptions->server->name);
    $mail->setBodyText('This is email for collected errors and debug output from TRANSLATE-3487');
    $mail->addTo('errors@translate5.net');
    $mail->createAttachment(
        file_get_contents(APPLICATION_PATH.'/../data/logs/BrokenSegmentsInReviewTask.log'),
        'text/plain',
        Zend_Mime::DISPOSITION_ATTACHMENT,
        Zend_Mime::ENCODING_BASE64,
        'BrokenSegmentsInReviewTask.log'
    );
    $mail->send();
}

error_log('Number of segments to fix:'.count($replaceInfo));

if(!empty($replaceInfo)){

    $updatedRows = 0;
    $viewsToDrop = [];
    $affectedTasks = [];

    $segment = ZfExtended_Factory::get(editor_Models_Segment::class);

    foreach ($replaceInfo as $id => $list){

        $targetEdit = $mappingData[$id];

        foreach ($list as $content) {
            $search = $content[0];
            $replace = $content[1];

            $targetEdit = str_replace($search,$replace,$targetEdit);
        }
        // save the last version of the segment in segment history
        $segment->load($content[2]);
        $history = $segment->getNewHistoryEntity();
        $history->save();

        $viewsToDrop[] = $segment->getTaskGuid();

        if(!isset($affectedTasks[$segment->getTaskGuid()])){

            $task = ZfExtended_Factory::get(editor_Models_Task::class);
            $task->loadByTaskGuid($segment->getTaskGuid());

            $affectedTasks[$segment->getTaskGuid()] = $task;
        }

        $task = $affectedTasks[$segment->getTaskGuid()];

        //CHECK TAGS
        $tags = new editor_Segment_FieldTags(
            $task,
            $segment->getId(),
            $targetEdit,
            'target',
            'targetEdit'
        );
        $tagComparision = new editor_Segment_Internal_TagComparision($tags, null);
        $stati =  $tagComparision->getStati();

        $runTagRepair =
            !empty($stati)
            &&
            array_key_exists(editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY,$stati);


        if($runTagRepair){

            //REPAIR TAGS
            $brokenTags = new editor_Segment_FieldTags(
                $task,
                $segment->getId(),
                $targetEdit,
                'target',
                'targetEdit'
            );
            $tagRepair = new editor_Segment_Internal_TagRepair($brokenTags, null);
            if($tagRepair->hadErrors()){
                $targetEdit = $brokenTags->render();
            }
        }

        $res = $db->getAdapter()->query('UPDATE LEK_segment_data SET edited = ?, editedToSort = ? WHERE id = ?;',[
            $targetEdit, // Update the new version of segment target edited
            strip_tags($targetEdit), // remove the tags from the targetEdit
            $id
        ]);

        $updatedRows += $res->rowCount();
    }

    error_log('Fixed segments:'.$updatedRows);

    if($updatedRows > 0 ){

        foreach ($viewsToDrop as $taskGuid){
            $view = ZfExtended_Factory::get(editor_Models_Segment_MaterializedView::class,[
                $taskGuid
            ]);
            $view->drop();
        }
    }
}