import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Icon from '../icons';

export default function DocView( {
	doc,
	allDocs = [],
	categories = [],
	onSelectDoc,
	onOpenQuickEdit,
	onTrashDoc,
	onNewChildDoc,
	onSaveContent,
	onReorderDocs,
} ) {
	const [ tab, setTab ] = useState( 'preview' );
	const [ slugCopied, setSlugCopied ] = useState( false );
	const [ title, setTitle ] = useState( doc.title );
	const [ content, setContent ] = useState( doc.content_raw || '' );
	const [ saving, setSaving ] = useState( false );

	useEffect( () => {
		setTitle( doc.title );
		setContent( doc.content_raw || '' );
	}, [ doc.id, doc.title, doc.content_raw ] );

	const categoryName = useMemo( () => {
		if ( ! doc.categories || doc.categories.length === 0 ) {
			return __( 'Uncategorized', 'douxir' );
		}
		const match = categories.find( ( cat ) => cat.id === doc.categories[ 0 ] );
		return match ? match.name : __( 'Uncategorized', 'douxir' );
	}, [ doc.categories, categories ] );

	const parentDoc = useMemo(
		() => ( doc.parent_id ? allDocs.find( ( item ) => item.id === doc.parent_id ) : null ),
		[ doc.parent_id, allDocs ]
	);

	const children = useMemo(
		() => allDocs.filter( ( item ) => item.parent_id === doc.id ).sort( ( a, b ) => ( a.menu_order || 0 ) - ( b.menu_order || 0 ) ),
		[ doc.id, allDocs ]
	);

	const moveChild = ( id, direction ) => {
		const list = [ ...children ];
		const index = list.findIndex( ( item ) => item.id === id );
		if ( index === -1 ) {
			return;
		}
		const next = direction === 'up' ? index - 1 : index + 1;
		if ( next < 0 || next >= list.length ) {
			return;
		}
		const [ moved ] = list.splice( index, 1 );
		list.splice( next, 0, moved );
		if ( onReorderDocs ) {
			onReorderDocs( list.map( ( item, order ) => ( { id: item.id, menu_order: order + 1 } ) ) );
		}
	};

	const copyPermalink = () => {
		if ( doc.permalink ) {
			navigator.clipboard.writeText( doc.permalink );
			setSlugCopied( true );
			setTimeout( () => setSlugCopied( false ), 2000 );
		}
	};

	const saveContent = async () => {
		setSaving( true );
		await onSaveContent( { id: doc.id, title, content } );
		setSaving( false );
	};

	const firstCategoryId = ( doc.categories && doc.categories[ 0 ] ) || 0;

	return (
		<div className="cd-doc-view-card">
			<div className="cd-doc-view-header">
				<div className="cd-doc-breadcrumbs">
					<span>{ __( 'Documentation', 'douxir' ) }</span>
					<span>/</span>
					<span>{ categoryName }</span>
					{ parentDoc && (
						<>
							<span>/</span>
							<span style={ { cursor: 'pointer', color: '#6366f1' } } onClick={ () => onSelectDoc( parentDoc.id ) }>
								{ parentDoc.title }
							</span>
						</>
					) }
					<span>/</span>
					<span style={ { color: '#0f172a', fontWeight: 600 } }>{ doc.title }</span>
				</div>
				<div className="cd-doc-title-row">
					<div className="cd-doc-view-title">
						<span>{ doc.title }</span>
						<span className={ `cd-status-badge ${ doc.status }` }>{ doc.status }</span>
						{ doc.pro && <span className="cd-pro-badge">{ __( 'PRO', 'douxir' ) }</span> }
					</div>
					<div className="cd-doc-actions-bar">
						{ doc.permalink && (
							<a href={ doc.permalink } target="_blank" rel="noreferrer" className="cd-btn cd-btn-outline cd-btn-sm">
								<Icon name="external-link" size={ 13 } />
								<span>{ __( 'View on Site', 'douxir' ) }</span>
							</a>
						) }
						<a
							href={ doc.edit_url }
							target="_blank"
							rel="noreferrer"
							className="cd-btn cd-btn-outline cd-btn-sm"
							title={ __( 'Open in the WordPress editor', 'douxir' ) }
						>
							<Icon name="pen-line" size={ 13 } />
							<span>{ __( 'WP Editor', 'douxir' ) }</span>
						</a>
						<button type="button" className="cd-btn cd-btn-primary cd-btn-sm" onClick={ () => onOpenQuickEdit( doc ) }>
							<Icon name="pen-line" size={ 13 } />
							<span>{ __( 'Quick Edit', 'douxir' ) }</span>
						</button>
						<button
							type="button"
							className="cd-btn cd-btn-danger-ghost cd-btn-sm"
							title={ __( 'Move to trash', 'douxir' ) }
							onClick={ () => {
								if ( window.confirm( sprintf(
								/* translators: %s: document title */
								__( 'Are you sure you want to move "%s" to trash?', 'douxir' ),
								doc.title
							) ) ) {
									onTrashDoc( doc.id );
								}
							} }
						>
							<Icon name="trash" size={ 14 } />
						</button>
					</div>
				</div>
			</div>
			<div className="cd-doc-meta-strip">
				<div className="cd-meta-item">
					<strong>{ __( 'Slug:', 'douxir' ) }</strong>
					<button type="button" className="cd-copy-slug-btn" onClick={ copyPermalink }>
						{ slugCopied ? <Icon name="check" size={ 12 } color="#10b981" /> : <Icon name="copy" size={ 12 } /> }
						<span>{ doc.slug || __( '(empty)', 'douxir' ) }</span>
					</button>
				</div>
				<div className="cd-meta-item">
					<strong>{ __( 'Category:', 'douxir' ) }</strong>
					<span>{ categoryName }</span>
				</div>
				{ parentDoc && (
					<div className="cd-meta-item">
						<strong>{ __( 'Parent:', 'douxir' ) }</strong>
						<span
							style={ { color: '#6366f1', cursor: 'pointer', textDecoration: 'underline' } }
							onClick={ () => onSelectDoc( parentDoc.id ) }
						>
							{ parentDoc.title }
						</span>
					</div>
				) }
				<div className="cd-meta-item">
					<strong>{ __( 'Menu Order:', 'douxir' ) }</strong>
					<span>{ doc.menu_order }</span>
				</div>
				<div className="cd-meta-item">
					<Icon name="calendar" size={ 13 } />
					<span>
						{ __( 'Updated:', 'douxir' ) } { doc.modified || doc.date }
					</span>
				</div>
				<div className="cd-meta-item">
					<Icon name="user" size={ 13 } />
					<span>{ doc.author }</span>
				</div>
			</div>
			<div className="cd-doc-view-tabs">
				<button type="button" className={ `cd-tab-item ${ tab === 'preview' ? 'active' : '' }` } onClick={ () => setTab( 'preview' ) }>
					<Icon name="eye" size={ 14 } />
					<span>{ __( 'Reader Preview', 'douxir' ) }</span>
				</button>
				<button type="button" className={ `cd-tab-item ${ tab === 'edit' ? 'active' : '' }` } onClick={ () => setTab( 'edit' ) }>
					<Icon name="pen-line" size={ 14 } />
					<span>{ __( 'Quick Content Editor', 'douxir' ) }</span>
				</button>
				<button type="button" className={ `cd-tab-item ${ tab === 'subdocs' ? 'active' : '' }` } onClick={ () => setTab( 'subdocs' ) }>
					<Icon name="layers" size={ 14 } />
					<span>
						{ __( 'Sub-Documents', 'douxir' ) } ({ children.length })
					</span>
				</button>
			</div>
			{ tab === 'preview' && (
				<div className="cd-doc-body-preview">
					{ doc.content_rendered ? (
						<div dangerouslySetInnerHTML={ { __html: doc.content_rendered } } />
					) : (
						<div style={ { textAlign: 'center', padding: '40px 20px', color: '#94a3b8' } }>
							<Icon name="file-text" size={ 36 } style={ { margin: '0 auto 12px', opacity: 0.5 } } />
							<p style={ { margin: 0, fontSize: '15px', fontWeight: 500 } }>
								{ __( 'This document does not have any content yet.', 'douxir' ) }
							</p>
							<div style={ { marginTop: '16px', display: 'flex', gap: '10px', justifyContent: 'center' } }>
								<button type="button" className="cd-btn cd-btn-outline cd-btn-sm" onClick={ () => setTab( 'edit' ) }>
									<Icon name="pen-line" size={ 13 } />
									<span>{ __( 'Write Content Now', 'douxir' ) }</span>
								</button>
								<a href={ doc.edit_url } target="_blank" rel="noreferrer" className="cd-btn cd-btn-primary cd-btn-sm">
									<span>{ __( 'Open WordPress Editor', 'douxir' ) }</span>
								</a>
							</div>
						</div>
					) }
				</div>
			) }
			{ tab === 'edit' && (
				<div style={ { padding: '24px' } }>
					<div className="cd-form-group">
						<label className="cd-form-label">{ __( 'Document Title', 'douxir' ) }</label>
						<input type="text" className="cd-form-input" value={ title } onChange={ ( event ) => setTitle( event.target.value ) } />
					</div>
					<div className="cd-form-group">
						<label className="cd-form-label">{ __( 'Document Content (HTML / Text)', 'douxir' ) }</label>
						<textarea
							className="cd-form-textarea"
							style={ { minHeight: '360px', fontFamily: 'monospace', fontSize: '13px', lineHeight: 1.6 } }
							value={ content }
							onChange={ ( event ) => setContent( event.target.value ) }
							placeholder={ __( 'Write your documentation content here...', 'douxir' ) }
						/>
					</div>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<span style={ { fontSize: '12px', color: '#64748b' } }>
							{ __( 'For advanced block styling or media uploads, use the full', 'douxir' ) }{ ' ' }
							<a href={ doc.edit_url } target="_blank" rel="noreferrer" style={ { color: '#6366f1' } }>
								{ __( 'WordPress Editor', 'douxir' ) }
							</a>
							.
						</span>
						<button type="button" className="cd-btn cd-btn-primary" disabled={ saving } onClick={ saveContent }>
							<Icon name="save" size={ 15 } />
							<span>{ saving ? __( 'Saving Changes...', 'douxir' ) : __( 'Save Changes', 'douxir' ) }</span>
						</button>
					</div>
				</div>
			) }
			{ tab === 'subdocs' && (
				<div style={ { padding: '24px' } }>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' } }>
						<h4 style={ { margin: 0, fontSize: '15px', fontWeight: 700, color: '#1e293b' } }>
							{ sprintf(
								/* translators: %s: parent document title */
								__( 'Child Pages of "%s"', 'douxir' ),
								doc.title
							) }
						</h4>
						<button
							type="button"
							className="cd-btn cd-btn-primary cd-btn-sm"
							onClick={ () => onNewChildDoc( doc.id, firstCategoryId ) }
						>
							<Icon name="plus" size={ 13 } />
							<span>{ __( 'Add Child Document', 'douxir' ) }</span>
						</button>
					</div>
					{ children.length === 0 ? (
						<div style={ { textAlign: 'center', padding: '30px', background: '#f8fafc', borderRadius: '8px', border: '1px dashed #cbd5e1' } }>
							<p style={ { margin: 0, color: '#64748b', fontSize: '14px' } }>
								{ __( 'No child documents under this article.', 'douxir' ) }
							</p>
							<button
								type="button"
								className="cd-btn cd-btn-outline cd-btn-sm"
								style={ { marginTop: '12px' } }
								onClick={ () => onNewChildDoc( doc.id, firstCategoryId ) }
							>
								<Icon name="plus" size={ 13 } />
								<span>{ __( 'Create First Sub-Doc', 'douxir' ) }</span>
							</button>
						</div>
					) : (
						<ul style={ { listStyle: 'none', margin: 0, padding: 0 } }>
							{ children.map( ( child, index ) => (
								<li
									key={ child.id }
									style={ {
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'space-between',
										padding: '12px 16px',
										background: '#ffffff',
										border: '1px solid #e2e8f0',
										borderRadius: '8px',
										marginBottom: '8px',
										cursor: 'pointer',
									} }
									onClick={ () => onSelectDoc( child.id ) }
								>
									<div style={ { display: 'flex', alignItems: 'center', gap: '10px' } }>
										<Icon name="corner-down-right" size={ 14 } color="#6366f1" />
										<span style={ { fontWeight: 600, color: '#1e293b', fontSize: '14px' } }>{ child.title }</span>
										<span className={ `cd-status-badge ${ child.status }` } style={ { fontSize: '11px', padding: '2px 8px' } }>
											{ child.status }
										</span>
										{ child.pro && (
											<span className="cd-pro-badge" style={ { fontSize: '9px' } }>
												{ __( 'PRO', 'douxir' ) }
											</span>
										) }
									</div>
									<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
										<span style={ { fontSize: '12px', color: '#94a3b8' } }>
											{ __( 'Order:', 'douxir' ) } { child.menu_order }
										</span>
										<div style={ { display: 'flex', alignItems: 'center', gap: '2px' } }>
											<button
												type="button"
												className="cd-icon-btn"
												title={ __( 'Move Up', 'douxir' ) }
												disabled={ index === 0 }
												style={ { opacity: index === 0 ? 0.3 : 1 } }
												onClick={ ( event ) => {
													event.stopPropagation();
													moveChild( child.id, 'up' );
												} }
											>
												<Icon name="arrow-up" size={ 13 } />
											</button>
											<button
												type="button"
												className="cd-icon-btn"
												title={ __( 'Move Down', 'douxir' ) }
												disabled={ index === children.length - 1 }
												style={ { opacity: index === children.length - 1 ? 0.3 : 1 } }
												onClick={ ( event ) => {
													event.stopPropagation();
													moveChild( child.id, 'down' );
												} }
											>
												<Icon name="arrow-down" size={ 13 } />
											</button>
										</div>
										<button
											type="button"
											className="cd-btn cd-btn-ghost cd-btn-sm"
											onClick={ ( event ) => {
												event.stopPropagation();
												onSelectDoc( child.id );
											} }
										>
											<span>{ __( 'View', 'douxir' ) }</span>
										</button>
									</div>
								</li>
							) ) }
						</ul>
					) }
				</div>
			) }
		</div>
	);
}
