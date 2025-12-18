<?php
declare(strict_types=1);

namespace MittagQI\Translate5\Tools;

use JsonException;

class XssInjector
{
    public const FILETREE = '#/editor/(taskid/\d+/)?(filetree|referencefile)(/root)?($|\?)#i';

    public const EXCLUDES = [
        '#^/editor/task/\d+/job#i' => ['state', 'role'],
        '#^/editor/log($|\?)#i' => ['eventCode', 'domain', 'file', 'line', 'appVersion', 'trace', 'method'],
        '#^/editor/(taskid/\d+/)?plugins_matchanalysis_matchanalysis($|\?)#i' => [
            'resourceName',
            'resourceType',
            'internalFuzzy',
            'metaData.fields.*.name',
            'metaData.fields.*.type',
            'metaData.fields.*.begin',
            'metaData.fields.*.end',
        ],
        '#^/editor/plugins_matchanalysis_pricingpreset(/\d+)?($|\?)#i' => ['unitType'],
        '#^/editor/(taskid/\d+/)?languageresourceinstance(/\d+)?($|\?)#i' => [
            'resourceId',
            'resourceType',
            'serviceType',
            'serviceName',
            'tmConversionState',
            'status',
            'specificData',
        ],
        '#^/editor/(taskid/\d+/)?taskcustomfield($|\?)#i' => ['type', 'mode', 'placesToShow'],
        '#^/editor/plugins_okapi_bconffilter($|\?)#i' => ['guiClass'],
        '#^/editor/plugins_okapi_bconffilter/getfprm($|\?)#i' => ['type', 'translations'],
        '#^/editor/(taskid/\d+/)?languageresourceresource($|\?)#i' => [
            'id',
            'serviceType',
            'serviceName',
            'defaultColor',
            'resourceType',
        ],
        '#^/editor/(taskid/\d+/)?task(/\d+)?($|\?)#i' => [
            'state',
            'lockedInternalSessionUniqId',
            'importAppVersion',
            'usageMode',
            'taskType',
            'visualType',
            'segmentFields.*.name',
            'segmentFields.*.type',
            'userPrefs.*.visibility',
            'userPrefs.*.fields',
            'allowedProcessStatuses.*.value',
            'taskassocs.*.name',
            'taskassocs.*.resourceId',
            'taskassocs.*.serviceType',
            'taskassocs.*.serviceName',
            'taskassocs.*.resourceType',
            'taskassocs.*.specificData',
            'workflowProgressSummary.*.workflowStep',
            'workflowProgressSummary.*.status',
        ],
        '#^/editor/user($|\?)#i' => ['roles'],
        // FIXME how to ensure that Application is working with added XSS in config? With a fixed added fake config???
        // is the config really relevant? use DOMpurifier on when loading config???
        '#^/editor/(taskid/\d+/)?config(/runtimeOptions\.[a-zA-Z0-9._-]+)?($|\?)#i' => [
            'value',
            'name',
            'module',
            'category',
            'default',
            'defaults',
            'type',
            'description',
            'accessRestriction',
            'guiName',
            'guiGroup',
            'comment',
            'origin',
        ],
        '#^/editor/(task/\d+/)?events($|\?)#i' => ['state', 'eventCode', 'domain'],
        '#^/editor/(taskid/\d+/)?segment($|\?)#i' => ['mid', 'metaCache'],
        '#^/editor/(taskid/\d+/)?segment/matchratetypes($|\?)#i' => ['matchrateType'],
        self::FILETREE => ['cls', 'extension'],
        '#^/editor/(taskid/\d+/)?quality/segment($|\?)#i' => ['field', 'type', 'category', 'tagName', 'cssClass'],
        '#^/editor/(taskid/\d+/)?quality/task($|\?)#i' => ['qtype'],
        '#^/editor/contentprotectioncontentrecognition($|\?)#i' => ['type'],
        '#^/editor/(taskid/\d+/)?languageresourcetaskassoc($|\?)#i' => [
            'resourceId',
            'resourceType',
            'serviceType',
            'serviceName',
            'name',
        ],
        '#^/editor/(taskid/\d+/)?languageresourceinstance/\d+/query($|\?)#i' => [
            'target',
            'rawTarget',
            'tmConversionState',
            'source',
            'languageResourceType',
            'state',
        ],
        '#^/editor/instanttranslateapi/filelist($|\?)#i' => [
            'sourceLang',
            'targetLang',
            'downloadUrl',
            'editUrl',
            'humanRevisionStatus',
            'saveToTmUrl',
        ],
        '#^/editor/plugins_termportal_data($|\?)#i' => [
            'l10n',
            'locale',
            'activeItem',
            'enabledIn',
            'existsIn',
            'filterWindow.processStatuses.*.alias',
            'filterWindow.tbxCreatedBy.*.ids',
            'filterWindow.tbxUpdatedBy.*.ids',
            'filterPanel.activeFilters.*.alias',
            'language',
            'loaderUrl',
            'filterWindow.attributes.*.level',
            'filterWindow.attributes.*.dataType',
            'filterWindow.attributes.*.alias',
            'filterWindow.attributes.*.type',
        ],
    ];

