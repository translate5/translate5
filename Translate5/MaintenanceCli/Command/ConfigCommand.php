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


class ConfigCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'config';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('List, get and set translate5 configuration values.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Tool to list, get and set translate5 configuration values - currently in Zf_configuration only.
Modified values are shown bold in the simple listing.');
        
        $this->addArgument('name', InputArgument::REQUIRED, 'The part of a configuration value name. If more than one config value is found, all are listed.');
        $this->addArgument('value', InputArgument::OPTIONAL, 'Value to be set for the configuration, only usable if name is concrete enough to find only one configuration entry.');
        $this->addOption(
            'detail',
            'd',
            InputOption::VALUE_NONE,
            'Show config details on listing');
        
        $this->addOption(
            'empty',
            null,
            InputOption::VALUE_NONE,
            'Set the value to an empty string (which can not be given as set argument).');
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
        $this->writeTitle('Change Translate5 configuration.');

        $config = new \editor_Models_Config();
        $name = $this->input->getArgument('name');
        $foundConfigs = $config->loadListByNamePart($name);
        if(empty($foundConfigs)) {
            $this->io->warning('No configuration found with name "*'.OutputFormatter::escape($name).'*"');
            return 1;
        }
        
        $newValue = $this->input->getArgument('value');
        $setEmpty = $this->input->getOption('empty');
        if(!is_null($newValue) && $setEmpty) {
            $this->io->error('Providing a value AND --empty is not allowed!');
            return 1;
        }
        
        $isExact = count($foundConfigs) === 1;
        if($isExact) {
            $this->io->section('Configuration found:');
        }
        else {
            $this->io->section('Multiple Configurations found:');
        }
        
        $listDetails = $this->input->getOption('detail');
        if(!$isExact) {
            if($listDetails) {
                foreach($foundConfigs as $configData) {
                    $this->showDetail($configData);
                }
            }
            else {
                $this->io->table(['name', 'origin', 'value'], array_map(function($item) {
                    $value = OutputFormatter::escape($item['value']);
                    if($item['value'] !== $item['default']) {
                        $value = '<options=bold>'.$value.'</>';
                    }
                    return [
                        OutputFormatter::escape($item['name']),
                        $item['origin'],
                        $value,
                    ];
                }, $foundConfigs));
            }
        }
        
        $newValue = $this->input->getArgument('value');
        $setEmpty = $this->input->getOption('empty');
        $exactConfig = reset($foundConfigs);
        if(is_null($newValue) && !$setEmpty) {
            if($isExact) {
                //show the config details if found exactly one entry
                $this->showDetail($exactConfig);
            }
            return 0;
        }
        if(!$isExact) {
            $this->io->error('Setting a value is only allowed if name is not ambiguous!');
            return 1;
        }
        
        $exactConfig['oldvalue'] = $exactConfig['value'];
        
        if($setEmpty) {
            $exactConfig['value'] = '';
            $msg = 'The value was set empty!';
        }
        else {
            $exactConfig['value'] = $newValue;
            $msg = 'The value was updated!';
        }
        $config->update($exactConfig['name'], $exactConfig['value']);
        $this->showDetail($exactConfig);
        if(array_key_exists('overwritten', $exactConfig)) {
            $this->io->warning($msg.' (in the DB only - change/remove it manually in/from the installation.ini)');
        }
        else {
            $this->io->success($msg);
        }
        return 0;
    }
    
    /**
     * Prints a config entry with all details
     * @param array $configData
     */
    protected function showDetail(array $configData) {
        $out = [
            '       <info>name: <options=bold>'.OutputFormatter::escape($configData['name']).'</>',
            '      value: <options=bold>'.OutputFormatter::escape($configData['value']).'</>',
            '   category: '.OutputFormatter::escape($configData['category']),
            '    default: '.OutputFormatter::escape($configData['default']),
            '   defaults: '.OutputFormatter::escape($configData['defaults']),
            '       type: '.$configData['type'],
            'description: '.OutputFormatter::escape($configData['description']),
            '      level: '.$configData['level'],
            '',
        ];
        
        $hasIni = array_key_exists('overwritten', $configData);
        if($hasIni) {
            $out[1] = '  ini value: <options=bold>'.OutputFormatter::escape($configData['value']).'</>';
            array_splice($out, 2, 0, '             <error>The value is set in the installation.ini and must be changed (or removed) there!</>');
            array_splice($out, 3, 0, '   db value: '.OutputFormatter::escape($configData['overwritten']).'');
        }
        if(array_key_exists('oldvalue', $configData)) {
            $out[1] = '  '.($hasIni ? 'ini':'new').' value: <fg=green;options=bold>'.OutputFormatter::escape($configData['value']).'</>';
            array_splice($out, 2, 0, '  old value: <fg=red>'.OutputFormatter::escape($configData['oldvalue']).'</>');
        }
        
        $this->io->text($out);
    }
}
