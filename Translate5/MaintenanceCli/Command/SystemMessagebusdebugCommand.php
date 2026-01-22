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
declare(strict_types=1);

namespace Translate5\MaintenanceCli\Command;

use editor_Plugins_FrontEndMessageBus_Init;
use Exception;
use JsonException;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use Zend_Http_Client_Exception;
use Zend_Http_Response;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Utils;

class SystemMessagebusdebugCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'system:messagebus:debug';

    protected function configure(): void
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Messagebus debug')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Messagebus debug');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @throws JsonException
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $messageBusUri = Zend_Registry::get('config')
            ->runtimeOptions
            ->plugins
            ->FrontEndMessageBus
            ->messageBusURI ?? null;

        if (empty($messageBusUri)) {
            $this->io->error(
                'Missing configuration: runtimeOptions.plugins.FrontEndMessageBus.messageBusURI must be set.'
            );

            return self::FAILURE;
        }

        $http = ZfExtended_Factory::get('Zend_Http_Client');
        $http->setUri($messageBusUri);
        $http->setParameterGet('version', editor_Plugins_FrontEndMessageBus_Init::CLIENT_VERSION);
        $response = $http->request($http::GET);
        $data = $this->parseResponse($response);

        $serverId = ZfExtended_Utils::installationHash('MessageBus');
        $instanceData = null;

        foreach (($data['instances'] ?? []) as $instance) {
            if (($instance['instance'] ?? null) === $serverId) {
                $instanceData = $instance;

                break;
            }
        }

        if ($instanceData === null) {
            $this->io->error('No data found for current instance with instanceId/serverId: ' . $serverId);

            return self::FAILURE;
        }

        $connections = $instanceData['connections'] ?? [];
        $sessions = $instanceData['sessions'] ?? [];

        $this->writeSessionTable($sessions, $connections);
        $this->writeOrphanConnectionsTable($sessions, $connections);
        $this->writeOpenedTasksTable($instanceData['channels'] ?? null, $sessions);

        return self::SUCCESS;
    }

    /**
     * @throws JsonException
     * @throws Zend_Exception
     */
    private function parseResponse(Zend_Http_Response $response): array
    {
        $validStates = [200, 201];
        if (! in_array($response->getStatus(), $validStates, true)) {
            throw new Zend_Exception(
                'FrontEndMessageBus: Response status "' . $response->getStatus() . '" indicates failure.'
            );
        }

        $responseBody = trim($response->getBody());
        $result = (empty($responseBody)) ? '' : json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR);

        if (empty($result) && strlen((string) $result) === 0) {
            throw new Zend_Exception('FrontEndMessageBus: empty JSON response.');
        }

        return $result;
    }

    private function writeSessionTable(array $sessions, array $connections): void
    {
        $connectionMap = $this->buildConnectionMap($connections);

        $rows = [];
        foreach ($sessions as $sessionId => $session) {
            $connectionIds = $connectionMap[$sessionId] ?? [];
            $rows[] = [
                'sessionId' => $sessionId,
                'userId' => $session['id'] ?? '',
                'login' => $session['login'] ?? '',
                'userGuid' => $session['userGuid'] ?? '',
                'loginTimeStamp' => $this->formatTimestamp($session['loginTimeStamp'] ?? null),
                'sessionStarted' => $this->formatTimestamp($session['sessionStarted'] ?? null),
                'connectionCount' => count($connectionIds),
                'connectionIds' => implode(',', $connectionIds),
            ];
        }

        if (empty($rows)) {
            $this->io->warning('No active sessions for this instance.');

            return;
        }

        $this->io->section('Sessions');
        $this->writeTable($rows);
    }

    private function formatTimestamp(int|string|null $timestamp): string
    {
        if (empty($timestamp)) {
            return '';
        }

        return date('Y-m-d H:i:s', (int) $timestamp);
    }

    private function buildConnectionMap(array $connections): array
    {
        $map = [];
        foreach ($connections as $connection) {
            $sessionId = $connection['sessionId'] ?? '';
            if ($sessionId === '') {
                continue;
            }
            $map[$sessionId] ??= [];
            $map[$sessionId][] = $connection['connectionId'] ?? '';
        }

        return $map;
    }

    private function writeOrphanConnectionsTable(array $sessions, array $connections): void
    {
        $rows = [];
        foreach ($connections as $connection) {
            $sessionId = $connection['sessionId'] ?? '';
            if ($sessionId === '' || ! array_key_exists($sessionId, $sessions)) {
                $rows[] = [
                    'connectionId' => $connection['connectionId'] ?? '',
                    'sessionId' => $sessionId,
                ];
            }
        }

        if (empty($rows)) {
            return;
        }

        $this->io->section('Orphan Connections');
        $this->writeTable($rows);
    }

    /**
     * @throws ReflectionException
     */
    private function writeOpenedTasksTable(?array $channels, array $sessions): void
    {
        if ($channels === null) {
            return;
        }

        $rows = [];
        $taskCache = [];
        $taskSessions = $this->buildTaskSessionMap($channels);

        foreach ($taskSessions as $taskGuid => $sessionIds) {
            if (! array_key_exists($taskGuid, $taskCache)) {
                $taskCache[$taskGuid] = $this->loadTaskInfo($taskGuid);
            }
            $taskInfo = $taskCache[$taskGuid];
            $sessionIds = array_keys($sessionIds);
            $sessionLines = [];
            foreach ($sessionIds as $sessionId) {
                $session = $sessions[$sessionId] ?? [];
                $sessionLines[] = implode('; ', [
                    $sessionId,
                    $session['userGuid'] ?? '',
                    $session['login'] ?? '',
                    $session['id'] ?? '',
                ]);
            }
            $rows[] = [
                'taskGuid' => $taskGuid,
                'taskId' => $taskInfo['taskId'],
                'taskName' => $taskInfo['taskName'],
                'By sessionId; userGuid; login; User ID' => implode(PHP_EOL, $sessionLines),
            ];
        }

        if (empty($rows)) {
            $this->io->warning('No opened tasks for this instance.');

            return;
        }

        $this->io->section('Opened Tasks');
        $this->writeTable($rows);
    }

    /**
     * @throws ReflectionException
     */
    private function loadTaskInfo(string $taskGuid): array
    {
        $task = ZfExtended_Factory::get('editor_Models_Task');

        try {
            $task->loadByTaskGuid($taskGuid);

            return [
                'taskId' => $task->getId(),
                'taskName' => $task->getTaskName(),
            ];
        } catch (Exception) {
            return [
                'taskId' => '',
                'taskName' => 'deleted',
            ];
        }
    }

    private function buildTaskSessionMap(array $channels): array
    {
        $taskSessions = [];
        foreach ($channels as $channelData) {
            if (! is_array($channelData) || ! isset($channelData['taskToSessions'])) {
                continue;
            }
            foreach ($channelData['taskToSessions'] as $taskGuid => $sessionIds) {
                $taskSessions[$taskGuid] ??= [];
                foreach ((array) $sessionIds as $sessionId) {
                    $taskSessions[$taskGuid][$sessionId] = true;
                }
            }
        }

        return $taskSessions;
    }
}
