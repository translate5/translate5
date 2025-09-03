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

use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;

class editor_Models_LanguageResources_Resource
{
    public const STRIP_FRAMING_TAGS_VALUES = 'values';

    public const FILE_EXTENSIONS = 'fileExtensions';

    /**
     * name of the resource
     * @var string
     */
    protected $name;

    /**
     * Match Type in the sense of the matchrate type
     * @var string
     */
    protected $type = editor_Models_Segment_MatchRateType::TYPE_TM;

    /**
     * Flag if this resource is filebased or not
     * service can set this flag as it needs it. for the case if some new services added in the future
     * can have filebased resources and non filebased ones at the same time
     * Must be overridden by class extension
     * @var boolean
     */
    protected $filebased = true;

    /**
     * Flag if this resource can be triggered for search requests
     * Must be overridden by class extension
     * @var boolean
     */
    protected $searchable = true;

    /**
     * Flag if edited matches can be saved back to this resource
     * Must be overridden by class extension
     * @var boolean
     */
    protected $writable = true;

    protected bool $deletable = true;

    /***
     * Flag if the resource can be used by match analysis
     *
     * @var string
     */
    protected $analysable = true;

    protected $service;

    protected $serviceName;

    protected bool $supportsStrippingFramingTags = false;

    protected bool $supportsResegmentation = false;

    /**
     * index is the fieldname for export values in the controller
     * value is the internal fieldname / getter
     */
    private array $fieldsForController = [
        'id' => 'id',
        'name' => 'name',
        'serviceName' => 'service',
        'serviceType' => 'serviceType',
        'resourceType' => 'type',
        'filebased' => 'filebased',
        'searchable' => 'searchable',
        'writable' => 'writable',
        'defaultColor' => 'defaultColor',
        'creatable' => 'creatable',
        'engineBased' => 'engineBased',
        'useEnginesCombo' => 'useEnginesCombo',
        'domainCodePreset' => 'domainCodePreset',
        'supportsStrippingFramingTags' => 'supportsStrippingFramingTags',
        'supportsResegmentation' => 'supportsResegmentation',
    ];

    /**
     * Resource URL
     * @var string
     */
    protected $url;

    /**
     * Resource Authorization Key
     * @var string
     */
    protected $authKey;

    /**
     * Some resources can be created only through API call
     */
    protected bool $creatable = true;

    protected bool $engineBased = false;

    protected bool $useEnginesCombo = true;

    protected string $domainCodePreset = '';

    protected string $defaultColor;

    protected string $queryMode;

    protected mixed $id;

    protected string $serviceType;

