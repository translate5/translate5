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

use MittagQI\Translate5\LSP\Exception\LspUserNotFoundException;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderTable;
use MittagQI\Translate5\LSP\Model\Db\LanguageServiceProviderUserTable;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderUser;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class LspUserRepository implements LspUserRepositoryInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new UserRepository(),
        );
    }

    public function save(LspUser $lspUser): void
    {
        $assoc = ZfExtended_Factory::get(LanguageServiceProviderUser::class);
        $assoc->setGuid($lspUser->guid);
        $assoc->setLspId((int) $lspUser->lsp->getId());
        $assoc->setUserId((int) $lspUser->user->getId());

        $assoc->save();
    }

    public function delete(LspUser $lspUser): void
    {
        ZfExtended_Factory::get(LanguageServiceProviderUser::class)->db->delete([
            'guid = ?' => $lspUser->guid,
        ]);
    }

    public function findByUser(User $user): ?LspUser
    {
        try {
            return $this->getByUser($user);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }

    /**
     * @throws LspUserNotFoundException
     */
    public function getByUser(User $user): LspUser
    {
        $lsp = ZfExtended_Factory::get(LanguageServiceProvider::class);
        $lspToUserTable = ZfExtended_Factory::get(LanguageServiceProviderUser::class)
            ->db
            ->info(LanguageServiceProviderUserTable::NAME);
        $lspDb = $lsp->db;

        $select = $lspDb->select()
            ->setIntegrityCheck(false)
            ->from([
                'lsp' => $lsp->db->info(LanguageServiceProviderTable::NAME),
            ])
            ->join([
                'lspToUser' => $lspToUserTable,
            ], 'lsp.id = lspToUser.lspId', ['lspToUser.guid'])
            ->where('lspToUser.userId = ?', $user->getId());

        $row = $lspDb->fetchRow($select);

        if (! $row) {
            throw new LspUserNotFoundException((int) $user->getId());
        }

        $guid = $row['guid'];

        $row->offsetUnset('guid');

        $lsp->init($row);

        return new LspUser($guid, $user, $lsp);
    }

    /**
     * @throws LspUserNotFoundException
     */
    public function getByUserGuid(string $userGuid): LspUser
    {
        try {
            $user = $this->userRepository->getByGuid($userGuid);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new LspUserNotFoundException($userGuid);
        }

        return $this->getByUser($user);
    }

    public function findByUserGuid(string $userGuid): ?LspUser
    {
        try {
            return $this->getByUserGuid($userGuid);
        } catch (LspUserNotFoundException) {
            return null;
        }
    }

    /**
     * @throws LspUserNotFoundException
     */
    public function getByUserId(int $userId): LspUser
    {
        try {
            $user = $this->userRepository->get($userId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new LspUserNotFoundException($userId);
        }

        return $this->getByUser($user);
    }

    /**
     * @return array<int, int>
     */
    public function getUserIdToLspIdMap(): array
    {
        $lspToUser = ZfExtended_Factory::get(LanguageServiceProviderUser::class);
        $assocs = $lspToUser->loadAll();

        return array_column($assocs, 'lspId', 'userId');
    }

    /**
     * {@inheritDoc}
     */
    public function getUsers(int $lspId): iterable
    {
        $user = ZfExtended_Factory::get(User::class);
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
            ], 'user.id = lspToUser.userId', [])
            ->where('lspToUser.lspId = ?', $lspId);

        $rows = $userDb->fetchAll($select);

        if (! $rows->valid()) {
            return yield from [];
        }

        foreach ($rows as $row) {
            $user->init($row);

            yield clone $user;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getLspUsers(LanguageServiceProvider $lsp): iterable
    {
        foreach ($this->getUsers((int) $lsp->getId()) as $user) {
            yield new LspUser($user->getUserGuid(), $user, $lsp);
        }
    }
}
