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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

use MittagQI\Translate5\Task\Export\FileParser\Xlf\Comments;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Across;


/**
 * XLF Fileparser Add On to parse Across XLF specific stuff
 */
class editor_Models_Export_FileParser_Xlf_Namespaces_Across
    extends editor_Models_Export_FileParser_Xlf_Namespaces_Abstract
{
    protected ?int $currentPropertiesKey = null;
    protected ?int $currentErrorInfosKey = null;

    public function __construct(editor_Models_Import_FileParser_XmlParser $xmlParser, Comments $comments)
    {
        parent::__construct($xmlParser, $comments);

        if ($comments->isEnabled()) {
            //currently only the comment export feature is implemented in the across XLF,
            // so if exporting comments is disabled we disable just the whole function
            $this->registerParserHandler();
        }
    }

    private function registerParserHandler(): void
    {
        $this->xmlparser->registerElement('trans-unit ax:named-properties', null, function ($tag, $key) {
            $this->currentPropertiesKey = $key;
        });
        $this->xmlparser->registerElement('trans-unit ax:analysisResult ax:errorInfos', null, function ($tag, $key) {
            $this->currentErrorInfosKey = $key;
        });

        $this->xmlparser->registerElement('trans-unit', null, function ($tag, $key, $opener) {
            /*
            The following code adds translate5 comments as reals across comments.
            The reading of such comments is not implemented in across yet.
            So we disable it here and add the comments as analysis result
            $commentString = $this->comments->processComments(function(XMLWriter $xmlWriter, array $comment){
                //comments already imported from across are ignored
                if($comment['userGuid'] == Across::USERGUID) {
                    return;
                }
                $xmlWriter->startElement('ax:named-property');
                $xmlWriter->writeAttribute('name', 'Comment');
                $this->addNamedValue($xmlWriter, 'Author', $comment['userName']);
                $this->addNamedValue($xmlWriter, 'Created', date('m/d/Y H:i:s', strtotime($comment['modified'])));
                $this->addNamedValue($xmlWriter, 'Annotates', 'General');
                $this->addNamedValue($xmlWriter, 'Title', 'exported from translate5');
                $this->addNamedValue($xmlWriter, 'Text', $comment['comment']);
                $xmlWriter->endElement();
            });
            if (empty($this->currentPropertiesKey)) {
                $replacement = '<ax:named-properties>'.$commentString.
                    '</ax:named-properties>'.$this->xmlparser->getChunk($key);
            } else {
                $key = $this->currentPropertiesKey;
                $replacement = $commentString.$this->xmlparser->getChunk($key);
            }
            $xmlparser->replaceChunk($key, $replacement);
            */

            $keyToReplace = $this->currentErrorInfosKey;
            $this->currentPropertiesKey = null;
            $this->currentErrorInfosKey = null;

            $commentString = $this->comments->getCommentXml(function (XMLWriter $xmlWriter, array $comment) {
                //comments already imported from across are ignored:
                if ($comment['userGuid'] == Across::USERGUID) {
                    return;
                }
                $xmlWriter->startElement('ax:errorInfo');
                $xmlWriter->startElement('ax:description');

                $xmlWriter->startElement('ax:type');
                $xmlWriter->text('Comment');
                $xmlWriter->endElement();
                $xmlWriter->startElement('ax:title');
                $date = date('m/d/Y H:i:s', strtotime($comment['modified']));
                $xmlWriter->text('Comment from ' . $comment['userName'] . ' (' . $date . ') in translate5');
                $xmlWriter->endElement();
                $xmlWriter->startElement('ax:explanation');
                $xmlWriter->text($comment['comment']);
                $xmlWriter->endElement();
                $xmlWriter->startElement('ax:instruction');
                $xmlWriter->endElement();
                $xmlWriter->startElement('ax:examples');
                $xmlWriter->endElement();

                $xmlWriter->endElement(); //end ax:description
                $xmlWriter->endElement(); //end ax:errorInfo
            });
            if (empty($commentString) && $commentString !== '0') {
                return;
            }
            if (empty($keyToReplace)) {
                $keyToReplace = $key; //inject analysisResult before transunit
                $replacement = '<ax:analysisResult><ax:errorInfos>' . $commentString .
                    '</ax:errorInfos></ax:analysisResult>' . $this->xmlparser->getChunk($key);
            } else {
                //inject content before </ax:errorInfos>
                $replacement = $commentString . $this->xmlparser->getChunk($keyToReplace);
            }
            $this->xmlparser->replaceChunk($keyToReplace, $replacement);
        });
    }

    /**
     * creates a named value entry
     * @param XMLWriter $xmlWriter
     * @param string $name
     * @param string $value
     */
    protected function addNamedValue(XMLWriter $xmlWriter, string $name, string $value): void
    {
        $xmlWriter->startElement('ax:named-value');
        $xmlWriter->writeAttribute('ax:name', $name);
        $xmlWriter->text($value);
        $xmlWriter->endElement();
    }
}
