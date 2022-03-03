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
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;


class LogCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'log';

    const LEVELS = [
        1 => '<fg=red;options=bold>FATAL</>',
        2 => '<fg=red>ERROR</>',
        4 => '<fg=yellow>WARN </>',
        8 => '<fg=blue>INFO </>',
        16 => 'DEBUG',
        32 => 'TRACE',
    ];
    
    /**
     * Tracking the used filters for the summary.
     * @var array
     */
    protected $usedFilters = [];
    protected $summary = [];
    
    /**
     * flag if summary should be shown or not
     * @var boolean
     */
    protected $withSummary = true;
    
    /**
     * Tracking the last found id for reach run with --follow
     * @var integer
     */
    protected $lastFoundId = 0;
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Query the translate5 log')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Tool to query, investigate and purge the translate5 system log. By default a list of log entries and a summary is shown.

list output example:
  2020-09-07 09:26:11 FATAL E1027 (#123) core → PHP E_ERROR: Uncaught Error: Call to undefined method XyZ in foo.php:121
the format is:
  log timestamp       level ecode (#ID)  app.domain → message'
        );
        
        $this->addArgument('filter', InputArgument::OPTIONAL, 'Provide keywords to filter output. EXXXX is recognized as ecodes, text.text.text as domains and all other is searched in message. If keyword is only one Number, it is assumed that is a log ID an only that entry is shown.');
        
        $this->addOption(
            'level',
            'L',
            InputOption::VALUE_REQUIRED,
            'Filtering for specific level(s). If given as string, only the level given as string is shown. Given as integer: filtering for all levels as bitmask.');
        
        $this->addOption(
            'follow',
            'f',
            InputOption::VALUE_NONE,
            'Show the most recent log entries, and continuously print new entries as they are appended to the log. Do not show a summary.');
        
        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Shows the full blown log data (extra, trace, etc)');
        
        $this->addOption(
            'since',
            's',
            InputOption::VALUE_REQUIRED,
            'Shows log data since the given point in time (strtotime parsable string).');
        
        $this->addOption(
            'until',
            'u',
            InputOption::VALUE_REQUIRED,
            'Shows log data until the given point in time (strtotime parsable string). If the parameter starts with a "+" it is automatically added to the since date.');

        $this->addOption(
            'last',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Shows only the last X log entries (default 5).',
            false
        );

        $this->addOption(
            'no-summary',
            null,
            InputOption::VALUE_NONE,
            'Do not print the summary and intro texts - for further shell scripting.');
        
        $this->addOption(
            'summary-only',
            null,
            InputOption::VALUE_NONE,
            'Print only the summary.');
        
        $this->addOption(
            'purge',
            null,
            InputOption::VALUE_NONE,
            'Warning: purges the logs found be the given filters. Is asking for confirmation of not used with -q|--quiet or -n|--no-interaction.');
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
        
        $this->withSummary = !$input->getOption('no-summary');
        if($this->withSummary) {
            $this->writeTitle('Query Translate5 log.');
        }

        $log = new \ZfExtended_Models_Log();
        
        $filter = $this->input->getArgument('filter');
        if(is_numeric($filter)) {
            try {
                $log->load($filter);
                $this->showDetail((array) $log->getDataObject());
                return 0;
            } catch(\Exception $e) {
                //just proceed with normal filtering if no log entry with that ID found
            }
        }
        
        //defining always the --follow loop but break it, if not using following
        while(true) {
            $s = $log->db->select()->order('id DESC');
            
            $filtered = $this->parseArgumentToSelect($s);
            $filtered = $this->parseDateToSelect($s) || $filtered;
            $filtered = $this->parseLevelToSelect($s) || $filtered;

            $limit = $input->getOption('last');
            if($limit !== false) { // if === false, then it was not given at all
                $s->limit($limit ?? 5); //if $limit is null, then it was given empty, so defaulting to 5
            }

            if($input->getOption('follow')) {
                //on first run we respect limit, after that not anymore to get all logs in the 2 second gap
                if($this->lastFoundId > 0) {
                    $s->reset($s::LIMIT_COUNT);
                }
                $s->where('id > ?', $this->lastFoundId);
                $this->processResults($log, $s);
                sleep(2);
            }
            else {
                $this->processResults($log, $s);
                $this->summary($filtered);
                $this->purgeList($log, $s);
                return 0;
            }
        }
    }
    
    /**
     * When using with --purge the single record is deleted
     * @param \ZfExtended_Models_Log $log
     */
    protected function purgeOne(\ZfExtended_Models_Log $log) {
        if(!$this->input->getOption('purge') || $this->summary['count'] === 0) {
            return;
        }
        if(!$this->input->isInteractive() || $this->io->confirm('Really delete that log record?', false)) {
            $log->delete();
            $this->io->success('Above shown log record deleted!');
        }
    }
    
    /**
     * When using with --purge the single record is deleted
     * @param \ZfExtended_Models_Log $log
     * @param \Zend_Db_Table_Select $s
     */
    protected function purgeList(\ZfExtended_Models_Log $log, \Zend_Db_Table_Select $s) {
        if(!$this->input->getOption('purge') || $this->summary['count'] === 0) {
            return;
        }
        if(!$this->input->isInteractive() || $this->io->confirm('Really delete that log records?', false)) {
            //a little bit hacky: prevent logged config changes from deletion
            $s->where('eventCode != ?', 'E1324');
            $where = $s->getPart($s::WHERE);
            $log->db->delete(is_array($where) ? join(' ', $where) : $where);
            $this->io->success('Above shown log record(s) deleted!');
        }
    }
    
    /**
     * searches for log entries and process them
     * @param \ZfExtended_Models_Log $log
     * @param \Zend_Db_Table_Select $s
     */
    protected function processResults(\ZfExtended_Models_Log $log, \Zend_Db_Table_Select $s) {
        $summaryOnly = $this->input->getOption('summary-only');
        $rows = $log->db->fetchAll($s)->toArray();
        $rows = array_reverse($rows);
        $this->summary['count'] = count($rows);
        if($this->summary['count'] > 0) {
            $last = end($rows);
            $this->summary['last'] = $last['created'];
            $this->summary['first'] = reset($rows)['created'];
            $this->lastFoundId = $last['id'];
        }
        $ecodes = [];
        $this->summary['levels'] = array_fill_keys(array_keys(self::LEVELS), 0);
        if($this->withSummary && !$summaryOnly && !$this->input->getOption('follow')) {
            $this->io->section('Found log entries:');
        }
        foreach($rows as $row) {
            //find out max used eventCode
            if(empty($ecodes[$row['eventCode']])) {
                $ecodes[$row['eventCode']] = 1;
            }
            else {
                $ecodes[$row['eventCode']]++;
            }
            $this->summary['levels'][$row['level']]++;
            if($summaryOnly) {
                continue;
            }
            if($this->input->getOption('all')) {
                $this->showDetail($row);
            }
            else {
                $idBlock = '(# '.$row['id'];
                if($row['duplicates'] > 0) {
                    $idBlock .= ' <options=bold>*'.$row['duplicates'].'</>';
                }
                $idBlock .= ') ';
                $this->io->text($row['created'].' '.
                    self::LEVELS[$row['level']].' <options=bold>'.$row['eventCode'].'</> '.$idBlock.
                    OutputFormatter::escape((string) $row['domain']).' → '.
                    OutputFormatter::escape((string)str_replace("\n", ' ', $row['message'])));
            }
        }
        arsort($ecodes);
        $lastVal = 0;
        $this->summary['maxUsedEcode'] = [];
        foreach($ecodes as $ecode => $val) {
            if($val <= $lastVal) {
                break;
            }
            $this->summary['maxUsedEcode'][] = $ecode;
            $lastVal = $val;
        }
    }
    
    protected function summary(bool $usedFilter) {
        if(!$this->withSummary) {
            return;
        }
        $this->io->section('Summary');
        if($this->summary['count'] > 0) {
            $this->io->text([
                '<info>Found '.$this->summary['count'].' log entries</>: between <options=bold>'.$this->summary['first'].'</> and <options=bold>'.$this->summary['last'].'</>',
                '<info>EventCodes found most often:</> '.join(', ', $this->summary['maxUsedEcode']),
                '<info>levels counted:</> '
            ]);
            foreach($this->summary['levels'] as $key => $count) {
                if($count == 0) {
                    continue;
                }
                $this->io->text('  '.self::LEVELS[$key].': '.$count);
            }
        }
        else {
            $this->io->success('No log entries found!');
        }
        
        if(!empty($usedFilter)) {
            $this->io->text('<info>Filtered for:</> ');
            if(!empty($this->usedFilters['ecodes'])) {
                $this->io->text('  Ecode(s): '.join(', ', $this->usedFilters['ecodes']));
            }
            if(!empty($this->usedFilters['domains'])) {
                $this->io->text('  Domain(s): '.join(', ', $this->usedFilters['domains']));
            }
            if(!empty($this->usedFilters['ecodes'])) {
                $this->io->text('  Message: '.join(' ', $this->usedFilters['message']));
            }
            if(!empty($this->usedFilters['since'])) {
                $this->io->text('  Time since '.$this->usedFilters['since']);
            }
            if(!empty($this->usedFilters['until'])) {
                $this->io->text('  Time until '.$this->usedFilters['until']);
            }
            if(!empty($this->usedFilters['level'])) {
                $this->io->text('  Level(s): '.$this->usedFilters['level']);
            }
        }
    }
    
    /**
     * parses and adds the level filter(s)
     * @param \Zend_Db_Table_Select $s
     * @return boolean
     */
    protected function parseLevelToSelect(\Zend_Db_Table_Select $s): bool {
        $level = $this->input->getOption('level');
        if(!$level) {
            return false;
        }
        $level = strtolower($level);
        
        $levelList = [
            'fatal' => 1,
            'error' => 2,
            'warn' => 4,
            'info' => 8,
            'debug' => 16,
            'trace' => 32,
        ];
        
        if(is_numeric($level)) {
            $s->where('level & ?', $level);
            $foundLevels = [];
            foreach($levelList as $lev => $key) {
                if($key & $level) {
                    $foundLevels[] = $lev;
                }
            }
            $this->usedFilters['level'] = join(',', $foundLevels);
            return true;
        }
        if (!empty($levelList[$level])) {
            $s->where('level = ?', $levelList[$level]);
            $this->usedFilters['level'] = $level;
            return true;
        }
        $this->io->warning('The given --level|-l level filter is not valid - ignored!');
        return false;
    }
    
    /**
     * parses and adds the date filters
     * @param \Zend_Db_Table_Select $s
     * @return boolean
     */
    protected function parseDateToSelect(\Zend_Db_Table_Select $s): bool {
        $result = false;
        if($since = $this->input->getOption('since')) {
            $since = strtotime($since);
            if($since === false) {
                $this->io->warning('The given --since|-s time can not be parsed to a valid date - ignored!');
            }
            else {
                $since = date('Y-m-d H:i:s', $since);
                $this->usedFilters['since'] = $since;
                $s->where('created >= ?', $since);
                $result = true;
            }
        }
        if($until = $this->input->getOption('until')) {
            $until = trim($until);
            if(strpos($until, '+') !== false) {
                $until = $this->input->getOption('since').' '.$until;
            }
            $until = strtotime($until);
            if($until === false) {
                $this->io->warning('The given --until|-u time can not be parsed to a valid date - ignored!');
            }
            else {
                $until = date('Y-m-d H:i:s', $until);
                $this->usedFilters['until'] = $until;
                $s->where('created <= ?', $until);
                $result = true;
            }
        }
        return $result;
    }
    
    /**
     * parses the argument for valid filter strings and applies them. Returns boolean if filters are applied
     * @param \Zend_Db_Table_Select $s
     * @return boolean
     */
    protected function parseArgumentToSelect(\Zend_Db_Table_Select $s): bool {
        $filter = $this->input->getArgument('filter');
        if(empty($filter)) {
            return false;
        }
        $token = null;
        $ecodes = [];
        $domains = [];
        $message = [];
        $tokens = explode(' ', $filter);
        $token = array_shift($tokens);
        while(!is_null($token)) {
            $token = strtolower($token);
            if(preg_match('/^e[0-9]{4}$/', $token)) {
                $ecodes[] = $token;
                $token = array_shift($tokens);
                continue;
            }
            if(preg_match('/^[a-z0-9]+\.[a-z0-9.]+$/', $token)) {
                $domains[] = $token;
                $token = array_shift($tokens);
                continue;
            }
            $message[] = $token;
            $token = array_shift($tokens);
        }
        if(!empty($ecodes)) {
            $s->where('eventCode in (?)', $ecodes);
        }
        if(!empty($domains)) {
            $s->where('domain in (?)', $domains);
        }
        if(!empty($message)) {
            $s->where('message like ?', '%'.join(' ', $message).'%');
        }
        $this->usedFilters['ecodes'] = $ecodes;
        $this->usedFilters['domains'] = $domains;
        $this->usedFilters['message'] = $message;
        return true;
    }
    
    /**
     * Prints a config entry with all details
     * @param array $configData
     */
    protected function showDetail(array $row) {
        $out = [
            '         <info>id:</> '.(string) $row['id'],
            '      <info>level:</> '.(string) self::LEVELS[$row['level']],
            '    <info>created:</> '.(string) $row['created'],
            ' <info>duplicates:</> <options=bold>'.(string) $row['duplicates'].'</>',
            '       <info>last:</> '.(string) $row['last'],
            '      <info>ecode:</> <options=bold>'.OutputFormatter::escape((string) $row['eventCode']).'</>',
            '     <info>domain:</> '.OutputFormatter::escape((string) $row['domain']),
            '    <info>message:</> '.OutputFormatter::escape((string) $row['message']),
            ' <info>appVersion:</> '.OutputFormatter::escape((string) $row['appVersion']),
            '<info>file (line):</> '.OutputFormatter::escape((string) $row['file'].' ('.$row['line'].')'),
        ];

        if($row['duplicates'] == 0) {
            unset($out[3]);
            unset($out[4]);
        }
        
        if(!empty($row['httpHost'])) {
            $out[] = '       <info>Host:</> '.OutputFormatter::escape((string) $row['httpHost']);
        }
        if(!empty($row['url'])) {
            $out[] = '        <info>URL:</> '.OutputFormatter::escape((string) $row['method'].' '.$row['url']);
        }
        if(!empty($row['userGuid'])) {
            $out[] = '       <info>User:</> '.OutputFormatter::escape((string) $row['userLogin'].' ('.$row['userGuid'].')');
        }
        if(!empty($row['worker'])) {
            $out[] = '     <info>Worker:</> '.OutputFormatter::escape((string) $row['worker']);
        }
        if(!empty($row['trace'])) {
            $out[] = '      <info>Trace:</> '.preg_replace('/((#[0-9]+) |\([0-9]+\))/', "<options=bold>$0</>", OutputFormatter::escape((string) $row['trace']));
        }
        if(!empty($row['extra'])) {
            $out[] = '      <info>Extra:</> '.$this->prepareExtra($row['extra']);
        }
        $out[] = '';
            
        $this->io->text($out);
    }
    
    /**
     * returns the extra parameter prepared for output
     * @param string $extra
     * @return string
     */
    protected function prepareExtra(string $extra): string {
        $extra = json_decode($extra, true);
        
        if(!empty($extra['task']) && !empty($extra['task']['taskGuid']) && !empty($extra['task']['taskName'])) {
            settype($extra['task']['taskNr'], 'string');
            $extra['task'] = $extra['task']['taskName'].' #'.$extra['task']['taskNr'].' ID:'.$extra['task']['id'].' GUID:'.$extra['task']['taskGuid'];
        }
        
        $extra = json_encode($extra, JSON_PRETTY_PRINT);
        return OutputFormatter::escape((string) $extra);
    }
}
