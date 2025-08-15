/**
 * @license Copyright (c) 2014-2024, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

'use strict';

/* eslint-env node */

import path from 'path';
import webpack from 'webpack';
import {fileURLToPath} from 'url';
import {bundler, styles} from '@ckeditor/ckeditor5-dev-utils';
import {CKEditorTranslationsPlugin} from '@ckeditor/ckeditor5-dev-translations';
import TerserWebpackPlugin from 'terser-webpack-plugin';
import {createRequire} from 'module';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const require = createRequire(import.meta.url);
const themePath = require.resolve('@ckeditor/ckeditor5-theme-lark');

export default {
    devtool: 'source-map',
    performance: {hints: false},

    entry: path.resolve(__dirname, 'src', 'ckeditor.ts'),

    output: {
        // The name under which the editor will be exported.
        library: 'ClassicEditor',

        path: path.resolve(__dirname, 'build'),
        filename: 'ckeditor.js',
        libraryTarget: 'umd',
        libraryExport: 'default'
    },

    optimization: {
        minimizer: [
            new TerserWebpackPlugin({
                sourceMap: true,
                terserOptions: {
                    output: {
                        // Preserve CKEditor 5 license comments.
                        comments: /^!/
                    }
                },
                extractComments: false
            })
        ]
    },

    plugins: [
        new CKEditorTranslationsPlugin({
            // UI language. Language codes follow the https://en.wikipedia.org/wiki/ISO_639-1 format.
            // When changing the built-in language,
            // remember to also change it in the editor's configuration (src/ckeditor.ts).
            language: 'en',
            additionalLanguages: 'all'
        }),
        new webpack.BannerPlugin({
            banner: bundler.getLicenseBanner(),
            raw: true
        })
    ],

    resolve: {
        extensions: ['.ts', '.js', '.json']
    },

    module: {
        rules: [{
            test: /\.svg$/,
            use: ['raw-loader']
        }, {
            test: /\.ts$/,
            use: 'ts-loader'
        }, {
            test: /\.css$/,
            use: [{
                loader: 'style-loader',
                options: {
                    injectType: 'singletonStyleTag',
                    attributes: {
                        'data-cke': true
                    }
                }
            }, {
                loader: 'css-loader'
            }, {
                loader: 'postcss-loader',
                options: {
                    postcssOptions: styles.getPostCssConfig({
                        themeImporter: {
                            themePath: themePath
                        },
                        minify: true
                    })
                }
            }]
        }]
    }
};
