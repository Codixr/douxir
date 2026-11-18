import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Icon from '../icons';

export default function ManageCategoryModal( {
	isOpen,
	onClose,
	category,
	allDocs = [],
	categories = [],
	onSaveCategory,
	onReorderDocs,
} ) {
	const icons = ( window.douxirAdminData && window.douxirAdminData.iconOptions ) || [];
	const [ name, setName ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ icon, setIcon ] = useState( '' );
	const [ items, setItems ] = useState( [] );
	const [ dragging, setDragging ] = useState( null );
	const [ over, setOver ] = useState( null );
	const [ busy, setBusy ] = useState( false );

	const siblingDocs = useMemo( () => {
		if ( ! category ) {
			return [];
		}
		if ( category.docs && category.docs.length ) {
			return category.docs.map( ( doc ) => ( { id: doc.id, title: doc.title, menu_order: doc.menu_order || 0 } ) );
		}
		return allDocs
			.filter( ( doc ) => ! doc.parent_id && ( doc.categories || [] ).includes( category.id ) )
			.map( ( doc ) => ( { id: doc.id, title: doc.title, menu_order: doc.menu_order || 0 } ) );
	}, [ category, allDocs ] );

	useEffect( () => {
		if ( isOpen && category ) {
			setName( category.name || '' );
			setDescription( category.description || '' );
			setIcon( category.icon || '' );
			setItems( siblingDocs.map( ( doc, index ) => ( { ...doc, menu_order: doc.menu_order || index + 1 } ) ) );
			setBusy( false );
		}
	}, [ isOpen, category, siblingDocs ] );

	if ( ! isOpen || ! category ) {
		return null;
	}

	const moveItem = ( fromId, toId ) => {
		const from = items.findIndex( ( item ) => item.id === fromId );
		const to = items.findIndex( ( item ) => item.id === toId );
		if ( from === -1 || to === -1 || from === to ) {
			return;
		}
		const next = [ ...items ];
		const [ moved ] = next.splice( from, 1 );
		next.splice( to, 0, moved );
		setItems( next.map( ( item, index ) => ( { ...item, menu_order: index + 1 } ) ) );
	};

	const onDrop = ( event, id ) => {
		event.preventDefault();
		setOver( null );
		if ( dragging && dragging !== id ) {
			moveItem( dragging, id );
		}
		setDragging( null );
	};

	const setOrder = ( id, value ) => {
		setItems( ( current ) =>
			current.map( ( item ) => ( item.id === id ? { ...item, menu_order: parseInt( value, 10 ) || 0 } : item ) )
		);
	};

	const onSubmit = async ( event ) => {
		event.preventDefault();
		if ( ! name.trim() ) {
			return;
		}
		setBusy( true );
		if ( category.id > 0 && onSaveCategory ) {
			await onSaveCategory( {
				id: category.id,
				name: name.trim(),
				description,
				icon,
			} );
		}
		if ( onReorderDocs && items.length ) {
			await onReorderDocs( items.map( ( item ) => ( { id: item.id, menu_order: parseInt( item.menu_order, 10 ) || 0 } ) ) );
		}
		setBusy( false );
		onClose();
	};

	return (
		<div className="cd-modal-overlay" onClick={ onClose }>
			<div className="cd-modal-box douxir-manage-cat" onClick={ ( event ) => event.stopPropagation() }>
				<div className="cd-modal-header">
					<h3 className="cd-modal-title">{ __( 'Manage category', 'douxir' ) }</h3>
					<button type="button" className="cd-icon-btn" onClick={ onClose }>
						<Icon name="x" size={ 18 } />
					</button>
				</div>
				<form onSubmit={ onSubmit }>
					<div className="cd-modal-body">
						{ category.id > 0 && (
							<>
								<div className="cd-form-group">
									<label className="cd-form-label">{ __( 'Category name', 'douxir' ) }</label>
									<input className="cd-form-input" value={ name } onChange={ ( event ) => setName( event.target.value ) } required />
								</div>
								<div className="cd-form-group">
									<label className="cd-form-label">{ __( 'Description', 'douxir' ) }</label>
									<textarea className="cd-form-input" rows={ 3 } value={ description } onChange={ ( event ) => setDescription( event.target.value ) } />
								</div>
								<div className="cd-form-group">
									<label className="cd-form-label">{ __( 'Category icon', 'douxir' ) }</label>
									<select className="cd-form-select" value={ icon } onChange={ ( event ) => setIcon( event.target.value ) }>
										<option value="">{ __( 'None', 'douxir' ) }</option>
										{ icons.map( ( key ) => (
											<option key={ key } value={ key }>
												{ key }
											</option>
										) ) }
									</select>
								</div>
							</>
						) }
						<div className="cd-form-group">
							<label className="cd-form-label">{ __( 'Document order', 'douxir' ) }</label>
							<p className="douxir-field-help">
								{ __( 'Drag documents to reorder them, or type a number. Lower numbers appear first on the front end.', 'douxir' ) }
							</p>
							{ items.length === 0 ? (
								<p className="douxir-field-help">{ __( 'No documents in this category yet.', 'douxir' ) }</p>
							) : (
								<ul className="douxir-order-list">
									{ items.map( ( item ) => (
										<li
											key={ item.id }
											className={ 'douxir-order-item' + ( over === item.id ? ' is-over' : '' ) }
											draggable
											onDragStart={ () => setDragging( item.id ) }
											onDragOver={ ( event ) => {
												event.preventDefault();
												setOver( item.id );
											} }
											onDrop={ ( event ) => onDrop( event, item.id ) }
											onDragEnd={ () => {
												setDragging( null );
												setOver( null );
											} }
										>
											<span className="douxir-order-handle" aria-hidden="true">
												<Icon name="grip-vertical" size={ 14 } />
											</span>
											<span className="douxir-order-title">{ item.title }</span>
											<input
												type="number"
												className="cd-form-input douxir-order-input"
												value={ item.menu_order }
												onChange={ ( event ) => setOrder( item.id, event.target.value ) }
												aria-label={ __( 'Order', 'douxir' ) }
											/>
										</li>
									) ) }
								</ul>
							) }
						</div>
					</div>
					<div className="cd-modal-footer">
						<button type="button" className="cd-btn cd-btn-ghost" onClick={ onClose }>
							{ __( 'Cancel', 'douxir' ) }
						</button>
						<button type="submit" className="cd-btn cd-btn-primary" disabled={ busy || ( category.id > 0 && ! name.trim() ) }>
							{ busy ? __( 'Saving...', 'douxir' ) : __( 'Save order', 'douxir' ) }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
