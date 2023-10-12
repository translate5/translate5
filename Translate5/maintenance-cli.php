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

chdir(__DIR__.'/../'); //otherwise vendor below and ZfExtended implicit could not be found.
require_once 'vendor/autoload.php';

const TRANSLATE5_CLI = true;

use Symfony\Component\Console\Application;
use Translate5\MaintenanceCli\Command\{AuthTokenCommand,
    AuthTokenDeleteCommand,
    AuthTokenListCommand,
    CachePurgeCommand,
    ChangelogCommand,
    ConfigCommand,
    CronCommand,
    DatabaseBackupCommand,
    DatabaseStatCommand,
    DatabaseUpdateCommand,
    DevelopmentCreatetestCommand,
    DevelopmentEcodeCommand,
    DevelopmentGithookCommand,
    DevelopmentLocalServicesCommand,
    DevelopmentNewdbchangeCommand,
    DevelopmentNewModelCommand,
    DevelopmentOkapiBconfNextVersionCommand,
    DevelopmentSymlinksCommand,
    DevelopmentTriggerworkflowCommand,
    DevelopmentWorkertreeCommand,
    L10nAddCommand,
    L10nRemoveCommand,
    L10nTaskcreateCommand,
    LogCommand,
    MaintenanceAnnounceCommand,
    MaintenanceCommand,
    MaintenanceDisableCommand,
    MaintenanceMessageCommand,
    MaintenanceNotifyCommand,
    MaintenanceSetCommand,
    OkapiAddCommand,
    OkapiCleanBconfsCommand,
    OkapiListCommand,
    OkapiPurgeCommand,
    OkapiUpdateCommand,
    PluginDisableCommand,
    PluginEnableCommand,
    PluginListCommand,
    ReleaseNotesCommand,
    RoleListCommand,
    ServiceAutodiscoveryCommand,
    ServiceCheckCommand,
    ServicePingCommand,
    SessionImpersonateCommand,
    StatusCommand,
    SystemCheckCommand,
    SystemMailtestCommand,
    T5Memory\T5MemoryDeleteTmCommand,
    T5Memory\T5MemoryMigrationCommand,
    T5Memory\T5MemoryReorganizeCommand,
    T5Memory\T5memoryTmListCommand,
    TaskCleanCommand,
    TaskImportCommand,
    TaskInfoCommand,
    TaskSkeletonfileCommand,
    TermportalDatatypecheckCommand,
    TermportalReindexCommand,
    TestAddIniSectionCommand,
    TestApplicationRunCommand,
    TestApplytestsqlCommand,
    TestCleanupCommand,
    TestCreateFaultySegmentCommand,
    TestRunAllCommand,
    TestRunCommand,
    TestRunSuiteCommand,
    UserCreateCommand,
    UserInfoCommand,
    UserUpdateCommand,
    VisualConvertLegacyPdfReviewsCommand,
    VisualImplantReflownWysiwyg,
    WorkerCleanCommand,
    WorkerListCommand,
    WorkerQueueCommand,
    WorkerRunCommand,
    WorkflowCloneCommand,
    WorkflowListCommand,
    WorkflowStepCommand};
use Translate5\MaintenanceCli\Command\SegmentHistoryCommand;

$app = new Application('Translate5 CLI Maintenance', '1.0');
$commands = [
    new AuthTokenCommand(),
    new AuthTokenListCommand(),
    new AuthTokenDeleteCommand(),
    new CachePurgeCommand(),
    new ChangelogCommand(),
    new ConfigCommand(),
    new CronCommand(),
    new DatabaseBackupCommand(),
    new DatabaseStatCommand(),
    new DatabaseUpdateCommand(),
    new LogCommand(),
    new L10nAddCommand(),
    new L10nRemoveCommand(),
    new L10nTaskcreateCommand(),
    new MaintenanceAnnounceCommand(),
    new MaintenanceNotifyCommand(),
    new MaintenanceCommand(),
    new MaintenanceDisableCommand(),
    new MaintenanceMessageCommand(),
    new MaintenanceSetCommand(),
    new OkapiAddCommand(),
    new OkapiListCommand(),
    new OkapiPurgeCommand(),
    new OkapiUpdateCommand(),
    new OkapiCleanBconfsCommand(),
    new PluginDisableCommand(),
    new PluginEnableCommand(),
    new PluginListCommand(),
    new RoleListCommand(),
    new SegmentHistoryCommand(),
    new ServiceAutodiscoveryCommand(),
    new ServiceCheckCommand(),
    new ServicePingCommand(),
    new SessionImpersonateCommand(),
    new StatusCommand(),
    new SystemCheckCommand(),
    new SystemMailtestCommand(),
    new TaskCleanCommand(),
    new TaskInfoCommand(),
    new TaskImportCommand(),
    new TaskSkeletonfileCommand(),
    new TermportalReindexCommand(),
    new TermportalDatatypecheckCommand(),
    new T5MemoryDeleteTmCommand(),
    new T5memoryTmListCommand(),
    new T5MemoryReorganizeCommand(),
    new T5MemoryMigrationCommand(),
    new UserCreateCommand(),
    new UserUpdateCommand(),
    new UserInfoCommand(),
    new VisualConvertLegacyPdfReviewsCommand(),
    new VisualImplantReflownWysiwyg(),
    new WorkerCleanCommand(),
    new WorkerListCommand(),
    new WorkerQueueCommand(),
    new WorkerRunCommand(),
    new WorkflowCloneCommand(),
    new WorkflowListCommand(),
    new WorkflowStepCommand(),
];
if (file_exists('.git')) {
    $commands[] = new DevelopmentGithookCommand();
    $commands[] = new DevelopmentNewdbchangeCommand();
    $commands[] = new DevelopmentCreatetestCommand();
    $commands[] = new DevelopmentWorkertreeCommand();
    $commands[] = new TestApplytestsqlCommand();
    $commands[] = new TestRunAllCommand();
    $commands[] = new TestRunCommand();
    $commands[] = new TestRunSuiteCommand();
    $commands[] = new TestApplicationRunCommand();
    $commands[] = new TestAddIniSectionCommand();
    $commands[] = new TestCleanupCommand();
    $commands[] = new TestCreateFaultySegmentCommand();
    $commands[] = new ReleaseNotesCommand();
    $commands[] = new DevelopmentNewModelCommand();
    $commands[] = new DevelopmentEcodeCommand();
    $commands[] = new DevelopmentTriggerworkflowCommand();
    $commands[] = new \Translate5\MaintenanceCli\Command\TmxTs1040Command();
    $commands[] = new \Translate5\MaintenanceCli\Command\TmxFixOpenTM2Command();
    $commands[] = new DevelopmentOkapiBconfNextVersionCommand();
    $commands[] = new DevelopmentLocalServicesCommand();
    $commands[] = new DevelopmentSymlinksCommand();
}
$app->addCommands($commands);
$app->run();
