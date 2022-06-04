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
 * Set of Strings optimized for toString() performance. Thus suited well as value in
 * @link Ext.panel.Grid
 */
class StringSet extends Set {
    strval = ''

    add(v){
        this.strval += (this.strval && ', ') + v
        return super.add(v)
    }

    delete(v){
        var ret = super.delete(v)
        this.strval = Array.from(this).join(', ')
        return ret
    }

    clear(){
        this.strval = ''
        return super.clear()
    }

    /**
     * Optimized
     * @return {String}
     */
    toString(){
        return this.strval
    }

    constructor(iterable){
        super(iterable);
        this.strval = Array.from(this).join(', ')
        this.toJSON = this.toString
    }
}

Ext.ns('Editor.util.type').StringSet = StringSet; // Satisfy Ext.require
