
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

Ext.define('Editor.view.LanguageResources.TbxExport', {
    extend: 'Ext.window.Window',
    requires: [
        'Editor.view.LanguageResources.TbxExportViewController',
    ],
    controller: 'tbxexport',
    alias: 'widget.tbxexport',
    itemId: 'tbxExport',
    strings: {
    	tbxBasicOnlyLabel: '#UT#Nur TBX Basic Standardattribute + processStatus exportieren',
    	exportImagesLabel: '#UT#Bilder im Hex-Format exportieren, eingebettet in TBX',
    	exportButtonText:'#UT#Exportieren',
    	cancelButtonText:'#UT#Abbrechen',
    	title:'#UT#Als TBX exportieren',
    },
    modal:true,
	width:500,
	autoScroll:true,
    autoHeight:true,
	layout:'auto',
	border:false,
	bodyPadding: 10,
	initConfig: function(instanceConfig) {
        var me = this,
        config = {
    		title:me.strings.title,
			defaults: {
				margin: '0 0 0 15'
			},
            items:[{
        		xtype: 'checkbox',
        		flex: 1,
        		boxLabel: me.strings.tbxBasicOnlyLabel,
        		value: 0,
        		name: 'tbxBasicOnly'
        	}, {
        		xtype: 'checkbox',
        		flex: 1,
        		boxLabel: me.strings.exportImagesLabel,
        		value: 1,
        		name: 'exportImages'
        	}],
        	dockedItems: [{
                xtype: 'toolbar',
                dock: 'bottom',
                items: [{
                	xtype:'button',
                	itemId:'exportButton',
                	text:me.strings.exportButtonText,
                	handler: function() {
                		me.getController().exportTbx(
                			me.down('[name=tbxBasicOnly]'),
							me.down('[name=exportImages]'), me.record
						);
                	}
                },{
                	xtype:'button',
                	text:me.strings.cancelButtonText,
                	handler:function(){
                		this.up('window').destroy();
                	}
                }]
            }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});