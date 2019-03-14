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
UPDATE `Zf_configuration` SET `description`='Microsoft translator language resource api key. After compliting the account registration and resource configuration, get the api key from the azzure portal.' 
WHERE `name`='runtimeOptions.LanguageResources.microsoft.apiKey';

UPDATE `Zf_configuration` SET `default`='https://api.cognitive.microsofttranslator.com', `description`='Microsoft translator language resource api url. To be able to use microsoft translator, you should create an microsoft azure account. Create and setup and microsoft azure\naccount in the following link: https://azure.microsoft.com/en-us/services/cognitive-services/translator-text-api/' 
WHERE `name`='runtimeOptions.LanguageResources.microsoft.apiUrl';
