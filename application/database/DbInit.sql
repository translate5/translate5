-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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
--                       http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

-- MariaDB dump 10.19  Distrib 10.9.2-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: translate5
-- ------------------------------------------------------
-- Server version	10.9.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `LEK_browser_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_browser_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime DEFAULT NULL,
  `login` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'user login',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'userguid',
  `appVersion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'used browser version (navigator.appVersion)',
  `userAgent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'used userAgent (navigator.userAgent)',
  `browserName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'used browser (navigator.browserName)',
  `maxWidth` int(11) DEFAULT NULL COMMENT 'screen width',
  `maxHeight` int(11) DEFAULT NULL COMMENT 'screen height',
  `usedWidth` int(11) DEFAULT NULL COMMENT 'used window width',
  `usedHeight` int(11) DEFAULT NULL COMMENT 'used window height',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_browser_log`
--


--
-- Table structure for table `LEK_categories`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Where does this category come from / belong to?',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Name; can be original, but reliable reference to the original category is the originalCategoryId',
  `originalCategoryId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original id',
  `specificData` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Category specific info data',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categories serve the concept of labels and classifications (sometimes also referred to as "tags").';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_categories`
--


--
-- Table structure for table `LEK_change_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_change_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dateOfChange` date DEFAULT NULL,
  `jiraNumber` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userGroup` int(11) DEFAULT NULL,
  `type` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT 'change',
  `version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jiraNumberDate_unique` (`dateOfChange`,`jiraNumber`)
) ENGINE=InnoDB AUTO_INCREMENT=1343 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_change_log`
--

INSERT INTO `LEK_change_log` VALUES
(1,'2016-09-27','TRANSLATE-637','Inform users about application changes','The application informs users about new features / changes in a separate pop-up window.',14,'feature',NULL),
(2,'2016-09-27','TRANSLATE-137','Introduced a Maintenance Mode','The Maintenance Mode provides administrators with the ability to lock the whole application to prevent data loss on system updates.',14,'feature',NULL),
(3,'2016-09-27','TRANSLATE-680','Repetition-Editor now can handle tags in segments','Until this update the repetition editor could not handle segments containing tags. This means, these segments have never been found as repetitions. Now repetitions are found regardless of the tags and the tag-content. Just the tag position and number of tags in the segment must be equal in repeated segments.',14,'feature',NULL),
(4,'2016-09-27','TRANSLATE-612','User-Authentication via API','The API can now be used user authentication, see http://confluence.translate5.net/display/TAD/Session',8,'feature',NULL),
(5,'2016-09-27','TRANSLATE-664','Integrate separate help area','In configuration a URL to own help pages can be configured. The default header of translate5 must be used. See http://confluence.translate5.net/display/CON/Database+based+configuration',14,'feature',NULL),
(6,'2016-09-27','TRANSLATE-684','Introduce match-type column','Only for new imported tasks! The match type provided in SDLXLIFF files (like TM-Match, interactive, etc.) is now displayed as own hidden column in the segment grid and shown as icon with a tooltip in the match-rate column.',14,'feature',NULL),
(7,'2016-09-27','TRANSLATE-644','enable editor-only usage in translate5','Improved the ability to embed the Translate5 Editor component into an external system, that is used for task and user management.',8,'feature',NULL),
(8,'2016-09-27','TRANSLATE-718','Introduce a config switch to disable comment export','With TRANSLATE-707 exporting of comments into SDLXLIFF files was introduced. This can now optionally deactivated by a config switch.',12,'feature',NULL),
(9,'2016-09-27','TRANSLATE-625','Switch Task-Import and -export to work asynchronously','Switched import and export completely to asynchronous processing. This means, PMs do not have to wait for an import to finish, before they can proceed with other tasks in the GUI. It also means for admins, that they can now configure in the databse, how many imports are allowed to run at the same time. See runtimeOptions.worker.editor_Models_Import_Worker_SetTaskToOpen.maxParallelWorkers',12,'feature',NULL),
(10,'2016-09-27','TRANSLATE-621','New task status error','When there are errors while importing a task (configuration / corrupt data) the task remains in the application with the status „error“.',12,'feature',NULL),
(11,'2016-09-27','TRANSLATE-646','Improved segment content filter to handle special chars','Searching / filtering in segment content can deal now with special characters (for example German Umlaute)',14,'change',NULL),
(12,'2016-09-27','TRANSLATE-725','Fix error when using status column filter in task overview','',14,'bugfix',NULL),
(13,'2016-09-27','TRANSLATE-727','Fix error when using language column filters in task overview','',14,'bugfix',NULL),
(14,'2016-09-27','TRANSLATE-728','Provide missing column titles in match resource plug-in','Several column labels related to the MatchResource plug-in were missing.',14,'bugfix',NULL),
(15,'2016-09-27','several','Fixing ACL errors related to the plug-in system','Since more functionality is provided as plug-ins, some changes / fixes in core ACL system were needed.',8,'bugfix',NULL),
(16,'2016-09-27','TRANSLATE-715','Fix MQM short cut labels','The labelling of the keyboard shortcuts were wrong in the MQM menu.',14,'bugfix',NULL),
(17,'2016-10-17','TRANSLATE-726','New Column \"type\" in ChangeLog Plugin','A new column „type“ was added in the change-log grid, to show directly the type (bugfix, new feature, change) of a change log entry.',14,'feature',NULL),
(18,'2016-10-17','TRANSLATE-743','Implement filters in change-log grid','The change-log grid can be filtered right now.',14,'feature',NULL),
(19,'2016-10-17','TRANSLATE-612','User-Authentication via API - enable session deletion, login counter','Already existing sessions can now be deleted via API, the counter of invalid logins increases on invalid logins via API (restrictions are currently not implemented).',8,'change',NULL),
(20,'2016-10-17','TRANSLATE-644','enable editor-only usage in translate5 - enable direct task association','When creating the user session for the embedded editor a task to be opened can be associated to the session.',8,'change',NULL),
(21,'2016-10-17','TRANSLATE-750','Make API auth default locale configurable','When using the User Authentication API the default locale can be configured now.',8,'change',NULL),
(22,'2016-10-17','TRANSLATE-684','Introduce match-type column - fixing tests','Fixing the API tests according to the new column.',8,'bugfix',NULL),
(23,'2016-10-17','TRANSLATE-745','double tooltip on columns with icon in taskoverview','Since ExtJS6 Update the native tooltip implementation was in conflict with an custom implementation. This is solved.',14,'bugfix',NULL),
(24,'2016-10-17','TRANSLATE-749','session->locale sollte an dieser Stelle bereits durch LoginController gesetzt sein','Fixed seldom issue on login where the the following error was produced:“session ? locale sollte an dieser Stelle bereits durch LoginController gesetzt sein“',8,'bugfix',NULL),
(25,'2016-10-17','TRANSLATE-753','change-log-window is not translated on initial show','When he change-log window was opened automatically, it was not translated properly.',14,'bugfix',NULL),
(26,'2016-10-26','improved worker exception logging','Improve worker exception logging','Some types of exceptions were not logged when happened in worker context. This was fixed.',8,'change',NULL),
(27,'2016-10-26','TRANSLATE-759','Introduce a config switch to set the default application GUI language','Until now the GUI language was defined by the language setting in the browser. With the new optional config “runtimeOptions.translation.applicationLocale” a default language can be defined, overriding the browser language.',8,'change',NULL),
(28,'2016-10-26','TRANSLATE-751','Install and Update Script checks local DB configuration','Some Database settings are incompatible with the application. This is checked by the Install and Update Script now.',8,'change',NULL),
(29,'2016-10-26','TRANSLATE-760','Fix that sometimes source and target column were missing after import','Problem was introduced with refactoring import to Worker Architecture. Problem occurs only for users associated to the task. The visible columns in task specific settings were just initialized empty.',14,'bugfix',NULL),
(30,'2016-10-26','TRANSNET-10','Inserted translate statement in the default login page','The default login and password reset page did not contain statements to translate the error messages. This was fixed.',8,'bugfix',NULL),
(31,'2016-11-03','TRANSLATE-758','DbUpdater under Windows can not deal with DB Passwords with special characters','Only Windows users were affected by this issue, when using a MySQL DB Password with special characters. In this case the special character was removed by a PHP method before passing the password to MySQL. Then the script could not authenticate itself to the MySQL DB.',8,'bugfix',NULL),
(32,'2016-11-03','TRANSLATE-761','Task was not reloaded completely when switching from state import to open','This inclompete reload led to the problem that the source and target columns were missing when opening this task directly after import.',12,'bugfix',NULL),
(33,'2017-01-19','TRANSLATE-767','Changealike Window title was always in german','The title of the changealike popup window was always in german, regardless of the UI language',8,'bugfix',NULL),
(34,'2017-01-19','TRANSLATE-787','editor does not start anymore - on all installed instances','Due to a dependency on a foreign webservice translate5 did not start anymore since the webservice was deactivated.',8,'bugfix',NULL),
(35,'2017-01-19','TRANSLATE-782','Change text in task creation pop-up','Some notices for the users were updated in the task creation window.',12,'bugfix',NULL),
(36,'2017-01-19','TRANSLATE-781','different white space inside of SDLXLIFF internal tags leads to failures in relais import','Since the source segment is compared of Source Language and Relais Language, the content must be completely equal to get a matching relais segment. Segments containing tags did not match properly, since this internal tags could contain different whitespaces in the XML tags. This was fixed.',12,'bugfix',NULL),
(37,'2017-01-19','TRANSLATE-780','New Installations: ID column of LEK_browser_log must not be NULL','When installing the application the table statement of the browser logging could not be excecuted.',8,'bugfix',NULL),
(38,'2017-01-19','TRANSLATE-768','Db Updater complains about Zf_worker_dependencies is missing','When updating from an older version the updater complained about a missing Zf_worker_dependencies table.',8,'bugfix',NULL),
(39,'2017-03-29','TRANSLATE-807','Change default editor mode to ergonomic mode (configurable)','The new default mode of the editor is the so called ergonomic mode with bigger visualization of the source and target segment. This can be changed in the configuration on installation level.',14,'feature',NULL),
(40,'2017-03-29','TRANSLATE-796','Enhance usability of concordance search','The concordance search of the MatchResource plug-in is changed, so that more search results are loaded automatically when the user scrolls down in the result grid.',14,'feature',NULL),
(41,'2017-03-29','TRANSLATE-826','Show only a maximum of MessageBox messages','Saving a segment produces a uncritical system notification. In combination with other features and plug-ins not only one but multiple messages appear for each saved segment. When navigating very fast trough the application and save segments fastly the screen was flooded with such messages. Now only a specific amount of such messages remains on the screen. New messages deletes the oldest ones directly, instead of beeing added to the list of shown messages.',14,'feature',NULL),
(42,'2017-03-29','TRANSLATE-821','Switch translate5 to Triton theme','translate5 uses now the more modern Triton theme',14,'feature',NULL),
(43,'2017-03-29','TRANSLATE-502','OpenTM2-Integration into MatchResource plug-in','OpenTM2 is now usable through the MatchResource Plugin.',14,'feature',NULL),
(44,'2017-03-29','TRANSLATE-820','Implementation: Generalization of Languages model into ZfExtended','Implementation Detail: The Languages PHP class was moved into the ZfExtended library',8,'change',NULL),
(45,'2017-03-29','TRANSLATE-818','handling and structure of internal tags refactored – conversion script needs a long time!','Internal tags was using a unique HTML id, which was causing trouble in comparing internal tags. This unique IDs were removed since not needed any more.',12,'change',NULL),
(46,'2017-03-29','MITTAGQI-30','Some licenses has been changed','The Plugin exception of the translate5 license has been removed, the ZfExtended library is now under LGPL.',14,'change',NULL),
(47,'2017-03-29','TRANSLATE-833','Add application locale to the configurable Help URL','The configurable help pages receive now also the language key of the currently used language in the application, so that the help pages can also be localized.',8,'bugfix',NULL),
(48,'2017-03-29','TRANSLATE-839','Database importer problems with character set on non utf8mb4 systems','No character set was defined for the mysql connection of the database updater, so that the system default character set for mysql connections was used instead. This was leading to problems in some SQL files containing non ASCII characters.',8,'bugfix',NULL),
(49,'2017-03-29','TRANSLATE-844','Roweditor gets reduced to small bar in seldom circumstances','In seldom circumstances (switching tag view modes in combination with resetting segment grid filter and sorting) the roweditor was reduced to a small bar with about 10px height. This cannot happen anymore.',14,'bugfix',NULL),
(50,'2017-03-29','TRANSLATE-758','DbUpdater under Windows can not deal with DB Passwords with special characters','Some special characters cannot be used in the database password under windows. In this case the user is informed by the installer / database updater.',8,'bugfix',NULL),
(51,'2017-03-29','TRANSLATE-805','show match type tooltip also in roweditor','The match type tooltip was missing when hovering the roweditor',14,'bugfix',NULL),
(52,'2017-04-05','TRANSLATE-850','The frontend of the application did not recognize anymore when the user was logged out due timeout','The graphical user interface displays an error if the user was logged of due timeout instead of redirecting to the login page.',14,'bugfix',NULL),
(53,'2017-04-24','TRANSLATE-871','New Tooltip should show segment meta data over segmentNrInTask column','A new tooltip over the segments segmentNrInTask column should show all segment meta data expect the data which is already shown (autostates, matchrate and locked (by css))',14,'feature',NULL),
(54,'2017-04-24','TRANSLATE-823','Internal tags are ignored for relais import segment comparison ','When relais data is imported, the source columns of relais and normal data are compared to ensure that the alignment is correct. In this comparison internal tags are ignored now completely. Also HTML entities are getting normalized on both sides of the comparison.',12,'change',NULL),
(55,'2017-04-24','TRANSLATE-870','Enable MatchRate and Relais column per default in ergonomic mode','Enable MatchRate and Relais column per default in ergonomic mode',14,'change',NULL),
(56,'2017-04-24','TRANSLATE-875','The width of the relais column was calculated wrong','Since using ergonomic mode as default mode, the width of the relais column was calculated too small',14,'bugfix',NULL),
(57,'2017-05-29','TRANSLATE-871','New Tool-tip should show segment meta data over “segmentNrInTask” column','A new tool-tip over the segments “segmentNrInTask” column should show all segment meta data expect the data which is already shown (autostates, matchrate and locked (by css))',14,'feature',NULL),
(58,'2017-05-29','TRANSLATE-878','Enable GUI JS logger TheRootCause','By default the front-end error logger and feedback tool is enabled. The user always has the choice to send feedback to MittagQI. On JS errors the user gets also the choice to send technical information related to the error directly to MittagQI.',14,'feature',NULL),
(59,'2017-05-29','TRANSLATE-877','Make Worker URL separately configurable','For special system setups (for example using translate5 behind a SSL proxy) it may be necessary to configure the worker base URL separately compared to the public base URL.',8,'feature',NULL),
(60,'2017-05-29','TRANSLATE-823','Internal tags are ignored for relays import segment comparison ','When relays data is imported, the source columns of relays and normal data are compared to ensure that the alignment is correct. In this comparison internal tags are ignored now completely. Also HTML entities are getting normalized on both sides of the comparison.',12,'change',NULL),
(61,'2017-05-29','TRANSLATE-870','Enable MatchRate and Relays column per default in ergonomic mode','Enable MatchRate and Relais column per default in ergonomic mode',14,'change',NULL),
(62,'2017-05-29','TRANSLATE-857','change target column names in the segment grid','The target column names in the segment grid are changed from just “target” to “target (version on import time)”',14,'change',NULL),
(63,'2017-05-29','TRANSLATE-880','XLF import: Copy source to target, if target is empty or does not exist','On translation tasks (no translation exists at all) the target fields were empty on import time. For XLF files this is changed: The source content is copied to the target column.',12,'change',NULL),
(64,'2017-05-29','TRANSLATE-897','changes.xliff generation: alt-trans shorttext for target columns must be changed','In the lat-trans fields of the generated changes.xliff files, the shorttext attribute is changed for target columns. It contains now target instead of Zieltext.',12,'change',NULL),
(65,'2017-05-29','TRANSLATE-875','The width of the relays column was calculated wrong','Since using ergonomic mode as default mode, the width of the relays column was calculated too small',14,'bugfix',NULL),
(66,'2017-05-29','TRANSLATE-891','OpenTM2 responses containing Unicode characters and internal tags produces invalid HTML in the editor','If OpenTM2 returns a response containing Unicode characters with multiple bytes, and this response contains also internal tags, this was leading to invalid HTML on showing such a segment in the front-end.',14,'bugfix',NULL),
(67,'2017-05-29','TRANSLATE-888','Mask tab character in source files with internal tag (similar to multiple spaces)','Tabulator characters contained in the imported data are now converted to internal tags. A similar converting is already done for multiple white-space characters.',14,'bugfix',NULL),
(68,'2017-05-29','TRANSLATE-879','sdlxliff and XLF import does not work with missing target tags','On translation tasks the target tags in XML based import formats (SDLXLIFF and XLF) are missing. This leads to errors while importing such files. This is fixed for XLF files right now.',12,'bugfix',NULL),
(69,'2017-06-13','TRANSLATE-885','On translation tasks the original target content and its hash for the repetition editor is also filled','On translation tasks (all targets are initially empty) the original target column and its hash for the repetition editor is also filled with the translated content of the editable target column.',12,'feature',NULL),
(70,'2017-06-13','TRANSLATE-894','The source content (incl. Tags) can be copied to the target column with CTRL-INS','The source content (incl. Tags) can be copied to the target column with CTRL-INS',14,'feature',NULL),
(71,'2017-06-13','TRANSLATE-895','Individual tags can be copied from source to target by pressing CTRL + , followed by the tagnumber','Individual tags can be copied from source to target by pressing CTRL + , (comma) followed by the tagnumber.',14,'feature',NULL),
(72,'2017-06-13','TRANSLATE-901','A flexible extendable task creation wizard was introduced','A flexible extendable task creation wizard was introduced',14,'feature',NULL),
(73,'2017-06-13','TRANSLATE-902','With the Globalese Plug-In pretranslation with Globalese Machine Translation is possible','With the Globalese Plug-In pretranslation with Globalese Machine Translation is possible',14,'feature',NULL),
(74,'2017-06-13','TRANSLATE-296','Internal code refactoring to unify handling with special characters on the import','Some internal code refactoring was done, to unify the escaping of special characters and whitespace characters for all available import formats.',12,'change',NULL),
(75,'2017-06-13','TRANSLATE-896','The button layout on the segment grid toolbar was optimized','The button layout on the segment grid toolbar was optimized.',14,'change',NULL),
(76,'2017-06-23','TRANSLATE-882','Switch default match resource color from red to a nice green','',12,'change',NULL),
(77,'2017-06-23','TRANSLATE-845','On ended tasks (in general tasks with a missing materialized view) starting an export (with enabled SegmentStatistics Plug-In) leads to an error','This is fixed with this update.',14,'bugfix',NULL),
(78,'2017-06-23','TRANSLATE-904','Some content entered in the editor led to problems with OpenTM2','The user was able to paste linebreaks or other special characters in the editor. This led to errors in attached OpenTM2 instances.',14,'bugfix',NULL),
(79,'2017-07-04','TRANSLATE-911','Workflow Notification mails could be too large for underlying mail system','The attachment of the changes.xliff is now configurable and disabled by default, so that the generated e-mails are much smaller.',12,'change',NULL),
(80,'2017-07-04','TRANSLATE-907','Several smaller issues (wording, code changes etc)','TRANSLATE-906: translation bug: \"Mehr Info\" in EN<br>TRANSLATE-909: Editor window - change column title \"Target text(zur Importzeit)\"<br>TRANSLATE-894: Copy source to target – FIX<br>TRANSLATE-907: Rename QM-Subsegments to MQM in the GUI<br>TRANSLATE-818: internal tag replace id usage with data-origid and data-filename - additional migration script<br>TRANSLATE-895: Copy individual tags from source to target - ToolTip<br>TRANSLATE-885: fill non-editable target for translation tasks - compare targetHash to history<br>small fix for empty match rate tooltips showing \"null\"',14,'change',NULL),
(81,'2017-07-11','TRANSLATE-628','Log changed terminology in changes xliff','In the changes.xlf generated on workflow notifications, the terminology of the changed segments is added as valid mrk tags.',14,'change',NULL),
(82,'2017-07-11','TRANSLATE-921','Saving ChangeAlikes reaches PHP max_input_vars limit with a very high repetition count','Saving ChangeAlikes reaches PHP max_input_vars limit with a very high repetition count',8,'bugfix',NULL),
(83,'2017-07-11','TRANSLATE-922','Segment timestamp updates only on the first save of a segment','Segment timestamp updates only on the first save of a segment',14,'bugfix',NULL),
(84,'2017-08-07','TRANSLATE-925','Support xliff 1.2 as import format','See: http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#Struct_Segmentation',12,'feature',NULL),
(85,'2017-08-07','T5DEV-172','Ext 6.2 update prework: Quicktip manager instances have problems if configured targets does not exist anymore','Preparation for the upcoming update to ExtJS 6.2',8,'change',NULL),
(86,'2017-08-07','T5DEV-171','Ext 6.2 update prework: Get Controller instance getController works only with full classname','Preparation for the upcoming update to ExtJS 6.2',8,'change',NULL),
(87,'2017-08-07','TRANSLATE-953','Direct Workers (like GUI TermTagging) are using the wrong worker state','This problem was leading only seldom to errors.',8,'bugfix',NULL),
(88,'2017-08-17','TRANSLATE-957','XLF Import: Different tag numbering in review tasks on tags swapped position from source to target','Internal tags were numbered different in short tag view mode between source and target, if the tag position swapped between source and target',14,'change',NULL),
(89,'2017-08-17','TRANSLATE-955','XLF Import: Whitespace import in XLF documents','The application reacts differently on existing whitespace in XLF documents. The default behaviour of the application can be configured to ignore or preserve whitespace. In any case the xml:space attribute is respected.',14,'change',NULL),
(90,'2017-08-17','TRANSLATE-925','support xliff 1.2 as import format - several smaller fixes','Several smaller issues were fixed for the XLF import.',12,'bugfix',NULL),
(91,'2017-08-17','TRANSLATE-971','Importing an XLF with comments produces an error','XLF Import could not deal with XML comments',12,'bugfix',NULL),
(92,'2017-08-17','TRANSLATE-937','translate untranslated GUI elements','Added two english translations',14,'bugfix',NULL),
(93,'2017-08-17','TRANSLATE-968','Ignore CDATA blocks in the Import XMLParser','XLF Import could not deal with CDATA blocks',12,'bugfix',NULL),
(94,'2017-08-17','TRANSLATE-967','SDLXLIFF segment attributes could not be parsed','In special cases some SDLXLIFF attributes could not be parsed',12,'bugfix',NULL),
(95,'2017-08-17','MITTAGQI-42','Changes.xliff filename was invalid under windows; ErrorLogging was complaining about a missing HTTP_HOST variable','The generated changes.xliff filename was changed for windows installations, error logging can deal now with missing HTTP_HOST variable.',12,'bugfix',NULL),
(96,'2017-08-17','TRANSLATE-960','Trying to delete a task user assoc entry produces an exception with enabled JS Logger','Only installations with activated JS Logger were affected.',12,'bugfix',NULL),
(97,'2017-09-14','TRANSLATE-994','Support RTL languages in the editor','The editor now supports RTL languages. Which language should be be rendered as rtl language can be defined per language in the LEK_languages table.',14,'feature',NULL),
(98,'2017-09-14','TRANSLATE-974','Save all segments of a task to a TM','All segments of chosen tasks can be saved in a bulk manner into a TM of OpenTM2',12,'feature',NULL),
(99,'2017-09-14','TRANSLATE-925','support xliff 1.2 as import format - improve fileparser to file extension mapping','Improves internal handling of file suffixes. ',12,'change',NULL),
(100,'2017-09-14','TRANSLATE-926','ExtJS 6.2 update','The graphical user interface ExtJS is updated to version ExtJS 6.2',8,'change',NULL),
(101,'2017-09-14','TRANSLATE-972','translate5 does not check, if there are relevant files in the import zip','It could happen that a import ZIP could not contain any file to be imported. This produced unexpected errors.',12,'change',NULL),
(102,'2017-09-14','TRANSLATE-981','User inserts content copied from rich text wordprocessing tool','Pasting translation content from another website or word processing tool inserted also invisible formatting characters into the editor. This led to errors on saving the segment.',14,'change',NULL),
(103,'2017-09-14','TRANSLATE-984','The editor converts single quotes to the corresponding HTML entity','The XLF Import introduced a bug on typing single quotes in the editor, they were stored as HTML entity instead as character. A migration script is provided with this fix.',14,'bugfix',NULL),
(104,'2017-09-14','TRANSLATE-997','Reset password works only once without reloading the user data','Very seldom issue in the user administration interface, a user password could only be resetted once in the session.',12,'bugfix',NULL),
(105,'2017-09-14','TRANSLATE-915','JS Error: response is undefined','Some seldom errors in the workflow handling on the server triggers an error which was not properly handled in the GUI, this is fixed now.',12,'bugfix',NULL),
(106,'2017-10-16','TRANSLATE-869','Okapi integration for source file format conversion','Okapi is integrated to convert different source file formats into importable XLF.',12,'feature',NULL),
(107,'2017-10-16','TRANSLATE-995','Import files with generic XML suffix with auto type detection','Import files with generic XML suffix if they are recognized as XLF files.',12,'feature',NULL),
(108,'2017-10-16','TRANSLATE-994','Support RTL languages in the editor','translate5 supports now also RTL languages. This can be set per language in the languages DB table.',14,'feature',NULL),
(109,'2017-10-16','TRANSLATE-1012','Improve REST API on task creation','The following new data fields were added to a task: workflowStepName, foreignId and foreignName. On task creation for the language fields not only the DB internal language IDs can be used, but also the rfc5646 value and the lcid (prefixed with lcid-). See the REST API document in confluence.',8,'change',NULL),
(110,'2017-10-16','TRANSLATE-1004','Enhance text description for task grid column to show task type','The column label of the translation job boolean column the task overview was changed.',8,'change',NULL),
(111,'2017-10-16','TRANSLATE-1011','XLIFF Import can not deal with internal unicodePrivateUseArea tags','Some special utf8mb4 characters could not be imported with the XLIFF import, this fixed now.',12,'bugfix',NULL),
(112,'2017-10-16','TRANSLATE-1015','Reference Files are not attached to tasks','Reference files were not imported anymore, this is fixed now.',14,'bugfix',NULL),
(113,'2017-10-16','TRANSLATE-983','More tags in OpenTM2 answer than in translate5 segment lead to error','If an OpenTM2 answer contains more tags as expected, this leads to an error in translate5. This is fixed now.',14,'bugfix',NULL),
(114,'2017-10-16','TRANSLATE-972','translate5 does not check, if there are relevant files in the import zip','Translate5 crashed on the import if there were no usable files in the import ZIP. Now translate5 checks the existence of files and logs an error message instead of crashing.',12,'bugfix',NULL),
(115,'2017-10-19','TRANSLATE-944','Import and Export comments from Across Xliff','Across comments to trans-units are imported to and exported from translate5 segments.',14,'feature',NULL),
(116,'2017-10-19','TRANSLATE-1013','Improve embedded translate5 usage by a static link','For usage details see confluence.',12,'feature',NULL),
(117,'2017-10-19','T5DEV-161','Non public VisualReview Plug-In','This plug-in turns translate5 into a visual review editor. The Plug-In is not publically available!',14,'feature',NULL),
(118,'2017-10-19','TRANSLATE-1028','Correct wrong or misleading language shortcuts','Some wrong language tags are corrected: \"jp\" will be replaced by \"ja\" (refers to Japanese), \"no\" will be replaced by \"nb\" (refers to Norwegian Bokmal); Serbian (cyrillic) \"sr-Cyrl\" was added; Uszek (cyrillic) \"uz-Cyrl\" was added; Norwegian (Nynorsk) \"nn\" was added',12,'change',NULL),
(119,'2017-11-14','TRANSLATE-931','Tag check can NOT be skipped in case of tag error (configurable)','The application checks on saving a segment if all tags of the source exist in the target and also if tag order is correct. The user has had the choice to ignore the tag errors and save anyway. Now it is configurable if the user can save an erroneous segment anyway or not.',14,'feature',NULL),
(120,'2017-11-14','TRANSLATE-822','import XLF segment min and max length for future usage','XLF provides attributes to define the minimal and maximal length of segments. This constraints are imported and saved right now for usage in the future.',12,'feature',NULL),
(121,'2017-11-14','TRANSLATE-1027','Add translation step in default workflow','The default workflow covers now also translations tasks by providing a new first step \"translation\". The second step will then be \"proofReading\" and the third one \"translatorCheck\". All steps are optional and can be chosen just by setting the user to task association properly.',12,'feature',NULL),
(122,'2017-11-14','TRANSLATE-1001','Tag check did not work for translation tasks','Due the history reasons the tag validation on saving a task could not deal with translation tasks. This is fixed right now.',14,'bugfix',NULL),
(123,'2017-11-14','TRANSLATE-1037','VisualReview and feedback button are overlaying each other','The Feedback Button to send feedback about the application (if enabled so far) overlaps with the minimal navigation in visual review simple mode. This is fixed now by just hiding the feedback button in simple mode.',14,'bugfix',NULL),
(124,'2017-11-14','TRANSLATE-763','SDLXLIFF imports no segments with empty target tags','Some versions of SDL Studio export empty targets as empty single target tag. The import could not deal with such segments, this is fixed right now.',12,'bugfix',NULL),
(125,'2017-11-14','TRANSLATE-1051','Internal XLIFF reader for internal application translation can not deal with single tags','The internal XLIFF reader for internal application localization can not deal with single tags, this is fixed now for br tags.',8,'bugfix',NULL),
(126,'2017-11-30','TRANSLATE-935','Configure columns of task overview on system level','The position and visibility of grid columns in the task overview can be predefined.',14,'feature',NULL),
(127,'2017-11-30','TRANSLATE-905','Improve formatting of the maintenance mode message and add timezone to the timestamp.','Improve formatting of the maintenance mode message and add timezone to the timestamp.',14,'change',NULL),
(128,'2017-11-30','T5DEV-198','Fixes for the non public VisualReview Plug-In','Bugfixes for VisualReview. This plug-in turns translate5 into a visual review editor. The Plug-In is not publically available!',12,'bugfix',NULL),
(129,'2017-11-30','TRANSLATE-1063','VisualReview Plug-In: missing CSS for internal tags and to much line breaks','In the VisualReview some optical changes were made for the mouse over effect.',14,'bugfix',NULL),
(130,'2017-11-30','TRANSLATE-1053','Repetition editor starts over tag check dialog on overtaking segments from MatchResource','On saving a segment taken over from a match resource and having a incorrect internal tag structure the repetition editor dialog was overlapping the tag check dialog.',12,'bugfix',NULL),
(131,'2017-12-06','TRANSLATE-1074','On using the auth hash to open a task, finished tasks should be openable readonly.','Clicking on a link with a authhash the user does not know it the task is already finished. If the task is in status finished or waiting it should be opened readonly.',12,'feature',NULL),
(132,'2017-12-06','TRANSLATE-1055','Disable the rootcause feedback button.','Disable the rootcause feedback button.',12,'change',NULL),
(133,'2017-12-06','TRANSLATE-1073','Update configured languages.','All major languages are added and in some cases also corrected according rfc5646 standards. Where available MS LCIDs are added. Users who use language shortcuts that are not the same as rfc5646 should be careful with importing the corresponding changes sql to their database.',12,'change',NULL),
(134,'2017-12-06','TRANSLATE-1072','Set default GUI language for users to EN','Set default GUI language for users to EN',12,'change',NULL),
(135,'2017-12-06','visualReview','visualReview: fixes for translate5 embedded editor usage and RTL fixes','Some fixes were needed in order  to use VisualReview in embedded translate5.',12,'bugfix',NULL),
(136,'2017-12-11','TRANSLATE-1061','Add user locale dropdown to user add and edit window','When editing or adding a user in the administration panel, the user locale used in the GUI can now be presetted in the GUI.',12,'feature',NULL),
(137,'2017-12-11','TRANSLATE-1081','Using a taskGuid filter on /editor/task does not work for non PM users','Using a taskGuid filter on /editor/task does not work for non PM users',8,'bugfix',NULL),
(138,'2017-12-11','TRANSLATE-1077','Segment editing in IE 11 does not work','Segment editing in IE 11 does not work, this is reenabled now.',14,'bugfix',NULL),
(139,'2017-12-14','TRANSLATE-1084','refactor internal translation mechanism and usage of translation in emails','The internal translation mechanism was partly refactored, since it was not properly used in sending emails. This is fixed right now.',12,'change',NULL),
(140,'2017-12-14','several smaller issues','Several smaller issues','missing visitor mail templates',12,'bugfix',NULL),
(141,'2018-01-17','TRANSLATE-950','Implement a user hierarchy for user listing and editing','If the user has not the right to see all users, he sees just the users he has created (his child users), and the recursively their child users too.',12,'feature',NULL),
(142,'2018-01-17','TRANSLATE-1089','Create segment history entry when set autostatus untouched, auto-set and reset username on unfinish','If reverting in the workflow from step translator check back to proofreading, all segments with autostate untouched, auto-set are reset to their original autostate. This is tracked in the segment history right now. Also the previous editor is set in the segment (on setting it to untouched the responsible proofreader was entered).',14,'feature',NULL),
(143,'2018-01-17','TRANSLATE-1099','Exclude framing internal tags from xliff import','On importing XLIFF proofreading tasks, internal tags just encapsulating the segment content are not imported anymore. This makes editing easier.',14,'feature',NULL),
(144,'2018-01-17','TRANSLATE-941','New front-end rights enable more fine grained access to features in the GUI','New front-end rights enable more fine grained access to features in the GUI',12,'feature',NULL),
(145,'2018-01-17','TRANSLATE-942','New task attributes tab in task properties window','Some task attributes (like task name) can be changed via the new task attributes panel.',12,'feature',NULL),
(146,'2018-01-17','TRANSLATE-1090','Implement an ACL right to allow the usage of roles in the user administration panel.','Through that new ACL right it can be controlled which ACL roles the current user can set to other users.',12,'feature',NULL),
(147,'2018-01-17','Integrate segmentation rules for EN in Okapi default bconf-file','Integrate segmentation rules for EN in Okapi default bconf-file','Integrate segmentation rules for EN in Okapi default bconf-file',12,'change',NULL),
(148,'2018-01-17','TRANSLATE-1091','Rename \"language\" field/column in user grid / user add window','Trivial text change in the user administration.',12,'change',NULL),
(149,'2018-01-17','TRANSLATE-1101','Using Translate5 in internet explorer leads sometimes to logouts while application load','It can happen that users of internet explorer were logged out automatically directly after the application was loaded.',14,'bugfix',NULL),
(150,'2018-01-17','TRANSLATE-1086','Leaving a visual review task in translate5 leads to an error in IE 11','Leaving a visual review task in translate5 leads to an error in IE 11',14,'bugfix',NULL),
(151,'2018-01-17','T5DEV-219','Error Subsegment img found on saving some segments with tags and enabled track changes','Under some circumstances the saving of a segment fails if track changes is active, and the segment contains internal tags.',14,'bugfix',NULL),
(152,'2018-01-22','TRANSLATE-1076','windows only: install-and-update-batch overwrites path to mysql executable – fix config in installation.ini','If your windows server is affected (on each update the mysql_bin path had to be changed manually) check the installation.ini and change resource.db.params.executable to resource.db.executable!',8,'bugfix','2.8.7'),
(153,'2018-01-22','TRANSLATE-1103','TrackChanges Plug-In only: Open segment for editing leads to an error in IE11','On editing a segment in IE 11 with activated TrackChanges Plug-In an error in the front-end was triggered. This was fixed.',14,'bugfix','2.8.7'),
(154,'2018-02-13','TRANSLATE-32','Search and Replace in translate5 editor','The search and replace functionality is currently only available with enabled TrackChanges Plugin. ',14,'feature','2.8.7'),
(155,'2018-02-13','TRANSLATE-1116','Clone a already imported task','Clone a already imported task - must be activated via an additional ACL right editorCloneTask (resource frontend). ',12,'feature','2.8.7'),
(156,'2018-02-13','TRANSLATE-1109','Enable import of invalid XLIFF used for internal translations','Enable import of invalid XLIFF used for internal translations',8,'feature','2.8.7'),
(157,'2018-02-13','TRANSLATE-1107','VisualReview Converter Server Wrapper','With the VisualReview Converter Server Wrapper the converter can be called on a different server as the webserver.',8,'feature','2.8.7'),
(158,'2018-02-13','TRANSLATE-1019','Improve File Handling Architecture in the import and export process','Improve File Handling Architecture in the import and export process',8,'change','2.8.7'),
(159,'2018-02-13','T5DEV-218','Enhance visualReview matching algorithm','Enhance visualReview matching algorithm',12,'change','2.8.7'),
(160,'2018-02-13','TRANSLATE-1017','Use Okapi longhorn for merging files back instead tikal','Use Okapi longhorn for merging files back instead tikal',12,'change','2.8.7'),
(161,'2018-02-13','TRANSLATE-1121','Several minor improvement in the installer','Several minor improvement in the installer',8,'change','2.8.7'),
(162,'2018-02-13','TRANSLATE-667','GUI cancels task POST requests longer than 60 seconds','Now bigger uploads taking longer as 60 seconds are uploaded successfully.',12,'change','2.8.7'),
(163,'2018-02-13','TRANSLATE-1131','Internet Explorer compatibility mode results in non starting application','Internet Explorer triggers the compatibility mode for translate5 used in intranets.',14,'bugfix','2.8.7'),
(164,'2018-02-13','TRANSLATE-1122','TrackChanges: saving content to an attached matchresource (openTM2) saves also the <del> content','TrackChanges: saving content to an attached matchresource (openTM2) saves also the <del> content',14,'bugfix','2.8.7'),
(165,'2018-02-13','TRANSLATE-1108','VisualReview: absolute paths for CSS and embedded fonts are not working on installations with a modifed APPLICATION_RUNDIR','VisualReview: absolute paths for CSS and embedded fonts are not working on installations with a modifed APPLICATION_RUNDIR',8,'bugfix','2.8.7'),
(166,'2018-02-13','TRANSLATE-1138','Okapi Export does not work with files moved internally in translate5','Files moved inside the file tree on the left side of the editor window, could not merged back with Okapi on export.',12,'bugfix','2.8.7'),
(167,'2018-02-13','TRANSLATE-1112','Across XML parser has problems with single tags in the comment XML','Across XML parser has problems with single tags in the comment XML',12,'bugfix','2.8.7'),
(168,'2018-02-13','TRANSLATE-1110','Missing and wrong translated user roles in the notifyAllAssociatedUsers e-mail','Missing and wrong translated user roles in the notifyAllAssociatedUsers e-mail',12,'bugfix','2.8.7'),
(169,'2018-02-13','TRANSLATE-1117','In IE Edge in the HtmlEditor the cursor cannot be moved by mouse only by keyboard','In IE Edge in the HtmlEditor the cursor cannot be moved by mouse only by keyboard',14,'bugfix','2.8.7'),
(170,'2018-02-13','TRANSLATE-1141','TrackChanges: Del-tags are not ignored when the characters are counted in min/max length','With activated TrackChanges Plug-In del-tags are not ignored when the characters are counted in segment min/max length.',14,'bugfix','2.8.7'),
(171,'2018-02-15','TRANSLATE-1142','A task migration tracker was introduced for database migration scripts affecting all tasks','A task migration tracker was introduced for database migration scripts affecting all tasks',8,'change','2.8.7'),
(172,'2018-02-15','T5DEV-228','VisualReview: Für Segment Aliase wird nun ein Tooltip angezeigt','',12,'change','2.8.7'),
(173,'2018-02-15','TRANSLATE-1096','Changelog model produce unneeded error log','Changelog model produce unneeded error log, this is fixed now.',8,'bugfix','2.8.7'),
(174,'2018-03-12','TRANSLATE-1166','New task status \"unconfirmed\" and confirmation procedure','If enabled in configuration new tasks are created with status unconfirmed und must be confirmed by a PM or a proofreader before usable for proofreading.',14,'feature','2.8.7'),
(175,'2018-03-12','TRANSLATE-1070','Make initial values of checkboxes in task add window configurable','In the task creation wizard the initial values of some fields can be configured.',12,'feature','2.8.7'),
(176,'2018-03-12','TRANSLATE-949','delete old tasks by cron job (config sql file)','delete old (unused for a configurable time interval) tasks by cron job. Feature must be activated via Workflow Actions.',12,'feature','2.8.7'),
(177,'2018-03-12','TRANSLATE-1144','Disable translate5 update popup for non admin users','Now only admin users are getting such a notice.',12,'change','2.8.7'),
(178,'2018-03-12','PMs without loadAllTasks should be able to see their tasks, even without a task assoc.','PMs without loadAllTasks should be able to see their tasks, even without a task assoc.','PMs without loadAllTasks should be able to see their tasks, even without a task assoc.',12,'change','2.8.7'),
(179,'2018-03-12','TRANSLATE-1114','TrackChanges: fast replacing selected content triggers debugger statement','The debugger statement was removed. ',12,'change','2.8.7'),
(180,'2018-03-12','TRANSLATE-1145','Using TrackChanges and MatchResources was not working as expected','Using TrackChanges and MatchResources was not working as expected',14,'change','2.8.7'),
(181,'2018-03-12','TRANSLATE-1143','VisualReview: The text in the tooltips with ins-del tags is not readable in visualReview layout','The text in the tooltips with ins-del tags is not readable in visualReview layout',14,'change','2.8.7'),
(182,'2018-03-12','T5DEV-234 TrackChanges','TrackChanges: Fixing handling of translate5 internal keyboard shortcuts.','TrackChanges: Fixing handling of translate5 internal keyboard shortcuts.',12,'change','2.8.7'),
(183,'2018-03-12','TRANSLATE-1178','if there are only directories and not files in proofRead, this results in \"no importable files in the task\"','Now the proofRead folder can contain just folders.',12,'bugfix','2.8.7'),
(184,'2018-03-12','TRANSLATE-1078','VisualReview: Upload of single PDF file in task upload wizard does not work','Now the single upload works also for VisualReview.',12,'bugfix','2.8.7'),
(185,'2018-03-12','TRANSLATE-1164','VisualReview throws an exception with disabled headpanel','VisualReview throws an exception with disabled headpanel',14,'bugfix','2.8.7'),
(186,'2018-03-12','TRANSLATE-1155','Adding a translation check user to a proofreading task changes workflow step to translation','The workflow step of a task was erroneously changed on a adding at first a translation check user in a proofreading task. This is fixed right now.',12,'bugfix','2.8.7'),
(187,'2018-03-12','TRANSLATE-1153','Fixing Error: editor is not found','Fixing Error: editor is not found',14,'bugfix','2.8.7'),
(188,'2018-03-12','TRANSLATE-1148','Maximum characters allowed in toSort column is over the limit','Maximum characters allowed in toSort column is over the limit',8,'bugfix','2.8.7'),
(189,'2018-03-12','TRANSLATE-969','Calculation of next editable segment fails when sorting and filtering for a content column','Calculation of next editable segment fails when sorting and filtering for a content column',14,'bugfix','2.8.7'),
(190,'2018-03-12','TRANSLATE-1147','TrackChanges: Missing translations in application','Missing translations added',14,'bugfix','2.8.7'),
(191,'2018-03-12','TRANSLATE-1042','copy source to target is not working in firefox','copy source to target is not working in firefox',14,'bugfix','2.8.7'),
(192,'2018-03-15','T5DEV-236; visualReview','VisualReview: Matching-Algorithm improved','Added some more \"special space\" characters.',12,'change','2.8.7'),
(193,'2018-03-15','TRANSLATE-1183','Error in TaskActions.js using IE11','Fixing an urgent error in TaskActions.js using IE11',14,'bugfix','2.8.7'),
(194,'2018-03-15','TRANSLATE-1182','VisualReview - JS Error: Cannot read property \'getIframeBody\' of undefined','VisualReview - JS Error: Cannot read property \'getIframeBody\' of undefined',12,'bugfix','2.8.7'),
(195,'2018-03-15','T5DEV-213','Special Plugin for attaching Translate5 to across','This plugin sets the trans-units translate attribute in the XLF export depending on the autostate. If a segment was changed or a comment was added it is set to translatete=yes otherwise to no. Needed in special workflows together with across.',8,'feature','2.8.7'),
(196,'2018-03-15','TRANSLATE-1180','Improve logging and enduser communication in case of ZfExtended_NoAccessException exceptions','Improve logging and enduser communication in case of ZfExtended_NoAccessException exceptions',12,'change','2.8.7'),
(197,'2018-03-15','TRANSLATE-1179','HTTP HEAD and OPTIONS request should not create a log entry','HTTP HEAD and OPTIONS request should not create a log entry',8,'change','2.8.7'),
(198,'2018-04-11','TRANSLATE-1130','Show specific whitespace tag for tags protecting whitespace','Internal tags protecting whitespace are showing directly on the tag which type of content is protected by the tag',14,'feature','2.8.7'),
(199,'2018-04-11','TRANSLATE-1132','Tags which are protecting whitespace can be deleted in the editor','Tags which are protecting whitespace can be deleted in the editor - if enabled via config',14,'feature','2.8.7'),
(200,'2018-04-11','TRANSLATE-1127','XLIFF Import: Whitespace outside mrk tags is preserved','XLIFF Import: Whitespace outside mrk tags is preserved during import for export',12,'feature','2.8.7'),
(201,'2018-04-11','TRANSLATE-1137','Show bookmark and comment icons in autostatus column','Show bookmark and comment icons in autostatus column',14,'feature','2.8.7'),
(202,'2018-04-11','TRANSLATE-1058','Send changelog via email to admin users when updating with install-and-update script','Send changelog via email to admin users when updating with install-and-update script',8,'feature','2.8.7'),
(203,'2018-04-11','T5DEV-217','remaining search and replace todos','remaining todos in the search and replace plugin',14,'change','2.8.7'),
(204,'2018-04-11','TRANSLATE-1200','Refactor images of internal tags to SVG content instead PNG','Refactor images of internal tags to SVG content instead PNG',12,'change','2.8.7'),
(205,'2018-04-11','TRANSLATE-1209','TrackChanges: content tags in DEL INS tags are not displayed correctly in full tag mode','TrackChanges: content tags in DEL INS tags are not displayed correctly in full tag mode',14,'bugfix','2.8.7'),
(206,'2018-04-11','TRANSLATE-1212','TrackChanges: deleted content tags in a DEL tag can not readded via CTRL+, + Number','The keyboard shortcut CTRL+, + number was not working in conjunction with TrackChanges.',14,'bugfix','2.8.7'),
(207,'2018-04-11','TRANSLATE-1210','TrackChanges: Using repetition editor on segments where a content tag is in a DEL and INS tag throws an exception','TrackChanges: Using repetition editor on segments where a content tag is in a DEL and INS tag throws an exception',14,'bugfix','2.8.7'),
(208,'2018-04-11','TRANSLATE-1194','TrackChanges: remove unnecessary whitespace remaining at the place of deleted content','When the export removes deleted words, no double spaces must be left. - replace deleted content that has a (= one) whitespace at both ends with one single whitespace first',12,'bugfix','2.8.7'),
(209,'2018-04-11','TRANSLATE-1124','store whitespace tag metrics into internal tag','The whitespace tag metrics are stored into the internal tag, so that the min max length calculation is working correctly.',14,'bugfix','2.8.7'),
(210,'2018-04-11','VISUAL-24','visualReview: After adding a comment, a strange white window appears','After adding a comment, a strange white window appears, this is fixed right now.',14,'bugfix','2.8.7'),
(211,'2018-04-16','TRANSLATE-1218','XLIFF Import: preserveWhitespace per default to true','The new default value for the preserveWhitespace config for XLIFF Imports should be set to true. Currently its false, so that all whitespace is ignored in the XLIFF Import. This should be changed.',12,'change','2.8.7'),
(212,'2018-04-16','Renamed all editor modes','Renamed all editor modes','Renamed all editor modes',14,'change','2.8.7'),
(213,'2018-04-16','TRANSLATE-1154','XLIFF import does not set match rate','XLIFF Import is now importing the matchrate (match-quality) of the alt-trans tags containing the used TM references.',12,'bugfix','2.8.7'),
(214,'2018-04-16','TRANSLATE-1215','TrackChanges: JS Exception on CTRL+. usage','Fixed some error when using CTRL + . with TrackChanges.',14,'bugfix','2.8.7'),
(215,'2018-04-16','TRANSLATE-1140','Search and Replace: Row editor is not displayed after the first match in certain situations','Search and Replace: Row editor is not displayed after the first match in certain situations',14,'bugfix','2.8.7'),
(216,'2018-04-16','TRANSLATE-1219','MatchResource Plug-in: Editor iframe body is reset and therefore not usable due missing content','With activated MatchResource Plug-in the editor iframe body was reset and therefore the segment editor was not usable.',12,'bugfix','2.8.7'),
(217,'2018-04-16','VISUAL-28','Opening of visual task in IE 11 throws JS error','Opening of visual task in IE 11 throws JS error',14,'bugfix','2.8.7'),
(218,'2018-05-07','TRANSLATE-1136','Check for content outside of mrk-tags (xliff)','Import fails if there is other content as whitespace or tags outside of mrk mtype seg texts.',12,'feature','2.8.7'),
(219,'2018-05-07','TRANSLATE-1192','Length restriction: Add length of several segments','Length calculation is now done over multiple segments (mrks) of one trans-unit',14,'feature','2.8.7'),
(220,'2018-05-07','TRANSLATE-1130','Show specific whitespace-tag instead just internal tag symbol','Internal tags masking whitespace are now displaying which and how many characters are masked',14,'feature','2.8.7'),
(221,'2018-05-07','TRANSLATE-1190','Plugin: Automatic import of TBX files from Across','Plugin: Automatic import of TBX files from Across',8,'feature','2.8.7'),
(222,'2018-05-07','TRANSLATE-1189','Flexible Term and TermEntry Attributes','Term Attributes are now also imported and stored in the internal term DB',12,'feature','2.8.7'),
(223,'2018-05-07','TRANSLATE-1187','Introduce TermCollections to share terminology between different tasks','Introduce TermCollections to share terminology between different tasks',12,'feature','2.8.7'),
(224,'2018-05-07','TRANSLATE-1188','Extending the TBX-import','Extending the TBX-import so that terms could be added / updated in existing term collections.',12,'feature','2.8.7'),
(225,'2018-05-07','TRANSLATE-1186','new system role \"termCustomerSearch\"','new system role termCustomerSearch for new term search portal',12,'feature','2.8.7'),
(226,'2018-05-07','TRANSLATE-1184','Client management','Client management, deactivated by default',12,'feature','2.8.7'),
(227,'2018-05-07','TRANSLATE-1185','Add field \"end client\" to user management','Add field \"end client\" to user management',12,'feature','2.8.7'),
(228,'2018-05-07','VISUAL-30','The connection algorithm connects segments only partially','The connection algorithm connects segments only partially',12,'change','2.8.7'),
(229,'2018-05-07','TRANSLATE-1229','xliff 1.2 export deletes tags','On xliff 1.2 export some tags are lost on export, if the tag was the only content in a mrk tag.',12,'bugfix','2.8.7'),
(230,'2018-05-07','TRANSLATE-1236','User creation via API should accept a given userGuid','On creating users via API always a UserGuid was generated, and no guid could be given.',8,'bugfix','2.8.7'),
(231,'2018-05-07','TRANSLATE-1235','User creation via API produces errors on POST/PUT with invalid content','User creation via API produces errors on POST/PUT with given invalid content',8,'bugfix','2.8.7'),
(232,'2018-05-07','TRANSLATE-1128','Selecting segment and scrolling leads to jumping of grid ','Selecting segment and scrolling leads to jumping of grid, this is fixed now.',14,'bugfix','2.8.7'),
(233,'2018-05-07','TRANSLATE-1233','Keyboard Navigation through grid looses focus','Keyboard Navigation through grid looses focus',14,'bugfix','2.8.7'),
(234,'2018-05-08','TRANSLATE-1240','Integrate external libs correctly in installer','Integrate external libs correctly in installer',8,'change','2.8.7'),
(235,'2018-05-08','requests producing a 404 were causing a logout instead of showing 404','requests producing a 404 were causing a logout instead of showing 404','requests producing a 404 were causing a logout instead of showing 404',12,'bugfix','2.8.7'),
(236,'2018-05-09','TRANSLATE-1243','IE 11: Could not start the application due to an error in Segment.js','IE 11: Could not start the application due to an error in Segment.js',14,'bugfix','2.8.7'),
(237,'2018-05-09','TRANSLATE-1239','JS: Uncaught TypeError: Cannot read property \'length\' of undefined','This error happens only with very small amount of segments in the task, and if the users presses then segment grid reload.',8,'bugfix','2.8.7'),
(238,'2018-05-24','TRANSLATE-1135','Segment Grid: Highlight and Copy text in source and target columns','Segment Grid: Text in the segment grid cells can now be highlighted and copied from the source and target columns.',14,'feature','2.8.7'),
(239,'2018-05-24','TRANSLATE-1267','TrackChanges: on export more content as only the content between DEL tags is getting deleted on some circumstances','TrackChanges: on export more content as only the content between DEL tags is getting deleted on some circumstances',12,'bugfix','2.8.7'),
(240,'2018-05-24','VISUAL-33','VisualReview: Very huge VisualReview projects could not be imported','Very huge VisualReview projects lead to preg errors in PHP postprocessing of generated HTML, and could not be imported therefore.',12,'bugfix','2.8.7'),
(241,'2018-05-24','TRANSLATE-1102','The user was logged out randomly in very seldom circumstances','It could happen (mainly with IE) that a user was logged out by loosing his session. This was happening when a default module page (like 404) was called in between other AJAX requests.',8,'bugfix','2.8.7'),
(242,'2018-05-24','TRANSLATE-1226','fixed a Zend_Exception with message Array to string conversion','fixed a Zend_Exception with message Array to string conversion',8,'bugfix','2.8.7'),
(243,'2018-05-30','TRANSLATE-1269','Enable deletion of older terms in termportal','Enable deletion of older terms in termportal',8,'change','2.8.7'),
(244,'2018-05-30','TINTERNAL-28','Change TBX Collection directory naming scheme','The TBX Collection directory naming scheme was changed',8,'change','2.8.7'),
(245,'2018-05-30','TRANSLATE-1268','Pre-select language of term search with GUI-language','Pre-select language of term search with GUI-language',12,'change','2.8.7'),
(246,'2018-05-30','TRANSLATE-1266','Show \"-\" as value instead of provisionallyProcessed','Show \"-\" as value instead of provisionallyProcessed',12,'change','2.8.7'),
(247,'2018-05-30','TRANSLATE-1231','xliff 1.2 import can not handle different number of mrk-tags in source and target','In Across xliff it can happen that mrk tags in source and target have a different structure. Such tasks can now imported into translate5.',12,'bugfix','2.8.7'),
(248,'2018-05-30','TRANSLATE-1265','Deletion of task does not delete dependent termCollection','Deletion of task does not delete dependent termCollection',8,'bugfix','2.8.7'),
(249,'2018-05-30','TRANSLATE-1283','TermPortal: Add GUI translations for Term collection attributes','Add GUI translations for Term collection attributes',8,'bugfix','2.8.7'),
(250,'2018-05-30','TRANSLATE-1284','TermPortal: term searches are not restricted to a specific term collection','TermPortal: term searches are not restricted to a specific term collection',8,'bugfix','2.8.7'),
(251,'2018-06-27','TRANSLATE-1324','RepetitionEditor: repetitions could not be saved due JS error','RepetitionEditor: repetitions could not be saved due JS error',14,'bugfix','2.8.7'),
(252,'2018-06-27','TRANSLATE-1269','TermPortal: Enable deletion of older terms','Enable deletion of older terms in the TermPortal via config',8,'feature','2.8.7'),
(253,'2018-06-27','TRANSLATE-858','SpellCheck: Integrate languagetool grammer, style and spell checker as micro service','Integrate languagetool grammer, style and spell checker as micro service. Languagetool must be setup as separate server.',14,'feature','2.8.7'),
(254,'2018-06-27','VISUAL-44','VisualReview: Make \"switch editor mode\"-button configureable in visualReview','The VisualReview \"switch editor mode\"-button can be disabled via configuration',12,'feature','2.8.7'),
(255,'2018-06-27','TRANSLATE-1310','Improve import performance by SQL optimizing in metacache update','Improve import performance by SQL optimizing in metacache update',12,'change','2.8.7'),
(256,'2018-06-27','TRANSLATE-1317','A check for application:/data/tbx-import folder was missing','A check for application:/data/tbx-import folder was missing',8,'change','2.8.7'),
(257,'2018-06-27','TRANSLATE-1304','remove own js log call for one specific segment editing error in favour of rootcause','remove own js log call for one specific segment editing error in favour of rootcause',8,'change','2.8.7'),
(258,'2018-06-27','TRANSLATE-1287','TermPortal: Introduce scrollbar in left result column of termPortal','TermPortal: Introduce scrollbar in left result column of termPortal',12,'change','2.8.7'),
(259,'2018-06-27','TRANSLATE-1296','Simplify error message on missing tags on saving a segment','Simplify error message on missing tags on saving a segment',14,'change','2.8.7'),
(260,'2018-06-27','TRANSLATE-1295','Remove sorting by click on column header in editor','Remove sorting by click on column header in editor',14,'change','2.8.7'),
(261,'2018-06-27','TRANSLATE-1311','segmentMeta transunitId was set to null or was calculated wrong for string ids','For non XLIFF imports and XLIFF Imports where the trans-unit id was not an integer the final transunitId stored in the DB was calculated wrong. This leads to a wrong meta cache for sibling data which then results in corrupt data crashing the frontend.',8,'bugfix','2.8.7'),
(262,'2018-06-27','TRANSLATE-1313','No error handling if tasks languages are not present in TBX','No error handling if tasks languages are not present in TBX',8,'bugfix','2.8.7'),
(263,'2018-06-27','TRANSLATE-1315','SpellCheck & TrackChanges: corrected errors still marked','SpellCheck & TrackChanges: corrected errors still marked',14,'bugfix','2.8.7'),
(264,'2018-06-27','T5DEV-245','VisualReview: Error on opening a segment','VisualReview: Error on opening a segment',14,'bugfix','2.8.7'),
(265,'2018-06-27','TRANSLATE-1283','TermPortal: Use internal translation system for term attribute labels','TermPortal: Use internal translation system for term attribute labels',8,'bugfix','2.8.7'),
(266,'2018-06-27','TRANSLATE-1318','TermPortal: Pre-select search language with matching GUI language group','TermPortal: Pre-select search language with matching GUI language group',12,'bugfix','2.8.7'),
(267,'2018-06-27','TRANSLATE-1294','TermPortal: Undefined variable: translate in termportal','TermPortal: Undefined variable: translate in termportal',8,'bugfix','2.8.7'),
(268,'2018-06-27','TRANSLATE-1292','TermPortal: Undefined variable: file in okapi worker','TermPortal: Undefined variable: file in okapi worker',8,'bugfix','2.8.7'),
(269,'2018-06-27','TRANSLATE-1286','TermPortal: Number shows up, when selecting term from the live search','TermPortal: Number shows up, when selecting term from the live search',8,'bugfix','2.8.7'),
(270,'2018-07-03','VISUAL-43','VisualReview: Improve performance by splitting segments search into long, middle, short','VisualReview: Improve performance by splitting segments search into long, middle, short',12,'change','2.8.7'),
(271,'2018-07-03','TRANSLATE-1323','SpellCheck must not remove the TermTag-Markup','TermTag-Markup was removed by using the SpellChecker, this should not be.',14,'change','2.8.7'),
(272,'2018-07-03','TRANSLATE-1234','changes.xliff diff algorithm fails under some circumstances','changes.xliff diff algorithm fails under some circumstances',12,'bugfix','2.8.7'),
(273,'2018-07-03','TRANSLATE-1306','SpellCheck: blocked after typing with MatchResources','SpellCheck: blocked after typing with MatchResources',14,'bugfix','2.8.7'),
(274,'2018-07-04','TRANSLATE-1331','add application version to task table','To each task the application version on import time is stored, for debugging and migration.',8,'change','2.8.7'),
(275,'2018-07-04','TRANSLATE-1336','TermPortal: Fix unsupported functions by IE11 in termportal','TermPortal: Fix unsupported functions by IE11 in termportal',14,'bugfix','2.8.7'),
(276,'2018-07-17','TRANSLATE-1349','Remove the message of saving a segment successfully','Remove the message of saving a segment successfully',14,'change','2.8.7'),
(277,'2018-07-17','TRANSLATE-1337','removing orphaned tags is not working with tag check save anyway','removing orphaned tags is not working with tag check save anyway',14,'bugfix','2.8.7'),
(278,'2018-07-17','TRANSLATE-1245','Add missing keyboard shortcuts and other smaller fixes related to segment commenting','Add missing keyboard shortcuts and other smaller fixes related to segment commenting',14,'bugfix','2.8.7'),
(279,'2018-07-17','TRANSLATE-1326','VisualReview: Enable Comments for non-editable segment in visualReview mode and normal mode','Via ACL it can be enabled that non-editable segment can be commented in normal and in visualReview mode',14,'bugfix','2.8.7'),
(280,'2018-07-17','TRANSLATE-1345','Unable to import task with Relais language and terminology','Unable to import task with Relais language and terminology',12,'bugfix','2.8.7'),
(281,'2018-07-17','TRANSLATE-1347','Unknown Term status are not set to the default as configured','Unknown Term stats are now set again to the configured default value    ',12,'bugfix','2.8.7'),
(282,'2018-07-17','TRANSLATE-1351','Remove jquery from official release and bundle it as dependency','Remove jquery from official release and bundle it as dependency',8,'bugfix','2.8.7'),
(283,'2018-07-17','TRANSLATE-1353','Huge TBX files can not be imported','Huge TBX files can not be imported',12,'bugfix','2.8.7'),
(284,'2018-08-08','TRANSLATE-1352','Include PM changes in changes-mail and changes.xliff (xliff 2.1)','It can be configured if segment changes of PMs should be listed in the changes email and changes xliff file. By default its active.',14,'feature','2.8.7'),
(285,'2018-08-08','TRANSLATE-884','Implement generic match analysis and pre-translation (on the example of OpenTM2)','Implement generic match analysis and pre-translation on the example of OpenTM2',14,'feature','2.8.7'),
(286,'2018-08-08','TRANSLATE-392','Systemwide (non-persistent) memory','Implemented a memory cache for internal purposes',8,'feature','2.8.7'),
(287,'2018-08-08','VISUAL-31','VisualReview: improve segmentation','VisualReview: improve segmentation in performance and matchrate',8,'change','2.8.7'),
(288,'2018-08-08','TRANSLATE-1360','Make PM dropdown in task properties searchable','The dropdown to change the PM of a task in task properties is now searchable',12,'change','2.8.7'),
(289,'2018-08-08','TRANSLATE-1383','Additional workflow roles associated to a task prohibit a correct workflow switching','Some client specific workflows provides additional roles to be associated to a task. This additional roles prohibit a correct workflow step switching.',12,'bugfix','2.8.7'),
(290,'2018-08-08','TRANSLATE-1161','Task locking clean up is only done on listing the task overview','Some internal garbage collection was unified: locked tasks, old sessions are cleaned in a general way. On heavy traffic instances this can be changed to cronjob based garbage collection now.',8,'bugfix','2.8.7'),
(291,'2018-08-08','TRANSLATE-1067','API Usage: \'Zend_Exception\' with message \'Indirect modification of overloaded property Zend_View::$rows has no effect','On API usage and providing invalid roles on task user association creation this error was triggered.',8,'bugfix','2.8.7'),
(292,'2018-08-08','TRANSLATE-1385','PreFillSession Resource Plugin must be removed','PreFillSession Resource Plugin must be removed',8,'bugfix','2.8.7'),
(293,'2018-08-08','TRANSLATE-1340','IP based SessionRestriction is to restrictive for users having multiple IPs','Working areas with changing IPs was not possible due the SessionRestriction.',8,'bugfix','2.8.7'),
(294,'2018-08-08','TRANSLATE-1359','PM to task association dropdown in task properties list does not contain all PMs','PM to task association dropdown in task properties list does not contain all PMs',12,'bugfix','2.8.7'),
(295,'2018-08-14','TRANSLATE-1404','TrackChanges: Tracked changes disappear when using backspace/del','Inserted tracked changes disappear when using backspace/del on other places in the segment',14,'bugfix','2.8.7'),
(296,'2018-08-14','TRANSLATE-1376','Segment length calculation does not include length of content outside of mrk tags','The segment length calculation contains now also the content outside of MRK tags, mostly whitespace.',14,'bugfix','2.8.7'),
(297,'2018-08-14','TRANSLATE-1399','Using backspace on empty segment increases segment length','Using the backspace or delete key on empty segment increases segment length instead doing nothing.',14,'bugfix','2.8.7'),
(298,'2018-08-14','TRANSLATE-1395','Enhance error message on missing relais folder','Enhance error message on missing relais folder',8,'bugfix','2.8.7'),
(299,'2018-08-14','TRANSLATE-1379','TrackChanges: disrupt conversion into japanese characters','TrackChanges was not working properly with japanase characters',14,'bugfix','2.8.7'),
(300,'2018-08-14','TRANSLATE-1373','TermPortal: TermCollection import stops because of unsaved term','TermCollection import stops because of unsaved term',8,'bugfix','2.8.7'),
(301,'2018-08-14','TRANSLATE-1372','TrackChanges: Multiple empty spaces after export','TrackChanges: Multiple empty spaces after export',12,'bugfix','2.8.7'),
(302,'2018-08-17','TRANSLATE-1375','Map arbitrary term attributes to administrativeStatus','To translate5 unknown term status can now be mapped to known status values. See jira issue for details to the configuration.',12,'feature','2.8.7'),
(303,'2018-08-17','VISUAL-46','VisualReview: Loading screen until everything loaded','A loading screen locks the whole application until all visual review data is loaded.',14,'bugfix','2.8.7'),
(304,'2018-08-27','VISUAL-50','VisualReview: Improve initial loading performance by accessing images directly and not via PHP proxy','For security reasons all VisualReview content is piped through a PHP proxy for authentication. For huge VisualReview data this can be slow. By setting runtimeOptions.plugins.VisualReview.directPublicAccess = 1 in the config an alternative way by using symlinks is enabled.',12,'feature','2.8.7'),
(305,'2018-08-27','VISUAL-49','VisualReview: Extend default editor mode to support visualReview','The initial view mode in VisualReview can now be configured, this is either simple or default. Config: runtimeOptions.plugins.VisualReview.startViewMode.',8,'change','2.8.7'),
(306,'2018-08-27','TRANSLATE-1415','Rename startViewMode values in config','Rename startViewMode values in config',8,'change','2.8.7'),
(307,'2018-08-27','TRANSLATE-1416','exception \'PDOException\' with message \'SQLSTATE[42S01]: Base table or view already exists: 1050 Table \'siblings\' already exists\'','The exception can happen on cron triggered workflow actions.',8,'bugfix','2.8.7'),
(308,'2018-08-27','VISUAL-48','VisualReview: Improve visualReview scroll performance on very large VisualReview Projects','VisualReview: Improve visualReview scroll performance on very large VisualReview Projects',14,'bugfix','2.8.7'),
(309,'2018-08-27','TRANSLATE-1413','TermPortal: Import deletes all old Terms, regardless of the originating TermCollection','TermPortal: Import deletes all old Terms, regardless of the originating TermCollection',12,'bugfix','2.8.7'),
(310,'2018-08-27','TRANSLATE-1392','Unlock task on logout ','This change is needed, since garbage collector is triggered only periodically instead on each task overview request.',12,'bugfix','2.8.7'),
(311,'2018-08-28','TRANSLATE-1417','Task Import ignored termEntry IDs and produced there fore task mismatch','Task Import ignored termEntry IDs and produced there fore task mismatch',12,'bugfix','2.8.7'),
(312,'2018-09-13','TRANSLATE-1425','Provide ImportArchiv.zip as download from the export menu for admin users','Provide ImportArchiv.zip as download from the export menu for admin users',8,'feature','2.8.7'),
(313,'2018-09-13','TRANSLATE-1426','Segment length calculation was not working due not updated metaCache','The segment length calculation was not working due to a not updated metaCache',12,'bugfix','2.8.7'),
(314,'2018-09-13','TRANSLATE-1370','Xliff Import can not deal with empty source targets as single tags','Xliff Import can not deal with empty source targets as single tags',12,'bugfix','2.8.7'),
(315,'2018-09-13','TRANSLATE-1427','Date calculation in Notification Mails is wrong','The date calculation in Notification Mails was wrong',14,'bugfix','2.8.7'),
(316,'2018-09-13','TRANSLATE-1177','Clicking into empty area of file tree produces sometimes an JS error','Clicking into empty area of file tree produces sometimes an JS error',14,'bugfix','2.8.7'),
(317,'2018-09-13','TRANSLATE-1422','Uncaught TypeError: Cannot read property \'record\' of undefined','The following error in the frontend was fixed: Uncaught TypeError: Cannot read property \'record\' of undefined',14,'bugfix','2.8.7'),
(318,'2018-10-16','TRANSLATE-1433','Trigger workflow actions also on removing a user from a task','Trigger workflow actions also on removing a user from a task',12,'feature','2.8.7'),
(319,'2018-10-16','VISUAL-26','VisualReview: Add buttons to resize layout','Added some buttons to zoom into the visualized content in the upper visual review frame.',14,'feature','2.8.7'),
(320,'2018-10-16','TRANSLATE-1207','Add buttons to resize/zoom segment table','Add buttons to resize/zoom segment table',14,'feature','2.8.7'),
(321,'2018-10-16','TRANSLATE-1135','Highlight and Copy text in source and target columns','Highlight and Copy text in source and target columns',14,'feature','2.8.7'),
(322,'2018-10-16','TRANSLATE-1380','Change skeleton-files location from DB to filesystem','Change the location of the skeleton-files needed for the export from DB to filesystem',8,'change','2.8.7'),
(323,'2018-10-16','TRANSLATE-1381','Print proper error message if SDLXLIFF with comments is imported','Print proper error message if SDLXLIFF with comments is imported',8,'change','2.8.7'),
(324,'2018-10-16','TRANSLATE-1437','Collect relais file alignment errors instead mail and log each error separately','Collect relais file alignment errors instead mail and log each error separately',8,'change','2.8.7'),
(325,'2018-10-16','TRANSLATE-1396','Remove the misleading \"C:fakepath\" from task name','Remove the misleading \"C:fakepath\" from task name',12,'change','2.8.7'),
(326,'2018-10-16','TRANSLATE-1442','Repetition editor replaces wrong tag if segment contains tags only','Repetition editor uses the wrong tag if the target of the segment to be replaced is empty and if the segment contains tags only. ',14,'bugfix','2.8.7'),
(327,'2018-10-16','TRANSLATE-1441','Exception about missing segment materialized view on XLIFF2 export','Exception about missing segment materialized view on XLIFF2 export',12,'bugfix','2.8.7'),
(328,'2018-10-16','TRANSLATE-1382','Deleting PM users associated to tasks can lead to workflow errors','Deleting PM users associated to tasks can lead to workflow errors',12,'bugfix','2.8.7'),
(329,'2018-10-16','TRANSLATE-1335','Wrong segment sorting and filtering because of internal tags','Wrong segment sorting and filtering because of internal tags',14,'bugfix','2.8.7'),
(330,'2018-10-16','TRANSLATE-1129','Missing segments on scrolling with page-down / page-up','Using page-up and page-down keys in the segment grid for scrolling was jumping over some segments so not all segments were visible to the user',14,'bugfix','2.8.7'),
(331,'2018-10-16','TRANSLATE-1431','Deleting a comment can lead to a JS exception','Deleting a comment can lead to a JS exception',14,'bugfix','2.8.7'),
(332,'2018-10-16','VISUAL-55','VisualReview: Replace special Whitespace-Chars','Replace special Whitespace-Chars to get more matches',12,'bugfix','2.8.7'),
(333,'2018-10-16','TRANSLATE-1438','Okapi conversion did not work anymore due to Okapi Longhorn bug','Due to an Okapi Longhorn bug the conversion of native source files to xliff did not work any more with translate5. A workaround was implemented.',12,'bugfix','2.8.7'),
(334,'2018-10-25','TRANSLATE-1339','InstantTranslate-Portal: integration of SDL Language Cloud, Terminology, TM and MT resources and similar','With the InstantTranslate-Portal several language resource can be integrated in translate5. This are SDL Language Cloud, Terminology, TM and MT resources and similar. With this feature the Plugin MatchResource was renamed to LanguageResource and moved into the core code. Also Terminology Collections are now available and maintainable via the LanguageResource Panel.',14,'feature','2.9.1'),
(335,'2018-10-25','TRANSLATE-1362','Integrate Google Translate as language resource','Integrate Google Translate as language resource',12,'feature','2.9.1'),
(336,'2018-10-25','TRANSLATE-1162','GroupShare Plugin: Use SDL Trados GroupShare as Language-Resource','GroupShare Plugin: Use SDL Trados GroupShare as Language-Resource',12,'feature','2.9.1'),
(337,'2018-10-25','VISUAL-56','VisualReview: Change text that is shown, when segment is not connected','VisualReview: Change text that is shown, when segment is not connected',12,'change','2.9.1'),
(338,'2018-10-25','TRANSLATE-1447','Escaping XML Entities in XLIFF 2.1 export (like attribute its:person)','Escaping XML Entities in XLIFF 2.1 export (attributes like its:person were unescaped)',12,'bugfix','2.9.1'),
(339,'2018-10-25','TRANSLATE-1448','translate5 stops loading with Internet Explorer 11','translate5 stops loading with Internet Explorer 11',14,'bugfix','2.9.1'),
(340,'2018-10-30','Fixing Problems in IE11','Fixing Problems in IE11','Fixing Problems in IE11, Screens remain on loading',14,'bugfix','5.0.7'),
(341,'2018-10-30','TRANSLATE-1451','Missing customer frontend right blocks whole language resources','Missing customer frontend right blocks whole language resources',12,'bugfix','5.0.7'),
(342,'2018-10-30','TRANSLATE-1453','Updating from an older version of translate5 led to errors in updating','Fixing problems in Update process when updating from older versions',8,'bugfix','5.0.7'),
(343,'2018-12-20','TRANSLATE-1490','Highlight fuzzy range in source of match in translate5 editor','Highlight fuzzy range in source of match in translate5 editor for all TM resources.',14,'feature','5.0.7'),
(344,'2018-12-20','TRANSLATE-1430','Enable copy and paste of internal tags from source to target','Enable copy and paste of internal tags from source to target',6,'feature','5.0.7'),
(345,'2018-12-20','TRANSLATE-1397','Multitenancy phase 1','First steps for multi-tenancy, by setting grid filters automatically.',12,'feature','5.0.7'),
(346,'2018-12-20','TRANSLATE-1206','Add Whitespace chars to segment','Add additional whitespace, tab characters and non breaking space can be added to the segment - protected as internal tag.',6,'feature','5.0.7'),
(347,'2018-12-20','TRANSLATE-1483','PHP Backend: Implement an easy way to join tables for filtering via API','PHP Backend: Implement an easy way to join tables for filtering via API',8,'change','5.0.7'),
(348,'2018-12-20','TRANSLATE-1460','Deactivate export menu in taskoverview for editor users','The export menu in the taskoverview was deactivated for editor users and is now only visible for PM and admin users.',12,'change','5.0.7'),
(349,'2018-12-20','TRANSLATE-1500','PM dropdown field in task properties shows max 25 users','The dropdown field to change the PM in the task properties shows max 25 PMs',12,'bugfix','5.0.7'),
(350,'2018-12-20','TRANSLATE-1497','Convert JSON.parse calls to Ext.JSON.decode calls for better debugging','Due the wrong JSON decode call we got less error information on receiving invalid JSON.',8,'bugfix','5.0.7'),
(351,'2018-12-20','TRANSLATE-1491','Combine multiple OpenTM2 100% matches to one match','Group multiple identical OpenTM2 100% matches with different >100% match rates to one match with the best match rate.',14,'bugfix','5.0.7'),
(352,'2018-12-20','TRANSLATE-1488','JS Error \"Cannot read property \'row\' of null\" on using bookmark functionality','The error in the frontend is solved.',14,'bugfix','5.0.7'),
(353,'2018-12-20','TRANSLATE-1487','User can not change his own password','Fixed that user can not change his own password in the GUI.',14,'bugfix','5.0.7'),
(354,'2018-12-20','TRANSLATE-1477','Error on removing a user from a task which finished then the task','In some circumstances an PHP error happened on removing a user from a task.',12,'bugfix','5.0.7'),
(355,'2018-12-20','TRANSLATE-1476','TrackChanges: JS Error when replacing a character in certain cases','TrackChanges: JS Error when replacing a character in certain cases',6,'bugfix','5.0.7'),
(356,'2018-12-20','TRANSLATE-1475','Merging of term tagger result and track changes content leads to several errors','Merging of term tagger result and track changes content leads to several errors in the content. Missing or invalid content for example.',14,'bugfix','5.0.7'),
(357,'2018-12-20','TRANSLATE-1474','Clicking in Treepanel while segments are loading is creating an error','Clicking in Treepanel while segments are loading is creating an error',6,'bugfix','5.0.7'),
(358,'2018-12-20','TRANSLATE-1472','Task delete throws DB foreign key constraint error','Task delete throws DB foreign key constraint error',12,'bugfix','5.0.7'),
(359,'2018-12-20','TRANSLATE-1470','Do not automatically add anymore missing tags on overtaking results from language resources','Do not automatically add anymore missing tags on overtaking results from language resources',6,'bugfix','5.0.7'),
(360,'2018-12-20','TRANSLATE-146','Internal translation mechanism creates corrupt XLIFF','The internal translation mechanism of the application was creating corrupt XLIFF which then blocked application loading.',8,'bugfix','5.0.7'),
(361,'2018-12-20','TRANSLATE-1465','InstantTranslate: increased input-field must not be covered by other elements','InstantTranslate: increased input-field must not be covered by other elements',6,'bugfix','5.0.7'),
(362,'2018-12-20','TRANSLATE-1463','Trigger workflow action not in all remove user cases','Trigger workflow action not in all remove user cases',6,'bugfix','5.0.7'),
(363,'2018-12-20','TRANSLATE-1449','Spellcheck needs to handle whitespace tags as space / word boundary','Spellcheck needs to handle whitespace tags as space / word boundary',12,'bugfix','5.0.7'),
(364,'2018-12-20','TRANSLATE-1440','Short tag view does not accurately reflect tag order and relationship between tags','Short tag view does not accurately reflect tag order and relationship between tags',14,'bugfix','5.0.7'),
(365,'2018-12-20','TRANSLATE-1505','Several smaller issues','Fix defaultuser usage in session api test / Fix minor issue in TBX parsing / Fix tag number calculation / Fix typo in translation / Fix wrong file name for TM download / Fixing DB alter SQL issues with MatchAnalysis Plugin / Show logo on bootstrap JS load / detect and select compound languages from uploaded filename / fix misleading variable name / fix typo in code comment / fixing task delete on test / integrate OpenTM2 check in build and deploy / make PM user available in mail templates',12,'bugfix','5.0.7'),
(366,'2018-12-20','TRANSLATE-1429','TrackChanges: Unable to get property \'className\' of undefined or null reference','TrackChanges: Fixed Js error: Unable to get property \'className\' of undefined or null reference',6,'bugfix','5.0.7'),
(367,'2018-12-20','TRANSLATE-1398','TrackChanges: Backspace and DEL are removing whole content instead only single characters','TrackChanges: Backspace and DEL are removing whole content instead only single characters',6,'bugfix','5.0.7'),
(368,'2018-12-20','TRANSLATE-1333','Search and Replace: JS Error: Die Eigenschaft \"getActiveTab\" eines undefinierten oder Nullverweises kann nicht abgerufen werden','Search and Replace: JS Error: Die Eigenschaft \"getActiveTab\" eines undefinierten oder Nullverweises kann nicht abgerufen werden',6,'bugfix','5.0.7'),
(369,'2018-12-20','TRANSLATE-1332','Search and Replace - JS error: record is undefined','Search and Replace - JS error: record is undefined',6,'bugfix','5.0.7'),
(370,'2018-12-20','TRANSLATE-1300','TrackChanges: Position of the caret after deleting from CTRL+A','TrackChanges: Position of the caret after deleting from CTRL+A',6,'bugfix','5.0.7'),
(371,'2018-12-20','TRANSLATE-1020','Tasknames with HTML entities are producing errors in segmentstatistics plugin','Tasknames with HTML entities are producing errors in segmentstatistics plugin',12,'bugfix','5.0.7'),
(372,'2018-12-20','T5DEV-251','Several issues in InstantTranslate','Several issues in InstantTranslate',14,'bugfix','5.0.7'),
(373,'2018-12-20','T5DEV-253','Several issues in match analysis and pre-translation','Several issues in match analysis and pre-translation',12,'bugfix','5.0.7'),
(374,'2018-12-20','TRANSLATE-1499','Task Name filtering does not work anymore after leaving a task','Task Name filtering does not work anymore after opening and then leaving a task',12,'bugfix','5.0.7'),
(375,'2018-12-21','TRANSLATE-1412','TermPortal logout URL is wrong','',6,'bugfix','5.0.7'),
(376,'2018-12-21','TRANSLATE-1504','TermTagging does not work with certain terms','TermTagging does not work with certain terms',12,'bugfix','5.0.7'),
(377,'2018-12-21','Fixing several smaller problems','Fixing several smaller problems','Missing configuration of some language resources was blocking application, rendering issue in task grid for non PM users, fix some IE11 JS errors.',12,'bugfix','5.0.7'),
(378,'2019-01-21','TRANSLATE-1523','Configurable: Should source files be auto-attached as reference files?','Now it is configurable if non bilingual source files are auto-attached as reference files.',12,'feature','5.0.7'),
(379,'2019-01-21','TRANSLATE-1543','InstantTranslate: Only show main languages in InstantTranslate language selection','In InstantTranslate only the main languages are shown in the language drop-downs. The sub-languages can be enabled via config.',14,'change','5.0.7'),
(380,'2019-01-21','TRANSLATE-1533','Switch API value, that is checked to know, if Globalese engine is available','Just implemented a Globalese API change.',8,'change','5.0.7'),
(381,'2019-01-21','TRANSLATE-1540','Filtering language resources by customer replaces resource name with customer name','When filtering the language resources by customer, the resource name was replaced with the customer name.',12,'bugfix','5.0.7'),
(382,'2019-01-21','TRANSLATE-1541','For title tag of TermPortal and InstantTranslate translation mechanism is not used','The application title of TermPortal and InstantTranslate was not properly translated',12,'bugfix','5.0.7'),
(383,'2019-01-21','TRANSLATE-1537','GroupShare sync throws an exception if a language can no be found locally','Now the synchronisation proceeds and the missing languages are logged.',12,'bugfix','5.0.7'),
(384,'2019-01-21','TRANSLATE-1535','GroupShare license cache ID may not contain special characters','Each user using GroupShare locks a GroupShare license. The locking process could not deal with E-Mails as usernames.',12,'bugfix','5.0.7'),
(385,'2019-01-21','TRANSLATE-1534','internal target marker persists as translation on pretranslation with fuzzy match analysis','On using fuzzy analysis with pre-translation some segments were pre-translated with an internal marker.',14,'bugfix','5.0.7'),
(386,'2019-01-21','TRANSLATE-1532','Globalese integration: error 500 thrown, if no engines are available','No it is handled in a user friendly way if no Globalese engines are available for the selected language combination.',12,'bugfix','5.0.7'),
(387,'2019-01-21','TRANSLATE-1518','Multitenancy language resources to customer association fix (customer assoc migration fix)','The association between language resources and defaultcustomer was fixed.',12,'bugfix','5.0.7'),
(388,'2019-01-21','TRANSLATE-1522','Autostaus \"Autoübersetzt\" is untranslated in EN','Autostaus \"Autoübersetzt\" is untranslated in EN',12,'bugfix','5.0.7'),
(389,'2019-01-21','VISUAL-57','VisualReview: Prevent translate5 to scroll layout, if segment has been opened by click in the layout','The layout was scrolling to another segment when opening a segment via click on an alias segment.',14,'bugfix','5.0.7'),
(390,'2019-01-21','TRANSLATE-1519','Termcollection is not assigned with default customer with zip import','Termcollection is not assigned with default customer with zip import',12,'bugfix','5.0.7'),
(391,'2019-01-21','TRANSLATE-1521','OpenTM2 Matches with <it> or <ph> tags are not shown','Now the content of the tags is removed, so that the results can be shown instead of discarding it.',14,'bugfix','5.0.7'),
(392,'2019-01-21','TRANSLATE-1501','TrackChanges: Select a word with double click then type new text produces JS error and wrong track changes','TrackChanges: Select a word with double click then type new text produces JS error and wrong track changes',14,'bugfix','5.0.7'),
(393,'2019-01-21','TRANSLATE-1544','JS error on using grid filters','JS error on using grid filters solved: Cannot read property \'isCollapsedPlaceholder\' of undefined',12,'bugfix','5.0.7'),
(394,'2019-01-21','TRANSLATE-1527','JS error on copy text content in task overview area','JS error on copy text content in task overview area solved: JS Error: this.getSegmentGrid(...) is undefined',12,'bugfix','5.0.7'),
(395,'2019-01-21','TRANSLATE-1524','JS Error when leaving task faster as server responds terms of segment','JS Error when leaving task faster as server responds terms of segment solved: Cannot read property \'updateLayout\' of undefined',12,'bugfix','5.0.7'),
(396,'2019-01-21','TRANSLATE-1503','CTRL+Z does not undo CTRL+.','CTRL+Z does not undo CTRL+.',14,'bugfix','5.0.7'),
(397,'2019-01-21','TRANSLATE-1412','TermPortal logout URL is wrong - same for InstantTranslate','TermPortal logout URL is wrong - same for InstantTranslate',12,'bugfix','5.0.7'),
(398,'2019-01-21','TRANSLATE-1517','Add user: no defaultcustomer if no customer is selected','Add user: no defaultcustomer if no customer is selected',12,'bugfix','5.0.7'),
(399,'2019-01-21','TRANSLATE-1538','click in white head area of TermPortal or InstantTranslate leads to action','click in white head area of TermPortal or InstantTranslate leads to action',14,'bugfix','5.0.7'),
(400,'2019-01-21','TRANSLATE-1539','click on info icon of term does not transfer sublanguage, when opening term in TermPortal','click on info icon of term does not transfer sublanguage, when opening term in TermPortal',14,'bugfix','5.0.7'),
(401,'2019-01-24','TRANSLATE-1547','Workflow: send mail to PM if one user finishes a task','Must be enabled via Workflow DB config!',12,'feature','5.0.7'),
(402,'2019-01-24','TRANSLATE-1386','Pixel-based length restrictions for display text translation','By passing additional configuration data on the import, segments with size-unit = pixel can be checked on editing for min and max length.',12,'feature','5.0.7'),
(403,'2019-01-31','TRANSLATE-1555','Okapi Import: Add SRX segmentation rules for most common languages','Add SRX segmentation rules for most common languages (bg, br, cs, da, de, el, en, es, et, fi, fr, hr, hu, it, ja, ko, lt, lv, nl, pl, pt, ro, ru, sk, sl, sr, tw, zh)',12,'change','5.0.7'),
(404,'2019-01-31','TRANSLATE-1557','Implement the missing workflow step workflowEnded','For some workflow configurations a defined end of the workflow is needed. Therefore the step workflowEnded was created.',12,'bugfix','5.0.7'),
(405,'2019-01-31','TRANSLATE-1299','metaCache generation is cut off by mysql setting','In the frontend some strange JSON errors can appear if there were to much MRK tags in one segment.',12,'bugfix','5.0.7'),
(406,'2019-01-31','TRANSLATE-1554','List only terms in task languages combination in editor terminology list','When editing a task with multilingual TBX imported, all languages were shown in the term window of a segment.',14,'bugfix','5.0.7'),
(407,'2019-01-31','TRANSLATE-1550','unnecessary okapiarchive.zip wastes harddisk space','On each import an additional okapiarchive.zip was created, although there was no Okapi import.',8,'bugfix','5.0.7'),
(408,'2019-02-07','TRANSLATE-1570','Editor-only usage (embedded translate5) was not working properly due JS errors','If translate5 was used in the editor-only mode (embedded translate5 editor in different management software) there were some errors in the GUI.',8,'bugfix','5.0.7'),
(409,'2019-02-07','TRANSLATE-1548','TrackChanges: always clean up nested DEL tags in the frontend','Sometimes it happend, that the Editor was sending nested DEL tags to the server, this was producing an error on server side.',12,'bugfix','5.0.7'),
(410,'2019-02-07','TRANSLATE-1526','TrackChanges: pasting content into the editor could lead to an JS error ','The error happend when the segment content was selected via double click before the content was pasted.',14,'bugfix','5.0.7'),
(411,'2019-02-07','TRANSLATE-1566','Segment pixel length restriction does not work with globalese pretranslation','The segment pixel length restriction was not working if using globalese pretranslation.',12,'bugfix','5.0.7'),
(412,'2019-02-07','TRANSLATE-1556','pressing ctrl-c in language resource panel produced an JS error in the GUI','Using ctrl-c to copy content from the language resource panel produced an error in the GUI.',14,'bugfix','5.0.7'),
(413,'2019-02-07','TRANSLATE-910','Fast clicking on segment bookmark button produces an error on server side','Multiple fast clicking on segment bookmark button was leading to an error on server side.',14,'bugfix','5.0.7'),
(414,'2019-02-07','TRANSLATE-1545','TermPortal: Term details are not displayed in term portal','After term collection import, the terms attributes and term entry attributes were not listed when the term was clicked in the term portal.',12,'bugfix','5.0.7'),
(415,'2019-02-07','TRANSLATE-1525','TrackChanges: seldom error in the GUI fixed','The error was: Failed to execute \'setStartBefore\' on \'Range\': the given Node has no parent.',12,'bugfix','5.0.7'),
(416,'2019-02-07','TRANSLATE-1230','Translate5 was not usable on touch devices','The problem was caused by the used ExtJS library.',14,'bugfix','5.0.7'),
(417,'2019-02-28','TRANSLATE-1589','Separate button to sync the GroupShare TMs in LanguageResources panel','Groupshare TMs are now synchronized manually instead automatically',12,'feature','5.0.7'),
(418,'2019-02-28','TRANSLATE-1586','Close session on browser window close','The new default behaviour on closing the application window (browser) is to log out the user. This can be disabled via configuration (runtimeOptions.logoutOnWindowClose).',14,'feature','5.0.7'),
(419,'2019-02-28','TRANSLATE-1581','Click on PM Name in task overview opens e-mail program to send an e-mail to the PM','This feature must be enabled via config: runtimeOptions.frontend.tasklist.pmMailTo',14,'feature','5.0.7'),
(420,'2019-02-28','TRANSLATE-1457','Use OpenID Connect optionally for authentication and is now able to run under different domains','OpenID Connect can be optionally used for authentication. Therefore each customer cam be configured with a separate entry URL. If this URL is used to access the application the OpenID Configuration for that Customer is used.',8,'feature','5.0.7'),
(421,'2019-02-28','TRANSLATE-1583','VisualReview: Change the button layout in \"leave visual review\" messagebox','The button layout in \"leave visual review\" messagebox was changed.',14,'change','5.0.7'),
(422,'2019-02-28','TRANSLATE-1584','Rename \"Autostatus\" to \"Bearbeitungsstatus\" in translate5 editor (german GUI)','Just a wording change in german from \"Autostatus\" to \"Bearbeitungsstatus\".',14,'change','5.0.7'),
(423,'2019-02-28','TRANSLATE-1542','InstantTranslate: Improve language selection in InstantTranslate','Improved the language selection in InstantTranslate',14,'change','5.0.7'),
(424,'2019-02-28','TRANSLATE-1587','Enable session delete to delete via internalSessionUniqId','For API usage it makes sense to delete sessions via the internalSessionUniqId. This is possible right now.',8,'change','5.0.7'),
(425,'2019-02-28','TRANSLATE-1579','TermTagger is not tagging terminology automatically on task import wizard','Since terminology is also a language resource, the termtagger did not start automatically on task import.',12,'bugfix','5.0.7'),
(426,'2019-02-28','TRANSLATE-1588','Pre-translation is running although it was disabled','In the match-analysis panel the pre-translation was not checked, altough a pre-translation was started.',12,'bugfix','5.0.7'),
(427,'2019-02-28','TRANSLATE-1572','Import language resources in background','The import of language resources is running now asynchronously in the background. So processing huge TMs or TermCollections will not produce an error in the front-end anymore.',12,'bugfix','5.0.7'),
(428,'2019-02-28','TRANSLATE-1575','Unable to take over match from language resources match grid in editor','It could happen that the content in the match grid could not be used via double click or keyboard short-cut.',14,'bugfix','5.0.7'),
(429,'2019-02-28','TRANSLATE-1567','Globalese integration: Error occurred during file upload or translation','translate5 ignores now that specific error, since it has no influence to the pre-translation.',12,'bugfix','5.0.7'),
(430,'2019-02-28','TRANSLATE-1560','Introduce a config switch to disable match resource panel','In tasks having only terminology as language resource (for term tagging) the match panel can be deactivated via config right now.',8,'bugfix','5.0.7'),
(431,'2019-02-28','TRANSLATE-1580','Remove word count field from the task import wizard','In the task creation wizard the input field for the word count was removed, the wordcount is generated via match analysis right now. Via API the setting the word count is still possible.',12,'bugfix','5.0.7'),
(432,'2019-02-28','TRANSLATE-1571','Copy and paste segment content does not work when selecting whole source segment','Copy and paste of segment content was not working when selecting the whole source segment content via triple click.',14,'bugfix','5.0.7'),
(433,'2019-03-21','TRANSLATE-1600','TrackChanges: Make tracked change marks hideable via a button and keyboard short-cut','The tracked change marks are hideable via a the view mode menu and keyboard short-cut CTRL-SHIFT-E',14,'feature','5.0.7'),
(434,'2019-03-21','TRANSLATE-1390','Microsoft translator can be used as language resource','The Microsoft translator can be used as language resource right now, it must be configured in the configuration before usage.',12,'feature','5.0.7'),
(435,'2019-03-21','TRANSLATE-1613','The segment timestamp is not set properly with MySQL 8','The segment timestamp is not set properly with MySQL 8',8,'bugfix','5.0.7'),
(436,'2019-03-21','TRANSLATE-1612','Task clone does not clone language resources','Now the language resources are also cloned for a task',12,'bugfix','5.0.7'),
(437,'2019-03-21','TRANSLATE-1604','Jobs may not be created with status finished','Assigning a user in the status \"finished\" to a task makes no sense, so this is prohibited right now.',12,'bugfix','5.0.7'),
(438,'2019-03-21','TRANSLATE-1609','API Usage: On task creation no PM can be explicitly defined','Now the PM can be set directly on task creation if the API user has the right to do so.',8,'bugfix','5.0.7'),
(439,'2019-03-21','TRANSLATE-1603','Show the link to TermPortal in InstantTranslate only, if user has TermPortal access rights','Show the link to TermPortal in InstantTranslate only, if user has TermPortal access rights',8,'bugfix','5.0.7'),
(440,'2019-03-21','TRANSLATE-1595','Match analysis export button is disabled erroneously','Match analysis export button is disabled erroneously, this is fixed right now.',12,'bugfix','5.0.7'),
(441,'2019-03-21','TRANSLATE-1597','Concordance search uses only the source language','The concordance search was only searching in the source language, even if entered a search term in the target search field.',14,'bugfix','5.0.7'),
(442,'2019-03-21','TRANSLATE-1607','Feature logout on page change disables language switch','The new feature to logout on each page change (browser close) disabled the language switch, this is fixed right now.',14,'bugfix','5.0.7'),
(443,'2019-03-21','TRANSLATE-1599','Error in Search and Replace repaired','The error \"Cannot read property \'segmentNrInTask\' of undefined\" was fixed in search and replace',14,'bugfix','5.0.7'),
(444,'2019-03-21','T5DEV-266','Sessions can be hijacked','A serious bug in the session handling was fixed.',8,'bugfix','5.0.7'),
(445,'2019-04-17','VISUAL-63','VisualReview for translation tasks','VisualReview can now also be used for translation tasks.',12,'feature','5.0.7'),
(446,'2019-04-17','TRANSLATE-355','Better error handling and user communication on import and export errors','Im and Export errors - in general all errors occuring in the context of a task - can now be investigated in the frontend.',12,'feature','5.0.7'),
(447,'2019-04-17','TRANSLATE-702','Migrate translate5 to be using PHP 7.3','Translate5 runs now with PHP 7.3 only',8,'change','5.0.7'),
(448,'2019-04-17','TRANSLATE-613','Refactor error messages and error handling','The internal error handling in translate5 was completly changed',8,'change','5.0.7'),
(449,'2019-04-17','TRANSLATE-293','create separate config for error mails receiver','Due several filter settings the receiver of error mails could be better configured.',8,'change','5.0.7'),
(450,'2019-04-17','TRANSLATE-1605','TrackChanges splits up the words send to the languagetool','Sometimes TrackChanges splits up the words send to the languagetool',14,'bugfix','5.0.7'),
(451,'2019-04-17','TRANSLATE-1624','TrackChanges: not all typed characters are marked as inserted in special cases. ','If taking over a language resource match, select that content then with CTRL+A (select all), then type new characters: in that case not all characters are highlighted as inserted.',14,'bugfix','5.0.7'),
(452,'2019-04-17','TRANSLATE-1256','In the editor CTRL-Z (undo) does not work after pasting content','CTRL-Z does now also undo pasted content',14,'bugfix','5.0.7'),
(453,'2019-04-17','TRANSLATE-1356','In the editor the caret is placed wrong after CTRL+Z','The input caret was placed at a wrong place after undoing previously edited content',14,'bugfix','5.0.7'),
(454,'2019-04-17','TRANSLATE-1520','Last CTRL+Z \"loses\" the caret in the Edtior','On using CTRL+Z (undo) it could happen that the input caret in the editor disappeared.',14,'bugfix','5.0.7'),
(455,'2019-05-10','TRANSLATE-1403','Anonymize users in the workflow','If configured via task template in the task, the users associated to a task are shown anonymized. ',12,'feature','5.0.7'),
(456,'2019-05-10','TRANSLATE-1648','Disable the drop down menu in the column head of the task grid via ACL','By default each role is allowed to use the drop down menu.',8,'change','5.0.7'),
(457,'2019-05-10','TRANSLATE-1636','OpenID Connect: Automatically remove protocol from translate5 domain','The protocol (scheme) is determined automatically.',8,'change','5.0.7'),
(458,'2019-05-10','VISUAL-64','VisualReview: Improve texts on leaving visualReview task','Just some small wording changes.',12,'change','5.0.7'),
(459,'2019-05-10','TRANSLATE-1646','The frontend inserts invisible BOM (EFBBBF) characters into the saved segment','This invisible character is removed right now on saving a segment. If there are such characters in the imported data, they are masked as a tag.',12,'bugfix','5.0.7'),
(460,'2019-05-10','TRANSLATE-1642','Saving client with duplicate \"translate5 domain\" shows wrong error message','The error message was corrected.',8,'bugfix','5.0.7'),
(461,'2019-05-10','T5DEV-267','GroupShare Integration: pre-translation and analysis does not work','This is fixed now, also the other GroupShare related issue: T5DEV-268: continue not inside a loop or switch',12,'bugfix','5.0.7'),
(462,'2019-05-10','TRANSLATE-1635','OpenID Connect: Logout URL of TermPortal leads to error, when directly login again with OpenID via MS ActiveDirectory','The user is redirected now to the main login page.',8,'bugfix','5.0.7'),
(463,'2019-05-10','TRANSLATE-1633','Across XLF comment import does provide wrong comment date','This is fixed now.',12,'bugfix','5.0.7'),
(464,'2019-05-10','TRANSLATE-1641','Adjust the translate5 help window width and height','The window size was adjusted to more appropriate values.',8,'bugfix','5.0.7'),
(465,'2019-05-10','TRANSLATE-1640','OpenID Connect: Customer domain is mandatory for OpenId group','This is not mandatory anymore.',8,'bugfix','5.0.7'),
(466,'2019-05-10','TRANSLATE-1632','JS: Cannot read property \'length\' of undefined','This is fixed now.',14,'bugfix','5.0.7'),
(467,'2019-05-10','TRANSLATE-1631','JS: me.store.reload is not a function','This is fixed now.',14,'bugfix','5.0.7'),
(468,'2019-05-10','TRANSLATE-337','uniqid should not be used for security relevant issues','The usage of uniqid and the GUID generation is basing now on random_bytes.',8,'bugfix','5.0.7'),
(469,'2019-05-10','TRANSLATE-1639','OpenID Connect: OpenId authorization redirect after wrong translate5 password','This is fixed now',8,'bugfix','5.0.7'),
(470,'2019-05-10','TRANSLATE-1638','OpenID Connect: OpenId created user is not editable','The users are editable now.',12,'bugfix','5.0.7'),
(471,'2019-06-27','TRANSLATE-1676','Disable file extension check if a custom bconf is provided','If a custom BCONF file for Okapi is provided in the import Package, the file type filter in the import is deactivated. So the it is possible to enable currently not supported file formats via Okapi.',12,'feature','5.0.7'),
(472,'2019-06-27','TRANSLATE-1665','Change font colour to black','The font colour in the segment grid is changed to pure black',14,'change','5.0.7'),
(473,'2019-06-27','TRANSLATE-1701','Searching in bookmarked segments leads to SQL error (missing column)','When setting the segment filter to show only bookmarked segments, performing a search with search and replace triggered that error',14,'bugfix','5.0.7'),
(474,'2019-06-27','TRANSLATE-1660','Remove message for unsupported browser for MS Edge','The \"unsupported browser\" message is not shown any more for Edge users, IE 9 and 10 are not officially supported any more',8,'bugfix','5.0.7'),
(475,'2019-06-27','TRANSLATE-1620','Relais (pivot) import does not work, if Trados alters mid','It can happen that the MIDs of the segments do not match between the different languages. If this is the case, the pivot language is matched by the segmentNrInTask field.',12,'bugfix','5.0.7'),
(476,'2019-06-27','TRANSLATE-1181','Workflow Cron Daily actions are called multiple times','The delivery date remind e-mail were sent two times due this issue.',12,'bugfix','5.0.7'),
(477,'2019-06-27','TRANSLATE-1695','VisualReview: segmentmap generation has a bad performance','On loading a VisualReview we had some performance issues. For tasks where the loading performance is bad, a DB table rebuild should be triggered by end and reopen that task.',14,'bugfix','5.0.7'),
(478,'2019-06-27','TRANSLATE-1694','Allow SDLXLIFF tags with dashes in the ID','The task import do not stop anymore, if there are dashes in the tag IDs of the SDLXLIFF file.',12,'bugfix','5.0.7'),
(479,'2019-06-27','TRANSLATE-1691','Search and Replace does not escape entities in the replaced text properly','On using \"replace all\" in conjunction with the & < > entities, this entities were saved wrong in the DB.',12,'bugfix','5.0.7'),
(480,'2019-06-27','TRANSLATE-1684','Uneditable segments with tags only can lose content on export','Sometimes the content of non editable segments (containing tags only) is getting lost on export.',12,'bugfix','5.0.7'),
(481,'2019-06-27','TRANSLATE-1669','repetition editor deletes wrong tags','It could happen, that on using the repetition editor some tags are removed by accident',14,'bugfix','5.0.7'),
(482,'2019-06-27','TRANSLATE-1693','Search and Replace does not open segment on small tasks','The search and replace dialog did not open the segment if the segment grid was not scrolling on selecting a segment via the search.',14,'bugfix','5.0.7'),
(483,'2019-06-27','TRANSLATE-1666','Improve error communication when uploading a import package without proofRead folder','Improve error communication when uploading a import package without proofRead folder, also re-enable the import button in the task add wizard',12,'bugfix','5.0.7'),
(484,'2019-06-27','TRANSLATE-1689','Pressing \"tab\" in search and replace produces a JS error','Pressing \"tab\" in search and replace to jump between the input fields was producing a JS error',14,'bugfix','5.0.7'),
(485,'2019-06-27','TRANSLATE-1683','Inserting white-space tags in the editor can overwrite other tags in the target','Inserting new white-space tags in the editor can overwrite other existing tags in the target content',14,'bugfix','5.0.7'),
(486,'2019-06-27','TRANSLATE-1659','Change of description for auto-assignment area in user management','Change of textual description for auto-assignment area in user management',12,'bugfix','5.0.7'),
(487,'2019-06-27','TRANSLATE-1654','TermTagger stops working on import of certain task - improved error management and logging','Since the reason for the crashes could not determined, the logging and error management in the term tagging process was improved. So if a termtagger is not reachable any more, it is not used any more until it is available again.',8,'bugfix','5.0.7'),
(488,'2019-07-17','TRANSLATE-1489','Export task as excel and be able to reimport it','While the task is exported as excel, it is locked for further editing until the changes are reimported.',12,'feature','5.0.7'),
(489,'2019-07-17','TRANSLATE-1464','SpellCheck for Japanese and other languages using the Microsoft Input Method Editor','For such languages the spell check is currently triggered via a separate button.',14,'bugfix','5.0.7'),
(490,'2019-07-17','TRANSLATE-1715','XLF Import: Segments with tags only should be ignored and pretranslated automatically on translation tasks','Since the source contains tags only, no request to a TM must be done. The target is filled with the tags directly.',12,'bugfix','5.0.7'),
(491,'2019-07-17','TRANSLATE-1705','Pre-translation does not remove \"AdditionalTag\"-Tag from OpenTM2','Since it may happen, that TM matches may contain more or different tags, in the GUI this additional tags are shown. But for pre-translation these tags must be removed.',12,'bugfix','5.0.7'),
(492,'2019-07-17','TRANSLATE-1637','MatchAnalysis: Errors in Frontend when analysing multiple tasks','This JS error is fixed now.',12,'bugfix','5.0.7'),
(493,'2019-07-17','TRANSLATE-1658','Notify assoc users with state open in notifyOverdueTasks','All users were notified, also the ones which already finished the task.',12,'bugfix','5.0.7'),
(494,'2019-07-17','TRANSLATE-1709','Missing translator checkers in email when all proofreaders are finished','The information about the following translator checkers was readded.',12,'bugfix','5.0.7'),
(495,'2019-07-17','TRANSLATE-1708','Possible server error on segment search','This seldom error is fixed.',14,'bugfix','5.0.7'),
(496,'2019-07-17','TRANSLATE-1707','XLIFF 2.1 Export creates invalid XML','Some special characters inside a XML comment was leading to invalid XML.',12,'bugfix','5.0.7'),
(497,'2019-07-17','TRANSLATE-1702','Multiple parallel export of the same task from the same session leads to errors','Impatient users start multiple exports of the same task, this was leading to dead locks in the export.',12,'bugfix','5.0.7'),
(498,'2019-07-17','TRANSLATE-1706','Improve TrackChanges markup for internal tags in Editor','Now a changed internal tag can be recognized in the editor.',14,'bugfix','5.0.7'),
(499,'2019-07-30','TRANSLATE-1720','Add segment editing history (snapshots) to JS debugging (rootcause)','Now each segment editing steps are logged in case of an error in the front-end.',8,'feature','5.0.7'),
(500,'2019-07-30','TRANSLATE-1273','Propose new terminology and terminology changes','The TermPortal was extended with features to propose new terminology and terminology changes',8,'feature','5.0.7'),
(501,'2019-07-30','TRANSLATE-717','Blocked column in segment grid shows no values and filter is inverted','In the segment grid the blocked column was empty and the filter values yes and no were flipped.',14,'bugfix','5.0.7'),
(502,'2019-07-30','TRANSLATE-1305','Exclude framing internal tags from xliff import also for translation projects','This behaviour can be disabled by setting runtimeOptions.import.xlf.ignoreFramingTags to 0 in the configuration.',12,'bugfix','5.0.7'),
(503,'2019-07-30','TRANSLATE-1724','TrackChanges: JavaSript error: WrongDocumentError (IE11 only)','Fixed JavaSript error WrongDocumentError (IE11 only).',14,'bugfix','5.0.7'),
(504,'2019-07-30','TRANSLATE-1721','JavaScript error: me.allMatches is null','Fixed JavaScript error me.allMatches is null.',14,'bugfix','5.0.7'),
(505,'2019-07-30','TRANSLATE-1045','JavaScript error: rendered block refreshed at 16 rows while BufferedRenderer view size is 48','Fixed JavaScript error rendered block refreshed at 16 rows while BufferedRenderer view size is 48',14,'bugfix','5.0.7'),
(506,'2019-07-30','TRANSLATE-1717','Segments containing one whitespace character can crash Okapi on export','If in a XLF created from Okapi a segment with only a white-space character in the source is contained, this character is removed in the target. This led to errors in Okapi export then.',12,'bugfix','5.0.7'),
(507,'2019-07-30','TRANSLATE-1718','Flexibilize LanguageResource creation via API by allow also language lcid','Flexibilize LanguageResource creation via API by allow also language lcid and RFC 5646 values',8,'bugfix','5.0.7'),
(508,'2019-07-30','TRANSLATE-1716','Pretranslation does not replace tags in repetitions correctly','The correct tag content was not used, instead always the tags of the first segment were used.',12,'bugfix','5.0.7'),
(509,'2019-07-30','TRANSLATE-1634','TrackChanges: CTRL+Z: undo works, but looses the TrackChange-INS','Using undo is working, but some TrackChanges tags were lost.',14,'bugfix','5.0.7'),
(510,'2019-07-30','TRANSLATE-1711','TrackChanges are not added on segment reset to import state','Now the resetted content is placed in change marks too.',14,'bugfix','5.0.7'),
(511,'2019-07-30','TRANSLATE-1710','TrackChanges are not correct on taking over TM match','On taking over a TM match the changes marks were not placed at the correct place.',14,'bugfix','5.0.7'),
(512,'2019-07-30','TRANSLATE-1627','SpellCheck impedes TrackChanges for CTRL+V and CTRL+. into empty segments','No change marks were created on using CTRL+V and CTRL+. into empty segments with enabled spell checker.',14,'bugfix','5.0.7'),
(513,'2019-08-20','TRANSLATE-1738','Add \"Added from MT\" to note field of Term, if term stems from InstantTranslate','Add \"Added from MT\" to note field of Term, if term stems from InstantTranslate',8,'change','5.0.7'),
(514,'2019-08-20','TRANSLATE-1739','InstantTranslate: Add button to switch languages','Some new buttons to switch language were added.',8,'change','5.0.7'),
(515,'2019-08-20','TRANSLATE-1737','Only show \"InstantTranslate into\" drop down, if no field is open for editing','Only show \"InstantTranslate into\" drop down, if no field is open for editing',8,'change','5.0.7'),
(516,'2019-08-20','TRANSLATE-1743','Term proposal system: Icons and Shortcuts for Editing','Improved icons and shortcuts for Editing.',8,'change','5.0.7'),
(517,'2019-08-20','TRANSLATE-1752','error E1149 - Export: Some segments contains tag errors is logged to much on proofreading tasks.','error E1149 - Export: Some segments contains tag errors is logged to much on proofreading tasks.',8,'bugfix','5.0.7'),
(518,'2019-08-20','TRANSLATE-1732','Open Bugs term proposal system','Fixed several bugs.',8,'bugfix','5.0.7'),
(519,'2019-08-20','TRANSLATE-1749','LanguageTool: Spellcheck is not working any more in Firefox','The spellcheck did not work in the Firefox anymore.',14,'bugfix','5.0.7'),
(520,'2019-08-20','TRANSLATE-1758','TrackCanges: Combination of trackchanges and terminology produces sometimes corrupt segments (warning \"E1132\")','The combination of trackchanges and terminology produced sometimes corrupt segments with warning \"E1132 - Conflict in merging terminology and track changes\".',12,'bugfix','5.0.7'),
(521,'2019-08-20','TRANSLATE-1755','Transit Import is not working anymore','Now Transit import is working again.',12,'bugfix','5.0.7'),
(522,'2019-08-20','TRANSLATE-1754','Authentication via session auth hash does a wrong redirect if the instance is located in a sub directory','Only instances which are not directly in the document root were affected.',8,'bugfix','5.0.7'),
(523,'2019-08-20','TRANSLATE-1750','Loading of tasks in the task overview had a bad performance','The performance was improved.',12,'bugfix','5.0.7'),
(524,'2019-08-20','TRANSLATE-1747','E9999 - Missing Arguments $code and $message','A wrong usage of the logger was repaired.',8,'bugfix','5.0.7'),
(525,'2019-08-20','TRANSLATE-1757','JS Error in LanguageResources Overview if task names contain \" characters','Quotes in the task names were leading to a JS Error in LanguageResources overview',12,'bugfix','5.0.7'),
(526,'2019-08-29','TRANSLATE-1763','Import comments from SDLXLIFF to translate5','Comments in SDLXLIFF files can now be imported. This feature must be activated in the config, otherwise the comments will be deleted.',12,'feature','5.0.7'),
(527,'2019-08-29','TRANSLATE-1776','Terminology in meta panel is also shown on just clicking on a segment','This is also useful if a task is opened read-only, when segments could not be opened for editing.',14,'feature','5.0.7'),
(528,'2019-08-29','TRANSLATE-1730','Delete change markers from SDLXLIFF','If enabled in config, the marked changes are applied and the change-marks are deleted.',12,'bugfix','5.0.7'),
(529,'2019-08-29','TRANSLATE-1778','TrackChanges fail cursor-position in Firefox','The cursor position is now at the correct place.',14,'bugfix','5.0.7'),
(530,'2019-08-29','TRANSLATE-1781','TrackChanges: reset in combination with matches is buggy','This problem is fixed.',14,'bugfix','5.0.7'),
(531,'2019-08-29','TRANSLATE-1770','TrackChanges: reset to initial content must not mark own changes as as change','On other words: If a user resets his changes, no change-marks should be applied.',14,'bugfix','5.0.7'),
(532,'2019-08-29','TRANSLATE-1765','TrackChanges: Content marked as insert produces problems with SpellChecker','Now the spellcheck markup is correct with enabled track-changes.',14,'bugfix','5.0.7'),
(533,'2019-08-29','TRANSLATE-1767','Cloning of task where assigned TBX language resource has been deleted leads to failed import','This was happening only, if the task had associated an terminology language-resource which was deleted in the meantime.',12,'bugfix','5.0.7'),
(534,'2019-09-12','TRANSLATE-1736','Config switch to disable sub-languages for TermPortal search field','Config switch to disable sub-languages for TermPortal search field',8,'feature','5.0.7'),
(535,'2019-09-12','TRANSLATE-1741','Usage of user crowds in translate5','Multiple users assigned to a task can be used as user crowd. The first user who confirms the task will be responsible then, and unassign all other users.',12,'feature','5.0.7'),
(536,'2019-09-12','TRANSLATE-1734','InstantTranslate: Preset of languages used for translation','If a user logs in the first time, the languages are now also preset to a sense-full value.',8,'feature','5.0.7'),
(537,'2019-09-12','TRANSLATE-1735','Optionally make note field in TermPortal mandatory','Optionally make note field in TermPortal mandatory',8,'feature','5.0.7'),
(538,'2019-09-12','TRANSLATE-1733','System config in TermPortal: All languages available for adding a new term?','System config in TermPortal: All languages available for adding a new term?',8,'feature','5.0.7'),
(539,'2019-09-12','TRANSLATE-1792','Make columns in user table of workflow e-mails configurable','Some workflow e-mails are containing user lists. The columns of that lists are now configurable.',8,'change','5.0.7'),
(540,'2019-09-12','TRANSLATE-1791','Enable neutral salutation','Providing gender information for users is not mandatory anymore, salutation will be neutral in emails if value is omitted.',12,'change','5.0.7'),
(541,'2019-09-12','TRANSLATE-1742','Not configured mail server may crash application','Now the errors in connection to the mail server do not stop the request anymore, they are just logged.',8,'bugfix','5.0.7'),
(542,'2019-09-12','TRANSLATE-1771','\"InstantTranslate Into\" available in to many languages','\"InstantTranslate Into\" available in to many languages',8,'bugfix','5.0.7'),
(543,'2019-09-12','TRANSLATE-1788','Javascript error getEditorBody.textContent() is undefined','The error is fixed.',8,'bugfix','5.0.7'),
(544,'2019-09-12','TRANSLATE-1782','Minor TermPortal bugs fixed','Minor TermPortal bugs fixed.',8,'bugfix','5.0.7'),
(545,'2019-09-24','TRANSLATE-1045','Jvascript error: rendered block refreshed at (this is the fix for the doRefreshView override function in the BufferedRenderer)','An error occured, when the user sorted segments, while they where loaded',14,'bugfix','5.0.7'),
(546,'2019-09-24','TRANSLATE-1219','Editor iframe body is reset and therefore not usable due missing content','translate5 segment editor blocked the whole application, when a segment was opened. This occured in rare situations, but with a new Chrome release this suddenly occured in Chrome for every segment',14,'bugfix','5.0.7'),
(547,'2019-09-24','TRANSLATE-1756','Excel export error with segments containing an equal sign at the beginning','Segments starting with an equal sign (=) led to an error in Excel export of segments',12,'bugfix','5.0.7'),
(548,'2019-09-24','TRANSLATE-1796','Error on match analysis tab panel open','Opening the match analysis tab led to an error',12,'bugfix','5.0.7'),
(549,'2019-09-24','TRANSLATE-1797','Deleting of terms on import does not work','When importing a TBX file into a TermCollection the deletion of terms, that are not present in the TBX did only work, if the complete TermEntry was not present (the corresponding import flag has to be active to delete terms on import)',12,'bugfix','5.0.7'),
(550,'2019-09-24','TRANSLATE-1798','showSubLanguages in TermPortal does not work as it should','If the selection of sublanguages is disabled for the search field of the TermPortal, the main language was not present, if only terms with a sublanguage had been part of the TermCollection',14,'bugfix','5.0.7'),
(551,'2019-09-24','TRANSLATE-1799','TermEntry Proposals get deleted, when they should not','When importing a TBX file into a TermCollection, TermEntries containing only suggestions were deleted, even though suggestions should not be deleted (the corresponding import flag has to be active to delete terms on import)',12,'bugfix','5.0.7'),
(552,'2019-09-24','TRANSLATE-1800','Uncaught Error: rendered block refreshed at 0 rows','When certain rights have been disabled, the language resource panel was blocked, when it should not',12,'bugfix','5.0.7'),
(553,'2019-10-07','TRANSLATE-1671','(Un)lock 100%-Matches in task properties','100%-Matches can now be locked and unlocked in the task properties by a PM at any time in the workflow',12,'feature','5.0.7'),
(554,'2019-10-07','TRANSLATE-1803','New options for automatic term proposal deletion on TBX import','Via GUI and URL-triggered import term proposals can now be deleted completely independent of term deletion (meaning deletion of terms or proposals, that existed before the import in translate5)',12,'feature','5.0.7'),
(555,'2019-10-07','TRANSLATE-1816','Create a search & replace button','A new button for search&replace has been introduced to make it more easy for users to find the feature',14,'feature','5.0.7'),
(556,'2019-10-07','TRANSLATE-1817','Get rid of head panel in editor','The head panel of translate5 editor has been removed to give more space for the actual work by default',14,'feature','5.0.7'),
(557,'2019-10-07','TRANSLATE-1551','Readonly task is editable when using VisualReview','',14,'bugfix','5.0.7'),
(558,'2019-10-07','TRANSLATE-1761','Clean up „tbx-for-filesystem-import“ directory','Old non-needed TBX files left from old imports are deleted',8,'bugfix','5.0.7'),
(559,'2019-10-07','TRANSLATE-1790','In the general mail template the portal link points to wrong url','This has been the case, if translate5 is configured to run on a certain sub-domain for a certain customer',12,'bugfix','5.0.7'),
(560,'2019-10-08','TRANSLATE-1774','Integrate NEC-TM with translate5 as LanguageResource','The upcoming open source TM server engine NEC-TM has been added as available language resource as plug-in',12,'feature','5.0.7'),
(561,'2019-10-14','TRANSLATE-1378 Search & Replace','Search & Replace: Activate \"Replace\"-key right away','Bislang war der „Ersetzen“-Button erst aktiv, nachdem etwas bereits gesucht wurde. Nun ist er sofort aktiv und sucht etwas und markiert es zum Ersetzen.',14,'change','5.0.7'),
(562,'2019-10-14','TRANSLATE-1615 Move whitespace buttons to segment meta-panel','Move whitespace buttons to segment meta-panel','The buttons to add extra whitespace have been moved from beneath the opened segment to the right column',14,'change','5.0.7'),
(563,'2019-10-14','TRANSLATE-1815 Segment editor should automatically move down a bit','Segment editor should automatically move down a bit','The opened segment now tends to focus in below the upper third of the segments, if this is possible. This enables the user to always see preceeding and following content.',14,'change','5.0.7'),
(564,'2019-10-14','TRANSLATE-1836','Get rid of message \"Segment updated in TM!\"','',14,'change','5.0.7'),
(565,'2019-10-14','TRANSLATE-1826','Include east asian sub-languages and thai in string-based termTagging','For Asian sub-languages and Thai, string-based terminology highlighting was enabled, otherwise no term tagging would be possible there',12,'bugfix','5.0.7'),
(566,'2019-10-16','TRANSLATE-1838','OpenID integration: Support different roles for default roles than for maximal allowed roles','',14,'feature','5.0.7'),
(567,'2019-10-16','TRANSLATE-1719','The supplied node is incorrect or has an incorrect ancestor for this operation','This has been an error, that sometimes occured when TrackChanges has been used in Firefox on Windows 10',14,'bugfix','5.0.7'),
(568,'2019-10-16','TRANSLATE-1820','TermPortal (engl.): \"comment\", not \"note\" or \"Anmerkung\"','Language correction made in the TermPortal',12,'bugfix','5.0.7'),
(569,'2019-11-12','TRANSLATE-1839','Show some KPIs in translate5 task overview','One KPI is for example the average time until delivery of a task.',12,'feature','5.0.7'),
(570,'2019-11-12','TRANSLATE-1858','GroupShare TMs with several languages','Such TMs are listed now correctly.',12,'change','5.0.7'),
(571,'2019-11-12','TRANSLATE-1849','OpenID Connect integration should be able to handle and merge roles from different groups for one user','OpenID Connect integration should be able to handle and merge roles from different groups for one user',8,'change','5.0.7'),
(572,'2019-11-12','TRANSLATE-1848','define collection per user in automated term proposal export','the collections to be exported can be defined now',8,'change','5.0.7'),
(573,'2019-11-12','TRANSLATE-1869','Calling TermPortal without Session does not redirect to login','Now the user is redirected to the login page.',12,'bugfix','5.0.7'),
(574,'2019-11-12','TRANSLATE-1866','TermPortal does not filter along termCollection, when showing terms of a termEntry','This is fixed now.',8,'bugfix','5.0.7'),
(575,'2019-11-12','TRANSLATE-1819','TermPortal: comment is NOT mandatory when editing is canceled','TermPortal: comment is NOT mandatory when editing is canceled',8,'bugfix','5.0.7'),
(576,'2019-11-12','TRANSLATE-1865','JS error when resetting segment to initial value','JS error when resetting segment to initial value',8,'bugfix','5.0.7'),
(577,'2019-11-12','TRANSLATE-1480','JS Error on using search and replace','This is fixed now.',8,'bugfix','5.0.7'),
(578,'2019-11-12','TRANSLATE-1863','InvalidXMLException: E1024 - Invalid XML does not bubble correctly into frontend on import','Now the error is logged and shown correctly',12,'bugfix','5.0.7'),
(579,'2019-11-12','TRANSLATE-1861','Not all known default matchrate Icons are shown','All icons are shown again.',14,'bugfix','5.0.7'),
(580,'2019-11-12','TRANSLATE-1850','Non userfriendly error on saving workflow user prefs with an already deleted user','A human readable error message is shown now.',12,'bugfix','5.0.7'),
(581,'2019-11-12','TRANSLATE-1860','Error code error logging in visual review','Errors are now logged into the task log.',12,'bugfix','5.0.7'),
(582,'2019-11-12','TRANSLATE-1862','Copy & Paste from outside the segment','Copy & Paste from outside the segment is now possible',14,'bugfix','5.0.7'),
(583,'2019-11-12','TRANSLATE-1857','Some strings in the interface are shown in German','Translated the missing strings',12,'bugfix','5.0.7'),
(584,'2019-11-12','TRANSLATE-1363','Ensure that search and replace can not be sent without field to be searched','The problem is fixed now',8,'bugfix','5.0.7'),
(585,'2019-11-12','TRANSLATE-1867','TBX-Import: In TBX without termEntry-IDs terms get merged along autogenerated termEntry-ID','The generation of termEntry-ID was changed in the case the TBX does not provide such an ID.',12,'bugfix','5.0.7'),
(586,'2019-11-12','TRANSLATE-1552','Auto set needed ACL roles','If a user gets the role pm, the editor role is needed too. Such missing roles are added now automatically.',8,'bugfix','5.0.7'),
(587,'2019-11-12','VISUAL-52','Red bubble on changed alias segments in layout','Changed alias segments are now also shown as edited',14,'bugfix','5.0.7'),
(588,'2019-11-12','VISUAL-62','Integrate PDF optimization via ghostscript to reduce font errors on HTML conversion','This must be activated in the config. If activated import errors of the PDF files should be reduced.',8,'bugfix','5.0.7'),
(589,'2019-12-02','TRANSLATE-1167','Edit task simultanously with multiple users','Multiple users can edit the same task at the same time. See Translate5 confluence how to activate that feature!',14,'feature','5.0.7'),
(590,'2019-12-02','TRANSLATE-1493','Filter by user, workflow-step, job-status and language combination','Several new filters can be used in the task overview.',14,'feature','5.0.7'),
(591,'2019-12-02','TRANSLATE-1889','rfc 5646 value for estonian is wrong','The RFC 5646 value for estonian was wrong',12,'change','5.0.7'),
(592,'2019-12-02','TRANSLATE-1886','Error on refreshing GroupShare TMs when a used TM should be deleted','The error is fixed right now.',12,'change','5.0.7'),
(593,'2019-12-02','TRANSLATE-1884','Special Character END OF TEXT in importable content produces errors.','The special character END OF TEXT is masked in the import now.',12,'change','5.0.7'),
(594,'2019-12-02','TRANSLATE-1840','Insert opening and closing tag surround text selections with one key press','Insert opening and closing tag surround text selections with one key press',14,'change','5.0.7'),
(595,'2019-12-03','TRANSLATE-1871','Enhance theRootCause integration: users can activate video-recording after login','Users can activate optionally video-recording after login to improve error reporting.',8,'change','5.0.7'),
(596,'2019-12-18','TRANSLATE-1531','Provide progress data about a task','Editors and PMs see the progress of the tasks.',14,'feature','5.0.7'),
(597,'2019-12-18','TRANSLATE-1896','Delete MemoQ QA-tags on import of memoq xliff','Otherwise the MemoQ file could not be imported',12,'change','5.0.7'),
(598,'2019-12-18','TRANSLATE-1910','When talking to an OpenId server missing ssl certificates can be configured','If the SSO server uses a self signed certificate or is not configured properly a missing certificate chain can be configured in the SSO client used by translate5.',8,'change','5.0.7'),
(599,'2019-12-18','TRANSLATE-1824','xlf import does not handle some unicode entities correctly','The special characters are masked as tags now.',12,'bugfix','5.0.7'),
(600,'2019-12-18','TRANSLATE-1909','Reset the task tbx hash when assigned termcollection to task is updated','For the termtagger a cached TBX is created out of all term-collections assigned to a task. On term-collection update this cached file is updated too.',12,'bugfix','5.0.7'),
(601,'2019-12-18','TRANSLATE-1885','Several BugFixes in the GUI','Several BugFixes in the GUI',8,'bugfix','5.0.7'),
(602,'2019-12-18','TRANSLATE-1760','TrackChanges: Bugs with editing content','Some errors according to TrackChanges were fixed.',14,'bugfix','5.0.7'),
(603,'2019-12-18','TRANSLATE-1864','Usage of changealike editor may duplicate internal tags','This happened only under special circumstances.',14,'bugfix','5.0.7'),
(604,'2019-12-18','TRANSLATE-1804','Segments containing only the number 0 are not imported','There were also problems on the export of such segments.',12,'bugfix','5.0.7'),
(605,'2019-12-18','TRANSLATE-1879','Handle removals of corresponding opening and closing tags for tasks with and without trackChanges','If a removed tag was part of a tag pair, the second tag is deleted automatically.',14,'bugfix','5.0.7'),
(606,'2020-02-17','TRANSLATE-1960','Define if source or target is connected with visualReview on import','The user can choose now if the uploaded PDF corresponds to the source or target content.',12,'feature','5.0.7'),
(607,'2020-02-17','TRANSLATE-1831','Integrate DeepL in translate5 (Only for users with support- and development)','The DeepL Integration is only available for users with a support- and development contract. The Plug-In must be activated and the DeepL key configured in the config for usage. See https://confluence.translate5.net/display/TPLO/DeepL',14,'feature','5.0.7'),
(608,'2020-02-17','TRANSLATE-1455','Deadlines and assignment dates for every role of a task','This was only possible for the whole task, now per each associated user a dedicated deadline can be defined.',12,'feature','5.0.7'),
(609,'2020-02-17','TRANSLATE-1959','InstantTranslate: handle tags in the source as part of the source-text','InstantTranslate is now supposed to handle tags in the source as part of the source-text.',14,'change','5.0.7'),
(610,'2020-02-17','TRANSLATE-1918','VisualReview: log segmentation results','The results of the segmentation is logged into the task log and is sent via email.',12,'change','5.0.7'),
(611,'2020-02-17','TRANSLATE-1916','Change supported browser message','The message about the supported browsers was changed, also IE11 is no not supported anymore.',14,'change','5.0.7'),
(612,'2020-02-17','TRANSLATE-905','Improve maintenance mode','The maintenance mode has now a free-text field to display data to the users, also the maintenance can be announced to all admin users. See https://confluence.translate5.net/display/TIU/install-and-update.sh+functionality',8,'change','5.0.7'),
(613,'2020-02-17','TRANSLATE-1981','Sorting the bookmark column produces errors','Sorting the by default hidden bookmark column in the segment table produced an error.',14,'bugfix','5.0.7'),
(614,'2020-02-17','TRANSLATE-1975','Reenable Copy & Paste from term window','Copy and paste was not working any more for the terms listed in the segment meta panel on the right.',14,'bugfix','5.0.7'),
(615,'2020-02-17','TRANSLATE-1973','TrackChanges should not added by default on translation tasks without a workflow with CTRL+INS','When using CTRL+INS to copy the source to the target content, TrackChanges should be only added for review tasks in any case.',12,'bugfix','5.0.7'),
(616,'2020-02-17','TRANSLATE-1972','Default role in translation tasks should be translator not reviewer','This affects the front-end default role in the task user association window.',12,'bugfix','5.0.7'),
(617,'2020-02-17','TRANSLATE-1971','segments excluded with excluded framing ept and bpt tags could not be exported','Very seldom error in combination with segments containing ept and bpt tags.',12,'bugfix','5.0.7'),
(618,'2020-02-17','TRANSLATE-1970','Unable to open Instant-translate/Term-portal from translate5 buttons','This bug was applicable only if the config runtimeOptions.logoutOnWindowClose is enabled.',12,'bugfix','5.0.7'),
(619,'2020-02-17','TRANSLATE-1968','Correct spelling mistake','Fixed a german typo in the user notification on association pop-up.',12,'bugfix','5.0.7'),
(620,'2020-02-17','TRANSLATE-1969','Adding hunspell directories for spell checking does not work for majority of languages','Using external hunspell directories via LanguageTool is working now. Usage is described in https://confluence.translate5.net/display/TIU/Activate+additional+languages+for+spell+checking',12,'bugfix','5.0.7'),
(621,'2020-02-17','TRANSLATE-1966','File-system TBX import error on term-collection create','The file-system based TBX import is now working again.',8,'bugfix','5.0.7'),
(622,'2020-02-17','TRANSLATE-1964','OpenID: Check for provider roles before the default roles check','OpenID was throwing an exception if the default roles are not set for the client domain even if the openid provider provide the roles in the claims response.',8,'bugfix','5.0.7'),
(623,'2020-02-17','TRANSLATE-1963','Tbx import fails when importing a file','On TBX import the TBX parser throws an exception and the import process is stopped only when the file is uploaded from the users itself.',8,'bugfix','5.0.7'),
(624,'2020-02-17','TRANSLATE-1962','SDLLanguageCloud: status always returns unavailable','Checking the status was always returning unavailable, although the LanguageResource is available and working.',12,'bugfix','5.0.7'),
(625,'2020-02-17','TRANSLATE-1919','taskGuid column is missing in LEK_comment_meta','A database column was missing.',8,'bugfix','5.0.7'),
(626,'2020-02-17','TRANSLATE-1913','Missing translation if no language resource is available for the language combination','Just added the missing English translation.',12,'bugfix','5.0.7'),
(627,'2020-02-27','TRANSLATE-1987','Load custom page in the editors branding area','Custom content in the branding area can now be included via URL',8,'feature','5.0.7'),
(628,'2020-02-27','TRANSLATE-1927','Pre-translate documents in InstantTranslate','InstantTranslate is now able to translate documents',14,'feature','5.0.7'),
(629,'2020-02-27','TRANSLATE-1989','Erroneously locked segment on tasks with only one user and no simultaneous usage mode','Some segments were locked in the frontend although only one user was working on the task.',14,'bugfix','5.0.7'),
(630,'2020-02-27','TRANSLATE-1988','Enhanced filters button provides drop-down with to much user names','Only the users associated to the tasks visible to the current user should be visible.',12,'bugfix','5.0.7'),
(631,'2020-02-27','TRANSLATE-1986','Unable to import empty term with attributes','An error occurs when importing term with empty term value, valid term attributes and valid term id.',8,'bugfix','5.0.7'),
(632,'2020-02-27','TRANSLATE-1980','Button \"open task\" is missing for unaccepted jobs','For jobs that are not accepted so far, the \"open task\" action icon is missing. It should be shown again.',14,'bugfix','5.0.7'),
(633,'2020-02-27','TRANSLATE-1978','In InstantTranslate the Fuzzy-Match is not highlighted correctly','The source difference of fuzzy matches was not shown correctly.',8,'bugfix','5.0.7'),
(634,'2020-02-27','TRANSLATE-1911','Error if spellcheck answer returns from server after task was left already','When the task was left before the spellcheck answer was returned from the server an error occured.',14,'bugfix','5.0.7'),
(635,'2020-02-27','TRANSLATE-1841','pc elements in xliff 2.1 exports are not correctly nested in conjunction with TrackChanges Markup','The xliff 2.1 export produced invalid XML in some circumstances.',12,'bugfix','5.0.7'),
(636,'2020-04-08','TRANSLATE-1997','Show help window automatically and remember \"seen\" click','If configured the window pops up automatically and saves the \"have seen\" info',14,'feature','5.0.7'),
(637,'2020-04-08','TRANSLATE-2001','Support MemoQ comments for im- and export','Added comment support to the MemoQ im- and export',12,'feature','5.0.7'),
(638,'2020-04-08','TRANSLATE-2007','LanguageResources that cannot be used: Improve error handling','Improved the error handling if a chosen language-resource is not available.',12,'change','5.0.7'),
(639,'2020-04-08','TRANSLATE-2022','Prevent huge segments to be send to the termTagger','Huge Segments (configurable, default more then 150 words) are not send to the TermTagger anymore due performance reasons.',12,'bugfix','5.0.7'),
(640,'2020-04-08','TRANSLATE-1753','Import Archive for single uploads misses files and can not be reimported','In the import archive for single uploads some files were missing, so that the task could not be reimported with the clone button.',8,'bugfix','5.0.7'),
(641,'2020-04-08','TRANSLATE-2018','mysql error when date field as default value has CURRENT_TIMESTAMP','The problem is solved in translate5 by adding the current timestamp there',8,'bugfix','5.0.7'),
(642,'2020-04-08','TRANSLATE-2008','Improve TermTagger usage when TermTagger is not reachable','The TermTagger is not reachable in the time when it is tagging terms. So if the segments are bigger this leads to timeout messages when trying to connect to the termtagger.',8,'bugfix','5.0.7'),
(643,'2020-04-08','TRANSLATE-2004','send import summary mail to pm on import errors','Sends a summary of import errors and warnings to the PM, by default only if the PM did not start the import but via API. Can be overriden by setting always to true in the workflow notification configuration.',12,'bugfix','5.0.7'),
(644,'2020-04-08','TRANSLATE-1977','User can not be assigned to 2 different workflow roles of the same task','A user can not added multiple times in different roles to a task. For example: first as translator and additionaly as second reviewer.',12,'bugfix','5.0.7'),
(645,'2020-04-08','TRANSLATE-1998','Not able to edit segment in editor, segment locked','This was an error in the multi user backend',14,'bugfix','5.0.7'),
(646,'2020-04-08','TRANSLATE-2013','Not replaced relaisLanguageTranslated in task association e-mail','A text fragment was missing in the task association e-mail',12,'bugfix','5.0.7'),
(647,'2020-04-08','TRANSLATE-2012','MessageBus is not reacting to requests','The MessageBus-server was hanging in an endless loop in some circumstances.',8,'bugfix','5.0.7'),
(648,'2020-04-08','TRANSLATE-2003','Remove criticical data from error mails','Some critical data is removed automatically from log e-mails.',8,'bugfix','5.0.7'),
(649,'2020-04-08','TRANSLATE-2005','\"Display tracked changes\" only when TrackChanges are active for a task','The button to toggle TrackChanges is disabled if TrackChanges are not available due workflow reasons',14,'bugfix','5.0.7'),
(650,'2020-05-07','TRANSLATE-1999','Optional custom content can be displayed in the file area of the editor','See configuration runtimeOptions.editor.customPanel.url and runtimeOptions.editor.customPanel.title',8,'feature','5.0.7'),
(651,'2020-05-07','TRANSLATE-2028','Change how help window urls are defined in Zf_configuration','See https://confluence.translate5.net/display/CON/Database+based+configuration',8,'feature','5.0.7'),
(652,'2020-05-07','TRANSLATE-2039','InstantTranslate: Translate text area segmented against TM and MT and Terminology','InstantTranslate can deal now with multiple sentences',2,'feature','5.0.7'),
(653,'2020-05-07','TRANSLATE-2048','Provide segment auto-state summary via API','A segment auto-state summary is now provided via API',8,'feature','5.0.7'),
(654,'2020-05-07','TRANSLATE-2044','Change Edge browser support version','Minimum Edge Version is now: Version 80.0.361.50: 11. Februar or higher',14,'change','5.0.7'),
(655,'2020-05-07','TRANSLATE-2042','Introduce a tab panel used for the administrative main components','The administration main menu was improved',12,'change','5.0.7'),
(656,'2020-05-07','TRANSLATE-1926','Add LanguageResources: show all services that translate5 can handle','On adding LanguageResources also the not configured resources are shown (disabled, but the user knows now that it does exist)',12,'change','5.0.7'),
(657,'2020-05-07','TRANSLATE-2031','NEC-TM: Categeries are mandatory','On the creation and usage of NEC-TM categeries are now mandatory',12,'change','5.0.7'),
(658,'2020-05-07','TRANSLATE-1769','Fuzzy-Matching of languages in TermTagging does not work, when a TermCollection is added after task import','If choosing a language with out a sublanguage in translate5 (just \"de\" for example) the termtagger should also tag terms in the language de_DE. This was not working anymore.',12,'bugfix','5.0.7'),
(659,'2020-05-07','TRANSLATE-2024','InstantTranslate file translation: Segments stay empty, if no translation is provided','If for a segment no translation could be find, the source text remains.',2,'bugfix','5.0.7'),
(660,'2020-05-07','TRANSLATE-2029','NEC-TM Error in GUI: Save category assocs','A JS error occured on saving NEC-TMs',12,'bugfix','5.0.7'),
(661,'2020-05-07','TRANSLATE-2030','Garbage Collector produces DB DeadLocks due wrong timezone configuration','The problem was fixed internally, although it should be ensured, that the DB and PHP run in the same timezone.',8,'bugfix','5.0.7'),
(662,'2020-05-07','TRANSLATE-2033','JS error when leaving the application','The JS error \"Sync XHR not allowed in page dismissal\" was solved',8,'bugfix','5.0.7'),
(663,'2020-05-07','TRANSLATE-2034','In Chinese languages some ^h characters are added which prevents export then due invalid XML ','The characters are masked now as special character, which prevents the XML getting scrambled.',6,'bugfix','5.0.7'),
(664,'2020-05-07','TRANSLATE-2036','Handle empty response from the spell check','The Editor may handle empty spell check results now',2,'bugfix','5.0.7'),
(665,'2020-05-07','TRANSLATE-2037','VisualReview: Leaving a task leads to an error in Microsoft Edge','Is fixed now, was reproduced on Microsoft Edge: 44.18362.449.0',2,'bugfix','5.0.7'),
(666,'2020-05-07','TRANSLATE-2050','Change Language Resource API so that it is understandable','Especially the handling of the associated clients and the default clients was improved',8,'bugfix','5.0.7'),
(667,'2020-05-07','TRANSLATE-2051','TaskGrid advanced datefilter is not working','Especially the date at was not working',4,'bugfix','5.0.7'),
(668,'2020-05-07','TRANSLATE-2055','Switch okapi import to tags, that show tag markup to translators','Instead of g and x tags Okapi produces know ph, it, bpt and ept tags, which in the end shows the real tag content to the user in the Editor.',6,'bugfix','5.0.7'),
(669,'2020-05-07','TRANSLATE-2056','Finished task can not be opened readonly','Tasks finished in the workflow could not be opened anymore read-only by the finishing user',6,'bugfix','5.0.7'),
(670,'2020-05-07','TRANSLATE-2057','Disable term tagging in read only segments','This can be changed in the configuration, so that terms of non editable segments can be tagged if needed',6,'bugfix','5.0.7'),
(671,'2020-05-07','TRANSLATE-2059','Relais import fails with DB error message','This is fixed now.',4,'bugfix','5.0.7'),
(672,'2020-05-07','TRANSLATE-2023','InstantTranslate - Filetranslation: Remove associations to LanguageResources after translation','On using the file translation in InstantTranslate some automatically used language resources are now removed again',8,'bugfix','5.0.7'),
(673,'2020-05-11','TRANSLATE-1661','MatchAnalysis: GroupShare TMs support now also count of internal fuzzies','The GroupShare connector is now able to support the count of internal fuzzies',12,'feature','5.0.7'),
(674,'2020-05-11','TRANSLATE-2062','Support html fragments as import files without changing the structure','The Okapi import filter was changed, so that also HTML fragments (instead only valid HTML documents) can be imported',12,'bugfix','5.0.7'),
(675,'2020-05-27','TRANSLATE-2043','Use Composer to manage all the PHP dependencies in development','All PHP third party code libraries are now delivered as one third-party package. In development composer is used now to manage all the PHP dependencies.',8,'change','5.0.7'),
(676,'2020-05-27','TRANSLATE-2082','Missing surrounding tags on export of translation tags','For better usability surrounding tags of a segment are not imported. In translation task this tags are not added anymore on export. For review tasks everything was working.',12,'bugfix','5.0.7'),
(677,'2020-06-04','TRANSLATE-1610','Bundle tasks to projects','Several tasks with same content and same source language can now be bundled to projects. A completely new project overview was created therefore.',12,'feature','5.0.7'),
(678,'2020-06-04','TRANSLATE-1901','Support lines in pixel-based length check','If configured the width of each new-line in target content is calculated and checked separately.',14,'feature','5.0.7'),

(680,'2020-06-04','TRANSLATE-2086','Integrate ModelFront (MT risk prediction)','ModelFront risk prediction for MT matches is integrated.',12,'feature','5.0.7'),
(681,'2020-06-04','TRANSLATE-2087','VisualTranslation: Highlight pre-translated segments of bad quality / missing translations','Highlight pre-translated segments of bad quality / missing translations in visual translation ',6,'feature','5.0.7'),
(682,'2020-06-04','TRANSLATE-1929','VisualTranslation: HTML files can import directly','HTML files can be used directly as import file in VisualTranslation',4,'feature','5.0.7'),
(683,'2020-06-04','TRANSLATE-2072','move character pixel definition from customer to file level','The definition of character pixel widths is move from customer to file level',12,'change','5.0.7'),
(684,'2020-06-04','TRANSLATE-2084','Disable possiblity to delete tags by default','The possibility to save a segment with tag errors and ignore the warn message is disabled now. This can be re-enabled as described in https://jira.translate5.net/browse/TRANSLATE-2084. Whitespace tags can still be deleted. ',6,'change','5.0.7'),
(685,'2020-06-04','TRANSLATE-2085','InstantTranslate: handling of single segments with dot','Translating one sentence with a trailing dot was recognized as multiple sentences instead only one.',6,'change','5.0.7'),
(686,'2020-06-17','TRANSLATE-1900','Pixel length check: Handle characters with unkown pixel length','Pixel length check: Handle characters with unkown pixel length',8,'feature','5.0.7'),
(687,'2020-06-17','TRANSLATE-2054','Integrate PangeaMT with translate5','Integrate PangeaMT as new machine translation language resource.',12,'feature','5.0.7'),
(688,'2020-06-17','TRANSLATE-2092','Import specific DisplayText XML','Import specific DisplayText XML',8,'feature','5.0.7'),
(689,'2020-06-17','TRANSLATE-2070','In XLF Import: Move also bx,ex and it tags out of the segment (sponsored by Supertext)','Move paired tags out of the segment, where the corresponding tag belongs to another segment',14,'change','5.0.7'),
(690,'2020-06-17','TRANSLATE-2091','Prevent hanging imports when starting maintenance mode','Starting an improt while a maintenance is scheduled could lead to hanging import workers. Now workers don\'t start when a maintenance is scheduled.',8,'bugfix','5.0.7'),
(691,'2020-06-19','TRANSLATE-1900','Pixel length check: Handle characters with unkown pixel length','Pixel length check: Handle characters with unkown pixel length',8,'feature','5.0.7'),
(692,'2020-06-19','TRANSLATE-2054','Integrate PangeaMT with translate5','Integrate PangeaMT as new machine translation language resource.',12,'feature','5.0.7'),
(693,'2020-06-19','TRANSLATE-2092','Import specific DisplayText XML','Import specific DisplayText XML',8,'feature','5.0.7'),
(694,'2020-06-19','TRANSLATE-2071','VisualTranslation: When a XSL Stylesheet is linked in an imported XML, a HTML as source for the VisualReview will be generated from it','An imported XML may contains a link to an XSL stylesheet. If this link exists (as a file or valid URL) the Source for the VisualTranslation is generated from the XSL processing of the XML',12,'feature','5.0.7'),
(695,'2020-06-19','TRANSLATE-2070','In XLF Import: Move also bx,ex and it tags out of the segment (sponsored by Supertext)','Move paired tags out of the segment, where the corresponding tag belongs to another segment',14,'change','5.0.7'),
(696,'2020-06-19','TRANSLATE-2091','Prevent hanging imports when starting maintenance mode','Starting an improt while a maintenance is scheduled could lead to hanging import workers. Now workers don\'t start when a maintenance is scheduled.',8,'bugfix','5.0.7'),
(697,'2020-06-30','TRANSLATE-1774','Integrated NEC-TM with translate5 as Language-Resource','Integrated NEC-TM with translate5 as Language-Resource',12,'feature','5.0.7'),
(698,'2020-06-30','TRANSLATE-2052','Added capabilities to assign different segments of the same task to different users','Added capabilities to assign different segments of the same task to different users',12,'feature','5.0.7'),
(699,'2020-06-30','TRANSLATE-2094','Removed workflow action „setReviewersFinishDate“','Removed workflow action „setReviewersFinishDate“',12,'bugfix','5.0.7'),
(700,'2020-06-30','TRANSLATE-2096','Use FontAwesome5 for all icons in translate5','Use FontAwesome5 for all icons in translate5',8,'bugfix','5.0.7'),
(701,'2020-06-30','TRANSLATE-2097','Minimum characters requirement for client name in clients form is now 1','Minimum characters requirement for client name in clients form is now 1',12,'bugfix','5.0.7'),
(702,'2020-06-30','TRANSLATE-2101','Disable automated translation xliff creation from notFoundTranslation xliff in production instances','Disable automated creation of a xliff-file from notFoundTranslation xliff in production instances',8,'bugfix','5.0.7'),
(703,'2020-06-30','TRANSLATE-2102','VisualTranslation: Commas in the PDF filenames (formerly leading to failing imports) are now automatically corrected','VisualTranslation: Commas in PDF filenames (formerly leading to failing imports) are now automatically corrected',14,'bugfix','5.0.7'),
(704,'2020-06-30','TRANSLATE-2104','The KPI Button works as expected now','The KPI Button works as expected now',14,'bugfix','5.0.7'),
(705,'2020-06-30','TRANSLATE-2105','The serverside check for the pixel-based length check works as expected with multiple lines now','The serverside check for the pixel-based length check works as expected with multiple lines now',14,'bugfix','5.0.7'),
(706,'2020-06-30','TRANSLATE-2106','Whitespace and blanks from user login and password in the login form are automatically removed','Whitespace and blanks from user login and password in the login form are automatically removed',14,'bugfix','5.0.7'),
(707,'2020-06-30','TRANSLATE-2109','Remove string length restriction flag','Remove string length restriction configuration option',8,'bugfix','5.0.7'),
(708,'2020-06-30','TRANSLATE-2121','Fixed issues with filenames on NEC-TM tmx export and import','Fixed issues with filenames on NEC-TM tmx export and import',12,'bugfix','5.0.7'),
(709,'2020-07-06','TRANSLATE-2016','Files that must be imported directly into translate5 as translation jobs to generate the in-context view can now be automatically merged with already translated sdlxliff data.','',96,'feature','5.0.7'),
(710,'2020-07-06','TRANSLATE-2128','Add capabilities to disable the \"What you see is what you get\" via config-option','',96,'change','5.0.7'),
(711,'2020-07-06','TRANSLATE-2074','Complete deactivation of Internet Explorer 11 before log in, because  it is no longer supported','',98,'bugfix','5.0.7'),
(712,'2020-07-06','TRANSLATE-2114','Reload of browser led to session destruction on the server-side (by purpose) but in the front-end to a Javascript error. This is fixed now.','',98,'bugfix','5.0.7'),
(713,'2020-07-06','TRANSLATE-2123','In some cases the cache for a task could not be recreated','',96,'bugfix','5.0.7'),
(714,'2020-07-13','[TRANSLATE-2137] - Translate files with InstantTranslate','Enable the possibility to switch of the file translation in InstantTranslate in the system configuration','',8,'feature','5.0.7'),
(715,'2020-07-13','[TRANSLATE-2035] - Add extra column to languageresource log table','The possibilities to show log messages in the GUI for language resources have been enhanced','',8,'bugfix','5.0.7'),
(716,'2020-07-13','[TRANSLATE-2047] - Errormessages on DB Update V 3.4.1','Update possibilites for very old instances has been enhanced','',8,'bugfix','5.0.7'),
(717,'2020-07-13','[TRANSLATE-2120] - Add missing DB constraint to Zf_configuration table','The DB structure has been enhanced','',8,'bugfix','5.0.7'),
(718,'2020-07-13','[TRANSLATE-2129] - Look for and solve open Javascript bugs (theRootCause)','Some Javascript errors reported by users have been fixed','',14,'bugfix','5.0.7'),
(719,'2020-07-13','[TRANSLATE-2131] - APPLICATON_PATH under Windows contains slash','Solve a path issue when loading plug-ins for Windows installations','',8,'bugfix','5.0.7'),
(720,'2020-07-13','[TRANSLATE-2132] - Kpi buttons are visible for editor only users','The button for showing KPIs is now not visible for translators andn reviewers any more, because he can not make use of it in a sense-making way anyway','',12,'bugfix','5.0.7'),
(721,'2020-07-13','[TRANSLATE-2134] - Remove document properties for MS Office and LibreOffice formats of default okapi','By default the document properties are not extracted any more for MS Offfice and Libre Office files','',12,'bugfix','5.0.7'),
(722,'2020-07-23','TRANSLATE-2139','Pre-translation exceptions','The error handling for integrated language resources has been improved',12,'change','5.0.7'),
(723,'2020-07-23','TRANSLATE-2117','LanguageResources: update & query segments with tags','For PangeaMT and NEC-TM the usage of internal tags was provided / fixed and a general mechanism for language resources for this issue introduced',12,'bugfix','5.0.7'),
(724,'2020-07-23','TRANSLATE-2127','Xliff files with file extension xml are passed to okapi instead of translate5s xliff parser','XML files that acutally contain XLIFF had been passed to Okapi instead of the translate5 xliff parser, if they startet with a BOM (Byte order mark)',12,'bugfix','5.0.7'),
(725,'2020-07-23','TRANSLATE-2138','Visual via URL does not work in certain cases','In some cases passing the layout via URL did not work',12,'bugfix','5.0.7'),
(726,'2020-07-23','TRANSLATE-2142','Missing property definition','A small fix',8,'bugfix','5.0.7'),
(727,'2020-07-23','TRANSLATE-2143','Problems Live-Editing: Shortened segments, insufficient whitespace','Major enhancements in the „What you see is what you get“ feature regarding whitespace handling and layout issues',14,'bugfix','5.0.7'),
(728,'2020-07-23','TRANSLATE-2144','Several problems with copy and paste content into an edited segment','',14,'bugfix','5.0.7'),
(729,'2020-07-23','TRANSLATE-2146','Exclude materialized view check in segments total count','A small fix',8,'bugfix','5.0.7'),
(730,'2020-08-05','TRANSLATE-2069','Show task-id and segment-id in URL and enable to access a task via URL (sponsored by Supertext)','A user is now able to send an URL that points to a certain segment of an opened task to another user and he will be able to automatically open the segment and scroll to the task alone via entering the URL (provided he has access rights to the task). This works also, if the user still has to log in and also if login works via OpenID Connect.',14,'feature','5.0.7'),
(731,'2020-08-05','TRANSLATE-2150','Disable default enabled workflow action finishOverduedTaskUserAssoc','Disable default enabled workflow action finishOverduedTaskUserAssoc',8,'change','5.0.7'),
(732,'2020-08-05','TRANSLATE-2159','Update Third-Party-Library Horde Text Diff','Include the up2date version of the used diff library',8,'change','5.0.7'),

(734,'2020-08-05','TRANSLATE-2148','Load module plugins only','A fix in the architecture of translate5',8,'bugfix','5.0.7'),
(735,'2020-08-05','TRANSLATE-2153','In some cases translate5 deletes spaces between segments','This refers to the visual layout representation of segments (not the actual translation)',14,'bugfix','5.0.7'),
(736,'2020-08-05','TRANSLATE-2155','Visual HTML fails on import for multi-target-lang project','Creating a mulit-lang project failed, when fetching the layout via URL',12,'bugfix','5.0.7'),
(737,'2020-08-05','TRANSLATE-2158','Reflect special whitespace characters in the layout','Entering linebreak, non-breaking-space and tabs in the segment effects now „What you see is what you get“ the layout',14,'bugfix','5.0.7'),
(738,'2020-09-07','TRANSLATE-1134','Jump to last edited/active segment','The last edited/active segment is selected again on reopening a task.',2,'feature','5.7.9'),
(739,'2020-09-07','TRANSLATE-2111','Make pop-up about \"Reference files available\" and \"Do you really want to finish\" pop-up configurable','Make pop-up abaout \"Reference files available\" and \"Do you really want to finish\" pop-up configurable',8,'feature','5.7.9'),
(740,'2020-09-07','TRANSLATE-2125','Split screen for Visual Editing (sponsored by Transline)','In Visual Editing the original and the modified is shown in two beneath windows.',2,'feature','5.7.9'),
(741,'2020-09-07','TRANSLATE-2113','Check if translate5 runs with latest MariaDB and MySQL versions','It was verified that translate5 can be installed and run with latest MariaDB and MySQL versions.',8,'change','5.7.9'),
(742,'2020-09-07','TRANSLATE-2122','Unify naming of InstantTranslate and TermPortal everywhere','Unify naming of InstantTranslate and TermPortal everywhere',8,'change','5.7.9'),
(743,'2020-09-07','TRANSLATE-2175','Implement maintenance command to delete orphaned data directories','With the brand new ./translate5.sh CLI command several maintenance tasks can be performed. See https://confluence.translate5.net/display/CON/CLI+Maintenance+Command',8,'change','5.7.9'),
(744,'2020-09-07','TRANSLATE-2189','Ignore segments with tags only in SDLXLIFF import if enabled','SDLXLIFF Import: If a segment contains only tags it is ignored from import. This is the default behaviour in native XLF import.',4,'change','5.7.9'),
(745,'2020-09-07','TRANSLATE-2025','Change default for runtimeOptions.segments.userCanIgnoreTagValidation to 0','Tag errors can now not ignored anymore on saving a segment. ',14,'change','5.7.9'),
(746,'2020-09-07','TRANSLATE-2163','Enhance documentation of Across termExport for translate5s termImport Plug-in','Enhance documentation of Across termExport for translate5s termImport Plug-in',8,'change','5.7.9'),
(747,'2020-09-07','TRANSLATE-2165','Make language resource timeout for PangeaMT configurable','Make language resource timeout for PangeaMT configurable',8,'change','5.7.9'),
(748,'2020-09-07','TRANSLATE-2179','Support of PHP 7.4 for translate5','Support of PHP 7.4 for translate5',8,'change','5.7.9'),
(749,'2020-09-07','TRANSLATE-2182','Change default colors for Matchrate Colorization in the VisualReview','Change default colors for Matchrate Colorization in the VisualReview',14,'change','5.7.9'),
(750,'2020-09-07','TRANSLATE-2184','OpenID Authentication: User info endpoint is unreachable','This is fixed.',8,'change','5.7.9'),
(751,'2020-09-07','TRANSLATE-2192','Move \"leave task\" button in simple mode to the upper right corner of the layout area','Move \"leave task\" button in simple mode to the upper right corner of the layout area',2,'change','5.7.9'),
(752,'2020-09-07','TRANSLATE-2199','Support more regular expressions in segment search','Support all regular expressions in segment search, that are possible based on MySQL 8 or MariaDB 10.2.3',14,'change','5.7.9'),
(753,'2020-09-07','TRANSLATE-2002','Translated PDF files should be named xyz.pdf.txt in the export package','Okapi may return translated PDF files only as txt files, so the file should be named .txt instead .pdf.',12,'bugfix','5.7.9'),
(754,'2020-09-07','TRANSLATE-2049','ERROR in core: E9999 - Action does not exist and was not trapped in __call()','Sometimes the above error occurred, this is fixed now.',8,'bugfix','5.7.9'),
(755,'2020-09-07','TRANSLATE-2062','Support html fragments as import files without changing the structure','This feature was erroneously disabled by a bconf change which is revoked right now.',8,'bugfix','5.7.9'),
(756,'2020-09-07','TRANSLATE-2149','Xliff import deletes part of segment and a tag','In seldom circumstances XLF content was deleted on import.',12,'bugfix','5.7.9'),
(757,'2020-09-07','TRANSLATE-2157','Company name in deadline reminder footer','The company name was added in the deadline reminder footer e-mail.',8,'bugfix','5.7.9'),
(758,'2020-09-07','TRANSLATE-2162','Task can not be accessed after open randomly','It happend randomly, that a user was not able to access a task after opening it. The error message was: You are not authorized to access the requested data. This is fixed.',14,'bugfix','5.7.9'),
(759,'2020-09-07','TRANSLATE-2166','Add help page for project and preferences overview','Add help page for project and preferences overview',8,'bugfix','5.7.9'),
(760,'2020-09-07','TRANSLATE-2167','Save filename with a save request to NEC-TM','A filenames is needed for later TMX export, so one filename is generated and saved to NEC-TM.',12,'bugfix','5.7.9'),
(761,'2020-09-07','TRANSLATE-2176','remove not race condition aware method in term import','A method in the term import was not thread safe.',8,'bugfix','5.7.9'),

(763,'2020-09-07','TRANSLATE-2187','Bad performance on loading terms in segment meta panel','Bad performance on loading terms in segment meta panel',14,'bugfix','5.7.9'),
(764,'2020-09-07','TRANSLATE-2188','Text in layout of xsl-generated html gets doubled','Text in layout of xsl-generated html gets doubled',8,'bugfix','5.7.9'),
(765,'2020-09-07','TRANSLATE-2190','PHP ERROR in core: E9999 - Cannot refresh row as parent is missing - fixed in DbDeadLockHandling context','In DbDeadLockHandling it may happen that on redoing the request a needed row is gone, this is no problem so far, so this error is ignored in that case.',8,'bugfix','5.7.9'),
(766,'2020-09-07','TRANSLATE-2191','Session Problem: Uncaught Zend_Session_Exception: Zend_Session::start()','Fixed this PHP error.',8,'bugfix','5.7.9'),
(767,'2020-09-07','TRANSLATE-2194','NEC-TM not usable in InstantTranslate','NEC-TM not usable in InstantTranslate',10,'bugfix','5.7.9'),
(768,'2020-09-07','TRANSLATE-2198','Correct spelling of \"Ressource(n)\" in German','Correct spelling of \"Ressource(n)\" in German',8,'bugfix','5.7.9'),
(769,'2020-09-07','TRANSLATE-2210','If a task is left, it is not focused in the project overview','This is fixed now',14,'bugfix','5.7.9'),
(770,'2020-09-16','TRANSLATE-1050','Save user customization of editor','The user may now change the visible columns and column positions and widths of the segment grid. This customizations are restored on next login.',14,'feature','5.7.9'),
(771,'2020-09-16','TRANSLATE-2071','VisualReview: XML with \"What you see is what you get\" via XSL transformation','A XML with a XSLT can be imported into translate5. The XML is then converted into viewable content in VisualReview.',4,'feature','5.7.9'),
(772,'2020-09-16','TRANSLATE-2111','Make pop-up about \"Reference files available\" and \"Do you really want to finish\" pop-up configurable','For both pop ups it is now configurable if they should be used and shown in the application.',8,'feature','5.7.9'),
(773,'2020-09-16','TRANSLATE-1793','search and replace: keep last search field or preset by workflow step.','The last searched field and content is saved and remains in the search window when it was closed.',2,'feature','5.7.9'),
(774,'2020-09-16','TRANSLATE-1617','Renaming of buttons on leaving a task','The label of the leave Button was changed.',6,'change','5.7.9'),
(775,'2020-09-16','TRANSLATE-2180','Enhance displayed text for length restrictions in the editor','The display text of the segment length restriction was changed.',2,'change','5.7.9'),
(776,'2020-09-16','TRANSLATE-2186','Implement close window button for editor only usage','To show that Button set runtimeOptions.editor.toolbar.hideCloseButton to 0. This button can only be used if translate5 was opened via JS window.open call.',8,'change','5.7.9'),
(777,'2020-09-16','TRANSLATE-2193','Remove \"log out\" button in editor','The user has first to leave the task before he can log out.',14,'change','5.7.9'),
(778,'2020-09-16','TRANSLATE-630','Enhance, when text filters of columns are send','When using a textfilter in a grid in the frontend, the user has to type very fast since the filters were sent really fast to the server. This is changed now.',2,'bugfix','5.7.9'),
(779,'2020-09-16','TRANSLATE-1877','Missing additional content and filename of affected file in E1069 error message','Error E1069 shows now also the filename and the affected characters.',8,'bugfix','5.7.9'),
(780,'2020-09-16','TRANSLATE-2010','Change tooltip of tasks locked because of excel export','The content of the tooltip was improved.',4,'bugfix','5.7.9'),
(781,'2020-09-16','TRANSLATE-2014','Enhance \"No results found\" message in InstantTranslate','Enhance \"No results found\" message in InstantTranslate',2,'bugfix','5.7.9'),
(782,'2020-09-16','TRANSLATE-2156','Remove \"Choose automatically\" option from drop-down, that chooses source or target for connecting the layout with','Since this was confusing users the option was removed and source is the new default',4,'bugfix','5.7.9'),
(783,'2020-09-16','TRANSLATE-2195','InstantTranslate filepretranslation API has a wrong parameter name','The parameter was 0 instead as documented in confluence.',8,'bugfix','5.7.9'),
(784,'2020-09-16','TRANSLATE-2215','VisualReview JS Error: me.down(...) is null','Error happend in conjunction with the usage of the action buttons in Visual Review.',8,'bugfix','5.7.9'),
(785,'2020-09-16','TRANSLATE-1031','Currently edited column in row editor is not aligned right','When scrolling horizontally in the segment grid, this could lead to positioning problems of the segment editor.',2,'bugfix','5.7.9'),
(786,'2020-10-06','TRANSLATE-2244','Embed translate5 guide video in help window','Embed the translate5 guide videos as iframe in the help window. The videos are either in german or english, they are chosen automatically depending on the GUI interface. A list of links to jump to specific parrs of the videos are provided.',8,'change','5.7.9'),
(787,'2020-10-06','TRANSLATE-2214','Change SSO Login Button Position','The SSO Login Button is now placed right of the login button instead between the login input field and the submit button.',8,'change','5.7.9'),
(788,'2020-10-06','TRANSLATE-1237','Exported xliff 2.1 is not valid','The XLF 2.1 output is now valid (validated against https://okapi-lynx.appspot.com/validation).',12,'change','5.7.9'),
(789,'2020-10-06','TRANSLATE-2243','Task properties panel stays enabled without selected task','Sometimes the task properties panel was enabled even when there is no task selected in the project tasks grid.',12,'bugfix','5.7.9'),
(790,'2020-10-06','TRANSLATE-2242','Source text translation in matches and concordance search grid','Change the German translation for matches and concordance search grid source column from: Quelltext to Ausgangstext.',6,'bugfix','5.7.9'),
(791,'2020-10-06','TRANSLATE-2240','PDF in InstantTranslate','Translating a PDF file with InstantTranslate document upload leads to a file with 0 bytes and file extension .pdf instead a TXT file named .pdf.txt. (like Okapi is producing it).',12,'bugfix','5.7.9'),
(792,'2020-10-06','TRANSLATE-2239','Installer is broken due zend library invocation change','The installer is broken since the the zend libraries were moved and integrated with the composer auto loader. Internally a class_exist is used which now returns always true which is wrong for the installation.',8,'bugfix','5.7.9'),
(793,'2020-10-06','TRANSLATE-2237','Auto state translations','Update some of the auto state translations (see image attached)',6,'bugfix','5.7.9'),
(794,'2020-10-06','TRANSLATE-2236','Change quality and state flags default values','Update the default value of the runtimeOptions.segments.stateFlags and runtimeOptions.segments.qualityFlags to more usable demo values.',15,'bugfix','5.7.9'),
(795,'2020-10-06','TRANSLATE-2235','Not all segmentation rules (SRX rules) in okapi bconf acutally are triggered','The reason seems to be, that all segment break=\"no\" rules of a language need to be above all break=\"yes\" rules, even if the break=\"yes\" rules do not interfere with the break=\"no\" rules.',12,'bugfix','5.7.9'),
(796,'2020-10-06','TRANSLATE-2234','Error on global customers filter','-',12,'bugfix','5.7.9'),
(797,'2020-10-06','TRANSLATE-2233','Remove autoAssociateTaskPm workflow action','Remove the autoAssociateTaskPm workflow functionality from the workflow action configuration and from the source code too.',12,'bugfix','5.7.9'),
(798,'2020-10-06','TRANSLATE-2232','Action button \"Associated tasks\" is visible for non TM resources','The action button for re-importing segments to tm in the language resource overview grid is visible for no tm resources (ex: the button is visible for mt resources). The button only should be visible for TM resources.',12,'bugfix','5.7.9'),
(799,'2020-10-06','TRANSLATE-2218','Trying to edit a segment with disabled editable content columns lead to JS error','Trying to edit a segment when all editable columns are hidden, was leading to a JS error.',15,'bugfix','5.7.9'),
(800,'2020-10-06','TRANSLATE-2173','Language resources without valid configuration should be shown with brackets in \"Add\" dialogue','Available but not configured LanguageResources are shown in the selection list in brackets.',12,'bugfix','5.7.9'),
(801,'2020-10-06','TRANSLATE-2075','Fuzzy-Selection of language resources does not work as it should','When working with language resources the mapping between the languages of the language resource and the languages in translate5 was improved, especially in matching sub-languages. For Details see the issue.',12,'bugfix','5.7.9'),
(802,'2020-10-06','TRANSLATE-2041','Tag IDs of created XLF 2 are invalid for importing in other CAT tools','The XLF 2.1 output is now valid (validated against https://okapi-lynx.appspot.com/validation).',12,'bugfix','5.7.9'),
(803,'2020-10-06','TRANSLATE-2011','translate 2 standard term attributes for TermPortal','Added the missing term-attribute translations.',12,'bugfix','5.7.9'),
(804,'2020-10-14','TRANSLATE-2246','Move the Ip based exception and the extended user model into the same named Plugin','Some code refactoring.',8,'change','5.7.9'),
(805,'2020-10-14','TRANSLATE-2259','Inconsistent workflow may lead in TaskUserAssoc Entity Not Found error when saving a segment.','The PM is allowed to set the Job associations as they want it. This may lead to an inconsistent workflow. One error when editing segments in an inconsistent workflow is fixed now.',15,'bugfix','5.7.9'),
(806,'2020-10-14','TRANSLATE-2258','Fix error E1161 \"The job can not be modified due editing by a user\" so that it is not triggered by viewing only users.','The above mentioned error is now only triggered if the user has opened the task for editing, before also a readonly opened task was triggering that error.',12,'bugfix','5.7.9'),
(807,'2020-10-14','TRANSLATE-2247','New installations save wrong mysql executable path (for installer and updater)','Fix a bug preventing new installations to be usable.',8,'bugfix','5.7.9'),
(808,'2020-10-14','TRANSLATE-2045','Use utf8mb4 charset for DB','Change all utf8 fields to the mysql datatype utf8mb4. ',8,'bugfix','5.7.9'),
(809,'2020-10-21','TRANSLATE-2279','Integrate git hook checks','Development: Integrate git hooks to validate source code.',8,'change','5.7.9'),
(810,'2020-10-21','TRANSLATE-2282','Mixing XLF id and rid values led to wrong tag numbering','When in some paired XLF tags the rid was used, and in others the id to pair the tags, this could lead to duplicated tag numbers.',6,'bugfix','5.7.9'),
(811,'2020-10-21','TRANSLATE-2280','OpenTM2 is not reachable anymore if TMPrefix configuration is empty','OpenTM2 installations were not reachable anymore from the application if the tmprefix was not configured. Empty tmprefixes are valid again.',8,'bugfix','5.7.9'),
(812,'2020-10-21','TRANSLATE-2278','Check if the searched text is valid for segmentation','Text segmentation and text segmentation search in instant-translate only will be done only when for the current search TM is available or risk-predictor (ModelFront) is enabled.',2,'bugfix','5.7.9'),
(813,'2020-10-21','TRANSLATE-2277','UserConfig value does not respect config data type','The UserConfig values did not respect the underlying configs data type, therefore the preferences of the repetition editor were not loaded correctly and the repetition editor did not come up.',6,'bugfix','5.7.9'),
(814,'2020-10-21','TRANSLATE-2265','Microsoft translator directory lookup change','Solves the problem that microsoft translator does not provide results when searching text in instant translate with more then 5 characters.',15,'bugfix','5.7.9'),
(815,'2020-10-21','TRANSLATE-2264','Relative links for instant-translate file download','Fixed file file download link in instant translate when the user is accessing translate5 from different domain.',12,'bugfix','5.7.9'),
(816,'2020-10-21','TRANSLATE-2263','Do not use ExtJS debug anymore','Instead of using the debug version of ExtJS now the normal one is used. This reduces the initial load from 10 to 2MB.',8,'bugfix','5.7.9'),
(817,'2020-10-21','TRANSLATE-2262','Remove sensitive data of API endpoint task/userlist','The userlst needed for filtering in the task management exposes the encrypted password.',8,'bugfix','5.7.9'),
(818,'2020-10-21','TRANSLATE-2261','Improve terminology import performance','The import performance of large terminology was really slow, by adding some databases indexes the imported was boosted. ',12,'bugfix','5.7.9'),
(819,'2020-10-21','TRANSLATE-2260','Visual Review: Normalizing whitespace when comparing segments for content-align / pivot-language','Whitespace will now be normalized when aligned visuals in the visual review or pivot languages are validated against the segments ',12,'bugfix','5.7.9'),
(820,'2020-10-21','TRANSLATE-2252','Reapply tooltip over processing status column','The tool-tips were changed accidentally and are restored now.',6,'bugfix','5.7.9'),
(821,'2020-10-21','TRANSLATE-2251','Reapply \"Red bubble\" to changed segments in left side layout of split screen','The red bubble representing edited segments will now also show in the left (unedited) frame of the split-view of the visual review',6,'bugfix','5.7.9'),
(822,'2020-10-21','TRANSLATE-2250','Also allow uploading HTML for VisualReview','Since it is possible to put HTML files as layout source in the visual folder of the zip import package, selecting an HTML file in the GUI should be allowed, too.',12,'bugfix','5.7.9'),
(823,'2020-10-21','TRANSLATE-2245','Switch analysis to batch mode, where language resources support it','Sending multiple segment per request when match analysis and pre-translation is running now can be configured in (default enabled): runtimeOptions.plugins.MatchAnalysis.enableBatchQuery; Currently this is supported by the following language resources: Nectm, PangeaMt, Microsoft, Google, DeepL',12,'bugfix','5.7.9'),
(824,'2020-10-21','TRANSLATE-2220','XML/XSLT import for visual review: Filenames may not be suitable for OKAPI processing','FIX: Any filenames e.g. like \"File (Kopie)\" now can be processed, either as aligned XML/XSLT file or with a direct XML/XSL import ',12,'bugfix','5.7.9'),
(825,'2020-11-17','TRANSLATE-2225','Import filter for special Excel file format containing texts with special length restriction needs','A client specific import filter for a data in a client specific excel file format.',12,'feature','5.7.9'),
(826,'2020-11-17','TRANSLATE-2296','Improve Globalese integration to work with project feature','Fix Globalese integration with latest translate5.',12,'change','5.7.9'),
(827,'2020-11-17','TRANSLATE-2313','InstantTranslate: new users sometimes can not use InstantTranslate','New users are sometimes not able to use instanttranslate. That depends on the showSubLanguages config and the available languages.',8,'bugfix','5.7.9'),
(828,'2020-11-17','TRANSLATE-2312','Can\'t use \"de\" anymore to select a target language','In project creation target language field type \"(de)\" and you get no results. Instead typing \"Ger\" works. The first one is working now again.',12,'bugfix','5.7.9'),
(829,'2020-11-17','TRANSLATE-2311','Cookie Security','Set the authentication cookie according to the latest security recommendations.',8,'bugfix','5.7.9'),
(830,'2020-11-17','TRANSLATE-2308','Disable webserver directory listing','The apache directory listing is disabled for security reasons in the .htaccess file.',8,'bugfix','5.7.9'),
(831,'2020-11-17','TRANSLATE-2307','Instanttranslate documents were accessable for other users','Instanttranslate documents could be accessed from other users by guessing the task id in the URL.',8,'bugfix','5.7.9'),
(832,'2020-11-17','TRANSLATE-2306','Rename \"Continue task later\" button','The button in the editor to leave a task (formerly \"Leave task\"), which is currently labeled \"Continue task later\" is renamed to \"Back to task list\" as agreed in monthly meeting.',15,'bugfix','5.7.9'),
(833,'2020-11-17','TRANSLATE-2293','Custom panel is not state full','The by default disabled custom panel is now also stateful.',8,'bugfix','5.7.9'),
(834,'2020-11-17','TRANSLATE-2288','Reduce translate5.zip size to decrease installation time','The time needed for an update of translate5 depends also on the package size. The package was blown up in the last time, now the size is reduced again.',8,'bugfix','5.7.9'),
(835,'2020-11-17','TRANSLATE-2287','Styles coming from plugins are added multiple times to the HtmlEditor','Sometimes the content styles of the HTML Editor are added multiple times, this is fixed.',8,'bugfix','5.7.9'),
(836,'2020-11-17','TRANSLATE-2265','Microsoft translator directory lookup change','Solves the problem that microsoft translator does not provide results when searching text in instant translate with more then 5 characters.',15,'bugfix','5.7.9'),
(837,'2020-11-17','TRANSLATE-2224','Deleted tags in TrackChanges do not really look deleted','FIX: Deleted tags in TrackChanges in the HTML-Editor now look deleted as well (decorated with a strike-through)',15,'bugfix','5.7.9'),
(838,'2020-11-17','TRANSLATE-2172','maxNumberOfLines currently only works for pixel-length and not char-length checks','Enabling line based length check also for length unit character.',12,'bugfix','5.7.9'),
(839,'2020-11-17','TRANSLATE-2151','Visual Editing: If page grows to large (gets blue footer) and had  been zoomed, some visual effects do not work, as they should','Fixed inconsistencies with the Text-Reflow and especially the page-growth colorization when zooming the visual review. Pages now keep their grown size  when scrolling them out of view & back.',15,'bugfix','5.7.9'),
(840,'2020-11-17','TRANSLATE-1034','uploading file bigger as post_max_size or upload_max_filesize gives no error message, just a empty window','If uploading a file bigger as post_max_size or upload_max_filesize gives an error message is given now.',8,'bugfix','5.7.9'),
(841,'2020-12-21','TRANSLATE-2249','Length restriction for sdlxliff files','SDLXLIFF specific length restrictions are now read out and used for internal processing.',12,'feature','5.7.9'),
(842,'2020-12-21','TRANSLATE-2343','Enhance links from default skin to www.translate5.net','Change links from default skin to www.translate5.net',8,'change','5.7.9'),
(843,'2020-12-21','TRANSLATE-390','Prevent that the same error creates a email on each request to prevent log spam','Implemented the code base to recognize duplicated errors and prevent sending error mails.',8,'change','5.7.9'),
(844,'2020-12-21','TRANSLATE-2353','OpenTM2 strange matching of single tags','In the communication with OpenTM2 the used tags are modified to improve found matches.',15,'bugfix','5.7.9'),
(845,'2020-12-21','TRANSLATE-2346','Wrong Tag numbering on using language resources','If a segment containing special characters and is taken over from a language resource, the tag numbering could be messed up. This results then in false positive tag errors.',15,'bugfix','5.7.9'),
(846,'2020-12-21','TRANSLATE-2339','OpenTM2 can not handle  datatype=\"unknown\" in TMX import','OpenTM2 does not import any segments from a TMX, that has  datatype=\"unknown\" in its header tag, this is fixed by modifying the TMX on upload.',12,'bugfix','5.7.9'),
(847,'2020-12-21','TRANSLATE-2338','Use ph tag in OpenTM2 to represent line-breaks','In the communication with OpenTM2 line-breaks are converted to ph type=\"lb\" tags, this improves the matchrates for affected segments.',15,'bugfix','5.7.9'),
(848,'2020-12-21','TRANSLATE-2336','Auto association of language resources does not use language fuzzy match','Now language resources with a sub-language (de-de, de-at) are also added to tasks using only the base language (de). ',12,'bugfix','5.7.9'),
(849,'2020-12-21','TRANSLATE-2334','Pressing ESC while task is uploading results in task stuck in status import','Escaping from task upload window while uploading is now prevented.',12,'bugfix','5.7.9'),
(850,'2020-12-21','TRANSLATE-2332','Auto user association on task import does not work anymore','Auto associated users are added now again, either as translators or as revieweres depending on the nature of the task.',12,'bugfix','5.7.9'),
(851,'2020-12-21','TRANSLATE-2328','InstantTranslate: File upload will not work behind a proxy','InstantTranslate file upload may not work behind a proxy, depending on the network configuration. See config worker.server.',8,'bugfix','5.7.9'),
(852,'2020-12-21','TRANSLATE-2294','Additional tags from language resources are not handled properly','The tag and whitespace handling of all language resources are unified and fixed, regarding to missing or additional tags.',12,'bugfix','5.7.9'),
(853,'2021-02-02','TRANSLATE-2385','introduce user login statistics','Now the login usage of the users is tracked in the new Zf_login_log table.',15,'feature','5.7.9'),
(854,'2021-02-02','TRANSLATE-2374','Time of deadlines also visible in grid columns and notification mails','The date-time is now visible in the translate5 interface for date fields(if the time is relevant for this date field), and also in the mail templates.',15,'feature','5.7.9'),
(855,'2021-02-02','TRANSLATE-2362','HTML / XML tag protection of tags in any kind of file format','XLF and CSV files can now contain HTML content (CSV: plain, XLF: encoded), the  HTML tags are protected as internal tags. This must be enabled in the config for the affected tasks.',15,'feature','5.7.9'),
(856,'2021-02-02','TRANSLATE-471','Overwrite system config by client and task','Adds possibility to overwrite system configuration on 4 different levels: system, client, task import and task overwrite,',15,'feature','5.7.9'),
(857,'2021-02-02','TRANSLATE-2368','Add segment matchrate to Xliff 2 export as translate5 namespaced element','Each segment in the xliff 2 export will have the segment matchrate as translate5 namespace attribute.',15,'change','5.7.9'),
(858,'2021-02-02','TRANSLATE-2357','introduce DeepL config switch \"formality\"','The \"formality\" deepl api flag now is available as task import config.\nMore about the formality flag:\n\nSets whether the translated text should lean towards formal or informal language. This feature currently works for all target languages except \"EN\" (English), \"EN-GB\" (British English), \"EN-US\" (American English), \"ES\" (Spanish), \"JA\" (Japanese) and \"ZH\" (Chinese).\nPossible options are:\n\"default\" (default)\n\"more\" - for a more formal language\n\"less\" - for a more informal language',15,'change','5.7.9'),
(859,'2021-02-02','TRANSLATE-2354','Add language code to filename of translate5 export zip','When exporting a task, in the exported zip file name, the task source and target language codes are included.',15,'change','5.7.9'),
(860,'2021-02-02','TRANSLATE-1120','Change default values of several configuration parameters','The default value in multiple system configurations is changed.',15,'change','5.7.9'),
(861,'2021-02-02','TRANSLATE-929','Move old task template values to new system overwrite','The task template parameters definition moved to system configuration.',15,'change','5.7.9'),
(862,'2021-02-02','TRANSLATE-2384','Okapi does not always fill missing targets with source content','In some use cases only a few segments are translated, and on export via Okapi the not translated segments are filled up by copying the source content to target automatically. This copying was failing for specific segments.',15,'bugfix','5.7.9'),
(863,'2021-02-02','TRANSLATE-2382','ERROR in core.api.filter: E1223 - Illegal field \"customerUseAsDefaultIds\" requested','Sometimes it may happen that a filtering for customers used as default in the language resource grid leads to the above error message. This is fixed now.',15,'bugfix','5.7.9'),
(864,'2021-02-02','TRANSLATE-2373','Prevent termtagger usage if source and target language are equal','FIX: Prevent termtagger hanging when source and target language of a task are identical. Now in these cases the terms are not tagged anymore',15,'bugfix','5.7.9'),
(865,'2021-02-02','TRANSLATE-2372','Whitespace not truncated InstantTranslate text input field','All newlines, spaces (including non-breaking spaces), and tabs are removed from the beginning and the end of the searched string in instant translate.',15,'bugfix','5.7.9'),
(866,'2021-02-02','TRANSLATE-2367','NoAccessException directly after login','Opening Translate5 with an URL containing a task to be opened for editing leads to ZfExtended_Models_Entity_NoAccessException exception if the task was already finished or still in state waiting instead of opening the task in read only mode.',15,'bugfix','5.7.9'),
(867,'2021-02-02','TRANSLATE-2365','Help window initial size','On smaller screens the close button of the help window (and also the \"do not show again\" checkbox) were not visible.',15,'bugfix','5.7.9'),
(868,'2021-02-02','TRANSLATE-2352','Visual: Repetitions are linked to wrong position in the layout','FIXED: Problem in Visual Review that segments pointing to multiple occurances in the visual review always jumped to the first occurance when clicking on the segment in the segment grid. Now the current context (position of segment before, scroll-position of review) is taken into account',15,'bugfix','5.7.9'),
(869,'2021-02-02','TRANSLATE-2351','Preserve \"private use area\" of unicode characters in visual review and ensure connecting segments','Characters of the Private Use Areas (as used in some symbol fonts e.g.) are now preserved in the Visual Review layout',15,'bugfix','5.7.9'),
(870,'2021-02-02','TRANSLATE-2335','Do not query MT when doing analysis in batch mode without MT pre-translation','When the MT pre-translation checkbox is not checked in the match analysis overview, and batch query is enabled, all associated MT resources will not be used for batch query.',15,'bugfix','5.7.9'),
(871,'2021-02-02','TRANSLATE-2311','Cookie Security','Set the authentication cookie according to the latest security recommendations.',15,'bugfix','5.7.9'),
(872,'2021-02-04','TRANSLATE-2383','OpenTM2 workaround to import swiss languages','Since OpenTM2 is not capable of importing sub languages we have to provide fixes on demand. Here de-CH, it-CH and fr-CH are fixed.',15,'feature','5.7.9'),
(873,'2021-02-17','TRANSLATE-1484','Count translated characters by MT engine and customer','Enables language resources usage log and statistic export.',15,'feature','5.7.9'),
(874,'2021-02-17','TRANSLATE-2407','Embed new configuration help window','The brand-new help videos about the configuration possibilities are available now and embedded in the application as help pop-up.',15,'change','5.7.9'),
(875,'2021-02-17','TRANSLATE-2402','Remove rights for PMs to change instance defaults for configuration','The PM will not longer be able to modify instance level configurations, only admin users may do that.',15,'change','5.7.9'),
(876,'2021-02-17','TRANSLATE-2379','Workflow mails: Show only changed segments','Duplicating TRANSLATE-1979',15,'change','5.7.9'),
(877,'2021-02-17','TRANSLATE-2406','Translated text is not replaced with translation but concatenated','FIX: Solved problem where the Live editing did not remove the original text completely when replacing it with new contents',15,'bugfix','5.7.9'),
(878,'2021-02-17','TRANSLATE-2403','Visual Review: Images are missing, the first Image is not shown in one Iframe','FIX: A downloaded Website for the Visual Review may not show responsive images when they had a source set defined\nFIX: Elements with a background-image set by inline style in a downloaded website for the Visual Review may not show the background image\nFIX: Some images were not shown either in the original iframe or the WYSIWIG iframe in a Visual Review\nENHANCEMENT: Focus-styles made the current page hard to see in the Visual Review pager ',15,'bugfix','5.7.9'),
(879,'2021-02-17','TRANSLATE-2401','DeepL formality fallback','Formality will be set to \"default\" for resources with unsupported target languages.',15,'bugfix','5.7.9'),
(880,'2021-02-17','TRANSLATE-2396','Diverged GUI and Backend version after update','The user gets an error message if the version of the GUI is older as the backend - which may happen after an update in certain circumstances. Normally this is handled due the usage of the maintenance mode.',15,'bugfix','5.7.9'),
(881,'2021-02-17','TRANSLATE-2391','\"Complete task?\". Text in dialog box is confusing.','The \"Complete task?\" text in the pop-up dialog was changed since it was confusing.',15,'bugfix','5.7.9'),
(882,'2021-02-17','TRANSLATE-2390','TermImport plug-in matches TermCollection name to non-Termcollection-type languageresources','The termImport plug-in imports a TBX into an existing termCollection, if the name is the same as the one specified in the plug-in config file. Although the language resource type was not checked, so this led to errors if the found language resource was not of type term collection.',15,'bugfix','5.7.9'),
(883,'2021-02-17','TRANSLATE-1979','Do not list \"changes\" of translator in mail send after finish of translation step','The changed segments will not longer be listed in the notification mails after translation step is finished - since all segments were changed here.',15,'bugfix','5.7.9'),
(884,'2021-03-31','TRANSLATE-2412','Create a shortcut to directly get into the concordance search bar','New editor shortcut (F3) to get the cursor in \"concordance search\" source field.',15,'feature','5.7.9'),
(885,'2021-03-31','TRANSLATE-2375','Set default deadline per workflow step in configuration','Define default deadline date for task-user association',15,'feature','5.7.9'),
(886,'2021-03-31','TRANSLATE-2342','Show progress of document translation','Import progress bar in instant translate file translation and in the task overview.',15,'feature','5.7.9'),

(888,'2021-03-31','TRANSLATE-2446','Fonts Management for Visual: Add search capabilities by name / taskname','ENHANCEMENT: Added search-field to search for fonts by task name in the font management',15,'change','5.7.9'),
(889,'2021-03-31','TRANSLATE-2440','Project task backend tests','Implement API tests testing the import of multiple tasks bundled in a project (one source language, multiple target languages).',15,'change','5.7.9'),
(890,'2021-03-31','TRANSLATE-2424','Add Language as label under Language Flag image','TermPortal - added language label to language flag to display RFC language.',15,'change','5.7.9'),
(891,'2021-03-31','TRANSLATE-2350','Make configurable if pivot language should be available in add task wizard','The availability / visibility of the pivot language in the add task wizard can be configured in the configuration for each customer now.',15,'change','5.7.9'),
(892,'2021-03-31','TRANSLATE-2248','Change name of \"visualReview\" folder to \"visual\"','The \"visualReview\" folder in the zip import package is deprecated from now on. In the future please always use the new folder \"visual\" instead. All files that need to be reviewed or translated will have to be placed in the new folder \"visual\" from now on. In some future version of translate5 the support for \"visualReview\" folder will be completely removed. Currently it still is supported, but will write a \"deprecated\" message to the php error-log.',15,'change','5.7.9'),
(893,'2021-03-31','TRANSLATE-1925','BUG: Workers running parallelism is not implemented correctly','Enhancement: Setting more workers to \"waiting\" in the \"wakeupScheduled\" call independently of the calling worker to improve the parallelism of running workers',15,'change','5.7.9'),
(894,'2021-03-31','TRANSLATE-1596','Change name of \"proofRead\" folder to \"workfiles\"','The \"proofRead\" folder in the zip import package is deprecated from now on. In the future please always use the new folder \"workfiles\" instead. All files that need to be reviewed or translated will have to be placed in the new folder \"workfiles\" from now on. In some future version of translate5 the support for \"proofRead\" folder will be completely removed. Currently it still is supported, but will write a \"deprecated\" message to the php error-log.',15,'change','5.7.9'),
(895,'2021-03-31','TRANSLATE-2456','Quote in task name produces error','Fixed problem with language resources to task association when the task name contains single or double quotes.',15,'bugfix','5.7.9'),
(896,'2021-03-31','TRANSLATE-2454','Configuration userCanModifyWhitespaceTags is not loaded properly','Users were not able to save segments with changed whitespace tags, since the corresponding configuration which allows this was not loaded properly.',15,'bugfix','5.7.9'),
(897,'2021-03-31','TRANSLATE-2453','Fix unescaped control characters in language resource answers','Solving the the following error coming from OpenTM2: ERROR in editor.languageresource.service.connector: E1315 - JSON decode error: Control character error, possibly incorrectly encoded',15,'bugfix','5.7.9'),
(898,'2021-03-31','TRANSLATE-2451','Fix description text of lock segment checkbox and task column','Clarify that feature \"Locked segments in the imported file are also locked in translate5\" is for SDLXLIFF files only.',15,'bugfix','5.7.9'),
(899,'2021-03-31','TRANSLATE-2449','Grid grouping feature collapse/expand error','Fixes error with collapse/expand in locally filtered config grid.',15,'bugfix','5.7.9'),
(900,'2021-03-31','TRANSLATE-2448','Unable to refresh entity after save','Fixing an error which may occur when using pre-translation with enabled batch mode of language resources.',15,'bugfix','5.7.9'),
(901,'2021-03-31','TRANSLATE-2445','Unknown bullet prevents proper segmentation','FIX: Added some more bullet characters to better filter out list markup during segmentation\nFIX: Priorize longer segments during segmentation to prevent segments containing each other (e.g. \"Product XYZ\", \"Product XYZ is good\") can not be found properly.',15,'bugfix','5.7.9'),
(902,'2021-03-31','TRANSLATE-2442','Disabled connectors and repetitions','Fixing a problem with repetitions in match analysis and pre-translation context, also a repetition column is added in resource usage log excel export.',15,'bugfix','5.7.9'),
(903,'2021-03-31','TRANSLATE-2441','HTML Cleanup in Visual Review way structurally changed internal tags','FIXED: Segments with interleaving term-tags and internal-tags may were not shown properly in the visual review (parts of the text missing).',15,'bugfix','5.7.9'),
(904,'2021-03-31','TRANSLATE-2438','Fix plug-in XlfExportTranslateByAutostate for hybrid usage of translate5','The XlfExportTranslateByAutostate plug-in was designed for t5connect only, a hybrid usage of tasks directly uploaded and exported to and from translate5 was not possible. This is fixed now.',15,'bugfix','5.7.9'),
(905,'2021-03-31','TRANSLATE-2435','Add reply-to with project-manager mail to all automated workflow-mails','In all workflow mails, the project manager e-mail address is added as reply-to mail address.',15,'bugfix','5.7.9'),
(906,'2021-03-31','TRANSLATE-2433','file extension XLF can not be handled - xlf can','Uppercase file extensions (XLF instead xlf) were not imported. This is fixed now.',15,'bugfix','5.7.9'),
(907,'2021-03-31','TRANSLATE-2432','Make default bconf path configurable','More flexible configuration for Okapi import/export .bconf files changeable per task import.',15,'bugfix','5.7.9'),
(908,'2021-03-31','TRANSLATE-2428','Blocked segments and task word count','Include or exclude the blocked segments from task total word count and match-analysis when enabling or disabling  \"100% matches can be edited\" task flag.',15,'bugfix','5.7.9'),
(909,'2021-03-31','TRANSLATE-2427','Multiple problems with worker related to match analsis and pretranslation','A combination of multiple problems led to hanging workers when importing a project with multiple targets and activated pre-translation.',15,'bugfix','5.7.9'),
(910,'2021-03-31','TRANSLATE-2426','Term-tagging with default term-collection','term-tagging was not done with term collection assigned as default for the project-task customer',15,'bugfix','5.7.9'),
(911,'2021-03-31','TRANSLATE-2425','HTML Import does not work properly when directPublicAccess not set','FIX: Visual Review does not show files from subfolders of the review-directory when directPublicAccess is not active (Proxy-access)',15,'bugfix','5.7.9'),
(912,'2021-03-31','TRANSLATE-2423','Multicolumn CSV import was not working anymore in some special cases','Multicolumn CSV import with multiple files and different target columns was not working anymore, this is fixed now.',15,'bugfix','5.7.9'),
(913,'2021-03-31','TRANSLATE-2421','Worker not started due maintenance should log to the affected task','If a worker is not started due maintenance, this should be logged to the affected task if possible.',15,'bugfix','5.7.9'),
(914,'2021-03-31','TRANSLATE-2420','Spelling mistake: Task finished, E-mail template','Spelling correction.',15,'bugfix','5.7.9'),
(915,'2021-03-31','TRANSLATE-2413','Wrong E-Mail encoding leads to SMTP error with long segments on some mail servers','When finishing a task an email is sent to the PM containing all edited segments. If there are contained long segments, or segments with a lot of tags with long content, this may result on some mail servers in an error. ',15,'bugfix','5.7.9'),
(916,'2021-03-31','TRANSLATE-2411','Self closing g tags coming from Globalese pretranslation can not be resolved','Globalese receives a segment with a <g>tag</g> pair, but returns it as self closing <g/> tag, which is so far valid XML but could not be resolved by the reimport of the data.',15,'bugfix','5.7.9'),
(917,'2021-03-31','TRANSLATE-2325','TermPortal: Do not show unknown tag name in the attribute header.','Do not show tag name any more in TermPortal for unkown type-attribute values and other attribute values',15,'bugfix','5.7.9'),
(918,'2021-03-31','TRANSLATE-2256','Always activate button \"Show/Hide TrackChanges\"','Show/hide track changes checkbox will always be available (no matter on the workflow step)',15,'bugfix','5.7.9'),
(919,'2021-03-31','TRANSLATE-198','Open different tasks if editor is opened in multiple tabs','The user will no longer be allowed to edit 2 different tasks using 2 browser tabs. ',15,'bugfix','5.7.9'),
(920,'2021-04-15','TRANSLATE-2363','Development tool session:impersonate accessible via api','Enables an API user to authenticate in a name of different user. This feature is only available via translate5 API and for users with api role. More info you can find here : https://confluence.translate5.net/display/TAD/Session',15,'feature','5.7.9'),
(921,'2021-04-15','TRANSLATE-2471','Auto-assigned users and deadline-date','Fixes missing deadline date for auto assigned users.',15,'bugfix','5.7.9'),
(922,'2021-04-15','TRANSLATE-2470','Errors on log mail delivery stops whole PHP process','Errors on log e-mail delivery stops whole application process and leads to additional errors. The originating error is not logged in the translate5 log, only in the PHP log.',15,'bugfix','5.7.9'),
(923,'2021-04-15','TRANSLATE-2468','Instant-translate custom title','Enables instant-translate custom title definition in client-specific locales.',15,'bugfix','5.7.9'),
(924,'2021-04-15','TRANSLATE-2467','RootCause Error \"Cannot read property \'nodeName\' of null\"','Fixed Bug in TrackChanges when editing already edited segments',15,'bugfix','5.7.9'),
(925,'2021-04-15','TRANSLATE-2465','Add version parameter to instanttranslate and termportal assets','The web assets (CSS and JS files) were not probably updated in termportal and instanttranslate after an update.',15,'bugfix','5.7.9'),
(926,'2021-04-15','TRANSLATE-2464','Tag protection feature does not work if content contains XML comments or CDATA blocks','The tag protection feature was not working properly if the content contains XML comments or CDATA blocks.',15,'bugfix','5.7.9'),
(927,'2021-04-15','TRANSLATE-2463','Match analysis and batch worker fix','Fixes that machine translation engines were queried to often with enabled batch quries and projects with multiple target languages and some other minor problems with match analysis and batch query workers.',15,'bugfix','5.7.9'),
(928,'2021-04-15','TRANSLATE-2461','Non Public Plugin Classes referenced in public code','Pure public translate5 installations were not usable due a code reference to non public code.',15,'bugfix','5.7.9'),
(929,'2021-04-15','TRANSLATE-2459','Segments grid scroll-to uses private function','Segments grid scroll to segment function improvement.',15,'bugfix','5.7.9'),
(930,'2021-04-15','TRANSLATE-2458','Reenable logout on window close also for open id users','Currently the logout on window close feature is not working for users logging in via OpenID connect.',15,'bugfix','5.7.9'),
(931,'2021-04-15','TRANSLATE-2457','Globalese engines string IDs crash translate5 task import wizard','Globalese may return also string based engine IDs, translate5 was only supporting integer ids so far.',15,'bugfix','5.7.9'),
(932,'2021-04-15','TRANSLATE-2431','Errors on update with not configured mail server','If there is no e-mail server configured, the update shows an error due missing SMTP config.',15,'bugfix','5.7.9'),
(933,'2021-05-31','TRANSLATE-2417','OpenTM: writeable by default','The default enabled for configuration of language resources is now split up into read default rights and write default rights, so that reading and writing is configurable separately. The write default right is not automatically set for existing language resources.\nThe old API field \"resourcesCustomersHidden\" in language-resources to customers association will no longer be supported. It was marked as deprecated since April 2020. Please use only customerUseAsDefaultIds from now on.',15,'feature','5.7.9'),
(934,'2021-05-31','TRANSLATE-2315','Filtering for Repeated segments in translate5s editor','Added a filter in the segments grid to filter for repeated segments.',15,'feature','5.7.9'),
(935,'2021-05-31','TRANSLATE-2196','Complete Auto QA for translate5','Introduces a new Quality Assurance:\n\n* Panel to filter the Segment Grid by quality\n* GUI to set evaluated quality errors as \"false positives\"\n* Improved panels to set the manual QA for the whole segment and within the segment (now independant from saving edited segment content)\n* Automatic evaluation of several quality problems (AutoQA)\n\nFor an overview how to use the new feature, please see https://confluence.translate5.net/pages/viewpage.action?pageId=557218\n\nFor an overview of the new REST API, please see https://confluence.translate5.net/pages/viewpage.action?pageId=256737288',15,'feature','5.7.9'),
(936,'2021-05-31','TRANSLATE-2077','Offer export of Trados-Style analysis xml','The match analysis report can be exported now in a widely usable XML format.',15,'feature','5.7.9'),
(937,'2021-05-31','TRANSLATE-2494','Plugins enabled by default','Enables ModelFront, IpAuthentication and PangeaMt plugins to be active by default.',15,'change','5.7.9'),
(938,'2021-05-31','TRANSLATE-2481','Enable default deadline in configuration to be also decimal values (number of days in the future)','Default deadline date configuration accepts decimal number as configuration. You will be able to define 1 and a half day for the deadline when setting the config to 1.5',15,'change','5.7.9'),
(939,'2021-05-31','TRANSLATE-2473','Show language names in language drop downs in InstantTranslate','The languages drop-down in instant translate will now show the full language name + language code',15,'change','5.7.9'),
(940,'2021-05-31','TRANSLATE-2527','Remove instant-Translate default rest api routes','The default rest-routes in instant translate are removed.',15,'bugfix','5.7.9'),
(941,'2021-05-31','TRANSLATE-2517','NULL as string in Zf_configuration defaults instead real NULL values','Some default values in the configuration are not as expected.',15,'bugfix','5.7.9'),
(942,'2021-05-31','TRANSLATE-2515','Remove the limit from customers drop-down','Fixes the customer limit in language resources customers combobox.',15,'bugfix','5.7.9'),
(943,'2021-05-31','TRANSLATE-2511','PHP error on deleting tasks','Fixed seldom problem on deleting tasks:\nERROR in core: E9999 - Argument 1 passed to editor_Models_Task_Remover::cleanupProject() must be of the type int, null given',15,'bugfix','5.7.9'),
(944,'2021-05-31','TRANSLATE-2509','Bugfix: target \"_blank\" in Links in the visual review causes unwanted popups with deactivated links','External Links opening a new window still cause unwanted popups in the Visual Review',15,'bugfix','5.7.9'),
(945,'2021-05-31','TRANSLATE-2499','Search window saved position can be moved outside of the viewport','Search window saved position can be moved outside of the viewport and the user is then not able to move it back. This is fixed now for the search window, for other windows the bad position is not saved, so after reopening the window it is accessible again.\nAlso fixed logged configuration changes, always showing old value the system value instead the overwritten level value.',15,'bugfix','5.7.9'),
(946,'2021-05-31','TRANSLATE-2496','Enable target segmentation in Okapi','So far target segmentation had not been activated in okapi segmentation settings. For PO files with partly existing target this let to <mrk>-segment tags in the source, but not in the target and thus to an import error in translate5. This is changed now.\n',15,'bugfix','5.7.9'),
(947,'2021-05-31','TRANSLATE-2484','Buffered grid \"ensure visible\" override','Fixes problems with the segment grid.',15,'bugfix','5.7.9'),
(948,'2021-05-31','TRANSLATE-2482','Serialization failure: 1213 Deadlock found when trying to get lock','Fixes update worker progress mysql deadlock.',15,'bugfix','5.7.9'),
(949,'2021-05-31','TRANSLATE-2480','Instant-translate expired user session','On expired session, the user will be redirected to the login page in instant translate or term portal.',15,'bugfix','5.7.9'),
(950,'2021-05-31','TRANSLATE-2478','Add missing languages','Adds additional languages: \nsr-latn-rs, so-so, am-et, es-419, rm-ch, es-us, az-latn-az, uz-latn-uz, bs-latn-ba',15,'bugfix','5.7.9'),
(951,'2021-05-31','TRANSLATE-2455','Empty Segment Grid after opening a task','Fixing a seldom issue where the segment grid remains empty after opening a task.',15,'bugfix','5.7.9'),
(952,'2021-05-31','TRANSLATE-2439','prevent configuration mismatch on level task-import','Task import specific configurations are now fixed after the task import and can neither be changed for the rest of the task\'s lifetime nor can they be overwritten otherwise',15,'bugfix','5.7.9'),
(953,'2021-05-31','TRANSLATE-2410','Add Warning for users editing Korean, Vietnamese or Japanese tasks when working with Firefox','This was no bug in translate5 - everything correct here - but a problem with Firefox on Windows for Korean and Vietnamese, preventing the users to enter Asiatic characters. Translate5 users with Korean or Vietnamese target language will get a warning message now, that they should switch to Chrome or Edge.',15,'bugfix','5.7.9'),
(954,'2021-05-31','TRANSLATE-1643','A separate autostatus pretranslated is missing for pretranslation','Introduced new processing state (AutoStatus) \"pretranslated\".\nThis state is used for segments pre-translated in translate5, but also for imported segments which provide such information. For example SDLXLIFF: \nif edit100%percentMatch is disabled, used full TM matches not edited in Trados manually are not editable. So edited 100% matches are editable in translate5 by the reviewer now. Not changed has the behaviour for auto-propagated segments and segments with a match-rate < 100%: they are still editable as usual.',15,'bugfix','5.7.9'),
(955,'2021-05-31','TRANSLATE-1481','Improve tag handling with matches coming from OpenTM2','The TMX files imported into OpenTM2 are modified. The internal tags are modified (removing type attribute and convert tags with content to single placeholder tags) to improve matching when finding segments.',15,'bugfix','5.7.9'),
(956,'2021-06-08','TRANSLATE-2501','Create table that contains all attribute types of a termCollection','All available data type attributes for term collection are saved in database.',15,'change','5.7.9'),
(957,'2021-06-08','TRANSLATE-2532','ERROR in core: E9999 - Call to a member function getMessage() on null','Fix a seldom PHP error, only happening when translate5 instance is tried to be crawled.',15,'bugfix','5.7.9'),
(958,'2021-06-08','TRANSLATE-2531','Microsoft Translator language resource connector is not properly implemented','The Microsoft Translator language resource connector is not properly implemented regarding error handling and if a location restriction is used in the azure API configuration.',15,'bugfix','5.7.9'),
(959,'2021-06-08','TRANSLATE-2529','Brute-Force attacks may produce: ERROR in core: E9999 - $request->getParam(\'locale\') war keine gültige locale','Providing invalid locales as parameter on application loading has produced an error. Now the invalid locale is ignored and the default one is loaded.',15,'bugfix','5.7.9'),
(960,'2021-06-08','TRANSLATE-2526','Run analysis on task import wizard','Fixes problem with analysis and pre-translation not triggered for default associated resources on task import (without opening the language resources wizard)',15,'bugfix','5.7.9'),
(961,'2021-06-09','TRANSLATE-2500','Worker Architecture: Solving Problems with Deadlocks and related Locking/Mutex Quirks','Improved the internal worker handling regarding DB dead locks and a small opportunity that workers run twice.',15,'bugfix','5.7.9'),
(962,'2021-06-24','TRANSLATE-2556','PHP Error Specified column previousOrigin is not in the row','This error was triggered in certain circumstances by the import of SDLXLIFF files containing empty origin information.',15,'bugfix','5.7.9'),
(963,'2021-06-24','TRANSLATE-2555','XML errors in uploaded TMX files are not shown properly in the TM event log','The XML error was logged in the system log, but was not added to the specific log of the TM. This is changed now so that the PM can see what is wrong.',15,'bugfix','5.7.9'),
(964,'2021-06-24','TRANSLATE-2554','BUG TermTagger Worker: Workers are scheduled exponentially','FIXED: Bug in TermTagger Worker leads to scheduling workers exponentially what causes database deadlocks',15,'bugfix','5.7.9'),
(965,'2021-06-24','TRANSLATE-2552','Typos in translate5','Fixes couple of typos in translate5 locales',15,'bugfix','5.7.9'),
(966,'2021-07-06','TRANSLATE-2081','Preset of user to task assignments','Provides the functionality to configure auto-assignment of users to tasks on client configuration level, filtered by language, setting the to be used user and workflow step.',15,'feature','5.7.9'),
(967,'2021-07-06','TRANSLATE-2545','Flexibilize workflow by putting role and step definitions in database','The definition of all available workflow steps and roles is now stored in the database instead in a fixed workflow class. A new complex workflow is added for demonstration purposes and usage if wanted.',15,'change','5.7.9'),
(968,'2021-07-06','TRANSLATE-2516','Add user column to Excel language resource usage log','The spreadsheet with the usage log of language resources is extended with a user column, that shows, who actually did the request.',15,'change','5.7.9'),
(969,'2021-07-06','TRANSLATE-2563','Adjust texts that connect analysis and locking of 100%-Matches','Adjust texts that connect analysis and locking of 100%-Matches.',15,'bugfix','5.7.9'),
(970,'2021-07-06','TRANSLATE-2560','Combination of term-tagging and enabled source editing duplicates tags on saving a segment, AutoQA removes/merges TrackChanges from different Users','FIXED BUG in the TermTagger leading to duplication of internal tags when source editing was activated\nFIXED BUG in the AutoQA leading to TrackChanges tags from different users being merged',15,'bugfix','5.7.9'),
(971,'2021-07-06','TRANSLATE-2557','Select correct okapi file filter for txt-files by default','By default the file format conversion used for txt-files the okapi-filter \"moses-text\". In this filter xml-special characters like & < > where kept in encoded version when the file was reconverted back to txt after export from translate5. This was wrong. Now the default was changed to the okapi plain-text filter, what handles the xml-special chars correctly.',15,'bugfix','5.7.9'),
(972,'2021-07-06','TRANSLATE-2547','Clean-up project tasks','Deleting a project deletes all files from database but not from disk. This is fixed.',15,'bugfix','5.7.9'),
(973,'2021-07-06','TRANSLATE-2536','Task Configuration Panel does show old Values after Import','FIX: Task Qualities & Task Configuration panels now update their view automatically after import to avoid outdated date is being shown',15,'bugfix','5.7.9'),
(974,'2021-07-06','TRANSLATE-2533','Line breaks in InstantTranslate are deleted','InstantTranslate dealing of line breaks is fixed.',15,'bugfix','5.7.9'),
(975,'2021-07-20','TRANSLATE-2518','Add project description to project and tasks','A project description can be added on project creation.',15,'feature','5.7.9'),
(976,'2021-07-20','TRANSLATE-2477','Language resource to task assoc: Set default for pre-translation and internal-fuzzy options in system config','Default values for \"internal fuzzy\", \"translate MT\" and \"translate TM and Term\" checkboxes  can be defined as system configuration configuration (overwritable on client level).',15,'feature','5.7.9'),
(977,'2021-07-20','TRANSLATE-992','New Keyboard shortcuts for process / cancel repetition editor','Adding keyboard shortcuts to save (ctrl+s) or cancel (esc) the processing of repetitions in the repetition editor.',15,'feature','5.7.9'),
(978,'2021-07-20','TRANSLATE-2566','Integrate Theme-Switch in translate5','Users are able to change the translate5 theme.',15,'change','5.7.9'),
(979,'2021-07-20','TRANSLATE-2381','Visual: Enhance the reflow mechanism for overlapping elements','Visual: Improved Text-Reflow. This signifantly reduces the rate of PDFs that cannot be imported with a functional WYSIWIG preview. There now is a threshhold for detected reflow-rendering errors that can be raised for individual tasks that had to many errors on Import as a last ressort. Although that will rarely be neccessary.',15,'change','5.7.9'),
(980,'2021-07-20','TRANSLATE-1808','Installer should set the timezone','The installer always set timezone europe/berlin, know the  user is asked on installation which timezone should be used.',15,'change','5.7.9'),
(981,'2021-07-20','TRANSLATE-2581','Task user assoc workflow step drop-down filtering','If a user was added twice to a task, and the workflow step of the second user was changed to the same step of the first user, this led to a duplicated key error message.',15,'bugfix','5.7.9'),
(982,'2021-07-20','TRANSLATE-2578','Reload users to task association grid after task import finishes','Refresh users to task association grid after the task import is done.',15,'bugfix','5.7.9'),
(983,'2021-07-20','TRANSLATE-2576','Notify associated user button does not work','Fixes problem with \"Notify users\" button not sending emails.',15,'bugfix','5.7.9'),
(984,'2021-07-20','TRANSLATE-2575','System default configuration on instance or client level has no influence on Multiple user setting in import wizard','The default value for the \"multiple user\" setting drop-down was not correctly preset from config.',15,'bugfix','5.7.9'),
(985,'2021-07-20','TRANSLATE-2573','User assignment entry disappears in import wizard, when pre-assigned deadline is changed','Edited user association in import wizard was disappearing after switching the workflow.',15,'bugfix','5.7.9'),
(986,'2021-07-20','TRANSLATE-2571','ERROR in core: E9999 - TimeOut on waiting for the following materialized view to be filled','There was a problem when editing a default associated user of a task in the task add wizard. This is fixed now.',15,'bugfix','5.7.9'),
(987,'2021-07-20','TRANSLATE-2568','ModelFront plug-in is defect and prevents language resource usage','The ModelFront plug-in was defect and stopped match analysis and pre-translation from working.',15,'bugfix','5.7.9'),
(988,'2021-07-20','TRANSLATE-2567','TagProtection can not deal with line breaks in HTML attributes','When using TagProtection (protect plain HTML code in XLF as tags) line breaks in HTML attributes were not probably resolved.',15,'bugfix','5.7.9'),
(989,'2021-07-20','TRANSLATE-2565','GroupShare: Wrong tag order using the groupshare language resource','Nested internal tags were restored in wrong order if using a segment containing such tags from the groupshare language resource. ',15,'bugfix','5.7.9'),
(990,'2021-07-20','TRANSLATE-2546','New uuid column of match analysis is not filled up for existing analysis','The new uuid database column of the match analysis table is not filled up for existing analysis.',15,'bugfix','5.7.9'),
(991,'2021-07-20','TRANSLATE-2544','Focus new project after creating it','After task/project creation the created project will be focused in the project overview',15,'bugfix','5.7.9'),
(992,'2021-07-20','TRANSLATE-2525','npsp spaces outside of mrk-tags of mtype \"seg\" should be allowed','Due to invalid XLIFF from Across there is a check in import, that checks, if there is text outside of mrk-tags of mtype \"seg\" inside of seg-source or target tags. Spaces and tags are allowed, but nbsp characters were not so far. This is changed and all other masked whitespace tags are allowed to be outside of mrk tags too.',15,'bugfix','5.7.9'),
(993,'2021-07-20','TRANSLATE-2388','Ensure config overwrite works for \"task usage mode\"','The task usageMode can now be set via API on task creation.',15,'bugfix','5.7.9'),
(994,'2021-08-04','TRANSLATE-2580','Add segment length check to AutoQA','AutoQA now incorporates a check of the(pixel based)  segment-length',15,'feature','5.7.9'),
(995,'2021-08-04','TRANSLATE-2416','Create PM-light system role','A new role PM-light is created, which may only administrate its own projects and tasks and has no access to user management or language resources management.',15,'feature','5.7.9'),
(996,'2021-08-04','TRANSLATE-2586','Check the URLs in the reviewHtml.txt file for the visual','ENHANCEMENT: Warn and clean visual source URLs that can not be imported because they have a fragment \"#\"\nENHANCEMENT: Skip duplicates and clean URLs in the reviewHtml.txt file',15,'change','5.7.9'),
(997,'2021-08-04','TRANSLATE-2583','Save config record instead of model sync','Code improvements in the configuration overview grid.',15,'change','5.7.9'),
(998,'2021-08-04','TRANSLATE-2589','Exclude meta data of images for word files by default','By default translate5 will now not extract any more meta data of images, that are embedded in MS Word files.',15,'bugfix','5.7.9'),
(999,'2021-08-04','TRANSLATE-2587','Improve error logging','Improves error messages in instant-translate.',15,'bugfix','5.7.9'),
(1000,'2021-08-04','TRANSLATE-2585','Evaluate auto_set_role acl for OpenID authentications','All missing mandatory translate roles for users authentication via SSO will be automatically added.',15,'bugfix','5.7.9'),
(1001,'2021-08-04','TRANSLATE-2584','Across XLF with translate no may contain invalid segmented content','Across XLF may contain invalid segmented content for not translatable (not editable) segments. This is fixed by using the not segment content in that case.',15,'bugfix','5.7.9'),
(1002,'2021-08-04','TRANSLATE-2570','AutoQA checks blocked segments / finds unedited fuzzy errors in unedited bilingual segments','ENHANCEMENT: blocked segments will no longer be evaluated in the quality-management, only if they have structural internal tag-errors they will appear in a new category for this\nFIX: Missing internal tags may have been detected in untranslated empty segments\nFIX: Added task-name & guid to error-logs regarding structural internal tag errors\nFIX: Quality-Management is now bound to a proper ACL\nFIX: Re-establish proper layout of action icons in Task-Grid\n\n',15,'bugfix','5.7.9'),
(1003,'2021-08-04','TRANSLATE-2564','Do not render MQM-Tags parted by overlappings','FIX: MQM-Tags now are visualized with overlappings unresolved (not cut into pieves)\n',15,'bugfix','5.7.9'),
(1004,'2021-08-06','TRANSLATE-2596','Message bus session synchronization rights','Solves the problem where the message bus did not have the rights to synchronize the session.',15,'bugfix','5.7.9'),
(1005,'2021-08-06','TRANSLATE-2595','Customers store autoload for not authorized users','Solves the problem with loading of the customers for not-authorized users.',15,'bugfix','5.7.9'),
(1006,'2021-09-30','TRANSLATE-2302','Accept and reject TrackChanges','Plugin TrackChanges\n* added capabilities for the editor, to accept/reject changes from preceiding workflow-steps\n* reduced tracking of changes in the translation step, only pretranslated segments are tracked\n* by default, TrackChanges is invisible in the translation step\n* the visibility of changes is normally reduced to the changes of the preceiding workflow steps\n* the visibility and capability to accept/reject for the editor can be set via the user assocciations on the task and customer level',15,'feature','5.7.9'),
(1007,'2021-09-30','TRANSLATE-1405','TermPortal as terminology management solution','Introduced the brand new TermPortal, now completely usable as terminology management solution.',15,'feature','5.7.9'),
(1008,'2021-09-30','TRANSLATE-2629','Integrate beo-proposals for German names of standard tbx attributes','Term-portal improvement UI names of standard TBX attributes',15,'change','5.7.9'),
(1009,'2021-09-30','TRANSLATE-2625','Solve tag errors automatically on export','Internal Tag Errors (faulty structure) will be fixed automatically when exporting a task: Orphan opening/closing tags will be removed, structurally broken tag pairs will be corrected. The errors in the task itself will remain.',15,'change','5.7.9'),
(1010,'2021-09-30','TRANSLATE-2623','Move theme switch button and language switch button in settings panel','The drop-down for switching the translate5 language and translate5 theme is moved under \"Preferences\" ->\"My settings\" tab.',15,'change','5.7.9'),
(1011,'2021-09-30','TRANSLATE-2622','CLI video in settings help window','Integrate CLI video in preferences help page.',15,'change','5.7.9'),
(1012,'2021-09-30','TRANSLATE-2611','Check Visual Review URLs before downloading them if they are accessible','Added additional check for Visual Review URLs if the URL is accessible before downloading it to improve the logged error',15,'change','5.7.9'),
(1013,'2021-09-30','TRANSLATE-2621','Logging task specific stuff before task is saved leads to errors','In seldom cases it may happen that task specific errors should be logged in the time before the task was first saved to DB, this was producing a system error on processing the initial error and the information about the initial error was lost.',15,'bugfix','5.7.9'),
(1014,'2021-09-30','TRANSLATE-2618','Rename tooltips for next segment in translate5','Improves tooltip text in editor meta panel segment navigation.',15,'bugfix','5.7.9'),
(1015,'2021-09-30','TRANSLATE-2614','Correct translate5 workflow names of complex workflow','Improve the step names and translations of the complex workflow',15,'bugfix','5.7.9'),
(1016,'2021-09-30','TRANSLATE-2612','Job status changes from open to waiting on deadline change','If the deadline of a job in a task is changed, the status of the job changes from \"open\" to \"waiting\". This is fixed.',15,'bugfix','5.7.9'),
(1017,'2021-09-30','TRANSLATE-2609','Import of MemoQ comments fails','HOTFIX: MemoQ comment parsing produces corrupt comments with single comment nodes. Add Exception to the base parsing API to prevent usage of negative length\'s',15,'bugfix','5.7.9'),
(1018,'2021-09-30','TRANSLATE-2603','Browser does not refresh cache for maintenance page','It could happen that users were hanging in the maintenance page - depending on their proxy / cache settings. This is solved now.',15,'bugfix','5.7.9'),
(1019,'2021-09-30','TRANSLATE-2602','msg is not defined','Fixed a ordinary programming error in the frontend message bus.',15,'bugfix','5.7.9'),
(1020,'2021-09-30','TRANSLATE-2601','role column is not listed in workflow mail','The role was not shown any more in the notification e-mails if a task was assigned to users.',15,'bugfix','5.7.9'),
(1021,'2021-09-30','TRANSLATE-2599','reviewer can not open associated task in read-only mode','If a user with segment ranges tries to open a task read-only due workflow state waiting or finished this was resulting in an error.',15,'bugfix','5.7.9'),
(1022,'2021-09-30','TRANSLATE-2598','Layout Change Logout','Changing translate5 theme will no longer logout the user.',15,'bugfix','5.7.9'),
(1023,'2021-09-30','TRANSLATE-2591','comments of translate no segments are not exported anymore','comments of segments with translate = no were not exported any more, this is fixed now.',15,'bugfix','5.7.9'),
(1024,'2021-10-07','TRANSLATE-2640','Remove InstantTranslate on/off button from InstantTranslate and move functionality to configuration','The auto-translate feature in instant translate can be configured if active for each client.',15,'feature','5.7.9'),
(1025,'2021-10-07','TRANSLATE-2645','TermPortal: set mysql fulltext search minimum word length to 1 and disable stop words','Please set innodb_ft_min_token_size in your mysql installation to 1 and  	innodb_ft_enable_stopword to 0.\nThis is necessary for TermPortal to find words shorter than 3 characters. If you did already install translate5 5.5.0 on your server OR if you did install translate 5.5.1 BEFORE you did change that settings in your mysql installation, then you would need to update the fulltext indexes of your DB term-tables manually. \nIf this is the case, please call \"./translate5.sh termportal:reindex\" or contact us, how to do this.\nPlease run \"./translate5.sh system:check\" to check afterwards if everything is properly configured.',15,'change','5.7.9'),
(1026,'2021-10-07','TRANSLATE-2641','AdministrativeStatus default attribute and value','The \"Usage Status (administrativeStatus)\" attribute is now the leading one regarding the term status. Its value is synchronized to all other similar attributes (normativeAuthorization and other custom ones).',15,'change','5.7.9'),
(1027,'2021-10-07','TRANSLATE-2634','Integrate PDF documentation in translate5 help window','Pdf documentation in the editor help window is available now.\nTo change PDF location or disable see config runtimeOptions.frontend.helpWindow.editor.documentationUrl',15,'change','5.7.9'),
(1028,'2021-10-07','TRANSLATE-2607','Make type timeout in InstantTranslate configurable','The translation delay in instant translate can be configured now.',15,'change','5.7.9'),
(1029,'2021-10-07','TRANSLATE-2644','Task related notification emails should link directly to the task','Currently task related notification E-Mails do not point to the task but to the portal only. This is changed.',15,'bugfix','5.7.9'),
(1030,'2021-10-07','TRANSLATE-2643','Usability improvements: default user assignment','Usability improvements in default user association overview.',15,'bugfix','5.7.9'),
(1031,'2021-10-11','TRANSLATE-2637','Warn regarding merging terms','Warning message will be shown when using merge terms functionality in term collection import/re-import',15,'change','5.7.9'),
(1032,'2021-10-11','TRANSLATE-2630','Add language resource name to language resource pop-up - same for projects','Improves info messages and windows titles in language resources, project and task overview.',15,'change','5.7.9'),
(1033,'2021-10-11','TRANSLATE-2597','Set resource usage log lifetime by default to 30 days','This will set the default lifetime days for resources usage log configuration to 30 days when there is no value set.',15,'bugfix','5.7.9'),
(1034,'2021-10-11','TRANSLATE-2528','Instant-translate and Term-portal route after login','Fixed problems accessing TermPortal / InstantTranslate with external URLs.',15,'bugfix','5.7.9'),
(1035,'2021-10-28','TRANSLATE-2613','Add Locaria Logo to Website and App','Added Locaria logo to the app',15,'feature','5.7.9'),
(1036,'2021-10-28','TRANSLATE-2076','Define analysis fuzzy match ranges','The ranges of the match rates for the analysis can now be defined in the configuration: runtimeOptions.plugins.MatchAnalysis.fuzzyBoundaries',15,'feature','5.7.9'),
(1037,'2021-10-28','TRANSLATE-2652','Add keyboard short-cuts for Accept/Reject TrackChanges','ENHANCEMENT: Keyboard Shortcuts for TrackChanges accept/reject feature',15,'change','5.7.9'),
(1038,'2021-10-28','TRANSLATE-2625','Solve tag errors automatically on export','Internal Tag Errors (faulty structure) will be fixed automatically when exporting a task: Orphan opening/closing tags will be removed, structurally broken tag pairs will be corrected. The errors in the task itself will remain.',15,'change','5.7.9'),
(1039,'2021-10-28','TRANSLATE-2681','Language naming mismatch regarding the chinese languages','The languages zh-Hans and zh-Hant were missing. Currently zh-CN was named \"Chinese simplified\", this is changed now to Chinese (China).',15,'bugfix','5.7.9'),
(1040,'2021-10-28','TRANSLATE-2680','Okapi empty target fix was working only for tasks with editable source','The Okapi export fix TRANSLATE-2384 was working only for tasks with editable source. Now it works in general. Also in case of an export error, the XLF in the export zip was named as original file (so file.docx was containing XLF). This is changed, so that the XLF is named now file.docx.xlf). Additionally a export-error.txt is created which explains the problem.\n',15,'bugfix','5.7.9'),
(1041,'2021-10-28','TRANSLATE-2679','Microsoft translator connection language code mapping is not case insensitive','Microsoft translator returns zh-Hans for simplified Chinese, we have configured zh-hans in our language table. Therefore the language can not be used. This is fixed now.',15,'bugfix','5.7.9'),
(1042,'2021-10-28','TRANSLATE-2672','UI theme selection may be wrong if system default is not triton theme','The users selected theme may be resetted to triton theme instead to the system default theme.',15,'bugfix','5.7.9'),
(1043,'2021-10-28','TRANSLATE-2664','Fix TermPortal client-specific favicon and CSS usage','The technical possibilities to customize the TermPortal layout were not fully migrated from the old termportal.',15,'bugfix','5.7.9'),
(1044,'2021-10-28','TRANSLATE-2658','Wrong tag numbering between source and target in imported MemoQ XLF files','For MemoQ XLF files it may happen that tag numbering between source and target was wrong. This is corrected now.',15,'bugfix','5.7.9'),
(1045,'2021-10-28','TRANSLATE-2657','Missing term roles for legacy admin users','Activate the term portal roles for admin users not having them.',15,'bugfix','5.7.9'),
(1046,'2021-10-28','TRANSLATE-2656','Notify associated users checkbox is not effective','The bug is fixed where the \"notify associated users checkbox\" in the import wizard does not take effect when disabled.',15,'bugfix','5.7.9'),
(1047,'2021-10-28','TRANSLATE-2592','Reduce and by default hide use of TrackChanges in the translation step','Regarding translation and track changes: changes are only recorded for pre-translated segments and changes are hidden by default for translators (and can be activated by the user in the view modes drop-down of the editor)\n\n',15,'bugfix','5.7.9'),
(1048,'2021-11-15','TRANSLATE-2638','Implement new layout for InstantTranslate','Implement new layout for InstantTranslate as discussed with the consortium members.',15,'feature','5.7.9'),
(1049,'2021-11-15','TRANSLATE-2683','Editor Embedded: export may be started while last edited segment still is saving','For translate5 embedded usage: the JS API function Editor.util.TaskActions.isTaskExportable() returns true or false if the currently opened task can be exported regarding the last segment save call.',15,'change','5.7.9'),
(1050,'2021-11-15','TRANSLATE-2649','Small fixes for TermPortal','A number of fixes/improvements implemented',15,'change','5.7.9'),
(1051,'2021-11-15','TRANSLATE-2632','TermPortal code refactoring','Termportal code and related tests are now refactored for better maintainability.',15,'change','5.7.9'),
(1052,'2021-11-15','TRANSLATE-2489','Change of attribute label in GUI','Added ability to edit attribute labels',15,'change','5.7.9'),
(1053,'2021-11-15','TRANSLATE-2701','Source term from InstantTranslate not saved along with target term','TermPortal: In case the source term, that had been translated in InstantTranslate was not contained in the TermCollection, only the target term was added, the new source term not. This is fixed.',15,'bugfix','5.7.9'),
(1054,'2021-11-15','TRANSLATE-2699','Add missing ID column to task overview and fix date type in meta data excel','Add missing ID column to task overview and fix date type in meta data excel export.',15,'bugfix','5.7.9'),
(1055,'2021-11-15','TRANSLATE-2696','Malicious segments may lead to endless loop while term tagging','Segments with specific / malicious content may lead to endless loops while term tagging so that the task import is running forever.',15,'bugfix','5.7.9'),
(1056,'2021-11-15','TRANSLATE-2695','JS error task is null','Due unknown conditions there might be an error task is null in the GUI. Since the reason could not be determined, we just fixed the symptoms. As a result a user might click twice on the menu action item to get all items.',15,'bugfix','5.7.9'),
(1057,'2021-11-15','TRANSLATE-2694','Improve GUI logging for false positive \"Not all repeated segments could be saved\" messages','Improve GUI logging for message like: Not all repeated segments could be saved. With the advanced logging should it be possible to detect the reason behind.',15,'bugfix','5.7.9'),
(1058,'2021-11-15','TRANSLATE-2691','SDLXLIFF diff export is failing with an endless loop','The SDLXLIFF export with diff fails by hanging in an endless loop if the segment content has a specific form. This is fixed by updating the underlying diff library.',15,'bugfix','5.7.9'),
(1059,'2021-11-15','TRANSLATE-2690','task is null: User association in import wizard','Fix for \"task is null\" error in import user-assoc wizard',15,'bugfix','5.7.9'),
(1060,'2021-11-15','TRANSLATE-2689','TBX import fails because of some ID error','Terminology containing string based IDs could not be imported if the same ID was used one time lower case and one time uppercase.',15,'bugfix','5.7.9'),
(1061,'2021-11-15','TRANSLATE-2688','For many languages the lcid is missing in LEK_languages','Added some missing LCID values in the language table.',15,'bugfix','5.7.9'),
(1062,'2021-11-15','TRANSLATE-2687','Wrong texts in system config options','Improve description and GUI-text for system configurations.',15,'bugfix','5.7.9'),
(1063,'2021-11-15','TRANSLATE-2686','TermTagging does not work after import','If term tagging is started along with analysis on an already imported task, nothing gets tagged.',15,'bugfix','5.7.9'),
(1064,'2021-11-15','TRANSLATE-2404','There is no way to run only the terminology check only after import','There is no way to start the terminology check only from the language resource association panel, a analysis is always started as well. This is changed now.',15,'bugfix','5.7.9'),
(1065,'2021-12-08','TRANSLATE-2728','Link terms in segment meta panel to the termportal','In the segments meta panel all terms of the currently edited segment are shown. This terms are now clickable linked to the termportal - if the termportal is available.',15,'feature','5.7.9'),
(1066,'2021-12-08','TRANSLATE-2713','Use HTML linking in Visual based on xml/xsl, if workfiles are xliff','Added option to add a XML/XSL combination as visual source direct in the /visual folder of the import zip: If there is an XML in the /visual folder with a linked XSL stylesheet that is present in the /visual folder as well, the visual HTML is generated from these files using the normal, text-based segmentation (and not aligning the XML against the imported bilingual workfiles)',15,'feature','5.7.9'),
(1067,'2021-12-08','TRANSLATE-2666','WYSIWYG for Images with Text','This new feature enables using a single Image as a source for a Visual. \nThis Image is then analyzed (OCR) and the found text can be edited in the right WYSIWIG-frame. \n* The Image must be imported in the subfolder /visual/image of the import-zip\n* A single WebFont-file (*.woff) can be added alongside the Image and then will be used as Font for the whole text on the Image\n* If no font is provided, Arial is the general fallback\n* Any text not present in the bilingual file in /workfiles will be removed from the OCR\'s output. This means, the bilingual file should contain exactly the text, that is expected to be on the image and to be translated',15,'feature','5.7.9'),
(1068,'2021-12-08','TRANSLATE-2387','Annotate visual','The users are able to add text annotations(markers) where ever he likes in the visual area.  Also the users are able to create segment annotations when clicking on a segment in the layout.',15,'feature','5.7.9'),
(1069,'2021-12-08','TRANSLATE-2303','Overview of comments','A new Comment section has been added to the left-hand side of the Segment editor.\nIt lists all the segment comments and visual annotations ordered by page. The type is indicated by a small symbol to the left. Its background color indicates the authoring user.\nWhen an element of that list is clicked, translate5 jumps to the corresponding remark, either in the VisualReview or in the segment grid.\nOn hover the full remark is shown in a tooltip, together with the authoring user and the last change date.\nNew comments are added in realtime to the list.',15,'feature','5.7.9'),
(1070,'2021-12-08','TRANSLATE-2740','PHP 8 is now required - support for older PHP versions is dropped','Translate5 and all dependencies use now PHP 8.',15,'change','5.7.9'),
(1071,'2021-12-08','TRANSLATE-2733','Embed translate5 task video in help window','Embed the translate5 task videos as iframe in the help window. The videos are either in german or english, they are chosen automatically depending on the GUI interface. A list of links to jump to specific parts of the videos are provided.',15,'change','5.7.9'),
(1072,'2021-12-08','TRANSLATE-2726','Invert tooltipt font color in term-column in left panel','Term tooltip font color set to black for proposals to be readable',15,'change','5.7.9'),
(1073,'2021-12-08','TRANSLATE-2693','Write tests for new TermPortal','Created tests for all termportal api endpoints',15,'change','5.7.9'),
(1074,'2021-12-08','TRANSLATE-2670','WYSIWIG for Images: Frontend - General Review-type, new (mostly dummy) ImageScroller, extensions IframeController','see Translate-2666',15,'change','5.7.9'),
(1075,'2021-12-08','TRANSLATE-2669','WYSIWIG for Images: Extend Font-Management','see TRANSLATE-2666',15,'change','5.7.9'),
(1076,'2021-12-08','TRANSLATE-2668','WYSIWIG for Images: Add new Review-type, add worker & file managment, creation of HTML file representing the review','see TRANSLATE-2666',15,'change','5.7.9'),
(1077,'2021-12-08','TRANSLATE-2667','WYSIWIG for Images: Implement Text Recognition','see TRANSLATE-2666',15,'change','5.7.9'),
(1078,'2021-12-08','TRANSLATE-2487','Edit an attribute for multiple occurrences at once','Added ability for attributes batch editing',15,'change','5.7.9'),
(1079,'2021-12-08','TRANSLATE-2741','Segment processing status is wrong on unchanged segments with tags','On reviewing the processing state of a segment was set wrong if the segment contains tags and was saved unchanged.',15,'bugfix','5.7.9'),
(1080,'2021-12-08','TRANSLATE-2739','Segment length validation does also check original target on TM usage','On tasks using segment length restrictions some segments could not be saved if content was overtaken manually from a language resource and edited afterwards to fit in the length restriction.',15,'bugfix','5.7.9'),
(1081,'2021-12-08','TRANSLATE-2737','VisualReview height not saved in session','Persist VisualReview height between reloads.',15,'bugfix','5.7.9'),
(1082,'2021-12-08','TRANSLATE-2736','State of show/hide split iframe is not saved correctly','Fix issues with the saved state of the show/hide split frame button in the visual',15,'bugfix','5.7.9'),
(1083,'2021-12-08','TRANSLATE-2732','Advanced filter users list anonymized users query','Solves advanced filter error for users with no \"read-anonymized\" users right.',15,'bugfix','5.7.9'),
(1084,'2021-12-08','TRANSLATE-2731','No redirect to login page if maintenance is scheduled','The initial page of the translate5 instance does not redirect to the login page if a maintenance is scheduled.',15,'bugfix','5.7.9'),
(1085,'2021-12-08','TRANSLATE-2730','Improve maintenance handling regarding workers','If maintenance is scheduled the export was hanging in a endless loop, also import related workers won\'t start anymore one hour before maintenance. ',15,'bugfix','5.7.9'),
(1086,'2021-12-08','TRANSLATE-2729','PDO type casting error in bind parameters','The user will no longer receive an error when the customer was deleted.',15,'bugfix','5.7.9'),
(1087,'2021-12-08','TRANSLATE-2724','Translation error in the Layout','Workflow name is localized now.',15,'bugfix','5.7.9'),
(1088,'2021-12-08','TRANSLATE-2720','Termportal initial loading takes dozens of seconds','Solved termportal long initial loading problem',15,'bugfix','5.7.9'),
(1089,'2021-12-08','TRANSLATE-2715','String could not be parsed as XML - on tbx import','The exported TBX was no valid XML therefore was an error on re-importing that TBX.',15,'bugfix','5.7.9'),
(1090,'2021-12-08','TRANSLATE-2708','Visual review: iframe scaling problem','Enables zoom in in all directions in visual.',15,'bugfix','5.7.9'),
(1091,'2021-12-08','TRANSLATE-2707','correct display language-selection in Instant-Translate','Fixed the language listing in InstantTranslate, which was broken for a lot of languages.',15,'bugfix','5.7.9'),
(1092,'2021-12-08','TRANSLATE-2706','Not all repetitions are saved after exchanging the term-collection','Not all repeated segments were changed if saving repetitions with terminology and the term-collection was changed in the task.',15,'bugfix','5.7.9'),
(1093,'2021-12-08','TRANSLATE-2700','Improve termtagging performance due table locks','The queuing of the segments prepared for term tagging is improved, so that multiple term taggers really should work in parallel. ',15,'bugfix','5.7.9'),
(1094,'2021-12-17','TRANSLATE-2761','Test for tbx specialchars import','Added test for import tbx containing specialchars',15,'change','5.7.9'),
(1095,'2021-12-17','TRANSLATE-2760','AutoQA also processed when performing an Analysis  & add AutoQA Reanalysis','* The AnalysisOperation in a task\'s MatchAnalysis panel now covers a re-evaluation of the QA\n* This makes the seperate Button to tag the Terms obsolete, so it is removed\n* added Button to Re-check the QA in the task\'s QA panel',15,'change','5.7.9'),
(1096,'2021-12-17','TRANSLATE-2488','Excel export of TermCollection','Added ability to export TermCollections into xlsx-format',15,'change','5.7.9'),
(1097,'2021-12-17','TRANSLATE-2763','Term term entries older than current import deletes also unchanged terms','TBX Import: The setting \"Term term entries older than current import\" did also delete the terms which are contained unchanged in the TBX.',15,'bugfix','5.7.9'),
(1098,'2021-12-17','TRANSLATE-2759','Deleted newlines were still counting as newline in length calculation','When using the line counting feature in segment content deleted newlines were still counted since they still exist as trackchanges.',15,'bugfix','5.7.9'),
(1099,'2021-12-17','TRANSLATE-2758','scrollToAnnotation: Annotation references, sorting and size','Scrolling, size and sorting of annotations has been fixed',15,'bugfix','5.7.9'),
(1100,'2021-12-17','TRANSLATE-2756','Segments were locked after pre-translation but no translation content was set','It could happen that repeated segments were blocked with a matchrate >= 100% but no content was pre-translated in the segment. Also the target original field was filled wrong on using repetitions. And the match-rate for repetitions is now 102% as defined and not original the percentage from the repeated segment. This is now the same behaviour as in the analysis.',15,'bugfix','5.7.9'),
(1101,'2021-12-17','TRANSLATE-2755','Workers getting PHP fatal errors remain running','Import workers getting PHP fatal errors were remain running, instead of being properly marked crashed. ',15,'bugfix','5.7.9'),
(1102,'2021-12-17','TRANSLATE-2751','Mouse over segment with add-annotation active','The cursor will be of type cross when the user is in annotation creation mode and the mouse is over the segment.',15,'bugfix','5.7.9'),
(1103,'2021-12-17','TRANSLATE-2750','Make project tasks overview and task properties resizable and stateful','The height of the project tasks overview and the property panel of a single task are now resizeable.',15,'bugfix','5.7.9'),
(1104,'2021-12-17','TRANSLATE-2749','Blocked segments in workflow progress','The blocked segments now will be included in the workflow step progress calculation.',15,'bugfix','5.7.9'),
(1105,'2021-12-17','TRANSLATE-2747','Proposals are not listed in search results in some cases','TermPortal: it\'s now possible to find proposals for existing terms using \'Unprocessed\' as a value of \'Process status\' filter',15,'bugfix','5.7.9'),
(1106,'2021-12-17','TRANSLATE-2746','Add a Value for \"InstantTranslate: TM minimum match rate\"','Set the default value to 70 for minimum matchrate allowed to be displayed in InstantTranslate result list for TM language resources.',15,'bugfix','5.7.9'),
(1107,'2021-12-17','TRANSLATE-2745','500 Internal Server Error on creating comments','Creating a segment comment was leading to an error due the new comment overview feature.',15,'bugfix','5.7.9'),
(1108,'2021-12-17','TRANSLATE-2744','XLIFF2 Export with more than one translator does not work','The XLIFF2 export was not working with more than one translator associated to the task.',15,'bugfix','5.7.9'),
(1109,'2021-12-17','TRANSLATE-2719','TermPortal result column is empty, despite matches are shown','TermPortal: fixed \'left column is empty, despite matches are shown\' bug',15,'bugfix','5.7.9'),
(1110,'2022-02-03','TRANSLATE-2727','Task Management - Column for \"ended\" date of a task in the task grid and the exported meta data file for a task','A new column \"ended date\" is added to the task overview. It is filled automatically with the timestamp when the task is ended by the pm (not to be confused with finishing a workflow step).',15,'feature','5.7.9'),
(1111,'2022-02-03','TRANSLATE-2671','Import/Export, VisualReview / VisualTranslation - WYSIWIG for Videos','The visual now has capabilities to load a video as source together with segments and their timecodes (either as XSLX or SRT file). This gives the following new Features:\n\n* Video highlights the timecoded segments when the player reaches the play position\n* Annotations can be added to the video that appear as tooltip with an arrow pointing to the position on the selected timecodes frame\n* Import of subtitle (.srt) files as workfiles\n* Player can be navigated by clicking on the segments in the grid to play the segment\n* Clicking on the timerail of the video highlights the associated segment\n* Jumping from segment to segment, forth and back with player buttons and shortcuts\n* In the Comment/Annotation Overview, clicking Comments/Annotations will navigate the video\n* The Items in the Comment/Annotation Overview show their timecodes and are ordered by timecode\n\nThe Following prequesites must be fullfilled by a video to be used as visual source:\n* mp4 file-format,\n* h264 Codec\n* max FullHD (1920x1080) resolution\n',15,'feature','5.7.9'),
(1112,'2022-02-03','TRANSLATE-2540','Auto-QA - Check \"target is empty or contains only spaces, punctuation, or alike\"','Empty segments check added',15,'feature','5.7.9'),
(1113,'2022-02-03','TRANSLATE-2537','Auto-QA - Check inconsistent translations','Added consistency checks: segments with same target, but different source and segments with same source, but different target. In both cases tags ignored.',15,'feature','5.7.9'),
(1114,'2022-02-03','TRANSLATE-2491','TermPortal - Term-translation-Workflow','Added ability to transfer terms from TermPortal to Translate5, and import back to those terms TermCollection(s) once translated',15,'feature','5.7.9'),
(1115,'2022-02-03','TRANSLATE-2080','Task Management - Round up project creation wizard by refactoring and enhancing first screen','The first page of the project / task creation wizard was completely reworked with regard to the file upload. Now files can be added by drag and drop. The source and target language of bilingual files is automatically read out from the file and set then in wizard. This allows project creation directly out of a bunch of files without putting them in a ZIP file before. The well known ZIP import will still work.',15,'feature','5.7.9'),
(1116,'2022-02-03','TRANSLATE-2792','TermPortal - Sort attributes filter drop down alphabetically','options in TermPortal filter-window attributes-combobox are now sorted alphabetically',15,'change','5.7.9'),
(1117,'2022-02-03','TRANSLATE-2777','TermPortal - Usability enhancements for TermPortal','Added a number of usability enhancements for TermPortal',15,'change','5.7.9'),

(1119,'2022-02-03','TRANSLATE-2678','VisualReview / VisualTranslation - WYSIWIG for Videos: Export Video Annotations','See TRANSLATE-2671',15,'change','5.7.9'),
(1120,'2022-02-03','TRANSLATE-2676','VisualReview / VisualTranslation - WYSIWIG for Videos: Frontend: Extending Annotations for Videos','See TRANSLATE-2671',15,'change','5.7.9'),
(1121,'2022-02-03','TRANSLATE-2675','VisualReview / VisualTranslation - WYSIWIG for Videos: Frontend: New IframeController \"Video\", new Visual iframe for Videos','See TRANSLATE-2671',15,'change','5.7.9'),
(1122,'2022-02-03','TRANSLATE-2674','VisualReview / VisualTranslation - WYSIWIG for Videos: Add new Review-type, Video-HTML-Template','See TRANSLATE-2671',15,'change','5.7.9'),
(1123,'2022-02-03','TRANSLATE-2673','Import/Export - WYSIWIG for Videos: Import Videos with Excel Timeline','See TRANSLATE-2671',15,'change','5.7.9'),
(1124,'2022-02-03','TRANSLATE-2801','Repetition editor - Do not update matchrate on repetitions for review tasks','In the last release it was introduced that segments edited with the repetition editor was getting always the 102% match-rate for repetitions. Since is now changed so that this affects only translations and in review tasks the match rate is not touched in using repetitions.',15,'bugfix','5.7.9'),
(1125,'2022-02-03','TRANSLATE-2800','Editor general - User association wizard error when removing users','Solves problem when removing associated users from the task and quickly selecting another user from the grid afterwards.',15,'bugfix','5.7.9'),
(1126,'2022-02-03','TRANSLATE-2797','TBX-Import - Definition is not addable on language level due wrong default datatype','In some special cases the collected term attribute types and labels were overwriting some default labels. This so overwritten labels could then not be edited any more in the GUI.',15,'bugfix','5.7.9'),
(1127,'2022-02-03','TRANSLATE-2796','TermPortal - Change tooltip / Definition on language level cant be set / Double attribute of \"Definition\" on entry level','tooltips changed to \'Forbidden\' / \'Verboten\' for deprecatedTerm and supersededTerm statuses',15,'bugfix','5.7.9'),
(1128,'2022-02-03','TRANSLATE-2795','Import/Export, TermPortal - Term TBX-ID and term tbx-entry-id should be exported in excel-export','TermCollection Excel-export feature is now exporting Term/Entry tbx-ids instead of db-ids',15,'bugfix','5.7.9'),
(1129,'2022-02-03','TRANSLATE-2794','TermPortal - TermEntries are not deleted on TermCollection deletion','TermEntries are not deleted automatically on TermCollection deletion due a missing foreign key connection in database.',15,'bugfix','5.7.9'),
(1130,'2022-02-03','TRANSLATE-2791','TermTagger integration - Extend term attribute mapping to <descrip> elements','In the TermPortal proprietary TBX attributes could be mapped to the Usage Status. This was restricted to termNotes, now all types of attributes can be mapped (for example xBool_Forbidden in descrip elements).',15,'bugfix','5.7.9'),
(1131,'2022-02-03','TRANSLATE-2790','OpenTM2 integration - Disable OpenTM2 fixes if requesting t5memory','The OpenTM2 TMX import fixes are not needed anymore for the new t5memory, they should be disabled if the language resource is pointing to t5memory instead OpenTM2.',15,'bugfix','5.7.9'),
(1132,'2022-02-03','TRANSLATE-2788','Configuration - No default values in config editor for list type configs with defaults provided','For some configuration values the config editor in the settings was not working properly. This is fixed now.',15,'bugfix','5.7.9'),
(1133,'2022-02-03','TRANSLATE-2785','LanguageResources - Improve DeepL error handling and other fixes','DeepL was shortly not reachable, the produced errors were not handled properly in translate5, this is fixed. ',15,'bugfix','5.7.9'),
(1134,'2022-02-03','TRANSLATE-2781','Editor general - Access to job is still locked after user has closed his window','If a user just closes the browser it may happen that the there triggered automaticall logout does not work. Then the edited task of the user remains locked. The garbage cleaning and the API access to so locked jobs are improved, so that the task is getting unlocked then.',15,'bugfix','5.7.9'),
(1135,'2022-02-03','TRANSLATE-2780','VisualReview / VisualTranslation - Add missing close button to visual review simple mode','For embedded usage of the translate5 editor only: In visual review simple mode there is now also a close application button - in the normal mode it was existing already.',15,'bugfix','5.7.9'),
(1136,'2022-02-03','TRANSLATE-2776','Import/Export - XLF translate no with different mrk counts lead to unknown mrk tag error','The combination of XLF translate = no and a different amount of mrk segments in source and target was triggering erroneously this error.',15,'bugfix','5.7.9'),
(1137,'2022-02-03','TRANSLATE-2775','InstantTranslate - Issue with changing the language in InstantTranslate','fixed issue with changing the language in InstantTranslate',15,'bugfix','5.7.9'),
(1138,'2022-02-03','TRANSLATE-2774','Workflows - The calculation of a tasks workflow step is not working properly','The workflow step calculation of a task was calculating a wrong result if a workflow step (mostly visiting of a visitor) was added as first user.',15,'bugfix','5.7.9'),
(1139,'2022-02-03','TRANSLATE-2773','Auto-QA - Wrong job loading method in quality context used','There were errors on loading a tasks qualities on a no workflow task.',15,'bugfix','5.7.9'),
(1140,'2022-02-03','TRANSLATE-2771','OpenTM2 integration - translate5 sends bx / ex tags to opentm2 instead of paired g-tag','The XLF tag pairer does not work if the string contains a single tag in addition to the paired tag.',15,'bugfix','5.7.9'),
(1141,'2022-02-03','TRANSLATE-2770','TermPortal - Creating terms in TermPortal are creating null definitions instead empty strings','Fixed a bug on importing TBX files with empty definitions.',15,'bugfix','5.7.9'),
(1142,'2022-02-03','TRANSLATE-2769','VisualReview / VisualTranslation - Hide and collapse annotations is not working','Fixes the problem with hide and collapse annotations in visual.',15,'bugfix','5.7.9'),
(1143,'2022-02-03','TRANSLATE-2767','TermPortal - Issues popped up in Transline presentation','fixed js error, added tooltips for BatchWindow buttons',15,'bugfix','5.7.9'),
(1144,'2022-02-03','TRANSLATE-2723','Task Management, User Management - Reminder E-Mail sent multiple times','Fixed an annoying bug responsible for sending the deadline reminder e-mails multiple times. ',15,'bugfix','5.7.9'),
(1145,'2022-02-03','TRANSLATE-2712','VisualReview / VisualTranslation - Visual review: cancel segment editing removes the content from layout','FIXED: Bug where Text in the Visual disappeared, when the segment-editing was canceled',15,'bugfix','5.7.9'),
(1146,'2022-02-09','TRANSLATE-2810','TermPortal - All roles should be able to see all terms with all process status.','unprocessed-terms are now searchable even if user has no termProposer-role',15,'change','5.7.9'),
(1147,'2022-02-09','TRANSLATE-2809','TermPortal - Reimport term should be only possible for tasks created by the Term-Translation workflow','Reimport of terms back to their TermCollections is possible only for task, created via TermPortal terms transfer function',15,'change','5.7.9'),
(1148,'2022-02-09','TRANSLATE-2825','Task Management - Multiple files with multiple pivot files can not be uploaded','Multiple files with multiple pivot files can not be added in the task creation wizard. The pivot files are marked as invalid.',15,'bugfix','5.7.9'),
(1149,'2022-02-09','TRANSLATE-2824','Okapi integration - Enable aggressive tag clean-up in Okapi for MS Office files by default','Office often creates an incredible mess with inline tags, if users edit with character based markup.\nOkapi has an option to partly clean this up when converting an office file.\nThis option is now switched on by default.',15,'bugfix','5.7.9'),
(1150,'2022-02-09','TRANSLATE-2821','Auto-QA - Empty segment check does not report completely empty segments','Segments with completely empty targets are now counted in AutoQA: Empty-check',15,'bugfix','5.7.9'),
(1151,'2022-02-09','TRANSLATE-2817','VisualReview / VisualTranslation - Solve Problems with CommentNavigation causing too much DB strain','FIX: Loading of Comment Navigation may was slow',15,'bugfix','5.7.9'),
(1152,'2022-02-09','TRANSLATE-2816','Comments - Comment Overview performance problem and multiple loading calls','\"AllComments\" store: Prevent multiple requests by only making new ones when none are pending.',15,'bugfix','5.7.9'),
(1153,'2022-02-09','TRANSLATE-2815','Import/Export, Task Management - Upload time out for bigger files','The upload timeout in the import wizard is increased to prevent timeouts for slower connections.',15,'bugfix','5.7.9'),
(1154,'2022-02-09','TRANSLATE-2814','VisualReview / VisualTranslation - Solve Problems with Caching of plugin resources','FIX: Resources needed for the visual may become cached too long generating JS errors',15,'bugfix','5.7.9'),
(1155,'2022-02-09','TRANSLATE-2808','TermPortal - Mind sublanguages while terms transfer validation','Sublanguages are now respected while terms transfer validation',15,'bugfix','5.7.9'),
(1156,'2022-02-09','TRANSLATE-2803','Editor general - Displaying the logos causes issues','If the consortium logos were shown with a configured delay on the application startup, this may lead to problems when loading the application via an URL containing a task ID to open that task directly.',15,'bugfix','5.7.9'),
(1157,'2022-02-09','TRANSLATE-2802','Task Management - Add isReview and isTranslation methods to task entity','Internal functions renamed.',15,'bugfix','5.7.9'),
(1158,'2022-02-09','TRANSLATE-2685','Editor general - Error on pasting tags inside segment-editor','There was JS problem when editing a segment and pasting external content containing XML fragments.',15,'bugfix','5.7.9'),
(1159,'2022-04-07','TRANSLATE-2942','Repetition editor - Make repetitions more restrict by including segment meta fields into repetition calculation','Make repetition calculation more restrict by including segment meta fields (like maxLength) into repetition calculation. Can be defined by new configuration runtimeOptions.alike.segmentMetaFields.',15,'feature','5.7.9'),
(1160,'2022-04-07','TRANSLATE-2842','Workflows - new configuration to disable workflow mails','Workflow mails can be disabled via configuration.',15,'feature','5.7.9'),
(1161,'2022-04-07','TRANSLATE-2386','Configuration, Editor general - Add language specific special characters in database configuration for usage in editor','The current bar in the editor that enables adding special characters (currently non-breaking space, carriage return and tab) can be extended by characters, that can be defined in the configuration.\nExample of the config layout can be found here:\nhttps://confluence.translate5.net/display/BUS/Special+characters',15,'feature','5.7.9'),
(1162,'2022-04-07','TRANSLATE-2946','Editor general, Editor Length Check - Multiple problems on automatic adding of newlines in to long segments','Multiple Problems fixed: add newline or tab when with selected text in editor lead to an error. Multiple newlines were added in some circumstances in multiline segments with to long content. Optionally overwrite the trailing whitespace when newlines are added automatically.',15,'bugfix','5.7.9'),
(1163,'2022-04-07','TRANSLATE-2943','MatchAnalysis & Pretranslation - No analysis is shown if all segments were pre-translated and locked for editing','No analysis was shown if all segments were locked for editing due successful pre-translation although the analysis was run. Now an empty result is shown.',15,'bugfix','5.7.9'),
(1164,'2022-04-07','TRANSLATE-2941','Editor general - Ignore case for imported files extensions','The extension validator in the import wizard will no longer be case sensitive.',15,'bugfix','5.7.9'),
(1165,'2022-04-07','TRANSLATE-2940','Main back-end mechanisms (Worker, Logging, etc.) - Login redirect routes','Instant-translate / Term-portal routes will be evaluated correctly on login.',15,'bugfix','5.7.9'),
(1166,'2022-04-07','TRANSLATE-2939','TermTagger integration - Fix language matching on term tagging','The language matching between a task and terminology was not correct. Now terms in a major language (de) are also used in tasks with a sub language (de-DE)',15,'bugfix','5.7.9'),
(1167,'2022-04-07','TRANSLATE-2914','I10N - Missing localization for Chinese','Added missing translations for Chinese languages in the language drop downs.',15,'bugfix','5.7.9'),
(1168,'2022-02-17','TRANSLATE-2789','Import/Export - Import: Support specially tagged bilingual pdfs from a certain client','FEATURE: Support special bilingual PDFs as source for the Visual',15,'feature','5.7.9'),
(1169,'2022-02-17','TRANSLATE-2717','Client management, Configuration - Take over client configuration from another client','New feature where customer configuration and default user assignments can be copied from one customer to another.',15,'feature','5.7.9'),
(1170,'2022-02-17','TRANSLATE-2819','SpellCheck (LanguageTool integration) - SpellChecker: Add toggle button to activate/deactivate the SpellCheck','',15,'change','5.7.9'),
(1171,'2022-02-17','TRANSLATE-2722','InstantTranslate, TermPortal - Customizable header for InstantTranslate including custom HTML','Enables custom header content configuration in instant-translate and term-portal. For more info see the instant-translate and term-portal header section in this link https://confluence.translate5.net/pages/viewpage.action?pageId=3866712',15,'change','5.7.9'),
(1172,'2022-02-17','TRANSLATE-2841','Client management - Contents of clients tabs are not updated, when a new client is selected','Editing a customer in the customer panel is now possible with just selecting a row.',15,'bugfix','5.7.9'),
(1173,'2022-02-17','TRANSLATE-2840','Import/Export - Delete user association if the task import fails','Remove all user associations from a task, if the task import fails. So no e-mail will be sent to the users.',15,'bugfix','5.7.9'),
(1174,'2022-02-17','TRANSLATE-2837','Okapi integration - Change default segmentation rules to match Trados and MemoQ instead of Okapi and Across','So far translate5 (based on Okapi) did not segment after a colon.\nSince Trados and MemoQ do that by default, this is changed now to make translate5 better compatible with the vast majority of TMs out there.',15,'bugfix','5.7.9'),
(1175,'2022-02-17','TRANSLATE-2834','MatchAnalysis & Pretranslation - Change repetition behaviour in pre-translation','On pre-translations with using fuzzy matches, repeated segments may be filled with different tags / amount of tags as there are tags in the source content. Then the repetition algorithm could not process such segments as repetitions and finally the analysis was not counting them as repetitions.\nNow such segments always count as repetition in the analysis, but it does not get the 102% matchrate (since this may lead the translator to jump over the segment and ignore its content). Therefore such a repeated segment is filled with the fuzzy match content and the fuzzy match-rate. If the translator then edits and fix the fuzzy to be the correct translation , and then uses the repetition editor to fill the repetitions, then it is set to 102% matchrate.',15,'bugfix','5.7.9'),
(1176,'2022-02-17','TRANSLATE-2832','LanguageResources - Language filter in language resources overview is wrong','Language filter in language resources overview will filter for rfc values instead of language name.',15,'bugfix','5.7.9'),
(1177,'2022-02-17','TRANSLATE-2828','Editor general - Pivot language selector for zip uploads','Pivot language can now be set when uploading zip in the import wizard.',15,'bugfix','5.7.9'),
(1178,'2022-02-17','TRANSLATE-2827','Import/Export, Task Management - Improve workfile and pivot file matching','The matching between the workfile and pivot filenames is more easier right now, since the filename is compared now only to the first dot. So file.en-de.xlf matches now file.en-it.xlf and there is no need to rename such pivot files.',15,'bugfix','5.7.9'),
(1179,'2022-02-17','TRANSLATE-2826','TermPortal, TermTagger integration - processStatus is not correctly mapped by tbx import','processStatus is now set up correctly on processStatus-col in terms_term-table',15,'bugfix','5.7.9'),
(1180,'2022-02-17','TRANSLATE-2818','Auto-QA - AutoQA: Length-Check must Re-Evaluate also when processing Repititions','FIX: AutoQA now re-evaluates the length check for each segment individually when saving repititions',15,'bugfix','5.7.9'),
(1181,'2022-02-24','TRANSLATE-2852','TermPortal - Allow role TermPM to start Term-Translation-Workflow','termPM-role is now sifficient for Transfer-button to be shown.\nTermPortal filter window will assume *-query if yet empty.',15,'change','5.7.9'),
(1182,'2022-02-24','TRANSLATE-2851','TermPortal - Security dialogue, when deleting something in TermPortal','Added confirmation dialogs on term/attribute deletion attempt',15,'change','5.7.9'),
(1183,'2022-02-24','TRANSLATE-2856','API, Editor general - Login/Logout issues','Fixed a race condition on logout that sometimes resulted in HTML being parsed as javascript.',15,'bugfix','5.7.9'),
(1184,'2022-02-24','TRANSLATE-2853','Editor general - User association error','Solves problem when assigning users in import wizard after a workflow is changed and the current import produces only one task.',15,'bugfix','5.7.9'),
(1185,'2022-02-24','TRANSLATE-2846','Task Management - Filter on QA errors column is not working','FIX: Sorting/Filtering of column \"QS Errors\" in task grid now functional',15,'bugfix','5.7.9'),
(1186,'2022-02-24','TRANSLATE-2818','Auto-QA - Length-Check must Re-Evaluate also when processing Repititions','FIX: AutoQA now re-evaluates the length check for each segment individually when saving repititions',15,'bugfix','5.7.9'),
(1187,'2022-03-03','TRANSLATE-2872','Import/Export - Implement a URL callback triggered after task import is finished','Now a URL can be configured (runtimeOptions.import.callbackUrl) to be called after a task was imported. \nThe URL is called via POST and receives the task object as JSON. So systems creating tasks via API are getting now immediate answer if the task is imported. The status of the task (error on error, or open on success) contains info about the import success. If the task import is running longer as 48 hours, the task is set to error and the callback is called too.',15,'feature','5.7.9'),
(1188,'2022-03-03','TRANSLATE-2860','TermPortal - Attribute levels should be collapsed by default','Entry-level images added to language-level ones in Images-column of Siblings-panel',15,'feature','5.7.9'),
(1189,'2022-03-03','TRANSLATE-2483','InstantTranslate - Save InstantTranslate translation to TM','Enables translation to be saved to \"Instant-Translate\" TM memory. For more info how this should be used, check this link: https://confluence.translate5.net/display/TAD/InstantTranslate',15,'feature','5.7.9'),
(1190,'2022-03-03','TRANSLATE-2882','Main back-end mechanisms (Worker, Logging, etc.) - Calling updateProgress on export triggers error in the GUI','The progress update was also triggered on exports, causing some strange task undefined errors in the GUI.',15,'bugfix','5.7.9'),
(1191,'2022-03-03','TRANSLATE-2879','TermPortal - termPM-role have no sufficient rights to transfer terms from TermPortal','Fixed: terms transfer was unavailable for termPM-users',15,'bugfix','5.7.9'),
(1192,'2022-03-03','TRANSLATE-2878','Editor general - Metadata export error with array type filter','Filtered tasks with multiple option filter will no longer produce an error when Export meta data is clicked.',15,'bugfix','5.7.9'),
(1193,'2022-03-03','TRANSLATE-2876','Search & Replace (editor) - Search and replace match case search','Error will no longer happen when searching with regular expression with match-case on.',15,'bugfix','5.7.9'),
(1194,'2022-03-03','TRANSLATE-2875','Import/Export - Task Entity not found message on sending a invalid task setup in upload wizard','The message \"Task Entity not found\" was sometimes poping up when creating a new task with invalid configuration.',15,'bugfix','5.7.9'),
(1195,'2022-03-03','TRANSLATE-2874','InstantTranslate, MatchAnalysis & Pretranslation - MT stops pre-translation at first repeated segment','On pre-translating against MT only, repetitions are producing an error, preventing the pre-translation to be finshed. ',15,'bugfix','5.7.9'),
(1196,'2022-03-03','TRANSLATE-2871','InstantTranslate - Instant-translate result list name problem','Problem with listed results in instant translate with multiple resources with same name.',15,'bugfix','5.7.9'),
(1197,'2022-03-03','TRANSLATE-2870','Task Management - Deleting a cloned task deletes the complete project','This bug affects only projects containing one target task. If this single task is cloned, and the original task was deleted, the whole project was deleted erroneously. This is changed now by implicitly creating a new project for such tasks. ',15,'bugfix','5.7.9'),
(1198,'2022-03-03','TRANSLATE-2858','TermPortal - Proposal for Term entries cant be completed','Fixed proposal creation when newTermAllLanguagesAvailable config option is Off',15,'bugfix','5.7.9'),
(1199,'2022-03-03','TRANSLATE-2854','TermPortal - Term-portal error: join(): Argument #1 ($pieces) must be of type array, string given','Fixed bug in loading terms.',15,'bugfix','5.7.9'),
(1200,'2022-03-07','TRANSLATE-2889','API - logoutOnWindowClose does not work','If just closing the application window the user is now logged out correctly (if configured).',15,'bugfix','5.7.9'),
(1201,'2022-03-07','TRANSLATE-2888','Comments, VisualReview / VisualTranslation - Commenting a segment via Visual does not work','The creation of comments in Visual by clicking in the Visual window was not working any more.\n',15,'bugfix','5.7.9'),
(1202,'2022-03-07','TRANSLATE-2887','Editor general - Search/Replace is not working sometimes','Make Search/Replace work again on tasks with many segments.',15,'bugfix','5.7.9'),
(1203,'2022-03-08','TRANSLATE-2892','Import/Export - Visual Mapping source for project uploads','Solves problem of visual mapping not set to the correct value for project imports.',15,'bugfix','5.7.9'),
(1204,'2022-03-17','TRANSLATE-2895','Import/Export - Optionally remove single tags and bordering tag pairs at segment borders','The behaviour how tags are ignored from XLF (not SDLXIFF!) imports has been improved so that all surrounding tags can be ignored right now. The config runtimeOptions.import.xlf.ignoreFramingTags has therefore been changed and has now 3 config values: disabled, paired, all. Where paired ignores only tag pairs at the start and end of a segment, and all ignores all tags before and after plain text. Tags inside of text (and their paired partners) remain always in the segment. The new default is to ignore all tags, not only the paired ones.',15,'feature','5.7.9'),
(1205,'2022-03-17','TRANSLATE-2891','TermPortal - Choose in TermTranslation Workflow, if definitions are translated','It\'s now possible to choose whether definition-attributes should be exported while exporting terms from TermPortal to main Translate5 app',15,'feature','5.7.9'),
(1206,'2022-03-17','TRANSLATE-2899','VisualReview / VisualTranslation - Base Work for Visual API tests','Added capabilities for generating API -tests for the Visual',15,'change','5.7.9'),
(1207,'2022-03-17','TRANSLATE-2897','Import/Export - Make XML Parser more standard conform','The internal used XML parser was not completly standard conform regarding the naming of tags.',15,'change','5.7.9'),
(1208,'2022-03-17','TRANSLATE-2906','TBX-Import - Improve slow TBX import of huge TBX files','Due a improvement in TBX term ID handling, the import performance for bigger TBX files was reduced. This is repaired now.',15,'bugfix','5.7.9'),
(1209,'2022-03-17','TRANSLATE-2900','OpenId Connect - Auto-set roles for sso authentications','Auto set roles is respected in SSO created users.',15,'bugfix','5.7.9'),
(1210,'2022-03-17','TRANSLATE-2898','Editor general - Disable project deletion while task is importing','Now project can not be deleted while there is a running project-task import.',15,'bugfix','5.7.9'),
(1211,'2022-03-17','TRANSLATE-2896','Editor general - Remove null safe operator from js code','Javascript code improvement.',15,'bugfix','5.7.9'),
(1212,'2022-03-17','TRANSLATE-2883','VisualReview / VisualTranslation - Enable visual with source website, html, xml/xslt and images to provide more than 19 pages','FIX: The Pager for the visual now shows reviews with more than 9 pages properly.',15,'bugfix','5.7.9'),
(1213,'2022-03-17','TRANSLATE-2868','Editor general - Jump to segment on task open: priority change','URL links to segments work now. The segment id from the URL hash gets prioritized over the last edited segment id.',15,'bugfix','5.7.9'),
(1214,'2022-03-17','TRANSLATE-2859','TermPortal - Change logic, who can edit and delete attributes','The rights who can delete terms are finer granulated right now.',15,'bugfix','5.7.9'),
(1215,'2022-03-17','TRANSLATE-2849','Import/Export - Disable Filename-Matching for 1:1 Files, it is possible to upload matching-faults','File-name matching in visual for single project tasks is disabled and additional import project wizard improvements.',15,'bugfix','5.7.9'),
(1216,'2022-03-17','TRANSLATE-2345','Editor general, TrackChanges - Cursor jumps to start of segment, when user enters space and stops typing for a while','FIX: Cursor Jumps when inserting Whitespace, SpellChecking and in various other situations',15,'bugfix','5.7.9'),
(1217,'2022-03-22','TRANSLATE-2915','Okapi integration - Optimize okapi android xml and ios string settings','Settings for android xml and IOs string files were optimized to protect certain tag structures, cdata and special characters',15,'change','5.7.9'),
(1218,'2022-03-22','TRANSLATE-2907','InstantTranslate - Improve FileTranslation in InstantTranslate','InstantTranslate FileTranslation always starts direct after selecting (or Drag\'nDrop) the file no matter what is configed for runtimeOptions.InstantTranslate.instantTranslationIsActive',15,'change','5.7.9'),
(1219,'2022-03-22','TRANSLATE-2903','TermPortal - Batch edit for Process Status and Usage Status attrs','TermPortal: batch editing is now possible for Process Status and Usage Status attributes',15,'change','5.7.9'),
(1220,'2022-03-22','TRANSLATE-2920','Editor general - REVERT:  TRANSLATE-2345-fix-jumping-cursor','ROLLBACK: Fix for jumping cursor reverted',15,'bugfix','5.7.9'),
(1221,'2022-03-22','TRANSLATE-2912','Import/Export - reviewHTML.txt import in zip file does not work anymore','Fixes a problem where reviewHTML.txt file in the zip import package is ignored.',15,'bugfix','5.7.9'),
(1222,'2022-03-22','TRANSLATE-2911','Editor general - Cursor jumps to start of segment','FIX: Cursor Jumps when SpellChecker runs and after navigating with arrow-keys ',15,'bugfix','5.7.9'),
(1223,'2022-03-22','TRANSLATE-2905','InstantTranslate - No usable error message on file upload error due php max file size reached','Custom error message when uploading larger files as allowed in instant-translate.',15,'bugfix','5.7.9'),
(1224,'2022-03-22','TRANSLATE-2890','Main back-end mechanisms (Worker, Logging, etc.) - Module redirect based on initial_page acl','Authentication acl improvements',15,'bugfix','5.7.9'),
(1225,'2022-03-22','TRANSLATE-2848','Import/Export - TermCollection not listed in import wizard','Language resources will be grouped by task in language-resources to task association panel in the import wizard.',15,'bugfix','5.7.9'),
(1226,'2022-03-30','TRANSLATE-2697','VisualReview / VisualTranslation - General plugin which parses a visual HTML source from a reference file','Added capabilities to download the visual source from an URL embedded in a reference XML file',15,'feature','5.7.9'),
(1227,'2022-03-30','TRANSLATE-2923','MatchAnalysis & Pretranslation - Enable 101% Matches to be shown as <inContextExact in Trados analysis XML export','A matchrate of 101% may be mapped to InContextExact matches in the analysis XML export for Trados (if configured: runtimeOptions.plugins.MatchAnalysis.xmlInContextUsage)',15,'change','5.7.9'),
(1228,'2022-03-30','TRANSLATE-2938','Editor general - Remove the limit from the global customer switch','The global customer dropdown has shown only 20 customers, now all are show.',15,'bugfix','5.7.9'),
(1229,'2022-03-30','TRANSLATE-2937','Main back-end mechanisms (Worker, Logging, etc.) - Workflow user prefs loading fails on importing task','Solves a problem with user preferences in importing tasks.',15,'bugfix','5.7.9'),
(1230,'2022-03-30','TRANSLATE-2934','VisualReview / VisualTranslation - Bookmark segment in visual does not work','The segment bookmark filter button in the simple view mode of visual review was not working, this is fixed.',15,'bugfix','5.7.9'),
(1231,'2022-03-30','TRANSLATE-2930','InstantTranslate - Instant-translate task types listed in task overview','Pre-translated files with instant-translate will not be listed anymore as tasks in task overview.',15,'bugfix','5.7.9'),
(1232,'2022-03-30','TRANSLATE-2922','MatchAnalysis & Pretranslation - 103%-Matches are shown in wrong category in Trados XML Export','A matchrate of 103% must be mapped to perfect matches in the analysis XML export for Trados (was previously mapped to InContextExact).',15,'bugfix','5.7.9'),
(1233,'2022-03-30','TRANSLATE-2921','TermPortal - Batch edit should only change all terms on affected level','Batch editing was internally changed, so the only selected terms and language- and termEntry- levels of selected terms are affected.',15,'bugfix','5.7.9'),
(1234,'2022-03-30','TRANSLATE-2844','Import/Export - upload wizard is blocked by zip-file as reference file','Disallow zip files to be uploaded as a reference file via the UI, since they can not be processed and were causing errors.',15,'bugfix','5.7.9'),
(1235,'2022-03-30','TRANSLATE-2835','OpenTM2 integration - Repair invalid OpenTM2 TMX export','Depending on the content in the TM the exported TMX may result in invalid XML. This is tried to be fixed as best as possible to provide valid XML.',15,'bugfix','5.7.9'),
(1236,'2022-03-30','TRANSLATE-2766','Client management - Change client sorting in drop-downs to alphabethically','All over the application clients in the drop-downs were sorted by the order, they have been added to the application. Now they are sorted alphabetically.',15,'bugfix','5.7.9'),
(1237,'2022-04-26','TRANSLATE-2949','Configuration, User Management - Make settings for new users pre-configurable','Enable setting default pre-selected source and target languages in instant translate. For more info how this can be configured, please check the config option runtimeOptions.InstantTranslate.user.defaultLanguages in this link\nhttps://confluence.translate5.net/display/TAD/InstantTranslate',15,'feature','5.7.9'),
(1238,'2022-04-26','TRANSLATE-2869','Import/Export, Task Management - Export of editing history of a task','Provide for PMs the possibility to download the tasks content as spreadsheet containing all segments, with the pre-translated target and the target content after each workflow step.',15,'feature','5.7.9'),
(1239,'2022-04-26','TRANSLATE-2822','MatchAnalysis & Pretranslation - Match Analysis on a character basis','Match analysis now can be displayed on character or word base.',15,'feature','5.7.9'),
(1240,'2022-04-26','TRANSLATE-2779','Auto-QA - QA check for leading/trailing white space in segments','Added check for 3 different kinds of leading/trailing whitespaces within a segment',15,'feature','5.7.9'),
(1241,'2022-04-26','TRANSLATE-2762','InstantTranslate - Enable tags in InstantTranslate text field','Instant Translate now supports using HTML markup in the text to translate. Tag-errors maybe caused by the used services (e.g. DeepL) are automatically repaired when markup is submitted. Please note, that for the time, the typed markup is incomplete or the markup is syntactically incorrect, an error hinting at the invalidity of the markup is shown.',15,'feature','5.7.9'),
(1242,'2022-04-26','TRANSLATE-2952','Editor general - Automated workflow and user roles video','Integrates the automated workflow and user roles in translate5 help page.',15,'change','5.7.9'),
(1243,'2022-04-26','TRANSLATE-2902','Configuration, Task Management, TermPortal - Send e-mail to specific PM on creation of project through TermTranslation Workflow','Added system config to specify user to be assigned as PM for termtranslation-projects by default, and to send an email notification to that user on termtranslation-project creation',15,'change','5.7.9'),
(1244,'2022-04-26','TRANSLATE-2958','TermPortal - TermCollection not updateable after deleting the initial import user','If a user was deleted, and this user has imported a TBX, the resulting term collection could no be updated by re-importing a TBX anymore. This is fixed.',15,'bugfix','5.7.9'),
(1245,'2022-04-26','TRANSLATE-2955','LanguageResources, OpenTM2 integration - Segment can not be saved if language resource is writable and not available','If a language resource is assigned writable to a task and the same language resource is not available, the segment can not be saved.',15,'bugfix','5.7.9'),
(1246,'2022-04-26','TRANSLATE-2954','Import/Export - If Import reaches PHP max_file_uploads limit there is no understandable error message','If the amount of files reaches the configured max_file_uploads in PHP there is no understandable error message for the user what is the underlying reason why the upload is failing. ',15,'bugfix','5.7.9'),
(1247,'2022-04-26','TRANSLATE-2953','Import/Export - Create task without selecting file','Fixes a problem where the import wizard form could be submitted without selecting a valid workfile.',15,'bugfix','5.7.9'),
(1248,'2022-04-26','TRANSLATE-2951','API, InstantTranslate - Instant-translate filelist does not return the taskId','Fixes a problem where the task-id was not returned as parameter in the instant-translate filelist api call.',15,'bugfix','5.7.9'),
(1249,'2022-04-26','TRANSLATE-2947','Import/Export - Can not import SDLXLIFF where sdl-def tags are missing','For historical reasons sdl-def tags were mandatory in SDLXLIFF trans-units, which is not necessary anymore.',15,'bugfix','5.7.9'),
(1250,'2022-04-26','TRANSLATE-2924','InstantTranslate - translate file not usable in InstantTranslate','Improved GUI behaviour, file translation is always selectable and shows an Error-message if no translation service is available for the selected languages. Also, when changing languages the mode is not automatically reset to \"text translation\" anymore',15,'bugfix','5.7.9'),
(1251,'2022-04-26','TRANSLATE-2862','InstantTranslate - Issue with the usage of \"<\" in InstantTranslate','BUGFIX InstantTranslate Plugin: Translated text is not terminated anymore after a single \"<\" in the original text',15,'bugfix','5.7.9'),
(1252,'2022-04-26','TRANSLATE-2850','Import/Export - File review.html created in import-zip, even if not necessary','reviewHtml.txt will be no longer created when there are no visual-urls defined on import.',15,'bugfix','5.7.9'),
(1253,'2022-04-26','TRANSLATE-2843','Import/Export - translate5 requires target language in xliff-file','Xml based files where no target language is detected on import(import wizard), will be imported as non-bilingual files.',15,'bugfix','5.7.9'),
(1254,'2022-04-26','TRANSLATE-2799','LanguageResources - DeepL API - some languages missing compared to https://www.deepl.com/translator','All DeepL resources where the target language is EN or PT, will be changed from EN -> EN-GB and PT to PT-PT. The reason for this is a recent DeepL api change.',15,'bugfix','5.7.9'),
(1255,'2022-04-26','TRANSLATE-2534','Editor general - Enable opening multiple tasks in multiple tabs','Multiple tasks can now be opened in different browser tabs within the same user session at the same time. This is especially interesting for embedded usage of translate5 where tasks are opened via custom links instead of the translate5 internal task overview.',15,'bugfix','5.7.9'),
(1256,'2022-05-10','TRANSLATE-2960','VisualReview / VisualTranslation - Enable Markup processing in Subtitle Import parsers','Visual Video: Enable markup protection in internal tags as well as whitespace  for the import',15,'change','5.7.9'),
(1257,'2022-05-10','TRANSLATE-2931','Okapi integration, Task Management - Import file format and segmentation settings - Bconf Management (Milestone 1)','Translate5 can now manage Okapi BatchConfiguration files - needed for configuring the import file filters. Admins and PMs can upload, download, rename Bconfs and upload and download contained SRX files in the new \'File format and segmentation settings\' grid under \'Preferences\'. It is also available under \'Clients\' to easily handle specific requirements of different customers. You can also set a default there, which overrides the one from the global perspective. During Project Creation a dropdown menu presents the available Bconf files for the chosen client, preset with the configured default. The selected one is then passed to Okapi on import.',15,'change','5.7.9'),
(1258,'2022-05-10','TRANSLATE-2901','InstantTranslate - Languageresource type filter in instanttranslate API','ENHANCEMENT: Added filters to filter InstantTranslate API for language resource types and id\'s. See https://confluence.translate5.net/display/TAD/InstantTranslate for details\n\nFIX: fixed whitespace-rendering in translations when Translation Memories were requested and text to translate was segmented therefore',15,'change','5.7.9'),
(1259,'2022-05-10','TRANSLATE-2884','Main back-end mechanisms (Worker, Logging, etc.) - Further restrict nightly error mail summaries','A new role systemadmin is added, to be used only for technical people and translate5 system administrators. \nOnly users with that role will receive the nightly error summary e-mail in the future (currently all admins). Only systemadmins can set the role systemadmin and api.\nFor hosted clients: contact us so that we can enable the right for desired users.\nFor on premise clients: the role must be added manually in the DB to one user. With that user the role can then be set on other users.',15,'change','5.7.9'),
(1260,'2022-05-10','TRANSLATE-2962','LanguageResources - DeepL error when when sending large content','Fixes problem with failing request to DeepL because of exhausted request size.',15,'bugfix','5.7.9'),
(1261,'2022-05-10','TRANSLATE-2961','Editor general - Error on repetition save','Solves a problem where an error happens in the UI after saving repetitions with repetition editor.',15,'bugfix','5.7.9'),
(1262,'2022-05-10','TRANSLATE-2959','OpenId Connect - Overlay for SSO login auto-redirect','Adds overlay when auto-redirecting with SSO authentication.',15,'bugfix','5.7.9'),
(1263,'2022-05-10','TRANSLATE-2957','OpenId Connect - Missing default text on SSO button','When configuring SSO via OpenID no default button text is provided, therefore the SSO Login button may occur as button without text - not recognizable as button then.',15,'bugfix','5.7.9'),
(1264,'2022-05-10','TRANSLATE-2910','TermPortal, User Management - Role rights for approval workflow of terms in the TermPortal','Terms/attributes editing/deletion access logic reworked for Term Proposer, Term Reviewer and Term Finalizer roles',15,'bugfix','5.7.9'),
(1265,'2022-05-10','TRANSLATE-2558','Editor general - Task focus after login','On application load always the first project was selected, instead the one given in the URL. This is fixed now. Other application parts (like preferences or clients) can now also opened directly after application start by passing its section in the URL.',15,'bugfix','5.7.9'),
(1266,'2022-05-24','TRANSLATE-2642','LanguageResources - DeepL terminology integration','Enable deepL language resources to use terminology as glossar.',15,'feature','5.7.9'),
(1267,'2022-05-24','TRANSLATE-2314','Editor general - Be able to lock/unlock segments in the editor by a PM','The project-manager is now able to lock and unlock single segments (CTRL+L). \nA jump to segment is implemented (CTRL+G).\nBookmarks can now be set also on just a selected segment, not only on an opened one (CTRL+D). Locking and bookmarking can be done in a batch way on all segments in the current filtered grid. ',15,'feature','5.7.9'),
(1268,'2022-05-24','TRANSLATE-2976','Okapi integration - Make MS Office document properties translatable by default','The Okapi default settings are changed, so that MS Office document properties are now translateable by default.\n',15,'change','5.7.9'),
(1269,'2022-05-24','TRANSLATE-2973','LanguageResources - Tag Repair creates Invalid Internal tags when Markup is too complex','FIX: Automatic tag repair may generated invalid internal tags when complex markup was attempted to be translated',15,'bugfix','5.7.9'),
(1270,'2022-05-24','TRANSLATE-2972','Editor general - Leaving and Navigating to Deleted Tasks','Trying to access a deleted task via URL was not handled properly. Now the user is redirected to the task overview.',15,'bugfix','5.7.9'),
(1271,'2022-05-24','TRANSLATE-2969','Import/Export - Reintroduce BCONF import via ZIP','FIX: Re-enabled using a customized BCONF for OKAPI via the import zip. Please note, that this feature is nevertheless deprecated and the BCONF in the import zip will not be added to the application\'s BCONF pool.',15,'bugfix','5.7.9'),
(1272,'2022-05-24','TRANSLATE-2968','LanguageResources - Deleted space at start or end of fuzzy match not highlighted','Fixed visualization issues of added / deleted white-space in the fuzzy match grind of the lower language resource panel in the editor.',15,'bugfix','5.7.9'),
(1273,'2022-05-24','TRANSLATE-2967','TermPortal - TermPortal: grid-attrs height problem','Fixed the tiny height of attribute grids. ',15,'bugfix','5.7.9'),
(1274,'2022-05-24','TRANSLATE-2965','GroupShare integration - GroupShare sync deletes all associations between tasks and language-resources','The synchronization of GroupShare TMs was deleting to much task language resource associations.',15,'bugfix','5.7.9'),
(1275,'2022-05-24','TRANSLATE-2964','Workflows - PM Project Notification is triggered on each project instead only on term translation projects','Project creation notifications can now be sent only for certain project types.',15,'bugfix','5.7.9'),
(1276,'2022-05-24','TRANSLATE-2926','Okapi integration - Index and variables can not be extracted from Indesign','So far it was not possible to translate Indesign text variables and index entries, because Okapi did not extract them.\n\nWith an okapi contribution by Denis, financed by translate5, this is changed now.\n\nAlso translate5 default okapi settings are changed, so that text variables and index entries are now translated by default for idml.',15,'bugfix','5.7.9'),
(1277,'2022-06-14','TRANSLATE-2811','Editor general, LanguageResources - Integrate MS Translator synonym search in editor','Microsoft\'s translator synonym search is now part of translate5 editor.',15,'feature','5.7.9'),
(1278,'2022-06-14','TRANSLATE-2539','Auto-QA - AutoQA: Numbers check','AutoQA: added 12 number-checks from SNC library',15,'feature','5.7.9'),
(1279,'2022-06-14','TRANSLATE-2986','Main back-end mechanisms (Worker, Logging, etc.) - Trigger callback when all users did finish the assigned role','After all jobs are finished, callback workflow action can be configured. How this can be configured it is explained in this link:  https://confluence.translate5.net/display/BUS/Workflow+Action+and+Notification+Customization#:~:text=Remote%20callback%20when%20all%20users%20finish%20there%20jobs',15,'change','5.7.9'),
(1280,'2022-06-14','TRANSLATE-2978','Editor Length Check - Disable automatic adding of newlines on segments by configuration','The automatic adding of newlines could now disabled by configuration.',15,'change','5.7.9'),
(1281,'2022-06-14','TRANSLATE-2985','Editor general - Error on configuration overview filtering','The error which pops-up when quick-typing in configuration filter is solved.',15,'bugfix','5.7.9'),
(1282,'2022-06-14','TRANSLATE-2983','Editor general - Task action menu error after leaving a task','Opening the task action menu after leaving the task will no longer produce error.',15,'bugfix','5.7.9'),
(1283,'2022-06-14','TRANSLATE-2982','TermPortal, TermTagger integration - Empty term in TBX leads to crashing termtagger','If an imported TBX was containing empty terms (which is basically non sense) and that term collection was then used for termtagging in asian languages, the termtagger was hanging in an endless loop and was not usable anymore.',15,'bugfix','5.7.9'),
(1284,'2022-06-14','TRANSLATE-2981','TBX-Import - Importing TBX with invalid XML leads to high CPU usage','On importing a TBX file with invalid XML the import process was caught in an endless loop. This is fixed and the import stops now with an error message.',15,'bugfix','5.7.9'),
(1285,'2022-06-14','TRANSLATE-2980','Editor general - On task delete translate5 keeps the old route','Missing task message when the task is removed will no longer be shown.',15,'bugfix','5.7.9'),
(1286,'2022-06-30','TRANSLATE-2984','Task Management - Archive and delete old tasks','Implement a workflow action to export ended tasks, save the export (xliff2 and normal export) to a configurable destination and delete the task afterwards.\nThis action is disabled by default.',15,'feature','5.7.9'),
(1287,'2022-06-30','TRANSLATE-2855','MatchAnalysis & Pretranslation - Pre-translate pivot language with language resource','Pivot segments in task now can be be filled/translated using language resources. For api usage check this link: https://confluence.translate5.net/display/TAD/LanguageResources%3A+pivot+pre-translation',15,'feature','5.7.9'),
(1288,'2022-06-30','TRANSLATE-2839','OpenTM2 integration - Attach to t5memory service','Structural adjustments for t5memory service.',15,'feature','5.7.9'),
(1289,'2022-06-30','TRANSLATE-2988','LanguageResources - Make translate5 fit for switch to t5memory','Add some fixes and data conversions when exporting a TMX from OpenTM2 so that it can be imported into t5memory.',15,'change','5.7.9'),
(1290,'2022-06-30','TRANSLATE-2992','Main back-end mechanisms (Worker, Logging, etc.) - PHP\'s setlocale has different default values','The PHP\'s system locale was not correctly set. This is due a strange behaviour setting the default locale randomly.',15,'bugfix','5.7.9'),
(1291,'2022-06-30','TRANSLATE-2990','OpenTM2 integration - Improve error handling on task re-import into TM','Sometimes the re-import a task into a TM feature was hanging and blocking the task. This is solved, the task is reopened in the case of an error and the logging was improved.',15,'bugfix','5.7.9'),
(1292,'2022-06-30','TRANSLATE-2989','Import/Export - XLIFF2 export is failing','The XLIFF 2 export was failing if the imported tasks was containing one file which was ignored on import (for example if all segments were tagged with translate no)',15,'bugfix','5.7.9'),
(1293,'2022-07-22','TRANSLATE-3002','Workflows - Ask for task finish on task close too','Added dialog shown on leaving the application in embedded mode, with finish task and just leave as possible choices. Added config option to control whether such dialog should be shown.',15,'feature','5.7.9'),
(1294,'2022-07-22','TRANSLATE-2999','TermPortal - Create missing term attributes datatype foreign key','Fixed problem with missing data types for term attributes in term portal.',15,'change','5.7.9'),
(1295,'2022-07-22','TRANSLATE-3007','InstantTranslate - Instant translate search content with tags','FIXED Bug in Instanttranslate when segmented results are processed due to a missing API',15,'bugfix','5.7.9'),
(1296,'2022-07-22','TRANSLATE-3006','LanguageResources - Problem with DeepL target language','Fixes problem where the DeepL language resource target language was saved as lowercase value.',15,'bugfix','5.7.9'),
(1297,'2022-07-22','TRANSLATE-3004','Editor general - Error on deleting project','Solves problem where error pop-up was shown when deleting project.',15,'bugfix','5.7.9'),
(1298,'2022-07-22','TRANSLATE-3000','Editor general - Use project task store for task reference in import wizard','Solves problem in import wizard when assigning task users.',15,'bugfix','5.7.9'),
(1299,'2022-07-22','TRANSLATE-2996','MatchAnalysis & Pretranslation - Analysis grid reconfigure leads to an error','Solves problem with front-end error in match analysis overview.',15,'bugfix','5.7.9'),
(1300,'2022-07-22','TRANSLATE-2995','Main back-end mechanisms (Worker, Logging, etc.) - Event logger error','Fixed back-end error with workflow actions info logging.',15,'bugfix','5.7.9'),
(1301,'2022-07-22','TRANSLATE-2987','Task Management - Routing problems when jumping from and to project overview','Fixed a problem where the selected task was not focused after switching between the overviews.',15,'bugfix','5.7.9'),
(1302,'2022-07-22','TRANSLATE-2963','Main back-end mechanisms (Worker, Logging, etc.), MatchAnalysis & Pretranslation - Queuing matchanalysis multiple times leads to locked tasks','FIX: Prevent running multiple operations for the same task',15,'bugfix','5.7.9'),
(1303,'2022-07-22','TRANSLATE-2813','Client management, LanguageResources, Task Management, User Management - Copy&paste content of PM grids','Now you can copy text from all grids cells in translate5.',15,'bugfix','5.7.9'),
(1304,'2022-07-22','TRANSLATE-2786','Import/Export - xliff 1.2 import fails if a g tag contains a mrk segment tag','The XLF import fails if there are g tags surrounding the mrk segmentation tags.',15,'bugfix','5.7.9'),
(1305,'2022-08-05','TRANSLATE-3010','LanguageResources - Set default pivot language in systemconfiguration','Default task pivot languages can be configured for each customer.',15,'feature','5.7.9'),
(1306,'2022-08-05','TRANSLATE-2812','Editor general, LanguageResources - Send highlighted word in segment to concordance search or synonym search','Enables selected text in editor to be send as synonym or concordance search.',15,'feature','5.7.9'),
(1307,'2022-08-05','TRANSLATE-2538','Auto-QA - AutoQA: Include Spell-, Grammar- and Style-Check','All spelling, grammar and style errors found by languagetool for all segments of a task are now listed in AutoQA and it is possible to filter the segments by error type.\nIn addition errors are now not only marked in the segment open for editing, but also in all other segments.\nIn addition there are now many more subtypes for errors (before we had only spelling, grammar and style).',15,'feature','5.7.9'),
(1308,'2022-08-05','TRANSLATE-3008','LanguageResources - Change tooltip for checkbox \"Pre-translate (MT)\"','Improves tooltip texts in match analysis.',15,'change','5.7.9'),
(1309,'2022-08-05','TRANSLATE-2932','Okapi integration, Task Management - BCONF Management Milestone 2','BCONF Management Milestone 2\n* adds capabilities to upload/update the SRX files embedded in a BCONF\n* adds the frontend to manage the embedded filters/FPRM\'s of a bconf together with the related extension-mapping\n* New filters can be created by cloning existing (customized or default) ones\n* adds capabilities to generally edit and validate filters/FPRM\'s\n* adds frontend editors for the following filters: okf_html, okf_icml, okf_idml, okf_itshtml5, okf_openxml, okf_xml, okf_xmlstream',15,'change','5.7.9'),
(1310,'2022-08-05','TRANSLATE-3022','Editor general - RXSS with help page editordocumentation possible','Security related fix.',15,'bugfix','5.7.9'),
(1311,'2022-08-05','TRANSLATE-3020','Editor general - PXSS on showing reference files','Security related fix.',15,'bugfix','5.7.9'),
(1312,'2022-08-05','TRANSLATE-3011','Import/Export - Extend error handling in xlf parser','Error handling code improvement for xlf parser.',15,'bugfix','5.7.9'),
(1313,'2022-08-05','TRANSLATE-3009','Editor general - Base tooltip class problem','Fix for a general problem when tooltips are shown in some places in the application.',15,'bugfix','5.7.9'),
(1314,'2022-08-05','TRANSLATE-2935','Auto-QA, TermTagger integration - Avoid term-check false positive in case of homonyms and display homonyms in source and target','TermTagger: Fixed term-check false positives in case of homonyms',15,'bugfix','5.7.9'),
(1315,'2022-08-05','TRANSLATE-2063','Import/Export - Enable parallele use of multiple okapi versions to fix Okapi bugs','Multiple okapi instances can be configured and used for task imports.',15,'bugfix','5.7.9'),
(1316,'2022-08-18','TRANSLATE-2380','VisualReview / VisualTranslation - Visual: Also connect segments, that contain variables with the layout','Visual: Segmentation of PDF/HTML based reviews now finds segments containing variables in the layout\nFIX: The Segmentation result is now calculated for all visual files together\nFIX: Alike Segments may have been not updated in the layout when changing the master',15,'change','5.7.9'),
(1317,'2022-08-18','TRANSLATE-3025','OpenTM2 integration - OpenTM2 returns sometimes empty source language','On TMX export from OpenTM2 the source xml:lang attribute of a segment was sometimes empty. This is fixed now for a proper migration to t5memory.',15,'bugfix','5.7.9'),
(1318,'2022-08-18','TRANSLATE-3024','LanguageResources - Solve Problems with Empty Sources and TMs','FIX: Empty sources in segments lead to errors when saving them to Translation Memories',15,'bugfix','5.7.9'),
(1319,'2022-08-18','TRANSLATE-2916','VisualReview / VisualTranslation - Repetitions in the segment grid are not linked to the visual','NOTHING TO MENTION, issue resolved with TRANSLATE-2380',15,'bugfix','5.7.9'),
(1320,'2022-09-01','TRANSLATE-3019','Configuration - Support Subnets in IP-based authentication','Change IpAuthentication plugin to support subnet masks, e.g. 192.168.0.1/24',15,'feature','5.7.9'),
(1321,'2022-09-01','TRANSLATE-3016','Configuration, Editor general, TermTagger integration - Show and use only terms of a certain process level in the editor','What kind of process status the terms has, used for term tagging and listed in the editor term-portlet  can be configured as system, client and task level.',15,'feature','5.7.9'),
(1322,'2022-09-01','TRANSLATE-3015','TBX-Import - Merge multiple attributes of the same type in TBX import','Two attributes will be merged into one if they are from the same type and appear on same level.',15,'feature','5.7.9'),
(1323,'2022-09-01','TRANSLATE-3014','Editor general - Show color of TermCollection behind term in editors termportlet','Term collection color will be listed in the term portlet for each term.',15,'feature','5.7.9'),
(1324,'2022-09-01','TRANSLATE-3003','Editor general - Show term attributes in term-portlet of translate5s editor','Tooltip with the term entry, language and term attributes will be show with mouse over the terms in the term portlet in editor.',15,'feature','5.7.9'),
(1325,'2022-09-01','TRANSLATE-3045','TermTagger integration - Optimize terms_term indexes','Improve the DB indizes for the terms_term table.',15,'bugfix','5.7.9'),
(1326,'2022-09-01','TRANSLATE-3043','SpellCheck (LanguageTool integration) - spellcheck markup is destroying internal tags','SpellCheck: Multi-whitespaces are now respected while applying spellcheck styles',15,'bugfix','5.7.9'),
(1327,'2022-09-01','TRANSLATE-3041','Auto-QA, Editor general - Wrong whitespace tag numbering leads to non working whitespace added QA check','The internal numbering of whitespace tags (newline, tab etc) was not consistent anymore between source and target, therefore the whitespace added auto QA is producing a lot of false positives.',15,'bugfix','5.7.9'),
(1328,'2022-09-01','TRANSLATE-3030','Auto-QA - Fixes Spellcheck-QA-Worker: Index for state-field, proper solution for logging / \"last worker\"','FIX: Spellcheck AutoQA-worker was lacking an database-Index, with the index spellchecking should be faster on import',15,'bugfix','5.7.9'),
(1329,'2022-09-01','TRANSLATE-3029','file format settings - IDML FPRM Editor too heigh','FIX: Height of IDML FPRM Editor too big on smaller screens so that buttons are not visible',15,'bugfix','5.7.9'),
(1330,'2022-09-01','TRANSLATE-3028','Main back-end mechanisms (Worker, Logging, etc.) - Reset password error','Fix for a problem where the user was not able to reset the password.',15,'bugfix','5.7.9'),
(1331,'2022-09-20','TRANSLATE-3038','Editor general - Integrate anti virus software (4.6)','SECURITY ENHANCEMENT: Added blacklist to limit uploadable reference file types',15,'feature','5.7.11'),
(1332,'2022-09-20','TRANSLATE-3016','Configuration, Editor general, TermTagger integration - Show and use only terms of a certain process level in the editor','Only the terms with a defined process status are used for term tagging and listed in the editor term-portlet. The configuration is runtimeOptions.termTagger.usedTermProcessStatus. ',15,'feature','5.7.11'),
(1333,'2022-09-20','TRANSLATE-3057','TermPortal - Extend term status map','Extend the term status mapping with additional types.',15,'change','5.7.11'),
(1334,'2022-09-20','TRANSLATE-3040','User Management - On password change the old one must be given (4.8)','If a user is changing his password, the old password must be given and validated too, to prevent taking over stolen user accounts.',15,'change','5.7.11'),
(1335,'2022-09-20','TRANSLATE-3056','Auto-QA - MQM Controller does not activate when changing task after deactivation','FIX: After deactivating MQM, it was not activated anymore when opening the next task',15,'bugfix','5.7.11'),
(1336,'2022-09-20','TRANSLATE-3051','User Management - Add SALT to MD5 user password (4.4)','The user passwords are now stored in a more secure way.',15,'bugfix','5.7.11'),
(1337,'2022-09-20','TRANSLATE-3050','Import/Export - Whitespace tag handling did encode internal tag placeholders on display text import filter','Fix for a proprietary import filter.',15,'bugfix','5.7.11'),
(1338,'2022-09-20','TRANSLATE-3041','Auto-QA, Editor general - Wrong whitespace tag numbering leads to non working whitespace added QA check','The internal numbering of whitespace tags (newline, tab etc) was not consistent anymore between source and target, therefore the whitespace added auto QA is producing a lot of false positives.',15,'bugfix','5.7.11'),
(1339,'2022-09-20','TRANSLATE-3036','VisualReview / VisualTranslation - Visual: Do not update blocked empty segments, fix multiple-variables segments in variable segmentation','FIX: Visual: Segments with several singular internal tags seen as variables were not detected\r\nFIX: Visual: A hidden left iframe may prevented a proper update with the current segments in the right iframes layout\r\nENHANCEMENT: Visual: Empty blocked segments are not updated (=deleted) in the layout anymore',15,'bugfix','5.7.11'),
(1340,'2022-09-20','TRANSLATE-3035','SpellCheck (LanguageTool integration) - UI spellcheck is not working after a task with disabled spellcheck was opened','Spellcheck remained disabled for other tasks after opening one task where spellcheck was explicitly disabled with liveCheckOnEditing config.',15,'bugfix','5.7.11'),
(1341,'2022-09-20','TRANSLATE-3026','Editor general - Jump to task from task overview to project overview','Fix for the problem when clicking on jump to task action button in task overview, the project grid is stuck in endless reload loop.',15,'bugfix','5.7.11'),
(1342,'2022-09-22','TRANSLATE-2988','LanguageResources - Make translate5 fit for switch to t5memory','FIXED in 5.7.11: the language mapping to en-UK was used till 5.7.10 erroneously for saving and querying segments. To fix the affected languages are queried both in OpenTM2.\r\nAdd some fixes and data conversions when exporting a TMX from OpenTM2 so that it can be imported into t5memory.',15,'change','5.7.11');

--
-- Table structure for table `LEK_comment_meta`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_comment_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `commentId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_comments',
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `affectedField` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'source or target',
  `originalId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'The original comment ID for imported comments',
  `severity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'A severity value if given for imported comments',
  `version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'A version value if given for imported comments',
  PRIMARY KEY (`id`),
  UNIQUE KEY `commentId` (`commentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_comment_meta_ibfk_1` FOREIGN KEY (`commentId`) REFERENCES `LEK_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_comment_meta_ibfk_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_comment_meta`
--


--
-- Table structure for table `LEK_comments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segmentId` int(11) NOT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_comments_ibfk_1` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_comments_ibfk_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_comments`
--


--
-- Table structure for table `LEK_customer`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `searchCharacterLimit` int(11) DEFAULT 100000 COMMENT 'Maximum number of search characters in language resources of this customer',
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdServer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdServerRoles` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdAuth2Url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdClientId` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdClientSecret` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdRedirectLabel` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdRedirectCheckbox` tinyint(1) DEFAULT NULL,
  `openIdIssuer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdDefaultServerRoles` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number_UNIQUE` (`number`),
  UNIQUE KEY `domain_UNIQUE` (`domain`),
  KEY `name` (`name`),
  KEY `number` (`number`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_customer`
--

INSERT INTO `LEK_customer` VALUES
(1,'defaultcustomer','default for legacy data',100000,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);

--
-- Table structure for table `LEK_customer_config`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_customer_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerId` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerIdConfigName` (`customerId`,`name`),
  KEY `customerIdIdx` (`customerId`),
  KEY `configNameIdx` (`name`),
  CONSTRAINT `LEK_customer-LEK_customer_config-fk` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE,
  CONSTRAINT `Zf_configuration-LEK_customer_config-fk` FOREIGN KEY (`name`) REFERENCES `Zf_configuration` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_customer_config`
--


--
-- Table structure for table `LEK_customer_meta`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_customer_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customerId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_customer',
  `defaultBconfId` int(11) DEFAULT NULL COMMENT 'Foreign Key to LEK_okapi_bconf',
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerId` (`customerId`),
  KEY `fk-customer_meta-okapi_bconf` (`defaultBconfId`),
  CONSTRAINT `fk-customer_meta-customer` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk-customer_meta-okapi_bconf` FOREIGN KEY (`defaultBconfId`) REFERENCES `LEK_okapi_bconf` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_customer_meta`
--


--
-- Table structure for table `LEK_file_filter`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_file_filter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fileId` int(11) NOT NULL,
  `type` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'import',
  `filter` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index2` (`fileId`,`taskGuid`,`type`),
  KEY `fk_LEK_file_filter_1` (`taskGuid`),
  CONSTRAINT `fk_LEK_file_filter_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `fk_LEK_file_filter_2` FOREIGN KEY (`fileId`) REFERENCES `LEK_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_file_filter`
--


--
-- Table structure for table `LEK_files`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fileName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fileParser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sourceLang` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `targetLang` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relaisLang` int(11) NOT NULL,
  `fileOrder` int(11) NOT NULL,
  `encoding` varchar(19) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_files_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_files`
--


--
-- Table structure for table `LEK_foldertree`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_foldertree` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tree` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `referenceFileTree` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_foldertree_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_foldertree`
--


--
-- Table structure for table `LEK_languageresources`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityVersion` int(11) NOT NULL DEFAULT 0 COMMENT 'automatic entity versioning',
  `langResUuid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'language resource guid for interoperability of language resources over different instances',
  `name` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'human readable name of the service',
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the hexadecimal colorcode',
  `resourceId` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the id of the concrete underlying resource',
  `serviceType` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'service type class name',
  `serviceName` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'a human readable service name',
  `specificData` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Language resource specific info data',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `resourceType` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources`
--

/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER LEK_matchresource_tmmt_versioning BEFORE UPDATE ON `LEK_languageresources` FOR EACH ROW 
        IF OLD.entityVersion = NEW.entityVersion THEN 
          SET NEW.entityVersion = OLD.entityVersion + 1;
        ELSE 
          CALL raise_version_conflict; 
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `LEK_languageresources_batchresults`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_batchresults` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResource` int(11) NOT NULL,
  `segmentId` int(11) NOT NULL,
  `result` mediumblob DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_LEK_match_analysis_batchresults_1_idx` (`languageResource`),
  KEY `fk_LEK_match_analysis_batchresults_2_idx` (`segmentId`),
  CONSTRAINT `fk_LEK_match_analysis_batchresults_1` FOREIGN KEY (`languageResource`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_match_analysis_batchresults_2` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_batchresults`
--


--
-- Table structure for table `LEK_languageresources_category_assoc`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_category_assoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_LEK_languageresources_category_assoc_1` (`languageResourceId`),
  KEY `fk_LEK_languageresources_category_assoc_2` (`categoryId`),
  CONSTRAINT `fk_LEK_languageresources_category_assoc_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_LEK_languageresources_category_assoc_2` FOREIGN KEY (`categoryId`) REFERENCES `LEK_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_category_assoc`
--


--
-- Table structure for table `LEK_languageresources_customerassoc`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_customerassoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) DEFAULT NULL,
  `customerId` int(11) DEFAULT NULL,
  `useAsDefault` tinyint(1) DEFAULT NULL,
  `writeAsDefault` tinyint(1) DEFAULT NULL COMMENT 'If set to 1, the assigned tasks to this customer will have this language resource associated by default and it will be writable by default (only for TM)',
  `pivotAsDefault` tinyint(1) DEFAULT NULL COMMENT 'If set to 1, the assigned tasks to this customer will have this language resource used to pre-translate pivot language',
  PRIMARY KEY (`id`),
  KEY `fk_LEK_languageresources_customerassoc_1_idx` (`languageResourceId`),
  KEY `fk_LEK_languageresources_customerassoc_2_idx` (`customerId`),
  CONSTRAINT `fk_LEK_languageresources_customerassoc_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_LEK_languageresources_customerassoc_2` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_customerassoc`
--


--
-- Table structure for table `LEK_languageresources_internal_tm`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_internal_tm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) DEFAULT NULL,
  `mid` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `internalFuzzy` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `mid` (`mid`),
  KEY `LEK_languageresources_internal_tm_ibfk_1` (`languageResourceId`),
  CONSTRAINT `LEK_languageresources_internal_tm_ibfk_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_internal_tm`
--


--
-- Table structure for table `LEK_languageresources_languages`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sourceLang` int(11) DEFAULT NULL,
  `targetLang` int(11) DEFAULT NULL,
  `sourceLangCode` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `targetLangCode` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `languageResourceId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_LEK_languageresources_languages_1_idx` (`languageResourceId`),
  CONSTRAINT `fk_LEK_languageresources_languages_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_languages`
--


--
-- Table structure for table `LEK_languageresources_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) NOT NULL,
  `level` tinyint(2) NOT NULL DEFAULT 4,
  `eventCode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `worker` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authUserGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authUser` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `extra` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `languageResourceId` (`languageResourceId`),
  CONSTRAINT `LEK_languageresources_log_ibfk_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_log`
--


--
-- Table structure for table `LEK_languageresources_taskassoc`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_taskassoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) DEFAULT NULL,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segmentsUpdateable` tinyint(4) NOT NULL DEFAULT 0,
  `autoCreatedOnImport` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tmmtId` (`languageResourceId`,`taskGuid`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_languageresources_taskassoc_ibfk_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`),
  CONSTRAINT `LEK_languageresources_taskassoc_ibfk_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_taskassoc`
--


--
-- Table structure for table `LEK_languageresources_taskpivotassoc`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_taskpivotassoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) DEFAULT NULL,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_LEK_languageresources_taskpivotassoc_1_idx` (`languageResourceId`),
  KEY `fk_LEK_languageresources_taskpivotassoc_2` (`taskGuid`),
  CONSTRAINT `fk_LEK_languageresources_taskpivotassoc_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_languageresources_taskpivotassoc_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_taskpivotassoc`
--


--
-- Table structure for table `LEK_languageresources_usage_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_usage_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) DEFAULT NULL,
  `sourceLang` int(11) DEFAULT NULL,
  `targetLang` int(11) DEFAULT NULL,
  `queryString` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requestSource` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'MT language resource request source. Mt usage can be requested from the translate5 editor, or translate5 apps like instant translate.',
  `translatedCharacterCount` int(11) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `customers` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repetition` tinyint(1) DEFAULT 0 COMMENT 'Is the current queryString for segment repetition',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_LEK_languageresources_usage_log_1_idx` (`languageResourceId`),
  CONSTRAINT `fk_LEK_languageresources_usage_log_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_usage_log`
--


--
-- Table structure for table `LEK_languageresources_usage_log_sum`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languageresources_usage_log_sum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `langageResourceId` int(11) NOT NULL,
  `langageResourceName` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `langageResourceType` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sourceLang` int(11) NOT NULL,
  `targetLang` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  `yearAndMonth` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `totalCharacters` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monthlySummaryKey` (`langageResourceId`,`sourceLang`,`targetLang`,`customerId`,`yearAndMonth`),
  KEY `fk_LEK_languageresources_usage_log_sum_1_idx` (`customerId`),
  CONSTRAINT `fk_LEK_languageresources_resource_usage_log_sum_customer` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languageresources_usage_log_sum`
--


--
-- Table structure for table `LEK_languages`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `langName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lcid` int(11) DEFAULT NULL,
  `rfc5646` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RFC5646 language shortcut according to the specification',
  `iso3166Part1alpha2` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO_3166-1_alpha-2 country shortcut for the country, where the language is most often spoken',
  `sublanguage` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RFC5646 language shortcut (or similar) of the sublanguage that is most important e.g. for SpellChecks',
  `rtl` tinyint(1) DEFAULT 0 COMMENT 'defines if the language is a rtl language',
  `iso6393` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `langName` (`langName`),
  UNIQUE KEY `lcid` (`lcid`),
  UNIQUE KEY `rfc5646` (`rfc5646`)
) ENGINE=InnoDB AUTO_INCREMENT=555 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_languages`
--

INSERT INTO `LEK_languages` VALUES
(4,'Deutsch',NULL,'de','de','de-DE',0,'ger'),
(5,'Englisch',NULL,'en','gb','en-GB',0,'eng'),
(6,'Spanisch',NULL,'es','es','es-ES',0,'spa'),
(251,'Englisch (UK)',2057,'en-GB','gb','en-GB',0,'eng'),
(252,'Englisch (US)',1033,'en-US','us','en-US',0,'eng'),
(253,'Französisch',NULL,'fr','fr','fr-FR',0,'fra'),
(254,'Italienisch',NULL,'it','it','it-IT',0,'ita'),
(255,'Bulgarisch',NULL,'bg','bg','bg-BG',0,'bul'),
(256,'Dänisch',NULL,'da','dk','da-DK',0,'dan'),
(257,'Estnisch',NULL,'et','ee','ee-EE',0,'est'),
(258,'Finnisch',NULL,'fi','fi','fi-FI',0,'fin'),
(259,'Griechisch',NULL,'el','gr','el-GR',0,'gre'),
(260,'Kroatisch',NULL,'hr','hr','hr-HR',0,'hrv'),
(261,'Niederländisch',NULL,'nl','nl','nl-NL',0,'nld'),
(262,'Norwegisch',NULL,'no',NULL,NULL,0,NULL),
(263,'Polnisch',NULL,'pl','pl','pl-PL',0,NULL),
(264,'Portugiesisch',NULL,'pt','pt','pt-PT',0,NULL),
(265,'Portugiesisch (Brasilien)',1046,'pt-BR','br','pt-BR',0,NULL),
(266,'Rumänisch',NULL,'ro','ro','ro-RO',0,NULL),
(267,'Russisch',NULL,'ru','ru','ru-RU',0,NULL),
(268,'Schwedisch',NULL,'sv','se','sv-SE',0,NULL),
(269,'Slowakisch',NULL,'sk','sk','sk-SK',0,NULL),
(270,'Slowenisch',NULL,'sl','si','sl-SI',0,NULL),
(271,'Chinesisch (Taiwan)',1028,'zh-TW','tw','zh-TW',0,'cht'),
(272,'Chinesisch (China)',2052,'zh-CN','cn','zh-CN',0,'chi'),
(273,'Afrikaans',NULL,'af','za','af-ZA',0,NULL),
(274,'Albanisch',NULL,'sq','al','sq-AL',0,NULL),
(275,'Armenisch',NULL,'hy','am','hy-AM',0,NULL),
(276,'Aserbaidschanisch (Latein)',NULL,'az','az','az-AZ',0,NULL),
(277,'Bengalisch',NULL,'bn','in','bn-IN',0,NULL),
(278,'Bosnisch',NULL,'bs','ba','bs-BA',0,NULL),
(279,'Niederländisch (Belgien)',2067,'nl-BE','be','nl-BE',0,NULL),
(280,'Französisch (Belgien)',2060,'fr-BE','be','fr-BE',0,NULL),
(281,'Gälisch (Irisch)',2108,'ga-IE','ie','ga-IE',0,NULL),
(282,'Georgisch',NULL,'ka','ge','ka-GE',0,NULL),
(283,'Gujarati',NULL,'gu','in','gu-IN',0,NULL),
(284,'Hindi',NULL,'hi','in','hi-IN',0,NULL),
(285,'Igbo',NULL,'ig','ng','ig-NG',0,NULL),
(286,'Indonesisch',NULL,'id','id','id-ID',0,NULL),
(287,'Isländisch',NULL,'is','is','is-IS',0,NULL),
(288,'Japanisch',NULL,'ja','jp','ja-JP',0,NULL),
(289,'Khmer',NULL,'km','kh','km-KH',0,NULL),
(290,'Kannada',NULL,'kn','in','kn-IN',0,NULL),
(291,'Kasachisch',NULL,'kk','kz','kk-KZ',0,NULL),
(292,'Katalanisch',NULL,'ca','es','ca-ES',0,NULL),
(293,'Kirgisisch',NULL,'ky','kg','ky-KG',0,NULL),
(294,'Koreanisch',NULL,'ko','kr','ko-KR',0,NULL),
(295,'Lettisch',NULL,'lv','lv','lv-LV',0,NULL),
(296,'Litauisch',NULL,'lt','lt','lt-LT',0,NULL),
(297,'Malayalam',NULL,'ml','in','ml-IN',0,NULL),
(298,'Malaysisch',NULL,'ms','my','ms-MY',0,NULL),
(299,'Maltesisch',NULL,'mt','mt','mt-MT',0,NULL),
(300,'Marathi',NULL,'mr','in','mr-IN',0,NULL),
(301,'Mazedonisch',NULL,'mk','mk','mk-MK',0,NULL),
(302,'Moldawisch',NULL,'mo','md','mo-MD',0,NULL),
(303,'Panjabi',NULL,'pa','in','pa-IN',0,NULL),
(304,'Paschtu',NULL,'ps','af','ps-AR',1,NULL),
(305,'Persisch (Farsi)',NULL,'fa','ir','fa-IR',1,NULL),
(306,'Serbisch (Latein)',NULL,'sr','rs','sr-SP',0,NULL),
(307,'Sesotho',NULL,'st','ls','st-LS',0,NULL),
(308,'Somali',NULL,'so','so','so-SO',0,NULL),
(309,'Spanisch (Kolumbien)',9226,'es-CO','co','es-CO',0,NULL),
(310,'Spanisch (Mexiko)',2058,'es-MX','mx','es-MX',0,NULL),
(311,'Suaheli',NULL,'sw',NULL,'sw-KE',0,NULL),
(312,'Tadschikisch',NULL,'tg','tj','tg-TJ',0,NULL),
(313,'Tagalog',NULL,'tl','ph','tl-PH',0,NULL),
(314,'Tamilisch',NULL,'ta','lk','ta-IN',0,NULL),
(315,'Telugu',NULL,'te','in','te-IN',0,NULL),
(316,'Thai',NULL,'th','th','th-TH',0,NULL),
(317,'Tibetisch',NULL,'bo','ti','bo-TI',0,NULL),
(318,'Tschechisch',NULL,'cs','cz','cs-CZ',0,NULL),
(319,'Tswana',NULL,'tn','za','tn-ZA',0,NULL),
(320,'Türkisch',NULL,'tr','tr','tr-TR',0,NULL),
(321,'Turkmenisch',NULL,'tk','tm','tk-TM',0,NULL),
(322,'Ukrainisch',NULL,'uk','ua','uk-UA',0,NULL),
(323,'Ungarisch',NULL,'hu','hu','hu-HU',0,NULL),
(324,'Usbekisch (Latein)',NULL,'uz','uz','uz-UZ',0,NULL),
(325,'Vietnamesisch',NULL,'vi','vn','vi-VN',0,NULL),
(326,'Weißrussisch',NULL,'be','by','be-BY',0,NULL),
(327,'Xhosa',NULL,'xh','za','xh-ZA',0,NULL),
(328,'Yoruba',NULL,'yo',NULL,'yo',0,NULL),
(329,'Zulu',NULL,'zu','za','zu-ZA',0,NULL),
(330,'Afrikaans (Südafrika)',1078,'af-ZA','za','af-ZA',0,NULL),
(331,'Arabisch',NULL,'ar','sa','ar-AE',1,NULL),
(332,'Arabisch (Vereinigte Arabische Emirate)',14337,'ar-AE','ae','ar-AE',1,NULL),
(333,'Arabisch (Bahrain)',15361,'ar-BH','bh','ar-BH',1,NULL),
(334,'Arabisch (Algerien)',5121,'ar-DZ','dz','ar-DZ',1,NULL),
(335,'Arabisch (Ägypten)',3073,'ar-EG','eg','ar-EG',1,NULL),
(336,'Arabisch (Irak)',2049,'ar-IQ','iq','ar-IQ',1,NULL),
(337,'Arabisch (Jordanien)',11265,'ar-JO','jo','ar-JO',1,NULL),
(338,'Arabisch (Kuwait)',13313,'ar-KW','kw','ar-KW',1,NULL),
(339,'Arabisch (Libanon)',12289,'ar-LB','lb','ar-LB',1,NULL),
(340,'Arabisch (Libyen)',4097,'ar-LY','ly','ar-LY',1,NULL),
(341,'Arabisch (Marokko)',6145,'ar-MA','ma','ar-MA',1,NULL),
(342,'Arabisch (Oman)',8193,'ar-OM','om','ar-OM',1,NULL),
(343,'Arabisch (Katar)',16385,'ar-QA','qa','ar-QA',1,NULL),
(344,'Arabisch (Saudi-Arabien)',1025,'ar-SA','sa','ar-SA',1,'ara'),
(345,'Arabisch (Syrien)',10241,'ar-SY','sy','ar-SY',1,NULL),
(346,'Arabisch (Tunesien)',7169,'ar-TN','tn','ar-TN',1,NULL),
(347,'Arabisch (Jemen)',9217,'ar-YE','ye','ar-YE',1,NULL),
(348,'Aserbaidschanisch (Kyrillisch)',NULL,'az-Cyrl','az','az-Cyrl',0,NULL),
(349,'Aserbaidschanisch (Latein) (Aserbaidschan)',NULL,'az-AZ','az','az-AZ',0,NULL),
(350,'Aserbaidschanisch (Kyrillisch) (Aserbaidschan)',2092,'az-Cyrl-AZ','az','az-Cyrl-AZ',0,NULL),
(351,'Weißrussisch (Weißrussland)',1059,'be-BY','by','be-BY',0,NULL),
(352,'Bulgarisch (Bulgarien)',1026,'bg-BG','bg','bg-BG',0,'bul'),
(353,'Bosnisch (Bosnien und Herzegowina)',NULL,'bs-BA','ba','bs-BA',0,NULL),
(354,'Katalanisch (Spanien)',1027,'ca-ES','es','ca-ES',0,NULL),
(355,'Tschechisch (Tschechien)',1029,'cs-CZ','cz','cs-CZ',0,'cze'),
(356,'Walisisch',NULL,'cy','gb','cy-GB',0,NULL),
(357,'Walisisch (UK)',1106,'cy-GB','xs','cy-GB',0,NULL),
(358,'Dänisch (Dänemark)',1030,'da-DK','dk','da-DK',0,'dan'),
(359,'Deutsch (Österreich)',3079,'de-AT','at','de-AT',0,NULL),
(360,'Deutsch (Schweiz)',2055,'de-CH','ch','de-CH',0,NULL),
(361,'Deutsch (Deutschland)',1031,'de-DE','de','de-DE',0,'ger'),
(362,'Deutsch (Liechtenstein)',5127,'de-LI','li','de-LI',0,NULL),
(363,'Deutsch (Luxemburg)',4103,'de-LU','lu','de-LU',0,NULL),
(364,'Dhivehi',NULL,'dv','in','dv-MV',1,NULL),
(365,'Dhivehi (Malediven)',1125,'dv-MV','mv','dv-MV',1,NULL),
(366,'Griechisch (Griechenland)',1032,'el-GR','gr','el-GR',0,'gre'),
(367,'Englisch (Australien)',3081,'en-AU','au','en-AU',0,NULL),
(368,'Englisch (Belize)',10249,'en-BZ','bz','en-BZ',0,NULL),
(369,'Englisch (Kanada)',4105,'en-CA','ca','en-CA',0,NULL),
(370,'Englisch (Karibik)',9225,'en-CB',NULL,'en-CB',0,NULL),
(371,'Englisch (Irland)',6153,'en-IE','ie','en-IE',0,NULL),
(372,'Englisch (Jamaika)',8201,'en-JM','jm','en-JM',0,NULL),
(373,'Englisch (Neuseeland)',5129,'en-NZ','nz','en-NZ',0,NULL),
(374,'Englisch (Philippinen)',13321,'en-PH','ph','en-PH',0,NULL),
(375,'Englisch (Trinidad und Tobego)',11273,'en-TT','tt','en-TT',0,NULL),
(376,'Englisch (Südafrika)',7177,'en-ZA','za','en-ZA',0,NULL),
(377,'Englisch (Simbabwe)',12297,'en-ZW','zw','en-ZW',0,NULL),
(378,'Esperanto',NULL,'eo',NULL,'eo',0,NULL),
(379,'Spanisch (Argentinien)',11274,'es-AR','ar','es-AR',0,NULL),
(380,'Spanisch (Bolivien)',16394,'es-BO','bo','es-BO',0,NULL),
(381,'Spanisch (Chile)',13322,'es-CL','cl','es-CL',0,NULL),
(382,'Spanisch (Costa Rica)',5130,'es-CR','cr','es-CR',0,NULL),
(383,'Spanisch (Dominikanische Republik)',7178,'es-DO','do','es-DO',0,NULL),
(384,'Spanisch (Ecuador)',12298,'es-EC','ec','es-EC',0,NULL),
(385,'Spanisch (Spanien)',1034,'es-ES','es','es-ES',0,'spa'),
(386,'Spanisch (Guatemala)',4106,'es-GT','gt','es-GT',0,NULL),
(387,'Spanisch (Honduras)',18442,'es-HN','hn','es-HN',0,NULL),
(388,'Spanisch (Nicaragua)',19466,'es-NI','ni','es-NI',0,NULL),
(389,'Spanisch (Panama)',6154,'es-PA','pa','es-PA',0,NULL),
(390,'Spanisch (Peru)',10250,'es-PE','pe','es-PE',0,NULL),
(391,'Spanisch (Puerto Rico)',20490,'es-PR','pr','es-PR',0,NULL),
(392,'Spanisch (Paraguay)',15370,'es-PY','py','es-PY',0,NULL),
(393,'Spanisch (El Salvador)',17418,'es-SV','sv','es-SV',0,NULL),
(394,'Spanisch (Uruguay)',14346,'es-UY','uy','es-UY',0,NULL),
(395,'Spanisch (Venezuela)',8202,'es-VE','ve','es-VE',0,NULL),
(396,'Estnisch (Estland)',1061,'et-EE','ee','et-EE',0,'est'),
(397,'Baskisch',NULL,'eu','es','eu-ES',0,NULL),
(398,'Baskisch (Spanien)',1069,'eu-ES','es','eu-ES',0,NULL),
(399,'Persisch (Iran)',1065,'fa-IR','ir','fa-IR',1,'per'),
(400,'Finnisch (Finnland)',1035,'fi-FI','fi','fi-FI',0,'fin'),
(401,'Färöisch',NULL,'fo','fo','fo-FO',0,NULL),
(402,'Färöisch (Färöer)',1080,'fo-FO','fo','fo-FO',0,NULL),
(403,'Französisch (Kanada)',3084,'fr-CA','ca','fr-CA',0,NULL),
(404,'Französisch (Schweiz)',4108,'fr-CH','ch','fr-CH',0,NULL),
(405,'Französisch (Frankreich)',1036,'fr-FR','fr','fr-FR',0,'fra'),
(406,'Französisch (Luxemburg)',5132,'fr-LU','lu','fr-LU',0,NULL),
(407,'Französisch (Monaco)',6156,'fr-MC','mc','fr-MC',0,NULL),
(408,'Galicisch',NULL,'gl','es','gl-ES',0,NULL),
(409,'Galicisch (Spanien)',1110,'gl-ES','es','gl-ES',0,NULL),
(410,'Gujarati (Indien)',1095,'gu-IN','in','gu-IN',0,NULL),
(411,'Kurdisch',NULL,'ku','ku','ku-KU',0,NULL),
(412,'Hebräisch',NULL,'he','il','he-IL',1,NULL),
(413,'Hebräisch (Israel)',1037,'he-IL','il','he-IL',1,'heb'),
(414,'Hindi (Indien)',1081,'hi-IN','in','hi-IN',0,'hin'),
(415,'Kroatisch (Bosnien und Herzegomina)',4122,'hr-BA','ba','hr-BA',0,NULL),
(416,'Kroatisch (Kroatien)',1050,'hr-HR','hr','hr-HR',0,'hrv'),
(417,'Ungarisch (Ungarn)',1038,'hu-HU','hu','hu-HU',0,'hun'),
(418,'Armenisch (Armenien)',1067,'hy-AM','am','hy-AM',0,NULL),
(419,'Indonesisch (Indonesien)',1057,'id-ID','id','id-ID',0,'ind'),
(420,'Isländisch (Island)',1039,'is-IS','is','is-IS',0,NULL),
(421,'Italienisch (Schweiz)',2064,'it-CH','ch','it-CH',0,NULL),
(422,'Italienisch (Italien)',1040,'it-IT','it','it-IT',0,'ita'),
(423,'Japanisch (Japan)',1041,'ja-JP','jp','ja-JP',0,'jpn'),
(424,'Georgisch (Georigien)',1079,'ka-GE','ge','ka-GE',0,NULL),
(425,'Kasachisch (Kasachstan)',1087,'kk-KZ','kz','kk-KZ',0,NULL),
(426,'Kannada (Indien)',1099,'kn-IN','in','kn-IN',0,NULL),
(427,'Koreanisch (Korea)',1042,'ko-KR','kr','ko-KR',0,'kor'),
(428,'Konkani',NULL,'kok','in','kok-IN',0,NULL),
(429,'Konkani (Indien)',1111,'kok-IN','in','kok-IN',0,NULL),
(430,'Kirgisisch (Kirgisistan)',1088,'ky-KG','kg','ky-KG',0,NULL),
(431,'Litauisch (Litauen)',1063,'lt-LT','lt','lt-LT',0,'lit'),
(432,'Lettisch (Lettland)',1062,'lv-LV','lv','lv-LV',0,'lav'),
(433,'Māori',NULL,'mi','nz','mi-NZ',0,NULL),
(434,'Māori (Neuseeland)',1153,'mi-NZ','nz','mi-NZ',0,NULL),
(435,'Mazedonisch (Mazedonien)',1071,'mk-MK','mk','mk-MK',0,NULL),
(436,'Mongolisch',NULL,'mn','mn','mn-MN',0,NULL),
(437,'Mongolisch (Mongolei)',1104,'mn-MN','mn','mn-MN',0,NULL),
(438,'Marathi (Indien)',1102,'mr-IN','in','mr-IN',0,NULL),
(439,'Malaysisch (Brunei)',2110,'ms-BN','bn','ms-BN',0,NULL),
(440,'Malaysisch (Malaysia)',1086,'ms-MY','my','ms-MY',0,'may'),
(441,'Maltesisch (Malta)',1082,'mt-MT','mt','mt-MT',0,'mlt'),
(442,'Norwegisch (Bokmal)',NULL,'nb','no','nb-NO',0,NULL),
(443,'Norwegisch (Bokmal) (Norwegen)',1044,'nb-NO','no','nb-NO',0,'nor'),
(444,'Niederländisch (Niederlande)',1043,'nl-NL','nl','nl-NL',0,'dut'),
(445,'Norwegisch (Nynorsk)',NULL,'nn','no','nn-NO',0,NULL),
(446,'Norwegisch (Nynorsk) (Norwegen)',2068,'nn-NO','no','nn-NO',0,NULL),
(447,'Nord-Sotho',NULL,'ns','za','ns-ZA',0,NULL),
(448,'Nord-Sotho (Südafrika)',NULL,'ns-ZA','za','ns-ZA',0,NULL),
(449,'Panjabi (Indien)',1094,'pa-IN','in','pa-IN',0,NULL),
(450,'Polnisch (Polen)',1045,'pl-PL','pl','pl-PL',0,'pol'),
(451,'Paschtu (Afghanistan)',NULL,'ps-AR','af','ps-AR',1,NULL),
(452,'Portugiesisch (Portugal)',2070,'pt-PT','pt','pt-PT',0,'por'),
(453,'Quechua',NULL,'qu','pe','qu-PE',0,NULL),
(454,'Quechua (Bolivien)',NULL,'qu-BO','bo','qu-BO',0,NULL),
(455,'Quechua (Ecuador)',NULL,'qu-EC','ec','qu-EC',0,NULL),
(456,'Quechua (Peru)',NULL,'qu-PE','pe','qu-PE',0,NULL),
(457,'Rumänisch (Rumänien)',1048,'ro-RO','ro','ro-RO',0,'rum'),
(458,'Russisch (Russland)',1049,'ru-RU','ru','ru-RU',0,'rus'),
(459,'Sanskrit',NULL,'sa','in','sa-IN',0,NULL),
(460,'Sanskrit (Indien)',1103,'sa-IN','in','sa-IN',0,NULL),
(461,'Samisch',NULL,'se',NULL,'se-FI',0,NULL),
(462,'Samisch (Finnland)',NULL,'se-FI','fi','se-FI',0,NULL),
(463,'Samisch (Norwegen)',1083,'se-NO','no','se-NO',0,NULL),
(464,'Samisch (Schweden)',NULL,'se-SE','se','se-SE',0,NULL),
(465,'Slowakisch (Slowakei)',1051,'sk-SK','sk','sk-SK',0,'slo'),
(466,'Slowenisch (Slowenien)',1060,'sl-SI','si','sl-SI',0,'slv'),
(467,'Albanisch (Albanien)',1052,'sq-AL','al','sq-AL',0,'alb'),
(468,'Serbisch (Kyrillisch)',NULL,'sr-Cyrl','rs','sr-Cyrl',0,NULL),
(469,'Serbisch (Latein) (Bosnien und Herzegowina)',NULL,'sr-BA','rs','sr-BA',0,NULL),
(470,'Serbisch (Kyrillisch) (Bosnien und Herzegowina)',NULL,'sr-Cyrl-BA','ba','sr-Cyrl-BA',0,NULL),
(471,'Serbisch (Latein) (Serbien und Montenegro)',2074,'sr-SP','rs','sr-SP',0,NULL),
(472,'Serbisch (Kyrillisch) (Serbien und Montenegro)',3098,'sr-Cyrl-SP','rs','sr-Cyrl-SP',0,NULL),
(473,'Schwedisch (Finnland)',2077,'sv-FI','fi','sv-FI',0,NULL),
(474,'Schwedisch (Schweden)',1053,'sv-SE','se','sv-SE',0,'swe'),
(475,'Suaheli (Kenia)',1089,'sw-KE','ke','sw-KE',0,NULL),
(476,'Syrisch',NULL,'syr','sy','syr-SY',0,NULL),
(477,'Syrisch (Syrien)',1114,'syr-SY','sy','syr-SY',0,NULL),
(478,'Tamilisch (Indien)',1097,'ta-IN','in','ta-IN',0,NULL),
(479,'Telugu (Indien)',1098,'te-IN','in','te-IN',0,NULL),
(480,'Thai (Thailand)',1054,'th-TH','th','th-TH',0,'tha'),
(481,'Tagalog (Philippinen)',NULL,'tl-PH','ph','tl-PH',0,NULL),
(482,'Tswana (Südafrika)',1074,'tn-ZA','za','tn-ZA',0,NULL),
(483,'Türkisch (Türkei)',1055,'tr-TR','tr','tr-TR',0,'tur'),
(484,'Tatarisch',NULL,'tt','ru','tt-RU',0,NULL),
(485,'Tatarisch (Russland)',1092,'tt-RU','ru','tt-RU',0,NULL),
(486,'Tsonga',NULL,'ts',NULL,'ts',0,NULL),
(487,'Ukrainisch (Ukraine)',1058,'uk-UA','ua','uk-UA',0,'ukr'),
(488,'Urdu',NULL,'ur','pk','ur-PK',1,NULL),
(489,'Urdu (Pakistan)',1056,'ur-PK','pk','ur-PK',1,'urd'),
(490,'Usbekisch (Kyrillisch)',NULL,'Uz-Cyrl','uz','uz-Cyrl',0,NULL),
(491,'Usbekisch (Latein) (Usbekistan)',NULL,'uz-UZ','uz','uz-UZ',0,NULL),
(492,'Usbekisch (Kyrillisch) (Usbekistan)',2115,'uz-Cyrl-UZ','uz','uz-Cyrl-UZ',0,NULL),
(493,'Vietnamesisch (Vietnam)',1066,'vi-VN','vn','vi-VN',0,'vie'),
(494,'Xhosa (Südafrika)',1076,'xh-ZA','za','xh-ZA',0,NULL),
(495,'Chinesisch',NULL,'zh','cn','zh-CN',0,NULL),
(496,'Chinesisch (Hong Kong)',3076,'zh-HK','hk','zh-HK',0,NULL),
(497,'Chinesisch (Macau)',5124,'zh-MO','mo','zh-MO',0,NULL),
(498,'Chinesisch (Singapur)',4100,'zh-SG','sg','zh-SG',0,NULL),
(499,'Zulu (Südafrika)',1077,'zu-ZA','za','zu-ZA',0,NULL),
(500,'Serbisch (Kyrillisch) (Serbien)',10266,'sr-Cyrl-RS','rs','sr-Cyrl-RS',0,'srp'),
(501,'Serbisch (Latein) (Serbien)',9242,'sr-RS','rs','sr-RS',0,'srp'),
(504,'Amharisch',NULL,'am',NULL,'am',0,NULL),
(505,'Cebuano',NULL,'ceb',NULL,'ceb',0,NULL),
(506,'Korsisch',NULL,'co',NULL,'co',0,NULL),
(507,'Friesisch',NULL,'fy',NULL,'fy',0,NULL),
(508,'Irländisch',NULL,'ga',NULL,'ga',0,NULL),
(509,'Schottisch-Gälisch',NULL,'gd',NULL,'gd',0,NULL),
(510,'Hausa',NULL,'ha',NULL,'ha',0,NULL),
(511,'Hawaianisch',NULL,'haw',NULL,'haw',0,NULL),
(512,'Hmong',NULL,'hmn',NULL,'hmn',0,NULL),
(513,'Haitianisches Kreol',NULL,'ht',NULL,'ht',0,NULL),
(514,'Latein',NULL,'la',NULL,'la',0,NULL),
(515,'Luxemburgisch',NULL,'lb',NULL,'lb',0,NULL),
(516,'Lao',NULL,'lo',NULL,'lo',0,NULL),
(517,'Madagassisch',NULL,'mg',NULL,'mg',0,NULL),
(518,'Myanmar (Birmanisch)',NULL,'my',NULL,'my',0,NULL),
(519,'Nepali',NULL,'ne',NULL,'ne',0,NULL),
(521,'Nyanja (Chichewa)',NULL,'ny',NULL,'ny',0,NULL),
(522,'Odia (Oriya)',NULL,'or',NULL,'or',0,NULL),
(523,'Kinyarwanda',NULL,'rw',NULL,'rw',0,NULL),
(524,'Sindhi',NULL,'sd',NULL,'sd',0,NULL),
(525,'Singhalesisch (Singhalesisch)',NULL,'si',NULL,'si',0,NULL),
(526,'Samoan',NULL,'sm',NULL,'sm',0,NULL),
(527,'Shona',NULL,'sn',NULL,'sn',0,NULL),
(528,'Sundanesisch',NULL,'su',NULL,'su',0,NULL),
(529,'Uigurisch',NULL,'ug',NULL,'ug',0,NULL),
(530,'Jiddisch',NULL,'yi',NULL,'yi',0,NULL),
(531,'Philippinisch',NULL,'fil',NULL,'fil',0,NULL),
(532,'Fidschianisch',NULL,'fj',NULL,'fj',0,NULL),
(533,'Kurdisch (Nord)',NULL,'kmr',NULL,'kmr',0,NULL),
(534,'Hmong Daw',NULL,'mww',NULL,'mww',0,NULL),
(535,'Queretaro Otomi',NULL,'otq',NULL,'otq',0,NULL),
(536,'Dari',NULL,'prs',NULL,'prs',0,NULL),
(537,'Klingonisch',NULL,'tlh-Latn',NULL,'tlh-Latn',0,NULL),
(538,'Klingonisch (plqaD)',NULL,'tlh-Piqd',NULL,'tlh-Piqd',0,NULL),
(539,'Tongan',NULL,'to',NULL,'to',0,NULL),
(540,'Tahitian',NULL,'ty',NULL,'ty',0,NULL),
(541,'Yucatec Maya',NULL,'yua',NULL,'yua',0,NULL),
(542,'Kantonesisch (traditionell)',NULL,'yue',NULL,'yue',0,NULL),
(543,'Serbisch (Latein Serbien)',NULL,'sr-Latn-RS','rs','sr-RS',0,NULL),
(544,'Somali (Somalia)',1143,'so-SO','so','so',0,NULL),
(545,'Amharisch (Äthiopien)',1118,'am-ET','am','am',0,NULL),
(546,'Rätoromanisch (Schweiz)',1047,'rm-CH','rm','rm-ch',0,NULL),
(547,'Spanisch (Vereinigte Staaten)',21514,'es-US','es','es',0,NULL),
(548,'Spanisch (Lateinamerika und Karibik)',58378,'es-419','es','es',0,NULL),
(549,'Aserbaidschanisch (Latein Aserbaidschan)',1068,'az-Latn-AZ','az','az-AZ',0,NULL),
(550,'Usbekisch (Latein Usbekistan)',1091,'uz-Latn-UZ','uz','uz-UZ',0,NULL),
(551,'Bosnisch (Latein Bosnien und Herzegowina)',5146,'bs-Latn-BA','ba','bs-BA',0,NULL),
(552,'Chinesisch (vereinfacht)',4,'zh-Hans','cn','zh-hans-CN',0,NULL),
(553,'Chinesisch (traditionell)',31748,'zh-Hant','cn','zh-hant-CN',0,NULL),
(554,'Serbisch (Latein) (Montenegro)',11290,'sr-Latn-ME','sr','Latn-ME',0,'cnr');

--
-- Table structure for table `LEK_match_analysis`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_match_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `analysisId` int(11) DEFAULT NULL,
  `segmentId` int(11) DEFAULT NULL,
  `segmentNrInTask` int(11) DEFAULT NULL,
  `internalFuzzy` tinyint(1) DEFAULT NULL,
  `languageResourceid` int(11) DEFAULT NULL,
  `matchRate` int(11) DEFAULT NULL,
  `wordCount` int(11) DEFAULT NULL,
  `characterCount` int(11) DEFAULT NULL,
  `type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_LEK_match_analysis_1_idx` (`taskGuid`),
  KEY `index3` (`segmentId`),
  KEY `index4` (`languageResourceid`),
  KEY `fk_LEK_match_analysis_2_idx` (`analysisId`),
  CONSTRAINT `fk_LEK_match_analysis_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_match_analysis_2` FOREIGN KEY (`analysisId`) REFERENCES `LEK_match_analysis_taskassoc` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_match_analysis`
--


--
-- Table structure for table `LEK_match_analysis_taskassoc`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_match_analysis_taskassoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `internalFuzzy` tinyint(1) DEFAULT 0,
  `pretranslateMatchrate` int(11) DEFAULT NULL,
  `uuid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `finishedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_LEK_match_analysis_taskassoc_1_idx` (`taskGuid`),
  CONSTRAINT `fk_LEK_match_analysis_taskassoc_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_match_analysis_taskassoc`
--


--
-- Table structure for table `LEK_okapi_bconf`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_okapi_bconf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerId` int(11) DEFAULT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isDefault` tinyint(1) NOT NULL DEFAULT 0,
  `versionIdx` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `customerId` (`customerId`),
  CONSTRAINT `LEK_okapi_bconf_ibfk_1` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_okapi_bconf`
--


--
-- Table structure for table `LEK_okapi_bconf_filter`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_okapi_bconf_filter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bconfId` int(11) NOT NULL,
  `okapiType` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `okapiId` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mimeType` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hash` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bconfId` (`bconfId`),
  CONSTRAINT `LEK_okapi_bconf_filter_ibfk_1` FOREIGN KEY (`bconfId`) REFERENCES `LEK_okapi_bconf` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_okapi_bconf_filter`
--


--
-- Table structure for table `LEK_pixel_mapping`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_pixel_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `fileId` int(11) DEFAULT NULL COMMENT 'Foreign Key to LEK_files',
  `font` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fontsize` int(3) NOT NULL,
  `unicodeChar` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(numeric)',
  `pixelWidth` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fileId` (`fileId`,`font`,`fontsize`,`unicodeChar`),
  KEY `fk_LEK_pixel_mapping_1` (`taskGuid`),
  CONSTRAINT `fk_LEK_pixel_mapping_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_pixel_mapping_2` FOREIGN KEY (`fileId`) REFERENCES `LEK_files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_pixel_mapping`
--


--
-- Table structure for table `LEK_plugin_segmentstatistic_terms`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_plugin_segmentstatistic_terms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `mid` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_terms',
  `segmentId` int(11) NOT NULL COMMENT 'Segment ID, no FK needed',
  `fileId` int(11) NOT NULL COMMENT 'File ID, no FK needed',
  `fieldName` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'name of the segment field',
  `fieldType` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'type of the segment field',
  `term` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Term Content',
  `notFoundCount` int(11) NOT NULL DEFAULT 0 COMMENT 'count of this term not found',
  `foundCount` int(11) NOT NULL DEFAULT 0 COMMENT 'count of this term found',
  `type` enum('import','export') COLLATE utf8mb4_unicode_ci DEFAULT 'import',
  PRIMARY KEY (`id`),
  UNIQUE KEY `termPerTask` (`mid`,`segmentId`,`fieldName`,`type`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_plugin_segmentstatistic_terms_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_plugin_segmentstatistic_terms`
--


--
-- Table structure for table `LEK_plugin_segmentstatistics`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_plugin_segmentstatistics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `segmentId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_segments',
  `fileId` int(11) NOT NULL COMMENT 'Foreign Key to segment source file, needed for grouping',
  `fieldName` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'name of the segment field',
  `fieldType` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'type of the segment field',
  `charCount` int(11) NOT NULL COMMENT 'number of chars (incl. whitespace) in the segment field',
  `wordCount` int(11) NOT NULL COMMENT 'number of words in the segment',
  `termNotFound` int(11) NOT NULL COMMENT 'number of terms not translated in the target',
  `type` enum('import','export') COLLATE utf8mb4_unicode_ci DEFAULT 'import',
  `termFound` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `segmentIdFieldName` (`segmentId`,`fieldName`,`type`),
  KEY `fileId` (`fileId`),
  KEY `taskGuid` (`taskGuid`),
  KEY `segmentId` (`segmentId`),
  CONSTRAINT `LEK_plugin_segmentstatistics_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `LEK_plugin_segmentstatistics_ibfk_2` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_plugin_segmentstatistics`
--


--
-- Table structure for table `LEK_segment_data`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` int(11) DEFAULT 0,
  `name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segmentId` int(11) NOT NULL,
  `mid` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `originalMd5` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `originalToSort` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edited` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editedToSort` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segment_data_ibfk_1` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segment_data`
--


--
-- Table structure for table `LEK_segment_field`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_field` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'contains the label without invalid chars',
  `type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'target',
  `label` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'field label as provided by CSV / directory',
  `rankable` tinyint(1) NOT NULL COMMENT 'defines if this field is rankable in the ranker',
  `editable` tinyint(1) NOT NULL COMMENT 'defines if only the readOnly Content column is provided',
  `width` int(11) NOT NULL DEFAULT 0 COMMENT 'sets the width of the column in the GUI. Default 0, because actual max value is set with runtimeOptions.editor.columns.maxWidth and calculation needs to start at 0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`,`name`),
  CONSTRAINT `LEK_segment_field_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segment_field`
--


--
-- Table structure for table `LEK_segment_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'This is the DB save time of the History entry!',
  `segmentId` int(11) NOT NULL,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL COMMENT 'This is the old segment mod time',
  `editable` tinyint(1) NOT NULL,
  `pretrans` tinyint(1) NOT NULL,
  `qmId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stateId` int(11) DEFAULT NULL,
  `autoStateId` int(11) NOT NULL DEFAULT 0,
  `workflowStepNr` int(11) NOT NULL DEFAULT 0,
  `workflowStep` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `matchRate` int(11) DEFAULT 0,
  `matchRateType` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT 'import',
  PRIMARY KEY (`id`),
  KEY `LEK_segment_history_segmentId_FK` (`segmentId`),
  CONSTRAINT `LEK_segment_history_segmentId_FK` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segment_history`
--


--
-- Table structure for table `LEK_segment_history_data`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_history_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `duration` int(11) DEFAULT 0,
  `segmentHistoryId` int(11) NOT NULL,
  `name` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `originalMd5` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edited` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `segmentId` int(11) NOT NULL,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `segmentHistoryId` (`segmentHistoryId`),
  KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segment_history_data_ibfk_1` FOREIGN KEY (`segmentHistoryId`) REFERENCES `LEK_segment_history` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segment_history_data`
--


--
-- Table structure for table `LEK_segment_quality`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_quality` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segmentId` int(11) NOT NULL,
  `field` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `startIndex` int(11) NOT NULL DEFAULT 0,
  `endIndex` int(11) NOT NULL DEFAULT -1,
  `falsePositive` int(1) NOT NULL DEFAULT 0,
  `additionalData` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoryIndex` int(2) NOT NULL DEFAULT -1,
  `severity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segment_quality_ibfk_1` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_segment_quality_ibfk_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segment_quality`
--


--
-- Table structure for table `LEK_segment_tags`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `segmentId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_segments',
  `tags` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_term` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segment_tags_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `LEK_segment_tags_ibfk_2` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segment_tags`
--


--
-- Table structure for table `LEK_segment_user_assoc`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_user_assoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentId` int(11) NOT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isWatched` int(1) NOT NULL DEFAULT 1,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `segment_user` (`segmentId`,`userGuid`),
  KEY `userGuid` (`userGuid`),
  KEY `segmentId` (`segmentId`),
  CONSTRAINT `LEK_segments_FK` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `Zf_users_FK` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segment_user_assoc`
--


--
-- Table structure for table `LEK_segments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `editable` tinyint(1) NOT NULL DEFAULT 1,
  `pretrans` tinyint(1) NOT NULL DEFAULT 0,
  `matchRate` int(11) NOT NULL DEFAULT 0,
  `matchRateType` varchar(1084) COLLATE utf8mb4_unicode_ci DEFAULT 'import',
  `stateId` int(11) NOT NULL DEFAULT 0,
  `autoStateId` int(11) NOT NULL DEFAULT 0,
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workflowStepNr` int(11) NOT NULL DEFAULT 0,
  `workflowStep` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isRepeated` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segments_fileId_FK` FOREIGN KEY (`fileId`) REFERENCES `LEK_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_segments_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segments`
--


--
-- Table structure for table `LEK_segments_meta`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segments_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `segmentId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_segments',
  `notTranslated` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'defines, if segment is marked in imported file as locked not translated - or is acutally empty, but the source is not empty.',
  `termtagState` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT 'untagged' COMMENT 'Contains the TermTagger-state for this segment while importing',
  `transitLockedForRefMat` tinyint(4) DEFAULT 0 COMMENT 'Is set to true if segment is locked for reference material in transit file',
  `noMissingTargetTermOnImport` tinyint(4) DEFAULT 0 COMMENT 'Is set to false if a term in source does not exist in target column',
  `minWidth` int(11) DEFAULT NULL,
  `maxWidth` int(11) DEFAULT NULL,
  `maxNumberOfLines` int(3) DEFAULT NULL COMMENT 'max. number of lines in pixel-based length check',
  `sizeUnit` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'char or pixel',
  `font` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'font-family',
  `fontSize` int(3) NOT NULL DEFAULT 0,
  `transunitId` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siblingData` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'contains the data of the current segment needed also in sibling segments (same transunit)',
  `additionalUnitLength` int(11) NOT NULL DEFAULT 0 COMMENT 'Additional string length in transunit before first mrk, to be added to the length calculation of the segment',
  `additionalMrkLength` int(11) NOT NULL DEFAULT 0 COMMENT 'Additional string length after each mrk tag, to be added to the length calculation of the segment',
  `autopropagated` tinyint(1) DEFAULT NULL COMMENT 'Autopropagated propertie for the segment from the import file',
  `locked` tinyint(1) DEFAULT NULL COMMENT 'Locked propertie for the segment on the file import',
  `sourceWordCount` int(11) NOT NULL DEFAULT 0 COMMENT 'The word count of the source column calculated on import',
  `sourceCharacterCount` int(11) NOT NULL DEFAULT 0 COMMENT 'The characters count of the source column calculated on import',
  `preTransLangResUuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'if segment was field with pre-translation the language resource uuid is stored here for reference',
  `spellcheckState` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT 'unchecked' COMMENT 'Contains the SpellCheck-state for this segment while importing',
  PRIMARY KEY (`id`),
  UNIQUE KEY `segmentId` (`segmentId`),
  KEY `taskGuid_transunitId` (`taskGuid`,`transunitId`),
  KEY `taskGuid` (`taskGuid`,`termtagState`),
  KEY `taskGuid_2` (`taskGuid`,`spellcheckState`),
  CONSTRAINT `LEK_segments_meta_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `LEK_segments_meta_ibfk_2` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_segments_meta`
--


--
-- Table structure for table `LEK_task`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityVersion` int(11) NOT NULL DEFAULT 0,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taskNr` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `foreignId` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Used as optional reference field for Tasks create vi API',
  `taskName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `foreignName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Used as optional reference field for Tasks create vi API',
  `sourceLang` int(11) NOT NULL,
  `targetLang` int(11) NOT NULL,
  `relaisLang` int(11) NOT NULL,
  `lockedInternalSessionUniqId` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locked` datetime DEFAULT NULL,
  `lockingUser` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'import',
  `workflow` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `workflowStep` int(11) NOT NULL DEFAULT 1,
  `workflowStepName` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pmGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pmName` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wordCount` int(11) NOT NULL,
  `userCount` int(11) NOT NULL DEFAULT 0,
  `referenceFiles` tinyint(1) NOT NULL DEFAULT 0,
  `terminologie` tinyint(1) NOT NULL DEFAULT 0,
  `orderdate` datetime DEFAULT NULL,
  `enddate` datetime DEFAULT NULL,
  `enableSourceEditing` tinyint(1) NOT NULL DEFAULT 0,
  `edit100PercentMatch` tinyint(1) NOT NULL DEFAULT 0,
  `lockLocked` tinyint(1) NOT NULL DEFAULT 0,
  `qmSubsegmentFlags` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emptyTargets` tinyint(1) NOT NULL DEFAULT 0,
  `importAppVersion` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'contains the application version at import time of the task',
  `customerId` int(11) DEFAULT NULL COMMENT 'Client (= id from table LEK_customer)',
  `usageMode` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT 'cooperative' COMMENT 'defines in which how the task should be used by the users',
  `segmentCount` int(11) DEFAULT 0,
  `segmentFinishCount` int(11) DEFAULT 0,
  `taskType` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default',
  `projectId` int(11) DEFAULT NULL,
  `diffExportUsable` tinyint(1) DEFAULT 0,
  `description` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`),
  KEY `fk_LEK_task_1_idx` (`customerId`),
  KEY `fk_LEK_task_1_idx1` (`projectId`),
  CONSTRAINT `fk_LEK_task_1` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`),
  CONSTRAINT `fk_project_tasks` FOREIGN KEY (`projectId`) REFERENCES `LEK_task` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_task`
--

/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `LEK_task_versioning` BEFORE UPDATE ON `LEK_task`
 FOR EACH ROW IF OLD.entityVersion = NEW.entityVersion THEN 
          SET NEW.entityVersion = OLD.entityVersion + 1;
        ELSE 
          CALL raise_version_conflict; 
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `LEK_taskUserAssoc`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_taskUserAssoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `role` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reviewer',
  `workflowStepName` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reviewing' COMMENT 'workflow step which is used for this job entry',
  `workflow` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default' COMMENT 'the workflow to which this job belongs',
  `segmentrange` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usedState` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usedInternalSessionUniqId` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isPmOverride` int(1) NOT NULL DEFAULT 0,
  `staticAuthHash` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deadlineDate` datetime DEFAULT NULL,
  `assignmentDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `finishedDate` datetime DEFAULT NULL,
  `trackchangesShow` tinyint(1) NOT NULL DEFAULT 1,
  `trackchangesShowAll` tinyint(1) NOT NULL DEFAULT 1,
  `trackchangesAcceptReject` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`,`userGuid`,`workflow`,`workflowStepName`),
  KEY `userGuid` (`userGuid`),
  CONSTRAINT `fk_LEK_taskUserAssoc_1` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE,
  CONSTRAINT `fk_LEK_taskUserAssoc_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_taskUserAssoc`
--

/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `LEK_taskUserAssoc_versioning_ins` BEFORE INSERT ON `LEK_taskUserAssoc`
 FOR EACH ROW IF not @`entityVersion` is null AND @`entityVersion` > 0 THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `LEK_taskUserAssoc_versioning_up` BEFORE UPDATE ON `LEK_taskUserAssoc`
 FOR EACH ROW IF not @`entityVersion` is null AND @`entityVersion` > 0 THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `LEK_taskUserAssoc_versioning_del` BEFORE DELETE ON `LEK_taskUserAssoc`
 FOR EACH ROW IF not @`entityVersion` is null AND @`entityVersion` > 0 THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = OLD.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `LEK_taskUserTracking`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_taskUserTracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taskOpenerNumber` int(3) NOT NULL,
  `firstName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`,`userGuid`),
  KEY `userGuid` (`userGuid`),
  CONSTRAINT `LEK_taskUserTracking_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `LEK_taskUserTracking_ibfk_2` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_taskUserTracking`
--


--
-- Table structure for table `LEK_task_config`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuidConfigName` (`taskGuid`,`name`),
  KEY `taskGuidIdx` (`taskGuid`),
  KEY `configNameIdx` (`name`),
  CONSTRAINT `LEK_task_fk` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `Zf_configuration_fk` FOREIGN KEY (`name`) REFERENCES `Zf_configuration` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_task_config`
--


--
-- Table structure for table `LEK_task_excelexport`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task_excelexport` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to Zf_users',
  `exported` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_LEK_task_excelexport_1` (`taskGuid`),
  KEY `fk_LEK_task_excelexport_2` (`userGuid`),
  CONSTRAINT `fk_LEK_task_excelexport_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `fk_LEK_task_excelexport_2` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_task_excelexport`
--


--
-- Table structure for table `LEK_task_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint(2) NOT NULL DEFAULT 4,
  `state` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `eventCode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `worker` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'extra data to the error',
  `authUserGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authUser` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_task_log_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_task_log`
--


--
-- Table structure for table `LEK_task_meta`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `tbxHash` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'TBX Hash value',
  `mappingType` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT 'automatic',
  `bconfId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`),
  KEY `fk-task_meta-okapi_bconf` (`bconfId`),
  CONSTRAINT `LEK_task_meta_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `fk-task_meta-okapi_bconf` FOREIGN KEY (`bconfId`) REFERENCES `LEK_okapi_bconf` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_task_meta`
--


--
-- Table structure for table `LEK_task_migration`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task_migration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_task_migration_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_task_migration`
--


--
-- Table structure for table `LEK_task_usage_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task_usage_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskType` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sourceLang` int(11) NOT NULL,
  `targetLang` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  `yearAndMonth` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taskCount` double(19,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_type_count` (`taskType`,`customerId`,`yearAndMonth`,`sourceLang`,`targetLang`),
  KEY `fk_customer_idx` (`customerId`),
  CONSTRAINT `fk_customer` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_task_usage_log`
--


--
-- Table structure for table `LEK_term_attribute_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_term_attribute_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `historyCreated` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'timestamp of history entry creation',
  `attributeId` int(11) NOT NULL COMMENT 'reference to the attribute',
  `collectionId` int(11) NOT NULL COMMENT 'reference to the collection',
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'old attribute value',
  `processStatus` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT 'finalized' COMMENT 'old attribute processStatus',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'editing user of old attribute version',
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'editing user of old attribute version',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'creation date of old attribute version',
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'editing date of old attribute version',
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attributeId` (`attributeId`),
  KEY `collectionId` (`collectionId`),
  CONSTRAINT `LEK_term_attribute_history_ibfk_1` FOREIGN KEY (`attributeId`) REFERENCES `LEK_term_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `LEK_term_attribute_history_ibfk_2` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_term_attribute_history`
--


--
-- Table structure for table `LEK_term_attribute_proposal`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_term_attribute_proposal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'the proposed value',
  `collectionId` int(11) NOT NULL COMMENT 'links to the collection',
  `attributeId` int(11) DEFAULT NULL COMMENT 'links to the attribute',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `attributeId` (`attributeId`),
  KEY `collectionId` (`collectionId`),
  CONSTRAINT `LEK_term_attribute_proposal_ibfk_1` FOREIGN KEY (`attributeId`) REFERENCES `LEK_term_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `LEK_term_attribute_proposal_ibfk_2` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_term_attribute_proposal`
--


--
-- Table structure for table `LEK_term_attributes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_term_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `labelId` int(11) DEFAULT NULL,
  `collectionId` int(11) NOT NULL,
  `termId` int(11) DEFAULT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `parentId` int(11) DEFAULT NULL,
  `internalCount` int(11) DEFAULT NULL,
  `language` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attrType` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attrDataType` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attrTarget` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attrId` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attrLang` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `processStatus` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT 'finalized' COMMENT 'old term processStatus',
  PRIMARY KEY (`id`),
  KEY `fk_LEK_term_attributes_2_idx` (`termId`),
  KEY `fk_LEK_term_attributes_3_idx` (`labelId`),
  KEY `fk_LEK_term_attributes_1_idx` (`collectionId`),
  KEY `termEntryId` (`termEntryId`),
  KEY `idx_term_attributes_search` (`collectionId`,`parentId`,`labelId`,`termId`,`termEntryId`),
  KEY `parentId` (`parentId`),
  CONSTRAINT `LEK_term_attributes_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `LEK_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_term_attributes_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_term_attributes_2` FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_term_attributes_3` FOREIGN KEY (`labelId`) REFERENCES `LEK_term_attributes_label` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_term_attributes`
--


--
-- Table structure for table `LEK_term_attributes_label`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_term_attributes_label` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `labelText` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `idx_term_attributes_label_type` (`label`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_term_attributes_label`
--

INSERT INTO `LEK_term_attributes_label` VALUES
(1,'transac','origination','Erstellung'),
(2,'transacNote','responsibility','Verantwortlich'),
(3,'date',NULL,'Datum'),
(4,'transac','modification','Letzte Änderung'),
(5,'termNote','termType','Term Typ'),
(6,'descrip','definition','Definition'),
(7,'termNote','abbreviatedFormFor','Abkürzung für'),
(8,'termNote','pronunciation','Aussprache'),
(9,'termNote','normativeAuthorization','Einstufung'),
(10,'transac','origination','Erstellung'),
(11,'transacNote','responsibility','Verantwortlich'),
(12,'date',NULL,'Datum'),
(13,'transac','modification','Letzte Änderung'),
(14,'termNote','termType','Term Typ'),
(15,'descrip','definition','Definition'),
(16,'termNote','abbreviatedFormFor','Abkürzung für'),
(17,'termNote','pronunciation','Aussprache'),
(18,'termNote','normativeAuthorization','Einstufung'),
(19,'descrip','subjectField','Fachgebiet'),
(20,'descrip','relatedConcept','Verwandtes Konzept'),
(21,'descrip','relatedConceptBroader','Erweitertes verwandtes Konzept'),
(22,'admin','productSubset','Produkt-Untermenge'),
(23,'admin','sourceIdentifier','Quellenidentifikator'),
(24,'termNote','partOfSpeech','Wortart'),
(25,'descrip','context','Kontext'),
(26,'admin','businessUnitSubset','Teilbereich der Geschäftseinheit'),
(27,'admin','projectSubset','Projektuntermenge'),
(28,'termNote','grammaticalGender','Grammatikalisches Geschlecht'),
(29,'note',NULL,'Anmerkung'),
(30,'termNote','administrativeStatus','Administrativer Status'),
(31,'termNote','transferComment','Übertragungskommentar'),
(32,'admin','entrySource','Quelle des Eintrags');

--
-- Table structure for table `LEK_term_entry`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_term_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `groupId` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isProposal` tinyint(1) DEFAULT 0 COMMENT 'is the term entry proposal',
  PRIMARY KEY (`id`),
  KEY `fk_LEK_term_entry_1_idx` (`collectionId`),
  CONSTRAINT `fk_LEK_term_entry_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_term_entry`
--


--
-- Table structure for table `LEK_term_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_term_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `historyCreated` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'timestamp of history entry creation',
  `termId` int(11) NOT NULL COMMENT 'reference to the term',
  `collectionId` int(11) NOT NULL COMMENT 'reference to the collection',
  `term` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'old term value',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'old term status',
  `processStatus` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT 'finalized' COMMENT 'old term processStatus',
  `definition` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'old term definition',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'editing user of old term version',
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'editing user of old term version',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'creation date of old term version',
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'editing date of old term version',
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `termId` (`termId`),
  KEY `collectionId` (`collectionId`),
  CONSTRAINT `LEK_term_history_ibfk_1` FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `LEK_term_history_ibfk_2` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_term_history`
--


--
-- Table structure for table `LEK_term_proposal`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_term_proposal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'the proposed term',
  `collectionId` int(11) NOT NULL COMMENT 'links to the collection',
  `termId` int(11) DEFAULT NULL COMMENT 'links to the term',
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `termId` (`termId`),
  KEY `collectionId` (`collectionId`),
  CONSTRAINT `LEK_term_proposal_ibfk_1` FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `LEK_term_proposal_ibfk_2` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_term_proposal`
--


--
-- Table structure for table `LEK_terms`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `mid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processStatus` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT 'finalized',
  `definition` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groupId` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` int(32) NOT NULL,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `groupId` (`groupId`),
  KEY `fk_LEK_terms_1_idx` (`termEntryId`),
  KEY `fk_LEK_terms_2_idx` (`collectionId`),
  KEY `idx_term_tcid_language` (`collectionId`,`language`),
  CONSTRAINT `fk_LEK_terms_1` FOREIGN KEY (`termEntryId`) REFERENCES `LEK_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_terms_2` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_terms`
--


--
-- Table structure for table `LEK_user_assoc_default`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_user_assoc_default` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerId` int(11) NOT NULL,
  `sourceLang` int(11) DEFAULT NULL,
  `targetLang` int(11) DEFAULT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workflowStepName` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reviewing',
  `workflow` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `deadlineDate` double(19,2) DEFAULT NULL,
  `trackchangesShow` tinyint(1) NOT NULL DEFAULT 1,
  `trackchangesShowAll` tinyint(1) NOT NULL DEFAULT 1,
  `trackchangesAcceptReject` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customerId` (`customerId`,`sourceLang`,`targetLang`,`userGuid`,`workflow`,`workflowStepName`),
  KEY `sourceLang` (`sourceLang`),
  KEY `targetLang` (`targetLang`),
  KEY `userGuid` (`userGuid`),
  KEY `workflow` (`workflow`),
  KEY `fk_LEK_user_assoc_default_1_idx` (`workflowStepName`),
  CONSTRAINT `LEK_user_assoc_default_ibfk_1` FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `LEK_user_assoc_default_ibfk_2` FOREIGN KEY (`sourceLang`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `LEK_user_assoc_default_ibfk_3` FOREIGN KEY (`targetLang`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `LEK_user_assoc_default_ibfk_4` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_user_assoc_default_1` FOREIGN KEY (`workflowStepName`) REFERENCES `LEK_workflow_step` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_user_assoc_default_2` FOREIGN KEY (`workflow`) REFERENCES `LEK_workflow` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_user_assoc_default`
--


--
-- Table structure for table `LEK_user_changelog_info`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_user_changelog_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `changelogId` int(11) DEFAULT NULL,
  PRIMARY KEY (`userId`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_user_changelog_info`
--


--
-- Table structure for table `LEK_user_config`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_user_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`,`userGuid`),
  UNIQUE KEY `userGuidConfigNameIndex` (`name`,`userGuid`),
  KEY `userGuid` (`userGuid`),
  KEY `fk_LEK_user_config_1_idx` (`name`),
  CONSTRAINT `LEK_user_config_ibfk_1` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE,
  CONSTRAINT `fk_LEK_user_config_1` FOREIGN KEY (`name`) REFERENCES `Zf_configuration` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_user_config`
--


--
-- Table structure for table `LEK_user_meta`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_user_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `sourceLangDefault` int(11) DEFAULT NULL,
  `targetLangDefault` int(11) DEFAULT NULL,
  `lastUsedApp` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Last used translate5 app for the user (instanttranslate, termportal etc..).',
  PRIMARY KEY (`id`),
  KEY `fk_LEK_user_meta_1_idx` (`userId`),
  KEY `fk_LEK_user_meta_2_idx` (`sourceLangDefault`),
  KEY `fk_LEK_user_meta_3_idx` (`targetLangDefault`),
  CONSTRAINT `fk_LEK_user_meta_1` FOREIGN KEY (`userId`) REFERENCES `Zf_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_user_meta_2` FOREIGN KEY (`sourceLangDefault`) REFERENCES `LEK_languages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_LEK_user_meta_3` FOREIGN KEY (`targetLangDefault`) REFERENCES `LEK_languages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_user_meta`
--


--
-- Table structure for table `LEK_workflow`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'technical workflow name, use alphanumeric chars only (refresh app cache!)',
  `label` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'human readable workflow name (goes through the translator, refresh app cache!)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_workflow`
--

INSERT INTO `LEK_workflow` VALUES
(1,'default','Standard-Workflow'),
(2,'complex','Complex workflow');

--
-- Table structure for table `LEK_workflow_action`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_workflow_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `trigger` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'triggering function in the workflow',
  `inStep` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'action is only triggered if caused in the given step, null for all steps',
  `byRole` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'action is only triggered if caused by the given role, null for all roles',
  `userState` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'action os only triggered for the given state, null for all states',
  `actionClass` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'class to be called',
  `action` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'class method to be called',
  `parameters` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'parameters given to the action, stored as JSON here',
  `position` int(11) NOT NULL DEFAULT 0 COMMENT 'defines the sort order of actions with same conditions',
  `description` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'contains a human readable description for the row',
  PRIMARY KEY (`id`),
  KEY `workflow` (`workflow`,`trigger`,`position`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_workflow_action`
--

INSERT INTO `LEK_workflow_action` VALUES
(1,'default','handleAllFinishOfARole','reviewing','reviewer','finished','editor_Workflow_Actions','segmentsSetUntouchedState',NULL,0,NULL),
(3,'default','handleAllFinishOfARole',NULL,NULL,'finished','editor_Workflow_Notification','notifyAllFinishOfARole',NULL,2,NULL),
(4,'default','handleUnfinish',NULL,'reviewer',NULL,'editor_Workflow_Actions','segmentsSetInitialState',NULL,0,NULL),
(6,'default','handleImport',NULL,NULL,NULL,'editor_Workflow_Notification','notifyNewTaskForPm',NULL,0,NULL),
(8,'disabled','doCronDaily',NULL,NULL,NULL,'editor_Workflow_Actions','finishOverduedTaskUserAssoc',NULL,0,NULL),
(10,'default','handleAfterImport',NULL,NULL,NULL,'editor_Workflow_Notification','notifyAllAssociatedUsers',NULL,0,NULL),
(11,'default','doCronDaily',NULL,NULL,NULL,'editor_Workflow_Notification','notifyTermProposals','{\"receiverUser\":\"\"}',0,NULL),
(12,'default','handleFirstConfirmOfAStep',NULL,NULL,NULL,'editor_Workflow_Actions','confirmCooperativeUsers',NULL,0,NULL),
(13,'default','handleFirstConfirmOfAStep',NULL,NULL,NULL,'editor_Workflow_Actions','removeCompetitiveUsers',NULL,0,NULL),
(14,'default','doCronPeriodical',NULL,NULL,NULL,'editor_Workflow_Notification','notifyDeadlineApproaching','{\"daysOffset\": 2}',0,'For the separate delivery date of every role a reminder mail is send by the translate5 cronController. How much days before the deadline a reminder will be send is defined in the parameters column. Example of 2 days before deadline reminder: {\"daysOffset\": 2}'),
(15,'default','handleImportCompleted',NULL,NULL,NULL,'editor_Workflow_Notification','notifyImportErrorSummary','{\"always\":false}',0,'Sends a summary of import errors and warnings to the PM. By default the PM gets the mail only if he did not start the import, so the import was started by API. Can be overriden by setting always to true.'),
(16,'default','doCronPeriodical',NULL,NULL,NULL,'editor_Workflow_Actions','removeOldConnectorUsageLog',NULL,0,NULL),
(17,'complex','handleAllFinishOfARole','reviewing','reviewer','finished','editor_Workflow_Actions','segmentsSetUntouchedState',NULL,0,NULL),
(18,'complex','handleAllFinishOfARole',NULL,NULL,'finished','editor_Workflow_Notification','notifyAllFinishOfARole',NULL,2,NULL),
(19,'complex','handleUnfinish',NULL,'reviewer',NULL,'editor_Workflow_Actions','segmentsSetInitialState',NULL,0,NULL),
(20,'complex','handleImport',NULL,NULL,NULL,'editor_Workflow_Notification','notifyNewTaskForPm',NULL,0,NULL),
(21,'complex','handleAfterImport',NULL,NULL,NULL,'editor_Workflow_Notification','notifyAllAssociatedUsers',NULL,0,NULL),
(22,'complex','doCronDaily',NULL,NULL,NULL,'editor_Workflow_Notification','notifyTermProposals','{\"receiverUser\":\"\"}',0,NULL),
(23,'complex','handleFirstConfirmOfAStep',NULL,NULL,NULL,'editor_Workflow_Actions','confirmCooperativeUsers',NULL,0,NULL),
(24,'complex','handleFirstConfirmOfAStep',NULL,NULL,NULL,'editor_Workflow_Actions','removeCompetitiveUsers',NULL,0,NULL),
(25,'complex','doCronPeriodical',NULL,NULL,NULL,'editor_Workflow_Notification','notifyDeadlineApproaching','{\"daysOffset\": 2}',0,'For the separate delivery date of every role a reminder mail is send by the translate5 cronController. How much days before the deadline a reminder will be send is defined in the parameters column. Example of 2 days before deadline reminder: {\"daysOffset\": 2}'),
(26,'complex','handleImportCompleted',NULL,NULL,NULL,'editor_Workflow_Notification','notifyImportErrorSummary','{\"always\":false}',0,'Sends a summary of import errors and warnings to the PM. By default the PM gets the mail only if he did not start the import, so the import was started by API. Can be overriden by setting always to true.'),
(27,'complex','doCronPeriodical',NULL,NULL,NULL,'editor_Workflow_Actions','removeOldConnectorUsageLog',NULL,0,NULL),
(32,'default','handleDirect::notifyAllUsersAboutTaskAssociation',NULL,NULL,NULL,'editor_Workflow_Notification','notifyAllAssociatedUsers',NULL,0,NULL),
(33,'complex','handleDirect::notifyAllUsersAboutTaskAssociation',NULL,NULL,NULL,'editor_Workflow_Notification','notifyAllAssociatedUsers',NULL,0,NULL),
(35,'complex','handleProjectCreated',NULL,NULL,NULL,'editor_Workflow_Notification','notifyNewProjectForPm','{\"projectTypes\": [\"termtranslation\"]}',0,NULL),
(36,'default','handleProjectCreated',NULL,NULL,NULL,'editor_Workflow_Notification','notifyNewProjectForPm','{\"projectTypes\": [\"termtranslation\"]}',0,NULL),
(38,'default','handleAllFinishOfARole',NULL,NULL,NULL,'editor_Workflow_Actions','triggerCallbackAction','',0,'Send a request to the configured url parametar with the task and task user assoc data. If params field is provided in the parametars field, this will be applied to in the request json.'),
(39,'complex','handleAllFinishOfARole',NULL,NULL,NULL,'editor_Workflow_Actions','triggerCallbackAction','',0,'Send a request to the configured url parametar with the task and task user assoc data. If params field is provided in the parametars field, this will be applied to in the request json.');

--
-- Table structure for table `LEK_workflow_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_workflow_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taskState` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authUserGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authUser` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stepName` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stepNr` int(11) NOT NULL DEFAULT 0,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_workflow_log_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_workflow_log`
--


--
-- Table structure for table `LEK_workflow_step`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_workflow_step` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflowName` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'technical workflow step name, use alphanumeric chars only (refresh app cache!)',
  `label` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'human readable workflow step name (goes through the translator,  refresh app cache!)',
  `role` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'one of the available roles, by default review|translator|translatorCheck|visitor can be extended by customized PHP workflows',
  `position` int(11) DEFAULT NULL COMMENT 'the position of the step in the workflow, may be null if not in chain (for visitor for example), steps with same position are ordered by name then',
  `flagInitiallyFiltered` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'define if the segments of the previous step should be filtered in the GUI when reaching this step',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`workflowName`),
  KEY `workflowName` (`workflowName`),
  CONSTRAINT `LEK_workflow_step_ibfk_1` FOREIGN KEY (`workflowName`) REFERENCES `LEK_workflow` (`name`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_workflow_step`
--

INSERT INTO `LEK_workflow_step` VALUES
(1,'default','translation','Übersetzung','translator',1,0),
(2,'default','reviewing','Lektorat','reviewer',2,0),
(3,'default','translatorCheck','Zweites Lektorat','translatorCheck',3,1),
(4,'default','visiting','Nur anschauen','visitor',NULL,0),
(5,'complex','firsttranslation','Übersetzung','translator',1,0),
(6,'complex','review1stlanguage','1. Lektorat (sprachlich)','reviewer',2,0),
(7,'complex','review1sttechnical','1. Lektorat (technisch)','reviewer',3,0),
(8,'complex','review2ndlanguage','2. Lektorat (sprachlich)','reviewer',4,0),
(9,'complex','review2ndtechnical','2. Lektorat (technisch)','reviewer',5,0),
(10,'complex','textapproval','Textfreigabe','reviewer',6,0),
(11,'complex','graphicimplementation','Externes DTP (nur Leserechte in translate5)','visitor',7,0),
(12,'complex','finaltextapproval','Finale Textfreigabe','reviewer',8,0);

--
-- Table structure for table `LEK_workflow_userpref`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_workflow_userpref` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Foreign Key to LEK_task',
  `workflow` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'links to the used workflow for this ',
  `workflowStep` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the workflow step which is affected by the settings, optional, null to affect all steps',
  `anonymousCols` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'should the column names be rendered anonymously',
  `visibility` enum('show','hide','disable') COLLATE utf8mb4_unicode_ci DEFAULT 'show' COMMENT 'visibility of non-editable target columns',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Foreign Key to Zf_users, optional, constrain the prefs to this user',
  `fields` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'field names as used in LEK_segment_fields',
  `notEditContent` tinyint(1) NOT NULL DEFAULT 0,
  `taskUserAssocId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_LEK_workflow_userpref_1_idx` (`taskGuid`),
  KEY `fk_LEK_workflow_userpref_2_idx` (`taskUserAssocId`),
  CONSTRAINT `fk_LEK_workflow_userpref_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `fk_LEK_workflow_userpref_2` FOREIGN KEY (`taskUserAssocId`) REFERENCES `LEK_taskUserAssoc` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LEK_workflow_userpref`
--

/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `LEK_workflow_userpref_versioning_ins` BEFORE INSERT ON `LEK_workflow_userpref`
 FOR EACH ROW IF not @`entityVersion` is null AND @`entityVersion` > 0 THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `LEK_workflow_userpref_versioning_up` BEFORE UPDATE ON `LEK_workflow_userpref`
 FOR EACH ROW IF not @`entityVersion` is null AND @`entityVersion` > 0 THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `LEK_workflow_userpref_versioning_del` BEFORE DELETE ON `LEK_workflow_userpref`
 FOR EACH ROW IF not @`entityVersion` is null AND @`entityVersion` > 0 THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = OLD.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `Zf_acl_rules`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_acl_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the PHP module this acl rule was defined for',
  `role` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'the name of the role which has the defined rule',
  `resource` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'the resource to be allowed',
  `right` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'the single right to be allowed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `module` (`module`,`role`,`resource`,`right`)
) ENGINE=InnoDB AUTO_INCREMENT=451 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_acl_rules`
--

INSERT INTO `Zf_acl_rules` VALUES
(447,'editor','admin','frontend','pluginSpellCheckMain'),
(448,'editor','editor','frontend','pluginSpellCheckMain'),
(449,'editor','pm','frontend','pluginSpellCheckMain'),
(450,'editor','pmlight','frontend','pluginSpellCheckMain'),
(228,'default','admin','auto_set_role','editor'),
(229,'default','admin','auto_set_role','pm'),
(232,'default','api','auto_set_role','admin'),
(230,'default','api','auto_set_role','editor'),
(231,'default','api','auto_set_role','pm'),
(437,'default','instantTranslate','frontend','ipBasedAuthentication'),
(121,'default','instantTranslate','initial_page','instantTranslatePortal'),
(44,'default','noRights','error','all'),
(65,'default','noRights','help','all'),
(45,'default','noRights','index','all'),
(64,'default','noRights','license','all'),
(47,'default','noRights','login','all'),
(46,'default','noRights','translate','all'),
(227,'default','pm','auto_set_role','editor'),
(436,'default','termCustomerSearch','frontend','ipBasedAuthentication'),
(105,'default','termCustomerSearch','initial_page','termPortal'),
(177,'default','termProposer','initial_page','termPortal'),
(236,'editor','admin','applicationconfigLevel','customer'),
(208,'editor','admin','applicationconfigLevel','instance'),
(237,'editor','admin','applicationconfigLevel','task'),
(238,'editor','admin','applicationconfigLevel','taskImport'),
(210,'editor','admin','applicationconfigLevel','user'),
(184,'editor','admin','auto_set_role','editor'),
(185,'editor','admin','auto_set_role','pm'),
(350,'editor','admin','auto_set_role','termCustomerSearch'),
(351,'editor','admin','auto_set_role','termFinalizer'),
(347,'editor','admin','auto_set_role','termPM'),
(348,'editor','admin','auto_set_role','termPM_allClients'),
(349,'editor','admin','auto_set_role','termProposer'),
(352,'editor','admin','auto_set_role','termReviewer'),
(55,'editor','admin','backend','seeAllUsers'),
(128,'editor','admin','editor_apps','all'),
(378,'editor','admin','editor_attribute','deleteAny'),
(68,'editor','admin','editor_config','all'),
(115,'editor','admin','editor_plugins_termimport_termimport','all'),
(117,'editor','admin','editor_plugins_termimport_termimport','crossapi'),
(116,'editor','admin','editor_plugins_termimport_termimport','filesystem'),
(167,'editor','admin','editor_task','excelexport'),
(170,'editor','admin','editor_task','excelreimport'),
(190,'editor','admin','editor_task','userlist'),
(381,'editor','admin','editor_term','deleteAny'),
(66,'editor','admin','editor_test','all'),
(240,'editor','admin','frontend','configOverwriteGrid'),
(107,'editor','admin','frontend','downloadImportArchive'),
(355,'editor','admin','frontend','editorCancelImport'),
(132,'editor','admin','frontend','editorCustomerSwitch'),
(175,'editor','admin','frontend','editorExcelreexportTask'),
(173,'editor','admin','frontend','editorExcelreimportTask'),
(134,'editor','admin','frontend','editorLogTask'),
(317,'editor','admin','frontend','editorManageQualities'),
(216,'editor','admin','frontend','editorTaskKpi'),
(141,'editor','admin','frontend','editorTaskOverviewColumnMenu'),
(91,'editor','admin','frontend','languageResourcesAddNonFilebased'),
(222,'editor','admin','frontend','pluginNecTm'),
(219,'editor','admin','frontend','pluginPangeaMt'),
(112,'editor','admin','frontend','pluginSpellCheck'),
(164,'editor','admin','frontend','readAnonymyzedUsers'),
(321,'editor','admin','frontend','taskConfigOverwriteGrid'),
(62,'editor','admin','getUpdateNotification','all'),
(59,'editor','admin','setaclrole','admin'),
(60,'editor','admin','setaclrole','editor'),
(162,'editor','admin','setaclrole','instantTranslate'),
(58,'editor','admin','setaclrole','pm'),
(315,'editor','admin','setaclrole','pmlight'),
(163,'editor','admin','setaclrole','termCustomerSearch'),
(161,'editor','admin','setaclrole','termProposer'),
(204,'editor','api','applicationconfigLevel','instance'),
(211,'editor','api','applicationconfigLevel','user'),
(188,'editor','api','auto_set_role','admin'),
(186,'editor','api','auto_set_role','editor'),
(187,'editor','api','auto_set_role','pm'),
(189,'editor','api','backend','applicationstate'),
(136,'editor','api','backend','sessionDeleteByInternalId'),
(126,'editor','api','editor_apps','all'),
(120,'editor','api','editor_instanttranslate','all'),
(77,'editor','api','editor_language','all'),
(181,'editor','api','editor_languageresourceinstance','testexport'),
(242,'editor','api','editor_session','impersonate'),
(169,'editor','api','editor_task','excelexport'),
(172,'editor','api','editor_task','excelreimport'),
(193,'editor','api','editor_task','userlist'),
(142,'editor','api','frontend','editorTaskOverviewColumnMenu'),
(165,'editor','api','frontend','readAnonymyzedUsers'),
(195,'editor','api','initial_tasktype','default'),
(194,'editor','api','initial_tasktype','instanttranslate-pre-translate'),
(198,'editor','api','initial_tasktype','project'),
(213,'editor','api','initial_tasktype','projectTask'),
(71,'editor','api','readAuthHash','all'),
(316,'editor','api','setaclrole','pmlight'),
(78,'editor','basic','editor_plugins_changelog_changelog','all'),
(245,'editor','basic','editor_quality','all'),
(9,'editor','basic','editor_user','authenticated'),
(207,'editor','basic','frontend','editorMenuTask'),
(79,'editor','basic','frontend','pluginChangeLogChangelog'),
(8,'editor','basic','frontend','taskOverviewFrontendController'),
(7,'editor','basic','frontend','userPrefFrontendController'),
(6,'editor','basic','headPanelFrontendController','all'),
(203,'editor','editor','applicationconfigLevel','user'),
(17,'editor','editor','editor_alikesegment','all'),
(127,'editor','editor','editor_apps','all'),
(182,'editor','editor','editor_category','all'),
(14,'editor','editor','editor_comment','all'),
(356,'editor','editor','editor_commentnav','all'),
(200,'editor','editor','editor_config','all'),
(13,'editor','editor','editor_file','all'),
(3,'editor','editor','editor_index','all'),
(440,'editor','editor','editor_index','applicationstate'),
(438,'editor','editor','editor_index','index'),
(441,'editor','editor','editor_index','localizedjsstrings'),
(439,'editor','editor','editor_index','logbrowsertype'),
(444,'editor','editor','editor_index','makexliff'),
(443,'editor','editor','editor_index','pluginpublic'),
(442,'editor','editor','editor_index','wdhehelp'),
(119,'editor','editor','editor_instanttranslate','all'),
(87,'editor','editor','editor_languageresourceinstance','query'),
(86,'editor','editor','editor_languageresourceinstance','search'),
(417,'editor','editor','editor_languageresourceinstance','translate'),
(85,'editor','editor','editor_languageresourcetaskassoc','index'),
(418,'editor','editor','editor_languageresourcetaskpivotassoc','index'),
(114,'editor','editor','editor_plugins_spellcheck_spellcheckquery','all'),
(18,'editor','editor','editor_referencefile','all'),
(15,'editor','editor','editor_segment','all'),
(16,'editor','editor','editor_segmentfield','all'),
(69,'editor','editor','editor_segmentuserassoc','all'),
(435,'editor','editor','editor_session','resyncOperation'),
(11,'editor','editor','editor_task','get'),
(10,'editor','editor','editor_task','index'),
(12,'editor','editor','editor_task','put'),
(191,'editor','editor','editor_task','userlist'),
(138,'editor','editor','editor_taskusertracking','all'),
(19,'editor','editor','editor_user','index'),
(22,'editor','editor','frontend','editorEditTask'),
(20,'editor','editor','frontend','editorFinishTask'),
(21,'editor','editor','frontend','editorOpenTask'),
(140,'editor','editor','frontend','editorTaskOverviewColumnMenu'),
(92,'editor','editor','frontend','languageResourcesMatchQuery'),
(93,'editor','editor','frontend','languageResourcesSearchQuery'),
(416,'editor','editor','frontend','languageResourcesSynonymSearch'),
(221,'editor','editor','frontend','pluginNecTm'),
(218,'editor','editor','frontend','pluginPangeaMt'),
(111,'editor','editor','frontend','pluginSpellCheck'),
(23,'editor','editor','frontend','useChangeAlikes'),
(104,'editor','editor','initial_page','editor'),
(130,'editor','instantTranslate','editor_apps','all'),
(123,'editor','instantTranslate','editor_instanttranslate','all'),
(197,'editor','instantTranslate','initial_tasktype','instanttranslate-pre-translate'),
(2,'editor','noRights','editor_cron','all'),
(361,'editor','noRights','editor_fakelangres','all'),
(323,'editor','noRights','editor_index','applicationstate'),
(244,'editor','noRights','editor_session','get'),
(70,'editor','noRights','editor_session','index'),
(243,'editor','noRights','editor_session','post'),
(53,'editor','noRights','editor_worker','all'),
(52,'editor','noRights','editor_worker','queue'),
(1,'editor','noRights','error','all'),
(233,'editor','pm','applicationconfigLevel','customer'),
(234,'editor','pm','applicationconfigLevel','task'),
(235,'editor','pm','applicationconfigLevel','taskImport'),
(212,'editor','pm','applicationconfigLevel','user'),
(183,'editor','pm','auto_set_role','editor'),
(29,'editor','pm','backend','editAllTasks'),
(28,'editor','pm','backend','loadAllTasks'),
(54,'editor','pm','backend','seeAllUsers'),
(129,'editor','pm','editor_apps','all'),
(201,'editor','pm','editor_config','all'),
(100,'editor','pm','editor_customer','all'),
(415,'editor','pm','editor_customermeta','all'),
(118,'editor','pm','editor_instanttranslate','all'),
(124,'editor','pm','editor_instanttranslateapi','all'),
(82,'editor','pm','editor_languageresourceinstance','all'),
(83,'editor','pm','editor_languageresourceresource','all'),
(84,'editor','pm','editor_languageresourcetaskassoc','all'),
(81,'editor','pm','editor_plugins_globalesepretranslation_globalese','all'),
(225,'editor','pm','editor_plugins_matchanalysis_matchanalysis','all'),
(432,'editor','pm','editor_plugins_okapi_bconf','all'),
(434,'editor','pm','editor_plugins_okapi_bconfdefaultfilter','all'),
(433,'editor','pm','editor_plugins_okapi_bconffilter','all'),
(24,'editor','pm','editor_task','all'),
(158,'editor','pm','editor_task','analysisOperation'),
(357,'editor','pm','editor_task','autoqaOperation'),
(168,'editor','pm','editor_task','excelexport'),
(171,'editor','pm','editor_task','excelreimport'),
(159,'editor','pm','editor_task','pretranslationOperation'),
(192,'editor','pm','editor_task','userlist'),
(67,'editor','pm','editor_taskmeta','all'),
(26,'editor','pm','editor_taskuserassoc','all'),
(139,'editor','pm','editor_term','all'),
(96,'editor','pm','editor_termcollection','all'),
(25,'editor','pm','editor_user','all'),
(246,'editor','pm','editor_userassocdefault','all'),
(27,'editor','pm','editor_workflowuserpref','all'),
(125,'editor','pm','frontend','customerAdministration'),
(31,'editor','pm','frontend','editorAddTask'),
(39,'editor','pm','frontend','editorAddUser'),
(226,'editor','pm','frontend','editorAnalysisTask'),
(36,'editor','pm','frontend','editorChangeUserAssocTask'),
(101,'editor','pm','frontend','editorCloneTask'),
(133,'editor','pm','frontend','editorCustomerSwitch'),
(205,'editor','pm','frontend','editorDeleteProject'),
(38,'editor','pm','frontend','editorDeleteTask'),
(41,'editor','pm','frontend','editorDeleteUser'),
(43,'editor','pm','frontend','editorEditAllTasks'),
(180,'editor','pm','frontend','editorEditTaskEdit100PercentMatch'),
(76,'editor','pm','frontend','editorEditTaskOrderDate'),
(75,'editor','pm','frontend','editorEditTaskPm'),
(72,'editor','pm','frontend','editorEditTaskTaskName'),
(40,'editor','pm','frontend','editorEditUser'),
(33,'editor','pm','frontend','editorEndTask'),
(176,'editor','pm','frontend','editorExcelreexportTask'),
(174,'editor','pm','frontend','editorExcelreimportTask'),
(384,'editor','pm','frontend','editorExportExcelhistory'),
(32,'editor','pm','frontend','editorExportTask'),
(135,'editor','pm','frontend','editorLogTask'),
(318,'editor','pm','frontend','editorManageQualities'),
(215,'editor','pm','frontend','editorMenuProject'),
(35,'editor','pm','frontend','editorPreferencesTask'),
(206,'editor','pm','frontend','editorProjectTask'),
(359,'editor','pm','frontend','editorReloadProject'),
(34,'editor','pm','frontend','editorReopenTask'),
(42,'editor','pm','frontend','editorResetPwUser'),
(5,'editor','pm','frontend','editorShowexportmenuTask'),
(217,'editor','pm','frontend','editorTaskKpi'),
(137,'editor','pm','frontend','editorTaskLog'),
(143,'editor','pm','frontend','editorTaskOverviewColumnMenu'),
(37,'editor','pm','frontend','editorWorkflowPrefsTask'),
(89,'editor','pm','frontend','languageResourcesAddFilebased'),
(88,'editor','pm','frontend','languageResourcesOverview'),
(90,'editor','pm','frontend','languageResourcesTaskassoc'),
(407,'editor','pm','frontend','lockSegmentBatch'),
(409,'editor','pm','frontend','lockSegmentOperation'),
(80,'editor','pm','frontend','pluginGlobalesePreTranslationGlobalese'),
(224,'editor','pm','frontend','pluginMatchAnalysisMatchAnalysis'),
(223,'editor','pm','frontend','pluginNecTm'),
(431,'editor','pm','frontend','pluginOkapiBconfPrefs'),
(220,'editor','pm','frontend','pluginPangeaMt'),
(113,'editor','pm','frontend','pluginSpellCheck'),
(166,'editor','pm','frontend','readAnonymyzedUsers'),
(320,'editor','pm','frontend','taskConfigOverwriteGrid'),
(313,'editor','pm','frontend','taskUserAssocFrontendController'),
(408,'editor','pm','frontend','unlockSegmentBatch'),
(410,'editor','pm','frontend','unlockSegmentOperation'),
(30,'editor','pm','frontend','userAdministration'),
(196,'editor','pm','initial_tasktype','default'),
(199,'editor','pm','initial_tasktype','project'),
(214,'editor','pm','initial_tasktype','projectTask'),
(57,'editor','pm','setaclrole','editor'),
(122,'editor','pm','setaclrole','instantTranslate'),
(56,'editor','pm','setaclrole','pm'),
(314,'editor','pm','setaclrole','pmlight'),
(95,'editor','pm','setaclrole','termCustomerSearch'),
(344,'editor','pm','setaclrole','termFinalizer'),
(345,'editor','pm','setaclrole','termPM'),
(346,'editor','pm','setaclrole','termPM_allClients'),
(160,'editor','pm','setaclrole','termProposer'),
(343,'editor','pm','setaclrole','termReviewer'),
(247,'editor','pmlight','applicationconfigLevel','task'),
(248,'editor','pmlight','applicationconfigLevel','taskImport'),
(249,'editor','pmlight','applicationconfigLevel','user'),
(250,'editor','pmlight','auto_set_role','editor'),
(251,'editor','pmlight','backend','seeAllUsers'),
(252,'editor','pmlight','editor_apps','all'),
(253,'editor','pmlight','editor_config','all'),
(270,'editor','pmlight','editor_customer','index'),
(272,'editor','pmlight','editor_languageresourcetaskassoc','all'),
(254,'editor','pmlight','editor_plugins_changelog_changelog','all'),
(255,'editor','pmlight','editor_plugins_globalesepretranslation_globalese','all'),
(256,'editor','pmlight','editor_plugins_matchanalysis_matchanalysis','all'),
(257,'editor','pmlight','editor_plugins_visualreview_fonts','all'),
(258,'editor','pmlight','editor_plugins_visualreview_visualreview','all'),
(259,'editor','pmlight','editor_task','all'),
(260,'editor','pmlight','editor_task','analysisOperation'),
(358,'editor','pmlight','editor_task','autoqaOperation'),
(261,'editor','pmlight','editor_task','excelexport'),
(262,'editor','pmlight','editor_task','excelreimport'),
(263,'editor','pmlight','editor_task','pretranslationOperation'),
(264,'editor','pmlight','editor_task','userlist'),
(265,'editor','pmlight','editor_taskmeta','all'),
(266,'editor','pmlight','editor_taskuserassoc','all'),
(267,'editor','pmlight','editor_term','all'),
(268,'editor','pmlight','editor_termcollection','all'),
(269,'editor','pmlight','editor_user','index'),
(271,'editor','pmlight','editor_workflowuserpref','all'),
(273,'editor','pmlight','frontend','editorAddTask'),
(274,'editor','pmlight','frontend','editorAnalysisTask'),
(275,'editor','pmlight','frontend','editorChangeUserAssocTask'),
(276,'editor','pmlight','frontend','editorCloneTask'),
(277,'editor','pmlight','frontend','editorDeleteProject'),
(278,'editor','pmlight','frontend','editorDeleteTask'),
(279,'editor','pmlight','frontend','editorEditTaskDeliveryDate'),
(280,'editor','pmlight','frontend','editorEditTaskEdit100PercentMatch'),
(281,'editor','pmlight','frontend','editorEditTaskTaskName'),
(282,'editor','pmlight','frontend','editorEndTask'),
(283,'editor','pmlight','frontend','editorExcelreexportTask'),
(284,'editor','pmlight','frontend','editorExcelreimportTask'),
(385,'editor','pmlight','frontend','editorExportExcelhistory'),
(285,'editor','pmlight','frontend','editorExportTask'),
(286,'editor','pmlight','frontend','editorLogTask'),
(319,'editor','pmlight','frontend','editorManageQualities'),
(287,'editor','pmlight','frontend','editorMenuProject'),
(288,'editor','pmlight','frontend','editorPreferencesTask'),
(289,'editor','pmlight','frontend','editorProjectTask'),
(360,'editor','pmlight','frontend','editorReloadProject'),
(290,'editor','pmlight','frontend','editorReopenTask'),
(291,'editor','pmlight','frontend','editorShowexportmenuTask'),
(292,'editor','pmlight','frontend','editorTaskKpi'),
(293,'editor','pmlight','frontend','editorTaskLog'),
(294,'editor','pmlight','frontend','editorTaskOverviewColumnMenu'),
(295,'editor','pmlight','frontend','editorWorkflowPrefsTask'),
(303,'editor','pmlight','frontend','languageResourcesTaskassoc'),
(411,'editor','pmlight','frontend','lockSegmentBatch'),
(413,'editor','pmlight','frontend','lockSegmentOperation'),
(296,'editor','pmlight','frontend','pluginGlobalesePreTranslationGlobalese'),
(297,'editor','pmlight','frontend','pluginInstantTranslateInstantTranslate'),
(298,'editor','pmlight','frontend','pluginMatchAnalysisMatchAnalysis'),
(299,'editor','pmlight','frontend','pluginSpellCheck'),
(300,'editor','pmlight','frontend','pluginVisualReviewFontPrefs'),
(301,'editor','pmlight','frontend','pluginVisualReviewGlobal'),
(302,'editor','pmlight','frontend','pluginVisualReviewSegmentMapping'),
(305,'editor','pmlight','frontend','readAnonymyzedUsers'),
(322,'editor','pmlight','frontend','taskConfigOverwriteGrid'),
(304,'editor','pmlight','frontend','taskUserAssocFrontendController'),
(412,'editor','pmlight','frontend','unlockSegmentBatch'),
(414,'editor','pmlight','frontend','unlockSegmentOperation'),
(306,'editor','pmlight','initial_tasktype','default'),
(307,'editor','pmlight','initial_tasktype','project'),
(308,'editor','pmlight','initial_tasktype','projectTask'),
(309,'editor','pmlight','setaclrole','editor'),
(310,'editor','pmlight','setaclrole','instantTranslate'),
(311,'editor','pmlight','setaclrole','termCustomerSearch'),
(312,'editor','pmlight','setaclrole','termProposer'),
(386,'editor','systemadmin','auto_set_role','admin'),
(387,'editor','systemadmin','auto_set_role','editor'),
(388,'editor','systemadmin','auto_set_role','pm'),
(391,'editor','systemadmin','backend','systemLogSummary'),
(445,'editor','systemadmin','editor_index','systemstatus'),
(389,'editor','systemadmin','editor_log','all'),
(390,'editor','systemadmin','frontend','systemLog'),
(446,'editor','systemadmin','frontend','systemStatus'),
(392,'editor','systemadmin','setaclrole','admin'),
(393,'editor','systemadmin','setaclrole','api'),
(394,'editor','systemadmin','setaclrole','basic'),
(395,'editor','systemadmin','setaclrole','editor'),
(396,'editor','systemadmin','setaclrole','instantTranslate'),
(397,'editor','systemadmin','setaclrole','noRights'),
(398,'editor','systemadmin','setaclrole','pm'),
(399,'editor','systemadmin','setaclrole','pmlight'),
(400,'editor','systemadmin','setaclrole','systemadmin'),
(401,'editor','systemadmin','setaclrole','termCustomerSearch'),
(402,'editor','systemadmin','setaclrole','termFinalizer'),
(403,'editor','systemadmin','setaclrole','termPM'),
(404,'editor','systemadmin','setaclrole','termPM_allClients'),
(405,'editor','systemadmin','setaclrole','termProposer'),
(406,'editor','systemadmin','setaclrole','termReviewer'),
(131,'editor','termCustomerSearch','editor_apps','all'),
(353,'editor','termCustomerSearch','editor_index','pluginpublic'),
(324,'editor','termCustomerSearch','editor_plugins_termportal_data','all'),
(150,'editor','termCustomerSearch','editor_term','get'),
(154,'editor','termCustomerSearch','editor_termattribute','get'),
(94,'editor','termCustomerSearch','editor_termcollection','all'),
(106,'editor','termCustomerSearch','editor_termportal','all'),
(327,'editor','termFinalizer','auto_set_role','termCustomerSearch'),
(328,'editor','termFinalizer','editor_attribute','put'),
(332,'editor','termPM','auto_set_role','termCustomerSearch'),
(334,'editor','termPM','auto_set_role','termFinalizer'),
(333,'editor','termPM','auto_set_role','termProposer'),
(335,'editor','termPM','auto_set_role','termReviewer'),
(337,'editor','termPM','editor_attribute','delete'),
(379,'editor','termPM','editor_attribute','deleteAny'),
(363,'editor','termPM','editor_languageresourcetaskassoc','post'),
(362,'editor','termPM','editor_task','import'),
(364,'editor','termPM','editor_task','post'),
(365,'editor','termPM','editor_task','pretranslationOperation'),
(336,'editor','termPM','editor_term','delete'),
(382,'editor','termPM','editor_term','deleteAny'),
(367,'editor','termPM','initial_tasktype','project'),
(366,'editor','termPM','initial_tasktype','termtranslation'),
(340,'editor','termPM_allClients','auto_set_role','termCustomerSearch'),
(341,'editor','termPM_allClients','auto_set_role','termFinalizer'),
(338,'editor','termPM_allClients','auto_set_role','termPM'),
(339,'editor','termPM_allClients','auto_set_role','termProposer'),
(342,'editor','termPM_allClients','auto_set_role','termReviewer'),
(375,'editor','termPM_allClients','editor_attribute','delete'),
(380,'editor','termPM_allClients','editor_attribute','deleteAny'),
(354,'editor','termPM_allClients','editor_attributedatatype','put'),
(369,'editor','termPM_allClients','editor_languageresourcetaskassoc','post'),
(368,'editor','termPM_allClients','editor_task','import'),
(370,'editor','termPM_allClients','editor_task','post'),
(371,'editor','termPM_allClients','editor_task','pretranslationOperation'),
(374,'editor','termPM_allClients','editor_term','delete'),
(383,'editor','termPM_allClients','editor_term','deleteAny'),
(373,'editor','termPM_allClients','initial_tasktype','project'),
(372,'editor','termPM_allClients','initial_tasktype','termtranslation'),
(329,'editor','termProposer','auto_set_role','termCustomerSearch'),
(149,'editor','termProposer','editor_apps','all'),
(376,'editor','termProposer','editor_attribute','delete'),
(155,'editor','termProposer','editor_attribute','post'),
(331,'editor','termProposer','editor_attribute','put'),
(179,'editor','termProposer','editor_term','commentOperation'),
(377,'editor','termProposer','editor_term','delete'),
(151,'editor','termProposer','editor_term','post'),
(152,'editor','termProposer','editor_term','proposeOperation'),
(330,'editor','termProposer','editor_term','put'),
(153,'editor','termProposer','editor_term','removeproposalOperation'),
(156,'editor','termProposer','editor_termattribute','proposeOperation'),
(157,'editor','termProposer','editor_termattribute','removeproposalOperation'),
(178,'editor','termProposer','editor_termcollection','all'),
(148,'editor','termProposer','editor_termportal','all'),
(147,'editor','termProposer','termCustomerSearch','all'),
(325,'editor','termReviewer','auto_set_role','termCustomerSearch'),
(326,'editor','termReviewer','editor_attribute','put'),
(419,'editor','instantTranslate','editor_languageresourcetaskpivotassoc','post'),
(426,'editor','instantTranslate','editor_languageresourcetaskpivotassoc','pretranslationOperation'),
(420,'editor','pm','editor_languageresourcetaskpivotassoc','all'),
(427,'editor','pm','editor_languageresourcetaskpivotassoc','pretranslationOperation'),
(421,'editor','pm','frontend','languageResourcesTaskPivotAssoc'),
(422,'editor','pmlight','editor_languageresourcetaskpivotassoc','all'),
(428,'editor','pmlight','editor_languageresourcetaskpivotassoc','pretranslationOperation'),
(423,'editor','pmlight','frontend','languageResourcesTaskPivotAssoc'),
(424,'editor','termPM','editor_languageresourcetaskpivotassoc','post'),
(429,'editor','termPM','editor_languageresourcetaskpivotassoc','pretranslationOperation'),
(425,'editor','termPM_allClients','editor_languageresourcetaskpivotassoc','post'),
(430,'editor','termPM_allClients','editor_languageresourcetaskpivotassoc','pretranslationOperation');

--
-- Table structure for table `Zf_configuration`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_configuration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'corresponds to the old INI key',
  `confirmed` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'used for new values, 0 not confirmed by user, 1 confirmed',
  `module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the PHP module this config value was defined for',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other' COMMENT 'field to categorize the config values',
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the config value',
  `default` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the system default value for this config',
  `defaults` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'a comma separated list of default values, only one of this value is possible to be set by the GUI',
  `type` enum('string','integer','boolean','list','map','absolutepath','float') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'the type of the config value is needed also for GUI',
  `typeClass` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'contains the php class for extended type validation, use null to use default type handling',
  `description` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'contains a human readable description for what this config is for',
  `level` int(11) DEFAULT 1 COMMENT 'Configuration level',
  `guiName` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Human readable configuration name for easy understanding. This value is for frontend display.',
  `guiGroup` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Zf configuration group.',
  `comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Additional info about this config.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=424 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_configuration`
--

INSERT INTO `Zf_configuration` VALUES
(1,'resources.db.matViewLifetime',1,'editor','system','14','14','','integer',NULL,'define the default lifetime in days after which unused materialized views are deleted',1,'','',''),
(2,'runtimeOptions.alike.defaultBehaviour',1,'editor','system','individual','individual','never,always,individual','string',NULL,'Default behaviour, for the repetition editor (auto-propgate). Possible values: \'never\', \'always\', \'individual\' – they refer to when automatic replacements are made. Individual means, for every segment with repetitions a window will pop-up and asked the user what to do.',32,'Autopropagate / Repetition editor default behaviour','Editor: Miscellaneous options',''),
(3,'runtimeOptions.companyName',1,'app','company','MittagQI - Quality Informatics','MittagQI - Quality Informatics','','string',NULL,'Name of the company, that uses translate5. Is shown in E-Mails and other places',2,'Company name','System setup: General',''),
(4,'runtimeOptions.contactData.emergencyContactDepartment',1,'app','company','IT-Abteilung','IT-Abteilung','','string',NULL,'Department that is responsible for translate5 in the company, that uses translate5.',2,'Responsible department','System setup: General',''),
(5,'runtimeOptions.contactData.emergencyTelephoneNumber',1,'app','company','07473 / 220202','07473 / 220202','','string',NULL,'Telephone number, where you can contact the department responsible for translate5.',2,'Phone to call','System setup: General',''),
(6,'runtimeOptions.cronIP',1,'editor','cron','127.0.0.1','127.0.0.1','','string',NULL,'It is recommended to call translate5s cron job mechanism every 15 min. These calls are only allowed to originate from the IP address configured here.',2,'IP address allowed for cron calls','System setup: General',''),
(7,'runtimeOptions.defines.ALLOWED_FILENAME_CHARS',1,'app','base','\'[^.A-Za-z0-9_!@#$%^&()+={}\\[\\]\\\',~`-]\'','\'[^.A-Za-z0-9_!@#$%^&()+={}\\[\\]\\\',~`-]\'','','string',NULL,'Regulärer Ausdruck, der innerhalb einer pcre-Zeichenklasse gültig sein muss -  bei Dateiuploads werden alle anderen Zeichen aus dem Dateinamen rausgeworfen',1,'','',''),
(8,'runtimeOptions.defines.DATE_REGEX',1,'app','base','\"^\\d\\d\\d\\d-[01]\\d-[0-3]\\d [0-2]\\d:[0-6]\\d:[0-6]\\d$\"','\"^\\d\\d\\d\\d-[01]\\d-[0-3]\\d [0-2]\\d:[0-6]\\d:[0-6]\\d$\"','','string',NULL,'',1,'','',''),
(9,'runtimeOptions.defines.EMAIL_REGEX',1,'app','base','\"^[A-Za-z0-9._%+-]+@(?:[A-Za-z0-9-]+\\.)+[A-Za-z]{2,19}$\"','\"^[A-Za-z0-9._%+-]+@(?:[A-Za-z0-9-]+\\.)+[A-Za-z]{2,19}$\"','','string',NULL,'',1,'','',''),
(10,'runtimeOptions.defines.GUID_REGEX',1,'app','base','\"^(\\{){0,1}[0-9a-fA-F]{8}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{12}(\\}){0,1}$\"','\"^(\\{){0,1}[0-9a-fA-F]{8}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{12}(\\}){0,1}$\"','','string',NULL,'',1,'','',''),
(11,'runtimeOptions.defines.GUID_START_UNDERSCORE_REGEX',1,'app','base','\"^_[0-9a-fA-F]{8}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{12}$\"','\"^_[0-9a-fA-F]{8}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{4}\\-[0-9a-fA-F]{12}$\"','','string',NULL,'',1,'','',''),
(12,'runtimeOptions.defines.ISO639_1_REGEX',1,'app','base','\"^([A-Za-z-]{2,3})|([A-Za-z]{2,3}-[A-Za-z]{2})$\"','\"^([A-Za-z-]{2,3})|([A-Za-z]{2,3}-[A-Za-z]{2})$\"','','string',NULL,'',1,'','',''),
(13,'runtimeOptions.dir.locales',1,'app','system','../data/locales','../data/locales','','absolutepath',NULL,'',1,'','',''),
(14,'runtimeOptions.dir.logs',1,'app','system','../data/cache','../data/cache','','absolutepath',NULL,'',1,'','',''),
(15,'runtimeOptions.dir.tagImagesBasePath',1,'editor','imagetag','modules/editor/images/imageTags','modules/editor/images/imageTags','','string',NULL,'Image Tags und Image Tags JSON Verzeichnisse: die Pfadangabe ist vom public-Verzeichnis aus zu sehen ohne beginnenden Slash (http-Pfad). Trennzeichen ist immer \'/\' (Slash).',1,'','',''),
(17,'runtimeOptions.dir.taskData',1,'editor','system','../data/editorImportedTasks','../data/editorImportedTasks','','absolutepath',NULL,'Pfad zu einem vom WebServer beschreibbaren, über htdocs nicht erreichbaren Verzeichnis, in diesem werden die kompletten persistenten (und temporären) Daten zu einem Task gespeichert',1,'','',''),
(18,'runtimeOptions.dir.tmp',1,'app','system','../data/tmp','../data/tmp','','absolutepath',NULL,'',1,'','',''),
(19,'runtimeOptions.disableErrorMails.all',1,'app','logging','0','0','','boolean',NULL,'',1,'','','deprecated'),
(20,'runtimeOptions.disableErrorMails.default',1,'app','logging','0','0','','boolean',NULL,'deaktiviert ausschließlich den Versand der Error-Mails ohne dump',1,'','','deprecated'),
(21,'runtimeOptions.disableErrorMails.fulldump',1,'app','logging','0','0','','boolean',NULL,'',1,'','','deprecated'),
(22,'runtimeOptions.disableErrorMails.minidump',1,'app','logging','0','0','','boolean',NULL,'deaktiviert ausschließlich den Versand der Error-Mails mit minidump',1,'','','deprecated'),
(23,'runtimeOptions.disableErrorMails.notFound',1,'app','logging','1','1','','boolean',NULL,'deaktiviert ausschließlich den Versand der Error-Mails ohne dump',1,'','','deprecated'),
(24,'runtimeOptions.editor.branding',1,'editor','layout','','','','string',NULL,'see editor skinning documentation',1,'','',''),
(25,'runtimeOptions.editor.editorViewPort',1,'editor','system','Editor.view.ViewPortEditor','Editor.view.ViewPortEditor','Editor.view.ViewPortEditor,Editor.view.ViewPortSingle','string',NULL,'the editor viewport is changeable, default is Editor.view.ViewPort, also available: Editor.view.ViewPortSingle',1,'','',''),
(26,'runtimeOptions.editor.enable100pEditWarning',1,'editor','system','1','1','','boolean',NULL,'If set to active, a warning will be shown, if the user edits a 100% match',16,'100% Matches: Warn, if edited','Editor: QA',''),
(27,'runtimeOptions.autoQA.enableMqmTags',1,'editor','mqm','1','1','','boolean',NULL,'If activated (default), the Manual QA (inside Segment) can be used',8,'Manual QA (inside segment)','Editor: QA',''),
(29,'runtimeOptions.editor.export.wordBreakUpRegex',1,'editor','export','\"([^\\w-])\"u','\"([^\\w-])\"u','','string',NULL,'regex which defines non-word-characters; must include brackets () for the return of the delimiters of preg_split by PREG_SPLIT_DELIM_CAPTURE; define including delimiters and modificators',1,'','',''),
(30,'runtimeOptions.editor.notification.saveXmlToFile',1,'editor','workflow','1','1','','boolean',NULL,'defines if the generated xml should be additionaly stored in the task directory',1,'','',''),
(31,'runtimeOptions.editor.qmFlagXmlFileDir',1,'editor','mqm','modules/editor','modules/editor','','string',NULL,'path beneath APPLICATION_RUNDIR to the directory inside which the standard qmFlagXmlFile resides (must be relative from APPLICATION_RUNDIR without trailing slash)',1,'','',''),
(32,'runtimeOptions.editor.qmFlagXmlFileName',1,'editor','mqm','QM_Subsegment_Issues.xml','QM_Subsegment_Issues.xml','','string',NULL,'path to the XML Definition of QM Issues. Used on import.',1,'','',''),
(33,'runtimeOptions.editor.qmSeverity',1,'editor','mqm','{\"critical\": \"Critical\",\"major\": \"Major\",\"minor\": \"Minor\"}','{\"critical\": \"Critical\",\"major\": \"Major\",\"minor\": \"Minor\"}','','map',NULL,'Severity levels for the MQM quality assurance. The MQM issue types can be overwritten in the import zip file (please see https://confluence.translate5.net/display/BUS/ZIP+import+package+format ). Please contact translate5s developers, if this should be available as a GUI configuration option.',1,'MQM severity levels','Editor: QA',''),
(35,'runtimeOptions.errorCollect',1,'app','logging','0','0','','boolean',NULL,'Wert mit 1 aktiviert grundsätzlich das errorCollecting im Errorhandler. D. h. Fehler werden nicht mehr vom ErrorController, sondern vom ErrorcollectController behandelt und im Fehlerfall wird nicht sofort eine Exception geworfen, sondern die Fehlerausgabe erfolgt erst für alle Fehler gesammelt am Ende jedes Controller-Dispatches. Fehlermails und Logging analog zum normalen ErrorController. Wert 0 ist die empfohlene Standardeinstellung, da bei sauberer Programmierung schon ein fehlender Array-Index (also ein php-notice) zu unerwarteten Folgeerscheinungen führt und daher nicht kalkulierbare Nachwirkungen auf Benutzer und Datenbank hat. Wert kann über die Zend_Registry an beliebiger Stelle im Prozess per Zend_Registry aktiviert werden. Damit diese Einstellung greifen kann, muss das Resource-Plugin ZfExtended_Controllers_Plugins_ErrorCollect in der application.ini aktiviert sein',1,'','','deprecated'),
(36,'runtimeOptions.extJs.basepath.407',1,'app','system','/ext-4.0.7','/ext-4.0.7','','string',NULL,'Ext JS Base Verzeichnis',1,'','','deprecated'),
(37,'runtimeOptions.extJs.theme',1,'editor','layout','triton','triton','default,aria,triton','string',NULL,'The \"default\" equals to the theme configured in runtimeOptions.extJs.defaultTheme config. If this config is empty ExtJs \"Triton\" theme will be used. The dark theme equals the ExtJs \"Aria\" theme. More themes can be used if configured on db level. Please contact translate5 support, if you need this.',32,'Extjs theme to be used for translate5','System setup: General',''),
(38,'runtimeOptions.fileSystemEncoding',1,'app','system','UTF-8','UTF-8','','string',NULL,'encoding von Datei- und Verzeichnisnamen im Filesystem (muss von iconv unterstützt werden)',1,'','',''),
(39,'runtimeOptions.forkNoRegenerateId',1,'app','base','C1D11C25-45D2-11D0-B0E2-444553540000','C1D11C25-45D2-11D0-B0E2-444553540000','','string',NULL,'ID die einem Fork übergeben wird und verhindert, dass der Fork Zend_Session::regenerateId aufruft. Falls dieser Quellcode öffentlich wird: Diesen String bei jeder Installation individuell definieren, um Hacking vorzubeugen (beliebiger String gemäß [A-Za-z0-9])',1,'','',''),
(40,'runtimeOptions.headerOptions.height',1,'editor','layout','0','0','','integer',NULL,'Nur mit ViewPortSingle: Definiert die Headerhöhe in Pixeln.',1,'','',''),
(41,'runtimeOptions.headerOptions.pathToHeaderFile',1,'editor','layout','','','','string',NULL,'Nur mit ViewPortSingle: Diese Datei wird als Header eingebunden. Die Pfadangabe ist relativ zum globalen Public Verzeichnis.',1,'','',''),
(42,'runtimeOptions.imageTag.backColor.B',1,'editor','imagetag','163','163','','integer',NULL,'Blau-Wert der Hintergrundfarbe',1,'','',''),
(43,'runtimeOptions.imageTag.backColor.G',1,'editor','imagetag','255','255','','integer',NULL,'Grün-Wert der Hintergrundfarbe',1,'','',''),
(44,'runtimeOptions.imageTag.backColor.R',1,'editor','imagetag','57','57','','integer',NULL,'Rot-Wert der Hintergrundfarbe',1,'','',''),
(45,'runtimeOptions.imageTag.fontColor.B',1,'editor','imagetag','0','0','','integer',NULL,'Blau-Wert der Schriftfarbe',1,'','',''),
(46,'runtimeOptions.imageTag.fontColor.G',1,'editor','imagetag','0','0','','integer',NULL,'Grün-Wert der Schriftfarbe',1,'','',''),
(47,'runtimeOptions.imageTag.fontColor.R',1,'editor','imagetag','0','0','','integer',NULL,'Rot-Wert der Schriftfarbe',1,'','',''),
(48,'runtimeOptions.imageTag.fontFilePath',1,'editor','imagetag','modules/editor/ThirdParty/Open_Sans/OpenSans-Regular.ttf','modules/editor/ThirdParty/Open_Sans/OpenSans-Regular.ttf','','absolutepath',NULL,'must be true type font - relative path to application folder',1,'','',''),
(49,'runtimeOptions.imageTag.fontSize',1,'editor','imagetag','9','9','','integer',NULL,'',1,'','',''),
(50,'runtimeOptions.imageTag.height',1,'editor','imagetag','14','14','','integer',NULL,'',1,'','',''),
(51,'runtimeOptions.imageTag.horizStart',1,'editor','imagetag','0','0','','integer',NULL,'horizontalrer Startpunkt der Schrift von der linken unteren Ecke aus',1,'','',''),
(52,'runtimeOptions.imageTag.paddingRight',1,'editor','imagetag','1','1','','integer',NULL,'',1,'','',''),
(53,'runtimeOptions.imageTag.vertStart',1,'editor','imagetag','11','11','','integer',NULL,'vertikaler Startpunkt der Schrift von der linken unteren Ecke aus',1,'','',''),
(54,'runtimeOptions.imageTags.qmSubSegment.backColor.B',1,'editor','qmimagetag','21','21','','integer',NULL,'Blau-Wert der Hintergrundfarbe',1,'','',''),
(55,'runtimeOptions.imageTags.qmSubSegment.backColor.G',1,'editor','qmimagetag','130','130','','integer',NULL,'Grün-Wert der Hintergrundfarbe',1,'','',''),
(56,'runtimeOptions.imageTags.qmSubSegment.backColor.R',1,'editor','qmimagetag','255','255','','integer',NULL,'Rot-Wert der Hintergrundfarbe',1,'','',''),
(57,'runtimeOptions.imageTags.qmSubSegment.horizStart',1,'editor','qmimagetag','2','2','','integer',NULL,'horizontalrer Startpunkt der Schrift von der linken unteren Ecke aus',1,'','',''),
(58,'runtimeOptions.imageTags.qmSubSegment.paddingRight',1,'editor','qmimagetag','3','3','','integer',NULL,'',1,'','',''),
(60,'runtimeOptions.import.csv.delimiter',1,'editor','csv',',',',','','string',NULL,'The delimiter translate5 will expect to parse CSV files. If this is not present in the CSV, a CSV import will fail (Okapi bconf is not used for CSV iimport).',8,'CSV import: delimiter','File formats',''),
(61,'runtimeOptions.import.csv.enclosure',1,'editor','csv','\"','\"','','string',NULL,'The enclosure translate5 will expect to parse CSV files. If this is not present in the CSV, a CSV import will fail (Okapi bconf is not used for CSV iimport).',8,'CSV import: ecnclosure','File formats',''),
(62,'runtimeOptions.import.csv.fields.mid',1,'editor','csv','id','id','','string',NULL,'The name of the ID column for a CSV import. If this does not exist in the CSV, the CSV import will fail. All columns with column header different than the source text column and the ID column will be treated as target text columns (potentially many).',8,'CSV import: Name of ID column','File formats',''),
(63,'runtimeOptions.import.csv.fields.source',1,'editor','csv','source','source','','string',NULL,'The name of the source text column for a CSV import. If this does not exist in the CSV, the CSV import will fail. All columns with column header different than the source text column and the ID column will be treated as target text columns (potentially many).',8,'CSV import: Name of source text column','File formats',''),
(64,'runtimeOptions.import.enableSourceEditing',1,'editor','import','0','0','','boolean',NULL,'If activated, the import option that decides, if the editing of the source text in the editor is possible is by default active. Else it is disabled by default (but can be enabled in the import settings). Please note: The export of the changed source text is only possible for CSV so far. ',8,'Source editing possible','Editor: Miscellaneous options',''),
(65,'runtimeOptions.import.keepFilesOnError',1,'editor','import','1','1','','boolean',NULL,'keep also the task files after an exception while importing, if false the files will be deleted',1,'','',''),
(66,'runtimeOptions.import.languageType',1,'editor','import','rfc5646','rfc5646','rfc5646,unix,lcid','string',NULL,'Beim Import können die zu importierenden Sprachen in verschiedenen Formaten mitgeteilt werden',1,'','',''),
(67,'runtimeOptions.import.proofReadDirectory',1,'editor','import','proofRead','proofRead','','string',NULL,'',1,'','',''),
(68,'runtimeOptions.import.referenceDirectory',1,'editor','import','referenceFiles','referenceFiles','','string',NULL,'Verzeichnisnamen unter welchem innerhalb des Import Ordners die Referenz Dateien gesucht werden soll',1,'','',''),
(69,'runtimeOptions.import.relaisDirectory',1,'editor','import','relais','relais','','string',NULL,'Relaissprachen Steuerung: Befinden sich im ImportRoot zwei Verzeichnisse mit den folgenden Namen, so wird zu dem Projekt eine Relaissprache aus den Daten im relaisDirectory importiert. Die Inhalte in relais und proofRead müssen strukturell identisch sein',1,'','',''),
(70,'runtimeOptions.import.reportOnNoRelaisFile',1,'editor','import','1','1','','boolean',NULL,'gibt an, ob bei fehlenden Relaisinformationen eine Fehlermeldung ins Log geschrieben werden soll',1,'','',''),
(72,'runtimeOptions.loginUrl',1,'editor','editor','/login/logout','/login/logout','','string',NULL,'http-orientierte URL auf die umgelenkt wird, wenn REST ein 401 Unauthorized wirft',1,'','',''),
(73,'runtimeOptions.mail.generalBcc',1,'app','email','[]','[]','','list',NULL,'List of e-mail addresses, that will be set to BCC for ALL e-mails translate5 sends.',2,'BCC addresses for ALL mails','System setup: General',''),
(74,'runtimeOptions.messageBox.delayFactor',1,'editor','layout','1.0','1.0','','string',NULL,'Faktor um die Dauer der eingeblendeten Nachrichten zu beeinflussen (Dezimalzeichen = Punkt!)',1,'','',''),
(75,'runtimeOptions.publicAdditions.css',1,'editor','layout','[\"css/editorAdditions.css?v=1\"]','[\"css/editorAdditions.css?v=1\"]','','list',NULL,'CSS Dateien welche zusätzlich eingebunden werden sollen. Pfad relativ zum Web-Root der Anwendung. Per Default wird das CSS zur Anzeige des Translate5 Logos eingebunden.',1,'','',''),
(76,'runtimeOptions.segments.disabledFields',1,'editor','metadata','[\"editableColumn\"]','[\"editableColumn\"]','','list',NULL,'Column itemIds der Spalten die per Default ausgeblendet sein sollen. Die itemIds werden in der ui/segments/grid.js definiert, in der Regel Spaltenname + \'Column\'',1,'','','deprecated'),
(77,'runtimeOptions.segments.qualityFlags',1,'editor','metadata','{\"1\": \"Demo-QM-Fehler 1\", \"2\": \"Falsche Übersetzung\", \"3\": \"Terminologieproblem\", \"4\": \"Fließendes Problem\", \"5\": \"Inkonsistenz\"}','{\"1\": \"Demo-QM-Fehler 1\", \"2\": \"Falsche Übersetzung\", \"3\": \"Terminologieproblem\", \"4\": \"Fließendes Problem\", \"5\": \"Inkonsistenz\"}','','map',NULL,'Available options for the quality assurcance panel on the right side of the editor',1,'Quality assurance options','Editor: QA',''),
(78,'runtimeOptions.segments.showStatus',1,'editor','metadata','1','1','','boolean',NULL,'If set to active, the status panel on the right side of the editor is visible',16,'Status panel: Show','Editor: Miscellaneous options',''),
(79,'runtimeOptions.segments.stateFlags',1,'editor','metadata','{\"1\": \"Manueller Demo Status 1\", \"2\": \"Muss erneut überprüft werden\"}','{\"1\": \"Manueller Demo Status 1\", \"2\": \"Muss erneut überprüft werden\"}','','map',NULL,'Available options for the status, that can be set manually on the right side of the editor',1,'Manual status options','Editor: Miscellaneous options',''),
(80,'runtimeOptions.sendMailLocally',1,'app','logging','0','0','','boolean',NULL,'Legt fest, ob alle E-Mails lokal verschickt werden sollen, dann wird bei allen E-Mails alles ab dem @ bis zum Ende der Adresse beim, Versenden der Mail als Empfängeradresse weggelassen. Aus new@marcmittag.de wird also new',1,'','','deprecated'),
(81,'runtimeOptions.server.name',1,'app','system','translate5.local','www.translate5.net','','string',NULL,'Domainname under which de application is running',2,'Server name','System setup: General',''),
(82,'runtimeOptions.server.pathToIMAGES',1,'app','system','/images','/images','','string',NULL,'http-orientierter Pfad zum image-Verzeichnis',1,'','',''),
(83,'runtimeOptions.server.pathToJsDir',1,'app','system','/js','/js','','string',NULL,'http-orientierter Pfad zum js-Verzeichnis',1,'','',''),
(84,'runtimeOptions.server.protocol',1,'app','system','http://','http://','http://,https://','string',NULL,'Protocol of the application (http:// or https://',2,'Server protocol','System setup: General',''),
(85,'runtimeOptions.showErrorsInBrowser',1,'app','logging','0','0','','boolean',NULL,'Bei Wert 0 zeigt er für den Produktivbetrieb dem Anwender im Browser nur eine allgemeine Fehlermeldung und keinen Trace',1,'','','deprecated'),
(86,'runtimeOptions.singleUserRestriction',1,'app','login','0','0','','boolean',NULL,'If set to active, a user can only login with one browser at the same time. Else he could login with the same username in 2 different browsers at the same time.',1,'Only one login per user','',''),
(87,'runtimeOptions.tbx.defaultAdministrativeStatus',1,'editor','termtagger','admittedTerm-admn-sts','admitted','admitted','string',NULL,'Default value for the usage status, if in the imported file no usage status is defined for a term.',2,'Default usage status for terminology','TermPortal',''),
(88,'runtimeOptions.termTagger.debug',1,'editor','termtagger','0','0','','boolean',NULL,'Enables the TermTagger to be verbose',1,'','',''),
(90,'runtimeOptions.termTagger.fuzzy',1,'editor','termtagger','0','0','','boolean',NULL,'Enables the fuzzy mode',1,'','',''),
(91,'runtimeOptions.termTagger.fuzzyPercent',1,'editor','termtagger','70','70','','integer',NULL,'The fuzzy percentage as integer, from 0 to 100',1,'','',''),
(94,'runtimeOptions.termTagger.maxWordLengthSearch',1,'editor','termtagger','2','2','','integer',NULL,'max. word count for fuzzy search',1,'','',''),
(95,'runtimeOptions.termTagger.minFuzzyStartLength',1,'editor','termtagger','2','2','','integer',NULL,'min. number of chars at the beginning of a compared word in the text, which have to be identical to be matched in a fuzzy search',1,'','',''),
(96,'runtimeOptions.termTagger.minFuzzyStringLength',1,'editor','termtagger','5','5','','integer',NULL,'min. char count for words in the text compared in fuzzy search',1,'','',''),
(99,'runtimeOptions.termTagger.stemmed',1,'editor','termtagger','1','1','','boolean',NULL,'Enables the stemmer',1,'','',''),
(100,'runtimeOptions.termTagger.targetStringMatch',1,'editor','termtagger','[\"zh\", \"ja\", \"ko\", \"ko-KR\",\"zh-CN\",\"zh-HK\",\"zh-MO\",\"zh-SG\",\"zh-TW\",\"ja-JP\",\"th\",\"th-TH\"]','[\"zh\", \"ja\", \"ko\", \"ko-KR\",\"zh-CN\",\"zh-HK\",\"zh-MO\",\"zh-SG\",\"zh-TW\",\"ja-JP\",\"th\",\"th-TH\"]','','list',NULL,'For certain languages (East Asian ones) it makes no sense to use the stemmer for terminology checking. List here the rfc5646 codes of those languages, where the stemmer should not be used (use the language codes, that the languages have in translate5)',4,'Terminology check: Disable stemming for languages','Editor: QA',''),
(101,'runtimeOptions.translation.sourceCodeLocale',1,'app','system','de','de','','string',NULL,'should be the default-locale in translation-setup, if no target locale is set',1,'','',''),
(102,'runtimeOptions.translation.sourceLocale',1,'app','system','ha','ha','','string',NULL,'setze auf Hausa als eine Sprache, die wohl nicht als Oberflächensprache vorkommen wird. So kann auch das deutsche mittels xliff-Datei überschrieben werden und die in die Quelldateien einprogrammierten Werte müssen nicht geändert werden',1,'','',''),
(103,'runtimeOptions.workflow.default.anonymousColumns',1,'editor','workflow','0','0','','boolean',NULL,'If true the column labels are getting an anonymous column name.',1,'','',''),
(104,'runtimeOptions.workflow.default.visibility',1,'editor','workflow','show','show','show,hide,disable','string',NULL,'visiblity of non-editable targetcolumn(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns.',1,'','',''),
(105,'runtimeOptions.workflow.dummy.anonymousColumns',1,'editor','workflow','1','1','','boolean',NULL,'If true the column labels are getting an anonymous column name.',1,'','',''),
(106,'runtimeOptions.workflow.dummy.visibility',1,'editor','workflow','show','show','show,hide,disable','string',NULL,'visiblity of non-editable targetcolumn(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns.',1,'','',''),
(107,'runtimeOptions.workflow.ranking.anonymousColumns',1,'editor','workflow','1','1','','boolean',NULL,'If true the column labels are getting an anonymous column name.',1,'','',''),
(108,'runtimeOptions.workflow.ranking.visibility',1,'editor','workflow','disable','disable','show,hide,disable','string',NULL,'visiblity of non-editable targetcolumn(s): For \"show\" or \"hide\" the user can change the visibility of the columns in the usual way in the editor. If \"disable\" is selected, the user has no access at all to the non-editable columns.',1,'','',''),
(111,'runtimeOptions.server.pathToCSS',1,'app','system','/css/translate5.css?v=2','/css/translate5.css?v=2','','string',NULL,'Pfad zu einzelner Datei unterhalb des public-Verzeichnisses mit beginnendem Slash',1,'','',''),
(112,'runtimeOptions.content.mainMenu',1,'default','system','[]','[]','','list',NULL,'list of menu-entries for the logged out status of translate5. Other view scripts will lead to 404 in logged out status, even if they exist',1,'','',''),
(113,'runtimeOptions.segments.fieldMetaIdentifier',1,'editor','metadata','','','','string',NULL,'If the here defined string is found in the column name, the column is to be considered as a meta column. Also the string will be removed in frontend!',1,'','',''),
(114,'runtimeOptions.editor.columns.widthFactor',1,'editor','layout','8.6','8.6',NULL,'string',NULL,'factor which is used to calculate the column width from the max chars of a column, if it can be smaller than maxWidth',1,'','',''),
(115,'runtimeOptions.editor.columns.widthFactorHeader',1,'editor','layout','9','9',NULL,'string',NULL,'factor which is used to calculate the column width from the chars of a column-header, if the otherwise calculated width would be to small for the header',1,'','',''),
(116,'runtimeOptions.editor.columns.widthFactorErgonomic',1,'editor','layout','1.9','1.9',NULL,'string',NULL,'factor which is used to calculate the column width for the ergonomic mode from the width which is set for the editing mode, if it is smaller than the maxWidth ',1,'','',''),
(117,'runtimeOptions.editor.columns.maxWidth',1,'editor','layout','250','250',NULL,'integer',NULL,'default width for text contents columns in the editor in pixel. If column needs less space, this is adjusted automatically',1,'','',''),
(118,'runtimeOptions.plugins.active',1,'default','plugins','[\"editor_Plugins_Transit_Bootstrap\",\"editor_Plugins_TermTagger_Bootstrap\",\"editor_Plugins_ChangeLog_Init\",\"editor_Plugins_SpellCheck_Init\",\"editor_Plugins_MatchAnalysis_Init\",\"editor_Plugins_PangeaMt_Init\",\"editor_Plugins_NecTm_Init\",\"editor_Plugins_IpAuthentication_Init\",\"editor_Plugins_ModelFront_Init\"]','[\"editor_Plugins_ChangeLog_Init\",\"editor_Plugins_MatchAnalysis_Init\",\"editor_Plugins_TermTagger_Bootstrap\",\"editor_Plugins_Transit_Bootstrap\",\"editor_Plugins_SpellCheck_Init\",\"editor_Plugins_PangeaMt_Init\",\"editor_Plugins_IpAuthentication_Init\",\"editor_Plugins_ModelFront_Init\"]','','list',NULL,'This list contains the plugins which should be loaded for the application! Please see https://confluence.translate5.net/display/CON/Plug-in+overview for more information. If you activate a plug-in, every user should log out and log in again. Also some plug-ins like TrackChanges should not be deactivated, once they had been used.',1,'Active plug-ins','System setup: General',''),
(120,'runtimeOptions.editor.columns.widthOffsetEditable',1,'editor','layout','20','20','','integer',NULL,'The here configured value is used as padding in pixels to add to a column width, if the column is editable. It depends on the editable icon width.',1,'','',''),
(123,'runtimeOptions.plugins.transit.writeInfoField.enabled',1,'editor','import','0','0','NULL','boolean',NULL,'If checked, informations are added to the target-infofield of a segment- further configuration values decide, which information.',16,'Transit files: Add info to infofield ','File formats',''),
(124,'runtimeOptions.plugins.transit.writeInfoField.exportDate',1,'editor','import','0','0','NULL','boolean',NULL,'If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), the export date of the current file from translate5 to the file system is added to the target infofield on export',16,'Transit files: Add export date to infofield ','File formats',''),
(125,'runtimeOptions.plugins.transit.writeInfoField.manualStatus',1,'editor','import','0','0','NULL','boolean',NULL,'If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), the manual status is added to the target infofield on export',16,'Transit files: Write manual status to infofield ','File formats',''),
(126,'runtimeOptions.plugins.transit.writeInfoField.termsWithoutTranslation',1,'editor','import','0','0','NULL','boolean',NULL,'If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), and if this checkbox is checcked, terms in the source text without any translation in the target text are written to infofield.',16,'Transit files: Write source terms without translation to infofield','File formats',''),
(127,'runtimeOptions.plugins.LockSegmentsBasedOnConfig.metaToLock.notTranslated',1,'editor','plugins','0','0','NULL','boolean',NULL,'decides, if segments with metadata \"notTranslated\" will be locked from editing by this plugin.',1,'','',''),
(128,'runtimeOptions.plugins.LockSegmentsBasedOnConfig.metaToLock.transitLockedForRefMat',1,'editor','plugins','0','0','NULL','boolean',NULL,'decides, if segments with metadata \"transitLockedForRefMat\" will be locked from editing by this plugin.',1,'','',''),
(129,'runtimeOptions.plugins.LockSegmentsBasedOnConfig.metaToLock.noMissingTargetTermOnImport',1,'editor','plugins','0','0','NULL','boolean',NULL,'decides, if segments with metadata \"noMissingTargetTermOnImport\" will be locked from editing by this plugin.',1,'','',''),
(130,'runtimeOptions.autoQA.enableQm',1,'editor','metadata','1','1','','boolean',NULL,'If activated (default), the Manual QA (complete Segment) can be used',8,'Manual QA (complete segment)','Editor: QA',''),
(135,'runtimeOptions.termTagger.url.default',1,'plugin','termtagger','[\"http://localhost:9001\"]','[\"http://localhost:9001\"]','','list',NULL,'Comma separated list of available TermTagger-URLs. At least one available URL must be defined. Example: [\"http://localhost:9000\"]',1,'','','deprecated'),
(136,'runtimeOptions.termTagger.url.import',1,'plugin','termtagger','[\"http://localhost:9001\"]','[\"http://localhost:9001\"]','','list',NULL,'Refers to import processes. List one or multiple URLs, where termtagger-instances can be reached for checking and marked in the segments (to check, if the correct terminology is used). Translate5 does a load balancing, if more than one is configured.',2,'TermTagger for imports','Language resources',''),
(137,'runtimeOptions.termTagger.url.gui',1,'plugin','termtagger','[\"http://localhost:9001\"]','[\"http://localhost:9001\"]','','list',NULL,'Refers to segments saved in the GUI. List one or multiple URLs, where termtagger-instances can be reached for checking and marked in the segments (to check, if the correct terminology is used). Translate5 does a load balancing, if more than one is configured.',2,'TermTagger for GUI','Language resources',''),
(141,'runtimeOptions.sendMailDisabled',1,'app','email','1','0','','boolean',NULL,'This flag disables the application to send E-Mails.',1,'','',''),
(142,'runtimeOptions.maintenance.startDate',1,'app','system','','','','string',NULL,'The server maintenance start date and time in the format 2016-09-21 09:21',1,'','','deprecated'),
(143,'runtimeOptions.maintenance.timeToNotify',1,'app','system','30','30','','string',NULL,'This is set to a number of minutes. This defines, how many minutes before the runtimeOptions.maintenance.startDate the users who are currently logged in are notified',1,'','','deprecated'),
(144,'runtimeOptions.maintenance.timeToLoginLock',1,'app','system','5','5','','string',NULL,'This is set to a number of minutes. This defines, how many minutes before the runtimeOptions.maintenance.startDate the no new users are log in anymore.',1,'','','deprecated'),
(145,'runtimeOptions.defaultLanguage',1,'app','system','de','de','','string',NULL,'The default locale to be used when using users with invalid stored locale',1,'','',''),
(146,'runtimeOptions.translation.applicationLocale',1,'app','system','','','de,en','string',NULL,'Set here a default locale for the application GUI. If empty the default locale is derived from the users browser (which is the default).',2,'Application GUI locale','System setup: General',''),
(147,'runtimeOptions.worker.server',1,'app','worker','','','','string',NULL,'If empty defaults to \"runtimeOptions.server.protocol\" and \"runtimeOptions.server.name\". This config allows to access the local worker API through a different URL as the public one. Format of this configuration value: SCHEME://HOST:PORT',1,'','',''),
(148,'runtimeOptions.translation.fallbackLocale',1,'app','system','en','en','','string',NULL,'This is the fallback locale used for users in the application GUI. First is checked if the user has configured a locale, if not applicationLocale is checked. If that is empty the prefered browser languages are evaluated. If there is also no usable language this last fallbackLocale is used.',2,'Application GUI fallback locale','System setup: General',''),
(149,'runtimeOptions.plugins.transit.writeInfoField.exportDateValue',1,'editor','import','','','','string',NULL,'If the writing of information to the target-infofield is activated (this is determined by another configuraiton parameter), and if the export date is added to the target-infofield (also determined by another parameter) this text field becomes relevant. If it is empty the current date is used as export date. If it contains a valid date in the form YYYY-MM-DD this date is used.',16,'Transit files: Export date to write','File formats',''),
(150,'runtimeOptions.plugins.transit.exportOnlyEditable',1,'editor','export','1','1','NULL','boolean',NULL,'If checked, only the content of editable segments is written back to transit file om export. This does not influence the Info Field!',16,'Transit files: Write back only editable','File formats',''),
(151,'runtimeOptions.termTagger.switchOn.GUI',1,'editor','termtagger','1','1',NULL,'boolean',NULL,'Setting this to 0 switches off the termTagger for the GUI.',1,'','',''),
(152,'runtimeOptions.termTagger.switchOn.import',1,'editor','termtagger','1','1',NULL,'boolean',NULL,'Setting this to 0 switches off the termTagger for the import.',1,'','',''),
(153,'runtimeOptions.editor.notification.includeDiff',1,'editor','workflow','1','1','','boolean',NULL,'defines if the generated xml should also contain an alt trans field with a diff like content of the segment.',1,'','',''),
(156,'runtimeOptions.worker.editor_Models_Export_ExportedWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the export completed notification worker.',1,'','',''),
(157,'runtimeOptions.worker.editor_Models_Import_Worker_SetTaskToOpen.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the Import completed notification worker',1,'','',''),
(158,'runtimeOptions.worker.editor_Plugins_MtComparEval_Worker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the MtComparEval communication worker',1,'','',''),
(159,'runtimeOptions.worker.editor_Plugins_MtComparEval_CheckStateWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of MtComparEval check state worker',1,'','',''),
(160,'runtimeOptions.worker.editor_Plugins_LockSegmentsBasedOnConfig_Worker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the LockSegmentsBasedOnConfig plugin worker',1,'','',''),
(161,'runtimeOptions.worker.editor_Plugins_SegmentStatistics_Worker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running workers of the SegmentStatistics creation worker',1,'','',''),
(162,'runtimeOptions.worker.editor_Plugins_SegmentStatistics_WriteStatisticsWorker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running workers of the SegmentStatistics writer worker',1,'','',''),
(163,'runtimeOptions.worker.editor_Plugins_NoMissingTargetTerminology_Worker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the NoMissingTargetTerminology plugin worker',1,'','',''),
(164,'runtimeOptions.worker.editor_Plugins_TermTagger_Worker_TermTagger.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'How many parallel processes ',1,'','',''),
(165,'runtimeOptions.worker.editor_Plugins_TermTagger_Worker_TermTaggerImport.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the termTagger import worker',1,'','',''),
(166,'runtimeOptions.worker.ZfExtended_Worker_Callback.maxParallelWorkers',1,'app','worker','1','1','','integer',NULL,'Max parallel running workers of the generic callback worker',1,'','',''),
(167,'runtimeOptions.extJs.basepath.600',1,'app','system','/ext-6.0.0','/ext-6.0.0','','string',NULL,'Ext JS Base Verzeichnis',1,'','',''),
(168,'runtimeOptions.showSupportedBrowsersMsg',1,'editor','system','1','1','','boolean',NULL,'If set to active, translate5 shows a message if the used browser is not supported.',1,'','',''),
(169,'runtimeOptions.browserAdvice',1,'editor','system','1','1','','boolean',NULL,'If enabled, shows IE users an advice to use a more performant browser.',1,'','',''),
(170,'runtimeOptions.worker.editor_Models_Import_Worker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'How many parallel processes are allowed for file and segment parsing in the import. This value depends on what your hardware can serve. Please consult translate5s team, if you change this.',2,'Import: Max. parallel import processes','System setup: Load balancing',''),
(171,'runtimeOptions.worker.editor_Models_Export_Worker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'How many parallel processes are allowed for the export. This value depends on what your hardware can serve. Please consult translate5s team, if you change this.',2,'Export: Max. parallel import processes','System setup: Load balancing',''),
(172,'runtimeOptions.editor.export.exportComments',1,'editor','export','1','1','','boolean',NULL,'If set to active, the segment comments will be exported into the exported bilingual file (if this is supported by the implementation for that file type).',16,'Export comments to xliff','File formats',''),
(174,'runtimeOptions.editor.startViewMode',1,'editor','system','normal','normal','normal,details','string',NULL,'View mode which should be used on editor start up (if visual mode is used for the task, the default editor mode for visual is applied. It is defined in the section „Editor: Visual“).',16,'Default editor mode','Editor: UI layout & more',''),
(175,'runtimeOptions.import.relaisCompareMode',1,'editor','import','[\"IGNORE_TAGS\",\"NORMALIZE_ENTITIES\"]','[\"IGNORE_TAGS\",\"NORMALIZE_ENTITIES\"]','IGNORE_TAGS,NORMALIZE_ENTITIES','list',NULL,'Flag list how import source and relais source should be compared on relais import. IGNORE_TAGS: if given ignore all tags; NORMALIZE_ENTITIES: try to convert back all HTML entities into applicable characters for comparison.',1,'','',''),
(176,'runtimeOptions.debug.enableJsLogger',1,'editor','logging','1','1','','boolean',NULL,'If set to active, error-logging in the graphical user interface is activated. Errors will be send to translate5s developers via theRootCause.io. Users can decide on every single occurence of an error, if they want to report it.',2,'Error-logging in the browser','System setup: General',''),
(177,'runtimeOptions.editor.notification.enableSegmentXlfAttachment',1,'editor','workflow','0','0','','boolean',NULL,'If enabled, notification e-mails with segment data get also added the changed segments as XLIFF-attachment.',4,'Workflow notifications: Attach XLIFF with changes','Workflow',''),
(178,'runtimeOptions.import.xlf.preserveWhitespace',1,'editor','import','1','1','','boolean',NULL,'Defines how to import whitespace in XLF files and all native file formats (since they are converted to XLIFF by Okapi). If checcked, whitespace is preserved, if not whitespace is collapsed. See http://confluence.translate5.net/display/TFD/Xliff.',8,'XLIFF (and others): Preserve whitespace','File formats',''),
(179,'runtimeOptions.extJs.basepath.620',1,'app','system','/ext-6.2.0','/ext-6.2.0','','string',NULL,'Ext JS Base Verzeichnis',1,'','',''),
(180,'runtimeOptions.hashAuthentication',1,'editor','system','disabled','disabled','disabled,dynamic,static','string',NULL,'Enables and configures the ability to login via a hash value. In dynamic mode the hash changes after each usage, in static mode the hash remains the same (insecure!).',1,'','',''),
(183,'runtimeOptions.segments.userCanIgnoreTagValidation',1,'editor','workflow','0','0','','boolean',NULL,'If enabled the user can ignore tag validation errors. If disabled the user must correct the errors before saving the segment. Whitespace tags are configured with another config option.',16,'AutoQA: Allow tag errors','Editor: QA',''),
(185,'runtimeOptions.taskLifetimeDays',1,'app','system','100','100','','integer',NULL,'Attention: This is by default NOT active. To activate it, a workflow action needs to be configured. This is currently only possible on DB-Level. \nIf the task is not touched more than defined days, it will be automatically deleted. Older means, that it is not touched in the system for a longer time than this. Touching means at least opening the task or changing any kind of task assignments (users, language resources, etc.)',2,'Auto-Delete tasks older than','System setup: General',''),
(186,'runtimeOptions.editor.notification.xliff2Active',1,'editor','workflow','0','0','','boolean',NULL,'If set to active, if the generated XLIFF will be in XLIFF 2 format. Else XLIFF 1.2',4,'Workflow notifications: XLIFF version','Workflow',''),
(188,'runtimeOptions.plugins.SegmentStatistics.xlsTemplateExport',1,'editor','plugins','modules/editor/Plugins/SegmentStatistics/templates/export-template.xlsx','modules/editor/Plugins/SegmentStatistics/templates/export-template.xlsx','NULL','absolutepath',NULL,'Path to the XLSX export template. Path can be absolute or relative to application directory.',1,'','',''),
(189,'runtimeOptions.plugins.SegmentStatistics.xlsTemplateImport',1,'editor','plugins','modules/editor/Plugins/SegmentStatistics/templates/import-template.xlsx','modules/editor/Plugins/SegmentStatistics/templates/import-template.xlsx','NULL','absolutepath',NULL,'Path to the XLSX import template. Path can be absolute or relative to application directory.',1,'','',''),
(190,'runtimeOptions.plugins.SegmentStatistics.metaToIgnore.transitLockedForRefMat',1,'editor','plugins','0','0','NULL','boolean',NULL,'decides, if segments with metadata \"transitLockedForRefMat\" will be ignored by this plugin.',1,'','',''),
(191,'runtimeOptions.plugins.SegmentStatistics.disableFileWorksheetCount',1,'editor','plugins','15','15','','integer',NULL,'If there are more files in the task as configured here, the worksheets per file are disabled, only the summary worksheet is shown',1,'','',''),
(192,'runtimeOptions.plugins.SegmentStatistics.createFilteredOnly',1,'editor','plugins','0','0','','boolean',NULL,'If enabled only the filtered file set of statistics are created. If disabled all are generated.',1,'','',''),
(193,'runtimeOptions.worker.editor_Plugins_GlobalesePreTranslation_Worker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'This refers to the xliff file based pre-translation with Globalese – not the language resource-based one. How many parallel processes are allowed depends on Globalese capabilities.',2,'Globalese: Max. parallel Globalese pre-translation processes','System setup: Load balancing',''),
(194,'runtimeOptions.plugins.GlobalesePreTranslation.api.username',1,'editor','plugins','','','','string',NULL,'Username for Globalese API authentication',2,'Globalese: API username','System setup: Language resources',''),
(195,'runtimeOptions.plugins.GlobalesePreTranslation.api.url',1,'editor','plugins','https://translate5.globalese-mt.com/api/v2/','https://translate5.globalese-mt.com/api/v2/','','string',NULL,'Url used for Globalese api',2,'Globalese: API URL','System setup: Language resources',''),
(196,'runtimeOptions.plugins.GlobalesePreTranslation.api.apiKey',1,'editor','plugins','','','','string',NULL,'Api key for Globalese authentication',2,'Globalese: API key','System setup: Language resources',''),
(197,'runtimeOptions.LanguageResources.preloadedTranslationSegments',1,'editor','editor','1','1','','integer',NULL,'For how many segments starting from the current one in advance fuzzy matches are pre-loaded, so that they are immidiately available for the translator?',2,'Preload fuzzy matches in advance','Language resources',''),
(198,'runtimeOptions.LanguageResources.moses.server',1,'editor','editor','[]','[]','','list',NULL,'Zero, one or more URLs, where a Moses server is accessable as a language resource. Example: http://www.translate5.net:8124/RPC2',2,'Moses server URL(s)','Language resources',''),
(199,'runtimeOptions.LanguageResources.moses.matchrate',1,'editor','editor','70','70','','integer',NULL,'Default fuzzy match value for translations done by Moses. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.',2,'Moses default match rate','Language resources',''),
(200,'runtimeOptions.LanguageResources.opentm2.server',1,'editor','editor','[]','[]','','list',NULL,'Zero, one or more URLs, where an OpenTM2 server is accessable as a language resource. Example: http://opentm2.translate5.net:1984/otmmemoryservice/',2,'OpenTM2 server URL(s)','Language resources',''),
(201,'runtimeOptions.LanguageResources.lucylt.server',1,'editor','editor','[]','[\"https://ltxpress.lucysoftware.com/AutoTranslateRS/V1.3\"]','','list',NULL,'List of available Lucy LT servers',2,'Lucy LT server URL(s)','Language resources',''),
(202,'runtimeOptions.LanguageResources.lucylt.credentials',1,'editor','editor','[]','[\"translate5:DyJvc57=F2\"]','','list',NULL,'List of Lucy LT credentials to the Lucy LT Servers. Each server entry must have one credential entry. One credential entry looks like: \"username:password\"',2,'Lucy LT credentials','Language resources',''),
(203,'runtimeOptions.LanguageResources.lucylt.matchrate',1,'editor','editor','80','80','','integer',NULL,'Default fuzzy match value for translations done by Lucy LT. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.',2,'Lucy LT default match rate','Language resources',''),
(204,'runtimeOptions.LanguageResources.opentm2.tmprefix',1,'editor','editor','','','','string',NULL,'When using one OpenTM2 instance for multiple translate5 instances, a unique prefix for each translate5 instance must be configured to avoid filename collisions of the Memories on the OpenTM2 server.',2,'OpenTM2 instance pre-fix','Language resources',''),
(205,'runtimeOptions.worker.editor_Models_LanguageResources_Worker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the MatchResource ReImport worker',1,'','',''),
(206,'runtimeOptions.plugins.ArchiveTaskBeforeDelete.mysqlDumpPath',1,'editor','plugins','','','','string',NULL,'Optional path to the mysqldump executable, if empty the directory to the already configured mysql executable is reused.',1,'','',''),
(207,'runtimeOptions.worker.editor_Plugins_Okapi_Worker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'How many parallel processes are allowed for okapi file conversion within the translate5 instance. Please consult translate5s team, if you change this.',2,'Import: File filters: Max. parallel processes','System setup: Load balancing',''),
(209,'runtimeOptions.plugins.Okapi.tikal.executable',1,'editor','import','','','','string',NULL,'The absolute path to the tikal executable, no usable default can be given so is empty and must be configured by the user!',1,'','',''),
(210,'runtimeOptions.garbageCollector.invocation',1,'app','system','request','request','request,cron','string',NULL,'Defines how garbage collection should be triggerd: on each request in a specific time frame, cron via cronjob URL /editor/cron/periodical. Calling the cron URL once reconfigures the application to use cron based garbage collection.',1,'','',''),
(211,'runtimeOptions.worker.editor_Models_Import_Worker_FileTree.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running import filetree workers',1,'','',''),
(212,'runtimeOptions.worker.editor_Models_Import_Worker_ReferenceFileTree.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running import reference filetree workers',1,'','',''),
(213,'runtimeOptions.frontend.importTask.edit100PercentMatch',1,'editor','import','0','0','','boolean',NULL,'If set to active, the import option that decides, if 100% matches can be edited in the task is activated by default. Else it is disabled by default (but can be enabled in the import settings).',2,'100% matches: Edit them','Editor: QA',''),
(214,'runtimeOptions.import.initialTaskState',1,'editor','import','open','open','open,unconfirmed','string',NULL,'Defines the state a task should get directly after import. Possible states are: open, unconfirmed',8,'Initial task state','Workflow',''),
(215,'runtimeOptions.segments.userCanModifyWhitespaceTags',1,'editor','workflow','1','1','','boolean',NULL,'If enabled deleted / added whitespace tags are ignored in the tag validation. If disabled the user must have the same whitespace tags ins source and target.',16,'AutoQA: Allow whitespace tag errors','Editor: QA',''),
(216,'runtimeOptions.worker.editor_Models_Export_Xliff2Worker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running processes of the xliff2 export worker are allowed. Please consult translate5s team, if you change this.',2,'Export: Xliff2: Max. parallel processes','System setup: Load balancing',''),
(217,'runtimeOptions.termportal.defaultlanguages',1,'editor','system','[\"de-de\", \"en-gb\"]','[\"de-de\", \"en-gb\"]','','list',NULL,'Default languages in the termportal term search',2,'','TermPortal',''),
(218,'runtimeOptions.termportal.searchTermsCount',1,'editor','system','10','10','','integer',NULL,'The maximum count of the search results in the autocomplete',1,'','',''),
(219,'runtimeOptions.editor.notification.pmChanges',1,'editor','workflow','sameStepIncluded','sameStepIncluded','allIncluded,sameStepIncluded,notIncluded','string',NULL,'Defines how changes of PMs should be included into the notification mails: You can choose to include all PM changes, only the PM changes that happened in the workflow step, that just had been finished or if they should not be included at all.',16,'Workflow notifications: Include PM changes','Workflow',''),
(231,'runtimeOptions.plugins.SpellCheck.liveCheckOnEditing',1,'editor','plugins','0','0','','boolean',NULL,'If set to active, spell- grammar and style check is active while typing in the editor (based on languagetool)',8,'Spell-, grammar and style live-check on segment editing','Editor: QA',''),
(232,'runtimeOptions.plugins.SpellCheck.languagetool.url.gui',1,'editor','plugins','http://yourlanguagetooldomain:8081/v2','http://yourlanguagetooldomain/api/v2','','string',NULL,'Base-URL used for LanguagaTool - use the API-URL of your installed languageTool (without trailing slash!) - for example http://yourlanguagetooldomain/api/v2',2,'Spell-, grammar and style check service URL for GUI','System setup: General',''),
(233,'runtimeOptions.LanguageResources.fileExtension',1,'editor','system','','','','map',NULL,'Available file types by extension per engine type. The engine type is defined by source rcf5646,target rcf5646. ex: \"en-ge,en-us\"',1,'','',''),
(234,'runtimeOptions.LanguageResources.searchCharacterLimit',1,'editor','instanttranslate','[]','[]','','map',NULL,'Maximum character per language resource allowed for search. The configuration key is the language resource id, and the value is the character limit. Ex: {{\"1\": 100},{\"2\": 300}}',1,'','',''),
(235,'runtimeOptions.InstantTranslate.minMatchRateBorder',1,'editor','instanttranslate','','50','','integer',NULL,'Minimum matchrate allowed to be displayed in InstantTranslate result list for TM language resources',2,NULL,NULL,NULL),
(236,'runtimeOptions.LanguageResources.sdllanguagecloud.server',1,'editor','editor','[\"https://lc-api.sdl.com/\"]','','','list',NULL,'List of available SdlLanguageCloud servers',2,'SDL language cloud server URL(s)','Language resources',''),
(237,'runtimeOptions.LanguageResources.sdllanguagecloud.apiKey',1,'editor','editor','','','','string',NULL,'Api key used for authentication to the SDL language cloud api',2,'SDL language cloud API key','Language resources',''),
(238,'runtimeOptions.LanguageResources.google.projectId',1,'editor','system','','','','string',NULL,'Project id used by the google translate api.',2,'Google: project id','Language resources',''),
(239,'runtimeOptions.LanguageResources.google.apiKey',1,'editor','system','','','','string',NULL,'Api key to authenticate with google cloud translate api.',2,'Google: API key','Language resources',''),
(240,'runtimeOptions.LanguageResources.google.matchrate',1,'editor','editor','70','70','','integer',NULL,'Default fuzzy match value for translations done by Google. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.',2,'Google default match rate','Language resources',''),
(241,'runtimeOptions.LanguageResources.sdllanguagecloud.matchrate',1,'editor','editor','70','70','','integer',NULL,'Default fuzzy match value for translations done by SDL languagecloud. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.',2,'SDL language cloud default match rate','Language resources',''),
(243,'runtimeOptions.openid.sslCertificatePath',1,'default','openid','','','','string',NULL,'The name of a file(full system path) holding one or more certificates to verify the peer with.',1,'','',''),
(244,'runtimeOptions.maintenance.message',1,'app','system','','','','string',NULL,'An additional text message about the maintenance, shown in the GUI and in the maintenance announcement e-mail.',1,'','','deprecated'),
(245,'runtimeOptions.maintenance.announcementMail',1,'app','system','admin','admin','','string',NULL,'A comma separated list of system roles, which should receive the maintenance announcement e-mail. Single users can be added by adding user:LOGINNAME instead a group.',1,'','','deprecated'),
(246,'runtimeOptions.segments.userCanInsertWhitespaceTags',1,'editor','workflow','1','1','','boolean',NULL,'If enabled, the user can insert new whitespace while editing. Requires „AutoQA: Allow whitespace tag errors“ to be enabled, too.',16,'Allow adding new whitespace','Editor: QA',''),
(247,'runtimeOptions.lengthRestriction.pixelMapping',1,'editor','system','{\"8\":\"7\", \"9\":\"8\", \"10\":\"9\", \"11\":\"10\", \"12\":\"11\", \"13\":\"12\", \"14\":\"13\", \"15\":\"14\", \"16\":\"15\", \"17\":\"16\", \"18\":\"25\", \"19\":\"18\", \"20\":\"26\", \"24\":\"31\", \"54\":\"48\", \"70\": \"42\", \"96\": \"56\"}','{\"8\":\"7\", \"9\":\"8\", \"10\":\"9\", \"11\":\"10\", \"12\":\"11\", \"13\":\"12\", \"14\":\"13\", \"15\":\"14\", \"16\":\"15\", \"17\":\"16\", \"18\":\"17\", \"19\":\"18\", \"20\":\"19\"}','','map',NULL,'Define the default pixel-widths for font-sizes, independent from the used font or character. Key is the font size and value the pixel width assumed in the GUI check.',1,'Pixel length restriction: Default mappings','Editor: QA',''),
(248,'runtimeOptions.LanguageResources.opentm2.showMultiple100PercentMatches',1,'editor','editor','0','0','','boolean',NULL,'If this is set to disabled, for 100%-Matches that differ in the target, the target of the match with the highest match rate is shown. If the match rate is the same, the match with the newest change date is shown.If set to active, all 100%-Matches that differ in the target are shown.',16,'OpenTM2: Show all 100% matches','Language resources',''),
(249,'runtimeOptions.plugins.Okapi.import.fileconverters.attachOriginalFileAsReference',1,'editor','editor','0','1','','boolean',NULL,'Attach original files as reference files for all files, that are converted by Okapi (all except bilingual file formatts and CSV)',1,'Original files: Attach them','File formats',''),
(250,'runtimeOptions.errorCodesUrl',1,'app','system','https://github.com/translate5/translate5/blob/develop/docs/ERRORCODES.md#{0}','https://confluence.translate5.net/display/TAD/EventCodes#EventCodes-{0}','','string',NULL,'Url for information to the error codes. The placeholder \"{0}\" will be replaced by the error code.',1,'','',''),
(252,'runtimeOptions.logoutOnWindowClose',1,'app','system','1','1','','boolean',NULL,'If enabled the session of the user is tried to be destroyed when the application window is closed.',1,'','',''),
(253,'runtimeOptions.worker.editor_Services_ImportWorker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running processes of the import of language resource data (TMX or TBX, etc) are allowed. Please consult translate5s team, if you change this.',2,'Language resource import: Max. parallel processes','Language resources',''),
(254,'runtimeOptions.frontend.tasklist.pmMailTo',1,'editor','editor','0','1','','boolean',NULL,'If this is active, the PM name in the task overview PM column will be linked with the mail address of the PM.',2,'PM mail address in task overview','Project and task overview',''),
(255,'runtimeOptions.editor.LanguageResources.disableIfOnlyTermCollection',1,'editor','editor','1','1','','boolean',NULL,'If set to active and only a TermCollection and no MT or TM language resource is assigned to the task, the fuzzy match panel will not be shown in translate5s editor.',16,'Only TermCollections assigned: Hide fuzzy match panel','Language resources',''),
(256,'runtimeOptions.LanguageResources.microsoft.apiUrl',1,'editor','system','','https://api.cognitive.microsofttranslator.com','','string',NULL,'Microsoft translator language resource api url. To be able to use microsoft translator, you should create an microsoft azure account. Create and setup and microsoft azureaccount in the following link: https://azure.microsoft.com/en-us/services/cognitive-services/translator-text-api/',2,'Microsoft translator API URL','Language resources',''),
(257,'runtimeOptions.LanguageResources.microsoft.apiKey',1,'editor','system','','','','string',NULL,'Microsoft translator language resource api key. After completing the account registration and resource configuration, get the API key from the azure portal.',2,'Microsoft translator API key','Language resources',''),
(258,'runtimeOptions.LanguageResources.microsoft.matchrate',1,'editor','editor','70','70','','integer',NULL,'Default fuzzy match value for translations done by MicroSoft translator. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.',2,'Microsoft translator default match rate','Language resources',''),
(259,'runtimeOptions.customers.openid.showOpenIdDefaultCustomerData',1,'editor','editor','0','0','','boolean',NULL,'If set to active, the OpenID Connect configuration data is also shown for the default customer.',2,'Show OpenID configuration for default customer','System setup: Authentication',''),
(260,'runtimeOptions.customers.anonymizeUsers',1,'editor','metadata','1','0','','boolean',NULL,'Are user names anonymized in the workflow (for other users of the workflow)?',8,'Anonymize users','Workflow',''),
(261,'runtimeOptions.worker.editor_Models_Excel_Worker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running processes of the Excel task export and reimport are allowed. Please consult translate5s team, if you change this.',2,'Export/Import: Excel: Max. parallel processes','System setup: Load balancing',''),
(262,'runtimeOptions.tbx.defaultTermAttributeStatus',1,'editor','import','finalized','finalized','finalized,unprocessed','string',NULL,'Default term and term entry attribute status for newly imported term attributes.',2,'Terminology import: Default term attributes process status','Language resources',''),
(263,'runtimeOptions.import.xlf.ignoreFramingTags',1,'editor','import','all','all','none,paired,all','string',NULL,'If enabled framing tags (tag pairs only or all tags that surround the complete segment) are ignored on import. Does work for native file formats and standard xliff. Does not work for sdlxliff. See http://confluence.translate5.net/display/TFD/Xliff.',8,'Import: Ignore framing tag pairs','Editor: QA',''),
(264,'runtimeOptions.import.sdlxliff.importComments',1,'editor','import','0','0','','boolean',NULL,'Defines if SDLXLIFF comments should be imported or they should produce an error on import. See https://confluence.translate5.net/display/TFD/SDLXLIFF.',8,'SDLXLIFF comments: Import them','File formats',''),
(265,'runtimeOptions.import.sdlxliff.applyChangeMarks',1,'editor','import','1','1','','boolean',NULL,'Defines if SDLXLIFF change marks should be applied to and removed from the content, or if they should produce an error on import. See http://confluence.translate5.net/display/TFD/SDLXLIFF.',8,'SDLXLIFF track changes: Apply on import','File formats',''),
(266,'runtimeOptions.termportal.newTermAllLanguagesAvailable',1,'editor','termportal','1','1','','boolean',NULL,'If activated, when the user creates a new term in the TermPortal, he is able to select the language of the term from all languages available in translate5. If deactivated, he can only choose from those languages, that exist in the language resources that are available for him at the moment.',2,'All translate5 languages available for creating term?','TermPortal',''),
(267,'runtimeOptions.termportal.commentAttributeMandatory',1,'editor','termportal','0','0','','boolean',NULL,'Is a comment mandatory, when a new term is created or proposed?',2,'Term creation: Comment mandatory','TermPortal',''),
(268,'runtimeOptions.import.initialTaskUsageMode',1,'editor','import','cooperative','cooperative','competitive,cooperative,simultaneous','string',NULL,'Initial mode how the task should be used by different users. See also https://confluence.translate5.net/display/TAD/Task',4,'Multi user task editing mode','Workflow',''),
(269,'runtimeOptions.editor.notification.userListColumns',1,'editor','workflow','[\"surName\",\"firstName\",\"email\",\"role\",\"state\",\"deadlineDate\"]','[\"surName\",\"firstName\",\"login\",\"email\",\"role\",\"state\"]','surName,firstName,login,email,role,state','list',NULL,'Some workflow mail notifications contain a user listing. The available columns can be configured here.',16,'Workflow notifications: User listing columns','Workflow',''),
(270,'runtimeOptions.editor.toolbar.hideCloseButton',1,'editor','editor','1','1','','boolean',NULL,'0 if the close button in the segments grid header should be shown (only senseful in editor only usage).',1,'','',''),
(271,'runtimeOptions.editor.toolbar.hideLeaveTaskButton',1,'editor','editor','0','0','','boolean',NULL,'1 if the leave task button should be hidden in the segments grid header.',1,'','',''),
(272,'runtimeOptions.editor.customHtmlContainer',1,'editor','layout','','','','string',NULL,'If set, this content is loaded in the upper right part of the editor. For moreinfo see the branding paragraph in confluence link: https://confluence.translate5.net/display/TAD/Implement+a+custom+translate5+skin',4,'Custom HTML in editor (upper right)','Editor: UI layout & more',''),
(273,'runtimeOptions.debug.enableJsLoggerVideo',1,'editor','logging','0','0','','boolean',NULL,'If set to active, the error-logging in the GUI (see previous option) is extended by video recording. Videos are only kept in case of an error, that is send by the user to theRootCause.io. The user still has the option to decide, if he only wants to submit the error or if he also wants to submit the video. If a video is provided, it will be deleted, when translate5s developers did look after the error.',2,'Error-logging in the browser – activate video','System setup: General',''),
(274,'runtimeOptions.appName',1,'app','company','Translate5','Translate5','','string',NULL,'Name of the application shown in the application itself.',1,'','',''),
(275,'runtimeOptions.editor.editorBrandingSource',1,'editor','editor','','','','string',NULL,'Url for the brending source in the editor branding area. When the config is configured with this value : /client-specific/branding.phtml , then the branding.phtml file will be loaded from the client-specific/public direcotry .',1,'','',''),
(276,'runtimeOptions.worker.editor_Models_Import_Worker_FinalStep.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the Final Import Worker worker',1,'','',''),
(277,'runtimeOptions.termTagger.maxSegmentWordCount',1,'plugin','termtagger','150','150','','integer',NULL,'Only segments with a lesser word count are sent to the termTagger',1,'','',''),
(278,'runtimeOptions.frontend.defaultState.helpWindow.customeroverview',1,'editor','system','{\"doNotShowAgain\":false}','{\"doNotShowAgain\":false}','','map',NULL,'Help window default state configuration for the client overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.',32,'Help window client overview: No auto-show','System setup: Help',''),
(279,'runtimeOptions.frontend.defaultState.helpWindow.taskoverview',1,'editor','system','{\"doNotShowAgain\":false}','{\"doNotShowAgain\":false}','','map',NULL,'Help window default state configuration for the task overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.',32,'Help window task overview: No auto-show','System setup: Help',''),
(280,'runtimeOptions.frontend.defaultState.helpWindow.useroverview',1,'editor','system','','{\"doNotShowAgain\":false}','','map',NULL,'Help window default state configuration for the user overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.',32,'Help window user overview: No auto-show','System setup: Help',''),
(281,'runtimeOptions.frontend.defaultState.helpWindow.editor',1,'editor','system','{\"doNotShowAgain\":false}','{\"doNotShowAgain\":false}','','map',NULL,'Help window default state configuration for the editor overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.',32,'Help window editor: No auto-show','System setup: Help',''),
(282,'runtimeOptions.frontend.defaultState.helpWindow.languageresource',1,'editor','system','','{\"doNotShowAgain\":false}','','map',NULL,'Help window default state configuration for the language resource overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „Do not show again“ himself in the help window, which will be remembered for this user.',32,'Help window language resources: No auto-show','System setup: Help',''),
(283,'runtimeOptions.frontend.defaultState.adminTaskGrid',1,'editor','system','','','','map',NULL,'Defines, what columns in the task overview are shown in  what order and if they are hidden or visible. For more information please see\nhttps://confluence.translate5.net/display/CON/Configure+tabular+views+default+layout',32,'Task overview default configuration','Project and task overview',''),
(284,'runtimeOptions.frontend.defaultState.adminUserGrid',1,'editor','system','','','','map',NULL,'Defines, what columns in the user overview are shown in  what order and if they are hidden or visible. For more information please see\nhttps://confluence.translate5.net/display/CON/Configure+tabular+views+default+layout',32,'User overview default configuration','User management',''),
(285,'runtimeOptions.editor.customPanel.url',1,'editor','layout','','','','string',NULL,'If set, another Accordion tab is included in the left part of the editor and filled with the contents of the set URL. For more info see the branding paragraph in confluence link: https://confluence.translate5.net/display/TAD/Implement+a+custom+translate5+skin',4,'Custom HTML in editor (left accordion)','Editor: UI layout & more',''),
(286,'runtimeOptions.editor.customPanel.title',1,'editor','layout','','','','string',NULL,'Optional title for the additional custom panel in the left. This text is used for all GUI languages. If it should be translated, overwrite it in a XLF file in client-specific/locales',4,'Title for custom HTML in editor (left accordion)','Editor: UI layout & more',''),
(287,'runtimeOptions.frontend.defaultState.editor.segmentsGrid',1,'editor','system','{}','','','map',NULL,'Segment table default state configuration. When this config is empty, the task grid state will not be saved or applied. For how to config this value please visit this page: ',32,'Editor segment table default configuration','Editor: UI layout & more',''),
(288,'runtimeOptions.frontend.helpWindow.customeroverview.loaderUrl',1,'editor','system','/help/{0}','','','string',NULL,'The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.',2,'Help window URL: client overview','System setup: Help',''),
(289,'runtimeOptions.frontend.helpWindow.taskoverview.loaderUrl',1,'editor','system','/help/{0}','','','string',NULL,'The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.',2,'Help window URL: task overview','System setup: Help',''),
(290,'runtimeOptions.frontend.helpWindow.useroverview.loaderUrl',1,'editor','system','','','','string',NULL,'The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.',2,'Help window URL: user overview','System setup: Help',''),
(291,'runtimeOptions.frontend.helpWindow.editor.loaderUrl',1,'editor','system','/help/{0}','','','string',NULL,'The content of the defined URL will be loaded in this help page section. If empty and if the URL for the PDF documentation is also empty, nothing is loaded and the help button will not be available. If a PDF documentation URL is also defined there will be a tab navigation in the help window.',2,'Help window URL video: Editor','System setup: Help',''),
(292,'runtimeOptions.frontend.helpWindow.languageresource.loaderUrl',1,'editor','system','','','','string',NULL,'The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.',2,'Help window URL: language resource overview','System setup: Help',''),
(293,'runtimeOptions.termTagger.tagReadonlySegments',1,'plugin','termtagger','0','0','','boolean',NULL,'If set to active, the termTagger checks also read-only segments. Should not be activated to safe performance, if possible.',8,'Terminology check: Check read-only segments','Editor: QA',''),
(294,'runtimeOptions.InstantTranslate.fileTranslation',1,'editor','instanttranslate','1','1','','boolean',NULL,'If set to active, it is possible to upload files for pre-translation. All file formats supported by translate5 are supported.',4,NULL,NULL,NULL),
(295,'runtimeOptions.plugins.PangeaMt.server',1,'editor','plugins','[\"http://prod.pangeamt.com:8080\"]','[]','','list',NULL,'PangeaMT Api Server; format: [\"SCHEME://HOST:PORT\"]',2,'PangeaMT API URL','System setup: Language resources',''),
(296,'runtimeOptions.plugins.PangeaMt.apikey',1,'editor','plugins','','(put your api key here)','','string',NULL,'The apikey as given from PangeaMT',2,'PangeaMT API key','System setup: Language resources',''),
(297,'runtimeOptions.plugins.PangeaMt.matchrate',1,'editor','editor','70','70','','integer',NULL,'Default fuzzy match value for translations done by PangeaMT. Used in the analysis and in the fuzzy match panel, if ModelFront is not used for risk prediction of MT.',2,'PangeaMT default match rate','System setup: Language resources',''),
(298,'runtimeOptions.plugins.ModelFront.apiUrl',1,'editor','plugins','','https://api.modelfront.com/v1/','','string',NULL,'ModelFront api url. In the current config also the api version should be included.',2,'ModelFront API URL','System setup: Language resources',''),
(299,'runtimeOptions.plugins.ModelFront.apiToken',1,'editor','plugins','','','','string',NULL,'ModelFront api token. The token is used for api authentication. More info can be found in https://modelfront.com/',2,'ModelFront API token','System setup: Language resources',''),
(300,'runtimeOptions.worker.editor_Plugins_ModelFront_Worker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running workers of the ModelFront worker',2,'Import: Analysis: Modelfront: Max. parallel workers','System setup: Load balancing',''),
(301,'runtimeOptions.plugins.FrontEndMessageBus.messageBusURI',1,'editor','plugins','http://127.0.0.1:9057','http://127.0.0.1:9057','','string',NULL,'Message Bus URI, change default value according to your needs (as configured in config.php of used FrontEndMessageBus). Unix sockets are also possible, example: unix:///tmp/translate5MessageBus – please see https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information',2,'Websockets server URL','System setup: General',''),
(302,'runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema',1,'editor','plugins','ws','ws','ws,wss','string',NULL,'WebSocket Server default schema. In Order to use SSL, set this to wss instead of ws and configure the backend accordingly. See FrontEndMessageBus/config.php.example how to enable SSL for WebSockets.',2,'Websockets server protocol','System setup: General',''),
(303,'runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost',1,'editor','plugins','','','','string',NULL,'WebSocket Server default HTTP host, if empty the current host of the application in the frontend is used. Can be configured to a fixed value here. Example: www.translate5.net – please see https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information',2,'Websockets server host','System setup: General',''),
(304,'runtimeOptions.plugins.FrontEndMessageBus.socketServer.port',1,'editor','plugins','9056','9056','','string',NULL,'WebSocket Server default port, socketServer port in the FrontEndMessageBus backend config.php  – please see https://confluence.translate5.net/display/CON/WebSocket+Server+for+FrontEndMessageBus+Plug-In for more information',2,'Websockets server port','System setup: General',''),
(305,'runtimeOptions.plugins.FrontEndMessageBus.socketServer.route',1,'editor','plugins','/translate5','/translate5','','string',NULL,'WebSocket Server default route, defaults to \"/translate5\" and should normally not be changed. If using SSL (wss) with a ProxyPass statement, prepend the alias here. Example: \"/tobedefinedbyyou/translate5\" Attention: this config has nothing to do with the APPLICATION_RUNDIR in translate5!',2,'Websockets server default route','System setup: General',''),
(306,'runtimeOptions.plugins.NecTm.server',1,'editor','plugins','[]','[\"http://pangeanic-online.com:47979\"]','','list',NULL,'NEC-TM Api Server URL; format: [\"SCHEME://HOST:PORT\"]',2,'NEC-TM: API URL','System setup: Language resources',''),
(307,'runtimeOptions.plugins.NecTm.credentials',1,'editor','editor','[]','[\"mittagqi:K62yMbCgYMT9n4x4\"]','','list',NULL,'Username and password for connecting to NEC-TM.',2,'NEC-TM: API credentials','System setup: Language resources',''),
(308,'runtimeOptions.plugins.NecTm.topLevelCategoriesIds',1,'editor','editor','[]','[\"tag391\",\"tag840\"]','','list',NULL,'Only TM data below the top-level categories (in NEC-TMs wording these are called „Tags“) can be accessed (plus all public categories). Enter the NEC-TM\'s tag-ids here, not their tag-names!',2,'NEC-TM: Top-level categories','System setup: Language resources',''),
(309,'runtimeOptions.worker.editor_Plugins_NecTm_Worker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running processes of the are NEC-TM catagories (aka tags) sync are allowed. Please consult translate5s team, if you change this.',1,'','',''),
(310,'runtimeOptions.worker.editor_Plugins_MatchAnalysis_Worker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running processes of the match analysis worker are allowed. Please consult translate5s team, if you change this.',2,'Import: Analysis: Max. parallel processes','System setup: Load balancing',''),
(311,'runtimeOptions.openid.requestUserInfo',1,'default','openid','1','1','','boolean',NULL,'Request the authentication provider for additional user information via user info endpoint',2,'OpenID Connect: Use user-info endpoint','System setup: Authentication',''),
(312,'runtimeOptions.maintenance.allowedIPs',1,'app','system','[]','[]','','list',NULL,'A list of IP addresses not affected by the maintenance mode. For testing stuff calling workers add the server IP addresses too (127.0.0.1 and the external). Since this is a dangerous feature the values are resetted on disabling the maintenance!',1,NULL,NULL,NULL),
(313,'runtimeOptions.startup.showConsortiumLogos',1,'app','system','3','3','','string',NULL,'show Consortium Logos on application load for xyz seconds [default 3]. time counts after application is loaded completely. if set to 0, the consortium logos are not shown at all.',1,NULL,NULL,NULL),
(314,'runtimeOptions.frontend.defaultState.editor.westPanel',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor west panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor left panel default configuration','Editor: UI layout & more',''),
(315,'runtimeOptions.frontend.defaultState.editor.eastPanel',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor east panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor right panel default configuration','Editor: UI layout & more',''),
(316,'runtimeOptions.frontend.defaultState.editor.westPanelFileorderTree',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor west panel file tree. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor left panel file tree default configuration','Editor: UI layout & more',''),
(317,'runtimeOptions.frontend.defaultState.editor.westPanelReferenceFileTree',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor west panel reference files tree. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor left panel review file tree default configuration','Editor: UI layout & more',''),
(318,'runtimeOptions.frontend.defaultState.editor.eastPanelSegmentsMetapanel',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor east panel segments meta. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor right panel review segment meta data default configuration','Editor: UI layout & more',''),
(319,'runtimeOptions.frontend.defaultState.editor.eastPanelCommentPanel',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor east panel comments. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor right panel review comments area default configuration','Editor: UI layout & more',''),
(320,'runtimeOptions.frontend.defaultState.editor.languageResourceEditorPanel',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor fuzzy match and concordoance search panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor bottom panel default configuration','Editor: UI layout & more',''),
(321,'runtimeOptions.editor.showReferenceFilesPopup',1,'editor','editor','1','1','','boolean',NULL,'If set to active, a pop-up is shown after a task is opened in the translate5 editor, if reference files are attached to the task.',16,'Show reference file pop-up','Editor: Miscellaneous options',''),
(322,'runtimeOptions.editor.showConfirmFinishTaskPopup',1,'editor','editor','1','1','','boolean',NULL,'When an assigned user leaves a task, he is asked, if he wants to finish or just leave the task. If set to active, and the user that leaves the task clicks „finish task“, he will be asked a second time, if he really wants to finish.',4,'On leaving a task: Show second confirmation window','Editor: Miscellaneous options',''),
(323,'runtimeOptions.frontend.helpWindow.project.loaderUrl',1,'editor','system','/help/{0}','/help/{0}','','string',NULL,'The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.',2,'Help window URL: project overview','System setup: Help',''),
(324,'runtimeOptions.frontend.defaultState.helpWindow.project',1,'editor','system','{\"doNotShowAgain\":false}','{\"doNotShowAgain\":false}','','map',NULL,'Help window default state configuration for the project overview panel. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „do not show again“ himself in the help window, which will be remembered for this user.',32,'Help window project overview: No auto-show','System setup: Help',''),
(325,'runtimeOptions.frontend.helpWindow.preferences.loaderUrl',1,'editor','system','/help/{0}','/help/{0}','','string',NULL,'The content from the defined url will be loaded in this help page section. If emtpy, nothing is loaded and the help button will not be available.',2,'Help window URL: preferences','System setup: Help',''),
(326,'runtimeOptions.frontend.defaultState.helpWindow.preferences',1,'editor','system','{\"doNotShowAgain\":false}','{\"doNotShowAgain\":false}','','map',NULL,'Help window default state configuration for the preferences section. When this is set to disabled, the window will appear automatically in the user overview. A user can then mark the checkbox „do not show again“ himself in the help window, which will be remembered for this user.',32,'Help window preferences: No auto-show','System setup: Help',''),
(327,'runtimeOptions.frontend.defaultState.editor.searchreplacewindow',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor search window. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'','',''),
(328,'runtimeOptions.alike.showOnEmptyTarget',1,'editor','system','0','0','','boolean',NULL,'Default behaviour, for „empty target“ checkbox in the repetition editor (auto-propgate): Only replace repetition automatically / propose replacement of repetition, if target is empty. This is the default behaviour, that can be changed by the user.',32,'Autopropagate / Repetition editor default behaviour for empty target','Editor: Miscellaneous options',''),
(329,'runtimeOptions.jiraIssuesUrl',1,'app','system','https://jira.translate5.net/browse/{0}','https://jira.translate5.net/browse/{0}','','string',NULL,'Url for information to the error codes. The placeholder \"{0}\" will be replaced by the error code.',1,'','',''),
(330,'runtimeOptions.frontend.defaultState.editor.customWestPanel',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor west panel custom panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,NULL,NULL,NULL),
(331,'runtimeOptions.lengthRestriction.sizeUnit',1,'editor','system','char','char','char,pixel','string',NULL,'Defines how the unit of measurement size used for length calculation.',8,'Segment length restriction: Unit of measurement','Editor: QA',''),
(332,'runtimeOptions.lengthRestriction.maxWidth',1,'editor','system','','','','integer',NULL,'The count is based on the unit of measurement. If maxNumberOfLines is set, maxWidth refers to the length of each line, otherwise maxWidth refers to the trans-unit in the underlying xliff (which might span multiple segments)',8,'Segment length restriction: Maximal allowed width','Editor: QA',''),
(333,'runtimeOptions.lengthRestriction.minWidth',1,'editor','system','','','','integer',NULL,'The count is based on the unit of measurement. If maxNumberOfLines is set, minWidth refers to the length of each line, otherwise minWidth refers to the trans-unit in the underlying xliff (which might span multiple segments)',8,'Segment length restriction: Minimal allowed width','Editor: QA',''),
(334,'runtimeOptions.lengthRestriction.maxNumberOfLines',1,'editor','system','','','','integer',NULL,'How many lines the text in the segment is maximal allowed to have (can be overwritten in xliff\'s trans-unit)',8,'Segment length restriction: Allowed number of lines in segment','Editor: QA',''),
(335,'runtimeOptions.lengthRestriction.pixelmapping.font',1,'editor','system','','','','string',NULL,'Contains the name of a font-family, e.g. \"Arial\" or \"Times New Roman\", that refers to the pixel-mapping.xlsx file (see documentation in translate5s confluence)',8,'Segment length restriction (pixel-based): Font name','Editor: QA',''),
(336,'runtimeOptions.lengthRestriction.pixelmapping.fontSize',1,'editor','system','','','','integer',NULL,'Contains a font-size, e.g. \"12\", that refers to the pixel-mapping.xlsx file (see documentation in translate5s confluence)',8,'Segment length restriction (pixel-based): Font size','Editor: QA',''),
(338,'runtimeOptions.import.fileparser.csv.options.regexes.beforeTagParsing.regex',1,'editor','system','[]','[]','','list',NULL,'Must contain a valid php-pcre REGEX. Patterns that match the REGEX will be protected as internal tags inside the segments during the translation process. If the regex is not valid, the import will throw an error and continue without using the regex. If \"protect tags\" is active, the REGEX will be applied to the segment BEFORE translate5 tries to protect tags. If \"protect tags\" is not active, the REGEX will still be applied.',8,'CSV import: Regular expression (run BEFORE tag protection)','File formats',''),
(339,'runtimeOptions.import.fileparser.csv.options.regexes.afterTagParsing.regex',1,'editor','system','[]','[]','','list',NULL,'Must contain a valid php-pcre REGEX. Patterns that match the REGEX will be protected as internal tags inside the segments during the translation process. If the regex is not valid, the import will throw an error and continue without using the regex. If \"protect tags\" is active, the REGEX will be applied to the segment AFTER translate5 tries to protect tags. If \"protect tags\" is not active, the REGEX will still be applied.',8,'CSV import: Regular expression (run AFTER tag protection)','File-parser: after tag protection regex',''),
(340,'runtimeOptions.LanguageResources.usageLogger.logLifetime',1,'editor','system','30','30','','integer',NULL,'How many days the resource usage logs will be keeped in the database.',2,'Resource usage log lifetime','Language resources',''),
(341,'runtimeOptions.import.fileparser.options.protectTags',0,'editor','system','0','0','','boolean',NULL,'If set to active, the content of the file is treated as HTML/XML (regardless of its format). Tags inside the imported file are protected as tags in translate5 segments. This is done for all HTML5 tags and in addition for all tags that look like a valid XML snippet. If the import format is xliff, the HTML tags are expected to be escaped as entities (e. g. &lt;strong&gt; for an opening <strong>-tag). For other formats they are expected to be plain HTML (e. g. <strong> for a <strong>-tag). When importing SDLXLIFF or Transit files, this feature is not supported.',8,'Protect tags','File formats',''),
(342,'runtimeOptions.workflow.default.translation.defaultDeadlineDate',1,'editor','workflow',NULL,NULL,NULL,'float',NULL,'The config defines, how many days the deadline should be in the future based on the order date',4,'Default deadline date: workflow:Standard Workflow,step:Übersetzung','Workflow',NULL),
(343,'runtimeOptions.workflow.default.reviewing.defaultDeadlineDate',1,'editor','workflow',NULL,NULL,NULL,'float',NULL,'The config defines, how many days the deadline should be in the future based on the order date',4,'Default deadline date: workflow:Standard Workflow,step:Lektorat','Workflow',NULL),
(344,'runtimeOptions.workflow.default.translatorCheck.defaultDeadlineDate',1,'editor','workflow',NULL,NULL,NULL,'float',NULL,'The config defines, how many days the deadline should be in the future based on the order date',4,'Default deadline date: workflow:Standard Workflow,step:Zweites Lektorat','Workflow',NULL),
(345,'runtimeOptions.workflow.default.visiting.defaultDeadlineDate',1,'editor','workflow',NULL,NULL,NULL,'float',NULL,'The config defines, how many days the deadline should be in the future based on the order date',4,'Default deadline date: workflow:Standard Workflow,step:Nur anschauen','Workflow',NULL),
(349,'runtimeOptions.frontend.importTask.pivotDropdownVisible',1,'editor','import','1','1','','boolean',NULL,'If set to active, the pivot language dropdown will be visible in the task add window.',2,'Pivot language dropdown','Editor: QA',NULL),
(350,'runtimeOptions.worker.editor_Segment_Quality_ImportFinishingWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the global quality check finishing worker',1,NULL,NULL,NULL),
(351,'runtimeOptions.worker.editor_Segment_Quality_ImportWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the global quality check import worker.',1,NULL,NULL,NULL),
(352,'runtimeOptions.frontend.defaultState.editor.westPanelQualityFilter',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor west panel quality filter panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor left panel quality filter default configuration','Editor: UI layout & more',''),
(353,'runtimeOptions.autoQA.enableInternalTagCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking invalid internal tags',8,'Enable internal tag integrity check','Editor: QA',''),
(354,'runtimeOptions.autoQA.enableEdited100MatchCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking edited 100% matches',8,'Enable edited 100% match check','Editor: QA',''),
(355,'runtimeOptions.autoQA.enableUneditedFuzzyMatchCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking not edited fuzzy matches',8,'Enable not edited fuzzy match check','Editor: QA',''),
(356,'runtimeOptions.LanguageResources.microsoft.apiLocation',1,'editor','system','','','','string',NULL,'Microsoft translator language resource API location restriction. After completing the account registration and resource configuration, get the API location value from the azure portal.',2,'Language resources','Microsoft translator API location restriction',NULL),
(357,'runtimeOptions.workflow.initialWorkflow',1,'editor','workflow','default','default','','string',NULL,'The name of the workflow which should be used by default on task creation.',4,'Initial workflow on task creation','Workflow',NULL),
(358,'runtimeOptions.workflow.notifyAllUsersAboutTask',1,'editor','workflow','1','1','','boolean',NULL,'Defines if the associated users of a task should be notified about the association (after a successfull import of a task).',4,'Workflow notifications: Notify associated users','Workflow',NULL),
(359,'runtimeOptions.frontend.changeUserThemeVisible',1,'editor','system','1','1','','boolean',NULL,'Can the user change the translate5 GUI layout theme from within the translate5 front-end ?',2,'User can change GUI layout theme','Editor: UI layout & more',NULL),
(360,'runtimeOptions.cleanUpUserTheme',1,'editor','system','0','0','','boolean',NULL,'If set to active, a changed GUI layout theme is only temporary for the current user session.',2,'Reset GUI layout theme to default with new login.','Editor: UI layout & more',NULL),
(361,'runtimeOptions.plugins.MatchAnalysis.internalFuzzyDefault',1,'editor','plugins','1','1','','boolean',NULL,'Is \"Count internal fuzzy\" checkbox in the analysis overview checked by default.',4,'Count internal fuzzy checked by default','Match analysis: defaults',NULL),
(362,'runtimeOptions.plugins.MatchAnalysis.pretranslateTmAndTermDefault',1,'editor','plugins','1','1','','boolean',NULL,'Is \"Pre-translate (TM & Terms)\" checkbox in the analysis overview checked by default',4,'Pre-translate (TM & Terms) checked by default','Match analysis: defaults',NULL),
(363,'runtimeOptions.plugins.MatchAnalysis.pretranslateMtDefault',1,'editor','plugins','1','1','','boolean',NULL,'Is \"Pre-translate (MT)\" checkbox in the analysis overview checked by default',4,'Pre-translate (MT) checked by default','Match analysis: defaults',NULL),
(364,'runtimeOptions.autoQA.segmentPixelLengthTooShortPercent',1,'editor','system','20','20','','integer',NULL,'If given, defines how long of the max defined length a segment has to be in percent',8,'Defines the length check for segments being too short in percent','Editor: QA',''),
(365,'runtimeOptions.autoQA.segmentPixelLengthTooShortPixel',1,'editor','system','100','100','','integer',NULL,'If given, defines how much shorter a segment can be than the defined length in pixels',8,'Defines the length check for segments being too short in pixels','Editor: QA',''),
(366,'runtimeOptions.autoQA.segmentPixelLengthTooShortChars',1,'editor','system','20','20','','integer',NULL,'If given, defines how much shorter a segment can be than the defined length in characters',8,'Defines the length check for segments being too short in characters','Editor: QA',''),
(367,'runtimeOptions.autoQA.enableSegmentLengthCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking the segment length',8,'Enables segment length check','Editor: QA',''),
(368,'runtimeOptions.termportal.liveSearchMinChars',1,'editor','termportal','3','3','','integer',NULL,'Number of typed characters to start live search in the search field',2,'When to start live search','TermPortal',''),
(369,'runtimeOptions.tbx.termLabelMap',1,'editor','termtagger','{\"admittedTerm\": \"permitted\", \"deprecatedTerm\": \"forbidden\", \"legalTerm\": \"permitted\", \"preferredTerm\": \"preferred\", \"regulatedTerm\": \"permitted\", \"standardizedTerm\": \"permitted\", \"supersededTerm\": \"forbidden\"}','{\"admittedTerm\": \"permitted\", \"deprecatedTerm\": \"forbidden\", \"legalTerm\": \"permitted\", \"preferredTerm\": \"preferred\", \"regulatedTerm\": \"permitted\", \"standardizedTerm\": \"permitted\", \"supersededTerm\": \"forbidden\"}','','map',NULL,'Defines how the Term Status should be visualized in the Frontend, valid values are preferred,permitted,forbidden',1,'','',''),
(370,'runtimeOptions.frontend.helpWindow.editor.documentationUrl',1,'editor','system','/help/editordocumentation/?lang={0}','/help/editordocumentation/?lang={0}','','string',NULL,'The content of the defined URL will be loaded in this help page section. If empty and if the URL for the help video is also empty, nothing is loaded and the help button will not be available. If a video URL is also defined there will be a tab navigation in the help window.',2,'Help window URL PDF documentation: Editor','System setup: Help',NULL),
(371,'runtimeOptions.extJs.defaultTheme',1,'editor','layout','triton','triton','aria,classic,crisp,crisp-touch,gray,neptune,neptune-touch,triton','string',NULL,'The system default layout theme to be used. Not all layouts are thoroughly tested, so layout fixes may be needed.',1,'','',''),
(372,'runtimeOptions.worker.editor_Plugins_TermTagger_Worker_SetTaskToOpen.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the termtag task to openworker',1,NULL,NULL,NULL),
(373,'runtimeOptions.frontend.defaultState.projectGrid',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the project panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Project overview default configuration','Project and task overview',''),
(374,'runtimeOptions.frontend.defaultState.projectTaskGrid',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the project tasks panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Project tasks overview default configuration','Project and task overview',''),
(375,'runtimeOptions.frontend.defaultState.projectTaskPrefWindow',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the task preferences panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Project task preferences default configuration','Project and task overview',''),
(376,'runtimeOptions.worker.editor_Task_Operation_StartingWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of an operations starting worker.',1,NULL,NULL,NULL),
(377,'runtimeOptions.worker.editor_Task_Operation_FinishingWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of an operations finishing worker',1,NULL,NULL,NULL),
(378,'runtimeOptions.worker.editor_Segment_Quality_OperationFinishingWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the global quality check operation finishing worker',1,NULL,NULL,NULL),
(379,'runtimeOptions.worker.editor_Segment_Quality_OperationWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the global quality check operation worker.',1,NULL,NULL,NULL),
(380,'runtimeOptions.autoQA.segmentPunctuationChars',1,'editor','system',',.-;:_!?¿()}{[]\"\'~<>„“”‘’«»',',.-;:_!?¿()}{[]\"\'~<>„“”‘’«»','','string',NULL,'If given, defines which characters are treated as segment punctuation characters',8,'Defines the segment punctiation characters','Editor: QA',''),
(381,'runtimeOptions.autoQA.enableSegmentEmptyCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking the segment target is empty or contains only spaces, punctuation, or alike',8,'Enables segment emptiness check','Editor: QA',''),
(382,'runtimeOptions.autoQA.enableSegmentConsistentCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking segment consistency, e.g having same sources/targets for different targets/sources',8,'Enables segment consistency check','Editor: QA',''),
(383,'runtimeOptions.worker.editor_Models_Export_Exported_TransferWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the export completed notification worker.',1,'','',''),
(384,'runtimeOptions.worker.editor_Models_Export_Exported_FiletranslationWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the export completed notification worker.',1,'','',''),
(385,'runtimeOptions.worker.editor_Models_Export_Exported_ZipDefaultWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the export completed notification worker.',1,'','',''),
(386,'runtimeOptions.frontend.defaultState.editor.commentNav',1,'editor','system','{}','{}','','map',NULL,'Default state configuration for the editor west panel comment overview panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.',32,'Editor left panel comment overview default configuration','Editor: UI layout & more',''),
(387,'runtimeOptions.autoQA.enableSegmentNumbersCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking the segments against numbers checks, provided by SNC-lib',8,'Enables segment numbers checks','Editor: QA',''),
(388,'runtimeOptions.import.callbackUrl',1,'editor','import','','','','string',NULL,'A URL which is called via POST after import. The imported task is send as raw JSON in that request.',8,'Import: callback URL','System setup: Import',''),
(389,'runtimeOptions.import.timeout',1,'editor','import','48','48','','integer',NULL,'The timeout in hours after which a task in status import is set to status error.',2,'Import: timeout','System setup: Import',''),
(390,'runtimeOptions.editor.segments.editorSpecialCharacters',1,'editor','editor','{\n    \"de\": [\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        },\n        {\n            \"unicode\": \"U+201E\",\n            \"visualized\": \"„\"\n        },\n        {\n            \"unicode\": \"U+201C\",\n            \"visualized\": \"“\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2039\",\n            \"visualized\": \"‹\"\n        },\n        {\n            \"unicode\": \"U+203A\",\n            \"visualized\": \"›\"\n        },\n        {\n            \"unicode\": \"U+201A\",\n            \"visualized\": \"‚\"\n        },\n        {\n            \"unicode\": \"U+2018\",\n            \"visualized\": \"‘\"\n        },\n        {\n            \"unicode\": \"U+0160\",\n            \"visualized\": \"Š\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        },\n        {\n            \"unicode\": \"U+0027\",\n            \"visualized\": \"\'\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+00D7\",\n            \"visualized\": \"×\"\n        }\n    ],\n    \"fr\": [\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+0160\",\n            \"visualized\": \"Š\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        },\n        {\n            \"unicode\": \"U+0152\",\n            \"visualized\": \"Œ\"\n        },\n        {\n            \"unicode\": \"U+0153\",\n            \"visualized\": \"œ\"\n        },\n        {\n            \"unicode\": \"U+00E9\",\n            \"visualized\": \"é\"\n        },\n        {\n            \"unicode\": \"U+00E8\",\n            \"visualized\": \"è\"\n        },\n        {\n            \"unicode\": \"U+00EA\",\n            \"visualized\": \"ê\"\n        },\n        {\n            \"unicode\": \"U+00EB\",\n            \"visualized\": \"ë\"\n        },\n        {\n            \"unicode\": \"U+00E0\",\n            \"visualized\": \"à\"\n        },\n        {\n            \"unicode\": \"U+00E2\",\n            \"visualized\": \"â\"\n        },\n        {\n            \"unicode\": \"U+00F9\",\n            \"visualized\": \"ù\"\n        },\n        {\n            \"unicode\": \"U+00FB\",\n            \"visualized\": \"û\"\n        },\n        {\n            \"unicode\": \"U+00FC\",\n            \"visualized\": \"ü\"\n        },\n        {\n            \"unicode\": \"U+00EE\",\n            \"visualized\": \"î\"\n        },\n        {\n            \"unicode\": \"U+00EF\",\n            \"visualized\": \"ï\"\n        },\n        {\n            \"unicode\": \"U+00F4\",\n            \"visualized\": \"ô\"\n        },\n        {\n            \"unicode\": \"U+00E7\",\n            \"visualized\": \"ç\"\n        },\n        {\n            \"unicode\": \"U+00C7\",\n            \"visualized\": \"Ç\"\n        },\n        {\n            \"unicode\": \"U+00E6\",\n            \"visualized\": \"æ\"\n        },\n        {\n            \"unicode\": \"U+00C6\",\n            \"visualized\": \"Æ\"\n        },\n        {\n            \"unicode\": \"U+0023\",\n            \"visualized\": \"#\"\n        },\n        {\n            \"unicode\": \"U+0040\",\n            \"visualized\": \"@\"\n        },\n        {\n            \"unicode\": \"U+00D7\",\n            \"visualized\": \"×\"\n        },\n        {\n            \"unicode\": \"U+00B3\",\n            \"visualized\": \"³\"\n        },\n        {\n            \"unicode\": \"U+00B7\",\n            \"visualized\": \"·\"\n        }\n    ],\n    \"it\": [\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+201C\",\n            \"visualized\": \"“\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        }\n    ],\n    \"en\": [\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+2018\",\n            \"visualized\": \"‘\"\n        },\n        {\n            \"unicode\": \"U+201C\",\n            \"visualized\": \"“\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+2014\",\n            \"visualized\": \"—\"\n        },\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        }\n    ],\n    \"da\":[\n        {\n            \"unicode\": \"U+00E6\",\n            \"visualized\": \"æ\"\n        },\n        {\n            \"unicode\": \"U+00F8\",\n            \"visualized\": \"ø\"\n        },\n        {\n            \"unicode\": \"U+00E5\",\n            \"visualized\": \"å\"\n        },\n        {\n            \"unicode\": \"U+00C6\",\n            \"visualized\": \"Æ\"\n        },\n        {\n            \"unicode\": \"U+00D8\",\n            \"visualized\": \"Ø\"\n        },\n        {\n            \"unicode\": \"U+00C5\",\n            \"visualized\": \"Å\"\n        }\n    ],\n    \"es\":[\n        {\n            \"unicode\": \"U+00F1\",\n            \"visualized\": \"ñ\"\n        },\n        {\n            \"unicode\": \"U+00D1\",\n            \"visualized\": \"Ñ\"\n        },\n        {\n            \"unicode\": \"U+00BF\",\n            \"visualized\": \"¿\"\n        },\n        {\n            \"unicode\": \"U+00A1\",\n            \"visualized\": \"¡\"\n        }\n    ],\n    \"el\":[\n        {\n            \"unicode\": \"U+03AC\",\n            \"visualized\": \"ά\"\n        },\n        {\n            \"unicode\": \"U+03AD\",\n            \"visualized\": \"έ\"\n        },\n        {\n            \"unicode\": \"U+03CC\",\n            \"visualized\": \"ό\"\n        },\n        {\n            \"unicode\": \"U+03AF\",\n            \"visualized\": \"ί\"\n        },\n        {\n            \"unicode\": \"U+03CD\",\n            \"visualized\": \"ύ\"\n        },\n        {\n            \"unicode\": \"U+03BA\",\n            \"visualized\": \"α\"\n        },\n        {\n            \"unicode\": \"U+03AE\",\n            \"visualized\": \"ή\"\n        },\n        {\n            \"unicode\": \"U+0389\",\n            \"visualized\": \"Ή\"\n        },\n        {\n            \"unicode\": \"U+037E\",\n            \"visualized\": \";\"\n        },\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        }\n    ],\n    \"hu\":[\n        {\n            \"unicode\": \"U+00E1\",\n            \"visualized\": \"á\"\n        },\n        {\n            \"unicode\": \"U+00E9\",\n            \"visualized\": \"é\"\n        },\n        {\n            \"unicode\": \"U+00ED\",\n            \"visualized\": \"í\"\n        },\n        {\n            \"unicode\": \"U+00F6\",\n            \"visualized\": \"ö\"\n        },\n        {\n            \"unicode\": \"U+0151\",\n            \"visualized\": \"ő\"\n        },\n        {\n            \"unicode\": \"U+00FC\",\n            \"visualized\": \"ü\"\n        },\n        {\n            \"unicode\": \"U+0171\",\n            \"visualized\": \"ű\"\n        },\n        {\n            \"unicode\": \"U+00F3\",\n            \"visualized\": \"ó\"\n        },\n        {\n            \"unicode\": \"U+00C1\",\n            \"visualized\": \"Á\"\n        },\n        {\n            \"unicode\": \"U+00C9\",\n            \"visualized\": \"É\"\n        },\n        {\n            \"unicode\": \"U+00CD\",\n            \"visualized\": \"Í\"\n        },\n        {\n            \"unicode\": \"U+00D6\",\n            \"visualized\": \"Ö\"\n        },\n        {\n            \"unicode\": \"U+0150\",\n            \"visualized\": \"Ő\"\n        },\n        {\n            \"unicode\": \"U+00DC\",\n            \"visualized\": \"Ü\"\n        },\n        {\n            \"unicode\": \"U+0170\",\n            \"visualized\": \"Ű\"\n        },\n        {\n            \"unicode\": \"U+00DA\",\n            \"visualized\": \"Ú\"\n        },\n        {\n            \"unicode\": \"U+00D3\",\n            \"visualized\": \"Ó\"\n        },\n        {\n            \"unicode\": \"U+33A5\",\n            \"visualized\": \"m³\"\n        },\n        {\n            \"unicode\": \"U+33A1\",\n            \"visualized\": \"m²\"\n        }\n    ],\n    \"lt\":[\n    	{\n            \"unicode\": \"U+201E\",\n            \"visualized\": \"„\"\n        },\n        {\n            \"unicode\": \"U+201C\",\n            \"visualized\": \"“\"\n        },\n        {\n            \"unicode\": \"U+0105\",\n            \"visualized\": \"ą\"\n        },\n        {\n            \"unicode\": \"U+0104\",\n            \"visualized\": \"Ą\"\n        },\n        {\n            \"unicode\": \"U+010D\",\n            \"visualized\": \"č\"\n        },\n        {\n            \"unicode\": \"U+010C\",\n            \"visualized\": \"Č\"\n        },\n        {\n            \"unicode\": \"U+0119\",\n            \"visualized\": \"ę\"\n        },\n        {\n            \"unicode\": \"U+0118\",\n            \"visualized\": \"Ę\"\n        },\n        {\n            \"unicode\": \"U+0117\",\n            \"visualized\": \"ė\"\n        },\n        {\n            \"unicode\": \"U+0116\",\n            \"visualized\": \"Ė\"\n        },\n        {\n            \"unicode\": \"U+012F\",\n            \"visualized\": \"į\"\n        },\n        {\n            \"unicode\": \"U+012E\",\n            \"visualized\": \"Į\"\n        },\n        {\n            \"unicode\": \"U+0161\",\n            \"visualized\": \"š\"\n        },\n        {\n            \"unicode\": \"U+0160\",\n            \"visualized\": \"Š\"\n        },\n        {\n            \"unicode\": \"U+0173\",\n            \"visualized\": \"ų\"\n        },\n        {\n            \"unicode\": \"U+0172\",\n            \"visualized\": \"Ų\"\n        },\n        {\n            \"unicode\": \"U+016B\",\n            \"visualized\": \"ū\"\n        },\n        {\n            \"unicode\": \"U+016A\",\n            \"visualized\": \"Ū\"\n        },\n        {\n            \"unicode\": \"U+017E\",\n            \"visualized\": \"ž\"\n        },\n        {\n            \"unicode\": \"U+017D\",\n            \"visualized\": \"Ž\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        }\n    ],\n    \"lv\":[\n        {\n            \"unicode\": \"U+0100\",\n            \"visualized\": \"Ā\"\n        },\n        {\n            \"unicode\": \"U+0101\",\n            \"visualized\": \"ā\"\n        },\n        {\n            \"unicode\": \"U+010D\",\n            \"visualized\": \"č\"\n        },\n        {\n            \"unicode\": \"U+010C\",\n            \"visualized\": \"Č\"\n        },\n        {\n            \"unicode\": \"U+0112\",\n            \"visualized\": \"Ē\"\n        },\n        {\n            \"unicode\": \"U+0113\",\n            \"visualized\": \"ē\"\n        },\n        {\n            \"unicode\": \"U+0122\",\n            \"visualized\": \"Ģ\"\n        },\n        {\n            \"unicode\": \"U+0123\",\n            \"visualized\": \"ģ\"\n        },\n        {\n            \"unicode\": \"U+012A\",\n            \"visualized\": \"Ī\"\n        },\n        {\n            \"unicode\": \"U+012B\",\n            \"visualized\": \"ī\"\n        },\n        {\n            \"unicode\": \"U+0136\",\n            \"visualized\": \"Ķ\"\n        },\n        {\n            \"unicode\": \"U+0137\",\n            \"visualized\": \"ķ\"\n        },\n        {\n            \"unicode\": \"U+013B\",\n            \"visualized\": \"Ļ\"\n        },\n        {\n            \"unicode\": \"U+013C\",\n            \"visualized\": \"ļ\"\n        },\n        {\n            \"unicode\": \"U+0145\",\n            \"visualized\": \"Ņ\"\n        },\n        {\n            \"unicode\": \"U+0146\",\n            \"visualized\": \"ņ\"\n        },\n        {\n            \"unicode\": \"U+0160\",\n            \"visualized\": \"Š\"\n        },\n        {\n            \"unicode\": \"U+0161\",\n            \"visualized\": \"š\"\n        },\n        {\n            \"unicode\": \"U+016B\",\n            \"visualized\": \"ū\"\n        },\n        {\n            \"unicode\": \"U+016A\",\n            \"visualized\": \"Ū\"\n        },\n        {\n            \"unicode\": \"U+017E\",\n            \"visualized\": \"ž\"\n        },\n        {\n            \"unicode\": \"U+017D\",\n            \"visualized\": \"Ž\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+2014\",\n            \"visualized\": \"—\"\n        },\n        {\n            \"unicode\": \"U+201E\",\n            \"visualized\": \"„\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        }\n    ],\n    \"no\":[\n 	{\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        },\n         {\n            \"unicode\": \"U+00E6\",\n            \"visualized\": \"æ\"\n        },\n        {\n            \"unicode\": \"U+00F8\",\n            \"visualized\": \"ø\"\n        },\n        {\n            \"unicode\": \"U+00E5\",\n            \"visualized\": \"å\"\n        },\n        {\n            \"unicode\": \"U+00C6\",\n            \"visualized\": \"Æ\"\n        },\n        {\n            \"unicode\": \"U+00D8\",\n            \"visualized\": \"Ø\"\n        },\n        {\n            \"unicode\": \"U+00C5\",\n            \"visualized\": \"Å\"\n        }\n    ],\n    \"fi\":\n    [\n    	{\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        }, \n        {\n            \"unicode\": \"U+00E5\",\n            \"visualized\": \"å\"\n        }\n    ],\n    \"sw\":\n    [\n    	 {\n            \"unicode\": \"U+00E5\",\n            \"visualized\": \"å\"\n        },\n        {\n            \"unicode\": \"U+00C5\",\n            \"visualized\": \"Å\"\n        },\n        {\n            \"unicode\": \"U+00E4\",\n            \"visualized\": \"ä\"\n        },\n        {\n            \"unicode\": \"U+00C4\",\n            \"visualized\": \"Ä\"\n        },\n        {\n            \"unicode\": \"U+00F6\",\n            \"visualized\": \"ö\"\n        },\n        {\n            \"unicode\": \"U+00D6\",\n            \"visualized\": \"Ö\"\n        }\n    ],\n    \"pl\":\n    [\n        {\n            \"unicode\": \"U+201E\",\n            \"visualized\": \"„\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        }\n    ]\n}','{\n    \"de\": [\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        },\n        {\n            \"unicode\": \"U+201E\",\n            \"visualized\": \"„\"\n        },\n        {\n            \"unicode\": \"U+201C\",\n            \"visualized\": \"“\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2039\",\n            \"visualized\": \"‹\"\n        },\n        {\n            \"unicode\": \"U+203A\",\n            \"visualized\": \"›\"\n        },\n        {\n            \"unicode\": \"U+201A\",\n            \"visualized\": \"‚\"\n        },\n        {\n            \"unicode\": \"U+2018\",\n            \"visualized\": \"‘\"\n        },\n        {\n            \"unicode\": \"U+0160\",\n            \"visualized\": \"Š\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        },\n        {\n            \"unicode\": \"U+0027\",\n            \"visualized\": \"\'\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+00D7\",\n            \"visualized\": \"×\"\n        }\n    ],\n    \"fr\": [\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+0160\",\n            \"visualized\": \"Š\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        },\n        {\n            \"unicode\": \"U+0152\",\n            \"visualized\": \"Œ\"\n        },\n        {\n            \"unicode\": \"U+0153\",\n            \"visualized\": \"œ\"\n        },\n        {\n            \"unicode\": \"U+00E9\",\n            \"visualized\": \"é\"\n        },\n        {\n            \"unicode\": \"U+00E8\",\n            \"visualized\": \"è\"\n        },\n        {\n            \"unicode\": \"U+00EA\",\n            \"visualized\": \"ê\"\n        },\n        {\n            \"unicode\": \"U+00EB\",\n            \"visualized\": \"ë\"\n        },\n        {\n            \"unicode\": \"U+00E0\",\n            \"visualized\": \"à\"\n        },\n        {\n            \"unicode\": \"U+00E2\",\n            \"visualized\": \"â\"\n        },\n        {\n            \"unicode\": \"U+00F9\",\n            \"visualized\": \"ù\"\n        },\n        {\n            \"unicode\": \"U+00FB\",\n            \"visualized\": \"û\"\n        },\n        {\n            \"unicode\": \"U+00FC\",\n            \"visualized\": \"ü\"\n        },\n        {\n            \"unicode\": \"U+00EE\",\n            \"visualized\": \"î\"\n        },\n        {\n            \"unicode\": \"U+00EF\",\n            \"visualized\": \"ï\"\n        },\n        {\n            \"unicode\": \"U+00F4\",\n            \"visualized\": \"ô\"\n        },\n        {\n            \"unicode\": \"U+00E7\",\n            \"visualized\": \"ç\"\n        },\n        {\n            \"unicode\": \"U+00C7\",\n            \"visualized\": \"Ç\"\n        },\n        {\n            \"unicode\": \"U+00E6\",\n            \"visualized\": \"æ\"\n        },\n        {\n            \"unicode\": \"U+00C6\",\n            \"visualized\": \"Æ\"\n        },\n        {\n            \"unicode\": \"U+0023\",\n            \"visualized\": \"#\"\n        },\n        {\n            \"unicode\": \"U+0040\",\n            \"visualized\": \"@\"\n        },\n        {\n            \"unicode\": \"U+00D7\",\n            \"visualized\": \"×\"\n        },\n        {\n            \"unicode\": \"U+00B3\",\n            \"visualized\": \"³\"\n        },\n        {\n            \"unicode\": \"U+00B7\",\n            \"visualized\": \"·\"\n        }\n    ],\n    \"it\": [\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+201C\",\n            \"visualized\": \"“\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        }\n    ],\n    \"en\": [\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+2018\",\n            \"visualized\": \"‘\"\n        },\n        {\n            \"unicode\": \"U+201C\",\n            \"visualized\": \"“\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+2014\",\n            \"visualized\": \"—\"\n        },\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        }\n    ],\n    \"da\":[\n        {\n            \"unicode\": \"U+00E6\",\n            \"visualized\": \"æ\"\n        },\n        {\n            \"unicode\": \"U+00F8\",\n            \"visualized\": \"ø\"\n        },\n        {\n            \"unicode\": \"U+00E5\",\n            \"visualized\": \"å\"\n        },\n        {\n            \"unicode\": \"U+00C6\",\n            \"visualized\": \"Æ\"\n        },\n        {\n            \"unicode\": \"U+00D8\",\n            \"visualized\": \"Ø\"\n        },\n        {\n            \"unicode\": \"U+00C5\",\n            \"visualized\": \"Å\"\n        }\n    ],\n    \"es\":[\n        {\n            \"unicode\": \"U+00F1\",\n            \"visualized\": \"ñ\"\n        },\n        {\n            \"unicode\": \"U+00D1\",\n            \"visualized\": \"Ñ\"\n        },\n        {\n            \"unicode\": \"U+00BF\",\n            \"visualized\": \"¿\"\n        },\n        {\n            \"unicode\": \"U+00A1\",\n            \"visualized\": \"¡\"\n        }\n    ],\n    \"el\":[\n        {\n            \"unicode\": \"U+03AC\",\n            \"visualized\": \"ά\"\n        },\n        {\n            \"unicode\": \"U+03AD\",\n            \"visualized\": \"έ\"\n        },\n        {\n            \"unicode\": \"U+03CC\",\n            \"visualized\": \"ό\"\n        },\n        {\n            \"unicode\": \"U+03AF\",\n            \"visualized\": \"ί\"\n        },\n        {\n            \"unicode\": \"U+03CD\",\n            \"visualized\": \"ύ\"\n        },\n        {\n            \"unicode\": \"U+03BA\",\n            \"visualized\": \"α\"\n        },\n        {\n            \"unicode\": \"U+03AE\",\n            \"visualized\": \"ή\"\n        },\n        {\n            \"unicode\": \"U+0389\",\n            \"visualized\": \"Ή\"\n        },\n        {\n            \"unicode\": \"U+037E\",\n            \"visualized\": \";\"\n        },\n        {\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        }\n    ],\n    \"hu\":[\n        {\n            \"unicode\": \"U+00E1\",\n            \"visualized\": \"á\"\n        },\n        {\n            \"unicode\": \"U+00E9\",\n            \"visualized\": \"é\"\n        },\n        {\n            \"unicode\": \"U+00ED\",\n            \"visualized\": \"í\"\n        },\n        {\n            \"unicode\": \"U+00F6\",\n            \"visualized\": \"ö\"\n        },\n        {\n            \"unicode\": \"U+0151\",\n            \"visualized\": \"ő\"\n        },\n        {\n            \"unicode\": \"U+00FC\",\n            \"visualized\": \"ü\"\n        },\n        {\n            \"unicode\": \"U+0171\",\n            \"visualized\": \"ű\"\n        },\n        {\n            \"unicode\": \"U+00F3\",\n            \"visualized\": \"ó\"\n        },\n        {\n            \"unicode\": \"U+00C1\",\n            \"visualized\": \"Á\"\n        },\n        {\n            \"unicode\": \"U+00C9\",\n            \"visualized\": \"É\"\n        },\n        {\n            \"unicode\": \"U+00CD\",\n            \"visualized\": \"Í\"\n        },\n        {\n            \"unicode\": \"U+00D6\",\n            \"visualized\": \"Ö\"\n        },\n        {\n            \"unicode\": \"U+0150\",\n            \"visualized\": \"Ő\"\n        },\n        {\n            \"unicode\": \"U+00DC\",\n            \"visualized\": \"Ü\"\n        },\n        {\n            \"unicode\": \"U+0170\",\n            \"visualized\": \"Ű\"\n        },\n        {\n            \"unicode\": \"U+00DA\",\n            \"visualized\": \"Ú\"\n        },\n        {\n            \"unicode\": \"U+00D3\",\n            \"visualized\": \"Ó\"\n        },\n        {\n            \"unicode\": \"U+33A5\",\n            \"visualized\": \"m³\"\n        },\n        {\n            \"unicode\": \"U+33A1\",\n            \"visualized\": \"m²\"\n        }\n    ],\n    \"lt\":[\n    	{\n            \"unicode\": \"U+201E\",\n            \"visualized\": \"„\"\n        },\n        {\n            \"unicode\": \"U+201C\",\n            \"visualized\": \"“\"\n        },\n        {\n            \"unicode\": \"U+0105\",\n            \"visualized\": \"ą\"\n        },\n        {\n            \"unicode\": \"U+0104\",\n            \"visualized\": \"Ą\"\n        },\n        {\n            \"unicode\": \"U+010D\",\n            \"visualized\": \"č\"\n        },\n        {\n            \"unicode\": \"U+010C\",\n            \"visualized\": \"Č\"\n        },\n        {\n            \"unicode\": \"U+0119\",\n            \"visualized\": \"ę\"\n        },\n        {\n            \"unicode\": \"U+0118\",\n            \"visualized\": \"Ę\"\n        },\n        {\n            \"unicode\": \"U+0117\",\n            \"visualized\": \"ė\"\n        },\n        {\n            \"unicode\": \"U+0116\",\n            \"visualized\": \"Ė\"\n        },\n        {\n            \"unicode\": \"U+012F\",\n            \"visualized\": \"į\"\n        },\n        {\n            \"unicode\": \"U+012E\",\n            \"visualized\": \"Į\"\n        },\n        {\n            \"unicode\": \"U+0161\",\n            \"visualized\": \"š\"\n        },\n        {\n            \"unicode\": \"U+0160\",\n            \"visualized\": \"Š\"\n        },\n        {\n            \"unicode\": \"U+0173\",\n            \"visualized\": \"ų\"\n        },\n        {\n            \"unicode\": \"U+0172\",\n            \"visualized\": \"Ų\"\n        },\n        {\n            \"unicode\": \"U+016B\",\n            \"visualized\": \"ū\"\n        },\n        {\n            \"unicode\": \"U+016A\",\n            \"visualized\": \"Ū\"\n        },\n        {\n            \"unicode\": \"U+017E\",\n            \"visualized\": \"ž\"\n        },\n        {\n            \"unicode\": \"U+017D\",\n            \"visualized\": \"Ž\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        }\n    ],\n    \"lv\":[\n        {\n            \"unicode\": \"U+0100\",\n            \"visualized\": \"Ā\"\n        },\n        {\n            \"unicode\": \"U+0101\",\n            \"visualized\": \"ā\"\n        },\n        {\n            \"unicode\": \"U+010D\",\n            \"visualized\": \"č\"\n        },\n        {\n            \"unicode\": \"U+010C\",\n            \"visualized\": \"Č\"\n        },\n        {\n            \"unicode\": \"U+0112\",\n            \"visualized\": \"Ē\"\n        },\n        {\n            \"unicode\": \"U+0113\",\n            \"visualized\": \"ē\"\n        },\n        {\n            \"unicode\": \"U+0122\",\n            \"visualized\": \"Ģ\"\n        },\n        {\n            \"unicode\": \"U+0123\",\n            \"visualized\": \"ģ\"\n        },\n        {\n            \"unicode\": \"U+012A\",\n            \"visualized\": \"Ī\"\n        },\n        {\n            \"unicode\": \"U+012B\",\n            \"visualized\": \"ī\"\n        },\n        {\n            \"unicode\": \"U+0136\",\n            \"visualized\": \"Ķ\"\n        },\n        {\n            \"unicode\": \"U+0137\",\n            \"visualized\": \"ķ\"\n        },\n        {\n            \"unicode\": \"U+013B\",\n            \"visualized\": \"Ļ\"\n        },\n        {\n            \"unicode\": \"U+013C\",\n            \"visualized\": \"ļ\"\n        },\n        {\n            \"unicode\": \"U+0145\",\n            \"visualized\": \"Ņ\"\n        },\n        {\n            \"unicode\": \"U+0146\",\n            \"visualized\": \"ņ\"\n        },\n        {\n            \"unicode\": \"U+0160\",\n            \"visualized\": \"Š\"\n        },\n        {\n            \"unicode\": \"U+0161\",\n            \"visualized\": \"š\"\n        },\n        {\n            \"unicode\": \"U+016B\",\n            \"visualized\": \"ū\"\n        },\n        {\n            \"unicode\": \"U+016A\",\n            \"visualized\": \"Ū\"\n        },\n        {\n            \"unicode\": \"U+017E\",\n            \"visualized\": \"ž\"\n        },\n        {\n            \"unicode\": \"U+017D\",\n            \"visualized\": \"Ž\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+2014\",\n            \"visualized\": \"—\"\n        },\n        {\n            \"unicode\": \"U+201E\",\n            \"visualized\": \"„\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        }\n    ],\n    \"no\":[\n 	{\n            \"unicode\": \"U+00AB\",\n            \"visualized\": \"«\"\n        },\n        {\n            \"unicode\": \"U+00BB\",\n            \"visualized\": \"»\"\n        },\n         {\n            \"unicode\": \"U+00E6\",\n            \"visualized\": \"æ\"\n        },\n        {\n            \"unicode\": \"U+00F8\",\n            \"visualized\": \"ø\"\n        },\n        {\n            \"unicode\": \"U+00E5\",\n            \"visualized\": \"å\"\n        },\n        {\n            \"unicode\": \"U+00C6\",\n            \"visualized\": \"Æ\"\n        },\n        {\n            \"unicode\": \"U+00D8\",\n            \"visualized\": \"Ø\"\n        },\n        {\n            \"unicode\": \"U+00C5\",\n            \"visualized\": \"Å\"\n        }\n    ],\n    \"fi\":\n    [\n    	{\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        }, \n        {\n            \"unicode\": \"U+00E5\",\n            \"visualized\": \"å\"\n        }\n    ],\n    \"sw\":\n    [\n    	 {\n            \"unicode\": \"U+00E5\",\n            \"visualized\": \"å\"\n        },\n        {\n            \"unicode\": \"U+00C5\",\n            \"visualized\": \"Å\"\n        },\n        {\n            \"unicode\": \"U+00E4\",\n            \"visualized\": \"ä\"\n        },\n        {\n            \"unicode\": \"U+00C4\",\n            \"visualized\": \"Ä\"\n        },\n        {\n            \"unicode\": \"U+00F6\",\n            \"visualized\": \"ö\"\n        },\n        {\n            \"unicode\": \"U+00D6\",\n            \"visualized\": \"Ö\"\n        }\n    ],\n    \"pl\":\n    [\n        {\n            \"unicode\": \"U+201E\",\n            \"visualized\": \"„\"\n        },\n        {\n            \"unicode\": \"U+201D\",\n            \"visualized\": \"”\"\n        },\n        {\n            \"unicode\": \"U+2013\",\n            \"visualized\": \"–\"\n        },\n        {\n            \"unicode\": \"U+00B2\",\n            \"visualized\": \"²\"\n        },\n        {\n            \"unicode\": \"U+2082\",\n            \"visualized\": \"₂\"\n        },\n        {\n            \"unicode\": \"U+2019\",\n            \"visualized\": \"’\"\n        },\n        {\n            \"unicode\": \"U+00A9\",\n            \"visualized\": \"©\"\n        },\n        {\n            \"unicode\": \"U+00AE\",\n            \"visualized\": \"®\"\n        },\n        {\n            \"unicode\": \"U+2122\",\n            \"visualized\": \"™\"\n        }\n    ]\n}','','string',NULL,'List of characters which will be shown as buttons in the editor for matching target language of the task and can be added in the caret location by clicking on them.',1,'Special characters','Editor: Segments',''),
(391,'runtimeOptions.autoQA.enableSegmentWhitespaceCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking 3 kinds of whitespaces (tabs, line breaks and non-breaking spaces) at the beginning/ending of the segment',8,'Enables segment leading/trailing whitespaces check','Editor: QA',''),
(392,'runtimeOptions.workflow.disableNotifications',1,'editor','workflow','0','0','','boolean',NULL,'When set to active, no workflow emails will be send.',16,'Disable workflow notifications','Workflow',''),
(393,'runtimeOptions.alike.segmentMetaFields',1,'editor','system','[]','[]','minWidth,maxWidth,maxNumberOfLines,sizeUnit,font,fontSize','list',NULL,'Segment meta fields to be added into the calculation of repetitions (auto-propagation)',8,'Autopropagate / Repetition editor additional filters','Editor: Miscellaneous options',''),
(394,'runtimeOptions.lengthRestriction.newLineReplaceWhitespace',1,'editor','system','1','1','','boolean',NULL,'If one line of a segment is to long, a new line is automatically added. By default previous whitespace is replaced by the new-line, this can be disabled',16,'Segment length restriction: auto new-line replace whitespace','Editor: QA',''),
(395,'runtimeOptions.lengthRestriction.automaticNewLineAdding',1,'editor','system','1','1','','boolean',NULL,'If one line of a segment is to long, a new line is automatically added. This could be disabled.',16,'Segment length restriction: auto new-line insertion','Editor: QA',''),
(396,'runtimeOptions.worker.MittagQI\\Translate5\\Workflow\\ArchiveWorker.maxParallelWorkers',1,'editor','worker','2','2','','integer',NULL,'Max parallel running workers of the ArchiveWorker completed notification worker.',1,'','',''),
(397,'runtimeOptions.worker.MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers for pivot pre-translation',1,NULL,NULL,NULL),
(398,'runtimeOptions.LanguageResources.Pretranslation.pivot.pretranslateMtDefault',1,'editor','plugins','1','1','','boolean',NULL,'Should TM be used in pivot pre-translation	',4,'Use MT for pivot pre-translation','Language resources',NULL),
(399,'runtimeOptions.editor.toolbar.askFinishOnClose',1,'editor','editor','0','0','','boolean',NULL,'1 if on task close user should be asked for finishing the task',1,'','',''),
(400,'runtimeOptions.autoQA.segmentWhitespaceChars',1,'editor','system','⎵↵→·','⎵↵→·','','string',NULL,'If given, defines which characters are treated as segment whitespace characters',8,'Defines the segment whitespace characters','Editor: QA',''),
(401,'runtimeOptions.project.defaultPivotLanguage',1,'editor','import','','','','string','','Default pivot language used for all new tasks. Only rfc5646 language values are supported.',4,'Default pivot language','System setup: Import',NULL),
(402,'runtimeOptions.worker.editor_Plugins_MatchAnalysis_BatchWorker.maxParallelWorkers',1,'editor','worker','3','3','','integer',NULL,'Max parallel running workers of the MatchAnalysis BatchWorker',2,'Import: Analysis: Max. parallel processes','System setup: Load balancing',''),
(403,'runtimeOptions.LanguageResources.Pretranslation.enableBatchQuery',1,'editor','plugins','1','1','','boolean',NULL,'Enables batch query requests for pretranslations only for the associated language resource that support batch query. Batch query is much faster for many language resources for imports and InstantTranslate',2,'Language resource batch query: Enable','System setup: Language resources',''),
(404,'runtimeOptions.plugins.MatchAnalysis.fuzzyBoundaries',1,'editor','plugins','{\"50\":59,\"60\":69,\"70\":79,\"80\":89,\"90\":99,\"100\":100,\"101\":101,\"102\":102,\"103\":103,\"104\":104}','{\"50\":59,\"60\":69,\"70\":79,\"80\":89,\"90\":99,\"100\":100,\"101\":101,\"102\":102,\"103\":103,\"104\":104}','','map','editor_Plugins_MatchAnalysis_DbConfig_FuzzyBoundaryType','Define the fuzzy match rate boundaries to be used in the analysis (GUI / XML / Excel export).',8,'Match rate boundaries','Match analysis: defaults',NULL),
(405,'runtimeOptions.plugins.MatchAnalysis.xmlInContextUsage',1,'editor','plugins','0','0','','boolean',NULL,'Define if 101% matches should be used as exact (default) match or as inContextUsage match in the analysis export as Trados XML',8,'Analysis XML export: 101% usage','Match analysis: defaults',NULL),
(406,'runtimeOptions.plugins.MatchAnalysis.calculateBasedOn',1,'editor','plugins','word','word','word,character','string',NULL,'Define the base of the analysis calculation. It can be word or character based.',2,'Analysis calculation base','Match analysis: defaults',NULL),
(407,'runtimeOptions.termTagger.enableAutoQA',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers superseeded and deprecated terms, terms with not found or not defined target translation',8,'Terminology check','Editor: QA',''),
(408,'runtimeOptions.worker.editor_Plugins_TermTagger_Worker_Remove.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers of the Termtagger removal worker.',1,NULL,NULL,NULL),
(409,'runtimeOptions.termTagger.usedTermProcessStatus',1,'editor','termtagger','[\"finalized\"]','[\"finalized\"]','provisionallyProcessed,rejected,finalized,unprocessed','list',NULL,'Term processing status used for term tagging',8,'Term processing status for term tagging','TermTagger',NULL),
(410,'runtimeOptions.plugins.SpellCheck.languagetool.url.import',1,'editor','plugins','[\"http://yourlanguagetooldomain:8081/v2\"]','[\"http://localhost:8081/v2\"]','','list',NULL,'Refers to import processes. List one or multiple URLs, where LanguageTool-instances can be reached for segment target text spell checking. Translate5 does a load balancing, if more than one is configured.',2,'Spell-, grammar and style check service URLs for import','Editor: QA',''),
(411,'runtimeOptions.plugins.SpellCheck.languagetool.url.default',1,'editor','plugins','[\"http://yourlanguagetooldomain:8081/v2\"]','[\"http://localhost:8081/v2\"]','','list',NULL,'List of available LanguageTool-URLs. At least one available URL must be defined. Example: [\"http://localhost:8081/v2\"]',1,'Spell-, grammar and style check service default URL','Editor: QA','deprecated'),
(412,'runtimeOptions.worker.editor_Plugins_SpellCheck_Worker_Import.maxParallelWorkers',1,'editor','worker','2','1','','integer',NULL,'Max parallel running workers of the spellCheck import worker',1,NULL,NULL,NULL),
(413,'runtimeOptions.autoQA.enableSegmentSpellCheck',1,'editor','system','1','1','','boolean',NULL,'If activated (default), AutoQA covers checking the segments against spell-, grammar- and style-checks, provided by LanguageTool',8,'Enables segment spell checks','Editor: QA',''),
(414,'runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName',1,'editor','editor','okapi_default_import.bconf','okapi_default_import.bconf','','string',NULL,'Name of the default Okapi import-configuration file.',8,'Okapi import: Default import bconf file name','System setup: General',NULL),
(415,'runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName',1,'editor','editor','okapi_default_export.bconf','okapi_default_export.bconf','','string',NULL,'Name of the default Okapi export-configuration file.',8,'Okapi export: Default export bconf file name','System setup: General',NULL),
(416,'runtimeOptions.plugins.Okapi.dataDir',1,'editor','system','../data/editorOkapiBconf','../data/editorOkapiBconf','','absolutepath',NULL,'Pfad zu einem vom WebServer beschreibbaren, über htdocs nicht erreichbaren Verzeichnis, in diesem werden die Bconf-Dateien für Task-Importe gehalten.',1,NULL,NULL,NULL),
(417,'runtimeOptions.plugins.Okapi.server',1,'editor','plugins','','','','map','editor_Plugins_Okapi_DbConfig_OkapiConfigType','Available okapi instances with unique names. Do not change the name after the instance is assigned to a task.',2,'Okapi longhorn available instances','System setup: General',NULL),
(418,'runtimeOptions.plugins.Okapi.serverUsed',1,'editor','plugins','','','','string',NULL,'Okapi server used for the a task. All available values are automatically generated out of the runtimeOptions.plugins.Okapi.server config',8,'Okapi longhorn server used for a task','System setup: General',NULL),
(420,'runtimeOptions.authentication.ipbased.IpCustomerMap',1,'editor','system','{}','{}','','map',NULL,'List of ip addresses with map to customer for ip based authentication. Example where the users coming from 192.168.2.143 are assigned to customer with number 1000 :{\"192.168.2.143\" : \"1000\"}.',2,'IP-based authentication: IP to customer number mapping','System setup: Authentication',''),
(421,'runtimeOptions.authentication.ipbased.userRoles',1,'editor','system','[\"instantTranslate\",\"termCustomerSearch\"]','[\"instantTranslate\",\"termCustomerSearch\"]','instantTranslate,termCustomerSearch','list',NULL,'User roles that should be assigned to users, that authenticate via IP.',2,'IP-based authentication: Assigned roles','System setup: Authentication',''),
(422,'runtimeOptions.plugins.PangeaMt.requestTimeout',1,'editor','plugins','50','50','','integer',NULL,'Request timeout in seconds for PangeaMT.',2,'PangeaMT timeout','System setup: Language resources',''),
(423,'runtimeOptions.worker.MittagQI\\Translate5\\LanguageResource\\Pretranslation\\BatchCleanupWorker.maxParallelWorkers',1,'editor','worker','1','1','','integer',NULL,'Max parallel running workers for batch clean up',1,NULL,NULL,NULL);

--
-- Table structure for table `Zf_dbversion`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_dbversion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `filename` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `md5` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `appVersion` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=826 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_dbversion`
--

INSERT INTO `Zf_dbversion` VALUES
(1,'zfextended','001-mysql-invalid-login.sql','93c0fe35336737e24f0721bf7e97a4bc','INITIAL','2014-12-12 09:09:59'),
(2,'zfextended','002-mysql-users.sql','47f4e9cd20054a5060a2e3d8b4e40d36','INITIAL','2014-12-12 09:09:59'),
(3,'zfextended','003-mysql-passwd-reset.sql','95a322e43b665434d854ebdc76172d0b','INITIAL','2014-12-12 09:09:59'),
(4,'zfextended','004-mysql-users-passwd-changes.sql','cd89b5be270a26299e250f3a50106d12','INITIAL','2014-12-12 09:09:59'),
(5,'zfextended','005-mysql-users-editable.sql','7bd25f9884a02018b62a9495ac447699','INITIAL','2014-12-12 09:09:59'),
(6,'zfextended','006-mysql-TRANSLATE-170.sql','c9c2dc5627f41dec1ed090b3be33846c','INITIAL','2014-12-12 09:09:59'),
(7,'zfextended','007-mysql-TRANSLATE-191.sql','d44bd62ecab65808c82b968bc31d0631','INITIAL','2014-12-12 09:09:59'),
(8,'zfextended','008-mysql-TRANSLATE-123.sql','d8721ffb508f722d1c3b6ebd63f22f79','INITIAL','2014-12-12 09:09:59'),
(9,'zfextended','009-mysql-TRANSLATE-123-initial-config.sql','b194edf26c0089a83963644591d59133','INITIAL','2014-12-12 09:09:59'),
(10,'zfextended','010-mysql-TRANSLATE-130-dbversion.sql','a457c380e8f01a086e7b8e8fb1a29874','INITIAL','2014-12-12 09:09:59'),
(11,'zfextended','011-mysql-TRANSLATE-22-worker.sql','d64e70fea89beda20107805dd8a0b498','INITIAL','2014-12-12 09:09:59'),
(12,'zfextended','012-mysql-TRANSLATE-22-plugin-config.sql','42c8e51a19816f4d0013ce43e413f79a','INITIAL','2014-12-12 09:09:59'),
(13,'zfextended','013-mysql-TRANSLATE-531-disable-mail-config.sql','55c469773bfe5d7b0221b89a0d09c274','INITIAL','2015-07-10 09:59:59'),
(14,'default','001-create-db-and-session.sql','d09635adfad00f19136a250c2f13c894','INITIAL','2014-12-12 09:09:59'),
(15,'default','002-create-sessionMapInternalUniqId.sql','1c0056a5b1a2a75e9224987fc42c3dbc','INITIAL','2014-12-12 09:09:59'),
(16,'default','003-fill-LEK-languages-after-editor-sql.sql','73c408c210c9f2c218d21b91f25315e5','INITIAL','2014-12-12 09:09:59'),
(17,'default','004-translate5-mysql-dbversion.sql','e9100524968d82a0c8e5fa1dba88dc81','INITIAL','2014-12-12 09:09:59'),
(18,'default','005-translate5-mysql-TRANSLATE-123-ACL.sql','210f37a9a35a64feb5d9401289527bb9','INITIAL','2014-12-12 09:09:59'),
(19,'default','006-translate5-mysql-TRANSLATE-123-config-updates.sql','9a49b147405aa1a0db27164e847281df','INITIAL','2014-12-12 09:09:59'),
(20,'default','007-mysql-TRANSLATE-277.sql','c63a46757823ed93b8a9e7584ab212f1','INITIAL','2014-12-12 09:09:59'),
(21,'default','008-mysql-TRANSLATE-322.sql','a17a9a21a23bb3522da54daf2a708733','INITIAL','2014-12-12 09:09:59'),
(22,'default','009-mysql-TRANSLATE-22-worker-ACL.sql','37de8304cae593e3ab9c809023385b32','INITIAL','2014-12-12 09:09:59'),
(23,'editor','001-editor-mssql.sql','7c2a06b5a0c05ad73c7d5b6af529481d','INITIAL','2014-12-12 09:09:59'),
(24,'editor','001-editor-mysql.sql','940b1dfc8cbca2a4a5cb64f35dc6696a','INITIAL','2014-12-12 09:09:59'),
(25,'editor','002-editor-mssql-addforeignKeys.sql','4cc6896a66d94230c8fc03e9a3183433','INITIAL','2014-12-12 09:09:59'),
(26,'editor','002-editor-mysql-addforeignKeys.sql','5e4bf1014da996bdba4925f91f5567cd','INITIAL','2014-12-12 09:09:59'),
(27,'editor','003-editor-mssql-add-2-columns-to-LEK_segments.sql','cc3b673308f181ea30684dfac3548ae7','INITIAL','2014-12-12 09:09:59'),
(28,'editor','003-editor-mysql-add-2-columns-to-LEK_segments.sql','72932ea2eafd812f01a3684f98eac537','INITIAL','2014-12-12 09:09:59'),
(29,'editor','004-editor-mssql-change-ntext-to-varchar-max.sql','f1bcfdd32914507c7b889efb4caee870','INITIAL','2014-12-12 09:09:59'),
(30,'editor','004-editor-mysql-change-ntext-to-varchar-max.sql','0b7af0b56abc5dd232b344370233c209','INITIAL','2014-12-12 09:09:59'),
(31,'editor','005-editor-mssql-skeletonfile-as-blob.sql','b756bfe9693fd46722d64ace06044cb5','INITIAL','2014-12-12 09:09:59'),
(32,'editor','005-editor-mysql-skeletonfile-as-blob.sql','a8846c8413fc0255a5b3ca1e786b47ac','INITIAL','2014-12-12 09:09:59'),
(33,'editor','006-editor-mssql-change-toSort-to-max.sql','ebe13ab92270c4c9b45479b2949ed456','INITIAL','2014-12-12 09:09:59'),
(34,'editor','006-editor-mysql-change-toSort-to-300.sql','63ba5de72ec25999e14a56e006ef18f1','INITIAL','2014-12-12 09:09:59'),
(35,'editor','007-editor-mssql-terminologie.sql','7282f2a53a5f05397a2bd534f85280b3','INITIAL','2014-12-12 09:09:59'),
(36,'editor','007-editor-mysql-terminologie.sql','b7479aff89d870bc0f802b34064456fa','INITIAL','2014-12-12 09:09:59'),
(37,'editor','007z-TESTDATA-Generator.sql','c54d6a0d5cf273820df58cddcd31f664','INITIAL','2014-12-12 09:09:59'),
(38,'editor','008-editor-mssql-task-table-and-keys.sql','c6cda505c40602888111aae5c50c615d','INITIAL','2014-12-12 09:09:59'),
(39,'editor','008-editor-mysql-task-table-and-keys.sql','353ca22f99b673bb7839917aed812d5d','INITIAL','2014-12-12 09:09:59'),
(40,'editor','009-CHANGE_EXISTING_LANGUAGES_TABLE_editor-mysql-languages.sql','a3933e69d226e2874279febe0321791f','INITIAL','2014-12-12 09:09:59'),
(41,'editor','009-editor-mysql-languages.sql','d1d89ce8a6e56d66299f1ba1d5e002df','INITIAL','2014-12-12 09:09:59'),
(42,'editor','010-editor-mysql-Tree-to-MediumText-Delete-AutostatusId.sql','be81607af5fc71938fc24ec74e416766','INITIAL','2014-12-12 09:09:59'),
(43,'editor','011-editor-mysql-LEK_terms-language-to-int.sql','e9c44f046ceab8bc863b48fecacaa2d0','INITIAL','2014-12-12 09:09:59'),
(44,'editor','012-editor-mysql-rename-termId-to-mid.sql','fc81bec6544793e0a4e2df9fc0f15ec6','INITIAL','2014-12-12 09:09:59'),
(45,'editor','013-editor-mysql-termbugfix.sql','652a16adf94c15dfcd4058a01ef4af9d','INITIAL','2014-12-12 09:09:59'),
(46,'editor','014-editor-mysql-relaisLanguage.sql','4eaedaae6639fae43a32f497e8501db8','INITIAL','2014-12-12 09:09:59'),
(47,'editor','015-editor-mysql-reference-files.sql','582c1d47bdf697f4b3245866e40ca4bf','INITIAL','2014-12-12 09:09:59'),
(48,'editor','016-editor-mysql-qmsubsegments.sql','5ecbe564a8b2e2354afe8af8a0337ed1','INITIAL','2014-12-12 09:09:59'),
(49,'editor','017-editor-mysql-changeSegmentIdToVarchar.sql','99b0ab7c4c6372ba01bfc5cd84e3256f','INITIAL','2014-12-12 09:09:59'),
(50,'editor','018-editor-mysql-editable-source-column.sql','d81c7fa1316fc249787619ee3a84730f','INITIAL','2014-12-12 09:09:59'),
(51,'editor','019-editor-mysql-add-segmentNr-column.sql','1eb14ee2050ff9a8f5462406dd0a29da','INITIAL','2014-12-12 09:09:59'),
(52,'editor','020-editor-mysql-comment.sql','babf761524f5aa0724265d00b59658fa','INITIAL','2014-12-12 09:09:59'),
(53,'editor','021-editor-mysql-taskoverview.sql','2ddc90ce9861a25780e391945909f043','INITIAL','2014-12-12 09:09:59'),
(54,'editor','022-editor-mysql-csv-import.sql','6c68881669149263338f2fdea2b8b9b2','INITIAL','2014-12-12 09:09:59'),
(55,'editor','023-editor-mysql-taskUserAssoc.sql','9a86501e61443129c049ee733d36d8ef','INITIAL','2014-12-12 09:09:59'),
(56,'editor','024-editor-mysql-logging.sql','a038dc99371e94df98a465a1509739b7','INITIAL','2014-12-12 09:09:59'),
(57,'editor','025-editor-mysql-exportRunning.sql','fc098c0d01243f1e6972def4530d46aa','INITIAL','2014-12-12 09:09:59'),
(58,'editor','026-system-user.sql','7bc838ed7fabce08e2c2bece0843c0f9','INITIAL','2014-12-12 09:09:59'),
(59,'editor','027-editor-mysql-workflow-update.sql','3065fa608750f510c5b2f1058aa8f9a8','INITIAL','2014-12-12 09:09:59'),
(60,'editor','028-editor-mysql-migration.sql','abca6380e3a3cc02da67e04686b946ed','INITIAL','2014-12-12 09:09:59'),
(61,'editor','029-editor-mysql-taskoverview-filter.sql','7a95355169eec6b106d82f08631f6d62','INITIAL','2014-12-12 09:09:59'),
(62,'editor','030-editor-mysql-taskUserAssoc-used.sql','f1cbba2a5a2396842fb8997ccfe35820','INITIAL','2014-12-12 09:09:59'),
(63,'editor','031-editor-mysql-TRANSLATE-118-alternates.sql','64c8a3c2963424a480373e616965d96b','INITIAL','2014-12-12 09:09:59'),
(64,'editor','031-editor-mysql-TRANSLATE-148.sql','242a49678fac62ce9dab7e6b031e6c8a','INITIAL','2014-12-12 09:09:59'),
(65,'editor','032-editor-mysql-TRANSLATE-118-migration.sql','0391aa30a8a385adfe29177247ced152','INITIAL','2014-12-12 09:09:59'),
(66,'editor','033-editor-mysql-TRANSLATE-118-fixes.sql','e66d596351910c2eeefaaed4247cedef','INITIAL','2014-12-12 09:09:59'),
(67,'editor','034-editor-mysql-TRANSLATE-167.sql','3e5e7cafde03b93852ce7e856af2681e','INITIAL','2014-12-12 09:09:59'),
(68,'editor','035-editor-mysql-TRANSLATE-158.sql','f9bdccfca5adb3317d40eb8f6e979cbf','INITIAL','2014-12-12 09:09:59'),
(69,'editor','036-editor-mysql-TRANSLATE-113.sql','8c43b0e13247a5eb0c8caf7ac57765fa','INITIAL','2014-12-12 09:09:59'),
(70,'editor','037-editor-mysql-TRANSLATE-113.sql','423c19e4768230f43645f6d0f3ad0223','INITIAL','2014-12-12 09:09:59'),
(71,'editor','039-editor-mysql-TRANSLATE-123-ACL.sql','b639e1f3143d7f5bc03d2ce6038957c8','INITIAL','2014-12-12 09:09:59'),
(72,'editor','040-editor-mysql-TRANSLATE-22-TermTagger-config-inserts.sql','8675fd07a855af65a58a2ddb4dd90ffb','INITIAL','2014-12-12 09:09:59'),
(73,'editor','040-editor-mysql-TRANSLATE-318.sql','51285cdfe212962aac127a85e7b6d0e5','INITIAL','2014-12-12 09:09:59'),
(74,'editor','041-editor-mysql-TRANSLATE-317.sql','ceedf95cf9ebe05b858e365a9d0045f4','INITIAL','2014-12-12 09:09:59'),
(75,'editor','042-editor-mysql-remove-preloadImages.sql','afbc8ff26f1cc5e8e961852e6b31303f','INITIAL','2014-12-12 09:09:59'),
(76,'editor','043-editor-mysql-TRANSLATE-311-plugin_segmentstatistics.sql','2be200d0a81e971f31646c44830dab98','INITIAL','2014-12-12 09:09:59'),
(77,'editor','044-editor-mysql-TRANSLATE-308-segment-meta.sql','108021ad28b3a4f4921ea46b7a5ddeb5','INITIAL','2014-12-12 09:09:59'),
(78,'editor','045-editor-mysql-TRANSLATE-301-task-meta.sql','044f047f1a3ad6d9eb9450c42f861d5a','INITIAL','2014-12-12 09:09:59'),
(79,'editor','045-editor-mysql-TRANSLATE-303-plugin-transit-import.sql','00d1557388a0daef7ae959914726df74','INITIAL','2014-12-12 09:09:59'),
(80,'editor','046-editor-mysql-TRANSLATE-22-task-import-state.sql','788f10071e3c89067f608f58b19f9eaa','INITIAL','2014-12-12 09:09:59'),
(81,'editor','046-editor-mysql-TRANSLATE-306-lockSegmentsBasedOnConfig.sql','55613eb0867004c788d27a09bd48b6c4','INITIAL','2014-12-12 09:09:59'),
(82,'editor','047-editor-mysql-TRANSLATE-305-state-default.sql','f2f449fb6c5c23bef1c6828d4d17a2e4','INITIAL','2014-12-12 09:09:59'),
(83,'editor','048-editor-mysql-TRANSLATE-333-disableQM-config-inserts.sql','5b67f0542afaa88e0ac1f6e6542b6c85','INITIAL','2014-12-12 09:09:59'),
(84,'editor','049-editor-mysql-TRANSLATE-22-TermTagger-cleanup.sql','d837ec8403dc24e55be583e0e8eeef8a','INITIAL','2014-12-12 09:09:59'),
(85,'editor','050-editor-mysql-TRANSLATE-22-initial-ACL.sql','d32f3aaa37a6bf34848803b7a7cdc0ca','INITIAL','2014-12-12 09:09:59'),
(86,'editor','051-editor-mysql-TRANSLATE-22-modify-Zf_worker.sql','7c304e1d5d80707e898f901e0b30207a','INITIAL','2014-12-12 09:09:59'),
(87,'editor','052-editor-mysql-TRANSLATE-311-plugin_segmentstatistics.sql','0e9daa19b5c0256804d032636912a1cc','UPDATED','2014-12-12 15:35:11'),
(88,'editor','053-editor-mysql-TRANSLATE-367-plugin_segmentstatistics_1.sql','c9dc41ac8cbbfc321278a1f21a890bcc','UPDATED','2015-01-19 10:34:55'),
(89,'editor','054-editor-mysql-TRANSLATE-372-escape-entities-in-table-LEK_terms.sql','2fb1e30b87be4c1fcb280f6a6e38f554','UPDATED','2015-01-19 10:34:58'),
(90,'editor','054-editor-mysql-TRANSLATE-389-FK-zfworker2task-for-autodelete.sql','69031d9300bdab09b74f0dc61a162720','UPDATED','2015-02-16 07:51:38'),
(91,'editor','056-editor-mysql-TRANSLATE-399.sql','1ac4b27e6c02d66be13619d0276eb9cb','UPDATED','2015-02-16 07:51:38'),
(92,'editor','043-editor-mysql-TRANSLATE-328.sql','10ee49b24d72fbf72daebd26f469c141','INITIAL','2015-02-16 07:51:44'),
(93,'editor','038-editor-mysql-TRANSLATE-217.php','875f8d90fc45c98861aedd5542888139','INITIAL','2015-02-19 17:04:42'),
(94,'editor','055-editor-mysql-TRANSLATE-391.php','e38f259669d51adbba6aa617fbdc85a4','INITIAL','2015-02-19 17:16:17'),
(95,'editor','057-editor-mysql-TRANSLATE-453.sql','c29a439c5a8de222f808359000859e13','UPDATED','2015-03-04 13:17:48'),
(96,'editor','058-editor-mysql-TRANSLATE-1-installer.sql','55c474617654d09b3f5cdf87abdd778d','UPDATED','2015-03-12 07:15:22'),
(97,'editor','038-editor-mysql-TRANSLATE-217.php','875f8d90fc45c98861aedd5542888139','INITIAL','2015-03-16 07:35:51'),
(98,'editor','055-editor-mysql-TRANSLATE-391.php','e38f259669d51adbba6aa617fbdc85a4','INITIAL','2015-03-16 07:35:53'),
(99,'editor','058-editor-mysql-TRANSLATE-1-installer.sql','55c474617654d09b3f5cdf87abdd778d','INITIAL','2015-03-16 07:35:53'),
(100,'default','009-mysql-testimonials.sql','efa563df2d88dd8c3c35d54760bc9261','UPDATED','2015-03-16 07:35:56'),
(101,'zfextended','014-mysql-TRANSLATE-625-import-workers.sql','84fed4dec3c67c2716acecee0845c6d2','UPDATED','2018-01-18 14:01:33'),
(102,'zfextended','015-mysql-TRANSLATE-137-Maintenance-mode.sql','1976333e9c8ace3ad331b5f832a5a613','UPDATED','2018-01-18 14:01:33'),
(103,'zfextended','016-mysql-TRANSLATE-750-API-auth-default-locale.sql','87a1fb8751040c4f2e420ed82dcc773d','UPDATED','2018-01-18 14:01:33'),
(104,'zfextended','017-mysql-TRANSLATE-759-browser-language.sql','dce50325154e5bef5762bcac786abbd4','UPDATED','2018-01-18 14:01:33'),
(105,'zfextended','018-mysql-TRANSLATE-768-missing-table.sql','9f1cfaca4265b592e6c9d98c9be7b877','UPDATED','2018-01-18 14:01:34'),
(106,'zfextended','019-TRANSLATE-877-separate-worker-URL.sql','eb4bb31ac2496fa9b3a77d99410cacad','UPDATED','2018-01-18 14:01:34'),
(107,'zfextended','020-TRANSLATE-1084-intial-user-locales.sql','4ccde5129e63ec068f42383a9d617d97','UPDATED','2018-01-18 14:01:34'),
(108,'zfextended','020-editor-mysql-TRANSLATE-943.sql','ba1834242de7cfa442dd574c17571925','UPDATED','2018-01-18 14:01:34'),
(109,'zfextended','021-editor-mysql-TRANSLATE-950.sql','592fee3d152733d3f80abefb4f90134f','UPDATED','2018-01-18 14:01:34'),
(110,'zfextended','022-mysql-TRANSLATE-940.sql','dd824f98590e2ef8c43632c647cab6c6','UPDATED','2018-01-18 14:01:34'),
(111,'default','010-mysql-downloads.sql','53525b0822d8112175b587ce0b4f33bb','UPDATED','2018-01-18 14:01:34'),
(112,'default','011-mysql-main-menu.sql','ec5dbefd33122fb0df479c023e097c24','UPDATED','2018-01-18 14:01:34'),
(113,'default','011-mysql-update-info-acl.sql','9f10bfba9dacca147ed904c7d21a6d93','UPDATED','2018-01-18 14:01:34'),
(114,'default','012-mysql-license-acl.sql','a48dfbde615be358c73ca41607735c67','UPDATED','2018-01-18 14:01:34'),
(115,'default','013-mysql-main-menu-update.sql','59b62752cb681fa6e5d7c89f88da282a','UPDATED','2018-01-18 14:01:34'),
(116,'default','014-mysql-TRANSLATE-664-help-pages.sql','27e48426161685ed393cbd11a8ce09ef','UPDATED','2018-01-18 14:01:34'),
(117,'default','015-sql-changelog-2016-09-27.sql','28e9f47fed82fa73eb9eb46fd8583c91','UPDATED','2018-01-18 14:01:34'),
(118,'default','016-change-menu.php','15465e84d318a4f79df863f59ab77aee','UPDATED','2018-01-18 14:01:34'),
(119,'default','016-sql-changelog-2016-10-17.sql','a493cad52c52b550f0586a89ddfe33f7','UPDATED','2018-01-18 14:01:34'),
(120,'default','017-mysql-TRANSLATE-1073-Update-LEK_languages.sql','306eaa10994a48c40d1de21477e90050','UPDATED','2018-01-18 14:01:34'),
(121,'editor','059-editor-mysql-TRANSLATE-22-TermTagger-cleanup.sql','82dccae2248b23150bcddc5f8c9864a6','UPDATED','2018-01-18 14:01:34'),
(122,'editor','060-editor-mysql-TRANSLATE-468-segmentstatistics-on-export.sql','0733a6a14253d1e201b5c5b89a534a6c','UPDATED','2018-01-18 14:01:34'),
(123,'editor','061-editor-mysql-TRANSLATE-478-transit-export-config.sql','dca882950f0de77eedbae9e8f9dfde60','UPDATED','2018-01-18 14:01:34'),
(124,'editor','062-editor-mysql-TRANSLATE-479-term-definition.sql','41f28fce159428e7f10ec6b396a240c3','UPDATED','2018-01-18 14:01:34'),
(125,'editor','063-editor-mysql-TERMTAGGER-50-transit-export-config.sql','f2e2495f94471b02cbe251d1e88604e4','UPDATED','2018-01-18 14:01:34'),
(126,'editor','064-editor-mysql-TRANSLATE-403.php','b41f67302319852ab90f5c7a342a2b7d','UPDATED','2018-01-18 14:01:34'),
(127,'editor','065-editor-mysql-segment-history-id.sql','3cf0a887e16af41fed0f3b52c4e48a1a','UPDATED','2018-01-18 14:01:34'),
(128,'editor','066-editor-mysql-termtagger-switch-off.sql','de7c5f824492a5e765bf68b09438a83a','UPDATED','2018-01-18 14:01:35'),
(129,'editor','067-editor-mysql-worker-chain-dependency.sql','90fc97704781ee66cf3b1397860b1fd9','UPDATED','2018-01-18 14:01:35'),
(130,'editor','068-editor-mysql-admin-role.sql','9aa0119480b119bfe206da66de7047dc','UPDATED','2018-01-18 14:01:35'),
(131,'editor','069-editor-mysql-TRANSLATE-537.sql','f7295ab76e438134dd00be1061cc0b4c','UPDATED','2018-01-18 14:01:35'),
(132,'editor','070-editor-mysql-TRANSLATE-541.sql','acb1c6664afb4b3e309fa75ef3092dd2','UPDATED','2018-01-18 14:01:35'),
(133,'editor','071-editor-mysql-TRANSLATE-526.sql','0ff213fab97b4300d87625933ee01368','UPDATED','2018-01-18 14:01:35'),
(134,'editor','072-editor-mysql-TRANSLATE-562-taskmeta.sql','3ab95248a350b243546c7f9f20304cfc','UPDATED','2018-01-18 14:01:35'),
(135,'editor','073-editor-mysql-TRANSLATE-491.sql','13049a3f8e83a2288874dfcf6f952ba4','UPDATED','2018-01-18 14:01:35'),
(136,'editor','074-editor-mysql-change-korean-to-ISO-639.sql','5d65043f2dcb44e4c79819556f299222','UPDATED','2018-01-18 14:01:35'),
(137,'editor','074-editor-mysql-config-controller.sql','97838f373a95ff2d1e3b86df7f967b1e','UPDATED','2018-01-18 14:01:35'),
(138,'editor','075-editor-mysql-TRANSLATE-216.sql','62bab86c8779c0647fd100dfaa37a03c','UPDATED','2018-01-18 14:01:35'),
(139,'editor','075-editor-mysql-TRANSLATE-614.sql','78075d1231b30c8e4bc2f08888f2c405','UPDATED','2018-01-18 14:01:35'),
(140,'editor','076-editor-mysql-BEOSPHERE-60.sql','3154ab53f5c91ccdef5543c85c22b440','UPDATED','2018-01-18 14:01:35'),
(141,'editor','077-editor-mysql-TRANSLATE-640.sql','569f5a9618cc47b6cf5ada46adea1814','UPDATED','2018-01-18 14:01:35'),
(142,'editor','078-editor-mysql-TRANSLATE-635.sql','d3057eb559a1f55756c1f564d9ba7b21','UPDATED','2018-01-18 14:01:35'),
(143,'editor','079-editor-mysql-TRANSLATE-586.sql','180c36153135fd0d0a18ddb7e2588bc0','UPDATED','2018-01-18 14:01:35'),
(144,'editor','080-editor-mysql-TRANSLATE-631.sql','6e0fc498afd8723e24ec5cc24ad441a4','UPDATED','2018-01-18 14:01:35'),
(145,'editor','081-editor-mysql-TRANSLATE-679.sql','b11c77b466a7dd8d79644ef6c4ae5e3a','UPDATED','2018-01-18 14:01:35'),
(146,'editor','082-editor-mysql-TRANSLATE-631.sql','a23a9362f6aadaaae913d12ac4e9eb6b','UPDATED','2018-01-18 14:01:35'),
(147,'editor','082-editor-mysql-TRANSLATE-684.sql','a31bf85bf7932f8ea983ce39d3216996','UPDATED','2018-01-18 14:01:35'),
(148,'editor','083-editor-mysql-TRANSLATE-684.php','0260c7d60d54a6df6b4ccce9309b2c81','UPDATED','2018-01-18 14:01:35'),
(149,'editor','084-editor-mysql-TRANSLATE-700.sql','97f82a49d9ceed345227a93f00662e43','UPDATED','2018-01-18 14:01:36'),
(150,'editor','085-editor-mysql-TRANSLATE-646.php','57a946495433284dde8137c12890ea74','UPDATED','2018-01-18 14:01:36'),
(151,'editor','086-editor-mysql-TRANSLATE-625.sql','a3490a1a58bb9bddeed955a349dd3cb1','UPDATED','2018-01-18 14:01:36'),
(152,'editor','087-editor-mysql-TRANSLATE-718.sql','d8345b8665b97c0950fcf2784fd25a8d','UPDATED','2018-01-18 14:01:36'),
(153,'editor','088-editor-mysql-TRANSLATE-612.sql','c627a73c53f602f0aa74ea1af89af9e8','UPDATED','2018-01-18 14:01:36'),
(154,'editor','089-editor-mysql-TRANSLATE-637.sql','95b46e4b488f7592b2a7b5a51ce207e2','UPDATED','2018-01-18 14:01:36'),
(155,'editor','089-editor-mysql-TRANSLATE-664.sql','6ea7c1d28541f585ad9c02a13449aa7a','UPDATED','2018-01-18 14:01:36'),
(156,'editor','090-editor-mysql-TRANSLATE-664-2.sql','3f1e4632cad6408b6d17a9a5c96d4464','UPDATED','2018-01-18 14:01:36'),
(157,'editor','091-editor-mysql-TRANSLATE-137.sql','219f629b2dbb280e2e7f5ac4b29127ec','UPDATED','2018-01-18 14:01:36'),
(158,'editor','092-editor-mysql-TRANSLATE-726.sql','fdb426b5510056d85769a302951a5e02','UPDATED','2018-01-18 14:01:36'),
(159,'editor','093-editor-mysql-CHANGELOG-2016-10-26.sql','84f355e7bcf7e91d72d43b7d2249679c','UPDATED','2018-01-18 14:01:36'),
(160,'editor','094-editor-mysql-CHANGELOG-2016-11-03.sql','cda687867e3519334c9009dce0c5d84a','UPDATED','2018-01-18 14:01:36'),
(161,'editor','095-editor-mysql-enable-new-plugins.sql','bef2d660be6712d6bcd2a91bd2d9199b','UPDATED','2018-01-18 14:01:36'),
(162,'editor','096-editor-mysql-CHANGELOG-2017-01-19.sql','d4e9c59827330721c2d097ac4f108a3c','UPDATED','2018-01-18 14:01:36'),
(163,'editor','097-editor-mysql-TRANSLATE-818-for-selected-tasks.php','813b532746f9e5b2c5d522fb021bf27d','UPDATED','2018-01-18 14:01:36'),
(164,'editor','097-editor-mysql-TRANSLATE-818.php','659a00333566b62abe445c9f65ced282','UPDATED','2018-01-18 14:01:36'),
(165,'editor','097-editor-mysql-TRANSLATE-833.sql','7a889e53c9ce62cc5394016bebcbc976','UPDATED','2018-01-18 14:01:36'),
(166,'editor','098-editor-mysql-TRANSLATE-807.sql','bd64b778c10288be3d7dac70c382c673','UPDATED','2018-01-18 14:01:36'),
(167,'editor','099-editor-mysql-TRANSLATE-821.sql','e0bad1b838d74e8df46cf93d9acf4aac','UPDATED','2018-01-18 14:01:36'),
(168,'editor','100-editor-mysql-TRANSLATE-823.sql','5e6242fcb12f4f139d746586d11d8248','UPDATED','2018-01-18 14:01:36'),
(169,'editor','101-editor-mysql-TRANSLATE-878.sql','33b1037faebdc101afc4aade2be7f07b','UPDATED','2018-01-18 14:01:36'),
(170,'editor','102-editor-mysql-TRANSLATE-885.sql','f5f798553fb730e00f290dabc881f275','UPDATED','2018-01-18 14:01:36'),
(171,'editor','103-editor-mysql-TRANSLATE-628.sql','2ed8998a48437d10a3cb8293885ca51b','UPDATED','2018-01-18 14:01:36'),
(172,'editor','103-editor-mysql-TRANSLATE-885.sql','4e7e5052b2c435706f3a7e4d3d2187fd','UPDATED','2018-01-18 14:01:36'),
(173,'editor','104-editor-mysql-TRANSLATE-878.sql','d957d43c68b1ec62f3194a5f39cfd17f','UPDATED','2018-01-18 14:01:36'),
(174,'editor','105-editor-mysql-TRANSLATE-911.sql','15790ff978a712a8c18fc76aa3200523','UPDATED','2018-01-18 14:01:36'),
(175,'editor','106-editor-mysql-TRANSLATE-955-xlf-whitespace-preserve.sql','7d92e0458d4210a60188709da2c6f7c2','UPDATED','2018-01-18 14:01:36'),
(176,'editor','107-editor-mysql-TRANSLATE-925-xliff-import.sql','d72542d4d1a216db0382c3b1fcacbb93','UPDATED','2018-01-18 14:01:36'),
(177,'editor','108-editor-mysql-TRANSLATE-926-extjs-update.sql','f61c1c743c2e2aa85146fd7b5ea09e12','UPDATED','2018-01-18 14:01:36'),
(178,'editor','109-editor-mysql-TRANSLATE-1014-file-filters.sql','1de96a67f14f939767ecdf0bd191e026','UPDATED','2018-01-18 14:01:37'),
(179,'editor','110-editor-mysql-TRANSLATE-984.php','021655f55954a3c4f8beff756972a421','UPDATED','2018-01-18 14:01:37'),
(180,'editor','111-editor-mysql-TRANSLATE-1012-improve-task-REST-API.sql','f4eeef8547905378bf35a60cc74b172d','UPDATED','2018-01-18 14:01:37'),
(181,'editor','112-editor-mysql-TRANSLATE-1013-static-login-link.sql','217fa5d7031f7fe745912874e0c9e0f5','UPDATED','2018-01-18 14:01:37'),
(182,'editor','114-editor-mysql-TRANSLATE-1024-email-on-user-task-association.sql','f21ec6aa30606622ad6a312fb9fa0d13','UPDATED','2018-01-18 14:01:37'),
(183,'editor','114-editor-mysql-TRANSLATE-935.sql','563b491ee876c95f78885e8f967e74b3','UPDATED','2018-01-18 14:01:37'),
(184,'editor','115-editor-mysql-TRANSLATE-1047-fix-file-filters-engine.sql','eb06953a76c41510ab11ad628344da98','UPDATED','2018-01-18 14:01:37'),
(185,'editor','116-editor-mysql-TRANSLATE-1027-translation-step.sql','f0f1c0fdbe0057111863351059202fed','UPDATED','2018-01-18 14:01:37'),
(186,'editor','117-editor-mysql-TRANSLATE-822-segment-min-max-length.sql','980a4e4d16b50dc0f0b1b6085422319d','UPDATED','2018-01-18 14:01:37'),
(187,'editor','118-editor-mysql-TRANSLATE-931-ignore-tag-validation.sql','a69e378f1b60bdfc473954edeae57251','UPDATED','2018-01-18 14:01:37'),
(188,'editor','119-editor-mysql-TRANSLATE-1057-workflow-action-matrix.sql','66c91d702fcddcc7a4bca1dbd9e7203b','UPDATED','2018-01-18 14:01:37'),
(189,'editor','119-editor-mysql-TRANSLATE-941.sql','cf7411e31264b49149f4f5f0c20aeb50','UPDATED','2018-01-18 14:01:37'),
(190,'editor','119-mysql-editor-TRANSLATE-822-segment-min-max-length-config.sql','8b2ef601163c851c1bb776a4942dfd6e','UPDATED','2018-01-18 14:01:37'),
(191,'editor','120-editor-mysql-TRANSLATE-942.sql','1c9a56fc1794607613f0dd886fecc6b6','UPDATED','2018-01-18 14:01:37'),
(192,'editor','121-editor-mysql-TRANSLATE-941.sql','529d87f8f84fcd316a348211276b8279','UPDATED','2018-01-18 14:01:37'),
(193,'editor','121-editor-mysql-doCronDaily-finishOverduedTasks.sql','96a39150a9b65eed8fa33f5ce9b70447','UPDATED','2018-01-18 14:01:37'),
(194,'editor','122-editor-mysql-TRANSLATE-1024-email-on-user-task-association.sql','488fc0e6170f03f0558143eb2c258b19','UPDATED','2018-01-18 14:01:37'),
(195,'editor','123-editor-mysql-TRANSLATE-949.sql','9852caef4bc9364eb30aa6f94de53f70','UPDATED','2018-01-18 14:01:37'),
(196,'editor','124-editor-mysql-TRANSLATE-948.sql','ef4600bee80b4e5ed19adfc76bc919b2','UPDATED','2018-01-18 14:01:37'),
(197,'editor','125-editor-mysql-TRANSLATE-950.sql','42a8c400350c5e6d1d3ec9a6254e0bc7','UPDATED','2018-01-18 14:01:37'),
(198,'editor','126-editor-mysql-TRANSLATE-949.sql','f7da144580ce65d70d5a7534bf0d60de','UPDATED','2018-01-18 14:01:37'),
(199,'editor','sql-changelog-2017-03-29.sql','f96ade94eef71815c2ff0b3d676013be','UPDATED','2018-01-18 14:01:37'),
(200,'editor','sql-changelog-2017-04-05.sql','4ae06746ad8221f62368f6f02e6f5850','UPDATED','2018-01-18 14:01:37'),
(201,'editor','sql-changelog-2017-04-24.sql','2df19bfc61bfa0d6f526c296a45c91b9','UPDATED','2018-01-18 14:01:37'),
(202,'editor','sql-changelog-2017-05-29.sql','d53125b97ed1f2c5ec32d883c95fb1d4','UPDATED','2018-01-18 14:01:37'),
(203,'editor','sql-changelog-2017-06-13.sql','d0207dcfce66bb53930a0dccad4222e6','UPDATED','2018-01-18 14:01:37'),
(204,'editor','sql-changelog-2017-06-23.sql','38ae420e38ab2f6b27199b944d257a9f','UPDATED','2018-01-18 14:01:37'),
(205,'editor','sql-changelog-2017-07-04.sql','0d3cd43eb84c5aa007b1962c4e6477a9','UPDATED','2018-01-18 14:01:37'),
(206,'editor','sql-changelog-2017-07-11.sql','1c69363bf6a3f3c273d9ade064604fcd','UPDATED','2018-01-18 14:01:38'),
(207,'editor','sql-changelog-2017-08-07.sql','c0af054803d2315dd37f2fc5e6c24a2f','UPDATED','2018-01-18 14:01:38'),
(208,'editor','sql-changelog-2017-08-17.sql','4d5a9116d0466547f13d9c49137268f4','UPDATED','2018-01-18 14:01:38'),
(209,'editor','sql-changelog-2017-09-14.sql','32e11eb880e2bfb914c574f3b56ea5f6','UPDATED','2018-01-18 14:01:38'),
(210,'editor','sql-changelog-2017-10-16.sql','76bef1ed7270a338bf349b9b0532edb4','UPDATED','2018-01-18 14:01:38'),
(211,'editor','sql-changelog-2017-10-19.sql','0781c442618879c6648b0128d7a3a3bf','UPDATED','2018-01-18 14:01:38'),
(212,'editor','sql-changelog-2017-11-14.sql','5a596f15e0e76fd24ebf1173fcfb50ca','UPDATED','2018-01-18 14:01:38'),
(213,'editor','sql-changelog-2017-11-30.sql','8e573ebef5e9868d361b4e2725b6868b','UPDATED','2018-01-18 14:01:38'),
(214,'editor','sql-changelog-2017-12-06.sql','a78f59a7642e477e5bece767799c6621','UPDATED','2018-01-18 14:01:38'),
(215,'editor','sql-changelog-2017-12-11.sql','1c9ad0d9b0da04417258536ae94ec613','UPDATED','2018-01-18 14:01:38'),
(216,'editor','sql-changelog-2017-12-14.sql','5fa0f42a73457c9d6635dbd56da55f76','UPDATED','2018-01-18 14:01:38'),
(217,'editor','sql-changelog-2018-01-17.sql','d0fa066a2941c3944b1e5fd083679161','UPDATED','2018-01-18 14:01:38'),
(218,'LockSegmentsBasedOnConfig','001-editorPlugin-LockSegmentsBasedOnConfig-mysql.sql','7e1a56aba1ba1e665b2f2a427fb6b1d5','UPDATED','2018-01-18 14:01:38'),
(219,'LockSegmentsBasedOnConfig','002-editorPlugin-LockSegmentsBasedOnConfig-mysql.sql','03355ca3337b89227cd4cee375d4a600','UPDATED','2018-01-18 14:01:38'),
(220,'MtComparEval','001-editorPlugin-MTCompareEval-mysql.sql','f662f89f59aa0508e04480a9ab2269de','UPDATED','2018-01-18 14:01:38'),
(221,'SegmentStatistics','001-editorPlugin-SegmentStatistics-mysql.sql','0b2d2cf470aa09df8f32fdac1eb8a35f','UPDATED','2018-01-18 14:01:38'),
(222,'SegmentStatistics','002-editorPlugin-SegmentStatistics-mysql.sql','d534880afb9d9555bf341865df27cbde','UPDATED','2018-01-18 14:01:38'),
(223,'SegmentStatistics','003-editorPlugin-SegmentStatistics-mysql.sql','2b972b2f71b8b8e3e822a8206172fdaf','UPDATED','2018-01-18 14:01:38'),
(224,'SegmentStatistics','004-editorPlugin-SegmentStatistics-mysql.sql','e027cf1f4a5fad5e9a7c62b1a4f8fe13','UPDATED','2018-01-18 14:01:38'),
(225,'SegmentStatistics','005-editorPlugin-SegmentStatistics-mysql.sql','ec974b6a092d8f6df74e4b3f7ac84a2a','UPDATED','2018-01-18 14:01:38'),
(226,'SegmentStatistics','006-editorPlugin-SegmentStatistics-mysql.sql','d8aceec795fec552dbfd9754f2bbfd7c','UPDATED','2018-01-18 14:01:38'),
(227,'ChangeLog','001-editorPlugin-ChangeLog-mysql.sql','52d0fe2cca2b010118962ac8fa030b9d','UPDATED','2018-01-18 14:01:38'),
(228,'GlobalesePreTranslation','001-editorPlugin-GlobalesePreTranslation-mysql.sql','2259980a7aa7e5438279b6b1e1e99e18','UPDATED','2018-01-18 14:01:38'),
(229,'MatchResource','001-editorPlugin-MatchResource-mysql.sql','27b0fb3b1488bd5a4638d02518badbbf','UPDATED','2018-01-18 14:01:39'),
(230,'MatchResource','002-editorPlugin-MatchResource-mysql.sql','16ca6da97bfcee60d4b467a901a02ffb','UPDATED','2018-01-18 14:01:39'),
(231,'MatchResource','003-editorPlugin-MatchResource-mysql.sql','ca39ad29eb436cd06405f1245f6bbbeb','UPDATED','2018-01-18 14:01:39'),
(232,'MatchResource','004-editorPlugin-MatchResource-mysql.sql','4d2c8b7035837c1066e7d7447019c909','UPDATED','2018-01-18 14:01:39'),
(233,'MatchResource','005-editorPlugin-MatchResource-mysql.sql','1f9e1c6ef8a35705de0bdea9a1ba6098','UPDATED','2018-01-18 14:01:39'),
(234,'MatchResource','006-editorPlugin-MatchResource-mysql.sql','a3e102ff411d7958e9932da9c5f85005','UPDATED','2018-01-18 14:01:39'),
(235,'MatchResource','007-editorPlugin-MatchResource-mysql.sql','d230e02bdf706bdafab3609f07057ee5','UPDATED','2018-01-18 14:01:39'),
(236,'NoMissingTargetTerminology','001-editorPlugin-NoMissingTargetTerminology-mysql.sql','f1e348a14223230fc251432f1f2a2723','UPDATED','2018-01-18 14:01:39'),
(237,'NoMissingTargetTerminology','002-editorPlugin-NoMissingTargetTerminology-mysql.sql','dcc56184263a20d6885a7dc2cff46e98','UPDATED','2018-01-18 14:01:39'),
(238,'ArchiveTaskBeforeDelete','001-editorPlugin-ArchiveTaskBeforeDelete-mysql.sql','3d4e31b46f47024e2afbf7be60df2c24','UPDATED','2018-01-18 14:01:39'),
(239,'TermTagger','001-editorPlugin-TermTagger-mysql.sql','f66f7f1e45c95a2e17283dea2b913f2a','UPDATED','2018-01-18 14:01:39'),
(240,'TermTagger','002-editorPlugin-TermTagger-mysql.sql','f36a0d26bfd132480e987695a2e3f5bf','UPDATED','2018-01-18 14:01:39'),
(241,'Okapi','001-editorPlugin-Okapi-mysql.sql','4adeb2b39217dcff6930ab6cad820cc7','UPDATED','2018-01-18 14:01:39'),
(242,'editor','113-editor-mysql-TRANSLATE-1028-Correct-wrong-or-misleading-language-shortcuts.sql','a373eb105e008a78eba23a617e315973','UPDATED','2018-01-18 14:01:39'),
(243,'editor','109-editor-mysql-TRANSLATE-994-rtl-languages.sql','b14400e17cb4ece79f404ccbfc5e2289','UPDATED','2018-01-18 14:01:39'),
(244,'zfextended','023-mysql-TRANSLATE-137.sql','a13194a80d44313416da1aadb9ccbaed','UPDATED','2018-10-23 14:47:51'),
(245,'zfextended','024-mysql-TRANSLATE-392-memory-cache.sql','922b61dbb7a8591bdc6a7a181ed292d8','UPDATED','2018-10-23 14:47:51'),
(246,'default','018-mysql-main-menu-update.sql','6f33d7f3dde9ffb0e351fcef59e72026','UPDATED','2018-10-23 14:47:51'),
(247,'default','019-add-default-term-attribute-labels.sql','6f33d7f3dde9ffb0e351fcef59e72026','UPDATED','2018-10-23 14:47:51'),
(248,'default','020-mysql-main-menu-update.sql','5675965c7aadd0682cf1621dd4c50365','UPDATED','2018-10-23 14:47:51'),
(249,'editor','114-editor-mysql-TRANSLATE-1142-task-migration-tracker.sql','7a83e64d5be87bea7623f42fc896c515','UPDATED','2018-10-23 14:47:51'),
(250,'editor','114-editor-mysql-TRANSLATE-32-toSort-column-size.sql','46fa478f6494f48dd3b58021498cc081','UPDATED','2018-10-23 14:47:51'),
(251,'editor','115-editor-mysql-TRANSLATE-32.php','8672dc68e819bd5e795c805b3c22dfb2','UPDATED','2018-10-23 14:47:51'),
(252,'editor','127-editor-mysql-TRANSLATE-1019-file-import.sql','68f49ee6e259ca598174ec3694c9775f','UPDATED','2018-10-23 14:47:51'),
(253,'editor','127-editor-mysql-TRANSLATE-822-min-max-length-config-default-value.sql','54a8742c9addbd776149d1c06d360589','UPDATED','2018-10-23 14:47:51'),
(254,'editor','128-editor-mysql-TRANSLATE-949.sql','85cf4a3eefaf10eccdab2097a16c446c','UPDATED','2018-10-23 14:47:51'),
(255,'editor','129-editor-mysql-TRANSLATE-1070.sql','f42f98a07d4dd6dd5b96fe02cd86487f','UPDATED','2018-10-23 14:47:51'),
(256,'editor','129-editor-mysql-TRANSLATE-1166.sql','99220ec5152e7f2e1cf861bac58a0159','UPDATED','2018-10-23 14:47:51'),
(257,'editor','130-editor-mysql-TRANSLATE-1144.sql','def270eb4e8a8ffd0428496c59e1615c','UPDATED','2018-10-23 14:47:51'),
(258,'editor','131-editor-mysql-TRANSLATE-1058.sql','f9f84c18d14294c6dcaeb26b4169f5b7','UPDATED','2018-10-23 14:47:51'),
(259,'editor','131-editor-mysql-TRANSLATE-1186.sql','47a2149ad514271d692fd32d2e61d23c','UPDATED','2018-10-23 14:47:51'),
(260,'editor','131-editor-mysql-customer-administration.sql','fecdc88600c15b8befb3ae89ee3b339e','UPDATED','2018-10-23 14:47:52'),
(261,'editor','132-editor-mysql-TRANSLATE-1132-whitespace-tag-modification.sql','00dbc2a055ecb826743cb92722f84008','UPDATED','2018-10-23 14:47:52'),
(262,'editor','132-editor-mysql-TRANSLATE-1187.sql','42d9533387e1755951d937230d5a3138','UPDATED','2018-10-23 14:47:52'),
(263,'editor','133-editor-mysql-TRANSLATE-1188-collection-taskassoc.sql','b20ae08bfe7aa03ef1f956be865180d2','UPDATED','2018-10-23 14:47:52'),
(264,'editor','133-editor-mysql-TRANSLATE-1218-preserveWhitespace-config.sql','70de69608234a915f3924acad952290b','UPDATED','2018-10-23 14:47:52'),
(265,'editor','134-editor-mysql-TRANSLATE-1116-task-clone.sql','344fd8d0261dbac712a73e8ef777890f','UPDATED','2018-10-23 14:47:52'),
(266,'editor','134-editor-mysql-TRANSLATE-1188.sql','b18a9c286f0de63907dde2bc4ec7f750','UPDATED','2018-10-23 14:47:52'),
(267,'editor','135-editor-mysql-TRANSLATE-1188-termAttribute-alters.sql','e56279d6a51d310f799b62f6869638a1','UPDATED','2018-10-23 14:47:53'),
(268,'editor','135-editor-mysql-TRANSLATE-1192-length-restriction.sql','2d04728f30d788d9a552a489d1584e72','UPDATED','2018-10-23 14:47:53'),
(269,'editor','136-editor-mysql-TRANSLATE-1126.sql','951bd060c5e9dcd01bae73e86d7217b8','UPDATED','2018-10-23 14:47:53'),
(270,'editor','136-editor-mysql-TRANSLATE-1191.sql','d7b1990822837908a6c70ccd65a53378','UPDATED','2018-10-23 14:47:53'),
(271,'editor','137-editor-mysql-TRANSLATE-1188-add-attribute-datatype.sql','19a4085e8761258086111e1bf65db5e5','UPDATED','2018-10-23 14:47:53'),
(272,'editor','138-editor-mysql-TRANSLATE-1191-Interface-of-the-term-search.sql','0b6ef4814713d58c4670c195213c8287','UPDATED','2018-10-23 14:47:54'),
(273,'editor','139-editor-mysql-TRANSLATE-1191-update-termportal-role.sql','1f7e811df1df4678d0ee06c9ce755a02','UPDATED','2018-10-23 14:47:54'),
(274,'editor','140-editor-mysql-customer-administration.sql','a6c184401b1cc9c401a9c99a43720250','UPDATED','2018-10-23 14:47:54'),
(275,'editor','141-editor-mysql-language-flag-isso-3166-column-rename.sql','26bcdc319f790ffa4cb3867c8fb488a1','UPDATED','2018-10-23 14:47:54'),
(276,'editor','142-editor-mysql-TINTERNAL28-change-termcollection-name-scheme.php','3cfb46dae5e49bbd24b9d4140ff45fe4','UPDATED','2018-10-23 14:47:54'),
(277,'editor','142-editor-mysql-TRANSLATE-858-spellcheck-sublanguages.sql','edfb663f55884dc16202a2169b8e7f2a','UPDATED','2018-10-23 14:47:55'),
(278,'editor','143-editor-mysql-TRANSLATE-858-spellcheck-plugin.sql','046c7148fb7f8f92c39817ca53f1d2f3','UPDATED','2018-10-23 14:47:55'),
(279,'editor','144-editor-mysql-TRANSLATE-1265-task-termcollection-deletion.sql','99d05eda87e1bf8b06fec099a1998b5e','UPDATED','2018-10-23 14:47:55'),
(280,'editor','145-editor-mysql-TRANSLATE-1265-update-autocreated-collections.sql','594b978379ff6ff57780947d594edd0b','UPDATED','2018-10-23 14:47:55'),
(281,'editor','146-editor-mysql-TRANSLATE-1331-application-version.sql','b804d39c6f5224750698c552e0a89293','UPDATED','2018-10-23 14:47:55'),
(282,'editor','147-editor-mysql-TRANSLATE-1347-unknown-term-status.sql','ec6f2645380c6a056a58860d3cf2529c','UPDATED','2018-10-23 14:47:55'),
(283,'editor','147-editor-mysql-default-term-attribute-labels.sql','f29bf523549053d55757f53b706c20b6','UPDATED','2018-10-23 14:47:55'),
(284,'editor','148-editor-mysql-TRANSLATE-1352-PMMail.sql','f63666b22e0185f1be50ddc8c691afdf','UPDATED','2018-10-23 14:47:55'),
(285,'editor','149-editor-mysql-TRANSLATE-1376-segment-length-with-whitespace.sql','eb26ea8f5a2c3ed05b16ad07ee66f9ac','UPDATED','2018-10-23 14:47:55'),
(286,'editor','150-editor-mysql-TRANSLATE-1375-term-status-maps.sql','b0a5771808afe937c40077314e70e885','UPDATED','2018-10-23 14:47:56'),
(287,'editor','151-editor-mysql-TRANSLATE-1415-startViewModeConfig.sql','dfa6d3996d76e72c93952a1b0300bdb9','UPDATED','2018-10-23 14:47:56'),
(288,'editor','152-editor-mysql-TRANSLATE-1425-download-importArchive.sql','9628600ecffe18f3fb7b09b867f227d4','UPDATED','2018-10-23 14:47:56'),
(289,'editor','153-editor-mysql-TRANSLATE-1310-segment-length-performance.sql','61e784baad4b1ce7d66dd4324dd0f026','UPDATED','2018-10-23 14:47:56'),
(290,'editor','154-editor-mysql-TRANSLATE-1380.php','2b7ced8dcfe83e86ee90e587278523b7','UPDATED','2018-10-23 14:47:56'),
(291,'editor','sql-changelog-2018-01-22.sql','357352253d29e7d008605a7e71d3c091','UPDATED','2018-10-23 14:47:56'),
(292,'editor','sql-changelog-2018-02-13.sql','d92344afd8262eb41db74d4763acdc0e','UPDATED','2018-10-23 14:47:56'),
(293,'editor','sql-changelog-2018-02-15.sql','ec7cc5aacd726de8946b271d00f347a7','UPDATED','2018-10-23 14:47:56'),
(294,'editor','sql-changelog-2018-03-12.sql','25a63f7ba19ecc4265146677bf1591a4','UPDATED','2018-10-23 14:47:56'),
(295,'editor','sql-changelog-2018-03-15-2.sql','91aff200f9acec8ff15a947cfa06dac8','UPDATED','2018-10-23 14:47:56'),
(296,'editor','sql-changelog-2018-03-15.sql','11aacbf86776ff10a9ac30fb0e14b10d','UPDATED','2018-10-23 14:47:56'),
(297,'editor','sql-changelog-2018-04-11.sql','d23474cab225c5ffd1be8e156b3e3948','UPDATED','2018-10-23 14:47:56'),
(298,'editor','sql-changelog-2018-04-16.sql','b51e1f634dd79c383a25d78f5114cef8','UPDATED','2018-10-23 14:47:56'),
(299,'editor','sql-changelog-2018-05-07.sql','bdfb52f7c8495b456e6b7351807631ef','UPDATED','2018-10-23 14:47:56'),
(300,'editor','sql-changelog-2018-05-08.sql','bcb187da6560a69a8c079591095b6a73','UPDATED','2018-10-23 14:47:56'),
(301,'editor','sql-changelog-2018-05-09.sql','7f41306c76bf55c574f6c35370e48502','UPDATED','2018-10-23 14:47:56'),
(302,'editor','sql-changelog-2018-05-24.sql','7340f6739f1b9ec3a5c7970869f8b64e','UPDATED','2018-10-23 14:47:56'),
(303,'editor','sql-changelog-2018-05-30.sql','bfc0d6a9dfe2afb8b89caea9c003af8b','UPDATED','2018-10-23 14:47:56'),
(304,'editor','sql-changelog-2018-06-27-2.sql','b71dae3334b56f9675a07a255a46edec','UPDATED','2018-10-23 14:47:56'),
(305,'editor','sql-changelog-2018-06-27.sql','af5a4a3ceeedc9ca7c6d6ee2d5e64a6a','UPDATED','2018-10-23 14:47:56'),
(306,'editor','sql-changelog-2018-07-03.sql','d9b98e1ca9d1a13a100ace5a25f83d15','UPDATED','2018-10-23 14:47:56'),
(307,'editor','sql-changelog-2018-07-04.sql','7cf6d3c21114eeb5672549c0e5492ae1','UPDATED','2018-10-23 14:47:56'),
(308,'editor','sql-changelog-2018-07-17.sql','ec8bdf90ed92c7cd53051b8ec8e4a8f4','UPDATED','2018-10-23 14:47:56'),
(309,'editor','sql-changelog-2018-08-08.sql','74086f7c20037881302f527506f802bf','UPDATED','2018-10-23 14:47:56'),
(310,'editor','sql-changelog-2018-08-14-2.sql','e8cf1472a1ce81ab87a4dc9d8832c043','UPDATED','2018-10-23 14:47:56'),
(311,'editor','sql-changelog-2018-08-14.sql','b10235551baca2645f5bb588cb68142c','UPDATED','2018-10-23 14:47:56'),
(312,'editor','sql-changelog-2018-08-17.sql','0a6efc68f62f287b9f4f1701fa4df02f','UPDATED','2018-10-23 14:47:56'),
(313,'editor','sql-changelog-2018-08-27.sql','777bf1e82b176b1730466b1d1c568a6f','UPDATED','2018-10-23 14:47:56'),
(314,'editor','sql-changelog-2018-08-28.sql','7f896880d87556f2b6e96092fa173ae4','UPDATED','2018-10-23 14:47:56'),
(315,'editor','sql-changelog-2018-09-13.sql','2ff7340b37742ef0abb542dd9d57614b','UPDATED','2018-10-23 14:47:56'),
(316,'editor','sql-changelog-2018-10-16.sql','4228a8083a5f07687f6eaf44cab5b804','UPDATED','2018-10-23 14:47:56'),
(317,'MatchAnalysis','001-editorPlugin-MatchAnalysis-mysql.sql','cfcd116c646467a70f12a73105d73c5b','UPDATED','2018-10-23 14:47:56'),
(318,'MatchAnalysis','002-editorPlugin-MatchAnalysis-mysql.sql','038c49bc3faf0ffac48962a78cb8b0f4','UPDATED','2018-10-23 14:47:57'),
(319,'SpellCheck','001-editorPlugin-SpellCheck-mysql.sql','95cb61a15814d9c0a6f85a46b0d5b782','UPDATED','2018-10-23 14:47:57'),
(320,'TermImport','001-TermImport-plugin.sql','fcd0a1e5feac278508cd0b76f07c97be','UPDATED','2018-10-23 14:47:57'),
(321,'Okapi','002-editorPlugin-Okapi-mysql.sql','42cc3a35c5c59bf2d720844266f9bf19','UPDATED','2018-10-23 14:47:57'),
(322,'editor','148-editor-mysql-TRANSLATE-1341-instanttranslate.sql','a0f4840c0492b28086b517d49bc69fb0','UPDATED','2018-10-25 17:14:17'),
(323,'editor','148-editor-mysql-TRANSLATE-1342-language-resources-migration.sql','8f82ee19dbf95e8f0fe6448bb0acd68e','UPDATED','2018-10-25 17:14:17'),
(324,'editor','149-editor-mysql-TRANSLATE-1342-sdllanguagecloud-api.sql','c5085a4d2ce58c473ec73ee5304dd992','UPDATED','2018-10-25 17:14:17'),
(325,'editor','150-editor-mysql-TRANSLATE-1341-instanttranslate-iso6393-language.sql','607d6d4d6feefaa6a6c702a9bad14e03','UPDATED','2018-10-25 17:14:18'),
(326,'editor','151-editor-mysql-TRANSLATE-1342-instanttranslate-language-rfc-iso.sql','378a8443d2ed810e22c2dbdc4fa02197','UPDATED','2018-10-25 17:14:18'),
(327,'editor','152-editor-mysql-TRANSLATE-1342-instanttranslate-role.sql','3a973845687aff1c46333447def54f2d','UPDATED','2018-10-25 17:14:18'),
(328,'editor','153-editor-mysql-TRANSLATE-1342-instanttranslate-administration.sql','a7788f9cd85584034f664ba1af345b44','UPDATED','2018-10-25 17:14:18'),
(329,'editor','154-editor-mysql-TRANSLATE-1343-instanttranslate-termcollections.sql','7b5c2a714f12384123307972cee530dc','UPDATED','2018-10-25 17:14:19'),
(330,'editor','155-editor-mysql-TRANSLATE-1343-instanttranslate-termcollections-languages.php','e5ea4ee20708b50036c302a4bdb839ea','UPDATED','2018-10-25 17:14:19'),
(331,'editor','156-editor-mysql-TRANSLATE-1362-languageresources-google-api.sql','b714017452344d8df4d0f6b98580446a','UPDATED','2018-10-25 17:14:19'),
(332,'editor','157-editor-mysql-TRANSLATE-1250-internal-fuzzies-flag.sql','0f031c1a05fe9b4fab1e22eaf7274eb0','UPDATED','2018-10-25 17:14:19'),
(333,'editor','158-editor-mysql-TRANSLATE-1342-languageresources-additional.sql','3154c04fd13931f6588182561b365fe3','UPDATED','2018-10-25 17:14:19'),
(334,'editor','159-editor-mysql-TRANSLATE-1343-task-termcollection-name-update.php','b4add5b35a05daf8ffc6deb8ededee32','UPDATED','2018-10-25 17:14:19'),
(335,'editor','160-editor-mysql-T5DEV-251-additional-sql.sql','dbf6e35e3e88a7628090815d713a1b67','UPDATED','2018-10-25 17:14:20'),
(336,'editor','sql-changelog-2018-10-25.sql','9b058e46391694550d4cdaa15b7ebb46','UPDATED','2018-10-25 17:14:20'),
(337,'zfextended','025-mysql-TRANSLATE-1457-openId-user-fields.sql','8c49382d839df2850fbfa8561b1bae14','UPDATED','2020-08-12 10:39:27'),
(338,'zfextended','025-mysql-TRANSLATE-613-errorlog-database.sql','89d1fbdc2bbd9ef55af7f4703b1addcb','UPDATED','2020-08-12 10:39:27'),
(339,'zfextended','026-mysql-T5DEV-266-session-token.sql','3b047348bedf9e3acbe11fbccc18152b','UPDATED','2020-08-12 10:39:28'),
(340,'zfextended','026-mysql-TRANSLATE-613-errorlog-database.sql','878eb07af3eb0c6545271784da615159','UPDATED','2020-08-12 10:39:28'),
(341,'zfextended','027-mysql-TRANSLATE-1654-worker-slot-fix.sql','e149e5af5adbea4536f0e4ca795281dc','UPDATED','2020-08-12 10:39:28'),
(342,'zfextended','028-mysql-openid-TRANSLATE-1910-ssl-certificate-path-from-config.sql','c8c4df22a87cec17345a2f89dedbd5b4','UPDATED','2020-08-12 10:39:28'),
(343,'zfextended','029-mysql-TRANSLATE-905-maintenance-mode.sql','977ee5e2d4ec6f4f25bcda8e0976c0ea','UPDATED','2020-08-12 10:39:28'),
(344,'zfextended','030-mysql-change-system-user-email.sql','4397358b67e7b8154b47cb2b0226a322','UPDATED','2020-08-12 10:39:28'),
(345,'default','021-mysql-main-menu-remove.sql','caa7de32829f5d2e45fbd0bd82731c3b','UPDATED','2020-08-12 10:39:28'),
(346,'editor','159-editor-mysql-TRANSLATE-1451-customer-frontend-right.sql','280a89f2e2ea9d857885188e4ac8b64a','UPDATED','2020-08-12 10:39:28'),
(347,'editor','161-editor-mysql-TRANSLATE-1206-add-whitespace-chars-to-segment.sql','848659cee89f9aab6bd2dbd7e5256ea8','UPDATED','2020-08-12 10:39:28'),
(348,'editor','161-editor-mysql-TRANSLATE-1460-deactivate-export-menu.sql','66417fb1535ece70fa945890dacc9ffb','UPDATED','2020-08-12 10:39:28'),
(349,'editor','162-editor-mysql-T5DEV-253-match-rate-type.sql','0f6fb1d81a566a6965c5381d1e871efe','UPDATED','2020-08-12 10:39:28'),
(350,'editor','163-editor-mysql-TRANSLATE-1472-terms-foreign-key.sql','8e17888e2e7a9db69fefb00acdf2e18e','UPDATED','2020-08-12 10:39:28'),
(351,'editor','164-editor-mysql-T5DEV-251-instanttranslate-apps-route.sql','ca9051cfc6db77a2119323198c9402cd','UPDATED','2020-08-12 10:39:28'),
(352,'editor','164-editor-mysql-TRANSLATE-1397-multitenancy-phase-1.sql','28d136f6a739da4f1c38b44fde8317e1','UPDATED','2020-08-12 10:39:28'),
(353,'editor','165-editor-mysql-T5DEV-253-term-attributes-label-defaults-.sql','45bdd9c6a10a28c1d32831f102bef20e','UPDATED','2020-08-12 10:39:28'),
(354,'editor','165-editor-mysql-TRANSLATE-1386-pixel-based-length-restrictions.sql','8d7ae179d3ac57aaa3041984ac76494b','UPDATED','2020-08-12 10:39:28'),
(355,'editor','165-editor-mysql-TRANSLATE-1397-multitenancy-phase-1-cont.sql','6f8fb77eb91387cb0e6f6b3ab74c0874','UPDATED','2020-08-12 10:39:28'),
(356,'editor','166-editor-mysql-T5DEV-253-term-collection-task-assoc-drop-.sql','57df6d0bb932d8b5d51734b787a68989','UPDATED','2020-08-12 10:39:28'),
(357,'editor','166-editor-mysql-TRANSLATE-1397-multitenancy-phase-1-cont.sql','4f8782bd4ea4a4616a91f14d0b3bf774','UPDATED','2020-08-12 10:39:28'),
(358,'editor','167-editor-mysql-TRANSLATE-1397-multitenancy-phase-1-cont.sql','3ccf467c2f1a07b17d1f9ccd458f6656','UPDATED','2020-08-12 10:39:28'),
(359,'editor','167-editor-mysql-TRANSLATE-1491-collect-tm-matches-config.sql','8abe83926cfcf592242ca08b484d1cd6','UPDATED','2020-08-12 10:39:28'),
(360,'editor','168-editor-mysql-TRANSLATE-1484-mt-language-resource-usage-log.sql','1e9b6911bf5e8ad5974c7ade51b3298d','UPDATED','2020-08-12 10:39:28'),
(361,'editor','169-editor-mysql-TRANSLATE-1491-show-different-target-rename.sql','d0dde4946a4b8cec63d0d5746c032564','UPDATED','2020-08-12 10:39:28'),
(362,'editor','170-editor-mysql-TRANSLATE-1491-collect-tm-matches-config-change.sql','748d201323567e235ef8675f3b992212','UPDATED','2020-08-12 10:39:28'),
(363,'editor','171-editor-mysql-TRANSLATE-253-change-word-breakup-regex-config.sql','b9ceca7b20cd7c2b34d07cb3b68af1fa','UPDATED','2020-08-12 10:39:28'),
(364,'editor','172-editor-mysql-plugin-config.sql','014de106f17d1b27aaad57304289d5b3','UPDATED','2020-08-12 10:39:28'),
(365,'editor','173-editor-mysql-TRANSLATE-1342-sdllanguagecloud-api.sql','d8d41d882b1b19ab5167b105bd17e5e2','UPDATED','2020-08-12 10:39:28'),
(366,'editor','174-editor-mysql-TRANSLATE-1518-assoc-migration-fix.sql','a8eb4679c7e4709a277cd38aa27665ae','UPDATED','2020-08-12 10:39:29'),
(367,'editor','175-editor-mysql-TRANSLATE-1523-attach-original-as-reference-config.sql','abc11c8b5a9b340392b1134b2da88f3e','UPDATED','2020-08-12 10:39:29'),
(368,'editor','176-editor-mysql-TRANSLATE-1523-attach-original-as-reference-config-new-name.sql','db546cf2e96d7f455fe5886ce3d9c495','UPDATED','2020-08-12 10:39:29'),
(369,'editor','177-editor-mysql-TRANSLATE-1523-attach-original-as-reference-config-fix.sql','025c1be7eb82c3f043c4b005c076cc39','UPDATED','2020-08-12 10:39:29'),
(370,'editor','178-editor-mysql-missing-languages.sql','37b6ebe20d5822a675de0b7758808af2','UPDATED','2020-08-12 10:39:29'),
(371,'editor','179-editor-mysql-TRANSLATE-1523-attach-original-as-reference-config-fix.sql','b3f781b1338148db4a117f65503f392e','UPDATED','2020-08-12 10:39:29'),
(372,'editor','180-editor-mysql-TRANSLATE-1386-pixel-based-length-restrictions.sql','112cf4d0019bc5fb2fdc8fb1eb86eb16','UPDATED','2020-08-12 10:39:29'),
(373,'editor','181-editor-mysql-TRANSLATE-613-refactor-task-log.sql','6278ab59cb4dd27ce7b8d77bce3e8aca','UPDATED','2020-08-12 10:39:29'),
(374,'editor','181-editor-mysql-extended-workflow-logging.sql','a76baca7f4bf712dc5c146f33da03397','UPDATED','2020-08-12 10:39:29'),
(375,'editor','182-editor-mysql-TRANSLATE-1457-translate5-as-openID-Connect-client.sql','ecfd67621b34ada6bf332b4779e7df7a','UPDATED','2020-08-12 10:39:29'),
(376,'editor','182-editor-mysql-TRANSLATE-1586-close-session-on-browser-close.sql','c83414dd6b6142e941e0e3a283410073','UPDATED','2020-08-12 10:39:29'),
(377,'editor','182-editor-mysql-TRANSLATE-613-refactor-task-log.sql','4b5888d97ef222bd04d1edfab7fbc1b4','UPDATED','2020-08-12 10:39:29'),
(378,'editor','183-editor-mysql-TRANSLATE-613-refactor-task-log.sql','d098ee44e6c5c5b8e842d929249f5e74','UPDATED','2020-08-12 10:39:29'),
(379,'editor','184-editor-mysql-TRANSLATE-1572-language-resources-errorloggin.sql','fc10b33baea23184a3d0f19c8818bea7','UPDATED','2020-08-12 10:39:29'),
(380,'editor','184-editor-mysql-TRANSLATE-613-refactor-task-log.sql','4c281f804e55d9ad8e5fb5e30f53e02f','UPDATED','2020-08-12 10:39:29'),
(381,'editor','189-editor-mysql-TRANSLATE-1572-import-languageresources-in-background.sql','f992d605059cb5242a9cd65f74932e64','UPDATED','2020-08-12 10:39:29'),
(382,'editor','190-editor-mysql-TRANSLATE-1457-openId-client-additional-fields.sql','a861324c3259ace3f65908ce76fb478e','UPDATED','2020-08-12 10:39:29'),
(383,'editor','191-editor-mysql-TRANSLATE-1572-language-resources-error-loging-additional.sql','e8ae66cfa6b6b61c71e71fa6f8fc65a3','UPDATED','2020-08-12 10:39:29'),
(384,'editor','191-editor-mysql-TRANSLATE-1581-Mailto-from-task-list-project-manager-column.sql','dae4a55c3be4d6f2b94f087e1c1a1da1','UPDATED','2020-08-12 10:39:29'),
(385,'editor','192-editor-mysql-TRANSLATE-1581-config-default-value.sql','1fe0564c10a61416830215ce86a24f91','UPDATED','2020-08-12 10:39:29'),
(386,'editor','193-editor-mysql-TRANSLATE-1560-disable-match-resources-panel-in-editor.sql','027a6cdc0454b8e0f5e6b53f5b4c457d','UPDATED','2020-08-12 10:39:29'),
(387,'editor','194-editor-mysql-TRANSLATE-1390-microsoft-translator.sql','598cd2dc9a4d729fb82e452a0ac1c486','UPDATED','2020-08-12 10:39:29'),
(388,'editor','194-editor-mysql-TRANSLATE-1457-openId-client-domain-and-config.sql','3d24ee4b48aeaace4d73665763c4f7ac','UPDATED','2020-08-12 10:39:29'),
(389,'editor','195-editor-mysql-TRANSLATE-1342-worker-rename-fix.sql','ab5dc4ddcf77957debcc581d51ebcbbe','UPDATED','2020-08-12 10:39:29'),
(390,'editor','196-editor-mysql-TRANSLATE-1390-microsoft-translator-api-config-default-value.sql','2dfbf176c35360f7e0882563242a3357','UPDATED','2020-08-12 10:39:29'),
(391,'editor','197-editor-mysql-TRANSLATE-382-error-logging-acl.sql','3af9d838d238e458cf60a0a776c888ad','UPDATED','2020-08-12 10:39:29'),
(392,'editor','198-editor-mysql-TRANSLATE-1403-anonymize-users.sql','d82e7ab1df7e314cc0b5fefcf8238788','UPDATED','2020-08-12 10:39:29'),
(393,'editor','198-editor-mysql-remove-MtComparEval.sql','ea793694a32a4b600d3332a48a5bed3e','UPDATED','2020-08-12 10:39:29'),
(394,'editor','199-editor-mysql-TRANSLATE-1275-Proposing-changes-to-terms.sql','8b6fc130e3326ec1a7133127530cdd22','UPDATED','2020-08-12 10:39:29'),
(395,'editor','200-editor-mysql-TRANSLATE-1275-Proposing-changes-to-terms.sql','b9d37792dd3bd559fb128281842f5cf1','UPDATED','2020-08-12 10:39:30'),
(396,'editor','200-editor-mysql-TRANSLATE-1457-openid-issuer-customer-field.sql','26f13dadf1d07078b462c77c496e6c65','UPDATED','2020-08-12 10:39:30'),
(397,'editor','201-editor-mysql-LW-10-taskoverview-menu-acl.sql','9e350a047518e68dcdf4c12a35fcfe3b','UPDATED','2020-08-12 10:39:30'),
(398,'editor','201-editor-mysql-TRANSLATE-1275-Proposing-changes-to-terms.sql','42df77d7d4ec79168b2166ac7593b24b','UPDATED','2020-08-12 10:39:30'),
(399,'editor','202-editor-mysql-TRANSLATE-1403-anonymize-users.sql','32462e4e2ef25a9c611820a5b24733d7','UPDATED','2020-08-12 10:39:30'),
(400,'editor','203-editor-mysql-TRANSLATE-1366-export-of-term-proposals.sql','4c8f8e2b6879f8687d580fc90595d45b','UPDATED','2020-08-12 10:39:30'),
(401,'editor','203-editor-mysql-TRANSLATE-1489-Excel-Ex-Import.sql','8d59a4869bc7002e4fc4f1c56ef76756','UPDATED','2020-08-12 10:39:30'),
(402,'editor','203-editor-mysql-TRANSLATE-1672-Reference-file-tree-JSON-is-invalid.sql','d17a0b2d3de6f5c48f572e36119f5de3','UPDATED','2020-08-12 10:39:30'),
(403,'editor','204-editor-mysql-TRANSLATE-1367-changes-to-tbx-import.sql','edd2d3a86672dee19fcc92ea6816518b','UPDATED','2020-08-12 10:39:30'),
(404,'editor','204-editor-mysql-TRANSLATE-1654-termtagger-config.sql','2ef64b5702f71f677f4206d24f00e6d7','UPDATED','2020-08-12 10:39:30'),
(405,'editor','205-editor-mysql-TRANSLATE-1685-open-issues-termportal.sql','c5aa98e2c40c8d88cbdc44e7b39b392a','UPDATED','2020-08-12 10:39:30'),
(406,'editor','205-editor-mysql-TRANSLATE-613-refactor-task-log.sql','b0a85ac76dcf261ce510ba1c6c847e59','UPDATED','2020-08-12 10:39:30'),
(407,'editor','206-editor-mysql-TRANSLATE-1275-changes-to-terms-comment-acl.sql','3cd05e9185141468cd5751fc767e1fb3','UPDATED','2020-08-12 10:39:30'),
(408,'editor','206-editor-mysql-TRANSLATE-1671.sql','4ded5d18b744829b80370654b04b513b','UPDATED','2020-08-12 10:39:30'),
(409,'editor','207-editor-mysql-TRANSLATE-1305-xlf-framing-tags.sql','a43ce9d303324dd3220b56d12cde26a4','UPDATED','2020-08-12 10:39:30'),
(410,'editor','207-editor-mysql-TRANSLATE-1401-termportal-instantranslate-navigation.sql','2b22b73d8206426c1cb7bc51a49f2412','UPDATED','2020-08-12 10:39:30'),
(411,'editor','208-editor-mysql-T5DEV-270-tests.sql','0687085c8bf84a9b363d14f4737df217','UPDATED','2020-08-12 10:39:30'),
(412,'editor','209-editor-mysql-TRANSLATE-1732-term-table-mid-characters-length.sql','dbfcf26f016c5098c176ae57d6be0d71','UPDATED','2020-08-12 10:39:30'),
(413,'editor','210-editor-mysql-TRANSLATE-1767-associated-languageresource-can-not-be-deleted.sql','5d2a38e0d73223e60310ff7a8bf9863e','UPDATED','2020-08-12 10:39:30'),
(414,'editor','211-editor-mysql-TRANSLATE-1767-associated-languageresource-can-not-be-deleted.sql','040b63e862d85899a5f3375f807115dc','UPDATED','2020-08-12 10:39:30'),
(415,'editor','212-editor-mysql-TRANSLATE-1763-sdlxliff-comments-meta.sql','8247e58ccbf457151271198a010a3bbe','UPDATED','2020-08-12 10:39:30'),
(416,'editor','213-editor-mysql-TRANSLATE-1733-All-languages-available-for-adding-a-new-term.sql','fb88eb9507685475592c0be27e1bea8e','UPDATED','2020-08-12 10:39:30'),
(417,'editor','213-editor-mysql-TRANSLATE-1735-note-mandatory-config.sql','117595ad580555664b4772b2fddb568b','UPDATED','2020-08-12 10:39:30'),
(418,'editor','213-editor-mysql-TRANSLATE-1741-competetive-users.sql','c02c550fa92a8e5fafca52007384f02b','UPDATED','2020-08-12 10:39:30'),
(419,'editor','213-editor-mysql-TRANSLATE-1761.php','a5333fea42122316510406dba331052b','UPDATED','2020-08-12 10:39:30'),
(420,'editor','214-editor-mysql-TRANSLATE-1792-workflow-mail-configuration.sql','77eea97caccf5f26f3c1f5cf1aa245dd','UPDATED','2020-08-12 10:39:30'),
(421,'editor','215-editor-mysql-TRANSLATE-1671-unlock-100-matches.sql','cef83e71a81d78c351ed8d6a48c4a615','UPDATED','2020-08-12 10:39:30'),
(422,'editor','215-editor-mysql-TRANSLATE-1799-TermEntry-Proposals-get-deleted.sql','4f5a3bf6277bb94c4a54b50ca4d3b910','UPDATED','2020-08-12 10:39:30'),
(423,'editor','216-editor-mysql-TRANSLATE-1774-categories.sql','0b29dcce3aeb90a5328af1bec46adae9','UPDATED','2020-08-12 10:39:30'),
(424,'editor','216-editor-mysql-TRANSLATE-1799-TermEntry-Proposals-get-deleted-update-script.php','48440351d1a8e6c9a2050247533c15ca','UPDATED','2020-08-12 10:39:30'),
(425,'editor','217-editor-mysql-    TRANSLATE-1806-Set-default-of-for-applying-sdlxliff-track-changes-on-import-time-to-active.sql','5045e81181d85688e63ad9190a9fcfee','UPDATED','2020-08-12 10:39:31'),
(426,'editor','218-editor-mysql-TRANSLATE-1826-Include-east-asian-sub-languages-in-string-based-termTagging.sql','a45511bf6603e5768f2f1385d1b9d288','UPDATED','2020-08-12 10:39:31'),
(427,'editor','219-editor-mysql-TRANSLATE-1817-hide-logout-leavetask-button-config.sql','9c98f115bcb2455926ae6c9699a254fa','UPDATED','2020-08-12 10:39:31'),
(428,'editor','220-editor-mysql-TRANSLATE-1817-custom-html-container-config.sql','6e04f34a5fb718133256978817786f48','UPDATED','2020-08-12 10:39:31'),
(429,'editor','221-editor-mysql-TRANSLATE-1838-customer-default-system-roles-config.sql','51a2cbb6b4aafd23e960794a1bd3ee1b','UPDATED','2020-08-12 10:39:31'),
(430,'editor','221-editor-mysql-TRANSLATE-1839-KPI.sql','c5ac7c91afd13e3c6ecc757bf189fe48','UPDATED','2020-08-12 10:39:31'),
(431,'editor','222-editor-mysql-TRANSLATE-1167-multi-user-edit.sql','52bceacd6960f832da3bc3e18e17a229','UPDATED','2020-08-12 10:39:31'),
(432,'editor','222-editor-mysql-TRANSLATE-1552-ACL-inheritence.sql','d2192448b8d1445249c53143784ba67c','UPDATED','2020-08-12 10:39:31'),
(433,'editor','223-editor-mysql-TRANSLATE-1167-multi-user-edit.sql','a86606c5bd5719b07f341527457e0986','UPDATED','2020-08-12 10:39:31'),
(434,'editor','223-editor-mysql-TRANSLATE-1493-advanced-filters.sql','49d4005280ca9496ce03085c1eb0afcb','UPDATED','2020-08-12 10:39:31'),
(435,'editor','223-editor-mysql-TRANSLATE-1871.sql','b4b7678aa31a399db9b703be899a4153','UPDATED','2020-08-12 10:39:31'),
(436,'editor','223-editor-mysql-TRANSLATE-1889-rfc-5646-value-for-estonian-is-wrong.sql','45fed330cea8c28314b0dd377bb5e76b','UPDATED','2020-08-12 10:39:31'),
(437,'editor','224-editor-mysql-TRANSLATE-1531-Provide-progress-data-about-a-task.sql','8680d83a4286281ea7d8f806e5162974','UPDATED','2020-08-12 10:39:31'),
(438,'editor','225-editor-mysql-TRANSLATE-1531-rename-role-and-step-to-reviewer.php','b3b08348b60eb1f23c8b65b5c6e97358','UPDATED','2020-08-12 10:39:31'),
(439,'editor','225-editor-mysql-TRANSLATE-1919-taskGuid-comment-meta.sql','06a8b88f229c2af25af21c87f97f4e54','UPDATED','2020-08-12 10:39:31'),
(440,'editor','225-editor-mysql-TRANSLATE-1960-source-or-target-connected-with-visualreview.sql','c95843cc96c497714844c20b65d91b94','UPDATED','2020-08-12 10:39:31'),
(441,'editor','226-editor-mysql-TRANSLATE-1455-Deadlines-and-assignment-dates-for-every-role-of-a-task.sql','aa8746a72d5b1e8cfcb0afd49b9f52ce','UPDATED','2020-08-12 10:39:31'),
(442,'editor','226-editor-mysql-TRANSLATE-1531-rename-role-and-step-to-reviewer.sql','0fc8b2c66213607cd8a8438de73649e2','UPDATED','2020-08-12 10:39:31'),
(443,'editor','226-editor-mysql-TRANSLATE-1927-Add-types-for-tasks-in-core.sql','8b6bab8fff58a3654b61411336d4f1af','UPDATED','2020-08-12 10:39:31'),
(444,'editor','227-editor-mysql-TRANSLATE-1531-update-segmentCount-segmentFinishCount-for-tasks.php','9592d535d7d7920044e114f9763808f7','UPDATED','2020-08-12 10:39:31'),
(445,'editor','227-editor-mysql-TRANSLATE-905-application-name-configurable.sql','80d135d6e135616a613de688cf1f07c7','UPDATED','2020-08-12 10:39:31'),
(446,'editor','228-editor-mysql-TRANSLATE-1455-deadlineDate-update.sql','bbbee8486059e86c7fcf6e8025c4898b','UPDATED','2020-08-12 10:39:31'),
(447,'editor','228-editor-mysql-TRANSLATE-1969-Languages.sql','ee22c6e9f2d3571ec367b36f6a02a73c','UPDATED','2020-08-12 10:39:31'),
(448,'editor','229-editor-mysql-TRANSLATE-1987-Load-custom-page-in-the-editors-branding-area.sql','fce4c0a53b473ccab58f18614ff5035c','UPDATED','2020-08-12 10:39:31'),
(449,'editor','230-editor-mysql-TRANSLATE-1531-remove-the-dummy-workflow.sql','0a175ec78267125996dbea5a9be0ce1b','UPDATED','2020-08-12 10:39:31'),
(450,'editor','231-editor-mysql-TRANSLATE-1996-Remove-auth-hash-from-log.sql','86b798e0ca3b75e57cfc29eb583db9fd','UPDATED','2020-08-12 10:39:31'),
(451,'editor','231-editor-mysql-TRANSLATE-2004-send-summary-mail-to-pm.sql','970b3983f56ee16cb498c29f1fbeb3b4','UPDATED','2020-08-12 10:39:31'),
(452,'editor','231-editor-mysql-TRANSLATE-2008-termtagger-fixes.sql','04f58c69f76764ee644cfb3dc49d22ca','UPDATED','2020-08-12 10:39:31'),
(453,'editor','232-editor-mysql-TRANSLATE-1610-bundle-task-to-projects.sql','19a8070e7b79af53ee6f8819237ffe46','UPDATED','2020-08-12 10:39:31'),
(454,'editor','232-editor-mysql-TRANSLATE-1977-assign-2-roles-to-user.sql','c0fb8957f55c6cab92d5f68984ee16aa','UPDATED','2020-08-12 10:39:32'),
(455,'editor','232-editor-mysql-TRANSLATE-1997-show-help-window-state-migration.php','b685f93aa0793b2e064fa3ab468d97c9','UPDATED','2020-08-12 10:39:32'),
(456,'editor','232-editor-mysql-TRANSLATE-2022-huge-segment-definition.sql','178585b6c176b1e6e4f90789206444ed','UPDATED','2020-08-12 10:39:32'),
(457,'editor','233-editor-mysql-TRANSLATE-1997-show-help-window.sql','e03c49be7fca32cfc4aed5964a79cc4f','UPDATED','2020-08-12 10:39:32'),
(458,'editor','234-editor-mysql-TRANSLATE-1997-show-help-window-userconfig-update.sql','10f4c684fb8cd886c96e2b7f6e3bf3dc','UPDATED','2020-08-12 10:39:32'),
(459,'editor','234-editor-mysql-TRANSLATE-1999-custom-accordion.sql','15eaefea7141d7cb24ae482520f88559','UPDATED','2020-08-12 10:39:32'),
(460,'editor','235-editor-mysql-TRANSLATE-1997-show-help-window-state-url-migration.php','7364078c004c4511abc410285cba6b77','UPDATED','2020-08-12 10:39:32'),
(461,'editor','236-editor-mysql-TRANSLATE-1997-show-help-window-helpconfi-remove.sql','f28a66dc1e644f68b262310fa58ca2e2','UPDATED','2020-08-12 10:39:32'),
(462,'editor','237-editor-mysql-TRANSLATE-1997-show-help-window-lvl-update.sql','5094e713c49a0b783a6bc7eb05166738','UPDATED','2020-08-12 10:39:32'),
(463,'editor','238-editor-mysql-TRANSLATE-1610-bundle-tasks-to-projects.sql','b1c2f87b10a7e26dc162e71e50e6d148','UPDATED','2020-08-12 10:39:32'),
(464,'editor','238-editor-mysql-TRANSLATE-1901-pixel-based-length-with-lines.sql','f358f17d2b8e76b0c37be58a17770505','UPDATED','2020-08-12 10:39:32'),
(465,'editor','238-editor-mysql-TRANSLATE-1997-show-help-window.sql','9c0bd92f9865742415067209114ec1cb','UPDATED','2020-08-12 10:39:32'),
(466,'editor','239-editor-mysql-TRANSLATE-2042-frontend-tabpanel.sql','f5c4f08f99a253081bbdda67f6054510','UPDATED','2020-08-12 10:39:32'),
(467,'editor','240-editor-mysql-TRANSLATE-2028-help-url-config.sql','a02c976b440569b1dcefa4b0a935fed7','UPDATED','2020-08-12 10:39:32'),
(468,'editor','240-editor-mysql-TRANSLATE-2057-termtagger-readonly-segments.sql','218e62ff3073ce09ec592fb65a62559f','UPDATED','2020-08-12 10:39:32'),
(469,'editor','241-editor-mysql-TRANSLATE-2028-help-url-config-migration.php','20049404136255aab415df380779dca9','UPDATED','2020-08-12 10:39:32'),
(470,'editor','242-editor-mysql-TRANSLATE-1610-bundle-task-to-projects-projectTask-type.sql','c4c06417823b1d94e4e26ed27c5effbe','UPDATED','2020-08-12 10:39:32'),
(471,'editor','242-editor-mysql-TRANSLATE-2072-pixel-mapping-on-file-level.sql','7a832c4742ec17fc2446e06bcafa240c','UPDATED','2020-08-12 10:39:32'),
(472,'editor','243-editor-mysql-TRANSLATE-1200-png-cleanup.php','2cd1f88eb0d4024ed227047acb7d7260','UPDATED','2020-08-12 10:39:32'),
(473,'editor','244-editor-mysql-TRANSLATE-1610-bundle-tasks-to-projects-projectId-key.sql','3386c1a986de0f0b34ddf56b8f53046a','UPDATED','2020-08-12 10:39:32'),
(474,'editor','244-editor-mysql-TRANSLATE-2082-fix-target-tags.php','bcaa539d748bf9bedcfeb3e8ad9e8038','UPDATED','2020-08-12 10:39:32'),
(475,'editor','245-editor-mysql-TRANSLATE-1610-isSdlxliffFileParser-flag.sql','92cc37fe0f6d290d855a414570093e0b','UPDATED','2020-08-12 10:39:32'),
(476,'editor','245-editor-mysql-TRANSLATE-2092-import-DisplayText-XML.sql','d7fb979a53410bd122e2c213bbaff3e1','UPDATED','2020-08-12 10:39:32'),
(477,'editor','246-editor-mysql-T5DEV-273.sql','bfedbaaafb73a59d302a6a18e933a47a','UPDATED','2020-08-12 10:39:32'),
(478,'editor','246-editor-mysql-TRANSLATE-2052-segments-to-users.sql','e3551a54b59a1a8eb8e950301cd04e44','UPDATED','2020-08-12 10:39:32'),
(479,'editor','246-editor-mysql-TRANSLATE-2094-Set-finish-date-for-all-reviewers.sql','99c8b2f19f06d9ba1f4fcb3168f5a057','UPDATED','2020-08-12 10:39:32'),
(480,'editor','247-editor-mysql-TRANSLATE-2101-disable-notFountTranslation-xliff-autocreation.php','f87b932607ba53f77b2bd59b75788d9d','UPDATED','2020-08-12 10:39:32'),
(481,'editor','248-editor-mysql-TRANSLATE-2109-Remove-string-length-restriction-flag.sql','21a37f220ed5c5ebd0dd8b7ea3ba716b','UPDATED','2020-08-12 10:39:32'),
(482,'editor','249-editor-mysql-TRANSLATE-2132-kpi-acl.sql','c752f396ceb946f59cc0bbbe53ea7b84','UPDATED','2020-08-12 10:39:32'),
(483,'editor','250-editor-mysql-TRANSLATE-2035-Add-extra-column-to-languageresource-log-table.sql','18412105cd8c1f526729263d689006d1','UPDATED','2020-08-12 10:39:32'),
(484,'editor','251-editor-mysql-TRANSLATE-2047-Errormessages-on-DB-Update.sql','b11eb205e1db0a99c3857129e988b295','UPDATED','2020-08-12 10:39:32'),
(485,'editor','252-editor-mysql-TRANSLATE-2137-instantranslate-filetranslate-flag.sql','d2c69de9d20db93ea6c19487ae51eec9','UPDATED','2020-08-12 10:39:32'),
(486,'editor','253-editor-mysql-TRANSLATE-2120-db-constraint-userconfig-table.sql','a97a8481119dec09ceb038b345d78e6c','UPDATED','2020-08-12 10:39:33'),
(487,'editor','254-editor-mysql-TRANSLATE-2053-Deadline-by-hour-and-not-only-by-day.php','e1dedc628e223f191f7e64aa4b46577f','UPDATED','2020-08-12 10:39:33'),
(488,'editor','255-editor-mysql-TRANSLATE-2150-disable-finish-overdue.sql','485a95a84574511297179f9debf9dde2','UPDATED','2020-08-12 10:39:33'),
(489,'editor','sql-changelog-2018-10-30.sql','48e7d54fd08ac888fdf66d27e5dc865a','UPDATED','2020-08-12 10:39:33'),
(490,'editor','sql-changelog-2018-12-20.sql','106d44edb7d6fa95e8366d46592b7a36','UPDATED','2020-08-12 10:39:33'),
(491,'editor','sql-changelog-2018-12-21.sql','f2019cbfceb643d309ef475dcae1a36f','UPDATED','2020-08-12 10:39:33'),
(492,'editor','sql-changelog-2019-01-21.sql','08c6709599e38d4b2f4191b903296dae','UPDATED','2020-08-12 10:39:33'),
(493,'editor','sql-changelog-2019-01-24.sql','417d4b60e271dcfd942df020c5fa3546','UPDATED','2020-08-12 10:39:33'),
(494,'editor','sql-changelog-2019-01-31.sql','0d413760cb8505ae9f840f7b52490d7f','UPDATED','2020-08-12 10:39:33'),
(495,'editor','sql-changelog-2019-02-07.sql','adfebc9dc702b2bf75e79e55cebf20ac','UPDATED','2020-08-12 10:39:33'),
(496,'editor','sql-changelog-2019-02-28.sql','b2e00cde9a669d4b3d90fedd860b1990','UPDATED','2020-08-12 10:39:33'),
(497,'editor','sql-changelog-2019-03-21.sql','5a425463b6e54695edbb3b1fe9cfbdb0','UPDATED','2020-08-12 10:39:33'),
(498,'editor','sql-changelog-2019-04-17.sql','24be423384b142d499b708d78f51e83d','UPDATED','2020-08-12 10:39:33'),
(499,'editor','sql-changelog-2019-05-10.sql','a67f729e74fae2800432ff48e47ffaa0','UPDATED','2020-08-12 10:39:33'),
(500,'editor','sql-changelog-2019-06-27.sql','09d521571805455f7a2a45964699ce1c','UPDATED','2020-08-12 10:39:33'),
(501,'editor','sql-changelog-2019-07-17.sql','729d2a3b7a0d043e96d1e89f6e63a280','UPDATED','2020-08-12 10:39:33'),
(502,'editor','sql-changelog-2019-07-30.sql','869e84a5b5821ea4ded5e4e495fa3b8c','UPDATED','2020-08-12 10:39:33'),
(503,'editor','sql-changelog-2019-08-20.sql','f72b4584dd87aecb086a5652a60d643f','UPDATED','2020-08-12 10:39:33'),
(504,'editor','sql-changelog-2019-08-29.sql','7773a1983dae58c3e731300e8d2a762d','UPDATED','2020-08-12 10:39:33'),
(505,'editor','sql-changelog-2019-09-12.sql','9443de1139b8cfdeb2ea7328709e1211','UPDATED','2020-08-12 10:39:33'),
(506,'editor','sql-changelog-2019-09-24.sql','87c7ff1034ce67831ebc506c6c514adc','UPDATED','2020-08-12 10:39:33'),
(507,'editor','sql-changelog-2019-10-07.sql','72feb0424ee20caa17e40e8b85597fe5','UPDATED','2020-08-12 10:39:33'),
(508,'editor','sql-changelog-2019-10-08.sql','dbb7b90b789dd6f47f3885e115a7400c','UPDATED','2020-08-12 10:39:33'),
(509,'editor','sql-changelog-2019-10-14.sql','6ebce1ec677a46e52ac4061b1cc4d89b','UPDATED','2020-08-12 10:39:33'),
(510,'editor','sql-changelog-2019-10-16.sql','3e9ca5edbe49e0261e019c93654a8aed','UPDATED','2020-08-12 10:39:33'),
(511,'editor','sql-changelog-2019-11-12.sql','166a04813f631ba15d24ec4b5065eee1','UPDATED','2020-08-12 10:39:33'),
(512,'editor','sql-changelog-2019-12-02.sql','26c4da7564e4e55dad72c2c091470330','UPDATED','2020-08-12 10:39:33'),
(513,'editor','sql-changelog-2019-12-03.sql','7a15fd4e594fd8cd9c9cd4b8a6d62986','UPDATED','2020-08-12 10:39:33'),
(514,'editor','sql-changelog-2019-12-18.sql','e55db216411240ab7cd374af485f7de5','UPDATED','2020-08-12 10:39:33'),
(515,'editor','sql-changelog-2020-02-17.sql','f13997438f5d88f5bf883174a292d9e6','UPDATED','2020-08-12 10:39:33'),
(516,'editor','sql-changelog-2020-02-27.sql','e6cd1c6be9820c5da34eee1f28127b45','UPDATED','2020-08-12 10:39:33'),
(517,'editor','sql-changelog-2020-03-12.sql','307130181ccc8d28a2239ee108fe55e7','UPDATED','2020-08-12 10:39:33'),
(518,'editor','sql-changelog-2020-04-08.sql','1335eb7c2ebc40d41740036b0c1cd570','UPDATED','2020-08-12 10:39:33'),
(519,'editor','sql-changelog-2020-05-07.sql','02db1d1c7a595841a0952f1fb17e1967','UPDATED','2020-08-12 10:39:33'),
(520,'editor','sql-changelog-2020-05-11.sql','0879bd650e1664b5e90bd1d29093f46f','UPDATED','2020-08-12 10:39:33'),
(521,'editor','sql-changelog-2020-05-27.sql','a657338ae22d8e23f8ccdab6bdcd036b','UPDATED','2020-08-12 10:39:33'),
(522,'editor','sql-changelog-2020-06-04.sql','6aa03bd58ae19a59d398a0750ce1778c','UPDATED','2020-08-12 10:39:33'),
(523,'editor','sql-changelog-2020-06-17.sql','fd423a51dcb95ad9c766135661470dd1','UPDATED','2020-08-12 10:39:33'),
(524,'editor','sql-changelog-2020-06-19.sql','07669f56fee1efe2c8afdfa878b820d2','UPDATED','2020-08-12 10:39:33'),
(525,'editor','sql-changelog-2020-06-30.sql','ba6e15562146b28027b6e4def13ed09e','UPDATED','2020-08-12 10:39:33'),
(526,'editor','sql-changelog-2020-07-06.sql','a8ab44577b7dc6f9f9bf8ad63cbc8084','UPDATED','2020-08-12 10:39:33'),
(527,'editor','sql-changelog-2020-07-13.sql','92ae4bab9f85020c6fd01a3d86ece417','UPDATED','2020-08-12 10:39:33'),
(528,'editor','sql-changelog-2020-07-23.sql','a4ae417454bfc9b19ce487a81a457336','UPDATED','2020-08-12 10:39:33'),
(529,'editor','sql-changelog-2020-08-05.sql','3bffd4aabbba5ee03089d07a91013011','UPDATED','2020-08-12 10:39:33'),
(530,'PangeaMt','001-editorPlugin-PangeaMt-mysql.sql','d1639d6b2660f90b3a9907561a505b4b','UPDATED','2020-08-12 10:39:33'),
(531,'ModelFront','001-editorPlugin-ModelFront-mysql.sql','b1fc5c2e536a50abab9b5419b37c6713','UPDATED','2020-08-12 10:39:33'),
(532,'FrontEndMessageBus','001-editorPlugin-MessageBus-config-mysql.sql','a77a2fbd3206c53892ddb9d0b9ecd099','UPDATED','2020-08-12 10:39:34'),
(533,'NecTm','001-editorPlugin-NecTm-mysql.sql','fabb8313ee3315269d54418df00c36d5','UPDATED','2020-08-12 10:39:34'),
(534,'NecTm','002-editorPlugin-NecTm-mysql.sql','33361873e3b8977ad78881ed5bfdf65c','UPDATED','2020-08-12 10:39:34'),
(535,'MatchAnalysis','004-editorPlugin-MatchAnalysis-mysql.sql','ba15dca00a67d8da83fed55beba0ed8e','UPDATED','2020-08-12 10:39:34'),
(536,'MatchAnalysis','005-editorPlugin-MatchAnalysis-mysql.sql','3e0dac9508e161d14daa65e0336ea605','UPDATED','2020-08-12 10:39:34'),
(537,'editor','259-editor-mysql-TRANSLATE-2045-Use-utf8mb4-charset-for-DB.php','18ff4a1ebea2d88c764b858f8774a106','UPDATED','2020-08-12 10:39:34'),
(538,'zfextended','031-mysql-TRANSLATE-2484-user-info-endpoint-is-unreachable.sql','213aae8d46249ff5a82eaebd1e105290','5.7.9','2022-09-21 19:23:13'),
(539,'zfextended','032-TRANSLATE-390-log-duplication.sql','932fbda7c13addbbfb555f85efc40732','5.7.9','2022-09-21 19:23:13'),
(540,'zfextended','033-mysql-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','f5a5a2b2087019a9de02b939803c8534','5.7.9','2022-09-21 19:23:13'),
(541,'zfextended','034-TRANSLATE-2385-user-login-statistics.sql','96241d8684ea44e5f26dd690e544bace','5.7.9','2022-09-21 19:23:13'),
(542,'zfextended','035-TRANSLATE-198-Open-different-tasks-if-editor-is-opened-in-multiple-tabs.sql','8ff5a84f651478b01ff35cd755f5301b','5.7.9','2022-09-21 19:23:13'),
(543,'zfextended','036-TRANSLATE-2500-worker-deadlocks.sql','e1ed736baa9a1f2543cf3d8721dfd255','5.7.9','2022-09-21 19:23:13'),
(544,'zfextended','036-TRANSLATE-2585-evaluate-auto_set_role-acl.sql','3084532e51794eb2c88fd9939bcb871b','5.7.9','2022-09-21 19:23:13'),
(545,'zfextended','037-TRANSLATE-2076-define-analysis-fuzzy-ranges.sql','4440ad056d6b7f4bf278a47ef71685a8','5.7.9','2022-09-21 19:23:13'),
(546,'zfextended','038-TRANSLATE-2666-wysiwig-for-images.sql','a08ee4bb0d69561e5373ca92a0d31602','5.7.9','2022-09-21 19:23:13'),
(547,'zfextended','038-maintenance-ip-exception.sql','fa7e237836d82d528b1974b26d380db8','5.7.9','2022-09-21 19:23:13'),
(548,'default','022-TRANSLATE-2663-show-consortium-logos-on-loading-screen.sql','1339827a0f27091e3b9490e30a36f677','5.7.9','2022-09-21 19:23:13'),
(549,'editor','256-editor-mysql-TRANSLATE-1050-Save-user-customization-of-editor.sql','1ba8693da407d5e53984a37443ea871a','5.7.9','2022-09-21 19:23:13'),
(550,'editor','256-editor-mysql-TRANSLATE-2025-Change-default-for-userCanIgnoreTagValidatio- to-0.sql','a27b85c4b063fa948c6ce6598467ab81','5.7.9','2022-09-21 19:23:14'),
(551,'editor','256-editor-mysql-TRANSLATE-2111-reference-files-popup.sql','5e675341ced6e06171de3b5bf47bcd0f','5.7.9','2022-09-21 19:23:14'),
(552,'editor','257-editor-mysql-TRANSLATE-1050-Save-user-customization-of-editor-editor-default-state-config.sql','f6a5926194195fc1461ac3f1b207ff83','5.7.9','2022-09-21 19:23:14'),
(553,'editor','258-editor-mysql-TRANSLATE-2166-Add-help-page-for-project-and-preferences-overview.sql','9ed04a903cb80b4d587adac5d813a6a4','5.7.9','2022-09-21 19:23:14'),
(554,'editor','259-editor-mysql-TRANSLATE-1050-Save-user-customization-of-editor.sql','1b3ac967927025d52622e1b8d6a89864','5.7.9','2022-09-21 19:23:14'),
(555,'editor','260-editor-mysql-TRANSLATE-1050-Save-user-customization-of-editor.sql','b56d1e1a902c14a372e59b57046f0994','5.7.9','2022-09-21 19:23:14'),
(556,'editor','260-editor-mysql-TRANSLATE-2166-help-page-fixes.sql','2c48b39c1225b85985aeeed9a2981d86','5.7.9','2022-09-21 19:23:14'),
(557,'editor','261-editor-mysql-TRANSLATE-2193-TRANSLATE-2186-logout-close-btn.sql','7e00e786996b570edc0a97c686cf69e2','5.7.9','2022-09-21 19:23:14'),
(558,'editor','262-editor-mysql-TRANSLATE-1050-Save-user-customization-of-editor.sql','35f397c18449be314ec9fa14ca9b9e4c','5.7.9','2022-09-21 19:23:14'),
(559,'editor','263-editor-mysql-TRANSLATE-2011-translate-2-standard-term-attributes-for-TermPortal.sql','910ad9f818e32233ee98e87ad6a1f423','5.7.9','2022-09-21 19:23:14'),
(560,'editor','263-editor-mysql-TRANSLATE-2075-Fuzzy-Selection-of-language-resources.sql','8cb0be8076655d5ebc7268f99a047cc5','5.7.9','2022-09-21 19:23:14'),
(561,'editor','263-editor-mysql-TRANSLATE-2236-Change-quality-and-state-flags-default-values.sql','7d1aac615538cb59b87f6fd5daa8357b','5.7.9','2022-09-21 19:23:14'),
(562,'editor','264-editor-mysql-TRANSLATE-2075-Fuzzy-Selection-of-language-resources-migration.php','d2efee18f4511fb860b0c24ae411933a','5.7.9','2022-09-21 19:23:14'),
(563,'editor','265-editor-mysql-TRANSLATE-2075-Fuzzy-Selection-of-language-resources-missing-languages.sql','d01d0d75a85b4694ddd10ed85483b550','5.7.9','2022-09-21 19:23:14'),
(564,'editor','266-editor-mysql-TRANSLATE-2233-Remove-autoAssociateTaskPm-workflow-action.sql','b20fe54bfdcddef9d5e0998967b846ce','5.7.9','2022-09-21 19:23:14'),
(565,'editor','267-editor-mysql-MITTAGQI-44-build-and-deploy.sql','85c4825aeeac6bcd7f9c8c1b4ad93c22','5.7.9','2022-09-21 19:23:14'),
(566,'editor','268-TRANSLATE-2261-improve-terminology-import.sql','c74a147f883cc2b2a6d32e24853222ad','5.7.9','2022-09-21 19:23:14'),
(567,'editor','269-TRANSLATE-2293-stateful-custom-panel.sql','4e3e1b65b2fb29b2cc184a65e9b2da79','5.7.9','2022-09-21 19:23:14'),
(568,'editor','269-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','d02a64192cc6e41930d31f28ba13e8e0','5.7.9','2022-09-21 19:23:14'),
(569,'editor','270-TRANSLATE-471_Overwrite_system_config_by_client_and_task_migration_script.php','239c4ba631d64b0efb62a3183d218908','5.7.9','2022-09-21 19:23:14'),
(570,'editor','270-editor-mysql-helpvideo-editor.sql','cae57ea67ad30e057a3985c18a345344','5.7.9','2022-09-21 19:23:14'),
(571,'editor','271-TRANSLATE-2294-additional-langres-tags.sql','4df394c0d2ade63039b313c22f4ada31','5.7.9','2022-09-21 19:23:14'),
(572,'editor','271-TRANSLATE-471_Overwrite_system_config_by_client_and_task_after_migration.sql','8b93a1a79c061f452221e9ac19c201b5','5.7.9','2022-09-21 19:23:14'),
(573,'editor','272-TRANSLATE-471_Overwrite_system_config_by_client_new_sql.sql','1f45ee925844a2e8fc252b79a99cfe62','5.7.9','2022-09-21 19:23:14'),
(574,'editor','273-TRANSLATE-471_Overwrite_system_config_by_client_additional_sql_changes.sql','99c8a69356d9b8bc5e0749be7711c599','5.7.9','2022-09-21 19:23:14'),
(575,'editor','274-TRANSLATE-2357_deepl_config_formality_opentm2_config_level.sql','c1b7477a07542b89d4ecfbabbbd0f364','5.7.9','2022-09-21 19:23:14'),
(576,'editor','275-TRANSLATE-1484_Count_translated_characters_by_MT_engine_and_customer.sql','d47890d9d30715d95e67b15bde3ddd54','5.7.9','2022-09-21 19:23:14'),
(577,'editor','275-TRANSLATE-2362_tag_protection.sql','d1c556ecb1dccbb3fb6c447173d8053f','5.7.9','2022-09-21 19:23:14'),
(578,'editor','275-TRANSLATE-471_Overwrite_system_config_by_client_additional_sql_changes.sql','a1d1b38675ee1fe3d0d5185f0bf6c1cd','5.7.9','2022-09-21 19:23:14'),
(579,'editor','276-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','b3b3f36c07c9b49e1dfb5a6e7b136bf3','5.7.9','2022-09-21 19:23:14'),
(580,'editor','277-TRANSLATE-2362_tag_protection.sql','110bb564a4c0ae5908794178f8b7954c','5.7.9','2022-09-21 19:23:14'),
(581,'editor','278-TRANSLATE-2402_Remove_rights_for_PMs_to_change_instance_defaults_for_configuration.sql','8fc455bf179671b8e58aad53740c9854','5.7.9','2022-09-21 19:23:14'),
(582,'editor','279-TRANSLATE-1484_Count_translated_characters_by_MT_engine_and_customer-unique-index-fix.sql','d07ceeeecc5a469d99ecc4a6841cd53d','5.7.9','2022-09-21 19:23:14'),
(583,'editor','279-TRANSLATE-2375_Set_default_deadline_per_workflow_step_in_configuration.php','5e445770afc9c0cd39251eb668773017','5.7.9','2022-09-21 19:23:14'),
(584,'editor','280-TRANSLATE-1643-separate-autostatus-pretranslated.sql','ea491da5420f69e6aeb95bebed89ba96','5.7.9','2022-09-21 19:23:14'),
(585,'editor','280-TRANSLATE-2442_Disabled_connectors_and_repetitions.sql','ce42d31f3479dae597e489987be6e43b','5.7.9','2022-09-21 19:23:14'),
(586,'editor','281-TRANSLATE-2350_Configurable__Should_pivot_language_be_available_in_add_task_wizard.sql','091644fe5164ef25dad7f3f4b7ecc1e4','5.7.9','2022-09-21 19:23:14'),
(587,'editor','282-TRANSLATE-2342_Show_progress_of_document_translation.sql','d8c69d2c201a2a073f7a8b2339920eea','5.7.9','2022-09-21 19:23:14'),
(588,'editor','283-TRANSLATE-2363_Development_tool_session_impersonate_accessible_via_api.sql','d99895e57a25c177a25b66cc5bcf4c0d','5.7.9','2022-09-21 19:23:14'),
(589,'editor','284-internal-dummy-TM.sql','04f83f2f1c05f8c8e95699b905256f57','5.7.9','2022-09-21 19:23:14'),
(590,'editor','285-TRANSLATE-2417_OpenTM_writeable_by_default.sql','2ac53c594b4b96aa69d1829889ee06ec','5.7.9','2022-09-21 19:23:14'),
(591,'editor','285-TRANSLATE-2478_Add_missing_languages.sql','0005b3c917af73ac84ef1bdc49e78e59','5.7.9','2022-09-21 19:23:14'),
(592,'editor','286-TRANSLATE-2196-AutoQA-a-prequesites.sql','fab518fa1bd4a032a1daf029ffc4f6e0','5.7.9','2022-09-21 19:23:14'),
(593,'editor','286-TRANSLATE-2196-AutoQA-b-convert.php','948685706409826120124f8b1deba11e','5.7.9','2022-09-21 19:23:14'),
(594,'editor','286-TRANSLATE-2196-AutoQA-c-cleanup.sql','b7ce5280d5a53068960152f95ded22cc','5.7.9','2022-09-21 19:23:14'),
(595,'editor','286-TRANSLATE-2315_repeated_segments_filter.sql','c3a81aca9525fa4307bbf74a044fddd4','5.7.9','2022-09-21 19:23:14'),
(596,'editor','286-TRANSLATE-2494_Plugins_enabled_by_default.php','252fcd44b8719a5f55f04e21eeda8391','5.7.9','2022-09-21 19:23:14'),
(597,'editor','286-TRANSLATE-2517-null-as-string-fix.sql','4774762576ae660af8c682bea787b2b1','5.7.9','2022-09-21 19:23:14'),
(598,'editor','288-TRANSLATE-2315-repeated-segments-migration.php','afc5bbc84b934aa501f4614f198e720a','5.7.9','2022-09-21 19:23:14'),
(599,'editor','289-TRANSLATE-198-Open-different-tasks-if-editor-is-opened-in-multiple-tabs.php','21af5b43affcd8204061b1fcc88019a3','5.7.9','2022-09-21 19:23:14'),
(600,'editor','290-TRANSLATE-2350-Make-configurable-if-pivot-language-should-be-available-in-add-task-wizard.sql','2f3f00cff427212dbcbf0e6c8b211862','5.7.9','2022-09-21 19:23:14'),
(601,'editor','291-TRANSLATE-2481-default-deadline-float.sql','d531147f9462d67f3e5ee6d24da195f4','5.7.9','2022-09-21 19:23:14'),
(602,'editor','292-TRANSLATE-2531-microsoft-translator.sql','b286fd46a992a773513d31f29db9162d','5.7.9','2022-09-21 19:23:14'),
(603,'editor','293-TRANSLATE-2545-flexibilize-workflow-definition.sql','99cfbd56075589f9494a80430476892f','5.7.9','2022-09-21 19:23:14'),
(604,'editor','294-TRANSLATE-2081_Preset_of_user_to_task_assignments_new.sql','3876d389bb1424ff73be6e19f0bdade0','5.7.9','2022-09-21 19:23:14'),
(605,'editor','294-TRANSLATE-2545-flexibilize-workflow-definition.sql','acb033f208252540e03ae7b9301cf8fd','5.7.9','2022-09-21 19:23:14'),
(606,'editor','295-TRANSLATE-2516-add-user-column-to-excel-.sql','25fc34114976c4b1158a776a0daa558d','5.7.9','2022-09-21 19:23:14'),
(607,'editor','297-remove-LEK_segment_durations-view.sql','64c38bb96277b382541a57405bd7df68','5.7.9','2022-09-21 19:23:15'),
(608,'editor','298-TRANSLATE-2545.sql','1abeff8f51e661cec10b767af35381a0','5.7.9','2022-09-21 19:23:15'),
(609,'editor','298-TRANSLATE-2566-integrate-theme-switch.sql','d3d33772689e3ba4c2265f3399b52c3b','5.7.9','2022-09-21 19:23:15'),
(610,'editor','299-TRANSLATE-2477_Language_resource_to_task_assoc__Set_default_for_pre-translation_and_internal-fuzzy_options_in_system_config.sql','bce926d544b77016601e4945aa0e2362','5.7.9','2022-09-21 19:23:15'),
(611,'editor','299-TRANSLATE-2566-integrate-theme-switch.php','c0954afd5f94a968528121d51a0a2fc0','5.7.9','2022-09-21 19:23:15'),
(612,'editor','300-TRANSLATE-2416-create-pm-light-role.sql','9d93f56fc6ba7c944d585ba912689775','5.7.9','2022-09-21 19:23:15'),
(613,'editor','300-TRANSLATE-2518-project-task-description.sql','29111a6d6d0b97f171a623c75c4fff58','5.7.9','2022-09-21 19:23:15'),
(614,'editor','300-TRANSLATE-2570-2564-acl-for-quality-supervisor.sql','345a143243628ee287aa6a9e2379b4a7','5.7.9','2022-09-21 19:23:15'),
(615,'editor','300-TRANSLATE-2575-system-default-configurat-initialTaskUsageMode.sql','45a8a7c7b6f8b0f0bbdd82d5ae342168','5.7.9','2022-09-21 19:23:15'),
(616,'editor','300-TRANSLATE-2576-notify-associated-user-button-does-not-work.sql','2b775faa5c9929a23c7aac9b850b5b36','5.7.9','2022-09-21 19:23:15'),
(617,'editor','301-TRANSLATE-2481-default-deadline-float.sql','03e72c8776e7c6fdda524b1c213d2478','5.7.9','2022-09-21 19:23:15'),
(618,'editor','302-TRANSLATE-2580-autoqa-segment-length.sql','3809eb012f5de7db2ebba13641fc63a5','5.7.9','2022-09-21 19:23:15'),
(619,'editor','303-TRANSLATE-2416_Create_PM-light_system_role.sql','81ed584fb0e393a6a1b0426fa2840241','5.7.9','2022-09-21 19:23:15'),
(620,'editor','304-expose-app-state-to-cronip.sql','930f5ac1b1a4173be34c0bc910cf7242','5.7.9','2022-09-21 19:23:15'),
(621,'editor','305-TRANSLATE-2614-fix-translate5-workflow-names-complex.sql','a66834bb59d8adde762c3b9b49c1d799','5.7.9','2022-09-21 19:23:15'),
(622,'editor','306-TRANSLATE-2302-accept-reject-trackchanges.sql','223e0b3afb75b8c418004819b1256ca0','5.7.9','2022-09-21 19:23:15'),
(623,'editor','306-TRANSLATE-2623_Move_theme_switch_button_and_language_switch_button_in_settings_panel.sql','ca0e68ac054408ecd205146b85ebbdf8','5.7.9','2022-09-21 19:23:15'),
(624,'editor','315-TRANSLATE-1405-TermPortal-DB-structure.sql','01922f3829b23c4184e868f7e07f3681','5.7.9','2022-09-21 19:23:15'),
(625,'editor','316-TRANSLATE-1405-TermPortal-configuration-acl.sql','19113e9092d5a8cb47f40499d405c4de','5.7.9','2022-09-21 19:23:15'),
(626,'editor','317-TRANSLATE-1405-TermPortal-data-migration.sql','f3e18e53b440d5aef48a1879e5323f89','5.7.9','2022-09-21 19:23:15'),
(627,'editor','318-TRANSLATE-1405-TermPortal-datatype-migration.sql','fa6404c4be595db0f056c1ecc7d12032','5.7.9','2022-09-21 19:23:15'),
(628,'editor','319-TRANSLATE-1405-TermPortal_stage_3_Extending_towards_full_term_administration.php','9b6a2120c7a92a4cea02a84b3c9a65d6','5.7.9','2022-09-21 19:23:15'),
(629,'editor','320-TRANSLATE-1405-TermPortal-fix-label-config.sql','7b61148934b37ace5a2aa9a3bc24fadc','5.7.9','2022-09-21 19:23:15'),
(630,'editor','320-TRANSLATE-1405-TermPortal_stage_3_Extending_towards_full_term_administration.sql','780a2c1b8102e23a249894f37fe35273','5.7.9','2022-09-21 19:23:15'),
(631,'editor','321-TRANSLATE-1405-TermPortal_l10System-en_processStatus.sql','25da9c9bc31aa8cdd2ef4de421dbe2e9','5.7.9','2022-09-21 19:23:15'),
(632,'editor','322-TRANSLATE-1405-TermPortal-fix-label-config.sql','11f9ef59cc64e6f0213039bfcc48ecbd','5.7.9','2022-09-21 19:23:15'),
(633,'editor','323-TRANSLATE-1405-TermPortal_nulls_for_createdBy_if_unknown.php','21966b79e4b1d51bfc4959fc9bbda8d1','5.7.9','2022-09-21 19:23:15'),
(634,'editor','323-TRANSLATE-1405-add-default-administrativeStatus.sql','ef469fe025644b7271b9aa732f015b57','5.7.9','2022-09-21 19:23:15'),
(635,'editor','323-TRANSLATE-2634-integrate-pdf-documentation.sql','6152019f0b8713a58c6b37b6e95bf22a','5.7.9','2022-09-21 19:23:15'),
(636,'editor','324-TRANSLATE-1405-TermPortal-createdBy_updatedBy_fk.sql','f8dac83329e622514f4c8a314b773d74','5.7.9','2022-09-21 19:23:15'),
(637,'editor','325-TRANSLATE-1405-default-administrativeStatus-config.sql','e314a47778b0dcc1b1a86ceee99ed475','5.7.9','2022-09-21 19:23:15'),
(638,'editor','326-TRANSLATE-1405-TermPortal_l10System-de_admStatus.sql','592358f15b0ab5e373d6a72bd1074e50','5.7.9','2022-09-21 19:23:15'),
(639,'editor','327-TRANSLATE-1405-TermPortal-missing-definition.sql','21169a556959fc94e2c35783891269c1','5.7.9','2022-09-21 19:23:15'),
(640,'editor','327-TRANSLATE-1405-TermPortal_partOfSpeech_language.sql','28f6d482cbf722353b5639b43b68b1fd','5.7.9','2022-09-21 19:23:15'),
(641,'editor','328-TRANSLATE-1405-TermPortal_newroles_migraion_openid_customers.sql','f322d95c26a65b8388da0173ab75ee25','5.7.9','2022-09-21 19:23:15'),
(642,'editor','329-TRANSLATE-2597-set-resource-usage-log-config.sql','205e1f40c3e269002a7aafeada97b124','5.7.9','2022-09-21 19:23:15'),
(643,'editor','330-TRANSLATE-2649-fix_missing_processStatus.php','488974ae7a663e1540d6044a1da42221','5.7.9','2022-09-21 19:23:15'),
(644,'editor','330-TRANSLATE-2656-notify-associated-users-checkbox.sql','a9151a24d1e4faec87eef1749d5c6ce6','5.7.9','2022-09-21 19:23:15'),
(645,'editor','331-TRANSLATE-2649-terms_transacgrp_person.sql','ccaef661c0d69fd1556e2e20ab42edc8','5.7.9','2022-09-21 19:23:15'),
(646,'editor','332-TRANSLATE-2649-terms_transacgrp_person.sql','3fa77da2f10166642728342c0c43fef5','5.7.9','2022-09-21 19:23:15'),
(647,'editor','332-TRANSLATE-2649-transac_things.sql','6d951859d751ae80daec74bde5ada379','5.7.9','2022-09-21 19:23:15'),
(648,'editor','332-TRANSLATE-2672-default-theme-fix.sql','fd68f0bf77894cb26e8845790b295538','5.7.9','2022-09-21 19:23:15'),
(649,'editor','333-TRANSLATE-1405-insert-processStatus-attr-if-missing.sql','8247ae5015e2deb7c5b87e17654d1fbd','5.7.9','2022-09-21 19:23:15'),
(650,'editor','333-TRANSLATE-2681-chinese-languages.sql','ce099e280d9a5a7f2da4439fbddc0850','5.7.9','2022-09-21 19:23:15'),
(651,'editor','334-TRANSLATE-2489-acl_attribute_datatype.sql','5242fba9cac76707a6633c2f6185d29d','5.7.9','2022-09-21 19:23:15'),
(652,'editor','334-TRANSLATE-2687-wrong-texts-in-system-config.sql','2959d8f7ffe0ed15e584a8e7569a7eba','5.7.9','2022-09-21 19:23:15'),
(653,'editor','334-TRANSLATE-2688-fix-missing-lcids.sql','3239514640082d00338c0542e1fd5ac3','5.7.9','2022-09-21 19:23:15'),
(654,'editor','335-TRANSLATE-2404-run-only-termtagger.sql','41b1fd60b1b7c9b112c5fb6fbd59a25a','5.7.9','2022-09-21 19:23:16'),
(655,'editor','335-TRANSLATE-2699-add-id-and-fix-date.sql','d3d1e48b93070b3cc163026443ef799c','5.7.9','2022-09-21 19:23:16'),
(656,'editor','336-TRANSLATE-2632-datatype-missing-figure-fix.sql','e574cb0b0b566f546a9b0fd3f12aa932','5.7.9','2022-09-21 19:23:16'),
(657,'editor','337-TRANSLATE-2303-overview-of-comments.sql','9508fa54afbb12e695e7a7adbff93570','5.7.9','2022-09-21 19:23:16'),
(658,'editor','337-TRANSLATE-2487-attributes-isDraft-column.sql','1f9ea36f9760e31a7759b91ec3a2f537','5.7.9','2022-09-21 19:23:16'),
(659,'editor','338-TRANSLATE-2723-reminder-e-mail-multiple-times.sql','2b71a6c7b1efe6be4ba92653e39dc0d1','5.7.9','2022-09-21 19:23:16'),
(660,'editor','338-TRANSLATE-2724-translation-error.sql','b49ac2108e171ca7e77523e343cf2a94','5.7.9','2022-09-21 19:23:16'),
(661,'editor','338-TRANSLATE-2733-embed-translate5-task-video.sql','291646845f0ede3e98aa37c8a27a6cd6','5.7.9','2022-09-21 19:23:16'),
(662,'editor','339-TRANSLATE-2750-project-overview-resizable.sql','6adfa82c1ad9fb6d86e8250f8ad07f51','5.7.9','2022-09-21 19:23:16'),
(663,'editor','339-editor-mysql-TRANSLATE-2749_Blocked_segments_in_workflow_progress.php','96ca2a2496d76db1c9636d5bade6af48','5.7.9','2022-09-21 19:23:16'),
(664,'editor','340-TRANSLATE-2666-wysiwig-for-images.sql','e17471e484881b00caced324a16e0636','5.7.9','2022-09-21 19:23:16'),
(665,'editor','340-TRANSLATE-2740-autoqa-analysis.sql','f6f822353af8e97613bc9e6f375bcabe','5.7.9','2022-09-21 19:23:16'),
(666,'editor','340-TRANSLATE-2756-segments-were-locked-but-not-translated.sql','6bdb7a8966f7f215ee65aab274f380fd','5.7.9','2022-09-21 19:23:16'),
(667,'editor','341-TRANSLATE-2540-autoqa-segment-empty.sql','d69268d099740e5932f7df6efac8b66d','5.7.9','2022-09-21 19:23:16'),
(668,'editor','342-TRANSLATE-2537-autoqa-inconsistent.sql','da70b511036560d9ca6db9967ec2968d','5.7.9','2022-09-21 19:23:16'),
(669,'editor','343-TRANSLATE-2491-exported_workers.sql','e04fb4e756b7fda5cfcf75773a287a70','5.7.9','2022-09-21 19:23:16'),
(670,'editor','343-TRANSLATE-2727-column-for-ended.sql','3bd8939ff61f6de47c3372addb3866e2','5.7.9','2022-09-21 19:23:16'),
(671,'editor','343-TRANSLATE-2781-unlock-job-on-missing-connection.sql','0df57284a51da6f1b1d7e2d1035d2721','5.7.9','2022-09-21 19:23:16'),
(672,'editor','344-TRANSLATE-2303-overview-of-comments.sql','ab9a95a7ea402013378ea988d9130891','5.7.9','2022-09-21 19:23:16'),
(673,'editor','344-TRANSLATE-2791-extend-term-attribute-mapping.sql','2755f76d59f7da14493c20c81f2f02bd','5.7.9','2022-09-21 19:23:16'),
(674,'editor','344-TRANSLATE-2999-terms_attributes_dataTypeId_fk.sql','f9c1f24e797afcbceb09e41d05ffca0e','5.7.9','2022-09-21 19:23:16'),
(675,'editor','345-TRANSLATE-2777-figure-attr-gui-label.sql','1480f65e80d68c692c72cc825b3ceba3','5.7.9','2022-09-21 19:23:16'),
(676,'editor','345-TRANSLATE-2794-terms_term_entry_collectionId_fk.sql','c4af4cca40cb606bd1579627a05d1264','5.7.9','2022-09-21 19:23:16'),
(677,'editor','345-TRANSLATE-2797-tbx-import-damage-datatypes.php','afa42e36f050915a8aa2272351589282','5.7.9','2022-09-21 19:23:16'),
(678,'editor','346-TRANSLATE-2539-autoqa-numbers-check.sql','8197725a5f97089f2ae2b8553cf7cd25','5.7.9','2022-09-21 19:23:16'),
(679,'editor','346-TRANSLATE-2791-extend-term-attribute-mapping.sql','82e12af7ecefb7987efa624e9f3ec5af','5.7.9','2022-09-21 19:23:16'),
(680,'editor','347-TRANSLATE-2872-task-import-callback.sql','b91902fa93618c89ed263e0f18512f9a','5.7.9','2022-09-21 19:23:16'),
(681,'editor','347-TRANSLATE-2879-termPM-transfer-terms-rights.sql','2139145c11025e5fae9278bd4812b54c','5.7.9','2022-09-21 19:23:16'),
(682,'editor','348-TRANSLATE-2879-termPM-transfer-terms-rights.sql','c00baf19abb2b624847f4f377b69e606','5.7.9','2022-09-21 19:23:16'),
(683,'editor','349-TRANSLATE-2859-termProposer-delete-rights.sql','91213865febaadc05c003f1b69d6e058','5.7.9','2022-09-21 19:23:16'),
(684,'editor','349-TRANSLATE-2890-Module-redirect-based-on-initial_page-acl.sql','33d6873d73d728825a6076b280189b5a','5.7.9','2022-09-21 19:23:16'),
(685,'editor','349-TRANSLATE-2895-boundary-tag-remover.sql','6e6f5a3d8fd8993dd0df152b29262b7a','5.7.9','2022-09-21 19:23:16'),
(686,'editor','350-TRANSLATE-2906-improve-tbx-import-performance.sql','6c1ab4bc3623928a9d96812e4a35daec','5.7.9','2022-09-21 19:23:16'),
(687,'editor','351-TRANSLATE-2386-add-language-specific-special-characters.sql','29137d4f6c1c7b75025f30203adc64b3','5.7.9','2022-09-21 19:23:16'),
(688,'editor','351-TRANSLATE-2779-enableSegmentWhiteSpaceCheck.sql','bc80ee0f608298dc6527430259ff2c27','5.7.9','2022-09-21 19:23:16'),
(689,'editor','351-TRANSLATE-2842-new-configuration-to-disable-workflow-mails.sql','a421c025044baa115603e0e3e585ab59','5.7.9','2022-09-21 19:23:16'),
(690,'editor','351-TRANSLATE-2942-repetiton-configuration.sql','9ac49e1ad23a019338617d06c49df686','5.7.9','2022-09-21 19:23:16'),
(691,'editor','352-TRANSLATE-2902-workflow-handleProjectCreated-trigger.sql','c5af2971dbbcba051c191cfc348fbf9e','5.7.9','2022-09-21 19:23:16'),
(692,'editor','352-TRANSLATE-2946-JS-length-check.sql','8701e23d6f0e502d604a414f1b7e1f47','5.7.9','2022-09-21 19:23:16'),
(693,'editor','353-TRANSLATE-2386-slq-typo.sql','a52ba4c71a10e2da3aa3fc6f40f9a7c6','5.7.9','2022-09-21 19:23:16'),
(694,'editor','354-TRANSLATE-2952-automated-workflow-video.sql','11e519227f42fd5eaed0cc5acee86f54','5.7.9','2022-09-21 19:23:16'),
(695,'editor','355-TRANSLATE-2386-add-language-specific-special-characters.sql','22c8afe10c075d65c503aa491a87e1fc','5.7.9','2022-09-21 19:23:16'),
(696,'editor','356-TRANSLATE-2822-match-analysis-on-a-character-base.sql','314645c5c9c5037b875d973c05f1f5a2','5.7.9','2022-09-21 19:23:16'),
(697,'editor','356-TRANSLATE-2869-export-task-editing-history.sql','7c0a319e0969200bb7f7f566b4f4569f','5.7.9','2022-09-21 19:23:16'),
(698,'editor','357-TRANSLATE-2266-customer-meta.sql','c595ee758e4a2758c602cb43d9fd08e9','5.7.9','2022-09-21 19:23:16'),
(699,'editor','357-TRANSLATE-2386-consortium-members-suggestions.sql','2b83cfebad55fc61131841135fb82845','5.7.9','2022-09-21 19:23:16'),
(700,'editor','357-TRANSLATE-2884-restrict-nightly-error-summaries.sql','49bba687c727b52ef50bdef3e05e518d','5.7.9','2022-09-21 19:23:16'),
(701,'editor','358-TRANSLATE-2314-single-segment-locking.sql','3850f1a0cbcab90a22681a9225d68017','5.7.9','2022-09-21 19:23:16'),
(702,'editor','358-TRANSLATE-2964-notifyNewProjectForPm-filterByProjectType.sql','5cb048643b03ddd12f2fb22bcb6236a5','5.7.9','2022-09-21 19:23:16'),
(703,'editor','359-TRANSLATE-2266-editor-mysql-customermeta.sql','f2e512bf51807b136af5848cb75981ea','5.7.9','2022-09-21 19:23:16'),
(704,'editor','360-TRANSLATE-2811-integrate-ms-translator-synonym.sql','4e4e0786a26ed5300347eb62e7e701fc','5.7.9','2022-09-21 19:23:16'),
(705,'editor','360-TRANSLATE-2978-disable-auto-newline.sql','cec20c3f7d3144111f99767eff1b2a47','5.7.9','2022-09-21 19:23:16'),
(706,'editor','360-TRANSLATE-2984-archive-and-delete-tasks.sql','829aa4d6cf1e0380183fb049fe751ad2','5.7.9','2022-09-21 19:23:16'),
(707,'editor','361-Fix-outdated-termtagger-dependency.sql','2c2db1c06529e0d6a4afd3ab259d4b6d','5.7.9','2022-09-21 19:23:16'),
(708,'editor','361-TRANSLATE-2855-pre-translate-pivot.sql','5964ba55f3569796bbc67dd8667a573f','5.7.9','2022-09-21 19:23:16'),
(709,'editor','361-TRANSLATE-2986-trigger-callback.sql','a88874004da2e87f2a7f996bab9f9984','5.7.9','2022-09-21 19:23:16'),
(710,'editor','362-TRANSLATE-2988-fix-opentm2-export.sql','288d91dfc5d7b52138596a19c071f3a7','5.7.9','2022-09-21 19:23:17'),
(711,'editor','363-errorcode-link.sql','626f9442304bcab8fbd693550f7b4278','5.7.9','2022-09-21 19:23:17'),
(712,'editor','364-TRANSLATE-1405-clean-up-preinsert.sql','e65dfb311bdeb99e0479e7457af6bb93','5.7.9','2022-09-21 19:23:17'),
(713,'editor','364-TRANSLATE-3002-task-askFinishOnClose.sql','d27b2c0f9123cc29f2237b5bc47fb41a','5.7.9','2022-09-21 19:23:17'),
(714,'editor','365-TRANSLATE-2538-autoqa-segment-whitespace-chars-config.sql','ed1c520e7bce27a3fb0ececa333c13ff','5.7.9','2022-09-21 19:23:17'),
(715,'editor','365-TRANSLATE-3010-set-default-pivot-language.sql','102a80655341e51a7fa6387e1dde0d0e','5.7.9','2022-09-21 19:23:17'),
(716,'editor','366-MITTAGQI-145-modify-foreignId-column.sql','bccc40be371ba37cc1f0116858069cea','5.7.9','2022-09-21 19:23:17'),
(717,'editor','366-TRANSLATE-2063-Enable-parallele-use-of-multiple-okapi-versions.sql','e3ca7e061045398272cf2966eab89d68','5.7.9','2022-09-21 19:23:17'),
(718,'editor','367-TRANSLATE-3043-ordinary-space-placeholder.sql','d862f4621015d4247138064d4c31ea7a','5.7.9','2022-09-21 19:23:17'),
(719,'editor','367-TRANSLATE-3045-Optimize-terms_term-indexes.sql','c616ae80d033c67eb7e19084948908c3','5.7.9','2022-09-21 19:23:17'),
(720,'editor','sql-changelog-2020-09-07.sql','623139ebcb9320cec33577ae0856babb','5.7.9','2022-09-21 19:23:17'),
(721,'editor','sql-changelog-2020-09-16.sql','c46e73cca70d4529e6e490c9d264dcf9','5.7.9','2022-09-21 19:23:17'),
(722,'editor','sql-changelog-5.0.10-2020-10-06.sql','d98eb1f6c2fbd5ef18f18acbadc5b4c2','5.7.9','2022-09-21 19:23:17'),
(723,'editor','sql-changelog-5.0.11-2020-10-14.sql','57c03d8a21ee0eda1b25065424c14882','5.7.9','2022-09-21 19:23:17'),
(724,'editor','sql-changelog-5.0.12-2020-10-21.sql','dd13fb2662efa32d33e1e19c22b500e1','5.7.9','2022-09-21 19:23:17'),
(725,'editor','sql-changelog-5.0.13-2020-11-17.sql','6d3bc04d96d9133f593c225447e0b3b2','5.7.9','2022-09-21 19:23:17'),
(726,'editor','sql-changelog-5.0.15-2020-12-21.sql','e39a72ed286d2f6ab05503af4be399f5','5.7.9','2022-09-21 19:23:17'),
(727,'editor','sql-changelog-5.1.0-2021-02-02.sql','8dd7085797c0d6ad00e861b83f605d00','5.7.9','2022-09-21 19:23:17'),
(728,'editor','sql-changelog-5.1.0-2021-02-04.sql','51c7f5bac97d1a42f7abaa8aa2f3691a','5.7.9','2022-09-21 19:23:17'),
(729,'editor','sql-changelog-5.1.1-2021-02-17.sql','642ded1d89c1d4d48ab81c1bd895d340','5.7.9','2022-09-21 19:23:17'),
(730,'editor','sql-changelog-5.1.2-2021-03-31.sql','4d6ca301f6d2fa41b1c5b9a51d34bc48','5.7.9','2022-09-21 19:23:17'),
(731,'editor','sql-changelog-5.1.3-2021-04-15.sql','41aa3fe54eee7c295d1af364c1585cec','5.7.9','2022-09-21 19:23:17'),
(732,'editor','sql-changelog-5.2.0-2021-05-31.sql','e71bdae132fb3960ab7567223c8fe178','5.7.9','2022-09-21 19:23:17'),
(733,'editor','sql-changelog-5.2.1-2021-06-08.sql','684cd0fb368783212c0d392fefa55dff','5.7.9','2022-09-21 19:23:17'),
(734,'editor','sql-changelog-5.2.2-2021-06-09.sql','2e122504ab2999ba7632b5e14dac6ab0','5.7.9','2022-09-21 19:23:17'),
(735,'editor','sql-changelog-5.2.3-2021-06-24.sql','958d901ab1afcce83d17a58963ab9bf3','5.7.9','2022-09-21 19:23:17'),
(736,'editor','sql-changelog-5.2.4-2021-07-06.sql','3bb411661135970af4b55e2f5a48ca29','5.7.9','2022-09-21 19:23:17'),
(737,'editor','sql-changelog-5.2.5-2021-07-20.sql','3442403e2e8d1db65c3c62ccb45b9428','5.7.9','2022-09-21 19:23:17'),
(738,'editor','sql-changelog-5.2.6-2021-08-04.sql','f9f6b3e190509348b17640a6b1c1d2bc','5.7.9','2022-09-21 19:23:17'),
(739,'editor','sql-changelog-5.2.7-2021-08-06.sql','d6832d668bce91d45afa39c4aafcc747','5.7.9','2022-09-21 19:23:17'),
(740,'editor','sql-changelog-5.5.0-2021-09-30.sql','6c50999e8050b76a2cb22bb8957d4078','5.7.9','2022-09-21 19:23:17'),
(741,'editor','sql-changelog-5.5.1-2021-10-07.sql','bf2a370a79fa06f88f7ced6fd0e72d8a','5.7.9','2022-09-21 19:23:17'),
(742,'editor','sql-changelog-5.5.2-2021-10-11.sql','a4cec4328d820dc8af7a641dbaa05c03','5.7.9','2022-09-21 19:23:17'),
(743,'editor','sql-changelog-5.5.3-2021-10-28.sql','7dbe7669307b57c6e3a084fe5569d7db','5.7.9','2022-09-21 19:23:17'),
(744,'editor','sql-changelog-5.5.4-2021-11-15.sql','f1659fe8c93fffae59a8cc4d16c1357c','5.7.9','2022-09-21 19:23:17'),
(745,'editor','sql-changelog-5.5.5-2021-12-08.sql','1718c68e612602baae16eb414caf5075','5.7.9','2022-09-21 19:23:17'),
(746,'editor','sql-changelog-5.5.6-2021-12-17.sql','3bebb26721e2a93ccb35220ec020d138','5.7.9','2022-09-21 19:23:17'),
(747,'editor','sql-changelog-5.6.0-2022-02-03.sql','110997a50dce06111db86c4c2fd7a27b','5.7.9','2022-09-21 19:23:17'),
(748,'editor','sql-changelog-5.6.1-2022-02-09.sql','885dc713a912d00c72f3063add9ef7f5','5.7.9','2022-09-21 19:23:17'),
(749,'editor','sql-changelog-5.6.10-2022-04-07.sql','bcc8387ddeb973e646179e3b254780e4','5.7.9','2022-09-21 19:23:17'),
(750,'editor','sql-changelog-5.6.2-2022-02-17.sql','2eed9c544f2d71db5e7dce16bbcbf3ef','5.7.9','2022-09-21 19:23:17'),
(751,'editor','sql-changelog-5.6.3-2022-02-24.sql','8f5b6504f1e86bdc0ef61469f338074d','5.7.9','2022-09-21 19:23:17'),
(752,'editor','sql-changelog-5.6.4-2022-03-03.sql','c50b01626f5fc2edafc8c65d6c612776','5.7.9','2022-09-21 19:23:17'),
(753,'editor','sql-changelog-5.6.5-2022-03-07.sql','c36bb85a021727fba43a86178296e3ca','5.7.9','2022-09-21 19:23:17'),
(754,'editor','sql-changelog-5.6.6-2022-03-08.sql','3b176b11c9218f5c1064f1754ef4251e','5.7.9','2022-09-21 19:23:17'),
(755,'editor','sql-changelog-5.6.7-2022-03-17.sql','72d9ce60ab03eea822c20856e4d2c69b','5.7.9','2022-09-21 19:23:17'),
(756,'editor','sql-changelog-5.6.8-2022-03-22.sql','f400999145a7003190059aa6a96bdef8','5.7.9','2022-09-21 19:23:17'),
(757,'editor','sql-changelog-5.6.9-2022-03-30.sql','65119ab50cfc108668f738bd20ab63c5','5.7.9','2022-09-21 19:23:17'),
(758,'editor','sql-changelog-5.7.0-2022-04-26.sql','90fc0ab5cdc5f014a6a015d555fefc42','5.7.9','2022-09-21 19:23:17'),
(759,'editor','sql-changelog-5.7.1-2022-05-10.sql','6ec915d7b5416fa7c35d697091f958c7','5.7.9','2022-09-21 19:23:17'),
(760,'editor','sql-changelog-5.7.2-2022-05-24.sql','aa9a78ab2669653bf2347f6e4d103c4f','5.7.9','2022-09-21 19:23:17'),
(761,'editor','sql-changelog-5.7.3-2022-06-14.sql','5577e53126b1029887e1560f2592fab2','5.7.9','2022-09-21 19:23:17'),
(762,'editor','sql-changelog-5.7.4-2022-06-30.sql','16fbdc897f4bfb6f6724051c5ede07e3','5.7.9','2022-09-21 19:23:17'),
(763,'editor','sql-changelog-5.7.5-2022-07-22.sql','5618f323e22a8bf211b820277d7e9822','5.7.9','2022-09-21 19:23:17'),
(764,'editor','sql-changelog-5.7.6-2022-08-05.sql','289cdf36d8e73aedac1c6eb3c48523cf','5.7.9','2022-09-21 19:23:17'),
(765,'editor','sql-changelog-5.7.7-2022-08-09.sql','ed50df4b1211fb28e083e2b4981bb0ea','5.7.9','2022-09-21 19:23:17'),
(766,'editor','sql-changelog-5.7.8-2022-08-18.sql','0d384e12e6d248061daa54c12fe1149f','5.7.9','2022-09-21 19:23:17'),
(767,'editor','sql-changelog-5.7.9-2022-09-01.sql','2cfa499e0fae5761d373ecd2cee28204','5.7.9','2022-09-21 19:23:17'),
(768,'MatchAnalysis','006-editorPlugin-MatchAnalysis-mysql.sql','ea1cddb531b75a5811e1052588174459','5.7.9','2022-09-21 19:23:17'),
(769,'MatchAnalysis','007-editorPlugin-MatchAnalysis-mysql.sql','0adfdfaf9bf1527bd1c4f157d25c13e8','5.7.9','2022-09-21 19:23:17'),
(770,'MatchAnalysis','008-TRANSLATE-2045-utf8mb4-fixes.sql','a4dd69bfb59cc8f9c686906258a90071','5.7.9','2022-09-21 19:23:17'),
(771,'MatchAnalysis','008-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','e180ccd0ad83026961d821e53592e531','5.7.9','2022-09-21 19:23:17'),
(772,'MatchAnalysis','009-TRANSLATE-2294-additional-langres-tags.sql','a26d952c903a62bdf8a4360994fceb1c','5.7.9','2022-09-21 19:23:17'),
(773,'MatchAnalysis','010-TRANSLATE-2427-pretranslation-worker-problems.sql','b41b6ab986601d42485379de19a92f72','5.7.9','2022-09-21 19:23:17'),
(774,'MatchAnalysis','011-TRANSLATE-2077-match_analysis_xml_export.sql','b294eb9fe29980a6d8c05f5abf749bf5','5.7.9','2022-09-21 19:23:17'),
(775,'MatchAnalysis','012-TRANSLATE-2196-AutoQA.sql','1a2de9a0ea6abd7f8176d4939bf0e6ac','5.7.9','2022-09-21 19:23:17'),
(776,'MatchAnalysis','013-TRANSLATE-2546-fill-uuid-column-for-legacy-analysis.sql','bb2f6b75817d5dce3d60e882fb17c72f','5.7.9','2022-09-21 19:23:17'),
(777,'MatchAnalysis','014-TRANSLATE-2076-define-analysis-fuzzy-ranges.sql','a1eb1d851ee65f50477afbdc0c70dba2','5.7.9','2022-09-21 19:23:17'),
(778,'MatchAnalysis','015-TRANSLATE-2076-define-analysis-fuzzy-ranges.sql','2c8d5aaf38a881bdd1db7b70ac83a4a8','5.7.9','2022-09-21 19:23:17'),
(779,'MatchAnalysis','016-TRANSLATE-2740-autoqa-analysis.sql','745cf4fbd2363fdfbb34da75a9d66663','5.7.9','2022-09-21 19:23:17'),
(780,'MatchAnalysis','016-TRANSLATE-2923-inContextExact-usage.sql','de73631799d989d279ff4bd231663e9f','5.7.9','2022-09-21 19:23:17'),
(781,'MatchAnalysis','017-TRANSLATE-2822-match-analysis-on-a-character-base.sql','96966893ab16ad421187dc6e7a1a3a96','5.7.9','2022-09-21 19:23:17'),
(782,'MatchAnalysis','018-Fix-outdated-termtagger-dependency.sql','b720a59bc8d483e69fc95f2dcd56dfca','5.7.9','2022-09-21 19:23:17'),
(783,'MatchAnalysis','019-TRANSLATE-2855-pre-translate-pivot.php','c057f15a70ea611da5ae790b6c5d5561','5.7.9','2022-09-21 19:23:17'),
(784,'TermTagger','003-TermTagger-AutoQA.sql','25966436008e9a80834b6d8f92d6b1b5','5.7.9','2022-09-21 19:23:17'),
(785,'TermTagger','004-TRANSLATE-2740-autoqa-analysis.sql','5698e6018b5cc805f3e5648a0e7ccb37','5.7.9','2022-09-21 19:23:17'),
(786,'TermTagger','005-Fix-outdated-termtagger-dependency.sql','97ba24c4dcc8aec3aac328e88cecabf5','5.7.9','2022-09-21 19:23:17'),
(787,'TermTagger','006-TRANSLATE-Show-and-use-only-terms-of-a-certain-process-level-in-the-editor.sql','f82cf9e7d6b2b22907110ccb15f4800d','5.7.9','2022-09-21 19:23:17'),
(788,'LockSegmentsBasedOnConfig','003-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','a125d4641d8faa8cc2e07efed0a62ab7','5.7.9','2022-09-21 19:23:17'),
(789,'LockSegmentsBasedOnConfig','004-Fix-outdated-termtagger-dependency.sql','0f93a2cf9edd2c856c06bfeffc636eee','5.7.9','2022-09-21 19:23:17'),
(790,'SpellCheck','002-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','e1bce08ed89b9b5fed081b6b1c88ef99','5.7.9','2022-09-21 19:23:17'),
(791,'SpellCheck','003-TRANSLATE-2538_SpellCheck_as_AutoQA_things.sql','b13b1c2e686fa41cfe86d0adc6cbb537','5.7.9','2022-09-21 19:23:17'),
(792,'SpellCheck','004-TRANSLATE-2538_SpellCheck_HOTFIX.sql','f684668b393d6fe4229c09179793b4c8','5.7.9','2022-09-21 19:23:17'),
(793,'SpellCheck','005-TRANSLATE-2538_SpellCheck_HOTFIX2.sql','3fffa13ca5ab5db4c5c35d7b2f2a667d','5.7.9','2022-09-21 19:23:17'),
(794,'Okapi','003-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','b04d82823f4b4a2b90cd6b0af85dca0b','5.7.9','2022-09-21 19:23:17'),
(795,'Okapi','004-TRANSLATE-2432_Make_default_bconf_path_configurable.sql','d7377bd5ff21015e443dacc756b03797','5.7.9','2022-09-21 19:23:17'),
(796,'Okapi','005-TRANSLATE-2266-okapi-bconf-management.sql','ec4349df4134714ea851dcd8abde0eae','5.7.9','2022-09-21 19:23:17'),
(797,'Okapi','006-Fix-outdated-termtagger-dependency.sql','b371b1b7a87be67b8d50875fec655c82','5.7.9','2022-09-21 19:23:17'),
(798,'Okapi','007-TRANSLATE-2063-enable-parallele-use-of-okapi.sql','aac93f8acb1f872769b972e0b74b8113','5.7.9','2022-09-21 19:23:17'),
(799,'Okapi','007-TRANSLATE-2932-Okapi-Filters.sql','ea319f457087c4269f874eddde1be697','5.7.9','2022-09-21 19:23:17'),
(800,'Okapi','008-TRANSLATE-2932-Okapi-Filters.php','869f518b6d66d8888f256889776d0b0d','5.7.9','2022-09-21 19:23:17'),
(801,'Okapi','009-TRANSLATE-2063-enable-parallele-use-of-okapi.php','1e03fa604a69f7edfa64e10f42c7feeb','5.7.9','2022-09-21 19:23:17'),
(802,'FrontEndMessageBus','002-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','a4327a06bc790badc81469a41985ac63','5.7.9','2022-09-21 19:23:17'),
(803,'FrontEndMessageBus','003-TRANSLATE-2596-error-in-core-e1352.sql','8a2bb560c561d995d0891bea824b3a24','5.7.9','2022-09-21 19:23:17'),
(804,'ArchiveTaskBeforeDelete','002-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','7cb905b3dbd193b6d9a84c6affedfadc','5.7.9','2022-09-21 19:23:17'),
(805,'IpAuthentication','001-editorPlugin-IpAuthentication-mysql.sql','ea871ff50bbc86fb8b0bfbe03aedeb19','5.7.9','2022-09-21 19:23:17'),
(806,'IpAuthentication','002-editorPlugin-IpAuthentication-mysql.sql','484afed2773c83542d60599d45443888','5.7.9','2022-09-21 19:23:17'),
(807,'IpAuthentication','003-TRANSLATE-3019-remove-ipAdresses-config.sql','d57cb52b65ed35ff81cff3d1dcf3f02b','5.7.9','2022-09-21 19:23:17'),
(808,'ModelFront','002-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','be5ca82132d3c92e2417ee55062ec53c','5.7.9','2022-09-21 19:23:17'),
(809,'SegmentStatistics','007-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','5aa1903aa0ab64e2d1ed9b088db3471b','5.7.9','2022-09-21 19:23:17'),
(810,'SegmentStatistics','008-Fix-outdated-termtagger-dependency.sql','509738de8f92e313ee24be711f8c9c8c','5.7.9','2022-09-21 19:23:17'),
(811,'PangeaMt','002-editorPlugin-PangeaMt-mysql-request-timeout.sql','280afda86f4b1f6712e401ba2b33c947','5.7.9','2022-09-21 19:23:17'),
(812,'PangeaMt','003-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','678f4c7179d5fedd54873ab5a8eac9d1','5.7.9','2022-09-21 19:23:17'),
(813,'Transit','001-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','cd6825bdeeeabd4dd3b629628e2f9a3e','5.7.9','2022-09-21 19:23:17'),
(814,'NoMissingTargetTerminology','003-Fix-outdated-termtagger-dependency.sql','5c218e343ac51ec200a6865bf89eabd3','5.7.9','2022-09-21 19:23:17'),
(815,'NecTm','003-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','9709856aede5d0845d732cae4923ecff','5.7.9','2022-09-21 19:23:17'),
(816,'GlobalesePreTranslation','002-TRANSLATE-471_Overwrite_system_config_by_client_and_task.sql','c9e303adab22f9ada71f9a40aa7f2575','5.7.9','2022-09-21 19:23:17'),
(817,'GlobalesePreTranslation','003-Fix-outdated-termtagger-dependency.sql','9d9495de89b90170a5292b63e8952b59','5.7.9','2022-09-21 19:23:17'),
(818,'zfextended','039-TRANSLATE-3051-passwd-hash.sql','d3d1a188c0c72d91b99a5688a1257549','5.7.11','2022-09-23 10:26:06'),
(819,'zfextended','040-TRANSLATE-3051-passwd-hash.php','332eaa3a755f9c86fb2937c4e63cdfe9','5.7.11','2022-09-23 10:26:06'),
(820,'editor','368-systemcheck.sql','ac82a90b90c68097ebd60887bd16feab','5.7.11','2022-09-23 10:26:06'),
(821,'editor','369-TRANSLATE-2932-Okapi-Filters-cleanup.php','e5c7c60d5c8c767682f4b3860e7bcc3b','5.7.11','2022-09-23 10:26:06'),
(822,'editor','370-TRANSLATE-2855-fix-pivot-auto-assign.sql','a2fe76e5f919a38fbcaf5fd21f0f9db9','5.7.11','2022-09-23 10:26:06'),
(823,'editor','sql-changelog-5.7.10-2022-09-20.sql','0347031f3dd0710b287e9bbeece01ba1','5.7.11','2022-09-23 10:26:06'),
(824,'editor','sql-changelog-5.7.11-2022-09-22.sql','2c45a5d7cc54e8e05997ef1922dcd527','5.7.11','2022-09-23 10:26:06'),
(825,'SpellCheck','006-TRANSLATE-3035-ui-spellcheck-is-not-working.sql','c426d89d2964e0109229bba15b9a1b61','5.7.11','2022-09-23 10:26:06');

--
-- Table structure for table `Zf_errorlog`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_errorlog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created` timestamp NULL DEFAULT current_timestamp() COMMENT 'first occurence of error',
  `last` timestamp NULL DEFAULT NULL COMMENT 'last occurence of error',
  `duplicates` int(11) NOT NULL DEFAULT 0 COMMENT 'count of duplicates of same error between created and last',
  `duplicateHash` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'hash to identify duplicates',
  `level` tinyint(2) NOT NULL DEFAULT 4 COMMENT 'Error level: FATAL: 1; ERROR: 2; WARN: 4; INFO: 8; DEBUG: 16; TRACE: 32;',
  `domain` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'core' COMMENT 'filterable, hierarchical context domain of the error',
  `worker` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'worker class if error happened in a worker',
  `eventCode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'E0000' COMMENT 'Project unique event code (yeah, not only errors are logged)',
  `message` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'human readable description of the error',
  `appVersion` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the current application version',
  `file` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'file where the error happened',
  `line` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'line where the error happened',
  `trace` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'stack trace to the error',
  `extra` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'extra data to the error',
  `httpHost` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the HTTP host which was requested',
  `url` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the called URL',
  `method` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the called HTTP Method',
  `userLogin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the authenticated user',
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the authenticated user',
  PRIMARY KEY (`id`),
  KEY `origin_level` (`domain`,`level`),
  KEY `duplicateHash` (`duplicateHash`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_errorlog`
--

--
-- Table structure for table `Zf_invalidlogin`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_invalidlogin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_invalidlogin`
--


--
-- Table structure for table `Zf_login_log`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `login` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `way` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_login_log`
--


--
-- Table structure for table `Zf_memcache`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_memcache` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` varchar(4096) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastModified` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expire` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`,`expire`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_memcache`
--


--
-- Table structure for table `Zf_passwdreset`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_passwdreset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resetHash` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `expiration` int(11) DEFAULT NULL,
  `internalSessionUniqId` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `Zf_passwdreset_userId_FK` (`userId`),
  CONSTRAINT `Zf_passwdreset_userId_FK` FOREIGN KEY (`userId`) REFERENCES `Zf_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_passwdreset`
--


--
-- Table structure for table `Zf_users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passwd` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `editable` tinyint(1) NOT NULL DEFAULT 1,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parentIds` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customers` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `openIdIssuer` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `openIdSubject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `userGuid_2` (`userGuid`),
  KEY `userGuid` (`userGuid`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_users`
--

INSERT INTO `Zf_users` VALUES
(1,'{00000000-0000-0000-0000-000000000000}','system','user','m','system','noreply@translate5.net','','asdfasdfsdfsf',0,NULL,NULL,'',NULL,NULL),
(2,'{e6828cdf-2ee0-4a25-af0a-92e6f060e9eb}','Project','Manager','m','manager','noreply@translate5.net','systemadmin,admin,pm,editor','6a204bd89f3c8348afd5c77c717a097a',0,'de',NULL,'',NULL,NULL),
(3,'{f68616ee-9182-4fb7-9dec-1bc125e19cef}','Proof','Reader One','m','proofreader1','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,'en',NULL,'',NULL,NULL),
(4,'{294da45d-63c2-45b1-9c6e-73329129d7f0}','Proof','Reader Two','f','proofreader2','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL),
(5,'{c99ae747-0afb-446a-827a-6b3b32cfb819}','Trans','Lator One','f','translator1','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,'en',NULL,'',NULL,NULL),
(6,'{e5a23547-ffba-4ac8-95ab-6ced30778483}','Trans','Lator Two','m','translator2','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL),
(7,'{94ff4a53-dae0-4793-beae-1f09968c3c93}','Visi','Tor','f','visitor','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,'en',NULL,'',NULL,NULL),
(17,'{65751ea9-6834-444f-bd3a-2de95a335675}','Project','Manager','f','manager1','noreply@translate5.net','editor,pm','6a204bd89f3c8348afd5c77c717a097a',1,'en',NULL,'',NULL,NULL),
(19,'{3ee4510d-a33c-4cfc-be8b-f67d0696d726}','Project','Manager','m','manager2','noreply@translate5.net','editor,pm','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL),
(20,'{0973ca5c-c075-477d-9cdc-6ea920b6e792}','Project','Manager','m','manager3','noreply@translate5.net','editor,pm','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL),
(21,'{8ce8379e-0f5f-4708-99ad-c4f62627a49c}','Project','Manager','m','manager4','noreply@translate5.net','editor,pm','6a204bd89f3c8348afd5c77c717a097a',1,'en',NULL,'',NULL,NULL),
(22,'{82e86f75-7e3f-4bf8-aceb-5ff16e474052}','Proof','Reader Three','m','proofreader3','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL),
(23,'{4a74d898-d0f5-4b58-9a5d-8a93ddde418f}','Proof','Reader Four','m','proofreader4','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL),
(24,'{6ee122c2-9fbc-4e52-81e1-e54b3677d052}','Trans','Lator Three','m','translator3','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL),
(25,'{e1be7132-8edb-4b94-bc31-2501dd50a1a5}','Trans','Lator Four','m','translator4','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL),
(26,'{dae91e44-4e34-42df-ace4-a82b4c18d152}','Visi','Tor2','m','visitor1','noreply@translate5.net','editor','6a204bd89f3c8348afd5c77c717a097a',1,NULL,NULL,'',NULL,NULL);

--
-- Table structure for table `Zf_worker`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_worker` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parentId` int(11) DEFAULT 0,
  `state` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting',
  `worker` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `resource` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `slot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maxParallelProcesses` int(11) NOT NULL DEFAULT 1,
  `taskGuid` varchar(38) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `parameters` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `starttime` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `endtime` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maxRuntime` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `blockingType` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'slot',
  `progress` double(19,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker` (`worker`),
  KEY `taskGuid` (`taskGuid`),
  KEY `state` (`state`),
  KEY `Zf_worker_id_IDX` (`worker`,`state`,`taskGuid`),
  CONSTRAINT `zf_worker_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_worker`
--


--
-- Table structure for table `Zf_worker_dependencies`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_worker_dependencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `worker` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the worker class name',
  `dependency` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'the worker class name, which is dependent - that means that it should be processes before the worker for the same task',
  PRIMARY KEY (`id`),
  UNIQUE KEY `workerDependencyUnique` (`worker`,`dependency`)
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Zf_worker_dependencies`
--

INSERT INTO `Zf_worker_dependencies` VALUES
(83,'editor_Models_Export_Exported_FiletranslationWorker','editor_Models_Export_Worker'),
(84,'editor_Models_Export_Exported_FiletranslationWorker','editor_Models_Export_Xliff2Worker'),
(85,'editor_Models_Export_Exported_FiletranslationWorker','editor_Plugins_Okapi_Worker'),
(86,'editor_Models_Export_Exported_FiletranslationWorker','editor_Plugins_SegmentStatistics_CleanUpWorker'),
(87,'editor_Models_Export_Exported_FiletranslationWorker','editor_Plugins_SegmentStatistics_Worker'),
(88,'editor_Models_Export_Exported_FiletranslationWorker','editor_Plugins_SegmentStatistics_WriteStatisticsWorker'),
(76,'editor_Models_Export_Exported_TransferWorker','editor_Models_Export_Worker'),
(77,'editor_Models_Export_Exported_TransferWorker','editor_Models_Export_Xliff2Worker'),
(78,'editor_Models_Export_Exported_TransferWorker','editor_Plugins_Okapi_Worker'),
(79,'editor_Models_Export_Exported_TransferWorker','editor_Plugins_SegmentStatistics_CleanUpWorker'),
(80,'editor_Models_Export_Exported_TransferWorker','editor_Plugins_SegmentStatistics_Worker'),
(81,'editor_Models_Export_Exported_TransferWorker','editor_Plugins_SegmentStatistics_WriteStatisticsWorker'),
(90,'editor_Models_Export_Exported_ZipDefaultWorker','editor_Models_Export_Worker'),
(91,'editor_Models_Export_Exported_ZipDefaultWorker','editor_Models_Export_Xliff2Worker'),
(92,'editor_Models_Export_Exported_ZipDefaultWorker','editor_Plugins_Okapi_Worker'),
(93,'editor_Models_Export_Exported_ZipDefaultWorker','editor_Plugins_SegmentStatistics_CleanUpWorker'),
(94,'editor_Models_Export_Exported_ZipDefaultWorker','editor_Plugins_SegmentStatistics_Worker'),
(95,'editor_Models_Export_Exported_ZipDefaultWorker','editor_Plugins_SegmentStatistics_WriteStatisticsWorker'),
(5,'editor_Models_Export_ExportedWorker','editor_Models_Export_Worker'),
(46,'editor_Models_Export_ExportedWorker','editor_Models_Export_Xliff2Worker'),
(55,'editor_Models_Export_ExportedWorker','editor_Plugins_Okapi_Worker'),
(1,'editor_Models_Export_ExportedWorker','editor_Plugins_SegmentStatistics_CleanUpWorker'),
(2,'editor_Models_Export_ExportedWorker','editor_Plugins_SegmentStatistics_Worker'),
(3,'editor_Models_Export_ExportedWorker','editor_Plugins_SegmentStatistics_WriteStatisticsWorker'),
(45,'editor_Models_Export_Xliff2Worker','editor_Models_Export_Worker'),
(42,'editor_Models_Import_Worker','editor_Models_Import_Worker_FileTree'),
(43,'editor_Models_Import_Worker','editor_Models_Import_Worker_ReferenceFileTree'),
(39,'editor_Models_Import_Worker','editor_Plugins_Okapi_Worker'),
(59,'editor_Models_Import_Worker_FinalStep','editor_Models_Import_Worker'),
(58,'editor_Models_Import_Worker_FinalStep','editor_Models_Import_Worker_SetTaskToOpen'),
(44,'editor_Models_Import_Worker_ReferenceFileTree','editor_Models_Import_Worker_FileTree'),
(57,'editor_Models_Import_Worker_ReferenceFileTree','editor_Plugins_Okapi_Worker'),
(4,'editor_Models_Import_Worker_SetTaskToOpen','editor_Models_Import_Worker'),
(32,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_GlobalesePreTranslation_Worker'),
(12,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_LockSegmentsBasedOnConfig_Worker'),
(107,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_MatchAnalysis_BatchWorker'),
(64,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_MatchAnalysis_Worker'),
(60,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_ModelFront_Worker'),
(35,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_NoMissingTargetTerminology_Worker'),
(41,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_Okapi_Worker'),
(18,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_SegmentStatistics_CleanUpWorker'),
(20,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_SegmentStatistics_Worker'),
(25,'editor_Models_Import_Worker_SetTaskToOpen','editor_Plugins_SegmentStatistics_WriteStatisticsWorker'),
(68,'editor_Models_Import_Worker_SetTaskToOpen','editor_Segment_Quality_ImportFinishingWorker'),
(99,'editor_Models_Import_Worker_SetTaskToOpen','editor_Segment_Quality_OperationFinishingWorker'),
(124,'editor_Models_Import_Worker_SetTaskToOpen','MittagQI\\Translate5\\LanguageResource\\Pretranslation\\BatchCleanupWorker'),
(100,'editor_Models_Import_Worker_SetTaskToOpen','MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker'),
(30,'editor_Plugins_GlobalesePreTranslation_Worker','editor_Models_Import_Worker'),
(65,'editor_Plugins_GlobalesePreTranslation_Worker','editor_Plugins_MatchAnalysis_Worker'),
(13,'editor_Plugins_LockSegmentsBasedOnConfig_Worker','editor_Models_Import_Worker'),
(10,'editor_Plugins_LockSegmentsBasedOnConfig_Worker','editor_Plugins_NoMissingTargetTerminology_Worker'),
(11,'editor_Plugins_LockSegmentsBasedOnConfig_Worker','editor_Plugins_SegmentStatistics_Worker'),
(114,'editor_Plugins_LockSegmentsBasedOnConfig_Worker','editor_Segment_Quality_ImportFinishingWorker'),
(105,'editor_Plugins_MatchAnalysis_BatchWorker','editor_Models_Import_Worker'),
(110,'editor_Plugins_MatchAnalysis_BatchWorker','editor_Task_Operation_StartingWorker'),
(62,'editor_Plugins_MatchAnalysis_Worker','editor_Models_Import_Worker'),
(106,'editor_Plugins_MatchAnalysis_Worker','editor_Plugins_MatchAnalysis_BatchWorker'),
(109,'editor_Plugins_MatchAnalysis_Worker','editor_Task_Operation_StartingWorker'),
(36,'editor_Plugins_NoMissingTargetTerminology_Worker','editor_Models_Import_Worker'),
(34,'editor_Plugins_NoMissingTargetTerminology_Worker','editor_Plugins_SegmentStatistics_Worker'),
(121,'editor_Plugins_NoMissingTargetTerminology_Worker','editor_Segment_Quality_ImportFinishingWorker'),
(54,'editor_Plugins_Okapi_Worker','editor_Models_Export_Worker'),
(56,'editor_Plugins_Okapi_Worker','editor_Models_Import_Worker_FileTree'),
(6,'editor_Plugins_SegmentStatistics_CleanUpWorker','editor_Models_Export_Worker'),
(47,'editor_Plugins_SegmentStatistics_CleanUpWorker','editor_Models_Export_Xliff2Worker'),
(27,'editor_Plugins_SegmentStatistics_CleanUpWorker','editor_Models_Import_Worker'),
(17,'editor_Plugins_SegmentStatistics_CleanUpWorker','editor_Plugins_LockSegmentsBasedOnConfig_Worker'),
(15,'editor_Plugins_SegmentStatistics_CleanUpWorker','editor_Plugins_NoMissingTargetTerminology_Worker'),
(16,'editor_Plugins_SegmentStatistics_CleanUpWorker','editor_Plugins_SegmentStatistics_Worker'),
(7,'editor_Plugins_SegmentStatistics_Worker','editor_Models_Export_Worker'),
(48,'editor_Plugins_SegmentStatistics_Worker','editor_Models_Export_Xliff2Worker'),
(28,'editor_Plugins_SegmentStatistics_Worker','editor_Models_Import_Worker'),
(120,'editor_Plugins_SegmentStatistics_Worker','editor_Segment_Quality_ImportFinishingWorker'),
(8,'editor_Plugins_SegmentStatistics_WriteStatisticsWorker','editor_Models_Export_Worker'),
(49,'editor_Plugins_SegmentStatistics_WriteStatisticsWorker','editor_Models_Export_Xliff2Worker'),
(29,'editor_Plugins_SegmentStatistics_WriteStatisticsWorker','editor_Models_Import_Worker'),
(24,'editor_Plugins_SegmentStatistics_WriteStatisticsWorker','editor_Plugins_LockSegmentsBasedOnConfig_Worker'),
(22,'editor_Plugins_SegmentStatistics_WriteStatisticsWorker','editor_Plugins_NoMissingTargetTerminology_Worker'),
(26,'editor_Plugins_SegmentStatistics_WriteStatisticsWorker','editor_Plugins_SegmentStatistics_CleanUpWorker'),
(23,'editor_Plugins_SegmentStatistics_WriteStatisticsWorker','editor_Plugins_SegmentStatistics_Worker'),
(115,'editor_Plugins_SpellCheck_Worker_Import','editor_Segment_Quality_ImportWorker'),
(116,'editor_Plugins_SpellCheck_Worker_Import','editor_Segment_Quality_OperationWorker'),
(113,'editor_Plugins_TermTagger_Worker_Remove','editor_Segment_Quality_OperationWorker'),
(70,'editor_Plugins_TermTagger_Worker_SetTaskToOpen','editor_Plugins_TermTagger_Worker_TermTaggerImport'),
(118,'editor_Segment_Quality_ImportFinishingWorker','editor_Plugins_SpellCheck_Worker_Import'),
(69,'editor_Segment_Quality_ImportFinishingWorker','editor_Plugins_TermTagger_Worker_TermTaggerImport'),
(67,'editor_Segment_Quality_ImportFinishingWorker','editor_Segment_Quality_ImportWorker'),
(66,'editor_Segment_Quality_ImportWorker','editor_Models_Import_Worker'),
(122,'editor_Segment_Quality_ImportWorker','editor_Plugins_GlobalesePreTranslation_Worker'),
(108,'editor_Segment_Quality_ImportWorker','editor_Plugins_MatchAnalysis_Worker'),
(119,'editor_Segment_Quality_ImportWorker','editor_Plugins_Okapi_Worker'),
(117,'editor_Segment_Quality_OperationFinishingWorker','editor_Plugins_SpellCheck_Worker_Import'),
(74,'editor_Segment_Quality_OperationFinishingWorker','editor_Plugins_TermTagger_Worker_Remove'),
(73,'editor_Segment_Quality_OperationFinishingWorker','editor_Plugins_TermTagger_Worker_TermTaggerImport'),
(72,'editor_Segment_Quality_OperationFinishingWorker','editor_Segment_Quality_OperationWorker'),
(123,'editor_Segment_Quality_OperationWorker','editor_Plugins_GlobalesePreTranslation_Worker'),
(111,'editor_Segment_Quality_OperationWorker','editor_Plugins_MatchAnalysis_BatchWorker'),
(112,'editor_Segment_Quality_OperationWorker','editor_Plugins_MatchAnalysis_Worker'),
(71,'editor_Segment_Quality_OperationWorker','editor_Task_Operation_StartingWorker'),
(75,'editor_Task_Operation_FinishingWorker','editor_Segment_Quality_OperationFinishingWorker'),
(126,'MittagQI\\Translate5\\LanguageResource\\Pretranslation\\BatchCleanupWorker','editor_Plugins_MatchAnalysis_Worker'),
(125,'MittagQI\\Translate5\\LanguageResource\\Pretranslation\\BatchCleanupWorker','MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker'),
(101,'MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker','editor_Models_Import_Worker'),
(104,'MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker','editor_Plugins_MatchAnalysis_BatchWorker'),
(103,'MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker','editor_Plugins_MatchAnalysis_Worker'),
(102,'MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker','editor_Task_Operation_StartingWorker'),
(98,'MittagQI\\Translate5\\Workflow\\ArchiveWorker','editor_Models_Export_Worker'),
(97,'MittagQI\\Translate5\\Workflow\\ArchiveWorker','editor_Models_Export_Xliff2Worker');

--
-- Table structure for table `session`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `session_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `authToken` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modified` int(11) DEFAULT NULL,
  `lifetime` int(11) DEFAULT NULL,
  `session_data` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`session_id`,`name`),
  UNIQUE KEY `authToken` (`authToken`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--


--
-- Table structure for table `sessionMapInternalUniqId`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessionMapInternalUniqId` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `internalSessionUniqId` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `internalSessionUniqId` (`internalSessionUniqId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessionMapInternalUniqId`
--


--
-- Table structure for table `sessionUserLock`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessionUserLock` (
  `login` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `internalSessionUniqId` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`login`),
  KEY `userlock_sessionid_fk` (`internalSessionUniqId`),
  CONSTRAINT `sessionUserLock_ibfk_1` FOREIGN KEY (`internalSessionUniqId`) REFERENCES `sessionMapInternalUniqId` (`internalSessionUniqId`) ON DELETE CASCADE,
  CONSTRAINT `sessionUserLock_ibfk_2` FOREIGN KEY (`login`) REFERENCES `Zf_users` (`login`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessionUserLock`
--


--
-- Table structure for table `terms_attributes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `language` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termId` int(11) DEFAULT NULL,
  `termTbxId` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dataTypeId` int(11) DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isCreatedLocally` tinyint(1) NOT NULL DEFAULT 0,
  `createdBy` int(11) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatedBy` int(11) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `termEntryGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `langSetGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `elementName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attrLang` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isDescripGrp` tinyint(1) DEFAULT 0,
  `isDraft` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Created using batchwindow, but Save-button not yet pressed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_attributes_guid_uindex` (`guid`),
  KEY `collectionId_idx` (`collectionId`),
  KEY `termId_idx` (`termId`),
  KEY `termEntryId_idx` (`termEntryId`),
  KEY `termTbxId_idx` (`termTbxId`),
  KEY `dataTypeId_idx` (`dataTypeId`),
  KEY `ta_createdBy_fk` (`createdBy`),
  KEY `ta_updatedBy_fk` (`updatedBy`),
  KEY `isDraft_idx` (`isDraft`),
  CONSTRAINT `ta_createdBy_fk` FOREIGN KEY (`createdBy`) REFERENCES `Zf_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `ta_updatedBy_fk` FOREIGN KEY (`updatedBy`) REFERENCES `Zf_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tad_id` FOREIGN KEY (`dataTypeId`) REFERENCES `terms_attributes_datatype` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `terms_collection_ibfk_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_entry_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_term_ibfk_1` FOREIGN KEY (`termId`) REFERENCES `terms_term` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_attributes`
--


--
-- Table structure for table `terms_attributes_datatype`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_attributes_datatype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10nSystem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `l10nCustom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` set('entry','language','term') COLLATE utf8mb4_unicode_ci DEFAULT 'entry,language,term' COMMENT 'Level represented as comma separated values where the label(attribute) can appear. entry,language,term',
  `dataType` enum('plainText','noteText','basicText','picklist','Language code','date') COLLATE utf8mb4_unicode_ci DEFAULT 'plainText',
  `picklistValues` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Available comma separated values for selecting for the attribute when the attributa dataType is picklist.',
  `isTbxBasic` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_term_attributes_label_type_level` (`label`,`type`,`level`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_attributes_datatype`
--

INSERT INTO `terms_attributes_datatype` VALUES
(5,'termNote','termType','{\"de\":\"Benennungstyp\",\"en\":\"Term type\"}','{\"de\":\"\",\"en\":\"\"}','term','picklist','fullForm,acronym,abbreviation,shortForm,variant,phrase',1),
(6,'descrip','definition','{\"de\":\"Definition\",\"en\":\"Definition\"}','{\"de\":\"\",\"en\":\"\"}','entry,language','noteText',NULL,1),
(7,'termNote','abbreviatedFormFor','{\"de\":\"Abkürzung für\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','term','plainText',NULL,0),
(8,'termNote','pronunciation','{\"de\":\"Aussprache\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','term','plainText',NULL,0),
(9,'termNote','normativeAuthorization','{\"de\": \"Normative Berechtigung\", \"en\": \"Normative Authorization\"}','{\"de\":\"\",\"en\":\"\"}','term','picklist','admitted,admittedTerm,deprecated,deprecatedTerm,legalTerm,preferredTerm,proposed,regulatedTerm,standardizedTerm,supersededTerm',0),
(19,'descrip','subjectField','{\"de\":\"Sachgebiet\",\"en\":\"Subject field\"}','{\"de\":\"\",\"en\":\"\"}','entry','plainText',NULL,1),
(20,'descrip','relatedConcept','{\"de\":\"Verwandtes Konzept\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','entry,language,term','plainText',NULL,0),
(21,'descrip','relatedConceptBroader','{\"de\":\"Erweitertes verwandtes Konzept\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','entry,language,term','plainText',NULL,0),
(22,'admin','productSubset','{\"de\":\"Produkt-Untermenge\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','entry,language,term','plainText',NULL,0),
(23,'admin','sourceIdentifier','{\"de\":\"Quellenidentifikator\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','entry,language,term','plainText',NULL,0),
(24,'termNote','partOfSpeech','{\"de\":\"Wortart\",\"en\":\"Part of speech\"}','{\"de\":\"\",\"en\":\"\"}','term','picklist','noun,verb,adjective,adverb,properNoun,other',1),
(25,'descrip','context','{\"de\":\"Kontext\",\"en\":\"Context\"}','{\"de\":\"\",\"en\":\"\"}','term','noteText',NULL,1),
(26,'admin','businessUnitSubset','{\"de\":\"Teilbereich der Geschäftseinheit\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','entry,language,term','plainText',NULL,0),
(27,'admin','projectSubset','{\"de\":\"Projekt\",\"en\":\"Project\"}','{\"de\":\"\",\"en\":\"\"}','term','plainText',NULL,1),
(28,'termNote','grammaticalGender','{\"de\":\"Genus\",\"en\":\"Gender\"}','{\"de\":\"\",\"en\":\"\"}','term','picklist','masculine,feminine,neuter,other',1),
(29,'note',NULL,'{\"de\":\"Kommentar\",\"en\":\"Comment\"}','{\"de\":\"\",\"en\":\"\"}','entry,language,term','noteText',NULL,1),
(30,'termNote','administrativeStatus','{\"de\": \"Verwendungsstatus\", \"en\": \"Usage status\"}','{\"de\":\"\",\"en\":\"\"}','term','picklist','admitted,admittedTerm-admn-sts,deprecatedTerm-admn-sts,legalTerm-admn-sts,notRecommended,obsolete,preferred,preferredTerm-admn-sts,regulatedTerm-admn-sts,standardizedTerm-admn-sts,supersededTerm-admn-sts',1),
(31,'termNote','transferComment','{\"de\":\"Übertragungskommentar\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','term','plainText',NULL,0),
(32,'admin','entrySource','{\"de\":\"Quelle des Eintrags\",\"en\":\"\"}','{\"de\":\"\",\"en\":\"\"}','entry,language,term','plainText',NULL,0),
(33,'xref','xGraphic','{\"de\":\"Abbildung/Multimedia\",\"en\":\"Illustration / Multimedia\"}','{\"de\":\"\",\"en\":\"\"}','entry','plainText',NULL,1),
(36,'admin','source','{\"de\":\"Quelle\",\"en\":\"Source\"}','{\"de\":\"\",\"en\":\"\"}','term','noteText',NULL,1),
(37,'xref','externalCrossReference','{\"de\":\"externer Verweis\",\"en\":\"External reference\"}','{\"de\":\"\",\"en\":\"\"}','entry,term','plainText',NULL,1),
(38,'termNote','geographicalUsage','{\"de\":\"regionale Verwendung\",\"en\":\"Regional use\"}','{\"de\":\"\",\"en\":\"\"}','term','plainText',NULL,1),
(39,'termNote','termLocation','{\"de\":\"typischer Verwendungsfall\",\"en\":\"Typical use case\"}','{\"de\":\"\",\"en\":\"\"}','term','plainText',NULL,1),
(40,'ref','crossReference','{\"de\":\"Querverweis\",\"en\":\"Cross reference\"}','{\"de\":\"\",\"en\":\"\"}','entry,term','plainText',NULL,1),
(41,'admin','customerSubset','{\"de\":\"Kunde\",\"en\":\"TCustomer\"}','{\"de\":\"\",\"en\":\"\"}','term','plainText',NULL,1),
(42,'termNote','processStatus','{\"de\": \"Prozessstatus\", \"en\": \"Process status\"}','{\"de\":\"\",\"en\":\"\"}','term','picklist','unprocessed,provisionallyProcessed,finalized,rejected',1),
(43,'descrip','figure','{\"de\":\"Bild\",\"en\":\"Image\"}',NULL,'entry,language','plainText',NULL,1);

--
-- Table structure for table `terms_attributes_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_attributes_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attrId` int(11) NOT NULL,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `language` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termId` int(11) DEFAULT NULL,
  `dataTypeId` int(11) DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isCreatedLocally` tinyint(1) NOT NULL DEFAULT 0,
  `updatedBy` int(11) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `termEntryGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `langSetGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `elementName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attrLang` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tah_collectionId` (`collectionId`),
  KEY `tah_termEntryId` (`termEntryId`),
  KEY `tah_termId` (`termId`),
  KEY `tah_attrId` (`attrId`),
  KEY `tah_updatedBy_fk` (`updatedBy`),
  CONSTRAINT `tah_attrId` FOREIGN KEY (`attrId`) REFERENCES `terms_attributes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tah_collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tah_termEntryId` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tah_termId` FOREIGN KEY (`termId`) REFERENCES `terms_term` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tah_updatedBy_fk` FOREIGN KEY (`updatedBy`) REFERENCES `Zf_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_attributes_history`
--


--
-- Table structure for table `terms_collection_attribute_datatype`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_collection_attribute_datatype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) DEFAULT NULL,
  `dataTypeId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indexCollectionIdAndDataTypeId` (`collectionId`,`dataTypeId`),
  KEY `fk_terms_collection_attribute_datatype_1_idx` (`collectionId`),
  KEY `fk_terms_collection_attribute_datatype_2_idx` (`dataTypeId`),
  CONSTRAINT `fk_terms_collection_attribute_datatype_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_terms_collection_attribute_datatype_2` FOREIGN KEY (`dataTypeId`) REFERENCES `terms_attributes_datatype` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_collection_attribute_datatype`
--


--
-- Table structure for table `terms_images`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `targetId` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uniqueName` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `format` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collectionId` int(11) NOT NULL,
  `contentMd5hash` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'md5 hash of the file content, mainly to check if a file update on merge import is needed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueName_UNIQUE` (`uniqueName`),
  KEY `fk_terms_images_languageresources` (`collectionId`),
  CONSTRAINT `fk_terms_images_languageresources` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_images`
--


--
-- Table structure for table `terms_ref_object`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_ref_object` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) NOT NULL,
  `listType` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collectionId` (`collectionId`,`listType`,`key`),
  CONSTRAINT `terms_ref_object_ibfk_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_ref_object`
--


--
-- Table structure for table `terms_term`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_term` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `updatedBy` int(11) DEFAULT NULL COMMENT 'Local instance user (e.g. from Zf_users)',
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `languageId` int(11) NOT NULL,
  `language` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `term` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proposal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processStatus` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT 'finalized',
  `definition` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT '',
  `termEntryTbxId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `termTbxId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `termEntryGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `langSetGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tbxCreatedBy` int(11) DEFAULT NULL COMMENT 'transacgrp: creation responsiblePerson',
  `tbxCreatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tbxUpdatedBy` int(11) DEFAULT NULL COMMENT 'transacgrp: modification responsiblePerson',
  `tbxUpdatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_term_guid_uindex` (`guid`),
  KEY `collectionId_idx` (`collectionId`),
  KEY `termEntryId_idx` (`termEntryId`),
  KEY `termTbxId_idx` (`termTbxId`),
  KEY `languageId_collectionId_idx` (`collectionId`,`languageId`),
  KEY `termEntryTbxId_idx` (`termEntryTbxId`),
  KEY `tt_updatedBy_fk` (`updatedBy`),
  KEY `tt_tbxCreatedBy_fk` (`tbxCreatedBy`),
  KEY `tt_tbxUpdatedBy_fk` (`tbxUpdatedBy`),
  KEY `collectionId_termEntryTbxId_idx` (`collectionId`,`termEntryTbxId`),
  FULLTEXT KEY `fulltext` (`term`,`proposal`),
  FULLTEXT KEY `fulltext_term` (`term`),
  CONSTRAINT `terms_term_collection_ibfk_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_term_entry_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_termentry_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tt_tbxCreatedBy_fk` FOREIGN KEY (`tbxCreatedBy`) REFERENCES `terms_transacgrp_person` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tt_tbxUpdatedBy_fk` FOREIGN KEY (`tbxUpdatedBy`) REFERENCES `terms_transacgrp_person` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tt_updatedBy_fk` FOREIGN KEY (`updatedBy`) REFERENCES `Zf_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_term`
--


--
-- Table structure for table `terms_term_entry`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_term_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) DEFAULT NULL,
  `termEntryTbxId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `isCreatedLocally` tinyint(1) DEFAULT 0,
  `entryGuid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_term_entry_entryGuid_uindex` (`entryGuid`),
  KEY `collectionId_idx` (`collectionId`),
  KEY `termEntryTbxId_idx` (`termEntryTbxId`),
  CONSTRAINT `tte_collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_term_entry`
--


--
-- Table structure for table `terms_term_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_term_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `termId` int(11) NOT NULL,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `languageId` int(11) NOT NULL,
  `language` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `term` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `proposal` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processStatus` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT 'finalized',
  `updatedBy` int(11) DEFAULT NULL,
  `updatedAt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `definition` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT '',
  `termEntryTbxId` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termTbxId` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termEntryGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `langSetGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `collectionId` (`collectionId`),
  KEY `termEntryId` (`termEntryId`),
  KEY `termId` (`termId`),
  KEY `tth_updatedBy_fk` (`updatedBy`),
  CONSTRAINT `collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `termEntryId` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `termId` FOREIGN KEY (`termId`) REFERENCES `terms_term` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tth_updatedBy_fk` FOREIGN KEY (`updatedBy`) REFERENCES `Zf_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_term_history`
--


--
-- Table structure for table `terms_term_status_map`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_term_status_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'termNote',
  `tagAttributeType` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tagValue` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mappedStatus` enum('supersededTerm','preferredTerm','admittedTerm','deprecatedTerm','standardizedTerm','legalTerm','regulatedTerm') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mapTagType` (`tag`,`tagAttributeType`,`tagValue`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_term_status_map`
--

INSERT INTO `terms_term_status_map` VALUES
(1,'termNote','across_ISO_picklist_Usage','do not use','supersededTerm'),
(2,'termNote','across_ISO_picklist_Verwendung','Unwort','supersededTerm'),
(3,'termNote','across_userdef_picklist_Verwendung','Unwort','supersededTerm'),
(4,'termNote','normativeAuthorization','preferredTerm','preferredTerm'),
(5,'termNote','normativeAuthorization','standardizedTerm','standardizedTerm'),
(6,'termNote','normativeAuthorization','regulatedTerm','regulatedTerm'),
(7,'termNote','normativeAuthorization','legalTerm','legalTerm'),
(8,'termNote','normativeAuthorization','deprecatedTerm','deprecatedTerm'),
(9,'termNote','normativeAuthorization','supersededTerm','supersededTerm'),
(10,'termNote','normativeAuthorization','admittedTerm','admittedTerm'),
(11,'termNote','normativeAuthorization','proposed','preferredTerm'),
(12,'termNote','normativeAuthorization','admitted','admittedTerm'),
(13,'termNote','normativeAuthorization','deprecated','deprecatedTerm'),
(14,'termNote','administrativeStatus','preferredTerm-admn-sts','preferredTerm'),
(15,'termNote','administrativeStatus','standardizedTerm-admn-sts','standardizedTerm'),
(16,'termNote','administrativeStatus','regulatedTerm-admn-sts','regulatedTerm'),
(17,'termNote','administrativeStatus','legalTerm-admn-sts','legalTerm'),
(18,'termNote','administrativeStatus','deprecatedTerm-admn-sts','deprecatedTerm'),
(19,'termNote','administrativeStatus','supersededTerm-admn-sts','supersededTerm'),
(20,'termNote','administrativeStatus','admittedTerm-admn-sts','admittedTerm'),
(21,'termNote','administrativeStatus','preferred','preferredTerm'),
(22,'termNote','administrativeStatus','admitted','admittedTerm'),
(23,'termNote','administrativeStatus','notRecommended','deprecatedTerm'),
(24,'termNote','administrativeStatus','obsolete','deprecatedTerm'),
(25,'descrip','xBool_Forbidden','False','admittedTerm'),
(26,'descrip','xBool_Forbidden','True','supersededTerm');

--
-- Table structure for table `terms_transacgrp`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_transacgrp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `elementName` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transac` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `transacNote` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transacType` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isDescripGrp` tinyint(1) DEFAULT 0,
  `collectionId` int(11) NOT NULL,
  `termEntryId` int(11) DEFAULT NULL,
  `termId` int(11) DEFAULT NULL,
  `termTbxId` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `termEntryGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `langSetGuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guid` varchar(38) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_transacgrp_guid_uindex` (`guid`),
  KEY `collectionId_idx` (`collectionId`),
  KEY `termEntryId_idx` (`termEntryId`),
  KEY `terms_tgrp_term_ibfk_1` (`termId`),
  KEY `termTbxId` (`termTbxId`),
  CONSTRAINT `terms_tgrp_collection_ibfk_1` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_tgrp_entry_ibfk_1` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `terms_tgrp_term_ibfk_1` FOREIGN KEY (`termId`) REFERENCES `terms_term` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_transacgrp`
--


--
-- Table structure for table `terms_transacgrp_person`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms_transacgrp_person` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collectionId` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ttp_collectionId` (`collectionId`),
  CONSTRAINT `ttp_collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_transacgrp_person`
--

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-09-23 12:29:07
