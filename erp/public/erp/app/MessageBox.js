/**
 * Message Box Komponente, stellt Methoden bereit um Info Nachrichten für den Benutzer dem Nachrichten Stack hinzuzufügen, diese werden dann eingeblendet.
 * @class Erp.MessageBox
 */
Ext.define('Erp.MessageBox',{
  instance: null,
  msgCt: null,
  titles: {
	  ok: 'Ok!',
	  error: 'Fehler!',
	  directError: 'Es ist ein Fehler aufgetreten!',
	  warning: 'Warnung!',
	  notice: 'Hinweis!'
  },
  statics: {
    SUCCESS: 'ok',
    ERROR: 'error',
    WARNING: 'warning',
    INFO: 'notice',
    addSuccess: function(msg,delayFactor) {
      Erp.MessageBox.getInstance().addMessage(msg, Erp.MessageBox.SUCCESS, delayFactor);
    },
    addError: function(msg) {
      //Erp.MessageBox.getInstance().addMessage(msg, Erp.MessageBox.ERROR);
      //display a alert instead:
      Erp.MessageBox.getInstance().showDirectError(msg);
    },
    addInfo: function(msg, delayFactor) {
      Erp.MessageBox.getInstance().addMessage(msg, Erp.MessageBox.INFO, delayFactor);
    },
    //mostly used to display other messages delivered by REST
    addByOperation: function(operation) {
        if(!operation.success) {
            return; //on a real server error the message is processed by serverexception!
        }
        var resp = operation.response,
            json;
        if(!resp || !resp.responseText) {
            return;
        }
        json = Ext.JSON.decode(resp.responseText);
        if(!json.errors) {
            return;
        }
        Ext.Array.each(json.errors, function(error){
            Erp.MessageBox.getInstance().addMessage(error.msg, error.type || Erp.MessageBox.INFO);
        });
    },
    getInstance: function() {
      if(!Erp.MessageBox.instance){
        Erp.MessageBox.instance = new Erp.MessageBox();
      }
      return Erp.MessageBox.instance; 
    }
  },
  constructor: function(config) {
    // create the msgBox container.  used for App.setAlert
    this.msgCt = Ext.core.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
    this.msgCt.setStyle('position', 'absolute');
    this.msgCt.setStyle('z-index', 30000);
    this.msgCt.setWidth(300);
  },
  /**
   * zeugt die Nachricht an, und berechnet die Anzeigedauer der Nachricht anhand der Textlänge.
   * @private 
   * @param {String} msg
   * @param {String} status
   * @param {Float} factor optional delay factory of message, overrides default value from ini
   */
  addMessage : function(msg, status, factor) {
      // add some smarts to msg's duration (div by 13.3 between 3 & 9 seconds)
      var delay = Math.min(15, Math.max(3, (msg.length / 13.3)));
      factor = factor || Erp.data.messageBox.delayFactor;
      factor = (factor && parseFloat(factor) || 1);
      delay = delay * 1000 * factor;

      this.msgCt.alignTo(document, 't-t');
      var appendedBox = Ext.core.DomHelper.append(this.msgCt, {style: {visibility: 'hidden'},html:this.buildMessageBox(status, msg)}, true);
      appendedBox.slideIn('t').animate({duration: (delay)}).ghost("t", {remove:true});
  },
  buildMessageBox : function(status, msg) {
	  var title = this.titles[status];
      return [
          '<div class="app-msg">',
          '<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>',
          '<div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc"><h3 class="x-icon-text icon-status-' + status + '">', title, '</h3>', msg, '</div></div></div>',
          '<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>',
          '</div>'
      ].join('');
  },
  showDirectError: function(msg) {
      var box = Ext.MessageBox;
      box.show({
          title: this.titles.directError,
          msg: msg,
          buttons: box.OK,
          icon: box.ERROR
      });
  },
  stacktrace: function() { 
    function st2(f,count) {
      count = count + 1;
      if(count>30){
          return [f.toString().split('(')[0].substring(9) + '(' + Array.prototype.slice.call(f.arguments).join(',') + ')'];
      }
      return !f ? [] : 
          st2(f.caller,count).concat([f.toString().split('(')[0].substring(9) + '(' + Array.prototype.slice.call(f.arguments).join(',') + ')']);
    }
    return st2(arguments.callee.caller,0);
}
});