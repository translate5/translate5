
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

/***
 * Info message which will be shown to the user when he selects more than 4 characters in the editor.
 */
Ext.define('Editor.view.task.QuickSearchInfoMessage',{
    messageShown: false, // Flag if the info message for f3 and alt+f3 message is shown (search in concordence/synonym)
    synonymGridExist: false,

    searchInfoTooltip: null,

    /***
     * Show the info tost message. The message will only be shonw once per session.
     */
    showMessage: function (){
        var me = this;
        if(me.messageShown){
            me.hideAndDestroy();
            return;
        }
        var msg = Editor.data.l10n.quickSearchInfoMessage.concordenceSearchMessage,
            tooltipTarget = me.getTooltipTarget();

        if( !tooltipTarget){
            return;
        }
        if(me.synonymGridExist){
            msg += Editor.data.l10n.quickSearchInfoMessage.synonymSearchMessage;
        }

        if( !me.searchInfoTooltip){
            me.searchInfoTooltip = Ext.create('Ext.tip.ToolTip', {
                target: tooltipTarget,
                defaultAlign: 'b30-t70',
                dismissDelay: 0,
                showDelay: 0,
                autoHide: false,
                anchor: true,
                closable:true,
            });

            // handle the hiding of the component. If autoHide is used,the tooltip will be closed after the user
            // leaves the row editor component with the mouse. This will ensure that the tooltip is hidden after 5 sec
            me.searchInfoTooltip.on('show', function(){

                var timeout,
                    toolTipEl = me.searchInfoTooltip.getEl();

                toolTipEl.on('mouseout', function(){
                    timeout = window.setTimeout(function(){
                        me.hideAndDestroy();
                    }, 5000);
                });

                Ext.get(tooltipTarget).on('mouseout', function(){
                    timeout = window.setTimeout(function(){
                        me.hideAndDestroy();
                    }, 5000);
                });

            });
        }

        me.searchInfoTooltip.setHtml(msg);

        me.searchInfoTooltip.show();
        me.messageShown = true;
    },

    /***
     * Hide and destroy the tooltip component
     */
    hideAndDestroy: function (){
        var me = this;
        if(me.searchInfoTooltip){
            me.searchInfoTooltip.hide();
            Ext.destroy(me.searchInfoTooltip);
            me.searchInfoTooltip = null;
        }
    },

    /***
     * Get the component target for the info message. In this case it is row editor row dom element
     */
    getTooltipTarget:function(){
        var editor = Ext.ComponentQuery.query('#roweditor')[0];

        if(editor === undefined){
            return null;
        }
        return editor.context.row;
    },

});