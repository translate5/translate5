
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
 * Own Tooltip, bindable to iframes per boundFrame option
 * 
 * @class Editor.view.ToolTip
 * @extends Ext.tip.ToolTip
 */
Ext.define('Editor.view.ToolTip', {
    extend : 'Ext.tip.ToolTip',
    //enable own ToolTips only for the following img classes 
    delegate : '.ownttip', // accepts only simple selectors (no commas) so
    // define a own tooltip class
    strings: {
        deletedby: '#UT#Deleted by',
        insertedby: '#UT#Inserted by',
        history: '#UT#HISTORY',
        severity: '#UT#Gewichtung'
    },
    userStore: null,
    listeners : {
        // Change content dynamically depending on which element triggered
        // the show.
        beforeshow : function(tip) {
            var t = tip.triggerElement,
                fly = Ext.fly(t); 
            if(fly.hasCls('qmflag')) {
                this.handleQmFlag(t, tip);
            } else if (fly.hasCls('trackchanges')) {
                this.handleTrackChanges(t, tip);
            }
            //else if hasClass for other ToolTip Types
        }
    },

    constructor: function() {
        this.renderTo = Ext.getBody();
        this.callParent(arguments);
    },
    onTargetOver: function(e) {
        e.preventDefault(); //prevent title tags to be shown in IE
        this.callParent(arguments);
    },
    
    handleQmFlag: function(t, tip) {
        var me = this,
            qmFlagData = me.getQmFlagData(t),
            meta = {
                qmFlag: qmFlagData
            }
        // add tooltip for trackChanges?
        if (/(^|[\s])trackchanges([\s]|$)/.test(t.parentNode.className)) {
            meta.trackChanges = me.getTrackChangesData(t.parentNode);
        } else {
            meta.trackChanges = '';
        }
        if(!me.qmflagTpl) {
            me.qmflagTpl = new Ext.Template('{qmFlag}{trackChanges}');
            me.qmflagTpl.compile();
        }
        tip.update(me.qmflagTpl.apply(meta));		
    },
    getQmFlagData: function(node) {
        var me = this, 
            qmtype,
            cache = Editor.qmFlagTypeCache,
            meta = {sevTitle: me.strings.severity};
        qmtype = node.className.match(/qmflag-([0-9]+)/);
        if(qmtype && qmtype.length > 1) {
            meta.cls = node.className.split(' ');
            meta.sev = Ext.StoreMgr.get('Severities').getById(meta.cls.shift());
            meta.sev = meta.sev ? meta.sev.get('text') : '';
            meta.qmid = qmtype[1];
            meta.comment = Ext.fly(node).getAttribute('data-comment');
            meta.qmtype = cache[meta.qmid] ? cache[meta.qmid] : 'Unknown Type'; //impossible => untranslated
        }
        if (meta.comment == null) {
            meta.comment = '';
        } else {
            meta.comment = '<br />'+ meta.comment
        }
        // For Tooltip:
        return '<b>'+meta.qmtype+'</b><br />'+meta.sevTitle+': '+meta.sev+meta.comment+'<br />';
    },
    
    handleTrackChanges: function(node, tip) {
        var me = this,
            trackChangesData = me.getTrackChangesData(node),
            tplData = {
                trackChanges: trackChangesData
            };
        // add tooltip for qmFlag?
        var allQmFlagNodes = node.getElementsByClassName('qmflag');
        if (allQmFlagNodes.length == 1) {
            tplData.qmFlag = me.getQmFlagData(allQmFlagNodes[0]);
        } else {
            // a) there is no qmFlag-Node
            // b) there are many qmFlag-Nodes and we don't know which exactly the mouseover refers to
            tplData.qmFlag = '';
        }
        if(!me.trackChangesTpl) {
            me.trackChangesTpl = new Ext.Template('{qmFlag}{trackChanges}');
            me.trackChangesTpl.compile();
        }
        tip.update(me.trackChangesTpl.apply(tplData));
    },
    getTrackChangesData: function(node) {
        var me = this,
            trackChanges = Editor.plugins.TrackChanges.controller.Editor,
            attrnameUsername = trackChanges.ATTRIBUTE_USERNAME,
            attrnameTimestamp = trackChanges.ATTRIBUTE_TIMESTAMP,
            attrnameHistorylist = trackChanges.ATTRIBUTE_HISTORYLIST,
            attrnameHistoryActionPrefix = trackChanges.ATTRIBUTE_ACTION + trackChanges.ATTRIBUTE_HISTORY_SUFFIX,
            attrnameHistoryUsernamePrefix = trackChanges.ATTRIBUTE_USERNAME + trackChanges.ATTRIBUTE_HISTORY_SUFFIX,
            attrUserName,
            attrTimestamp,
            nodeAction = '',
            nodeUser = '',
            nodeDate = '',
            nodeHistory = '';
        // What has been done (INS/DEL)?
        if (node.nodeName.toLowerCase() == trackChanges.NODE_NAME_INS) {
            nodeAction = me.strings.insertedby;
        } else if (node.nodeName.toLowerCase() == trackChanges.NODE_NAME_DEL) {
            nodeAction = me.strings.deletedby;
        } else {
            return;
        }
        // Who has done it?
        if (node.hasAttribute(attrnameUsername)) {
            attrUserName = node.getAttribute(attrnameUsername);
            nodeUser = attrUserName; // can be used just as it is
        }
        // When?
        if (node.hasAttribute(attrnameTimestamp)) {
            attrTimestamp = parseInt(node.getAttribute(attrnameTimestamp));
            nodeDate = Ext.Date.format(new Date(attrTimestamp),'Y-m-d H:i');
        }
        // History
        if (node.hasAttribute(attrnameHistorylist)) {
            nodeHistory += '<hr><b>'+me.strings.history+':</b><hr>';
            var historyItems = node.getAttribute(attrnameHistorylist).split(",");
            for(var i=0, len=historyItems.length; i < len; i++){
                var historyItemTimestamp = historyItems[i],
                    historyItemAction = node.getAttribute(attrnameHistoryActionPrefix + historyItemTimestamp),
                    historyItemUser   = node.getAttribute(attrnameHistoryUsernamePrefix + historyItemTimestamp),
                    historyItemDate   = Ext.Date.format(new Date(parseInt(historyItemTimestamp)),'Y-m-d H:i');
                if (historyItemAction.toLowerCase() == trackChanges.NODE_NAME_INS) {
                    historyItemAction = me.strings.insertedby;
                } else if (historyItemAction.toLowerCase() == trackChanges.NODE_NAME_DEL) {
                    historyItemAction = me.strings.deletedby;
                }
                nodeHistory += '<b>'+historyItemAction+'</b><br>'+historyItemUser+'<br>'+historyItemDate+'<hr>';
            }
        }
        // => For Tooltip:
        return '<b>'+nodeAction+'</b><br>'+nodeUser+'<br>'+nodeDate+'<br>'+ nodeHistory;
    },
    
    /**
     * Override of default setTarget, only change see below.
     * Must be respected on ExtJS updates!
     */
    setTarget: function(target) {
        var me = this,
            listeners;
 
        if (me.targetListeners) {
            me.targetListeners.destroy();
        }
 
        if (target) {
            me.target = target = Ext.get(target.el || target);
            listeners = {
                mouseover: 'onTargetOver',
                mouseout: 'onTargetOut',
                mousemove: 'onMouseMove',
                tap: 'onTargetTap',
                scope: me,
                destroyable: true,
                delegated: false    //this is the only change in comparision to the original code
            };
 
            me.targetListeners = target.on(listeners);
        } else {
            me.target = null;
        }
    }
    
});