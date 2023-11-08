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

namespace MittagQI\Translate5\Test\Mock;

use Zend_Controller_Request_Http;

/**
 * Base class to implement Actions of a Mock-API
 * This mimics the Actions of a "real" controller
 * Note, that the request can be found with $this->request or $this->getRequest()
 * In $this->endpoint the raw action is stored (which can be a partial path in case of a deeper call)
 *
 * Exampes:
 * /editor/fakeapi/PluginName/smth
 * -> leads to MittagQI\Translate5\Plugins\PluginName\Test\MockActions::smthAction
 *
 * /editor/fakeapi/PluginName/smth-else
 * -> leads to MittagQI\Translate5\Plugins\PluginName\Test\MockActions::smthElseAction
 *
 * /editor/fakeapi/PluginName/smth/else/is/wanted
 * leads to MittagQI\Translate5\Plugins\PluginName\Test\MockActions::smthAction, /smth/else/is/wanted is accessible via $this->endpoint
 *
 * /editor/fakeapi/core/ServiceName/smth
 * -> leads to MittagQI\Translate5\Test\MockActions\ServiceName::smthAction
 *
 * /editor/fakeapi/core/ServiceName/smth/else/is/wanted
 * -> leads to MittagQI\Translate5\Test\MockActions\ServiceName::smthAction, /smth/else/is/wanted is accessible via $this->endpoint
 */
abstract class AbstractActions
{
    /**
     * This constant can be used in inheriting classes do define
     * a base-endpoint, that is deducted from the passed endpoint & action,
     * e.g. with the BASE_ENDPOINT of "v2"
     * a call on /editor/mockapi/plugin/v2/myendpoint would result in
     * calling on Plugin::endpointAction with the endpoint "/v2/myendpoint"
     */
    const BASE_ENDPOINT = '';

    /**
     * If this is set to true,
     * Routing the endpoints will turn slashes to dashes, so a path like /editor/mockapi/pluginname/some/crazy/action
     * will be turned to /editor/mockapi/pluginname/some-crazy-action
     * leading to a call to Pluginname::someCrazyAction
     */
    const SLASHES_TO_DASHES = false;

    /**
     * Simple Helper to retrieve the pure classname out of a namespaced classname
     * TODO: might move to a global helper tool
     * @param string $qualifiedClassName
     * @return string
     */
    public static function pureClassName(string $qualifiedClassName): string
    {
        $parts = explode('\\', $qualifiedClassName);
        return array_pop($parts);
    }

    /**
     * Helper to turn sth like "this-is-groovy" to "thisIsGroovy"
     * TODO: might move to a global helper tool
     * @param string $string
     * @return string
     */
    public static function dashesToCamelCase(string $string): string
    {
        return lcfirst(str_replace('-', '', ucwords($string, '-')));
    }

    /**
     * @param Zend_Controller_Request_Http $request
     * @param string $endpoint
     */
    public function __construct(
        protected Zend_Controller_Request_Http $request,
        protected string                       $endpoint
    )
    {
    }

    /**
     * This API can be used to route certain endpoints to certain actions in inheriting classes
     * e.g. some/thing -> somethingdifferent will lead to somethingdifferentAction() being called
     * Also, the SLASHES_TO_DASHES constant will change this behaviour
     * @param string $endpoint
     * @return string
     */
    public function route(string $endpoint): string
    {
        if (static::SLASHES_TO_DASHES) {
            return str_replace('/', '-', trim($endpoint, '/'));
        }
        return $endpoint;
    }

    /**
     * Helper to quickly test endpoints or generally the extending class
     * @return void
     */
    public function testAction(): void
    {
        $this->json([
            'success' => true,
            'endpoint' => $this->endpoint,
            'params' => $this->request->getParams(),
            'provider' => static::pureClassName(static::class)
        ]);
    }

    /**
     * output of a json result and exit
     * @param mixed $data
     * @return void
     */
    final protected function json(mixed $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return Zend_Controller_Request_Http
     */
    final public function getRequest(): Zend_Controller_Request_Http
    {
        return $this->request;
    }

    /**
     * @return string
     */
    final public function getCalledEndpoint(): string
    {
        return $this->endpoint;
    }
}
