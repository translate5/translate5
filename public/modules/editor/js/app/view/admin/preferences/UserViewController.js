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

Ext.define('Editor.view.admin.preferences.UserViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.preferencesUser',
    listen: {
        component: {
            '#preferencesUserWindow #saveBtn': {
                click: 'handleSave'
            },
            '#preferencesUserWindow #cancelBtn': {
                click: 'handleCancel'
            },
            '#languageSwitch': {
                change: 'changeLocale'
            },
            '#uiTheme' : {
                change: 'onUiThemeChange'
            }
        }
    },

    /**
     * Speichert die Einstellungen und schließt das Fenster
     */
    handleSave: function () {
        var me = this,
            form = me.getView().down('form').getForm(),
            pw = form.getValues().passwd,
            user = Editor.app.authenticatedUser;
        if (form.isValid()) {
            user.set('passwd', pw);
            user.save({
                url: Editor.data.restpath + 'user/authenticated',
                success: function () {
                    Editor.MessageBox.addSuccess(me.strings.pwSave);
                }
            });
        }
    },

    handleCancel: function () {
        this.getView().down('form').reset();
    },

    /***
     * Change translate5 language on language dropdown change
     * @param combo
     * @param locale
     */
    changeLocale: function (combo, locale) {
        Editor.app.setTranslation(locale);
    },

    /***
     * On ui change combo event handler
     * @param combo
     * @param newValue
     */
    onUiThemeChange:function(combo, newValue){
        var uiThemesRecord = Editor.app.getUserConfig('extJs.cssFile',true);
        uiThemesRecord.set('value',newValue);
        uiThemesRecord.save({
            callback:function(){
                location.reload();
            }
        });
    }

});
