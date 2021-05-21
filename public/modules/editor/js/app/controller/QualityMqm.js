
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

/**
 * Encapsulates all logic for MQM tags processing
 * The MQM tags adder will be added in the segments editor east panel in the MetaPanel
 */
Ext.define('Editor.controller.QualityMqm', {
    extend : 'Ext.app.Controller',
    views: ['ToolTip'],
    refs:[{
    	ref : 'addMenuFirstLevel',
    	selector : 'qualityMqmFieldset button > menu'
    },{
    	ref : 'mqmFieldset',
    	selector : 'qualityMqmFieldset'
    },{
	    ref : 'segmentGrid',
	    selector : '#segmentgrid'
    },{
	    ref : 'metaInfoForm',
	    selector : '#metaInfoForm'
    },{
        ref: 'metaSevCombo',
        selector: '#metapanel combobox[name="mqmseverity"]'
    }],
    listen: {
        controller: {
            '#Editor': {
                'assignMQMTag': 'handleAddMqmKey'
            },
            '#Editor.$application': {
                editorConfigLoaded:'onEditorConfigLoaded'
            }
        },
        component: {
            'qualityMqmFieldset menuitem': {
                click: 'handleAddMqmClick'
            },
            '#metaInfoForm': {
                afterrender: 'handleInitEditor'
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
        var me = this,
            isControllerActive = app.getTaskConfig('autoQA.enableMqmTags');
        // this controller is active when enableMqmTags is set
        me.setActive(isControllerActive);
    },
    
    handleInitEditor: function() {
        this.initFieldSet();
        var combo = this.getMetaSevCombo(),
            sevStore = Ext.create('Ext.data.Store', {
                fields: ['id', 'text'],
                storeId: 'Severities',
                data: Editor.data.task.getMqmSeverities()
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
     * TODO FIXME: The MQM panel should be an own view with & this class here it's view controller
     */
    initFieldSet: function() {
    	if(!Editor.data.task.hasMqm()){
    		return;
    	}
    	var mpForm = this.getMetaInfoForm(),
    		pos = mpForm.items.findIndex('itemId', 'segmentQm'); // MQM will be added after QM panel
    	mpForm.insert(pos, {xtype: 'qualityMqmFieldset', menuConfig: this.getMenuConfig()});
    },
    /**
     * generates the config menu tree for QM Flag Menu
     * @returns
     */
    getMenuConfig: function() {
		Editor.mqmFlagTypeCache = {};
		var me = this,
			cache = Editor.mqmFlagTypeCache,
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
		return iterate(Editor.data.task.getMqmCategories());
	},
    /**
     * Inserts the QM Issue Tag in the Editor by key shortcut
     * @param key
     */
    handleAddMqmKey: function(key) {
        var me = this,
            found = false,
            menuitem = me.getMqmFieldset().down('menuitem[qmid='+key+']');
        
        if (menuitem) {
            me.handleAddMqmClick(menuitem);
        }
    },
    /**
     * Inserts the QM Issue Tag in the Editor
     * @param menuitem
     */
    handleAddMqmClick: function(menuitem) {
        var me = this,
            sev = me.getMqmFieldset().down('combo[name="mqmseverity"]');
            commentField = me.getMqmFieldset().down('textfield[name="mqmcomment"]'),
            format = Ext.util.Format,
            comment = format.stripTags(commentField.getValue()).replace(/[<>"'&]/g,'');
            //@todo when we are going to make qm subsegments editable, we should improve the handling of html tags in comments.
            //since TRANSLATE-80 we are stripping the above chars, 
            //because IE did not display the segment content completly with qm subsegments containing these chars
            //WARNING: if we allow tags and special chars here, we must fix CSV export too! See comment in export/FileParser/Csv.php
                
        me.addMqmFlagToEditor(menuitem.qmid, comment, sev.getValue());
        sev.reset();
        commentField.reset();
        me.addMqmHistory(menuitem);
    },
    /**
     * Inserts the QM Issue IMG Tags around the text selection in the editor 
     * @param {Number} qmid Qm Issue ID
     * @param {String} comment 
     * @param {String} sev Severity ID
     * @return {Boolean}
     */
    addMqmFlagToEditor: function(qmid, comment, sev){
        var editor = this.getSegmentGrid().editingPlugin.editor.mainEditor,
            tagDef;
        // MQM tags must not be added in DEL tags, so there must be an error message for the user when his MQM selection ends in a delete tag.
        if(! this.fireEvent('beforeInsertMqmTag')) {
            return;
        }
        tagDef = this.insertMqmFlag(editor,qmid, comment, sev);
        this.fireEvent('afterInsertMqmTag',tagDef); // Inserted tags are marked with INS-trackChange-markers.
        return true;
    },
    /**
     * QM IMG Tag Inserter for for Modern Browsers (= not IE) 
     * @param {Editor.view.segments.HtmlEditor} editor
     * @param {Number} qmid Qm Issue ID
     * @param {String} comment 
     * @param {String} sev Severity ID
     * @returns {Object}
     */
    insertMqmFlag: function(editor, qmid, comment, sev){
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
    		uniqid = Ext.id(), // retrieves something like ext-1234. This is crucial to distinguish these IDs from real database quality id's
    		config = function(open){
    		return {
    			tag: 'img', 
    			id: 'qm-image-'+(open ? 'open' : 'close')+'-'+uniqid, 
    			'data-comment': (comment ? comment : ""), 
    			'data-t5qid': uniqid, 
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
    addMqmHistory: function(menuitem) {
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
    }
});
