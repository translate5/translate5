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
 * @class Editor.view.segments.Grid
 * @extends Editor.view.ui.segments.Grid
 * @initalGenerated
 */
Ext.define('Editor.view.segments.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.segments.RowEditing',
        'Editor.view.segments.GridFilter',
        'Editor.view.segments.column.Content',
        'Editor.view.segments.column.ContentEditable',
        'Editor.view.segments.column.SegmentNrInTask',
        'Editor.view.segments.column.State',
        'Editor.view.segments.column.Quality',
        'Editor.view.segments.column.Matchrate',
        'Editor.view.segments.column.AutoState',
        'Editor.view.segments.column.UserName',
        'Editor.view.segments.column.Comments',
        'Editor.view.segments.column.WorkflowStep',
        'Editor.view.segments.column.Editable'
    ],
    alias: 'widget.segments.grid',
    stateful: false,
    
    store: 'Segments',
  
    cls: 'segment-tag-viewer',
    id: 'segment-grid',
  
    title: 'Segmentliste und Editor',
    title_readonly: '#UT#Segmentliste und Editor - [LESEMODUS]',

    //Item Strings: 
    item_viewModesMenu: '#UT#Editormodi',
    item_viewModeBtn: '#UT#Ansichtsmodus',
    item_editModeBtn: '#UT#Bearbeitungsmodus',
    item_ergonomicModeBtn: '#UT#Ergonimic',
    item_hideTagBtn: '#UT#Tags verbergen',
    item_shortTagBtn: '#UT#Tag-Kurzansicht',
    item_fullTagBtn: '#UT#Tag-Vollansicht',
    item_qmsummaryBtn: '#UT#QM-Subsegment-Statistik',
    item_optionsTagBtn: '#UT#Einstellungen',
    item_clearSortAndFilterBtn: '#UT#Sortierung und Filter zurücksetzen',
    column_edited: '#UT#{0}: bearbeitet',
  
    columnMap:{},
    stateData: {},
    qualityData: {},
    features: [{
        ftype: 'editorGridFilter'
    }],
    //eigener X-Type für den Scroller
    verticalScrollerType: 'editorgridscroller',
    invalidateScrollerOnRefresh: false,
    //Einbindung des eigenen Editor Plugins
    /**
     * Config Parameter für die {Ext.grid.View} des Grids
     */
    viewConfig: {
        blockRefresh: true,
        getRowClass: function(record, rowIndex, rowParams, store){
            if(record.get('editable')){
                return "";
            }
            return "editing-disabled";
        }
    },
    constructor: function() {
        this.plugins = [
            Ext.create('Editor.view.segments.RowEditing', {
                clicksToMoveEditor: 1,
                autoCancel: false
            })
        ];
        this.callParent(arguments);
    },
    initComponent: function() {
        var me = this,
            columns = [],
            firstTargetFound = false,
            fields = Editor.data.task.segmentFields(),
            fieldList = [];
        
        this.store = Ext.create('Editor.store.Segments',{
            storeId: 'Segments'
        });
        
        //befülle interne Hash Map mit QM und Status Werten:
        Ext.each(Editor.data.segments.stateFlags, function(item){
            me.stateData[item.id] = item.label;
        });
        Ext.each(Editor.data.segments.qualityFlags, function(item){
            me.qualityData[item.id] = item.label;
        });

        columns.push.apply(columns, [{
            xtype: 'segmentNrInTaskColumn',
            itemId: 'segmentNrInTaskColumn',
            width: 50
        },{
            xtype: 'workflowStepColumn',
            itemId: 'workflowStepColumn',
            renderer: function(v) {
                var steps = Editor.data.app.wfSteps;
                return steps[v] ? steps[v] : v;
            },
            width: 140
        }]);
        
        //conv store data to an array
        fields.each(function(rec){
            fieldList.push(rec);
        });
        
        fieldList = Editor.model.segment.Field.listSort(fieldList);
        
        Ext.Array.each(fieldList, function(rec){
            var name = rec.get('name'),
                type = rec.get('type'),
                editable = rec.get('editable'),
                isEditableTarget = type == rec.TYPE_TARGET && editable,
                isErgoVisible = !firstTargetFound && isEditableTarget || type == rec.TYPE_SOURCE;
            
            //stored outside of function and must be set after isErgoVisible!
            firstTargetFound = firstTargetFound || isEditableTarget; 
            
            columns.push({
                xtype: 'contentColumn',
                segmentField: rec,
                fieldName: name,
                isErgonomicVisible: isErgoVisible && !editable,
                isErgonomicSetWidth: true, //currently true for all our affected default fields
                text: rec.get('label')
            });
            
            if(editable){
                columns.push({
                    xtype: 'contentEditableColumn',
                    segmentField: rec,
                    fieldName: name,
                    isErgonomicVisible: isErgoVisible,
                    isErgonomicSetWidth: true, //currently true for all our affected default fields
                    text: Ext.String.format(me.column_edited, rec.get('label'))
                });
            }
        });
        
    
        columns.push.apply(columns, [{
            xtype: 'commentsColumn',
            itemId: 'commentsColumn',
            width: 200
        }]);
    
        if(Editor.data.segments.showStatus){
            columns.push({
                xtype: 'stateColumn',
                itemId: 'stateColumn'
            });
        }
    
        columns.push.apply(columns, [{
            xtype: 'qualityColumn',
            itemId: 'qualityColumn'
        },{
            xtype: 'matchrateColumn',
            itemId: 'matchrateColumn',
            width: 82
        },{
            xtype: 'autoStateColumn',
            itemId: 'autoStateColumn',
            width: 82
        },{
            xtype: 'usernameColumn',
            itemId: 'usernameColumn',
            width: 122
        },{
            xtype: 'editableColumn',
            itemId: 'editableColumn'
        }]);
    
        Ext.applyIf(me, {
       /**
        * for information se below onReconfigure
        *
        * listeners: {'reconfigure': Ext.bind(this.onReconfigure, this)},
        */
            columns: columns,
            viewConfig: {},
            dockedItems: [{
                xtype: 'toolbar',
                width: 150,
                dock: 'top',
                items: [{
                    xtype: 'button',
                    text:me.item_viewModesMenu,
                    itemId: 'viewModeMenu',
                    menu: {
                        xtype: 'menu',
                        items: [{
                            xtype: 'button',
                            itemId: 'viewModeBtn',
                            enableToggle: true,
                            text: me.item_viewModeBtn,
                            toggleGroup: 'toggleView',
                            textAlign: 'left'
                        },{
                            xtype: 'button',
                            itemId: 'editModeBtn',
                            enableToggle: true,
                            pressed: true,
                            text: me.item_editModeBtn,
                            toggleGroup: 'toggleView',
                            textAlign: 'left'
                        },{
                            xtype: 'button',
                            itemId: 'ergonomicModeBtn',
                            enableToggle: true,
                            text: me.item_ergonomicModeBtn,
                            toggleGroup: 'toggleView',
                            textAlign: 'left'
                        }]
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    disabled: true,
                    itemId: 'hideTagBtn',
                    enableToggle: true,
                    text: me.item_hideTagBtn,
                    toggleGroup: 'tagMode'
                },{
                    xtype: 'button',
                    itemId: 'shortTagBtn',
                    enableToggle: true,
                    pressed: true,
                    text: me.item_shortTagBtn,
                    toggleGroup: 'tagMode'
                },{
                    xtype: 'button',
                    itemId: 'fullTagBtn',
                    enableToggle: true,
                    text: me.item_fullTagBtn,
                    toggleGroup: 'tagMode'
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    itemId: 'clearSortAndFilterBtn',
                    cls: 'clearSortAndFilterBtn',
                    text: me.item_clearSortAndFilterBtn
                },{
                    xtype: 'tbseparator',
                    hidden: !Editor.data.task.hasQmSub()
                },{
                    xtype: 'button',
                    itemId: 'qmsummaryBtn',
                    text: me.item_qmsummaryBtn,
                    hidden: !Editor.data.task.hasQmSub()
                },{
                    xtype: 'tbfill'
                },{
                    xtype: 'button',
                    itemId: 'optionsBtn',
                    text: me.item_optionsTagBtn
                }]
            }]
        });
        me.callParent(arguments);
    },
    selectOrFocus: function(localRowIndex) {
        var sm = this.getSelectionModel();
        if(sm.isSelected(localRowIndex)){
            this.getView().focusRow(localRowIndex);
        }
        else {
            sm.select(localRowIndex);
        }
    }
  /**
   *code which tries to get the roweditor behave on reconfigure of the grid - does not work with ext 4.0.7 so far. Trial-code
   * This code can be removed after upgrade to >4.1.1a - should be fixed there. See http://stackoverflow.com/questions/11963870/using-ext-grid-panel-reconfigure-breaks-the-grids-rowediting-plugin
   * @event reconfigure
   * Fires after a reconfigure.
   * @param {Ext.grid.Panel} this
   * @param {Ext.data.Store} store The store that was passed to the {@link #method-reconfigure} method
   * @param {Object[]} columns The column configs that were passed to the {@link #method-reconfigure} method

    onReconfigure: function (grid, store, columnConfigs) {
        var columns = grid.headerCt.getGridColumns(),
            rowEditingPlugin = grid.editingPlugin,
            me = this;
        //
        // Re-attached the 'getField' and 'setField' extension methods to each column
        //
        rowEditingPlugin.initFieldAccessors(columns);
    
        //
        // Re-create the actual editor (the UI component within the 'RowEditing' plugin itself)
        //
        // 1. Destroy and make sure we aren't holding a reference to it.
        //
        Ext.destroy(rowEditingPlugin.editor);
        rowEditingPlugin.editor = null;
        //
        // 2. This method has some lazy load logic built into it and will initialize a new row editor.
        //
        me.editor = rowEditingPlugin.getEditor();
        /*rowEditingPlugin.initEditor();
        Ext.Array.each(columns, function(col){
            me.editor.setField(col);
        },me);
        me.editor = rowEditingPlugin.getEditor();
    }*/
});