
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
    
    requires:['Editor.view.searchandreplace.SearchReplaceWindow'],
    listen:{
        component:{
            '#segmentgrid':{
                afterrender:'onSegmentGridAfterRender',
                filterchange:'onSegmentGridFilterChange',
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
    }],

    searchFields:[],
    replaceFields:[],
    activeColumnDataIndex:'',
    DEFAULT_COLUMN_DATA_INDEX:'targetEdit',
    
    
    currentIndex:null,
    isSearchPressed:true,
    
    activeSegment:{
        matchIndex:0,
        segmentResultIndex:0,
        matchCount:0
    },
    
    strings:{
        searchInfoMessage:'#UT#The search will be performed only on the filtered segments',
        comboFieldLabel:'#UT#Replace',
        noSearchResults:'#UT#No results for the current search!',
    },
    
    activeSelection:{
        segmentIndex:-1,
        indexInSegment:-1,
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
        if(filters.length>0){
            Editor.MessageBox.addInfo(this.strings.searchInfoMessage);
        }
    },
    
    onColumnVisibilityChange:function(){
        this.initColumnArrays();
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
            xtype:'combo',
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
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            vm=activeTab.getViewModel(),
            result=vm.get('result');
        
        debugger;
        me.isSearchPressed = true;
        if(result.length>0){
            //if edited segment select in it all finded results
            //if not edited open first find, and select the matches found
            
            //save the current position 
            me.handleRowSelection();
            return;
        }
        me.search();
    },
    
    onReplaceButtonClick:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
            vm=activeTab.getViewModel(),
            result=vm.get('result');
        
        me.isSearchPressed = false;
        if(result.length>0){
            //reset the current search index, so we start to replace from the first match
            //me.activeSegment.matchIndex=null;
            //me.activeSegment.segmentId=null;
            me.handleRowSelection();
        }
    },
    
    onReplaceAllButtonClick:function(){
        
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
        debugger;
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
            storeData=[],
            searchInCombos=Ext.ComponentQuery.query('#searchInCombo');
        
        Ext.Array.each(columns, function(rec) {
            storeData.push({'id':rec.dataIndex , 'value':rec.text.replace(/<(?:.|\n)*?>/gm, '')});
        });
        
        Ext.Array.each(searchInCombos,function(combo){
            combo.setStore(Ext.create('Ext.data.Store', {
                fields: ['id', 'value'],
                data:storeData
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
    
    search:function(){
        var me=this,
            tabPanel=me.getTabPanel(),
            activeTab=tabPanel.getActiveTab(),
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
                    activeTabViewModel=activeTab.getViewModel(),
                    tabPanelviewModel=tabPanel.getViewModel();

                tabPanelviewModel.set('searchPerformed',foundSegments.length > 0);
                activeTabViewModel.set('resultsCount',foundSegments.length);
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
            replaceCombo=activeTab.down('#replaceCombo'),
            searchValue ='(?!<.*?)(?![^<>]*?>)'+searchCombo.getRawValue(),///<\/?[^>]+(>|$)/g+(searchCombo.getRawValue());
            searchRegExp=null,
            caseSensitive=true;
        
        if(!iframeDocument){
            return;
        }

        if(searchCombo.getRawValue()===null || searchCombo.getRawValue()===""){
            return;
        }
            
        searchRegExp = new RegExp(searchValue, 'g' + (caseSensitive ? '' : 'i'));

        //me.store.each(function(record, idx) {
        var cell, matches, cellHTML,
            cell = Ext.get(iframeDocument.body);
        
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
        
            debugger;
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
            gridView=grid.getView(),
            firstVisibleIndex=gridView.getFirstVisibleRowIndex(),
            goToIndex=null,
            tmpRowNumber=null;
        
        if(results.length < 1){
            Editor.MessageBox.addInfo(me.strings.noSearchResults);
            return;
        }

        if(!plug.isEditing){
            for(var i=0;i<results.length;i++){
                //subtract one because this is a row number but we need the index
                tmpRowNumber=parseInt(results[i].row_number);
                if(tmpRowNumber===firstVisibleIndex){
                    goToIndex=firstVisibleIndex;
                    me.activeSegment.segmentResultIndex=i+1;
                    break;
                }
            }
        }
        
        if(goToIndex===null){
            goToIndex=parseInt(results[me.activeSegment.segmentResultIndex].row_number);
            me.activeSegment.segmentResultIndex++;
        }

        if(me.activeSegment.segmentResultIndex >= results.length){
            me.activeSegment.segmentResultIndex=0;
        }
        
        me.goToSegment(goToIndex,plug);
    },

    goToSegment:function(goToIndex,plug){
        var me=this,
            grid = plug.getSegmentGrid(),
            selModel=grid.getSelectionModel(),
            ed=grid.editingPlugin;

        callback = function() {
            grid.selectOrFocus(goToIndex);
            sel = selModel.getSelection();
            ed.startEdit(sel[0], null, ed.self.STARTEDIT_SCROLLUNDER);
            //delay the text selection because the dom is not initialized
            new Ext.util.DelayedTask(function(){
                me.selectOrReplaceText();
            }).delay(100);
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

    /*FIXME this works but somehow destroys the content inside the segment!!
    testSearch:function(testWindow,testDocument,text){
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
    */
});
    