
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
 * @class Editor.view.segments.Grid
 * @extends Editor.view.ui.segments.Grid
 * @initalGenerated
 */
Ext.define('Editor.view.segments.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.segments.grid.Toolbar',
        'Editor.view.segments.RowEditing',
        'Editor.view.segments.column.Content',
        'Editor.view.segments.column.ContentEditable',
        'Editor.view.segments.column.SegmentNrInTask',
        'Editor.view.segments.column.State',
        'Editor.view.segments.column.MatchrateType',
        'Editor.view.segments.column.Matchrate',
        'Editor.view.segments.column.AutoState',
        'Editor.view.segments.column.UserName',
        'Editor.view.segments.column.Comments',
        'Editor.view.segments.column.WorkflowStep',
        'Editor.view.segments.column.Editable',
        'Editor.view.segments.column.IsWatched',
        'Editor.view.segments.column.IsRepeated',
        'Editor.util.SegmentContent',
        'Editor.view.segments.GridViewModel',
        'Editor.view.segments.grid.Header'
    ],
    plugins: ['gridfilters'],
    alias: 'widget.segments.grid',
    helpSection: 'editor',
    stateId: 'editor.segmentsGrid',
    id: 'segment-grid',
    viewModel: {
        type:'segmentsGrid'
    },
    store: 'Segments',
    title: '#UT#Segmentliste und Editor',
    title_readonly: '#UT# - [LESEMODUS]',
    title_addition_unconfirmed: '#UT# - [AUFGABE UNBESTÄTIGT]',
    column_edited: '#UT#bearbeibar',    
    target_original: '#UT# (zur Importzeit)',    
    column_edited_icon: '{0} <img src="{1}" class="icon-editable" alt="{2}" title="{3}">',
    columnMap:{},
    hasRelaisColumn: false,
    stateData: {},
    qualityData: {},

    currentSegmentSize: null,

    /**
     * New segment size cls class with segment size number in it.
     */
    newSegmentSizeCls:'',

    /**
     * New segment size cls class with segment size number in it.
     */
    oldSegmentSizeCls:'',

    /**
     * @cfg {Int} segmentSize
     */
    segmentSize:0,
    
    publishes: {
    	//publish this field so it is bindable
    	segmentSize: true
    },
    
    //events to trigger the state update
    stateEvents:['segmentSizeChanged'],
    
     /***
     * add our custom config to the state return object
     */
    getState: function() {
		var me = this,
			state = me.callParent() || {};
		state = me.addPropertyToState(state, 'segmentSize');
        return state;
    },
    
    /***
     * After applying the default component states, add the custom one
     */
    applyState: function(state) {
    	if(Ext.isEmpty(state) || Ext.Object.isEmpty(state)){
    		return;
        }
        this.callParent(arguments);
        //to prevent a stateChange loop we have to call setSegmentSize silently:
    	this.setSegmentSize(state.segmentSize, false, true);
    },
	
	getSegmentSize:function(){
		return this.segmentSize;
    },
    
    /**
     * Sets the segment font size via CSS class
     */
    setSegmentSize: function(size, relative, ignorestatechange) {
        var me=this,
            oldSize = me.currentSegmentSize,
            sizer;
        if(ignorestatechange) {
            this.stateful = false;
        }
        if(relative) {
            size = oldSize + size;
        }
        size = Math.min(Math.max(size, 1), 6);
        me.currentSegmentSize = size;
        me.segmentSize = me.currentSegmentSize;
        sizer = function(size) {return 'segment-size-'+size};
        size = sizer(size);
        oldSize = sizer(oldSize);
        Ext.getBody().removeCls(oldSize);
        Ext.getBody().addCls(size);
        me.fireEvent('segmentSizeChanged', me, size, oldSize);
        me.newSegmentSizeCls = size;
        me.oldSegmentSizeCls = oldSize;
        if(ignorestatechange) {
            this.stateful = me.initialConfig.stateful;
        }
    },

    constructor: function() {
        this.plugins = [
            'gridfilters',
            Ext.create('Editor.view.segments.RowEditing')
        ];
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
            stateId: 'segmentNrInTaskColumn',
            width: 50
        },{
            xtype: 'workflowStepColumn',
            itemId: 'workflowStepColumn',
            stateId: 'workflowStepColumn',
            renderer: function(v) {
                var steps = Editor.data.task.getWorkflowMetaData().steps;
                return steps[v] ? steps[v] : v;
            },
            width: 140
        },{
            xtype: 'autoStateColumn',
            itemId: 'autoStateColumn',
            stateId: 'autoStateColumn',
            width: 82
        },{
            xtype: 'matchrateColumn',
            stateId: 'matchrateColumn',
        },{
            xtype: 'matchrateTypeColumn',
            stateId: 'matchrateTypeColumn',
            hidden: true
        }]);
        
        //conv store data to an array
        fields.each(function(rec){
            fieldList.push(rec);
        });
        
        fieldList = Editor.model.segment.Field.listSort(fieldList);

        Ext.Array.each(fieldList, function(rec){
            var col2push,
                name = rec.get('name'),
                type = rec.get('type'),
                editable = rec.get('editable'),
                label = rec.get('label'),
                width = rec.get('width'),
                widthFactorHeader = Editor.data.columns.widthFactorHeader,
                widthOffsetEditable = Editor.data.columns.widthOffsetEditable,
                labelWidth = (label.length) * widthFactorHeader,
                maxWidth = Editor.data.columns.maxWidth,
                isEditableTarget = type == rec.TYPE_TARGET && editable;
            
            if(!me.hasRelaisColumn && type == rec.TYPE_RELAIS) {
                me.hasRelaisColumn = true;
            }
            
            //stored outside of function and must be set after isErgoVisible!
            firstTargetFound = firstTargetFound || isEditableTarget; 

            if(!rec.isTarget() || ! userPref.isNonEditableColumnDisabled()) {
                //width is only lesser maxWidth for columns where width was calculated < 250px on serverside
                width = Math.min(Math.max(width, labelWidth), maxWidth);
                if(isEditableTarget) {
                    label = label + me.target_original;
                }
                col2push = {
                    xtype: 'contentColumn',
                    grid: me,
                    segmentField: rec,
                    fieldName: name,
                    stateId: 'contentColumn_'+name,
                    hidden: !userPref.isNonEditableColumnVisible() && rec.isTarget(),
                    isContentColumn: true,//TODO this propertie is missing
                    text: label,
                    tooltip: label,
                    width: width
                };
                columns.push(col2push);
            }
            
            if(editable){
                labelWidth = (label.length) * widthFactorHeader + widthOffsetEditable;
                label = Ext.String.format(me.column_edited_icon, rec.get('label'), Ext.BLANK_IMAGE_URL, me.column_edited, me.column_edited);
                //width is only lesser maxWidth for columns where width was calculated < 250px on serverside
                width = Math.min(Math.max(width, labelWidth), maxWidth);
                col2push = {
                    xtype: 'contentEditableColumn',
                    grid: me,
                    segmentField: rec,
                    fieldName: name,
                    stateId: 'contentColumn_'+name+'_edit',
                    isContentColumn: true,//TODO those properties are missing 
                    isEditableContentColumn: true,//TODO those properties are missing
                    tooltip: rec.get('label'),
                    text: label,
                    width: width
                };
                columns.push(col2push);
            }
        });
        columns.push.apply(columns, [{
            xtype: 'commentsColumn',
            itemId: 'commentsColumn',
            stateId:'commentsColumn',
            width: 200
        }]);

        if(Editor.app.getTaskConfig('segments.showStatus')){
            columns.push({
                xtype: 'stateColumn',
                itemId: 'stateColumn',
                stateId:'stateColumn',
            });
        }
        columns.push.apply(columns, [{
            xtype: 'usernameColumn',
            itemId: 'usernameColumn',
            stateId:'usernameColumn',
            width: 122
        },{
            xtype: 'editableColumn',
            itemId: 'editableColumn',
            stateId:'editableColumn'
        },{
            xtype: 'iswatchedColumn',
            itemId: 'iswatchedColumn',
            stateId:'iswatchedColumn'
        }, {
            xtype: 'isRepeatedColumn',
            itemId: 'isRepeatedColumn',
            stateId:'isRepeatedColumn',
            hideable: Editor.data.task.get('defaultSegmentLayout')
        }]);
    
        //allow the view mode controller to prepare (and store) the columns setup
        me.fireEvent('beforeinitcolumns', columns);
    
        Ext.applyIf(me, {
       /**
        * for information se below onReconfigure
        *
        * listeners: {'reconfigure': Ext.bind(this.onReconfigure, this)},
        */
            listeners:{
                beforerender:function(grid){
                    //Disable the sorting on column click in editor : TRANSLATE-1295
                    grid.down('headercontainer').sortOnClick = false;
                }
            },
            header: {
                xtype: 'segmentsHeader',
            },
            columns: columns
        });

        me.callParent(arguments);

        fieldClsList = me.query('contentEditableColumn').concat(me.query('contentColumn'));
    },
    
    initConfig: function(instanceConfig) {
            var me = this,
            config = {
                title: me.title, //see EXT6UPD-9
                viewConfig: {
                    //FIXME rowParams is marked as deprecated in extjs 6.2 docu
                    getRowClass: function(record, rowIndex, rowParams, store){
                        var newClass = ['segment-font-sizable'],
                            // only on non sorted list we mark last file segments
                            isDefaultSort = (store.sorters && store.sorters.length == 0);
                        
                        if (isDefaultSort && record.get('isFirstofFile')){
                            newClass.push('first-in-file');
                        }
                        me.lastRowIdx = rowIndex;
                        if (!record.get('editable')) {
                            newClass.push('editing-disabled');
                        }
                        try {
                            me.fireEvent('renderrowclass', newClass, record, rowIndex, store);
                        }catch (e) {
                            Ext.raise(e);
                        }
                        return newClass.join(' ');
                    }
                },
                dockedItems: [{
                    xtype: 'segmentsToolbar',
                    dock: 'top',
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    /**
     * Scrolls the view to the segment on the given rowindex,
     * positions the desired segment in the view as defined in targetregion
     * editor means under the roweditor, if no editor is opened the default is used.
     * 
     * @param {Integer} rowindex
     * @param {Object} config, for config see positionRowAfterScroll, its completly given to that method
     */
    scrollTo: function(rowindex, config) {
        var me = this,
            options = {
                animate: false //may not be animated, to place the callback at the correct place 
            };
        
        options.callback = function(idx, model, row) {
            if(model.get('editable')){
                me.selectOrFocus(rowindex);
            }
            me.positionRowAfterScroll(rowindex, row, config);
        };

        me.ensureVisible(rowindex, options);
    },
    /**
     * positions the given row to the given target, for valid targets see scrollTo
     * @private
     * @param {Integer} rowindex
     * @param {HTMLElement} row
     * @param {Object} config
     * config parameters: 
     *   target: {String} one of "editor", "top", "bottom", "center" (default), 
     *   notScrollCallback: {Function} callback which will be called, if we are not possible to scroll to the desired position (top/bottom reached)
     *   callback: {Function} callback which will be called in every case after final scroll animation

     */
    positionRowAfterScroll: function(rowindex, row, config) {
        var me = this,
            view = me.getView(),
            editor = me.editingPlugin.editor,
            rowFly = Ext.fly(row),
            rowHeight = rowFly.getHeight(),
            rowTop = rowFly.getOffsetsTo(view)[1],
            topMargin = 20,
            viewHeight = view.getHeight(),
            bottomMargin = 20,
            config = Ext.applyIf(config || {}, {
                target: 'editor',
                notScrollCallback: Ext.emptyFn,
                callback: Ext.emptyFn
            }),
            target = config.target,
            deltaY;
                    
        //if no editor exists scroll to center
        if(target == 'editor' && !editor) {
            target = 'center';
        }

        switch (target) {
            case 'editor':
                deltaY = editor.editorLocalTop - rowTop;
                break;
            case 'top':
                deltaY = topMargin - rowTop;
                break;
            case 'bottom':
                deltaY = (viewHeight - bottomMargin) - (rowTop + rowHeight);
                break;
            case 'center':
            default:
                deltaY = viewHeight/2 - (rowTop + rowHeight/2);
                break;
        }
        if(!view.el.scroll('t', deltaY, {callback: config.callback})) {
            config.notScrollCallback();
        }
    },
    /**
     * selects and give the focus to the segment on the given rowindex
     */
    selectOrFocus: function(rowIdx) {
        var me = this,
            sm = me.getSelectionModel();
        if(sm.isSelected(rowIdx)){
            me.getView().focusRow(rowIdx);
        }
        else {
            sm.select(rowIdx);
        }
    },
    
    /***
     * Return visible row indexes in segment grid
     * TODO if needed move this as overide so it can be used for all grids
     * @returns {Object} { top:topIndex, bottom:bottomIndex }
     */
    getVisibleRowIndexBoundaries:function(){
        var view = this.getView(),
            vTop = view.el.getTop(),
            vBottom = view.el.getBottom(),
            top=-1, bottom=-1;


        Ext.each(view.getNodes(), function (node) {
            if (top < 0 && Ext.fly(node).getBottom() > vTop) {
                top = view.indexOf(node);
            }
            if (Ext.fly(node).getTop() < vBottom) {
                bottom = view.indexOf(node);
            }
        });

        return {
            top:top,
            bottom:bottom,
        };
    },

    /***
     * Search for segment position in the current store filtering
     */
    searchPosition:function(segmentNrInTask){
        var me = this,
            params = me.getStore().getParams();        
        return new Promise((res,rej) => {
            Ext.Ajax.request({
                url: Editor.data.restpath+'segment/'+segmentNrInTask+'/position',
                method: 'GET',
                params: params,
                scope: me,
                success: function(response){
                    var responseData = Ext.JSON.decode(response.responseText),
                        index = responseData ? responseData.index : -1;
                    res(index);
                },
                failure: function(response){
                    if(response.status===404 && (response.statusText ==="Nicht gefunden!" || response.statusText ==="Not Found")){
                        //404 means no index found
                        res(-1);
                        return;
                    }
                    Editor.app.getController('ServerException').handleException(response);
                }
            });
        });
    }
});