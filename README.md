#Dusty Sun WP Settings API
A class to include in your WordPress plugin to make it easy to add fields and settings.

Features
--------
* **Builds a complete plugin panel or just the fields.**    

  Can use one of the functions to build a completed plugin panel, with tabs, if you choose. If you don't want a complete plugin panel, there's a function to just build the fields so you can put your own HTML wrapper around the fields.

* **Easy to set up.**

  Simply fill out the options in the JSON file and include this in your plugin. An example JSON file is included.

Getting Started
---------------

### Adding the class to your theme

Require the file:
```
require( dirname( __FILE__ ) . '/lib/ds_wp_settings_api.php');
```
Create the class and pass the JSON file:
```
$ds_example_json_file = plugin_dir_path( __FILE__ ) . '/ds-example-options.json';

$ds_example_settings_obj = new DustySun_WP_Settings_API(($ds_example_json_file), true);
```
True means the options page is built. False will not build the options page - use this elsewhere in your plugin.

## Notes

If you create the class with the second option set to true, it will run the logic to create the options pages. If this second parameter is missing or false, the options will not be built. However, the current_settings function will be available.

Why the difference? Well on your admin pages you'll definitely want to create this object with the parameter set to true so your options will be built.

For the user-facing code in your plugin, you can create this object with the parameter set to false. This will read in any default values and pull any required ones from the DB. They will then be available to your plugin via the current_settings function.

For example you could do this in your plugin:

```
$ds_example_current_settings = $ds_example_settings_obj->current_settings();
```
### Instantiation

Example:
```
$my_api_settings = array(
  'json_file' => plugin_dir_path( __FILE__ ) . '/plugin-options.json',
  'register_settings' => true
);

$my_settings_page = new My_DustySun_WP_Settings_API($my_api_settings);
```


### Available Functions

#### get_reset_ajax_form
Call this function in a PHP file to output an AJAX form that can be used to remove all settings from the db for the plugin.

#### read_json_file
Pass a file name while building the class or put a file with the .json extension in the same directory as the settings api php file. It must have the same name as the PHP file but with the .json extension.

JSON General Options
------------
# Fields - main_settings (old plugin_settings)
You can also add additional keys here should you choose, and they will be available

## text_domain
Old: plugin_domain

Some of your plugin or theme options are stored under this key along with the suffix \_item_settings. Also used for language Settings

## tabs
Whether or not to show the various options fields as separate tabs or all on one page.

## options_suffix
Optional: For each field name, this is appended to the end when the options are stored in the DB. Default is "\_options"

For example, if you have a key under options called "advanced" and you set options_suffix to \_options then your options would be stored in the WordPress database under "advanced_options".

## page_suffix
For each tab in the settings page, the URL will be the name of the field plus this suffix. Default is "\_page"

## author
Author name

## author_uri
Author webpage

## name
Old: plugin_name
Friendly name of your plugin or theme

## item_uri
Alternates: plugin_uri or theme_uri
Homepage for your plugin or theme

## support_uri
Link to support for your plugin or theme

## support_email
Email for your plugin or theme support or queries.

## page_slug
Page slug you supplied to your plugin or theme for the page where your options appear.


## item_slug
Old: plugin_slug
The slug/name of the main folder in for your plugin or theme. This should be to be set to the folder of the plugin or theme file. Page_slug will be used if not defined.


## version
Version needs to be updated every time.


MORE NOTES TO COLLATE


JSON Options
------------
# Fields

Add a key named "fields." These fields are available:

## id
id used for the field - must be unique

## label
the label you want to appear next to the field

## value
the default value shown

## type
specify what kind of input field should be shown. Pick from the following types:

### text
standard text field

unique fields (all are optional):
* randomize - set to true if you want a randomized string to appear

* required - set to true if field must be filled in

#### Example of a standard text field:
```json
{
  "id":"full_name",
  "label":"Full name:",
  "type":"text",
  "value":"Your Name"
}
```

#### Example of a randomized field that's required:
```json
{
  "id":"webhook_key",
  "label":"Webhook Key:",
  "type":"text",
  "randomize":"true",
  "required":"true"
}
```

### number
input field allowing numbers only
unique fields (all are optional):
* min - minimum amount (by default this is set to 0 if you do not specify a minimum)
* max - maximum amount
* required - choose if field must be filled in
* step - the step increment

```json
{
  "id":"coupon_amount",
  "label":"Amount:",
  "type":"number",
  "step":".01",
  "min":"5",
  "max":"100",
  "value":"10"
}
```

### select
standard drop down menu

* options - specify the options in standard JSON with the option name on the left and the display name on the right, like this:

Example:

