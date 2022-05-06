
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-05-05', 'TRANSLATE-2960', 'change', 'VisualReview / VisualTranslation - Enable Markup processing in Subtitle Import parsers', 'Visual Video: Enable markup protection in internal tags as well as whitespace  for the import', '15'),
('2022-05-05', 'TRANSLATE-2931', 'change', 'Okapi integration, Task Management - Import file format and segmentation settings - Bconf Management (Milestone 1)', 'Translate5 can now manage Okapi BatchConfiguration files - needed for configuring the import file filters. Admins and PMs can upload, download, rename Bconfs and upload and download contained SRX files in the new \'File format and segmentation settings\' grid under \'Preferences\'. It is also available under \'Clients\' to easily handle specific requirements of different customers. You can also set a default there, which overrides the one from the global perspective. During Project Creation a dropdown menu presents the available Bconf files for the chosen client, preset with the configured default. The selected one is then passed to Okapi on import.', '15'),
('2022-05-05', 'TRANSLATE-2901', 'change', 'InstantTranslate - Languageresource type filter in instanttranslate API', 'ENHANCEMENT: Added filters to filter InstantTranslate API for language resource types and id\'s. See https://confluence.translate5.net/display/TAD/InstantTranslate for details

FIX: fixed whitespace-rendering in translations when Translation Memories were requested and text to translate was segmented therefore', '15'),
('2022-05-05', 'TRANSLATE-2884', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Further restrict nightly error mail summaries', 'A new role systemadmin is added, to be used only for technical people and translate5 system administrators. 
Only users with that role will receive the nightly error summary e-mail in the future (currently all admins). Only systemadmins can set the role systemadmin and api.
For hosted clients: contact us so that we can enable the right for desired users.
For on premise clients: the role must be added manually in the DB to one user. With that user the role can then be set on other users.', '15'),
('2022-05-05', 'TRANSLATE-2961', 'bugfix', 'Editor general - Error on repetition save', 'Solves a problem where an error happens in the UI after saving repetitions with repetition editor.', '15'),
('2022-05-05', 'TRANSLATE-2959', 'bugfix', 'OpenId Connect - Overlay for SSO login auto-redirect', 'Adds overlay when auto-redirecting with SSO authentication.', '15'),
('2022-05-05', 'TRANSLATE-2957', 'bugfix', 'OpenId Connect - Missing default text on SSO button', 'When configuring SSO via OpenID no default button text is provided, therefore the SSO Login button may occur as button without text - not recognizable as button then.', '15'),
('2022-05-05', 'TRANSLATE-2910', 'bugfix', 'TermPortal, User Management - Role rights for approval workflow of terms in the TermPortal', 'Terms/attributes editing/deletion access logic reworked for Term Proposer, Term Reviewer and Term Finalizer roles', '15'),
('2022-05-05', 'TRANSLATE-2558', 'bugfix', 'Editor general - Task focus after login', 'On application load always the first project was selected, instead the one given in the URL. This is fixed now. Other application parts (like preferences or clients) can now also opened directly after application start by passing its section in the URL.', '15');