<?php
class MainTest extends \ZfExtended_ApiTestcase {
    public function testFoo() {
        $this->login('manager', 'foo123*t5');

        $response = $this->request('editor/user', 'GET', array(
            'filter' => '[{"type":"string","value":"manager","field":"login"}]',
            'page' => 1,
            'start' => 0,
            'limit' => 20.
        ));
        
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
        
        error_log($response->getStatus());
        error_log($response->getBody());
    }
}