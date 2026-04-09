
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

Ext.define('Editor.view.admin.task.TaskAttributes', {
    extend: 'Ext.form.Panel',
    requires: [
        'Editor.view.admin.task.TaskAttributesViewController',
        'Editor.view.admin.task.TaskAttributesViewModel'
    ],
    alias: 'widget.taskattributes',
    strings: {
        taskName:'#UT#Task name',
        description:'#UT#Project description',
        customerName:'#UT#Client',
        orderDate:'#UT#Order date',
        pmGuid:'#UT#Project manager',
        btnSave: '#UT#Save',
        successUpdate:'#UT#The task was updated successfully',
        btnCancel:'#UT#Cancel',
        btnReload: '#UT#Refresh',
        loadingMask:'#UT#Refresh',
        fullMatchLabel: '#UT#Edit unchanged 100% TM matches',
        editTrue:'#UT#Yes',
        editFalse:'#UT#No',
        usageModeTitle: '#UT#Multiple users',
        usageModeInfo: '#UT#When assigning multiple users to the same workflow step',
        usageModeDisabled: '#UT#Option can only be changed if no user is assigned',
        usageModeCoop: '#UT#Sequential work',
        usageModeCompetitive: '#UT#Competing assignment',
        usageModeSimultaneous: '#UT#Simultaneous work',
        usageModeCoopInfo: '#UT#All users assigned to a workflow step can edit the task, but only one user can do so at a time.',
        usageModeCompetitiveInfo: '#UT#The first user assigned to a workflow step who opens and accepts the job will do the job. All other users’ assignments are removed, and the users informed accordingly.',
        usageModeSimultaneousInfo: '#UT#All users assigned to a workflow step can work simultaneously',
        edit100PercentMatchEnabledMessage:'#UT#If enabled, locked segments are included in the analysis and word count.',
        edit100PercentMatchDisabledMessage:'#UT#If disabled, locked 100% matches will no longer be included in the analysis and word count.',
        deadlineDateLabel:'#UT#Deadline date',
    },
    itemId:'taskAttributesPanel',
    controller:'taskattributesviewcontroller',
    viewModel:{
    	type:'taskattributes'
    },
    title: '#UT#Properties',
    border: 0,
    initConfig: function(instanceConfig) {
        var me = this,
            config,
            allowedItems=me.getAllowedFields();
    
        if(!allowedItems){
            return;
        }
        config = {
            title: me.title, //see EXT6UPD-9
            bodyPadding: 10,
            layout: 'anchor',
            autoScroll: true,
            defaults: {
                labelWidth: 200,
                anchor: '60%'
            },
            items:allowedItems,
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    enableOverflow: true,
                    defaultButtonUI: false,
                    ui: 'footer',
                    border: '1 0 0 0',
                    items: [
                        {
                            xtype: 'tbfill'
                        },
                        {
                            xtype: 'button',
                            itemId: 'cancelTaskAttributes',
                            glyph: 'f00d@FontAwesome5FreeSolid',
                            bind: {
                                disabled: '{!enablePanel}'
                            },
                            listeners:{
                                click:'onCancelTaskAttributesClick'
                            },
                            text: me.strings.btnCancel
                        },
                        {
                            xtype: 'button',
                            itemId: 'reloadTaskAttributes',
                            glyph: 'f2f1@FontAwesome5FreeSolid',
                            bind: {
                                disabled: '{!enablePanel}'
                            },
                            listeners:{
                                click:'onReloadTaskAttributesClick'
                            },
                            text: me.strings.btnReload
                        },{
                            xtype: 'button',
                            itemId: 'saveTaskAttributes',
                            glyph: 'f00c@FontAwesome5FreeSolid',
                            bind: {
                                disabled: '{!enablePanel}'
                            },
                            listeners:{
                                click:'onSaveTaskAttributesClick'
                            },
                            text: me.strings.btnSave
                        }
                    ]
                }
            ]
        };
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /***
     * Return the allowed fields in the task attributes tab. If the field is not allowed for the current logged user,
     * the component type will be displayfield(the value will be noneditable)
     * @returns Array
     */
    getAllowedFields:function(){
        var me=this,
            auth = Editor.app.authenticatedUser,
            items=[], dateRenderer = function(value, displayField) {
                return Ext.Date.format(value, Ext.Date.defaultFormat);
            };

        items.push({
            xtype: 'displayfield',
            fieldLabel: me.strings.customerName,
            name: 'customerId',
            bind:'{currentTask.customerId}',
            renderer: me.customerRenderer
        });
        
        //TODO: the displayfield value is not selectable
        //EXTJSBUG: https://www.sencha.com/forum/showthread.php?330319-Value-not-selectable-in-display-and-text-fields-under-6-2-0
        //is the user allowed to edit the task name
        items.push({
            xtype: auth.isAllowed('editorEditTaskTaskName') ? 'textfield' : 'displayfield',
            fieldLabel: me.strings.taskName,
            bind: {
                disabled: '{!enablePanel}',
                value: '{currentTask.taskName}'
            },
            name:'taskName',
            itemId:'taskName'
        });

        items.push({
            xtype: 'datefield',
            name: 'deadlineDate',
            itemId:'deadlineDate',
            bind: {
                disabled: '{!enablePanel}',
                value: '{currentTask.deadlineDate}'
            },
            fieldLabel: me.strings.deadlineDateLabel
        });

        items.push({
            //lazy: just use the same right as for editTaskName
            xtype: auth.isAllowed('editorEditTaskTaskName') ? 'textarea' : 'displayfield',
            fieldLabel: me.strings.description,
            bind: {
                disabled: '{!enablePanel}',
                value: '{currentTask.description}'
            },
            name:'description',
            itemId:'description'
        });

        items.push(me.getPmFieldConfig());

        items.push(me.applyIfNotAllowed({
            xtype: 'datefield',
            fieldLabel: me.strings.orderDate,
            bind: {
                disabled: '{!enablePanel}',
                value: '{currentTask.orderdate}'
            },
            name:'orderdate',
            itemId:'orderdate'
        },'editorEditTaskOrderDate',{
            xtype: 'displayfield',
            renderer: dateRenderer
        }));
        
        //is the user allowed to edit the Edit100PercentMatch
        items.push(me.applyIfNotAllowed({
            xtype: 'checkbox',
            fieldLabel:me.strings.fullMatchLabel,
            bind: {
                disabled: '{!enablePanel}',
                value: '{currentTask.edit100PercentMatch}'
            },
            name:'edit100PercentMatch',
            listeners:{
                change:'onEdit100PercentMatchChange'
            },
            itemId:'edit100PercentMatch'
        },'editorEditTaskEdit100PercentMatch',{
            xtype: 'displayfield',
            renderer: function(value, displayField) {
                return value ? me.strings.editTrue : me.strings.editFalse;
            }
        }));
        
        me.setUsageModeConfig(items);

        return items.concat(
            Editor.controller.admin.TaskCustomField.getFormFieldsFor(
                'projectWizard',
                true
            ));
    },
    applyIfNotAllowed: function(baseItem, right, overwrite) {
        if(Editor.app.authenticatedUser.isAllowed(right)) {
            return baseItem;
        }
        return Ext.apply(baseItem, overwrite);
    },
    customerRenderer : function(val) {
        if (val == undefined) {
            return val;
        }
        var customersStore = Ext.StoreManager.get('customersStore'),
            customer = customersStore && customersStore.getById(val);
        return customer ? Ext.String.htmlEncode(customer.get('name')) : '';
    },
    /**
     * Adds the usage mode radio box to the items list if allowed
     */
    setUsageModeConfig: function(items) {
        var me=this,
            auth = Editor.app.authenticatedUser,
            infoTpl = new Ext.Template('{0} <img src="'+Editor.data.pathToRunDir+'/modules/editor/images/information.png" data-qtip="{1}" />');
        //without task user assoc view, this setting may also not be visible 
        if(!auth.isAllowed('editorChangeUserAssocTask')) {
            return;
        }
        items.push({
        	xtype: 'displayfield',
        	value: me.strings.usageModeInfo
        },{
        	xtype: 'component',
        	html: me.strings.usageModeDisabled,
        	bind: {
        		hidden:'{!disableUsageMode}'
        	}
        },{
            xtype: 'radiogroup',
            name:'usageMode',
            simpleValue:true,
            bind: {
                disabled: '{!enablePanel}',
                value: '{currentTask.usageMode}'
            },
            fieldLabel : me.strings.usageModeTitle,
            columns: 1,
            anchor: '100%',
            items: [
                {
                    boxLabel  : infoTpl.apply([me.strings.usageModeSimultaneous, me.strings.usageModeSimultaneousInfo]),
                    name      : 'usageMode',
                    inputValue: 'simultaneous',
                    bind: {
                        disabled:'{disableUsageMode}'
                    }
                }, {
                    boxLabel  : infoTpl.apply([me.strings.usageModeCompetitive, me.strings.usageModeCompetitiveInfo]),
                    name      : 'usageMode',
                    inputValue: 'competitive',
                    bind: {
                        disabled:'{disableUsageMode}'
                    }
                }, {
                    boxLabel  : infoTpl.apply([me.strings.usageModeCoop, me.strings.usageModeCoopInfo]),
                    name      : 'usageMode',
                    inputValue: 'cooperative',
                    bind: {
                        disabled:'{disableUsageMode}'
                    }
                }
            ]
        });
    },
    getPmFieldConfig: function() {
        var me=this,
            auth = Editor.app.authenticatedUser;
        if(auth.isAllowed('editorEditTaskPm') || auth.isAllowed('editorEditAllTasks')) {
            return {
                xtype: 'combo',
                fieldLabel: me.strings.pmGuid,
                bind: {
                    disabled: '{!enablePanel}',
                    value: '{currentTask.pmGuid}'
                },
                allowBlank: false,
                typeAhead: false,
                forceSelection: true,
                anyMatch: true,
                queryMode: 'local',
                name: 'pmGuid',
                itemId: 'pmGuid',
                displayField: 'longUserName',
                valueField: 'userGuid',
                listConfig: {
                    loadMask: false
                },
                store: {
                    autoLoad: true,
                    storeId: 'pmGuidCombo_User',
                    model: 'Editor.model.admin.User',
                    pageSize: 0,
                    proxy : {
                        type : 'rest',
                        url: Editor.data.restpath+'user/pm',
                        extraParams: {
                            sort: '[{"property":"surName","direction":"ASC"},{"property":"firstName","direction":"ASC"}]',
                            pmRoles: 'pmlight'
                        },
                        reader : {
                            rootProperty: 'rows',
                            type : 'json'
                        }
                    }
                }
            };
        }

        return {
            xtype: 'displayfield',
            name: 'pmGuid',
            bind:'{currentTask.pmGuid}',
            fieldLabel: me.strings.pmGuid
        };
    }
  });
