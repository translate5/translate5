
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

Ext.define('Editor.view.admin.token.CreateTokenWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminCreateTokenWindowViewController',
    listen: {
        component: {
            '#adminCreateTokenWindow #create-btn': {
                click: 'handleCreateToken'
            },
            '#adminCreateTokenWindow #cancel-btn': {
                click: 'handleUserCancel'
            }
        }
    },
    handleUserCancel: function() {
        var win = this.getView();
        win.down('form').getForm().reset();
        win.close();
    },
    /**
     * is called after clicking save user
     */
    handleCreateToken: function() {
        var me = this,
            window = me.getView(),
            tokenForm = window.down('form');

        if (!tokenForm.isValid()) {
            return;
        }

        window.setLoading(true);
        tokenForm.submit({
            timeout: 3600, //1h, is seconds here, ensure upload of bigger files
            url: Editor.data.restpath + 'token',
            scope: me,
            success: function (form, submit) {
                var msg = Ext.String.format(Editor.data.l10n.token.token_created_message, submit.result.token);
                Ext.ComponentQuery.query('#tokenGrid').pop().getStore().reload();
                window.setLoading(false);
                window.close();

                Ext.Msg.show({
                    msg : msg,
                    closable: false,
                    buttons: Ext.MessageBox.OK,
                    buttonText: {
                        ok: Editor.data.l10n.token.copy_and_close_btn
                    },
                    fn : function (){
                        if (navigator?.clipboard?.writeText) {
                            navigator.clipboard.writeText(submit.result.token);

                            return;
                        }

                        console.info('The Clipboard API is not available.');

                        const el = document.createElement('textarea');
                        el.value = submit.result.token;
                        el.setAttribute('readonly', '');
                        el.style.position = 'absolute';
                        el.style.left = '-9999px';
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                    },
                    icon : Ext.Msg.WARNING
                });
            },
            failure: function (form, submit) {
                var res = submit.result;
                window.setLoading(false);
                //submit results are always state 200.
                //If success false and errors is an array, this errors are shown in the form directly,
                // so we dont need the handleException
                if (!res || res.success || !Ext.isArray(res.errors)) {
                    Editor.app.getController('ServerException').handleException(submit.response);

                    return;
                }

                if (Ext.isArray(res.errors)) {
                    form.markInvalid(res.errors);
                    me.showGeneralErrors(res.errors);
                }
            }
        });
    }
});
