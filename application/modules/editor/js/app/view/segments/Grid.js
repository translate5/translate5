
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * @class Editor.view.segments.Grid
 * @extends Editor.view.ui.segments.Grid
 * @initalGenerated
 */
Ext.define('Editor.view.segments.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.segments.RowEditing',
        //'Editor.view.segments.GridFilter',
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
    
    tools: [
        {
            type: 'up',
            itemId: 'headPanelUp'
        },
        {
            type: 'down',
            hidden: true,
            itemId: 'headPanelDown'
        }
    ],
    
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
    column_edited: '#UT#bearbeibar',
    
    column_edited_icon: '{0} <img src="{1}" class="icon-editable" alt="{2}" title="{3}">',
    
    columnMap:{},
    stateData: {},
    qualityData: {},
    //features: [{
        //ftype: 'editorGridFilter'
    //}],
    //FIXME ext6 disable own vertical scroller:
    //X-Type for own vertical scroller
    //verticalScrollerType: 'editorgridscroller',
    //FIXME ext6 disable this setting: invalidateScrollerOnRefresh: false,
    //Einbindung des eigenen Editor Plugins
    /**
     * Config Parameter für die {Ext.grid.View} des Grids
     */
    viewConfig: {
        //FIXME ext6 blockRefresh: true,
        getRowClass: function(record, rowIndex, rowParams, store){
            if(record.get('editable')){
                return "";
            }
            return "editing-disabled";
        }
    },
    constructor: function() {
        /* FIXME ext6 todo
        this.plugins = [
            Ext.create('Editor.view.segments.RowEditing', {
                clicksToMoveEditor: 1,
                autoCancel: false
            })
        ];
        */
        this.callParent(arguments);
    },
    initComponent: function() {
        var me = this,
            columns = [],
            firstTargetFound = false,
            fields = Editor.data.task.segmentFields(),
            userPref = Editor.data.task.userPrefs().first(),
            fieldList = [],
            fieldClsList;

        if(Editor.app.authenticatedUser.isAllowed('editorCommentsForLockedSegments')) {
            me.addCls('comments-for-locked-segments');
        } 
        
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
                var steps = Editor.data.task.getWorkflowMetaData().steps;
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
                label = rec.get('label'),
                width = rec.get('width'),
                widthFactorHeader = Editor.data.columns.widthFactorHeader,
                widthOffsetEditable = Editor.data.columns.widthOffsetEditable,
                ergoWidth = width * Editor.data.columns.widthFactorErgonomic,
                labelWidth = (label.length) * widthFactorHeader,
                maxWidth = Editor.data.columns.maxWidth,
                isEditableTarget = type == rec.TYPE_TARGET && editable,
                isErgoVisible = !firstTargetFound && isEditableTarget || type == rec.TYPE_SOURCE;
            
            
            //stored outside of function and must be set after isErgoVisible!
            firstTargetFound = firstTargetFound || isEditableTarget; 
            
            if(!rec.isTarget() || ! userPref.isNonEditableColumnDisabled()) {
                width = Math.min(Math.max(width, labelWidth), maxWidth);
                var col2push = {
                    xtype: 'contentColumn',
                    segmentField: rec,
                    fieldName: name,
                    hidden: !userPref.isNonEditableColumnVisible() && rec.isTarget(),
                    isErgonomicVisible: isErgoVisible && !editable,
                    isErgonomicSetWidth: true, //currently true for all our affected default fields
                    text: label,
                    width: width
                };
                if(width < maxWidth){
                //the following line would be an alternative to only adjust the columnWidth of 
                //hidden cols in ergoMode. This would ensure, that the horizontal scrollbar
                //keeps working, which it does not if it is not initialized at the first place
                //(due to an ExtJs-bug)    
                //if(width !== maxWidth && (isErgoVisible && !editable)=== false){
                    col2push.ergonomicWidth = Math.max(labelWidth, ergoWidth);
                }
                columns.push(col2push);
            }
            
            if(editable){
                labelWidth = (label.length) * widthFactorHeader + widthOffsetEditable;
                label = Ext.String.format(me.column_edited_icon, label, Ext.BLANK_IMAGE_URL, me.column_edited, me.column_edited);
                width = Math.min(Math.max(width, labelWidth), maxWidth);
                var col2push = {
                    xtype: 'contentEditableColumn',
                    segmentField: rec,
                    fieldName: name,
                    isErgonomicVisible: isErgoVisible,
                    isErgonomicSetWidth: true, //currently true for all our affected default fields
                    text: label,
                    width: width
                };
                if(width < maxWidth){
                //the following line would be an alternative to only adjust the columnWidth of 
                //hidden cols in ergoMode. This would ensure, that the horizontal scrollbar
                //keeps working, which it does not if it is not initialized at the first place
                //(due to an ExtJs-bug)
                //if(width !== maxWidth && !isErgoVisible){
                    col2push.ergonomicWidth = Math.max(labelWidth, ergoWidth);
                }
                columns.push(col2push);
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
        
        if(Editor.data.segments.showQM){
            columns.push({
                xtype: 'qualityColumn',
                itemId: 'qualityColumn'
            });
        }
    
        columns.push.apply(columns, [{
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
        
        fieldClsList = me.query('.contentEditableColumn').concat(me.query('.contentColumn'));
        Ext.Array.each(fieldClsList, function(item, idx){
            fieldClsList[idx] = '#segment-grid .x-grid-row .x-grid-cell-'+item.itemId+' .x-grid-cell-inner { width: '+item.width+'px; }';
        });
        Ext.util.CSS.removeStyleSheet('segment-content-width-definition');
        Ext.util.CSS.createStyleSheet(fieldClsList.join("\n"),'segment-content-width-definition');
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
});