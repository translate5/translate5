
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
                reconnect: 'onReconnect'
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
            }
        },
        component: {
            '#segmentgrid autoStateColumn' : {
                initOtherRenderers: 'injectToolTipInfo'
            },
            '#segmentgrid' : {
                renderrowclass: 'onSegmentUpdate',
                itemclick: 'clickSegment',
                beforestartedit: 'enterSegment',
                canceledit: 'leaveSegment',
                edit: 'leaveSegment'
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
        // the serverId ensures that we communicate with the correct instance, additional security comes from the sessionId, which must match
        // authentication by passing the session id to the server
        url.push('?serverId=', Editor.data.app.serverId, '&sessionId=', Ext.util.Cookies.get(Editor.data.app.sessionKey));
        
        me.bus = new Editor.util.messageBus.MessageBus({
            id: 'translate5',
            url: url.join('')
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
    onSegmentLocked: function(data) {
        var me = this,
            selectedId = data.segmentId, 
            byUserGuid = data.userGuid,
            sessionHash = data.sessionHash,
            grid = me.getSegmentGrid(),
            meta = me.getSegmentMeta(selectedId),
            segment;
    
        //remove previous edit lock from that locking connection
        me.segmentUsageData.each(function(meta) {
            if(meta.editingConn === sessionHash) {
                meta.editingConn = false;
                meta.editingUser = false;
                //remove color mark from previous segment
                if(segment = grid.store.getById(meta.id)) {
                    segment.set('editable', segment.getPreviousValue('editable'));
                    segment.set('autoStateId', segment.getPreviousValue('autoStateId'));
                    segment.commit(false); //trigger render
                }
            }
        });
        
        //add user id as selector to the segment
        meta.editingConn = sessionHash;
        meta.editingUser = byUserGuid;
        
        //add color mark to current segment
        if(segment = grid.store.getById(selectedId)) {
            segment.set('editable', false);
            segment.set('autoStateId', Editor.data.segments.autoStates.EDITING_BY_USER);
            segment.commit(false); //trigger render
        }
    },
    leaveSegment: function() {
        console.log('leaveSegment', arguments);
        //me.bus.send();
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
            selectingSessions: {},
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
            sessionHash = data.sessionHash,
            grid = me.getSegmentGrid(),
            meta = me.getSegmentMeta(selectedId),
            segment;
        
        //remove previous selection
        me.segmentUsageData.each(function(meta) {
            if(Ext.isDefined(meta.selectingSessions[sessionHash])) {
                delete meta.selectingSessions[sessionHash];
                //remove color mark from previous segment
                if(segment = grid.store.getById(meta.id)) {
                    segment.commit(false); //trigger render
                }
            }
        });
        
        //add user id as selector to the segment
        meta.selectingSessions[sessionHash] = byUserGuid;
        
        //add color mark to current segment
        if(segment = grid.store.getById(selectedId)) {
            segment.commit(false); //trigger render
        }
    },
    onSegmentUpdate: function(rowClass, segment, index, store) {
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
            meta.editingConn = sessionHash;
            meta.editingUser = byUserGuid;
            trackedUser = tracking.getAt(tracking.findExact('userGuid', meta.editingUser));
            if(trackedUser) {
                rowClass.push('other-user-edit'); 
                rowClass.push('usernr-'+trackedUser.get('taskOpenerNumber'));
                return false; 
            }
            return; //if the segment is edited, this should be visualized.
        }
        //check if segment is selected by somebody and colorize it, but only if it is not edited. 
        if(meta.selectingSessions) {
            Ext.Object.each(meta.selectingSessions, function(sessionHash, userGuid) {
                trackedUser = tracking.getAt(tracking.findExact('userGuid', userGuid));
                if(trackedUser) {
                    rowClass.push('other-user-select');
                    rowClass.push('usernr-'+trackedUser.get('taskOpenerNumber'));
                    return false; 
                }
            });
        }
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
                if(Ext.isDefined(meta) && meta.selectingSessions) {
                    Ext.Object.each(meta.selectingSessions, function(sessionHash, userGuid) {
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
    }
});
