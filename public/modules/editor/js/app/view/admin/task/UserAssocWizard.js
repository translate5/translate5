
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
 * @class Editor.view.admin.task.UserAssocWizard
 * @extends Ext.form.Panel
 */
Ext.define('Editor.view.admin.task.UserAssocWizard', {
    extend:'Editor.view.admin.task.UserAssoc',
    alias: 'widget.adminTaskUserAssocWizard',
    itemId:'adminTaskUserAssocWizard',
    requires: [
        'Editor.view.admin.task.UserAssoc'
    ],
    mixins:['Editor.controller.admin.IWizardCard'],

    //card type, used for card display order
    importType:'postimport',

    task:null,
    header:false,
    title:null,

    strings:{
        wizardTitle:'#UT#Standard-Benutzerzuweisungen'
    },

    listeners:{
        activate:'onUserAssocWizardActivate'
    },

    initComponent:function(){
        var me=this,
            gridConfig = me.items[0];
        gridConfig.features= [{
            ftype: 'grouping',
            startCollapsed: true,
            groupHeaderTpl: '{targetLang} ({rows.length})'
        }];
        me.callParent();
        me.loadCustomConfig();
    },

    loadCustomConfig:function(){
        var me=this,
            assocGrid = me.down('#adminTaskUserAssocGrid');
        assocGrid.down('#userSpecialPropertiesBtn').setHidden(true);
        assocGrid.down('#reload-btn').setHidden(true);

        assocGrid.reconfigure(assocGrid.getStore(),[{
            xtype: 'gridcolumn',
            width: 230,
            dataIndex: 'sourceLang',
            text: 'Source'
        },{
            xtype: 'gridcolumn',
            width: 230,
            dataIndex: 'targetLang',
            text: 'Target'
        },{
            xtype: 'gridcolumn',
            width: 230,
            dataIndex: 'login',
            renderer: function(v, meta, rec) {
                if(Editor.data.debug) {
                    v = Ext.String.format('<a href="{0}session/?authhash={1}">{2}</a>', Editor.data.restpath, rec.get('staticAuthHash'), v);
                }
                return rec.get('surName')+', '+rec.get('firstName')+' ('+v+')';
            },
            filter: {
                type: 'string'
            },
            text: assocGrid.strings.userGuidCol
        },{
            xtype: 'gridcolumn',
            width: 100,
            dataIndex: 'role',
            renderer: function(v,meta,rec) {
                var task=me.lookupViewModel().get('currentTask'),
                    vfm=task && task.getWorkflowMetaData(),
                    role=(vfm && vfm.roles && vfm.roles[v]) || v;
                return role;
            },
            text: assocGrid.strings.roleCol
        },{
            xtype: 'gridcolumn',
            width: 70,
            dataIndex: 'segmentrange',
            text: assocGrid.strings.segmentrangeCol
        }]);
    },

    /***
     */
    onUserAssocWizardActivate:function(){
        var me=this,
            store=me.down('#adminTaskUserAssocGrid').getStore();
        store.setExtraParams({
            projectId:me.task.get('projectId')
        });
        store.load();
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