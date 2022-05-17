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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

INSERT IGNORE INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginOkapiBconfPrefs');
INSERT IGNORE INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_okapi_bconf', 'all');
INSERT IGNORE INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_okapi_bconffilter', 'all');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
 ('runtimeOptions.plugins.Okapi.dataDir', 1, 'editor', 'system', '../data/editorOkapiBconf', '../data/editorOkapiBconf', '', 'absolutepath', 'Pfad zu einem vom WebServer beschreibbaren, über htdocs nicht erreichbaren Verzeichnis, in diesem werden die Bconf-Dateien für Task-Importe gehalten.');

CREATE TABLE IF NOT EXISTS `LEK_okapi_bconf` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customerId` INT(11) DEFAULT NULL,
    `name` VARCHAR(50),
    `description` TEXT,
    `isDefault` TINYINT(1) NOT NULL DEFAULT 0,
    `versionIdx` INT(10) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE(`name`),
    CONSTRAINT FOREIGN KEY (`customerId`) REFERENCES `LEK_customer` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `LEK_okapi_bconf_filter` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `bconfId` INT(11) NOT NULL,
    `okapiId` VARCHAR(50) NOT NULL,
    `okapiName` VARCHAR(50) DEFAULT NULL,
    `mimeType` VARCHAR(20) DEFAULT NULL,
    `name` VARCHAR(50) DEFAULT NULL,
    `notes` VARCHAR(200) DEFAULT NULL,
    `extensions` VARCHAR(200) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`bconfId`) REFERENCES `LEK_okapi_bconf` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `LEK_okapi_bconf_default_filter` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `okapiId` VARCHAR(50) NOT NULL,
  `mimeType` VARCHAR(50) DEFAULT NULL,
  `name` VARCHAR(50) DEFAULT NULL,
  `extensions` VARCHAR(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `hasGui` TINYINT(1) NOT NULL DEFAULT 0,
   PRIMARY KEY (`id`)
);

INSERT INTO `LEK_okapi_bconf_default_filter` (`okapiId`, `mimeType`, `name`, `extensions`, `description`)
VALUES
    ('okf_odf',                            'text/x-odf',             'OpenDocument',                                     NULL,               'XML OpenDocument files (e.g. use inside OpenOffice.org documents).'),
    ('okf_mosestext',                      'text/x-mosestext',       'Moses Text Default',                              'txt',              'Default Moses Text configuration.'),
    ('okf_tradosrtf',                      'application/rtf',        'Trados-Tagged RTF',                               'rtf',              'Configuration for Trados-tagged RTF files - READING ONLY.'),
    ('okf_rainbowkit',                     'application/x-rainbo',   'Rainbow Translation Kit',                         'rkm',              'Configuration for Rainbow translation kit.'),
    ('okf_rainbowkit-package',             'application/x-rainbo',   'Rainbow Translation Kit Package',                 'rkp',              'Configuration for Rainbow translation kit package.'),
    ('okf_rainbowkit-noprompt',            'application/x-rainbo',   'Rainbow Translation Kit (No prompt)',             'rkm',              'Configuration for Rainbow translation kit (without prompt).'),
    ('okf_mif',                            'application/vnd.mif',    'MIF (BETA)',                                      'mif',              'Adobe FrameMaker MIF documents'),
    ('okf_archive',                        'application/x-archiv',   'Archive Files',                                   'archive',          'Configuration for archive files'),
    ('okf_transifex',                      'application/x-transi',   'Transifex Project',                               'txp',              'Transifex project with prompt when starting'),
    ('okf_transifex-noPrompt',             'application/x-transi',   'Transifex Project (without prompt)',               NULL,               'Transifex project without prompt when starting'),
    ('okf_xini',                           'text/x-xini',            'XINI',                                            'xini',             'Configuration for XINI documents from ONTRAM'),
    ('okf_xini-noOutputSegmentation',      'text/x-xini',            'XINI (no output segmentation)',                    NULL,               'Configuration for XINI documents from ONTRAM (fields in the output are not segmented)'),
    ('okf_itshtml5',                       'text/html',              'Standard HTML5',                                  'html,htm',        'Configuration for standard HTML5 documents.'),
    ('okf_txml',                           'text/xml',               'TXML',                                            'txml',             'Wordfast Pro TXML documents'),
    ('okf_txml-fillEmptyTargets',          'text/xml',               'TXML (Fill empty targets in output)',             'txml',             'Wordfast Pro TXML documents with empty targets filled on output.'),
    ('okf_wiki',                           'text/x-wiki-txt',        'Wiki Markup',                                     'txt',              'Text with wiki-style markup'),
    ('okf_transtable',                     'text/x-transtable',      'Translation Table Default',                        NULL,               'Default TransTable configuration.'),
    ('okf_simplification',                 'text/xml',               'XML (Simplified resources and codes)',            'xml',              'Configuration for extracting resources from an XML file. Resources and then codes are simplified.'),
    ('okf_simplification-xmlResources',    'text/xml',               'XML (Simplified resources)',                      'xml',              'Configuration for extracting resources from an XML file. Resources are simplified.'),
    ('okf_simplification-xmlCodes',        'text/xml',               'XML (Simplified codes)',                          'xml',              'Configuration for extracting resources from an XML file. Codes are simplified.'),
    ('okf_xliff2',                         'application/xliff+xm',   'XLIFF-2',                                         'xlf',              'Configuration for XLIFF-2 documents.'),
    ('okf_icml',                           'application/x-icml+x',   'ICML',                                            'wcml,icml',       'Adobe InDesign ICML documents'),
    ('okf_markdown',                       'text/markdown',          'Markdown',                                        'md,markdown',     'Markdown files'),
    ('okf_pdf',                            'application/pdf',        'PDF (Portable Document Format)',                  'pdf',              'Configuration for PDF documents'),
    ('okf_sdlpackage',                     'application/x-sdlpac',   'SDL Trados Package Files',                        'sdlppx,sdlrpx',   'SDL Trados 2017 SDLPPX and SDLRPX files'),
    ('okf_autoxliff',                      'application/xliff+xm',   'XLIFF 1.2 and 2.0 Filter',                        'xlf,xliff',       'Calls the appropriate filter for any version of XLIFF'),
    ('okf_multiparsers',                   'text/csv',               'Multi-Parsers: CSV with Plain-Text',              'csv',              'Configuration for CSV files with plain-text on all columns'),
    ('okf_table',                          'text/csv',               'Table Files',                                      NULL,               'Table-like files such as tab-delimited, CSV, fixed-width columns, etc.'),
    ('okf_table_csv',                      'text/csv',               'Table (Comma-Separated Values)',                  'csv',              'Comma-separated values, optional header with field names.'),
    ('okf_table_catkeys',                  'text/csv',               'Haiku CatKeys',                                    NULL,               'Haiku CatKeys resource files'),
    ('okf_table_src-tab-trg',              'text/csv',               'Table (Tab-Separated Values)',                     NULL,               '2-column (source + target), tab separated files.'),
    ('okf_table_fwc',                      'text/csv',               'Table (Fixed-Width Columns)',                      NULL,               'Fixed-width columns table padded with white-spaces.'),
    ('okf_table_tsv',                      'text/csv',               'Table (Tab-Separated Values)',                     NULL,               'Columns, separated by one or more tabs.'),
    ('okf_plaintext',                      'text/plain',             'Plain Text',                                      'txt',              'Plain text files.'),
    ('okf_plaintext_trim_trail',           'text/plain',             'Plain Text (Trim Trail)',                          NULL,               'Text files, trailing spaces and tabs removed from extracted lines.'),
    ('okf_plaintext_trim_all',             'text/plain',             'Plain Text (Trim All)',                            NULL,               'Text files, leading and trailing spaces and tabs removed from extracted lines.'),
    ('okf_plaintext_paragraphs',           'text/plain',             'Plain Text (Paragraphs)',                          NULL,               'Text files extracted by paragraphs (separated by 1 or more empty lines).'),
    ('okf_plaintext_spliced_backslash',    'text/plain',             'Spliced Lines (Backslash)',                        NULL,               'Spliced lines filter with the backslash character () used as the splicer.'),
    ('okf_plaintext_spliced_underscore',   'text/plain',             'Spliced Lines (Underscore)',                       NULL,               'Spliced lines filter with the underscore character (_) used as the splicer.'),
    ('okf_plaintext_spliced_custom',       'text/plain',             'Spliced Lines (Custom)',                           NULL,               'Spliced lines filter with a user-defined splicer.'),
    ('okf_plaintext_regex_lines',          'text/plain',             'Plain Text (Regex, Line=Paragraph)',               NULL,               'Plain Text Filter using regex-based linebreak search. Extracts by lines.'),
    ('okf_plaintext_regex_paragraphs',     'text/plain',             'Plain Text (Regex, Block=Paragraph)',              NULL,               'Plain Text Filter using regex-based linebreak search. Extracts by paragraphs.'),
    ('okf_xml',                            'text/xml',               'Generic XML',                                     'xml',              'Configuration for generic XML documents (default ITS rules).'),
    ('okf_xml-resx',                       'text/xml',               'RESX',                                            'resx',             'Configuration for Microsoft RESX documents (without binary data).'),
    ('okf_xml-MozillaRDF',                 'text/xml',               'Mozilla RDF',                                     'rdf',              'Configuration for Mozilla RDF documents.'),
    ('okf_xml-JavaProperties',             'text/xml',               'Java Properties XML',                              NULL,               'Configuration for Java Properties files in XML.'),
    ('okf_xml-AndroidStrings',             'text/xml',               'Android Strings',                                  NULL,               'Configuration for Android Strings XML documents.'),
    ('okf_xml-WixLocalization',            'text/xml',               'WiX Localization',                                'wxl',              'Configuration for WiX (Windows Installer XML) Localization files.'),
    ('okf_xml-AppleStringsdict',           'text/xml',               'Apple Stringsdict',                               'stringsdict',      'Configuration for Apple Stringsdict files'),
    ('okf_xml-docbook',                    'text/xml',               'DocBook',                                          NULL,               'Configuration for DocBook v5 files'),
    ('okf_html',                           'text/html',              'HTML',                                            'html,htm',        'HTML or XHTML documents'),
    ('okf_html-wellFormed',                'text/html',              'HTML (Well-Formed)',                               NULL,               'XHTML and well-formed HTML documents'),
    ('okf_tmx',                            'application/x-tmx+xm',   'TMX',                                             'tmx',              'Configuration for Translation Memory eXchange (TMX) documents.'),
    ('okf_dtd',                            'application/xml+dtd',    'DTD (Document Type Definition)',                  'dtd',              'Configuration for XML DTD documents (entities content)'),
    ('okf_json',                           'application/json',       'JSON (JavaScript Object Notation)',               'json',             'Configuration for JSON files'),
    ('okf_idml',                           'application/vnd.adob',   'IDML',                                            'idml',             'Adobe InDesign IDML documents'),
    ('okf_ttx',                            'application/x-ttx+xm',   'TTX',                                             'ttx',              'Configuration for Trados TTX documents.'),
    ('okf_properties',                     'text/x-properties',      'Java Properties',                                 'properties',       'Java properties files (Output used uHHHH escapes)'),
    ('okf_properties-outputNotEscaped',    'text/x-properties',      'Java Properties (Output not escaped)',             NULL,               'Java properties files (Characters in the output encoding are not escaped)'),
    ('okf_properties-skypeLang',           'text/x-properties',      'Skype Language Files',                            'lang',             'Skype language properties files (including support for HTML codes)'),
    ('okf_properties-html-subfilter',      'text/x-properties',      'Properties with complex HTML Content',             NULL,               'Java Property content processed by an HTML subfilter'),
    ('okf_phpcontent',                     'application/x-php',      'PHP Content Default',                             'php',              'Default PHP Content configuration.'),
    ('okf_vignette',                       'text/xml',               'Vignette Export/Import Content',                   NULL,               'Default Vignette Export/Import Content configuration.'),
    ('okf_vignette-nocdata',               'text/xml',               'Vignette Export/Import Content (escaped HTML)',    NULL,               'Vignette files without CDATA sections.'),
    ('okf_pensieve',                       'application/x-pensie',   'Pensieve TM',                                     'pentm',            'Configuration for Pensieve translation memories.'),
    ('okf_xliff-sdl',                      'application/x-xliff+',   'SDLXLIFF',                                        'sdlxliff',         'Configuration for SDL XLIFF documents. Supports SDL specific metadata'),
    ('okf_xliff-iws',                      'application/x-xliff+',   'IWSXLIFF',                                        'xlf',              'Configuration for IWS XLIFF documents. Supports IWS specific metadata'),
    ('okf_ts',                             'application/x-ts',       'TS',                                              'ts',               'Configuration for Qt TS files.'),
    ('okf_regex',                          'text/x-regex',           'Regex Default',                                    NULL,               'Default Regex configuration.'),
    ('okf_regex-srt',                      'text/x-regex',           'SRT Sub-Titles',                                  'srt',              'Configuration for SRT (Sub-Rip Text) sub-titles files.'),
    ('okf_regex-textLine',                 'text/x-regex',           'Text (Line=Paragraph)',                            NULL,               'Configuration for text files where each line is a text unit'),
    ('okf_regex-textBlock',                'text/x-regex',           'Text (Block=Paragraph)',                           NULL,               'Configuration for text files where text units are separated by 2 or more line-breaks.'),
    ('okf_regex-macStrings',               'text/x-regex',           'Text (Mac Strings)',                              'strings',          'Configuration for Macintosh .strings files.'),
    ('okf_po',                             'application/x-gettex',   'PO (Standard)',                                   'po',               'Standard bilingual PO files'),
    ('okf_po-monolingual',                 'application/x-gettex',   'PO (Monolingual)',                                'po',               'Monolingual PO files (msgid is a real ID, not the source text).'),
    ('okf_yaml',                           'text/x-yaml',            'YAML',                                            'yml,yaml',        'YAML files'),
    ('okf_xmlstream',                      'text/xml',               'XML Stream',                                       NULL,               'Large XML Documents'),
    ('okf_xmlstream-dita',                 'text/xml',               'DITA',                                            'dita,ditamap',    'DITA XML'),
    ('okf_xmlstream-JavaPropertiesHTML',   'text/xml',               'Java Properties XML + HTML',                       NULL,               'Java Properties XML with Embedded HTML'),
    ('okf_doxygen',                        'text/x-doxygen-txt',     'Doxygen-commented Text',                          'h,c,cpp,java,py,m',                   'Doxygen-commented Text Documents'),
    ('okf_xliff',                          'application/x-xliff+',   'XLIFF',                                           'xlf,xliff,mxliff,mqxliff',              'Configuration for XML Localisation Interchange File Format (XLIFF) documents.'),
    ('okf_openoffice',                     'application/x-openof',   'OpenOffice.org Documents',                        'odt,ods,odg,odp,ott,ots,otp,otg',   'OpenOffice.org ODT, ODS, ODP, ODG, OTT, OTS, OTP, OTG documents'),
    ('okf_openxml',                        'text/xml',               'Microsoft Office Document',                       'docx,docm,dotx,dotm,pptx,pptm,ppsx,ppsm,potx,potm,xlsx,xlsm,xltx,xltm,vsdx,vsdm',   'Microsoft Office documents (DOCX, DOCM, DOTX, DOTM, PPTX, PPTM, PPSX, PPSM, POTX, POTM, XLSX, XLSM, XLTX, XLTM, VSDX, VSDM).');


-- Add constraints at last, so tables are created even when constraints fail
ALTER TABLE LEK_customer_meta ADD CONSTRAINT `fk-customer_meta-okapi_bconf`
FOREIGN KEY (`defaultBconfId`) REFERENCES LEK_okapi_bconf(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE LEK_task_meta ADD CONSTRAINT `fk-task_meta-okapi_bconf`
FOREIGN KEY (`bconfId`) REFERENCES LEK_okapi_bconf(`id`) ON DELETE SET NULL ON UPDATE CASCADE;