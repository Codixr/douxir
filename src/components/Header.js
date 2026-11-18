import { __ } from '@wordpress/i18n';
import Icon from '../icons';

export default function Header( { onOpenNewDoc, onOpenNewCat, onOpenSearch, onHomeClick } ) {
	const logoUrl = ( window.douxirAdminData && window.douxirAdminData.logoUrl ) || '';

	return (
		<header className="cd-header">
			<div className="cd-header-brand" style={ { cursor: 'pointer' } } onClick={ onHomeClick }>
				<div className={ logoUrl ? 'cd-brand-logo douxir-brand-logo' : 'cd-brand-logo' }>
					{ logoUrl ? (
						<img src={ logoUrl } alt="" width={ 34 } height={ 34 } />
					) : (
						<Icon name="book-open" size={ 19 } />
					) }
				</div>
				<div>
					<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
						<h1 className="cd-brand-title">{ __( 'Douxir', 'douxir' ) }</h1>
					</div>
				</div>
			</div>
			<div className="cd-header-center">
				<button type="button" className="cd-global-search-btn" onClick={ onOpenSearch }>
					<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
						<Icon name="search" size={ 15 } />
						<span>{ __( 'Search docs, categories, or keywords...', 'douxir' ) }</span>
					</div>
					<span className="cd-kbd">{ __( 'Ctrl K', 'douxir' ) }</span>
				</button>
			</div>
			<div className="cd-header-actions">
				<button type="button" className="cd-btn cd-btn-primary" onClick={ () => onOpenNewDoc() }>
					<Icon name="plus" size={ 16 } />
					<span>{ __( 'New Doc', 'douxir' ) }</span>
				</button>
				<button type="button" className="cd-btn cd-btn-outline" onClick={ () => onOpenNewCat() }>
					<Icon name="folder-plus" size={ 15 } />
					<span>{ __( 'New Category', 'douxir' ) }</span>
				</button>
			</div>
		</header>
	);
}
