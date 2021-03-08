
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
    },{
	    ref : 'segmentGrid',
	    selector : '#segmentgrid'
    },{
	    ref : 'metaInfoForm',
	    selector : '#metaInfoForm'
    },{
        ref: 'metaSevCombo',
        selector: '#metapanel combobox[name="qmsubseverity"]'
    }],
    /**
     * Deactivated this feature for IE, see EXT6UPD-111
     */
    constructor: function() {
        this.disableIE();
        this.callParent(arguments);
    },
    disableIE: function() {
        if(!Ext.isIE) {
            return;
        }
        var msg = function() {
                Editor.MessageBox.addInfo('MQM Issues can not be used with this Version of Internet Explorer!');
            };
        this.config.listen = {
            component: {
                '#segmentgrid': {
                    afterrender: msg
                },
                '#segmentgrid #qmsummaryBtn': {
                    click: msg
                }
            }
        };
    },
    listen: {
        controller: {
            '#Editor': {
                'assignMQMTag': 'handleAddQmFlagKey'
            },
            '#Editor.$application': {
                editorConfigLoaded:'onEditorConfigLoaded'
            }
        },
        component: {
            '#segmentgrid #qmsummaryBtn': {
                click:'showQmSummary'
            },
            'qmSubsegmentsFlagFieldset menuitem': {
                click: 'handleAddQmFlagClick'
            },
            '#metaInfoForm': {
                afterrender: 'handleInitEditor'
            },
            'segmentsHtmleditor': {
                afterinitframedoc: 'initIframeDoc'
            }
        }
    },
    strings: {
    	buttonTooltip10: '#UT# (ALT+{0})',
    	buttonTooltip20: '#UT# (ALT+SHIFT+{0})'
    },
    
    /***
     * After task config load event handler.
     */
    onEditorConfigLoaded:function(app, task){
        var me=this,
            isControllerActive = app.getTaskConfig('autoQA.enableMqmTags');
        //this controller is active when enableMqmTags is set
        me.setActive(isControllerActive);
    },
    
    handleInitEditor: function() {
        this.initFieldSet();
        var combo = this.getMetaSevCombo(),
            sevStore = Ext.create('Ext.data.Store', {
                fields: ['id', 'text'],
                storeId: 'Severities',
                data: Editor.data.task.get('qmSubSeverities')
            });
        if(!combo){
            return;
        }
        //bindStore dynamically to combo:
        combo.bindStore(sevStore);
        combo.setValue(sevStore.getAt(0).get('id'));
        combo.resetOriginalValue();
    },
    /**
     * initialises the QM SubSegment Fieldset in the MetaPanel
     */
    initFieldSet: function() {
    	if(!Editor.data.task.hasQmSub()){
    		return;
    	}
    	var mpForm = this.getMetaInfoForm(),
    		pos = mpForm.items.findIndex('itemId', 'metaQm');
    	mpForm.insert(pos, {xtype: 'qmSubsegmentsFlagFieldset', menuConfig: this.getMenuConfig()});
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
			var result, 
			    text, id;
			if(Ext.isArray(node)){
				result = [];
				Ext.each(node, function(item) {
					result.push(iterate(item));
				});
				return result;
			}
			
			text = node.text;
			if(node.id <= 10) {
			    id = node.id == 10 ? 0 : node.id;
			    text += Ext.String.format(me.strings.buttonTooltip10, id);
			}
			else if(node.id > 10 && node.id <= 20) {
			    id = node.id == 20 ? 0 : (node.id - 10);
			    text += Ext.String.format(me.strings.buttonTooltip20, id);
			}
			
			result = {
				text: text,
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
        if(Ext.isIE){
            editor.iframeEl.on('beforedeactivate', this.handleEditorBlur, this);
            editor.iframeEl.on('focus', this.handleEditorFocus, this);
        }
    },
    /**
     * displays the QM Summary Window
     */
    showQmSummary: function() {
        this.getWindow().show();
    },
    /**
     * Inserts the QM Issue Tag in the Editor by key shortcut
     * @param key
     */
    handleAddQmFlagKey: function(key) {
        var me = this,
            found = false,
            menuitem = me.getQmFieldset().down('menuitem[qmid='+key+']');
        
        if (menuitem) {
            me.handleAddQmFlagClick(menuitem);
        }
    },
    /**
     * Inserts the QM Issue Tag in the Editor
     * @param menuitem
     */
    handleAddQmFlagClick: function(menuitem) {
        var me = this,
            sev = me.getQmFieldset().down('combo[name="qmsubseverity"]');
            commentField = me.getQmFieldset().down('textfield[name="qmsubcomment"]'),
            format = Ext.util.Format,
            comment = format.stripTags(commentField.getValue()).replace(/[<>"'&]/g,'');
            //@todo when we are going to make qm subsegments editable, we should improve the handling of html tags in comments.
            //since TRANSLATE-80 we are stripping the above chars, 
            //because IE did not display the segment content completly with qm subsegments containing these chars
            //WARNING: if we allow tags and special chars here, we must fix CSV export too! See comment in export/FileParser/Csv.php
                
        me.addQmFlagToEditor(menuitem.qmid, comment, sev.getValue());
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
        var editor = this.getSegmentGrid().editingPlugin.editor.mainEditor,
            tagDef;
        // MQM tags must not be added in DEL tags, so there must be an error message for the user when his MQM selection ends in a delete tag.
        if(! this.fireEvent('beforeInsertMqmTag')) {
            return;
        }
        if(Ext.isIE) { //although >IE11 knows ranges we can't use it, because of an WrongDocumentError
            tagDef = this.insertQmFlagsIE(editor,qmid, comment, sev);
        } else {
            tagDef = this.insertQmFlagsH5(editor,qmid, comment, sev);
        }
        this.fireEvent('afterInsertMqmTag',tagDef); // Inserted tags are marked with INS-trackChange-markers.
        this.lastSelectedRangeIE = null;
        return true;
    },
    /**
     * QM IMG Tag Inserter for for HTML5 Browsers (= not IE) 
     * @param {Editor.view.segments.HtmlEditor} editor
     * @param {Number} qmid Qm Issue ID
     * @param {String} comment 
     * @param {String} sev Severity ID
     * @returns {Object}
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
		rangeEnd.insertNode(close);
		rangeBegin.insertNode(open);
		doc.getSelection().removeAllRanges();
		rangeEnd.collapse(false);
		doc.getSelection().addRange(rangeEnd);
		return tagDef;
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
    	if(menuitem.parentMenu && !menuitem.parentMenu.parentMenu) {
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
     * QM IMG Tag Inserter for IE, which don't support ranges in older versions
     * @see http://stackoverflow.com/questions/1470932/ie8-iframe-designmode-loses-selection
     * @see http://www.webmasterworld.com/javascript/3820483.htm
     * 
     * for IE11 range / selection things were working, but we are getting issues about different documents on inserting the DomNode
     * so we keep on our "strong" replacing method for IE11
     * @see http://stackoverflow.com/questions/2284356/cant-appendchild-to-a-node-created-from-another-frame
     * 
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
    		sel = doc.getSelection ? doc.getSelection() : doc.selection,
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
    	sel.removeAllRanges ? sel.removeAllRanges() : sel.empty();
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
    	return sel.type ? sel.type.toLowerCase() != 'none' : sel.isCollapsed;
    }
});
