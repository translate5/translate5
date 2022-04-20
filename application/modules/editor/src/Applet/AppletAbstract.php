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

/**
 * Abstract Applet to configure applets like (termportal, instanttranslate, etc)
 */
abstract class AppletAbstract {
    /**
     * The URL path part of the application
     * @var string
     */
    protected string $urlPathPart;

    /**
     * The initial page ACL for the applet
     * @var string
     */
    protected string $initialPage;

    /**
     * Used for sorting which applet should be loaded if all are allowed; higher weight is used first
     * @var int
     */
    protected int $weight = 0;

    /**
     * returns the URL path part, may contain also a hash component!
     * @return string
     */
    public function getUrlPathPart(): string
    {
        return $this->urlPathPart;
    }

    /**
     * @return string
     */
    public function getInitialPage(): string {
        return $this->initialPage;
    }

    /**
     * returns true if the current user has this applet in his initial_page ACLs
     */
    public function hasAsInitialPage(): bool {
        return \editor_User::instance()->isAllowed('initial_page', $this->initialPage);
    }

    public function getWeight(): int
    {
        return $this->weight;
    }
}
