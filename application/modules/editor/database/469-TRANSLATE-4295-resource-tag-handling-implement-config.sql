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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES
    ('runtimeOptions.LanguageResources.google.tagHandler', '1', 'app', 'system', 'xliff_paired_tags', 'xliff_paired_tags', 'remover,html_image,xlf_repair,xliff_paired_tags', 'string', 'Tag handler (and repair) type that is used in communication with the 3rd party system. For details see <a href="https://confluence.translate5.net/x/AQBHIw" target="_blank">Tag-Handler description</a>', 8, 'Google tag handler', 'Language resources', ''),
    ('runtimeOptions.LanguageResources.microsoft.tagHandler', '1', 'app', 'system', 'xlf_repair', 'xlf_repair', 'remover,html_image,xlf_repair,xliff_paired_tags', 'string', 'Tag handler (and repair) type that is used in communication with the 3rd party system. For details see <a href="https://confluence.translate5.net/x/AQBHIw" target="_blank">Tag-Handler description</a>', 8, 'Microsoft tag handler', 'Language resources', ''),
    ('runtimeOptions.LanguageResources.t5memory.tagHandler', '1', 'app', 'system', 't5memoryxliff', 't5memoryxliff', 'remover,html_image,t5memoryxliff,xlf_repair,xliff_paired_tags', 'string', 'Tag handler (and repair) type that is used in communication with the 3rd party system. For details see <a href="https://confluence.translate5.net/x/AQBHIw" target="_blank">Tag-Handler description</a>', 1, 'T5Memory tag handler', 'Language resources', ''),
    ('runtimeOptions.LanguageResources.pangeamt.tagHandler', '1', 'app', 'system', 'xlf_repair', 'xlf_repair', 'remover,html_image,xlf_repair,xliff_paired_tags', 'string', 'Tag handler (and repair) type that is used in communication with the 3rd party system. For details see <a href="https://confluence.translate5.net/x/AQBHIw" target="_blank">Tag-Handler description</a>', 8, 'PangeaMT tag handler', 'Language resources', ''),
    ('runtimeOptions.LanguageResources.google.sendWhitespaceAsTag', '1', 'app', 'system', '1', '1', '', 'boolean', 'If enabled whitespaces will be sent as tags to the corresponding 3rd party system. Applicable only for tag handlers that are supporting tags.', 8, 'Google send whitespaces as tag', 'Language resources', ''),
    ('runtimeOptions.LanguageResources.microsoft.sendWhitespaceAsTag', '1', 'app', 'system', '1', '1', '', 'boolean', 'If enabled whitespaces will be sent as tags to the corresponding 3rd party system. Applicable only for tag handlers that are supporting tags.', 8, 'Microsoft send whitespaces as tag', 'Language resources', ''),
    ('runtimeOptions.LanguageResources.t5memory.sendWhitespaceAsTag', '1', 'app', 'system', '0', '0', '', 'boolean', 'If enabled whitespaces will be sent as tags to the corresponding 3rd party system. Applicable only for tag handlers that are supporting tags.', 8, 'T5Memory send whitespaces as tag', 'Language resources', ''),
    ('runtimeOptions.LanguageResources.pangeamt.sendWhitespaceAsTag', '1', 'app', 'system', '0', '0', '', 'boolean', 'If enabled whitespaces will be sent as tags to the corresponding 3rd party system. Applicable only for tag handlers that are supporting tags.', 8, 'PangeaMT send whitespaces as tag', 'Language resources', ''),
    ('runtimeOptions.LanguageResources.google.format', '1', 'app', 'system', 'text', 'text', 'html_image,text', 'string', 'This parameter determines how Google will handle the submitted content. The default should be "text", which can also handle tags. HTML should be set if you are submitting HTML content.', 8, 'Google source text format', 'Language resources', '')
;
