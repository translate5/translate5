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

use editor_Services_Connector_TagHandler_Abstract as AbstractTagHandler;
use MittagQI\Translate5\LanguageResource\Adapter\TagsProcessing\TagHandlerFactory;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;

/**
 * Abstract Base Connector
 */
abstract class editor_Services_Connector_Abstract
{
    use editor_Services_UsageLogerTrait;

    protected const TAG_HANDLER_CONFIG_PART = 'default';

    /**
     * @deprecated Moved to MittagQI\Translate5\LanguageResource\Status
     */
    public const STATUS_ERROR = 'error';

    /**
     * @deprecated Moved to MittagQI\Translate5\LanguageResource\Status
     */
    public const STATUS_AVAILABLE = 'available';

    /**
     * @deprecated Moved to MittagQI\Translate5\LanguageResource\Status
     */
    public const STATUS_NOCONNECTION = 'noconnection';

    /**
     * @deprecated Moved to MittagQI\Translate5\LanguageResource\Status
     */
    public const STATUS_NOVALIDLICENSE = 'novalidlicense';

    /**
     * @deprecated Moved to MittagQI\Translate5\LanguageResource\Status
     */
    public const STATUS_NOT_LOADED = 'notloaded';

    /**
     * @deprecated Moved to MittagQI\Translate5\LanguageResource\Status
     */
    public const STATUS_QUOTA_EXCEEDED = 'quotaexceeded';

    public const FUZZY_SUFFIX = '-fuzzy-';

    /***
     * Source languages array key for the languages result.
     * In some of the resources the supported "from-to" languages are not the same.
     * That is why the languages grouping is required in some of them.
     * @var string
     */
    public const SOURCE_LANGUAGES_KEY = 'sourceLanguages';

    /***
     * Target languages array key for the languages result.
     * In some of the resources the supported "from-to" languages are not the same.
     * That is why the languages grouping is required in some of them.
     * @var string
     */
    public const TARGET_LANGUAGES_KEY = 'targetLanguages';

    /***
     * Default resource matchrate
     * @var integer
     */
    protected $defaultMatchRate = 0;

    /**
     * @var editor_Models_LanguageResources_LanguageResource|null
     */
    protected $languageResource = null;

    /**
     * Container for the connector results
     * @var editor_Services_ServiceResult
     */
    protected $resultList;

    /***
     * connector source language
     * @var integer
     */
    protected $sourceLang;

    /***
     * connector target language
     * @var integer
     */
    protected $targetLang;

    /***
     * Flag for if the current connector supports internal fuzzy calculations
     * @var boolean
     */
    protected $isInternalFuzzy = false;

    /***
     * @var editor_Models_LanguageResources_Resource
     */
    protected $resource;

    /**
     * Tag Handler instance as needed by the concrete Connector
     */
    protected AbstractTagHandler $tagHandler;

    /**
     * @var string
     */
    protected $lastStatusInfo = '';

    /**
     * Logger instance
     * @var ZfExtended_Logger
     */
    public $logger;

    /***
     * By default the config values are all overwritten by instance (level 2).
     * Depending on the context, this config can be overwritten on level 4,8,16 (client,task-import,task).
     * @var Zend_Config
     */
    protected $config;

    /***
     *  Is the current connector disabled for usage.
     * @var bool
     */
    protected $disabled = false;

    /**
     *  Is the connector generally able to support HTML Tags in the ->translate() API; see ::canTranslateHtmlTags
     * @var bool
     */
    protected $htmlTagSupport = false;

    /**
     *  Is the connector generally able to support Internal Tags in the ->translate() API; see
     * ::canTranslateInternalTags
     * @var bool
     */
    protected $internalTagSupport = false;

    private ?int $customerId = null;

    /**
     * initialises the internal result list
     */
    public function __construct()
    {
        //init the default logger, is changed in connectTo
        $this->logger = Zend_Registry::get('logger');
        $this->config = Zend_Registry::get('config');
        $this->tagHandler = $this->createTagHandler();
        $this->resultList = ZfExtended_Factory::get('editor_Services_ServiceResult');
    }

    /**
     * Just for logging the called methods
     * @param string $msg
     */
    protected function log($method, $msg = '')
    {
        //error_log($method." LanguageResource ".$this->languageResource->getName().' - '.$this->languageResource->getServiceName().$msg);
    }

