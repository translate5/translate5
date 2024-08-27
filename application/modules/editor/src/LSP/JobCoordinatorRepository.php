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
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderUserTable;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderUser;
use MittagQI\Translate5\Repository\LspRepository;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException as NotFoundException;
use ZfExtended_Models_User;

class JobCoordinatorRepository
{
    public function __construct(
        private readonly LspRepository $lspRepository,
        private readonly LspUserRepository $lspUserRepository,
    ) {
    }

    public function findByUser(ZfExtended_Models_User $user): ?JobCoordinator
    {
        try {
            return $this->getByUser($user);
        } catch (NotFoundException|CantCreateCoordinatorFromUserException) {
            return null;
        }
    }

    /**
     * @throws NotFoundException
     * @throws CantCreateCoordinatorFromUserException
     */
    public function getByUser(ZfExtended_Models_User $user): JobCoordinator
    {
        $user = $this->lspUserRepository->getByUser($user);

        return JobCoordinator::fromLspUser($user);
    }

    /**
     * @return iterable<JobCoordinator>
     */
    public function getByLSP(LanguageServiceProvider $lsp): iterable
    {
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $lspToUserTable = ZfExtended_Factory::get(LanguageServiceProviderUser::class)
            ->db
            ->info(LanguageServiceProviderUserTable::NAME);
        $userDb = $user->db;

        $select = $userDb->select()
            ->setIntegrityCheck(false)
            ->from([
                'user' => $user->db->info($user->db::NAME),
            ])
            ->join([
                'lspToUser' => $lspToUserTable,
            ], 'user.id = lspToUser.userId', ['lspToUser.guid'])
            ->where('lspToUser.lspId = ?', $lsp->getId())
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%');

        $rows = $userDb->fetchAll($select);

        if (! $rows->valid()) {
            return yield from [];
        }

        foreach ($rows as $row) {
            $guid = $row['guid'];

            $row->offsetUnset('guid');

            $user->init($row);

            yield new JobCoordinator($guid, clone $user, clone $lsp);
        }
    }

    /**
     * @return iterable<JobCoordinator>
     */
    public function getSubLspJobCoordinators(JobCoordinator $coordinator): iterable
    {
        foreach ($this->lspRepository->getSubLspList($coordinator->lsp) as $subLsp) {
            yield from $this->getByLSP($subLsp);
        }
    }

    public function getCoordinatorsCount(LanguageServiceProvider $lsp): int
    {
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $lspToUserTable = ZfExtended_Factory::get(LanguageServiceProviderUser::class)
            ->db
            ->info(LanguageServiceProviderUserTable::NAME);
        $userDb = $user->db;

        $select = $userDb->select()
            ->setIntegrityCheck(false)
            ->from([
                'user' => $user->db->info($user->db::NAME),
            ], [
                'count' => 'COUNT(*)',
            ])
            ->join([
                'lspToUser' => $lspToUserTable,
            ], 'user.id = lspToUser.userId', [])
            ->where('lspToUser.lspId = ?', $lsp->getId())
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%');

        return (int) $userDb->fetchRow($select)['count'];
    }
}
