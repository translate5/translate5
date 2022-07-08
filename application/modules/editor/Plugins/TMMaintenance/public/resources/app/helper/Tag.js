Ext.define('TMMaintenance.helper.Tag', {
    constructor: function () {
        this.createRuler();
    },

    transform: function (source) {
        let result = source;

        result = this.transformPaired(result);

        return this.transformSingle(result);
    },

    reverseTransform: function (source) {
        let tagsRe = /<img[^>]*>/gm;
        let matches = source.match(tagsRe);
        let result = source;

        if (null === matches) {
            return result;
        }

        let index = 1;
        matches.forEach(function (match) {
            let params = match.match(/data-original="(.+)" /);
            let replace = decodeURI(params[1]);

            result = result.replace(match, replace);

            index++;
        });

        return result;
    },

    highlight: function (source, search) {
        let tagsRe = /<[^>]*>/gm;
        let tagsProtect = '\x0f';
        let matches = source.match(tagsRe);

        if (null === matches) {
            return source;
        }

        let result = source.replace(tagsRe, tagsProtect);
        let searchRegexp = new RegExp(search, 'gi');

        result = result.replace(searchRegexp, function (item) {
            return '<span class="highlight">' + item + '</span>';
        }, this);

        // restore protected tags
        matches.forEach(function (match) {
            result = result.replace(tagsProtect, match);
        });

        return result;
    },

    transformPaired: function (source) {
        let tagsRe = /<[^>]*>/gm;
        let matches = source.match(tagsRe);
        let result = source;
        let me = this;

        if (null === matches) {
            return result;
        }

        matches.forEach(function (match) {
            let tagMatches = match.match(/<(bpt|ept).*i="(\d+)"/);

            if (null === tagMatches) {
                return;
            }

            let svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + (me.measureWidth('<' + tagMatches[2] + '>') + 2) + '">';
            svg += '<rect width="100%" height="100%" fill="rgb(207,207,207)" rx="3" ry="3"/><text x="0.25em" y="1em" font-size="13px">';
            svg += Ext.String.htmlEncode('&lt;' + (tagMatches[1] === 'ept' ? '/' : '') + tagMatches[2] + '&gt;') + '</text></svg>';

            let replace = '<img src="data:image/svg+xml;charset=utf-8,' + encodeURI(svg);
            replace += '" data-tag-type="' + (tagMatches[1] === 'bpt' ? 'open' : 'close') + '" data-tag-id="' + tagMatches[2] + '" data-original="' + encodeURI(match) + '" class="tag"/>';

            result = result.replace(match, replace);
        });

        return result;
    },

    transformSingle: function (source) {
        let tagsRe = /<[^>]*>/gm;
        let matches = source.match(tagsRe);
        let result = source;
        let me = this;

        if (null === matches) {
            return result;
        }

        matches.forEach(function (match) {
            let tagMatches = match.match(/<(ph|hardReturn|softReturn|space|char)(.*[i|x]="(\d+)")?(.*ts="([\d|\w]+)")?/);

            if (null === tagMatches) {
                return;
            }

            let character = me.getSingleCharacter(tagMatches);

            if (tagMatches[1] === 'ph') {
                character = '&lt;' + tagMatches[3] + '/&gt;';
            }

            let svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + (me.measureWidth(character) + 2) + '">';
            svg += '<rect width="100%" height="100%" fill="rgb(207,207,207)" rx="3" ry="3"/><text x="0.25em" y="1em" font-size="13px">';
            svg += Ext.String.htmlEncode(character) + '</text></svg>';

            let replace = '<img src="data:image/svg+xml;charset=utf-8,' + encodeURI(svg);
            replace += '" data-tag-type="single" data-original="' + encodeURI(match) + '" class="tag"/>';

            result = result.replace(match, replace);
        });

        return result;
    },

    getSingleCharacter: function (tagMatches) {
        let character = '';

        switch (tagMatches[1]) {
            case 'hardReturn':
                character = '↵';
                break;
            case 'softReturn':
                character = '↵';
                break;
            case 'char':
                if (tagMatches[5] === 'c2a0') {
                    character = '⎵';
                }
                break;
            case 'space':
                character = '⎵';
                break;
            case 'tab':
                character = '→';
                break;
        }

        return character;
    },

    /**
     * Creates the hidden "ruler" div that is used to measure text length
     */
    createRuler: function () {
        this.ruler = document.createElement('div');
        this.ruler.classList.add(Ext.baseCSSPrefix + 'textmetrics');
        this.ruler.setAttribute('role', 'presentation');
        this.ruler.dataset.sticky = true;
        this.ruler.style.position = 'absolute';
        this.ruler.style.left = '-1000px';
        this.ruler.style.top = '-1000px';
        this.ruler.style.visibility = 'hidden';

        document.body.appendChild(this.ruler);
    },

    /**
     * Measures the passed internal tag's data evaluating the width of the span
     *
     * @param {String} text
     */
    measureWidth: function (text) {
        this.ruler.innerHTML = text;

        return this.ruler.getBoundingClientRect().width;
    }
});