    public function getSourceLang(): int
    {
        return $this->sourceLang;
    }

    public function getTargetLang(): int
    {
        return $this->targetLang;
    }

    /**
     * Link this Connector Instance to the given LanguageResource and its resource, in the given language combination
     * @param int $sourceLang language id
     * @param int $targetLang language id
     */
    public function connectTo(
        editor_Models_LanguageResources_LanguageResource $languageResource,
        $sourceLang,
        $targetLang,
        $config = null
    ) {
        $this->resource = $languageResource->getResource();
        $this->languageResource = $languageResource;
        $this->resultList->setLanguageResource($languageResource);
        $this->setServiceLanguages($sourceLang, $targetLang);

        if ($languageResource->getId() !== null) {
            $this->languageResource->sourceLangCode = $this->languageResource->getSourceLangCode();
            $this->languageResource->targetLangCode = $this->languageResource->getTargetLangCode();
        }

        if (! is_null($config)) {
            $this->config = $config;
        }
        // TODO FIXME: why should a tag-handler be needed before ->connectTo was called ??
        // Maybe it would be enough to set the remove-handler for instantiation ... better none at all.
        $this->tagHandler = $this->createTagHandler();
        $this->tagHandler->setLanguages(
            (int) ($sourceLang ?: $languageResource->getSourceLang()),
            (int) ($targetLang ?: $languageResource->getSourceLang())
        );
        $this->logger = $this->logger->cloneMe(
            'editor.languageresource.' . strtolower($this->resource->getService()) . '.connector'
        );
    }

    /**
     * @throws Zend_Cache_Exception|ReflectionException
     */
    protected function setServiceLanguages(?int $sourceLang, ?int $targetLang): void
    {
        if ($this->languageResource === null || $sourceLang === null || $targetLang === null) {
            $this->sourceLang = $sourceLang;
            $this->targetLang = $targetLang;

            return;
        }

        $fuzzy = ZfExtended_Factory::get(editor_Models_Languages::class);
        $sourceFuzzy = $fuzzy->getFuzzyLanguages($sourceLang, includeMajor: true);
        $targetFuzzy = $fuzzy->getFuzzyLanguages($targetLang, includeMajor: true);

        $languages = ZfExtended_Factory::get(editor_Models_LanguageResources_Languages::class);

        // load only the required languages
        $langaugepair = $languages->loadFilteredPairs((int) $this->languageResource->getId(), $sourceFuzzy, $targetFuzzy);

        // if only 1 language combination is available for the langauge resource, use it.
        if (count($langaugepair) === 1) {
            $langaugepair = $langaugepair[0];
            $this->sourceLang = (int) $langaugepair['sourceLang'];
            $this->targetLang = (int) $langaugepair['targetLang'];

            return;
        }

        // check for direct match
        foreach ($langaugepair as $item) {
            if (($item['sourceLang'] === $sourceLang) && ($item['targetLang'] === $targetLang)) {
                $this->sourceLang = $item['sourceLang'];
                $this->targetLang = $item['targetLang'];

                return;
            }
        }

        // check for fuzzy match
        foreach ($langaugepair as $item) {
            // find the langauges using fuzzy matching
            if (in_array($item['sourceLang'], $sourceFuzzy, true) && in_array($item['targetLang'], $targetFuzzy, true)) {
                $this->sourceLang = $item['sourceLang'];
                $this->targetLang = $item['targetLang'];

                return;
            }
        }
    }

