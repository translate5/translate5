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

use editor_Models_Task;
use Exception;
use MittagQI\Translate5\Plugins\VisualReview\Source\SourceFileEntity;
use MittagQI\Translate5\Plugins\VisualReview\Source\SourceFiles;
use MittagQI\Translate5\Plugins\VisualReview\Source\SourceType;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Zend_Registry;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Plugin_Manager;

/**
 * Command to convert all legacy PDF based reviews
 * This mainly is for development of the feature but may also is of use for installations having legacy reviews
 */
class VisualImplantReflownWysiwyg extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'visual:implantreflow';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Visual: Implants the visual source file for the wysiwyg screen from one source task to one or more target tasks. The tasks must be supplied by their ID')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Visual: Implants the visual source file for the wysiwyg screen from one source task to one or more target tasks. The tasks must be supplied by their ID');

        $this->addArgument(
            'sourceid',
            InputArgument::REQUIRED,
            'The ID of the source task'
        );

        $this->addArgument(
            'targetids',
            InputArgument::REQUIRED,
            'One or more comma seperated IDs of the target task(s)'
        );

        $this->addOption(
            name: 'overwrite-wysiwyg',
            shortcut: 'o',
            mode: InputOption::VALUE_NONE,
            description: 'If set, an existing reflown visual will be overwritten, otherwise the attempt to overweite creates an error'
        );

        $this->addOption(
            name: 'overwrite-splitfile',
            shortcut: 's',
            mode: InputOption::VALUE_NONE,
            description: 'If set, an existing split visual will be overwritten, otherwise the former solitary main visual is taken/renamed'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws \Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $pluginmanager = Zend_Registry::get('PluginManager');
        /* @var $pluginmanager ZfExtended_Plugin_Manager */
        if (! $pluginmanager->isActive('VisualReview')) {
            $this->io->error('The visual plugin must be active to use this command !');

            return static::FAILURE;
        }

        $sourceTaskId = $this->input->getArgument('sourceid');
        $targetTaskIds = explode(',', str_replace(' ', '', $this->input->getArgument('targetids')));
        $overwriteWysiwyg = (bool) $this->input->getOption('overwrite-wysiwyg');
        $overwriteSplitfile = (bool) $this->input->getOption('overwrite-splitfile');

        if (empty($sourceTaskId) || empty($targetTaskIds)) {
            $this->io->error('Arguments missing');

            return static::FAILURE;
        }

        // gathering all needed visuals
        try {
            $sourceVisual = $this->fetchFirstVisualSourceFile(intval($sourceTaskId), null);
            $targetVisuals = [];
            foreach ($targetTaskIds as $taskId) {
                $targetVisuals[] = $this->fetchFirstVisualSourceFile(intval($taskId), $sourceVisual->getSource(), $overwriteWysiwyg);
            }
        } catch (Throwable $e) {
            $this->io->error('There was a problem with the given task-id\'s: ' . $e->getMessage());

            return static::FAILURE;
        }
        if (! $sourceVisual->hasSplitFile()) {
            $this->io->error('The source visual does not have a WYSIWYG');

            return static::FAILURE;
        }
        // copy the visuals, adjust the file-entities
        $sourcePath = $sourceVisual->getFile(true);
        foreach ($targetVisuals as $targetVisual) {
            $targetPath = $targetVisual->getFile(true);
            $targetHasSplit = $targetVisual->hasSplitFile();
            if (! $targetHasSplit || $overwriteSplitfile) {
                // create split & copy main file as split file
                $splitFileName = $targetVisual->generateSplitFileName();
                $targetVisual->setSplitFileName($splitFileName);
                $targetVisual->setGenerator('reflow');
                $targetVisual->save();
                // implant the split file, either by renaming or overwriting
                if ($targetHasSplit) {
                    // overwrite
                    copy($sourceVisual->getSplitFile(true), $targetVisual->getSplitFile(true));
                    unlink($targetPath);
                    $splitSuccess = 'implanted split file from "' . $sourceVisual->getSplitFile(true) . '" to "' . $targetVisual->getSplitFile(true) . '"';
                } else {
                    // make main to split file
                    rename($targetPath, $targetVisual->getSplitFile(true));
                    $splitSuccess = 'renamed main file to split file';
                }
                // implant main file, correct file-rights
                copy($sourcePath, $targetPath);
                $this->correctFileRights($targetPath, $sourcePath);
                $this->io->success('Implanted wysiwyg-visual from "' . $sourcePath . '" to "' . $targetPath . '", ' . $splitSuccess . '.');
            } else {
                // just overwrite main/wysiwyg file
                @unlink($targetPath);
                copy($sourcePath, $targetPath);
                $this->correctFileRights($targetPath, $sourcePath);
                $this->io->success('Copied wysiwyg-visual from "' . $sourcePath . '" to "' . $targetPath . '".');
            }
        }

        return static::SUCCESS;
    }

    /**
     * @param string|null $sourcePdf : signals, if we are the source visual (empty) or the target visual (PDF-filename the visual was generated from)
     * @throws Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function fetchFirstVisualSourceFile(int $taskId, string $sourcePdf = null, bool $overwriteWysiwyg = false): SourceFileEntity
    {
        $task = new editor_Models_Task();
        $task->load($taskId);
        $sourceFiles = SourceFiles::instance($task);
        $sourceFile = $sourceFiles->getFirstFile();
        if (empty($sourceFile)) {
            throw new Exception('No first source file found for task ' . $taskId);
        }
        $sourceFileId = $sourceFile->getId();
        if ($sourceFile->getSourceType() !== SourceType::PDF) {
            throw new Exception('Task ' . $taskId . ', visual file ' . $sourceFileId . ', has no visual source-file of type "PDF"');
        }
        if ($sourcePdf === null) {
            // check source visual qualifications
            if (! $sourceFile->hasSplitFile() || $sourceFile->getGenerator() !== 'reflow') {
                throw new Exception('Source visual for task ' . $taskId . ', visual file ' . $sourceFileId . ', has no split file or has the wrong generator');
            }
        } else {
            // check target visual qualifications
            if ($sourceFile->getSource() !== $sourcePdf) {
                throw new Exception('Target visual for task ' . $taskId . ', visual file ' . $sourceFileId . ', has a different source-PDF "' . $sourceFile->getSource() . '", expected "' . $sourcePdf . '"');
            }
            if (! $overwriteWysiwyg && ($sourceFile->hasSplitFile() || $sourceFile->getGenerator() !== 'pdf2html')) {
                throw new Exception('Target visual for task ' . $taskId . ', visual file ' . $sourceFileId . ', already has a split file or has the wrong generator');
            }
        }

        return $sourceFile;
    }

    /**
     * Adjusts the file-rights if neccessary
     */
    private function correctFileRights(string $path, string $referencePath)
    {
        if (PHP_OS_FAMILY !== 'Windows') { // TODO FIXME: on windows this may lead to an unusable installation if called with elevated rights
            $referenceUser = @posix_getpwuid(@fileowner($referencePath));
            $referenceGroup = @posix_getgrgid(@filegroup($referencePath));
            $user = @posix_getpwuid(@fileowner($path));
            if ($referenceUser && $referenceGroup && $user && $user !== $referenceUser) {
                if (! chown($path, $referenceUser) || ! chgrp($path, $referenceGroup)) {
                    $this->io->warning('Adjusting the file rights failed! Please check "' . $path . '".');
                }
            }
        } else {
            $this->io->warning('You are using this command on windows. This may create review-files with wrong file-rights! Please check "' . $path . '".');
        }
    }
}
