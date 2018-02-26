
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class SearchReplace
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.SearchReplace', {
    extend : 'Ext.app.Controller',
    
    requires:[
        'Editor.view.searchandreplace.SearchReplaceWindow'
    ],
    
    mixins: [
        'Editor.util.SearchReplaceUtils'
    ],

    listen:{
        component:{
            '#segmentgrid':{
                afterrender:'onSegmentGridAfterRender',
                filterchange:'onSegmentGridFilterChange',
                sortchange:'onSegmentGridSortChange',
                columnshow:'onColumnVisibilityChange',
                columnhide:'onColumnVisibilityChange',
                edit:'onSegmentGridEdit',
                canceledit:'onSegmentGridCancelEdit',
            },
            '#searchreplacewindow':{
                show:'onSearchReplaceWindowShow',
                destroy:'onSearchReplaceWindowDestroy'
            },
            '#searchreplacetabpanel #searchButton':{
                click:'triggerSearch'
            },
            '#searchreplacetabpanel #replaceButton':{
                click:'onReplaceButtonClick'
            },
            '#searchreplacetabpanel #replaceAllButton':{
                click:'onReplaceAllButtonClick'
            },
            '#searchTopChekbox':{
                change:'onSearchTopChange'
            },
            '#searchCombo':{
                change:'onSearchComboChange',
                keyup:'triggerSearchOnEnter'
            },
            '#searchInCombo':{
                keyup:'triggerSearchOnEnter'
            },
            'segmentsHtmleditor': {
                initialize: 'initEditor',
                push: 'handleAfterPush'
            }
        },
        controller:{
            '#Editor': {
                beforeKeyMapUsage: 'handleEditorKeyMapUsage',
                prepareTrackChangesForSaving: 'handleTrackChangesForSaving'
            },
            '#Editor.$application': {
                editorViewportClosed: 'onEditorViewportClosed',
                editorViewportOpened:'onEditorViewportOpened'
            },
            '#Editor.plugins.TrackChanges.controller.Editor':{
                deleteAndReplaceFinished:'onDeleteAndReplaceFinished'
            }
        }
    },
    
    refs:[{
        ref:'segmentGrid',
        selector:'#segmentgrid'
    },{
        ref:'tabPanel',
        selector:'#searchreplacetabpanel'
    },{
        ref:'searchReplaceWindow',
        selector:'#searchreplacewindow'
    }],

    /***
     * Available fields in the grid for search
     */
    searchFields:[],
    
    /***
     * Available fields in grid for replace
     */
    replaceFields:[],
    
    /***
     * Currently active column data index
     */
    activeColumnDataIndex:'',
    
    
    /***
     * Default column data index
     */
    DEFAULT_COLUMN_DATA_INDEX:'targetEdit',

    /***
     * When the filter is active and the user open the search/replace window, we display the info message that
     * the search/replace will be performed only on filtered segments
     */
    isFilterActive:false,
    
    /***
     * The segment information.
     * matchIndex -> the match index in the currently edited segment
     * nextSegmentIndex -> the index of the next segment which needs to be opened (index in the result array)
     * currentSegmentIndex -> currently active segment index (index in the result array)
     * matchCount -> number of matches in the currently edited segment 
     */
    activeSegment:{
        matchIndex:0,
        nextSegmentIndex:0,
        currentSegmentIndex:0,
        matchCount:0,
        openedSegments:[]
    },
    
    /***
     * Pointer to the array index of replace ranges
     */
    replaceArrayPointer:0,

    /***
     * Serialized ranges for replacement
     */
    replaceRanges:[],
    
    
    /***
     * Flag if the db search should be provided
     */
    searchRequired:true,
    
    /***
     * Previous searched cell
     */
    oldSerchedCell:null,
    
    
    /***
     * Flag so we know if the track changes are active or not
     */
    activeTrackChanges:true,

    /***
     * The segment's Editor (Editor.view.segments.HtmlEditor) 
     */
    editor: null,

    /***
     * Segment's Editor: HTMLDocument
     */
    editorDoc: null,

    /***
     * Segment's Editor: HTMLBodyElement
     */
    editorBody: null,
    
    /***
     * Segment's Editor: Ext.dom.Element
     */
    editorBodyExtDomElement: null,
    
    strings:{
        searchInfoMessage:'#UT#Die Suche wird nur auf den gefilterten Segmenten durchgeführt',
        comboFieldLabel:'#UT#Ersetzen',
        noSearchResults:'#UT#Keine Ergebnisse für die aktuelle Suche!',
        replaceAllWindowBtnText:'#UT#Alle Ergebnisse ersetzen',
        cancelReplaceAllWindowBtnText:'#UT#Abbrechen',
        replaceAllWindowTitle:'#UT#Ergebnisse ersetzen',
        replaceAllWindowMessage:'#UT#übereinstimmungen gefunden. Wollen Sie wirklich alle ersetzen? Alle gefundenen Segmente werden inklusive Auto-Status und letztem Editor geändert',
        characterLimitError:'#UT#Der Suchstring ist zu groß',
        noIndexFound:'#UT#Das Segment ist in Ihrer aktuellen Filterung nicht enthalten.',
        searchAndReplaceMenuItem:'#UT#Suchen und ersetzen'
    },
    
    constructor:function(){
        this.callParent(arguments);;
    },
    
    initConfig:function(){
        var me=this;
        me.callParent(arguments);
        me.resetActiveColumnDataIndex();
    },
    
    /**
     * @param {Editor.view.segments.HtmlEditor} editor
     */
    initEditor: function(editor) {
        var me = this;
        Ext.util.CSS.createStyleSheetToWindow(
            editor.getDoc(),
            '.searchreplace-active-match {background-color: rgb(255, 0, 0) !important;}'+
            '.searchreplace-hide-element {display: none !important;}'//this class exist also in t5 main css. It is used also by the segment grid
        );

        me.editor = editor;
    },

    /**
     * Handle push-Even
     */
    handleAfterPush: function(editor) {
        var me=this;
        me.initEditorContent(editor);
        //if the search replace window exist, try to find matches
        if(me.getSearchReplaceWindow()){
            me.findMatchesDelay();
            return;
        }
    },

    /**
     * Init the editor document, body and ext body objects
     */
    initEditorContent: function(editor) {
        var me = this;
        if (me.editorBodyExtDomElement == null || me.editor == null) {
            me.initEditor(editor);
        }
        me.editorDoc = me.editor.getDoc();
        me.editorBody = me.editor.getEditorBody();
        me.editorBodyExtDomElement = Ext.get(me.editorBody);
    },

    /***
     * Use this function to get the editor
     */
    getEditorBody:function(){
        var me=this;
        if(!me.editor){
            return false;
        }
        if(me.editor.editorBody){
            return me.editor.editorBody;
        }
        //reinit the editor body
        me.editorBody=me.editor.getEditorBody();
        return me.editorBody;
    },

    /***
     * Use this function to get the editor ext document element
     */
    getEditorBodyExtDomElement:function(){
        var me=this;
        me.editorBodyExtDomElement=Ext.get(me.getEditorBody());
        return me.editorBodyExtDomElement;
    },

    /**
     * Fire event so the internal flag isSearchReplaceRange in trackchanges class is set
     * That is used to enable/disable some of the trackchanges functionality needed for the search and replace
     */
    setTrackChangesInternalFlag:function(isSearchReplaceRange){
        this.fireEvent('isSearchReplaceRangeChange',isSearchReplaceRange);
    },
    
    /***
     * when the editor is close
     */
    onEditorViewportClosed:function(){
        this.destroySearchWindow();
    },
    
    /**
     * On editor view port opened handler
     */
    onEditorViewportOpened:function(){
        this.isActiveTrackChanges();
    },

    /***
     * Event handler
     */
    handleTrackChangesForSaving:function(){
        var me=this;
        me.cleanMarkTags();
        me.removeReplaceClass();
    },
    
    /***
     * On segment grid after render handler
     * Here we add the search and replace menu item on the needed column
     */
    onSegmentGridAfterRender:function(segmentGrid){
        var me=this,
            menu = segmentGrid.headerCt.getMenu();
        
        //add the menu item to the grid menu
        me.addSearchReplaceMenu(menu);
        
        //add menu handler, so we hide and show the search/replace menu item
        menu.on({
            beforeshow:{
                fn:me.onSegmentGridMenuBeforeShow,
                scope:me
            },
            hide:{
                fn:me.onSegmentGridMenuHide,
                scope:me
            },
        });
        
        //init the search/replace column index arrays
        me.initColumnArrays();
    },
    
    /***
     * On segment grid filter change handler.
     * Set the flag that the filter is active, so the info message is displayed
     */
    onSegmentGridFilterChange:function(store,filters,eOpts){
        this.isFilterActive=filters.length>0;
        this.destroySearchWindow(true);
    },
    
    /***
     * On segment grid sort change handler.
     * If the segment window is active, we destroy the window and open it again
     */
    onSegmentGridSortChange:function(ct,column,direction,eOpts){
        this.destroySearchWindow(true);
    },
    
    /***
     * On segment grid edit handler.
     * Reset the active segment match index and match count
     */
    onSegmentGridEdit:function(){
        var me=this;
        me.activeSegment.matchIndex=0;
        me.activeSegment.matchCount=0;
        me.removeReplaceClass();
    },
    
    /***
     * On segment grid cancel edit handler.
     * Reset the active segment match index and match count
     */
    onSegmentGridCancelEdit:function(){
        this.activeSegment.matchIndex=0;
        this.activeSegment.matchCount=0;
    },
    
    /***
     * On segment grid column show/hide.
     * Init the search and replace field arrays
     */
    onColumnVisibilityChange:function(){
        this.initColumnArrays();
    },
    
    /***
     * On search top click handler.
     * Update the current active segment index.
     */
    onSearchTopChange:function(checkbox,newValue,oldValue,eOpts){
        this.updateSegmentIndex(newValue);
    },
    
    onSearchComboChange:function(){
        this.searchRequired=true;
    },

    /**
     * After the delete and replace is finished handler
     */
    onDeleteAndReplaceFinished:function(){
        var me=this,
            iframeDocument = me.getSegmentIframeDocument();

        //clean the mark tags from the editor
        me.cleanMarkTags();
        
        //run the search once again
        me.findMatches();
        
        //disable the flag
        me.setTrackChangesInternalFlag(false);

        //fire event when the replace is done
        me.fireEvent('editorTextReplaced',iframeDocument.body.innerHTML);

        me.jumpToNextSegment();
    },

    /***
     * Add keymap for search and replace
     */
    handleEditorKeyMapUsage: function(cont, area, mapOverwrite) {
        var me = this;
        cont.keyMapConfig['ctrl-f'] = ['f',{ctrl: true, alt: false},me.handleSearchReplaceHotkey, true];
        cont.keyMapConfig['ctrl-h'] = ['h',{ctrl: true, alt: false},me.handleSearchReplaceHotkey, true];
    },
    
    /***
     * Add the search and replace meinu item
     */
    addSearchReplaceMenu:function(gridMenu){
        // add menu item  into the menu and store its reference
        var me=this,
            menuItem = gridMenu.add({
                itemId:'searchReplaceMenu',
                text: me.strings.searchAndReplaceMenuItem,
                iconCls:'x-fa fa-search',
                scope:me,
                handler:me.showSearchAndReplaceWindow
            });
    },
    
    /***
     * On segment grid menu before show.
     * Show or hide the search and replace window, based of if the column is searchable or not
     */
    onSegmentGridMenuBeforeShow:function(menu){
        // get data index of column for which menu will be displayed
        var me=this,
            searchReplaceMenu=menu.down('#searchReplaceMenu'),
            currentDataIndex = menu.activeHeader.dataIndex;

        me.activeColumnDataIndex = currentDataIndex;
        // show/hide menu item in the menu
        if(Ext.Array.contains(me.searchFields,currentDataIndex)) {
            searchReplaceMenu.show();
            return;
        }
        searchReplaceMenu.hide();
    },
    
    /***
     * Segment grid menu hide hanlder
     */
    onSegmentGridMenuHide:function(){
        //reset the current active column data index
        this.resetActiveColumnDataIndex();
    },
    
    /***
     * Reset the default active column data index
     */
    resetActiveColumnDataIndex:function(){
        var me=this;
        me.activeColumnDataIndex =me.DEFAULT_COLUMN_DATA_INDEX;
    },
    
    /***
     * Insert the replace combo in the replace tab
     */
    onSearchReplaceWindowShow:function(win){
        var tabPanel=win.down('#searchreplacetabpanel'),
            replaceTab=tabPanel.down('#replaceTab'),
            activeTab=tabPanel.getActiveTab(),
            searchCombo=activeTab.down('#searchCombo'),
            searchInCombo=activeTab.down('#searchInCombo');

        
        replaceTab.insert(1,{
            xtype:'textfield',
            itemId:'replaceCombo',
            focusable:true,
            fieldLabel:this.strings.comboFieldLabel,
        });
        
        searchCombo.focus();
        this.initSearchInComboStore(searchInCombo);
    },
    
    /***
     * On search window destroy handler
     */
    onSearchReplaceWindowDestroy:function(){
      //reset the current active column data index
        this.resetActiveColumnDataIndex();
    },
    
    /***
     * Handler for search
     */
    triggerSearch:function(field,ev,eOpts){
        var me=this;
        
        //set the current field from where the search is triggered
        me.searchFieldTrigger=field;

        if(me.isSearchRequired()){
            me.search();
            return;
        }
        me.handleRowSelection();
    },

    /**
     * Search handler for field on enter pressed
     */
    triggerSearchOnEnter:function(field,ev,eOpts){
        if(ev.getKey() == ev.ENTER){
            this.triggerSearch(field,ev,eOpts);
        }
    },
    
    /***
     * Handler for replace all
     */
    onReplaceButtonClick:function(field){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            replaceCombo=activeTab.down('#replaceCombo'),
            replaceText=replaceCombo.getRawValue(),
            editor=Editor.app.getController('Editor'),
            grid = editor.getSegmentGrid(),
            ed=grid.editingPlugin;
        
        
        if(!ed.editing){
            return;
        }

        //set the feild for focus
        me.searchFieldTrigger=field;

        //find matches once again, the content can be changed between replaces
        me.cleanMarkTags();

        me.findMatches();
        
        
        me.setTrackChangesInternalFlag(true);
        
        if(me.jumpToNextSegment()){
            return;
        }
        
        if(!me.activeTrackChanges){
            me.pureReplace(me.replaceRanges[me.activeSegment.matchIndex],replaceText);
            me.onDeleteAndReplaceFinished();
            return;
        }

        me.fireEvent('deleteAndReplace',
            me.replaceRanges[me.activeSegment.matchIndex],
            replaceText
        );
    },
    
    onReplaceAllButtonClick:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            vm=activeTab.getViewModel(),
            result=vm.get('result');
        
        Ext.create('Ext.window.MessageBox').show({
            title: me.strings.replaceAllWindowTitle,
            msg: result.length+" "+me.strings.replaceAllWindowMessage,
            buttons: Ext.Msg.YESNO,
            fn:me.handleLeaveTaskButton,
            scope:me,
            defaultFocus:'no',
            icon: Ext.MessageBox.QUESTION,
            buttonText: {
                yes: me.strings.replaceAllWindowBtnText,
                no: me.strings.cancelReplaceAllWindowBtnText
            }
        });
    },
    
    /***
     * Handler for the leave task dialog window.
     * 
     */
    handleLeaveTaskButton:function(button){
        if(button=="cancel"){
            return false
        }
        if(button=="yes"){
            this.replaceAll();
            return true;
        }
        return false
    },

    
    /***
     * Show the search replace window based on if the hotkey is used
     */
    showSearchAndReplaceWindow:function(key){
        var me=this;
        if(key instanceof Object){
            var tmpMenu=key.up('menu');
            me.activeColumnDataIndex=tmpMenu.activeHeader.dataIndex;
            me.handleSerchReplaceMenu();
            return;
        }
        
        //if it is not from menu, check if the window is opened from edited segment, if yes select to search to field the current edited segment column
        //if onyl ctrl+f or ctrl+h is pressed and there 
        
        var searchReplaceWindow=Ext.widget('searchreplacewindow'),
            plug = Editor.app.getController('Editor').getEditPlugin();
            //rec = plug.editing && plug.context.record,
            //grid = me.getSegmentGrid(),
            //selModel = grid.getSelectionModel();

        if(plug.editor && plug.editor.editingPlugin.editing){
            me.activeColumnDataIndex = plug.editor.columnClicked;
        }

        if(!key || (key == Ext.event.Event.F)){
            focusTab = 'searchTab';
            if(!Ext.Array.contains(me.searchFields,me.activeColumnDataIndex)){
                me.activeColumnDataIndex=me.DEFAULT_COLUMN_DATA_INDEX;
            }
        }

        if(key == Ext.event.Event.H){
            if(Ext.Array.contains(me.replaceFields,me.activeColumnDataIndex)){
                me.activeColumnDataIndex=me.DEFAULT_COLUMN_DATA_INDEX;
            }
            focusTab = 'replaceTab';
        }
        
        var tabPanel=searchReplaceWindow.down('#searchreplacetabpanel'),
            activeTab=tabPanel.down('#'+focusTab+'');
        tabPanel.setActiveTab(activeTab);
        tabPanel.getViewModel().set('searchView',(focusTab == 'searchTab') ? true : false);
        searchReplaceWindow.show();
    },
    
    /***
     * Handler for search/replace window hotkey
     */
    handleSearchReplaceHotkey:function(key){
        var me=Editor.app.getController('SearchReplace'),
            segmentGrid=me.getSegmentGrid();
        if(!segmentGrid || !segmentGrid.isVisible()) {
            return;
        }
        //if the filter is active, show the info message
        if(me.isFilterActive){
            Editor.MessageBox.addInfo(me.strings.searchInfoMessage);
        }

        me.showSearchAndReplaceWindow(key);
    },
    
    /***
     * Show the search/replace window
     */
    handleSerchReplaceMenu:function(){
        var me=this,
            searchReplaceWindow=Ext.widget('searchreplacewindow');
        searchReplaceWindow.show();
    },
    
    /***
     * Init the search and replace columns combos
     */
    initSearchInComboStore:function(){
        var me=this,
            segmentGrid=Ext.ComponentQuery.query('#segmentgrid')[0],
            columns = segmentGrid.query('gridcolumn[isContentColumn]:not([hidden])'),
            searchStoreData=[],
            replaceStoreData=[],
            searchInCombos=Ext.ComponentQuery.query('#searchInCombo');
        
        
        Ext.Array.each(columns, function(rec) {
            searchStoreData.push({'id':rec.dataIndex , 'value':rec.text.replace(/<(?:.|\n)*?>/gm, '')});
            if(rec.isEditableContentColumn){
                replaceStoreData.push({'id':rec.dataIndex , 'value':rec.text.replace(/<(?:.|\n)*?>/gm, '')});
            }
        });
        
        Ext.Array.each(searchInCombos,function(combo){
            combo.setStore(Ext.create('Ext.data.Store', {
                fields: ['id', 'value'],
                data:combo.up('#searchTab') ? searchStoreData :replaceStoreData
            }));
            var rec = combo.findRecord('id',me.activeColumnDataIndex);
            if(rec){
                combo.setSelection(rec);
            }
        });
    },
    
    /***
     * Initialize the search and replace columns (only the visible one will be selected)
     */
    initColumnArrays:function(){
        var me=this,
            segmentGrid=me.getSegmentGrid();
        me.searchFields=[];
        me.replaceFields=[];
        me.searchFields=me.getColumnDataIndex(segmentGrid.query('gridcolumn[isContentColumn]:not([hidden])'));
        me.replaceFields=me.getColumnDataIndex(segmentGrid.query('gridcolumn[isEditableContentColumn]:not([hidden])'));
    },
    
    /***
     * Get data indexes for given columns(this will put all grid columns data indexes in one array)
     */
    getColumnDataIndex:function(columns){
        if(columns.length < 1){
            return [];
        }
        var dataArray=[];
        Ext.Array.each(columns,function(col){
            Ext.Array.push(dataArray,col.dataIndex)
        });
        return dataArray;
    },

    /***
     * Search the givven imput string
     */
    search:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            activeTabViewModel=activeTab.getViewModel(),
            form=activeTab.getForm(),
            segmentGrid = me.getSegmentGrid(),
            segmentStore=segmentGrid.editingPlugin.grid.store,
            proxy = segmentStore.getProxy(),
            params = {};
        
        params[proxy.getFilterParam()] = proxy.encodeFilters(segmentStore.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(segmentStore.getSorters().items);
        params['taskGuid']=Editor.data.task.get('taskGuid');

        me.searchRequired=false;
        
        form.submit({
            url: Editor.data.restpath+'segment/search',
            params:params,
            method:'GET',
            success: function(form, submit){
                if(!submit.result || !submit.result.rows){
                    return;
                }
                var foundSegments = submit.result.rows,
                    foundSegmentsLength=foundSegments.length,
                    tabPanelviewModel=tabPanel.getViewModel();

                tabPanelviewModel.set('searchPerformed',foundSegmentsLength > 0);
                activeTabViewModel.set('resultsCount',foundSegmentsLength);
                
                activeTabViewModel.set('result',foundSegments);
                activeTabViewModel.set('showResultsLabel',true);
                me.handleRowSelection();
            },
            failure: function(form, submit){
                var res = submit.result;
                //submit results are always state 200.
                //If success false and errors is an array, this errors are shown in the form directly,
                // so we dont need the handleException
                if(!res || res.success || !Ext.isArray(res.errors)) {
                    Editor.app.getController('ServerException').handleException(submit.response);
                    return;
                }
                if(Ext.isArray(res.errors)) {
                    form.markInvalid(res.errors);
                    return;
                }
            }
        });
    },
    
    /***
     * Replace the text only, without ins, dell tags
     */
    pureReplace:function(bookmarkRangeForDel,replaceText){
        var me=this,
            allImagesInNode=[],
            collectedNodesForDel,
            iframeDocument = me.getSegmentIframeDocument(),
            range = rangy.createRange();
        
        range.moveToBookmark(bookmarkRangeForDel);
        
        //collect all images in the range
        collectedNodesForDel = range.getNodes([1,3], function(node) {
            if(node.nodeType == 1 && node.nodeName.toLowerCase() == 'img') {
                allImagesInNode.push(node.cloneNode());
            }
            return true;
        });

        //FIXME this uses tc function for remove content, can be moved in my code for example
        //remove the content between the range
        range.deleteContents();

        var tmpImgNode=null;
        
        allImagesInNode.reverse();
        
        //add the removed images, if there are some
        for(var i=0;i<allImagesInNode.length;i++){
            tmpImgNode=allImagesInNode[i];
            range.insertNode(tmpImgNode);
        }
        
        //add the replace node
        var replaceNode=Ext.DomHelper.createDom({
            tag: 'span',
            cls: me.CSS_CLASSNAME_REPLACED_INS
        });
        replaceNode.innerHTML = replaceText;
        range.insertNode(replaceNode);
    },
    
    
    replaceAll:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            replaceComboValue=activeTab.down('#replaceCombo').getValue(),
            activeTabViewModel=activeTab.getViewModel(),
            form=activeTab.getForm(),
            params = {};

        params['replaceComboValue']=replaceComboValue;
        params['taskGuid']=Editor.data.task.get('taskGuid');

        if(me.isActiveTrackChanges()){
            params['isActiveTrackChanges']=true;
            params['attributeWorkflowstep']=Editor.data.task.get('workflowStepName')+Editor.data.task.get('workflowStep');
            params['userColorNr']=Editor.data.task.get('userColorNr');
        }
        

        form.submit({
            url: Editor.data.restpath+'segment/replaceall',
            params:params,
            method:'POST',
            success: function(form, submit){
                if(!submit.result || !submit.result.rows){
                    return;
                }
                var foundSegments = submit.result.rows;
            },
            failure: function(form, submit){
                var res = submit.result;
                //submit results are always state 200.
                //If success false and errors is an array, this errors are shown in the form directly,
                // so we dont need the handleException
                if(!res || res.success || !Ext.isArray(res.errors)) {
                    Editor.app.getController('ServerException').handleException(submit.response);
                    return;
                }
                if(Ext.isArray(res.errors)) {
                    form.markInvalid(res.errors);
                    return;
                }
            }
        });
    },
    
    /***
     * Open the segment for editing, or move through the search hits in the currently edited segment
     */
    handleRowSelection:function(){
        var me=this,
            plug = Editor.app.getController('Editor'),
            editor = plug.getEditPlugin().editor,
            grid = plug.getSegmentGrid(),
            selModel = grid.getSelectionModel(),
            ed = plug.getEditPlugin();
        
        if(me.activeSegment.matchIndex >= me.activeSegment.matchCount-1) {
            me.activeSegment.matchIndex=0;
            me.findEditorSegment(plug);
            return;
        }
        //FIXME can be moved
        //clean the mark tags from the editor
        me.cleanMarkTags();
        
        me.findMatchesDelay();
        me.activeSegment.matchIndex++;
    },


    /***
     * Triggers the findMatches function with delay.
     */
    findMatchesDelay:function(){
        var me=this;
        //delay so the roweditor is loaded
        var task = new Ext.util.DelayedTask(function(){
            if(me.isContentEditableField()){
                me.findMatches();
            }
            
        });

        task.delay(300);
    },
    
    /***
     * Search and mark the hits in the current open segment in editor
     */
    findMatches:function(){
        var me=this,
            iframeDocument = me.getSegmentIframeDocument(),
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            searchCombo=activeTab.down('#searchCombo'),
            searchTerm=searchCombo.getRawValue(),
            replaceCombo=activeTab.down('#replaceCombo'),
            searchType=activeTab.down('radiofield').getGroupValue(),
            matchCase=activeTab.down('#matchCase').checked;
        
        var classApplierModule = rangy.modules.ClassApplier,
            searchResultApplier=null,
            applyFirstMatchCss=false;
        
        me.replaceRanges=[];
            
        rangy.init();

        if (rangy.supported && classApplierModule && classApplierModule.supported) {
            
            searchResultApplier = rangy.createClassApplier("searchResult",{
                elementTagName:me.NODE_NAME_MARK,
                onElementCreate:function(element){
                    //if(!applyFirstMatchCss){
                    //    return;
                    //}
                    //change the color
                    //element.classList.add("searchreplace-active-match");
                }
            });
            
            searchResultApplier._OVERRIDDENisIgnorableWhiteSpaceNode = searchResultApplier.isIgnorableWhiteSpaceNode;
            
            //The point of this overide is - do not apply mark tags to delete tags.
            //This fix is available only for the current object
            searchResultApplier.isIgnorableWhiteSpaceNode=function(node) {
                if(node.parentElement && node.parentElement.nodeName.toLowerCase() === me.NODE_NAME_DEL){
                    return true;
                }
                return this._OVERRIDDENisIgnorableWhiteSpaceNode.call(this,node);
            };

            // Remove existing highlights
            var range = rangy.createRange(),
                caseSensitive = false,
                searchScopeRange = rangy.createRange();
            
            searchScopeRange.selectNodeContents(iframeDocument);

            var options = {
                caseSensitive: caseSensitive,
                wholeWordsOnly: false,
                withinRange: searchScopeRange,
                wordOptions:{
                    includeTrailingSpace:true  
                },
                direction: "forward" // This is redundant because "forward" is the default,
            };


            //clean the old search in different cell
            if(me.oldSerchedCell){
                range.selectNodeContents(me.oldSerchedCell);
                searchResultApplier.undoToRange(range);
                me.oldSerchedCell=null;
            }
            
            range.selectNodeContents(iframeDocument.body);
            searchResultApplier.undoToRange(range);

            
            if (searchTerm === "") {
                return
            }

            //add display none to all del nodes, with this thay are ignored as searchable
            me.prepareDelNodeForSearch(true);

            //build the search term before search
            searchTerm=me.handleSearchType(searchTerm,searchType,matchCase);
            
            me.activeSegment.matchCount=0;
            // Iterate over matches
            while (range.findText(searchTerm, options)) {
                
                applyFirstMatchCss=false;
                
                //is not an allready replaced match
                if(!me.hasReplacedClass(range)){

                    me.activeSegment.matchCount++;
                    
                    //check if the current hit should be marked
                    //applyFirstMatchCss=me.activeSegment.matchCount==(me.activeSegment.matchIndex+1);
                    
                    //apply to range, this will select the text
                    searchResultApplier.applyToRange(range);

                    
                    //save the range for later replace usage
                    me.replaceRanges.push(range.getBookmark());
                }
                // Collapse the range to the position immediately after the match
                range.collapse(false);
            }
            
            //set the dell nodes visible again
            me.prepareDelNodeForSearch(false);

            //set active class to all mark tags in the current active range
            me.setActiveMatchClass(me.replaceRanges[me.activeSegment.matchIndex]);
            
            //focus the search trigger field
            me.searchFieldTrigger && me.searchFieldTrigger.focus();
        }
    },
    
    /***
     * Find matches in non editable cell.
     */
    findMatchesGrid:function(cell){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            searchCombo=activeTab.down('#searchCombo'),
            searchTerm=searchCombo.getRawValue(),
            replaceCombo=activeTab.down('#replaceCombo'),
            searchType=activeTab.down('radiofield').getGroupValue(),
            matchCase=activeTab.down('#matchCase').checked;
        
        var classApplierModule = rangy.modules.ClassApplier,
            searchResultApplier=null;
        
            
        if (rangy.supported && classApplierModule && classApplierModule.supported) {
            
            searchResultApplier = rangy.createClassApplier("searchResult",{
                elementTagName:me.NODE_NAME_MARK
            });
            
            searchResultApplier._OVERRIDDENisIgnorableWhiteSpaceNode = searchResultApplier.isIgnorableWhiteSpaceNode;
            
            //The point of this overide is - do not apply mark tags to delete tags.
            //This fix is available only for the current object
            searchResultApplier.isIgnorableWhiteSpaceNode=function(node) {
                if(node.parentElement && node.parentElement.nodeName.toLowerCase() === me.self.NODE_NAME_DEL){
                    return true;
                }
                return this._OVERRIDDENisIgnorableWhiteSpaceNode.call(this,node);
            };
    
            // Remove existing highlights
            var range = rangy.createRange(),
                caseSensitive = false,
                searchScopeRange = rangy.createRange(),
                contentDiv=Ext.get(cell.dom).query('div.x-grid-cell-inner'),
                contentDiv=contentDiv.length>0 ? contentDiv[0] : null;
    
            searchScopeRange.selectNodeContents(cell.dom);
    
            var options = {
                caseSensitive: caseSensitive,
                wholeWordsOnly: false,
                withinRange: searchScopeRange,
                wordOptions:{
                    includeTrailingSpace:true  
                },
                direction: "forward" // This is redundant because "forward" is the default,
            };
    
            
            if(me.oldSerchedCell){
                range.selectNodeContents(me.oldSerchedCell);
                searchResultApplier.undoToRange(range);
            }
            
            range.selectNodeContents(cell.dom);
            searchResultApplier.undoToRange(range);
            
            me.oldSerchedCell=cell.dom;

            //if the contend div is found, try to find and hide all div childs(thay are not needed for the search)
            if(contentDiv){
                var divNodesToHide=Ext.get(contentDiv).query('div'),
                arrLength=divNodesToHide.length;
                
                for (i = 0; i < arrLength; i++){
                    node = divNodesToHide[i];
                    node.classList.add(me.CSS_CLASSNAME_HIDE_ELEMENT);
                }
            }
            
            if (searchTerm === "") {
                return
            }
    
            //build the search term before search
            searchTerm=me.handleSearchType(searchTerm,searchType);
            
            // Iterate over matches
            while (range.findText(searchTerm, options)) {
                //apply to range, this will select the text
                searchResultApplier.applyToRange(range);
                // Collapse the range to the position immediately after the match
                range.collapse(false);
            }
            
            if(contentDiv){
                //set the hidden divs back to visible
                for (i = 0; i < arrLength; i++){
                    node = divNodesToHide[i];
                    node.classList.remove(me.CSS_CLASSNAME_HIDE_ELEMENT);
                }
            }

            //focus the search trigger field
            me.searchFieldTrigger && me.searchFieldTrigger.focus();
        }
    },
    
    /***
     * Find the segment in the editor, and open it for editing.
     */
    findEditorSegment:function(plug){
        var me=this,
            grid = plug.getSegmentGrid(),
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            activeTabViewModel=activeTab.getViewModel(),
            results=activeTabViewModel.get('result'),
            saveCurrentOpen=activeTab.down('#saveCurrentOpen').checked,
            searchTopChekbox=activeTab.down('#searchTopChekbox').checked,
            gridView=grid.getView(),
            indexBoundaries=me.getVisibleRowIndexBoundaries(grid),
            goToIndex=null,
            goToIndexEdited=null,
            tmpRowNumber=null,
            inVisibleAreaFound=false;

        if(results.length < 1){
            Editor.MessageBox.addInfo(me.strings.noSearchResults);
            return;
        }
        
        //check if the track changes are active, and set the internal flag
        me.isActiveTrackChanges();

        //check if all search segment parametars are 0(this is the initial state of the search)
        isSearchStart=function(){
            return me.activeSegment.matchIndex===0 &&
            me.activeSegment.nextSegmentIndex===0 &&
            me.activeSegment.currentSegmentIndex===0 &&
            me.activeSegment.matchCount===0;
        };

        //if it is a new search, find the first/last visible rows, and find the current edited segment index in the search results
        if(isSearchStart()){
            //find the record from the results, located between the visible index area
            for(var index=0;index<results.length;index++){
                
                //if the segment is edited, check if this record also exist in the search results
                //this record is with highest priority
                if(grid.editingPlugin.context){
                    goToIndexEdited=me.getSegmentEditedRowNumber(grid.editingPlugin.context.record,results[index]);
                }

                //since this state is with highest prio, stop with the loop
                if(goToIndexEdited!=null && goToIndexEdited>=0){
                    me.activeSegment.nextSegmentIndex=index;
                    break;
                }

                tmpRowNumber=me.getSegmentRowNumber(results[index]);
                //if the hit row is in the range of the visible columns in the grid
                if(tmpRowNumber>=indexBoundaries.top && tmpRowNumber<=indexBoundaries.bottom){
                    if(!inVisibleAreaFound){
                        goToIndex=tmpRowNumber;
                        //find the direction of the next segment
                        me.activeSegment.nextSegmentIndex=index;
                        inVisibleAreaFound=true;
                    }
                }
            }
        }

        if(grid.editingPlugin.context && goToIndexEdited!=null && goToIndexEdited>=0){
            goToIndex=goToIndexEdited;
        }
        
        //go to segment and open it for editing
        callback=function(indexToGo){
            me.removeReplaceClass();
            me.goToSegment(indexToGo,plug,saveCurrentOpen,activeTabViewModel);
            me.activeSegment.currentSegmentIndex=me.activeSegment.nextSegmentIndex;
            //update the segment indexes
            me.updateSegmentIndex(searchTopChekbox);
        };
        
        //if no index is found, use the initial one -> 0
        if(goToIndex===null){
            //if the search top is checked before the search is triggered, it will be in value of -1, which means the last record
            //in that case call updatesegmentindex so we get the right index
            if(me.activeSegment.nextSegmentIndex<0 && results.length>0){
                me.updateSegmentIndex(searchTopChekbox);
            }
            goToIndex=me.getSegmentRowNumber(results[me.activeSegment.nextSegmentIndex]);
        }

        //if no index, try to find it
        if(goToIndex>=0){
            callback(goToIndex);
        }else{
            me.searchIndex(results[me.activeSegment.nextSegmentIndex].segmentNrInTask,callback);
        }
    },

    /***
     * Scroll the segment and open it for editing
     */
    goToSegment:function(goToIndex,plug,saveCurrentOpen,activeTabViewModel){
        var me=this,
            grid = plug.getSegmentGrid(),
            selModel=grid.getSelectionModel(),
            ed=grid.editingPlugin,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            replaceCombo=activeTab.down('#searchInCombo'),
            selectedColumnDataIndex=replaceCombo.getSelection().get('id');

        callback = function() {
            grid.selectOrFocus(goToIndex);
            sel = selModel.getSelection();

            if(saveCurrentOpen===false && ed.editing){
                ed.cancelEdit();
            }
            
            if(me.isContentEditableField()){
                var theColum=grid.query('gridcolumn[dataIndex="'+selectedColumnDataIndex+'"]'),
                    editableColumn=null;
                
                if(theColum.length>0){
                    editableColumn=theColum[0];
                }
                
                ed.startEdit(sel[0], editableColumn, ed.self.STARTEDIT_SCROLLUNDER);
                
                //clean the mark tags from the editor
                me.cleanMarkTags();
                //find matches 
                me.findMatchesDelay();
            }else{
                var visibleColumns=grid.query('gridcolumn:not([hidden])'),
                    cellIndex=0;

                //find the index of the searched column
                for(var i=0;i<visibleColumns.length;i++){
                    if(visibleColumns[i].dataIndex===selectedColumnDataIndex){
                        cellIndex=i;
                        break;
                    }
                }
                //get searched cell in the selected row
                var gridCell=grid.getView().getCell(sel[0], cellIndex);
                me.findMatchesGrid(gridCell);
            }
        };
        
        grid.scrollTo(goToIndex, {
            callback: callback,
            notScrollCallback: callback
        });
    },
    
    /***
     * Find a index for given segment number in task, if the index is found the callback is called
     */
    searchIndex:function(segmentNrInTask,callback){
        var me=this,
            segmentGrid = me.getSegmentGrid(),
            segmentStore=segmentGrid.editingPlugin.grid.store,
            proxy = segmentStore.getProxy(),
            params = {};
        
        params[proxy.getFilterParam()] = proxy.encodeFilters(segmentStore.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(segmentStore.getSorters().items);
        Ext.Ajax.request({
            url: Editor.data.restpath+'segment/'+segmentNrInTask+'/position',
            method: 'GET',
            params: params,
            scope: me,
            success: function(response){
                var responseData = JSON.parse(response.responseText);
                if(!responseData){
                    return;
                }
                var segmentIndex =responseData.index,
                    segmentNrInTask = responseData.segmentNrInTask;
                //if no index is found, display info message
                if(!segmentIndex || segmentIndex<0){
                    Editor.MessageBox.addInfo(me.strings.noIndexFound);
                    return;
                }
                
                callback(segmentIndex);
            },
            failure: function(response){
                if(response.status===404 && (response.statusText ==="Nicht gefunden!" || response.statusText ==="Not Found")){
                    Editor.MessageBox.addInfo(me.strings.noIndexFound);
                    return;
                }
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /***
     * Get the segment iframe
     */
    getSegmentIframeDocument:function(){
        var me=this,
            plug=Editor.app.getController('Editor'),
            editor = plug.getEditPlugin().editor,
            iframeDocument=null;
        try {
            iframeDocument = editor.mainEditor.iframeEl.dom.contentDocument || editor.mainEditor.iframeEl.getWin().document;
        } catch(error) {
            return false;
        }
        return iframeDocument;
    },
    
    /***
     * Is new search is required
     */
    isSearchRequired:function(){
        return this.searchRequired;
    },
    
    /***
     * Calculate and update the next segment index
     */
    updateSegmentIndex:function(checked){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            activeTabViewModel=activeTab.getViewModel(),
            results=activeTabViewModel.get('result');
        
        //recalculate the next index
        if(checked){
            //set one position back
            me.activeSegment.nextSegmentIndex=me.activeSegment.currentSegmentIndex-1;
        }else{
            //set one position to front (we assume that )
            me.activeSegment.nextSegmentIndex=me.activeSegment.currentSegmentIndex+1;
        }
        
        //check if the index is out of the result boundaries
        if(me.activeSegment.nextSegmentIndex >= results.length){
            me.activeSegment.nextSegmentIndex=0;
        }
        
        if(me.activeSegment.nextSegmentIndex < 0){
            me.activeSegment.nextSegmentIndex=results.length-1;
        }
    },
    
    /***
     * Get segment row index from the segment store
     */
    getSegmentRowNumber:function(record){
        var grid=this.getSegmentGrid(),
            store=grid.store,
            newRecord=store.findRecord('id',record.id),
            index=grid.store.indexOf(newRecord);
        return index;
    },


    /***
     * Check if the current edited segment is in the search results
     */
    getSegmentEditedRowNumber:function(segmentRecord,record){
        return segmentRecord.id==record.id ? this.getSegmentRowNumber(record) : null;
    },
    
    /***
     * Check if the current selected field is content editable
     */
    isContentEditableField:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
        searchInCombo=activeTab.down('#searchInCombo'),
        searchInComboSelectedVal=searchInCombo.getValue();
        return Ext.Array.contains(this.replaceFields,searchInComboSelectedVal);
    },

    /**
     * Destroy the search window, create new if needed
     */
    destroySearchWindow:function(createNew){
        //remove all exisiting search windows
        var searchWindow=Ext.ComponentQuery.query('#searchreplacewindow');
        if(searchWindow.length>0){
            for(var i=0;i<searchWindow.length;i++){
                searchWindow[i].destroy();
            }
            createNew && this.handleSearchReplaceHotkey(null);
        }
    },
    
    /***
     * Return visible row indexes in segment grid
     * @param {Object} segment grid
     * @returns {Object} { top:topIndex, bottom:bottomIndex }
     */
    getVisibleRowIndexBoundaries:function(grid){
        var view=grid.getView(),
            vTop = view.el.getTop(),
            vBottom = view.el.getBottom(),
            top=-1, bottom=-1;


        Ext.each(view.getNodes(), function (node) {
            if (top<0 && Ext.fly(node).getBottom() > vTop) {
                top=view.indexOf(node);
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
     * Return the search term for given type
     */
    handleSearchType:function(searchTerm,searchType,matchCase){
        var me=this;
        
        if (searchType==="regularExpressionSearch" ) {
            searchTerm = new RegExp(searchTerm,"g"+(!matchCase ? 'i':''));   
        }
        
        if (searchType==="wildcardsSearch" ) {
            
            function globStringToRegex(str) {
                return new RegExp(preg_quote(str).replace(/\\\*/g, '.*').replace(/\\\?/g, '.'), 'gi');
            }
            function preg_quote (str, delimiter) {
                return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
            }
            searchTerm=globStringToRegex(searchTerm);
        }
        
        return searchTerm;
    },

    /***
     * Move to the next segment if needed.
     * Returns true if the segment is saved or new segment selection is needed
     * Return false if next segment selection is not needed 
     */
    jumpToNextSegment:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            saveCurrentOpen=activeTab.down('#saveCurrentOpen').checked,
            editor=Editor.app.getController('Editor');

        if(me.activeSegment.matchCount === 0 || me.activeSegment.matchIndex > me.activeSegment.matchCount-1){
                
            //if there is only one match, and the save current open is active -> save the segment
            if(saveCurrentOpen && me.activeSegment.currentSegmentIndex===me.activeSegment.nextSegmentIndex){
                editor.save();
            }else{
                me.setTrackChangesInternalFlag(false);
                me.handleRowSelection();
            }
            //TODO: make the if nest better
            return true;
        }
        return false;
    },

    /**
     * Check if the trackchanges are active. The function will also initialize the 
     * activeTrackChanges internal flag
     */
    isActiveTrackChanges:function(){
        //check if the trackchanges are active
        if(!Editor.plugins.TrackChanges){
            this.activeTrackChanges=false;
        }else{
            this.activeTrackChanges=!(Editor.data.task.get('workflowStepName')  == 'translation'
                                    && Editor.data.task.get('workflowStep') =='1');
        }
        return this.activeTrackChanges;
    }
    
});
    