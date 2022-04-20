<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Controller plugin to hijack the given URI
 * it searches for currenttask/ID/editor/* in the URI, reroutes to editor/* and stores the found task ID internally
 */
class editor_Controllers_Plugins_LoadCurrentTask extends Zend_Controller_Plugin_Abstract {
    /**
     * URI delimiter
     */
    const URI_DELIMITER = '/';

    /**
     * The task ID of the current task or null, if no task currently used by this request
     * @var int|null
     */
    protected static ?int $currentTaskId = null;

    /**
     * returns the taskId of the currently opened task
     * @return int|null
     */
    public static function getTaskId(): ?int {
        return self::$currentTaskId;
    }

    /**
     * returns the URL path part to the task for the given ID
     * @param int $id
     * @return string
     */
    public static function makeUrlPath(int $id): string {
        return APPLICATION_RUNDIR . '/editor/taskid/'.$id.'/';
    }

    /**
     * invoked in routing startup so that the request can be parsed and modified for further processing without currenttask/ID
     * Example URI:
     * http://translate5.localdev/editor/taskid/1234/go/segment?_dc=1643116609168&page=2&start=200&limit=200
     * is parsed to get and remove the task ID 1234, is the resulting in URI
     * http://translate5.localdev/editor/segment?_dc=1643116609168&page=2&start=200&limit=200
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $uri = $request->getRequestUri();
//error_log("IN ".$uri);
        $uri = explode('?', $uri);
        $queryPath = empty($uri[1]) ? '' : ('?'.$uri[1]);

        $path = trim(reset($uri), self::URI_DELIMITER);
        $path = explode(self::URI_DELIMITER, $path);

        //we loop over the path parts to find out if we are "on a task"
        if (empty($path)) {
            return;
        }
        $pathToUse = [];
        do {
            $pathPart = array_shift($path);
            // if pathpart is "taskid"
            if($pathPart == 'taskid' && end($pathToUse) == 'editor') {
                //next part is the ID
                self::$currentTaskId = (int) array_shift($path);
                continue; //do not add taskid to pathToUse
            }

            //collect all other pathpars to pass it to normal request processing
            $pathToUse[] = $pathPart;
        } while (!empty($pathPart));
        $bareRestUrl = self::URI_DELIMITER.trim(join(self::URI_DELIMITER, $pathToUse), self::URI_DELIMITER);
        $request->setRequestUri($bareRestUrl.$queryPath);
        $request->setPathInfo(); //calculate the path with from the given request URI
//error_log("OUT ".$bareRestUrl.$queryPath);
    }
}
