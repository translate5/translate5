/**
 * This Controller integrates into Ext.data.proxy.Server through constructor overriding.
 * It handles all server exceptions if no Operation.callback is given.
 * @class Erp.controller.ServerException
 * @extends Ext.app.Controller
 */
Ext.define('Erp.controller.ServerException', {
    extend: 'Ext.app.Controller',
    strings: {
        "403": 'Für die angeforderten Daten fehlen Ihnen die nötigen Berechtigungen!',
        "403_destroy": 'Zum Löschen der geforderten Daten fehlen Ihnen die nötigen Berechtigungen!',
        "403_create": 'Zum Speichern von neuen Daten fehlen Ihnen die nötigen Berechtigungen!',
        "403_edit": 'Zum Speichern der bearbeiten Daten fehlen Ihnen die nötigen Berechtigungen!',
        "404": 'Die angeforderten / zu bearbeitenden Daten wurden nicht gefunden!',
        "401_title": 'Erneute Anmeldung',
        "401_msg": 'Ihre Sitzungsdaten sind abgelaufen. Sie werden nun zur Anmeldeseite weitergeleitet.',
        "406": 'Es ist ein Fehler aufgetreten!',
        "409": 'Ihre Daten konnten nicht gespeichert werden, beim Speichern kam es zu einem Konflikt!',
        title: 'Fehler',
        text: 'Fehler beim Speichern oder beim Auslesen von Daten. Bitte wenden Sie sich an unseren Support!',
        timeout: 'Die Anfrage über das Internet an den Server dauerte zu lange. Dies kann an Ihrer Internetverbindung oder an einer Überlastung des Servers liegen. Bitte versuchen Sie es erneut.',
        serverMsg: '<br />Meldung vom Server: <i>{0} {1}</i>'
    },
    
    /**
     * Interceptor handler used in proxy exceptions
     * @param {Object} response
     * @returns void
     */
    handleException: function(response){
        var status = (response && response.status ? response.status : -1),
            statusText = (response && response.statusText ? response.statusText : '');
        this.handleFailedAjaxRequest(status, statusText, response);
    },
    
    
    /**
     * handles / displays the given error
     * @param {Integer} status
     * @param {String} statusText
     * @param {Object} response
     */
    handleFailedAjaxRequest: function(status, statusText, response) {
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
                statusText = appendServerMsg(str.timeout);
                if(response && response.request) {
                    statusText += '<p style="font-size: 10px;color: #808080;font-style: italic;user-select: text;">';
                    statusText += 'URL: '+response.request.url+' '+Ext.Date.format(new Date(), 'Y-m-d H:i:sO')+'</p>';
                }
                Ext.Msg.alert(str.title, statusText);
                return;
            //@todo remove this specific 405 handler with TRANSLATE-94
            case 405: //Method Not Allowed: the used HTTP Method is not allowed
                var req = response.request,
                    regex = new RegExp('^'+Erp.data.restpath+'taskuserassoc');
                if(req && req.options && req.options.method == 'DELETE' && regex.test(req.options.url)) {
                    if(req.options && req.options.records && req.options.records.length > 0) {
                        //for this Array.difference see TRANSLATE-95 it should be changed with TRANSLATE-94
                        var assocs = Ext.getStore('admin.TaskUserAssocs'),
                            removed = assocs.getRemovedRecords();
                        assocs.removed = Ext.Array.difference(removed, req.options.records);
                    }
                    Erp.MessageBox.addError(appendServerMsg(str["405_del_assoc"]));
                    return;
                }
                Ext.Msg.alert(str.title, text+tpl.apply([status, statusText]));
                return;
            case 403: //Forbidden: authenticated, but not allowed to see the specific resource 
            case 404: //Not Found: Resource does not exist
                if(str[_status+'_'+action]) {
                    _status = _status+'_'+action;
                }
                Erp.MessageBox.addError(appendServerMsg(str[_status]));
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
                Erp.MessageBox.addError(appendServerMsg(str["409"]));
                return;
            case 406: //Not Acceptable: show message from server
                Erp.MessageBox.addError(getServerMsg());
            case 502: //Bad Gateway → the real error is coming from a requested third party system
                Ext.Array.each(json.errors, function(item){
                    Erp.MessageBox.getInstance().showDirectError(item.msg, item.data);
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
        this.handleFailedRequest(operation);
        return false;
    },
    /**
     * Used to return the above handleCallback Method, but doing also a form markinvalid with the given form
     * @param {Ext.form.Panel} form the form where errors should be marked
     * @return {Function} returns the handleCallback correctly binded to this controller
     */
    invokeFormCallback: function(form, callback) {
        var me = this;
        
        return function(records, op, success) {
            var error;
            (callback || Ext.emptyFn)(records, op, success);
            if(! op.success && op.error.response && op.error.response.responseText) {
                
                error = Ext.decode(op.error.response.responseText);
                if(error.errors && op.error && op.error.status == '400') {
                    form.getForm().markInvalid(error.errors);
                }
            }
            me.handleCallback.apply(me, arguments);
        };
    },
    /**
     * handles / displays the given error
     * @param {Ext.data.operation.Operation} operation
     */
    handleFailedRequest: function(operation) {

        //this.handleFailedRequest(status, statusText, response);
        var me = this,
            str = me.strings,
            error = operation.getError(),
            status = (error && error.status ? error.status : -1),
            statusText = (error && error.statusText ? error.statusText : ''),
            _status = status.toString(),
            response = error.response,
            text = str.text,
            respText = response && response.responseText || '{"errors": [{"_errorMessage": "unknown"}]}',
            tpl = new Ext.Template(str.serverMsg),
            action = response && response.request && response.request.options.action,
            getServerMsg = function() {
                var json = Ext.JSON.decode(respText);
                return json.errors[0]._errorMessage;
            },
            appendServerMsg = function(msg) {
                var serverMsg = getServerMsg();
                if(serverMsg == 'unknown') {
                    return msg;
                }
                return msg + tpl.apply(['', getServerMsg()]);
            };

        switch(status) {
            case -1:
                //ignore the canceled requests (they return staus -1)
                if(statusText==="transaction aborted"){
                    return;
                }
                Ext.Msg.alert(str.title, appendServerMsg(str.timeout));
                return;
            case 400: //Bad Request: Error is shown in form, no message currently.
                return;
            case 403: //Forbidden: authenticated, but not allowed to see the specific resource 
            case 405: //Method Not Allowed: the used HTTP Method is not allowed
                Ext.Msg.alert(str.title, text+tpl.apply([status, statusText]));
                return;
            case 404: //Not Found: Ressource does not exist
                if(str[_status+'_'+action]) {
                    _status = _status+'_'+action;
                }
                Erp.MessageBox.addError(appendServerMsg(str[_status]));
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
                Erp.MessageBox.addError(appendServerMsg(str["409"]));
                return;
            case 406: //Not Acceptable: show message from server
                Erp.MessageBox.addError(getServerMsg());
                return;
        }
        Ext.Msg.alert(str.title, text+tpl.apply([status, statusText]));
    },
    /**
     * Helper to redirect to the login page
     */
    handleNotVerified: function(){
        location.href = Erp.data.loginUrl;
    }
},
           /**
 * Init after class creation
 * bind to all ServerProxy exceptions, and call unified handler if no Operation Callback is defined.
 * If a callback is defined, the default controller method handleCallbackHttpFailure can be called:
 * var errorHandler = Erp.app.getController('ServerException');
 * errorHandler.handleCallback.apply(errorHandler, arguments); 
 */
           function() {
               //override Ext.data.proxy.Server
               Ext.data.proxy.Server.override({
                   constructor: function() {
                       this.callOverridden(arguments);
                       this.on('exception', function(proxy, request, op){
                           if(!op.initialConfig.failure){
                               Erp.app.getController('ServerException').handleFailedRequest(op);
                           }
                       });
                   }
               });
           });