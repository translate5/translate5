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