const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		//'block-awhitepixel-myfirstblock': './src/block-awhitepixel-myfirstblock.js',
        'editor': './src/linkgenius-editor.js'
	},
	output: {
		path: path.join(__dirname, '../js/editor'),
		filename: '[name].js'
	},
	optimization: {
		// minimize: false,
	},
}