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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Editor.controller.PreloadImages behandelt das komplette Image Preloading der Tags
 * @class Editor.controller.PreloadImages
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.PreloadImages', {
    extend: 'Ext.app.Controller',
    ERROR_TITLE: 'Fehler',
    ERROR_TEXT: 'Fehler beim Speichern oder beim Auslesen von Daten. Bitte wenden Sie sich an unseren Support!',
    start:0,
    runner: new Ext.util.TaskRunner(),
    task:null,
    success:true,//ensure, that the next preload will not be started, before the current one has been succeeded
    preload : function(){
        var me = this;
        if(me.success){
            me.success = false;
            Ext.Ajax.request({
              scope: me,
              url: Editor.data.pathToRunDir+'/'+Editor.data.zfModule+'/index/preloadimages/start/'+me.start,
              method: 'get',
              success: function(response){
                var json = Ext.decode(response.responseText);
                Ext.Array.each(json.images2preload, function(imgName) {
                   var image = Ext.create('Ext.Img', {
                        src: Editor.data.segments.fullTagPath+imgName,
                        renderTo: Ext.getDom('imagePreload')
                    });
                });
                me.start = json.start;
                if (json.goOn === false){
                    me.runner.stop(me.task);
                }
                me.success = true;
              },
              failure: function(){
                  Ext.Msg.alert(Editor.controller.PreloadImages.prototype.ERROR_TITLE, Editor.controller.PreloadImages.prototype.ERROR_TEXT);
              }
            });
        }
    },
    init: function() {
        var me = this,
        delayPreload = new Ext.util.DelayedTask(function(){
            me.task = {
                scope:me,
                run: me.preload,
                interval: 2100 //1 second
            };
            me.runner.start(me.task);
        },me);

    delayPreload.delay(10000);
    }
    
});