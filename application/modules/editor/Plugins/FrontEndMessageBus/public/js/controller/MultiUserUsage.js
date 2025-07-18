
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.FrontEndMessageBus.controller.MultiUserUsage
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.FrontEndMessageBus.controller.MultiUserUsage', {
    extend: 'Ext.app.Controller',
    segmentUsageData: null,
    onlineUsers: null,
    tooltip: null,
    refs: [{
        ref : 'segmentGrid',
        selector : '#segmentgrid'
    },{
        ref:'searchReplaceWindow',
        selector:'#searchreplacetabpanel'
    }],
    strings: {
        noConnection: '#UT#Sie haben Verbindungsprobleme zum Internet. <br />Bitte prüfen Sie die Stabilität Ihrer Internetverbindung. <br />Sollte diese Meldung dauerhaft zu sehen sein, kann es auch sein, <br />dass das Websocket-Protokoll wss:// in Ihrer Firewall blockiert ist.',
        noConnectionSeg: '#UT#Sie können kein Segment editieren, so lange keine Verbindung zum Server besteht.',
        inUseTitle: '#UT#Segment bereits in Bearbeitung',
        inUse: '#UT#Ein anderer Benutzer war schneller und hat im Moment das Segment zur Bearbeitung gesperrt.',
        inUseMsg: '#UT#Das ausgewählte Segment wird von einem anderen Benutzer bereits bearbeitet und kann daher nicht geöffnet werden.',
        currentUser: '#UT#Aktueller Bearbeiter',
        editors: '#UT#Bearbeiter: ',
        myself: '#UT#Ich',
        selectedBy: '#UT#Ausgewählt von',
        segmentChangedOnServerTitle: '#UT#Segment wurde auf der Serverseite geändert.',
        segmentChangedOnServerMessage: '#UT#Segment wurde auf der Serverseite geändert.<br/><b>Quelltext:<b><br/>{source}<br/><b>Zieltext:<b><br/>{target}<br/>Möchten Sie die Änderungen akzeptieren?',
    },

    listen: {
        messagebus: {
            '#translate5': {
                error: 'onError',
                close: 'onClose',
                reconnect: 'onReconnect',
                triggerReload: 'onTriggerReload'
            },
            '#translate5 instance': {
                pong: 'onMessageBusPong',
                resyncSession: 'onResyncSession'
            },
            '#translate5 task': {
                segmentselect:  'onSegmentSelect',
                segmentOpenNak: 'onSegmentOpenNak',
                segmentLeave: 'onSegmentLeave',
                segmentSave: 'onSegmentSave',
                segmentLocked: 'onSegmentLocked',
                triggerReload: 'onTriggerTaskReload',
                updateOnlineUsers: 'onUpdateOnlineUsers',
                segmentsProcessed: 'onSegmentsProcessed'
            }
        },
        store: {
            '#Segments': {
                prefetch: 'onSegmentPefetch'
            }
        },
        controller: {
            '#Editor.$application': {
                editorViewportClosed: 'onCloseEditorViewport'
            },
            '#ChangeAlike': {
                cancelManualProcessing: 'onCancelChangeAlikes'
            }
        },
        component: {
            '#segmentgrid' : {
                afterrender: 'onSegmentGridRender',
                renderrowclass: 'onSegmentRender',
                itemclick: 'clickSegment',
                beforestartedit: 'enterSegment',
                canceledit: 'leaveSegment'
            }
        }
    },

    listeners:{
        taskOpenedOnReconnect:'onTaskOpenedOnReconnect'
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

        //if js logging is activated, we add some interesting data to it
        if(window.logger) {
            logger.data.messageBus = {
                serverId: Editor.data.app.serverId,
                connectionId: conf.connectionId,
                version: conf.clientVersion
            };
        }

        var schema = location.protocol.match('https') ? 'wss' : 'ws',
            host = conf.socketServer.httpHost || window.location.hostname,
            port = conf.socketServer.port || (schema === 'wss' ? 443 : 80),
            path = '/' + schema + conf.socketServer.route;

        url.push(schema, '://', host, ':', port, path);

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
                connectionId: conf.connectionId,
                //to compare server and client version
                version: conf.clientVersion

            }
        });
        me.segmentUsageData = new Ext.util.Collection();

        me.gcTask = Ext.TaskManager.start({
            interval: 5 * 60000, //5 minutes
            run: me.garbageCollector,
            scope: me
        });

        //deactivate controller, is activated again when opening a multiuser task
