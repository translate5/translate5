<?php
namespace Translate5\FrontEndMessageBus;

/**
 * Checks if a given methodname in this instance is usable from frontend
 */
trait FrontendMsgValidator {
    protected $_validFrontendMethods = null;
    
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
            $cls = $param->getClass();
            if(empty($cls)) {
                continue;
            }
            $isMsg = $cls->getName() === $msgCls || $cls->isSubclassOf($msgCls);
            $isBackendMsg = $cls->getName() === $msgCls || $cls->isSubclassOf($backMsgCls);
            if($isMsg && !$isBackendMsg) {
                $this->_validFrontendMethods[] = $method->getName();
            }
        }
    }
    
    public function isValidFrontendCall(string $methodName) {
        if(is_null($this->_validFrontendMethods)) {
            $this->_initValidFrontendMethods();
        }
        return in_array($methodName, $this->_validFrontendMethods);
    }
}