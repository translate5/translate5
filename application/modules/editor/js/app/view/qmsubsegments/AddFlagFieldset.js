/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
	title : "QM Subsegmente",
	strings: {
		severityLabel: '##UT##Gewichtung',
		commentLabel: '##UT##Kommentar',
		qmAddBtn: '##UT##QM Subsegment hinzufügen'
	},
	initComponent : function() {
		var me = this,
		    sevStore = Ext.StoreMgr.get('Severities');
		Ext.applyIf(me, {
			items : [{
				xtype : 'button',
				text : me.strings.qmAddBtn,
				margin: '0 0 6 0',
				menu : {
					bodyCls: 'qmflag-menu',
					items: me.controller.menuConfig,
					listeners: {
	                    afterrender: function(component) {
	                    	if(component.keyNav) {
	                    		component.keyNav.disable();
	                    	}
	                    }
                	}
				}
			},{
				xtype: 'combobox',
				anchor: '100%',
				name: 'qmsubseverity',
				queryMode: 'local',
				autoSelect: true,
				fieldLabel: me.strings.severityLabel,
				forceSelection: true,
				editable: false,
				value: sevStore.getAt(0).get('id'),
			    displayField: 'text',
			    valueField: 'id',
				store: sevStore
			},{
				xtype: 'textfield',
				anchor: '100%',
				fieldLabel: me.strings.commentLabel,
				name: 'qmsubcomment'
			}]
		});
		me.callParent(arguments);
	}
});