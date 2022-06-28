Ext.define('TMMaintenance.helper.Tag', {
    transform: function (source) {
        let tagsRe = /<[^>]*>/gm;
        let matches = source.match(tagsRe);
        let result = source;

        if (null === matches) {
            return result;
        }

        matches.forEach(function (match) {
            let tagMatches = match.match(/<(bpt|ept).*i="(\d+)"/);

            if (null === tagMatches) {
                return;
            }

            let svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="rgb(207,207,207)" rx="3" ry="3"/><text x="0.25em" y="1em" font-size="13px">'
            svg += Ext.String.htmlEncode('&lt;' + (tagMatches[1] === 'ept' ? '/' : '') + tagMatches[2] + '&gt;') + '</text></svg>';

            let replace = '<img src="data:image/svg+xml;charset=utf-8,' + encodeURI(svg);
            replace += '" data-tag-type="' + (tagMatches[1] === 'bpt' ? 'open' : 'close') + '" data-tag-id="' + tagMatches[2] + '" class="tag"/>';

            result = result.replace(match, replace);
        });

        return result;
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
            let params = match.match(/data-tag-id="(\d+)" data-tag-type="(open|close)"/);
            let replace = '<' + (params[2] === 'open' ? 'bpt' : 'ept') + ' x="' + index + '" i="' + params[1] + '"/>';

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
});
