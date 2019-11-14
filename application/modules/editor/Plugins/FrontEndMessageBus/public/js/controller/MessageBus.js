
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
 * @class Editor.plugins.ChangeLog.controller.Changelog
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.FrontEndMessageBus.controller.MessageBus', {
    extend: 'Ext.app.Controller',
    segmentUsageData: null,
    refs: [{
        ref : 'segmentGrid',
        selector : '#segmentgrid'
    }],
    listen: {
        messagebus: {
            '#translate5': {
                reconnect: 'onReconnect',
                triggerReload: 'onTriggerReload',
            },
            '#translate5 instance': {
                pong: 'onMessageBusPong',
                resyncSession: 'onResyncSession'
            },
            '#translate5 task': {
                segmentselect:  'onSegmentSelect',
                segmentOpenAck: 'onSegmentOpenAck',
                segmentOpenNak: 'onSegmentOpenNak',
                segmentLocked: 'onSegmentLocked',
                triggerReload: 'onTriggerTaskReload',
            }
        },
        store: {
            '#Segments': {
                prefetch: 'onSegmentPefetch'
            }
        },
        component: {
            '#segmentgrid autoStateColumn' : {
                initOtherRenderers: 'injectToolTipInfo'
            },
            '#segmentgrid' : {
                renderrowclass: 'onSegmentRender',
                itemclick: 'clickSegment',
                beforestartedit: 'enterSegment',
                canceledit: 'leaveSegment',
            }
        }
    },
    init: function(){
        var me = this,
            conf = Editor.data.plugins.FrontEndMessageBus,
            url = [];
        me.callParent(arguments);
        
        if(!conf) {
            Ext.Logger.warn("MessageBus WebSocket communication deactivated due missing configuration of the socket server.");
            return;
        }
        url.push(conf.socketServer.schema, '://');
        url.push(conf.socketServer.httpHost || window.location.hostname);
        url.push(':', conf.socketServer.port, conf.socketServer.route);
        
        Ext.Ajax.setDefaultHeaders(Ext.apply({
            'X-Translate5-MessageBus-ConnId': conf.connectionId
        }, Ext.Ajax.getDefaultHeaders()));
        
        me.bus = new Editor.util.messageBus.MessageBus({
            id: 'translate5',
            url: url.join(''),
            params: {
                // the serverId ensures that we communicate with the correct instance
                serverId: Editor.data.app.serverId,
                // additional security comes from the sessionId, which must match
                // authentication by passing the session id to the server
                sessionId: Ext.util.Cookies.get(Editor.data.app.sessionKey),
                //needed to identify different browser windows with same sessionId persistent over reconnections
                connectionId: conf.connectionId
            }
        });
        me.segmentUsageData = new Ext.util.Collection();
    },
    /**
     * On connection reconnect we send a ping to the server, if the reconnection was because of a server crash, 
     * the ping resyncs the session data from translate5
     * @param {Editor.util.messageBus.MessageBus} bus
     */
    onReconnect: function(bus){
        bus.send('instance', 'ping');
        var me = this,
            grid = me.getSegmentGrid(),
            sel;

        if(grid && (sel = grid.getSelectionModel().getSelection()) && sel.length > 0) {
            me.clickSegment(null, sel[0]);
        }

    },
    enterSegment: function(plugin, context) {
        //if we got the segment, we can proceed with segment opening
        if(plugin.enterSegmentAcked) {
            return;
        }
        var me = this;
        me.bus.send('task', 'segmentEditRequest', [context[0].get('taskGuid'), context[0].get('id')]);
        me.editorPlugin = plugin;
        me.currentSegmentEditContext = context;
        
            return false;
    },
    leaveSegment: function(plugin, context) {
        this.bus.send('task', 'segmentLeave', [context.record.get('taskGuid'), context.record.get('id')]);
    },
    /**
     * If we receive a segment edit ACK from the server, we open it  
     */
    onSegmentOpenAck: function() {
        var plugin = this.editorPlugin, 
            context = this.currentSegmentEditContext;
        if(plugin && context) {
            plugin.enterSegmentAcked = true;
            plugin.startEdit.apply(plugin, context);
            plugin.enterSegmentAcked = false;
        }
    },
    onSegmentOpenNak: function() {
        console.log('onSegmentOpenNak', arguments);
    },
    onSegmentLeave: function(data) {
        this.segmentUnlock(this.getSegmentMeta(data.segmentId), data.connectionId);
    },
    onSegmentSave: function(data) {
        var segment,
            grid = this.getSegmentGrid();
        if(segment = grid.store.getById(data.segmentId)) {
            console.log("reload updated segment", segment.data);
            this.segmentUnlock(this.getSegmentMeta(data.segmentId), data.connectionId, true);
            segment.load();
        }
    },
    onSegmentLocked: function(data) {
        var me = this,
            selectedId = data.segmentId, 
            byUserGuid = data.userGuid,
            connectionId = data.connectionId,
            grid = me.getSegmentGrid(),
            meta = me.getSegmentMeta(selectedId),
            segment;
    
        //remove previous edit lock from that locking connection
        me.segmentUsageData.each(function(meta) {
            me.segmentUnlock(meta, connectionId);
            if(meta.id !== selectedId) {
                me.removeUnused(meta);
            }
        });
        
        //add user id as selector to the segment
        meta.editingConn = connectionId;
        meta.editingUser = byUserGuid;
        
        //add color mark to current segment
        if(segment = grid.store.getById(selectedId)) {
            me.markSegmentEdited(segment);
        }
    },
    markSegmentEdited: function(segment) {
        segment.set('editable', false);
        segment.set('autoStateId', Editor.data.segments.autoStates.EDITING_BY_USER);
        segment.commit(false); //trigger render
    },
    segmentUnlock: function(meta, connectionId, keepRender) {
        var me = this, segment,
            grid = me.getSegmentGrid();

        if(meta.editingConn !== connectionId) {
            return;
        }
        meta.editingConn = false;
        meta.editingUser = false;
        //remove color mark from previous segment
        if(!keepRender && (segment = grid.store.getById(meta.id))) {
            segment.set('editable', segment.getPrevious('editable'));
            segment.set('autoStateId', segment.getPrevious('autoStateId'));
            segment.commit(false); //trigger render
            console.log("segment unlocked", segment.data);
        }
    },
    onMessageBusPong: function() {
        Ext.Logger.info('Received a pong on my ping');
    },
    /**
     * Returns or creates a segmentMeta object
     * @param {Number} segmentId the segmentId to which the meta entry is created
     * @return {Object} the meta data object to the segment
     */
    getSegmentMeta: function(segmentId) {
        var map = this.segmentUsageData,
            meta = map.get(segmentId);
        if(Ext.isDefined(meta)) {
            return meta;
        }
        return map.add({
            id: segmentId,
            selectingConns: {},
            editingUser: false,
            editingConn: false
        });
    },
    clickSegment: function(view, segment) {
        //taskGuid is necessary for the future when we can open multiple tasks in one session
        this.bus.send('task', 'segmentClick', [segment.get('taskGuid'), segment.get('id')]);
    },
    /**
     * Another user has selected a segment
     * @param {Object} data information about the selected segment
     */
    onSegmentSelect: function(data) {
        var me = this,
            selectedId = data.segmentId, 
            byUserGuid = data.userGuid,
            connectionId = data.connectionId,
            grid = me.getSegmentGrid(),
            meta = me.getSegmentMeta(selectedId),
            segment;
        
        //remove previous selection
        me.segmentUsageData.each(function(meta) {
            if(Ext.isDefined(meta.selectingConns[connectionId])) {
                delete meta.selectingConns[connectionId];
                //remove color mark from previous segment
                if(segment = grid.store.getById(meta.id)) {
                    segment.commit(false); //trigger render
                }
            }
            if(meta.id !== selectedId) {
                me.removeUnused(meta);
            }
        });
        
        //add user id as selector to the segment
        meta.selectingConns[connectionId] = byUserGuid;
        
        //add color mark to current segment
        if(segment = grid.store.getById(selectedId)) {
            segment.commit(false); //trigger render
        }
    },
    removeUnused: function(meta) {
        if(meta && Ext.Object.isEmpty(meta.selectingConns) && !meta.editingConn && !meta.editingUser) {
            this.segmentUsageData.remove(meta);
        }
    },
    onSegmentPefetch: function(store, segments) {
        var me = this;
        Ext.Array.each(segments, function(segment){
            var meta = me.segmentUsageData.get(segment.get('id'));
            if(meta && meta.editingConn && meta.editingUser) {
                me.markSegmentEdited(segment);
            }
            
        });
    },
    onSegmentRender: function(rowClass, segment, index, store) {
        //modify rowClass here
        var me = this, 
            meta = me.segmentUsageData.get(segment.get('id')),
            tracking = Editor.data.task.userTracking(),
            trackedUser;
        
        //if we do not have a meta entry to the rendering segment, nothing to be rendered then
        if(!Ext.isDefined(meta)) {
            return;
        }
        //check if segment is edited by somebody
        if(meta.editingConn) {
            trackedUser = tracking.getAt(tracking.findExact('userGuid', meta.editingUser));
            
            if(trackedUser) {
                rowClass.push('other-user-edit'); 
                rowClass.push('usernr-'+trackedUser.get('taskOpenerNumber'));
                return false; 
            }
            return; //if the segment is edited, this should be visualized and not the following selection stuff
        }
        //check if segment is selected by somebody and colorize it, but only if it is not edited. 
        if(meta.selectingConns) {
            Ext.Object.each(meta.selectingConns, function(connectionId, userGuid) {
                trackedUser = tracking.getAt(tracking.findExact('userGuid', userGuid));
                if(trackedUser) {
                    rowClass.push('other-user-select');
                    rowClass.push('usernr-'+trackedUser.get('taskOpenerNumber'));
                    return false; 
                }
            });
        }
    },
    onResyncSession: function() {
        var sessionId = Ext.util.Cookies.get(Editor.data.app.sessionKey);
        Ext.Ajax.request({
            url: Editor.data.restpath+'session/'+sessionId+'/resync/operation',
            method: "POST",
            scope: this,
            success: function(response){
                Ext.Logger.info('Session resync successfull');
            }, 
            failure: function(response){
                Ext.Logger.warn('Session resync failed!');
                Editor.app.getController('ServerException').handleException(response);
            }
        })
    },
    onOpenEditorViewport: function() {
        this.bus.send('task', 'resyncTask', [segment.get('taskGuid')]);
    },
    onTriggerTaskReload: function() {
        Ext.Logger.info('Task reload triggered');
        Editor.data.task && Editor.data.task.load();
    },
    onTriggerReload: function() {
        //idea is to reload the store given as storeid, and optionally reload only one record of the store, given by id as optional second parameter.
        Ext.Logger.warn("generic trigger reload NOT implemented yet"); 
    },
    /**
     * Injects the selecting users into the segmentNrInTask and AutoState tooltip
     */
    injectToolTipInfo: function(otherRenderer) {
        var me = this,
            tracking = Editor.data.task.userTracking();
        otherRenderer._selectedUsers = {
            text: 'Ausgewählt von', 
            renderer: function(noop, noop2, record) {
                var meta = me.segmentUsageData.get(record.get('id')),
                    result = [], trackedUser;
                //if we have a meta entry to the rendering segment, check selections
                if(Ext.isDefined(meta) && meta.selectingConns) {
                    Ext.Object.each(meta.selectingConns, function(connectionId, userGuid) {
                        trackedUser = tracking.getAt(tracking.findExact('userGuid', userGuid));
                        trackedUser && result.push(trackedUser.get('userName'));
                    });
                }
                return result.join('<br>');
            },
            scope: me
        };
        otherRenderer._editingUser = {
            text: 'aktueller Bearbeiter', 
            renderer: function(noop, noop2, record) {
                var meta = me.segmentUsageData.get(record.get('id')),
                result = [], trackedUser;
                //if we have a meta entry to the rendering segment, check selections
                if(Ext.isDefined(meta) && (trackedUser = tracking.getAt(tracking.findExact('userGuid', meta.editingUser)))) {
                    return trackedUser.get('userName');
                }
                return result.join('<br>');
            },
            scope: me
        };
    }
});
