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

Ext.define('Editor.view.admin.user.AddWindowViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.adminUserAddWindow',
    strings: {
        userSaved: '#UT#Der Änderungen an Benutzer "{0}" wurden erfolgreich gespeichert.',
        userAdded: '#UT#Der Benutzer "{0}" wurde erfolgreich erstellt.',
    },
    listen: {
        component: {
            '#adminUserAddWindow #save-user-btn': {
                click: 'handleUserSave',
            },
            '#adminUserAddWindow #cancel-user-btn': {
                click: 'handleUserCancel',
            },
        },
    },
    handleUserCancel: function () {
        const win = this.getView(),
            form = win.down('form'),
            rec = form.getRecord()
        form.reset();
        rec.reject();
        win.close();
    },
    /**
     * is called after clicking save user
     */
    handleUserSave: function () {
        var me = this,
            win = me.getView(),
            form = win.down('form'),
            basic = form.getForm(),
            rec = form.getRecord();

        if (!basic.isValid()) {
            return;
        }

        //if in first save attempt we got an error from server,
        //and we then disable the password in the second save,
        //the password will be kept in the model, so reject it here
        rec.reject();
        basic.updateRecord(rec);
        win.setLoading(true);
        rec.save({
            //prevent default ServerException handling
            preventDefaultHandler: true,
            failure: function (rec, op) {
                win.setLoading(false);
                var errorHandler = Editor.app.getController('ServerException');
                errorHandler.handleFormFailure(basic, rec, op);

                const response = Ext.decode(op.error.response.responseText);

                if (! response.hasOwnProperty('errorsTranslated')) {
                    return;
                }

                if (response.errorsTranslated && typeof response.errorsTranslated[Symbol.iterator] === 'function') {
                    for (const error of response.errorsTranslated) {
                        if (! error.id || basic.findField(error.id).hidden) {
                            if (error.hasOwnProperty('msg')) {
                                Editor.MessageBox.addWarning(error.msg);

                                continue;
                            }

                            if (typeof error[Symbol.iterator] === 'function') {
                                for (const msg of error) {
                                    Editor.MessageBox.addWarning(msg);
                                }
                            }
                        }
                    }

                    return;
                }

                for (const [key, errors] of Object.entries(response.errorsTranslated)) {
                    if (!basic.findField(key).hidden) {
                        continue;
                    }

                    for (const error of errors) {
                        Editor.MessageBox.addWarning(error);
                    }
                }
            },
            success: function () {
                var user = rec.get('surName') + ', ' + rec.get('firstName') + ' (' + rec.get('login') + ')',
                    msg = win.editMode ? me.strings.userSaved : me.strings.userAdded;
                win.setLoading(false);
                win.close();
                //reload the users store
                rec.store && rec.store.load();
                Editor.MessageBox.addSuccess(Ext.String.format(msg, user));
            },
        });
    },
});
