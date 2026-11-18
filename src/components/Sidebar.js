import { useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Icon from '../icons';

export default function Sidebar( {
	categories = [],
	allDocs = [],
	selectedDocId,
	onSelectDoc,
	onNewDocForCategory,
	onNewDocForParent,
	onHomeClick,
	onReorderDocs,
	onReorderCategories,
} ) {
	const [ filter, setFilter ] = useState( '' );
	const [ openCats, setOpenCats ] = useState( () => {
		const state = {};
		categories.forEach( ( cat ) => {
			state[ cat.id ] = true;
		} );
		return state;
	} );
	const [ openDocs, setOpenDocs ] = useState( () => {
		const state = {};
		allDocs.forEach( ( doc ) => {
			if ( doc.children && doc.children.length ) {
				state[ doc.id ] = true;
			}
		} );
		return state;
	} );
	const [ draggingCat, setDraggingCat ] = useState( null );
	const [ overCat, setOverCat ] = useState( null );
	const [ draggingDoc, setDraggingDoc ] = useState( null );
	const [ overDoc, setOverDoc ] = useState( null );

	const toggleCat = ( id, event ) => {
		if ( event ) {
			event.stopPropagation();
		}
		setOpenCats( ( state ) => ( { ...state, [ id ]: ! state[ id ] } ) );
	};

	const toggleDoc = ( id, event ) => {
		if ( event ) {
			event.stopPropagation();
		}
		setOpenDocs( ( state ) => ( { ...state, [ id ]: ! state[ id ] } ) );
	};

	const visibleCats = useMemo( () => {
		if ( ! filter.trim() ) {
			return categories;
		}
		const needle = filter.toLowerCase();
		return categories
			.map( ( cat ) => {
				const nameMatch = cat.name.toLowerCase().includes( needle );
				const docs = ( cat.docs || [] ).filter( ( doc ) => {
					const titleMatch = doc.title.toLowerCase().includes( needle );
					const childMatch = doc.children && doc.children.some( ( child ) => child.title.toLowerCase().includes( needle ) );
					return titleMatch || childMatch || nameMatch;
				} );
				return { ...cat, docs, hasMatch: nameMatch || docs.length > 0 };
			} )
			.filter( ( cat ) => cat.hasMatch );
	}, [ categories, filter ] );

	const onCatDragStart = ( event, id ) => {
		setDraggingCat( id );
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData( 'text/plain', `cat:${ id }` );
	};

	const onCatDragOver = ( event, id ) => {
		event.preventDefault();
		if ( draggingCat && draggingCat !== id ) {
			setOverCat( id );
		}
	};

	const onCatDrop = ( event, id ) => {
		event.preventDefault();
		setOverCat( null );
		if ( ! draggingCat || draggingCat === id ) {
			setDraggingCat( null );
			return;
		}
		const ids = categories.map( ( cat ) => cat.id );
		const from = ids.indexOf( draggingCat );
		const to = ids.indexOf( id );
		if ( from !== -1 && to !== -1 ) {
			const next = [ ...ids ];
			const [ moved ] = next.splice( from, 1 );
			next.splice( to, 0, moved );
			onReorderCategories( next );
		}
		setDraggingCat( null );
	};

	const onDocDragStart = ( event, id ) => {
		event.stopPropagation();
		setDraggingDoc( id );
		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.setData( 'text/plain', `doc:${ id }` );
	};

	const onDocDragOver = ( event, id ) => {
		event.preventDefault();
		event.stopPropagation();
		if ( draggingDoc && draggingDoc !== id ) {
			setOverDoc( id );
		}
	};

	const onDocDrop = ( event, id, siblings ) => {
		event.preventDefault();
		event.stopPropagation();
		setOverDoc( null );
		if ( ! draggingDoc || draggingDoc === id ) {
			setDraggingDoc( null );
			return;
		}
		const ids = siblings.map( ( doc ) => doc.id );
		const from = ids.indexOf( draggingDoc );
		const to = ids.indexOf( id );
		if ( from !== -1 && to !== -1 ) {
			const next = [ ...siblings ];
			const [ moved ] = next.splice( from, 1 );
			next.splice( to, 0, moved );
			onReorderDocs( next.map( ( doc, index ) => ( { id: doc.id, menu_order: index + 1 } ) ) );
		}
		setDraggingDoc( null );
	};

	return (
		<aside className="cd-sidebar">
			<div className="cd-sidebar-filter">
				<div className="cd-search-icon-wrap">
					<Icon name="search" size={ 15 } className="cd-search-icon" />
					<input
						type="text"
						className="cd-input-search"
						placeholder={ __( 'Filter categories & docs...', 'douxir' ) }
						value={ filter }
						onChange={ ( event ) => setFilter( event.target.value ) }
					/>
				</div>
				<div className="cd-sidebar-tabs">
					<button type="button" className={ `cd-sidebar-tab ${ selectedDocId ? '' : 'active' }` } onClick={ onHomeClick }>
						{ __( 'Dashboard', 'douxir' ) }
					</button>
					<div style={ { flex: 1, textAlign: 'right', fontSize: '11px', color: '#94a3b8', lineHeight: '24px' } }>
						{ allDocs.length } { __( 'Docs Total', 'douxir' ) }
					</div>
				</div>
			</div>
			<div className="cd-tree-scroll">
				{ visibleCats.length === 0 ? (
					<div style={ { padding: '24px 16px', textAlign: 'center', color: '#94a3b8', fontSize: '13px' } }>
						{ __( 'No matching documents found.', 'douxir' ) }
					</div>
				) : (
					visibleCats.map( ( cat ) => {
						const isOpen = openCats[ cat.id ] ?? true;
						return (
							<div
								key={ cat.id }
								className={ `cd-cat-group ${ draggingCat === cat.id ? 'cd-dragging' : '' } ${ overCat === cat.id ? 'cd-drag-over-top' : '' }` }
								onDragOver={ ( event ) => onCatDragOver( event, cat.id ) }
								onDrop={ ( event ) => onCatDrop( event, cat.id ) }
							>
								<div className="cd-cat-header" onClick={ ( event ) => toggleCat( cat.id, event ) }>
									<div className="cd-cat-info">
										<span
											className="cd-drag-handle"
											title={ __( 'Drag to reorder category', 'douxir' ) }
											draggable
											onDragStart={ ( event ) => onCatDragStart( event, cat.id ) }
											onDragEnd={ () => {
												setDraggingCat( null );
												setOverCat( null );
											} }
											onClick={ ( event ) => event.stopPropagation() }
										>
											<Icon name="grip-vertical" size={ 13 } />
										</span>
										{ isOpen ? (
											<Icon name="chevron-down" size={ 15 } color="#64748b" />
										) : (
											<Icon name="chevron-right" size={ 15 } color="#64748b" />
										) }
										<Icon name="folder" size={ 15 } color="#6366f1" />
										<span className="cd-cat-name" title={ cat.name }>
											{ cat.name }
										</span>
										<span className="cd-cat-count">{ ( cat.docs && cat.docs.length ) || 0 }</span>
									</div>
									<div className="cd-cat-actions">
										<button
											type="button"
											className="cd-icon-btn"
											title={ sprintf(
												/* translators: %s: category name */
												__( 'Add doc to %s', 'douxir' ),
												cat.name
											) }
											onClick={ ( event ) => {
												event.stopPropagation();
												onNewDocForCategory( cat.id );
											} }
										>
											<Icon name="plus" size={ 14 } />
										</button>
									</div>
								</div>
								{ isOpen && (
									<ul className="cd-doc-list">
										{ ! cat.docs || cat.docs.length === 0 ? (
											<li style={ { padding: '6px 12px 6px 26px', fontSize: '12px', color: '#94a3b8', fontStyle: 'italic' } }>
												{ __( 'No docs yet in this category.', 'douxir' ) }
											</li>
										) : (
											cat.docs.map( ( doc ) => {
												const hasChildren = doc.children && doc.children.length > 0;
												const childrenOpen = openDocs[ doc.id ] ?? true;
												return (
													<li
														key={ doc.id }
														className={ `${ draggingDoc === doc.id ? 'cd-dragging' : '' } ${ overDoc === doc.id ? 'cd-drag-over-top' : '' }` }
														onDragOver={ ( event ) => onDocDragOver( event, doc.id ) }
														onDrop={ ( event ) => onDocDrop( event, doc.id, cat.docs ) }
													>
														<div className={ `cd-doc-item ${ selectedDocId === doc.id ? 'active' : '' }` } onClick={ () => onSelectDoc( doc.id ) }>
															<div className="cd-doc-main">
																<span
																	className="cd-drag-handle"
																	title={ __( 'Drag to reorder document', 'douxir' ) }
																	draggable
																	onDragStart={ ( event ) => onDocDragStart( event, doc.id ) }
																	onDragEnd={ () => {
																		setDraggingDoc( null );
																		setOverDoc( null );
																	} }
																	onClick={ ( event ) => event.stopPropagation() }
																>
																	<Icon name="grip-vertical" size={ 13 } />
																</span>
																{ hasChildren ? (
																	<button
																		type="button"
																		className="cd-icon-btn"
																		style={ { padding: 0, width: '16px', height: '16px' } }
																		onClick={ ( event ) => toggleDoc( doc.id, event ) }
																	>
																		{ childrenOpen ? (
																			<Icon name="chevron-down" size={ 14 } color="#64748b" />
																		) : (
																			<Icon name="chevron-right" size={ 14 } color="#64748b" />
																		) }
																	</button>
																) : (
																	<Icon
																		name="file-text"
																		size={ 14 }
																		color={ selectedDocId === doc.id ? '#6366f1' : '#94a3b8' }
																		style={ { flexShrink: 0 } }
																	/>
																) }
																<span className={ `cd-status-dot ${ doc.status }` } title={ `${ __( 'Status:', 'douxir' ) } ${ doc.status }` } />
																<span className="cd-doc-title" title={ doc.title }>
																	{ doc.title }
																</span>
																{ doc.pro && <span className="cd-pro-badge">{ __( 'PRO', 'douxir' ) }</span> }
															</div>
															<div className="cd-cat-actions">
																<button
																	type="button"
																	className="cd-icon-btn"
																	title={ __( 'Add child page', 'douxir' ) }
																	onClick={ ( event ) => {
																		event.stopPropagation();
																		onNewDocForParent( doc.id, cat.id );
																	} }
																>
																	<Icon name="plus" size={ 12 } />
																</button>
															</div>
														</div>
														{ hasChildren && childrenOpen && (
															<ul className="cd-doc-children">
																{ doc.children.map( ( child ) => (
																	<li
																		key={ child.id }
																		className={ `${ draggingDoc === child.id ? 'cd-dragging' : '' } ${ overDoc === child.id ? 'cd-drag-over-top' : '' }` }
																		onDragOver={ ( event ) => onDocDragOver( event, child.id ) }
																		onDrop={ ( event ) => onDocDrop( event, child.id, doc.children ) }
																	>
																		<div className={ `cd-doc-item ${ selectedDocId === child.id ? 'active' : '' }` } onClick={ () => onSelectDoc( child.id ) }>
																			<div className="cd-doc-main">
																				<span
																					className="cd-drag-handle"
																					title={ __( 'Drag to reorder child document', 'douxir' ) }
																					draggable
																					onDragStart={ ( event ) => onDocDragStart( event, child.id ) }
																					onDragEnd={ () => {
																						setDraggingDoc( null );
																						setOverDoc( null );
																					} }
																					onClick={ ( event ) => event.stopPropagation() }
																				>
																					<Icon name="grip-vertical" size={ 12 } />
																				</span>
																				<Icon name="corner-down-right" size={ 12 } color="#94a3b8" style={ { flexShrink: 0 } } />
																				<span className={ `cd-status-dot ${ child.status }` } title={ `${ __( 'Status:', 'douxir' ) } ${ child.status }` } />
																				<span className="cd-doc-title" title={ child.title }>
																					{ child.title }
																				</span>
																				{ child.pro && <span className="cd-pro-badge">{ __( 'PRO', 'douxir' ) }</span> }
																			</div>
																		</div>
																	</li>
																) ) }
															</ul>
														) }
													</li>
												);
											} )
										) }
									</ul>
								) }
							</div>
						);
					} )
				) }
			</div>
		</aside>
	);
}
