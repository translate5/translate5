/*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor Javascript GUI and build on ExtJs 4 lib
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
 
 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty
 for any legal issue, that may arise, if you use these FLOSS exceptions and recommend
 to stick to GPL 3. For further information regarding this topic please see the attached 
 license.txt of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        "403_edit": '#UT#Zum Speichern der bearbeiten Daten fehlen Ihnen die nötigen Berechtigungen!',
        "404": '#UT#Die angeforderten / zu bearbeitenden Daten wurden nicht gefunden!',
        "405_del_assoc": '#UT#Ein Benutzer konnte nicht aus der Aufgabe entfernt werden, da er die Aufgabe aktuell benutzt.',
        "401_title": '#UT#Erneute Anmeldung',
        "401_msg": '#UT#Ihre Sitzungsdaten sind abgelaufen. Sie werden nun zur Anmeldeseite weitergeleitet.',
        "406": '#UT#Es ist ein Fehler aufgetreten!',
        "409": '#UT#Ihre Daten konnten nicht gespeichert werden, beim Speichern kam es zu einem Konflikt!',
        title: '#UT#Fehler',
        text: '#UT#Fehler beim Speichern oder beim Auslesen von Daten. Bitte wenden Sie sich an unseren Support!',
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
        if(operation.success) {
            return true;
        }
        if(operation.response) {
            this.handleException(operation.response);
        }
        else {
            this.handleFailedRequest(operation.error.status, operation.error.statusText);
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
            tpl = new Ext.Template(str.serverMsg),
            action = response && response.request && response.request.options.action,
            getServerMsg = function() {
                var json = Ext.JSON.decode(respText);
                return json.errors[0]._errorMessage;
            },
            appendServerMsg = function(msg) {
                return msg + tpl.apply(['', getServerMsg()]);
            };
        
        switch(status) {
            case -1:
                Ext.Msg.alert(str.title, appendServerMsg(text));
                return;
            case 403: //Forbidden: authenticated, but not allowed to see the specific resource 
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
            case 404: //Not Found: Ressource does not exist
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
                return;
        }
        Ext.Msg.alert(str.title, text+tpl.apply([status, statusText]));
    },
    /**
     * Helper to redirect to the login page
     */
    handleNotVerified: function(){
        location.href = Editor.data.loginUrl;
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
        constructor: function() {
            this.callOverridden(arguments);
            this.on('exception', function(proxy, resp, op){
                if(op.callback){
                    op.response = resp; //Operation does not contain response by default
                }
                else {
                    Editor.app.getController('ServerException').handleException(resp);
                }
            });
        }
    });
});