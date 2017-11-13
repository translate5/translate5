
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
        'Editor.view.searchandreplace.SearchReplaceWindow',
        'Editor.controller.searchandreplace.SearchSegment'
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
                click:'onSearchButtonClick'
            },
            '#searchreplacetabpanel #replaceButton':{
                click:'onReplaceButtonClick'
            },
            '#searchreplacetabpanel #replaceAllButton':{
                click:'onReplaceAllButtonClick'
            },
            '#roweditor':{
                show:'onRowEditorShow'
            },
            '#searchTopChekbox':{
                change:'onSearchTopChange'
            }
        },
        controller:{
            '#Editor': {
                beforeKeyMapUsage: 'handleEditorKeyMapUsage',
            },
            '#Editor.$application': {
                editorViewportClosed: 'onEditorViewportClosed'
            },
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

    searchFields:[],
    replaceFields:[],
    activeColumnDataIndex:'',
    DEFAULT_COLUMN_DATA_INDEX:'targetEdit',
    

    /***
     * Flag for if the search or replace is clicked(so we know if we only move the index, or we also replace the currently active value)
     */
    isSearchPressed:true,
    
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
        matchCount:0
    },
    
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
        searchAndReplaceMenuItem:'#UT#Suchen und ersetzen',
        noIndexInfoMessage:'#UT#'
    },
    
    
    initConfig:function(){
        this.callParent(arguments);
        this.resetActiveColumnDataIndex();
    },
    
    /***
     * when the editor is close
     */
    onEditorViewportClosed:function(){
        this.destroySearchWindow();
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
        this.activeSegment.matchIndex=0;
        this.activeSegment.matchCount=0;
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
     * Handler for search button
     */
    onSearchButtonClick:function(button){
        var me=this;
        me.isSearchPressed = true;
        if(me.isSearchRequired()){
            me.search();
            return;
        }
        me.handleRowSelection();
    },
    
    /***
     * Handler for replace all
     */
    onReplaceButtonClick:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            vm=activeTab.getViewModel(),
            result=vm.get('result');
        
        me.isSearchPressed = false;
        if(result.length>0){
            me.handleRowSelection();
        }
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
     * Delay the text selection in the iframe (the iframe is not initialized)
     */
    onRowEditorShow:function(){
        var me=this;
        if(!me.getSearchReplaceWindow()){
            return;
        }
        //delay so the roweditor is loaded
        var task = new Ext.util.DelayedTask(function(){
            me.selectOrReplaceText();
        });
        task.delay(100);
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
    
    replaceAll:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            activeTabViewModel=activeTab.getViewModel(),
            form=activeTab.getForm(),
            params = {};
        
        params['taskGuid']=Editor.data.task.get('taskGuid');
        params['result']=JSON.stringify(activeTabViewModel.get('result'));
        
        form.submit({
            url: Editor.data.restpath+'segment/replaceall',
            params:params,
            method:'GET',
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
        
        if(me.activeSegment.matchIndex >= me.activeSegment.matchCount) {
            me.activeSegment.matchIndex=0;
            me.findEditorSegment(plug);
            return;
        }
        me.selectOrReplaceText();
        //FIXME find first in grid (the function from Thomas)
        //so the starting point is definded
    },
    
    //TODO replace text
    selectOrReplaceText:function(){
        var me=this,
            iframeDocument = me.getSegmentIframeDocument(),
            count = 0,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            searchCombo=activeTab.down('#searchCombo'),
            searchInCombo=activeTab.down('#searchInCombo'),
            searchComboRawValue=searchCombo.getRawValue(),
            replaceCombo=activeTab.down('#replaceCombo'),
            searchType=activeTab.down('radiofield').getGroupValue(),
            searchValue ='(?!<.*?)(?![^<>]*?>)'+searchComboRawValue,///<\/?[^>]+(>|$)/g+(searchCombo.getRawValue());
            searchRegExp=null,
            caseSensitive=true;//FIXME fix the case sensetive

        if(!iframeDocument){
            return;
        }
        
        if(searchComboRawValue===null || searchComboRawValue===""){
            return;
        }
        
        //if we are searchin in non editable field, do not select in the iframe
        if(!me.isContentEditableField(searchInCombo.value)){
            return;
        }
        
        searchRegExp = new RegExp(searchValue, 'g' + (caseSensitive ? '' : 'i'));

        //me.store.each(function(record, idx) {
        var cell, matches, cellHTML,
            cell = Ext.get(iframeDocument.body);

            //var searchClass=Ext.create('Editor.controller.searchandreplace.SearchSegment');
            //searchClass.search(cell.dom.innerHTML,searchComboRawValue);
            //return;
            
            //matches = cell.dom.innerHTML.match(me.tagsRe);
            //cellHTML = cell.dom.innerHTML.replace(me.tagsRe, me.tagsProtect);
        
            //clear the html tags from the string
            if(!me.isSearchPressed){
                cellHTML = cell.dom.innerHTML.replace(/<mark[^>]*>+|<\/mark>/g, "");
            }else{
                cellHTML = cell.dom.innerHTML;
            }
            //cellHTML = cell.dom.innerHTML.replace(/<\/?[^>]+(>|$)/g, "");
            
            matches = cellHTML.match(searchRegExp);
        
            if(!matches){
                return;
            }

            me.activeSegment.matchCount=matches.length;
            // populate indexes array, set matchIndex, and replace wrap matched string in a span
            cellHTML = cellHTML.replace(searchRegExp, function(m) {
                //if (me.activeSegment.matchIndex === null) {
                //    me.activeSegment.matchIndex = 1;
                //}
                //if(me.activeSegment.matchIndex === count){
                if(!me.isSearchPressed){
                    me.activeSegment.matchIndex = me.activeSegment.matchIndex-1;
                    me.activeSegment.matchIndex = Math.max(me.activeSegment.matchIndex,0);
                    if(me.activeSegment.matchIndex === count){
                        count++;
                        return replaceCombo.getRawValue();
                    }
                }
                if(me.activeSegment.matchIndex===count){
                    count++;
                    return '<mark style="background-color:red;">' + m + '</mark>';
                }
                count++;
                return '<mark>' + m + '</mark>';
               //return '<span style="background-color:yellow;">' + m + '</span>';
               //return '<mark>' + m + '</mark>';
            });
            
            if(matches.length > 0){
                me.activeSegment.matchIndex++;
            }
            cell.dom.innerHTML = cellHTML;
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
        console.log(me.activeSegment);
        
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
            me.goToSegment(indexToGo,plug,saveCurrentOpen,activeTabViewModel);
            me.activeSegment.currentSegmentIndex=me.activeSegment.nextSegmentIndex;
            //update the segment indexes
            me.updateSegmentIndex(searchTopChekbox);
        };
        
        //if no index is found, use the initial one -> 0
        if(goToIndex===null){
            //goToIndex=parseInt(results[me.activeSegment.nextSegmentIndex].row_number);
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
            ed=grid.editingPlugin;

        callback = function() {
            grid.selectOrFocus(goToIndex);
            sel = selModel.getSelection();

            if(saveCurrentOpen===false && ed.editing){
                ed.cancelEdit();
            }
            
            ed.startEdit(sel[0], null, ed.self.STARTEDIT_SCROLLUNDER);
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
            iframeDocument = editor.mainEditor.iframeEl.dom.contentDocument || iFrame.getWin().document;
        } catch(error) {
            return false;
        }
        return iframeDocument;
    },
    
    /***
     * Calculates if the search is required.
     * Search is required when, the current result is empty and jump to new segment is required
     */
    isSearchRequired:function(){
        var me=this,
            switchSegment=me.activeSegment.matchIndex >= me.activeSegment.matchCount;
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            vm=activeTab.getViewModel(),
            result=vm.get('result'),
            resultLength=result.length;
        
        return switchSegment && resultLength<1;
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
    isContentEditableField:function(currentDataIndex){
        return Ext.Array.contains(this.replaceFields,currentDataIndex);
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
    
    escapeRegExp:function(str) {
        return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
    },

    //FIXME this works but somehow destroys the content inside the segment!!
    testSearch:function(text){
        var testWindow=Ext.getWin().dom,
            testDocument=testWindow.document;
        
        if (testWindow.find && testWindow.getSelection) {
            testDocument.designMode = "on";
            var sel = testWindow.getSelection();
            sel.collapse(testDocument.body, 0);

            while (testWindow.find(text)) {
                testDocument.execCommand("HiliteColor", false, "yellow");
                sel.collapseToEnd();
            }
            testDocument.designMode = "off";
        } else if (testDocument.body.createTextRange) {
            var textRange = testDocument.body.createTextRange();
            while (textRange.findText(text)) {
                textRange.execCommand("BackColor", false, "yellow");
                textRange.collapse(false);
            }
        }
    },

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
});
    