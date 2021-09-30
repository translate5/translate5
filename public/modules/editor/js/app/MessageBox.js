
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
    addWarning: function(msg,delayFactor) {
        Editor.MessageBox.getInstance().addMessage(msg, Editor.MessageBox.WARNING, delayFactor);
    },
    /**
     * @param {String} msg
     * @param {String} errorCode 
     */
    addError: function(msg, errorCode) {
      //Editor.MessageBox.getInstance().addMessage(msg, Editor.MessageBox.ERROR);
      //display a alert instead:
      Editor.MessageBox.getInstance().showDirectError(msg, null, errorCode);
    },
    addInfo: function(msg, delayFactor) {
      Editor.MessageBox.getInstance().addMessage(msg, Editor.MessageBox.INFO, delayFactor);
    },
    //mostly used to display other messages delivered by REST
    addByOperation: function(operation) {
        if(!operation.success) {
            return; //on a real server error the message is processed by serverexception!
        }
        this.addByResponse(operation.getResponse());
    },
    addByResponse: function(resp) {
        var json, tpl;
        if(!resp || !resp.responseText) {
            return;
        }
        json = Ext.JSON.decode(resp.responseText);
        if(!json.errors) {
            return;
        }
        Ext.Array.each(json.errors, function(error){
            if(error.data) {
                if(Ext.isString(error.data)) {
                    error.msg = Ext.String.format(error.msg, error.data);
                }
                else {
                    error.msg = error.msg += Editor.MessageBox.dataTable(error.data);
                }
            }
            Editor.MessageBox.getInstance().addMessage(error.msg, error.type || Editor.MessageBox.INFO);
        });
        
    },
    /**
     * converts the given array to a table with errors
     */
    dataTable: function(data) {
        if(!data) {
            return '';
        }
        var tpl = new Ext.XTemplate([
            '<table class="message-box-data">',
            '<tpl for=".">',
            '<tr><td class="type">{type}: </td><td class="msg">{error}</td></tr>',
            '</tpl>',
            '</table>'
        ]);
        return tpl.applyTemplate(data);
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
    },
    /**
     * returns a debug string to be usable in error popups with the date string and additional data. 
     * Background is, that users often make a screenshot of an error popup, but do not provide an exact date to compare that with the log.
     */
    debugInfoMarkup: function(info) {
        return '<p style="font-size: 10px;color: #808080;font-style: italic;user-select: text;">'+info+' '+Ext.Date.format(new Date(), 'Y-m-d H:i:sO')+'</p>';
    }
  },
  constructor: function(config) {
    // create the msgBox container.  used for App.setAlert
    this.msgCt = Ext.dom.Helper.append(document.body, {id:'msg-div'}, true);
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
      var found, appendedBox = appendedBox = Ext.dom.Helper.append(this.msgCt, {style: {visibility: 'hidden'},html:this.buildMessageBox(status, msg)}, true);
      this.msgElements.push(appendedBox); 
      if(delay < 0) {
          appendedBox.slideIn('t');
      }
      else {
          appendedBox.slideIn('t');
          setTimeout(function(){
              appendedBox.dom && appendedBox.ghost("t", {remove:true});
          }, delay);
      }
      if(this.msgElements.length > 3) {
          found = this.msgElements.shift();
          found.dom && found.ghost("t", {remove:true});
      }
  },
  msgElements: [],
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
  showDirectError: function(msg, data, errorCode) {
      var box = Ext.MessageBox,
          info = [];
      if(data) {
          msg = msg + Editor.MessageBox.dataTable(data);
      }
      if(errorCode) {
        if(Editor.data.errorCodesUrl) {
            errorCode = '<a href="'+Ext.String.format(Editor.data.errorCodesUrl, errorCode)+'" target="_blank">'+errorCode+'</a>'; 
        }
        info.push(errorCode);
      }
      info.push(Editor.data.app.user.login);
      if(Editor.data.task) {
        info.push("tid: "+Editor.data.task.id);
      }
      msg += Editor.MessageBox.debugInfoMarkup(info.join('; '));
      box.show({
          title: this.titles.directError,
          msg: msg,
          buttons: box.OK,
          icon: box.ERROR
      });
  }
});