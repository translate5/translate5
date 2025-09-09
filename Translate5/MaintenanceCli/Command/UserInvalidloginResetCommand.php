<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Command;

use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class UserInvalidloginResetCommand extends UserAbstractCommand
{
    public const ARG_IDENTIFIER = 'identifier';

    protected static $defaultName = 'user:invalidlogin:reset';

    protected function configure(): void
    {
        $this
            ->setDescription('Unlocks a login locked user by removing its invalid login entries')
            ->setHelp('Unlocks a user locked by invalid logins');

        $this->addIdentifierArgument(self::ARG_IDENTIFIER);
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @throws ReflectionException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->writeTitle('Invalid login list (timestamp, login)');
        $users = $this->findUsers(self::ARG_IDENTIFIER);
        $invalidLogins = new \ZfExtended_Models_Invalidlogin();

        if (count($users) > 1) {
            $this->io->error('More then one user found, please be more specific!');
            foreach ($users as $user) {
                $this->io->writeln($user->id . ' ' . $user->userGuid . ' ' . $user->login);
            }

            return self::FAILURE;
        }

        $user = reset($users);
        $this->io->success('User ' . $user->id . ' ' . $user->userGuid . ' ' . $user->login . ' unlocked.');
        $invalidLogins->resetCounter($user->login);

        return self::SUCCESS;
    }
}
