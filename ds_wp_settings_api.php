<?php
// GitHub: https://github.com/srtalley/dustysun-wp-settings-api
// Version 2.0.7
// Author: Steve Talley
// Organization: Dusty Sun
// Author URL: https://dustysun.com/

// Libraries Included
// https://github.com/farbelous/fontawesome-iconpicker

// Include the admin panel page
// https://github.com/kmhcreative/icon-picker

namespace DustySun\WP_Settings_API\v2;
/* To use this library, create a new class object and pass the complete path and name of a JSON file, or place a JSON file named ds_wp_settings_api.json in the same directory as this file.

You should also have a views directory in the same directory as this file, and any PHP files that have the same name as the section will be displayed on those tabs.

The main info shown above the tab but below the title can be defined with a file named 
"main_settings.php" while the rest of the files are named after the id of the about_sections or options sections.

Example instatiation:
$ds_api_settings = array(
	'json_file' => plugin_dir_path( __FILE__ ) . '/my-plugin-settings.json',
	'register_settings' => true,
	'views_dir' => plugin_dir_path( __FILE__ ) . '/admin/views'
);
//Create the settings object
$ds_settings_page = new \Dusty_Sun\WP_Settings_API\v1\DustySun_WP_Settings_API($ds_api_settings);

*/

if(!class_exists('DustySun\WP_Settings_API\v2\SettingsBuilder'))  { class SettingsBuilder {
	
	private $ds_wp_api_settings_init_data;

	private $ds_wp_settings_json;

	private $ds_wp_settings_api_full_config = array();

	private $main_settings = array();

	private $ds_wp_settings_option_fields = array();

	private $current_settings = array();

	private $ds_wp_settings_api_about_sections = array();

	private $ds_wp_settings_api_messages = array();

	// Create the object
	public function __construct($data = null) {
		// Read the settings init data
		$this->set_ds_wp_api_settings_init_data($data);

		// Read the JSON file with the settings that makes everything work
		$this->read_json_file($this->ds_wp_api_settings_init_data['json_file']);
		
		// Set item defaults
		$this->set_main_settings();

		// Register the settings if true - if we're building an options page
		if ($this->ds_wp_api_settings_init_data['register_settings']) add_action( 'admin_init', array( $this, 'build_settings' ) );

		add_action('wp_ajax_ds_wp_api_reset_settings-' . $this->main_settings['item_slug'], array($this, 'ds_wp_api_reset_settings'));

	} // end public function __construct()

	public function set_ds_wp_api_settings_init_data($data) {
		$json_file = isset($data['json_file']) && !empty($data['json_file']) ? $data['json_file'] : null;
		$register_settings = isset($data['register_settings']) && !empty($data['register_settings']) ? $data['register_settings'] : false;
		$views_dir = isset($data['views_dir']) && !empty($data['views_dir']) ? $data['views_dir'] : dirname(__FILE__) . '/views';

		$this->ds_wp_api_settings_init_data = array(
			'json_file' => $json_file,
			'register_settings' => $register_settings,
			'views_dir' => $views_dir
		);
	} //end read_ds_wp_api_settings_init_data
	public function read_json_file($file_name){

		if(!$file_name) {
			//see if there's a json file in the same directory with the same name as this file - only works with plugins 
			$file_name = plugin_dir_path(__FILE__) . basename(__FILE__, '.php') . '.json';
		} // end if(!$file_name)

		//make sure the path we were passed exists
		if(file_exists($file_name)) {

			ob_start();
			include( $file_name );
			$this->ds_wp_settings_api_full_config = json_decode(ob_get_clean(), true);

			// see if there are options fields 
			if(isset($this->ds_wp_settings_api_full_config['options']) && $this->ds_wp_settings_api_full_config['options'] != '') {
				$this->ds_wp_settings_option_fields = $this->ds_wp_settings_api_full_config['options'];
			// } if(isset($this->ds_wp_settings_api_full_config['sections']) && $this->ds_wp_settings_api_full_config['sections'] != '') {
			// 	foreach($this->ds_wp_settings_api_full_config['sections'] as $section_key => $section_item) {
			// 		if($section_item['type'] == 'option') {
			// 			$this->ds_wp_settings_option_fields[$section_key] = $section_item;
			// 		}
			// 	}
			} else {
				$this->ds_wp_settings_option_fields = false;
			} //end if

		} else {
			wp_die('<h2>' . basename(__FILE__) . ' ERROR:</h2> <p>No JSON file was passed when initializing the settings class. Either pass a file name when constructing it or place a file with the same name as this class but with .json at the end in order to successfully initialize this class.</p> <p><strong>' . dirname(__FILE__) . '</strong></p>');
		}//end if(file_exists($file_path))

	} // end function read_json_file

	public function set_main_settings($update_db = false, $reset_defaults = false) {

		// if $update_db is true the main settings key can optionally be stored in
		// the database. This is useful for recording this info on plugin or theme 
		// activation. The $reset_defaults flag will clear any existing values from 
		// the database.

		$this->main_settings = $this->ds_wp_settings_api_full_config['main_settings'];

		// set default options which can be overridden by the JSON file
		$ds_default_main_settings = array(
			'text_domain' => 'ds_wp_settings_api',
			'tabs' => 'true',
			'options_suffix' => '',
			'page_suffix' => '_page',
			'author' => '',
			'author_uri' => '',
			'item_name' => 'Untitled',
			'item_uri' => '',
			'page_hook' => '',
			'page_slug' => '',
			'item_slug' => '',
			'support_uri' => '',
			'support_email' => '',
			'version' => '',
		);

		// Go through the options that were set in the JSON config file and overwrite
		// any of the defaults above if so. Then set the main_settings variable
		foreach($ds_default_main_settings as $ds_default_main_settings_key => $ds_default_main_setting) {
			if(!isset($this->main_settings[$ds_default_main_settings_key]) || $this->main_settings[$ds_default_main_settings_key] == '') {
				$this->main_settings[$ds_default_main_settings_key] = $ds_default_main_setting;
			} // end if
		} // end foreach

		// set the item_slug key to the page_slug key if item_slug is not set
		if($this->main_settings['item_slug'] == '') {
			$this->main_settings['item_slug'] = $this->main_settings['page_slug'];
		} // end if($this->main_settings['item_slug'] == '')

		// now check for an existing unique ID which we store in a separate key
		$ds_main_settings_unique_id_key = $this->main_settings['text_domain'] . '_uid';

		// see if the option is set. If not, return blank
		$this->main_settings['unique_id'] = get_option($ds_main_settings_unique_id_key, '');

		// update the current settings
		$this->current_settings = $this->set_current_settings();

		// only do the following as admin, such as adding views that don't need to be done
		// every time the site is accessed by a regular user
		if(is_admin()) {

			// Get the views
			$this->ds_wp_settings_api_full_config = $this->read_stored_views( $this->ds_wp_settings_api_full_config ); 

			// Get the views for main settings screen
			$this->main_settings['info'] = $this->ds_wp_settings_api_full_config['main_settings']['info'];
			
			// Get the views for any option/field tabs
			if(isset($this->ds_wp_settings_api_full_config['options'])) {
				$this->ds_wp_settings_option_fields = $this->ds_wp_settings_api_full_config['options'] ;
			} else {
				$this->ds_wp_settings_option_fields = false;
			}

			// Get the views for any about section tabs
			// see if there are about sections 
			if(isset($this->ds_wp_settings_api_full_config['about_sections']) && $this->ds_wp_settings_api_full_config['about_sections'] != '') {
				$this->ds_wp_settings_api_about_sections = $this->ds_wp_settings_api_full_config['about_sections'];
			} else {
				$this->ds_wp_settings_api_about_sections = false;
			} // end if

			// Check to see if the unique ID is properly stored in the DB.
			// if it's blank set a new id and put it in the DB
			if($this->main_settings['unique_id'] == ''){
				$this->main_settings['unique_id'] = $this->ds_wp_settings_api_random_string();
				update_option($ds_main_settings_unique_id_key, $this->main_settings['unique_id']);
			} // end if

			// Register admin scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'register_ds_wp_settings_api_admin_styles_scripts' ), 1, 1 );

			// check if the reset_defaults flag was set and clear the existing options if so
			// set the ds_wp_settings_api_messages value for another function to use
			if($reset_defaults) {
				$delete_main_settings = delete_option($this->main_settings['text_domain'] . '_main_settings');

				if($delete_main_settings){
					$this->ds_wp_settings_api_messages[] = 'Deleted ' . $this->main_settings['text_domain'] . '_main_settings';
				} else {
					$this->ds_wp_settings_api_messages[] = $this->main_settings['text_domain'] . '_main_settings' . ' was not set so it was not deleted.';
				} //end if($delete_main_settings){
			} //end if($reset_defaults)

			// check if the update flag was set. If so set the options except for
			// the info key
			if($update_db) {

				$ds_wp_settings_api_main_settings_except_views = $this->main_settings;
				$ds_wp_settings_api_main_settings_except_views['info'] = '';

				update_option($this->main_settings['text_domain'] . '_main_settings', $ds_wp_settings_api_main_settings_except_views);

			} // end update_db

		}// end if is_admin
	} // end function

	public function register_ds_wp_settings_api_admin_styles_scripts($hook) {
		// check to see if the hook is the same as what was defined in the config
		// Tip: get the hook by assigning the add_options_page function to a
		// variable.
		// match the page hook OR match the page slug if it's in the hook
		if($hook == $this->main_settings['page_hook'] || strpos($hook, $this->main_settings['page_slug'])) {

			// Google fonts
			wp_enqueue_style('ds-wp-google-fonts-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700');

			wp_enqueue_style('ds-wp-google-fonts-montserrat', 'https://fonts.googleapis.com/css?family=Montserrat:300,400,600,700');

			// Add the color picker css file
			wp_enqueue_style( 'wp-color-picker' );

			// Sync this version with the latest supported version for fontawesome-iconpicker
			wp_register_style('ds-wp-settings-api-fontawesome', 'https://use.fontawesome.com/releases/v5.1.1/css/all.css', '5.1.1');
			wp_enqueue_style('ds-wp-settings-api-fontawesome');

			// Plugin panel CSS
			wp_enqueue_style('ds-wp-settings-api', $this->get_file_url('/css/ds-wp-settings-api-admin.css'));

			// Font Awesome Picker CSS
			wp_register_style('ds-wp-settings-api-fontawesome-iconpicker', $this->get_file_url('/css/fontawesome-iconpicker.min.css'));
			wp_enqueue_style('ds-wp-settings-api-fontawesome-iconpicker');

			// Font Awesome Picker JS
			wp_register_script('ds-wp-settings-api-fontawesome-iconpicker', $this->get_file_url('/js/fontawesome-iconpicker.min.js'));
			wp_enqueue_script('ds-wp-settings-api-fontawesome-iconpicker');

			// Load the JS that adds the color picker
			wp_enqueue_script( 'ds-wp-settings-api-admin', $this->get_file_url( '/js/ds-wp-settings-api-admin.js'), array( 'wp-color-picker' ), false, true );

		} // end if($hook == $this->main_settings['page_hook'])

	} // end public function register_ds_ucfml_admin_styles_scripts
		
	public function get_main_settings() {
		return $this->main_settings;
	} // end function get_item_options

	public function get_current_settings() {
		return $this->current_settings;
	} // end function get_current_settings

	public function ds_wp_api_reset_settings() {

		// see if we should upate the db or not

		$update_db_choice = true;
		$reset_options = $_POST['remove_data'];
		if($reset_options == 'true') $update_db_choice = false;

		$reset_messages = 'Attempting to reset all settings.';

		// delete stored settings
		$this->set_main_settings($update_db = $update_db_choice, $reset_defaults = true);

		// delete the settings fields
		$this->set_current_settings($update_db = $update_db_choice, $reset_defaults = true);

		$response_html = '';
		foreach($this->ds_wp_settings_api_messages as $reset_message) {
			$response_html .= '<p>' . $reset_message . '</p>';
		}

		$response = array(
			'messages' => $response_html,
		);
		wp_send_json($response);

		wp_die();

	} // end function reset_all_settings

	public function get_reset_ajax_form() {

		$reset_html = '<h2>RESET ALL SETTINGS</h2>
		  <h4>Caution: This will reset all settings to default values.</h4>

		  <form id="ds-wp-settings-reset" action="#" method="POST" data-item-slug="' . $this->main_settings['item_slug'] . '">
		    <div class="ds-wp-settings-api-ajax-form">
				<div class="ds-wp-settings-api-ajax-form-row"><label><input type="checkbox" id="ds_wp_settings_api_remove_data" name="ds_wp_settings_api_remove_data" value="true" style="margin-right: 10px;">Delete ALL settings data from database, too.</label></div>
		        <div class="ds-wp-settings-api-ajax-form-row"><input type="submit" value="Reset All Settings" /></div>
		    </div>
		  </form>

		  <div id="ds-wp-settings-reset-response"></div>';
		return $reset_html;

	} // end function reset_all_settings

	/* Create the actual options page */
	// pass a title, header content and about sections. If you don't, then the header content will be loaded from the "main_settings" key along with the content from any file named main_settings.php that is placed in the views directory

	// if there's a logo_file key specified with a path relative to this library it will be shown in the header bar 
	
	public function build_settings_panel($title = null, $header_content = null){

		if($title == null) $title = $this->main_settings['name'];
		if($header_content == null) $header_content = $this->main_settings['info'];
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
			echo settings_errors();
		?>
		<div id="<?php echo $this->main_settings['page_hook'];?>" class="ds-wp-settings-api-panel-wrap">
			<div class="ds-wp-settings-api-admin-title">
				<h1><?php echo $title; ?></h1>
				<?php
				// check if the image file is set
				if(isset($this->main_settings['logo_file']) && $this->main_settings['logo_file'] != null) {
					?>
					<img id="ds-wp-settings-logo" src="<?php echo  plugins_url( $this->main_settings['logo_file'], __FILE__);?>">
					<?php
				}
				?>
			</div>
			<div class="ds-wp-settings-api-inner-wrap">
				<?php if($header_content != '') { ?>

					<div class="ds-wp-settings-api-header">
						<?php echo $header_content; ?>
					</div> <!--ds-wp-settings-api-header-->
						
				<?php } ?>
			
			<?php
			$this->build_options_form(); ?>
			
			<div class="ds-wp-settings-api-attribution">
				<span class="ds-wp-settings-api-item-home"><a href="<?php echo $this->main_settings['item_uri'];?>"><?php echo $this->main_settings['name'];?></a></span>
				<span class="ds-wp-settings-api-item-version">Version <?php echo $this->main_settings['version'];?> by <a href="<?php echo $this->main_settings['author_uri'];?>"><?php echo $this->main_settings['author'];?></a></span>
			</div><!--ds-wp-settings-api-attribution-->
		</div><!--ds-wp-settings-api-inner-wrap-->
	</div> <!--ds-wp-settings-api-panel-wrap-->

	<?php
	} // end function build_settings_panel
	public function build_options_form() {
		
		if($this->ds_wp_settings_option_fields || $this->ds_wp_settings_api_about_sections) { ?>
			<div class="ds-wp-settings-api-admin-panel-wrap">
		<?php
			$tabs = $this->main_settings['tabs'];
			// See if tabs should be created and if so, create them

			if($tabs == "true") {
				// build tabs
				?>
				<h2 class="nav-tab-wrapper" id="panel-nav-tab">
						<!-- when tab buttons are clicked we jump back to the same page but with a new parameter that represents the clicked tab. accordingly we make it active -->
						<?php
						$ds_wp_settings_api_section_loop_counter = 1;

						// we check if the page is visited by click on the tabs or on the menu button.
						// then we get the active tab.
						$active_tab = '';
						if(isset($_GET["tab"]))
						{
							$active_tab = $_GET["tab"];
						}

						// Our tabs can be made up of options or about_sections in the JSON file.
						// Combine into an array since we have settings or about sections that
						// can be tabs. Add them to an array and add a key to mark what they are
						
						if($this->ds_wp_settings_option_fields) {				
							foreach($this->ds_wp_settings_option_fields as $option_key => $option_array) {
								if(isset($option_array['skip']) && ($option_array['skip'] == 'true' || $option_array['skip'] === true)) {
									continue;
								} else {
									$option_array['type'] = 'option';
									$ds_wp_settings_api_tab_array[$option_key] = $option_array;
								} // end if skip
							} // end foreach 
						} // end if
						if($this->ds_wp_settings_api_about_sections) {
							foreach($this->ds_wp_settings_api_about_sections as $about_section_key => $about_section_array) {
								if(isset($about_section_array['skip']) && ($about_section_array['skip'] == 'true' || $about_section_array['skip'] === true)) {
									continue;
								} else {
									$about_section_array['type'] = 'about_section';
									$ds_wp_settings_api_tab_array[$about_section_key] = $about_section_array;
								} // end if skip
							} // end foreach 
						} // end if

						// loop through the combined array to create tabs

						// prepare the arrays - if there is no tab order, assign 100 
						foreach($ds_wp_settings_api_tab_array as $ds_wp_settings_api_section_key => $ds_wp_settings_api_section) {
							$tab_order_counter = 100;
							if(!array_key_exists('tab_order', $ds_wp_settings_api_section)) {
								$ds_wp_settings_api_tab_array[$ds_wp_settings_api_section_key]['tab_order'] = $tab_order_counter;
							}
						} // end foreach

						// do the multidimensional sort, preserving array keys
						uasort($ds_wp_settings_api_tab_array, function ($a, $b) {
							return $a['tab_order'] - $b['tab_order'];
						});

						foreach($ds_wp_settings_api_tab_array as $ds_wp_settings_api_section_key => $ds_wp_settings_api_section) {

							$ds_wp_settings_api_section_slug = $ds_wp_settings_api_section_key;
							if($ds_wp_settings_api_section_loop_counter < 2 && $active_tab == '') {
								$active_tab = $ds_wp_settings_api_section_slug;
							}

							// set the tab title
							if(isset($ds_wp_settings_api_section['tab_label']) && $ds_wp_settings_api_section['tab_label'] != '') {
								$tab_label = $ds_wp_settings_api_section['tab_label'];
							} else if(isset($ds_wp_settings_api_section['title']) && $ds_wp_settings_api_section['title'] != '') {
								$tab_label = $ds_wp_settings_api_section['title'];
							} else {
								$tab_label = 'Option ' . $ds_wp_settings_api_section_loop_counter;
							}
							?>
							<a href="<?php menu_page_url($this->main_settings['page_slug']);?>&tab=<?php echo $ds_wp_settings_api_section_slug; ?>#panel-nav-tab" class="nav-tab <?php if($active_tab == $ds_wp_settings_api_section_slug){echo 'nav-tab-active';} ?>"><?php _e($tab_label, $this->main_settings['text_domain']); ?></a>

							<?php
							$ds_wp_settings_api_section_loop_counter++;
						} // end foreach
					?>
				</h2>
				<?php
			}
		?>
				<div class="ds-wp-settings-api-admin-panel">
					<?php
					// check if we have tabs and if not show all sections
					if($tabs == "true"){
						if($ds_wp_settings_api_tab_array[$active_tab]['type'] == 'option') {
							// do the form  ?>

							<form action="options.php" method="POST" id="<?php echo $active_tab;?>"> <?php
							$current_settings_page = $active_tab . $this->main_settings['page_suffix'];
							settings_fields ( $current_settings_page  );
							do_settings_sections( $current_settings_page );
							submit_button(); ?>
						</form>
						<?php
						} else {
							// about section so print the info
							$current_about_section = $ds_wp_settings_api_tab_array[$active_tab];

								$current_about_section_title = isset($current_about_section['title']) && !empty($current_about_section['title']) ? $current_about_section['title'] : '';

								$current_about_section_info = isset($current_about_section['info']) && !empty($current_about_section['info']) ? $current_about_section['info'] : '';
								echo '<h2>' . $current_about_section_title . '</h2>';
								echo $current_about_section_info;
						} // end if($tabs == "true")

					} else {
						// All of our settings are on one page instead of tabs
						// do the form  
						if($this->ds_wp_settings_option_fields) {?>

						<form action="options.php" method="POST"> <?php
							settings_fields ( $this->main_settings['text_domain'] );
							// get each option section
							foreach($this->ds_wp_settings_option_fields as $ds_wp_settings_api_section_key => $ds_wp_settings_api_section) {
								$current_settings_page = $ds_wp_settings_api_section_key . $this->main_settings['page_suffix'];
								do_settings_sections( $current_settings_page );
							} // end foreach
							submit_button(); ?>
						</form>
					<?php
						} // end if

						// get all about sections
						if($this->ds_wp_settings_api_about_sections) {
							foreach($this->ds_wp_settings_api_about_sections as $ds_wp_settings_api_about_key => $ds_wp_settings_api_about_section) {
								echo '<h2>' . $ds_wp_settings_api_about_section['title'] . '</h2>';
								echo $ds_wp_settings_api_about_section['info'];
							} // end foreach
						} // end if

					} // end if tabs == true
					?>
				</div><!--ds-wp-settings-api-admin-panel-->				
			</div><!--ds-wp-settings-api-inner-wrap-->
			<?php 
		} // end if
	} // end function ds_wp_settings_api_menu_options

	public function set_current_settings($update_db = false, $reset_defaults = false) {

		// with this function we can get the default values as well as any saved
		// to the db. If the $update_db flag is set the values will be saved
		// to the database. $reset_defaults will delete the existing keys and
		// insert default settings
		if ($this->ds_wp_settings_option_fields) {

		// get the various fields from the array
		foreach($this->ds_wp_settings_option_fields as $ds_settings_key => $ds_default_settings_fields) {
			// set the option name used in the db and fields
			$ds_settings_option_name = $ds_settings_key . $this->main_settings['options_suffix'];

			// first fill our options array with all default values
			foreach($ds_default_settings_fields['fields'] as $ds_default_setting_field){
				// assign the default value to the array if it exists otherwise assign blank
				if(isset($ds_default_setting_field['value']) && !empty($ds_default_setting_field['value'])) {
					$ds_wp_settings_values[$ds_settings_option_name][$ds_default_setting_field['id']] = $ds_default_setting_field['value'];
				} else {
					if($ds_default_setting_field['type'] == 'color_picker') {
						// set the default color picker to black
						$ds_wp_settings_values[$ds_settings_option_name][$ds_default_setting_field['id']] = '#000000';
					} else if($ds_default_setting_field['type'] == 'protected') {
					$ds_wp_settings_values[$ds_settings_option_name][$ds_default_setting_field['id']] = 'protected_must_decrypt';
					} else {
						$ds_wp_settings_values[$ds_settings_option_name][$ds_default_setting_field['id']] = '';
					}
				} // end if isset

				// If the randomize key is set, set a random value
				if(isset($ds_default_setting_field['randomize']) && $ds_default_setting_field['randomize'] == 'true') {
					$ds_random_string = $this->ds_wp_settings_api_random_string();
					$ds_wp_settings_values[$ds_settings_option_name][$ds_default_setting_field['id']] = $ds_random_string;
				}

			} // end foreach

			// delete existing options if that flag is set
			// set the ds_wp_settings_api_messages for another function to use
			if($reset_defaults) {
				$delete_field_setting = delete_option($ds_settings_option_name);
					if($delete_field_setting){
						$this->ds_wp_settings_api_messages[] = 'Deleted ' . $ds_settings_option_name;
					} else {
						$this->ds_wp_settings_api_messages[] = $ds_settings_option_name . ' was not set so it was not deleted.';
					} //end if($delete_main_settings)
			} //end if($reset_defaults)

			// now get the options set in the db
			$ds_db_settings_fields = get_option($ds_settings_option_name);

			// replace the values in the array with values from the db
			if(is_array($ds_db_settings_fields)){
				foreach($ds_db_settings_fields as $ds_db_setting_field_key => $ds_db_setting_field) {
					// $this->wl($ds_wp_settings_values);
					// if($ds_wp_settings_values[$ds_settings_option_name][$ds_db_setting_field_key] == 'protected_must_decrypt') {
					// 	$this->wl('decrypt');
					// } else {
						$ds_wp_settings_values[$ds_settings_option_name][$ds_db_setting_field_key] = $ds_db_setting_field;

					// }
				} // end foreach
			}// end if

			// add the settings api key value for use in sanitize callbacks
			$ds_wp_settings_values[$ds_settings_option_name]['ds_wp_settings_api_option_key'] = $ds_settings_key;
		} // end foreach

		// check if the update flag was set. If so set the options
		if($update_db) {
			foreach ($ds_wp_settings_values as $ds_wp_setting_key => $ds_wp_setting_value) {

				//The sanitize callback is going to be called because register_settings has been run. Add a $_POST key since our sanitize callback checks for that with the proper option key name
				$_POST[$ds_wp_setting_key] = array( 'ds_wp_settings_api_option_key' => $ds_wp_setting_value['ds_wp_settings_api_option_key']);

				update_option($ds_wp_setting_key, $ds_wp_setting_value);
			} // end foreach
		} // end update_db
		// return the array
		return $ds_wp_settings_values;
		} // end if

	} // end function set_current_settings

	public function ds_wp_settings_api_random_string($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
	}

	public function wl ( $log )  {
		if ( true === WP_DEBUG ) {
				if ( is_array( $log ) || is_object( $log ) ) {
						error_log( print_r( $log, true ) );
				} else {
						error_log( $log );
				}
			}
	} // end write_log


	public function encrypt_data($input, $password = null) {
		// adapted from https://stackoverflow.com/questions/3422759/php-aes-encrypt-decrypt/46872528#46872528

		// if the unique ID isn't set for whatever reason use the unique ID in our settings
		if(!$password){

			if(isset($this->main_settings['unique_id']) && $this->main_settings['unique_id'] != '') {
				// use the unique ID that was added when the item was created
				$password = $this->main_settings['unique_id'];
			} else {
				$password = 'testing_purposes_only';
			}
		} // end if(!$password)

		$openssl_method = 'AES-256-CBC';

		$openssl_key = hash('sha256', $password, true);

		$openssl_iv = openssl_random_pseudo_bytes(16);

		$cipher_input = openssl_encrypt($input, $openssl_method, $openssl_key, OPENSSL_RAW_DATA, $openssl_iv);

		$openssl_hash = hash_hmac('sha256', $cipher_input, $openssl_key, true);

		$encrypted_encoded = base64_encode($openssl_iv . $openssl_hash . $cipher_input);
		return $encrypted_encoded;

	} // end function encrypt_data

	public static function decrypt_data($encoded_input, $password = null) {
		// if the unique ID isn't set for whatever reason use the unique ID in our settings
		if(!$password){

			if(isset($this->main_settings['unique_id']) && $this->main_settings['unique_id'] != '') {
				// use the unique ID that was added when the item was created
				$password = $this->main_settings['unique_id'];
			} else {
				$password = 'testing_purposes_only';
			}
		} // end if(!$password)
		// adapted from https://stackoverflow.com/questions/3422759/php-aes-encrypt-decrypt/46872528#46872528

		$input = base64_decode($encoded_input);

		$openssl_method = 'AES-256-CBC';

		$openssl_key = hash('sha256', $password, true);

		$openssl_iv = substr($input, 0, 16);

		$openssl_hash = substr($input, 16, 32);

		$cipher_input = substr($input, 48);

		if(hash_hmac('sha256', $cipher_input, $openssl_key, true) != $openssl_hash) return null;
		$decoded_input = openssl_decrypt($cipher_input, $openssl_method, $openssl_key, OPENSSL_RAW_DATA, $openssl_iv);

		return $decoded_input;

	} // end function encrypt_data

	// Checks for php files to read into the info key for the main_settings key
	// and each option under options and about_sections keys
	public function read_stored_views($ds_wp_settings_api_key) {

		foreach ($ds_wp_settings_api_key as $section_key => $section) {
			if($section_key == 'main_settings') {
				if(!isset($section['info']) || $section['info'] == '') {
					// Set our directory name for views
					$ds_wp_settings_api_key[$section_key]['info'] = $this->ds_wp_settings_api_import_view($section_key);
				} // end if
			} elseif ($section_key == 'options') {
				if(!isset($section['info']) || $section['info'] == '') {
					foreach ($ds_wp_settings_api_key[$section_key] as $option_key => $option) {
						$ds_wp_settings_api_key[$section_key][$option_key]['info'] = $this->ds_wp_settings_api_import_view($option_key);
					} //end foreach option
				} //end if
			} elseif ($section_key == 'about_sections') {
				if(!isset($section['info']) || $section['info'] == '') {
					foreach ($ds_wp_settings_api_key[$section_key] as $about_section_key => $about_section) {
						$ds_wp_settings_api_key[$section_key][$about_section_key]['info'] = $this->ds_wp_settings_api_import_view($about_section_key);
					} //end foreach about_sections
				} // end if
			} 
			
		} // end foreach foreach ($ds_wp_settings_api_key as $section_key => $section)

		return $ds_wp_settings_api_key;
	} // end function read_stored_views

	protected function ds_wp_settings_api_import_view($view_name) {
		$view_file = $this->ds_wp_api_settings_init_data['views_dir'] . '/' . $view_name . '.php';

		// if the file exists try to open
		if(file_exists($view_file)){
			try{
				ob_start();
				include( $view_file );
				return ob_get_clean();

			} catch (Exception $e){
				$this->wl($e->getMessage());
			} // end try
		} else {
			return '';
		}// end if(file_exists($view_file))
	} // end protected function ds_wp_settings_api_import_view

	/* Register the various settings */
	public function build_settings() {

		if($this->ds_wp_settings_option_fields) {
		foreach ($this->ds_wp_settings_option_fields as $ds_wp_settings_api_setting_id => $ds_wp_settings_api_field_setting) {

			// create the name for our options key
			$ds_wp_settings_api_option_name = $ds_wp_settings_api_setting_id . $this->main_settings['options_suffix'];

			// create the page name
			$ds_wp_settings_api_option_page = $ds_wp_settings_api_setting_id . $this->main_settings['page_suffix'];

			// get the section title
			$ds_wp_settings_api_settings_section_title = isset($ds_wp_settings_api_field_setting['title']) && !empty($ds_wp_settings_api_field_setting['title']) ? $ds_wp_settings_api_field_setting['title'] : '';

			// add the settings section
			add_settings_section(
				$ds_wp_settings_api_setting_id, // String for use in the 'id' attribute of tags.
				$ds_wp_settings_api_settings_section_title, // Title of the section.
				array($this, 'ds_wp_settings_api_create_settings_section_callback'), // Callback
				$ds_wp_settings_api_option_page // Page. The menu page on which to display this section.
			);

			// create the option group name - use the same as page name if tabs
			// and use the text domain if not tabs
			$tabs = $this->main_settings['tabs'];
			if($tabs == "true") {
				$ds_wp_settings_api_option_group = $ds_wp_settings_api_option_page;
			} else {
				// use the text domain for the page name
				$ds_wp_settings_api_option_group = $this->main_settings['text_domain'];
			}

			// register the settings
			register_setting( $ds_wp_settings_api_option_group, $ds_wp_settings_api_option_name, array($this, 'ds_wp_settings_api_sanitize') );

			// loop through the fields and create settings fields for each
			foreach ($ds_wp_settings_api_field_setting['fields'] as $ds_wp_settings_api_field_setting) {
				// set a class for the row with the field type in the class
				$ds_wp_settings_api_option_class = 'ds-wp-settings-api-' . $ds_wp_settings_api_field_setting['type'] . '-row';

				add_settings_field(
					$ds_wp_settings_api_field_setting['id'], // String for use in the 'id' attribute of tags.
					__($ds_wp_settings_api_field_setting['label'], $this->main_settings['text_domain']), // Title of the field.
					array($this, 'ds_wp_settings_api_create_settings_field_callback'), // callback
					$ds_wp_settings_api_option_page, // Page.  The menu page on which to display this field.
					$ds_wp_settings_api_setting_id, // The section of the settings page (added via add_settings_section)
					array(
						'fields' => $ds_wp_settings_api_field_setting,
						'option_name' => $ds_wp_settings_api_option_name,
						'class' => $ds_wp_settings_api_option_class // class for the tr
					) // options passed to the callback
				);
			} // nested foreach
		} // end foreach
		} // end if 
	} // end public function ds_wp_settings_api_register_settings

	public function ds_wp_settings_api_create_settings_field_callback($args){

		$settings = $args['fields'];
		$option_name = $args['option_name'];
		$option_class = isset($settings['class']) ? $settings['class'] : '';

		// defaults
		$ds_input_class = '';

		// get the current option value
		$ds_input_setting_option = $this->current_settings[$option_name][$settings['id']];

		// check if this is a required value and if the value is blank. if so set the class
		if((isset($settings['required']) && $settings['required'] == "true") && $ds_input_setting_option == '') {
			$ds_input_class = "ds-wp-api-input-required";
			echo '<span class="ds-wp-api-required-message">*This field is required</span>';
		}

		if($settings['type'] == 'text') {
			echo '<input type="text" id="' . $settings['id'] . '" name="' . $option_name . '[' . $settings['id'] . ']" value="'. $ds_input_setting_option . '" class="ds-wp-api-input ' . $ds_input_class . ' ' . $option_class . '" />';
			echo settings_errors($settings['id']);
		} // end if($settings['type'] == 'text')

		else if($settings['type'] == 'multifield_text') {

			if(!is_array($ds_input_setting_option)) {
				$ds_input_setting_option = array( $ds_input_setting_option);
			} 
			echo '<div class="ds-wp-api-expanding-input-fields">';
			$multifield_text_counter = 0;
			foreach ($ds_input_setting_option as $option){
				if(!($multifield_text_counter > 1 && $option == '')) {
					echo '<div class="ds-wp-api-expanding-input"><input type="text" id="' . $settings['id'] . '_' . $option . '" name="' . $option_name . '[' . $settings['id'] . '][]" value="'. $option . '" class="ds-wp-api-input ' . $ $ds_input_class . ' ' . $option_class .'" /><span class="ds-wp-api-expanding-input-remove"></span></div>';
					$multifield_text_counter++;
				} // end if 
			}
			echo '<div class="ds-wp-api-expanding-input-fields-add">Add </div></div>';
			echo settings_errors($settings['id']);
		} // end if($settings['type'] == 'text')

		else if($settings['type'] == 'color_picker')  {
			echo '<input type="text" id="' . $settings['id'] . '" name="' . $option_name . '[' . $settings['id'] . ']" value="'. $ds_input_setting_option . '" class="ds-wp-api-input cpa-color-picker ' . $ $ds_input_class . ' ' . $option_class .'" />';

			// Show the error if any for this ID
			echo settings_errors($settings['id']);
		} // end else if($settings['type'] == 'color_picker')

		else if($settings['type'] == 'fontawesome_picker')  {
			echo '<input type="text" id="' . $settings['id'] . '" name="' . $option_name . '[' . $settings['id'] . ']" value="'. $ds_input_setting_option . '" class="ds-wp-api-input fontawesome-picker ' . $ $ds_input_class . ' ' . $option_class .'" />';
			echo '<span class="input-group-addon iconpicker-component"></span>';
		} // end else if($settings['type'] == 'fontawesome_picker')

		else if($settings['type'] == 'number')  {
			// set the step amount for decimal places
			$step_amount = isset($settings['step']) && !empty($settings['step']) ? $settings['step'] : '1';

			echo '<input type="number" id="' . $settings['id'] . '" name="' . $option_name . '[' . $settings['id'] . ']" step="' . $step_amount . '" value="'. $ds_input_setting_option . '" class="ds-wp-api-input ' . $ds_input_class . ' ' . $option_class .'" />';

			// Show the error if any for this ID
			echo settings_errors($settings['id']);
		} // end else if($settings['type'] == 'number')

		else if($settings['type'] == 'protected')  {
			echo '<input type="password" id="' . $settings['id'] . '" name="' . $option_name . '[' . $settings['id'] . ']" value="'. $ds_input_setting_option . '" class="ds-wp-api-input ' . $ds_input_class . ' ' . $option_class .'" />';

			echo settings_errors($settings['id']);
		}
		else if($settings['type'] == 'password')  {
			echo '<input type="password" id="' . $settings['id'] . '" name="' . $option_name . '[' . $settings['id'] . ']" value="'. $ds_input_setting_option . '" class="ds-wp-api-input ' . $ds_input_class . ' ' . $option_class .'" />';

			echo settings_errors($settings['id']);
		} // end
		else if($settings['type'] == 'hidden')  {

			echo '<input type="hidden" id="' . $settings['id'] . '" name="' . $option_name . '[' . $settings['id'] . ']" value="'. $ds_input_setting_option . '" class="ds-wp-api-input ds-wp-api-hidden ' . $ds_input_class .'" />';

			// Show the error if any for this ID
			echo settings_errors($settings['id']);
		} // end else if($settings['type'] == 'color_picker')

		else if($settings['type'] == 'checkbox')  {

			echo '<div class="ds-checkbox">';
			foreach ($settings['options'] as $option_value => $option_text) {
				echo '<div class="ds-checkbox-inner">';
				$checked = ' ';
				if(is_array($ds_input_setting_option) && in_array($option_value, $ds_input_setting_option)){
					$checked = ' checked="checked" ';
				}
				else if ($ds_input_setting_option == $option_value) {
					$checked = ' checked="checked" ';
				}
				else if ($ds_input_setting_option === FALSE && $settings['value'] == $option_value){
					$checked = ' checked="checked" ';
				}
				else {
					$checked = ' ';
				}
				echo '<input type="checkbox" name="' . $option_name . '[' . $settings['id'] . '][]" id="' . $settings['id'] . '_' . $option_value . '" class="' .  $option_class . '" value="' . $option_value . '" ' . $checked . '/>';
				echo '<label for="' . $settings['id'] . '_' . $option_value . '">' . $option_text . '</label>&nbsp;';
				echo '</div>';
			}
			echo '</div>';
		} // else if($settings['type'] == 'checkbox')

		else if($settings['type'] == 'radio')  {
			echo '<div class="ds-radio">';
			foreach ($settings['options'] as $option_value => $option_text) {
				$checked = ' ';
				if ($ds_input_setting_option == $option_value) {
					$checked = ' checked="checked" ';
				}
				else if ($ds_input_setting_option === FALSE && $settings['value'] == $option_value){
					$checked = ' checked="checked" ';
				}
				else {
					$checked = ' ';
				}

				echo '<input type="radio" name="' . $option_name . '[' . $settings['id'] . ']" id="' . $settings['id'] . '_' . $option_value . '" class="' .  $option_class . '" value="' . $option_value . '" ' . $checked . '/>';
				echo '<label for="' . $settings['id'] . '_' . $option_value . '">' . $option_text . '</label>&nbsp;';
			}
			echo '</div>';
		} // else if($settings['type'] == 'radio')

		else if($settings['type'] == 'radio_on_off')  {
			// only two options are allowed here
			$option_counter = 0;
			echo '<div class="ds-switch">';

			foreach ($settings['options'] as $option_value => $option_text) {
				// break if more than two options
				if(++$option_counter > 2) break;
				// for($option_counter = 1; $option_counter <=2; $option_counter++) {
				$checked = ' ';
				// if (get_option($settings['id']) == $option_value) {
				if ($ds_input_setting_option == $option_value) {
					$checked = ' checked="checked" ';
				}
				else if ($ds_input_setting_option === FALSE && $settings['value'] == $option_value){
					$checked = ' checked="checked" ';
				}
				else {
					$checked = ' ';
				}
				echo '<input type="radio" class="ds-switch-input ' . $option_class . '" name="' . $option_name . '[' . $settings['id'] . ']" id="' . $settings['id'] . '_' . $option_value . '" value="' . $option_value . '" ' . $checked . '/>';
				echo '<label class="ds-switch-label ds-switch-label-' . $option_counter . '" for="' . $settings['id'] . '_' . $option_value . '">' . $option_text . '</label>';
				// } // end for($option_counter = 1; $option_counter <=2; $option_counter++)
			}// end foreach ($settings['options'] as $option_value => $option_text)
			echo '<span class="ds-switch-selection">';
			echo '</div>';
		} // else if($settings['type'] == 'radio_on_off')
		else if($settings['type'] == 'select')  {
			echo '<div class="ds-select">';
			echo '<select name="' . $option_name . '[' . $settings['id'] . ']" id="' . $settings['id'] . '" class="' . $option_class . '">';
			foreach ($settings['options'] as $option_value => $option_text) {
				$selected = ' ';
				if ($ds_input_setting_option == $option_value) {
					$selected = ' selected ';
				}
				else if ($ds_input_setting_option === FALSE && $settings['default'] == $option_value){
					$selected = ' selected ';
				}
				else {
					$selected = ' ';
				}

				echo '<option value="' . $option_value . '" ' . $selected . '/>' . $option_text . '</option>';
			} // end foreach
			echo '</select>';
			echo '</div>';
		} // else if($settings['type'] == 'select')
	}// end public function ds_wp_settings_api_create_settings_field_callback


	// create the settings page
	public function ds_wp_settings_api_create_settings_section_callback($args) {
		// set the info text if it exists
		$info_text = isset($this->ds_wp_settings_option_fields[$args['id']]['info']) && !empty($this->ds_wp_settings_option_fields[$args['id']]['info']) ? $this->ds_wp_settings_option_fields[$args['id']]['info'] : '';
		$section_callback = $info_text;
		echo '<div class="ds-wp-settings-api-info-text">' . $info_text . '</div>';
		// Add a hidden field for our sanitizer that has the options key name
		echo '<input type="hidden" name="' . $args['id'] . $this->main_settings['options_suffix'] .  '[ds_wp_settings_api_option_key]" value="'. $args['id'] . '" />';
	} // end function

	function endsWith($needle, $haystack) {
     return preg_match('/' . preg_quote($needle, '/') . '$/', $haystack);
 	}
	// sanitize various types of data
	public function ds_wp_settings_api_sanitize( $raw_input_data_fields )
	{
		// sanitized array which will be returned
		$cleaned_input_data = array();

		// find which db key holds our field data
		$option_key =	isset($raw_input_data_fields['ds_wp_settings_api_option_key']) && !empty($raw_input_data_fields['ds_wp_settings_api_option_key']) ? $raw_input_data_fields['ds_wp_settings_api_option_key'] : '';

		//work around a wordpress issue where the first save of a new option
		//doesn't have the hidden key for ds_wp_settings_api_option_key set.
		//we can find it in the POST array though.
		if($option_key == '') {

			//go through the post values
			foreach($_POST as $post_key => $post_value) {

				//use our function to check if the POST key ends with our option key name and use it if so
				$endsWith = $this->endsWith( $this->main_settings['options_suffix'],$post_key);

				if($endsWith) {
					$option_key = isset($_POST[$post_key]['ds_wp_settings_api_option_key']) && !empty($_POST[$post_key]['ds_wp_settings_api_option_key']) ? $_POST[$post_key]['ds_wp_settings_api_option_key'] : '';
				} //end if($endsWith)
			} //end foreach($_POST as $post_key => $post_value)
		}//end if

		// get the saniziation type
		$sanitization_fields = isset($this->ds_wp_settings_option_fields[$option_key]['fields']) && !empty($this->ds_wp_settings_option_fields[$option_key]['fields']) ? $this->ds_wp_settings_option_fields[$option_key]['fields'] : '';

		//work around issue if this sanitization is called without any field data
		if($sanitization_fields == '') {

			$this->wl('Error: No cleaning of data was performed.');

			$this->ds_wp_settings_api_messages[] = 'Error: No cleaning of data was performed.';

		} else {
			foreach($sanitization_fields as $sanitization_field){
				// get the validation type. If not set, send the type of field
				$validation_type = isset($sanitization_field['validation']) && !empty($sanitization_field['validation']) ? $sanitization_field['validation'] : $sanitization_field['type'];

				if ($validation_type == "number") {
					$validated_info = $this->validate_number($raw_input_data_fields[$sanitization_field['id']],
					$sanitization_field['id'], $sanitization_field['label']);
				}
				elseif ($validation_type == "multifield_text") {
					$validated_info = $this->validate_array($raw_input_data_fields[$sanitization_field['id']], $sanitization_field['id'], $sanitization_field['label']);
				}
				elseif ($validation_type == "checkbox") {
					$validated_info = $this->validate_array($raw_input_data_fields[$sanitization_field['id']], $sanitization_field['id'], $sanitization_field['label']);
				}
				elseif ($validation_type == "color_picker") {
					$validated_info = $this->validate_color_picker($raw_input_data_fields[$sanitization_field['id']], $sanitization_field['id'], $sanitization_field['label']);
				}
				elseif ($validation_type == "protected") {

					// check if the field is empty and save nothing if so
					if($raw_input_data_fields[$sanitization_field['id']] == '') {
						$validated_info = $raw_input_data_fields[$sanitization_field['id']];
					}
					// check if the encoded string is what's already in the db. if so
					// don't encrypt it again. Have to get the current settings plus and also
					// add the options suffix - this is a long and hard to read string
					else if($raw_input_data_fields[$sanitization_field['id']] == $this->current_settings[$option_key . $this->main_settings['options_suffix']][$sanitization_field['id']]) {
						$validated_info = $raw_input_data_fields[$sanitization_field['id']];
					}
					else {
						// validate the data and encrypt it
						$validated_info = $this->validate_protected($raw_input_data_fields[$sanitization_field['id']]);
					} // end if
				}
				else {
					$validated_info = $this->validate_text($raw_input_data_fields[$sanitization_field['id']]);
				}
				$cleaned_input_data[$sanitization_field['id']] = $validated_info;
			} // end foreach
			return $cleaned_input_data;

		} // end if($sanitization_fields == '')
	} // end function sanitize

	public function validate_text($input) {
		return sanitize_text_field($input);
	} // end function validate_text

	public function validate_number($input, $field_id, $label) {
		// check if it's numeric or just blank
		if((is_numeric($input) && $input >= 0) || $input == ''){
			return sanitize_text_field($input);
		} else {
			$this->create_settings_error($field_id, $label, 'make sure this field only contains a number');
		}
	} // end function validate_number

	public function validate_color_picker($input, $field_id, $label) {
		// check that this is a hex code with either three or six chars
		if(preg_match('/#([a-fA-F0-9]{3}){1,2}\b/', $input)) {
			return sanitize_text_field($input);
		} else {
			$this->create_settings_error($field_id, $label, 'This must be a hex color value such as #000000.');
		}
	} // end function validate_color_picker

	public function validate_protected($input) {

		//first clean it
		$cleaned_input = sanitize_text_field($input);

		$encrypted_input = $this->encrypt_data($cleaned_input);
		return $encrypted_input;

	} //end function validate_protected

	public function validate_array($input, $field_id, $label) {
		if(is_array($input)){
			$new_input = array();
			foreach ($input as $key=>$item) {
				$new_input[$key] = sanitize_text_field($item);
			}
			return $new_input;
		} else {
			//not passed an array but go ahead and sanitize
			sanitize_text_field($input);
			return $input;
		}
	} // end function validate_color_picker

	public function create_settings_error($field_id, $label, $message) {
		add_settings_error(
			$field_id, // settings_error callback ID
			$field_id . '-validationError', // ID for the error section
			$label . ' ' . $message, // message text
			'error ' . $field_id // class for the error
		);
	} // end function create_settings_error


    /**
     * @param string $filePath
     * @return string
     * Adapted from plugin-update-checker
     */
    public function get_file_url($filePath) {

		$absolutePath = realpath(dirname(__FILE__) . '/' . ltrim($filePath, '/'));

        //Where is the library located inside the WordPress directory structure?
        $absolutePath = wp_normalize_path($absolutePath);
        $pluginDir = wp_normalize_path(WP_PLUGIN_DIR);
        $muPluginDir = wp_normalize_path(WPMU_PLUGIN_DIR);
        $themeDir = wp_normalize_path(get_theme_root());

        if ( (strpos($absolutePath, $pluginDir) === 0) || (strpos($absolutePath, $muPluginDir) === 0) ) {
            //It's part of a plugin.
            return plugins_url(basename($absolutePath), $absolutePath);
        } else if ( strpos($absolutePath, $themeDir) === 0 ) {
            //It's part of a theme.
            $relativePath = substr($absolutePath, strlen($themeDir) + 1);
            $template = substr($relativePath, 0, strpos($relativePath, '/'));
            $baseUrl = get_theme_root_uri($template);

            if ( !empty($baseUrl) && $relativePath ) {
                return $baseUrl . '/' . $relativePath;
            }
        } 
        return '';
    } // end function get_updater_url

}} // end class DustySun_WP_Settings_API
