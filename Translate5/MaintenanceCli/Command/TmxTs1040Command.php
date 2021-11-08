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


class TmxTs1040Command extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'tmx:ts1040';

    protected bool $inSegment = false;

    protected function configure()
    {
        $this
        ->setDescription('Helper tool to convert TMX files according to TS-1040')
        ->setHelp('Helper tool to convert TMX files according to TS-1040');
        
        $this->addArgument('file', InputArgument::REQUIRED, 'The TMX file to be converted.');
        $this->addOption(
            'analyze',
            'a',
            InputOption::VALUE_NONE,
            'Analyzes the used content tags (ph it bpt ept)');

        $this->addOption(
            'count',
            'c',
            InputOption::VALUE_NONE,
            'Counts the used XML tags.');

        $this->addOption(
            'utf8out',
            'u',
            InputOption::VALUE_NONE,
            'returns the content in UTF8 instead the usual UTF16 which is used in TMX files');

        $this->addOption(
            'write',
            'w',
            InputOption::VALUE_NONE,
            'writes the output back to a new file (same name with .cleaned.tmx suffix) instead to stdout');
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

        $file = $this->input->getArgument('file');
        if(!is_file($file)) {
            $this->io->error("Given filepath does not point to a file!");
            return 1;
        }
        if(!is_readable($file)) {
            $this->io->error("Given file is not readable!");
            return 1;
        }
        if(stripos($file, '.tmx') === false) {
            $this->io->error("Given file is no TMX file!");
            return 1;
        }

        //all found tags:
//        $tags = [];

        $parser = new \editor_Models_Import_FileParser_XmlParser();

        $data = file_get_contents($file);
        $data = @iconv('utf-16','utf-8', $data);

        if($input->getOption('analyze')) {
            $parser->registerElement('*', null, function($tag, $idx, $opener) use ($parser) {
                if(!is_null($parser->getParent('seg')) && $tag != 'seg') {
                    echo $parser->join($parser->getRange($opener['openerKey'], $idx))."\n";
                }
            });
            $parser->parse($data);
            return 0;
        }


        if($input->getOption('count')) {
            //tag counter
            $this->io->info('Counts for '.$file);
            $parser->registerElement('*', function($tag) use (&$tags) {
                settype($tags[$tag], 'integer');
                $tags[$tag]++;
            });
            $parser->parse($data);
            print_r($tags);
            return 0;
        }

        $parser->registerElement('seg', function($tag) {
            $this->inSegment = true;
        }, function($tag) {
            $this->inSegment = false;
        });

        $parser->registerElement('seg ph, seg it', function() {
            //when we enter a ph or it, then we are not anymore in a segment:
            $this->inSegment = false;
        }, function($tag, $idx, $opener) use ($parser) {
            $tagContent = $parser->join($parser->getRange($opener['openerKey'], $idx));
            $isEncodedPipe = strpos($tagContent, 'Pipe') > 0 || strpos($tagContent, '|') > 0;
            $isFmt = strpos($tagContent, '{}') > 0 && $parser->getAttribute($opener['attributes'], 'type') == 'fmt';
            if($isEncodedPipe || $isFmt) {
                $parser->replaceChunk($opener['openerKey'], '', $opener['isSingle'] ? 1 : ($idx - $opener['openerKey']+1));
                $parser->replaceChunk($opener['openerKey'], '<ph type="lb"/>');
                $this->inSegment = true;
                return;
            }
            //replace @TAGs already escaped as tags
            if(preg_match('/@[A-Za-z0-9_]/', $tagContent)) {
                $parser->replaceChunk($opener['openerKey'], '', $opener['isSingle'] ? 1 : ($idx - $opener['openerKey']+1));
                $parser->replaceChunk($opener['openerKey'], '<it type="struct" />');
                $this->inSegment = true;
                return;
            }
            //all others are removed:
            $parser->replaceChunk($opener['openerKey'], '', $opener['isSingle'] ? 1 : ($idx - $opener['openerKey']+1));

            $this->inSegment = true;
        });

        //removing ept / bpt
        $parser->registerElement('seg ept, seg bpt', null, function ($tag, $idx, $opener) use ($parser){
            $parser->replaceChunk($opener['openerKey'], '', $opener['isSingle'] ? 1 : ($idx - $opener['openerKey']+1));
        });

        $parser->registerOther(function($other, $key) use ($parser){
            if(!$this->inSegment) {
                return;
            }
            $other = str_replace('|', '<ph type="lb"/>', $other);

            $other = preg_replace_callback('/@[A-Za-z0-9_]+(\$[0-9]+|\$@[A-Za-z0-9_]+)?/', function($matches){
                return '<it type="struct" />';
            }, $other);

            $parser->replaceChunk($key, $other);
        });

        if($input->getOption('utf8out')) {
            $data = $parser->parse($data);
        }
        else {
            $data = @iconv('utf-8','utf-16',$parser->parse($data));
        }
        unset($parser);

        if($input->getOption('write')) {
            $file = str_ireplace('.tmx$', '', $file.'$').'.cleaned.tmx';
            $bytes = file_put_contents($file, $data);
            $this->io->info($bytes.' bytes written to file '.basename($file));
        }
        else {
            echo $data;
        }

        return 0;
    }
}
