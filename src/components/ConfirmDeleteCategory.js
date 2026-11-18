import { useEffect, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Icon from '../icons';

export default function ConfirmDeleteCategory( { cat, busy, onCancel, onConfirm } ) {
	const docCount = cat.docs ? cat.docs.length : 0;
	const cancelRef = useRef( null );

	useEffect( () => {
		if ( cancelRef.current ) {
			cancelRef.current.focus();
		}
		const onKey = ( event ) => {
			if ( event.key === 'Escape' && ! busy ) {
				onCancel();
			}
		};
		window.addEventListener( 'keydown', onKey );
		return () => window.removeEventListener( 'keydown', onKey );
	}, [ busy, onCancel ] );

	const docsMessage =
		docCount > 0
			? sprintf(
					/* translators: %s: "1 document is" or "N documents are" */
					__( 'Its %s not deleted. They stay in Douxir and move to Uncategorized, unless they also belong to another category.', 'douxir' ),
					docCount === 1
						? __( '1 document is', 'douxir' )
						: sprintf(
								/* translators: %d: number of documents */
								__( '%d documents are', 'douxir' ),
								docCount
						  )
			  )
			: __( 'This category has no documents.', 'douxir' );

	return (
		<div
			className="cd-modal-overlay"
			onClick={ () => {
				if ( ! busy ) {
					onCancel();
				}
			} }
		>
			<div
				className="cd-modal-box douxir-confirm-box"
				role="alertdialog"
				aria-modal="true"
				aria-labelledby="douxir-del-title"
				aria-describedby="douxir-del-desc"
				onClick={ ( event ) => event.stopPropagation() }
			>
				<div className="cd-modal-header">
					<h3 id="douxir-del-title" className="cd-modal-title">
						{ __( 'Delete category?', 'douxir' ) }
					</h3>
					<button type="button" className="cd-icon-btn" aria-label={ __( 'Close', 'douxir' ) } onClick={ onCancel } disabled={ busy }>
						<Icon name="x" size={ 18 } />
					</button>
				</div>
				<div className="cd-modal-body" id="douxir-del-desc">
					<p className="douxir-confirm-text">
						{ __( 'The category', 'douxir' ) }{ ' ' }
						<strong>{ cat.name }</strong>{ ' ' }
						{ __( 'will be permanently deleted.', 'douxir' ) }
					</p>
					<p className="douxir-confirm-text">{ docsMessage }</p>
					<p className="douxir-confirm-text">
						{ __( 'Child documents stay under their parent document, and subcategories move up one level.', 'douxir' ) }
					</p>
				</div>
				<div className="cd-modal-footer">
					<button type="button" ref={ cancelRef } className="cd-btn cd-btn-outline" onClick={ onCancel } disabled={ busy }>
						{ __( 'Cancel', 'douxir' ) }
					</button>
					<button type="button" className="cd-btn douxir-btn-danger" onClick={ onConfirm } disabled={ busy }>
						{ busy ? __( 'Deleting...', 'douxir' ) : __( 'Delete category', 'douxir' ) }
					</button>
				</div>
			</div>
		</div>
	);
}