    /**
     * Sets the internal stored resource, needed for connections without a concrete language resource (pinging for
     * example)
     */
    public function setResource(editor_Models_LanguageResources_Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Updates translations in the connected service
     * for returning error messages to the GUI use rest_messages
     */
    public function update(editor_Models_Segment $segment, ?UpdateOptions $updateOptions = null): void
    {
        //to be implemented if needed
        $this->log(__METHOD__, ' segment ' . $segment->getId());
    }

    /***
     * Updates translation to the connected service
     * @param string $source source translation
     * @param string $target target (translated source) translation
     * @return void
     */
    public function updateTranslation(string $source, string $target)
    {
        //to be implemented if needed
        $this->log(__METHOD__, ' source ' . $source . ' | target' . $target);
    }

    /***
     * Reset the tm result list data
     */
    public function resetResultList()
    {
        $this->resultList->resetResult();
    }

    /***
     * Get the connector language resource
     * @return editor_Models_LanguageResources_LanguageResource
     */
    public function getLanguageResource()
    {
        return $this->languageResource;
    }

    /***
     * Set the connector language resource
     *
     * @return editor_Models_LanguageResources_LanguageResource
     */
    public function setLanguageResource(editor_Models_LanguageResources_LanguageResource $languageResource)
    {
        return $this->languageResource = $languageResource;
    }

    /***
     * Get the connector service resource
     * @return editor_Models_LanguageResources_Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /***
     * Return the connectors default matchrate.(this should be configured in the zf config)
     * @return number
     */
    public function getDefaultMatchRate()
    {
        return $this->defaultMatchRate;
    }

    /**
     * makes a tm / mt / file query to find a match / translation
     * returns an array with stdObjects, each stdObject contains the fields:
     *languageResource
     * @return editor_Services_ServiceResult
     */
    abstract public function query(editor_Models_Segment $segment);

    /**
     * returns the original or edited source content to be queried, depending on source edit
     * @return string
     */
    public function getQueryString(editor_Models_Segment $segment)
    {
        return $this->getQueryStringByName($segment, editor_Models_SegmentField::TYPE_SOURCE);
    }

    /***
     * returns the original or edited $segmentField content to be queried, depending on source edit
     *
     * @param editor_Models_Segment $segment
     * @param string $segmentField: segmentField (source or target)
     * @return string
     */
    public function getQueryStringByName(editor_Models_Segment $segment, string $segmentField)
    {
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($segment->getTaskGuid());
        $sourceMeta = $sfm->getByName($segmentField);
        $isSourceEdit = ($sourceMeta !== false && $sourceMeta->editable == 1);

        return $isSourceEdit ? $segment->getFieldEdited($segmentField) : $segment->getFieldOriginal($segmentField);
    }

    /**
     * makes a tm / mt / file concordance search
     * @param string $field
     * @return editor_Services_ServiceResult
     */
    public function search(string $searchString, $field = 'source', $offset = null)
    {
        throw new BadMethodCallException("This Service Connector does not support search requests!");
    }

    /**
     * Check the status of the language resource. If using the HttpClient,
     *  the handling of general service down and timeout as no connection, is done in the connector wrapper.
     */
    abstract public function getStatus(
        editor_Models_LanguageResources_Resource $resource,
        editor_Models_LanguageResources_LanguageResource $languageResource = null,
    ): string;

    /**
     * returns the last stored additional info string from the last getStatus call
     */
    public function getLastStatusInfo(): string
    {
        return $this->lastStatusInfo;
    }

    /**
     * set the last stored additional info string for the last getStatus call from outside
     */
    public function setLastStatusInfo(string $info)
    {
        return $this->lastStatusInfo = $info;
    }

    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language
     *
     * @param string $searchString plain text without tags
     * @return editor_Services_ServiceResult
     */
    abstract public function translate(string $searchString);

    /**
     * get query string from segment and set it as result default source
     */
    protected function getQueryStringAndSetAsDefault(editor_Models_Segment $segment): string
    {
        $qs = $this->getQueryString($segment);
        $this->resultList->setDefaultSource($qs);

        return $qs;
    }

    /**
     * Opens the with connectTo given TM on the configured Resource (on task open, not on each request)
     */
    public function open()
    {
        //to be implemented if needed
        $this->log(__METHOD__);
    }

    /***
     * Return the available language codes for the current resource endpoint(api)
     * Use SOURCE_LANGUAGES_KEY and TARGET_LANGUAGES_KEY as languages grouped results when
     * the resource does not support same from - to language combinations
     *
     * MAY NOT THROW EXCEPTIONS! But return empty list on errors.
     *
     * @return string[]
     */
    public function languages(): array
    {
        $languages = ZfExtended_Factory::get(editor_Models_Languages::class);
        $ret = $languages->loadAllKeyValueCustom('id', 'rfc5646');

        return array_values($ret);
    }

    /***
     * Initialize fuzzy connectors. Returns the current instance if not supported.
     * @param int $analysisId
     * @return editor_Services_Connector_Abstract
     */
    public function initForFuzzyAnalysis($analysisId)
    {
        return $this;
    }

    /**
     * The fuzzy languageResource name format is: oldname-fuzzy-AnalysisId
     */
    protected function renderFuzzyLanguageResourceName($name, $analysisId)
    {
        return $name . self::FUZZY_SUFFIX . $analysisId;
    }

    /***
     * Is internal fuzzy connector
     * @return boolean
     */
    public function isInternalFuzzy()
    {
        return $this->isInternalFuzzy;
    }

    /**
     * By default batch queries are not supported. The according editor_Services_Connector_BatchTrait trait must be
     * used in the connector in order to enable batch queries.
     * @return boolean
     */
    public function isBatchQuery(): bool
    {
        return false;
    }

    /**
     * Logs all queued log entries, adding segment  {
}data on each log entry
     */
    public function logForSegment(editor_Models_Segment $segment)
    {
        if (! $this->tagHandler->logger->hasQueuedLogs()) {
            return;
        }
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($segment->getTaskGuid());
        $this->tagHandler->logger->flush([
            'segmentId' => $segment->getId(),
            'nrInTask' => $segment->getSegmentNrInTask(),
            'task' => $task,
        ], $this->logger->getDomain());
    }

    public function setCustomerId(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    protected function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setConfig(Zend_Config $config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function isDisabled()
    {
        return $this->disabled;
    }

    public function disable()
    {
        $this->disabled = true;
    }

    /**
     * @return bool
     * Retrieves, if the connector supports handling of HTML tags in the ->translate() API which then will not be
     *     stripped This API currently is only used by InstantTranslate and will perform an automatic tag-repair Be
     *     aware that the markup is expected to be valid ! The general capabilities for this (e.g. when pretranslating)
     *     are configured via the tag-handler
     */
    public function canTranslateHtmlTags(): bool
    {
        return $this->htmlTagSupport;
    }

    /**
     * @return bool
     * Retrieves, if the connector supports handling of Internal tags in the ->translate() API which then will not be
     *     stripped This API currently is only used by InstantTranslate
     */
    public function canTranslateInternalTags(): bool
    {
        return $this->internalTagSupport;
    }

    /**
     * Retrieves the configuerd tag handler
     */
    public function getTagHandler(): AbstractTagHandler
    {
        return $this->tagHandler;
    }

    protected function getSourceLanguageCode(): string
    {
        $langModel = editor_ModelInstances::language($this->sourceLang);

        return $langModel->getRfc5646();
    }

    protected function getSourceLanguageName(): string
    {
        $langModel = editor_ModelInstances::language($this->sourceLang);

        return $langModel->getLangName();
    }

    protected function getTargetLanguageCode(): string
    {
        $langModel = editor_ModelInstances::language($this->targetLang);

        return $langModel->getRfc5646();
    }

    protected function getTargetLanguageName(): string
    {
        $langModel = editor_ModelInstances::language($this->targetLang);

        return $langModel->getLangName();
    }

    protected function getServiceNameDisplayedInLog(): string
    {
        $showLanguageResourceName = (bool) $this->getConfig()
            ->get('runtimeOptions')->LanguageResources->showNameInErrors;

        if ($showLanguageResourceName && $this->languageResource) {
            return 'Language resource ' . $this->languageResource->getName();
        }

        return $this->getResourceName();
    }

    protected function getResourceName(): string
    {
        return $this->getResource()->getName();
    }

    protected function createTagHandler(array $params = []): AbstractTagHandler
    {
        if (! array_key_exists(AbstractTagHandler::OPTION_KEEP_WHITESPACE_TAGS, $params)) {
            $params[AbstractTagHandler::OPTION_KEEP_WHITESPACE_TAGS] = $this->isSendingWhitespaceAsTagEnabled();
        }

        // This is for backwards compatibility of the tag handler configuration inside the connector class
        if (isset($this->tagHandlerClass)) {
            return ZfExtended_Factory::get($this->tagHandlerClass, [$params]);
        }

        return TagHandlerFactory::createHandler(
            static::TAG_HANDLER_CONFIG_PART,
            $params,
            $this->config
        );
    }

    protected function isSendingWhitespaceAsTagEnabled(): bool
    {
        $config = $this->config->runtimeOptions->LanguageResources->{static::TAG_HANDLER_CONFIG_PART};

        return $config && $config->sendWhitespaceAsTag;
    }
}
