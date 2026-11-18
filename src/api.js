/**
 * REST helpers for the Douxir admin namespace.
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Call a route under /douxir/v1/admin/.
 *
 * @param {string} path    Path relative to the admin namespace, e.g. "data".
 * @param {Object} options apiFetch options (method, data, etc.).
 * @return {Promise<*>} Response body.
 */
export async function request( path, options = {} ) {
	try {
		return await apiFetch( {
			path: `/douxir/v1/admin/${ path }`,
			...options,
		} );
	} catch ( error ) {
		const message = error && error.message ? error.message : __( 'API request failed', 'douxir' );
		// eslint-disable-next-line no-console
		console.error( 'API Error:', error );
		window.alert( `${ __( 'Error:', 'douxir' ) } ${ message }` );
		throw error;
	}
}
