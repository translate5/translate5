
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class Editor.util.SegmentEditorSnapshots
 */
Ext.define('Editor.util.SegmentEditorSnapshots', {
    mixins: ['Editor.util.DevelopmentTools',
             'Editor.util.SegmentEditor'
    ],
    
    editorSnapshotHistory: null,        // array with snapshot-objects of the content in the Editor before each new step of editing
    editorSnapshotReference: null,      // index of snapshot-item currently used (start with 0)

    // =========================================================================
    // History with snaphots of the content
    // =========================================================================
    
    // Everytime the user opens the Editor for a new segment from the grid, the snapshot-history starts new.
    // When the same segment is edited right after closing it before, the Snapshot-History behaves as if 
    // the editing continues.
    
    /**
     * Initialize the snapshot-history.
     */ 
    initSnapshotHistory: function() {
        var me = this;
        me.consoleLog('Initialize Snapshot-History.');
        me.editorSnapshotHistory = [];
        me.editorSnapshotReference = null;
        
    },
    /**
     * Save the current content in the Editor as newest snapshot-item in the history.
     */ 
    saveSnapshot: function() {
        var me = this,
            contentForSnaphot,
            selectionForSnapshot,
            bookmarkForSnapshot;
        if (me.editorSnapshotHistory == null) {
            me.initSnapshotHistory();
        }
        if (me.getEditorBodyExtDomElement() == null) {
            me.consoleLog("saveSnapshot: no snapshop saved because editor-body was not found (maybe the editor is closed already)."); 
            return;
        }
        contentForSnaphot = me.getEditorBodyExtDomElement().getHtml();
        selectionForSnapshot = rangy.getSelection(me.getEditorBody());
        bookmarkForSnapshot = selectionForSnapshot.getBookmark(me.getEditorBody());
        me.removeNewerSnapshots(); // delete newer items if there are any.
        me.editorSnapshotHistory.push({content: contentForSnaphot, bookmark: bookmarkForSnapshot});
        me.editorSnapshotReference = me.editorSnapshotHistory.length - 1;
    	me.consoleLog("snapshot saved");
    },
    /**
     * Return the content of the newest snaphot-item.
     * @returns {String}
     */ 
    getContentOfNewestSnapshot: function() {
        var me = this,
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
            me.consoleLog("Rewinding the snapshot-history is not possible; end of history is reached already (newReference: " + newReference + ").");
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
            me.consoleLog("Fastforwarding the snapshot-history is not possible; end of history is reached already (newReference: " + newReference + ").");
        }
    },
    /**
     * Restore the content and cursor-position of the snapshot-item at the current reference as current content in the Editor.
     */ 
    restoreSnapshotInEditor: function() {
        var me = this,
            currentSnapshot = me.editorSnapshotHistory[me.editorSnapshotReference];
        if (currentSnapshot != undefined) {
        	me.consoleLog("restore snapshot (content)...");
            me.getEditorBodyExtDomElement().setHtml(currentSnapshot.content);
            me.restorePositionOfCaret(currentSnapshot.bookmark);
        } else {
            me.consoleLog("currentSnapshot does not exist for editorSnapshotReference = " + me.editorSnapshotReference);
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
    	me.consoleLog("restore snapshot (caret)...");
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
            indexRemoveFrom = me.editorSnapshotReference + 1,       // keep the currently indexed item, start removing with the next item
            indexRemoveTo = (me.editorSnapshotHistory.length) - 1,  // the length is not the index...
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
                if (me.editorSnapshotHistory[i].content == me.editorSnapshotHistory[i-1].content ) {
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