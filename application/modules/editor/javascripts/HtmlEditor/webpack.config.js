'use strict';

const path = require('path');
const {styles} = require('@ckeditor/ckeditor5-dev-utils');

module.exports = {
    // https://webpack.js.org/configuration/entry-context/
    entry: [
        './Editor/index.js',
    ],
    // https://webpack.js.org/configuration/output/
    output: {
        path: path.resolve('/', 'build'),
        filename: 'editor.js',
        library: "RichTextEditor",
        libraryTarget: "umd",
    },
    module: {
        rules: [
            {
                test: /ckeditor5-[^/\\]+[/\\]theme[/\\]icons[/\\][^/\\]+\.svg$/,
                use: ['raw-loader']
            },
            {
                test: /ckeditor5-[^/\\]+[/\\]theme[/\\].+\.css$/,
                use: [
                    {
                        loader: 'style-loader',
                        options: {
                            injectType: 'singletonStyleTag',
                            attributes: {
                                'data-cke': true
                            }
                        }
                    },
                    'css-loader',
                    {
                        loader: 'postcss-loader',
                        options: {
                            postcssOptions: styles.getPostCssConfig({
                                themeImporter: {
                                    themePath: require.resolve('@ckeditor/ckeditor5-theme-lark')
                                },
                                minify: true
                            })
                        }
                    }
                ]
            }
        ]
    },
    mode: 'development',
    // Useful for debugging.
    devtool: 'source-map',
    // By default webpack logs warnings if the bundle is bigger than 200kb.
    performance: {hints: false},
    watch: true,
    watchOptions: {
        aggregateTimeout: 600,
    },
};
