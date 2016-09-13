<?php
class HelpController extends ZfExtended_Controllers_Action {
	
	public function init(){
		parent::init();
	}
	
	public function indexAction(){
		$this->_helper->layout->disableLayout();
	}
    public function editorAction() {
    	$this->_helper->layout->disableLayout();
    }
    public function matchresourceAction() {
    	$this->_helper->layout->disableLayout();
    }
    public function taskoverviewAction() {
    	$this->_helper->layout->disableLayout();
    }
    public function useroverviewAction() {
    	$this->_helper->layout->disableLayout();
    }
}

?>
