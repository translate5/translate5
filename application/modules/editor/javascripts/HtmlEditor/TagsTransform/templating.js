export default class Templating {
    /**
     * @param {string} idPrefix
     */
    constructor(idPrefix) {
        this.idPrefix = idPrefix;

        // TODO remove dependency on EXTJS
        this.intImgTpl = new Ext.Template([
            '<img id="' + this.idPrefix + '{key}" class="{type}" title="{title}" alt="{text}" src="{path}" data-length="{length}" data-pixellength="{pixellength}" data-tag-number="{nr}"/>'
        ]);
        this.intImgTplQid = new Ext.Template([
            '<img id="' + this.idPrefix + '{key}" class="{type}" title="{title}" alt="{text}" src="{path}" data-length="{length}" data-pixellength="{pixellength}" data-t5qid="{qualityId}" />'
        ]);
        this.intSpansTpl = new Ext.Template([
            '<span title="{title}" class="short">{shortTag}</span>',
            '<span data-originalid="{id}" data-length="{length}" class="full">{text}</span>'
        ]);
        this.intNumberSpansTpl = new Ext.Template([
            '<span title="{title}" class="short">{shortTag}</span>',
            '<span data-originalid="{id}" data-length="{length}" data-source="{source}" data-target="{target}" class="full"></span>'
        ]);
        this.termSpanTpl = new Ext.Template([
            '<span class="{className}" title="{title}"></span>'
        ]);
        this.termSpanTplQid = new Ext.Template([
            '<span class="{className}" title="{title}" data-t5qid="{qualityId}"></span>'
        ]);
        this.intImgTpl.compile();
        this.intImgTplQid.compile();
        this.intSpansTpl.compile();
        this.intNumberSpansTpl.compile();
        this.termSpanTpl.compile();
        this.termSpanTplQid.compile();
    }
}
