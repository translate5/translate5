
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
    cls : 't5ttip',
    targetIframe: null, //target iframe which should be used for offset calculation
    messages: {
        deletedby: '#UT#Deleted by',
        insertedby: '#UT#Inserted by',
        history: '#UT#HISTORY',
        notrackchangesplugin: '#UT#TrackChanges found, but Plugin is not activated.',
        severity: '#UT#Gewichtung'
    },
    userStore: null,
    listeners : {
        beforeshow : 'onBeforeShow'
    },

    // Change content dynamically depending on which element triggered the show.
    onBeforeShow: function(tip) {
        var t = tip.triggerElement,
            fly = Ext.fly(t); 
        if(fly.hasCls('qmflag') || fly.hasCls('trackchanges') || fly.hasCls('internal-tag')) {
            // Don't show multiple ToolTips that overlap, but collect data into one single ToolTip
            return this.handleCollectedTooltip(t, tip);
        }
        return false;
    },
    
    constructor: function() {
        this.renderTo = Ext.getBody();
        this.callParent(arguments);
    },
    onTargetOver: function(e) {
        e.preventDefault(); //prevent title tags to be shown in IE
        this.callParent(arguments);
    },
    getAlignRegion: function() {
        //add the iframes offset, if we are configured to be relative to an iframe
        if(this.targetIframe) {
            this.targetOffset = this.targetIframe.getXY();
        }
        return this.callParent(arguments);
    },
    
    /**
     * Collect data for"common" ToolTip.
     * First the node itself is checked, but (if necessary) be careful to check for parent or child-Nodes, too.
     */
    handleCollectedTooltip: function(node, tip) {
        var me = this,
            fly = Ext.fly(node),
            result = '';
        // 'default'
        // add tooltip for qmFlag?
        if(fly.hasCls('qmflag')) {
            result = me.getQmFlagData(node);
        } else {
            var allQmFlagNodes = node.getElementsByClassName('qmflag');
            if (allQmFlagNodes.length == 1) {
                result = me.getQmFlagData(allQmFlagNodes[0]);
            } else {
                // a) there is no qmFlag-Node
                // b) there are many qmFlag-Nodes and we don't know which exactly the mouseover refers to
                result = '';
            }
        }
        // add tooltip for trackChanges?
        if(fly.hasCls('trackchanges')) {
            result += me.getTrackChangesData(node);
        } else if (node.parentNode && /(^|[\s])trackchanges([\s]|$)/.test(node.parentNode.className)) {
            result += me.getTrackChangesData(node.parentNode);
        }
        

        //Workaround to show the titles of the img tags always in fulltag mode
        if(fly.hasCls('internal-tag') && (fly.hasCls('tab')||fly.hasCls('space')||fly.hasCls('newline')||fly.hasCls('nbsp'))) {
            result = fly.down('span.short').getAttribute('title') + (result ? '<br>'+result : '');
        };
        tip.update(result);
        return !!result; //if there is no content for ttip, we return false to prevent the show of the tooltip
    },
    
    // ------------------------------------------------------------------
    // ----------------- get data ---------------------------------------
    // ------------------------------------------------------------------
    
    getQmFlagData: function(node) {
        var me = this, 
            qmtype,
            cache = Editor.mqmFlagTypeCache,
            meta = {sevTitle: me.messages.severity};
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
        // => For Tooltip:
        return '<b>'+meta.qmtype+'</b><br />'+meta.sevTitle+': '+meta.sev+meta.comment+'<br />';
    },
    getTrackChangesData: function(node) {
        var me = this,
            trackChanges,
            attrnameUserTrackingId,
            attrnameUsername,
            attrnameTimestamp,
            attrnameHistorylist,
            attrUserName,
            attrTimestamp,
            nodeAction = '',
            nodeUser = '',
            nodeDate = '',
            nodeHistory = '',
            userTrackingId,
            taskUserTrackingsStore;
        // TrackChanges-Plugin activated?
        if (!Editor.plugins.TrackChanges) {
            return me.messages.notrackchangesplugin;
        }
        trackChanges = Editor.plugins.TrackChanges.controller.Editor;
        taskUserTrackingsStore = Editor.data.task.userTracking();
        attrnameUserTrackingId = trackChanges.ATTRIBUTE_USERTRACKINGID;
        attrnameUsername = trackChanges.ATTRIBUTE_USERNAME;
        attrnameTimestamp = trackChanges.ATTRIBUTE_TIMESTAMP;
        attrnameHistorylist = trackChanges.ATTRIBUTE_HISTORYLIST;
        // What has been done (INS/DEL)?
        if (node.nodeName.toLowerCase() == trackChanges.NODE_NAME_INS) {
            nodeAction = me.messages.insertedby;
        } else if (node.nodeName.toLowerCase() == trackChanges.NODE_NAME_DEL) {
            nodeAction = me.messages.deletedby;
        } else {
            return;
        }
        // Who has done it?
        if (node.hasAttribute(attrnameUserTrackingId)) {
            userTrackingId = node.getAttribute(attrnameUserTrackingId);
            // returns username under consideration of anonymization
            attrUserName = taskUserTrackingsStore.getById(parseInt(userTrackingId));
            attrUserName = attrUserName ? attrUserName.get('userName') : '';
        } else if (node.hasAttribute(attrnameUsername)) {
            // (fallback for tasks before anonymizing was implemented)
            attrUserName = node.getAttribute(attrnameUsername);
        }
        nodeUser = attrUserName; // can be used just as it is
        // When?
        if (node.hasAttribute(attrnameTimestamp)) {
            attrTimestamp = node.getAttribute(attrnameTimestamp);
            if (Number(parseInt(attrTimestamp)) == attrTimestamp) { // TRANSLATE-1202: some older dates might be stored in millisecond-timestamp, others now in ISO
                nodeDate = Ext.Date.format(new Date(parseInt(attrTimestamp)),'Y-m-d H:i');
            } else {
                nodeDate = Ext.Date.format(new Date(attrTimestamp),'Y-m-d H:i');
            }
        }
        // History
        if (node.hasAttribute(attrnameHistorylist)) {
            nodeHistory += '<hr><b>'+me.messages.history+':</b><hr>';
            var historyItems = node.getAttribute(attrnameHistorylist).split(",");
            for(var i=0, len=historyItems.length; i < len; i++){
                var attrnameHistoryAction,
                    attrnameHistoryUsername,
                    historyItemTimestamp = historyItems[i],
                    historyItemAction,
                    historyItemUser,
                    historyItemDate,
                    historyUserTrackingId;
                // history-item: date
                if (Number(parseInt(historyItemTimestamp)) == historyItemTimestamp) { // TRANSLATE-1202: some older dates might be stored in millisecond-timestamp, others now in ISO
                    historyItemDate = Ext.Date.format(new Date(parseInt(historyItemTimestamp)),'Y-m-d H:i');
                } else {
                    historyItemDate = Ext.Date.format(new Date(historyItemTimestamp),'Y-m-d H:i');
                }
                if (Number(parseInt(historyItemTimestamp)) != historyItemTimestamp) { 
                    historyItemTimestamp = Ext.Date.format(new Date(historyItemTimestamp), 'time'); // TRANSLATE-1202, but attribute-name would be invalid using ISO => still uses millisecond-timestamp
                }
                // history-item: user
                attrnameHistoryUsername = trackChanges.ATTRIBUTE_USERTRACKINGID + trackChanges.ATTRIBUTE_HISTORY_SUFFIX + historyItemTimestamp;
                if (node.hasAttribute(attrnameHistoryUsername)) {
                    historyUserTrackingId = node.getAttribute(attrnameHistoryUsername);
                    // returns username under consideration of anonymization
                    historyItemUser = taskUserTrackingsStore.getById(parseInt(historyUserTrackingId));
                    historyItemUser = historyItemUser ? historyItemUser.get('userName') : '';
                } else if (node.hasAttribute(attrnameUsername)) {
                    // (fallback for tasks before anonymizing was implemented)
                    attrnameHistoryUsername = trackChanges.ATTRIBUTE_USERNAME + trackChanges.ATTRIBUTE_HISTORY_SUFFIX + historyItemTimestamp;
                    historyItemUser = node.getAttribute(attrnameHistoryUsername);
                }
                // history-item: action
                attrnameHistoryAction = trackChanges.ATTRIBUTE_ACTION + trackChanges.ATTRIBUTE_HISTORY_SUFFIX + historyItemTimestamp;
                historyItemAction = node.getAttribute(attrnameHistoryAction);
                if (historyItemAction.toLowerCase() == trackChanges.NODE_NAME_INS) {
                    historyItemAction = me.messages.insertedby;
                } else if (historyItemAction.toLowerCase() == trackChanges.NODE_NAME_DEL) {
                    historyItemAction = me.messages.deletedby;
                }
                // => history-item:
                nodeHistory += '<b>'+historyItemAction+'</b><br>'+historyItemUser+'<br>'+historyItemDate+'<hr>';
            }
        }
        // => For Tooltip:
        return '<b>'+nodeAction+'</b><br>'+nodeUser+'<br>'+nodeDate+'<br>'+ nodeHistory;
    },
    
    // ------------------------------------------------------------------
    
    /**
     * Override of default setTarget, only change see below.
     * Must be respected on ExtJS updates!
     */
    setTarget: function(target) {
        var me = this,
            listeners;
 
        if (me.targetListeners) {
            
            //FIXME: Fix for the bug in internet explorer
            //http://jira.translate5.net/browse/TRANSLATE-1086
        	//same problem with different error log under edge
        	//https://jira.translate5.net/browse/TRANSLATE-2037
            if(!Ext.isIE && !Ext.isEdge){
            	me.targetListeners.destroy();
            }
            me.targetListeners=null;
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