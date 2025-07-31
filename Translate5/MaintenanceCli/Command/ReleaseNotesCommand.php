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
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Version;
use JiraRestApi\JiraException;
use JiraRestApi\Project\ProjectService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Utils;

class ReleaseNotesCommand extends Translate5AbstractCommand
{
    /**
     * Translate5 JIRA Project key
     */
    public const PROJECT_KEY = 'TRANSLATE';

    // the name of the command (the part after "bin/console")
    public const NEW_FEATURE = 'new feature';

    protected static $defaultName = 'release:notes';

    /**
     * Local storage for JIRA server config
     */
    protected ?ArrayConfiguration $jiraConf = null;

    /**
     * selected release version
     */
    protected ?Version $releaseVersion = null;

    /**
     * Container for the collected important release notes
     */
    protected array $importantNotes = [];

    /**
     * Container for the different issues
     */
    protected array $issues = [
        self::NEW_FEATURE => [],
        'change' => [],
        'fix' => [],
    ];

    /**
     * available types and labels
     */
    protected array $types = [
        self::NEW_FEATURE => 'New features',
        'change' => 'Changes',
        'fix' => 'Fixes',
    ];

    protected function configure(): void
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Updates the release notes, only usable in development installations.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Tool to update the release notes from JIRA, only usable in development installations.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws JiraException
     * @throws Zend_Exception
     * @throws Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $this->writeTitle('Update the translate5 release notes.');

        $version = ZfExtended_Utils::getAppVersion();
        if ($version != ZfExtended_Utils::VERSION_DEVELOPMENT) {
            $this->io->error('This is a development command and can be run only in development instances!');

            return 1;
        }

        //init cookie based config
        $this->initConfig();

        try {
            $this->askReleaseVersion();
        } catch (JiraException $e) {
            if (! str_contains($e->getMessage(), 'CURL HTTP Request Failed: Status Code : 401')) {
                throw $e;
            }
            $this->initConfig(true);
            $this->askReleaseVersion();
        }
        $this->loadIssues();

        $url = \Zend_Registry::get('config')->runtimeOptions->jiraIssuesUrl;
        $this->io->text([
            '<info>URL to list and modify issues of this release</info>',
            parse_url($url, PHP_URL_SCHEME) . '://' .
            parse_url($url, PHP_URL_HOST) . '/issues/?jql=project%20%3D%20' .
            self::PROJECT_KEY . '%20and%20fixVersion%20%3D%20%22' . $this->releaseVersion->id . '%22',
        ]);

        if (! $this->io->confirm('Does the important release notes contain all API / GUI relevant changes?', false)) {
            return self::SUCCESS;
        }

        $version = trim(str_replace(['translate5 - ', ' '], ['', '-'], $this->releaseVersion->name));

        if (! $this->updateReleaseVersionFile($version)) {
            $this->io->error('The new version could not be write to build/release file!');

            return self::FAILURE;
        }

        $sql = $this->createSql();
        $md = $this->updateChangeLog($version);
        $sql = str_replace(getcwd() . '/', '', $sql);
        $md = str_replace(getcwd() . '/', '', $md);
        $this->io->writeln([
            'Execute outside of the container: ',
            'git add application/modules/editor/PrivatePlugins',
            'git add library/ZfExtended',
            'git add ' . $md,
            'git add ' . $sql,
            'git commit -m "change log and submodules release ' . $this->releaseVersion->name
            . '" application/modules/editor/PrivatePlugins library/ZfExtended ' . $sql . ' ' . $md,
            'git push',
        ]);

        if (! $this->releaseVersion->released) {
            $this->io->note('Please release the version on URL https://jira.translate5.net/projects/TRANSLATE/versions/'
                . $this->releaseVersion->id);
        }

