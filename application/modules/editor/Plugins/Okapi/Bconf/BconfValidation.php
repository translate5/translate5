<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf;

use editor_Models_ConfigException;
use MittagQI\Translate5\Plugins\Okapi\OkapiAdapter;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use MittagQI\Translate5\Plugins\Okapi\Worker\OkapiWorkerHelper;
use ReflectionException;
use SplFileInfo;
use Throwable;
use ZfExtended_Debug;
use ZfExtended_Exception;

/**
 * This class processes an example file to validate a bconf
 * Therefore testfiles for all supported Filters::TESTABLE_EXTENSIONS exist in data/testfiles
 * Be aware, that not all BCONFs can be validated this way
 */
class BconfValidation
{
    /**
     * Used for testing/validating bconfs
     * @var string
     */
    public const SOURCE_LANGUAGE = 'en';

    /**
     * Used for testing/validating bconfs
     * @var string
     */
    public const TARGET_LANGUAGE = 'de';

    protected BconfEntity $bconf;

    protected bool $valid = true;

    protected bool $testable = true;

    protected string $validationError;

    protected bool $doDebug;

    public function __construct(BconfEntity $bconf)
    {
        $this->bconf = $bconf;
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfValidation');
    }

    /**
     * Validates the bconf by processing an okapi project with it
     * @throws ZfExtended_Exception
     * @throws ReflectionException
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function validate(): bool
    {
        $this->testable = true;
        $testfilePath = $this->getTestFilePath();
        // if the bconf supprted none of our extension we can not test it
        if (empty($testfilePath)) {
            // if the bconf generally supports no extensions it is invalid
            if (count($this->bconf->getSupportedExtensions()) < 1) {
                $this->validationError = 'The bconf has no extensions mapped';

                return false;
            }
            $this->validationError = 'The bconf could not be tested as none of the available testfiles are supported';
            // DEBUG
            if ($this->doDebug) {
                error_log('BCONF VALIDATION ERROR: ' . $this->validationError);
            }
            $this->testable = false;

            return true;
        }

        return $this->process($testfilePath);
    }

    /**
     * Some bconfs can not be tested since we do not have a testfile to check the supported extensions
     */
    public function wasTestable(): bool
    {
        return $this->testable;
    }

    /**
     * @throws ZfExtended_Exception
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    protected function getTestFilePath(): ?string
    {
        foreach (Filters::TESTABLE_EXTENSIONS as $extension) {
            if ($this->bconf->hasSupportFor($extension)) {
                return Filters::createTestfilePath('test.' . $extension);
            }
        }

        return null;
    }

    /**
     * Retrieves the error that caused the file to be invalid
     */
    public function getValidationError(): string
    {
        return $this->validationError;
    }

    /**
     * @throws ReflectionException
     * @throws OkapiException
     */
    protected function process(string $testfilePath): bool
    {
        $testDir = BconfEntity::getUserDataDir() . '/tmp';
        if (! is_dir($testDir)) {
            @mkdir($testDir, 0777, true);
        }
        $manifestFile = sprintf(OkapiWorkerHelper::MANIFEST_FILE, 'test');
        $testfile = basename($testfilePath);
        $api = new OkapiAdapter();

        try {
            $api->createProject();
            $api->uploadOkapiConfig($this->bconf->getPath(), true);
            $api->uploadInputFile($testfile, new SplFileInfo($testfilePath));
            $api->executeTask(self::SOURCE_LANGUAGE, self::TARGET_LANGUAGE);
            $convertedFile = $api->downloadFile($testfile, $manifestFile, new SplFileInfo($testDir));
            // cleanup downloaded files
            unlink($convertedFile);
            unlink($testDir . '/' . $manifestFile);
        } catch (Throwable $e) {
            $this->validationError =
                'Failed to convert ' . $testfile . ' for import with OKAPI [' . $e->getMessage() . ']';
            // DEBUG
            if ($this->doDebug) {
                error_log('BCONF VALIDATION ERROR: ' . $this->validationError);
            }

            return false;
        } finally {
            $api->removeProject();
        }
        // DEBUG
        if ($this->doDebug) {
            error_log('BCONF VALIDATION SUCCESS: ' . $this->bconf->getName() . ' is valid');
        }

        return true;
    }
}
