/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
/**
 * Set of Strings with a two-phase lifecycle:
 * 1) Setup phase: Normal set functions are effective, items can be added fast
 * 2) Cache phase: Set functions will update the cached strval property
 * @property {String} strval A cached string representation of the set
 */
class StringSet extends Set {
    strval
    cacheMode = {
        addAndUpdate(v){
            var ret = super.add(v)
            this.updateStrVal()
            return ret
        },
        deleteAndUpdate(v){
            var ret = super.delete(v)
            this.updateStrVal()
            return ret
        },
        clearAndUpdate(){
            super.clear()
            this.updateStrVal()
        },
        toStringCached(){
            return this.strval
        },
    }
    constructor(iterable) {
        super(iterable);
        this.toJSON = this.toString
    }
    updateStrVal(){
        this.strval = Array.from(this).join(', ')
    }

    /**
     * Starts the cache phase. Set operations update the cached strval property.
     */
    startCachedMode(){
        Object.assign(this, this.cacheMode);
        this.toJSON = this.toString
    }

    /**
     * Trigger cached mode on first call
     */
    toString(){
        this.updateStrVal()
        this.startCachedMode();
        return this.strval
    }
}

Ext.ns('Editor.util.type').StringSet = StringSet; // Satisfy Ext.require
