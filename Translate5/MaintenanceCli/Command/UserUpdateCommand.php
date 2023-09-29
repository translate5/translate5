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

use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use ZfExtended_Acl;
use ZfExtended_Factory;
use ZfExtended_Models_Passwdreset;
use ZfExtended_Models_User;


class UserUpdateCommand extends UserAbstractCommand
{
    const ROLES_FIXED = ['noRights', 'basic'];

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'user:update';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Updates user via CLI')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Updates translate5 User via CLI - see arguments and options. Example:
        ./translate5.sh user:update example@translate5.net -p 5trongPa55wor6 -add-roles pm
        ');

        $this->addArgument('login', InputArgument::REQUIRED, 'The login of the User');

        $this->addOption('firstname', null, InputOption::VALUE_OPTIONAL, 'The firstname of the new User.');
        $this->addOption('lastname', null, InputOption::VALUE_OPTIONAL, 'The lastname of the new User.');
        $this->addOption(
            'email',
            'e',
            InputOption::VALUE_OPTIONAL,
            'The e-mail to be used'
        );

        $this->addOption(
            'editable',
            null,
            InputOption::VALUE_OPTIONAL,
            'Set if User editable or not. Possible values: 0, 1, true, false, yes, no'
        );

        $this->addOption(
            'add-roles',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Add one or multiple roles for the user.'
            . ' To get valid roles call with -R. Provide one -ar per role, see example.',
        );

        $this->addOption(
            'choose-add-roles',
            'A',
            InputOption::VALUE_NONE,
            'With this option you can select interactively the roles to add for User. -ar is ignored then.'
        );

        $this->addOption(
            'remove-roles',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Remove one or multiple roles for the user.'
            . ' To get valid roles call with -RR. Provide one -rr per role, see example.',
        );

        $this->addOption(
            'choose-remove-roles',
            'R',
            InputOption::VALUE_NONE,
            'With this option you can select interactively the roles to add for User. -rr is ignored then.'
        );

        $this->addOption(
            'locale',
            'l',
            InputOption::VALUE_OPTIONAL,
            'The initial locale to be used, defaults to "en". Alternative is just "de" at the moment.',
            'en'
        );

        $this->addOption(
            'password',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Set an password for the user'
        );

        $this->addOption(
            'reset-password',
            '',
            InputOption::VALUE_OPTIONAL,
            'Reset an password for the user and send an email'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userModel = ZfExtended_Factory::get(ZfExtended_Models_User::class);

        try {
            $userModel->loadByLogin($this->input->getArgument('login'));
        } catch (\ZfExtended_Models_Entity_NotFoundException) {
            $this->io->warning(sprintf('User with login [%s] not found', $this->input->getArgument('login')));

            return static::FAILURE;
        }

        if (null !== $this->input->getOption('editable')) {
            $val = $this->input->getOption('editable');

            if (!is_numeric($val) && !in_array($val, ['yes', 'no', 'false', 'true'], true)) {
                $this->io->warning('Invalid value provided for option `editable`');
            }

            $userModel->setEditable((is_numeric($val) && 1 === (int) $val) || in_array($val, ['yes', 'true'], true));
        }

        if ($this->input->getOption('firstname')) {
            $userModel->setFirstName($this->input->getOption('firstname'));
        }

        if ($this->input->getOption('lastname')) {
            $userModel->setSurName($this->input->getOption('lastname'));
        }

        $this->updateEmail($userModel);

        if ($this->input->getOption('locale')) {
            $userModel->setLocale($this->input->getOption('locale'));
        }

        $this->addRoles($userModel);
        $this->removeRoles($userModel);

        try {
            if (!$this->resetPasswordIfAsked($userModel)) {
                $this->setUserPassword($userModel);
            }

            $userModel->validate();
        } catch (Exception $e) {
            $this->io->error($e->getMessage());

            return static::FAILURE;
        }

        $userModel->save();

        $this->printOneUser($userModel->getDataObject());

        return static::SUCCESS;
    }

    protected function allRoles(): array
    {
        return array_diff(ZfExtended_Acl::getInstance()->getAllRoles(), self::ROLES_FIXED);
    }

    private function resetPasswordIfAsked(ZfExtended_Models_User $userModel): bool
    {
        if (!$this->input->hasParameterOption('--reset-password')) {
            return false;
        }

        $email = filter_var($userModel->getEmail(), FILTER_VALIDATE_EMAIL)
            ? $userModel->getEmail()
            : $userModel->getLogin();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('User does not have valid e-mail address to reset password');
        }

        $passwdreset = ZfExtended_Factory::get(ZfExtended_Models_Passwdreset::class);

        return $passwdreset->reset($userModel->getLogin(), 'CLI::userUpdate');
    }

    private function addRoles(ZfExtended_Models_User $userModel): void
    {
        if (!$this->input->getOption('choose-add-roles') && !$this->input->getOption('add-roles')) {
            return;
        }

        $roles = $this->input->getOption('choose-add-roles')
            ? $this->askRoles()
            : $this->input->getOption('add-roles');

        $selectedRoles = array_intersect($roles, $this->allRoles());
        $userModel->setRoles(ZfExtended_Acl::getInstance()->mergeAutoSetRoles($selectedRoles, $userModel->getRoles()));
    }

    private function removeRoles(ZfExtended_Models_User $userModel): void
    {
        if (!$this->input->getOption('choose-remove-roles') && !$this->input->getOption('remove-roles')) {
            return;
        }

        $roles = $this->input->getOption('choose-remove-roles')
            ? $this->askRoles()
            : $this->input->getOption('remove-roles');

        $selectedRoles = array_intersect($roles, $this->allRoles());
        $rolesToSet = [];

        foreach ($userModel->getRoles() as $role) {
            if (!in_array($role, $selectedRoles, true)) {
                $rolesToSet[] = $role;
            }
        }

        $userModel->setRoles(ZfExtended_Acl::getInstance()->mergeAutoSetRoles($rolesToSet, []));
    }

    private function updateEmail(ZfExtended_Models_User $userModel): void
    {
        if ($this->input->getOption('email')) {
            $email = $this->input->getOption('email');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->io->warning(sprintf('User with login [%s] not found', $this->input->getArgument('login')));

                return;
            }

            $userModel->setEmail($email);
        }
    }
}
