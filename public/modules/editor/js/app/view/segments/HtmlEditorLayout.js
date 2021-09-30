
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
 * Layout class for {@link Ext.form.field.HtmlEditor} fields. Sizes textarea and iframe elements.
 * @private
 */
Ext.define('Editor.view.segments.HtmlEditorLayout', {
    extend: 'Ext.layout.component.field.HtmlEditor',
    alias: ['layout.htmleditorlayout'],

    type: 'htmleditor',

    /**
     * removing toolbar
     */
    beginLayout: function(ownerContext) {
        var owner = this.owner,
            dom;
            
        // In gecko, it can cause the browser to hang if we're running a layout with
        // a heap of data in the textarea (think several images with data urls).
        // So clear the value at the start, then re-insert it once we're done
        if (Ext.isGecko) {
            dom = owner.textareaEl.dom;
            this.lastValue = dom.value;
            dom.value = '';
        }
        //don't call the parent beginLayout, because this contains the evil toolbar call
        this.superclass.superclass.beginLayout.apply(this, arguments);


        ownerContext.inputCmpContext = ownerContext.context.getCmp(owner.inputCmp);
        ownerContext.bodyCellContext = ownerContext.getEl('bodyEl');
        ownerContext.textAreaContext = ownerContext.getEl('textareaEl');
        ownerContext.iframeContext   = ownerContext.getEl('iframeEl');
    }
});