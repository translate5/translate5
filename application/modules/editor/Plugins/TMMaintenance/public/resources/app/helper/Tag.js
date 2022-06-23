Ext.define('TMMaintenance.helper.Tag', {
    transform: function (source) {
        console.log(source);
    },

    reverseTransform: function (source) {
        console.log(source);
    },

    highlight: function (source, search) {
        let tagsRe = /<[^>]*>/gm;
        let tagsProtect = '\x0f';
        let matches;
        let result;

        matches = source.match(tagsRe);
        result = source.replace(tagsRe, tagsProtect);

        let searchRegexp = new RegExp(search, 'gi');

        result = result.replace(searchRegexp, function(item) {
            return '<span class="highlight">' + item + '</span>';
        }, this);

        // restore protected tags
        if (null !== matches) {
            matches.forEach(function(match) {
                result = result.replace(tagsProtect, match);
            });
        }

        return result;
    },
});
