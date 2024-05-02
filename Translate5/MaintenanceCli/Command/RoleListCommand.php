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

use MittagQI\Translate5\Test\Api\Exception;
use MittagQI\ZfExtended\Acl\AutoSetRoleResource;
use MittagQI\ZfExtended\Acl\ResourceManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RoleListCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'role:list';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Prints a list of current roles')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Prints a list of current roles.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('roles');

        $namedRights = [];
        foreach (ResourceManager::getAllRights() as $aclRight) {
            $aclRight->description = str_replace("\n", '', $aclRight->description);
            $name = $aclRight->resource . '::' . $aclRight->name;
            if ($aclRight->resource === AutoSetRoleResource::ID) {
                $namedRights[$name] = '<info>' . $name . '</info>: ' . $aclRight->description;
            } else {
                $namedRights[$name] = '<options=bold>' . $name . '</>: ' . $aclRight->description;
            }
        }

        $db = \ZfExtended_Factory::get(\ZfExtended_Models_Db_AclRules::class);

        //currently we load the rules for all modules, if we ever need to differ,
        // we have to do that in the isAllowed call
        // the previous module filter was producing too many problems
        $rules = $db->loadAll();

        $roles = [];
        foreach ($rules as $rule) {
            $role = $rule['role'];
            if (! array_key_exists($role, $roles)) {
                $roles[$role] = [
                    'name' => $role,
                    'rights' => [],
                    AutoSetRoleResource::ID => [],
                    'ruleCount' => 0,
                ];
            }
            $right = $rule['resource'] . '::' . $rule['right'];
            $roles[$role]['rights'][$right] = $namedRights[$right] ?? $right;
            $roles[$role]['ruleCount']++;
            if ($rule['resource'] === AutoSetRoleResource::ID) {
                $roles[$role][AutoSetRoleResource::ID][] = $rule['right'];
            }
        }

        foreach ($roles as $role) {
            $fromOtherRoleCounter = [];
            $this->io->section($role['name']);
            if (! empty($role['rights'])) {
                ksort($role['rights']);
                foreach ($role['rights'] as $right => $label) {
                    foreach ($role[AutoSetRoleResource::ID] as $subRole) {
                        if (! empty($roles[$subRole]['rights'][$right])) {
                            $fromOtherRoleCounter[$subRole]++;

                            continue 2;
                        }
                    }
                    $this->io->writeln('  ' . $label);
                }
            }
            $this->io->writeln('');
            foreach ($fromOtherRoleCounter as $otherRole => $counter) {
                $this->io->writeln('  Inherited from Role ' . $otherRole . ' ' . $counter . ' rights');
            }
        }

        return self::SUCCESS;
    }
}
