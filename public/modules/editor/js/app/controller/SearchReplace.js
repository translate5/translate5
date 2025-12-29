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

Ext.define('Editor.controller.SearchReplace', {
    extend: 'Ext.app.Controller',

    requires: [
        'Editor.view.searchandreplace.SearchReplaceWindow'
    ],

    mixins: [
        'Editor.util.Range',
        'Editor.util.SearchReplaceUtils',
        'Editor.util.HtmlCleanup',
    ],

    listen: {
        component: {
            '#segmentgrid': {
                afterrender: 'onSegmentGridAfterRender',
                    filterchange: 'onSegmentGridFilterChange',
                    sortchange: 'onSegmentGridSortChange',
                    columnshow: 'onColumnVisibilityChange',
                    columnhide: 'onColumnVisibilityChange',
                    edit: 'onSegmentGridEdit',
                    canceledit: 'onSegmentGridCancelEdit',
            },
            '#searchreplacewindow': {
                show: 'onSearchReplaceWindowShow',
                destroy: 'onSearchReplaceWindowDestroy'
            },
            '#searchreplacetabpanel #searchButton': {
                click: 'triggerSearch'
            },
            '#searchreplacetabpanel #replaceButton': {
                click: 'onReplaceButtonClick'
            },
            '#searchreplacetabpanel #replaceAllButton': {
                click: 'onReplaceAllButtonClick'
            },
            '#searchTopChekbox': {
                change: 'onSearchTopChange'
            },
            '#searchField': {
                change: 'onSearchFieldChange',
                keyup: 'triggerSearchOnEnter',
                render: 'onSearchFieldRender'
            },
            '#searchInField': {
                keyup: 'triggerSearchOnEnter'
            },
            '#t5Editor': {
                afterInstantiateEditor: 'onEditorInstantiate',
            },
            'segmentsToolbar': {
                render: 'onSegmentsToolbarRender'
            }
        },
        controller: {
            '#Editor': {
                beforeKeyMapUsage: 'onBeforeEditorKeyMapUsage',
            },
            '#Editor.$application': {
                editorViewportClosed: 'onEditorViewportClosed',
                // editorViewportOpened:'onEditorViewportOpened'
            },
        }
    },

    refs: [
        {
            ref: 'segmentGrid',
            selector: '#segmentgrid'
        },
        {
            ref: 'tabPanel',
            selector: '#searchreplacetabpanel'
        },
        {
            ref: 'searchReplaceWindow',
            selector: '#searchreplacewindow'
        }
    ],

    /** Available fields in the grid for search */
    searchFields: [],

    /** Available fields in grid for replace */
    replaceFields: [],

    activeColumnDataIndex: '',

    defaultColumnDataIndex: 'targetEdit',

    /**
     * When the filter is active and the user open the search/replace window, we display the info message that
     * the search/replace will be performed only on filtered segments
     */
    isFilterActive: false,

    /**
     * Flag indicating whether searchreplacewindow was already opened at least once since the current task was opened
     * This is used to set up empty value for search field whe searchreplacewindow is opened for the first time
     * because when that window is opened 2nd and further times - previous value is set to search field in there
     */
    searchReplaceOpenedAtLeastOnce: false,

    /**
     * The segment information.
     * matchIndex -> the match index in the currently edited segment
     * nextSegmentIndex -> the index of the next segment which needs to be opened (index in the result array)
     * currentSegmentIndex -> currently active segment index (index in the result array)
     * matchCount -> number of matches in the currently edited segment
     */
    activeSegment: {
        matchIndex: 0,
        nextSegmentIndex: 0,
        currentSegmentIndex: 0,
        matchCount: 0,
        openedSegments: []
    },

    /** Pointer to the array index of replace ranges */
    replaceArrayPointer: 0,

    /** Serialized ranges for replacement */
    replaceRanges: [],

    /** Flag if the db search should be provided */
    searchRequired: true,

    /** Previous searched cell */
    oldSerchedCell: null,

    /** Flag so we know if the track changes are active or not */
    activeTrackChanges: true,

    /** @type {Editor.view.segments.new.EditorNew} */
    editor: null,

    /** Segment's Editor: HTMLBodyElement */
    editorBody: null,

    /** Segment's Editor: Ext.dom.Element */
    editorBodyExtDomElement: null,

    /** Segment search replace time tracking */
    timeTracking: null,

    /** The index of replaced segments on manual replace click */
    replacedSegmentsIndex: [],

    /** Required search parameters (must contain value) */
    requiredParams: ['searchInField', 'searchField', 'searchType'],

    strings: {
        searchInfoMessage: '#UT#Die Suche wird nur auf den gefilterten Segmenten durchgeführt',
        comboFieldLabel: '#UT#Ersetzen',
        noSearchResults: '#UT#Keine Ergebnisse für die aktuelle Suche!',
        replaceAllWindowBtnText: '#UT#Alle Ergebnisse ersetzen',
        cancelReplaceAllWindowBtnText: '#UT#Abbrechen',
        replaceAllWindowTitle: '#UT#Ergebnisse ersetzen',
        replaceAllWindowMessage: '#UT#übereinstimmungen gefunden. Wollen Sie wirklich alle ersetzen? Alle gefundenen Segmente werden inklusive Auto-Status und letztem Editor geändert',
        characterLimitError: '#UT#Der Suchstring ist zu groß',
        noIndexFound: '#UT#Das Segment ist in Ihrer aktuellen Filterung nicht enthalten.',
        searchAndReplaceMenuItem: '#UT#Suchen und ersetzen',
        replaceAllErrors: "#UT#Die automatischen Ersetzungen konnten nicht in allen gefundenen Segmenten durchgeführt werden. Dies kann unterschiedliche Ursachen haben. Bitte verwenden Sie Suche und Ersetzen ohne die \'Alles Ersetzen\' Funktionalität um die betroffenen Segmente einzeln zu finden und zu bearbeiten."
    },

    initConfig: function () {
        this.callParent(arguments);
        this.resetActiveColumnDataIndex();
    },

    /**
     * @param {Editor.view.segments.new.EditorNew} editor
     */
    onEditorInstantiate: function (editor) {
        this.editor = editor;

        // If retry flag was set - try findMatches once again
        if (this.retryFindMatchesOnEditorInstantiate) {
            this.findMatches();
            delete this.retryFindMatchesOnEditorInstantiate;
        }
    },

    /**
     * On segments toolbar render handler
     */
    onSegmentsToolbarRender: function (toolbar) {
        const me = this;
        let index = 7,
            segmentsToolbar = Ext.ComponentQuery.query('segmentsToolbar');

        //calculate the index of the search button
        if (segmentsToolbar.length > 0) {
            segmentsToolbar = segmentsToolbar[0];
            const watchListFilterBtn = segmentsToolbar.down('#watchListFilterBtn');

            if (watchListFilterBtn) {
                index = segmentsToolbar.items.indexOf(watchListFilterBtn);
            }
        }

        toolbar.insert(index, [{
            xtype: 'button',
            itemId: 'searchReplaceToolbarBtn',
            cls: 'searchReplaceToolbarBtn',
            icon: Editor.data.moduleFolder + 'images/magnifier.png',
            bind: {
                tooltip: '{l10n.segmentGrid.toolbar.searchAndReplaceButtonTooltip}'
            },
            handler: function () {
                me.showSearchAndReplaceWindow(null);
            }
        }, {
            xtype: 'tbseparator'
        }])
    },

    /**
     * when the editor is close
     */
    onEditorViewportClosed: function () {
        this.destroySearchWindow();
    },

    /**
     * On segment grid after render handler
     * Here we add the search and replace menu item on the needed column
     */
    onSegmentGridAfterRender: function (segmentGrid) {
        const me = this,
            menu = segmentGrid.headerCt.getMenu();

        //add the menu item to the grid menu
        me.addSearchReplaceMenu(menu);

        // Reset searchreplacewindow instantiation flag
        this.searchReplaceOpenedAtLeastOnce = false;

        //add menu handler, so we hide and show the search/replace menu item
        menu.on({
            beforeshow: {
                fn: me.onSegmentGridMenuBeforeShow,
                scope: me
            },
            hide: {
                fn: me.onSegmentGridMenuHide,
                scope: me
            },
        });

        //init the search/replace column index arrays
        me.initColumnArrays();
    },

    /**
     * On segment grid filter change handler.
     * Set the flag that the filter is active, so the info message is displayed
     */
    onSegmentGridFilterChange: function (store, filters, eOpts) {
        this.isFilterActive = filters.length > 0;
        this.destroySearchWindow(true);
    },

    /**
     * On segment grid sort change handler.
     * If the segment window is active, we destroy the window and open it again
     */
    onSegmentGridSortChange: function (ct, column, direction, eOpts) {
        this.destroySearchWindow(true);
    },

    /**
     * On segment grid edit handler.
     * Reset the active segment match index and match count
     */
    onSegmentGridEdit: function () {
        this.activeSegment.matchIndex = 0;
        this.activeSegment.matchCount = 0;
    },

    /**
     * On segment grid cancel edit handler.
     * Reset the active segment match index and match count
     */
    onSegmentGridCancelEdit: function () {
        this.activeSegment.matchIndex = 0;
        this.activeSegment.matchCount = 0;
    },

    /**
     * On segment grid column show/hide.
     * Init the search and replace field arrays
     */
    onColumnVisibilityChange: function () {
        this.initColumnArrays();
    },

    /**
     * On search top click handler.
     * Update the current active segment index.
     */
    onSearchTopChange: function (checkbox, newValue, oldValue, eOpts) {
        this.updateSegmentIndex(newValue);
    },

    onSearchFieldChange: function () {
        this.searchRequired = true;
    },

    /**
     * After the delete and replace is finished handler
     */
    onDeleteAndReplaceFinished: function () {
        const me = this;

        //clean the mark tags from the editor
        me.cleanMarkTags();

        //run the search once again
        me.findMatches();

        //check if the segment is already visited
        if (Ext.Array.contains(me.replacedSegmentsIndex, me.activeSegment.nextSegmentIndex)) {
            //reset the search parametars
            me.resetSearchParameters();
            //save the currently opened segment

            if(me.getTabPanel().getActiveTab().down('#saveCurrentDraft').checked){
                Editor.app.getController('Editor').saveDraft();
            } else {
                Editor.app.getController('Editor').save();
            }
            return;
        }

        //cache the next replace segment
        me.replacedSegmentsIndex.push(me.activeSegment.currentSegmentIndex);

        me.jumpToNextSegment();
    },

    /**
     * Add keymap for search and replace
     */
    onBeforeEditorKeyMapUsage: function (cont, area, mapOverwrite) {
        cont.keyMapConfig['ctrl-f'] = ['f', {ctrl: true, alt: false}, this.handleSearchReplaceHotkey, true];
        cont.keyMapConfig['ctrl-h'] = ['h', {ctrl: true, alt: false}, this.handleSearchReplaceHotkey, true];
    },

    /**
     * Add the search and replace meinu item
     */
    addSearchReplaceMenu: function (gridMenu) {
        // add menu item  into the menu and store its reference
        const me = this;
        gridMenu.add({
            itemId: 'searchReplaceMenu',
            text: me.strings.searchAndReplaceMenuItem,
            iconCls: 'x-fa fa-search',
            scope: me,
            handler: me.showSearchAndReplaceWindow
        });
    },

    /**
     * On segment grid menu before show.
     * Show or hide the search and replace window, based of if the column is searchable or not
     */
    onSegmentGridMenuBeforeShow: function (menu) {
        // get data index of column for which menu will be displayed
        const me = this,
            searchReplaceMenu = menu.down('#searchReplaceMenu'),
            currentDataIndex = menu.activeHeader.dataIndex;

        me.activeColumnDataIndex = currentDataIndex;

        // show/hide menu item in the menu
        if (Ext.Array.contains(me.searchFields, currentDataIndex)) {
            searchReplaceMenu.show();

            return;
        }

        searchReplaceMenu.hide();
    },

    /**
     * Segment grid menu hide hanlder
     */
    onSegmentGridMenuHide: function () {
        //reset the current active column data index
        this.resetActiveColumnDataIndex();
    },

    /**
     * Reset the default active column data index
     */
    resetActiveColumnDataIndex: function () {
        this.activeColumnDataIndex = null;
    },

    /**
     * Insert the replace combo in the replace tab
     */
    onSearchReplaceWindowShow: function (win) {
        const tabPanel = win.down('#searchreplacetabpanel'),
            replaceTab = tabPanel.down('#replaceTab'),
            activeTab = tabPanel.getActiveTab(),
            searchField = activeTab.down('#searchField'),
            searchInField = activeTab.down('#searchInField');

        replaceTab.insert(1, {
            xtype: 'textfield',
            itemId: 'replaceField',
            name: 'replaceField',
            focusable: true,
            fieldLabel: this.strings.comboFieldLabel,
        });

        searchField.focus();
        this.initSearchInFieldStore(searchInField);
    },

    /**
     * On search window destroy handler
     */
    onSearchReplaceWindowDestroy: function () {
        //reset the current active column data index
        this.resetActiveColumnDataIndex();
    },

    /**
     * Handler for search
     */
    triggerSearch: function (field, ev, eOpts) {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            isValid = activeTab.isValid();

        if (!isValid) {
            return;
        }

        // set the current field from where the search is triggered
        me.searchFieldTrigger = field;

        if (me.isSearchRequired()) {
            me.sendSearchRequest();

            return;
        }

        me.handleRowSelection();
    },

    /**
     * Search handler for field on enter pressed
     */
    triggerSearchOnEnter: function (field, ev, eOpts) {
        if (ev.getKey() === ev.ENTER) {
            this.triggerSearch(field, ev, eOpts);
        }
    },

    /**
     * Handler for replace all
     */
    onReplaceButtonClick: function (field) {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            replaceField = activeTab.down('#replaceField'),
            replaceText = replaceField.getRawValue(),
            editor = Editor.app.getController('Editor'),
            grid = editor.getSegmentGrid(),
            ed = grid.editingPlugin;

        if (!ed.editing || me.isSearchRequired()) {
            me.triggerSearch(field);

            return;
        }

        //set the field for focus
        me.searchFieldTrigger = field;

        //find matches once again, the content can be changed between replaces
        me.cleanMarkTags();
        me.findMatches();
        // me.setTrackChangesInternalFlag(true);

        if (me.jumpToNextSegment()) {
            return;
        }

        me.pureReplace(me.replaceRanges[me.activeSegment.matchIndex], replaceText);
        me.onDeleteAndReplaceFinished();
    },

    /**
     * On replace all button click handler
     */
    onReplaceAllButtonClick: function () {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            vm = activeTab.getViewModel(),
            result = vm.get('result');

        Ext.create('Ext.window.MessageBox').show({
            title: me.strings.replaceAllWindowTitle,
            msg: result.length + " " + me.strings.replaceAllWindowMessage,
            buttons: Ext.Msg.YESNO,
            fn: me.handleLeaveTaskButton,
            scope: me,
            defaultFocus: 'no',
            icon: Ext.MessageBox.QUESTION,
            buttonText: {
                yes: me.strings.replaceAllWindowBtnText,
                no: me.strings.cancelReplaceAllWindowBtnText
            }
        });
    },

    /**
     * Handler for the leave task dialog window.
     */
    handleLeaveTaskButton: function (button) {
        if (button === "cancel") {
            return false
        }

        if (button === "yes") {
            this.replaceAll();

            return true;
        }

        return false
    },


    /**
     * Show the search replace window based on if the hotkey is used
     */
    showSearchAndReplaceWindow: function (key) {
        const me = this;

        if (key instanceof Object) {
            const tmpMenu = key.up('menu');
            me.activeColumnDataIndex = tmpMenu.activeHeader.dataIndex;
            me.handleSearchReplaceMenu();

            return;
        }

        //if it is not from menu, check if the window is opened from edited segment, if yes select to search to field the current edited segment column
        //if only ctrl+f or ctrl+h is pressed and there

        const searchReplaceWindow = Ext.ComponentQuery.query('searchreplacewindow').pop()
            || Ext.widget('searchreplacewindow'),
            plug = Editor.app.getController('Editor').getEditPlugin();
        let focusTab = null;

        this.searchReplaceOpenedAtLeastOnce = true;

        if (plug.editor && plug.editor.editingPlugin.editing) {
            me.activeColumnDataIndex = plug.editor.columnClicked;
        }

        if (!key || (key === Ext.event.Event.F)) {
            focusTab = 'searchTab';

            if (!Ext.Array.contains(me.searchFields, me.activeColumnDataIndex)) {
                me.activeColumnDataIndex = me.defaultColumnDataIndex;
            }
        }

        if (key === Ext.event.Event.H) {
            if (Ext.Array.contains(me.replaceFields, me.activeColumnDataIndex)) {
                me.activeColumnDataIndex = me.defaultColumnDataIndex;
            }

            focusTab = 'replaceTab';
        }

        const tabPanel = searchReplaceWindow.down('#searchreplacetabpanel'),
            activeTab = tabPanel.down('#' + focusTab);

        tabPanel.setActiveTab(activeTab);
        tabPanel.getViewModel().set('searchView', focusTab === 'searchTab');
        searchReplaceWindow.show();
    },

    /**
     * Handler for search/replace window hotkey
     */
    handleSearchReplaceHotkey: function (key) {
        const me = Editor.app.getController('SearchReplace'),
            segmentGrid = me.getSegmentGrid();

        if (!segmentGrid || !segmentGrid.isVisible()) {
            return;
        }

        //if the filter is active, show the info message
        if (me.isFilterActive) {
            Editor.MessageBox.addInfo(me.strings.searchInfoMessage);
        }

        me.showSearchAndReplaceWindow(key);
    },

    /**
     * Show the search/replace window
     */
    handleSearchReplaceMenu: function () {
        Ext.widget('searchreplacewindow').show();
    },

    /**
     * Init the search and replace columns combos
     */
    initSearchInFieldStore: function () {
        const me = this,
            segmentGrid = Ext.ComponentQuery.query('#segmentgrid')[0],
            columns = segmentGrid.query('gridcolumn[isContentColumn]:not([hidden])'),
            searchStoreData = [],
            replaceStoreData = [],
            searchInFields = Ext.ComponentQuery.query('#searchInField');

        Ext.Array.each(columns, function (rec) {
            searchStoreData.push({'id': rec.dataIndex, 'value': rec.text.replace(/<(?:.|\n)*?>/gm, '')});
            if (rec.isEditableContentColumn) {
                replaceStoreData.push({'id': rec.dataIndex, 'value': rec.text.replace(/<(?:.|\n)*?>/gm, '')});
            }
        });

        Ext.Array.each(searchInFields, function (combo) {
            combo.setStore(Ext.create('Ext.data.Store', {
                fields: ['id', 'value'],
                data: combo.up('#searchTab') ? searchStoreData : replaceStoreData
            }));
            let rec = combo.findRecord('id', me.activeColumnDataIndex);

            if (!rec) {
                rec = combo.getStore().getAt(0);
            }

            combo.setSelection(rec);
        });
    },

    /**
     * Initialize the search and replace columns (only the visible one will be selected)
     */
    initColumnArrays: function () {
        const me = this,
            segmentGrid = me.getSegmentGrid();
        me.searchFields = [];
        me.replaceFields = [];
        //as concepted we provide only the visible grid columns for searching / replacing
        me.searchFields = me.getColumnDataIndex(segmentGrid.query('gridcolumn[isContentColumn]:not([hidden])'));
        me.replaceFields = me.getColumnDataIndex(segmentGrid.query('gridcolumn[isEditableContentColumn]:not([hidden])'));
    },

    /**
     * Get data indexes for given columns(this will put all grid columns data indexes in one array)
     */
    getColumnDataIndex: function (columns) {
        if (columns.length < 1) {
            return [];
        }

        const dataArray = [];

        Ext.Array.each(columns, function (col) {
            Ext.Array.push(dataArray, col.dataIndex)
        });

        return dataArray;
    },

    /**
     * Search for the matches in the database.
     * If matches are found, set the viewmodels and open the first segment where the matches are found
     */
    sendSearchRequest: function () {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            activeTabViewModel = activeTab.getViewModel();

        me.searchRequired = false;

        //get the search parameters (with filter and sort included)
        const params = me.getSearchReplaceParams();

        //validate the required params
        if (!me.searchParamValidator(params)) {
            activeTab.isValid();

            return;
        }

        Ext.Ajax.request({
            url: Editor.data.restpath + 'segment/search',
            params: params,
            method: 'GET',
            success: function (response) {
                const responseData = Ext.JSON.decode(response.responseText);

                if (!responseData) {
                    return;
                }

                const foundSegments = responseData.rows,
                    message = responseData.message,
                    tabPanelviewModel = tabPanel.getViewModel();

                if (!tabPanelviewModel) {
                    return;
                }

                tabPanelviewModel.set('hasMqm', !!responseData.hasMqm);
                tabPanelviewModel.set('isOpenedByMoreThanOneUser', responseData.isOpenedByMoreThanOneUser);

                if (!foundSegments && message) {
                    Editor.MessageBox.addInfo(message);

                    return;
                }

                tabPanelviewModel.set('searchResultsFound', foundSegments.length > 0);
                activeTabViewModel.set('resultsCount', foundSegments.length);
                activeTabViewModel.set('result', foundSegments);
                activeTabViewModel.set('showResultsLabel', true);
                me.handleRowSelection();
                me.startTimeTracking();
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /**
     * Replace the text only, without ins, dell tags
     */
    pureReplace: function (bookmarkRangeForDel, replaceText) {
        const tagsConversion = this.editor.editor.getTagsConversion();
        const contentInRange = this.editor.editor.getContentInRange(bookmarkRangeForDel.start, bookmarkRangeForDel.end);

        const images = [];
        const traverseTree = (nodes) => {
            for (const node of nodes) {
                if (tagsConversion.isTrackChangesDelNode(node)) {
                    continue;
                }

                if (tagsConversion.isInternalTagNode(node)) {
                    images.push(node);
                }

                if (node.childNodes.length > 0) {
                    traverseTree(node.childNodes);
                }
            }
        }

        traverseTree(RichTextEditor.stringToDom(contentInRange).childNodes);

        for (const image of images) {
            replaceText += image.outerHTML;
        }

        this.editor.editor.replaceContentInRange(bookmarkRangeForDel.start, bookmarkRangeForDel.end, replaceText);
    },

    /**
     * Replace all ajax call
     */
    replaceAll: function () {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            activeTabViewModel = activeTab.getViewModel(),
            editingPlugin = me.getSegmentGrid().editingPlugin;

        //colose the row editor if is opened
        if (editingPlugin.editing) {
            editingPlugin.cancelEdit();
        }

        //stop the time tracking
        me.stopTimeTracking();

        //setup segment grid autostate before replace all is called
        me.segmentGridOnReplaceAll(activeTabViewModel.get('result'));

        //show the loading mask on the search window and on the segment grid
        me.showReplaceAllLoading(true);

        //get the search parameters
        const params = me.getSearchReplaceParams(true);

        if (Editor.data.plugins.hasOwnProperty('FrontEndMessageBus')) {
            params.async = true;
        }

        Ext.Ajax.request({
            url: Editor.data.restpath + 'segment/replaceall',
            timeout: '120000',//increase the timeout to 2 min
            params: params,
            method: 'POST',
            success: function (response) {
                //stop the loading
                me.showReplaceAllLoading(false);
                const responseData = Ext.JSON.decode(response.responseText);
                if (!responseData) {
                    return;
                }

                if (responseData.total !== activeTabViewModel.get('resultsCount')) {
                    Editor.MessageBox.addError(me.strings.replaceAllErrors);
                }

                //TODO: this should be implemented via websokets
                //update the segment finish count view model
                me.updateSegmentsFinishCount(responseData);

                const replacedSegments = responseData.rows,
                    message = responseData.message,
                    tabPanelviewModel = tabPanel.getViewModel();

                //display the message if there are no results
                if (!replacedSegments && message) {
                    Editor.MessageBox.addInfo(message);
                    tabPanelviewModel.set('hasMqm', responseData.hasMqm);

                    return;
                }

                tabPanelviewModel.set('hasMqm', false);

                if (! params.async) {
                    //update the modefied segments in the segment store
                    me.segmentGridOnReplaceAll(replacedSegments, true);
                }

                //reset some of the viewmodels properties (clean the search results)
                me.resetSearchParameters();
            },
            failure: function (response) {
                //stop the loading
                me.showReplaceAllLoading(false);

                //reload the requested segments
                me.segmentGridOnReplaceAll(activeTabViewModel.get('result'), true);

                Editor.app.getController('ServerException').handleException(response);
            }
        });
    },

    /**
     * Open the segment for editing, or move through the search hits in the currently edited segment
     */
    handleRowSelection: function () {
        const me = this,
            plug = Editor.app.getController('Editor');

        if (me.activeSegment.matchIndex >= me.activeSegment.matchCount - 1) {
            me.activeSegment.matchIndex = 0;
            me.findEditorSegment(plug);

            return;
        }

        //clean the mark tags
        me.cleanMarkTags();
        me.findMatchesDelay();
        me.activeSegment.matchIndex++;
    },

    /**
     * Triggers the findMatches function with delay.
     */
    findMatchesDelay: function () {
        const me = this;
        //delay so the roweditor is loaded
        const task = new Ext.util.DelayedTask(function () {
            if (me.isContentEditableField()) {
                me.findMatches();
            }
        });

        task.delay(300);
    },

    /**
     * Search and mark the hits in the current open segment in editor
     */
    findMatches: function () {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            searchField = activeTab.down('#searchField'),
            searchType = activeTab.down('radiofield').getGroupValue(),
            matchCase = activeTab.down('#matchCase').checked,
            classApplierModule = rangy.modules.ClassApplier;

        let searchTerm = searchField.getRawValue();

        if (searchTerm === "") {
            return;
        }

        rangy.init();

        if (!rangy.supported || !classApplierModule || !classApplierModule.supported) {
            return;
        }

        // If editor is not yet instantiated
        if (!this.editor?.editor) {
            me.retryFindMatchesOnEditorInstantiate = true;
            return;
        }

        const range = rangy.createRange()
        const div = document.createElement('div');
        div.innerHTML = this.editor.editor.getRawData();

        document.body.appendChild(div);

        const options = {
            caseSensitive: matchCase,
            wholeWordsOnly: false,
            wordOptions: {
                includeTrailingSpace: true
            },
            direction: "forward" // This is redundant because "forward" is the default,
        };

        //clean the old search in different cell
        if (me.oldSerchedCell) {
            me.oldSerchedCell = null;
        }

        range.selectNodeContents(div);

        //build the search term before search
        searchTerm = me.handleSearchType(searchTerm, searchType, matchCase);

        me.replaceRanges = [];
        me.activeSegment.matchCount = 0;
        // Iterate over matches
        while (range.findText(searchTerm, options)) {
            //is not an already replaced match
            if (!me.hasClassInRange(me.CSS_CLASSNAME_REPLACED_INS, range) && !me.isDeletion(range)) {
                me.activeSegment.matchCount++;

                //save the range for later replace usage
                me.replaceRanges.push(this.convertRangeToHTMLPositions(range, div));
            }

            // Collapse the range to the position immediately after the match
            range.collapse(false);
        }

        for (const [index, markRange] of me.replaceRanges.entries()) {
            const options = {
                value: index === me.activeSegment.matchIndex ? 'redMarker' : 'yellowMarker'
            };

            this.editor.editor.markContentInRange(markRange.start, markRange.end, options);
        }

        //focus the search trigger field
        me.searchFieldTrigger && me.searchFieldTrigger.focus();
    },

    convertRangeToHTMLPositions: function (range, root) {
        let charCount = 0;
        let startCharCount = 0;
        let endCharCount = 0;
        let foundStart = false;
        let foundEnd = false;

        const tagsConversion = this.editor.editor.getTagsConversion();

        // Function to traverse text nodes and calculate positions
        function traverseTextNodes(node) {
            if (tagsConversion.isInternalTagNode(node) || tagsConversion.isMQMNode(node)) {
                charCount += 1;

                return;
            }

            if (node.nodeType === Node.TEXT_NODE) {
                if (node === range.startContainer) {
                    startCharCount = charCount + range.startOffset;
                    foundStart = true;
                }

                if (node === range.endContainer) {
                    endCharCount = charCount + range.endOffset;
                    foundEnd = true;
                }

                charCount += node.length;
            }

            for (let child = node.firstChild; child && !foundEnd; child = child.nextSibling) {
                traverseTextNodes(child);

                if (foundStart && foundEnd) {
                    break;
                }
            }
        }

        // Start traversal from the root node
        traverseTextNodes(root);

        return {start: startCharCount, end: endCharCount};
    },

    /**
     * Find matches in non editable cell.
     */
    findMatchesGrid: function (cell) {
        const me = this,
            tabPanel = me.getTabPanel();

        //the tab panel does not exist (the window can be closed)
        if (!tabPanel) {
            return;
        }

        const activeTab = tabPanel.getActiveTab(),
            searchField = activeTab.down('#searchField'),
            searchType = activeTab.down('radiofield').getGroupValue(),
            matchCase = activeTab.down('#matchCase').checked,
            classApplierModule = rangy.modules.ClassApplier;

        let searchTerm = searchField.getRawValue(),
            searchResultApplier = null;

        if (rangy.supported && classApplierModule && classApplierModule.supported) {
            searchResultApplier = rangy.createClassApplier("searchResult", {
                elementTagName: me.NODE_NAME_MARK
            });

            searchResultApplier._OVERRIDDENisIgnorableWhiteSpaceNode = searchResultApplier.isIgnorableWhiteSpaceNode;

            //The point of this override is - do not apply mark tags to delete tags.
            //This fix is available only for the current object
            searchResultApplier.isIgnorableWhiteSpaceNode = function (node) {
                if (node.parentElement && node.parentElement.nodeName.toLowerCase() === me.self.NODE_NAME_DEL) {
                    return true;
                }
                return this._OVERRIDDENisIgnorableWhiteSpaceNode.call(this, node);
            };

            // Remove existing highlights
            const range = rangy.createRange(),
                searchScopeRange = rangy.createRange();

            searchScopeRange.selectNodeContents(cell.dom);

            const options = {
                caseSensitive: matchCase,
                wholeWordsOnly: false,
                withinRange: searchScopeRange,
                wordOptions: {
                    includeTrailingSpace: true
                },
                direction: "forward" // This is redundant because "forward" is the default,
            };

            if (me.oldSerchedCell) {
                range.selectNodeContents(me.oldSerchedCell);
                searchResultApplier.undoToRange(range);
            }

            range.selectNodeContents(cell.dom);
            searchResultApplier.undoToRange(range);

            me.oldSerchedCell = cell.dom;

            let celInner = Ext.get(cell.dom).query('div.x-grid-cell-inner'),
                contentDiv = celInner.length > 0 ? celInner[0] : null,
                divNodesToHide = [],
                arrLength = 0;

            //if the contend div is found, try to find and hide all div childs(thay are not needed for the search)
            if (contentDiv) {
                divNodesToHide = Ext.get(contentDiv).query('div');
                arrLength = divNodesToHide.length;

                for (let i = 0; i < arrLength; i++) {
                    const node = divNodesToHide[i];
                    node.classList.add(me.CSS_CLASSNAME_HIDE_ELEMENT);
                }
            }

            if (searchTerm === "") {
                return
            }

            //build the search term before search
            searchTerm = me.handleSearchType(searchTerm, searchType);

            // Iterate over matches
            while (range.findText(searchTerm, options)) {
                //apply to range, this will select the text
                searchResultApplier.applyToRange(range);
                // Collapse the range to the position immediately after the match
                range.collapse(false);
            }

            if (contentDiv) {
                //set the hidden divs back to visible
                for (let i = 0; i < arrLength; i++) {
                    const node = divNodesToHide[i];
                    node.classList.remove(me.CSS_CLASSNAME_HIDE_ELEMENT);
                }
            }

            //focus the search trigger field
            me.searchFieldTrigger && me.searchFieldTrigger.focus();
        }
    },

    /**
     * Find the segment in the grid, and open it for editing.
     */
    findEditorSegment: function (plug) {
        const me = this,
            grid = plug.getSegmentGrid(),
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            activeTabViewModel = activeTab.getViewModel(),
            results = activeTabViewModel.get('result'),
            saveCurrentOpen = activeTab.down('#saveCurrentOpen').checked,
            saveCurrentDraft = activeTab.down('#saveCurrentDraft').checked,
            searchTopChekbox = activeTab.down('#searchTopChekbox').checked,
            indexBoundaries = grid.getVisibleRowIndexBoundaries();

        let inVisibleAreaFound = false,
            tmpRowNumber,
            goToIndex = null,
            goToIndexEdited = null;

        if (results.length < 1) {
            Editor.MessageBox.addInfo(me.strings.noSearchResults);

            return;
        }

        //check if all search segment parameters are 0(this is the initial state of the search)
        const isSearchStart = function () {
            return me.activeSegment.matchIndex === 0 &&
                me.activeSegment.nextSegmentIndex === 0 &&
                me.activeSegment.currentSegmentIndex === 0 &&
                me.activeSegment.matchCount === 0;
        };

        //if it is a new search, find the first/last visible rows, and find the current edited segment index in the search results
        if (isSearchStart()) {
            //find the record from the results, located between the visible index area
            for (let index = 0; index < results.length; index++) {
                //if the segment is edited, check if this record also exist in the search results
                //this record is with highest priority
                if (grid.editingPlugin.context) {
                    goToIndexEdited = me.getSegmentEditedRowNumber(grid.editingPlugin.context.record, results[index]);
                }

                //since this state is with highest prio, stop with the loop
                if (goToIndexEdited != null && goToIndexEdited >= 0) {
                    me.activeSegment.nextSegmentIndex = index;

                    break;
                }

                tmpRowNumber = me.getSegmentRowNumber(results[index]);
                //if the hit row is in the range of the visible columns in the grid
                if (tmpRowNumber >= indexBoundaries.top && tmpRowNumber <= indexBoundaries.bottom) {
                    if (!inVisibleAreaFound) {
                        goToIndex = tmpRowNumber;
                        //find the direction of the next segment
                        me.activeSegment.nextSegmentIndex = index;
                        inVisibleAreaFound = true;
                    }
                }
            }
        }

        if (grid.editingPlugin.context && goToIndexEdited != null && goToIndexEdited >= 0) {
            goToIndex = goToIndexEdited;
        }

        //if no index is found, use the initial one -> 0
        if (goToIndex === null) {
            //if the search top is checked before the search is triggered, it will be in value of -1, which means the last record
            //in that case call updatesegmentindex so we get the right index
            if (me.activeSegment.nextSegmentIndex < 0 && results.length > 0) {
                me.updateSegmentIndex(searchTopChekbox);
            }

            goToIndex = me.getSegmentRowNumber(results[me.activeSegment.nextSegmentIndex]);
        }

        //go to segment and open it for editing
        const callback = function (indexToGo) {
            // me.removeReplaceClass();
            me.goToSegment(indexToGo, plug, saveCurrentOpen, saveCurrentDraft);
            me.activeSegment.currentSegmentIndex = me.activeSegment.nextSegmentIndex;
            //update the segment indexes
            me.updateSegmentIndex(searchTopChekbox);
        };

        //if no index, try to find it
        if (goToIndex >= 0) {
            callback(goToIndex);
        } else {
            const nextSegmenInTaskt = results[me.activeSegment.nextSegmentIndex];

            if (nextSegmenInTaskt) {
                grid.searchPosition(nextSegmenInTaskt.segmentNrInTask).then(function (index) {
                    if (index < 0) {
                        Editor.MessageBox.addInfo(me.strings.noIndexFound);

                        return;
                    }

                    callback(index);
                });
            }
        }
    },

    /**
     * Scroll the segment and open it for editing
     */
    goToSegment: function (goToIndex, plug, saveCurrentOpen, saveCurrentDraft) {
        const me = this,
            grid = plug.getSegmentGrid(),
            selModel = grid.getSelectionModel(),
            ed = grid.editingPlugin,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            replaceField = activeTab.down('#searchInField'),
            searchInLockedSegments = activeTab.down('#searchInLockedSegments').checked,
            selectedColumnDataIndex = replaceField.getSelection().get('id'),
            callback = function () {
                grid.selectOrFocus(goToIndex);
                const sel = selModel.getSelection();

                if (saveCurrentOpen === false && saveCurrentDraft === false && ed.editing) {
                    ed.cancelEdit();
                }

                if (me.isContentEditableField()) {
                    const theColum = grid.query('gridcolumn[dataIndex="' + selectedColumnDataIndex + '"]');
                    let editableColumn = null;

                    if (theColum.length > 0) {
                        editableColumn = theColum[0];
                    }

                    if(saveCurrentDraft) {
                        ed.isDraft = true;
                    }
                    ed.startEdit(sel[0], editableColumn, ed.self.STARTEDIT_MOVEEDITOR);

                    //clean the mark tags from the editor
                    me.cleanMarkTags();

                    //if is not a locked segment search, do regular find match
                    if (!searchInLockedSegments) {
                        me.findMatchesDelay();
                    }
                }

                //no editable content or locked search, find matches in the cell
                if (!me.isContentEditableField() || searchInLockedSegments) {
                    const visibleColumns = grid.query('gridcolumn:not([hidden])');
                    let cellIndex = 0;

                    //find the index of the searched column
                    for (let i = 0; i < visibleColumns.length; i++) {
                        if (visibleColumns[i].dataIndex === selectedColumnDataIndex) {
                            cellIndex = i;
                            break;
                        }
                    }

                    //get searched cell in the selected row
                    const gridCell = grid.getView().getCell(sel[0], cellIndex);
                    me.findMatchesGrid(gridCell);
                }
            };

        grid.scrollTo(goToIndex, {
            callback: callback,
            notScrollCallback: callback
        });
    },


    /**
     * Update segment grid data based on matched results.
     */
    segmentGridOnReplaceAll: function (results, updateRecord) {
        const me = this,
            segmentStore = me.getSegmentGrid().getStore();

        //if the update is needed, load the segment store
        if (updateRecord) {
            segmentStore.load();

            return;
        }

        for (let i = 0; i < results.length; i++) {
            //fieldName,value,startIndex,anyMatch,caseSensitive,exactMatch
            const record = segmentStore.findRecord('segmentNrInTask', results[i].segmentNrInTask, 0, false, false, true);

            if (!record) {
                continue;
            }

            //set the autostate
            record.set('autoStateId', 999);
        }
    },

    /**
     * Reset some of the search/replace tab view model properties.
     *
     */
    resetSearchParameters: function () {
        if (!this.getSearchReplaceWindow()) {
            return;
        }

        const me = this,
            tabPanel = me.getTabPanel(),
            tabPanelviewModel = tabPanel.getViewModel(),
            activeTab = tabPanel.getActiveTab(),
            activeTabViewModel = activeTab.getViewModel();

        tabPanelviewModel.set('searchResultsFound', false);
        activeTabViewModel.set('resultsCount', 0);
        activeTabViewModel.set('result', []);
        activeTabViewModel.set('showResultsLbel', false);

        me.replacedSegmentsIndex = [];

        me.searchRequired = true;
    },

    /**
     * Is new search is required
     */
    isSearchRequired: function () {
        return this.searchRequired;
    },

    /**
     * Calculate and update the next segment index
     */
    updateSegmentIndex: function (checked) {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            activeTabViewModel = activeTab.getViewModel(),
            results = activeTabViewModel.get('result');

        //recalculate the next index
        if (checked) {
            //set one position back
            me.activeSegment.nextSegmentIndex = me.activeSegment.currentSegmentIndex - 1;
        } else {
            //set one position to front (we assume that )
            me.activeSegment.nextSegmentIndex = me.activeSegment.currentSegmentIndex + 1;
        }

        //check if the index is out of the result boundaries
        if (me.activeSegment.nextSegmentIndex >= results.length) {
            me.activeSegment.nextSegmentIndex = 0;
        }

        if (me.activeSegment.nextSegmentIndex < 0) {
            me.activeSegment.nextSegmentIndex = results.length - 1;
        }
    },

    /**
     * Get segment row index from the segment store
     */
    getSegmentRowNumber: function (record) {
        if (!record) {
            return -1;
        }

        const grid = this.getSegmentGrid(),
            store = grid.store,
            newRecord = store.findRecord('id', record.id);

        return grid.store.indexOf(newRecord);
    },


    /**
     * Check if the current edited segment is in the search results
     */
    getSegmentEditedRowNumber: function (segmentRecord, record) {
        return segmentRecord.id === record.id ? this.getSegmentRowNumber(record) : null;
    },

    /**
     * Check if the current selected field is content editable
     */
    isContentEditableField: function () {
        const me = this,
            tabPanel = me.getTabPanel();

        if (!tabPanel) {
            return false;
        }

        const activeTab = tabPanel.getActiveTab(),
            searchInField = activeTab.down('#searchInField'),
            searchInFieldSelectedVal = searchInField.getValue();

        return Ext.Array.contains(this.replaceFields, searchInFieldSelectedVal);
    },

    /**
     * Destroy the search window, create new if needed
     */
    destroySearchWindow: function (createNew) {
        //remove all exisiting search windows
        const searchWindow = Ext.ComponentQuery.query('#searchreplacewindow');

        if (searchWindow.length > 0) {
            for (let i = 0; i < searchWindow.length; i++) {
                searchWindow[i].destroy();
            }
            createNew && this.handleSearchReplaceHotkey(null);
        }
    },

    /**
     * Get the search parameter from the search form.
     */
    getSearchReplaceParams: function (isReplace) {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            form = activeTab.getForm(),
            formFields = form.getFields().items,
            segmentGrid = me.getSegmentGrid(),
            segmentStore = segmentGrid.editingPlugin.grid.store,
            proxy = segmentStore.getProxy(),
            params = {};

        params[proxy.getFilterParam()] = proxy.encodeFilters(segmentStore.getFilters().items);
        params[proxy.getSortParam()] = proxy.encodeSorters(segmentStore.getSorters().items);
        params['taskGuid'] = Editor.data.task.get('taskGuid');
        params['searchType'] = activeTab.down('radiofield').getGroupValue();

        //set the form values as parameters
        for (let i = 0; i < formFields.length; i++) {
            if (formFields[i].itemId) {
                params[formFields[i].itemId] = formFields[i].getValue();
            }
        }

        if (isReplace) {
            params['durations'] = me.timeTracking;
        }

        //if track changes are active, set the trackchanges flag and parameters
        if (me.isActiveTrackChanges()) {
            params['isActiveTrackChanges'] = true;
            params['attributeWorkflowstep'] = Editor.data.task.get('workflowStepName') + Editor.data.task.get('workflowStep');
            params['userTrackingId'] = Editor.data.task.get('userTrackingId');
            params['userColorNr'] = Editor.data.task.get('userColorNr');
        }

        return params;
    },

    /**
     * Return the search term for given type
     */
    handleSearchType: function (searchTerm, searchType, matchCase) {
        if (searchType === "regularExpressionSearch") {
            searchTerm = new RegExp(searchTerm, "g" + (!matchCase ? 'i' : ''));
        }

        if (searchType === "wildcardsSearch") {
            function preg_quote(str, delimiter) {
                return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
            }

            function globStringToRegex(str) {
                return new RegExp(preg_quote(str).replace(/\\\*/g, '.*').replace(/\\\?/g, '.'), 'gi');
            }

            searchTerm = globStringToRegex(searchTerm);
        }

        return searchTerm;
    },

    /**
     * Move to the next segment if needed.
     * Returns true if the segment is saved or new segment selection is needed
     * Return false if next segment selection is not needed
     */
    jumpToNextSegment: function () {
        const me = this,
            tabPanel = me.getTabPanel(),
            activeTab = tabPanel.getActiveTab(),
            saveCurrentOpen = activeTab.down('#saveCurrentOpen').checked,
            saveCurrentDraft = activeTab.down('#saveCurrentDraft').checked;

        if (me.activeSegment.matchCount !== 0 || me.activeSegment.matchIndex <= me.activeSegment.matchCount - 1) {
            return false;
        }

        //if there is only one match, and the save current open is active -> save the segment
        if ((saveCurrentOpen || saveCurrentDraft) && me.activeSegment.currentSegmentIndex === me.activeSegment.nextSegmentIndex) {
            if(saveCurrentDraft) {
                Editor.app.getController('Editor').saveDraft();
            } else {
                Editor.app.getController('Editor').save();
            }
        } else {
            me.handleRowSelection();
        }

        return true;
    },

    /**
     * Show/hide the loading mask for segment grid and search replace window
     */
    showReplaceAllLoading: function (show) {
        this.getSegmentGrid().setLoading(show);
        this.getSearchReplaceWindow().setLoading(show);
    },

    /**
     * Start the replace all time tracking
     */
    startTimeTracking: function () {
        this.timeTracking = new Date();
    },

    /**
     * Stop the search and replace time tracking
     */
    stopTimeTracking: function () {
        const me = this;

        //if it was a date, calculate the spend time
        if (me.timeTracking instanceof Date) {
            me.timeTracking = (new Date()) - me.timeTracking;
        } else {
            me.timeTracking = 0;
        }
    },

    /**
     * update the segments finish count view model after replace all
     */
    updateSegmentsFinishCount: function (json) {
        Editor.app.getController('Segments').updateSegmentFinishCountViewModel(json.taskProgress, json.userProgress);
    },

    /**
     * Validate the required search parameters
     */
    searchParamValidator: function (params) {
        const me = this;
        let isValid = true;

        for (let i = 0; i < me.requiredParams.length; i++) {
            const p = me.requiredParams[i];

            if (!params[p] || params[p] === "") {
                isValid = false;

                break;
            }
        }

        return isValid;
    },

    /**
     * Check if the trackchanges are active
     */
    isActiveTrackChanges: function () {
        //check if the trackchanges are active
        if (!Editor.plugins.TrackChanges) {
            return false;
        }

        return !(Editor.data.task.get('workflowStepName') === 'translation' && Editor.data.task.get('workflowStep') === '1');
    },

    /**
     * Make sure field value is selected on field render,
     * so that it can be immediately replaced when Ctrl+V is pressed
     *
     * @param field
     */
    onSearchFieldRender: function(field) {
        Ext.defer(() => {
            field.selectText();
        }, 100);
    }
});
    