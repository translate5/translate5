
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

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-05-31', 'TRANSLATE-2417', 'feature', 'OpenTM: writeable by default', 'The default enabled for configuration of language resources is now split up into read default rights and write default rights, so that reading and writing is configurable separately. The write default right is not automatically set for existing language resources.
The old API field "resourcesCustomersHidden" in language-resources to customers association will no longer be supported. It was marked as deprecated since April 2020. Please use only customerUseAsDefaultIds from now on.', '15'),
('2021-05-31', 'TRANSLATE-2315', 'feature', 'Filtering for Repeated segments in translate5s editor', 'Added a filter in the segments grid to filter for repeated segments.', '15'),
('2021-05-31', 'TRANSLATE-2196', 'feature', 'Complete Auto QA for translate5', 'Introduces a new Quality Assurance:

* Panel to filter the Segment Grid by quality
* GUI to set evaluated quality errors as "false positives"
* Improved panels to set the manual QA for the whole segment and within the segment (now independant from saving edited segment content)
* Automatic evaluation of several quality problems (AutoQA)

For an overview how to use the new feature, please see https://confluence.translate5.net/pages/viewpage.action?pageId=557218

For an overview of the new REST API, please see https://confluence.translate5.net/pages/viewpage.action?pageId=256737288', '15'),
('2021-05-31', 'TRANSLATE-2077', 'feature', 'Offer export of Trados-Style analysis xml', 'The match analysis report can be exported now in a widely usable XML format.', '15'),
('2021-05-31', 'TRANSLATE-2494', 'change', 'Plugins enabled by default', 'Enables ModelFront, IpAuthentication and PangeaMt plugins to be active by default.', '15'),
('2021-05-31', 'TRANSLATE-2481', 'change', 'Enable default deadline in configuration to be also decimal values (number of days in the future)', 'Default deadline date configuration accepts decimal number as configuration. You will be able to define 1 and a half day for the deadline when setting the config to 1.5', '15'),
('2021-05-31', 'TRANSLATE-2473', 'change', 'Show language names in language drop downs in InstantTranslate', 'The languages drop-down in instant translate will now show the full language name + language code', '15'),
('2021-05-31', 'TRANSLATE-2527', 'bugfix', 'Remove instant-Translate default rest api routes', 'The default rest-routes in instant translate are removed.', '15'),
('2021-05-31', 'TRANSLATE-2517', 'bugfix', 'NULL as string in Zf_configuration defaults instead real NULL values', 'Some default values in the configuration are not as expected.', '15'),
('2021-05-31', 'TRANSLATE-2515', 'bugfix', 'Remove the limit from customers drop-down', 'Fixes the customer limit in language resources customers combobox.', '15'),
('2021-05-31', 'TRANSLATE-2511', 'bugfix', 'PHP error on deleting tasks', 'Fixed seldom problem on deleting tasks:
ERROR in core: E9999 - Argument 1 passed to editor_Models_Task_Remover::cleanupProject() must be of the type int, null given', '15'),
('2021-05-31', 'TRANSLATE-2509', 'bugfix', 'Bugfix: target "_blank" in Links in the visual review causes unwanted popups with deactivated links', 'External Links opening a new window still cause unwanted popups in the Visual Review', '15'),
('2021-05-31', 'TRANSLATE-2499', 'bugfix', 'Search window saved position can be moved outside of the viewport', 'Search window saved position can be moved outside of the viewport and the user is then not able to move it back. This is fixed now for the search window, for other windows the bad position is not saved, so after reopening the window it is accessible again.
Also fixed logged configuration changes, always showing old value the system value instead the overwritten level value.', '15'),
('2021-05-31', 'TRANSLATE-2496', 'bugfix', 'Enable target segmentation in Okapi', 'So far target segmentation had not been activated in okapi segmentation settings. For PO files with partly existing target this let to <mrk>-segment tags in the source, but not in the target and thus to an import error in translate5. This is changed now.
', '15'),
('2021-05-31', 'TRANSLATE-2484', 'bugfix', 'Buffered grid "ensure visible" override', 'Fixes problems with the segment grid.', '15'),
('2021-05-31', 'TRANSLATE-2482', 'bugfix', 'Serialization failure: 1213 Deadlock found when trying to get lock', 'Fixes update worker progress mysql deadlock.', '15'),
('2021-05-31', 'TRANSLATE-2480', 'bugfix', 'Instant-translate expired user session', 'On expired session, the user will be redirected to the login page in instant translate or term portal.', '15'),
('2021-05-31', 'TRANSLATE-2478', 'bugfix', 'Add missing languages', 'Adds additional languages: 
sr-latn-rs, so-so, am-et, es-419, rm-ch, es-us, az-latn-az, uz-latn-uz, bs-latn-ba', '15'),
('2021-05-31', 'TRANSLATE-2455', 'bugfix', 'Empty Segment Grid after opening a task', 'Fixing a seldom issue where the segment grid remains empty after opening a task.', '15'),
('2021-05-31', 'TRANSLATE-2439', 'bugfix', 'prevent configuration mismatch on level task-import', 'Task import specific configurations are now fixed after the task import and can neither be changed for the rest of the task\'s lifetime nor can they be overwritten otherwise', '15'),
('2021-05-31', 'TRANSLATE-2410', 'bugfix', 'Add Warning for users editing Korean, Vietnamese or Japanese tasks when working with Firefox', 'This was no bug in translate5 - everything correct here - but a problem with Firefox on Windows for Korean and Vietnamese, preventing the users to enter Asiatic characters. Translate5 users with Korean or Vietnamese target language will get a warning message now, that they should switch to Chrome or Edge.', '15'),
('2021-05-31', 'TRANSLATE-1643', 'bugfix', 'A separate autostatus pretranslated is missing for pretranslation', 'Introduced new processing state (AutoStatus) "pretranslated".
This state is used for segments pre-translated in translate5, but also for imported segments which provide such information. For example SDLXLIFF: 
if edit100%percentMatch is disabled, used full TM matches not edited in Trados manually are not editable. So edited 100% matches are editable in translate5 by the reviewer now. Not changed has the behaviour for auto-propagated segments and segments with a match-rate < 100%: they are still editable as usual.', '15'),
('2021-05-31', 'TRANSLATE-1481', 'bugfix', 'Improve tag handling with matches coming from OpenTM2', 'The TMX files imported into OpenTM2 are modified. The internal tags are modified (removing type attribute and convert tags with content to single placeholder tags) to improve matching when finding segments.', '15');