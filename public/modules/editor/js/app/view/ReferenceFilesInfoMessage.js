
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

Ext.define('Editor.view.ReferenceFilesInfoMessage', {
    extend: 'Ext.window.Window',
    itemId:'referenceFilesInfoMessage',
    y:0,
    y:0,
    width:250,
    bodyPadding:'10 10 10 10',
    border:false,
    //layout: {
    //    align: 'middle',
    //    pack: 'center',
    //    type: 'fit'
    //},
    strings:{
        windowTitle:'#UT#Referenz-Dateien',
        windowInfoMessage:'#UT#Referenzdateien vorhanden',
        buttonText:'#UT#Liste öffnen'
    },
    initComponent:function(instanceConfig){
        var me = this;
        me.title=me.strings.windowTitle;
        me.html=me.strings.windowInfoMessage;

        me.bbar=[{ 
            xtype: 'button',
            itemId:'showReferenceFilesButton',
            text: me.strings.buttonText,
            icon: Editor.data.moduleFolder+'images/page_attach.png'
        }];
        //me.items=[{
        //    xtype: 'button',
        //    itemId:'showReferenceFilesButton',
        //    text: me.strings.buttonText,
        //}];
        me.callParent(arguments);
    }
});
