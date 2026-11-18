import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Icon from '../icons';

export default function QuickEditModal( {
	isOpen,
	onClose,
	onSubmit,
	doc,
	categories = [],
	allDocs = [],
} ) {
	const [ title, setTitle ] = useState( '' );
	const [ slug, setSlug ] = useState( '' );
	const [ categoryId, setCategoryId ] = useState( 0 );
	const [ parentId, setParentId ] = useState( 0 );
	const [ status, setStatus ] = useState( 'publish' );
	const [ menuOrder, setMenuOrder ] = useState( 0 );
	const [ isPro, setIsPro ] = useState( false );
	const [ busy, setBusy ] = useState( false );

	useEffect( () => {
		if ( doc ) {
			setTitle( doc.title || '' );
			setSlug( doc.slug || '' );
			setCategoryId( ( doc.categories && doc.categories[ 0 ] ) || 0 );
			setParentId( doc.parent_id || 0 );
			setStatus( doc.status || 'publish' );
			setMenuOrder( doc.menu_order || 0 );
			setIsPro( !! doc.pro );
			setBusy( false );
		}
	}, [ doc ] );

	if ( ! isOpen || ! doc ) {
		return null;
	}

	const onFormSubmit = async ( event ) => {
		event.preventDefault();
		if ( ! title.trim() ) {
			return;
		}
		setBusy( true );
		await onSubmit( {
			id: doc.id,
			title: title.trim(),
			slug: slug.trim(),
			category_id: parseInt( categoryId, 10 ),
			parent_id: parseInt( parentId, 10 ),
			status,
			menu_order: parseInt( menuOrder, 10 ),
			pro: isPro,
		} );
		setBusy( false );
		onClose();
	};

	return (
		<div className="cd-modal-overlay" onClick={ onClose }>
			<div className="cd-modal-box" onClick={ ( event ) => event.stopPropagation() }>
				<div className="cd-modal-header">
					<h3 className="cd-modal-title">{ __( 'Quick Edit Document', 'douxir' ) }</h3>
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
								required
								value={ title }
								onChange={ ( event ) => setTitle( event.target.value ) }
							/>
						</div>
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Permalink Slug', 'douxir' ) }</label>
							<input
								type="text"
								className="cd-form-input"
								value={ slug }
								onChange={ ( event ) => setSlug( event.target.value ) }
							/>
						</div>
						<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' } }>
							<div className="cd-form-group">
								<label className="cd-form-label">{ __( 'Category', 'douxir' ) }</label>
								<select className="cd-form-select" value={ categoryId } onChange={ ( event ) => setCategoryId( event.target.value ) }>
									<option value={ 0 }>{ __( 'Uncategorized', 'douxir' ) }</option>
									{ categories
										.filter( ( cat ) => cat.id > 0 )
										.map( ( cat ) => (
											<option key={ cat.id } value={ cat.id }>
												{ cat.name }
											</option>
										) ) }
								</select>
							</div>
							<div className="cd-form-group">
								<label className="cd-form-label">{ __( 'Status', 'douxir' ) }</label>
								<select className="cd-form-select" value={ status } onChange={ ( event ) => setStatus( event.target.value ) }>
									<option value="publish">{ __( 'Published', 'douxir' ) }</option>
									<option value="draft">{ __( 'Draft', 'douxir' ) }</option>
									<option value="private">{ __( 'Private', 'douxir' ) }</option>
								</select>
							</div>
						</div>
						<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' } }>
							<div className="cd-form-group">
								<label className="cd-form-label">{ __( 'Parent Document', 'douxir' ) }</label>
								<select className="cd-form-select" value={ parentId } onChange={ ( event ) => setParentId( event.target.value ) }>
									<option value={ 0 }>{ __( '-- None (Top Level) --', 'douxir' ) }</option>
									{ allDocs
										.filter( ( item ) => item.id !== doc.id )
										.map( ( item ) => (
											<option key={ item.id } value={ item.id }>
												{ item.title }
											</option>
										) ) }
								</select>
							</div>
							<div className="cd-form-group">
								<label className="cd-form-label">{ __( 'Menu Order (Priority)', 'douxir' ) }</label>
								<input
									type="number"
									className="cd-form-input"
									value={ menuOrder }
									onChange={ ( event ) => setMenuOrder( event.target.value ) }
								/>
							</div>
						</div>
						<div className="cd-form-toggle-row">
							<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
								<Icon name="sparkles" size={ 16 } color="#8b5cf6" />
								<div>
									<div style={ { fontSize: '13px', fontWeight: 600, color: '#1e293b' } }>{ __( 'PRO Feature', 'douxir' ) }</div>
									<div style={ { fontSize: '11px', color: '#64748b' } }>{ __( 'Appends PRO badge', 'douxir' ) }</div>
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
							{ busy ? __( 'Saving...', 'douxir' ) : __( 'Save Changes', 'douxir' ) }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
