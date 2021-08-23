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
 * exports term data stored in translate5 to valid TBX files
 */
class editor_Models_Export_Terminology_Tbx {
    /**
     * @var Zend_Db_Table_Rowset_Abstract|null
     */
    protected Zend_Db_Table_Rowset_Abstract $data;

    /**
     * Holds the XML Tree
     * @var SimpleXMLElement
     */
    protected SimpleXMLElement $tbx;

    /**
     * @var string
     */
    protected string $target = '';

    /**
     * @var array
     */
    protected array $languageCache = [];

    /**
     * @var array
     */
    protected array $statusMap;

    /**
     * Counter for number of tigs on create tbx while export.
     * Needed to generate tig-id attribute in tbx-xml
     * @var integer
     */
    protected int $counterTig = 0;

    public function __construct()
    {
        $tbxImport = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $tbxImport editor_Models_Import_TermListParser_Tbx */
        $this->statusMap = array_flip($tbxImport->getStatusMap());
    }

    /**
     * Sets the Terminology data to be processed
     * Data must already be sorted by: groupId, language, id
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * sets the target where the data should be exported to
     * expects a TBX filename
     * @param string $target
     */
    public function setTarget(string $target)
    {
        $this->target = $target;
    }

    /**
     * creates the TBX Element and returns the body node to add data
     * @return SimpleXMLElement
     */
    protected function createTbx(): SimpleXMLElement
    {
        $this->tbx = new SimpleXMLElement('<martif/>');
        $this->tbx->addAttribute('noNamespaceSchemaLocation', 'TBXcsV02.xsd');
        $this->tbx->addAttribute('type', 'TBX');
        $this->tbx->addAttribute('TBX', 'en');
        $head = $this->tbx->addChild('martifHeader');
        $fileDesc = $head->addChild('fileDesc');
        $sourceDesc = $fileDesc->addChild('sourceDesc');
        $sourceDesc->addChild('p', 'TBX recovered from Translate5 DB');
        $text = $this->tbx->addChild('text');

        return $text->addChild('body');
    }

    /**
     * TODO: add the term attributes and term entry attributes
     * exports the internally stored data
     * @return string the generated data
     */
    public function export(): string
    {
        $body = $this->createTbx();

        //we assume that we got the data already sorted from DB
        $oldTermEntry = '';
        $oldLanguage = 0;
        foreach($this->data as $row) {
            if($oldTermEntry != $row->termEntryTbxId) {
                $termEntry = $body->addChild('termEntry');
                $termEntry->addAttribute('id', $row->termEntryTbxId);
                $oldTermEntry = $row->termEntryTbxId;
            }
            if($oldLanguage != $row->languageId) {
                $langSet = $termEntry->addChild('langSet');
                $langSet->addAttribute('lang', $this->getLanguage($row->languageId));
                $oldLanguage = $row->languageId;
            }
            $tig = $langSet->addChild('tig');
            if (isset($row->tigId)) {
                $tigId = $row->tigId;
            }
            if (empty($tigId)) {
                $tigId = $this->convertToTigId($row->termTbxId);
            }

            $tig->addAttribute('id', $tigId);

            $term = $tig->addChild('term', $row->term);
            $term->addAttribute('id', $row->termTbxId);

            $termNote = $tig->addChild('termNote', $row->status); //FIXME Status gemapped???
            $termNote->addAttribute('type', 'normativeAuthorization');
        }
        //SimpleXML throws an error when giving null, so we need this workaround:
        if (empty($this->target) && $this->target !== '0') {
            return $this->tbx->asXML();
        }

        return $this->tbx->asXML($this->target);
    }

    /**
     * returns the Rfc5646 language code to the given language id
     * @param int $langId
     * @return string
     */
    protected function getLanguage(int $langId): string
    {
        if (empty($this->languageCache[$langId])) {
            $lang = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $lang editor_Models_Languages */
            $lang->load($langId);
            $this->languageCache[$langId] = $lang->getRfc5646();
        }
        return $this->languageCache[$langId];
    }

    /**
     * reverts the status mapping of the TBX Import
     * @param string $status
     * @return string
     */
    protected function getStatus(string $status): string
    {
        if (empty($this->statusMap[$status])) {
            $default = $this->statusMap[editor_Models_Terminology_TbxObjects_Term::STAT_STANDARDIZED];
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError('Error on TBX creation, missing term status "'.$status.'", set to "'.$default.'" in file '.$this->target);

            return $default;
        }

        return $this->statusMap[$status];
    }

    /**
     * converts the given mid to a tig id
     * @param string $mid
     * @return string
     */
    protected function convertToTigId(string $mid): string
    {
        if (strpos($mid, 'term_') === false) {
            return 'tig_'.$mid;
        }

        // check if mid (aka term-Id) is autogenerated
        $midParts = explode('_', $mid);
        if (count($midParts) < 6) {
            // if not autogenerated..
            return str_replace('term', 'tig', $mid);
        }

        // if mid (aka term-Id) is autogenerated, generate tig-Id from splitted parts
        $this->counterTig += 1;
        $tempReturn =   'tig_'.$midParts[1]
                        .'_'.$midParts[2]
                        .'_'.str_pad($this->counterTig, 7, '0', STR_PAD_LEFT)
                        .'_'.$midParts[3];

        return $tempReturn;
    }
}
