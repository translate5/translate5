
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
 * @class Editor.util.SegmentContent
 */
Ext.define('Editor.util.messageBus.MessageBus', {
    mixins: [
        'Ext.mixin.Observable'
    ],
    
    /**
     * connection is not yet open.
     * @property {Number} CONNECTING
     * @readonly
     */
    CONNECTING: 0,

    /**
     * connection is open and ready to communicate.
     * @property {Number} OPEN
     * @readonly
     */
    OPEN: 1,

    /**
     * connection is in the process of closing.
     * @property {Number} CLOSING
     * @readonly
     */
    CLOSING: 2,

    /**
     * connection is closed or couldn't be opened.
     * @property {Number} CLOSED
     * @readonly
     */
    CLOSED: 3,
    
    /**
     */
    config: {
        /**
         * @cfg {String} busId (required) An identifier for different WebSocket connections
         */
        busId: '',
        
        /**
         * @cfg {String} url (required) The WebSocket URL
         */
        url: '',
        
        
        /**
         * @cfg {Object} optional parameters for the WebSocket connection
         */
        params: null,

        /**
         * @cfg {Boolean} reconnect If true, tries to re-connect the socket if closed by the server
         */
        reconnect: true,

        /**
         * @cfg {Int} reconnectInterval Interval for trying a reconnection, in milliseconds
         */
        reconnectInterval: 5000
    },
    socket: null,
    currentChannel: null,
    reconnectTask: null,
    constructor: function(config) {
        var me = this,
            busId;
        
        me.isInitializing = true;
        me.mixins.observable.constructor.call(me, config);
        me.isInitializing = false;

        busId = me.getBusId();
        if (!busId && (config && config.id)) {
            me.setBusId(busId = config.id);
        }

        if (busId) {
            //TODO do we need a manager???
            //similar to: Ext.data.StoreManager.register(me);
        }
        
        this.initSocket();
    },
    initSocket: function() {
        var ws,
            me = this,
            url = me.getUrl(),
            params = me.getParams(),
            errorMsg = 'Configuration error: Socket Server does not use the same protocol as the rest of the page. Either both must use SSL or both not.';
        if(!url) {
            Ext.raise('MessageBus: No URL for the websocket connection was given!');
            return;
        }
        
        if(Ext.isObject(params)) {
            url = url+'?'+Ext.Object.toQueryString(params);
        }
        
        //page protocol and socket server protocol must be the same. Either both SSL or both not. 
        if ((location.protocol == 'https:') !== /^wss:/.test(url)) {
            Editor.MessageBox.addError('Please contact your administrator: '+errorMsg);
            Ext.raise(errorMsg);
        }
            
        ws = me.socket = new WebSocket(url);
        
        /**
         * WebSocket on message handler
         */
        ws.onmessage = function(evt) {
            var data;
            try {
                data = Ext.JSON.decode(evt.data);
            }catch(e) {
                Ext.Logger.warn('MessageBus: could not decode message JSON.');
                return;
            }
            
            if(data.command === 'errorOnOpen') {
                console.log('↓ ', data.command, data.error);
                me.setReconnect(false);
                me.currentChannel = null; //needed in order to let the events come from the bus instance
                me.fireEvent('error', me, event, data.error);
                return;
            }
            
            me.currentChannel = data.channel;
            me.fireEvent(data.command, data.payload);
console.log('↓ ', data.channel, data.command, data.payload);
        };
        
        /**
         * WebSocket on open handler
         */
        ws.onopen = function (event) {
            me.currentChannel = null; //needed in order to let the events come from the bus instance
            // if there is a reconnection task, disable it on successful connection open
            if (me.reconnectTask) {
                Ext.Logger.info('MessageBus: reconnected busId '+me.getBusId());
                Ext.TaskManager.stop(me.reconnectTask);
                me.reconnectTask = null;
                me.fireEvent('reconnect', me, event);
            }
            else {
                me.fireEvent('open', me, event);
            }
        }
        
        /**
         * WebSocket on close handler
         */
        ws.onclose = function (event) {
            me.currentChannel = null; //needed in order to let the events come from the bus instance
            me.fireEvent('close', me, event);
            //start reconnection interval task
            Ext.Logger.info('MessageBus: connection close busId '+me.getBusId());
            if (me.getReconnect() && !me.reconnectTask) {
                me.reconnectTask = Ext.TaskManager.start({
                    interval: me.getReconnectInterval(),
                    run: function () {
                        if (me.getReadyStatus() === me.CLOSED) {
                            me.initSocket();
                        }
                    }
                });
            }
        }
        
        /**
         * WebSocket on error handler
         */
        ws.onerror = function (event) {
            me.currentChannel = null; //needed in order to let the events come from the bus instance
            me.fireEvent('error', me, event);
            //event did not provide useful information in some tests, so currently no further processing of event here 
        }
    },
    /**
     * returns if the websocket connection is ready
     * @return {Boolean} true if connection is ready, false otherwise
     */
    isReady: function () {
        return this.getReadyStatus() === this.OPEN;
    },
    /**
     * returns the current websocket status 
     * @return {Number} current websocket status (0: connecting, 1: open, 2: closing, 3: closed)
     */
    getReadyStatus: function () {
        return this.socket.readyState;
    },
    send: function(channel, command, data) {
        var msgObj = {
            channel: channel,
            command: command, 
            payload: data || null
        };
        if (this.isReady()) {
            console.log('↑', msgObj.channel, msgObj.command, msgObj.payload);
            this.socket.send(Ext.JSON.encode(msgObj));
        }
    }
});