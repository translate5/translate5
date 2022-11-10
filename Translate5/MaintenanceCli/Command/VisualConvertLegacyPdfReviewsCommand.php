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
use Zend_Registry;
use ZfExtended_Plugin_Manager;

/**
 * Command to convert all legacy PDF based reviews
 * This mainly is for development of the feature but may also is of use for installations having legacy reviews
 */
class VisualConvertLegacyPdfReviewsCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'visual:convertlegacypdfs';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Visual: Converts all PDF based reviews using the legacy scroller')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Visual: Converts all PDF based reviews using the legacy scroller');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $pluginmanager = Zend_Registry::get('PluginManager');
        /* @var $pluginmanager ZfExtended_Plugin_Manager */
        if(!$pluginmanager->isActive('VisualReview')) {
            $this->io->error('The visual plugin must be active to use this command !');
            return static::FAILURE;
        }
        $conversions = \editor_Plugins_VisualReview_Pdf_LegacyConversion::convert();
        if(count($conversions) === 0){
            $this->io->success('No reviews had to be adjusted');
        } else {
            $this->io->success('The following reviews have been adjusted: '."\n\n".implode("\n", $conversions));
        }
        return static::SUCCESS;
    }
}
