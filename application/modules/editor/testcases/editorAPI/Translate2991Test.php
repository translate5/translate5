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
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\LanguageResource;

class Translate2991Test extends \editor_Test_JsonTest
{
    /**
     * Id of a preset that was created during test run
     *
     * @var integer
     */
    protected static int $createdPresetId;

    /**
     * Id of a preset that was cloned from the created one
     *
     * @var integer
     */
    protected static int $clonedPresetId;

    /**
     * Default and created ranges
     *
     * @var array
     */
    protected $ranges = [
        'default' => [],
        'created' => [],
    ];

    /**
     * [from => rangeId] pairs for created ranges
     *
     * @var array
     */
    protected $byFrom = [];

    protected static bool $setupOwnCustomer = true;

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init',
    ];

    private static LanguageResource $dummyMt;

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    protected static function setupImport(Config $config): void
    {
        // Shortcuts
        $sourceLangRfc = 'en';
        $targetLangRfc = 'de';
        $customerId = static::$ownCustomer->id;

        // Setup language resource of type ZDemoMT
        self::$dummyMt = $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'API Testing::ZDemoMT_Translate2991');

        // Setup task
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFile('testfiles/Test-task-en-de.html')
            ->setProperty('taskName', 'API Testing::Translate2991Test');

        // Setup pretranslation
        $config->addPretranslation()->setProperty('pretranslateMt', 1);
    }

    /***
     * Test [CRUD, Clone] operations on presets, ranges and prices
     *
     * @throws Zend_Http_Client_Exception
     */
    public function testPricing()
    {
        // Check at least system default pricing preset exists
        /** @var Zend_Http_Response $resp */
        $json = static::api()->getJson('editor/plugins_matchanalysis_pricingpreset');
        $this->assertIsArray($json, 'Response is not an array');
        $rows = array_column($json, 'id', 'name');
        $default = \MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\Preset::PRESET_SYSDEFAULT_NAME;
        $this->assertEquals(true, isset($rows[$default]), 'System default pricing preset not exists');

        // Get prices and ranges for system default preset
        $json = static::api()->get('editor/plugins_matchanalysis_pricingpresetprices', [
            'presetId' => $rows[$default],
        ])->getBody();
        $json = json_decode($json, true);
        $this->assertIsArray($json, 'Response is not a valid json array');
        $this->assertArrayHasKey('metaData', $json, 'Response json has no metaData-key');
        $this->assertArrayHasKey('rows', $json, 'Response json has no metaData-key');
        $this->assertCount(10, $json['metaData'], 'Count of default ranges is not expected');
        $this->assertCount(0, $json['rows'], 'Count of default prices is not expected');

        // Remember ranges from system default preset
        $this->ranges['default'] = $json['metaData'];

        // Create preset
        $name = 'Created preset';
        $json = static::api()->postJson('editor/plugins_matchanalysis_pricingpreset', [
            'name' => $name,
        ]);
        $this->assertTrue($json->success, 'Create preset failed');
        self::$createdPresetId = $json->created->id;

        // Change preset props
        $json = static::api()->putJson('editor/plugins_matchanalysis_pricingpreset', $data = [
            'presetId' => self::$createdPresetId,
            'unitType' => 'character',
            'priceAdjustment' => 5,
            'description' => 'Description for created preset',
        ]);
        $this->assertTrue($json->success, 'Preset props change request was unsuccessful');
        $this->assertEquals($data['unitType'], $json->updated->unitType, 'Unit type change failed');
        $this->assertEquals($data['priceAdjustment'], $json->updated->priceAdjustment, 'Price adjustment change failed');
        $this->assertEquals($data['description'], $json->updated->description, 'Description change failed');

        // Get prices and ranges for created preset
        $json = static::api()->get('editor/plugins_matchanalysis_pricingpresetprices', [
            'presetId' => self::$createdPresetId,
        ])->getBody();
        $json = json_decode($json, true);
        $this->assertIsArray($json, 'Response is not a valid json array');
        $this->assertArrayHasKey('metaData', $json, 'Response json has no metaData-key');
        $this->assertArrayHasKey('rows', $json, 'Response json has no metaData-key');
        $this->assertCount(10, $json['metaData'], 'Count of default ranges is not expected');
        $this->assertCount(0, $json['rows'], 'Count of default prices is not expected');

        // Get ranges from created preset
        $this->ranges['created'] = $json['metaData'];

        // Build [from => till] pairs from default-preset ranges and from created-preset ranges
        foreach (['default', 'created'] as $type) {
            foreach ($this->ranges[$type] as $range) {
                $pairs[$type][] = [
                    'from' => $range['from'],
                    'till' => $range['till'],
                ];
            }
        }

        // Make sure ranges for created preset are same as for default preset, as cloned from there
        $this->assertEquals(
            json_encode($pairs['default']),
            json_encode($pairs['created']),
            'Ranges from default preset were not correctly cloned to created preset'
        );

        // Delete all ranges from created preset
        $json = static::api()->delete('editor/plugins_matchanalysis_pricingpresetrange', $data = [
            'rangeIds' => join(',', $rangeIdA = array_column($this->ranges['created'], 'id')),
        ]);
        $this->assertObjectHasAttribute('success', $json, 'Response json does not have success-prop');
        $this->assertTrue($json->success, 'Response json->success is NOT true');
        $this->assertObjectHasAttribute('deleted', $json, 'Response json does not have deleted-prop');
        $this->assertIsArray($json->deleted, 'Response json->deleted is NOT an array');
        $this->assertCount(count($rangeIdA), $json->deleted, 'Wrong count of deleted ranges');
        $this->ranges['created'] = [];

        // Add ranges 0-55, 56-79, 80-104
        foreach ([
            0 => 55,
            56 => 79,
            80 => 104,
        ] as $from => $till) {
            $this->createRange($from, $till);
        }

        // Create 4 prices-records
        $json = static::api()->postJson('editor/plugins_matchanalysis_pricingpresetprices', $data = [
            'presetId' => self::$createdPresetId,
            'sourceLanguageIds' => '5,361', // de, de-DE
            'targetLanguageIds' => '4,251', // en, en-GB
            'currency' => '$',
        ]);
        $this->assertTrue($json->success, 'Prices create request was unsuccessful');
        $this->assertCount(4, $json->append, 'Count of created prices is not expected');

        // Get pricing-record for [de => en]
        $de_en = null;
        foreach ($json->append as $appended) {
            if ($appended->sourceLanguageId == 5 && $appended->targetLanguageId == 4) {
                $de_en = $appended;
            }
        }

        // Set prices
        $data = $this->setPrices($de_en->id, array_combine(

            // Ranges ids
            array_keys($this->ranges['created']),

            // Prices
            [0.05, 0.06, 0.07]
        ));

        // Clone [de => en] prices-record for 4 combination of languages
        $json = static::api()->postJson('editor/plugins_matchanalysis_pricingpresetprices/clone', [
            'priceId' => $de_en->id,
            'sourceLanguageIds' => '361,362', // de-DE, de-LI
            'targetLanguageIds' => '251,252', // en-GB, en-US
        ]);
        $this->assertTrue($json->success, 'Prices-clone request was not successful');

        // Make sure only 3 clones were created instead of 4,
        // because record for [de-DE => en-GB] combination already exists
        $this->assertCount(3, $json->append, 'Count of clones prices is not expected');

        // Make sure prices are correctly cloned
        foreach ($json->append as $appended) {
            foreach ($data as $prop => $value) {
                $this->assertEquals($data[$prop], $appended->$prop, "Price for $prop was not as cloned correctly");
            }
        }

        // Get pricing-record for [de-LI => en-US]
        $deLI_enUS = null;
        foreach ($json->append as $appended) {
            if ($appended->sourceLanguageId == 362 && $appended->targetLanguageId == 252) {
                $deLI_enUS = $appended;
            }
        }

        // Delete de-LI => en-US pricing
        $json = static::api()->delete('editor/plugins_matchanalysis_pricingpresetprices?answer=yes', [
            'pricesId' => $deLI_enUS->id,
        ]);
        $this->assertTrue($json->success, 'Prices-record delete-request was not successful');

        // Delete range 80-104
        $json = static::api()->delete('editor/plugins_matchanalysis_pricingpresetrange', [
            'rangeIds' => $this->byFrom['80'],
        ]);
        $this->assertTrue($json->success, 'Range delete request was not successful');
        $this->assertIsArray($json->deleted, 'Response delete-prop is not an array');
        $this->assertEquals($this->byFrom['80'], join(',', $json->deleted), 'List of deleted range ids is wrong');

        // Expand range 56-79 to 56-104
        $json = static::api()->putJson('editor/plugins_matchanalysis_pricingpresetrange', $data = [
            'rangeId' => $this->byFrom['56'],
            'from' => 56,
            'till' => 104,
        ]);
        $this->assertTrue($json->success, 'Range expand request was not successful');
        $this->assertEquals($data['till'], $json->updated->till, 'Value of till-prop of expanded range is incorrect');

        $json = static::api()->postJson('editor/plugins_matchanalysis_pricingpreset/clone', $data = [
            'presetId' => self::$createdPresetId,
            'name' => 'Clone preset from created one',
        ]);
        $this->assertTrue($json->success, 'Range expand request was not successful');
        $this->assertEquals($data['name'], $json->clone->name, 'Name of cloned preset is not as expected');
        self::$clonedPresetId = $json->clone->id;

        // Get fresh prices and ranges for created preset within a single response
        $preset['created'] = static::api()->get('editor/plugins_matchanalysis_pricingpresetprices', [
            'presetId' => self::$createdPresetId,
        ])->getBody();
        $preset['created'] = json_decode($preset['created'], true);

        // Get prices and ranges for the clone preset
        $preset['cloned'] = static::api()->get('editor/plugins_matchanalysis_pricingpresetprices', [
            'presetId' => $json->clone->id,
        ])->getBody();
        $preset['cloned'] = json_decode($preset['cloned'], true);

        // Assert ranges and prices quantities are same
        $this->assertCount(count($preset['created']['metaData']), $preset['cloned']['metaData'], 'Ranges of a cloned preset are wrong');
        $this->assertCount(count($preset['created']['rows']), $preset['cloned']['rows'], 'Prices of a cloned preset are wrong');

        // Setup pricingPresetId for the task
        $json = static::api()->putJson('editor/taskmeta', [
            'id' => static::api()->getTask()->taskGuid,
            'data' => json_encode($data = [
                'pricingPresetId' => self::$createdPresetId,
            ]),
        ], null, false);
        $this->assertIsObject($json, 'Response is not object');
        $this->assertObjectHasAttribute('pricingPresetId', $json, 'Response object has now pricingPresetId-prop');
        $this->assertEquals($data['pricingPresetId'], $json->pricingPresetId, 'Pricing preset id was not updated for the task');

        // Change created preset's unitType back to 'word'
        $json = static::api()->putJson('editor/plugins_matchanalysis_pricingpreset', $data = [
            'presetId' => self::$createdPresetId,
            'unitType' => 'word',
        ]);
        $this->assertEquals($data['unitType'], $json->updated->unitType, 'Unit type change back failed');

        // Reduce range 56-104 back to 56-79
        $json = static::api()->putJson('editor/plugins_matchanalysis_pricingpresetrange', $data = [
            'rangeId' => $this->byFrom['56'],
            'from' => 56,
            'till' => 79,
        ]);
        $this->assertTrue($json->success, 'Range expand request was not successful');
        $this->assertEquals($data['till'], $json->updated->till, 'Value of till-prop of expanded range is incorrect');

        // Create range 80-104 and it's price back
        $this->createRange(80, 104);
        $this->setPrices($de_en->id, [
            $this->byFrom[80] => 0.07,
        ]);

        // File with json-encoded analysis data that is expected to be the same as fetch
        $file = 'analysis.json';

        // Get analysis data
        $json = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => static::api()->getTask()->taskGuid,
            'unitType' => 'word',
        ]);
        $this->assertNotEmpty($json, 'No results found for the task matchanalysis');

        // Remove the created timestamp since is not relevant for the test
        foreach ($json as &$row) {
            unset($row->created,$row->errorCount);
        }

        // Overwrite file contents if capture mode is On
        if (static::api()->isCapturing()) {
            file_put_contents(
                static::api()->getFile($file, null, false),
                json_encode($json, JSON_PRETTY_PRINT)
            );
        }

        // Get expected
        $expected = static::api()->getFileContent($file);

        // Check for differences between the expected and the actual analysis data
        $this->assertEquals($expected, $json, "The expected analysis and the result file does not match.");
    }

    /**
     * Create range
     *
     * @throws Zend_Http_Client_Exception
     */
    private function createRange($from, $till)
    {
        $json = static::api()->postJson('editor/plugins_matchanalysis_pricingpresetrange', [
            'presetId' => self::$createdPresetId,
            'from' => $from,
            'till' => $till,
        ]);
        $this->assertTrue($json->success, 'Range create request was unsuccessful');
        $this->assertIsNumeric($json->created, 'Created range id is not integer');
        $this->ranges['created'][$json->created] = [
            'from' => $from,
            'till' => $till,
        ];
        $this->byFrom[$from] = $json->created;
    }

    /**
     * Set price-by-rangeId values within an existing prices-record
     *
     * @throws Zend_Http_Client_Exception
     * @return array
     */
    private function setPrices($pricesId, $priceByRangeIdA)
    {
        // Prepare request data to update prices, and do update
        $data = [
            'pricesId' => $pricesId,
        ];
        foreach ($priceByRangeIdA as $rangeId => $price) {
            $data['range' . $rangeId] = $price;
        }
        $json = static::api()->putJson('editor/plugins_matchanalysis_pricingpresetprices', $data);
        $this->assertTrue($json->success, 'Prices-update request was not successful');

        // Remove pricesId-key from $data
        unset($data['pricesId']);

        // For each remaining rangeXX-keys - check prices are applied correctly
        foreach ($data as $prop => $value) {
            $this->assertEquals($data[$prop], $json->$prop, 'Price for ' . $prop . ' is not as expected');
        }

        return $data;
    }

    /**
     * Remove presets
     *
     * @throws Zend_Http_Client_Exception
     */
    public static function afterTests(): void
    {
        static::api()->delete('editor/plugins_matchanalysis_pricingpreset?answer=yes', [
            'presetId' => self::$createdPresetId,
        ]);
        static::api()->delete('editor/plugins_matchanalysis_pricingpreset?answer=yes', [
            'presetId' => self::$clonedPresetId,
        ]);
    }
}
