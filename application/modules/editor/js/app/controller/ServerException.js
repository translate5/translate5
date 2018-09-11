
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
 * This Controller integrates into Ext.data.proxy.Server through constructor overriding.
 * It handles all server exceptions if no Operation.callback is given.
 * @class Editor.controller.ServerException
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.ServerException', {
    extend: 'Ext.app.Controller',
    strings: {
        "403": '#UT#Für die angeforderten Daten fehlen Ihnen die nötigen Berechtigungen!',
        "403_destroy": '#UT#Zum Löschen der geforderten Daten fehlen Ihnen die nötigen Berechtigungen!',
        "403_create": '#UT#Zum Speichern von neuen Daten fehlen Ihnen die nötigen Berechtigungen!',
        "403_update": '#UT#Zum Speichern der bearbeiten Daten fehlen Ihnen die nötigen Berechtigungen!',
        "403_edit": '#UT#Zum Speichern der bearbeiten Daten fehlen Ihnen die nötigen Berechtigungen!',
        "404": '#UT#Die angeforderten / zu bearbeitenden Daten wurden nicht gefunden!',
        "405_del_assoc": '#UT#Ein Benutzer konnte nicht aus der Aufgabe entfernt werden, da er die Aufgabe aktuell benutzt.',
        "401_title": '#UT#Erneute Anmeldung',
        "401_msg": '#UT#Ihre Sitzungsdaten sind abgelaufen. Sie werden nun zur Anmeldeseite weitergeleitet.',
        "406": '#UT#Es ist ein Fehler aufgetreten!',
        "409": '#UT#Ihre Daten konnten nicht gespeichert werden, beim Speichern kam es zu einem Konflikt!',
        "503_title": '#UT#Es läuft eine Wartung',
        "503_msg": '#UT#Aktuell wird das System gewartet. Sobald die Wartung vorüber ist, können Sie wieder am System arbeiten.',
        title: '#UT#Fehler',
        text: '#UT#Fehler beim Speichern oder beim Auslesen von Daten. Bitte wenden Sie sich an unseren Support!',
        timeout: '#UT#Die Anfrage über das Internet an den Server dauerte zu lange. Dies kann an Ihrer Internetverbindung oder an einer Überlastung des Servers liegen. Bitte versuchen Sie es erneut.',
        serverMsg: '#UT#<br />Meldung vom Server: <i>{0} {1}</i>'
    },
    /**
     * Can be used in Operation callbacks to trigger the "default ServerException" failure behaviour
     * handles only failed requests, ignores successfully HTTP 2XX requests
     * @param {Array} records
     * @param {Ext.data.Operation} operation
     * @param {Boolean} success [not yet, ext > 4.0.7]
     * @return {Boolean} true if request was successfull, false otherwise
     */
    handleCallback: function(records, operation, success) {
        var resp = operation.getResponse();
        if(operation.success) {
            return true;
        }
        if(resp) {
            this.handleException(resp);
        }
        else {
            this.handleFailedRequest(operation.error.status, operation.error.statusText, operation.error.response);
        }
        return false;
    },
    /**
     * Interceptor handler used in proxy exceptions
     * @param {Object} response
     * @returns void
     */
    handleException: function(response){
        var status = (response && response.status ? response.status : -1),
            statusText = (response && response.statusText ? response.statusText : '');
        this.handleFailedRequest(status, statusText, response);
    },
    /**
     * handles / displays the given error
     * @param {Integer} status
     * @param {String} statusText
     * @param {Object} response
     */
    handleFailedRequest: function(status, statusText, response) {
        var me = this,
            str = me.strings,
            _status = status.toString(),
            text = str.text,
            respText = response && response.responseText || '{"errors": [{"_errorMessage": "unknown"}]}',
            json = null,
            tpl = new Ext.Template(str.serverMsg),
            action = response && response.request && response.request.options.action,
            getServerMsg = function() {
                if(!json.errors && json.message){
                    return json.message;
                }
                return json.errors[0]._errorMessage;
            },
            appendServerMsg = function(msg) {
                var serverMsg = getServerMsg();
                if(serverMsg == 'unknown') {
                    return msg;
                }
                return msg + tpl.apply(['', getServerMsg()]);
            };
        
        try {
            json = Ext.JSON.decode(respText);
        }
        catch(e){
            //if there is no valid JSON, the error is probably not from us. With 0 we pass by the below switch and just print the error
            status = 0; 
        }
            
        switch(status) {
            case -1:
                //if the XHR was aborted, do nothing here, since this is "wanted" behaviour
                if(response.aborted) {
                    return;
                }
                Ext.Msg.alert(str.title, appendServerMsg(str.timeout));
                return;
            //@todo remove this specific 405 handler with TRANSLATE-94
            case 405: //Method Not Allowed: the used HTTP Method is not allowed
                var req = response.request,
                    regex = new RegExp('^'+Editor.data.restpath+'taskuserassoc');
                if(req && req.options && req.options.method == 'DELETE' && regex.test(req.options.url)) {
                    if(req.options && req.options.records && req.options.records.length > 0) {
                        //for this Array.difference see TRANSLATE-95 it should be changed with TRANSLATE-94
                        var assocs = Ext.getStore('admin.TaskUserAssocs'),
                            removed = assocs.getRemovedRecords();
                        assocs.removed = Ext.Array.difference(removed, req.options.records);
                    }
                    Editor.MessageBox.addError(appendServerMsg(str["405_del_assoc"]));
                    return;
                }
                Ext.Msg.alert(str.title, text+tpl.apply([status, statusText]));
                return;
            case 403: //Forbidden: authenticated, but not allowed to see the specific resource 
            case 404: //Not Found: Resource does not exist
                if(str[_status+'_'+action]) {
                    _status = _status+'_'+action;
                }
                Editor.MessageBox.addError(appendServerMsg(str[_status]));
                return;
            case 401: //Unauthorized → redirect to login
                Ext.MessageBox.show({
                    title: str["401_title"],
                    msg: str["401_msg"],
                    buttons: Ext.MessageBox.OK,
                    fn: function(){
                        return me.handleNotVerified();
                    },
                    icon: Ext.MessageBox.WARNING
                });
                return;
            case 409: //Conflict: show message from server
                Editor.MessageBox.addError(appendServerMsg(str["409"]));
                return;
            case 406: //Not Acceptable: show message from server
                Editor.MessageBox.addError(getServerMsg());
            case 502: //Bad Gateway → the real error is coming from a requested third party system
                Ext.Array.each(json.errors, function(item){
                    Editor.MessageBox.getInstance().showDirectError(item.msg, item.data);
                });
                return;
            case 503:
                Ext.MessageBox.show({
                     title: str["503_title"],
                     msg: str["503_msg"],
                     buttons: Ext.MessageBox.OK,
                     fn: function(){
                         return me.handleMaintenance();
                     },
                     icon: Ext.MessageBox.WARNING
                 });
                return;
        }
        Ext.Msg.alert(str.title, text+tpl.apply([_status, statusText]));
    },
    /**
     * Helper to redirect to the login page
     */
    handleNotVerified: function(){
        location.href = Editor.data.loginUrl;
    },
    /**
     * Redirect user to the maintenance page
     */
    handleMaintenance:function(){
        location.reload();
    }
},
/**
 * Init after class creation
 * bind to all ServerProxy exceptions, and call unified handler if no Operation Callback is defined.
 * If a callback is defined, the default controller method handleCallbackHttpFailure can be called:
 * var errorHandler = Editor.app.getController('ServerException');
 * errorHandler.handleCallback.apply(errorHandler, arguments); 
 */
function() {
    //override Ext.data.proxy.Server
    Ext.data.proxy.Server.override({
    	afterRequest: function(request,success) {
    		this.callOverridden(arguments);
    		var response=request._operation._response;
    		if(!response){
    			return;
    		}
    		var responseheaders = response.getAllResponseHeaders();
    		for(var headername in responseheaders) {
    			if(headername ==='x-translate5-shownotice'){
    				var mntpnl = Ext.ComponentQuery.query('maintenancePanel');
    				if(!mntpnl || mntpnl.length>0){
    					return;
    				}
    				var viewport =Ext.ComponentQuery.query('viewport');
    				if(!viewport || viewport.length<1){
    				    return;
    				}
    				viewport[0].add({
    					  xtype:'maintenancePanel',
    					  region:'north',
    					  weight: 100,
    					  maintenanceStartDate:responseheaders[headername]
    				});
    			}
    		}
    	},
        constructor: function() {
            this.callOverridden(arguments);
            this.on('exception', function(proxy, resp, op){
                if(op.preventDefaultHandler){
                    op.response = resp; //Operation does not contain response by default
                }
                else {
                    Editor.app.getController('ServerException').handleException(resp);
                }
            });
        }
    });
    
});