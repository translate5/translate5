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

use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use ZfExtended_Factory;

class PatchApplyCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    public const CORECOMMIT = 'corecommit';

    public const ZFEXTENDED = 'zfextended';

    public const PRIVATE_PLUGIN = 'private-plugin';

    public const REPO_KEYS = [
        self::CORECOMMIT => 'translate5',
        self::ZFEXTENDED => 'zfextended',
        self::PRIVATE_PLUGIN => 'privateplugins',
    ];

    public const TARGET_PATHS = [
        self::CORECOMMIT => './',
        self::ZFEXTENDED => './library/ZfExtended/',
        self::PRIVATE_PLUGIN => './application/modules/editor/Plugins',
    ];

    protected static $defaultName = 'patch:apply';

    private bool $isDevelopment = false;

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Applies git commits as patch to the code base.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Provide commit hashes as parameters to fetch the code and apply it to the code base.' . PHP_EOL .
                'The commit hash for translate5 repo is provided as optional argument, ' . PHP_EOL .
                'ZfExtended or PrivatePlugin commits must be given with the corresponding options. ' . PHP_EOL .
                'First a dry run of the patches is tried, then you are asked if they should be applied or not. ' .
                PHP_EOL . PHP_EOL .
                '.orig Backups of the files are created. ' . PHP_EOL . PHP_EOL .
                'Authentication: the tool asks for the bitbucket credentials. Username and password can ' . PHP_EOL .
                'be passed concatinated with : in the username field. Password can also be an app token.' . PHP_EOL .
                'About the commits: ' . PHP_EOL .
                'each commit can be selected, but makes mostly sense when using a commit of a merged PR.'
            );

        $this->addArgument(
            self::CORECOMMIT,
            InputArgument::OPTIONAL,
            'The commit ID in the core repo.'
        );

        $this->addOption(
            self::ZFEXTENDED,
            'z',
            InputOption::VALUE_REQUIRED,
            'The commit ID in ZfExtended'
        );

        $this->addOption(
            self::PRIVATE_PLUGIN,
            'p',
            InputOption::VALUE_REQUIRED,
            'The commit ID in PrivatePlugins'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @return int
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $this->writeTitle('Apply code patch');

        $auth = $this->io->ask('Bitbucket Username (or username:password as one string)');
        if (! str_contains($auth, ':')) {
            $password = $this->io->askHidden('Password');
            $auth .= ':' . $password;
        }

        $patchDir = APPLICATION_DATA . '/patches/';
        if (! is_dir($patchDir)) {
            mkdir($patchDir);
        }

        if (! is_writable($patchDir)) {
            $this->io->error('Patch directory is not writable: ' . $patchDir);

            return self::FAILURE;
        }

        $commits = [
            self::CORECOMMIT => $input->getArgument(self::CORECOMMIT),
            self::ZFEXTENDED => $input->getOption(self::ZFEXTENDED),
            self::PRIVATE_PLUGIN => $input->getOption(self::PRIVATE_PLUGIN),
        ];

        $this->isDevelopment = file_exists('.git');

        $this->io->section('Fetch patches and check: ');

        foreach ($commits as $target => $commit) {
            $commits[$target] = $patchFile = null;
            if (! empty($commit)) {
                $commits[$target] = $patchFile = $this->fetchCommit($target, $commit, $auth, $patchDir);
            }
            if (is_null($patchFile)) {
                continue;
            }
            if (file_exists($patchFile)) {
                $this->patch($target, $patchFile);
            } else {
                $this->io->warning('Patch file does not exist: ' . $commits[$target]);
                $commits[$target] = null;
            }
        }

        if ($this->isDevelopment) {
            $this->io->success('.git directory found, applying patches not possible!');

            return self::SUCCESS;
        }

        $this->io->confirm('Shall the patches be applied?');

        foreach ($commits as $target => $patchFile) {
            if (! is_null($patchFile) && file_exists($patchFile)) {
                $this->patch($target, $patchFile, false);
            }
        }

        return 0;
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     */
    private function fetchCommit(string $target, string $commit, string $auth, string $patchDir): ?string
    {
        $http = ZfExtended_Factory::get(Zend_Http_Client::class);
        $uri = 'https://api.bitbucket.org/2.0/repositories/mittagqi/';
        $http->setUri($uri . self::REPO_KEYS[$target] . '/patch/' . $commit);
        $http->setHeaders('Accept-charset', 'UTF-8');
        $http->setHeaders('Accept', 'plain/text; charset=utf-8');

        //users with : in the password are lost...
        $http->setAuth(...explode(':', $auth));
        $response = $http->request();

        if ($response->getStatus() !== 200) {
            $this->io->warning('HTTP Response from bitbucket was not 200: ' . $response->getStatus());
            $this->io->warning('Body: ' . $response->getBody());

            return null;
        }

        $patchFile = $patchDir . '/' . $target . '-' . $commit;
        $result = file_put_contents($patchFile, $response->getBody());

        if (! $result) {
            $this->io->warning('Nothing was written to patchfile: ' . $patchFile);

            return null;
        }

        return $patchFile;
    }

    private function patch(string $target, string $patchFile, bool $dryRun = true): void
    {
        $path = self::TARGET_PATHS[$target];
        if ($this->isDevelopment) {
            $dryRun = true;
            if ($target === self::PRIVATE_PLUGIN) {
                $path = str_replace('/Plugins', '/PrivatePlugins', $path);
            }
        }
        $this->io->info('Patching in ' . $path);
        $cmd = 'cd ' . escapeshellarg($path);
        $cmd .= '; patch --verbose ';
        if ($dryRun) {
            $cmd .= '--dry-run';
        }
        $cmd .= ' -p1 -N -b -i ' . escapeshellarg($patchFile);
        passthru($cmd);
    }
}
