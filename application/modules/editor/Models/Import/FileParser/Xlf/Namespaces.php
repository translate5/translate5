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

use editor_Models_Import_FileParser_SegmentAttributes as SegmentAttributes;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\AbstractNamespace;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Across;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\MemoQ;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Tmgr;
use MittagQI\Translate5\Task\Import\FileParser\Xlf\Namespaces\Translate5;
use editor_Models_Import_FileParser_Xlf_ContentConverter as ContentConverter;


/**
 * XLF Namespace Handler
 */
class editor_Models_Import_FileParser_Xlf_Namespaces extends AbstractNamespace
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
     * Namespace instances to be used on XLF parsing (checked by isApplicable or force added manually)
     * @var array
     */
    protected array $activeNamespaces = [];

    public function __construct($xliff)
    {
        foreach (self::$registeredNamespaces as $name => $namespaceCls) {
            if ($namespaceCls::isApplicable($xliff)) {
                $this->addNamespace($name, ZfExtended_Factory::get($namespaceCls));
            }
        }
    }

    /**
     * @param string $xliff
     * @return bool
     */
    protected static function isApplicable(string $xliff): bool
    {
        throw new LogicException('It makes no sense to call that method on the general namespace class');
    }

    /**
     * Adds manually a specific XLF namespace handler
     * @param string $key
     * @param AbstractNamespace $namespace
     */
    public function addNamespace(string $key, AbstractNamespace $namespace): void
    {
        $this->activeNamespaces[$key] = $namespace;
    }

    /**
     * registers a new XLF namespace to be checked on applicability on each XLF import
     * @param string $key
     * @param string $namespace
     * @return void
     */
    public static function registerNamespace(string $key, string $namespace): void
    {
        self::$registeredNamespaces[$key] = $namespace;
    }

    /**
     * @param array $attributes
     * @param SegmentAttributes $segmentAttributes
     */
    public function transunitAttributes(array $attributes, SegmentAttributes $segmentAttributes): void
    {
        $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * calls the function in each namespace object, passes the arguments
     * @param string $function
     * @param array $arguments
     * @param mixed $default optional, the default result if no namespace was found
     * @return array|bool|null
     */
    protected function call(string $function, array $arguments, array|bool|null $default = null): ContentConverter|array|bool|null
    {
        //it is slightly unusual that a XLF file has multiple activeNamespaces, but still it can happen
        // we handle it, that if a empty result is produced, we proceed with the next namespace
        foreach ($this->activeNamespaces as $namespace) {
            $result = call_user_func_array([$namespace, $function], $arguments);
            if ((is_array($result) && empty($result)) || is_null($result)) {
                //empty array or null means, check next namespace
                continue;
            }
            return $result;
        }
        //if no namespace was defined, or nothing was returned by them, we return the default result
        return $default;
    }

    /**
     * @param array $currentSourceTag
     * @param SegmentAttributes $segmentAttributes
     */
    public function currentSource(array $currentSourceTag, SegmentAttributes $segmentAttributes): void
    {
        $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $currentTargetTag
     * @param SegmentAttributes $segmentAttributes
     */
    public function currentTarget(array $currentTargetTag, SegmentAttributes $segmentAttributes): void
    {
        $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     * @see AbstractNamespace::registerParserHandler()
     */
    public function registerParserHandler(editor_Models_Import_FileParser_XmlParser $xmlparser): void
    {
        $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     * @see AbstractNamespace::useTagContentOnly()
     */
    public function useTagContentOnly(): ?bool
    {
        //using null as explicit default value should trigger further investigation if the
        // tag content should be used or not (if the namespace did not provide information about it)
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     * @see AbstractNamespace::getComments()
     */
    public function getComments(): array
    {
        return $this->call(__FUNCTION__, func_get_args(), []);
    }

    public function getContentConverter(editor_Models_Task $task, string $filename): ContentConverter
    {
        //return the contentconverter given by namespace, or if none the default one
        return $this->call(__FUNCTION__, func_get_args()) ?? parent::getContentConverter($task, $filename);
    }
}