return; //FIXME prepare that socket server is only triggered for simultaneous usage, for beta testing we enable socket server just for each task
        this.deactivate();
    },
    /**
     * On connection reconnect we send a ping to the server, if the reconnection was because of a server crash,
     * the ping resyncs the session data from translate5
     * @param {Editor.util.messageBus.MessageBus} bus
     */
    onClose: function(bus){
        var me = this;

        console.log('onClose called',new Date().toLocaleString());

        var task = new Ext.util.DelayedTask(function(){
            if(!me.bus.isReady()) {
                Editor.app.maskViewport(me.strings.noConnection + '<br>' + me.bus.getUrl());
            }
        });
        task.delay(1000);
    },
    onError: function(bus, evt, error) {
        if(error && error === 'versionMismatch') {
            Editor.MessageBox.addError("Server / Client version mismatch. Please contact your system administrator!");
            bus.setReconnect(false);
        }
        if(error && error === 'noInstanceId') {
            Editor.MessageBox.addError("Missing instance ID on server connect. Please contact your system administrator!");
            bus.setReconnect(false);
        }
    },
    onReconnect: function(bus){
        bus.send('instance', 'ping');

        var me = this,
            grid = me.getSegmentGrid(),
            sel;

        //release all old locks (they may be not up to date anymore).
        if(grid) {
            me.segmentUsageData.each(function(meta) {
                meta.selectingConns = {};
                if(meta.editingConn && meta.editingUser) {
                    me.segmentUnlock(meta, meta.editingConn);
                }
            });
        }

        if(grid && Editor.data.task) {
            let me = this,
                task = Editor.data.task;

            task.set('userState', task.get('userState'));
            task.save({
                success: function() {
                    Editor.app.unmaskViewport();
                    me.bus.send('task', 'openTask', [Editor.data.task.get('taskGuid')]);
                    me.fireEvent('taskOpenedOnReconnect', Editor.data.task);
                },
                failure: function(record, op) {
                    Editor.app.unmaskViewport();
                    Editor.app.getController('ServerException').handleFailedRequest(op.error.status, op.error.statusText, op.error.response);
                }
            });
        }
        else {
            Editor.app.unmaskViewport();
        }
    },

    onTaskOpenedOnReconnect: function(task) {
        var me = this,
            grid = me.getSegmentGrid(),
            sel;

        if(grid && (sel = grid.getSelectionModel().getSelection()) && sel.length > 0) {
            me.clickSegment(null, sel[0]);
        }

        if(grid && grid.editingPlugin.editing) {
            me.enterSegment(grid.editingPlugin, [grid.editingPlugin.context.record]);
            //alikes GET is triggerd again in change alike controller
        }
    },

    enterSegment: function(plugin, context) {
        var me = this,
            msg = me.strings,
            rec = context[0];
        if(!me.bus.isReady()) {
            Ext.Msg.alert(msg.noConnection, msg.noConnectionSeg);
            return;
        }

        //if segment is not editable, we do not send a editrequest at all
        if(rec && !rec.get('editable')) {
            var meta = me.segmentUsageData.get(rec.get('id'));
            if(meta && meta.editingConn && meta.editingUser) {
                Editor.MessageBox.addInfo(me.strings.inUseMsg);
                return false;
            }
            return;
        }
        if(rec){
            me.bus.send('task', 'segmentEditRequest', [rec.get('taskGuid'), rec.get('id')]);
        }
        me.editorPlugin = plugin;
    },
    onCancelChangeAlikes: function(record) {
        this.bus.send('task', 'segmentCancelAlikes', [record.get('taskGuid'), record.get('id')]);
    },
    leaveSegment: function(plugin, context) {
        this.bus.send('task', 'segmentLeave', [context.record.get('taskGuid'), context.record.get('id')]);
    },
    onSegmentOpenNak: function(data) {
        var me = this,
            id = data.segmentId,
            msg = me.strings;
        if(me.editorPlugin && me.editorPlugin.editing && me.editorPlugin.context.record.get('id') === id) {
            me.editorPlugin.cancelEdit();
            Ext.Msg.alert(msg.inUseTitle, msg.inUse);
        }
    },
    onSegmentLeave: function(data) {
        var me = this,
            ids = (Ext.isArray(data.segmentId) ? data.segmentId : [data.segmentId]),
            grid = this.getSegmentGrid(),
            segment, keepRender;
        if(!grid){
            return
        }
        Ext.Array.each(ids, function(id){
            keepRender = id === data.selectedSegmentId;
            me.segmentUnlock(me.getSegmentMeta(id), data.connectionId, keepRender);
            if(keepRender){
                segment = grid && grid.store.getById(id);
                segment && segment.load();
            }
        });
    },
    onSegmentSave: function(data) {
        this.unlockAndReload(data.segmentId, data.connectionId);
    },
    unlockAndReload: function (segmentId, connectionId) {
        const segment = this.getSegment(segmentId);
        if (segment) {
            this.segmentUnlock(this.getSegmentMeta(segmentId), connectionId, true);
            segment.load();
        }
    },
    getSegment: function(segmentId) {
        const grid = this.getSegmentGrid();

        if (! grid) {
            return null;
        }

        return grid.store.getById(segmentId);
    },
    onSegmentsProcessed: function(data) {
        const me = this,
            msg = me.strings,
            baseUrl = (Editor.data.restpath.indexOf('/task/') === -1)
                ? Editor.data.restpath
                : Editor.data.restpath.split('/task/')[0] + '/'
        ;
        let segmentIds = data.segmentIds;

        if (this.editorPlugin && this.editorPlugin.editing) {
            const currentSegmentId = this.editorPlugin.context.record.get('id');
            const openSegmentId = data.segmentIds.find(id => id === currentSegmentId);
            segmentIds = data.segmentIds.filter(id => id !== currentSegmentId);

            if (openSegmentId) {
                Ext.Ajax.request({
                    url: baseUrl + 'segment/' + openSegmentId,
                    method: "GET",
                    success: function(response){
                        const resp = Ext.util.JSON.decode(response.responseText),
                            segment = resp.rows,
                            message = msg.segmentChangedOnServerMessage
                                .replace('{source}', segment.source)
                                .replace('{target}', segment.target);

                        Ext.Msg.confirm(
                            msg.segmentChangedOnServerTitle,
                            message,
                            function (result) {
                                if (result !== 'yes') {
                                    return;
                                }

                                me.unlockAndReload(openSegmentId, data.connectionId);

                                me.editorPlugin.cancelEdit();
                            }
                        );
                    },
                    failure: function(response){
                        Editor.app.getController('ServerException').handleException(response);
                    }
                });
            }
        }

        for (const segmentId of segmentIds) {
            me.unlockAndReload(segmentId, data.connectionId);
        }
    },
    onSegmentLocked: function(data) {
        var me = this,
            lockedIds = (Ext.isArray(data.segmentId) ? data.segmentId : [data.segmentId]),
            byTrackingId = data.trackingId,
            connectionId = data.connectionId,
            grid = me.getSegmentGrid(),
            segment;

        if(!grid) {
            return;
        }

        //add color mark to current segment and lock segment
        Ext.Array.each(lockedIds, function(lockedId){
            var meta = me.getSegmentMeta(lockedId);
            //add user id as locker to the segment
            meta.editingConn = connectionId;
            meta.editingUser = byTrackingId;
            //show the editing in the segment - if loaded in the grid
            if(segment = grid.store.getById(lockedId)) {
                me.markSegmentEdited(segment);
            }
        });
    },
    /**
     * render the segment as edited
     * @param {Editor.models.Segment} segment
     */
    markSegmentEdited: function(segment) {
        segment.set('editable', false);
        segment.set('autoStateId', Editor.data.segments.autoStates.EDITING_BY_USER);
        segment.commit(false); //trigger render
    },
    segmentUnlock: function(meta, connectionId, keepRender) {
        var me = this, segment,
            grid = me.getSegmentGrid();

        if(!grid || meta.editingConn !== connectionId) {
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
        //remove segment from segmentUsageData if not used anymore
        me.removeUnused(meta);
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
            byTrackingId = data.trackingId,
            connectionId = data.connectionId,
            grid = me.getSegmentGrid(),
            meta = me.getSegmentMeta(selectedId),
            segment;

        if(!grid) {
            return;
        }

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
        meta.selectingConns[connectionId] = byTrackingId;

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
            trackedUser = tracking.getById(meta.editingUser);

            if(trackedUser) {
                rowClass.push('other-user-edit');
                rowClass.push('usernr-'+trackedUser.get('taskOpenerNumber'));
                return false;
            }
            return; //if the segment is edited, this should be visualized and not the following selection stuff
        }
        //check if segment is selected by somebody and colorize it, but only if it is not edited. 
        if(meta.selectingConns) {
            Ext.Object.each(meta.selectingConns, function(connectionId, trackingId) {
                trackedUser = tracking.getById(trackingId);
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
        if(!sessionId) {
            //if there is no session id, we can not resync it
            return;
        }
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
    onSegmentGridRender: function(grid) {
        var me = this,
            view = grid.getView(),
            tracking = Editor.data.task.userTracking(),
            trackedUser;

        //if the list of tracking user changes, we have to update the is online information
        tracking.on('datachanged', me.updateOnlineUsers, me);

        //was previously invoked in #Editor.$application': editorViewportOpened, 
        // but then the open was triggerd when the grid was not ready yet, therefore resulting segment locks from the server were producing errors in the GUI
        me.bus.send('task', 'openTask', [Editor.data.task.get('taskGuid')]);

        if(Editor.data.task.get('usageMode') !== Editor.model.admin.Task.usageModes.SIMULTANEOUS) {
            return;
        }

        //positioning fix after title
        grid.header.insert(1, [{
            itemId: 'multiUserList',
            xtype: 'dataview',
            cls: 'multi-user-list x-btn-inner-default-small',
            itemSelector: '.user',
            tpl:[
                '<tpl if="values.length">',
                me.strings.editors,
                '</tpl>',
                '<tpl for=".">',
                '<tpl if="userGuid == Editor.data.app.user.userGuid">',
                '<span class="user usernr-{taskOpenerNumber}"><span class="icon"></span>'+me.strings.myself+'</span>',
                '<tpl else>',
                '<span class="user usernr-{taskOpenerNumber}"><span class="icon"></span>{userName}</span>',
                '</tpl>',
                '</tpl>'
            ],
            store: new Ext.data.ChainedStore({
                source: Editor.data.task.userTracking(),
                filters: [{
                    property: 'isOnline',
                    value: true
                }]
            })
        },{
            xtype: 'tbseparator'
        }]);

        trackedUser = tracking.getAt(tracking.findExact('userGuid', Editor.data.app.user.userGuid));
        if(trackedUser) {
            view.selectedItemCls += ' usernr-'+trackedUser.get('taskOpenerNumber');
        }

        me.tooltip = Ext.create('Ext.tip.ToolTip', {
            cls: 'multi-user-list multi-user-tip',
            // The overall target element.
            target: view.el,

            // Each grid row causes its own separate show and hide.
            delegate: view.itemSelector + ' tr.other-user-edit',
            // Moving within the row should not hide the tip.
            trackMouse: true,
            showOnTap: true,
            //defaultAlign: 'bl-tl',
            anchor: 'bottom',
            anchorToTarget: false,
            // Render immediately so that tip.body can be referenced prior to the first show.
            renderTo: Ext.getBody(),
            listeners: {
                // Change content dynamically depending on which element triggered the show.
                beforeshow: function (tip) {
                    var userNr = tip.currentTarget.dom && tip.currentTarget.dom.className.match(/(^| )usernr-([0-9]+)($| )/),
                        tracking = Editor.data.task.userTracking(),
                        user;
                    userNr = parseInt(userNr && userNr[2]);
                    user = tracking.getAt(tracking.findExact('taskOpenerNumber', userNr));
                    if(!user) {
                        return false;
                    }
                    //remove color from tip itself
                    tip.update('<span class="usernr-'+userNr+'"><span class="icon"></span>'+user.get('userName')+'</span>'); //set user name
                }
            }
        });
    },
    onTriggerTaskReload: function(data) {
        if(!Editor.data.task){
            return;
        }
        var taskGuid = null;
        if(Editor.data.task.taskGuid){
            taskGuid = Editor.data.task.taskGuid;
        }else if(Editor.data.task.isModel){
            taskGuid = Editor.data.task.get('taskGuid');
        }
        //reloads the currently opened task
        if(taskGuid && taskGuid == data.taskGuid) {

            // in case the current task is not a task model, create it as a model and trigger the reload
            if(!Editor.data.task.load){
                // when the task is not model, the object contains id and the taskGuid in it
                // Based on that, we can use it as init object for the new model
                Editor.data.task = Ext.create('Editor.model.admin.Task', Editor.data.task);
            }

            Editor.data.task.load();
        }
    },
    onTriggerReload: function() {
        //idea is to reload the store given as storeid, and optionally reload only one record of the store, given by id as optional second parameter.
        Ext.Logger.warn("generic trigger reload NOT implemented yet");
    },
    /**
     * is called every 5 Minutes and removes unused segment usage data
     */
    garbageCollector: function() {
        var me = this;
        me.segmentUsageData.each(function(meta) {
            me.removeUnused(meta);
        });
    },
    onCloseEditorViewport: function() {
        var me = this;
        me.tooltip && me.tooltip.destroy();
        me.segmentUsageData.removeAll();
    },
    /**
     * Updates the online users view
     */
    onUpdateOnlineUsers: function(data) {
        this.onlineUsers = data;
        this.updateOnlineUsers();
    },
    updateOnlineUsers: function() {
        var me = this,
            store = Editor.data.task && Editor.data.task.userTracking && Editor.data.task.userTracking();
        if(!store || !me.onlineUsers) {
            return;
        }
        var onlineQty = 0, srw = me.getSearchReplaceWindow();
        Ext.Object.each(me.onlineUsers.onlineInTask, function(key, val){
            var item = store.getById(key);
                item && item.set('isOnline', val);
            if (val) onlineQty ++;
        });

        if (srw) {
            srw.getViewModel().set('isOpenedByMoreThanOneUser', onlineQty > 1);
        }
        store.commitChanges();
    }
});
