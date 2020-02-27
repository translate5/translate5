
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.qmsubsegments.AddFlagFieldset
 * @extends Ext.form.FieldSet
 */
Ext.define('Editor.view.qmsubsegments.AddFlagFieldset', {
	extend : 'Ext.form.FieldSet',
	alias : 'widget.qmSubsegmentsFlagFieldset',
	title : "#UT#MQM",
	collapsible: true,
	strings: {
		severityLabel: '#UT#Gewichtung',
		commentLabel: '#UT#Kommentar',
		qmAddBtn: '#UT#MQM hinzufügen'
	},
	initConfig: function(instanceConfig) {
		var me = this,
		    config = {
		        title: me.title, //see EXT6UPD-9
			items : [{
				xtype: 'button',
				text: me.strings.qmAddBtn,
				//margin: '0 0 0 0',
				menu: {
				    xtype: 'menu',
					bodyCls: 'qmflag-menu',
					items: instanceConfig.menuConfig,
					listeners: {
	                    afterrender: function(component) {
	                    	if(component.keyNav) {
	                    		component.keyNav.disable();
	                    	}
	                    }
                	}
				}
			},{
				xtype: 'combo',
				anchor: '100%',
				name: 'qmsubseverity',
				queryMode: 'local',
				autoSelect: true,
				fieldLabel: me.strings.severityLabel,
				forceSelection: true,
				editable: false,
			    displayField: 'text',
			    valueField: 'id'
			},{
				xtype: 'textfield',
				anchor: '100%',
				fieldLabel: me.strings.commentLabel,
				name: 'qmsubcomment'
			}]
		};
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
	}
});