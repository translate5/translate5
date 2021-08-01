
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

/**
 * CTRL+Z and CTRL+Y do not work as expected with some translate5-Plugins.
 * Use this class to emulate the expected behaviour.
 * 
 * Multiple plugins must not trigger save, undo or redo multiple times;
 * therefore the Plugins that use the SnapshotHistory only activate it
 * (The keyboard-events themselves are handled by the Editor).
 * CAUTION: Never deactivate the SnapshotHistory from a plugin! This might 
 * also deactivate it for other plugins that need it!
 * 
 * Everytime the user opens the Editor for a new segment from the grid, 
 * the snapshot-history starts new.
 * 
 * @class Editor.controller.SnapshotHistory
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.SnapshotHistory', {
    extend : 'Ext.app.Controller',
    mixins: ['Editor.util.DevelopmentTools'],
    listen: {
        controller: {
            '#Editor': {
                activateSnapshotHistory: 'activateSnapshotHistory',
                // ----------------------------------------------------------------------------
                // pls try to control the following four main SnapshotHistory-events completely 
                // via the Editor-Controller, not from different places around the code:
            	saveSnapshot: 'saveSnapshot', 
            	updateSnapshotBookmark: 'updateSnapshotBookmark',
                undo: 'undo',
                redo: 'redo'
                // ----------------------------------------------------------------------------
            },
            '#Editor.plugins.SpellCheck.controller.Editor': {
            	activateSnapshotHistory: 'activateSnapshotHistory'
            },
            '#Editor.plugins.TrackChanges.controller.Editor': {
            	activateSnapshotHistory: 'activateSnapshotHistory'
            }
        },
        component:{
            'segmentsHtmleditor': {
                initialize: 'init',
                saveSnapshot: 'saveSnapshot'
            },
            '#segmentgrid': {
                beforeedit: 'initSnapshotHistory' // start new everytime we open a segment
            }
        }
    },

    /***
     * The segment's Editor (Editor.view.segments.HtmlEditor) 
     */
    editor: null,
    /***
     * The segment's Editor (Ext.dom.Element) 
     */
    editorBodyExtDomElement: null,
    
    editorSnapshotHistoryActived: false, // activate when needed (might be activated by multiple plugins, that's ok)
    editorSnapshotHistory: null,         // array with snapshot-objects of the content in the Editor before each new step of editing
    editorSnapshotReference: null,       // index of snapshot-item currently used (start with 0)
    
    /**
     * @param {Editor.view.segments.HtmlEditor} editor
     */
    init: function(editor) {
    	var me = this;
    	if(!me.editorSnapshotHistoryActived) {
    		return;
    	}
    	me.editor = editor;
    	me.editorBodyExtDomElement = me.getEditorBodyExtDomElement();
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

    // =========================================================================
    // History with snaphots of the content
    // =========================================================================
    
    /**
     * Is the snapshot-history to be used at all?
     * (If not really necessary, we save our time and energy and 
     * use the default behaviour for CTRL+Z and CTRL+Y.)
     */ 
    activateSnapshotHistory: function() {
        var me = this;
    	me.editorSnapshotHistoryActived = true;
        me.consoleLog('~~~~~~~~ Snapshot-History activated.');
    },
    /**
     * Initialize the snapshot-history and save first snapshot.
     */ 
    initSnapshotHistory: function(editor) {
        var me = this;
    	if(!me.editorSnapshotHistoryActived) {
    		return;
    	}
    	if(me.editor == null) {
    		me.init(editor);
    	}
        me.consoleLog('~~~~~~~~ Initialize Snapshot-History.');
        me.editorSnapshotHistory = [];
        me.editorSnapshotReference = null;
    	me.consoleLog(me.editorSnapshotHistory);
    },
    /**
     * Save the current content in the Editor as newest snapshot-item in the history.
     */ 
    saveSnapshot: function() {
        var me = this,
            contentForSnaphot,
            bookmarkForSnapshot;
    	if(!me.editorSnapshotHistoryActived) {
    		return;
    	}
        if (me.editorSnapshotHistory == null) {
            me.initSnapshotHistory();
        }
        if (me.editorBodyExtDomElement == null) {
            me.consoleLog('saveSnapshot: no snapshop saved because editor-body was not found (maybe the editor is closed already).'); 
            return;
        }
        contentForSnaphot = me.editorBodyExtDomElement.getHtml();
        if (contentForSnaphot === me.getContentOfNewestSnapshot()) {
            me.consoleLog('saveSnapshot: no snapshop saved because the content is not new.'); 
            return;
        }
        bookmarkForSnapshot = me.getCurrentBookmark();
        me.removeNewerSnapshots(); // delete newer items if there are any.
        me.editorSnapshotHistory.push({content: contentForSnaphot, bookmark: bookmarkForSnapshot});
        me.editorSnapshotReference = me.editorSnapshotHistory.length - 1;
        me.addSnapshotToLogger(contentForSnaphot, 'saved');
    	me.consoleLog('~~~~~~~~ SNAPSHOTHISTORY: snapshot saved (me.editorSnapshotReference: ' + me.editorSnapshotReference + ')');
    	me.consoleLog(me.editorSnapshotHistory);
    },
    /**
     * Save the new position of the cursor in the current bookmark.
     * (We don't check if the current position is the same as before in order to save time.)
     */
    updateSnapshotBookmark: function() {
    	var me = this;
    	if(!me.editorSnapshotHistoryActived) {
    		return;
    	}
		me.editorSnapshotHistory[me.editorSnapshotReference].bookmark = me.getCurrentBookmark();
    	me.consoleLog('~~~~~~~~SNAPSHOTHISTORY:  position of cursor updated.');
    },
    /**
     * Emulate CTRL+Z: Restore older snapshot.
     */
    undo: function() {
        var me = this;
    	if(!me.editorSnapshotHistoryActived) {
    		return;
    	}
    	me.consoleLog('~~~~~~~~SNAPSHOTHISTORY:  undo');
    	me.cleanupSnapshotHistory();
        me.rewindSnapshot();
        me.restoreSnapshotInEditor();
    },
    /**
     * Emulate CTRL+Y: Restore newer snapshot.
     */
    redo: function() {
        var me = this;
    	if(!me.editorSnapshotHistoryActived) {
    		return;
    	}
    	me.consoleLog('~~~~~~~~ SNAPSHOTHISTORY: redo');
        me.fastforwardSnapshot();
        me.restoreSnapshotInEditor();
    },
    /**
     * Add the given snapshot as Editor-content to therootcause-logger.
     * @param {String} snapshot
     * @param {String} action
     */
    addSnapshotToLogger: function(snapshot, action) {
        var logMessage = 'translate5 Editor Snapshot ('+action+'): '  + snapshot;
        this.fireEvent('addLogEntryToLogger', logMessage);
    },
    
    // =========================================================================
    // Internal helpers.
    // =========================================================================
    
    /**
     * Where is the cursor in the Editor?
     */
    getCurrentBookmark: function() {
    	var me = this;
        return rangy.getSelection(me.getEditorBody()).getBookmark(me.getEditorBody());
    },
    /**
     * Return the content of the newest snaphot-item.
     * @returns {String}
     */ 
    getContentOfNewestSnapshot: function() {
        var me = this,
            newestSnapshot;
        if (me.editorSnapshotHistory.length === 0) {
        	return null;
        }
        newestSnapshot = me.editorSnapshotHistory[me.editorSnapshotHistory.length - 1];
        return newestSnapshot.content;
    },
    /**
     * Return the bookmark of the newest snaphot-item.
     * @returns {Object}
     */ 
    getBookmarkOfNewestSnapshot: function() {
        var me = this,
            newestSnapshot = me.editorSnapshotHistory[me.editorSnapshotHistory.length - 1];
        return newestSnapshot.bookmark;
    },
    /**
     * Rewind snapshot-history (by number of given steps, if no number given: by one step).
     * @param {integer}
     */ 
    rewindSnapshot: function(nr) {
        var me = this,
            newReference;
        if (nr == null) {
            nr = 1;
        }
        newReference = me.editorSnapshotReference - nr;
        if(newReference >= 0) {
            me.editorSnapshotReference = newReference;
        } else {
            me.consoleLog('Rewinding the snapshot-history is not possible; end of history is reached already (newReference: ' + newReference + ').');
        }
    },
    /**
     * Fast-forward snapshot-history (by number of given steps, if no number given: by one step).
     * @param {integer}
     */ 
    fastforwardSnapshot: function(nr) {
        var me = this,
            newReference;
        if (nr == null) {
            nr = 1;
        }
        newReference = me.editorSnapshotReference + nr;
        if(newReference < me.editorSnapshotHistory.length) {
            me.editorSnapshotReference = newReference;
        } else {
            me.consoleLog('Fastforwarding the snapshot-history is not possible; end of history is reached already (newReference: ' + newReference + ').');
        }
    },
    /**
     * Restore the content and cursor-position of the snapshot-item at the current reference as current content in the Editor.
     */ 
    restoreSnapshotInEditor: function() {
        var me = this,
            currentSnapshot = me.editorSnapshotHistory[me.editorSnapshotReference];
        if (currentSnapshot !== undefined) {
        	me.consoleLog('restore snapshot (content)...');
            me.editorBodyExtDomElement.setHtml(currentSnapshot.content);
            me.restorePositionOfCaret(currentSnapshot.bookmark);
            me.addSnapshotToLogger(currentSnapshot.content, 'restored');
        } else {
            me.consoleLog('currentSnapshot does not exist for editorSnapshotReference = ' + me.editorSnapshotReference);
        }
    },
    /**
     * Move the cursor to the position of the bookmark.
     */
    restorePositionOfCaret: function(bookmark) {
        var me = this,
        	editorBody = me.getEditorBody(),
        	selectionForSnapshot = rangy.getSelection(editorBody),
            rangeForCaret;
    	me.consoleLog('restore snapshot (caret)...');
        if (bookmark.rangeBookmarks.length === 0) {
        	// The first snapshot after opening a segment does not always recognize a position of the cursor in the Editor.
        	// Workaround: If no bookmark is given, we place the cursor at the beginning of the editor (= this is where the cursor is after opening a segment).
        	rangeForCaret = rangy.createRange();
        	rangeForCaret.selectNodeContents(editorBody);
        	rangeForCaret.collapse(true);
        	selectionForSnapshot.setSingleRange(rangeForCaret);
        } else {
        	selectionForSnapshot.moveToBookmark(bookmark);
        }
    },
    /**
     * Remove snapshots that are newer than the current reference (if there are any).
     */
    removeNewerSnapshots: function() {
        var me = this,
            indexRemoveFrom,
            indexRemoveTo,
            howmany;
        if (me.editorSnapshotReference < me.editorSnapshotHistory.length) {
            indexRemoveFrom = me.editorSnapshotReference + 1;       // keep the currently indexed item, start removing with the next item
            indexRemoveTo = (me.editorSnapshotHistory.length) - 1;  // the length is not the index...
            howmany = 1 + (indexRemoveTo - indexRemoveFrom);        // howmany starts with 1 (= to remove the indexed item itself), additional items for removal are ADDED UP
            me.editorSnapshotHistory.splice(indexRemoveFrom,  1 + howmany);
            // me.editorSnapshotReference is a different topic; don't change it here automatically!
        }
    },
    /**
     * Remove double items from the history (typing fast might fill the History with one-and-the-same content, so walking back and forth might result in strange steps).
     */
    cleanupSnapshotHistory: function() {
        var me = this,
            i,
            arrLength = me.editorSnapshotHistory.length;
        for (i = arrLength; i--; ) {
            if (i > 1) {
                if (me.editorSnapshotHistory[i].content === me.editorSnapshotHistory[i-1].content ) {
                    me.editorSnapshotHistory.splice(i, 1);
                    if (me.editorSnapshotReference >= i) {
                        me.editorSnapshotReference = me.editorSnapshotReference - 1;
                    }
                    arrLength = me.editorSnapshotHistory.length;
                }
            }
        }
    }
    
});