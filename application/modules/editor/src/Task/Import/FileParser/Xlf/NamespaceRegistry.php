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
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Import\FileParser\Xlf;

use editor_Models_Export_FileParser_Xlf_Namespaces as XlfExportNamespace;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Namespaces as XlfImportNamespace;
use editor_Models_Import_FileParser_XmlParser as XmlParser;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Comments as ImportComments;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Across;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\MemoQ;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Tmgr;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Translate5;
use MittagQI\Translate5\Task\Export\FileParser\Xlf\Comments as ExportComments;
use ReflectionException;
use ZfExtended_Factory;

/**
 * XLF Namespace Handler
 */
class NamespaceRegistry
{

    /**
     * List of available specific XLF namespace classes
     * @var array|string[]
     */
    protected static array $registeredNamespaces = [
        'ibm' => Tmgr::class,
        'translate5' => Translate5::class,
        'across' => Across::class,
        'memoq' => MemoQ::class,
    ];

    /**
     * No direct usage allowed, only indirect over getImportNamespace / getExportNamespace
     */
    protected function __construct(protected array $toBeUsedNamespaces = [])
    {
    }

    /**
     * @param string $xliff
     * @param XmlParser $xmlparser
     * @param ExportComments $comments
     * @return XlfExportNamespace
     * @throws ReflectionException
     */
    public static function getExportNamespace(
        string         $xliff,
        XmlParser      $xmlparser,
        ExportComments $comments
    ): XlfExportNamespace
    {
        return ZfExtended_Factory::get(XlfExportNamespace::class, [
            self::getExportRegistryForXlf($xliff),
            $xmlparser,
            $comments
        ]);
    }

    /**
     * returns the namespace registry filled with the suitable export namespace instances only
     * @param string $xliff
     * @return self
     * @uses \MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\AbstractNamespace::getExportCls
     */
    public static function getExportRegistryForXlf(string $xliff): self
    {
        //get the registry for import and change / filter classes in instance:
        $registry = self::getImportRegistryForXlf($xliff);

        $registry->toBeUsedNamespaces = array_filter(array_map(function ($importCls) {
            return $importCls::getExportCls();
        }, $registry->toBeUsedNamespaces));

        return $registry;
    }

    /**
     * returns the namespace registry filled with the suitable import namespace instances only
     * @param string $xliff
     * @return self
     * @uses \MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\AbstractNamespace::isApplicable
     */
    public static function getImportRegistryForXlf(string $xliff): self
    {
        $namespaces = [];
        foreach (self::$registeredNamespaces as $name => $namespaceCls) {
            if ($namespaceCls::isApplicable($xliff)) {
                $namespaces[$name] = $namespaceCls;
            }
        }
        return new self($namespaces);
    }

    /**
     * @throws ReflectionException
     */
    public static function getImportNamespace(
        string         $xliff,
        XmlParser      $xmlParser,
        ImportComments $comments
    ): XlfImportNamespace
    {
        return ZfExtended_Factory::get(XlfImportNamespace::class, [
            self::getImportRegistryForXlf($xliff),
            $xmlParser,
            $comments
        ]);
    }

    /**
     * registers a new XLF namespace to be checked on applicability on each XLF import
     * @param string $key
     * @param string $namespaceCls
     * @return void
     */
    public static function registerNamespace(string $key, string $namespaceCls): void
    {
        self::$registeredNamespaces[$key] = $namespaceCls;
    }

    /**
     * @param XmlParser $xmlparser
     * @param Comments|ExportComments $comments
     * @return array
     * @throws ReflectionException
     */
    public function getImplementations(XmlParser $xmlparser, ImportComments|ExportComments $comments): array
    {
        return array_map(function ($cls) use ($xmlparser, $comments) {
            return ZfExtended_Factory::get($cls, [$xmlparser, $comments]);
        }, $this->toBeUsedNamespaces);
    }
}
