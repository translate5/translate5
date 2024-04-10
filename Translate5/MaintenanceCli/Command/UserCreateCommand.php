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
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Acl;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_User;
use ZfExtended_Utils;

class UserCreateCommand extends UserAbstractCommand
{
    public const ROLES_FIXED = ['noRights', 'basic'];

    protected array $allRoles = [];

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'user:create';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Creates a user via CLI.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Creates a translate5 user via CLI - see arguments and options. Example:
        ./translate5.sh user:create example@translate5.net Test User -r pm -r editor -r instantTranslate 
        ');

        $this->addArgument('login', InputArgument::REQUIRED, 'The login of the new user (at least 6 characters).');
        $this->addArgument('firstname', InputArgument::REQUIRED, 'The firstname of the new user.');
        $this->addArgument('lastname', InputArgument::REQUIRED, 'The last name of the new user.');
        $this->addArgument('email', InputArgument::OPTIONAL, 'The e-mail to be used, is needed if login is not a valid e-mail.');

        $this->addOption(
            'roles',
            'r',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Give one or multiple roles for the user. To get valid roles call with -R. Provide one -r per role, see example. If omitted just role editor is used.',
            ['editor']
        );

        $this->addOption(
            'choose-roles',
            'R',
            InputOption::VALUE_NONE,
            'With this option you can select interactively the roles of the new user. -r is ignored then.'
        );

        $this->addOption(
            'locale',
            'l',
            InputOption::VALUE_REQUIRED,
            'The initial locale to be used, defaults to "en". Alternative is just "de" at the moment.',
            'en'
        );

        $this->addOption(
            'password',
            'p',
            InputOption::VALUE_REQUIRED,
            'Set an password for the user to create'
        );

        $this->addOption(
            'read-only',
            null,
            InputOption::VALUE_NONE,
            'Creates the user non editable from the UI.'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        $this->allRoles = array_diff($acl->getAllRoles(), self::ROLES_FIXED);

        $login = $this->input->getArgument('login');
        $email = $this->input->getArgument('email');
        $this->writeTitle('Create user "' . $login . '"');

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $email = $login;
        }

        $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $userModel->setLogin($login);
        $userModel->setFirstName($this->input->getArgument('firstname'));
        $userModel->setSurName($this->input->getArgument('lastname'));
        $userModel->setEmail($email);
        $userModel->setUserGuid(ZfExtended_Utils::guid(true));
        $userModel->setGender($userModel::GENDER_NONE);
        $userModel->setLocale($this->input->getOption('locale'));

        if ($this->input->getOption('read-only')) {
            $userModel->setEditable(false);
        }

        $roles = $this->input->getOption('choose-roles')
            ? $this->askRoles('editor')
            : $this->input->getOption('roles');

        $selectedRoles = array_intersect($roles, $this->allRoles);
        $acl = ZfExtended_Acl::getInstance();
        $userModel->setRoles($acl->mergeAutoSetRoles($selectedRoles, []));

        try {
            $passwordNotSet = ! $this->setUserPassword($userModel);

            $userModel->validate();
        } catch (Exception $e) {
            $this->io->error($e->getMessage());

            return static::FAILURE;
        }

        try {
            $userModel->save();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            if ($e->isInMessage("for key 'login'")) {
                throw new RuntimeException(
                    'login "' . OutputFormatter::escape($login) . '" is in use already and can not be used again!'
                );
            }

            throw $e;
        }
        // if no password was set, we send an email
        if ($passwordNotSet) {
            $mailer = new \ZfExtended_TemplateBasedMail();
            $mailer->setTemplate('userHandlepasswdmail.phtml');
            $mailer->sendToUser($userModel);
        }

        $this->printOneUser($userModel->getDataObject());

        return static::SUCCESS;
    }

    protected function allRoles(): array
    {
        return $this->allRoles;
    }
}
