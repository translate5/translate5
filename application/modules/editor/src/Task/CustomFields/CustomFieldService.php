<?php

namespace MittagQI\Translate5\Task\CustomFields;

class CustomFieldService
{
    public function clearComboboxOptionUsages(Field $field, string $comboboxData): void
    {
        if ($field->getType() === 'combobox') {
            $was = array_keys(json_decode($field->getComboboxData(), true));
            $now = array_keys(json_decode($comboboxData, true));
            $del = array_diff($was, $now);
        }

        if (isset($del) && count($del)) {
            $field->clearComboboxOptionUsages($del);
        }
    }
}
