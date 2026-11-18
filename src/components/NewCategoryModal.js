import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Icon from '../icons';

export default function NewCategoryModal( { isOpen, onClose, onSubmit, categories = [] } ) {
	const icons = ( window.douxirAdminData && window.douxirAdminData.iconOptions ) || [];
	const [ name, setName ] = useState( '' );
	const [ slug, setSlug ] = useState( '' );
	const [ parent, setParent ] = useState( 0 );
	const [ description, setDescription ] = useState( '' );
	const [ icon, setIcon ] = useState( '' );
	const [ busy, setBusy ] = useState( false );

	useEffect( () => {
		if ( isOpen ) {
			setName( '' );
			setSlug( '' );
			setParent( 0 );
			setDescription( '' );
			setIcon( '' );
			setBusy( false );
		}
	}, [ isOpen ] );

	if ( ! isOpen ) {
		return null;
	}

	const onFormSubmit = async ( event ) => {
		event.preventDefault();
		if ( ! name.trim() ) {
			return;
		}
		setBusy( true );
		await onSubmit( {
			name: name.trim(),
			slug: slug.trim(),
			parent: parseInt( parent, 10 ),
			description,
			icon,
		} );
		setBusy( false );
		onClose();
	};

	return (
		<div className="cd-modal-overlay" onClick={ onClose }>
			<div className="cd-modal-box" onClick={ ( event ) => event.stopPropagation() }>
				<div className="cd-modal-header">
					<h3 className="cd-modal-title">{ __( 'Create New Category', 'douxir' ) }</h3>
					<button type="button" className="cd-icon-btn" onClick={ onClose }>
						<Icon name="x" size={ 18 } />
					</button>
				</div>
				<form onSubmit={ onFormSubmit }>
					<div className="cd-modal-body">
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Category name', 'douxir' ) }</label>
							<input className="cd-form-input" required autoFocus value={ name } onChange={ ( event ) => setName( event.target.value ) } />
						</div>
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Slug', 'douxir' ) }</label>
							<input className="cd-form-input" value={ slug } onChange={ ( event ) => setSlug( event.target.value ) } placeholder={ __( 'Optional', 'douxir' ) } />
						</div>
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Parent category', 'douxir' ) }</label>
							<select className="cd-form-select" value={ parent } onChange={ ( event ) => setParent( event.target.value ) }>
								<option value={ 0 }>{ __( 'None', 'douxir' ) }</option>
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
							<label className="cd-form-label">{ __( 'Description', 'douxir' ) }</label>
							<textarea className="cd-form-input" rows={ 3 } value={ description } onChange={ ( event ) => setDescription( event.target.value ) } />
						</div>
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Icon', 'douxir' ) }</label>
							<select className="cd-form-select" value={ icon } onChange={ ( event ) => setIcon( event.target.value ) }>
								<option value="">{ __( 'None', 'douxir' ) }</option>
								{ icons.map( ( key ) => (
									<option key={ key } value={ key }>
										{ key }
									</option>
								) ) }
							</select>
						</div>
					</div>
					<div className="cd-modal-footer">
						<button type="button" className="cd-btn cd-btn-ghost" onClick={ onClose }>
							{ __( 'Cancel', 'douxir' ) }
						</button>
						<button type="submit" className="cd-btn cd-btn-primary" disabled={ busy || ! name.trim() }>
							{ busy ? __( 'Creating...', 'douxir' ) : __( 'Create Category', 'douxir' ) }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
