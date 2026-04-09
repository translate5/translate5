export default class Templating {
    /**
     * @param {string} idPrefix
     */
    constructor(idPrefix) {
        this.idPrefix = idPrefix;

        this.intImgTpl = this.createTemplate(
            '<img id="' + this.idPrefix + '{key}" class="{type}" title="{title}" alt="{text}" src="{path}" data-length="{length}" data-pixellength="{pixellength}" data-tag-number="{nr}"/>'
        );
        this.intImgTplQid = this.createTemplate(
            '<img id="' + this.idPrefix + '{key}" class="{type}" title="{title}" alt="{text}" src="{path}" data-length="{length}" data-pixellength="{pixellength}" data-t5qid="{qualityId}" />'
        );
        this.intSpansTpl = this.createTemplate(
            '<span title="{title}" class="short">{shortTag}</span>' +
            '<span data-originalid="{id}" data-length="{length}" class="full">{text}</span>'
        );
        this.intNumberSpansTpl = this.createTemplate(
            '<span title="{title}" class="short">{shortTag}</span>' +
            '<span data-originalid="{id}" data-length="{length}" data-source="{source}" data-target="{target}" class="full"></span>'
        );
        this.termSpanTpl = this.createTemplate(
            '<span class="{className}" title="{title}"></span>'
        );
        this.termSpanTplQid = this.createTemplate(
            '<span class="{className}" title="{title}" data-t5qid="{qualityId}"></span>'
        );
    }

    /**
     * Create a template object with apply method
     * @param {string} template - Template string with {placeholder} syntax
     * @returns {Object} Template object with apply method
     */
    createTemplate(template) {
        return {
            apply: (data) => {
                return template.replace(/{(\w+)}/g, (match, key) => {
                    return data[key] !== undefined ? data[key] : '';
                });
            }
        };
    }
}
