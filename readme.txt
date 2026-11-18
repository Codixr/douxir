=== Douxir ===
Contributors: codixr
Tags: documentation, knowledge base, docs, help center, table of contents
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build a searchable documentation hub with nested documents, categories, a table of contents and a drag-and-drop admin screen.

== Description ==

Douxir adds a documentation section to your site. Write documents in the block editor, group them into categories, nest them under parent documents and arrange everything on the Douxir dashboard, a single admin screen for your whole knowledge base.

**For readers**

* Clean document layout with a sidebar, the article and an "On this page" table of contents.
* Live search box that suggests matching documents as you type.
* Breadcrumbs, reading time, last updated date, previous/next links, related documents and a print button.
* Optional "Was this document helpful?" feedback on every article.
* Collapsible sidebar groups, category icons and a mobile-friendly menu.
* A documentation homepage and category archives, with pretty URLs such as /documentation/getting-started/installation/.

**For editors**

* Douxir dashboard: browse categories and documents, create and quick-edit documents, change status, parent and order, and preview content.
* A Settings screen to control the docs slug, archive and single-document layout, search, permalinks and appearance.
* Front-end colors, typography, container width, card style and custom CSS, applied with CSS variables. A live preview is shown while you edit.
* Create and delete categories without leaving the dashboard. Deleting a category never deletes its documents. Categories can have icons and descriptions.
* Drag and drop to reorder documents and categories, plus a numeric display-order field when you add a document or manage a category.
* Optional "pro" badge for documents that cover premium features.
* Import and export your knowledge base as a JSON file.
* Works with the block editor and the REST API.

**Shortcodes and blocks**

* `[documentation_list]` or `[douxir_list]` shows the sidebar navigation with search. Attributes: `category`, `layout` (nav, list or grid), `columns`, `limit`, `show_search`, `parent`.
* `[documentation_content]` or `[douxir_content]` shows the current document, including the layout options from Settings.
* `[doc_categories_list]` or `[douxir_categories]` shows categories. Attributes: `layout`, `columns`.
* `[douxir_search]` shows a live search box. Attribute: `placeholder`.
* Matching Gutenberg blocks: Douxir Documents, Douxir Search and Douxir Categories.

Douxir is built and maintained by [Codixr](https://codixr.com), a software agency.

= Privacy and external requests =

Douxir does not send data to Codixr or to any other service and does not load files from external servers. The search box sends the visitor's search text to your own site through admin-ajax.php.

= Upgrading from Custom Documentation =

Douxir is the renamed successor of the "Custom Documentation" plugin. Documents, categories, shortcodes and URLs keep working: the post type (`custom_docs`), taxonomy (`doc_category`), "pro" meta key and shortcode names are unchanged. The saved category order is copied to a new Douxir option the first time an administrator opens the dashboard, and the old option is left in place. Deactivate the old plugin before activating Douxir.

== Installation ==

1. In your WordPress admin, go to Plugins > Add New and search for "Douxir", or upload the plugin ZIP from Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Open Douxir in the admin menu to create your first category and document.
4. Visit any document on the front end to see the documentation layout.

== Frequently Asked Questions ==

= Where do documents appear on my site? =

Each document has its own URL under /documentation/. If links return a 404 error, go to Settings > Permalinks and click Save Changes to refresh your permalinks.

= Can I use my theme's template instead? =

Douxir uses its own template for single documents, category archives and the documentation homepage so the sidebar and table of contents always work. You can also place the shortcodes or blocks on any page, and choose a custom documentation homepage in Settings.

= Can readers customize the look without code? =

Editors with the "manage options" capability can change colors, fonts, layout and add custom CSS from the Douxir Settings screen. The styles are output as CSS variables. Douxir does not load fonts or files from other websites.

= Who can manage documents? =

Users who can edit posts can open the Douxir dashboard. Publishing, editing and deleting a document follow normal WordPress permissions, and managing categories requires the "manage categories" capability.

= What happens to documents when I delete a category? =

They are kept. Deleting a category only removes the category itself. Documents that were in it stay published (or in whatever status they had) and move to "Uncategorized" unless they also belong to another category. Child documents stay under their parent document, and subcategories move up one level. You can assign the documents to another category later with Quick Edit.

= Does it work with Advanced Custom Fields? =

Yes. If ACF is active, Douxir also reads and writes the `documentation_pro` field through ACF. ACF is not required.

= Where can I get help? =

Email support@codixr.com or visit https://codixr.com.

== Screenshots ==

1. Douxir dashboard: categories, nested documents and stats in one screen.
2. Settings: layout, colors and a live appearance preview.
3. Create a new document, including a display-order field.
4. Front end: document layout with sidebar, search, reading time and table of contents.
5. Manage a category: drag-and-drop and numeric document order.

== Changelog ==

= 1.0.0 =
* Initial release.

== Development ==

The Douxir dashboard is built with [@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/). It uses the React copy that ships with WordPress (`wp-element`) instead of bundling its own. The readable source is in the `src/` folder.

To rebuild the admin script:

1. Install Node.js 18 or later.
2. From this plugin directory, run `npm install` and then `npm run build`.
3. This writes `assets/dist/admin-app.js` and `assets/dist/admin-app.asset.php`.
