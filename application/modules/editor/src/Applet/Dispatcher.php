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

namespace MittagQI\Translate5\Applet;

use JetBrains\PhpStorm\NoReturn;
use Zend_Registry;

/**
 * Applet dispatcher for translate5 applets (termportal, instanttranslate, etc)
 */
class Dispatcher {

    /**
     * @var AppletAbstract[]
     */
    protected array $applets = [];

    /**
     * singleton instance
     * @var self
     */
    protected static self $instance;

    public static function getInstance(): self {
        if(empty(self::$instance)) {
            return self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {

        // add the default applet editor, if this will change move the register into editor bootstrap
        $this->registerApplet('editor', new class extends AppletAbstract {
            protected int $weight = 100; //editor should have the heighest weight
            protected string $urlPathPart = '/editor/';
            protected string $initialPage = 'editor';
        });
    }

    public function registerApplet(string $name, AppletAbstract $applet) {
        $this->applets[$name] = $applet;
    }

    /**
     * returns a mapping of a hash key to the underlying pathPart
     * @return array
     */
    public function getHashPathMap(): array {
        $map = [];
        foreach($this->applets as $hash => $applet) {
            if($applet->hasAsInitialPage()) {
                $map[$hash] = $applet->getUrlPathPart();
            }
        }
        return $map;
    }

    /**
     * Dispatch the configured applets by evaluating the given redirect hash
     * @param string|null $target
     */
    public function dispatch(string $target = null) {
        //sort applets by weight
        uasort($this->applets, function(AppletAbstract $appletA, AppletAbstract $appletB){
            return $appletB->getWeight() - $appletA->getWeight();
        });

        //defaulting to editor applet if nothing given as target
        $this->call($target ?? Zend_Registry::get('module'), false);

        //if we are still here (so not redirected away by above call),
        // we try to load the last used app
        /** @var \editor_Models_UserMeta $meta */
        $meta = \ZfExtended_Factory::get('editor_Models_UserMeta');
        $meta->loadOrSet(\editor_User::instance()->getId());
        if($meta->getId() != null && !empty($meta->getLastUsedApp())){
            $this->call($meta->getLastUsedApp());
        }
        $this->call(); //fallback if no lastUsedApp configured
    }

    /**
     * Call the desired applet by the given URL hash, which is tried to be used as name for the applet
     * @param string|null $hash
     * @param bool $useFallbackLoop
     */
    public function call(string $hash = null, bool $useFallbackLoop = true)
    {
        //if the requested app could be used, then use it
        $applet = $this->getApplet($hash);
        if(!is_null($applet) && $applet->hasAsInitialPage()) {
            $this->redirect($applet);
        }

        if(!$useFallbackLoop) {
            return;
        }

        //if not, loop over all available and check for usage
        foreach($this->applets as $applet) {
            if($applet->hasAsInitialPage()) {
                $this->redirect($applet);
            }
        }

        //if we reach here, its the part of the caller to handle the situation
    }

    /**
     * get the applet to a given name
     * @param string|null $name
     * @return AppletAbstract|null
     */
    public function getApplet(string|null $name): ?AppletAbstract
    {
        return $this->applets[$name] ?? null;
    }

    #[NoReturn] private function redirect(AppletAbstract $app)
    {
        header ('HTTP/1.1 302 Moved Temporarily');
        header ('Location: '.$app->getUrlPathPart());
        exit;
    }

}
