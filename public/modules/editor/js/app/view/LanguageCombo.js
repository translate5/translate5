
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Language Combo Box. name must be set in order to find out automatically the labels
 */
Ext.define('Editor.view.LanguageCombo', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.languagecombo',
    typeAhead: false,
    displayField: 'label',
    forceSelection: true,
    anyMatch: true,
    queryMode: 'local',
    valueField: 'id',
    allowBlank: false,
    strings: {
        source: '#UT#Quellsprache',
        target: '#UT#Zielsprache',
        neutral: '#UT#Sprache'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                //each combo needs its own store instance, see EXT6UPD-8
                store: Ext.create(Editor.store.admin.Languages),
            };
        
        if(instanceConfig.name && /source/.test(instanceConfig.name)) {
            config.fieldLabel = config.toolTip = me.strings.source;
        }
        else if(instanceConfig.name && /target/.test(instanceConfig.name)) {
            config.fieldLabel = config.toolTip = me.strings.target;
        }
        else {
            config.fieldLabel = config.toolTip = me.strings.neutral;
        }
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});