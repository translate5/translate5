
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

Ext.define('Editor.view.admin.task.TaskAttributes', {
    extend: 'Ext.panel.Panel',
    //requires: ['Editor.view.admin.task.TaskAttributesViewController'],
    requires: [
        'Editor.view.admin.task.TaskAttributesViewController',
    ],
    alias: 'widget.taskattributes',
    strings: {
        taskName:'#UT#Aufgabenname',
        customerName:'#UT#Kunde',
        deliveryDate:'#UT#Lieferdatum (soll)',
        realDeliveryDate:'#UT#Lieferdatum (ist)',
        orderDate:'#UT#Bestelldatum',
        pmGuid:'#UT#Projektmanager',
        btnSave: '#UT#Speichern',
        successUpdate:'#UT#Die Aufgabe wurde erfolgreich aktualisiert',
        btnCancel:'#UT#Abbrechen',
        btnReload: '#UT#Aktualisieren',
        loadingMask:'#UT#Aktualisieren',
    },
    itemId:'taskAttributesPanel',
    controller:'taskattributesviewcontroller',
    title: '#UT#Eigenschaften',
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
            xtype: 'form',
            width: '100%',
            frame: true,
            defaults: {
                labelWidth: 200,
                anchor: '100%'
            },
            items:allowedItems,
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'bottom',
                    ui: 'footer',
                    items: [
                        {
                            xtype: 'tbfill'
                        },
                        {
                            xtype: 'button',
                            itemId: 'cancelTaskAttributes',
                            iconCls : 'ico-cancel',
                            listeners:{
                                click:'onCancelTaskAttributesClick'
                            },
                            text: me.strings.btnCancel
                        },
                        {
                            xtype: 'button',
                            itemId: 'reloadTaskAttributes',
                            iconCls: 'ico-refresh',
                            listeners:{
                                click:'onReloadTaskAttributesClick'
                            },
                            text: me.strings.btnReload
                        },{
                            xtype: 'button',
                            itemId: 'saveTaskAttributes',
                            iconCls : 'ico-save',
                            listeners:{
                                click:'onSaveTaskAttributesClick'
                            },
                            text: me.strings.btnSave
                        },
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
            items=[];

        items.push({
            xtype: 'displayfield',
            fieldLabel: me.strings.customerName,
            bind:{
                value:'{currentTask.customerName}'
            }
        });
        
        //TODO: the displayfield value is not selectable
        //EXTJSBUG: https://www.sencha.com/forum/showthread.php?330319-Value-not-selectable-in-display-and-text-fields-under-6-2-0
        //is the user allowed to edit the task name
        if(auth.isAllowed('editorEditTaskTaskName')) {
            items.push({
                xtype: 'textfield',
                fieldLabel: me.strings.taskName,
                dataIndex:'taskName',
                itemId:'taskName',
                bind:{
                    value:'{currentTask.taskName}'
                }
            });
        }else{
            items.push({
                xtype: 'displayfield',
                fieldLabel: me.strings.taskName,
                bind:{
                    value:'{currentTask.taskName}'
                }
            });
        }

        //is the user allowed to edit the project manager and is allowed to edit all tasks
        if(auth.isAllowed('editorEditTaskPm') && auth.isAllowed('editorEditAllTasks')) {
            items.push({
                xtype: 'combo',
                fieldLabel: me.strings.pmGuid,
                allowBlank: false,
                typeAhead: false,
                forceSelection: true,
                anyMatch: true,
                queryMode: 'local',
                dataIndex: 'pmGuid',
                itemId: 'pmGuid',
                displayField: 'longUserName',
                valueField: 'userGuid',
                listConfig: {
                    loadMask: false
                },
                bind:{
                    value:'{currentTask.pmGuid}'
                },
                store: Ext.create('Ext.data.Store',{
                    autoLoad: true,
                    model: 'Editor.model.admin.User',
                    pageSize: 0,
                    proxy : {
                        type : 'rest',
                        url: Editor.data.restpath+'user/pm',
                        extraParams: {
                            sort: '[{"property":"surName","direction":"ASC"},{"property":"firstName","direction":"ASC"}]'
                        },
                        reader : {
                            rootProperty: 'rows',
                            type : 'json'
                        }
                    }
                })
            });
        }else{
            items.push({
                xtype: 'displayfield',
                fieldLabel: me.strings.pmGuid,
                bind:{
                    value:'{currentTask.pmName}'
                }
            });
        }

        //is the user allowed to edit the delivery date
        if(auth.isAllowed('editorEditTaskDeliveryDate')) {
            items.push({
                xtype: 'datefield',
                fieldLabel: me.strings.deliveryDate,
                dataIndex:'targetDeliveryDate',
                itemId:'targetDeliveryDate',
                bind:{
                    value:'{currentTask.targetDeliveryDate}'
                }

            });
        }else{
            items.push({
                xtype: 'displayfield',
                fieldLabel: me.strings.deliveryDate,
                renderer: function(value, displayField) {
                    return Ext.Date.format(value, Ext.Date.defaultFormat);
                },
                bind:{
                    value:'{currentTask.targetDeliveryDate}'
                }
            });
        }

        //is the user allowed to edit the real delivery date
        if(auth.isAllowed('editorEditTaskRealDeliveryDate')) {
            items.push({
                xtype: 'datefield',
                fieldLabel: me.strings.realDeliveryDate,
                dataIndex:'realDeliveryDate',
                itemId:'realDeliveryDate',
                bind:{
                    value:'{currentTask.realDeliveryDate}'
                }
            });
        }else{
            items.push({
                xtype: 'displayfield',
                fieldLabel: me.strings.realDeliveryDate,
                renderer: function(value, displayField) {
                    return Ext.Date.format(value, Ext.Date.defaultFormat);
                },
                bind:{
                    value:'{currentTask.realDeliveryDate}'
                }
            });
        }

        //is the user allowed to edit the order date
        if(auth.isAllowed('editorEditTaskOrderDate')) {
            items.push({
                xtype: 'datefield',
                fieldLabel: me.strings.orderDate,
                dataIndex:'orderdate',
                itemId:'orderdate',
                bind:{
                    value:'{currentTask.orderdate}'
                }
            });
        }else{
            items.push({
                xtype: 'displayfield',
                fieldLabel: me.strings.orderDate,
                renderer: function(value, displayField) {
                    return Ext.Date.format(value, Ext.Date.defaultFormat);
                },
                bind:{
                    value:'{currentTask.orderdate}'
                }
            });
        }

        return items;
    },
  });