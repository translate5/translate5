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

use MittagQI\Translate5\Task\Export\FileParser\Xlf\Comments;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\MemoQ;

/**
 * XLF Fileparser Add On to parse MemoQ XLF specific stuff
 */
class editor_Models_Export_FileParser_Xlf_Namespaces_MemoQ
    extends editor_Models_Export_FileParser_Xlf_Namespaces_Abstract
{
    protected ?int $currentCommentsKey = null;

    public function __construct(editor_Models_Import_FileParser_XmlParser $xmlParser, Comments $comments)
    {
        parent::__construct($xmlParser, $comments);

        if ($comments->isEnabled()) {
            //currently only the comment export feature is implemented in the memoq XLF,
            // so if exporting comments is disabled we disable just the whole function
            $this->registerParserHandler();
        }
    }

    private function registerParserHandler(): void
    {
        $this->xmlparser->registerElement('trans-unit mq:comments', null, function ($tag, $key, $opener) {
            $this->currentCommentsKey = $key;
        });

        $this->xmlparser->registerElement('trans-unit', null, function ($tag, $key, $opener) {
            $commentString = $this->comments->getCommentXml(function (XMLWriter $xmlWriter, array $comment) {
                //ignore comments already imported via namespace
                if ($comment['userGuid'] == MemoQ::USERGUID) {
                    return;
                }
                $xmlWriter->startElement('mq:comment');
                $xmlWriter->writeAttribute('id', ZfExtended_Utils::uuid());
                $xmlWriter->writeAttribute('creatoruser', $comment['userName']);
                $xmlWriter->writeAttribute('time', gmdate('Y-m-d\TH:i:s\Z', strtotime($comment['modified'])));
                $xmlWriter->writeAttribute('deleted', 'false');
                $xmlWriter->writeAttribute('category', '0');
                $xmlWriter->writeAttribute('appliesto', 'Target');
                $xmlWriter->writeAttribute('origin', 'User');
                $xmlWriter->text($comment['comment']);
                $xmlWriter->endElement();
            });

            //if there is no <mq:comments> tag in the trans-unit we must create it, otherwise we have to reuse it
            if (empty($this->currentCommentsKey)) {
                $replacement = '<mq:comments>' . $commentString . '</mq:comments>' . $this->xmlparser->getChunk($key);
            } else {
                $key = $this->currentCommentsKey;
                $replacement = $commentString . $this->xmlparser->getChunk($key);
            }
            $this->xmlparser->replaceChunk($key, $replacement);

            $this->currentCommentsKey = null;
        });
    }
}
