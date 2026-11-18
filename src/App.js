import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { request } from './api';
import Header from './components/Header';
import Sidebar from './components/Sidebar';
import Overview from './components/Overview';
import DocView from './components/DocView';
import NewDocModal from './components/NewDocModal';
import NewCategoryModal from './components/NewCategoryModal';
import QuickEditModal from './components/QuickEditModal';
import SearchModal from './components/SearchModal';
import SettingsPage from './components/SettingsPage';
import ManageCategoryModal from './components/ManageCategoryModal';
import ConfirmDeleteCategory from './components/ConfirmDeleteCategory';

export default function App() {
	const settings = window.douxirAdminData || {};
	const [ data, setData ] = useState(
		() =>
			settings.initialData || {
				stats: {},
				categories: [],
				all_docs: [],
			}
	);
	const [ selectedDocId, setSelectedDocId ] = useState( null );
	const [ newDoc, setNewDoc ] = useState( { open: false, catId: 0, parentId: 0 } );
	const [ newCatOpen, setNewCatOpen ] = useState( false );
	const [ quickEdit, setQuickEdit ] = useState( { open: false, doc: null } );
	const [ searchOpen, setSearchOpen ] = useState( false );
	const [ toast, setToast ] = useState( null );
	const [ deleteCat, setDeleteCat ] = useState( null );
	const [ deleteBusy, setDeleteBusy ] = useState( false );
	const [ settingsOpen, setSettingsOpen ] = useState( () => settings.view === 'settings' || ( typeof window !== 'undefined' && window.location.hash === '#settings' ) );
	const [ manageCat, setManageCat ] = useState( null );

	const showToast = ( message ) => {
		setToast( message );
		setTimeout( () => setToast( null ), 3000 );
	};

	useEffect( () => {
		const onKey = ( event ) => {
			if ( ( event.ctrlKey || event.metaKey ) && event.key === 'k' ) {
				event.preventDefault();
				setSearchOpen( ( open ) => ! open );
			}
		};
		window.addEventListener( 'keydown', onKey );
		return () => window.removeEventListener( 'keydown', onKey );
	}, [] );

	const selectedDoc = useMemo( () => {
		if ( ! selectedDocId || ! data.all_docs ) {
			return null;
		}
		return data.all_docs.find( ( doc ) => doc.id === selectedDocId ) || null;
	}, [ selectedDocId, data.all_docs ] );

	const createDoc = async ( payload ) => {
		try {
			const response = await request( 'doc', { method: 'POST', data: payload } );
			if ( response && response.data ) {
				setData( response.data );
				if ( response.post_id ) {
					setSelectedDocId( response.post_id );
				}
				showToast( __( 'Document created successfully!', 'douxir' ) );
			}
		} catch ( error ) {
			// request() already shows an alert.
		}
	};

	const updateDoc = async ( payload ) => {
		try {
			const response = await request( 'quick-update', { method: 'POST', data: payload } );
			if ( response && response.data ) {
				setData( response.data );
				showToast( __( 'Document updated!', 'douxir' ) );
			}
		} catch ( error ) {
			// request() already shows an alert.
		}
	};

	const trashDoc = async ( id ) => {
		try {
			const response = await request( `doc/${ id }`, { method: 'DELETE' } );
			if ( response && response.data ) {
				setData( response.data );
				if ( selectedDocId === id ) {
					setSelectedDocId( null );
				}
				showToast( __( 'Document moved to trash.', 'douxir' ) );
			}
		} catch ( error ) {
			// request() already shows an alert.
		}
	};

	const createCategory = async ( payload ) => {
		try {
			const response = await request( 'category', { method: 'POST', data: payload } );
			if ( response && response.data ) {
				setData( response.data );
				showToast( __( 'Category created!', 'douxir' ) );
			}
		} catch ( error ) {
			// request() already shows an alert.
		}
	};

	const reorderDocs = async ( orders ) => {
		try {
			const response = await request( 'reorder', { method: 'POST', data: { orders } } );
			if ( response && response.data ) {
				setData( response.data );
				showToast( __( 'Document order updated!', 'douxir' ) );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( error );
		}
	};

	const reorderCategories = async ( orders ) => {
		try {
			const response = await request( 'reorder-categories', { method: 'POST', data: { orders } } );
			if ( response && response.data ) {
				setData( response.data );
				showToast( __( 'Categories reordered!', 'douxir' ) );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( error );
		}
	};

	const updateCategory = async ( payload ) => {
		try {
			const response = await request( `category/${ payload.id }/update`, { method: 'POST', data: payload } );
			if ( response && response.data ) {
				setData( response.data );
			}
			showToast( __( 'Category updated', 'douxir' ) );
		} catch ( error ) {
			// request() already surfaces the error.
		}
	};

	const confirmDeleteCategory = async ( cat ) => {
		setDeleteBusy( true );
		try {
			const response = await request( `category/${ cat.id }`, { method: 'DELETE' } );
			if ( response && response.data ) {
				setData( response.data );
				showToast( __( 'Category deleted. Its documents were kept.', 'douxir' ) );
			}
		} catch ( error ) {
			// request() already shows an alert.
		}
		setDeleteBusy( false );
		setDeleteCat( null );
	};

	return (
		<div className="cd-app">
			{ toast && (
				<div
					style={ {
						position: 'fixed',
						bottom: '24px',
						right: '24px',
						background: '#0f172a',
						color: '#ffffff',
						padding: '10px 18px',
						borderRadius: '8px',
						fontSize: '13px',
						fontWeight: 600,
						boxShadow: '0 8px 16px rgba(0,0,0,0.2)',
						zIndex: 999999,
						display: 'flex',
						alignItems: 'center',
						gap: '8px',
						animation: 'cdFadeIn 0.2s ease',
					} }
				>
					<span>{ toast }</span>
				</div>
			) }
			<Header
				onOpenNewDoc={ () => setNewDoc( { open: true, catId: 0, parentId: 0 } ) }
				onOpenNewCat={ () => setNewCatOpen( true ) }
				onOpenSearch={ () => setSearchOpen( true ) }
				onHomeClick={ () => {
					setSettingsOpen( false );
					setSelectedDocId( null );
					if ( window.location.hash === '#settings' ) {
						window.location.hash = '';
					}
				} }
			/>
			<div className="cd-body">
				{ settingsOpen && settings.canManageSettings ? (
					<main className="cd-main douxir-settings-main">
						<SettingsPage
							onClose={ () => {
								setSettingsOpen( false );
								if ( window.location.hash === '#settings' ) {
									window.location.hash = '';
								}
							} }
						/>
					</main>
				) : (
					<>
				<Sidebar
					categories={ data.categories || [] }
					allDocs={ data.all_docs || [] }
					selectedDocId={ selectedDocId }
					onSelectDoc={ ( id ) => setSelectedDocId( id ) }
					onHomeClick={ () => setSelectedDocId( null ) }
					onNewDocForCategory={ ( catId ) => setNewDoc( { open: true, catId, parentId: 0 } ) }
					onNewDocForParent={ ( parentId, catId ) => setNewDoc( { open: true, catId, parentId } ) }
					onReorderDocs={ reorderDocs }
					onReorderCategories={ reorderCategories }
				/>
				<main className="cd-content-area">
					{ selectedDoc ? (
						<DocView
							doc={ selectedDoc }
							allDocs={ data.all_docs || [] }
							categories={ data.categories || [] }
							onSelectDoc={ ( id ) => setSelectedDocId( id ) }
							onOpenQuickEdit={ ( current ) => setQuickEdit( { open: true, doc: current } ) }
							onTrashDoc={ trashDoc }
							onNewChildDoc={ ( parentId, catId ) => setNewDoc( { open: true, catId, parentId } ) }
							onSaveContent={ updateDoc }
							onReorderDocs={ reorderDocs }
						/>
					) : (
						<Overview
							stats={ data.stats || {} }
							categories={ data.categories || [] }
							allDocs={ data.all_docs || [] }
							onSelectDoc={ ( id ) => setSelectedDocId( id ) }
							onNewDoc={ ( catId = 0 ) => setNewDoc( { open: true, catId, parentId: 0 } ) }
							onNewCat={ () => setNewCatOpen( true ) }
							onDeleteCat={ settings.canManageCats ? ( cat ) => setDeleteCat( cat ) : null }
							onManageCat={ ( cat ) => setManageCat( cat ) }
						/>
					) }
				</main>
					</>
				) }
			</div>
			<NewDocModal
				isOpen={ newDoc.open }
				onClose={ () => setNewDoc( { open: false, catId: 0, parentId: 0 } ) }
				onSubmit={ createDoc }
				categories={ data.categories || [] }
				allDocs={ data.all_docs || [] }
				initialCatId={ newDoc.catId }
				initialParentId={ newDoc.parentId }
			/>
			<NewCategoryModal
				isOpen={ newCatOpen }
				onClose={ () => setNewCatOpen( false ) }
				onSubmit={ createCategory }
				categories={ data.categories || [] }
			/>
			<QuickEditModal
				isOpen={ quickEdit.open }
				onClose={ () => setQuickEdit( { open: false, doc: null } ) }
				doc={ quickEdit.doc }
				categories={ data.categories || [] }
				allDocs={ data.all_docs || [] }
				onSubmit={ updateDoc }
			/>
			{ deleteCat && (
				<ConfirmDeleteCategory
					cat={ deleteCat }
					busy={ deleteBusy }
					onCancel={ () => setDeleteCat( null ) }
					onConfirm={ () => confirmDeleteCategory( deleteCat ) }
				/>
			) }
			<SearchModal
				isOpen={ searchOpen }
				onClose={ () => setSearchOpen( false ) }
				allDocs={ data.all_docs || [] }
				categories={ data.categories || [] }
				onSelectDoc={ ( id ) => setSelectedDocId( id ) }
			/>
			<ManageCategoryModal
				isOpen={ !! manageCat }
				onClose={ () => setManageCat( null ) }
				category={ manageCat }
				allDocs={ data.all_docs || [] }
				categories={ data.categories || [] }
				onSaveCategory={ updateCategory }
				onReorderDocs={ reorderDocs }
			/>
		</div>
	);
}
