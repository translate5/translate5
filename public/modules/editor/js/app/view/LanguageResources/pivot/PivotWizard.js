
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
 * @class Editor.view.LanguageResources.pivot.PivotWizard
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.view.LanguageResources.pivot.PivotWizard', {
    extend:'Ext.panel.Panel',
    alias: 'widget.languageResourcePivotWizard',
    itemId:'languageResourcePivotWizard',
    requires:['Editor.view.LanguageResources.pivot.Assoc'],
    mixins:['Editor.controller.admin.IWizardCard'],
    
    //card type, used for card display order
    importType:'postimport',
    defaultListenerScope:true, // Until this component does not have his own view controller, this flag is required. It enables string defined event methods to be mapped to this class
    task:null,
    autoScroll: true,
    strings:{
        wizardTitle:'#UT#Pivot-Sprachressourcen'
    },
    listeners:{
        activate:'onPivotWizardActivate'
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                items: [{
                    xtype: 'languageResourcePivotAssoc',
                    header:false,
                    title:null
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([ config ]);
    },
    
    /***
     */
    onPivotWizardActivate:function(){
        var me = this,
            view = me.down('#languageResourcePivotAssoc'),
            store = view && view.getStore();

        store && store.load({
            params:{
                taskGuid:me.task.get('taskGuid')
            }
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