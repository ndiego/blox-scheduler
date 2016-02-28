<?php
/**
 * Creates the widgets section within the content tab and loads in all available options
 *
 * @since 1.0.0
 *
 * @package Blox
 * @author  Nicholas Diego
 */
class Blox_Content_Widgets {

	/**
	 * Holds the class object.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public static $instance;


	/**
	 * Path to the file.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $file = __FILE__;


	/**
	 * Holds the base class object.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public $base;


	/**
	 * Stores the current block id.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public $block_id_master;


	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Load the base class object.
		$this->base = Blox_Main::get_instance();

		add_filter( 'blox_content_type', array( $this, 'add_widgets_content' ), 20 );
		add_action( 'blox_get_content_widgets', array( $this, 'get_widgets_content' ), 10, 4 );
		add_filter( 'blox_save_content_widgets', array( $this, 'save_widgets_content' ), 10, 3 );
		add_action( 'blox_print_content_widgets', array( $this, 'print_widgets_content' ), 10, 4 );
	
		// Use builtin Genesis function to register widget area
		genesis_register_widget_area(
			array(
				'id'            => 'blox-widgets',
				'name'          => __( 'Blox Widgets', 'blox' ),
				'description'   => __( 'Place all widgets you would like to make available to Blox here. You can then toggle which ones you would like to use within Blox.', 'blox' )
			)
		);
	
	}


	/* Enabled the "custom" content (i.e. WP Editor) option in the plugin
	 *
	 * @since 1.0.0
	 *
	 * @param array $content_types  An array of the content types available
	 */
	public function add_widgets_content( $content_types ) {
		$content_types['widgets'] = 'Widgets';
		return $content_types;
	}


	/* Prints all of the editor ralated settings fields
	 *
	 * @since 1.0.0
	 *
	 * @param int $id             The block id
	 * @param string $name_prefix The prefix for saving each setting
	 * @param string $get_prefix  The prefix for retrieving each setting
	 * @param bool $global        The block state
	 */
	public function get_widgets_content( $id, $name_prefix, $get_prefix, $global ) {
	
		global $wp_registered_widgets;
		?>

		<!-- Wordpress Editor Settings -->
		<table class="form-table blox-content-widgets blox-hidden">
			<tbody>
				<tr class="blox-content-title"><th scope="row"><?php _e( 'Widgets Settings', 'blox' ); ?></th><td><hr></td></tr>
				<tr>
					<th scope="row"><?php _e( 'Available Widgets', 'blox' ); ?></th>
					<td>
						<?php 
					
						$sidebar_id       = 'blox-widgets';
						$sidebars_widgets = wp_get_sidebars_widgets();
					
						if ( ! empty ( $sidebars_widgets[$sidebar_id] ) ) {
					
						?>
						<div class="blox-checkbox-container">
							<ul class="blox-columns">
							<?php 
					
								foreach ( (array) $sidebars_widgets[$sidebar_id] as $widget_id ) {
								
									// Make sure out widget is in the registered widgets array
									if ( ! isset( $wp_registered_widgets[$widget_id] ) ) continue;
									?>
									<li>
										<label>
								
										<input type="checkbox" name="<?php echo $name_prefix; ?>[widgets][selection][]" value="<?php echo $widget_id; ?>" <?php echo ! empty( $get_prefix['widgets']['selection'] ) && in_array( $widget_id, $get_prefix['widgets']['selection'] ) ? 'checked="checked"' : ''; ?> /> <?php echo $wp_registered_widgets[$widget_id]['name']; ?>
										<?php							
										if ( isset( $wp_registered_widgets[$widget_id]['params'][0]['number'] ) ) {
						
											// Retrieve optional set title if the widget has one (code thanks to qurl: Dynamic Widgets)
											$number      = $wp_registered_widgets[$widget_id]['params'][0]['number'];
											$option_name = $wp_registered_widgets[$widget_id]['callback'][0]->option_name;
											$option      = get_option( $option_name );
						
											// if a title was found print it
											if ( ! empty( $option[$number]['title'] ) ) {
												echo ': <span class="in-widget-title">' . $option[$number]['title'] . '</span>';
											}
										}
										?>
										</label>
									</li>
								<?php } ?>
							</ul>
						</div>
						<div class="blox-checkbox-select-tools">
							<a class="blox-checkbox-select-all" href="#"><?php _e( 'Select All' ); ?></a> <a class="blox-checkbox-select-none" href="#"><?php _e( 'Unselect All' ); ?></a>
						</div>
						<?php } ?>
					</td>
				</tr>
			</tbody>
		</table>

		<?php
	}


	/* Saves all of the editor ralated settings
	 *
	 * @since 1.0.0
	 *
	 * @param string $name_prefix The prefix for saving each setting (this brings ...['editor'] with it)
	 * @param int $id             The block id
	 * @param bool $global        The block state
	 */
	public function save_widgets_content( $name_prefix, $id, $global ) {

		$settings = array();

		$settings['selection'] = isset( $name_prefix['selection'] ) ? array_map( 'esc_attr', $name_prefix['selection'] ) : '';

		return $settings;
	}


