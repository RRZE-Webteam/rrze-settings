const defaults = require("@wordpress/scripts/config/webpack.config");
const webpack = require("webpack");

/**
 * WP-Scripts Webpack config.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-scripts/#provide-your-own-webpack-config
 */
module.exports = {
    ...defaults,
    entry: {
        "mail/admin-email": "./src/mail/admin-email.js",
        "media/columns": "./src/media/columns.js",
        "media/svg-edit-post": "./src/media/svg-edit-post.js",
        "media/svg": "./src/media/svg",
        "taxonomies/attachment-media-filters":
            "./src/taxonomies/attachment-media-filters.js",        
        "menus/expand-collapse": "./src/menus/expand-collapse.js",
        "admin/admin": "./src/admin/admin.js",
        "writing/block-editor": "./src/writing/block-editor.js",
        "writing/block-editor-preferences":
            "./src/writing/block-editor-preferences.js",
        "discussion/avatars": "./src/discussion/avatars.js",
        "advanced/placeholder": "./src/advanced/placeholder.js",
    },
    plugins: [
        ...defaults.plugins,
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery",
        }),
    ],
};
