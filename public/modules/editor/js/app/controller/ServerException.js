
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
        "update_title": '#UT#Translate5 wurde auf eine neue Version aktualisiert',
        "update_msg": '#UT#Ein Update auf die Version {0} wurde durchgeführt. Bitte laden sie die Anwendung neu um Fehler in der Benutzung zu vermeiden.',
        title: '#UT#Fehler',
        text: '#UT#Fehler beim Speichern oder beim Auslesen von Daten. Bitte wenden Sie sich an unseren Support!',
        timeout: '#UT#Die Anfrage über das Internet an den Server dauerte zu lange. Dies kann an Ihrer Internetverbindung oder an einer Überlastung des Servers liegen. Bitte versuchen Sie es erneut.',
        serverMsg: '#UT#<br />Meldung vom Server: <i>{0} {1}</i>'
    },
    
    /**
     * Handle unproccessable entities
     * @param {Ext.form.Basic} form
     * @param {Ext.data.Model} record
     * @param {Ext.data.Operation} operation with operation.error = {response.status, response.statusText, response}
     */
    handleFormFailure: function(form, record, operation) {
        if(operation && operation.error.status === 422) {
            var json = Ext.decode(operation.error.response.responseText);
            if(json.errorsTranslated) {
                form.markInvalid(json.errorsTranslated);
                return;
            }
        }
        this.handleCallback(record, operation, false);
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
        var resp = operation.getResponse();
        if(resp) {
            this.handleException(resp);
        } else {
            this.handleFailedRequest(operation.error.status, operation.error.statusText, operation.error.response, operation);
        }
        return false;
    },
    /**
     * Interceptor handler used in proxy exceptions
     * @param {Object} response
     * @see Ext.data.request.Ajax.createResponse
     * @returns void
     */
    handleException: function(response){
        this.handleFailedRequest(response.status || -1, response.statusText || '', response);
    },
    /**
     * handles / displays the given error
     * @param {Integer} status
     * @param {String} statusText
     * @param {Object} response
     */
    handleFailedRequest: function(status, statusText, response, operation) {
        //FIXME refactor / clean up that function!
        //from bottom up, first remove all unneeded stuff at the bottom, then clean up the head.
        
        var me = this,
            str = me.strings,
            _status = status.toString(),
            text = str.text,
            respText = response && response.responseText || '{"errors": [{"_errorMessage": "unknown"}]}',
            json = null,
            tpl = new Ext.Template(str.serverMsg),
            action = response && response.request && response.request.options.action,
            errorCode,
            url = response?.request?.requestOptions?.url?.replaceAll('"', '~') || 'null',
            method = response?.request?.requestOptions?.method || 'null',
            getServerMsg = function() {
                if(json.errorMessage){
                    return json.errorMessage;
                }
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

            // Fire custom error code events for custom handling. The code bellow will not be processed If the event handler function returns false
            // This is used in plugin context for custom error handling
            if(json && json.errorCode && this.fireEvent('serverException'+json.errorCode,json,json.errorCode,response) === false){
                return;
            }
        }
        catch(e){
            var msg = 'Invalid JSON';
            //if there is no valid JSON, the error is probably not from us. With 0 we pass by the below switch and just print the error
            status = 0; 
            text += '<br><br> Invalid JSON from Server!';
            if(response && Ext.isEmpty(response.getAllResponseHeaders()['x-translate5-version'])) {
                text += '<br>Answer seems to come from a proxy!';
                msg += ' - answer seems not to be from translate5 - x-translate5-version header is missing.'
                if (jslogger) {
                    if (operation) {
                        jslogger.addLogEntry({ type : 'info', message : 'Request: ' + operation.getRequest().getMethod() + ' ' + operation.getRequest().getUrl()});
                    }
                    jslogger.addLogEntry({type: 'info', message: 'status-arg: ' + _status.replaceAll('"', '~')});
                    jslogger.addLogEntry({type: 'info', message: 'statusText-arg: ' + statusText.toString().replaceAll('"', '~')});
                    jslogger.addLogEntry({type: 'info', message: 'Response headers: ' + JSON.stringify(response.getAllResponseHeaders()).replaceAll('"', '~')});
                    jslogger.addLogEntry({type: 'info', message: 'Response text: ' + respText.toString().replaceAll('"', '~')});
                    jslogger.addLogEntry({type: 'info', message: 'Request URL: ' + method + ' ' + url});
                    jslogger.addLogEntry({type: 'info', message: 'Request URL Length: ' + url.length});

                    // In some cases RootCause shows too little frames in stack trace, so here we check if the current limit
                    // is still having the expected value (1000), and if no - update it and hope it will be respected by browser
                    jslogger.addLogEntry({type: 'info', message: 'Current Error.stackTraceLimit: ' + Error.stackTraceLimit});
                    if (Error.stackTraceLimit !== 1000) {
                        jslogger.addLogEntry({type: 'info', message: 'Updated Error.stackTraceLimit: ' + (Error.stackTraceLimit = 1000)});
                    }

                    // Attempt to load such a URL leads to 404 error, and the only guess right now is that this model class
                    // for some reason was not loaded at the app load step, so here we check if that guess is true
                    if (url.match('/Editor.model.admin.TaskUserAssoc')) {
                        jslogger.addLogEntry({type: 'info', message: 'Ext.Loader.isInHistory[\'Editor.model.admin.TaskUserAssoc\']: ' + Ext.Loader.isInHistory['Editor.model.admin.TaskUserAssoc']});
                    }
                }
            }

            Editor.MessageBox.addError(text);
            jslogger && jslogger.logException(new Error(msg));
        }
        if(json && json.errorCode === 'E1381') {
            Ext.Logger.error("E1381 error on the backend");
        }
        //it can happen on submit requests, that we receive the content in XML instead JSON:
        if(response && (!response.responseText || response.responseText.length == 0) && Ext.DomQuery.isXml(response.responseXML)) {
            json.httpStatus = status = Ext.DomQuery.selectNumber('httpStatus', response.responseXML);
            _status = Ext.DomQuery.selectValue('httpStatus', response.responseXML);
            json.message = statusText = Ext.DomQuery.selectValue('errorMessage', response.responseXML);
            json.errorMessage = '';
            
            //when we get here, calling form.markInvalid is over, so we dont need to fill the JSON structure, 
            // but have to add the errors to the message for plain output to the user 
            Ext.Array.each(Ext.DomQuery.select('errorsTranslated', response.responseXML), function(error) {
                Ext.Array.each(error.children, function(field) {
                    Ext.Array.each(field.children, function(singleErrors) {
                        json.errorMessage = json.errorMessage+singleErrors.firstChild.nodeValue+'<br>';
                    });
                });
            });
        }
            
        //form submits have here always a status of 200, so we have to get the real status from JSON
        if(json && json.httpStatus && status != json.httpStatus) {
            status = json.httpStatus;
            statusText = json.message;
            _status = status.toString();
        }
        errorCode = json && json.errorCode;
        switch(status) {
            case 0:
            case -1:
                //if the XHR was aborted, do nothing here, since this is "wanted" behaviour
                if(response.aborted) {
                    return;
                }
                statusText = appendServerMsg(str.timeout);
                if(response && response.request) {
                    statusText += Editor.MessageBox.debugInfoMarkup('URL: '+response.request.url);
                }
                Ext.Msg.alert(str.title, statusText);
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
                    Editor.MessageBox.addError(appendServerMsg(str["405_del_assoc"]), errorCode);
                    return;
                }
                if(response && response.request) {
                    statusText += Editor.MessageBox.debugInfoMarkup('URL: '+response.request.url);
                }
                Ext.Msg.alert(str.title, text+tpl.apply([status, statusText]));
                return;

            case 403: //Forbidden: authenticated, but not allowed to see the specific resource 
            case 404: //Not Found: Resource does not exist
                if(str[_status+'_'+action]) {
                    _status = _status+'_'+action;
                }
                Editor.MessageBox.addError(appendServerMsg(str[_status]), errorCode);
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

            case 423:
                let msg423 = 'No access on job anymore';
                if (response.request && response.request.options && response.request.options.url) {
                    msg423 = msg423 + ': ' + response.request.options.url;
                }
                // call the new logger function
                Editor.MessageBox.addError(appendServerMsg(str['403']));
                jslogger && jslogger.logException(new Error(msg423));
                return;

            case 409: //Conflict: show message from server
            case 422: // unprocessable entity: normally the errors are shown via form.markInvalid.
                // If not, we add up the error message with info from the payload
                var errorsToUse;
                if(json.errorMessage && json.errorsTranslated) {
                    Ext.Logger.warn('Original Error (a translated version was shown to the user): ' + json.errorMessage);
                    errorsToUse = json.errorsTranslated;
                } else if(json.errorMessage && json.errors) {
                    errorsToUse = json.errors;
                } else {
                    Editor.MessageBox.addError(appendServerMsg(str["409"]), errorCode);
                    return;
                }
                json.errorMessage = [];
                Ext.Object.each(errorsToUse, function(field, errors) {
                    Ext.Object.each(errors, function(key, error) {
                        if(Ext.isArray(error)){
                            error = error.join('<br/>')
                        }
                        json.errorMessage.push(error);
                    });
                });
                Editor.MessageBox.addError(str["409"]+'<ul><li>'+json.errorMessage.join('</li><li>')+'</li></ul>', errorCode);
                return;

            case 406: //Not Acceptable: show message from server
            case 502: //Bad Gateway → the real error is coming from a requested third party system
                Editor.MessageBox.addError(getServerMsg(), errorCode);
                Ext.Array.each(json.errors, function(item){
                    // TODO FIXME: by doing this in a loop, only the last is shown
                    //  This also overwrites the "main" messages above. Is that wanted ??
                    Editor.MessageBox.getInstance().showDirectError(item.msg, item.data, errorCode);
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
        if(json && json.errorMessage) {
            statusText += ': <br>'+json.errorMessage;
        }
        Editor.MessageBox.addError(text+tpl.apply([_status, statusText]), errorCode);
    },
    renderHtmlMessage: function(title, response){
        var result = '<h1>'+title+'</h1>';
        
        if(response.errorMessage && response.errorMessage.length > 0) {
            result += '<p>'+response.errorMessage+'</p>';
        }
        if(response.errors && response.errors.length > 0) {
            Ext.Array.each(response.errors, function(item){
                result += '<p>'+item+'</p>';
            });
        }
        return result;
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
            if(!request._operation._response){
                return;
            }
            var headers = request._operation._response.getAllResponseHeaders(),
                version = headers['x-translate5-version'];

            if(version !== Editor.data.app.version && version) {
                var {update_title, update_msg, reload} = Editor.controller.ServerException.prototype.strings;
                Ext.MessageBox.show({
                     title: update_title,
                     msg: Ext.String.format(update_msg, version),
                     icon: Ext.MessageBox.WARNING,
                     buttons: Ext.MessageBox.OK,
                     buttonText: {ok: reload},
                     fn: btnId => location.reload(),
                });
            }
    		//FIXME neuen WebSocket Issue anlegen, der sammelt was alles auf websockets umgebaut werden kann wenn diese Fix aktiv sind.
    		// Diese Funktionalität gehört da mit dazu!
			if(headers['x-translate5-shownotice'] || headers['x-translate5-maintenance-message']){
                var mntpnl = Ext.getCmp('maintenancePanel') || Ext.first('viewport')?.add({ // create if not exists
                    xtype:'maintenancePanel',
                    id:'maintenancePanel',
                    region:'north',
                    weight: 100,
                });
                mntpnl && mntpnl.update({
                    date: headers['x-translate5-shownotice'],
                    msg : headers['x-translate5-maintenance-message']
                });
    		}
        },
        constructor: function() {
            this.callOverridden(arguments);
            this.on('exception', function(proxy, resp, op){
                if(op.preventDefaultHandler){
                    op.response = resp; //Operation does not contain response by default
                } else {
                    Editor.app.getController('ServerException').handleException(resp);
                }
            });
        }
    });
    
});
