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

namespace MittagQI\Translate5\LSP;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\Exception\CoordinatorNotFoundException;
use MittagQI\Translate5\LSP\Exception\LspUserNotFoundException;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderUserTable;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Model\User;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Db_Table_Row;
use ZfExtended_Models_Db_User;

class JobCoordinatorRepository
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly LspRepository $lspRepository,
        private readonly LspUserRepository $lspUserRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            LspRepository::create(),
            LspUserRepository::create(),
        );
    }

    public function findByUser(User $user): ?JobCoordinator
    {
        try {
            return $this->getByUser($user);
        } catch (CoordinatorNotFoundException) {
            return null;
        }
    }

    /**
     * @throws CoordinatorNotFoundException
     */
    public function getByUser(User $user): JobCoordinator
    {
        try {
            $lspUser = $this->lspUserRepository->getByUser($user);

            return JobCoordinator::fromLspUser($lspUser);
        } catch (CantCreateCoordinatorFromUserException|LspUserNotFoundException) {
            throw new CoordinatorNotFoundException($user->getUserGuid());
        }
    }

    /**
     * @throws CoordinatorNotFoundException
     */
    public function getByUserGuid(string $userGuid): JobCoordinator
    {
        try {
            $lspUser = $this->lspUserRepository->getByUserGuid($userGuid);

            return JobCoordinator::fromLspUser($lspUser);
        } catch (CantCreateCoordinatorFromUserException|LspUserNotFoundException) {
            throw new CoordinatorNotFoundException($userGuid);
        }
    }

    public function findByUserGuid(string $userGuid): ?JobCoordinator
    {
        try {
            return $this->getByUserGuid($userGuid);
        } catch (CoordinatorNotFoundException) {
            return null;
        }
    }

    /**
     * @return iterable<JobCoordinator>
     */
    public function getByLspId(int $lspId): iterable
    {
        $lsp = $this->lspRepository->get($lspId);

        yield from $this->getByLsp($lsp);
    }

    /**
     * @return iterable<JobCoordinator>
     */
    public function getByLsp(LanguageServiceProvider $lsp): iterable
    {
        $user = new User();

        $select = $this->db
            ->select()
            ->from([
                'user' => ZfExtended_Models_Db_User::TABLE_NAME,
            ])
            ->join([
                'lspToUser' => LanguageServiceProviderUserTable::TABLE_NAME,
            ], 'user.id = lspToUser.userId', ['lspToUser.guid'])
            ->where('lspToUser.lspId = ?', $lsp->getId())
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%');

        $rows = $this->db->fetchAll($select);

        if (empty($rows)) {
            return yield from [];
        }

        foreach ($rows as $row) {
            $guid = $row['guid'];

            unset($row['guid']);

            $user->init(new Zend_Db_Table_Row(
                [
                    'table' => $user->db,
                    'data' => $row,
                    'stored' => true,
                    'readOnly' => false,
                ]
            ));

            yield new JobCoordinator($guid, clone $user, clone $lsp);
        }
    }

    /**
     * @return iterable<JobCoordinator>
     */
    public function getSubLspJobCoordinators(JobCoordinator $coordinator): iterable
    {
        foreach ($this->lspRepository->getSubLspList($coordinator->lsp) as $subLsp) {
            yield from $this->getByLsp($subLsp);
        }
    }

    public function getCoordinatorsCount(LanguageServiceProvider $lsp): int
    {
        $select = $this->db
            ->select()
            ->from([
                'user' => ZfExtended_Models_Db_User::TABLE_NAME,
            ], [
                'count' => 'COUNT(*)',
            ])
            ->join([
                'lspToUser' => LanguageServiceProviderUserTable::TABLE_NAME,
            ], 'user.id = lspToUser.userId', [])
            ->where('lspToUser.lspId = ?', $lsp->getId())
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%');

        return (int) $this->db->fetchRow($select)['count'];
    }
}
