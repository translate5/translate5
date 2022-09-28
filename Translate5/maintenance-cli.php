#!/usr/bin/env php
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

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Translate5\MaintenanceCli\Command\{CachePurgeCommand,
    ChangelogCommand,
    ConfigCommand,
    DatabaseUpdateCommand,
    DevelopmentCreatetestCommand,
    DevelopmentEcodeCommand,
    DevelopmentGithookCommand,
    DevelopmentNewModelCommand,
    DevelopmentNewdbchangeCommand,
    DevelopmentTriggerworkflowCommand,
    DevelopmentOkapiBconfNextVersionCommand,
    L10nAddCommand,
    L10nRemoveCommand,
    LogCommand,
    MaintenanceAnnounceCommand,
    MaintenanceNotifyCommand,
    MaintenanceCommand,
    MaintenanceDisableCommand,
    MaintenanceMessageCommand,
    MaintenanceSetCommand,
    PluginDisableCommand,
    PluginEnableCommand,
    PluginListCommand,
    ReleaseNotesCommand,
    SessionImpersonateCommand,
    StatusCommand,
    SystemCheckCommand,
    SystemMailtestCommand,
    TaskCleanCommand,
    TaskInfoCommand,
    TaskSkeletonfileCommand,
    TermportalReindexCommand,
    TermportalDatatypecheckCommand,
    TestRunAllCommand,
    TestRunCommand,
    TestRunSuiteCommand,
    TestCreateIniSectionCommand,
    UserCreateCommand,
    UserInfoCommand,
    WorkerCleanCommand,
    WorkerListCommand,
    WorkerQueueCommand};
use Translate5\MaintenanceCli\Command\SegmentHistoryCommand;

$app = new Application('Translate5 CLI Maintenance', '1.0');
$commands = [
    new CachePurgeCommand(),
    new ChangelogCommand(),
    new ConfigCommand(),
    new DatabaseUpdateCommand(),
    new LogCommand(),
    new L10nAddCommand(),
    new L10nRemoveCommand(),
    new MaintenanceAnnounceCommand(),
    new MaintenanceNotifyCommand(),
    new MaintenanceCommand(),
    new MaintenanceDisableCommand(),
    new MaintenanceMessageCommand(),
    new MaintenanceSetCommand(),
    new PluginDisableCommand(),
    new PluginEnableCommand(),
    new PluginListCommand(),
    new SegmentHistoryCommand(),
    new SessionImpersonateCommand(),
    new StatusCommand(),
    new SystemCheckCommand(),
    new SystemMailtestCommand(),
    new TaskCleanCommand(),
    new TaskInfoCommand(),
    new TaskSkeletonfileCommand(),
    new TermportalReindexCommand(),
    new TermportalDatatypecheckCommand(),
    new UserCreateCommand(),
    new UserInfoCommand(),
    new WorkerCleanCommand(),
    new WorkerListCommand(),
    new WorkerQueueCommand(),
];
if(file_exists('.git')) {
    $commands[] = new DevelopmentGithookCommand();
    $commands[] = new DevelopmentNewdbchangeCommand();
    $commands[] = new DevelopmentCreatetestCommand();
    $commands[] = new TestRunAllCommand();
    $commands[] = new TestRunCommand();
    $commands[] = new TestRunSuiteCommand();
    $commands[] = new TestCreateIniSectionCommand();
    $commands[] = new ReleaseNotesCommand();
    $commands[] = new DevelopmentNewModelCommand();
    $commands[] = new DevelopmentEcodeCommand();
    $commands[] = new DevelopmentTriggerworkflowCommand();
    $commands[] = new \Translate5\MaintenanceCli\Command\TmxTs1040Command();
    $commands[] = new \Translate5\MaintenanceCli\Command\TmxFixOpenTM2Command();
    $commands[] = new DevelopmentOkapiBconfNextVersionCommand();
}
$app->addCommands($commands);
$app->run();
