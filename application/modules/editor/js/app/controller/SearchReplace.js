
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
                columnhide:'onColumnVisibilityChange'
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
                beforeKeyMapUsage: 'handleEditorKeyMapUsage'
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

    searchFields:[],
    replaceFields:[],
    activeColumnDataIndex:'',
    DEFAULT_COLUMN_DATA_INDEX:'targetEdit',
    
    
    currentIndex:null,
    isSearchPressed:true,
    
    /***
     * When the filter is active and the user open the search/replace window, we display the info message that
     * the search/replace will be performed only on filtered segments
     */
    isFilterActive:false,
    
    activeSegment:{
        matchIndex:0,
        nextSegmentIndex:0,
        currentSegmentIndex:0,
        matchCount:0
    },
    
    strings:{
        searchInfoMessage:'#UT#The search will be performed only on the filtered segments',
        comboFieldLabel:'#UT#Replace',
        noSearchResults:'#UT#No results for the current search!',
        replaceAllWindowBtnText:'#UT#Alle Ergebnisse ersetzen',
        cancelReplaceAllWindowBtnText:'#UT#Abbrechen',
        replaceAllWindowTitle:'#UT#Ergebnisse ersetzen',
        replaceAllWindowMessage:'#UT#matches found. Do you really want to replace all of them? All found segments will be changed including auto-status and last editor',
        characterLimitError:'#UT#Der Suchstring ist zu groß'
    },
    
    
    initConfig:function(){
        this.callParent(arguments);
        this.resetActiveColumnDataIndex();
    },
    
    /***
     * On segment grid after render handler
     * Here we add the search and replace menu item on the needed column
     */
    onSegmentGridAfterRender:function(segmentGrid){
        var me=this,
            menu = segmentGrid.headerCt.getMenu();
        
        me.addSearchReplaceMenu(menu);
        
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
        
        me.initColumnArrays();
    },
    
    onSegmentGridFilterChange:function(store,filters,eOpts){
        this.isFilterActive=filters.length>0;
    },
    
    onSegmentGridSortChange:function(ct,column,direction,eOpts){
        var searchWindow=Ext.ComponentQuery.query('#searchreplacewindow');

        if(searchWindow.length>0){
            for(var i=0;i<searchWindow.length;i++){
                searchWindow[i].destroy();
            }
            this.handleSearchReplaceHotkey(null);
        }
    },
    
    onColumnVisibilityChange:function(){
        this.initColumnArrays();
    },
    
    onSearchTopChange:function(checkbox,newValue,oldValue,eOpts){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            activeTabViewModel=activeTab.getViewModel(),
            results=activeTabViewModel.get('result');
        
        //recalculate the next index
        if(newValue){
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
    
    handleEditorKeyMapUsage: function(cont, area, mapOverwrite) {
        var me = this;
        cont.keyMapConfig['ctrl-f'] = ['f',{ctrl: true, alt: false},me.handleSearchReplaceHotkey, true];
        cont.keyMapConfig['ctrl-h'] = ['h',{ctrl: true, alt: false},me.handleSearchReplaceHotkey, true];
    },
    
    addSearchReplaceMenu:function(gridMenu){
        // add menu item  into the menu and store its reference
        var me=this,
            menuItem = gridMenu.add({
                itemId:'searchReplaceMenu',
                text: 'Search and replace window',
                iconCls:'x-fa fa-search',
                scope:me,
                handler:me.showSearchAndReplaceWindow
            });
    },
    
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
    
    onSegmentGridMenuHide:function(){
        //reset the current active column data index
        this.resetActiveColumnDataIndex();
    },
    
    resetActiveColumnDataIndex:function(){
        var me=this;
        me.activeColumnDataIndex =me.DEFAULT_COLUMN_DATA_INDEX;
    },
    
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
    
    onSearchReplaceWindowDestroy:function(){
        this.resetActiveColumnDataIndex();
    },
    
    onSearchButtonClick:function(button){
        
        /*
        //START OF NEW TEST
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            searchCombo=activeTab.down('#searchCombo'),
            searchComboRawValue=searchCombo.getRawValue();
        
        
        var searchClass=Ext.create('Editor.controller.searchandreplace.SearchSegment');
        searchClass.search(searchComboRawValue);
        //END OF NEW TEST
        return;
        */
        
        
        var me=this,
            nextSegmentNr=me.isSearchRequired();
        
        me.isSearchPressed = true;

        if(nextSegmentNr>=0){
            me.search(nextSegmentNr);
            return;
        }
        me.handleRowSelection();
    },
    
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
    
    handleSearchReplaceHotkey:function(key){
        var me=Editor.app.getController('SearchReplace'),
            segmentGrid=me.getSegmentGrid();
        if(!segmentGrid || !segmentGrid.isVisible()) {
            return;
        }
        
        if(me.isFilterActive){
            Editor.MessageBox.addInfo(me.strings.searchInfoMessage);
        }

        me.showSearchAndReplaceWindow(key);
    },
    
    handleSerchReplaceMenu:function(){
        var me=this,
            searchReplaceWindow=Ext.widget('searchreplacewindow'),
            searchInCombo=searchReplaceWindow.down('#searchInCombo');
        searchReplaceWindow.show();
    },
    
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
    
    initColumnArrays:function(){
        var me=this,
            segmentGrid=me.getSegmentGrid();
        me.searchFields=[];
        me.replaceFields=[];
        me.searchFields=me.getColumnDataIndex(segmentGrid.query('gridcolumn[isContentColumn]:not([hidden])'));
        me.replaceFields=me.getColumnDataIndex(segmentGrid.query('gridcolumn[isEditableContentColumn]:not([hidden])'));
    },
    
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
    
    search:function(segmentNrInTask){
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
        params['segmentNrInTask']=segmentNrInTask;
        params['searchOffset']=me.calculateSearchOffset();

        form.submit({
            url: Editor.data.restpath+'segment/search',
            params:params,
            method:'GET',
            success: function(form, submit){
                if(!submit.result || !submit.result.rows){
                    return;
                }
                var foundSegments = submit.result.rows,
                    tabPanelviewModel=tabPanel.getViewModel();

                tabPanelviewModel.set('searchPerformed',foundSegments.length > 0);
                activeTabViewModel.set('resultsCount',foundSegments.length);
                if(submit.result.resultsCountNoOffset){
                    activeTabViewModel.set('resultsCountNoOffset',submit.result.resultsCountNoOffset);
                }
                activeTabViewModel.set('result',foundSegments.length >0 ? foundSegments : [] );
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
    
    //FIXME refactor this function!!!!!!!!!
    //the images should not be removed from the text, only the mark tags
    //FIXME fix the regular expression
    selectOrReplaceText:function(){
        var me=this,
            iframeDocument = me.getSegmentIframeDocument(),
            count = 0,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            searchCombo=activeTab.down('#searchCombo'),
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
            firstVisibleIndex=gridView.getFirstVisibleRowIndex(),
            lastVisibleIndex=gridView.getLastVisibleRowIndex(),
            goToIndex=null,
            tmpRowNumber=null;

        if(results.length < 1){
            Editor.MessageBox.addInfo(me.strings.noSearchResults);
            return;
        }
        var startIndex= searchTopChekbox ? results.length : 0;
        
        if(!plug.isEditing){
            
            for(var index=startIndex;index<results.length;){
                
                //subtract one because this is a row number but we need the index
                //tmpRowNumber=parseInt(results[index].row_number);
                tmpRowNumber=me.getSegmentRowNumber(grid,results[index]);
                //if the hit row is in the range of the visible columns in the grid
                if(tmpRowNumber>=firstVisibleIndex && tmpRowNumber<=lastVisibleIndex){
                    goToIndex=tmpRowNumber;
                    
                    //find the direction of the next segment
                    if(searchTopChekbox){
                        me.activeSegment.nextSegmentIndex=index;
                        index--;
                    }else{
                        me.activeSegment.nextSegmentIndex=index+1;
                        index++;
                    }
                    me.activeSegment.currentSegmentIndex=me.activeSegment.nextSegmentIndex;
                    break;
                }
                
                searchTopChekbox ? index-- : index++;
            }
        }
        //if no index is found, use the initial one -> 0
        if(goToIndex===null){
            //goToIndex=parseInt(results[me.activeSegment.nextSegmentIndex].row_number);
            goToIndex=me.getSegmentRowNumber(grid,results[me.activeSegment.nextSegmentIndex]);
            //save the current segment index
            me.activeSegment.currentSegmentIndex=me.activeSegment.nextSegmentIndex;
            
            //set the next index
            searchTopChekbox ? me.activeSegment.nextSegmentIndex-- : me.activeSegment.nextSegmentIndex++;
        }

        if(me.activeSegment.nextSegmentIndex >= results.length){
            me.activeSegment.nextSegmentIndex=0;
        }
        
        if(me.activeSegment.nextSegmentIndex < 0){
            me.activeSegment.nextSegmentIndex=results.length-1;
        }
        
        me.goToSegment(goToIndex,plug,saveCurrentOpen,activeTabViewModel);
    },

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
    
    isSearchRequired:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            vm=activeTab.getViewModel(),
            result=vm.get('result'),
            resultsCountNoOffset=vm.get('resultsCountNoOffset');
        
        if(result.length < 1){
            return 0;
        }
        if(result.length>0 && result.length<=resultsCountNoOffset){
            return -1;
        }
        if(me.activeSegment.matchIndex >= me.activeSegment.matchCount) {
            return result[me.activeSegment.nextSegmentIndex].id;
        }
        return -1;
    },
    
    getSegmentRowNumber:function(grid,record){
        var store=grid.store,
            newRecord=store.findRecord('id',record.id),
            index=grid.store.indexOf(newRecord);
        return index;
    },
    
    getSearchOffset:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            activeTabViewModel=activeTab.getViewModel();
        return activeTabViewModel.get('searchOffset');
    },

    calculateSearchOffset:function(){
        debugger;
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            activeTabViewModel=activeTab.getViewModel(),
            searchTopChekbox=activeTab.down('#searchTopChekbox').checked,
            result=activeTabViewModel.get('result'),
            resultsCountNoOffset=activeTabViewModel.get('resultsCountNoOffset'),
            searchOffset=activeTabViewModel.get('searchOffset'),
            calcOffset=-1;

        if(resultsCountNoOffset<1){
            return calcOffset;
        }

        //if(searchTopChekbox){
        //    calcOffset=Math.min(Math.max(searchOffset - 50, 0), resultsCountNoOffset);
        //    activeTabViewModel.set('searchOffset',calcOffset)
        //    return calcOffset;
        //}
        calcOffset=Math.min(Math.max(searchOffset + 50, 0), resultsCountNoOffset);
        activeTabViewModel.set('searchOffset',calcOffset);
        return calcOffset;
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
});
    