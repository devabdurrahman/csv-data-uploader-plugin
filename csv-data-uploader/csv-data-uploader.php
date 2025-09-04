<?php 
/*
* Plugin Name: CSV data uploader
* Author: Abdur Rahman
* Description: This plugin will upload CSV data to DB table
* Version: 1.0
* Plugin URI: devabdurrrahman.com
* Author URI: devabdurrrahman.com
*/
define("CDU_PLUGIN_DIR_PATH", plugin_dir_path(__FILE__));

add_shortcode('csv-data-uploader', 'cdu_display_uploader_form');
function cdu_display_uploader_form(){
	// start php buffer
	ob_start();

	include_once CDU_PLUGIN_DIR_PATH . "view/cdu_form.php";

	// read buffer

	$template = ob_get_contents();

	// clean buffer
	ob_get_clean();
	return $template;
}

// DB table on plugin activation
register_activation_hook( __FILE__,  "cdu_create_table");
function cdu_create_table(){
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$table_collate = $wpdb->get_charset_collate();
	$sql = "
		CREATE TABLE {$table_prefix}wp_students_data (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `name` varchar(120) DEFAULT NULL,
	  `email` varchar(80) DEFAULT NULL,
	  `phoneNo` varchar(50) DEFAULT NULL,
	  `gender` enum('male','female','other') DEFAULT NULL,
	  `designation` varchar(50) DEFAULT NULL,
	  PRIMARY KEY (`id`)
	) ".$table_collate." ";

	require_once(ABSPATH."wp-admin/includes/upgrade.php");

	dbDelta($sql);
}

// to add script file

add_action("wp_enqueue_scripts", "cdu_add_script_file");
function cdu_add_script_file(){
	wp_enqueue_script("cdu-script-js", plugin_dir_url(__FILE__) . "assets/script.js", array("jquery"), time(), true);
	wp_localize_script("cdu-script-js", "cdu_object", array(
		"ajax_url" => admin_url("admin-ajax.php")
	));
}

// capture ajax request

add_action("wp_ajax_cdu_submit_form", "cdu_ajax_handler");
add_action("wp_ajax_nopriv_cdu_submit_form", "cdu_ajax_handler");

function cdu_ajax_handler(){

	if($_FILES['csv-data-file']){

		$csvFile = $_FILES['csv-data-file']['tmp_name'];

		$handle = fopen($csvFile, "r");

		global $wpdb;

		$table_name = $wpdb->prefix."wp_students_data";


		if($handle){

			$row = 0;
			while(($data = fgetcsv($handle, 1000, ",")) !== false){

				if($row == 0){
					$row++;
					continue;
				}

				$wpdb->insert($table_name, array(
					"name" => $data[1],
					"email" => $data[2],
					"phoneNo" => $data[3],
					"gender" => $data[4],
					"designation" => $data[5]
				));

			}

			fclose($handle);

			echo json_encode([
				"status" => 1,
				"message" => "Data uploaded successfully"
			]);
		}


	}else{

		echo json_encode(array(
			"status" => 0,
			"message" => "No File Found"
		));

	}

	

	exit;
}
