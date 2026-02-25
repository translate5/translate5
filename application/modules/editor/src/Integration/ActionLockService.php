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

declare(strict_types=1);

namespace MittagQI\Translate5\Integration;

use MittagQI\Translate5\Repository\LanguageResourceRepository;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\FlockStore;

class ActionLockService
{
    private const READ_LOCK_TTL_SECONDS = 30;

    private const WRITE_LOCK_TTL_SECONDS = 30 * 60;

    public function __construct(
        private readonly LockFactory $lockFactory,
        private readonly LanguageResourceRepository $languageResourceRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new LockFactory(new FlockStore(APPLICATION_DATA . '/lock/language_resource')),
            LanguageResourceRepository::create(),
        );
    }

    public function getReadLockWithId(int $languageResourceId, ?int $ttl = null): SharedLockInterface
    {
        $languageResource = $this->languageResourceRepository->get($languageResourceId);

        return $this->getReadLock($languageResource->getLangResUuid(), $ttl);
    }

    public function getReadLock(string $languageResourceUuid, ?int $ttl = null): SharedLockInterface
    {
        return $this->lockFactory->createLock($languageResourceUuid, $ttl ?: self::READ_LOCK_TTL_SECONDS);
    }

    public function getWriteLockWithId(int $languageResourceId): LockInterface
    {
        $languageResource = $this->languageResourceRepository->get($languageResourceId);

        return $this->getWriteLock($languageResource->getLangResUuid());
    }

    public function getWriteLock(string $languageResourceUuid): LockInterface
    {
        return $this->lockFactory->createLock($languageResourceUuid, self::WRITE_LOCK_TTL_SECONDS);
    }
}
