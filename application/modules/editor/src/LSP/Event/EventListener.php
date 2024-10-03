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

namespace MittagQI\Translate5\LSP\Event;

use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\LspRepository;
use Zend_EventManager_Event;
use Zend_EventManager_SharedEventManager;

class EventListener
{
    public function __construct(
        private readonly Zend_EventManager_SharedEventManager $eventManager,
        private readonly LspRepositoryInterface $lspRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(Zend_EventManager_SharedEventManager $eventManager): self
    {
        return new self(
            $eventManager,
            LspRepository::create(),
        );
    }

    public function attachAll(): void
    {
        $this->eventManager->attach(
            EventDispatcher::class,
            LspUserCreatedEvent::class,
            $this->setNotifiableCoordinatorToLsp(),
        );
    }

    /**
     * @phpstan-return callable(Zend_EventManager_Event)
     */
    private function setNotifiableCoordinatorToLsp(): callable
    {
        return function (Zend_EventManager_Event $zendEvent): void {
            /** @var LspUserCreatedEvent $event */
            $event = $zendEvent->getParam('event');

            $lsp = $event->lspUser->lsp;

            if (null !== $lsp->getNotifiableCoordinatorId()) {
                return;
            }

            try {
                $coordinator = JobCoordinator::fromLspUser($event->lspUser);
            } catch (CantCreateCoordinatorFromUserException) {
                return;
            }

            $lsp->setNotifiableCoordinatorId((int) $coordinator->user->getId());

            $this->lspRepository->save($lsp);
        };
    }
}