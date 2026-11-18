import { __ } from '@wordpress/i18n';
import Icon from '../icons';

export default function Overview( {
	stats = {},
	categories = [],
	onSelectDoc,
	onNewDoc,
	onNewCat,
	onDeleteCat,
	onManageCat,
} ) {
	return (
		<div className="cd-overview-container">
			<div className="cd-overview-header">
				<h2 className="cd-overview-title">{ __( 'Welcome to Douxir', 'douxir' ) }</h2>
				<p className="cd-overview-sub">
					{ __( 'Manage your knowledge base, categories, and documentation hierarchy with an intuitive workspace.', 'douxir' ) }
				</p>
			</div>
			<div className="cd-stats-grid">
				<div className="cd-stat-card">
					<div className="cd-stat-icon indigo">
						<Icon name="file-text" size={ 22 } />
					</div>
					<div>
						<div className="cd-stat-num">{ stats.total_docs || 0 }</div>
						<div className="cd-stat-label">{ __( 'Total Documents', 'douxir' ) }</div>
					</div>
				</div>
				<div className="cd-stat-card">
					<div className="cd-stat-icon emerald">
						<Icon name="folder" size={ 22 } />
					</div>
					<div>
						<div className="cd-stat-num">{ stats.total_categories || 0 }</div>
						<div className="cd-stat-label">{ __( 'Categories', 'douxir' ) }</div>
					</div>
				</div>
				<div className="cd-stat-card">
					<div className="cd-stat-icon purple">
						<Icon name="sparkles" size={ 22 } />
					</div>
					<div>
						<div className="cd-stat-num">{ stats.pro_docs || 0 }</div>
						<div className="cd-stat-label">{ __( 'PRO Articles', 'douxir' ) }</div>
					</div>
				</div>
				<div className="cd-stat-card">
					<div className="cd-stat-icon amber">
						<Icon name="circle-check" size={ 22 } />
					</div>
					<div>
						<div className="cd-stat-num">
							{ stats.published_docs || 0 }{ ' ' }
							<span style={ { fontSize: '14px', fontWeight: 500, color: '#94a3b8' } }>
								/ { stats.draft_docs || 0 } { __( 'draft', 'douxir' ) }
							</span>
						</div>
						<div className="cd-stat-label">{ __( 'Published / Drafts', 'douxir' ) }</div>
					</div>
				</div>
			</div>
			<div className="cd-section-title">
				<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
					<span>{ __( 'Categories & Sections', 'douxir' ) }</span>
					<span style={ { fontSize: '12px', fontWeight: 400, color: '#64748b' } }>
						{ __( '(Drag and drop in the left sidebar to change order)', 'douxir' ) }
					</span>
				</div>
				<button type="button" className="cd-btn cd-btn-outline cd-btn-sm" onClick={ onNewCat }>
					<Icon name="plus" size={ 14 } />
					<span>{ __( 'Add Category', 'douxir' ) }</span>
				</button>
			</div>
			<div className="cd-cats-grid">
				{ categories.map( ( cat ) => (
					<div key={ cat.id } className="cd-cat-card">
						<div>
							<div className="cd-cat-card-header">
								<h4 className="cd-cat-card-title">{ cat.name }</h4>
								<span className="cd-cat-card-count">
									{ ( cat.docs && cat.docs.length ) || 0 } { __( 'docs', 'douxir' ) }
								</span>
							</div>
							<ul className="cd-cat-card-list">
								{ ! cat.docs || cat.docs.length === 0 ? (
									<li style={ { fontSize: '12px', color: '#94a3b8', fontStyle: 'italic', padding: '6px 0' } }>
										{ __( 'No documents created yet.', 'douxir' ) }
									</li>
								) : (
									cat.docs.slice( 0, 5 ).map( ( doc ) => (
										<li key={ doc.id } className="cd-cat-card-item" onClick={ () => onSelectDoc( doc.id ) }>
											<Icon name="file-text" size={ 13 } color="#6366f1" style={ { flexShrink: 0 } } />
											<span style={ { flex: 1, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' } }>
												{ doc.title }
											</span>
											{ doc.pro && (
												<span className="cd-pro-badge" style={ { fontSize: '9px', padding: '1px 4px' } }>
													{ __( 'PRO', 'douxir' ) }
												</span>
											) }
										</li>
									) )
								) }
							</ul>
						</div>
						<div className="douxir-cat-actions">
							{ cat.id > 0 && onDeleteCat ? (
								<button
									type="button"
									className="cd-btn cd-btn-danger-ghost cd-btn-sm"
									title={ __( 'Delete category', 'douxir' ) }
									aria-label={ `${ __( 'Delete category', 'douxir' ) } ${ cat.name }` }
									onClick={ () => onDeleteCat( cat ) }
								>
									<Icon name="trash" size={ 13 } />
									<span>{ __( 'Delete', 'douxir' ) }</span>
								</button>
							) : (
								<span />
							) }
							<div className="douxir-cat-actions-right">
								{ onManageCat ? (
									<button type="button" className="cd-btn cd-btn-ghost cd-btn-sm" onClick={ () => onManageCat( cat ) }>
										<Icon name="sliders" size={ 13 } />
										<span>{ __( 'Manage', 'douxir' ) }</span>
									</button>
								) : null }
								<button type="button" className="cd-btn cd-btn-ghost cd-btn-sm" onClick={ () => onNewDoc( cat.id ) }>
									<Icon name="plus" size={ 13 } />
									<span>{ __( 'Add Doc', 'douxir' ) }</span>
								</button>
							</div>
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
}
