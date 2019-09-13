
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
 * @class Editor.util.SegmentContent
 */
Ext.define('Editor.util.messageBus.MessageBus', {
    mixins: [
        'Ext.mixin.Observable'
    ],
    /**
     */
    config: {
        busId: null, //must be different for different connections
    },
    
    
    //FIXME we need a MessageBUs controller, which listenes to arbitary events in Translate5 (like segment open)
    // reacts on that event and sends the message via messageBus to the server. So far the counter part to the messageBus.EventDomain
    // This controller keeps also the internal state (segment opened, if yes which one) so that if the connection dies, 
    // the socket can be reopened and the data resend to the server. 
    // The problem is, if the socket server restarts, all data is gone, how to handle that?
    
    
    socket: null,
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
            //FIXME do we need a manager???
            //Ext.data.StoreManager.register(me);
        }
        
        this.initSocket();
        //FIXME this must be an observable in order that it can be used by the event domains 
    },
    initSocket: function() {
        var ws,
            me = this;
        //FIXME url, port etc from config
        // the serverId ensures that we communicate with the correct instance, additional security comes from the sessionId, which must match
        // authentication by passing the session id (or a hash from it, or something) to the server in a first send.
        ws = me.socket = new WebSocket('ws://localhost:9056/?serverId='+Editor.data.app.serverId);
        ws.onmessage = function(evt) {
            var data = Ext.JSON.decode(evt.data);
            //FIXME error handling if JSON decode fail
            me.currentChannel = data.channel;
            me.fireEvent(data.command, data.payload);
            console.log(evt);
        };
        ws.onopen = function (event) { 
            //FIXME send here auth request
            ws.send('test');
        }
        ws.onclose = function (event) {
            //FIXME if connection close try to reconnect, see https://stackoverflow.com/a/31985557/1749200
            console.log("CONNECTION CLOSE");
        }
        //FIXME onerror → no connection, disable message bus. How is the plan, make it disableable at all??
    },
    send: function(channel, command, data) {
        //FIXME we need always the session as identifier!
        var msgObj = {
            channel: channel,
            command: command, 
            data: data,
            sessionId: 123 //FIXME read out sessionId from cookie, first configured cookie key must be given to Editor.data.app.
        };
        this.socket.send(Ext.JSON.encode(msgObj));
    }
});