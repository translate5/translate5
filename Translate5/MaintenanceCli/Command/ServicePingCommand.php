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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServicePingCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'service:ping';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Simple tool to check availability of a TCP service')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Checks if a DNS entry is valid, returns the hosts behind and' .
                ' checks if a TCP connection is possible. For checking a concretes system health use system:check instead'
            );

        $this->addArgument(
            'url',
            InputArgument::REQUIRED,
            'Checks the given URL (host:port). '
            . 'Give at least a hostname. Port defaults to 80 (443 with --ssl-check) for TCP check.'
        );

        $this->addOption(
            '--dns-check-only',
            'd',
            InputOption::VALUE_NONE,
            'Checks only DNS, does not connect to the service'
        );

        $this->addOption(
            '--ssl-check',
            's',
            InputOption::VALUE_NONE,
            'Checks if the host is connectable via SSL on the given port'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $url = $input->getArgument('url');
        $dnsOnly = $input->getOption('dns-check-only');
        $checkSSL = $input->getOption('ssl-check');
        $this->io->title('Checking ' . $url);

        //we have to prepend a schema, otherwise parse_url would return
        // only a path instead a host if just a string is given:
        if (! str_contains($url, '://')) {
            $url = 'tcp://' . $url;
        }

        $urlParts = parse_url($url);

        $host = $urlParts['host'] ?? null;
        $port = $urlParts['port'] ?? ($checkSSL ? 443 : 80);
        if (empty($host)) {
            $this->io->error('Parsing the URL does not return a valid host!');

            return self::INVALID;
        }

        $ips = gethostbynamel($host);

        if ($ips === false) {
            $this->io->error('No DNS entry found for host ' . $host);

            return self::FAILURE;
        }

        $dnsRecords = dns_get_record($host);
        $keys = [];
        $data = [];
        //since the returned keys may differ per entry, we have to normalize that for printing
        // get headers:
        foreach ($dnsRecords as $dnsRecord) {
            $keys = array_unique(array_merge($keys, array_keys($dnsRecord)));
        }

        // get data in fixed order
        foreach ($dnsRecords as $dnsRecord) {
            $row = [];
            foreach ($keys as $key) {
                $row[] = $dnsRecord[$key] ?? '-';
            }
            $data[] = $row;
        }

        $this->io->section('DNS Entries:');
        $table = $this->io->createTable();
        $table->setHeaders($keys);
        $table->addRows($data);
        $table->render();

        if ($dnsOnly) {
            return self::SUCCESS;
        }

        $this->io->writeln('');
        $this->io->section('TCP Connections:');
        $success = false;
        $table = $this->io->createTable();
        $table->setHeaders(['IP', 'Port', 'Listen', 'Error (if any)']);
        foreach ($ips as $ip) {
            $result = '';
            $error = '';
            if ($this->checkService($ip, $port, $result, $error)) {
                $success = true; //at least one must be available to return succcess
                $table->addRow([$ip, $port, '<info>Yes</info>', $error]);
            } else {
                $table->addRow([$ip, $port, '<error>No</error>', $error]);
            }
        }

        $table->render();

        if ($checkSSL) {
            $result = '';
            $error = '';
            $this->io->writeln('');
            $this->io->section('SSL Check');
            if ($this->checkService('https://' . $host, $port, $result, $error)) {
                $this->io->success('SSL seems to be OK: ' . $error);
            } else {
                $this->io->error('SSL Problems: ' . $error);
                $success = false;
            }
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }

    private function checkService(string $ip, int $port, string &$result, string &$error): bool
    {
        $error = '';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $ip . ':' . $port);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);

        //// Timeout in seconds
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);

        $result = curl_exec($curl);
        if ($result === false) {
            $result = '';
            $error = curl_error($curl);

            return false;
        }

        return true;
    }
}
