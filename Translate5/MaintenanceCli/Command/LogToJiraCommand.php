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

use Exception;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\IssueType;
use JsonException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class LogToJiraCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'log:tojira';

    private const string PROJECT_KEY = 'TRANSLATE';

    private const string ISSUE_TYPE = 'Bug';

    private const int MAX_SUMMARY_LENGTH = 240;

    private const string ENV_JIRA_USER = 'JIRA_USER';

    private const string ENV_JIRA_PASSWORD = 'JIRA_PASSWORD';

    private const string ENV_JIRA_ACCESS_TOKEN = 'JIRA_ACCESS_TOKEN';

    // get ID via mouseover on ... menu in editing components
    //private const array COMPONENTS = ['11700'];
    private const array COMPONENTS = ['Editor general'];

    //urgency, 10321 → high
    private const array CUSTOMFIELD_URGENCY = [
        'value' => 'High',
    ];

    //peer developer
    private const array CUSTOMFIELD_12400 = [
        [
            'name' => 'tlauria',
        ],
    ];

    private const string DEFAULT_CHANGE_LOG = 'PHP error fixed';

    protected ?ArrayConfiguration $jiraConf = null;

    private bool $hasConfidentialData = false;

    protected function configure(): void
    {
        $this
            ->setDescription('Create a Jira issue from a translate5 log entry.')
            ->setHelp('Creates a Jira issue based on a log entry. '
                . 'Uses env JIRA_ACCESS_TOKEN if set, otherwise JIRA_USER/JIRA_PASSWORD or prompts.')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Log entry id to convert into a Jira issue.'
            )
            ->addOption(
                'confidential',
                'c',
            );
    }

    /**
     * @throws Zend_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();
        $this->writeTitle('Create a Jira issue from a log entry.');

        $logId = (int) $input->getArgument('id');
        $this->hasConfidentialData = (bool) $input->getOption('confidential');

        if ($logId <= 0) {
            $this->io->error('Log id must be a positive integer.');

            return self::FAILURE;
        }

        $log = new \ZfExtended_Models_Log();

        try {
            $log->load($logId);
        } catch (Exception $e) {
            $this->io->error('No log entry found for id ' . $logId . '.');

            return self::FAILURE;
        }

        $logRow = (array) $log->getDataObject();
        $summary = $this->buildJiraSummary($logRow);
        $description = $this->buildJiraDescription($logRow);
        $internalInformation = $this->buildInternalInformation($logRow);

        $this->io->section('Log entry');
        $this->showLogDetail($logRow);

        $this->io->section('Jira issue preview');
        $this->showJiraPreview($summary, $description, $internalInformation);

        if (! $this->io->confirm('Create the Jira issue with the previewed data?', false)) {
            return self::SUCCESS;
        }

        $jiraAuth = $this->getJiraCredentials();
        if ($jiraAuth['useToken'] && $jiraAuth['token'] === '') {
            $this->io->error('Jira access token is required to create the issue.');

            return self::FAILURE;
        }
        if (! $jiraAuth['useToken'] && ($jiraAuth['user'] === '' || $jiraAuth['password'] === '')) {
            $this->io->error('Jira credentials are required to create the issue.');

            return self::FAILURE;
        }

        $this->initJiraConfig($jiraAuth['user'], $jiraAuth['password'], $jiraAuth['token'], $jiraAuth['useToken']);

        $issueField = new IssueField();
        $issueType = new IssueType();
        $issueType->name = self::ISSUE_TYPE;

        $issueField
            ->setProjectKey(self::PROJECT_KEY)
            ->setIssueType($issueType)
            ->setSummary($summary)
            ->setDescription(implode("\n", $description));

        $issueField->addComponents(self::COMPONENTS);
        $issueField->addCustomField('customfield_11208', self::CUSTOMFIELD_URGENCY);
        $issueField->addCustomField('customfield_12400', self::CUSTOMFIELD_12400);
        $issueField->addCustomField('customfield_11800', self::DEFAULT_CHANGE_LOG);
        $issueField->addCustomField('customfield_10904', implode("\n", $internalInformation));

        try {
            $issueService = new IssueService($this->jiraConf);
            $issue = $issueService->create($issueField);
        } catch (Exception $e) {
            $this->io->error('Jira issue creation failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! empty($issue->key)) {
            $this->io->success('Created Jira issue ' . $issue->key . '.');
            $this->io->text($this->linkIssue($issue->key));
        } else {
            $this->io->success('Created Jira issue.');
        }

        return self::SUCCESS;
    }

    private function initJiraConfig(string $jiraUser, string $jiraPassword, string $accessToken, bool $useToken): void
    {
        $conf = [
            'jiraHost' => 'https://jira.translate5.net',
        ];
        if ($useToken) {
            $conf['useTokenBasedAuth'] = true;
            $conf['personalAccessToken'] = $accessToken;
        } else {
            $conf['jiraUser'] = $jiraUser;
            $conf['jiraPassword'] = $jiraPassword;
        }

        $this->jiraConf = new ArrayConfiguration($conf);
    }

    private function getJiraCredentials(): array
    {
        $accessToken = (string) getenv(self::ENV_JIRA_ACCESS_TOKEN);
        if ($accessToken === '') {
            //try if given with LC_ prefix, LC_ prefixed may be allowed by default
            $accessToken = (string) getenv('LC_' . self::ENV_JIRA_ACCESS_TOKEN);
        }
        if ($accessToken !== '') {
            return [
                'useToken' => true,
                'token' => $accessToken,
                'user' => '',
                'password' => '',
            ];
        }

        $jiraUser = (string) getenv(self::ENV_JIRA_USER);
        $jiraPassword = (string) getenv(self::ENV_JIRA_PASSWORD);

        if ($jiraUser !== '') {
            if ($jiraPassword === '') {
                $jiraPassword = (string) $this->io->askHidden('Jira password or API token');
            }

            return [
                'useToken' => false,
                'token' => '',
                'user' => $jiraUser,
                'password' => $jiraPassword,
            ];
        }

        $userOrToken = (string) $this->io->ask('Jira username or accesstoken');
        if (strlen($userOrToken) <= 40) {
            $jiraUser = $userOrToken;
            if ($jiraPassword === '') {
                $jiraPassword = (string) $this->io->askHidden('Jira password or API token');
            }

            return [
                'useToken' => false,
                'token' => '',
                'user' => $jiraUser,
                'password' => $jiraPassword,
            ];
        }

        return [
            'useToken' => true,
            'token' => $userOrToken,
            'user' => '',
            'password' => '',
        ];
    }

    private function buildJiraSummary(array $logRow): string
    {
        $parts = [];
        if (! empty($logRow['eventCode'])) {
            $parts[] = (string) $logRow['eventCode'];
        }
        if (! empty($logRow['domain'])) {
            $parts[] = (string) $logRow['domain'];
        }

        $prefix = empty($parts) ? '[log]' : '[' . implode(' ', $parts) . ']';
        $message = trim((string) ($logRow['message'] ?? ''));
        $summary = trim($prefix . ' ' . $message);

        if ($summary === $prefix) {
            $summary = $prefix . ' Log entry ' . (string) ($logRow['id'] ?? '');
        }

        if (strlen($summary) > self::MAX_SUMMARY_LENGTH) {
            $summary = substr($summary, 0, self::MAX_SUMMARY_LENGTH - 3) . '...';
        }

        return $summary;
    }

    private function buildInternalInformation(array $logRow): array
    {
        $lines = [
            'Domain: ' . (string) ($logRow['domain'] ?? ''),
        ];

        if (! empty($logRow['httpHost'])) {
            $lines[] = 'Host: ' . (string) $logRow['httpHost'];
        }

        if (! empty($logRow['url'])) {
            $method = trim((string) ($logRow['method'] ?? ''));
            $url = trim((string) $logRow['url']);
            $lines[] = 'URL: ' . trim($method . ' ' . $url);
        }

        if (! empty($logRow['userGuid'])) {
            $lines[] = 'User: ' . (string) ($logRow['userLogin'] ?? '') . ' (' . (string) $logRow['userGuid'] . ')';
        }

        if (! empty($logRow['extra'])) {
            $lines[] = '';
            $lines[] = 'Extra:';
            $lines[] = $this->normalizeExtraForDescription((string) $logRow['extra']);
        }

        return $lines;
    }

    private function buildJiraDescription(array $logRow): array
    {
        $lines = [
            'Log ID: ' . (string) ($logRow['id'] ?? ''),
            'Created: ' . (string) ($logRow['created'] ?? ''),
            'Level: ' . (string) ($logRow['level'] ?? ''),
            'Event Code: ' . (string) ($logRow['eventCode'] ?? ''),
            'Domain: ' . (string) ($logRow['domain'] ?? ''),
            'Message: ' . (string) ($logRow['message'] ?? ''),
            'App Version: ' . (string) ($logRow['appVersion'] ?? ''),
            'File: ' . (string) ($logRow['file'] ?? '') . ' (' . (string) ($logRow['line'] ?? '') . ')',
        ];

        if (! empty($logRow['duplicates'])) {
            $lines[] = 'Duplicates: ' . (string) $logRow['duplicates'];
            $lines[] = 'Last Seen: ' . (string) ($logRow['last'] ?? '');
        }

        if (! empty($logRow['trace'])) {
            $lines[] = '';
            $lines[] = 'Trace:';
            $lines[] = (string) $logRow['trace'];
        }

        if (! empty($logRow['worker'])) {
            $lines[] = 'Worker: ' . (string) $logRow['worker'];
        }

        return $lines;
    }

    private function normalizeExtraForDescription(string $extra): string
    {
        try {
            $decoded = json_decode($extra, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $extra;
        }

        if (! empty($decoded['task']) && ! empty($decoded['task']['taskGuid']) && ! empty($decoded['task']['taskName'])) {
            settype($decoded['task']['taskNr'], 'string');
            $decoded['task'] = $decoded['task']['taskName'] . ' #' . $decoded['task']['taskNr']
                . ' ID:' . $decoded['task']['id'] . ' GUID:' . $decoded['task']['taskGuid'];
        }

        return (string) json_encode($decoded, JSON_PRETTY_PRINT);
    }

    private function showLogDetail(array $row): void
    {
        $level = $row['level'] ?? null;
        $levelLabel = is_numeric($level) && isset(LogCommand::LEVELS[(int) $level])
            ? LogCommand::LEVELS[(int) $level]
            : (string) $level;

        $out = [
            'id: ' . (string) ($row['id'] ?? ''),
            'level: ' . $levelLabel,
            'created: ' . OutputFormatter::escape((string) ($row['created'] ?? '')),
            'event code: ' . OutputFormatter::escape((string) ($row['eventCode'] ?? '')),
            'domain: ' . OutputFormatter::escape((string) ($row['domain'] ?? '')),
            'message: ' . OutputFormatter::escape((string) ($row['message'] ?? '')),
            'app version: ' . OutputFormatter::escape((string) ($row['appVersion'] ?? '')),
            'file: ' . OutputFormatter::escape((string) ($row['file'] ?? ''))
                . ' (' . OutputFormatter::escape((string) ($row['line'] ?? '')) . ')',
        ];

        if (! empty($row['duplicates'])) {
            $out[] = 'duplicates: ' . OutputFormatter::escape((string) $row['duplicates']);
            $out[] = 'last: ' . OutputFormatter::escape((string) ($row['last'] ?? ''));
        }

        if (! empty($row['httpHost'])) {
            $out[] = 'host: ' . OutputFormatter::escape((string) $row['httpHost']);
        }

        if (! empty($row['url'])) {
            $out[] = 'url: ' . OutputFormatter::escape(trim((string) ($row['method'] ?? '') . ' ' . $row['url']));
        }

        if (! empty($row['userGuid'])) {
            $out[] = 'user: ' . OutputFormatter::escape((string) ($row['userLogin'] ?? '')) . ' ('
                . OutputFormatter::escape((string) $row['userGuid']) . ')';
        }

        if (! empty($row['worker'])) {
            $out[] = 'worker: ' . OutputFormatter::escape((string) $row['worker']);
        }

        if (! empty($row['trace'])) {
            $out[] = 'trace: ' . OutputFormatter::escape((string) $row['trace']);
        }

        if (! empty($row['extra'])) {
            $out[] = 'extra: ' . OutputFormatter::escape($this->normalizeExtraForDescription((string) $row['extra']));
        }

        $this->io->text($out);
    }

    private function showJiraPreview(string $summary, array $description, array $internalInformation): void
    {
        if ($this->hasConfidentialData) {
            array_unshift($internalInformation, $summary);
            $summary = 'TO BE WRITTEN';
            $internalInformation = array_merge($description, $internalInformation);
            $description = [$summary];
        }

        $lines = [
            'Project: ' . self::PROJECT_KEY,
            'Issue Type: ' . self::ISSUE_TYPE,
            'Components: ' . implode(', ', self::COMPONENTS),
            'customfield_11208 (Urgency): ' . self::CUSTOMFIELD_URGENCY['value'],
            'customfield_12400 (Peer developer): ' . OutputFormatter::escape((string) json_encode(self::CUSTOMFIELD_12400)),
            'customfield_11800 (Changelog): ' . self::DEFAULT_CHANGE_LOG,
            'Summary: ' . OutputFormatter::escape($summary),
            'Description:',
        ];

        if (! empty($description)) {
            $descriptionLines = array_map(
                static fn (string $line): string => '  ' . OutputFormatter::escape($line),
                $description,
            );

            $this->io->section('Public Information');
            $this->io->text(array_merge($lines, $descriptionLines));
        }

        if (! empty($internalInformation)) {
            $internalLines = array_map(
                static fn (string $line): string => '  ' . OutputFormatter::escape($line),
                $internalInformation,
            );
            $this->io->section('Internal Information');
            $this->io->text(array_merge($lines, $internalLines));
        }

        $this->io->warning([
            'Check above Summary, Message and filepaths for confidential information! '
            . 'Use -c to send all data as internal info instead.',
            'User / HTTP Host / URL / extra are send as internal info field anyway.',
        ]);
    }

    private function linkIssue(string $issueKey): string
    {
        $url = str_replace('{0}', '$1', \Zend_Registry::get('config')->runtimeOptions->jiraIssuesUrl);

        return (string) preg_replace('/(TRANSLATE-\d+)/', $url, $issueKey);
    }
}
