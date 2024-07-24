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

use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\Terminology\SearchCollection;

class editor_Services_TermCollection_Connector extends editor_Services_Connector_Abstract implements FileBasedInterface
{
    /**
     * If the query for the term had tags, the match rate must be less then 100% so that the user has to fix the tags
     * @var integer
     */
    public const TERMCOLLECTION_TAG_MATCH_VALUE = 99;

    public function __construct()
    {
        parent::__construct();
        //the translations from the term collections are with high priority, that is why 104 (this is the highest matchrate in translate5)
        $this->defaultMatchRate = self::TERMCOLLECTION_MATCH_VALUE;
    }

    /**
     * Set the source and the target langauge with the same value as the requested search langauges values.
     * Later the SearchCollection class filters the languages using the fuzzy logic.
     */
    protected function setServiceLanguages(?int $sourceLang, ?int $targetLang): void
    {
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
    }

    public function addTm(array $fileinfo = null, array $params = null): bool
    {
        if (empty($fileinfo)) {
            //empty term collection
            return true;
        }

        // check and prepare the import files (handle the zip)
        $fileinfo = $this->prepareImportFiles($fileinfo);

        if (empty($fileinfo)) {
            return false;
        }

        $import = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $import editor_Models_Import_TermListParser_Tbx */

        $import->mergeTerms = isset($params['mergeTerms']) ? filter_var($params['mergeTerms'], FILTER_VALIDATE_BOOLEAN) : false;

        $userGuid = $params['userGuid'] ?? ZfExtended_Authentication::getInstance()->getUserGuid();
        $import->loadUser($userGuid);

        //import the term collection
        if (! $import->parseTbxFile($fileinfo, (string) $this->languageResource->getId())) {
            $this->logger->error('E1321', 'Term Collection Import: Errors on parsing the TBX, the file could not be imported or contains no term entries.');

            return false;
        }

        // run all post import functions
        $this->doAfterImport($params);

        return true;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment)
    {
        $qs = $this->getQueryStringAndSetAsDefault($segment);

        return $this->queryCollectionResults($this->tagHandler->prepareQuery($qs), true);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = SearchCollection::SEARCH_SOURCE, $offset = null)
    {
        return $this->queryCollectionResults($searchString, false, $field, true);
    }

    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString)
    {
        return $this->queryCollectionResults($searchString);
    }

    /***
     * Search the terms in the term collection with the given query string
     * @param string $queryString
     * @param bool $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @param string $field optional, the field where the search will be performed
     * @return editor_Services_ServiceResult
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function queryCollectionResults(
        string $queryString,
        bool $reimportWhitespace = false,
        string $field = SearchCollection::SEARCH_SOURCE,
        bool $useWildcards = false
    ) {
        if (empty($queryString) && $queryString !== '0') {
            return $this->resultList;
        }
        $entity = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);
        $entity->load($this->languageResource->getId());

        /***
         * Search the current term collection with given query string. All fuzzy languages will be included in the
         * search.('en' as search language will result with search using 'en','en-US','en-GB' etc.)
         * Result will be listed only if there is matching term in the opposite language:
         * Example if there is a match for term in source(de), and in the same term entry,
         * there is term in the opposite language(en), than this
         */
        $searchCollection = ZfExtended_Factory::get(SearchCollection::class, [
            $entity->getId(),
            $this->sourceLang,
            $this->targetLang,
        ]);
        $searchCollection->setSearchField($field);
        $results = $searchCollection->search($queryString, $useWildcards);

        //load all available languages, so we can set the term rfc value to the frontend
        $langModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs = $langModel->loadAllKeyValueCustom('id', 'rfc5646');

        $groupids = array_column($results, 'termEntryId');
        $groupids = array_unique($groupids);

        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        $definitions = $term->getDeffinitionsByEntryIds($groupids);

        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        $groups = $term->sortTerms([$results]);
        foreach ($groups as $group) {
            foreach ($group as $res) {
                //add all available definitions from the term termEntry
                if (isset($definitions[$res['termEntryId']])) {
                    $res['definitions'] = $definitions[$res['termEntryId']];
                }
                //convert back to array
                $matchRate = ($this->tagHandler->getRealTagCount() > 0) ?
                    self::TERMCOLLECTION_TAG_MATCH_VALUE : $this->defaultMatchRate;
                if ($reimportWhitespace) {
                    $res['term'] = $this->tagHandler->restoreInResult($res['term']);
                }
                if (isset($res['language'])) {
                    $res['languageRfc'] = $lngs[$res['languageId']] ?? null;
                }
                //set the default source and the result depending on where the search is triggered
                $this->resultList->setDefaultSource($field === SearchCollection::SEARCH_SOURCE
                    ? $res['default' . $field] : $res['term']);

                $this->resultList->addResult(
                    $field === SearchCollection::SEARCH_SOURCE ? $res['term'] : $res['default' . $field],
                    $matchRate,
                    $res
                );
            }
        }

        return $this->resultList;
    }

    /**
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(
        editor_Models_LanguageResources_Resource $resource,
        editor_Models_LanguageResources_LanguageResource $languageResource = null
    ): string {
        // is may injected with the call
        if (! empty($languageResource)) {
            $this->languageResource = $languageResource;
        }

        if (! isset($this->languageResource)) {
            //this should come from the resource status check in the resources api request
            return self::STATUS_AVAILABLE;
        }
        $status = $this->languageResource->getStatus();
        if (empty($status)) {
            return self::STATUS_AVAILABLE;
        }

        return $status;
    }

    /***
     * Run post import functions.
     * @param array $params
     */
    protected function doAfterImport(array $params)
    {
        $termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $termModel editor_Models_Terminology_Models_TermModel */
        $collection = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $collection editor_Models_TermCollection_TermCollection */
        $validator = new Zend_Validate_Date();
        $validator->setFormat('Y-m-d H:i:s');

        //delete collection term entries older than the parameter date
        if (isset($params['deleteTermsLastTouchedOlderThan']) && ! empty($params['deleteTermsLastTouchedOlderThan'])) {
            if (! $validator->isValid($params['deleteTermsLastTouchedOlderThan'])) {
                $params['deleteTermsLastTouchedOlderThan'] = date('Y-m-d H:i:s', strtotime($params['deleteTermsLastTouchedOlderThan']));
            }
            $termModel->removeOldTerms([$this->languageResource->getId()], $params['deleteTermsLastTouchedOlderThan']);
            //clean the old tbx files from the disc
            $collection->removeOldCollectionTbxFiles($this->languageResource->getId(), strtotime($params['deleteTermsLastTouchedOlderThan']));
        }

        $deleteOlderThanCurrentImport = isset($params['deleteTermsOlderThanCurrentImport']) && filter_var($params['deleteTermsOlderThanCurrentImport'], FILTER_VALIDATE_BOOLEAN);
        //delete termcollection terms older then current import date
        if ($deleteOlderThanCurrentImport) {
            $termModel->removeOldTerms([$this->languageResource->getId()], NOW_ISO);
            //clean the old tbx files from the disc
            $collection->removeOldCollectionTbxFiles($this->languageResource->getId(), strtotime(NOW_ISO));
        }

        //check if the delete proposal older than date is set
        $deleteProposalsDate = null;
        if (! empty($params['deleteProposalsLastTouchedOlderThan']) && ! $validator->isValid($params['deleteProposalsLastTouchedOlderThan'])) {
            //the date is set but it is not in the required format
            $deleteProposalsDate = date('Y-m-d H:i:s', strtotime($params['deleteProposalsLastTouchedOlderThan']));
        }

        //the delet proposals older than the current import is set, use the now_iso as reference date
        $deleteProposalsOlderThanCurrentImport = isset($params['deleteProposalsOlderThanCurrentImport']) && filter_var($params['deleteProposalsOlderThanCurrentImport'], FILTER_VALIDATE_BOOLEAN);
        if (empty($deleteProposalsDate) && $deleteProposalsOlderThanCurrentImport) {
            $deleteProposalsDate = NOW_ISO;
        }

        //delete term proposals
        if (! empty($deleteProposalsDate)) {
            // Remove term proposals
            $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
            /* @var $term editor_Models_Terminology_Models_TermModel */
            $term->removeProposalsOlderThan([$this->languageResource->getId()], $deleteProposalsDate);

            // Remove attribute proposals
            $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
            /* @var $attribute editor_Models_Terminology_Models_AttributeModel */
            $attribute->removeProposalsOlderThan([$this->languageResource->getId()], $deleteProposalsDate);
        }

        //remove all empty term entries from the same term collection
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntry->removeEmptyFromCollection([$this->languageResource->getId()]);
    }

    /***
     *
     * @param array $fileInfo
     * @return array|array[]
     */
    protected function prepareImportFiles(array $fileInfo): array
    {
        $validator = new Zend_Validate_File_IsCompressed();
        if (! $validator->isValid($fileInfo['tmp_name'])) {
            return [$fileInfo];
        }

        $zip = new ZipArchive();
        if (! $zip->open($fileInfo['tmp_name'])) {
            // Zip file could not be opened
            $this->logger->error('E1358', 'Term Collection Import: Unable to open zip file from file-path:' . $fileInfo['tmp_name']);

            return [];
        }

        $filename = pathinfo($fileInfo['name'], PATHINFO_FILENAME);

        $newPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        if (! $zip->extractTo($newPath)) {
            //Content from zip file could not be extracted
            $this->logger->error('E1359', 'Term Collection Import: Content from zip file could not be extracted.');

            return [];
        }
        $zip->close();

        $newFileInfo = [];

        // list all extracted tbx files from the location
        foreach (editor_Utils::generatePermutations('tbx') as $pattern) {
            $list = glob($newPath . DIRECTORY_SEPARATOR . '*.' . implode($pattern));

            foreach ($list as $item) {
                $newFileInfo[] = [
                    'tmp_name' => $item,
                    'name' => basename($item),
                ];
            }
        }

        return $newFileInfo;
    }

    /**
     * {@inheritDoc}
     */
    public function getValidFiletypes()
    {
        return [
            'ZIP' => ['application/zip'],
            'TBX' => ['application/xml', 'text/xml'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getValidExportTypes()
    {
        return [
            'TBX' => 'application/xml',
        ];
    }

    /***
     * Add/parce tbx file to the exsisting termcollection
     *
     * {@inheritDoc}
     */
    public function addAdditionalTm(array $fileinfo = null, array $params = null)
    {
        return $this->addTm($fileinfo, $params);
    }

    public function getTm($mime)
    {
    }

    public function delete()
    {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
}
