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


/**
 * XLF Fileparser Add On to parse Across XLF specific stuff
 *
 * TODO This class is a draft!
 */
class editor_Models_Import_FileParser_Xlf_Namespaces_Across extends editor_Models_Import_FileParser_Xlf_Namespaces_Abstract{
    const ACROSS_XLIFF_NAMESPACE = 'xmlns:ax="AcrossXliff"';
    const USERGUID = 'across-imported';

    /**
     * @var array
     */
    protected $comments = [];

    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::registerParserHandler()
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        $currentComment = null;

        $xmlparser->registerElement('trans-unit ax:named-property', function($tag, $attributes) use (&$currentComment){
            if($attributes['name'] == 'Comment') {
                $currentComment = ZfExtended_Factory::get('editor_Models_Comment');
            }
            /* @var $currentComment editor_Models_Comment */
        }, function($tag, $key, $opener) use ($xmlparser, &$currentComment){
            if(!$opener['attributes']['name'] == 'Comment') {
                return;
            }
            $title = '';
            if(!empty($currentComment->across_title)) {
                $title .= 'Title: '.$currentComment->across_title.'';
            }
            if(!empty($title)) {
                $title .= "\n";
            }
            $currentComment->setComment($title.$currentComment->getComment());
            $this->comments[] = $currentComment;
        });
        $xmlparser->registerElement('trans-unit ax:named-property ax:named-value', null, function($tag, $key, $opener) use (&$currentComment, $xmlparser){
            $name = strtolower($opener['attributes']['ax:name']);
            if($opener['isSingle']) {
                return; //do nothing here, since the named-value is empty
            }
            $startText = $opener['openerKey'] + 1;
            $length = $key - $startText;
            $value = join($xmlparser->getChunks($startText, $length));
            switch ($name) {
                case 'annotates':
                    $currentComment->across_annotates = $value;
                    break;
                case 'author':
                    $currentComment->setUserName($value);
                    $currentComment->setUserGuid(self::USERGUID);
                    break;
                case 'text':
                    $currentComment->setComment($value);
                    break;
                case 'created':
                    $value = date('Y-m-d H:i:s', strtotime($value));
                    $currentComment->setCreated($value);
                    $currentComment->setModified($value);
                    break;
                case 'title':
                    $currentComment->across_title = $value;
                    break;
            }
        });
    }

    /**
     * In Across the complete tag content must be used
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::useTagContentOnly()
     */
    public function useTagContentOnly() {
        return false;
    }

    /**
     * After fetching the comments, the internal comments fetcher is resetted (if comments are inside MRKs and not the whole segment)
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser_Xlf_Namespaces_Abstract::getComments()
     */
    public function getComments() {
        $comments = $this->comments;
        $this->comments = [];
        return $comments;
    }
}