```json
{
  "id":"coupon_type",
  "label":"Coupon Type:",
  "type":"select",
  "options":{
    "fixed_cart":"Fixed Cart",
    "fixed_product":"Fixed Product",
    "percent":"Percent"
    },
  "value":"fixed_product"
}
```
### checkbox
creates a standard block of multi-selection checkboxes
```json
{
  "id":"shirt_sizes_available",
  "label":"Shirt Sizes:",
  "type":"checkbox",
  "options":{
    "small":"Small",
    "medium":"Medium",
    "large":"Large"
    },
  "value":"single"
}
```
### radio
creates a standard block of radio buttons
```json
{
  "id":"ticket_types",
  "label":"Choose type of ticket:",
  "type":"radio",
  "options":{
    "single":"Single Pass",
    "family":"Family Pass",
    "season":"Season Pass"
    },
  "value":"single"
}
```
### radio_on_off
creates a sliding button, so you have two options
```json
{
  "id":"coupon_delete_existing",
  "label":"Delete existing coupon with same code:",
  "type":"radio_on_off",
  "options":{
    "true":"On",
    "false":"Off"
    },
  "value":"true"
}
```
### color_picker
creates a color picker field for choosing a hex code from a color wheel
```json
{
  "id":"hover_label_bg_color",
  "label":"Hover Label Background Color:",
  "type":"color_picker",
  "value":"#000"
}
```
### fontawesome_picker
allows you to choose an icon from the FontAwesome icon set (v4.7)
```json
{
  "id":"error_icon",
  "label":"Error message icon:",
  "type":"fontawesome_picker",
  "value":"fa-info-circle"
}
```
### hidden
creates a hidden field
```json
{
  "id":"form_version",
  "type":"hidden",
  "value":"1.0"
}
```
### protected
creates a field with bulleted dots shown
```json
{
  "id":"webhook_secret",
  "label":"Webhook Secret:",
  "type":"protected"
}
```

Functions
---------

## get_main_settings()

Returns everything under the item_options key from the JSON file.
Old: get_plugin_options

## set_main_settings()
Sets the plugin defaults.

Old: set_plugin_options

Optional fields:
* $update_db - false by default - pass true to store values in the database
* $reset_defaults - false by default - set to true to delete options from the database

## build_settings_panel
Creates the options pages

Old: build_plugin_panel
Fields:

Title = title shown at top of options page
Header Content = Pass HTML or other items to be shown beneath the title. If this is left blank, a php file in the views directory with the name main_settings will be shown.


Views
-----
PHP files accessed in the Views directory run within the setings api class. You can access the class variable $this->main_settings

#### Additional documentation coming soon. Please reach out if you're attempting to use this work in progress!


### Changelog
#### v2.0.6 - 2020-12-03 
* Fixed the spacing of select drop downs.

#### v2.0.4 - 2019-08-20
* Added incomplete functions to decrypt data.
* Added password field type.

#### v2.0.3 - 2019-02-28
* Fixed error with info/view pages not showing for option tabs.
* Added the "tab_order" field - you can now sort the order that the tabs appear by using this optional field. Use an integer to sort.
* Added the ability to use toggle_ classes on fields to hide or show related fields.
* Added anchor tab to scroll the user to the top of the tab when changing tabs.

#### v2.0.2 - 2019-01-16
* Added code to check for the page slug in the hook returned when loading styles to fix when a submenu page prefix changes the page hook.

#### v2.0.1 - 2018-09-13
* Added ability to skip an option or about tab by adding skip: true to the JSON

#### 1.0.7 - 2018-07-11
* Addition of the checkbox field type and validation.
* Addition of the multifield-text box that stores everything in array.


#### 1.0.6 - 2018-05-21
* Adjustments to appearance of certain elements in the CSS.

#### 1.0.5 - 2018-02-21
* Changed error display for inputs - it still shows the message beneath each input that has an error but also outputs all the errors at the top of the screen.

#### 1.0.4 - 2018-02-11
* Added function to create an Ajax form that allows a reset/deletion of all options in the database.

#### 1.0.3 - 2018-02-07
* Tab pages did not have a correct URL. Added a page_slug option to the JSON file which will fill in the URL to the tabs correctly.
* Fixed a bug where the first save of a new option array would not save because the hidden option key name was not being set in the validate function. (Actually the validate function is running twice in WP for some reason with this first save, stripping the hidden value.) Worked around this issue by making sure that value is set and if not, it pulls it from the POST data which should have it.

#### 1.0.2 - 2018-02-03
* Changed method of instantiation - now must pass an array containing 'json_file' with a path to the json file and 'register_settings' set to true or false. If any of these items are not passed the plugin will not register settings and will look for a JSON file in the same directory as the php file to load.

#### 1.0.1 - 2018-01-27
* Fixed an issue with the settings library to allow having hidden options.
* Fixed issue with the current settings function being called too many times.
