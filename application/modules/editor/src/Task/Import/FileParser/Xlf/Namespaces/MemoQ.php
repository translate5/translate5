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

namespace MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces;

use editor_Models_Comment;
use editor_Models_Export_FileParser_Xlf_Namespaces_MemoQ;
use editor_Models_Import_FileParser_XmlParser as XmlParser;
use editor_Models_Segment_MatchRateType;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments;
use ZfExtended_Factory;
use editor_Models_Import_FileParser_SegmentAttributes as SegmentAttributes;

/**
 * XLF Fileparser Add On to parse MemoQ XLF specific stuff
 */
class MemoQ extends AbstractNamespace
{
    const MEMOQ_XLIFF_NAMESPACE = 'xmlns:mq="MQXliff"';
    const USERGUID = 'memoq-imported';

    public function __construct(protected XmlParser $xmlparser, protected Comments $comments)
    {
        parent::__construct($xmlparser, $comments);
        $this->registerParserHandler();
    }

    protected function registerParserHandler(): void
    {
        $memoqMqmTag = 'trans-unit > target > mrk[mtype=x-mq-range], ';
        $memoqMqmTag .= 'trans-unit > source > mrk[mtype=x-mq-range], ';
        $memoqMqmTag .= 'trans-unit > seg-source > mrk[mtype=x-mq-range]';
        $this->xmlparser->registerElement($memoqMqmTag, function ($tag, $attributes, $key) {
            $this->xmlparser->replaceChunk($key, '');
        }, function ($tag, $key, $opener) {
            $this->xmlparser->replaceChunk($key, '');
        });

        $this->xmlparser->registerElement(
            'trans-unit mq:comments mq:comment',
            null,
            function ($tag, $key, $opener) {
                $attr = $opener['attributes'];

                //if the comment is marked as deleted or is empty (a single attribute), we just do not import it
                if ($opener['isSingle'] || (!empty($attr['deleted']) && strtolower($attr['deleted']) == 'true')) {
                    return;
                }

                $comment = ZfExtended_Factory::get(editor_Models_Comment::class);

                $startText = $opener['openerKey'] + 1;
                $length = $key - $startText;
                $commentText = join($this->xmlparser->getChunks($startText, $length));

                $comment->setComment($commentText);

                $comment->setUserName($attr['creatoruser'] ?? 'no user');
                $comment->setUserGuid(self::USERGUID);

                $date = date('Y-m-d H:i:s', strtotime($attr['time'] ?? 'now'));
                $comment->setCreated($date);
                $comment->setModified($date);
                $this->comments->add($comment);
            }
        );
    }

    public static function isApplicable(string $xliff): bool
    {
        return str_contains($xliff, self::MEMOQ_XLIFF_NAMESPACE);
    }

    public static function getExportCls(): ?string
    {
        return editor_Models_Export_FileParser_Xlf_Namespaces_MemoQ::class;
    }

    /**
     * This method was implemented, but finally never approved by the client: TS-1292
     * - therefore its prefixed with DISABLED since it would in use by just naming it transunitAttributes
     */
    public function DISABLEDtransunitAttributes(array $attributes, SegmentAttributes $segmentAttributes): void
    {
        $status = strtolower($attributes['mq:status'] ?? '');
        switch ($status) {
            case 'notstarted':
                $segmentAttributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_EMPTY;
                break;
            case 'manuallyconfirmed':
            case 'partiallyedited':
                $segmentAttributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_INTERACTIVE;
                break;
            case 'pretranslated':
                $segmentAttributes->isPreTranslated = true;
                $segmentAttributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_TM;
                break;
            case 'machinetranslated':
                $segmentAttributes->isPreTranslated = true;
                $segmentAttributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_MT;
                if (!empty($attributes['mq:translatorcommitmatchrate'])) {
                    $segmentAttributes->matchRate = $attributes['mq:translatorcommitmatchrate'];
                }
                break;
            default:
                break;
        }
        if (!empty($attributes['mq:percent'])) {
            $segmentAttributes->matchRate = $attributes['mq:percent'];
        }
        if (!empty($attributes['mq:locked'])) {
            $segmentAttributes->locked = true;
        }
    }

    /**
     * Translate5 uses x,g and bx ex tags only. So the whole content of the tags incl. the tags must be used.
     * {@inheritDoc}
     * @see AbstractNamespace::useTagContentOnly()
     */
    public function useTagContentOnly(): ?bool
    {
        //FIXME should be calculated for memoQ. If content will result in {} only or was a single tag,
        // then it should be true, otherwise false. Also for across XLF
        return false;
    }
}
