const mix = require("laravel-mix");
require("core-js");
require("laravel-mix-polyfill");
const path = require("path");

//const BrowserSyncPlugin = require('browser-sync-webpack-plugin');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

module.exports = {
    resolve: {
        alias: {
            myApp: path.resolve(__dirname, "assets/js"),
        },
    },
    module: {
        rules: [
            {
                test: /\.ts$/,
                use: "ts-loader",
                exclude: /node_modules/,
            },
        ],
    },
    stats: {
        children: true,
    },
};

mix.options({ processCssUrls: false })
    .js("assets/js/app.js", "js/built.js")
    .sass("assets/sass/main.scss", "css/main.css")
    .minify("js/built.js", "js/built.min.js")
    .minify("css/main.css", "css/main.min.css")
    .polyfill({
        enabled: true,
        useBuiltIns: "entry",
        targets: false,
        entryPoints: "stable",
        corejs: 3,
    })
    .sourceMaps();