    public function __construct($id, $name, $url)
    {
        $this->id = $id;
        $this->name = $name . ' - ' . $url;
        $this->url = $url;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * returns the resource name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns the service name
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * returns the service type
     */
    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    /**
     * returns the match rate type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function getQueryMode(): string
    {
        return $this->queryMode;
    }

    /**
     * returns true if the resource can deal with the given source language
     * returns true per default, must be implemented in the service specific resource classes
     * @return boolean
     */
    public function hasSourceLang(editor_Models_Languages $sourceLang)
    {
        return true;
    }

    /**
     * returns true if the resource can deal with the given target language
     * returns true per default, must be implemented in the service specific resource classes
     * @return boolean
     */
    public function hasTargetLang(editor_Models_Languages $targetLang)
    {
        return true;
    }

    /***
     * Get the source language code for given source language id.
     * The language code is used as source language api parameter.
     *
     * @param int $sourceLanguageId
     * @return string
     */
    public function getLanguageCodeSource(int $sourceLanguageId)
    {
        return $this->getLanguageCode($sourceLanguageId);
    }

    /***
     * Get the target language code for given target language id.
     * The language code is used as target language api parameter.
     *
     * @param int $targetLanguageId
     * @return string
     */
    public function getLanguageCodeTarget(int $targetLanguageId)
    {
        return $this->getLanguageCode($targetLanguageId);
    }

    /**
     * Get the target language name for the given source language id.
     */
    public function getLanguageNameSource(int $sourceLanguageId): string
    {
        return editor_ModelInstances::language($sourceLanguageId)->getLangName();
    }

    /**
     * Get the target language name for the given target language id.
     */
    public function getLanguageNameTarget(int $targetLanguageId): string
    {
        return editor_ModelInstances::language($targetLanguageId)->getLangName();
    }

    /**
     * Get the langauge code for the given langauge id. By default the language code for the langauge is the rfc value.
     * Override this method in the child resources if differend language code is needed
     * @return string
     */
    protected function getLanguageCode(int $languageId)
    {
        return editor_ModelInstances::language($languageId)->getRfc5646();
    }

    /**
     * sets the service type
     */
    public function setService(editor_Services_ServiceAbstract $service, string $defaultColor)
    {
        $this->service = $service->getName();
        $this->serviceType = $service->getServiceNamespace();
        $this->defaultColor = $defaultColor;
        $this->queryMode = $service->getQueryMode();
    }

    /**
     * returns the configured URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * returns the configured authentication key, if the resources provides and needs one
     * must be loaded in the services resource class
     */
    public function getAuthenticationKey()
    {
        return $this->authKey;
    }

    /**
     * returns the resource as stdClass data object for the ResourceController
     */
    public function getDataObject(): stdClass
    {
        $data = new stdClass();
        foreach ($this->fieldsForController as $key => $index) {
            $method = 'get' . ucfirst($index);
            $data->$key = $this->$method();
        }

        // gives the chance to add additional Meta-Data per resource-type
        return $this->adjustMetaData($data);
    }

    /**
     * Returns just the resource-types meta-data (that will be part of the frontend-model for the resource-type)
     * @return boolean[]
     */
    public function getMetaData()
    {
        $meta = [
            'deletable' => $this->deletable,
            'writable' => $this->writable,
            'analysable' => $this->analysable,
            'searchable' => $this->searchable,
            'filebased' => $this->filebased,
        ];

        // gives the chance to adjust or add Meta-Data per resource-type
        return $this->adjustMetaData($meta);
    }

    /**
     * @return array{status:string,statusInfo:string}
     * @throws Zend_Exception
     */
    public function getInitialStatus(
        ?array $specificData,
        int $languageResourceId,
        ZfExtended_Zendoverwrites_Translate $translate,
    ): array {
        return [
            'status' => LanguageResourceStatus::NOTCHECKED,
            'statusInfo' => $translate->_('Wählen Sie die Ressource aus um weitere Infos zu bekommen.'),
        ];
    }

    /**
     * returns if resource is filebased or not
     * @return boolean
     */
    public function getFilebased()
    {
        return (bool) $this->getAdjustedMetaValue('filebased');
    }

    /**
     * returns if resource is searchable or not
     * @return boolean
     */
    public function getSearchable()
    {
        return (bool) $this->getAdjustedMetaValue('searchable');
    }

    /**
     * returns if resource is writable or not
     * @return boolean
     */
    public function getWritable()
    {
        return (bool) $this->getAdjustedMetaValue('writable');
    }

    /***
     * return if resource is analysable
     * @return string
     */
    public function getAnalysable()
    {
        return (bool) $this->getAdjustedMetaValue('analysable');
    }

    /**
     * return if resource is deletable
     */
    public function getDeletable(): bool
    {
        return (bool) $this->getAdjustedMetaValue('deletable');
    }

    public function getCreatable(): bool
    {
        return (bool) $this->getAdjustedMetaValue('creatable');
    }

    /**
     * returns the service type
     */
    public function getDefaultColor(): string
    {
        return (string) $this->getAdjustedMetaValue('defaultColor');
    }

    public function getEngineBased(): bool
    {
        return (bool) $this->getAdjustedMetaValue('engineBased');
    }

    public function getUseEnginesCombo(): bool
    {
        return (bool) $this->getAdjustedMetaValue('useEnginesCombo');
    }

    public function getDomainCodePreset(): string
    {
        return (string) $this->getAdjustedMetaValue('domainCodePreset');
    }

    public function getSupportsStrippingFramingTags(): bool
    {
        return (bool) $this->getAdjustedMetaValue('supportsStrippingFramingTags');
    }

    public function getSupportsResegmentation(): bool
    {
        return (bool) $this->getAdjustedMetaValue('supportsResegmentation');
    }

    public function getStrippingFramingTagsConfig(): array
    {
        return [];
    }

    public function getResegmentationConfig(): array
    {
        return [];
    }

    public function supportsInternalFuzzy(): bool
    {
        return false;
    }

    /**
     * Makes it possible to adjust the meta-data by service-name if needed
     * The passed data is either an assoc array or stdClass object depending on the context
     */
    protected function adjustMetaData(array|stdClass $data): array|stdClass
    {
        return $data;
    }

    /**
     * Helper to evaluate data that is either object or array
     */
    protected function dataHasProperty(string $property, array|stdClass $data): bool
    {
        return is_array($data) ? array_key_exists($property, $data) : property_exists($data, $property);
    }

    /**
     * Retrieves a single adjusted meta-value
     */
    private function getAdjustedMetaValue(string $name): mixed
    {
        $data = $this->adjustMetaData([
            $name => $this->$name,
        ]);

        return $data[$name];
    }
}
