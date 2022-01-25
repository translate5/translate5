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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use JiraRestApi\JiraException;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Project\ProjectService;
use JiraRestApi\Issue\Version;
use JiraRestApi\Issue\IssueService;

class ReleaseNotesCommand extends Translate5AbstractCommand
{
    /**
     * Translate5 JIRA Project key
     */
    const PROJECT_KEY = 'TRANSLATE';
    
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'release:notes';

    /**
     * Local storage for JIRA server config
     * @var ArrayConfiguration
     */
    protected $jiraConf = null;
    
    /**
     * selected release version
     * @var Version
     */
    protected $releaseVersion = null;
    
    /**
     * Container for the collected important release notes
     * @var array
     */
    protected $importantNotes = [];
    
    /**
     * Container for the different issues
     * @var array
     */
    protected $issues = [
        'new feature' => [],
        'change' => [],
        'fix' => [],
    ];
    
    /**
     * available types and labels
     */
    protected $types = [
        'new feature' => 'New features',
        'change' => 'Changes',
        'fix' => 'Fixes',
    ];
    
    protected function configure()
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
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $this->writeTitle('Update the translate5 release notes.');

        $version = \ZfExtended_Utils::getAppVersion();
        if($version != \ZfExtended_Utils::VERSION_DEVELOPMENT) {
            $this->io->error('This is a development command and can be run only in development instances!');
            return 1;
        }
        
        //init cookie based config
        $this->initConfig();
        try {
            $this->askReleaseVersion();
        }
        catch (JiraException $e) {
            if(strpos($e->getMessage(), 'CURL HTTP Request Failed: Status Code : 401') === false) {
                throw $e;
            }
            $this->initConfig(true);
            $this->askReleaseVersion();
        }
        $this->loadIssues();
        
