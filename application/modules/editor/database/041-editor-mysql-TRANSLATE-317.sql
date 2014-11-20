--  /*
--  START LICENSE AND COPYRIGHT
--  
--  This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
--  
--  Copyright (c) 2014 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU General Public License version 3.0
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU General Public License version 3.0 requirements will be met:
--  http://www.gnu.org/copyleft/gpl.html.
-- 
--  For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
--  General Public License version 3.0 as specified by Sencha for Ext Js. 
--  Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
--  that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
--  For further information regarding this topic please see the attached license.txt
--  of this software package.
--  
--  MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
--  brought in accordance with the ExtJs license scheme. You are welcome to support us
--  with legal support, if you are interested in this.
--  
--  
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
--              with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
--  
--  END LICENSE AND COPYRIGHT 
--  */
-- 

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

INSERT INTO  `translate5`.`Zf_configuration` (
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
