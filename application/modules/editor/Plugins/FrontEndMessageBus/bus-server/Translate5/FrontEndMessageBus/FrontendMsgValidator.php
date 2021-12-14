<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\FrontEndMessageBus;

/**
 * Checks if a given methodname in this instance is usable from frontend
 */
trait FrontendMsgValidator {
    static protected $_validFrontendMethods = null;
    
    protected function _initValidFrontendMethods() {
        $ref = new \ReflectionClass($this);
        $methods = $ref->getMethods();
        $msgCls = 'Translate5\FrontEndMessageBus\Message\Msg';
        $backMsgCls = 'Translate5\FrontEndMessageBus\Message\BackendMsg';
        foreach($methods as $method) {
            /* @var $method \ReflectionMethod */
            $params = $method->getParameters();
            //a handler for a frontend message has exactly one parameter: The FrontendMsg object.
            if(count($params) !== 1) {
                continue;
            }
            $param = $params[0];
            /* @var $param \ReflectionParameter */
            $cls = $param->getType()?->getName() ?? null;
            if(empty($cls)) {
                continue;
            }
            $isMsg = $cls === $msgCls || is_subclass_of($cls, $msgCls);
            $isBackendMsg = $cls === $backMsgCls || is_subclass_of($cls, $backMsgCls);
            if($isMsg && !$isBackendMsg) {
                self::$_validFrontendMethods[] = $method->getName();
            }
        }
    }
    
    /**
     * 
     * @param string $methodName
     * @return boolean
     */
    public function isValidFrontendCall(string $methodName) {
        if(is_null(self::$_validFrontendMethods)) {
            $this->_initValidFrontendMethods();
        }
        return in_array($methodName, self::$_validFrontendMethods);
    }
}