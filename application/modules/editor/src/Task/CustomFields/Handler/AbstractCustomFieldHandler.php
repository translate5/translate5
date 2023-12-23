<?php

namespace MittagQI\Translate5\Task\CustomFields\Handler;

abstract class AbstractCustomFieldHandler
{
    abstract public function addCustomField($field);
    abstract public function removeCustomField($field);
    abstract public function editCustomField($field, $newValues);
}