	/* Prints the editor content to the frontend
	 *
	 * @since 1.0.0
	 *
	 * @param array $content_data Array of all content data
	 * @param int $id             The block id
	 * @param array $block        NEED DESCRIPTION
	 * @param string $global      The block state
	 */
	public function print_widgets_content( $content_data, $block_id, $block, $global ) {
	
		$this->block_id_master = $block_id;
			
		// Check to see if the Blox Widgets area has widgets. If not, do nothing.
		if ( ! is_active_sidebar( 'blox-widgets' ) ) {
			return;
		}
	
		// Empty array of additional CSS classes
		$classes = array();
	
		// Empty array of blox widget area args
		$args = array();
	
		$defaults = apply_filters( 'blox_widget_area_defaults', array(
			'before'              => genesis_html5() ? '<aside class="blox-widgets widget-area ' . implode( ' ', apply_filters( 'blox_content_widgets_classes', $classes ) ) . '">' . genesis_sidebar_title( 'blox-widgets' ) : '<div class="widget-area">',
			'after'               => genesis_html5() ? '</aside>' : '</div>',
			'before_sidebar_hook' => 'blox_before_widget_area',
			'after_sidebar_hook'  => 'blox_after_widget_area',
		), 'blox-widgets', $args );
	
		// Merge our defaults and any "custom" args
		$args = wp_parse_args( $args, $defaults );
	
		// Opening widget area markup
		echo $args['before'];
	
		// Before widget area hook
		if ( $args['before_sidebar_hook'] ) {
			do_action( $args['before_sidebar_hook'] );
		}
	
		if ( ! empty( $content_data['widgets']['selection'] ) ) {

			// We need to outout buffer the widget contents
			ob_start();
			call_user_func( array( $this, 'blox_display_widgets' ), 'blox-widgets', $content_data, $block_id, $block, $global );
			$all_widgets = ob_get_clean();
		
			echo ( $all_widgets );

		} else {
			_e( 'You forgot to select some widgets to display!', 'blox' );
		}
	
		// After widget area hook
		if ( $args['after_sidebar_hook'] ) {
			do_action( $args['after_sidebar_hook'] );
		}
	
		// Closing widget area markup
		echo $args['after'];

	}



	public function blox_display_widgets( $index, $content_data, $block_id, $block, $global ) {
	
		global $wp_registered_sidebars, $wp_registered_widgets;
	
		$widget_prefix    = 'blox_' . $block_id . '_';
		$sidebar 		  = $wp_registered_sidebars[$index];
		$sidebars_widgets = wp_get_sidebars_widgets();
	
		// Bail early if "blox-widgets" does not exist or if we have no widgets in the widget area
		if ( empty( $sidebar ) || empty( $sidebars_widgets[ $index ] ) || ! is_array( $sidebars_widgets[ $index ] ) ) {
			return;
		}

		// Loop through all the widgets in the Blox Widgets sidebar and determine whether to show or not
		foreach ( (array) $sidebars_widgets[$index] as $id ) {
		
			// If the widget is not in the registered widgets array, bail...
			if ( !isset( $wp_registered_widgets[$id] ) ) continue;
		
			// If the widget is not in our "selected" widgets array, bail...
			if ( ! in_array( $id, $content_data['widgets']['selection'] ) ) continue;
		
			// Build our array of widget parameters 
			$params = array_merge(
				array( array_merge( $sidebar, array( 'widget_id' => $id, 'widget_name' => $wp_registered_widgets[$id]['name'] ) ) ),
				(array) $wp_registered_widgets[$id]['params']
			);

			// Substitute HTML id (with "blox_[id]_" prefix) and class attributes into before_widget
			$classname_ = '';
			foreach ( (array) $wp_registered_widgets[$id]['classname'] as $cn ) {
				if ( is_string( $cn ) ) {
					$classname_ .= '_' . $cn;
				} else if ( is_object( $cn ) ) {
					$classname_ .= '_' . get_class( $cn );
				}
			}
			$classname_ = ltrim( $classname_, '_' );
			$params[0]['before_widget'] = sprintf( $params[0]['before_widget'], $widget_prefix . $id, $classname_ );


			/**
			 * Filter the parameters passed to a widget's display callback.
			 *
			 * @since 1.0.0
			 *
			 * @param array $params {
			 *     @type array $args  {
			 *         @type string $name          Name of the sidebar the widget is assigned to.
			 *         @type string $id            ID of the sidebar the widget is assigned to.
			 *         @type string $description   The sidebar description.
			 *         @type string $class         CSS class applied to the sidebar container.
			 *         @type string $before_widget HTML markup to prepend to each widget in the sidebar.
			 *         @type string $after_widget  HTML markup to append to each widget in the sidebar.
			 *         @type string $before_title  HTML markup to prepend to the widget title when displayed.
			 *         @type string $after_title   HTML markup to append to the widget title when displayed.
			 *         @type string $widget_id     ID of the widget.
			 *         @type string $widget_name   Name of the widget.
			 *     }
			 *     @type array $widget_args {
			 *         An array of multi-widget arguments.
			 *
			 *         @type int $number Number increment used for multiples of the same widget.
			 *     }
			 * }
			 */
			$params = apply_filters( 'blox_widget_area_params', $params );

			// Make sure the widget callback function exists, then call it
			if ( is_callable( $wp_registered_widgets[$id]['callback'] ) ) {
				call_user_func_array( $wp_registered_widgets[$id]['callback'], $params );
			}
		}
	}

	
	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @return object The Blox_Content_Widgets object.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Blox_Content_Widgets ) ) {
			self::$instance = new Blox_Content_Widgets();
		}

		return self::$instance;
	}
}

// Load the editor content class.
$blox_content_widgets = Blox_Content_Widgets::get_instance();
