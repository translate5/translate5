<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * Test for customFields feature in task
 */
class Translate3483Test extends JsonTestAbstract
{
    public const EDITOR_TASKCUSTOMFIELD_ROUTE = 'editor/taskcustomfield/';

    protected static array $forbiddenPlugins = [
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
    ];

    protected static bool $setupOwnCustomer = false;

    protected static TestUser $setupUserLogin = TestUser::TestManager;

    private static stdClass $customFieldTaskAssigned;

    protected static function setupImport(Config $config): void
    {
        self::$customFieldTaskAssigned = self::addCustomField([
            'label' => '{"en":"Simple en lable","de":"Simple de lable"}',
            'type' => 'textarea',
            'mode' => 'optional',
            'placesToShow' => 'projectWizard,taskGrid',
            'position' => 1,
            'roles' => 'editor',
        ]);

        $config
            ->addTask('de', 'en', static::getTestCustomerId(), 'TRANSLATE-3483-de-en.xlf')
            ->setProperty(
                'customField' . self::$customFieldTaskAssigned->id,
                'Test value for custom field with id ' . self::$customFieldTaskAssigned->id
            );
    }

    /**
     * @throws Zend_Http_Client_Exception
     */
    public function testTaskCustomField()
    {
        $customFieldName = 'customField' . self::$customFieldTaskAssigned->id;
        $this->api()->reloadTask();
        $this->assertNotEmpty($this->api()->getTask()->{$customFieldName}, 'Custom field was not found in task object');

        self::assertEquals(
            'Test value for custom field with id ' . self::$customFieldTaskAssigned->id,
            $this->api()->getTask()->{$customFieldName}
        );

        self::deleteField(self::$customFieldTaskAssigned->id);
    }

    /**
     * Readonly field should not be changeable via api or via the UI.
     *
     * @throws Zend_Http_Client_Exception
     */
    public function testReadOnlyChange()
    {
        $field = self::addCustomField();

        self::api()->allowHttpStatusOnce(409);

        $response = self::api()->putJson(self::EDITOR_TASKCUSTOMFIELD_ROUTE . $field->id, [
            'mode' => 'readonly',
        ]);

        $this->assertEquals('E1586', $response->errorCode, 'Error code is not E1586');

        self::deleteField($field->id);
    }

    public function testTypeChange()
    {
        $field = self::addCustomField();

        self::api()->allowHttpStatusOnce(409);

        $response = self::api()->putJson(self::EDITOR_TASKCUSTOMFIELD_ROUTE . $field->id, [
            'type' => 'combobox',
        ]);

        $this->assertEquals('E1586', $response->errorCode, 'Error code is not E1586');

        self::deleteField($field->id);
    }

    private static function deleteField(int $fieldId): void
    {
        self::api()->delete(self::EDITOR_TASKCUSTOMFIELD_ROUTE . $fieldId);
        self::assertNotEmpty(self::api()->getLastResponseDecodeed(), 'Custom field was not deleted');
    }

    private static function addCustomField(array $data = [])
    {
        if (empty($data)) {
            $data = [
                'label' => '{"en":"Simple en lable","de":"Simple de lable"}',
                'type' => 'textarea',
                'mode' => 'optional',
                'placesToShow' => 'projectWizard,taskGrid',
                'position' => 1,
                'roles' => 'editor',
            ];
        }
        self::api()->postJson('editor/taskcustomfield', $data);
        $result = self::api()->getLastResponseDecodeed();
        self::assertNotEmpty($result, 'Custom field was not created:' . print_r($data, true));

        return $result;
    }
}
