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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

CREATE TABLE `LEK_comment_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `commentId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_comments',
  `affectedField` varchar(24) DEFAULT '' COMMENT 'source or target',
  `originalId` varchar(255) DEFAULT '' COMMENT 'The original comment ID for imported comments',
  `severity` varchar(255) DEFAULT '' COMMENT 'A severity value if given for imported comments',
  `version` varchar(255) DEFAULT '' COMMENT 'A version value if given for imported comments',
  PRIMARY KEY (`id`),
  UNIQUE KEY `commentId` (`commentId`),
  CONSTRAINT FOREIGN KEY (`commentId`) REFERENCES `LEK_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES ('runtimeOptions.import.sdlxliff.importComments', '1', 'editor', 'import', '0', '0', '', 'boolean', 'Defines if SDLXLIFF comments should be imported or they should produce an error on import. See https://confluence.translate5.net/display/TFD/SDLXLIFF.'),
('runtimeOptions.import.sdlxliff.applyChangeMarks', '1', 'editor', 'import', '0', '0', '', 'boolean', 'Defines if SDLXLIFF change marks should be applied to and removed from the content, or if they should produce an error on import. See http://confluence.translate5.net/display/TFD/SDLXLIFF.');

