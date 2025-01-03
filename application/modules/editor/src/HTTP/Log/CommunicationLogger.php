<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\HTTP\Log;

use Psr\Log\LoggerInterface;

class CommunicationLogger implements LoggerInterface
{
    private function __construct(
        private readonly \Zend_Config $config,
        private readonly \ZfExtended_Logger $logger,
    ) {
    }

    public static function create()
    {
        return new self(
            \Zend_Registry::get('config'),
            \Zend_Registry::get("logger")->cloneMe('http.client'),
        );
    }

    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $pattern = '/(?:REQUEST: [A-Z]+ |RESPONSE: )(?<uri>\S+)/';

        if (! preg_match($pattern, $message, $matches)) {
            return;
        }

        $url = $matches['uri'];

        if (! $this->isRequestDebugEnabled($url)) {
            return;
        }

        $this->logger->addWriter('default', \ZfExtended_Logger_Writer_Abstract::create([
            'type' => 'ErrorLog',
            'level' => $level,
        ]));
        $this->logger->debug('E0001', $message, $context);
    }

    private function isRequestDebugEnabled(string $url): bool
    {
        $debug = $this->config->debug->httpclient ?? false;

        if (! $debug) {
            return false;
        }

        $debug = strtolower($debug);

        return match ($debug) {
            '1', 'on', 'true' => true,
            default => (stripos($url, $debug) !== false),
        };
    }
}
