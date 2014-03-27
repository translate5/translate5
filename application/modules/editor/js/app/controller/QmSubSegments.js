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
 * Encapsulates all logic for qmsubsegment- and qmsummary-feature
 * @class Editor.controller.QmSubSegments
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.QmSubSegments', {
    extend : 'Ext.app.Controller',
    views: ['qmsubsegments.Window', 'ToolTip'],
    requires: ['Editor.store.QmSummary'], //QmSummary Store in "requires" instead "stores" to prevent automatic instantiation
    refs:[{
        ref : 'window',
        selector : '#qmsummaryWindow',
        autoCreate: true,
        xtype: 'qmSummaryWindow'
    },
    {
    	ref : 'addMenuFirstLevel',
    	selector : 'qmSubsegmentsFlagFieldset button > menu' //first menu level
    },
    {
    	ref : 'qmFieldset',
    	selector : 'qmSubsegmentsFlagFieldset' //first menu level
    },
    {
	    ref : 'segmentGrid',
	    selector : '#segmentgrid'
    }],
    strings: {
    	emptySelText: '##UT##Bitte wählen Sie im Editor ein Subsegment aus!',
    	emptySelTitle: '##UT##Kein Subsegment ausgewählt.'
    },
    init : function() {
        var me = this;
        me.useAlternateInsertion = Ext.isIE;
        me.control({
            '#segmentgrid #qmsummaryBtn': {
                click: me.showQmSummary
            },
            '#segmentgrid': {
                afterrender: function(){
                    me.tooltip = Ext.create('Editor.view.ToolTip', {
                        target: me.getSegmentGrid().getEl()
                    });
                }
            },
            'qmSubsegmentsFlagFieldset menuitem': {
                click: me.handleAddQmFlagClick
            },
            '#metaInfoForm': {
                beforerender: me.initFieldSet
            },
            'segmentsHtmleditor': {
                afterinitframedoc: me.initIframeDoc,
                afteriniteditor: me.initEditor
            }
        });
    },
    /**
     * initialises the QM SubSegment Fieldset in the MetaPanel
     */
    initFieldSet: function(mpForm) {
    	if(!Editor.data.task.hasQmSub()){
    		return;
    	}
    	var pos = mpForm.items.findIndex('itemId', 'metaQm');
    	mpForm.insert(pos, {xtype: 'qmSubsegmentsFlagFieldset', controller: this});
    },
    /**
     * generates the config menu tree for QM Flag Menu
     * @returns
     */
	getMenuConfig: function() {
		Editor.qmFlagTypeCache = {};
		var me = this,
			cache = Editor.qmFlagTypeCache,
		iterate = function(node) {
			var result;
			if(Ext.isArray(node)){
				result = [];
				Ext.each(node, function(item) {
					result.push(iterate(item));
				});
				return result;
			}
			result = {
				text: node.text,
				qmid: node.id,
				icon: me.getImgTagSrc(node.id, true),
				qmtype: 'qm-'+node.id,
				menuAlign: 'tr-tl'
			};
			cache[node.id] = node.text; 
			if(node.children && node.children.length > 0) {
				result.menu = {
					enableKeyNav: false,
					bodyCls: 'qmflag-menu',
					items: iterate(node.children),
	                listeners: {
	                    afterrender: function(component) {
	                    	if(component.keyNav) {
	                    		component.keyNav.disable();
	                    	}
	                    	// Hide menu and then re-show so that alignment is correct.
	                    	// see http://stackoverflow.com/questions/6687551/extjs-incorrect-dropdown-menu-alignment
	                        component.hide();
	                        component.show();
	                    }
                	}
				};
			}
			return result;
		};
		return iterate(Editor.data.task.get('qmSubFlags'));
	},
    /**
     * initialises things related to the editors iframe 
     * @param editor
     */
    initIframeDoc: function(editor) {
    	if(this.useAlternateInsertion){
	    	editor.iframeEl.on('beforedeactivate', this.handleEditorBlur, this);    	
	    	editor.iframeEl.on('focus', this.handleEditorFocus, this);    	
    	}
    },
    /**
     * initialises ToolTips and other things related to the editors iframe doc body
     * @param editor
     */
    initEditor: function(editor) {
    	if(this.editorTooltip){
    		this.editorTooltip.setTarget(editor.getEditorBody());
    		this.editorTooltip.boundFrame = editor.iframeEl;
    		return;
    	}
    	this.editorTooltip = Ext.create('Editor.view.ToolTip', {
    		target: editor.getEditorBody(),
    		boundFrame: editor.iframeEl
    	});
    },
    /**
     * displays the QM Summary Window
     */
    showQmSummary: function() {
        this.getWindow().show();
    },
    /**
     * Inserts the QM Issue Tag in the Editor, displays popup if nothing selected
     * @param menuitem
     */
    handleAddQmFlagClick: function(menuitem) {
        var me = this,
            sev = me.getQmFieldset().down('combo[name="qmsubseverity"]');
            commentField = me.getQmFieldset().down('textfield[name="qmsubcomment"]'),
            format = Ext.util.Format,
            comment = format.stripTags(commentField.getValue()).replace(/[<>"']/g,'');
            //@todo when we are going to make qm subsegments editable, we should improve the handling of html tags in comments.
            //since TRANSLATE-80 we are stripping the above chars, 
            //because IE did not display the segment content completly with qm subsegments containing these chars
                
        if(! me.addQmFlagToEditor(menuitem.qmid, comment, sev.getValue())) {
            Ext.Msg.alert(me.strings.emptySelTitle, me.strings.emptySelText);
            return;
        }
        sev.reset();
        commentField.reset();
        me.addQmFlagHistory(menuitem);
    },
    /**
     * Inserts the QM Issue IMG Tags around the text selection in the editor 
     * @param {Number} qmid Qm Issue ID
     * @param {String} comment 
     * @param {String} sev Severity ID
     * @return {Boolean}
     */
    addQmFlagToEditor: function(qmid, comment, sev){
		var editor = this.getSegmentGrid().editingPlugin.editor.mainEditor;
		if(!editor.hasSelection() && !this.lastSelectedRangeIE) {
			return false;
		}
		if(this.useAlternateInsertion) {
			this.insertQmFlagsIE(editor,qmid, comment, sev);
		} else {
			this.insertQmFlagsH5(editor,qmid, comment, sev);
		}
		this.lastSelectedRangeIE = null;
		return true;
    },
    /**
     * QM IMG Tag Inserter for for HTML5 Browsers (= not IE) 
     * @param {Editor.view.segments.HtmlEditor} editor
     * @param {Number} qmid Qm Issue ID
     * @param {String} comment 
     * @param {String} sev Severity ID
     */
    insertQmFlagsH5: function(editor, qmid, comment, sev){
		var doc = editor.getDoc(),
		rangeBegin = doc.getSelection().getRangeAt(0),
		rangeEnd = rangeBegin.cloneRange(),
		tagDef = this.getImgTagDomConfig(qmid, comment, sev),
		open = Ext.DomHelper.createDom(tagDef.open),
		close = Ext.DomHelper.createDom(tagDef.close);
		rangeBegin.collapse(true);
		rangeEnd.collapse(false);
		rangeBegin.insertNode(open);
		rangeEnd.insertNode(close);
		doc.getSelection().removeAllRanges();
    },
    /**
     * generates a Dom Config Object with the to image tags 
     * @param {Number} qmid Qm Issue ID
     * @param {String} comment 
     * @param {String} sev Severity ID
     * @returns {Object}
     */
    getImgTagDomConfig: function(qmid, comment, sev) {
    	var me = this, 
    		uniqid = Ext.id(),
    		config = function(open){
    		return {
    			tag: 'img', 
    			id: 'qm-image-'+(open ? 'open' : 'close')+'-'+uniqid, 
    			'data-comment': (comment ? comment : ""), 
    			'data-seq': uniqid, 
    			//minor qmflag qmflag-2 ownttip open
    			cls: sev+' qmflag ownttip '+(open ? 'open' : 'close')+' qmflag-'+qmid, 
    			src: me.getImgTagSrc(qmid, open)
    		};
    	};
    	return {
    		open: config(true),
    		close: config(false)
    	};
    },
    /**
     * generates the image tag src
     * @param {String} qmid
     * @param {Boolean} open
     * @returns {String}
     */
    getImgTagSrc: function(qmid, open) {
    	return Editor.data.segments.subSegment.tagPath+'qmsubsegment-'+qmid+'-'+(open ? 'left' : 'right')+'.png';
    },
    /**
     * maintains the last used QM Flags in the first level of the menu
     * @param {Ext.menu.Item} menuitem
     */
    addQmFlagHistory: function(menuitem) {
    	if(!menuitem.parentMenu.parentMenu) {
    		return; //ignore first level and history menu entries
    	}
    	var me = this,
    	id = menuitem.qmid,
    	toremove,
    	toadd = Ext.applyIf({}, menuitem.initialConfig),
    	menu = me.getAddMenuFirstLevel();
    	if(! me.lastUsed){
        	me.lastUsed = [];
        	me.historyTopIndex = menu.items.length + 1;
        	menu.add('-');
    	}
    	delete toadd.menu;
    	
    	//if already in list ignore
    	if(Ext.Array.contains(me.lastUsed, id)){
    		Ext.Array.remove(me.lastUsed, id); // remove from stack
    		me.lastUsed.push(id); //and put it to the end
    		return;
    	}
    	
    	menu.insert(me.historyTopIndex, toadd);
    	
    	//cycle through lastused id array
    	me.lastUsed.push(id);
    	if(me.lastUsed.length > 5) {
    		toremove = me.lastUsed.shift();
    		toremove = menu.query('> menuitem[qmid="'+toremove+'"]');
    		if(toremove.length > 0){
    			toremove[0].destroy();
    		}
    	}
    },
    /**
     * QM IMG Tag Inserter for IE
     * @see http://stackoverflow.com/questions/1470932/ie8-iframe-designmode-loses-selection
     * @see http://www.webmasterworld.com/javascript/3820483.htm
     * @param {Editor.view.segments.HtmlEditor} editor
     * @param {Number} qmid Qm Issue ID
     * @param {String} comment
     * @param {String} sev
     */
    insertQmFlagsIE: function(editor, qmid, comment, sev){
    	var doc = editor.getDoc(),
    		sep = Editor.TRANSTILDE,
    		tagDef = this.getImgTagDomConfig(qmid, comment, sev),
    		open = Ext.DomHelper.createDom({tag: 'div', children:tagDef.open}),
    		close = Ext.DomHelper.createDom({tag: 'div', children:tagDef.close}),
    		newValue;
        if(! this.isEmptySelectionIE(doc.selection)){
        	doc.execCommand('bold', false); //fake selection (in case htmleditor was not deselected)
        }
        this.lastSelectedRangeIE = null;
    	newValue = doc.body.innerHTML.replace(/<(\/?)strong>/ig, sep);
    	newValue = newValue.split(sep);
    	Ext.Array.insert(newValue, 1, open.innerHTML);
    	Ext.Array.insert(newValue, -1, close.innerHTML);
    	doc.body.innerHTML = newValue.join('');
		doc.selection.empty();
    },
    /**
     * On Leaving the editor, IE looses the selection so save it by marking it bold and storing the range 
     * @param {Object} ev
     * @param {Node} iframeEl
     */
    handleEditorBlur: function(ev,iframeEl) {
    	var doc = iframeEl.contentWindow.document,
    		sel = doc.getSelection ? doc.getSelection() : doc.selection;
    	if(! this.isEmptySelectionIE(sel)){
    		return;
    	}
    	doc.execCommand('bold', false); //fake selection
    	Ext.fly(doc.body).addCls('fakedsel');
    	this.lastSelectedRangeIE = sel.createRange();
    },
    /**
     * On entering the editor in IE, restore the selection 
     * @param {Object} ev
     * @param {Node} iframeEl
     */
    handleEditorFocus: function(ev,iframeEl) {
    	if(this.lastSelectedRangeIE){
    		var doc = iframeEl.contentWindow.document; 
    		this.lastSelectedRangeIE.select();
    		Ext.fly(doc.body).removeCls('fakedsel'); //remove bold 
    		doc.execCommand('bold', false); //remove fake selection
    	}
    	this.lastSelectedRangeIE = null;
    },
	/**
	 * @param {Selection} sel
	 * @returns {Boolean}
	 */
    isEmptySelectionIE: function(sel){
    	return sel.type.toLowerCase() != 'none';
    }
});