        return 0;
    }

    /**
     * Asks the user for the configuration to access JIRA
     * @param boolean $askCredentials
     */
    protected function initConfig($askCredentials = false)
    {
        $conf = [
            'jiraHost' => 'https://jira.translate5.net',
            // for basic authorization:
            //'jiraUser' => $jiraUser,
            //'jiraPassword' => $jiraPassword,
            // to enable session cookie authorization (with basic authorization only)
            'cookieAuthEnabled' => true,
            'cookieFile' => 'jira-cookie.txt',
        ];

        if ($askCredentials) {
            //IMPORTANT: currently no auth needed, since all data is publically available
            //$conf['jiraUser'] = $this->io->ask('Please enter JIRA username (is NOT stored locally)');
            //$conf['jiraPassword'] = $this->io->ask('Please enter JIRA password (is NOT stored locally)');
        }

        $this->jiraConf = new ArrayConfiguration($conf);
    }

    /**
     * Queries JIRA for releasable versions and asks the user for
     * which version the release notes should be queried and created:
     * @throws JiraException
     */
    protected function askReleaseVersion()
    {
        $unreleasedProjects = [];
        $proj = new ProjectService($this->jiraConf);
        $vers = $proj->getVersions(self::PROJECT_KEY);

        foreach ($vers as $v) {
            if ($v->released) {
                continue;
            }
            if (empty($v->releaseDate)) {
                $name = $v->name;
            } else {
                $name = $v->name . ' (planned for ' . $v->releaseDate . ')';
            }
            $unreleasedProjects[$name] = $v;
        }

        $version = $this->io->choice(
            'Choose the version for which the release notes should be created',
            array_keys($unreleasedProjects),
            0
        );
        $this->releaseVersion = $unreleasedProjects[$version];
    }

    protected function loadIssues()
    {
        $jql = 'project = ' . self::PROJECT_KEY . ' and fixVersion = "' . $this->releaseVersion->name . '"';

        $issueService = new IssueService($this->jiraConf);

        $ret = $issueService->search($jql, 0, -1, [
            'summary',
            'description',
            'components',
            'issuetype',
            'customfield_11800', //'ChangeLog Description'
            'customfield_11700', //'Important release notes'
        ]); //start at 0 and max = -1 for unlimited
        foreach ($ret->getIssues() as $issue) {
            $item = new \stdClass();
            $item->key = $issue->key;
            $item->summary = trim($issue->fields->summary);
            $item->components = join(', ', array_column($issue->fields->components ?? [], 'name'));
            $item->description = empty($issue->fields->customfield_11800)
                ? $issue->fields->description : $issue->fields->customfield_11800;
            if (! empty($issue->fields->customfield_11700)) {
                $this->importantNotes[$issue->key] = preg_replace(
                    '~\R~u',
                    "\n",
                    trim($issue->fields->customfield_11700)
                );
            }

            //to get the IDs go to https://jira.translate5.net/plugins/servlet/project-config/TRANSLATE/summary
            // and investigate the Issue Type Links
            switch ($issue->fields->issuetype->id) {
                case 1: //Bug
                case 8: //Technical Task (should never occur, since technical task should be used in hidden projects only for reference)
                    $this->issues['fix'][$issue->key] = $item;

                    break;
                case 2: //New Feature
                case 6: //Epic
                case 7: //Story
                    $this->issues[self::NEW_FEATURE][$issue->key] = $item;

                    break;
                case 3: //Task
                case 4: //Improvement
                case 5: //Sub Task
                case 10000: //Todo
                    $this->issues['change'][$issue->key] = $item;

                    break;
                default:
                    throw new Exception('Jira provides an unknown issue type in issue ' . $issue->key . ' ' . print_r($issue->fields->issuetype, 1));
            }
        }

        $this->io->section('Release notes preview for ' . $this->releaseVersion->name);

        if (! empty($this->importantNotes)) {
            $this->io->section('Important Release Notes');
            foreach ($this->importantNotes as $key => $note) {
                $this->io->text([
                    '<info>' . $key . '</info>',
                    $this->linkIssue($key, true),
                    $note,
                    '']);
            }
        }

        foreach ($this->types as $type => $label) {
            if (empty($this->issues[$type])) {
                continue;
            }
            $this->io->section($label);
            foreach ($this->issues[$type] as $issue) {
                $this->io->text([
                    '<info>' . $issue->key . ' (' . $issue->components . '): ' . $issue->summary . '</info>',
                    $this->linkIssue($issue->key, true),
                    $issue->description, '',
                ]);
            }
        }
    }

    /**
     * creates the SQL changelog and returns the path to it
     */
    protected function createSql(): string
    {
        $sql = '
-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of translate5.
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ';
        $sqlData = [];

        // mapping of groups to ints
        $groups = [
            "noRights" => 0,
            "basic" => 1,
            "editor" => 2,
            //WARNING: just adding and changing (admin / pm) integers is not possible.
            // the values must also respected in the GUI and application
            //"termCustomerSearch"=>4,
            //"termProposer"=>8,
            //"instantTranslate"=>16,
            "pm" => 4, // was 32
            "admin" => 8, // was 64
        ];

        $date = date('Y-m-d', time());

        //headlines

        foreach ($this->issues[self::NEW_FEATURE] as $row) {
            $sqlData[] = $this->makeSqlRow($row, 'feature', $date, $groups);
        }

        foreach ($this->issues['change'] as $row) {
            $sqlData[] = $this->makeSqlRow($row, 'change', $date, $groups);
        }

        foreach ($this->issues['fix'] as $row) {
            $sqlData[] = $this->makeSqlRow($row, 'bugfix', $date, $groups);
        }

        $sql .= join(",\n", $sqlData) . ';';

        $version = str_replace(
            ['translate5 - ', ' '],
            ['', '-'],
            $this->releaseVersion->name
        ) . '-' . date('Y-m-d', time());
        $filename = APPLICATION_ROOT . '/application/modules/editor/database/sql-changelog-' . $version . '.sql';
        $this->io->success('Created SQL changelog file ' . $filename);
        file_put_contents($filename, $sql);

        return $filename;
    }

    /**
     * Injects the MarkDown changelog into the CHANGELOG.md file
     * returns the filename of the changelog.md file
     * @throws Zend_Exception
     */
    protected function updateChangeLog(string $version): string
    {
        $date = date('Y-m-d', time());

        $importantNotes = [];
        if (! empty($this->importantNotes)) {
            foreach ($this->importantNotes as $key => $note) {
                $importantNotes[] = '#### ' . $this->linkIssue($key);
                $importantNotes[] = $note;
                $importantNotes[] = '';
            }
        }
        $importantNotes = join("\n", $importantNotes);

        $md = "\n\n## [$version] - $date\n\n### Important Notes:\n$importantNotes \n\n";

        if (! empty($this->issues[self::NEW_FEATURE])) {
            $md .= "\n### Added\n";
        }
        foreach ($this->issues[self::NEW_FEATURE] as $row) {
            $md .= '**' . $this->linkIssue($row->key) . ': ' . $row->components . ' - ' . $row->summary . "** <br>\n" . $row->description . "\n\n";
        }

        if (! empty($this->issues['change'])) {
            $md .= "\n### Changed\n";
        }
        foreach ($this->issues['change'] as $row) {
            $md .= '**' . $this->linkIssue($row->key) . ': ' . $row->components . ' - ' . $row->summary . "** <br>\n" . $row->description . "\n\n";
        }

        if (! empty($this->issues['fix'])) {
            $md .= "\n### Bugfixes\n";
        }
        foreach ($this->issues['fix'] as $row) {
            $md .= '**' . $this->linkIssue($row->key) . ': ' . $row->components . ' - ' . $row->summary . "** <br>\n" . $row->description . "\n\n";
        }

        $filename = APPLICATION_ROOT . '/docs/CHANGELOG.md';
        $content = file_get_contents($filename);
        if (mb_strpos($content, '[' . $version . ']') !== false) {
            $this->io->warning('Check the changelog! A version ' . $version . ' does exist already!');
        }
        $lastPos = mb_strpos($content, "\n## [");
        $this->io->success('Updated changelog file ' . $filename);
        file_put_contents($filename, substr_replace($content, $md, $lastPos, 0));

        return $filename;
    }

    protected function makeSqlRow($row, $type, $date, $groups)
    {
        //using only the issue nr, not the issue text
        $issue = $row->key;
        $title = addcslashes($row->components . ' - ' . $row->summary, "'");
        $desc = addcslashes($row->description, "'");

        //calculate the group integer
        //FIXME currently we deliver all changes for all users
        $gid = 15;

        //     "noRights"=>0,
        //         "basic"=>1,
        //         "editor"=>2,
        //         "pm"=>4, // was 32
        //         "admin"=>8 // was 64
        //         $gid =
        //         $group = explode(',', $row->usergroup);
        //         $gid = 0;
        //         foreach($group as $one) {
        //             $gid += $groups[$one];
        //         }

        return "('$date', '$issue', '$type', '$title', '$desc', '$gid')";
    }

    /**
     * Converts a TRANSLATE-XXX key into a link to JIRA
     * @param boolean $plain returns by default a MarkDown link, if plain = true it returns just the link
     * @throws Zend_Exception
     */
    protected function linkIssue(string $issue, bool $plain = false): string
    {
        $url = str_replace('{0}', '$1', \Zend_Registry::get('config')->runtimeOptions->jiraIssuesUrl);
        if (! $plain) {
            $url = '[$1](' . $url . ')';
        }

        return preg_replace('/(TRANSLATE-\d+)/', $url, $issue);
    }

    private function updateReleaseVersionFile(string $version): bool
    {
        $releaseVersionFile = APPLICATION_ROOT . '/build/release';
        list($major, $minor, $patch) = explode('.', $version);
        if (is_file($releaseVersionFile) && is_writable($releaseVersionFile)) {
            file_put_contents($releaseVersionFile, "#release version: created by ./translate5.sh release:notes command
MAJOR_VER=$major
MINOR_VER=$minor
BUILD=$patch
");

            return true;
        }

        return false;
    }
}
