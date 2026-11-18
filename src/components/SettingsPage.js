import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Icon from '../icons';
import { request } from '../api';

const TABS = [
	{ id: 'general', label: __( 'General', 'douxir' ) },
	{ id: 'layout', label: __( 'Layout', 'douxir' ) },
	{ id: 'search', label: __( 'Search & Permalinks', 'douxir' ) },
	{ id: 'appearance', label: __( 'Appearance', 'douxir' ) },
	{ id: 'tools', label: __( 'Tools', 'douxir' ) },
];

const FONT_LABELS = {
	inherit: __( 'Theme default', 'douxir' ),
	system: __( 'System UI', 'douxir' ),
	serif: __( 'Serif', 'douxir' ),
	monospace: __( 'Monospace', 'douxir' ),
};

function Toggle( { label, help, checked, onChange } ) {
	return (
		<label className="douxir-toggle">
			<input type="checkbox" checked={ !! checked } onChange={ ( event ) => onChange( event.target.checked ) } />
			<span>
				<span className="douxir-toggle-label">{ label }</span>
				{ help ? <span className="douxir-toggle-help">{ help }</span> : null }
			</span>
		</label>
	);
}

function Field( { label, help, children } ) {
	return (
		<div className="cd-form-group">
			<label className="cd-form-label">{ label }</label>
			{ children }
			{ help ? <p className="douxir-field-help">{ help }</p> : null }
		</div>
	);
}

function ColorField( { label, value, onChange } ) {
	return (
		<div className="cd-form-group">
			<label className="cd-form-label">{ label }</label>
			<div className="douxir-color-row">
				<input type="color" value={ value || '#000000' } onChange={ ( event ) => onChange( event.target.value ) } />
				<input
					type="text"
					className="cd-form-input"
					value={ value || '' }
					onChange={ ( event ) => onChange( event.target.value ) }
				/>
			</div>
		</div>
	);
}

