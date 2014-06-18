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
/**
 * @class Editor.model.ModelOverride
 * @overrides Ext.data.Model
 * extends the default model with the methods: 
 * Editor.model.ModelOverride::reload(config)
 * Editor.model.ModelOverride::destroyVersioned(version, config)
 */
Ext.define('Editor.model.ModelOverride', {
    override: 'Ext.data.Model',
    /**
     * reloads a single model instance
     * @param {Object} config object, same as for load
     * @return {Ext.data.Model}
     */
    reload: function(config) {
        config = Ext.apply({}, config);
        var me = this,
            scope = config.scope || this,
            success = config.success || Ext.emptyFn;
        
        config.success = function(rec) {
            Ext.callback(success, scope, arguments);
            me.set(rec.data);
            me.suspendEvents();
            me.commit();
            me.resumeEvents();
        };
        return me.self.load(me.get(me.idProperty), config);
    },
    /**
     * save method with version check: give the entity or version to be compared against
     * @see TRANSLATE-206 for more information
     * @param {Integer}|{Ext.data.Model} version version number or model with version to be compared against on the server
     * @param {Object} config object, same as for normal destroy
     * @return {Ext.data.Model}
     */
    saveVersioned: function(version, config) {
        var me = this;
        me.set('entityVersion', me.parseVersion(version));
        return me.save(config);
    },
    /**
     * destroy method with version check: give the entity or version to be compared against
     * @see TRANSLATE-206 for more information
     * @param {Integer}|{Ext.data.Model} version version number or model with version to be compared against on the server
     * @param {Object} config object, same as for normal destroy
     * @return {Ext.data.Model}
     */
    destroyVersioned: function(version, config) {
        var me = this,
            p = me.getProxy(),
            result;
        if(! p.headers) {
            p.headers = {};
        }
        version = me.parseVersion(version);
        p.headers['Mqi-Entity-Version'] = version;
        result = me.destroy(config);
        delete p.headers['Mqi-Entity-Version'];
        return result;
    },
    /**
     * returns the version of the given mixed value
     * @param mixed version can be a model or an integer
     * @return {Integer}
     */
    parseVersion: function(version){
        if(Ext.isNumeric(version)) {
            return version;
        }
        if(Ext.isObject(version) && version.isModel && version.get('entityVersion') !== undefined) {
            return version.get('entityVersion');
        }
        Ext.Error.raise('Given version is no integer and no Model, or Model has no entityVersion field!');
    }
});