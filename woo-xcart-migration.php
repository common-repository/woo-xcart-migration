<?php
/**
* @package MigrateXcartToWooCommerce
*/
/*
Plugin Name: Migrate Xcart to WooCommerce
Plugin URI: https://www.vgroupinc.com
Description: X-Cart to WooCommerce Migration Tool
Author: V Group Inc.
Version: 1.0.0
Author URI: https://profiles.wordpress.org/vgroup
*/

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.

Copyright 1999-2018 V Group Inc.
*/

if( ! defined( "ABSPATH" ) ) {
    die;
}

class WXM_Admin {
    private $error = '';

    /*
      Function Name: wxm_handle_pages
      Description: The function handles post requests on different import operations.
      Parameters: 
      Returns: 
    */
    function wxm_handle_pages() {
        if(isset($_POST['import_type'])) {
            switch (sanitize_text_field(esc_html($_POST['import_type']))) {
                case 'sync_product':
                    // get xcart database credentials.
                    global $table_prefix, $wpdb;
                    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
                        WHERE id = %d ', 1));
                    if(isset($result->host) && isset($result->db_name) && isset($result->db_user) && isset($result->db_pass) && isset($result->web_directory)) {
                        $xcart = new WXM_Sync($result);
                        $xcart->wxm_products();
                        break;
                    }
                    $this->wxm_db_connect();
                    exit;

                case 'sync_reviews':
                    // get xcart database credentials.
                    global $table_prefix, $wpdb;
                    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
                        WHERE id = %d ', 1));
                    if(isset($result->host) && isset($result->db_name) && isset($result->db_user) && isset($result->db_pass) && isset($result->web_directory)) {
                        $xcart = new WXM_Sync($result);
                        $xcart->wxm_product_reviews();
                        break;
                    }
                    $this->wxm_db_connect();
                    exit;
                    
                case 'sync_categories':
                    // get xcart database credentials.
                    global $table_prefix, $wpdb;
                    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
                        WHERE id = %d ', 1));
                    if(isset($result->host) && isset($result->db_name) && isset($result->db_user) && isset($result->db_pass) && isset($result->web_directory)) {
                        $xcart = new WXM_Sync($result);
                        $xcart->wxm_categories();
                        break;
                    }
                    $this->wxm_db_connect();
                    exit;
                
                case 'sync_attribute':
                    // get xcart database credentials.
                    global $table_prefix, $wpdb;
                    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
                        WHERE id = %d ', 1));
                    if(isset($result->host) && isset($result->db_name) && isset($result->db_user) && isset($result->db_pass) && isset($result->web_directory)) {
                        $xcart = new WXM_Sync($result);
                        $xcart->wxm_attribute();
                        break;
                    }
                    $this->wxm_db_connect();
                    exit;

                case 'sync_image':
                    // get xcart database credentials.
                    global $table_prefix, $wpdb;
                    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
                        WHERE id = %d ', 1));
                    if(isset($result->host) && isset($result->db_name) && isset($result->db_user) && isset($result->db_pass) && isset($result->web_directory)) {
                        $xcart = new WXM_Sync($result);
                        $xcart->wxm_product_images();
                        break;
                    }
                    $this->wxm_db_connect();
                    exit;                    

                case 'sync_users':
                    // get xcart database credentials.
                    global $table_prefix, $wpdb;
                    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
                        WHERE id = %d ', 1));
                    if(isset($result->host) && isset($result->db_name) && isset($result->db_user) && isset($result->db_pass) && isset($result->web_directory)) {
                        $xcart = new WXM_Sync($result);
                        $xcart->wxm_sync_users();
                        break;
                    } 
                    $this->wxm_db_connect();
                    exit;

                case 'sync_order':
                    // get xcart database credentials.
                    global $table_prefix, $wpdb;
                    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
                        WHERE id = %d ', 1));
                    if(isset($result->host) && isset($result->db_name) && isset($result->db_user) && isset($result->db_pass) && isset($result->web_directory)) {
                        $xcart = new WXM_Sync($result);
                        $xcart->wxm_import_order();
                        break;
                    }
                    $this->wxm_db_connect();
                    exit;

                case 'product_cron':
                    // get xcart database credentials.
                	wxm_products_cron_job();
                    break;
                    $this->wxm_db_connect();
                    exit;
            }
        }
    }

