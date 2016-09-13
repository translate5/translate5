<?php
class HelpController extends ZfExtended_Controllers_Action {
	
	
	/**
	 * @var Zend_Config
	 */
	protected $config;
	
	public function init(){
		parent::init();
		$this->config = Zend_Registry::get('config');
		$this->setJsVarsInView();
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
	protected function setJsVarsInView() {
		//$rop = $this->config->runtimeOptions;
		//Editor.data.enableSourceEditing → still needed for enabling / disabling the whole feature (Checkbox at Import).
		//$this->view->Php2JsVars()->set('helpUrl',$rop->helpUrl);
    }
    
}

?>
