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

class editor_Services_Microsoft_Resource extends editor_Models_LanguageResources_Resource
{
    /**
     * Microsoft Azure location configuration (for example north europe etc.pp.)
     * @var string
     */
    protected $location;

    protected $validLanguages = null;

    /**
     * The languages defined in the key will be mapped to the value when requesting api translations
     */
    private array $internalLangaugeMap = [
        'sr' => 'sr-latn',
        'sr-Cyrl' => 'sr-cyrl',
        'sr-BA' => 'sr-latn',
        'sr-Cyrl-BA' => 'sr-cyrl',
        'sr-SP' => 'sr-latn',
        'sr-Cyrl-SP' => 'sr-cyrl',
        'sr-Cyrl-RS' => 'sr-cyrl',
        'sr-RS' => 'sr-latn',
        'sr-Latn-RS' => 'sr-latn',
        'sr-Latn-ME' => 'sr-latn',
    ];

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

        $this->authKey = $config->runtimeOptions->LanguageResources->microsoft->apiKey ?? null;
        $this->url = $config->runtimeOptions->LanguageResources->microsoft->apiUrl ?? null;
        $this->location = $config->runtimeOptions->LanguageResources->microsoft->apiLocation ?? null;
    }

    /**
     * returns the configured azure location
     */
    public function getLocation(): string
    {
        return (string) $this->location;
    }

    /**
     * Check and return the correct language code for the given rfc5646 code
     */
    public function getMircosoftCode(string $rfc5646): string
    {
        $rfc5646 = strtolower($rfc5646);
        if (isset($this->internalLangaugeMap[$rfc5646])) {
            return $this->internalLangaugeMap[$rfc5646];
        }

        return $rfc5646;
    }

    /***
     * Get the langauge code for the given langauge id. By default, the language code for the langauge is the rfc value.
     * Override this method in the child resources if different language code is needed
     * @param int $languageId
     * @return string
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function getLanguageCode(int $languageId): string
    {
        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $language->load($languageId);

        return $this->getMircosoftCode($language->getRfc5646());
    }

    /**
     * Check if the valid resource language is valid for the api.
     * {@inheritDoc}
     * @throws ReflectionException
     * @see editor_Models_LanguageResources_Resource::hasSourceLang()
     */
    public function hasSourceLang(editor_Models_Languages $sourceLang): bool
    {
        return $this->isValidLanguage($sourceLang->getRfc5646());
    }

    /**
     * Check if the valid resource language is valid for the api
     * {@inheritDoc}
     * @throws ReflectionException
     * @see editor_Models_LanguageResources_Resource::hasTargetLang()
     */
    public function hasTargetLang(editor_Models_Languages $targetLang): bool
    {
        return $this->isValidLanguage($targetLang->getRfc5646());
    }

    /**
     * checks if the given language RFC5646 value is valid for this resource
     * @return boolean
     * @throws ReflectionException
     */
    protected function isValidLanguage(string $rfc5646): bool
    {
        if (is_null($this->validLanguages)) {
            $api = ZfExtended_Factory::get('editor_Services_Microsoft_HttpApi', [$this]);
            /* @var $api editor_Services_Microsoft_HttpApi */
            $this->validLanguages = array_map('strtolower', $api->getLanguages() ?? []);
        }

        return in_array(
            $this->getMircosoftCode($rfc5646),
            $this->validLanguages
        );
    }
}
