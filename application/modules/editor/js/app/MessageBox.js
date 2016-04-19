
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * Message Box Komponente, stellt Methoden bereit um Info Nachrichten für den Benutzer dem Nachrichten Stack hinzuzufügen, diese werden dann eingeblendet.
 * @class Editor.MessageBox
 */
Ext.define('Editor.MessageBox',{
  instance: null,
  msgCt: null,
  titles: {
	  ok: '#UT# Ok!',
	  error: '#UT# Fehler!',
	  directError: '#UT# Es ist ein Fehler aufgetreten!',
	  warning: '#UT# Warnung!',
	  notice: '#UT# Hinweis!'
  },
  statics: {
    SUCCESS: 'ok',
    ERROR: 'error',
    WARNING: 'warning',
    INFO: 'notice',
    addSuccess: function(msg,delayFactor) {
      Editor.MessageBox.getInstance().addMessage(msg, Editor.MessageBox.SUCCESS, delayFactor);
    },
    addError: function(msg,msgToServerlog) {
      //Editor.MessageBox.getInstance().addMessage(msg, Editor.MessageBox.ERROR);
      //display a alert instead:
      Editor.MessageBox.getInstance().showDirectError(msg);
      if(msgToServerlog !== undefined){
            Ext.Ajax.request({
                   url: Editor.data.pathToRunDir + '/error/jserror',
                   params: { jsError: msgToServerlog + ' Stacktrace: ' + Editor.MessageBox.getInstance().stacktrace() }
            });
      }
    },
    addInfo: function(msg, delayFactor) {
      Editor.MessageBox.getInstance().addMessage(msg, Editor.MessageBox.INFO, delayFactor);
    },
    //mostly used to display other messages delivered by REST
    addByOperation: function(operation) {
        if(!operation.success) {
            return; //on a real server error the message is processed by serverexception!
        }
        var resp = operation.getResponse(),
            json;
        if(!resp || !resp.responseText) {
            return;
        }
        json = Ext.JSON.decode(resp.responseText);
        if(!json.errors) {
            return;
        }
        Ext.Array.each(json.errors, function(error){
            Editor.MessageBox.getInstance().addMessage(error.msg, error.type || Editor.MessageBox.INFO);
        });
    },
    getInstance: function() {
      if(!Editor.MessageBox.instance){
        Editor.MessageBox.instance = new Editor.MessageBox();
      }
      return Editor.MessageBox.instance; 
    },
    showInitialMessages: function() {
        var msgs = Editor.data.messageBox && Editor.data.messageBox.initialMessages;
        if(!msgs || msgs.length == 0) {
            return;
        }
        Ext.each(msgs, function(msg){
            Editor.MessageBox.addInfo(msg);
        });
    }
  },
  constructor: function(config) {
    // create the msgBox container.  used for App.setAlert
    this.msgCt = Ext.core.DomHelper.append(document.body, {id:'msg-div'}, true);
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
      factor = factor || Editor.data.messageBox.delayFactor;
      factor = (factor && parseFloat(factor) || 1), 
      // add some smarts to msg's duration (div by 13.3 between 3 & 9 seconds)
      delay = msg.length / 13.3;
      delay = Math.min(15, Math.max(3, delay));
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