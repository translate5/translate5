<?php
class MainTest extends \ZfExtended_Testcase {
    const AUTH_COOKIE_KEY = 'zfExtended';
    /**
     * enable xdebug debugger in eclipse
     * @var boolean
     */
    protected $xdebug = false;

    /**
     * Authentication / session cookie
     * @var string
     */
    protected static $authCookie;

    /**
     * Authenticated login
     * @var string
     */
    protected static $authLogin;
    
    /**
     * @param string $url
     * @return Zend_Http_Client
     */
    protected function request($url, $method = 'GET', $parameters = array()) {
        $http = new Zend_Http_Client();
        //FIXME from config:
        //$url = $server.$rundir.$url;
        $url = 'http://translate5.localdev/'.$url;
        $http->setUri($url);
        
        //enable xdebug debugger in eclipse
        if($this->xdebug) {
            $http->setCookie('XDEBUG_SESSION','ECLIPSE_DBGP_192.168.178.31');
            $http->setConfig(array('timeout'      => 3600));
        }
        
        if(!empty(self::$authCookie)) {
            $http->setCookie(self::AUTH_COOKIE_KEY, self::$authCookie);
        }
        
        $addParamsMethod = $method == 'POST' ? 'setParameterPost' : 'setParameterGet';
        
        if(!empty($parameters)) {
            foreach($parameters as $key => $value) {
                $http->$addParamsMethod($key, $value);
            }
        }
        
        return $http->request($method);
    }
    
    protected function login($login, $password) {
        if(isset(self::$authLogin) && self::$authLogin == $login){
            return;
        }
        
        $response = $this->request('editor/');
        $this->assertEquals(200, $response->getStatus(), 'Server did not respond HTTP 200');
        
        $cookies = $response->getHeader('Set-Cookie');
        $this->assertTrue(count($cookies) > 0, 'Server did not send a Cookie.');
        
        $sessionId = null;
        foreach($cookies as $cookie) {
            if(preg_match('/'.self::AUTH_COOKIE_KEY.'=([^;]+)/', $cookie, $matches)) {
                $sessionId = $matches[1];
            }
        }
        $this->assertNotEmpty($sessionId, 'No session ID given from server as Cookie.');
        self::$authCookie = $sessionId;
        self::$authLogin = $login;
        
        $body = $response->getBody();
        $noCsrf = null;
        if(preg_match('#<input\s+type="hidden"\s+name="noCsrf"\s+value="([^"]+)"\s+id="noCsrf"\s+/>#', $body, $matches)) {
            $noCsrf = $matches[1];
        }
        $this->assertNotEmpty($noCsrf, 'No "noCsrf" key found in server response.');
        
        $response = $this->request('login/', 'POST', array(
            'noCsrf' => $noCsrf,
            'login' => $login,
            'passwd' => $password,
        ));
        if(preg_match('#<ul class="errors">(.+)</ul>#s', $response->getBody(), $matches)) {
            $this->fail('Could not login to server, message was: '.$matches[1]);
        }
    }
    
    public function testFoo() {
        $this->login('manager', 'foo123*t5');

        $response = $this->request('editor/user', 'GET', array(
            'filter' => '[{"type":"string","value":"manager","field":"login"}]',
            'page' => 1,
            'start' => 0,
            'limit' => 20.
        ));
        
        //$http->setHeaders('Accept', 'application/json');
        error_log($response->getStatus());
        error_log($response->getBody());
    }
    
    public function testFoo2() {
        $this->login('manager', 'foo123*t5');

        $response = $this->request('editor/user', 'GET', array(
            'filter' => '[{"type":"string","value":"hannibal","field":"login"}]',
            'page' => 1,
            'start' => 0,
            'limit' => 20.
        ));
        
        //$http->setHeaders('Accept', 'application/json');
        error_log($response->getStatus());
        error_log($response->getBody());
    }
}