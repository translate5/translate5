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
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Zend_Exception;
use Zend_Registry;
use Zend_Validate_Exception;
use ZfExtended_Authentication;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;
use ZfExtended_PasswordCheck;
use ZfExtended_Validate_Guid;
use ZfExtended_Validate_Uuid;

abstract class UserAbstractCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'user:info';

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->initInputOutput($input, $output);
        $this->initTranslate5();
    }

    protected function configure()
    {
    }

    protected function addIdentifierArgument(string $argument): void
    {
        $this->addArgument(
            $argument,
            InputArgument::REQUIRED,
            'Either a numeric user ID, a user GUID (with or without curly braces), '
            . 'a login or part of a login when providing % placeholders, or an e-mail.'
        );
    }

    /**
     * @return array<stdClass>
     * @throws ReflectionException
     * @throws Zend_Validate_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function findUsers(string $argumentName, bool $printTitles = true): array
    {
        $identifier = $this->input->getArgument($argumentName);

        $uuid = new ZfExtended_Validate_Uuid();
        $guid = new ZfExtended_Validate_Guid();

        $userModel = ZfExtended_Factory::get(ZfExtended_Models_User::class);

        if (is_numeric($identifier)) {
            if ($printTitles) {
                $this->writeTitle('Searching one user with ID "' . $identifier . '"');
            }
            $userModel->load($identifier);

            return [$userModel->getDataObject()];
        }

        if ($uuid->isValid($identifier)) {
            $identifier = '{' . $identifier . '}';
            if ($printTitles) {
                $this->writeTitle('Searching one user with GUID "' . $identifier . '"');
            }
            $userModel->loadByGuid($identifier);

            return [$userModel->getDataObject()];
        }

        if ($guid->isValid($identifier)) {
            if ($printTitles) {
                $this->writeTitle('Searching one user with GUID "' . $identifier . '"');
            }
            $userModel->loadByGuid($identifier);

            return [$userModel->getDataObject()];
        }

        if ($printTitles) {
            $this->writeTitle('Searching users with login or e-mail "' . $identifier . '"');
        }

        $result = $userModel->loadAllByLoginPartOrEMail($identifier);
        foreach ($result as $k => $v) {
            $result[$k] = (object) $v;
        }

        return $result;
    }

    protected function printOneUser(stdClass $data): void
    {
        $out = [
            '       <info>ID:</info> ' . $data->id,
            ' <info>Username:</info> ' . OutputFormatter::escape((string) $data->firstName . ' ' . $data->surName),
            '   <info>Gender:</info> ' . OutputFormatter::escape((string) $data->gender),
            '    <info>login:</info> ' . OutputFormatter::escape((string) $data->login),
            '   <info>E-Mail:</info> ' . OutputFormatter::escape((string) $data->email),
            '     <info>GUID:</info> ' . OutputFormatter::escape((string) $data->userGuid),
            '    <info>Roles:</info> ' . OutputFormatter::escape((string) $data->roles),
            '   <info>Locale:</info> ' . OutputFormatter::escape((string) $data->locale),
        ];

        if (! empty($data->customers)) {
            $out[] = '<info>Customers:</info> ' . OutputFormatter::escape((string) $data->customers);
        }

        if (! empty($data->openIdIssuer)) {
            $out[] = '<info>Open ID Issuer:</info> ' . OutputFormatter::escape((string) $data->openIdIssuer);
        }
        $out[] = '';

        $this->io->text($out);
        if (! $data->editable) {
            $this->io->warning('User is not editable in the UI!');
        }
    }

    protected function askPassword(callable $validator): string
    {
        $rules = [];
        ZfExtended_PasswordCheck::isValid('', $rules);
        $passwordQuestion = new Question(
            'Enter password. Password should contain:' . PHP_EOL . implode(PHP_EOL, $rules)
        );
        $passwordQuestion->setValidator($validator);
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);

        return $this->io->askQuestion($passwordQuestion);
    }

    /**
     * @throws RuntimeException
     * @throws Zend_Exception
     */
    protected function setUserPassword(ZfExtended_Models_User $userModel): bool
    {
        if (! $this->input->hasParameterOption('-p') && ! $this->input->hasParameterOption('--password')) {
            return false;
        }

        $validator = function (string $password): string {
            $errors = [];
            $config = Zend_Registry::get('config');
            if ($config?->development?->allowInsecurePasswords || ZfExtended_PasswordCheck::isValid($password, $errors)) {
                return $password;
            }

            throw new RuntimeException(
                'Invalid password provided. Broken rules:' . PHP_EOL . implode(PHP_EOL, $errors)
            );
        };

        $password = $this->input->getOption('password') ?: $this->askPassword($validator);

        $userModel->setPasswd(ZfExtended_Authentication::getInstance()->createSecurePassword($validator($password)));

        return true;
    }

    protected function askRoles(?string $default = null): mixed
    {
        $askRoles = new ChoiceQuestion(
            'Choose one or more roles (comma separated, auto-completion with tab)',
            $this->allRoles(),
            $default
        );
        $askRoles->setMultiselect(true);

        return $this->io->askQuestion($askRoles->setMultiselect(true));
    }

    protected function allRoles(): array
    {
        return [];
    }
}
