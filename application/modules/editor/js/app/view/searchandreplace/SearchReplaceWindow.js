
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

Ext.define('Editor.view.searchandreplace.SearchReplaceWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.searchreplacewindow',
    itemId: 'searchreplacewindow',
    requires:[
        'Editor.view.searchandreplace.TabPanel'
    ],
    minHeight : 350,
    width : 350,
    autoHeight: true,
    layout:'fit',
    strings:{
        windowTitle:'#UT#Search and replace window',
        helpButtonTooltip:'#UT#Info about the search and replace'
    },
    
    initConfig : function(instanceConfig) {
        var me = this,
            config = {
                title:me.strings.windowTitle,
                items:[{
                    xtype:'searchreplacetabpanel',
                    //items: [{
                        //xtype:'searchTab',
                   //     xtype:'tab',
                        //title:me.strings.searchTabTitle
                    //}, {
                        //xtype:'replaceTab',
                    //    xtype:'tab',
                        //title:me.strings.replaceTabTitle
                        //tabConfig: {
                        //    tooltip: 'A button tooltip'
                        //}
                    //}]
                }],
                tools:[{
                    type:'help',
                    tooltip:me.strings.helpButtonTooltip,
                    handler:function(){
                        window.open('http://confluence.translate5.net/','_blank');
                    }
                }]
        };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    }
});