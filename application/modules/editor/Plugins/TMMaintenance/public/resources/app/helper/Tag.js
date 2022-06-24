Ext.define('TMMaintenance.helper.Tag', {
    transform: function (source) {
        let tagsRe = /<[^>]*>/gm;
        let matches = source.match(tagsRe);
        let result = source;

        if (null === matches) {
            return source;
        }

        matches.forEach(function (match) {
            let tagMatches = match.match(/<(bpt|ept).*i="(\d+)"/);

            if (null === tagMatches) {
                return;
            }

            let svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="rgb(207,207,207)" rx="3" ry="3"/><text x="0.25em" y="1em" font-size="13px">'
            svg += Ext.String.htmlEncode('&lt;' + (tagMatches[1] === 'ept' ? '/' : '') + tagMatches[2] + '&gt;') + '</text></svg>';

            console.log(svg);
            console.log(encodeURI(svg));

            let replace = '<img src="data:image/svg+xml;charset=utf-8,' + encodeURI(svg) + '" data-tag-type="{tagType}" data-tag-id="{index}" class="tag"/>';
            result = result.replace(match, replace);
        });

        return result;
    },

    reverseTransform: function (source) {

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