    private string $uri;

    public function process(string $uri, mixed $data)
    {
        $this->uri = $uri;

        if (preg_match(self::FILETREE, $uri)) {
            return $this->processFileTree($data);
        }

        $to_process = array_keys($data);
        $processRows = isset($data['total']) && ! empty($data['rows']);
        if ($processRows && count($data['rows']) > 0) {
            $to_process = array_diff($to_process, ['rows', 'total']);
            $data = $this->processRows($data);
        }
        if (! empty($data['rows']) && $this->isAssocWithData($data['rows'])) {
            $to_process = array_diff($to_process, ['rows']);
            $data['rows'] = $this->processEntity($data['rows']);
        }

        foreach ($to_process as $processKey) {
            $data[$processKey] = $this->processField($processKey, $data[$processKey]);
        }
        //process other fields of data, but no total and rows if processRows
        // no rows when isAssocWithData

        return $data;
    }

    private function processRows(mixed $data): mixed
    {
        foreach ($data['rows'] as $idx => $row) {
            $data['rows'][$idx] = $this->processEntity($row);

            break; //process only first
        }

        return $data;
    }

    private function processEntity(array|object $row, array $keyPath = []): array|object
    {
        foreach ($row as $key => $value) {
            $row[$key] = $this->processField($key, $value, $keyPath);
        }

        return $row;
    }

    private function isFieldExcluded(int|string $key, array $keyPath): bool
    {
        foreach (self::EXCLUDES as $uri => $excludedFields) {
            if (! preg_match($uri, $this->uri)) {
                continue;
            }
            if (is_array($excludedFields)) {
                $keyPath[] = $key; // the current key must be added too
                if (in_array($key, $excludedFields) || in_array(join('.', $keyPath), $excludedFields)) {
                    return true;
                }
                //field definition contains one assoc key wildcard
                if (str_contains(join('', $excludedFields), '*')) {
                    foreach ($keyPath as $k => $v) {
                        $keyPath[$k] = '*';
                        if (in_array(join('.', $keyPath), $excludedFields)) {
                            return true;
                        }
                        $keyPath[$k] = $v;
                    }
                }
                /* @phpstan-ignore-next-line */
            } elseif (preg_match($excludedFields, $key)) {
                return true;
            }
        }

        return false;
    }

    private function processField(int|string $key, mixed $value, array $keyPath = []): mixed
    {
        if (is_array($value)) {
            $keyPath[] = $key;
            if ($this->isAssocWithData($value)) {
                foreach ($value as $k => $v) {
                    if ($this->isFieldExcluded($key, $keyPath)) {
                        continue;
                    }
                    $value[$k] = $this->processField($k, $v, $keyPath);
                }
            } else {
                $keyPath[] = '*';
                foreach ($value as $k => $v) {
                    if (is_array($v) || is_object($v)) {
                        $value[$k] = $this->processEntity($v, $keyPath);
                    } else {
                        $value[$k] = $this->processField($k, $v, $keyPath);
                    }
                }
            }

            return $value;
        }
        //(g)uids
        //ints we assume all fine, also null and empty ones
        if ($this->isFieldExcluded($key, $keyPath)
            || ! is_string($value)
            || strlen($value) === 0
            || is_numeric($value)) {
            return $value;
        }
        if (preg_match(
            '/^\{?[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}}?$/',
            $value
        )
        ) {
            return $value;
        }

        //common "safe" fields
        if (is_string($key) && preg_match('/^(workflow|workflowStepName)$/i', $key)) {
            return $value;
        }

        //dates
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        if (str_starts_with($value, '{') || str_starts_with($value, '[')) {
            try {
                $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
                foreach ($value as $k => $v) {
                    $value[$k] = $this->processField($k, $v, $keyPath);
                }

                return json_encode($value);
            } catch (JsonException) {
                return $value;
            }
        }

        return $this->addXss($value);
    }

    private function processFileTree(mixed $data)
    {
        foreach ($data as $idx => $row) {
            $data[$idx] = is_string($row) ? $this->processField($idx, $row) : $this->processEntity($row);

            break; //process only first
        }

        return $data;
    }

    private function addXss(string $value): string
    {
        return '<s>' . $value . '</s><img src=x onerror=\'alert(' . json_encode($this->uri) . ')\'>';
    }

    private function isAssocWithData(array $arr): bool
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
