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

namespace MittagQI\Translate5\HTTP;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use MittagQI\Translate5\HTTP\Log\CommunicationLogger;
use MittagQI\Translate5\HTTP\Middleware\RewindResponseMiddleware;
use MittagQI\Translate5\HTTP\Middleware\ThrowExceptionOnErrorMiddleware;

class ClientFactory
{
    public function __construct(
        private readonly CommunicationLogger $logger
    ) {
    }

    public static function create(): self
    {
        return new self(
            CommunicationLogger::create()
        );
    }

    public function createClient(array $options): ClientInterface
    {
        $stack = HandlerStack::create();
        $stack->unshift($this->createRequestLoggingMiddleware());
        $stack->unshift($this->createResponseLoggingMiddleware());
        $stack->unshift(RewindResponseMiddleware::create());

        return new Client(
            $options +
            [
                'handler' => $stack,
            ]
        );
    }

    public function createClientForceExceptions(array $options): ClientInterface
    {
        $stack = HandlerStack::create();
        $stack->remove('http_errors');
        $stack->push(ThrowExceptionOnErrorMiddleware::create());
        $stack->unshift($this->createRequestLoggingMiddleware());
        $stack->unshift($this->createResponseLoggingMiddleware());
        $stack->unshift(RewindResponseMiddleware::create());

        return new Client(
            $options +
            [
                'handler' => $stack,
            ]
        );
    }

    private function createRequestLoggingMiddleware(): callable
    {
        return Middleware::log(
            $this->logger,
            new MessageFormatter("REQUEST: {method} {uri} HTTP/{version}:\n {req_body}")
        );
    }

    private function createResponseLoggingMiddleware(): callable
    {
        return Middleware::log(
            $this->logger,
            new MessageFormatter("RESPONSE: {uri} {code}:\n {res_body}")
        );
    }
}