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
use MittagQI\ZfExtended\Acl\ResourceInterface;
use MittagQI\ZfExtended\Acl\RightDTO;
use Zend_Registry;
use ZfExtended_Acl;
use ZfExtended_Authentication as Auth;
use ZfExtended_NotAuthenticatedException;

/**
 * Applet dispatcher for translate5 applets (termportal, instanttranslate, etc)
 */
class Dispatcher implements ResourceInterface
{
    /**
     * ACL Resource ID for containing all initial pages / applets. Used to control where a user is redirected after login.
     */
    public const INITIAL_PAGE_RESOURCE = 'initial_page';

    /**
     * @var AppletAbstract[]
     */
    protected array $applets = [];

    /**
     * singleton instance
     */
    protected static self $instance;

    public static function getInstance(): self
    {
        if (empty(self::$instance)) {
            return self::$instance = new self();
        }

        return self::$instance;
    }

    public function registerApplet(string $name, AppletAbstract $applet): void
    {
        $this->applets[$name] = $applet;
        uasort($this->applets, function (AppletAbstract $appletA, AppletAbstract $appletB) {
            return $appletB->getWeight() - $appletA->getWeight();
        });
    }

    public function getAllApplets(): array
    {
        return $this->applets;
    }

    /**
     * returns a mapping of a hash key to the underlying pathPart
     */
    public function getHashPathMap(): array
    {
        $map = [];
        foreach ($this->applets as $hash => $applet) {
            if ($applet->hasAsInitialPage()) {
                $map[$hash] = $applet->getUrlPathPart();
            }
        }

        return $map;
    }

    /**
     * Dispatch the configured applets by evaluating the given redirect hash
     */
    public function dispatch(string $target = null): void
    {
        if (empty($target)) {
            $target = $this->getDefaultAppletForUser();
        }
        //defaulting to the current registered module if nothing given as target
        $this->call($target ?? Zend_Registry::get('module'), false);

        //if we are still here (so not redirected away by above call),
        // we try to load the last used app
        /** @var \editor_Models_UserMeta $meta */
        $meta = \ZfExtended_Factory::get('editor_Models_UserMeta');
        $meta->loadOrSet(Auth::getInstance()->getUserId());
        if ($meta->getId() != null && ! empty($meta->getLastUsedApp())) {
            $this->call($meta->getLastUsedApp());
        }
        $this->call(); //fallback if no lastUsedApp configured
    }

    /**
     * returns the ACL resource ID for the Applet Dispatcher
     */
    public function getId(): string
    {
        return self::INITIAL_PAGE_RESOURCE;
    }

    /**
     * returns the ACL rights provided by the Applet Dispatcher as initial page resources
     * @return RightDTO[]
     */
    public function getRights(): array
    {
        $result = [];
        foreach ($this->applets as $applet) {
            $acl = new RightDTO();
            $acl->resource = $this->getId();
            $acl->name = $applet->getInitialPage();
            $acl->id = strtoupper($this->getId() . '_' . $acl->name);
            $acl->description = 'Allows Applet ' . $acl->name . ' as initial page';
            $result[] = $acl;
        }

        return $result;
    }

    /**
     * Call the desired applet by the given URL hash, which is tried to be used as name for the applet
     */
    public function call(string $hash = null, bool $useFallbackLoop = true): void
    {
        //if the requested app could be used, then use it
        $applet = $this->getApplet($hash);
        if (! is_null($applet) && $applet->hasAsInitialPage()) {
            $this->redirect($applet);
        }

        if (! $useFallbackLoop) {
            return;
        }

        //if not, loop over all available and check for usage
        foreach ($this->applets as $applet) {
            if ($applet->hasAsInitialPage()) {
                $this->redirect($applet);
            }
        }

        //if we reach here, its the part of the caller to handle the situation
    }

    /**
     * get the applet to a given name
     */
    public function getApplet(string|null $name): ?AppletAbstract
    {
        return $this->applets[$name] ?? null;
    }

    /***
     * Calculate the default applet/module for the currently authenticated.
     * @return string
     * @throws \Zend_Db_Table_Exception
     * @throws \Zend_Exception
     */
    public function getDefaultAppletForUser(): string
    {
        // default redirect to editor
        $applett = 'editor';

        try {
            $acl = ZfExtended_Acl::getInstance();
            /* @var ZfExtended_Acl $acl */

            // get all initial_page acl records for all available user roles
            $aclModules = $acl->getInitialPageModulesForRoles(Auth::getInstance()->getUserRoles());

            // find the module redirect based on the modulesOrder config
            foreach (APPLICATION_MODULES as $module) {
                if (in_array($module, $aclModules)) {
                    $applett = $module;

                    break;
                }
            }
        } catch (ZfExtended_NotAuthenticatedException $exception) {
            // the user has no session -> no applet can be found
            $applett = '';
        }

        return $applett;
    }

    public function checkForceRedirect(array $userRoles, bool $isTaskRequest): void
    {
        foreach ($this->applets as $applet) {
            if ($applet->shouldForceRedirect($userRoles, $isTaskRequest)) {
                $this->redirect($applet);
            }
        }
    }

    #[NoReturn]
    private function redirect(AppletAbstract $app): void
    {
        header('HTTP/1.1 302 Moved Temporarily');
        header('Location: ' . $app->getUrlPathPart());
        exit;
    }

    public function getAppletToRedirect(string $redirectTo): string|null
    {
        $redirectTo = rtrim($redirectTo, '/');
        foreach ($this->applets as $hash => $applet) {
            if (rtrim($applet->getUrlPathPart(), '/') == $redirectTo) {
                return $hash;
            }
        }

        return null;
    }
}
