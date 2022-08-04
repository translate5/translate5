/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 		     http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

/**

 extractNotes.b=false
 simplifyCodes.b=true
 extractMasterSpreads.b=true
 skipThreshold.i=1000 (max: 32000, min: 1, has tooltip)
 newTuOnBr.b=false

 see /okapi/filters/icml/src/main/java/net/sf/okapi/filters/icml/Parameters.java
 */
Ext.define('Editor.plugins.Okapi.view.fprm.Icml', {
    extend: 'Editor.plugins.Okapi.view.fprm.Properties',
    width: 700,
    fieldDefinitions: {
        'extractNotes.b': {},
        "simplifyCodes.b": {},
        "extractMasterSpreads.b": {},
        "skipThreshold.i": { config: { minValue: 1, maxValue: 32000, hasTooltip: true }},
        "newTuOnBr.b": {}
    }
});