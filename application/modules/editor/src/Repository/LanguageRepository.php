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

namespace MittagQI\Translate5\Repository;

use editor_Models_Db_Languages;
use editor_Models_Languages;
use PDO;
use ReflectionException;
use Zend_Cache_Exception;
use Zend_Db_Statement_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class LanguageRepository
{
    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function get(int $langId): editor_Models_Languages
    {
        $lang = ZfExtended_Factory::get(editor_Models_Languages::class);
        $lang->load($langId);

        return $lang;
    }

    /**
     * @throws ReflectionException
     */
    public function find(int $langId): ?editor_Models_Languages
    {
        try {
            return $this->get($langId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }

    /**
     * @throws ReflectionException
     */
    public function findByRfc5646(string $rfc): ?editor_Models_Languages
    {
        try {
            $language = ZfExtended_Factory::get(editor_Models_Languages::class);
            $language->loadByRfc5646($rfc);

            return $language;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     */
    public function getRfc5646ToIdMap(): array
    {
        $languages = ZfExtended_Factory::get(editor_Models_Languages::class);
        $db = $languages->db->getAdapter();
        $select = $db->select()->from(editor_Models_Db_Languages::TABLE_NAME, ['rfc5646', 'id']);

        return $db->query($select)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Cache_Exception
     */
    public function findFuzzyLanguages(int $id, string $field = 'id', bool $includeMajor = false): array
    {
        $language = ZfExtended_Factory::get(editor_Models_Languages::class);

        return $language->getFuzzyLanguages($id, $field, $includeMajor);
    }

    /**
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function findMajorLanguageById(int $languageId): int
    {
        $language = ZfExtended_Factory::get(editor_Models_Languages::class);

        return $language->findMajorLanguageById($languageId);
    }
}
