-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

ALTER TABLE  `LEK_segment_field` ADD  `width` INT NOT NULL DEFAULT  '250' COMMENT  'first we have to set the default to 250 to have a sensemaking default for already imported data.';

ALTER TABLE  `LEK_segment_field` CHANGE  `width`  `width` INT( 11 ) NOT NULL DEFAULT  '0' COMMENT 'sets the width of the column in the GUI. Default 0, because actual max value is set with runtimeOptions.editor.columns.maxWidth and calculation needs to start at 0';

INSERT INTO  `Zf_configuration` (
`id` ,
`name` ,
`confirmed` ,
`module` ,
`category` ,
`value` ,
`default` ,
`defaults` ,
`type` ,
`description`
)
VALUES (
NULL ,  'runtimeOptions.editor.columns.widthFactor',  '1',  'editor',  'layout',  '8.6',  '8.6', NULL ,  'string', 'factor which is used to calculate the column width from the max chars of a column, if it can be smaller than maxWidth'
);

INSERT INTO  `Zf_configuration` (
`id` ,
`name` ,
`confirmed` ,
`module` ,
`category` ,
`value` ,
`default` ,
`defaults` ,
`type` ,
`description`
)
VALUES (
NULL ,  'runtimeOptions.editor.columns.widthFactorHeader',  '1',  'editor',  'layout',  '7',  '7', NULL ,  'string', 'factor which is used to calculate the column width from the chars of a column-header, if the otherwise calculated width would be to small for the header'
);

INSERT INTO  `Zf_configuration` (
`id` ,
`name` ,
`confirmed` ,
`module` ,
`category` ,
`value` ,
`default` ,
`defaults` ,
`type` ,
`description`
)
VALUES (
NULL ,  'runtimeOptions.editor.columns.widthFactorErgonomic',  '1',  'editor',  'layout',  '1.9',  '1.9', NULL ,  'string', 'factor which is used to calculate the column width for the ergonomic mode from the width which is set for the editing mode, if it is smaller than the maxWidth '
);

INSERT INTO  `Zf_configuration` (
`id` ,
`name` ,
`confirmed` ,
`module` ,
`category` ,
`value` ,
`default` ,
`defaults` ,
`type` ,
`description`
)
VALUES (
NULL ,  'runtimeOptions.editor.columns.maxWidth',  '1',  'editor',  'layout',  '250',  '250', NULL ,  'integer', 'default width for text contents columns in the editor in pixel. If column needs less space, this is adjusted automatically'
);
