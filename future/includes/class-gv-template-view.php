<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Load up the Gamajo Template Loader.
 *
 * @see https://github.com/GaryJones/Gamajo-Template-Loader
 */
if ( ! class_exists( 'Gamajo_Template_Loader' ) ) {
	require gravityview()->plugin->dir( 'future/lib/class-gamajo-template-loader.php' );
}

/**
 * The View Template class .
 *
 * Attached to a \GV\View and used by a \GV\View_Renderer.
 */
abstract class View_Template extends Template {

	/**
	 * @var \GV\View The view connected to this template.
	 */
	public $view;

	/**
	 * @var string The template slug to be loaded (like "table", "list")
	 */
	public static $slug;

	/**
	 * Initializer.
	 *
	 * @param \GV\View $view The View connected to this template.
	 */
	public function __construct( View $view ) {
		$this->view = $view;

		/** Add granular overrides. */
		add_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ), 10, 3 );

		parent::__construct();
	}

	/**
	 * Enable granular template overrides based on current post, view, form, etc.
	 *
	 * The loading order is:
	 *
	 * - post-[ID of post or page where view is embedded]-[View ID]-table-footer.php
	 * - post-[ID of post or page where view is embedded]-table-footer.php
	 * - view-[View ID]-table-footer.php
	 * - form-[Form ID]-table-footer.php
	 * - table-footer.php
	 *
	 * @see  Gamajo_Template_Loader::get_template_file_names() Where the filter is
	 * @param array $templates Existing list of templates.
	 * @param string $slug      Name of the template base, example: `table`, `list`, `datatables`, `map`
	 * @param string $name      Name of the template part, example: `body`, `footer`, `head`, `single`
	 *
	 * @return array $templates Modified template array, merged with existing $templates values
	 */
	public function add_id_specific_templates( $templates, $slug, $name ) {

		$specifics = array();

		list( $slug_dir, $slug_name ) = self::split_slug( $slug, $name );

		global $post;

		if ( ! gravityview()->request->is_view() && $post ) {
			$specifics []= sprintf( '%spost-%d-view-%d-%s', $slug_dir, $post->ID, $this->view->ID, $slug_name );
			$specifics []= sprintf( '%spost-%d-%s', $slug_dir, $post->ID, $slug_name );
		}

		
		$specifics []= sprintf( '%sview-%d-%s', $slug_dir, $this->view->ID, $slug_name );
		$specifics []= sprintf( '%sform-%d-%s', $slug_dir, $this->view->form->ID, $slug_name );

		return array_merge( $specifics, $templates );
	}

	/**
	 * Output some HTML.
	 *
	 * @return void
	 */
	public function render() {

		/**
		 * Make various pieces of data available to the template
		 *  under the $gravityview scoped variable.
		 *
		 * @filter `gravityview/template/view/data`
		 * @param array $data The default data available to all View templates.
		 * @since future
		 */
		$this->set_template_data( apply_filters( 'gravityview/template/view/data', array(

			'template' => $this,

			/** Shortcuts */
			'view' => $this->view,
			'fields' => $this->view->fields->by_visible(),
			'entries' => $this->view->entries,

		) ), 'gravityview' );

		/** Load the template. */
		$this->get_template_part( static::$slug );
	}
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-template-view-table.php' );