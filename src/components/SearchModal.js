import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Icon from '../icons';

export default function SearchModal( { isOpen, onClose, allDocs = [], categories = [], onSelectDoc } ) {
	const [ query, setQuery ] = useState( '' );
	const [ selected, setSelected ] = useState( 0 );
	const inputRef = useRef( null );

	useEffect( () => {
		if ( isOpen ) {
			setQuery( '' );
			setSelected( 0 );
			setTimeout( () => {
				if ( inputRef.current ) {
					inputRef.current.focus();
				}
			}, 50 );
		}
	}, [ isOpen ] );

	const results = useMemo( () => {
		if ( ! query.trim() ) {
			return allDocs.slice( 0, 10 );
		}
		const needle = query.toLowerCase();
		return allDocs
			.filter( ( doc ) => {
				const titleMatch = doc.title.toLowerCase().includes( needle );
				const slugMatch = doc.slug && doc.slug.toLowerCase().includes( needle );
				return titleMatch || slugMatch;
			} )
			.slice( 0, 15 );
	}, [ allDocs, query ] );

	const onKeyDown = ( event ) => {
		if ( event.key === 'ArrowDown' ) {
			event.preventDefault();
			setSelected( ( index ) => ( index < results.length - 1 ? index + 1 : 0 ) );
		} else if ( event.key === 'ArrowUp' ) {
			event.preventDefault();
			setSelected( ( index ) => ( index > 0 ? index - 1 : results.length - 1 ) );
		} else if ( event.key === 'Enter' ) {
			event.preventDefault();
			if ( results[ selected ] ) {
				onSelectDoc( results[ selected ].id );
				onClose();
			}
		} else if ( event.key === 'Escape' ) {
			onClose();
		}
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<div className="cd-modal-overlay" onClick={ onClose }>
			<div className="cd-search-modal" onClick={ ( event ) => event.stopPropagation() }>
				<div className="cd-search-modal-header">
					<Icon name="search" size={ 18 } color="#6366f1" />
					<input
						ref={ inputRef }
						type="text"
						className="cd-search-modal-input"
						placeholder={ __( 'Type to search documentation...', 'douxir' ) }
						value={ query }
						onChange={ ( event ) => {
							setQuery( event.target.value );
							setSelected( 0 );
						} }
						onKeyDown={ onKeyDown }
					/>
					<button type="button" className="cd-icon-btn" onClick={ onClose }>
						<Icon name="x" size={ 18 } />
					</button>
				</div>
				<div className="cd-search-modal-results">
					{ results.length === 0 ? (
						<div style={ { padding: '24px', textAlign: 'center', color: '#94a3b8', fontSize: '13px' } }>
							{ sprintf(
								/* translators: %s: search query */
								__( 'No documents found matching "%s".', 'douxir' ),
								query
							) }
						</div>
					) : (
						results.map( ( doc, index ) => {
							const category = categories.find( ( cat ) => cat.id === ( doc.categories && doc.categories[ 0 ] ) );
							return (
								<div
									key={ doc.id }
									className={ `cd-search-result-item ${ index === selected ? 'selected' : '' }` }
									onClick={ () => {
										onSelectDoc( doc.id );
										onClose();
									} }
									onMouseEnter={ () => setSelected( index ) }
								>
									<div style={ { display: 'flex', alignItems: 'center', gap: '10px' } }>
										<Icon name="file-text" size={ 15 } color="#6366f1" />
										<div>
											<div className="cd-search-result-title">{ doc.title }</div>
											<div style={ { fontSize: '11px', color: '#94a3b8' } }>
												{ __( 'Slug:', 'douxir' ) } /{ doc.slug }
											</div>
										</div>
									</div>
									<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
										{ category && <span className="cd-search-result-cat">{ category.name }</span> }
										{ doc.pro && <span className="cd-pro-badge">{ __( 'PRO', 'douxir' ) }</span> }
										<span className={ `cd-status-badge ${ doc.status }` } style={ { fontSize: '10px', padding: '1px 6px' } }>
											{ doc.status }
										</span>
									</div>
								</div>
							);
						} )
					) }
				</div>
				<div
					style={ {
						padding: '8px 16px',
						background: '#f8fafc',
						borderTop: '1px solid #e2e8f0',
						display: 'flex',
						justifyContent: 'space-between',
						fontSize: '11px',
						color: '#64748b',
					} }
				>
					<span>{ __( 'Navigation: ↑ ↓ arrows to select, Enter to open', 'douxir' ) }</span>
					<span>{ __( 'ESC to close', 'douxir' ) }</span>
				</div>
			</div>
		</div>
	);
}
