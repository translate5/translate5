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

class editor_Services_Google_Resource extends editor_Models_LanguageResources_Resource
{
    /**
     * Resource Project ID
     * @var string
     */
    protected $projectId;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->filebased = false; //forced to be no filebased
        $this->searchable = false; //forced to be non searchable (concordance search)
        $this->writable = false; //forced to be non writeable
        $this->analysable = true; //is used by match analysis
        $this->type = editor_Models_Segment_MatchRateType::TYPE_MT;

        $config = Zend_Registry::get('config');

        $this->authKey = $config->runtimeOptions->LanguageResources->google->apiKey ?? null;
        $this->projectId = $config->runtimeOptions->LanguageResources->google->projectId ?? null;
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    /***
     * Check if the valid resource language is valid for the api
     * {@inheritDoc}
     * @see editor_Models_LanguageResources_Resource::hasSourceLang()
     */
    public function hasSourceLang(editor_Models_Languages $sourceLang)
    {
        $api = ZfExtended_Factory::get('editor_Services_Google_ApiWrapper', [$this]);

        /* @var $api editor_Services_Google_ApiWrapper */
        return $api->isValidLanguage($sourceLang->getRfc5646());
    }

    /***
     * Check if the valid resource language is valid for the api
     * {@inheritDoc}
     * @see editor_Models_LanguageResources_Resource::hasTargetLang()
     */
    public function hasTargetLang(editor_Models_Languages $targetLang)
    {
        $api = ZfExtended_Factory::get('editor_Services_Google_ApiWrapper', [$this]);

        /* @var $api editor_Services_Google_ApiWrapper */
        return $api->isValidLanguage($targetLang->getRfc5646());
    }
}