        $url = \Zend_Registry::get('config')->runtimeOptions->jiraIssuesUrl;
        $this->io->text([
            '<info>URL to list and modify issues of this release</info>',
            parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).'/issues/?jql=project%20%3D%20'.self::PROJECT_KEY.'%20and%20fixVersion%20%3D%20%22'.$this->releaseVersion->id.'%22'
        ]);
        
        if(!$this->io->confirm('Does the important release notes contain all API / GUI relevant changes?', false)) {
            return 0;
        }
        if($this->io->confirm('Create the SQL and Update the change log (or modify them in JIRA again)?', true)) {
            $sql = $this->createSql();
            $md = $this->updateChangeLog();
            if($this->io->confirm('git: stage above files and commit them?', true)) {
                $sql = str_replace(getcwd().'/', '', $sql);
                $md = str_replace(getcwd().'/', '', $md);
                passthru('git add '.$md);
                passthru('git add '.$sql);
                passthru('git commit -m "change log release '.$this->releaseVersion->name.'" '.$sql.' '.$md);
                passthru('git push');
            }
        }
        if(!$this->releaseVersion->released) {
            $this->io->note('Please release the version on URL https://jira.translate5.net/projects/TRANSLATE/versions/'.$this->releaseVersion->id);
        }
        return 0;
    }
    
    /**
     * Asks the user for the configuration to access JIRA
     * @param boolean $askCredentials
     */
    protected function initConfig($askCredentials = false) {
        $conf = [
            'jiraHost' => 'https://jira.translate5.net',
            // for basic authorization:
            //'jiraUser' => $jiraUser,
            //'jiraPassword' => $jiraPassword,
            // to enable session cookie authorization (with basic authorization only)
            'cookieAuthEnabled' => true,
            'cookieFile' => 'jira-cookie.txt',
        ];
        
        if($askCredentials) {
        //IMPORTANT: currently no auth needed, since all data is publically available
            //$conf['jiraUser'] = $this->io->ask('Please enter JIRA username (is NOT stored locally)');
            //$conf['jiraPassword'] = $this->io->ask('Please enter JIRA password (is NOT stored locally)');
        }
        
        $this->jiraConf = new ArrayConfiguration($conf);
    }
    
    /**
     * Queries JIRA for releasable versions and asks the user for which version the release notes should be queried and created:
     */
    protected function askReleaseVersion() {
        $unreleasedProjects = [];
        $proj = new ProjectService($this->jiraConf);
        $vers = $proj->getVersions(self::PROJECT_KEY);
        
        foreach ($vers as $v) {
            /* @var $v Version */
            if($v->released) {
                continue;
            }
            if(empty($v->releaseDate)) {
                $name = $v->name;
            }
            else {
                $name = $v->name.' (planned for '.$v->releaseDate.')';
            }
            $unreleasedProjects[$name] = $v;
        }
        
        $version = $this->io->choice('Choose the version for which the release notes should be created', array_keys($unreleasedProjects));
        $this->releaseVersion = $unreleasedProjects[$version];
    }
    
    protected function loadIssues() {
        $jql = 'project = '.self::PROJECT_KEY.' and fixVersion = "'.$this->releaseVersion->name.'"';
        
        $issueService = new IssueService($this->jiraConf);
        
        $ret = $issueService->search($jql, 0, -1, [
            'summary',
            'description',
            'components',
            'issuetype',
            'customfield_11800', //'ChangeLog Description'
            'customfield_11700', //'Important release notes'
        ]); //start at 0 and max = -1 for unlimited
        foreach($ret->getIssues() as $issue) {
            $item = new \stdClass();
            $item->key = $issue->key;
            $item->summary = trim($issue->fields->summary);
            $item->components = join(', ', array_column($issue->fields->components ?? [], 'name'));
            $item->description = empty($issue->fields->customfield_11800) ? $issue->fields->description : $issue->fields->customfield_11800;
            if(!empty($issue->fields->customfield_11700)) {
                $this->importantNotes[$issue->key] = preg_replace('~\R~u', "\n", trim($issue->fields->customfield_11700));
            }
            
            //to get the IDs go to https://jira.translate5.net/plugins/servlet/project-config/TRANSLATE/summary
            // and investigate the Issue Type Links
            switch($issue->fields->issuetype->id) {
                case 1: //Bug
                case 8: //Technical Task (should never occur, since technical task should be used in hidden projects only for reference)
                    $this->issues['fix'][$issue->key] = $item;
                    break;
                case 2: //New Feature
                case 6: //Epic
                case 7: //Story
                    $this->issues['new feature'][$issue->key] = $item;
                    break;
                case 3: //Task
                case 4: //Improvement
                case 5: //Sub Task
                case 10000: //Todo
                    $this->issues['change'][$issue->key] = $item;
                    break;
                default:
                    throw new \Exception('Jira provides an unknown issue type in issue '.$issue->key.' '.print_r($issue->fields->issuetype,1));
            }
        }
        
        $this->io->section('Release notes preview for '.$this->releaseVersion->name);
        
        if(!empty($this->importantNotes)) {
            $this->io->section('Important Release Notes');
            foreach($this->importantNotes as $key => $note) {
                $this->io->text([
                    '<info>'.$key.'</info>',
                    $this->linkIssue($key, true),
                    $note,
                '']);
            }
        }
        
        foreach($this->types as $type => $label) {
            if(empty($this->issues[$type])) {
                continue;
            }
            $this->io->section($label);
            foreach($this->issues[$type] as $issue) {
                $this->io->text([
                    '<info>'.$issue->key.' ('.$issue->components.'): '.$issue->summary.'</info>',
                    $this->linkIssue($issue->key, true),
                    $issue->description, ''
                ]);
            }
        }
    }

    /**
     * creates the SQL changelog and returns the path to it
     * @return string
     */
    protected function createSql(): string {
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
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ';
        $sqlData = [];
        
        // mapping of groups to ints
        $groups = [
            "noRights"=>0,
            "basic"=>1,
            "editor"=>2,
            //WARNING: just adding and changing (admin / pm) integers is not possible.
            // the values must also respected in the GUI and application
            //"termCustomerSearch"=>4,
            //"termProposer"=>8,
            //"instantTranslate"=>16,
            "pm"=>4, // was 32
            "admin"=>8 // was 64
        ];
        
        $date = date('Y-m-d', time());
        
        //headlines
        
        foreach($this->issues['new feature'] as $row) {
            $sqlData[] = $this->makeSqlRow($row, 'feature', $date, $groups);
        }
        
        foreach($this->issues['change'] as $row) {
            $sqlData[] = $this->makeSqlRow($row, 'change', $date, $groups);
        }
        
        foreach($this->issues['fix'] as $row) {
            $sqlData[] = $this->makeSqlRow($row, 'bugfix', $date, $groups);
        }
        
        $sql .= join(",\n", $sqlData).';';
        
        $version = str_replace(['translate5 - ', ' '], ['', '-'], $this->releaseVersion->name).'-'.date('Y-m-d', time());
        $filename = APPLICATION_ROOT.'/application/modules/editor/database/sql-changelog-'.$version.'.sql';
        $this->io->success('Created SQL changelog file '.$filename);
        file_put_contents($filename, $sql);
        return $filename;
    }
    
    /**
     * Injects the MarkDown changelog into the CHANGELOG.md file
     * @return string returns the filename of the changelog.md file
     */
    protected function updateChangeLog(): string {
        $date = date('Y-m-d', time());
        
        //headlines
        $version = str_replace(['translate5 - ', ' '], ['', '-'], $this->releaseVersion->name);

        $importantNotes = [];
        if(!empty($this->importantNotes)) {
            foreach($this->importantNotes as $key => $note) {
                $importantNotes[] = '#### '.$this->linkIssue($key);
                $importantNotes[] = $note;
                $importantNotes[] = '';
            }
        }
        $importantNotes = join("\n", $importantNotes);
        
        $md = "\n\n## [$version] - $date\n\n### Important Notes:\n$importantNotes \n\n";
        
        if(!empty($this->issues['new feature'])) {
            $md .= "\n### Added\n";
        }
        foreach($this->issues['new feature'] as $row) {
            $md .= '**'.$this->linkIssue($row->key).': '.$row->components.' - '.$row->summary."** <br>\n".$row->description."\n\n";
        }
        
        if(!empty($this->issues['change'])) {
            $md .= "\n### Changed\n";
        }
        foreach($this->issues['change'] as $row) {
            $md .= '**'.$this->linkIssue($row->key).': '.$row->components.' - '.$row->summary."** <br>\n".$row->description."\n\n";
        }
        
        if(!empty($this->issues['fix'])) {
            $md .= "\n### Bugfixes\n";
        }
        foreach($this->issues['fix'] as $row) {
            $md .= '**'.$this->linkIssue($row->key).': '.$row->components.' - '.$row->summary."** <br>\n".$row->description."\n\n";
        }
        
        $filename = APPLICATION_ROOT.'/docs/CHANGELOG.md';
        $content = file_get_contents($filename);
        if(mb_strpos($content, '['.$version.']') !== false) {
            $this->io->warning('Check the changelog! A version '.$version.' does exist already!');
        }
        $lastPos = mb_strpos($content, "\n## [");
        $this->io->success('Updated changelog file '.$filename);
        file_put_contents($filename, substr_replace($content, $md, $lastPos, 0));
        return $filename;
    }
    
    protected function makeSqlRow($row, $type, $date, $groups) {
        //using only the issue nr, not the issue text
        $issue = $row->key;
        $title = addcslashes($row->components.' - '.$row->summary, "'");
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
     * @param string $issue
     * @param boolean $plain returns by default a MarkDown link, if plain = true it returns just the link
     * @return string
     */
    protected function linkIssue(string $issue, $plain = false): string {
        $url = str_replace('{0}', '$1', \Zend_Registry::get('config')->runtimeOptions->jiraIssuesUrl);
        if(! $plain) {
            $url = '[$1]('.$url.')';
        }
        return preg_replace('/(TRANSLATE-[0-9]+)/', $url, $issue);
    }
}
