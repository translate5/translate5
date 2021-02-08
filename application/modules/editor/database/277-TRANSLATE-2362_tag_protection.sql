-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of translate5.
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

UPDATE `Zf_configuration` 
SET `description` = 'If set to active, the content of the file is treated as HTML/XML (regardless of its format). Tags inside the imported file are protected as tags in translate5 segments. This is done for all HTML5 tags and in addition for all tags that look like a valid XML snippet. If the import format is xliff, the HTML tags are expected to be escaped as entities (e. g. &lt;strong&gt; for an opening <strong>-tag). For other formats they are expected to be plain HTML (e. g. <strong> for a <strong>-tag). When importing SDLXLIFF or Transit files, this feature is not supported.'
WHERE `name` = 'runtimeOptions.import.fileparser.options.protectTags';
