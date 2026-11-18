import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Icon from '../icons';

export default function NewDocModal( {
	isOpen,
	onClose,
	onSubmit,
	categories = [],
	allDocs = [],
	initialCatId = 0,
	initialParentId = 0,
} ) {
	const [ title, setTitle ] = useState( '' );
	const [ categoryId, setCategoryId ] = useState( initialCatId );
	const [ parentId, setParentId ] = useState( initialParentId );
	const [ status, setStatus ] = useState( 'publish' );
	const [ isPro, setIsPro ] = useState( false );
	const [ menuOrder, setMenuOrder ] = useState( 1 );
	const [ busy, setBusy ] = useState( false );

	useEffect( () => {
		if ( isOpen ) {
			const nextCat = initialCatId || ( categories[ 0 ] && categories[ 0 ].id ) || 0;
			setTitle( '' );
			setCategoryId( nextCat );
			setParentId( initialParentId || 0 );
			setStatus( 'publish' );
			setIsPro( false );
			const siblings = allDocs.filter( ( doc ) => ! doc.parent_id && ( ! nextCat || ( doc.categories || [] ).includes( Number( nextCat ) ) ) );
			const maxOrder = siblings.reduce( ( max, doc ) => Math.max( max, parseInt( doc.menu_order, 10 ) || 0 ), 0 );
			setMenuOrder( maxOrder + 1 );
			setBusy( false );
		}
	}, [ isOpen, initialCatId, initialParentId, categories, allDocs ] );

	if ( ! isOpen ) {
		return null;
	}

	const onFormSubmit = async ( event ) => {
		event.preventDefault();
		if ( ! title.trim() ) {
			return;
		}
		setBusy( true );
		await onSubmit( {
			title: title.trim(),
			category_id: parseInt( categoryId, 10 ),
			parent_id: parseInt( parentId, 10 ),
			status,
			pro: isPro,
			menu_order: parseInt( menuOrder, 10 ) || 0,
		} );
		setBusy( false );
		onClose();
	};

	return (
		<div className="cd-modal-overlay" onClick={ onClose }>
			<div className="cd-modal-box" onClick={ ( event ) => event.stopPropagation() }>
				<div className="cd-modal-header">
					<h3 className="cd-modal-title">{ __( 'Create New Document', 'douxir' ) }</h3>
					<button type="button" className="cd-icon-btn" onClick={ onClose }>
						<Icon name="x" size={ 18 } />
					</button>
				</div>
				<form onSubmit={ onFormSubmit }>
					<div className="cd-modal-body">
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Document Title *', 'douxir' ) }</label>
							<input
								type="text"
								className="cd-form-input"
								placeholder={ __( 'e.g. Getting Started Guide', 'douxir' ) }
								autoFocus
								required
								value={ title }
								onChange={ ( event ) => setTitle( event.target.value ) }
							/>
						</div>
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Category', 'douxir' ) }</label>
							<select
								className="cd-form-select"
								value={ categoryId }
								onChange={ ( event ) => {
									const nextCat = event.target.value;
									setCategoryId( nextCat );
									const siblings = allDocs.filter( ( doc ) => ! doc.parent_id && ( ! Number( nextCat ) || ( doc.categories || [] ).includes( Number( nextCat ) ) ) );
									const maxOrder = siblings.reduce( ( max, doc ) => Math.max( max, parseInt( doc.menu_order, 10 ) || 0 ), 0 );
									setMenuOrder( maxOrder + 1 );
								} }
							>
								{ categories.map( ( cat ) => (
									<option key={ cat.id } value={ cat.id }>
										{ cat.name }
									</option>
								) ) }
							</select>
						</div>
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Parent Document (Hierarchy)', 'douxir' ) }</label>
							<select className="cd-form-select" value={ parentId } onChange={ ( event ) => setParentId( event.target.value ) }>
								<option value={ 0 }>{ __( '-- None (Top Level Article) --', 'douxir' ) }</option>
								{ allDocs.map( ( doc ) => (
									<option key={ doc.id } value={ doc.id }>
										{ doc.parent_id ? `  ↳ ${ doc.title }` : doc.title }
									</option>
								) ) }
							</select>
						</div>
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Publishing Status', 'douxir' ) }</label>
							<select className="cd-form-select" value={ status } onChange={ ( event ) => setStatus( event.target.value ) }>
								<option value="publish">{ __( 'Published', 'douxir' ) }</option>
								<option value="draft">{ __( 'Draft', 'douxir' ) }</option>
								<option value="private">{ __( 'Private', 'douxir' ) }</option>
							</select>
						</div>
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Display order', 'douxir' ) }</label>
							<input
								type="number"
								className="cd-form-input"
								min="0"
								value={ menuOrder }
								onChange={ ( event ) => setMenuOrder( event.target.value ) }
							/>
							<p className="douxir-field-help">
								{ __( 'Lower numbers appear first in this category. You can also drag documents in Manage category after saving.', 'douxir' ) }
							</p>
						</div>
						{ ( () => {
							const siblings = allDocs
								.filter( ( doc ) => ! doc.parent_id && ( ! Number( categoryId ) || ( doc.categories || [] ).includes( Number( categoryId ) ) ) )
								.sort( ( a, b ) => ( a.menu_order || 0 ) - ( b.menu_order || 0 ) );
							if ( ! siblings.length ) {
								return null;
							}
							return (
								<div className="cd-form-group">
									<label className="cd-form-label">{ __( 'Current order in this category', 'douxir' ) }</label>
									<ul className="douxir-order-preview">
										{ siblings.map( ( doc ) => (
											<li key={ doc.id }>
												<span className="douxir-order-num">{ doc.menu_order || 0 }</span>
												<span>{ doc.title }</span>
											</li>
										) ) }
									</ul>
								</div>
							);
						} )() }
						<div className="cd-form-toggle-row">
							<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
								<Icon name="sparkles" size={ 16 } color="#8b5cf6" />
								<div>
									<div style={ { fontSize: '13px', fontWeight: 600, color: '#1e293b' } }>
										{ __( 'PRO Feature Article', 'douxir' ) }
									</div>
									<div style={ { fontSize: '11px', color: '#64748b' } }>
										{ __( 'Appends a stylish PRO badge on this document', 'douxir' ) }
									</div>
								</div>
							</div>
							<input
								type="checkbox"
								checked={ isPro }
								onChange={ ( event ) => setIsPro( event.target.checked ) }
								style={ { width: '18px', height: '18px', cursor: 'pointer' } }
							/>
						</div>
					</div>
					<div className="cd-modal-footer">
						<button type="button" className="cd-btn cd-btn-ghost" onClick={ onClose }>
							{ __( 'Cancel', 'douxir' ) }
						</button>
						<button type="submit" className="cd-btn cd-btn-primary" disabled={ busy || ! title.trim() }>
							{ busy ? __( 'Creating...', 'douxir' ) : __( 'Create Document', 'douxir' ) }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
