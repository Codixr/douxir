/**
 * Mount the Douxir admin app on the dashboard screen.
 */
import { createRoot, render, StrictMode, createElement } from '@wordpress/element';
import App from './App';

const rootElement = document.getElementById( 'douxir-admin-root' );

if ( rootElement ) {
	const app = createElement( StrictMode, null, createElement( App ) );
	if ( typeof createRoot === 'function' ) {
		createRoot( rootElement ).render( app );
	} else {
		render( app, rootElement );
	}
}
