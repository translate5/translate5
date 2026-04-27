import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default {
    mode: 'development',
    entry: './spell-check.js',
    output: {
        filename: 'spell-check.js',
        path: path.resolve(__dirname, '../public/js/custom'),
        library: {
            name: 'SpellCheck',
            type: 'var',
            export: 'default'
        }
    },
    externals: {
        // These will be provided by the ExtJS environment
        'RichTextEditor': 'RichTextEditor',
        'Editor': 'Editor'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            ['@babel/preset-env', {
                                targets: {
                                    browsers: ['last 2 versions', 'ie >= 11']
                                },
                                modules: false
                            }]
                        ]
                    }
                }
            }
        ]
    },
    resolve: {
        extensions: ['.js']
    },
    devtool: 'source-map'
};

