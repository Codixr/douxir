const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'admin-app': path.resolve( __dirname, 'src/index.js' ),
		blocks: path.resolve( __dirname, 'src/blocks/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'assets/dist' ),
		filename: '[name].js',
		clean: true,
	},
};
