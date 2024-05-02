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
use editor_Models_Export_FileParser_Xlf_Namespaces_Across;
use editor_Models_Import_FileParser_Xlf_ContentConverter as OrigContentConverter;
use editor_Models_Import_FileParser_XmlParser as XmlParser;
use editor_Models_Task;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments;
use ZfExtended_Factory;

/**
 * XLF Fileparser Add On to parse Across XLF specific stuff
 */
class Across extends AbstractNamespace
{
    public const ACROSS_XLIFF_NAMESPACE = 'xmlns:ax="AcrossXliff"';

    public const USERGUID = 'across-imported';

    private ?editor_Models_Comment $currentComment;

    public function __construct(
        XmlParser $xmlparser,
        protected Comments $comments
    ) {
        parent::__construct($xmlparser, $comments);
        $this->registerParserHandler();
    }

    protected function registerParserHandler(): void
    {
        $this->currentComment = null;

        $this->xmlparser->registerElement(
            'trans-unit ax:named-property',
            function ($tag, $attributes) {
                if ($attributes['name'] == 'Comment') {
                    $this->currentComment = ZfExtended_Factory::get(editor_Models_Comment::class);
                }
            },
            function ($tag, $key, $opener) {
                if (! $opener['attributes']['name'] == 'Comment') {
                    return;
                }
                $title = '';
                if (! empty($this->currentComment->across_title)) {
                    $title .= 'Title: ' . $this->currentComment->across_title;
                }
                if (! empty($title)) {
                    $title .= "\n";
                }
                $this->currentComment->setComment($title . $this->currentComment->getComment());
                $this->comments->add($this->currentComment);
            }
        );

        $this->xmlparser->registerElement(
            'trans-unit ax:named-property ax:named-value',
            null,
            function ($tag, $key, $opener) {
                $name = strtolower($opener['attributes']['ax:name']);
                if ($opener['isSingle']) {
                    return; //do nothing here, since the named-value is empty
                }
                $startText = $opener['openerKey'] + 1;
                $length = $key - $startText;
                $value = join($this->xmlparser->getChunks($startText, $length));
                switch ($name) {
                    case 'annotates':
                        $this->currentComment->across_annotates = $value;

                        break;
                    case 'author':
                        $this->currentComment->setUserName($value);
                        $this->currentComment->setUserGuid(self::USERGUID);

                        break;
                    case 'text':
                        $this->currentComment->setComment($value);

                        break;
                    case 'created':
                        $value = date('Y-m-d H:i:s', strtotime($value));
                        $this->currentComment->setCreated($value);
                        $this->currentComment->setModified($value);

                        break;
                    case 'title':
                        $this->currentComment->across_title = $value;

                        break;
                    default:
                        //set nothing here
                        break;
                }
            }
        );
    }

    public static function isApplicable(string $xliff): bool
    {
        return str_contains($xliff, self::ACROSS_XLIFF_NAMESPACE);
    }

    public static function getExportCls(): ?string
    {
        return editor_Models_Export_FileParser_Xlf_Namespaces_Across::class;
    }

    /**
     * In Across the complete tag content must be used
     * {@inheritDoc}
     * @see AbstractNamespace::useTagContentOnly()
     */
    public function useTagContentOnly(): ?bool
    {
        return false;
    }

    public function getContentConverter(editor_Models_Task $task, string $filename): OrigContentConverter
    {
        return ZfExtended_Factory::get(Across\ContentConverter::class, [
            $this,
            $task,
            $filename,
        ]);
    }
}
