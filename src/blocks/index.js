import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, RangeControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

function BlockPreview( { title, description } ) {
	return (
		<div className="douxir-block-preview">
			<strong>{ title }</strong>
			<p>{ description }</p>
		</div>
	);
}

registerBlockType( 'douxir/docs-list', {
	apiVersion: 3,
	title: __( 'Douxir Documents', 'douxir' ),
	description: __( 'A list or grid of documentation articles.', 'douxir' ),
	category: 'widgets',
	icon: 'media-document',
	attributes: {
		category: { type: 'string', default: '' },
		layout: { type: 'string', default: 'list' },
		columns: { type: 'number', default: 3 },
		limit: { type: 'number', default: 10 },
		showSearch: { type: 'boolean', default: false },
	},
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( { className: 'douxir-block-editor' } );
		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody title={ __( 'Documents', 'douxir' ) } initialOpen>
						<TextControl
							label={ __( 'Category slug or ID', 'douxir' ) }
							value={ attributes.category }
							onChange={ ( category ) => setAttributes( { category } ) }
							help={ __( 'Leave empty to include every category.', 'douxir' ) }
						/>
						<SelectControl
							label={ __( 'Layout', 'douxir' ) }
							value={ attributes.layout }
							options={ [
								{ label: __( 'List', 'douxir' ), value: 'list' },
								{ label: __( 'Grid', 'douxir' ), value: 'grid' },
								{ label: __( 'Sidebar navigation', 'douxir' ), value: 'nav' },
							] }
							onChange={ ( layout ) => setAttributes( { layout } ) }
						/>
						{ 'grid' === attributes.layout ? (
							<RangeControl
								label={ __( 'Columns', 'douxir' ) }
								value={ attributes.columns }
								onChange={ ( columns ) => setAttributes( { columns } ) }
								min={ 1 }
								max={ 4 }
							/>
						) : null }
						<RangeControl
							label={ __( 'Maximum documents', 'douxir' ) }
							value={ attributes.limit }
							onChange={ ( limit ) => setAttributes( { limit } ) }
							min={ 1 }
							max={ 50 }
						/>
						<ToggleControl
							label={ __( 'Show search box', 'douxir' ) }
							checked={ attributes.showSearch }
							onChange={ ( showSearch ) => setAttributes( { showSearch } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender
					block="douxir/docs-list"
					attributes={ attributes }
					EmptyResponsePlaceholder={ () => (
						<BlockPreview
							title={ __( 'Douxir Documents', 'douxir' ) }
							description={ __( 'The document list will appear here on the front end.', 'douxir' ) }
						/>
					) }
				/>
			</div>
		);
	},
	save() {
		return null;
	},
} );

registerBlockType( 'douxir/search', {
	apiVersion: 3,
	title: __( 'Douxir Search', 'douxir' ),
	description: __( 'A live search box for documentation.', 'douxir' ),
	category: 'widgets',
	icon: 'search',
	attributes: {
		placeholder: { type: 'string', default: '' },
	},
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( { className: 'douxir-block-editor' } );
		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody title={ __( 'Search', 'douxir' ) } initialOpen>
						<TextControl
							label={ __( 'Placeholder', 'douxir' ) }
							value={ attributes.placeholder }
							onChange={ ( placeholder ) => setAttributes( { placeholder } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender block="douxir/search" attributes={ attributes } />
			</div>
		);
	},
	save() {
		return null;
	},
} );

registerBlockType( 'douxir/categories', {
	apiVersion: 3,
	title: __( 'Douxir Categories', 'douxir' ),
	description: __( 'A grid or list of documentation categories.', 'douxir' ),
	category: 'widgets',
	icon: 'category',
	attributes: {
		layout: { type: 'string', default: 'grid' },
		columns: { type: 'number', default: 3 },
	},
	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( { className: 'douxir-block-editor' } );
		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody title={ __( 'Categories', 'douxir' ) } initialOpen>
						<SelectControl
							label={ __( 'Layout', 'douxir' ) }
							value={ attributes.layout }
							options={ [
								{ label: __( 'Grid', 'douxir' ), value: 'grid' },
								{ label: __( 'List', 'douxir' ), value: 'list' },
							] }
							onChange={ ( layout ) => setAttributes( { layout } ) }
						/>
						<RangeControl
							label={ __( 'Columns', 'douxir' ) }
							value={ attributes.columns }
							onChange={ ( columns ) => setAttributes( { columns } ) }
							min={ 1 }
							max={ 4 }
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender
					block="douxir/categories"
					attributes={ attributes }
					EmptyResponsePlaceholder={ () => (
						<BlockPreview
							title={ __( 'Douxir Categories', 'douxir' ) }
							description={ __( 'Category cards will appear here on the front end.', 'douxir' ) }
						/>
					) }
				/>
			</div>
		);
	},
	save() {
		return null;
	},
} );
