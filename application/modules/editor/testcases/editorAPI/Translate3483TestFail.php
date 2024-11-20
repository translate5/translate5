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
use MittagQI\Translate5\Test\JsonTestAbstract;
use PHPUnit\Framework\AssertionFailedError as AssertionFailedErrorAlias;

/**
 * Creates combobox and checkbox fields and try to create task once with non-existing value for those fields and once
 * with the correct one. In the first case it is expected the creation of task to fail.
 */
class Translate3483TestFail extends JsonTestAbstract
{
    protected static array $forbiddenPlugins = [
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
    ];

    protected static bool $setupOwnCustomer = false;

    protected static TestUser $setupUserLogin = TestUser::TestManager;

    /**
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function testComboBoxField()
    {
        self::createAndTestField([
            'label' => '{"en":"Numbers","de":"Zahlen"}',
            'type' => 'combobox',
            'comboboxData' => '{"option1":{"en":"one","de":"eins"},"option2":{"en":"two","de":"zwei"}}',
            'mode' => 'optional',
            'placesToShow' => 'projectWizard,taskGrid',
            'position' => 1,
            'roles' => 'editor',
        ], 'option1');
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws ZfExtended_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function testBooleanField()
    {
        self::createAndTestField([
            'label' => '{"en":"Boolean","de":"Boolean"}',
            'type' => 'checkbox',
            'mode' => 'optional',
            'placesToShow' => 'projectWizard,taskGrid',
            'position' => 1,
            'roles' => 'editor',
        ], '1');
    }

    /**
     * @throws ZfExtended_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws Zend_Http_Client_Exception
     */
    private static function createAndTestField(array $fieldData, string $value): void
    {
        $field = self::addCustomField($fieldData);

        try {
            // here we expect an error, because the value is not in the comboboxData
            self::createTaskWithPropertyForTest(
                'customField' . $field->id,
                'NotExistingValue'
            );
            /** @phpstan-ignore-next-line */
        } catch (AssertionFailedErrorAlias $e) {
            if (str_contains($e->getMessage(), 'NotExistingValue') === false) {
                self::api()->delete('editor/taskcustomfield/' . $field->id);
                self::assertNotEmpty(self::api()->getLastResponseDecodeed(), 'Custom field was not deleted');

                throw $e;
            }
            self::createTaskWithPropertyForTest(
                'customField' . $field->id,
                $value
            );
        }

        self::api()->delete('editor/taskcustomfield/' . $field->id);
        self::assertNotEmpty(self::api()->getLastResponseDecodeed(), 'Custom field was not deleted');
    }

    /**
     * @throws ZfExtended_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    private static function createTaskWithPropertyForTest(string $name, string $value): void
    {
        $config = static::getConfig();
        self::api()->allowHttpStatusOnce(422);
        $task = $config
            ->addTask('de', 'en', static::getTestCustomerId(), 'TRANSLATE-3483-de-en.xlf')
            ->setProperty(
                $name,
                $value
            )
            ->setNotToFailOnError();
        $config->import($task);
    }

    /**
     * @throws Zend_Http_Client_Exception
     */
    private static function addCustomField(array $data)
    {
        self::api()->postJson('editor/taskcustomfield', $data);
        $result = self::api()->getLastResponseDecodeed();
        self::assertNotEmpty($result, 'Custom field was not created:' . print_r($data, true));

        return $result;
    }
}
