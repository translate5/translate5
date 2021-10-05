<?php
/*
START LICENSE AND COPYRIGHT

This file is part of translate5

Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 *
 * Generate new bconf file
 *
 */
class editor_Plugins_Okapi_Bconf_Export
{
	const OKAPI_BCONF_BASE_PATH = 'D:/okapi/test/';
	const MAXBUFFERSIZE = 1024 * 8;
	const MAXBLOCKLEN = 45000;
	const SIGNATURE = "batchConf";
	const VERSION = 2;
	const  NUMPLUGINS = 0;
	const PIPELINEFILE = "D:/okapi/pipeline.pln";
	const EXTFILESDIR = "G:/projects/Marc/projects/translate5/application/modules/editor/Plugins/Okapi/data/okapi-import-bconf-generation/testfiles";

	protected $util;
	public function __construct(){
		$this->util = new editor_Plugins_Okapi_Bconf_Util();
	}
}