    /*
      Function Name: main
      Description: The function load the default plugin home page.
      Parameters: 
      Returns: 
    */
    function main() {
        if(!empty($_POST['host']) && !empty($_POST['dbname']) && !empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['web_directory'])) {
            try {
                    global $table_prefix, $wpdb;
                    $safe_username = $_POST['username'];
                    if ( strlen( $safe_username ) > 30 ) {
					  $safe_username = sanitize_text_field(esc_html(substr( $safe_username, 0, 30 )));
					}
					$safe_password = $_POST['password'];
                    if ( strlen( $safe_password ) > 30 ) {
					  $safe_password = sanitize_text_field(esc_html(substr( $safe_password, 0, 30 )));
					}
					$safe_dbname = $_POST['dbname'];
                    if ( strlen( $safe_dbname ) > 30 ) {
					  $safe_dbname = sanitize_text_field(esc_html(substr( $safe_dbname, 0, 30 )));
					}
					$safe_host = $_POST['host'];
                    if ( strlen( $safe_host ) > 25 ) {
					  $safe_host = sanitize_text_field(esc_html(substr( $safe_host, 0, 25 )));
					}
					$safe_web_directory = $_POST['web_directory'];
                    if ( strlen( $safe_web_directory ) > 80 ) {
					  $safe_web_directory = sanitize_text_field(esc_html(substr( $safe_web_directory, 0, 80 )));
					}
                    $conn = new wpdb($safe_username, $safe_password, $safe_dbname, $safe_host);
                    $conn->show_errors();

                    // make a database call to check if connection is successfull.
                    $result = $wpdb->get_results('SELECT COUNT(id) count FROM '.$table_prefix.'xcart_database WHERE id = 1 ');
                    if(count($result)) {
                        // update operation.
                        $dataArray = array(
                            'host' => $safe_host,
                            'db_name' => $safe_dbname,
                            'db_user' => $safe_username,
                            'db_pass' => $safe_password,
                            'web_directory' => $safe_web_directory
                        );
                        $where = array(
                            'id' => 1
                        );
                        $format = array(
							'%s',
							'%s',
							'%s',
							'%s',
							'%s'
						);
						$whereFormat = array(
							'%d'
						);
                        global $table_prefix, $wpdb;
                        $wpdb->update($table_prefix.'xcart_database', $dataArray, $where, $format, $whereFormat);

                        ?>
                        <div class="notice notice-success is-dismissible">
                            <p><strong>Database settings saved successfully.</strong></p>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="notice notice-error is-dismissible"> 
                            <p><strong>Error connecting to database.</strong></p>
                        </div>
                        <?php
                    }                    
                } catch(Exception $e) {
                    ?>
                    <div class="notice notice-error is-dismissible"> 
                        <p><strong>Error connecting to database.</strong></p>
                    </div>
                    <?php
                }
                
            }

        $this->wxm_handle_pages();
        
            ?>
            <div class="wrap">
                <h2>
                    X-Cart to WooCommerce Migration
                </h2><br/>
                    <?php if ($this->error !== '') : ?>
                        <div class="notice notice-error is-dismissible">
                            <p><strong><?php echo $this->error; ?></strong></p>
                        </div>
                    <?php endif; ?>
                <form class="add:the-list: validate" method="post" enctype="multipart/form-data">
                    <p>
                    <div id="formatdiv" class="postbox" style="display: block;">
                        <h3 class="hndle" style="cursor:auto;padding:10px;">
                            <span>
                                Select below data type to import X-Cart data
                            </span>
                        </h3>
                        <div class="inside">
                            <div id="post-formats-select">

