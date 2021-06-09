
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
 * @class Editor.view.admin.config.ConfigWizard
 * @extends Ext.form.Panel
 */
Ext.define('Editor.view.admin.config.ConfigWizard', {
    extend:'Ext.panel.Panel',
    alias: 'widget.adminConfigWizard',
    itemId:'adminConfigWizard',
    requires: [
        'Editor.view.admin.config.Grid'
    ],
    mixins:['Editor.controller.admin.IWizardCard'],
    
    //card type, used for card display order
    importType:'postimport',
    
    task:null,
    autoScroll: true,
    strings:{
        wizardTitle:'#UT#Konfiguration'
    },
    listeners:{
        activate:'onConfigWizardActivate'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                    items: [{
                        xtype: 'adminConfigGrid',
                        header:false,
                        store:Ext.create('Editor.store.admin.task.Config'),
                        title:null
                    }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    
    /***
     * On card visually activate load the configs
     */
    onConfigWizardActivate:function(){
        var me = this,
            grid = me.down('#adminConfigGrid');
        grid.setExtraParams({
            taskGuid:me.task.get('taskGuid')
        });
    },
    
    //called when next button is clicked
    triggerNextCard:function(activeItem){
        this.fireEvent('wizardCardFinished', null);
    },
    //called when skip button is clicked
    triggerSkipCard:function(activeItem){
        this.fireEvent('wizardCardFinished', 2);
    },

    disableSkipButton:function(){
        return false;
    },
    
    disableContinueButton:function(){
        return false;
    },
    
    disableAddButton:function(){
        return true;
    },
    
    disableCancelButton:function(){
        return false;
    }
    
});