export default function SettingsPage( { onClose } ) {
	const boot = window.douxirAdminData || {};
	const [ tab, setTab ] = useState( 'general' );
	const [ form, setForm ] = useState( () => ( { ...( boot.settings || {} ) } ) );
	const [ pages, setPages ] = useState( boot.pages || [] );
	const [ fonts, setFonts ] = useState( boot.fontOptions || [ 'inherit', 'system', 'serif', 'monospace' ] );
	const [ busy, setBusy ] = useState( false );
	const [ saved, setSaved ] = useState( false );
	const [ importMsg, setImportMsg ] = useState( '' );

	useEffect( () => {
		let cancelled = false;
		( async () => {
			try {
				const response = await request( 'settings' );
				if ( cancelled || ! response ) {
					return;
				}
				setForm( { ...( response.settings || {} ) } );
				if ( response.pages ) {
					setPages( response.pages );
				}
				if ( response.fontOptions ) {
					setFonts( response.fontOptions );
				}
			} catch ( error ) {
				// request() already surfaces the error.
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [] );

	const set = ( key, value ) => {
		setForm( ( current ) => ( { ...current, [ key ]: value } ) );
		setSaved( false );
	};

	const onSave = async ( event ) => {
		event.preventDefault();
		setBusy( true );
		try {
			const response = await request( 'settings', { method: 'POST', data: form } );
			if ( response && response.settings ) {
				setForm( { ...response.settings } );
				if ( window.douxirAdminData ) {
					window.douxirAdminData.settings = response.settings;
				}
				setSaved( true );
			}
		} catch ( error ) {
			// request() already surfaces the error.
		}
		setBusy( false );
	};

	const onExport = async () => {
		try {
			const data = await request( 'export' );
			const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
			const url = URL.createObjectURL( blob );
			const link = document.createElement( 'a' );
			link.href = url;
			link.download = 'douxir-export.json';
			link.click();
			URL.revokeObjectURL( url );
		} catch ( error ) {
			// request() already surfaces the error.
		}
	};

	const onImport = async ( event ) => {
		const file = event.target.files && event.target.files[ 0 ];
		event.target.value = '';
		if ( ! file ) {
			return;
		}
		try {
			const text = await file.text();
			const payload = JSON.parse( text );
			const response = await request( 'import', { method: 'POST', data: { payload } } );
			if ( response && response.success ) {
				setImportMsg(
					__( 'Import finished.', 'douxir' ) +
						' ' +
						__( 'Categories created:', 'douxir' ) +
						' ' +
						( response.categories_created || 0 ) +
						'. ' +
						__( 'Documents created:', 'douxir' ) +
						' ' +
						( response.documents_created || 0 ) +
						'.'
				);
			}
		} catch ( error ) {
			setImportMsg( __( 'The selected file could not be imported.', 'douxir' ) );
		}
	};

	const previewStyle = useMemo(
		() => ( {
			'--douxir-color-primary': form.color_primary || '#5B21B6',
			'--douxir-color-text': form.color_text || '#0f172a',
			'--douxir-color-background': form.color_background || '#ffffff',
			'--douxir-color-link': form.color_link || '#7C3AED',
			fontFamily:
				form.font_family === 'system'
					? 'system-ui, sans-serif'
					: form.font_family === 'serif'
					? 'Georgia, serif'
					: form.font_family === 'monospace'
					? 'ui-monospace, monospace'
					: 'inherit',
			fontSize: `${ form.font_size || 16 }px`,
			background: form.color_background || '#fff',
			color: form.color_text || '#0f172a',
		} ),
		[ form ]
	);

	return (
		<div className="douxir-settings">
			<div className="douxir-settings-header">
				<div>
					<h2 className="cd-overview-title">{ __( 'Settings', 'douxir' ) }</h2>
					<p className="cd-overview-sub">
						{ __( 'Customize how Douxir looks and behaves on the front end, without writing code.', 'douxir' ) }
					</p>
				</div>
				<button type="button" className="cd-btn cd-btn-ghost" onClick={ onClose }>
					{ __( 'Back to dashboard', 'douxir' ) }
				</button>
			</div>
			<div className="douxir-settings-tabs" role="tablist">
				{ TABS.map( ( item ) => (
					<button
						key={ item.id }
						type="button"
						role="tab"
						aria-selected={ tab === item.id }
						className={ tab === item.id ? 'is-active' : '' }
						onClick={ () => setTab( item.id ) }
					>
						{ item.label }
					</button>
				) ) }
			</div>
			<form onSubmit={ onSave }>
				{ tab === 'general' && (
					<div className="douxir-settings-grid">
						<Field label={ __( 'Documentation title', 'douxir' ) } help={ __( 'Shown in the sidebar, breadcrumbs and archive heading.', 'douxir' ) }>
							<input className="cd-form-input" value={ form.hub_title || '' } onChange={ ( event ) => set( 'hub_title', event.target.value ) } />
						</Field>
						<Field label={ __( 'Documents per archive page', 'douxir' ) }>
							<input type="number" min="1" max="50" className="cd-form-input" value={ form.docs_per_page || 12 } onChange={ ( event ) => set( 'docs_per_page', event.target.value ) } />
						</Field>
						<Field label={ __( 'Documentation homepage', 'douxir' ) } help={ __( 'Optional. Choose a page that contains Douxir shortcodes or blocks, or leave the default documentation URL.', 'douxir' ) }>
							<select className="cd-form-select" value={ form.docs_page_id || 0 } onChange={ ( event ) => set( 'docs_page_id', parseInt( event.target.value, 10 ) ) }>
								{ pages.map( ( page ) => (
									<option key={ page.id } value={ page.id }>
										{ page.title }
									</option>
								) ) }
							</select>
						</Field>
					</div>
				) }
				{ tab === 'layout' && (
					<div className="douxir-settings-grid">
						<h3 className="douxir-settings-heading">{ __( 'Archive layout', 'douxir' ) }</h3>
						<Field label={ __( 'Layout', 'douxir' ) }>
							<select className="cd-form-select" value={ form.archive_layout || 'grid' } onChange={ ( event ) => set( 'archive_layout', event.target.value ) }>
								<option value="grid">{ __( 'Grid', 'douxir' ) }</option>
								<option value="list">{ __( 'List', 'douxir' ) }</option>
							</select>
						</Field>
						<Field label={ __( 'Grid columns', 'douxir' ) }>
							<select className="cd-form-select" value={ form.archive_columns || 3 } onChange={ ( event ) => set( 'archive_columns', parseInt( event.target.value, 10 ) ) }>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
							</select>
						</Field>
						<Field label={ __( 'Card style', 'douxir' ) }>
							<select className="cd-form-select" value={ form.archive_card_style || 'elevated' } onChange={ ( event ) => set( 'archive_card_style', event.target.value ) }>
								<option value="simple">{ __( 'Simple', 'douxir' ) }</option>
								<option value="bordered">{ __( 'Bordered', 'douxir' ) }</option>
								<option value="elevated">{ __( 'Elevated', 'douxir' ) }</option>
							</select>
						</Field>
						<Toggle label={ __( 'Show document counts', 'douxir' ) } checked={ form.archive_show_count } onChange={ ( value ) => set( 'archive_show_count', value ) } />
						<Toggle label={ __( 'Show category descriptions', 'douxir' ) } checked={ form.archive_show_description } onChange={ ( value ) => set( 'archive_show_description', value ) } />

						<h3 className="douxir-settings-heading">{ __( 'Single document layout', 'douxir' ) }</h3>
						<Toggle label={ __( 'Show sidebar navigation', 'douxir' ) } checked={ form.show_sidebar } onChange={ ( value ) => set( 'show_sidebar', value ) } />
						<Field label={ __( 'Sidebar position', 'douxir' ) }>
							<select className="cd-form-select" value={ form.sidebar_position || 'left' } onChange={ ( event ) => set( 'sidebar_position', event.target.value ) } disabled={ ! form.show_sidebar }>
								<option value="left">{ __( 'Left', 'douxir' ) }</option>
								<option value="right">{ __( 'Right', 'douxir' ) }</option>
							</select>
						</Field>
						<Toggle label={ __( 'Show table of contents', 'douxir' ) } checked={ form.show_toc } onChange={ ( value ) => set( 'show_toc', value ) } />
						<Toggle label={ __( 'Show breadcrumbs', 'douxir' ) } checked={ form.show_breadcrumbs } onChange={ ( value ) => set( 'show_breadcrumbs', value ) } />
						<Toggle label={ __( 'Show previous and next links', 'douxir' ) } checked={ form.show_prev_next } onChange={ ( value ) => set( 'show_prev_next', value ) } />
						<Toggle label={ __( 'Show reading time', 'douxir' ) } checked={ form.show_reading_time } onChange={ ( value ) => set( 'show_reading_time', value ) } />
						<Toggle label={ __( 'Show last updated date', 'douxir' ) } checked={ form.show_last_updated } onChange={ ( value ) => set( 'show_last_updated', value ) } />
						<Toggle label={ __( 'Show print button', 'douxir' ) } checked={ form.show_print } onChange={ ( value ) => set( 'show_print', value ) } />
						<Toggle label={ __( 'Ask if the document was helpful', 'douxir' ) } checked={ form.show_feedback } onChange={ ( value ) => set( 'show_feedback', value ) } />
						<Toggle label={ __( 'Show related documents', 'douxir' ) } checked={ form.show_related } onChange={ ( value ) => set( 'show_related', value ) } />
						<Field label={ __( 'Number of related documents', 'douxir' ) }>
							<input type="number" min="1" max="8" className="cd-form-input" value={ form.related_count || 3 } onChange={ ( event ) => set( 'related_count', event.target.value ) } disabled={ ! form.show_related } />
						</Field>
					</div>
				) }
				{ tab === 'search' && (
					<div className="douxir-settings-grid">
						<h3 className="douxir-settings-heading">{ __( 'Search', 'douxir' ) }</h3>
						<Toggle label={ __( 'Show the live search box', 'douxir' ) } help={ __( 'Suggestions appear as visitors type.', 'douxir' ) } checked={ form.show_search } onChange={ ( value ) => set( 'show_search', value ) } />
						<Field label={ __( 'Search placeholder', 'douxir' ) }>
							<input className="cd-form-input" value={ form.search_placeholder || '' } onChange={ ( event ) => set( 'search_placeholder', event.target.value ) } />
						</Field>
						<Field label={ __( 'Maximum search results', 'douxir' ) }>
							<input type="number" min="3" max="25" className="cd-form-input" value={ form.search_results || 10 } onChange={ ( event ) => set( 'search_results', event.target.value ) } />
						</Field>
						<h3 className="douxir-settings-heading">{ __( 'Permalinks', 'douxir' ) }</h3>
						<Field label={ __( 'Documentation slug', 'douxir' ) } help={ __( 'Used in URLs such as /documentation/getting-started/. After changing this, WordPress permalinks are refreshed automatically.', 'douxir' ) }>
							<input className="cd-form-input" value={ form.docs_slug || '' } onChange={ ( event ) => set( 'docs_slug', event.target.value ) } />
						</Field>
						<Toggle label={ __( 'Include the category in document URLs', 'douxir' ) } help={ __( 'On: /documentation/category/document/. Off: /documentation/document/.', 'douxir' ) } checked={ form.include_category_in_url } onChange={ ( value ) => set( 'include_category_in_url', value ) } />
					</div>
				) }
				{ tab === 'appearance' && (
					<div className="douxir-settings-appearance">
						<div className="douxir-settings-grid">
							<ColorField label={ __( 'Primary color', 'douxir' ) } value={ form.color_primary } onChange={ ( value ) => set( 'color_primary', value ) } />
							<ColorField label={ __( 'Text color', 'douxir' ) } value={ form.color_text } onChange={ ( value ) => set( 'color_text', value ) } />
							<ColorField label={ __( 'Background color', 'douxir' ) } value={ form.color_background } onChange={ ( value ) => set( 'color_background', value ) } />
							<ColorField label={ __( 'Link color', 'douxir' ) } value={ form.color_link } onChange={ ( value ) => set( 'color_link', value ) } />
							<Field label={ __( 'Font family', 'douxir' ) } help={ __( 'Only system font stacks are used. Douxir does not load fonts from other websites.', 'douxir' ) }>
								<select className="cd-form-select" value={ form.font_family || 'inherit' } onChange={ ( event ) => set( 'font_family', event.target.value ) }>
									{ fonts.map( ( font ) => (
										<option key={ font } value={ font }>
											{ FONT_LABELS[ font ] || font }
										</option>
									) ) }
								</select>
							</Field>
							<Field label={ __( 'Base font size (px)', 'douxir' ) }>
								<input type="number" min="12" max="22" className="cd-form-input" value={ form.font_size || 16 } onChange={ ( event ) => set( 'font_size', event.target.value ) } />
							</Field>
							<Field label={ __( 'Container width (px)', 'douxir' ) }>
								<input type="number" min="720" max="1600" className="cd-form-input" value={ form.container_width || 1200 } onChange={ ( event ) => set( 'container_width', event.target.value ) } />
							</Field>
							<Field label={ __( 'Custom CSS', 'douxir' ) } help={ __( 'Added after Douxir styles on the front end. Tags and unsafe CSS are removed.', 'douxir' ) }>
								<textarea className="cd-form-input douxir-css-field" rows={ 8 } value={ form.custom_css || '' } onChange={ ( event ) => set( 'custom_css', event.target.value ) } />
							</Field>
						</div>
						<div className="douxir-live-preview" style={ previewStyle }>
							<p className="douxir-live-preview-kicker">{ __( 'Live preview', 'douxir' ) }</p>
							<h3 style={ { color: 'var(--douxir-color-primary)', margin: '0 0 8px' } }>{ form.hub_title || __( 'Documentation', 'douxir' ) }</h3>
							<p style={ { margin: '0 0 12px' } }>{ __( 'This is a sample paragraph so you can judge the text color, font and size.', 'douxir' ) }</p>
							<p>
								<a href="#preview" style={ { color: 'var(--douxir-color-link)' } } onClick={ ( event ) => event.preventDefault() }>
									{ __( 'A sample link', 'douxir' ) }
								</a>
							</p>
							<button type="button" className="douxir-preview-btn" style={ { background: 'var(--douxir-color-primary)', color: '#fff' } }>
								{ __( 'Primary button', 'douxir' ) }
							</button>
						</div>
					</div>
				) }
				{ tab === 'tools' && (
					<div className="douxir-settings-tools">
						<div className="douxir-tool-card">
							<h3>{ __( 'Export', 'douxir' ) }</h3>
							<p>{ __( 'Download your categories and documents as a JSON file. You can use this as a backup or to copy a knowledge base to another site.', 'douxir' ) }</p>
							<button type="button" className="cd-btn cd-btn-outline" onClick={ onExport }>
								<Icon name="download" size={ 16 } />
								<span>{ __( 'Download export', 'douxir' ) }</span>
							</button>
						</div>
						<div className="douxir-tool-card">
							<h3>{ __( 'Import', 'douxir' ) }</h3>
							<p>{ __( 'Import a Douxir export file. Existing slugs are left unchanged; only missing categories and documents are created.', 'douxir' ) }</p>
							<label className="cd-btn cd-btn-outline">
								<Icon name="upload" size={ 16 } />
								<span>{ __( 'Choose JSON file', 'douxir' ) }</span>
								<input type="file" accept="application/json,.json" hidden onChange={ onImport } />
							</label>
							{ importMsg ? <p className="douxir-field-help">{ importMsg }</p> : null }
						</div>
					</div>
				) }
				<div className="douxir-settings-footer">
					<button type="submit" className="cd-btn cd-btn-primary" disabled={ busy }>
						{ busy ? __( 'Saving...', 'douxir' ) : __( 'Save settings', 'douxir' ) }
					</button>
					{ saved ? <span className="douxir-saved">{ __( 'Settings saved.', 'douxir' ) }</span> : null }
				</div>
			</form>
		</div>
	);
}
