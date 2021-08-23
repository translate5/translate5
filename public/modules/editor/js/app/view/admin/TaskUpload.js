
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.admin.TaskUpload
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.admin.TaskUpload', {
    extend:'Ext.panel.Panel',
    alias: 'widget.taskUpload',
    requires:[
      'Editor.view.admin.TaskUploadViewController'
    ],
    controller:'taskUpload',
    mixins:['Editor.controller.admin.IWizardCard'],
    listeners: {
        activate:'onTaskUploadActivate'
    },
    cls: 'taskUploadCard',
    border: 0,
    padding:20,
    
    //card type, used for card display order
    importType:'postimport',
    
    strings:{
        wizardTitle:'#UT#Dateien werden hochgeladen',
        finishButton:'#UT#Schließen',
    },
    
    initConfig: function(instanceConfig) {
        var me = this,
            config = {};
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    
    triggerNextCard:function(activeItem){
        this.getController().handleNextCardClick();
    },
    
    
    disableContinueButton:function(){
        var me = this,
            win = me.up('window'),
            winLayout=win.getLayout(),
            nextStep=winLayout.getNext();
        
        if(!nextStep){
            return true;
        }
        return false;
    },

    disableCancelButton:function(){
        var me = this,
            win = me.up('window'),
            btnCancel=win.down('#cancel-task-btn');
        
        btnCancel.setText(me.strings.finishButton);
        
        return false;
    },
    
    disableSkipButton:function(){
        return true;
    },
    
    disableAddButton:function(){
        return true;
    }
    
    
});