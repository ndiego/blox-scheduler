<?php
/**
 * Plugin Name: Blox - Scheduler Addon
 * Plugin URI:  https://www.bloxwp.com
 * Description: Enables the Scheduler Addon for Blox.
 * Author:      Nick Diego
 * Author URI:  http://www.outermostdesign.com
 * Version:     1.0.0
 * Text Domain: blox-scheduler
 * Domain Path: languages
 *
 * Blox is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Blox is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Blox. If not, see <http://www.gnu.org/licenses/>.
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


add_action( 'plugins_loaded', 'blox_load_scheduler_addon' );
/**
 * Load the class. Must be called after all plugins are loaded
 *
 * @since 1.0.0
 */
function blox_load_scheduler_addon() {

	// If Blox is not active or if the addon class already exists, bail...
	if ( ! class_exists( 'Blox_Main' ) || class_exists( 'Blox_Scheduler_Main' ) ) {
		return;
	}

	/**
	 * Main plugin class.
	 *
	 * @since 1.0.0
	 *
	 * @package Blox
	 * @author  Nick Diego
	 */
	class Blox_Scheduler_Main {

		/**
		 * Holds the class object.
		 *
		 * @since 1.0.0
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * Plugin version, used for cache-busting of style and script file references.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * The name of the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $plugin_name = 'Blox - Scheduler Addon';
		
		/**
		 * Unique plugin slug identifier.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $plugin_slug = 'blox-scheduler';

		/**
		 * Plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $file = __FILE__;

		/**
		 * Primary class constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// Load the plugin textdomain.
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			
			// Add additional links to the plugin's row on the admin plugin page
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

			// Initialize the addon license 
			add_action( 'init', array( $this, 'license_init' ) );
			
			// Add and save all scheduler settings
			add_action( 'blox_visibility_settings', array( $this, 'print_scheduler_settings' ), 10, 4 );
			add_filter( 'blox_save_visibility_settings', array( $this, 'save_scheduler_settings' ), 10, 4 );
			
			// Modify the frontend visibility test based on scheduler settings
			add_filter( 'blox_content_block_visibility_test', array( $this, 'run_scheduler' ), 10, 4 );
			
			// Add scheduler meta data to local and global blocks
			add_filter( 'blox_visibility_meta_data', array( $this, 'scheduler_meta_data' ), 10, 3 );

			// Add necessary scripts and styles 
			add_action( 'blox_metabox_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'blox_metabox_styles', array( $this, 'enqueue_styles' ) );
			
			// Let Blox know the addon is active
			add_filter( 'blox_active_addons', array( $this, 'notify_of_active_addon' ), 10 );
		}
		
		
		/**
		 * Loads the plugin textdomain for translation.
		 *
		 * @since 1.0.0
		 */
		public function load_textdomain() {
			load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
		
		
		/**
		 * Adds additional links to the plugin row meta links
		 *
		 * @since 1.0.0
		 *
		 * @param array $links   Already defined meta links
		 * @param string $file   Plugin file path and name being processed
		 *
		 * @return array $links  The new array of meta links
		 */
		function plugin_row_meta( $links, $file ) {

			// If we are not on the correct plugin, abort
			if ( $file != 'blox-scheduler/blox-scheduler.php' ) {
				return $links;
			}

			$docs_link = esc_url( add_query_arg( array(
					'utm_source'   => 'admin-plugins-page',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'BloxPluginsPage',
					'utm_content'  => 'plugin-page-link'
				), 'https://www.bloxwp.com/documentation/scheduler' )
			);

			$new_links = array(
				'<a href="' . $docs_link . '">' . esc_html__( 'Documentation', 'blox-scheduler' ) . '</a>',
			);

			$links = array_merge( $links, $new_links );

			return $links;
		}
		
		
		/**
		 * Load license settings
		 *
		 * @since 1.0.0
		 */
		public function license_init() {
			
			// Setup the license
			if ( class_exists( 'Blox_License' ) ) {
				$blox_scheduler_addon_license = new Blox_License( __FILE__, 'Scheduler Addon', '1.0.0', 'Nicholas Diego', 'blox_scheduler_addon_license_key', 'https://www.bloxwp.com', 'addons' );
			}
		}

		
		/**
		 * Print scheduler settings on visibility tab
		 *
		 * @since 1.0.0
		 *
		 * @param int $id             The id of the content block, either global or individual (attached to post/page/cpt) 
		 * @param string $name_prefix The prefix for saving each setting
		 * @param string $get_prefix  The prefix for retrieving each setting
		 * @param bool $global	      The block state
		 */
		public function print_scheduler_settings( $id, $name_prefix, $get_prefix, $global ) {
			?>
			
			<tr>
				<th scope="row"><?php _e( 'Enable Scheduler', 'blox-scheduler' ); ?></th>
				<td>					
					<label>
						<input type="checkbox" name="<?php echo $name_prefix; ?>[scheduler][enable]" value="1" <?php ! empty( $get_prefix['scheduler']['enable'] ) ? checked( $get_prefix['scheduler']['enable'] ) : ''; ?> >
						<?php echo __( 'Check to enable block scheduling', 'blox-scheduler' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Begin Date/Time', 'blox-scheduler' ); ?></th>
				<td>
					<input type="text" class="blox-half-text scheduler-date" name="<?php echo $name_prefix; ?>[scheduler][begin]" value="<?php echo ! empty( $get_prefix['scheduler']['begin'] ) ? esc_attr( $get_prefix['scheduler']['begin'] ) : ''; ?>" placeholder="<?php _e( 'Begin Now', 'blox-scheduler' );?>"/>
					<div class="blox-description">
						<?php echo sprintf( __( 'Enter the date and time you want the block to %1$sbegin%2$s showing. Leave blank to show now.', 'blox-scheduler' ), '<strong>', '</strong>' ); ?>
					</div>			
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'End Date/Time', 'blox-scheduler' ); ?></th>
				<td>
					<input type="text" class="blox-half-text scheduler-date" name="<?php echo $name_prefix; ?>[scheduler][end]" value="<?php echo ! empty( $get_prefix['scheduler']['end'] ) ? esc_attr( $get_prefix['scheduler']['end'] ) : ''; ?>" placeholder="<?php _e( 'Never End', 'blox-scheduler' );?>"/>
					<div class="blox-description">
						<?php echo sprintf( __( 'Enter the date and time you want the block to %1$sstop%2$s showing. Leave blank for never.', 'blox-scheduler' ), '<strong>', '</strong>' ); ?>
					</div>			
				</td>
			</tr>
			
			<?php
		}
		
		
		/**
		 * Print scheduler settings on visibility tab
		 *
		 * @since 1.0.0
		 * 
		 * @return array $settings    Array of all existing settings
		 * @param int $post_id        The global block id or the post/page/custom post-type id corresponding to the local block 
		 * @param string $name_prefix The prefix for saving each setting
		 * @param bool $global        The block state
		 *
		 * @return array $settings    Return an array of updated settings
		 */
		public function save_scheduler_settings( $settings, $post_id, $name_prefix, $global ) {
			
			$settings['scheduler']['enable'] = isset( $name_prefix['scheduler']['enable'] ) ? 1 : 0;
			$settings['scheduler']['begin']  = esc_attr( $name_prefix['scheduler']['begin'] );
			$settings['scheduler']['end']    = esc_attr( $name_prefix['scheduler']['end'] );

			return $settings;
		}


		/**
		 * Run the scheduler to see if the block should be shown
		 *
		 * @since 1.0.0
		 *
		 * @param bool $visibility_test The current status of the visibility test
		 * @param int $id       		The block id, if global, id = $post->ID otherwise it is a random local id
		 * @param array $block  		Contains all of our block settings data
		 * @param bool $global  		Tells whether our block is global or local
		 */ 
		function run_scheduler( $visibility_test, $id, $block, $global ) {
			
			$scheduler_enabled = ! empty( $block['visibility']['scheduler']['enable'] ) ? true : false;
			
			// If scheduling is enabled and the visibility test is already true, continue... 
			if ( $visibility_test == true ) {
				if ( $scheduler_enabled ) {
			
					/**
					* current_time() will return an incorrect date/time if the server or another script sets a non-UTC timezone
					* (e.g. if server timezone set to LA, current_time() will take another 8 hours off the already adjusted datetime)
					* Therefore we force UTC time, then get current_time()
					*/
					//$existing_timezone = date_default_timezone_get();
					//date_default_timezone_set('UTC');
	
					$current_time = current_time( 'timestamp' );
					$begin 		  = strtotime( $block['visibility']['scheduler']['begin'] );
					$end   	 	  = strtotime( $block['visibility']['scheduler']['end'] );
					
					// Put timezone back in case other scripts rely on it
					//date_default_timezone_set( $existing_timezone );

					if ( ( '' != $begin && $begin > $current_time ) || ( '' != $end && $end < $current_time ) ) {
						// The block should NOT be shown
						return false;
					} else {
						return $visibility_test;
					}
				} else {
					// The scheduler is not enabled so ignore...
					return $visibility_test;
				}
			}
		}
		
		
		/**
		 * Add scheduler meta data to both local and global blocks
		 *
		 * @since 1.0.0
		 *
		 * @param bool $visibility_test The current status of the visibility test
		 * @param array $block  		Contains all of our block settings data
		 * @param bool $global  		Tells whether our block is global or local
		 */ 
		public function scheduler_meta_data( $output, $block, $global ) {
		
			$scheduler_enabled = ! empty( $block['visibility']['scheduler']['enable'] ) ? true : false;
			$clock = '';
			$separator = $global ? ' &nbsp;–&nbsp; ' : ' &nbsp;&middot&nbsp; ';
			
			if ( $scheduler_enabled ) {
				
				$current_time = current_time( 'timestamp' );
				$begin 		  = strtotime( $block['visibility']['scheduler']['begin'] );
				$end   	 	  = strtotime( $block['visibility']['scheduler']['end'] );
				
				$begin_text = empty( $block['visibility']['scheduler']['begin'] ) ? 'Now' : $block['visibility']['scheduler']['begin'];
				$end_text = empty( $block['visibility']['scheduler']['end'] ) ? 'Never' : $block['visibility']['scheduler']['end'];

				if ( ( '' != $begin && $begin > $current_time ) || ( '' != $end && $end < $current_time ) ) {
					// The block should NOT currently being shown
					$clock = $separator . '<span class="dashicons dashicons-clock" style="color:#a00;cursor:help" title="Begin: ' . $begin_text . ' End: ' . $end_text . '"></span>';
				} else {
					$clock = $separator . '<span class="dashicons dashicons-clock" style="cursor:help" title="Begin: ' . $begin_text . ' End: ' . $end_text . '"></span>';
				}
			}
			
			$output = $output . $clock;
			
			return $output;
		}


		/**
		 * Enqueue all necessary scripts
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {
			
			wp_register_script( 'timepicker-scripts', plugin_dir_url( __FILE__ ) . 'assets/js/jquery-ui-timepicker-addon.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-slider', 'jquery-ui-datepicker' ) );
       		
   		    wp_register_script( 'scheduler-scripts', plugin_dir_url( __FILE__ ) . 'assets/js/scheduler.js', array( 'timepicker-scripts' ) );
       		wp_enqueue_script( 'scheduler-scripts' );
		}
		
		
		/* Enqueue all necessary styles
		 *
		 * @since 1.0.0
		 */
		public function enqueue_styles() {
	  		wp_register_style( 'jquery-ui-styles', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css' );
		  	wp_register_style( 'jquery-ui-fresh-theme', plugin_dir_url( __FILE__ ) . 'assets/css/jquery-ui-fresh.min.css' );
			wp_register_style( 'jquery-timepicker-styles', plugin_dir_url( __FILE__ ) . 'assets/css/jquery-ui-timepicker-addon.min.css' );
			wp_register_style( 'scheduler-styles', plugin_dir_url( __FILE__ ) . 'assets/css/scheduler.css', array( 'jquery-ui-styles', 'jquery-ui-fresh-theme', 'jquery-timepicker-styles' ) );
       		wp_enqueue_style( 'scheduler-styles' );
		}

	
	
		/**
		 * Let Blox know this addon has been activated.
		 *
		 * @since 1.0.0
		 */
		public function notify_of_active_addon( $addons ) {

			$addons['blox-scheduler'] = __( 'Blox Scheduler Addon', 'blox-scheduler' );
			return $addons;
		}


		/**
		 * Returns the singleton instance of the class.
		 *
		 * @since 1.0.0
		 *
		 * @return object The class object.
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Blox_Scheduler_Main ) ) {
				self::$instance = new Blox_Scheduler_Main();
			}

			return self::$instance;
		}
	}

	// Load the main plugin class.
	$blox_scheduler_main = Blox_Scheduler_Main::get_instance();
}