                            	<?php
                            	$category_synced_at = '';
                            	// get last synced status
                            	global $table_prefix, $wpdb;
						        /*$query = 'SELECT synced_at
						            FROM wp_xcart_lastSynced 
						            WHERE term = "category"
						            ORDER BY id DESC
						            LIMIT 1 ';*/
						        $data = $wpdb->get_row($wpdb->prepare('SELECT synced_at
                                    FROM '.$table_prefix.'xcart_lastSynced 
                                    WHERE term = %s
                                    ORDER BY id DESC
                                    LIMIT 1 ', "category"));
						        if( !empty($data) && count($data) ) {
						        	$category_synced_at = $data->synced_at;
						        }
                            	?>
                                
                                <label>
                                    <input class="post-format selectOption" data-value="categories" type="radio" value="sync_categories" name="import_type" checked> 
                                    Product Categories <?=(!empty($category_synced_at))?' (Last Imported: '.$category_synced_at.')':''; ?>
                                </label>
                                <br>

                                <?php
                            	$attr_synced_at = '';
                            	// get last synced status
                            	global $table_prefix, $wpdb;
						        /*$query = 'SELECT synced_at
						            FROM wp_xcart_lastSynced 
						            WHERE term = "attribute"
						            ORDER BY id DESC
						            LIMIT 1 ';*/

						        $data = $wpdb->get_row($wpdb->prepare('SELECT synced_at
                                    FROM '.$table_prefix.'xcart_lastSynced 
                                    WHERE term = %s
                                    ORDER BY id DESC
                                    LIMIT 1 ', "attribute"));
						        if( !empty($data) && count($data) ) {
						        	$attr_synced_at = $data->synced_at;
						        }
                            	?>
                                
                                <!-- <label>
                                    <input class="post-format selectOption" data-value="attributes" type="radio" value="sync_attribute" name="import_type">
                                    Attributes <?=(!empty($attr_synced_at))?' (Last Imported: '.$attr_synced_at.')':''; ?>
                                </label>  
                                <br> -->

                                <?php
                                // check if cronjob is running.
                                global $table_prefix, $wpdb;

                                $countCron = 'SELECT COUNT(xc.id)
						        FROM '.$table_prefix.'xcart_cronjob xc
						        WHERE status = 1';
						        $cron_running = $wpdb->get_var($countCron);

						        if($cron_running) {
						        	?>
                                
	                                <label>
	                                    <input class="post-format selectOption" data-value="products" type="radio" value="sync_product" name="import_type" disabled>
	                                    Products (Importing Products. Please wait.)
	                                </label>
	                                <br>

	                                <?php
						        } else {
						        	$product_synced_at = '';
	                            	// get last synced status
							        $data = $wpdb->get_row($wpdb->prepare('SELECT synced_at
	                                    FROM '.$table_prefix.'xcart_lastSynced 
	                                    WHERE term = %s
	                                    ORDER BY id DESC
	                                    LIMIT 1 ', "product"));
							        if( !empty($data) && count($data) ) {
							        	$product_synced_at = $data->synced_at;
							        }
	                            	?>
	                                
	                                <label>
	                                    <input class="post-format selectOption" data-value="products" type="radio" value="sync_product" name="import_type">
	                                    Products <?=(!empty($product_synced_at))?' (Last Imported: '.$product_synced_at.')':''; ?>
	                                </label>
	                                <br>

	                                <?php
						        }
                            	
                            	$review_synced_at = '';
                            	// get last synced status
                            	global $table_prefix, $wpdb;
						        /*$query = 'SELECT synced_at
						            FROM wp_xcart_lastSynced 
						            WHERE term = "review"
						            ORDER BY id DESC
						            LIMIT 1 ';*/

						        $data = $wpdb->get_row($wpdb->prepare('SELECT synced_at
                                    FROM '.$table_prefix.'xcart_lastSynced 
                                    WHERE term = %s
                                    ORDER BY id DESC
                                    LIMIT 1 ', "review"));
						        if( !empty($data) && count($data) ) {
						        	$review_synced_at = $data->synced_at;
						        }
                            	?>

                                <label>
                                    <input class="post-format selectOption" data-value="reviews" type="radio" value="sync_reviews" name="import_type">
                                    X-Cart Reviews <?=(!empty($review_synced_at))?' (Last Imported: '.$review_synced_at.')':''; ?>
                                </label>
                                <br>

                                <?php
                            	$image_synced_at = '';
                            	// get last synced status
                            	global $table_prefix, $wpdb;
						        /*$query = 'SELECT synced_at
						            FROM wp_xcart_lastSynced 
						            WHERE term = "image"
						            ORDER BY id DESC
						            LIMIT 1 ';*/

						        $data = $wpdb->get_row($wpdb->prepare('SELECT synced_at
                                    FROM '.$table_prefix.'xcart_lastSynced 
                                    WHERE term = %s
                                    ORDER BY id DESC
                                    LIMIT 1 ', "image"));
						        if( !empty($data) && count($data) ) {
						        	$image_synced_at = $data->synced_at;
						        }
                            	?>
                                
                                <!-- <label>
                                    <input class="post-format selectOption" data-value="images" type="radio" value="sync_image" name="import_type">
                                    Product Images <?=(!empty($image_synced_at))?' (Last Imported: '.$image_synced_at.')':''; ?>
                                </label>
                                <br> -->

                                <?php
                            	$user_synced_at = '';
                            	// get last synced status
                            	global $table_prefix, $wpdb;
						        /*$query = 'SELECT synced_at
						            FROM wp_xcart_lastSynced 
						            WHERE term = "user"
						            ORDER BY id DESC
						            LIMIT 1 ';*/

						        $data = $wpdb->get_row($wpdb->prepare('SELECT synced_at
                                    FROM '.$table_prefix.'xcart_lastSynced 
                                    WHERE term = %s
                                    ORDER BY id DESC
                                    LIMIT 1 ', "user"));
						        if( !empty($data) && count($data) ) {
						        	$user_synced_at = $data->synced_at;
						        }
                            	?>

                                <label>
                                    <input class="post-format selectOption" data-value="users" type="radio" value="sync_users" name="import_type" >
                                    Customers <?=(!empty($user_synced_at))?' (Last Imported: '.$user_synced_at.')':''; ?>
                                </label>
                                <br>

                                <?php
                            	$order_synced_at = '';
                            	// get last synced status
                            	global $table_prefix, $wpdb;
						        /*$query = 'SELECT synced_at
						            FROM wp_xcart_lastSynced 
						            WHERE term = "order"
						            ORDER BY id DESC
						            LIMIT 1 ';*/

						        $data = $wpdb->get_row($wpdb->prepare('SELECT synced_at
                                    FROM '.$table_prefix.'xcart_lastSynced 
                                    WHERE term = %s
                                    ORDER BY id DESC
                                    LIMIT 1 ', "order"));
						        if( !empty($data) && count($data) ) {
						        	$order_synced_at = $data->synced_at;
						        }
                            	?>
                                
                                <label>
                                    <input class="post-format selectOption" data-value="orders" type="radio" value="sync_order" name="import_type">
                                    Orders <?=(!empty($order_synced_at))?' (Last Imported: '.$order_synced_at.')':''; ?>
                                </label>
                                <br>

                                <!-- <label>
                                    <input class="post-format selectOption" type="radio" value="product_cron" name="import_type">
                                    Run Products Cronjob
                                </label>
                                <br> -->

                                <p class="submit">
                                    <input type="submit" class="button" name="submit" value="Submit" />
                                </p>
                            </div>
                            
                            <input name="_csv_import_files_next" type="hidden" value="next" />
                        </div>
                    </div>
                    </p>                    
                    
                </form>
            </div>

            <?php
        
    }


    /*
      Function Name: wxm_db_connect
      Description: This function loads the setting page as well as handle post requests on settings page. 
      Parameters: 
      Returns: 
    */
    function wxm_db_connect() {
        if(!empty($_POST['host']) && !empty($_POST['dbname']) && !empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['web_directory'])) {
                try {
                    global $table_prefix, $wpdb;
                    $safe_username = $_POST['username'];
                    if ( strlen( $safe_username ) > 30 ) {
					  $safe_username = sanitize_text_field(esc_html(substr( $safe_username, 0, 30 )));
					}
					$safe_password = $_POST['password'];
                    if ( strlen( $safe_password ) > 30 ) {
					  $safe_password = sanitize_text_field(esc_html(substr( $safe_password, 0, 30 )));
					}
					$safe_dbname = $_POST['dbname'];
                    if ( strlen( $safe_dbname ) > 30 ) {
					  $safe_dbname = sanitize_text_field(esc_html(substr( $safe_dbname, 0, 30 )));
					}
					$safe_host = $_POST['host'];
                    if ( strlen( $safe_host ) > 25 ) {
					  $safe_host = sanitize_text_field(esc_html(substr( $safe_host, 0, 25 )));
					}
					$safe_web_directory = $_POST['web_directory'];
                    if ( strlen( $safe_web_directory ) > 80 ) {
					  $safe_web_directory = sanitize_text_field(esc_html(substr( $safe_web_directory, 0, 80 )));
					}
                    $conn = new wpdb($safe_username, $safe_password, $safe_dbname, $safe_host);
                    $conn->show_errors();

                    // make a database call to check if connection is successfull.
                    $result = $wpdb->get_results('SELECT COUNT(id) count FROM '.$table_prefix.'xcart_database WHERE id = 1 ');
                    if(count($result)) {
                        // update operation.
                        $dataArray = array(
                            'host' => $safe_host,
                            'db_name' => $safe_dbname,
                            'db_user' => $safe_username,
                            'db_pass' => $safe_password,
                            'web_directory' => $safe_web_directory
                        );
                        $where = array(
                            'id' => 1
                        );
                        $format = array(
							'%s',
							'%s',
							'%s',
							'%s',
							'%s'
						);
						$whereFormat = array(
							'%d'
						);
                        global $table_prefix, $wpdb;
                        $wpdb->update($table_prefix.'xcart_database', $dataArray, $where, $format, $whereFormat);

                        ?>
                        <div class="notice notice-success is-dismissible">
                            <p><strong>Database settings saved successfully.</strong></p>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="notice notice-error is-dismissible"> 
                            <p><strong>Error connecting to database.</strong></p>
                        </div>
                        <?php
                    }                    
                } catch(Exception $e) {
                    ?>
                    <div class="notice notice-error is-dismissible"> 
                        <p><strong>Error connecting to database.</strong></p>
                    </div>
                    <?php
                }
                
        }            
        ?>
        <div class="wrap">
            <h2>
                X-Cart to WooCommerce Migration
            </h2><br/>
                <?php if ($this->error !== '') : ?>
                <div class="notice notice-error is-dismissible"> 
                    <p><strong><?php echo $this->error; ?></strong></p>
                </div>
                <?php endif; ?>
            <form class="add:the-list: validate" method="post" enctype="multipart/form-data">
                <p>
                <div id="formatdiv" class="postbox" style="display: block;">
                    <h3 class="hndle" style="cursor:auto;padding:10px;">
                        <span>
                            X-Cart Database Connect
                        </span>
                    </h3>

                    <?php
                    // get xcart database credentials.
                    global $table_prefix, $wpdb;
            		$result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
                		WHERE id = %d ', 1));
                    ?>
                    <div class="inside">
                        <div>
                            <label>Database Host</label><br>
                            <input maxlength="25" type="text" name="host" required value="<?=(isset($result->host))?esc_html($result->host):''?>">
                        </div>
                        <div>
                            <label>Database Name</label><br>
                            <input maxlength="25" type="text" name="dbname" required value="<?=(isset($result->db_name))?esc_html($result->db_name):''?>">
                        </div>
                        <div>
                            <label>Database User</label><br>
                            <input maxlength="30" type="text" name="username" required value="<?=(isset($result->db_user))?esc_html($result->db_user):''?>">
                        </div>
                        <div>
                            <label>Database Password</label><br>
                            <input maxlength="30" type="password" name="password" required value="<?=(isset($result->db_pass))?esc_html($result->db_pass):''?>">
                        </div>

                        <div>
                            <label>Web Directory</label><br>
                            <input maxlength="80" type="text" name="web_directory" required value="<?=(isset($result->web_directory))?esc_html($result->web_directory):''?>">
                            <div class="tooltip"> <img src="<?=plugin_dir_url().'woo-xcart-migration/inc/question-mark-16x16.png'?>">
    						  <span class="tooltiptext">Web directory is the store-front URL of your X-Cart site, necessary for migration of images.</span>
    						</div>
                        </div>

                        <p class="submit">
                            <input type="submit" class="button" name="submit" value="Save" />
                        </p>
                    </div>
                </div>
                </p>            
            </form>
        </div>
        <?php
    }

    /*
      Function Name: wxm_operation_weight_status
      Description: The function is returning weight of operation which is being performed by the user. 
      Parameters: The function accept term (machine name) of the operation.
      Returns: It returns an arrya of term objects in case the term exist in table or return false.
    */
    function wxm_operation_weight_status($term) {
        global $wpdb, $table_prefix;
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_operation_weight
            WHERE term = %s ', $term));

        return(count($result))?json_encode($result):false;  
    }
    

} // end class.

// The schedule filter hook
function wxm_add_every_minute( $schedules ) {
    $schedules['every_minute'] = array(
            'interval'  => 60,
            'display'   => __( 'Every Minute', 'textdomain' )
    );
    return $schedules;
}

add_filter( 'cron_schedules', 'wxm_add_every_minute' );

/*
  Function Name: wxm_products_cron_job
  Description: The function is calling cron function in WXM_Sync class to import products from cron. 
  Parameters: 
  Returns: 
*/
function wxm_products_cron_job() {
	require_once( plugin_dir_path(__FILE__) . 'sync.php' );
	global $table_prefix, $wpdb;
    $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
        WHERE id = %d ', 1));
    if(count($result)) {
    	$xcart = new WXM_Sync($result);
    	$xcart->wxm_products_cron();
    }
}
// hook that function onto our scheduled event:
add_action ('productcronjob', 'wxm_products_cron_job');


/*
  Function Name: wxm_migration_install
  Description: The function fires when the plugin is activated and create custom plugin tables in wordpress database. 
  Parameters:
  Returns: 
*/
function wxm_migration_install() {
    // create relation table.
    global $table_prefix, $wpdb;
    $tblname = 'xcart_relation';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) != $wp_track_table) {
       $sql = "CREATE TABLE `" . $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `term`  varchar(150) NOT NULL, ";
        $sql .= "  `wp_id`  int(11) NOT NULL, ";
        $sql .= "  `xcart_id`  int(11) NOT NULL, ";
        $sql .= "  PRIMARY KEY (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
    
    $tblname = 'xcart_lastSynced';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) != $wp_track_table) {
       $sql = "CREATE TABLE `" . $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `term`  varchar(150) NOT NULL, ";
        $sql .= "  `synced_at`  varchar(150) NULL, ";
        $sql .= "  PRIMARY KEY (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    $tblname = 'xcart_cronjob';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) != $wp_track_table) {
       $sql = "CREATE TABLE `" . $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `term`  varchar(150) NOT NULL, ";
        $sql .= "  `start_limit`  int(11) DEFAULT 0, ";
        $sql .= "  `end_limit`  int(11) DEFAULT 0, ";
        $sql .= "  `totalrecords`  int(11) DEFAULT 0, ";
        $sql .= "  `status`  int(11) DEFAULT 0, ";
        $sql .= "  PRIMARY KEY (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    $tblname = 'xcart_operation_weight';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) != $wp_track_table) {
       $sql = "CREATE TABLE `" . $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11) NOT NULL AUTO_INCREMENT, ";
        $sql .= "  `term`  varchar(150) NOT NULL, ";
        $sql .= "  `depends_on`  varchar(250) NULL, ";
        $sql .= "  `status`  int(11) DEFAULT 0, ";
        $sql .= "  PRIMARY KEY (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);

        $sqlInsert = " INSERT INTO ". $wp_track_table ." ";
        $sqlInsert .= " (id,term,depends_on,status) ";
        $sqlInsert .= " VALUES (default, 'categories', null, default), ";
        $sqlInsert .= " (default, 'attributes', null,default), ";
        $sqlInsert .= " (default, 'products', 'Categories',default), ";
        $sqlInsert .= " (default, 'reviews', 'Products',default), ";
        $sqlInsert .= " (default, 'images', 'Products',default), ";
        $sqlInsert .= " (default, 'customers', null,default), ";
        $sqlInsert .= " (default, 'orders', 'Products,Customers',default), ";
        $sqlInsert .= " (default, 'attach_attribute', 'product,attribute',default); ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        //error_log($sqlInsert);
        dbDelta($sqlInsert);
    }

    $tblname = 'xcart_database';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) != $wp_track_table) {
       $sql = "CREATE TABLE `" . $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11) NOT NULL, ";
        $sql .= "  `encryption_key`  varchar(150) NOT NULL, ";
        $sql .= "  `host`  varchar(150) NULL, ";
        $sql .= "  `db_name`  varchar(255) NULL, ";
        $sql .= "  `db_user`  varchar(255) NULL, ";
        $sql .= "  `db_pass`  varchar(255) NULL, ";
        $sql .= "  `web_directory`  text NULL ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);

        $sqlInsert = " INSERT INTO ". $wp_track_table ." ";
        $sqlInsert .= " (id,encryption_key,host,db_name,db_pass, web_directory) ";
        $sqlInsert .= " VALUES (1, '!@#$%^&*', null, null, null, null); ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        //error_log($sqlInsert);
        dbDelta($sqlInsert);
    }

    // register cronjob.
    if( !wp_next_scheduled( 'productcronjob' ) ) {
	   wp_schedule_event( time(), 'every_minute', 'productcronjob' );
	}

}
register_activation_hook(__FILE__, 'wxm_migration_install');

/*
  Function Name: wxm_migration_uninstall
  Description: The function fires when the plugin is deactivated and deletes custom plugin tables from wordpress database created when the plugin was activated. 
  Parameters:
  Returns: 
*/
function wxm_migration_uninstall() {
    // drop tables created by plugin.
    global $table_prefix, $wpdb;
    $tblname = 'xcart_relation';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) == $wp_track_table) {
        $sql = "DROP TABLE `" . $wp_track_table . "` ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
    
    $tblname = 'xcart_lastSynced';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) == $wp_track_table) {
        $sql = "DROP TABLE `" . $wp_track_table . "` ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    $tblname = 'xcart_cronjob';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) == $wp_track_table) {
        $sql = "DROP TABLE `" . $wp_track_table . "` ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    $tblname = 'xcart_operation_weight';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) == $wp_track_table) {
        $sql = "DROP TABLE `" . $wp_track_table . "` ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    $tblname = 'xcart_database';
    $wp_track_table = $table_prefix . "$tblname";
    if ($wpdb->get_var($wpdb->prepare("show tables like %s",$wp_track_table)) == $wp_track_table) {
        $sql = "DROP TABLE `" . $wp_track_table . "` ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    // deregister cronjob.
    if( wp_next_scheduled( 'productcronjob' ) ){
        wp_clear_scheduled_hook( 'productcronjob' );
    }
}
register_uninstall_hook(__FILE__, 'wxm_migration_uninstall');

/*
  Function Name: wxm_admin_actions
  Description: The function add admin action tabs for the plugin. 
  Parameters:
  Returns: 
*/
function wxm_admin_actions() {
    $plugin = new WXM_Admin;
    add_menu_page("X-Cart Migration", "X-Cart Migration", "manage_options", "xcart_migration", array($plugin, 'main'), 'dashicons-cart' , 50);
    add_submenu_page('xcart_migration', "Settings", "Settings", "manage_options", "wxm_db_conn", array($plugin, 'wxm_db_connect'));
    //add_submenu_page('xcart_migration', "User Guide", "User Guide", "manage_options", "xcart_user_guide", array($plugin, 'xcart_user_guide'));
}
add_action('admin_menu', 'wxm_admin_actions');

/*  
  Description: Including plugin's file if admin is logged in.
*/
if (is_admin()) {
   require_once( plugin_dir_path(__FILE__) . 'sync.php' );
}


/*
  Hook Name: before_delete_post
  Description: The hook fires before a wordpress post (product/order) get deleted, plugin's instance for this post is also deleted. 
*/
add_action('before_delete_post', function($id) {
    global $post_type;

    switch ($post_type) {

        case 'shop_order':
            global $wpdb, $table_prefix;
            $format = array(
				'%d',
				'%s'
			);
            $wpdb->delete($table_prefix. 'xcart_relation', array( 'wp_id' => $id, 'term' => 'order' ), $format );

            // update operation weight table.
            $weightArray = array(
                'status' => 0
            );
            $where = array(
                'term' => 'order'
            );
            $format = array(
				'%d'
			);
			$whereFormat = array(
				'%s'
			);
            global $wpdb, $table_prefix;
            $wpdb->update( $table_prefix. 'xcart_operation_weight' ,$weightArray, $where, $format, $whereFormat);

            break;

        case 'product':
            global $wpdb, $table_prefix;
            $format = array(
				'%d',
				'%s'
			);
            $wpdb->delete( $table_prefix. 'xcart_relation', array( 'wp_id' => $id, 'term' => 'product' ), $format);

            // update operation weight table.
            $weightArray = array(
                'status' => 0
            );
            $where = array(
                'term' => 'products'
            );
            $format = array(
				'%d'
			);
			$whereFormat = array(
				'%s'
			);
            global $wpdb, $table_prefix;
            $wpdb->update( $table_prefix. 'xcart_operation_weight' ,$weightArray, $where, $format, $whereFormat);

            break;
        
        default:
            # code...
            break;
    }    
    
}, 10, 1);

/*
  Hook Name: deleted_user
  Description: The hook fires when a wordpress user get deleted, plugin's instance for this user is also deleted. 
*/
add_action('deleted_user', function($id) {

    global $wpdb, $table_prefix;
    $format = array(
		'%d',
		'%s'
	);
    $wpdb->delete( $table_prefix. 'xcart_relation', array( 'wp_id' => $id, 'term' => 'user' ), $format);

    // update operation weight table.
    $weightArray = array(
        'status' => 0
    );
    $where = array(
        'term' => 'users'
    );
    $format = array(
		'%d'
	);
	$whereFormat = array(
		'%s'
	);
    global $wpdb, $table_prefix;
    $wpdb->update($table_prefix.'xcart_operation_weight' ,$weightArray, $where, $format, $whereFormat);
    
}, 10, 1);

/*
  Hook Name: deleted_term_taxonomy
  Description: The hook fires when a wordpress taxonomy get deleted, plugin's instance for this taxonomy is also deleted. 
*/
add_action('deleted_term_taxonomy', function($id) {

    global $wpdb, $table_prefix;
    $format = array(
		'%d',
		'%s'
	);
    $wpdb->delete( $table_prefix.'xcart_relation', array( 'wp_id' => $id, 'term' => 'attribute' ), $format );

    // update operation weight table.
    $weightArray = array(
        'status' => 0
    );
    $where = array(
        'term' => 'attributes'
    );
    $format = array(
		'%d'
	);
	$whereFormat = array(
		'%s'
	);
    global $wpdb, $table_prefix;
    $wpdb->update($table_prefix.'xcart_operation_weight' ,$weightArray, $where, $format, $whereFormat);
    
}, 10, 1);

/*
  Hook Name: delete_term
  Description: The hook fires when a wordpress term get deleted, plugin's instance for this term is also deleted. 
*/
add_action('delete_term', function($term) {
    global $wpdb, $table_prefix;
    $format = array(
		'%s',
		'%s'
	);

    $wpdb->delete( $table_prefix.'xcart_relation', array( 'wp_id' => $term, 'term' => 'category' ), $format );

    // update operation weight table.
    $weightArray = array(
        'status' => 0
    );
    $where = array(
        'term' => 'categories'
    );
    $format = array(
		'%d'
	);
	$whereFormat = array(
		'%s'
	);
    global $wpdb, $table_prefix;
    $wpdb->update($table_prefix.'xcart_operation_weight' ,$weightArray, $where, $format, $whereFormat);
    $format = array(
		'%s',
		'%s'
	);
    $wpdb->delete($table_prefix. 'xcart_relation', array( 'wp_id' => $term, 'term' => 'attribute-term' ), $format );

    // update operation weight table.
    $weightArray = array(
        'status' => 0
    );
    $where = array(
        'term' => 'attributes'
    );
    $format = array(
		'%d'
	);
	$whereFormat = array(
		'%s'
	);

    global $wpdb, $table_prefix;
    $wpdb->update($table_prefix.'xcart_operation_weight' ,$weightArray, $where, $format, $whereFormat);
    
}, 10, 1);

/*
  Hook Name: delete_comment
  Description: The hook fires when a wordpress comment get deleted, plugin's instance for this comment is also deleted. 
*/
do_action( 'delete_comment', function($comment_id ) {
    global $wpdb, $table_prefix;
    $format = array(
		'%d',
		'%s'
	);
    $wpdb->delete( $table_prefix.'xcart_relation', array( 'wp_id' => $comment_id, 'term' => 'comment' ), $format );

    // update operation weight table.
    $weightArray = array(
        'status' => 0
    );
    $where = array(
        'term' => 'reviews'
    );
    $format = array(
		'%d'
	);
	$whereFormat = array(
		'%s'
	);
    global $wpdb, $table_prefix;    
    $wpdb->update($table_prefix.'xcart_operation_weight' ,$weightArray, $where, $format, $whereFormat);
    
}, 10, 1);

/*
  Hook Name: deleted_post
  Description: The hook fires when a wordpress post get deleted, plugin's instance for comments associated to this post is also deleted.
*/
do_action( 'deleted_post', function($post_id ) {
    global $wpdb, $table_prefix;
    $query = 'SELECT comment_ID
		FROM '.$table_prefix.'comments 
		WHERE comment_post_ID = %d ';

	$commentIds = $wpdb->get_results($wpdb->prepare($query, $post_id));
	if(count($commentIds)) {
		foreach ($commentIds as $key => $commentId) {
			$commentId = $commentId->comment_ID;
			// delete if found relation table.
			$format = array(
				'%d',
				'%s'
			);
			global $wpdb, $table_prefix;
			$wpdb->delete($table_prefix. 'xcart_relation', array( 'wp_id' => $commentId, 'term' => 'comment' ), $format );
		}
	}

    // update operation weight table.
    $weightArray = array(
        'status' => 0
    );
    $where = array(
        'term' => 'reviews'
    );
    $format = array(
		'%d'
	);
	$whereFormat = array(
		'%s'
	);
    global $wpdb, $table_prefix;
    $wpdb->update($table_prefix.'xcart_operation_weight' ,$weightArray, $where, $format, $whereFormat);
    
}, 10, 1);

/*
  Function Name: wxm_load_scripts
  Description: The function load plugin's CSS and JS files. 
  Parameters:
  Returns: 
*/
function wxm_load_scripts() {
	wp_enqueue_style( "xcart", plugin_dir_url( __FILE__ ) . '/inc/css/xcart.css' ); 

    // load our jquery file that sends the $.post request
    wp_enqueue_script( "xcart", plugin_dir_url( __FILE__ ) . '/inc/js/xcart.js', array( 'jquery' ) );
 
    // make the ajaxurl var available to the above script
    wp_localize_script( 'xcart', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );  
}
add_action('wp_print_scripts', 'wxm_load_scripts');

/*
  Function Name: wxm_get_weight_status
  Description: The function handle ajax request fires on import options change event, checks options status and return message to appear in popup(if any). 
  Parameters:
  Returns: 
*/
function wxm_get_weight_status() {
    // first check if data is being sent and that it is the data we want
    if ( isset( $_POST["post_var"] ) ) {
        // now set our response var equal to that of the POST var (this will need to be sanitized based on what you're doing with with it)
        $option = sanitize_text_field(esc_html($_POST["post_var"]));
        //echo 'your option is '.$option;
        global $wpdb, $table_prefix;
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_operation_weight
            WHERE term = %s ', $option));
        $data = $result;
        if(count($data)) {
        	// chech if this option has any dependency.
        	if(!is_null($data->depends_on)) {
        		$dependantsArray = explode(',', $data->depends_on);
        		if(count($dependantsArray)) {
        			$notSyncedDependants = [];
        			foreach ($dependantsArray as $key => $dependant) {
        				// get dependant's status.
        				global $wpdb, $table_prefix;
				        $result = $wpdb->get_row($wpdb->prepare('SELECT term, status FROM '.$table_prefix.'xcart_operation_weight
				            WHERE term = %s ', $dependant));
				        if($result->status == 0) {
				        	array_push($notSyncedDependants, $dependant);
				        }
        			}
        			if(count($notSyncedDependants)) {
        				// if dependant count is one.
        				if(count($notSyncedDependants) == 1) {
        					echo "Please sync ".$notSyncedDependants[0]." before syncing ".ucfirst($option);
        				}elseif(count($notSyncedDependants) == 2) {
        					echo "Please sync ".$notSyncedDependants[0]." and ". $notSyncedDependants[1] ." before syncing ".ucfirst($option);
        				} elseif(count($notSyncedDependants) == 3) {
        					echo "Please sync ".$notSyncedDependants[0].", ". $notSyncedDependants[1] ." and ". $notSyncedDependants[2] ." before syncing ".ucfirst($option);
        				}
        			} else {
                        echo 0;
                    }
        		}
        	}
        }        
        die();
    }
}
add_action( 'wp_ajax_wxm_get_weight_status', 'wxm_get_weight_status' );
add_action( 'wp_ajax_nopriv_wxm_get_weight_status', 'wxm_get_weight_status' );

/*
  Function Name: wxm_append_store_order_number
  Description: The function fires when a WooCommerce order is created, X-Cart order number is being appended to WooCommerce order number. 
  Parameters:
  Returns: 
*/
function wxm_append_store_order_number( $order_id ) {
    // get xcart order id.
    $xcartOrderId = get_post_meta( $order_id, 'Xcart Order Number', true );
    if(!empty($xcartOrderId)) {
        $suffix = '/Xcart_'.$xcartOrderId.'/';
        $new_order_id = $order_id . $suffix;
        return $new_order_id;
    }
}
add_filter( 'woocommerce_order_number', 'wxm_append_store_order_number' );
