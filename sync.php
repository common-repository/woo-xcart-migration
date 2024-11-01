<?php
class WXM_Sync {
    public $param;
    private $xcartdbhost, $xcartdbuser, $xcartdbpass, $xcartdbname, $xcartDB;

    public function __construct($param) {
        if (isset($param)) {
            global $wpdb;
            $this->xcartdbhost = $param->host;
            $this->xcartdbuser = $param->db_user;
            $this->xcartdbpass = $param->db_pass;
            $this->xcartdbname = $param->db_name;
            $this->xcartDB = $this->wxm_db_conn();
        }
    }

    /*
      Function Name: wxm_db_conn
      Description: The function create connection to X-Cart database.
      Parameters:
      Returns: 
    */
    private function wxm_db_conn() {
        $conn = new wpdb($this->xcartdbuser, $this->xcartdbpass, $this->xcartdbname, $this->xcartdbhost);
        if (!$conn) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Error establishing connection to database.</strong></p>
            </div>
            <?php
        }
        return $conn;
    }

    /*
      Function Name: wxm_sync_users
      Description: The function get users from X-Cart database and create/update users(role:customers) in wordpress.
      Parameters:
      Returns: 
    */
    public function wxm_sync_users() {
        $result = $this->wxm_get_users();

        $new = 0;
        $update = 0;
        $created = [];
        $updated = [];
        if (!empty($result) && count($result)) {
            foreach ($result as $key => $xcart_user) {
                $wpUserIds = $this->wxm_get_wp_ids_from_xcart_ids('user', [$xcart_user->profile_id]);
                if (count($wpUserIds)) {
                    // update user
                    $wp_user_id = $wpUserIds[0];
                    $userdata = array(
                        'ID' => $wp_user_id,
                        'user_login' => $xcart_user->login,
                        'user_email' => $xcart_user->login,
                        'role' => 'customer'
                    );


                    $user_id = wp_update_user($userdata);

                    //On success
                    if (!is_wp_error($user_id)) {
                        if (count($updated) < 10) {
                            array_push($updated, $xcart_user->login);
                        }
                        $update++;
                    }
                } else {

                    // insert user.
                    $userdata = array(
                        'user_login' => $xcart_user->login,
                        'user_email' => $xcart_user->login,
                        'user_pass' => NULL, // When creating an user, `user_pass` is expected.
                        'role' => 'customer'
                    );

                    $user_id = wp_insert_user($userdata);

                    //On success
                    if (!is_wp_error($user_id)) {
                        if (count($created) < 10) {
                            array_push($created, $xcart_user->login);
                        }
                        $new++;

                        // insert into relation table.
                        $relationArray = array(
                            'term' => 'user',
                            'wp_id' => $user_id,
                            'xcart_id' => $xcart_user->profile_id
                        );
                        $format = array(
                            '%s',
                            '%d',
                            '%d'
                        );
                        global $table_prefix, $wpdb;
                        $wpdb->insert($table_prefix.'xcart_relation', $relationArray, $format);
                        
                    }
                }

                if (isset($xcart_user->address) && count($xcart_user->address)) {
                    update_user_meta($user_id, 'first_name', $xcart_user->address['firstname']->value);
                    update_user_meta($user_id, 'last_name', $xcart_user->address['lastname']->value);
                    update_user_meta($user_id, 'billing_first_name', $xcart_user->address['firstname']->value);
                    update_user_meta($user_id, 'billing_last_name', $xcart_user->address['lastname']->value);
                    update_user_meta($user_id, 'billing_address_1', $xcart_user->address['street']->value);
                    //update_user_meta( $user_id, 'billing_address_2', $xcart_user->address['lastname']->value);
                    update_user_meta($user_id, 'billing_city', $xcart_user->address['city']->value);
                    update_user_meta($user_id, 'billing_postcode', $xcart_user->address['zipcode']->value);
                    update_user_meta($user_id, 'billing_country', $xcart_user->address['country_code']->value);
                    update_user_meta($user_id, 'billing_state', $xcart_user->address['state_id']->state_code);
                    update_user_meta($user_id, 'billing_phone', $xcart_user->address['phone']->value);
                    update_user_meta($user_id, 'billing_email', $xcart_user->login);

                    update_user_meta($user_id, 'shipping_first_name', $xcart_user->address['firstname']->value);
                    update_user_meta($user_id, 'shipping_last_name', $xcart_user->address['lastname']->value);
                    update_user_meta($user_id, 'shipping_address_1', $xcart_user->address['street']->value);
                    //update_user_meta( $user_id, 'shipping_address_2', $xcart_user->address['lastname']->value);
                    update_user_meta($user_id, 'shipping_city', $xcart_user->address['city']->value);
                    update_user_meta($user_id, 'shipping_postcode', $xcart_user->address['zipcode']->value);
                    update_user_meta($user_id, 'shipping_country', $xcart_user->address['country_code']->value);
                    update_user_meta($user_id, 'shipping_state', $xcart_user->address['state_id']->state_code);
                }

                /* if(count(explode(' ', $xcart_user->searchFakeField)) > 1) {
                  update_user_meta( $user_id, 'first_name', explode(' ', $xcart_user->searchFakeField)[0]);
                  update_user_meta( $user_id, 'last_name', explode(' ', $xcart_user->searchFakeField)[1]);
                  } */
            }

            // insert last synced status.
            $dataArray = array(
                'term' => 'user',
                'synced_at' => current_time('mysql')
            );
            $format = array(
                '%s',
                '%s'
            );
            global $table_prefix, $wpdb;
            $wpdb->insert($table_prefix.'xcart_lastSynced', $dataArray, $format);

            // update operation weight table.
            $weightArray = array(
                'status' => 1
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
            global $table_prefix, $wpdb;
            $wpdb->update($table_prefix.'xcart_operation_weight', $weightArray, $where, $format, $whereFormat);

            if ($new > 0) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?= $new ?> customers added successfully.</strong></p>
                    <?php foreach ($created as $key1 => $value) { ?>
                        <p><?= $key1 + 1 . ') ' . $value ?></p>
                    <?php } ?> 
                    <?php if ($new > 10) { ?>
                        <p style="text-align: right;"><a href="<?= admin_url() . 'users.php' ?>">View all</a></p>
                <?php } ?>
                </div> 
            <?php } ?>

            <?php if ($update > 0) { ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong><?= $update ?> customers updated successfully.</strong></p>
                    <?php foreach ($updated as $key2 => $value) { ?>
                        <p><?= $key2 + 1 . ') ' . $value ?></p>
                    <?php } ?>
                    <?php if ($update > 10) { ?>
                        <p style="text-align: right;"><a href="<?= admin_url() . 'users.php' ?>">View all</a></p>
                <?php } ?>
                </div> 
                <?php
            }
        }
    }

    /*
      Function Name: wxm_product_gallery
      Description: The function get products gallery images form X-Cart server and download them to wordpress server.
      Parameters:
      Returns: 
    */
    public function wxm_product_gallery() {
        $result = $this->wxm_get_products_gallery_images();
        if (!empty($result) && count($result)) {
        	$images = [];
            foreach ($result as $data) {
                $data = (array) $data;
                $post_id = wc_get_product_id_by_sku($data['sku']);
                if(!isset($images[$post_id])) {
                	$images[$post_id] = [];
                }
                if (!empty($data['path']) && !empty($post_id)) {
                    // check image already exist in our system; 
                    $attachment = $this->wxm_get_attachement_id((array) $data, $post_id);
                    //$gallery = get_post_meta($post_id, '_product_image_gallery', false);
                    if (is_null($attachment)) {
                        $thumpId = $this->wxm_wordpress_gallery_images((array) $data, $post_id);
                    } else {
                        $thumpId = $attachment->ID;
                    }
                    array_push($images[$post_id], $thumpId);
                }
            }
			if(count($images)) {
                foreach($images as $postId => $image){
                    $thumbId = get_post_meta($postId, '_thumbnail_id');
                    $attachementimages = [];
                    if(count($thumbId)) {
                        if(count($image)) {
                            $_product = wc_get_product( $postId );
                            if( ! $_product->is_type( 'variable' ) ) {
                                $image = array_diff($image, $thumbId);
                            }                            
                            //$attachementimages = implode(",",$image);
                        }
                    } else {
                        if(count($image)) {
                            //$attachementimages = implode(",",$image);
                        }
                    }
                    /*if(in_array($thumbId, $image)) {
                    	unset($image[$thumbId]);
                    	array_unshift($image , $thumbId);
                    }*/
                    sort($image);
                    $attachementimages = implode(",",$image);
                    update_post_meta($postId, '_product_image_gallery', $attachementimages);
                }
            }
            // insert last synced status.
            $dataArray = array(
                'term' => 'image',
                'synced_at' => current_time('mysql')
            );
            $format = array(
                '%s',
                '%s'
            );
            global $table_prefix, $wpdb;
            $wpdb->insert($table_prefix.'xcart_lastSynced', $dataArray, $format);
        }
    }

    /*
      Function Name: wxm_product_images
      Description: The function get products images form X-Cart server and download them to wordpress server.
      Parameters:
      Returns: 
    */
    public function wxm_product_images() {
        $new = 0;
        $update = 0;
        $fail = 0;
        $productCount = 0;
        // get xcart database credentials.
        global $table_prefix, $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
            WHERE id = %d ', 1));
        // check if web directory is set.
        if (isset($result->web_directory)) {
            $web_directory = $result->web_directory;
            $result = $this->wxm_get_products_images();            
            $variant_images = $this->wxm_get_products_variant_images();
            if(!count($result) && count($variant_images)) {
                ?>
                <div class="notice notice-error is-dismissible"> 
                    <p><strong>Images not found.</strong></p>
                </div> 
                <?php
            }
            
            
            if (!empty($result) && count($result)) {
                foreach ($result as $data) {
                    $post_id = wc_get_product_id_by_sku($data->sku);
                    if (!empty($data->path) && !empty($post_id)) {
                        // check image already exist in our system; 
                        $attachment = $this->wxm_get_attachement_id((array) $data, $post_id);
                        if (is_null($attachment)) {
                            $response = $this->wxm_wordpress_images((array) $data, $post_id);
                            if ($response) {
                                $new++;
                            } else {
                                $fail++;
                            }
                        } else {
                            $response = update_post_meta($post_id, '_thumbnail_id', $attachment->ID);
                            //update_post_meta($post_id, '_product_image_gallery', $attachment->ID, true);
                            if ($response) {
                                $update++;
                            } else {
                                //  $fail++;
                            }
                        }
                        $productCount++;
                    }
                    
                }

                // save product gallary images.
                $this->wxm_product_gallery();
            }

            if (!empty($variant_images) && count($variant_images)) {
                // upload variant images.
                foreach ($variant_images as $varImg) {
                    $post_id = $this->wxm_get_post_by_post_name($varImg->variant_id);
                    if (!empty($varImg->path) && $post_id) {
                        // check image already exist in our system;
                        $attachment = $this->wxm_get_variant_attachement_id((array) $varImg, $post_id);
                        if (is_null($attachment)) {
                            $response = $this->wxm_wordpress_variant_images((array) $varImg, $post_id);
                            if ($response) {
                                $new++;
                            } else {
                                $fail++;
                            }
                        } else {
                            $response = update_post_meta($post_id, '_thumbnail_id', $attachment->ID);
                            if ($response) {
                                $update++;
                            } else {
                                //  $fail++;
                            }
                        }
                    }                    
                }
                
            }

            // insert last synced status.
            $dataArray = array(
                'term' => 'image',
                'synced_at' => current_time('mysql')
            );
            $format = array(
                '%s',
                '%s'
            );
            global $table_prefix, $wpdb;
            $wpdb->insert($table_prefix.'xcart_lastSynced', $dataArray, $format);

            // update operation weight table.
            $weightArray = array(
                'status' => 1
            );
            $where = array(
                'term' => 'images'
            );
            $format = array(
                '%d'
            );
            $whereFormat = array(
                '%s'
            );
            global $table_prefix, $wpdb;
            $wpdb->update($table_prefix.'xcart_operation_weight', $weightArray, $where, $format, $whereFormat);

            /*if ($productCount > 0) {
                ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong>Images have been attached to <?= $productCount ?> products.</strong></p>
                </div> 
            <?php }*/

            /*if ($new > 0) {
                ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong><?= $new ?> images attached successfully.</strong></p>
                </div> 
            <?php } ?>

            <?php if ($update > 0) { ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong><?= $update ?> images updated successfully.</strong></p>
                </div> 
            <?php } ?>

            <?php if ($fail > 0) { ?>
                <div class="notice notice-error is-dismissible"> 
                    <p><strong><?= $fail ?> images failed to download.</strong></p>
                </div> 
                <?php
            }*/

        } else {
            ?>
            <div class="notice notice-error is-dismissible"> 
                <p><strong>Please save web directory in settings.</strong></p>
            </div>
            <?php
        }
       // return; 
    }

    /*
      Function Name: wxm_products
      Description: The function get products form X-Cart database, create/update them in wooCommerce and attach product images.
      Parameters:
      Returns: 
    */
    public function wxm_products() {
        $this->wxm_attribute();
        $result = $this->wxm_get_products();
        if (is_array($result) && count($result)) {
            $this->wxm_save_product_details($result);
            $this->wxm_product_images();
        } elseif( is_array($result) && count($result) == 0 ) {
            ?>
            <div class="notice notice-error is-dismissible"> 
                <p><strong>Products not found.</strong></p>
            </div> 
            <?php
        } elseif( !is_array($result) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php echo $result.' products found to import, cronjob started.' ?></strong></p>
            </div> 
            <?php
        }
    }

    /*
      Function Name: wxm_products_cron
      Description: The function get products form X-Cart database, create/update them in wooCommerce and attach product images from cron request.
      Parameters:
      Returns: 
    */
    public function wxm_products_cron() {
        $result = $this->wxm_get_products_cron();
        if (is_array($result) && count($result)) {
            $this->wxm_attribute();
            $this->wxm_save_product_details($result);
            $this->wxm_product_images();
        }
    }

    /*
      Function Name: wxm_product_reviews
      Description: The function get products reviews form X-Cart database, create/update them in wooCommerce and attach with product.
      Parameters:
      Returns: 
    */
    public function wxm_product_reviews() {
        $result = $this->wxm_get_product_reviews();
        if (!empty($result) && count($result)) {
            $this->wxm_save_product_reviews($result);
        } else {
            ?>
            <div class="notice notice-error is-dismissible"> 
                <p><strong>No record found.</strong></p>            
            </div> 
            <?php
        }
    }

    /*
      Function Name: wxm_categories
      Description: The function get categories form X-Cart database, create/update them in wooCommerce.
      Parameters:
      Returns: 
    */
    public function wxm_categories() {
        $result = $this->wxm_get_categories();
        if (!empty($result) && count($result)) {
            $this->wxm_save_categories($result);
        } else {
            ?>
            <div class="notice notice-error is-dismissible"> 
                <p><strong>No record found.</strong></p>            
            </div> 
            <?php
        }
    }

    /*
      Function Name: wxm_get_categories
      Description: The function get categories form X-Cart database.
      Parameters:
      Returns: 
    */
    public function wxm_get_categories() {
        $query = 'SELECT xcc.category_id, 
            CASE WHEN xcc.depth = 0
            THEN NULL 
            ELSE (
            SELECT name
            FROM xc_category_translations
            WHERE id = xcc.parent_id
            )
            END AS parent_category, xcc.depth, xcct.name, xcct.description
            FROM xc_categories xcc
            JOIN xc_category_translations xcct ON xcc.category_id = xcct.id
            WHERE xcc.depth != -1
            ORDER BY xcc.depth ASC 
            LIMIT 0, 300';

        return $this->xcartDB->get_results($query);
    }

    /*
      Function Name: wxm_get_users
      Description: The function get users(customers) form X-Cart database.
      Parameters:
      Returns: 
    */
    public function wxm_get_users() {
        $query = 'SELECT *
            FROM xc_profiles
            LIMIT 0, 1000';

        $users = $this->xcartDB->get_results($query);
        if (!empty($users) && count($users)) {
            foreach ($users as $key => $user) {
                // get address.
                $query = 'SELECT *
                    FROM  xc_profile_addresses
                    WHERE profile_id = ' . $user->profile_id . ' ';
                $address = $this->xcartDB->get_row($query);
                $address_id = $address->address_id;
                $query = 'SELECT xcafv.*,xcaf.serviceName
                    FROM  xc_address_field_value xcafv 
                    JOIN xc_address_field xcaf 
                    ON xcafv.address_field_id = xcaf.id
                    WHERE xcafv.address_id = ' . $address_id . '
                    ';
                $addresses = $this->xcartDB->get_results($query);
                // get country _name from country code.
                $addr = [];
                foreach ($addresses as $key => $add) {
                    switch ($add->serviceName) {
                        case 'country_code':
                            $query = 'SELECT country
                                FROM  xc_country_translations
                                WHERE id = "' . $add->value . '" ';
                            $country = $this->xcartDB->get_row($query);
                            $add->country_name = $country->country;
                            break;

                        case 'state_id':
                            $query = 'SELECT state, code
                                FROM  xc_states
                                WHERE state_id = "' . $add->value . '" ';
                            $state = $this->xcartDB->get_row($query);
                            $add->state_name = $state->state;
                            $add->state_code = $state->code;
                            break;
                    }
                    $serviceName = $add->serviceName;
                    $addr[$serviceName] = $add;
                }
                $user->address = $addr;
            }
            return $users;
        }
    }

    /*
      Function Name: wxm_get_products
      Description: The function get products form X-Cart database.
      Parameters:
      Returns: 
    */
    public function wxm_get_products() {
        global $table_prefix, $wpdb;
        // check if cronjob is active or not.
        $cron = 'SELECT xc.*
        FROM '.$table_prefix.'xcart_cronjob xc
        WHERE status = 1';
        $cron = $wpdb->get_row($cron);

        if(count($cron)) {
            // cronjob is running.
            $query = 'SELECT xcp.*, 
            xcptrans.* 
            FROM xc_products xcp 
            join xc_product_translations xcptrans 
            ON xcp.product_id = xcptrans.id 
            ORDER BY xcp.product_id 
            LIMIT '.$cron->start_limit.', '.$cron->end_limit.' ';
            $produts = $this->xcartDB->get_results($query);

            /*if( $cron->end_limit >= $cron->totalrecords ) {

            } elseif() {}*/
            // update operation cron table.
            $weightArray = array(
                'start_limit' => $cron->start_limit + 50,
                'end_limit'   => $cron->end_limit + 50,
                'status'      => ($cron->end_limit >= $cron->totalrecords)?0:1
            );
            $where = array(
                'id' => $cron->id
            );
            $format = array(
                '%d',
                '%d',
                '%d'
            );
            $whereFormat = array(
                '%d'
            );
            $wpdb->update($table_prefix.'xcart_cronjob', $weightArray, $where, $format, $whereFormat);
            return(count($produts)) ? $produts : false;
        }

        $countquery = 'SELECT COUNT(xcp.product_id)
        FROM xc_products xcp 
        join xc_product_translations xcptrans 
        ON xcp.product_id = xcptrans.id';
        $produts_count = $this->xcartDB->get_var($countquery);

        // if products are more than one 50 than ask shedule cronjob or import products.
        if($produts_count <= 50) {
            $query = 'SELECT xcp.*, 
            xcptrans.* 
            FROM xc_products xcp 
            join xc_product_translations xcptrans 
            ON xcp.product_id = xcptrans.id 
            ORDER BY xcp.product_id 
            LIMIT 0, 5000';
            $produts = $this->xcartDB->get_results($query);
            return(count($produts)) ? $produts : false;
        } else {
            // shedule cronjob. 
            $dataArray = array(
                'term' => 'product',
                'end_limit' => 50,
                'totalrecords' => $produts_count,
                'status' => 1
            );
            $format = array(
                '%s',
                '%d',
                '%d',
                '%d'
            );

            //global $table_prefix, $wpdb;
            $wpdb->insert($table_prefix.'xcart_cronjob', $dataArray, $format);

            return $produts_count;
        }
    }

    /*
      Function Name: wxm_get_products_cron
      Description: The function get products form X-Cart database when cron request fires.
      Parameters:
      Returns: 
    */
    public function wxm_get_products_cron() {
        global $table_prefix, $wpdb;
        // check if cronjob is active or not.
        $cron = 'SELECT xc.*
        FROM '.$table_prefix.'xcart_cronjob xc
        WHERE status = 1';
        $cron = $wpdb->get_row($cron);

        if(count($cron)) {
            // cronjob is running.
            $query = 'SELECT xcp.*, 
            xcptrans.* 
            FROM xc_products xcp 
            join xc_product_translations xcptrans 
            ON xcp.product_id = xcptrans.id 
            ORDER BY xcp.product_id 
            LIMIT '.$cron->start_limit.', '.$cron->end_limit.' ';
            $produts = $this->xcartDB->get_results($query);

            // update operation cron table.
            $weightArray = array(
                'start_limit' => $cron->start_limit + 50,
                'end_limit'   => $cron->end_limit + 50,
                'status'      => ($cron->end_limit >= $cron->totalrecords)?0:1
            );
            $where = array(
                'id' => $cron->id
            );
            $format = array(
                '%d',
                '%d',
                '%d'
            );
            $whereFormat = array(
                '%d'
            );
            $wpdb->update($table_prefix.'xcart_cronjob', $weightArray, $where, $format, $whereFormat);
            return(count($produts)) ? $produts : false;
        }
    }

    /*
      Function Name: wxm_get_product_reviews
      Description: The function get products reviews form X-Cart database.
      Parameters:
      Returns: 
    */
    public function wxm_get_product_reviews() {
        $query = 'SELECT xcr.*, xcp.sku
        FROM xc_reviews xcr
        JOIN xc_products xcp
        ON xcr.product_id = xcp.product_id
        LIMIT 0, 50000';

        $reviews = $this->xcartDB->get_results($query);
        return(!empty($reviews)) ? $reviews : false;
    }

    /*
      Function Name: wxm_get_products_images
      Description: The function get products images form X-Cart database.
      Parameters:
      Returns: 
    */
    public function wxm_get_products_images() {
            $query = 'SELECT * FROM 
                (SELECT xp.product_id, xp.sku, xpi.orderby, xpi.id, xpi.path, xpi.fileName, xpi.alt 
                FROM xc_product_images xpi 
                JOIN xc_products xp 
                ON xpi.product_id = xp.product_id 
                ORDER BY xpi.orderby ASC) AS tmp_table 
                GROUP BY `product_id`';
        return $this->xcartDB->get_results($query);
    }

    /*
      Function Name: wxm_get_products_variant_images
      Description: The function get products variants images form X-Cart database.
      Parameters:
      Returns: 
    */
    public function wxm_get_products_variant_images() {
        $query = 'SELECT xcpv.*, xcpvi.*
            FROM xc_product_variants xcpv
            JOIN xc_product_variant_images xcpvi ON xcpv.id = xcpvi.product_variant_id';

        return $this->xcartDB->get_results($query);
    }

    /*
      Function Name: wxm_get_products_gallery_images
      Description: The function get products gallery images form X-Cart database.
      Parameters:
      Returns: 
    */
    public function wxm_get_products_gallery_images() {
        $query = 'SELECT xp.product_id, xp.sku, xpi.id, xpi.path, xpi.fileName, xpi.alt
            FROM xc_products xp
            JOIN xc_product_images xpi ON xpi.product_id = xp.product_id
            LIMIT 0 , 10000';

        return $this->xcartDB->get_results($query);
    }

    /*
      Function Name: wxm_attribute
      Description: The function get global/product specific attributes and create/update them in wooCommerce.
      Parameters:
      Returns: 
    */
    public function wxm_attribute() {
        $attributes = $this->wxm_get_attributes();
        if (!empty($attributes) && count($attributes)) {
           	$this->wxm_save_attribute($attributes);
        } else {
            ?>
            <div class="notice notice-error is-dismissible"> 
                <p><strong>Attributes not found.</strong></p>
            </div> 
            <?php
        }
        $terms = $this->wxm_get_attribute_terms();
        if (!empty($terms) && count($terms)) {
            $this->wxm_save_attribute_terms($terms);
        }        
    }

    /*
      Function Name: wxm_save_attribute
      Description: The function create/update, global/product specific attributes in wooCommerce.
      Parameters: Attributes (Array)
      Returns: 
    */
    function wxm_save_attribute($attributes) {
    	if(count($attributes)) {
    		$new = 0;
	        $update = 0;
	        $created = [];
	        $updated = [];

    		foreach ($attributes as $key => $attribute) {
    			$label='';
    			$set=true;

    			global $table_prefix, $wpdb;
			    $label = $label == '' ? ucfirst($attribute->name) : $label;
			    $attribute_id = $this->wxm_get_attribute_id_from_name( sanitize_title($attribute->name) );

			    if( empty($attribute_id) ){
			        $attribute_id = NULL;
			    } else {
			        $set = false;
			    }
			    $args = array(
			        'attribute_id'      => $attribute_id,
			        'attribute_name'    => sanitize_title($attribute->name),
			        'attribute_label'   => $label,
			        'attribute_type'    => 'select',
			        'attribute_orderby' => 'menu_order',
			        'attribute_public'  => 0,
			    );
                $format = array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                );
			    if( $attribute_id == NULL ) {
			        $wpdb->insert(  "{$wpdb->prefix}woocommerce_attribute_taxonomies", $args, $format);
			    }

			    if( $set ){
			        $attributes = wc_get_attribute_taxonomies();
			        $args['attribute_id'] = $this->wxm_get_attribute_id_from_name( sanitize_title($attribute->name) );
			        $attributes[] = (object) $args;
			        //print_pr($attributes);
			        set_transient( 'wc_attribute_taxonomies', $attributes );
			        if (count($created) < 10) {
                        array_push($created, $attribute->name);
                    }
                    $new++;
			    } else {
			        if (count($updated) < 10) {
                        array_push($updated, $attribute->name);
                    }
                    $update++;
			    }
      
    		}

    		// insert last synced status.
            $dataArray = array(
                'term' => 'attribute',
                'synced_at' => current_time('mysql')
            );
            $format = array(
                '%s',
                '%s'
            );

            global $table_prefix, $wpdb;
            $wpdb->insert($table_prefix.'xcart_lastSynced', $dataArray, $format);

            // update operation weight table.
            $weightArray = array(
                'status' => 1
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
            global $table_prefix, $wpdb;
            $wpdb->update($table_prefix.'xcart_operation_weight', $weightArray, $where, $format, $whereFormat);

            /*if ($new > 0) {
                ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong><?= $new ?> attributes created successfully.</strong></p>
                    <?php foreach ($created as $key1 => $value) { ?>
                        <p><?= $key1 + 1 . ') ' . $value ?></p>
                    <?php } ?> 
                    <?php if ($new > 10) { ?>
                        <p style="text-align: right;"><a href="<?= admin_url() . 'edit.php?post_type=product&page=product_attributes' ?>">View all</a></p>
                <?php } ?>
                </div> 
            <?php } ?>

            <?php if ($update > 0) { ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong><?= $update ?> attributes updated successfully.</strong></p>
                    <?php foreach ($updated as $key2 => $value) { ?>
                        <p><?= $key2 + 1 . ') ' . $value ?></p>
                    <?php } ?>
                    <?php if ($update > 10) { ?>
                        <p style="text-align: right;"><a href="<?= admin_url() . 'edit.php?post_type=product&page=product_attributes' ?>">View all</a></p>
                <?php } ?>
                </div>
                <?php
            }*/
    	}
    }

    /*
      Function Name: wxm_get_attribute_id_from_name
      Description: The function returns wooCommerce attribute ID from attribute name.
      Parameters: Attribute name (String)
      Returns: wooCommerce attribute ID.
    */
    function wxm_get_attribute_id_from_name( $name ) {
	    global $wpdb;
	    $attribute_id = $wpdb->get_col($wpdb->prepare("SELECT attribute_id
	    FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
	    WHERE attribute_name = %s", $name));
	    return reset($attribute_id);
	}

    /*
      Function Name: wxm_save_attribute_terms
      Description: The function create/update wooCommerce terms for respective attributes.
      Parameters: Attribute Terms (Array)
      Returns:
    */
    function wxm_save_attribute_terms($terms) {
    	if(count($terms)) {
    		foreach ($terms as $key => $term) {
    			$attrName = sanitize_title($term->attribute_name);    			
    			register_taxonomy("pa_".$attrName, null, null );
    			wp_insert_term($term->name, 'pa_'.$attrName);
    			
    		}
    	}
    }

    /*
      Function Name: wxm_is_attribute_exist_by_name
      Description: The function checks if the attribute exist in wooCommerce by attribute name.
      Parameters: Attribute Name (String)
      Returns: Array of Attribute objects(if exist) or false.
    */
    function wxm_is_attribute_exist_by_name($attribute_name) {
        $attribute_name = sanitize_title($attribute_name);
        global $table_prefix, $wpdb;
        $query = 'SELECT attribute_id
            FROM '.$table_prefix.'woocommerce_attribute_taxonomies 
            WHERE attribute_name = %s ';

        $data = $wpdb->get_row($wpdb->prepare($query, $attribute_name));
        return(count($data)) ? $data : false;
    }

    /*
      Function Name: wxm_is_term_exist_by_name
      Description: The function checks if the attribute term exist in wooCommerce by attribute term name.
      Parameters: Attribute term Name (String)
      Returns: ID of Attribute term(if exist) or false.
    */
    function wxm_is_term_exist_by_name($term_name) {
    	$term_name = trim($term_name);
        global $table_prefix, $wpdb;
        $query = 'SELECT term_id
            FROM '.$table_prefix.'terms 
            WHERE name = %s ';

        $data = $wpdb->get_row($wpdb->prepare($query, $term_name));
        return(count($data)) ? $data->term_id : false;
    }

    /*
      Function Name: wxm_get_post_by_post_name
      Description: The function checks if the post exist in wooCommerce by post name.
      Parameters: Post Name (String)
      Returns: Post ID (if exist) or false.
    */
    function wxm_get_post_by_post_name($post_name) {
        global $table_prefix, $wpdb;
        $query = 'SELECT ID
            FROM '.$table_prefix.'posts 
            WHERE post_name = %s ';

        $data = $wpdb->get_row($wpdb->prepare($query, $post_name));
        return(count($data)) ? $data->ID : false;
    }

    /*
      Function Name: wxm_get_attributes
      Description: The function checks if the post exist in wooCommerce by post name.
      Parameters: Post Name (String)
      Returns: Post ID (if exist) or false.
    */
    public function wxm_get_attributes() {
        $query = 'SELECT xca.id,
            xcat.name
            FROM xc_attributes xca
            JOIN xc_attribute_translations xcat ON xca.id = xcat.id
            WHERE xcat.code = "en"
            GROUP BY xca.id
            LIMIT 0, 5000';

        return $this->xcartDB->get_results($query);
    }

    /*
      Function Name: wxm_get_attribute_terms
      Description: The function get attribute terms from X-Cart database.
      Parameters: 
      Returns: Array of Attribute terms.
    */
    public function wxm_get_attribute_terms() {
        $query = 'SELECT xcao.id, xcao.attribute_id,
            xcaot.name, xcat.name attribute_name
            FROM xc_attribute_options xcao
            JOIN xc_attribute_translations xcat ON xcao.attribute_id = xcat.id
            JOIN xc_attribute_option_translations xcaot ON xcao.id = xcaot.id
            WHERE xcaot.code = "en"
            GROUP BY xcao.attribute_id, xcaot.name
            LIMIT 0, 10000';
            
            // GROUP BY xcaot.name // removed for testing.

        return $this->xcartDB->get_results($query);
    }

    /*
      Function Name: wxm_get_attribute_from_id
      Description: The function returns attribute from wooCommerce by attribute id.
      Parameters: wooCommerce attribute id.
      Returns: Array of Attribute object(if exist) or false.
    */
    function wxm_get_attribute_from_id($wp_attribute_id) {
        global $table_prefix, $wpdb;
        $query = 'SELECT attribute_name
            FROM '.$table_prefix.'woocommerce_attribute_taxonomies 
            WHERE attribute_id = %d ';

        $data = $wpdb->get_row($wpdb->prepare($query, $wp_attribute_id));
        return(count($data)) ? $data : false;
    }

    /*
      Function Name: wxm_save_product_reviews
      Description: The function create/update product reviews in wooCommerce.
      Parameters: Reviews(Array).
      Returns: 
    */
    private function wxm_save_product_reviews($reviews) {
        $new = 0;
        $update = 0;
        if (!empty($reviews) && count($reviews)) {
            foreach ($reviews as $review) {
                $review = (array) $review;
                $postdate = date("Y-m-d H:i:s", $review['additionDate']);
                $wpUserIds = null;
                $wpProductIds = null;
                if (isset($review['profile_id'])) {
                    $wpUserIds = $this->wxm_get_wp_ids_from_xcart_ids('user', [$review['profile_id']]);
                    if (count($wpUserIds)) {
                        $wpUserIds = $wpUserIds[0];
                    }
                }
                if (!empty($review['sku'])) {
                    $product_id = wc_get_product_id_by_sku($review['sku']);
                    /*$wpProductIds = $this->wxm_get_wp_ids_from_xcart_ids('product', [$review['product_id']]);
                    if (count($wpProductIds)) {
                        $wpProductIds = $wpProductIds[0];
                    }*/
                }
                $wp_ids = $this->wxm_get_wp_ids_from_xcart_ids('comment', [$review['id']]);
                if (count($wp_ids)) {
                    // update operation.
                    // check if comment exist in wordpress.
                    global $table_prefix, $wpdb;
                    $query = 'SELECT COUNT(comment_ID) commentCount
                        FROM '.$table_prefix.'comments 
                        WHERE comment_ID = %d ';

                    $commentCount = $wpdb->get_row($wpdb->prepare($query, $wp_ids[0]));
                    if(count($commentCount)) {
                        if($commentCount->commentCount) {
                            // comment exist in wordpress.
                            $data = array(
                                'comment_ID' => $wp_ids[0],
                                'comment_post_ID' => ($product_id) ? $product_id : 0,
                                'comment_author' => (!empty($review['reviewerName'])) ? $review['reviewerName'] : '',
                                'comment_author_email' => '',
                                'comment_author_url' => '',
                                'comment_content' => (!empty($review['review'])) ? $review['review'] : '',
                                'comment_type' => '',
                                'comment_parent' => 0,
                                'user_id' => ($wpUserIds) ? $wpUserIds : 0,
                                'comment_author_IP' => (!empty($review['ip'])) ? $review['ip'] : 0,
                                'comment_agent' => '',
                                'comment_date' => $postdate,
                                'comment_approved' => (!empty($review['status'])) ? $review['status'] : 0,
                            );

                            $comment_id = wp_update_comment($data);

                            if (!empty($review['rating'])) {
                                update_comment_meta($wp_ids[0], 'verified', 0);
                                update_comment_meta($wp_ids[0], 'rating', $review['rating']);
                            }
                            $update++;
                        } else {
                            // comment not exist.
                            $data = array(
                                'comment_post_ID' => ($product_id) ? $product_id : 0,
                                'comment_author' => (!empty($review['reviewerName'])) ? $review['reviewerName'] : '',
                                'comment_author_email' => '',
                                'comment_author_url' => '',
                                'comment_content' => (!empty($review['review'])) ? $review['review'] : '',
                                'comment_type' => '',
                                'comment_parent' => 0,
                                'user_id' => ($wpUserIds) ? $wpUserIds : 0,
                                'comment_author_IP' => $review['ip'],
                                'comment_agent' => '',
                                'comment_date' => $postdate,
                                'comment_approved' => $review['status']
                            );

                            $comment_id = wp_insert_comment($data);

                            if ($comment_id) {
                                if (!empty($review['rating'])) {
                                    add_comment_meta($comment_id, 'verified', 0);
                                    add_comment_meta($comment_id, 'rating', $review['rating']);
                                }
                                // update into relation table.
                                $updateArray = array(
                                    'wp_id' => $comment_id
                                );
                                $where = array(
                                    'term' => 'comment',
                                    'xcart_id' => $review['id']
                                );
                                $format = array(
                                    '%d'
                                );
                                $whereFormat = array(
                                    '%s',
                                    '%d'
                                );
                                global $table_prefix, $wpdb;
                                $wpdb->update($table_prefix.'xcart_relation', $updateArray, $where, $format, $whereFormat);
                                $new++;
                            }
                        }
                    }
                    
                } else {
                    // insert operation.
                    $data = array(
                        'comment_post_ID' => ($product_id) ? $product_id : 0,
                        'comment_author' => (!empty($review['reviewerName'])) ? $review['reviewerName'] : '',
                        'comment_author_email' => '',
                        'comment_author_url' => '',
                        'comment_content' => (!empty($review['review'])) ? $review['review'] : '',
                        'comment_type' => '',
                        'comment_parent' => 0,
                        'user_id' => ($wpUserIds) ? $wpUserIds : 0,
                        'comment_author_IP' => $review['ip'],
                        'comment_agent' => '',
                        'comment_date' => $postdate,
                        'comment_approved' => $review['status']
                    );

                    $comment_id = wp_insert_comment($data);

                    if ($comment_id) {
                        if (!empty($review['rating'])) {
                            add_comment_meta($comment_id, 'verified', 0);
                            add_comment_meta($comment_id, 'rating', $review['rating']);
                        }
                        // insert into relation table.
                        $insertArray = array(
                            'term' => 'comment',
                            'wp_id' => $comment_id,
                            'xcart_id' => $review['id']
                        );
                        $format = array(
                            '%s',
                            '%d',
                            '%d'
                        );
                        global $table_prefix, $wpdb;
                        $wpdb->insert($table_prefix.'xcart_relation', $insertArray, $format);
                        $new++;
                    }
                }
            }

            // update operation weight table.
            $weightArray = array(
                'status' => 1
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
            global $table_prefix, $wpdb;
            $wpdb->update($table_prefix.'xcart_operation_weight', $weightArray, $where, $format, $whereFormat);

            // insert last synced status.   
            $dataArray = array(
                'term' => 'review',
                'synced_at' => current_time('mysql')
            );
            $format = array(
                '%s',
                '%s'
            );
            global $table_prefix, $wpdb;
            $wpdb->insert($table_prefix.'xcart_lastSynced', $dataArray, $format);

            if ($new > 0) {
                ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong><?= $new ?> reviews created successfully.</strong></p>
                    <p style="text-align: right;"><a href="<?= admin_url() . 'edit-comments.php' ?>">View all</a></p>
                </div> 
            <?php } ?>

            <?php if ($update > 0) { ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?= $update ?> reviews updated successfully.</strong></p>                
                    <p style="text-align: right;"><a href="<?= admin_url() . 'edit-comments.php' ?>">View all</a></p>
                </div> 
                <?php
            }
        }
        return;
    }

    /*
      Function Name: wxm_save_categories
      Description: The function get categories in wooCommerce.
      Parameters: Categories (Array).
      Returns: 
    */
    private function wxm_save_categories($categories) {
        $new = 0;
        $update = 0;
        $created = [];
        $updated = [];
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $category = (array) $category;
                $response = $this->wxm_category_save(array(
                    'cat_id' => $category['category_id'],
                    'cat_name' => $category['name'],
                    'description' => $category['description'],
                    'category_parent' => $category['parent_category']
                ));
                if ($response == 1) {
                    if (count($updated) < 10) {
                        array_push($updated, $category['name']);
                    }
                    $update++;
                } elseif ($response == 2) {
                    if (count($created) < 10) {
                        array_push($created, $category['name']);
                    }
                    $new++;
                }
            }

            // insert last synced status.
            $dataArray = array(
                'term' => 'category',
                'synced_at' => current_time('mysql')
            );
            $format = array(
                '%s',
                '%s'
            );
            global $table_prefix, $wpdb;
            $wpdb->insert($table_prefix.'xcart_lastSynced', $dataArray, $format);

            // update operation weight table.
            $weightArray = array(
                'status' => 1
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
            global $table_prefix, $wpdb;
            $wpdb->update($table_prefix.'xcart_operation_weight', $weightArray, $where, $format, $whereFormat);

            if ($new > 0) {
                ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong><?= $new ?> categories created successfully.</strong></p>
                    <?php foreach ($created as $key1 => $value) { ?>
                        <p><?= $key1 + 1 . ') ' . $value ?></p>
                    <?php } ?> 
                    <?php if ($new > 10) { ?>
                        <p style="text-align: right;"><a href="<?= admin_url() . 'edit-tags.php?taxonomy=product_cat&post_type=product' ?>">View all</a></p>
                <?php } ?>
                </div> 
            <?php } ?>

            <?php if ($update > 0) { ?>
                <div class="notice notice-success is-dismissible"> 
                    <p><strong><?= $update ?> categories updated successfully.</strong></p>
                    <?php foreach ($updated as $key2 => $value) { ?>
                        <p><?= $key2 + 1 . ') ' . $value ?></p>
                    <?php } ?>
                    <?php if ($update > 10) { ?>
                        <p style="text-align: right;"><a href="<?= admin_url() . 'edit-tags.php?taxonomy=product_cat&post_type=product' ?>">View all</a></p>
                <?php } ?>
                </div> 
                <?php
            }
        }
    }

    /*
      Function Name: wxm_category_save
      Description: The function create/update categories in wooCommerce.
      Parameters: Category (Array).
      Returns: Response (Int)
    */
    function wxm_category_save($data) {
        if (!empty($data['category_parent'])) {
            $parent_id = $this->term_exists($data['category_parent']);
        }

        $category_id = $this->wxm_is_category_exist($data['cat_id']);
        if ($category_id) {
            // update operation.
            $term_id = wp_update_term(
                    $category_id, 'product_cat', array(
                'name' => $data['cat_name'],
                'description' => $data['description'],
                'parent' => (isset($parent_id)) ? $parent_id : 0
                    )
            );

            return 1;
        } else {
            // insert operation.
            $term_id = wp_insert_term(
                    $data['cat_name'], 'product_cat', array(
                'description' => $data['description'],
                'parent' => (isset($parent_id)) ? $parent_id : 0
                    )
            );

            if (is_wp_error($term_id)) {
                $errors = $term_id->get_error_messages();
                //$response['error'] = implode(';', $response);
                foreach ($errors as $error) {
                    echo $error;
                }
            } else {
                // insert into relation table.
                $insertArray = array(
                    'term' => 'category',
                    'wp_id' => $term_id['term_id'],
                    'xcart_id' => $data['cat_id']
                );
                $format = array(
                    '%s',
                    '%d',
                    '%d'
                );
                global $table_prefix, $wpdb;
                $wpdb->insert($table_prefix.'xcart_relation', $insertArray, $format);

                return 2;
            }
        }
    }

    /*
      Function Name: wxm_is_category_exist
      Description: The function checks whether the category exists or not in wooCommerce.
      Parameters: X-Cart Category ID (Int).
      Returns: wooCommerce Category ID (Ind), (if exist) or false.
    */
    function wxm_is_category_exist($xcart_id) {
        global $table_prefix, $wpdb;
        $wp_id = $wpdb->get_row($wpdb->prepare('SELECT wp_id FROM '.$table_prefix.'xcart_relation WHERE term = %s AND xcart_id = %d ', 'category', $xcart_id));
        return(count($wp_id)) ? $wp_id->wp_id : false;
    }

    /*
      Function Name: wxm_is_attribute_exist
      Description: The function checks whether the attribute exists or not in wooCommerce.
      Parameters: X-Cart Attribute ID (Int).
      Returns: wooCommerce Attribute ID (Ind), (if exist) or false.
    */
    function wxm_is_attribute_exist($xcart_id) {
        global $table_prefix, $wpdb;
        $wp_id = $wpdb->get_row($wpdb->prepare('SELECT wp_id FROM '.$table_prefix.'xcart_relation WHERE term = %s AND xcart_id = %d ', 'attribute', $xcart_id));
        return(count($wp_id)) ? $wp_id->wp_id : false;
    }

    /*
      Function Name: wxm_is_attribute_term_exist
      Description: The function checks whether the attribute term exists or not in wooCommerce.
      Parameters: X-Cart Attribute term ID (Int).
      Returns: wooCommerce Attribute term ID (Ind), (if exist) or false.
    */
    function wxm_is_attribute_term_exist($xcart_id) {
        global $table_prefix, $wpdb;
        $wp_id = $wpdb->get_row($wpdb->prepare('SELECT wp_id FROM '.$table_prefix.'xcart_relation WHERE term = %s AND xcart_id = %d ', 'attribute-term', $xcart_id));
        return(count($wp_id)) ? $wp_id->wp_id : false;
    }

    /*
      Function Name: term_exists
      Description: The function checks whether the attribute term exists for the particula attribute taxonomy or not in wooCommerce.
      Parameters: term (string), taxonomy (String), parent (Int) (optional).
      Returns: Attribute term (Array) (if exist) or null.
    */
    function term_exists($term, $taxonomy = '', $parent = 0) {
        if (function_exists('term_exists')) { // 3.0 or later
            return term_exists($term, $taxonomy, $parent);
        } else {
            return is_term($term, $taxonomy, $parent);
        }
    }

    /*
      Function Name: wxm_save_product_details
      Description: The function create/update products, attach categories, attach attributes, attach variants in wooCommerce.
      Parameters: Producta (Array).
      Returns: 
    */
    private function wxm_save_product_details($details) {
        $new = 0;
        $update = 0;
        $createdProduct = [];
        $updatedProduct = [];
        foreach ($details as $key => $productData) {
            $productData = (array) $productData;
            $pid = wc_get_product_id_by_sku($productData['sku']);
            $post_id = null;
            if (!empty($pid)) {
                $wp_record = array(
                    'ID' => $pid,
                    'post_title' => $productData['name'],
                    'post_content' => $productData['description'],
                    //'post_excerpt' => $productData['metaDesc'],
                    'post_excerpt' => '',
                    'post_status' => 'publish',
                    'post_type' => "product",
                );
                if (!empty($productData['date'])) {
                    $wp_record['post_date'] = date('Y-m-d H:i:s', $productData['date']);
                }
                $post_id = wp_update_post($wp_record);

                if (is_wp_error($post_id)) {
                    $errors = $post_id->get_error_messages();
                    $response['error'] = implode(';', $response);
                    foreach ($errors as $error) {
                        echo $error;
                    }
                } else {
                    if (count($updatedProduct) < 10) {
                        array_push($updatedProduct, $productData['name']);
                    }
                    $update++;
                }
            } else {
                $wp_record = array(
                    'post_title' => $productData['name'],
                    'post_content' => $productData['description'],
                    //'post_excerpt' => $productData['metaDesc'],
                    'post_excerpt' => '',
                    'post_status' => 'publish',
                    'post_type' => "product",
                );

                if (!empty($productData['date'])) {
                    $wp_record['post_date'] = date('Y-m-d H:i:s', $productData['date']);
                }
                // Insert Operation;
                $post_id = wp_insert_post($wp_record);

                if (is_wp_error($post_id)) {
                    $errors = $post_id->get_error_messages();
                    $response['error'] = implode(';', $response);
                    foreach ($errors as $error) {
                        echo $error;
                    }
                } else {

                    // insert into relation table.
                    $relationArray = array(
                        'term' => 'product',
                        'wp_id' => $post_id,
                        'xcart_id' => $productData['product_id']
                    );
                    $format = array(
                        '%s',
                        '%d',
                        '%d'
                    );
                    global $table_prefix, $wpdb;
                    $wpdb->insert($table_prefix.'xcart_relation', $relationArray, $format);

                    // update operation weight table.
                    $weightArray = array(
                        'status' => 1
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
                    global $table_prefix, $wpdb;
                    $wpdb->update($table_prefix.'xcart_operation_weight', $weightArray, $where, $format, $whereFormat);

                    if (count($createdProduct) < 10) {
                        array_push($createdProduct, $productData['name']);
                    }
                    $new++;
                }
            }

            // attach categories.
            $xcartCategoryIds = $this->wxm_get_categories_from_product_id($productData['product_id']);
            if (count($xcartCategoryIds)) {
                $wpCategoryIds = $this->wxm_get_wp_ids_from_xcart_ids('category', $xcartCategoryIds);
                if (count($wpCategoryIds)) {
                    $cat_ids = array_map('intval', $wpCategoryIds);
                    $cat_ids = array_unique($cat_ids);
                    $term_taxonomy_ids = wp_set_object_terms($post_id, $cat_ids, 'product_cat', false);
                }
            }


            // attach attributes.
            $xcartAttributes = $this->wxm_get_attributes_from_product_id($productData['product_id']);
            $attributesHavingMultipleOptions = [];
            if (count($xcartAttributes)) {
                $thedata = [];
                foreach ($xcartAttributes as $key => $xcartAttribute) {
                    // get wordpress attribute id.
                    $wpAttributeId = $this->wxm_is_attribute_exist_by_name($xcartAttribute->name);
                    if($wpAttributeId) {                        
                        // attribute exist in wordpress.
                        $attribute = $this->wxm_get_taxonomy_by_id($wpAttributeId->attribute_id);
                        if (count($attribute)) {
                            $attribute_name = $attribute->attribute_name;
                            $xcartAttribute_terms = $this->wxm_atrribute_terms_by_product_id($productData['product_id'], $xcartAttribute->attribute_id);
                            $isMultipleAttributeOptions = (count($xcartAttribute_terms)>1)?1:0;
                            if($isMultipleAttributeOptions) {
                            	array_push($attributesHavingMultipleOptions, $attribute_name);
                            }
                        //    $is_variation = $this->wxm_is_attribute_is_variation($productData['product_id'], $xcartAttribute->attribute_id); 
                            if (count($xcartAttribute_terms)) {
                                $thedata['pa_' . $attribute_name] = Array(
                                    'name' => 'pa_' . $attribute_name,
                                    'value' => '',
                                    'is_visible' => 1,
                                    'is_variation' => ($isMultipleAttributeOptions) ? 1 : 0,
                                    'is_taxonomy' => 1
                                );

                                foreach ($xcartAttribute_terms as $key => $term) {
                                    // check if term exist in wordpress.
                                    $term_id = term_exists($term->name);
                                    if($term_id) {
                                        $term_taxonomy_ids = wp_set_object_terms($post_id, $term->name, 'pa_' . $attribute_name, true);
                                    }
                                }
                            }
                        }
                    }
                }
                if (!empty($thedata)) {
                    update_post_meta($post_id, '_product_attributes', $thedata);
                }
            }


            // attach variants.
            $variants = $this->wxm_get_product_variants($productData['product_id']);
            if (count($variants)) {
                //make product type be variable:
                wp_set_object_terms($post_id, 'variable', 'product_type');
                $i = 1;
                foreach ($variants as $key => $variant) {
                    $variant = (array) $variant;
                    $pvid = $this->wxm_get_post_by_name($variant['variant_id']);
                    $post_var_id = null;
                    if ($pvid) {
                        $wp_record = array(
                            'ID' => $pvid,
                            'post_title' => $productData['name'],
                            'post_name' => $variant['variant_id'],
                            'post_status' => 'publish',
                            'post_type' => "product_variation",
                            'post_parent' => $post_id,
                            'post_date' => date('Y-m-d H:i:s'),
                            'guid' => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $i
                        );

                        $post_var_id = wp_update_post($wp_record);

                        if (is_wp_error($post_var_id)) {
                            $errors = $post_var_id->get_error_messages();
                            $response['error'] = implode(';', $response);
                            foreach ($errors as $error) {
                                echo $error;
                            }
                        } else {
                            $response['status'] = TRUE;
                            $response['is_new'] = FALSE;
                        }
                    } else {
                        $wp_record = array(
                            'post_title' => $productData['name'],
                            'post_name' => $variant['variant_id'],
                            'post_status' => 'publish',
                            'post_type' => "product_variation",
                            'post_parent' => $post_id,
                            'post_date' => date('Y-m-d H:i:s'),
                            'guid' => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $i
                        );

                        // Insert Operation;
                        $post_var_id = wp_insert_post($wp_record);

                        if (is_wp_error($post_var_id)) {
                            $errors = $post_var_id->get_error_messages();
                            $response['error'] = implode(';', $response);
                            foreach ($errors as $error) {
                                echo $error;
                            }
                        } else {
                            $response['status'] = TRUE;
                            $response['is_new'] = FALSE;
                        }
                    }
                    // update post meta for this variant.
                    $this->wxm_update_post_by_variant_id($post_id, $post_var_id, $attributesHavingMultipleOptions, $variant, $productData);

                    $i++;
                }
            }/* else {
                if(count($attributesHavingMultipleOptions)) {
                    $thedata = [];
                    foreach ($attributesHavingMultipleOptions as $key => $attribute) {
                        $terms = wp_get_post_terms($post_id, 'pa_'.$attribute, array("fields" => "all"));
                        if(count($terms)) {
                            $thedata['pa_' . $attribute] = $terms[0]->slug;
                        }
                    }
                }

                update_post_meta($post_id, '_default_attributes', $thedata);
            }*/

            // attach related products.
            $relatedProducts = $this->wxm_get_related_products($productData['product_id']);
            if($relatedProducts) {
                $relatedProductsArray = [];
                foreach ($relatedProducts as $key => $product) {
                    $pid = wc_get_product_id_by_sku($product->sku);
                    if($pid) {
                        array_push($relatedProductsArray, $pid);
                    }
                }
                update_post_meta($post_id, '_upsell_ids', $relatedProductsArray);
            }


            //echo 'The post id is (' . $post_id . ') [' . $productData['name'] . '] [' . ( ($response['is_new']) ? ' Created' : 'Updated') . ']<br/>';

            if ($productData['amount'] > 0) {
                update_post_meta($post_id, '_stock_status', 'instock');
                update_post_meta($post_id, '_visibility', 'visible');
                update_post_meta($post_id, '_stock', $productData['amount']);
                update_post_meta($post_id, '_manage_stock', 'yes');
            } else {
                update_post_meta($post_id, '_stock_status', 'outofstock');
                update_post_meta($post_id, '_visibility', 'hidden');
            }
            update_post_meta($post_id, 'total_sales', '0');
            update_post_meta($post_id, '_downloadable', 'no');
            update_post_meta($post_id, '_virtual', 'yes');
            update_post_meta($post_id, '_regular_price', $productData['price']);
            if ($productData['salePriceValue'] != 0) {
                update_post_meta($post_id, '_sale_price', $productData['salePriceValue']);
                update_post_meta($post_id, '_sale_price_dates_from', '');
                update_post_meta($post_id, '_sale_price_dates_to', '');
            }
            update_post_meta($post_id, '_purchase_note', '');
            update_post_meta($post_id, '_featured', 'no');
            update_post_meta($post_id, '_weight', $productData['weight']);
            update_post_meta($post_id, '_length', $productData['boxLength']);
            update_post_meta($post_id, '_width', $productData['boxWidth']);
            update_post_meta($post_id, '_height', $productData['boxHeight']);
            update_post_meta($post_id, '_sku', $productData['sku']);
            //update_post_meta($post_id, '_product_attributes', array());
            update_post_meta($post_id, '_price', $productData['price']);
            update_post_meta($post_id, '_sold_individually', '');
            update_post_meta($post_id, '_backorders', 'no');

            if (function_exists('wpseo_auto_load')) {
                //plugin is activated
                if (!empty($productData['name'])) {
                    $updated_title = update_post_meta($post_id, '_yoast_wpseo_title', $productData['name']);
                }
                if (!empty($productData['metaDesc'])) {
                    $updated_desc = update_post_meta($post_id, '_yoast_wpseo_metadesc', $productData['metaDesc']);
                }
                if (!empty($productData['metaTags'])) {
                    $updated_kw = update_post_meta($post_id, '_yoast_wpseo_metakeywords', $productData['metaTags']);
                    $updated_kw = update_post_meta($post_id, '_yoast_wpseo_focuskw', $productData['metaTags']);
                    $updated_kw = update_post_meta($post_id, '_yoast_wpseo_focuskw_text_input', $productData['metaTags']);
                }
            }
        }

        // insert last synced status.
        $dataArray = array(
            'term' => 'product',
            'synced_at' => current_time('mysql')
        );
        $format = array(
            '%s',
            '%s'
        );
        global $table_prefix, $wpdb;
        $wpdb->insert($table_prefix.'xcart_lastSynced', $dataArray, $format);

        // update operation weight table.
        $weightArray = array(
            'status' => 1
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
        global $table_prefix, $wpdb;
        $wpdb->update($table_prefix.'xcart_operation_weight', $weightArray, $where, $format, $whereFormat);


        if ($new > 0) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?= $new ?> products created successfully.</strong></p>
                <?php foreach ($createdProduct as $key1 => $product) { ?>
                    <p><?= $key1 + 1 . ') ' . $product ?></p>
                <?php } ?> 
                <?php if ($new > 10) { ?>
                    <p style="text-align: right;"><a href="<?= admin_url() . 'edit.php?post_type=product' ?>">View all</a></p>
            <?php } ?>
            </div> 
        <?php } ?>

        <?php if ($update > 0) { ?>
            <div class="notice notice-success is-dismissible"> 
                <p><strong><?= $update ?> products updated successfully.</strong></p>
                <?php foreach ($updatedProduct as $key2 => $product) { ?>
                    <p><?= $key2 + 1 . ') ' . $product ?></p>
                <?php } ?>
                <?php if ($update > 10) { ?>
                    <p style="text-align: right;"><a href="<?= admin_url() . 'edit.php?post_type=product' ?>">View all</a></p>
            <?php } ?>
            </div> 
            <?php
        }
       // return;
    }

    /*
      Function Name: wxm_get_post_by_name
      Description: The function checks if the post exists in wooCommerce or not.
      Parameters: Post Name (String).
      Returns: Post Id (Int) if exist or false.
    */
    function wxm_get_post_by_name($postName) {
        $postName = trim($postName);
        global $table_prefix, $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT ID FROM '.$table_prefix.'posts
            WHERE post_name = %s ', $postName));
        if ($result) {
            return $result->ID;
        }
        return false;
    }

    /*
      Function Name: wxm_update_post_by_variant_id
      Description: The function update product variant.
      Parameters: Post id (Int), variant id (Int), attributes having multiple options (Array), variant (Array), product (Array).
      Returns: 
    */
    function wxm_update_post_by_variant_id($post_id, $post_var_id, $attributesHavingMultipleOptions, $variant, $productData) {
        if ($variant['amount']) {
            update_post_meta($post_var_id, '_stock_status', 'instock');
            update_post_meta($post_var_id, '_visibility', 'visible');
            update_post_meta($post_var_id, '_stock', $variant['amount']);
            update_post_meta($post_var_id, '_manage_stock', 'yes');
        } elseif($productData['amount']) {
            update_post_meta($post_var_id, '_stock_status', 'instock');
            update_post_meta($post_var_id, '_visibility', 'visible');
            update_post_meta($post_var_id, '_stock', $productData['amount']);
            update_post_meta($post_var_id, '_manage_stock', 'yes');
        } else {
            update_post_meta($post_var_id, '_stock_status', 'outofstock');
            update_post_meta($post_var_id, '_visibility', 'hidden');
        }
        // set attributes.
        if (isset($variant['attributeTerms']) && !empty($variant['attributeTerms'])) {
            $thedata = [];
            $attributes = [];
            foreach ($variant['attributeTerms'] as $key => $term) {
                $wpAttributeId = $this->wxm_is_attribute_exist_by_name($term->attribute_name);
                if($wpAttributeId) {
                    //$wpAttributeId = $this->wxm_get_wp_ids_from_xcart_ids('attribute', [$term->attribute_id]);
                    $attributeTaxonomy = $this->wxm_get_taxonomy_by_id($wpAttributeId->attribute_id);
                    if (count($attributeTaxonomy)) {
                        $attribute_name = $attributeTaxonomy->attribute_name;
                        array_push($attributes, $attribute_name);
                        //$wpAttributeTermId = $this->wxm_get_wp_ids_from_xcart_ids('attribute-term', [$term->attribute_option_id]);
                        $wpAttributeTermId = term_exists($term->name);
                        if($wpAttributeTermId) {
                            $termTaxonomy = $this->wxm_get_attribute_term_taxonomy_by_id($wpAttributeTermId);
                            if (count($termTaxonomy)) {
                                $termName = $termTaxonomy->slug;
                                update_post_meta($post_var_id, 'attribute_pa_' . $attribute_name, $termName);
                                $thedata['pa_' . $attribute_name] = $termName;
                                $term_taxonomy_ids = wp_set_object_terms($post_var_id, $termName, 'pa_' . $attribute_name, true);
                            }
                        }
                    }
                }
            }
            
            if(count($attributesHavingMultipleOptions)) {
            	// remove variant attribute from all attributes having multiple options.
            	$attributesHavingMultipleOptions = array_diff($attributesHavingMultipleOptions, $attributes);
            	foreach ($attributesHavingMultipleOptions as $key => $attribute) {
            		$terms = wp_get_post_terms($post_id, 'pa_'.$attribute, array("fields" => "all"));
            		if(count($terms)) {
            			$thedata['pa_' . $attribute] = $terms[0]->slug;
            		}
            	}
            }

            if ($variant['defaultValue']) {
                update_post_meta($post_id, '_default_attributes', $thedata);
            }
        }
        if($variant['price'] > 0) {
            update_post_meta($post_var_id, '_regular_price', $variant['price']);
            update_post_meta($post_var_id, '_price', $variant['price']);
        } elseif($productData['price'] > 0) {
            update_post_meta($post_var_id, '_regular_price', $productData['price']);
            update_post_meta($post_var_id, '_price', $productData['price']);
        } else {
            update_post_meta($post_var_id, '_regular_price', 0);
            update_post_meta($post_var_id, '_price', 0);
        }
        
        update_post_meta($post_var_id, '_weight', $variant['weight']);
        update_post_meta($post_var_id, '_sku', $variant['sku']);        
    }

    /*
      Function Name: wxm_atrribute_terms_by_product_id
      Description: The function returns X-Cart attribute terms by product id and attribute id.
      Parameters: X-Cart product id (Int), X-Cart Attribute id (Int)
      Returns: Specific Attribute terms for a specific product (Array) or false.
    */
    function wxm_atrribute_terms_by_product_id($xcart_product_id, $attribute_id) {
        $query = 'SELECT xcavs.attribute_option_id, xcaot.name FROM xc_attribute_values_select xcavs
            JOIN xc_attribute_option_translations xcaot
            ON xcavs.attribute_option_id = xcaot.id
            WHERE xcavs.product_id = ' . $xcart_product_id . ' AND xcavs.attribute_id = ' . $attribute_id . ' 
            GROUP BY xcavs.attribute_option_id ';
        $result = $this->xcartDB->get_results($query);

        return(count($result))?$result:false;

        /*if (count($result)) {
            $terms = array();
            foreach ($result as $key => $term) {
                array_push($terms, $term->attribute_option_id);
            }
            $xcartTermIdsArray = $terms;
            $wpTermIdsArray = $this->wxm_get_wp_ids_from_xcart_ids('attribute-term', $xcartTermIdsArray);
            if ($wpTermIdsArray) {
                $wpTermIdsArray = implode(',', $wpTermIdsArray);
                global $wpdb;
                $result = $wpdb->get_results('SELECT * FROM wp_terms
                    WHERE term_id IN (' . $wpTermIdsArray . ') ');

                return $result;
            }
        }*/
    }

    /*
      Function Name: wxm_is_attribute_is_variation
      Description: The function checks if the attribute is used for variantion or not by product id and attribute id.
      Parameters: X-Cart product id (Int), X-Cart Attribute id (Int)
      Returns: true or false (boolean).
    */
    function wxm_is_attribute_is_variation($product_id, $xcartAttributeId) {
        $query = 'SELECT COUNT(*) AS count FROM xc_product_variants_attributes
            WHERE product_id = '.$product_id.' AND attribute_id = '.$xcartAttributeId.' ';
        $result = $this->xcartDB->get_row($query);
        return $result->count;
    }

    /*
      Function Name: wxm_get_product_variants
      Description: The function returns X-Cart products variants by xcart product id.
      Parameters: X-Cart product id (Int)
      Returns: All product variants (Array).
    */
    function wxm_get_product_variants($product_id) {
        $query = 'SELECT * FROM xc_product_variants
            WHERE product_id = ' . $product_id;
        $variants = $this->xcartDB->get_results($query);

        if (!empty($variants)) {

            // get variant attribute.
            /* $query = 'SELECT * FROM xc_product_variants_attributes
              WHERE product_id = '.$product_id;
              $attributes = $this->xcartDB->get_results($query);
              if(!empty($attributes)) {
              $variants['attributes'] = $attributes;
              } */

            foreach ($variants as $key => $variant) {
                // get variant attribute terms.
                $query = 'SELECT t1.variant_id, t2.attribute_option_id, 
                t2.attribute_id,t3.name, t4.name attribute_name
                FROM xc_product_variant_attribute_value_select t1
                    JOIN xc_attribute_values_select t2
                    ON t1.attribute_value_id = t2.id
                    JOIN xc_attribute_option_translations t3
                    ON t2.attribute_option_id = t3.id
                    JOIN xc_attribute_translations t4
                    ON t2.attribute_id = t4.id
                    WHERE variant_id = ' . $variant->id . ' 
                    AND t4.code = "en" ';
                $attributeTerms = $this->xcartDB->get_results($query);
                if (!empty($attributeTerms)) {
                    $variants[$key]->attributeTerms = $attributeTerms;
                }

                // get variant images.
                $query = 'SELECT * FROM xc_product_variant_images
                    WHERE product_variant_id = ' . $variant->id;
                $variantImage = $this->xcartDB->get_results($query);
                if (!empty($variantImage)) {
                    $variants[$key]->variantImage = $variantImage;
                }
            }

            return $variants;
        }
    }

    /*
      Function Name: wxm_get_related_products
      Description: The function returns X-Cart related products by xcart product id.
      Parameters: X-Cart product id (Int)
      Returns: All related products (Array) or false.
    */
    function wxm_get_related_products($product_id) {
        $query = 'SELECT xcup.product_id, xcp.sku FROM xc_upselling_products xcup
        JOIN xc_products xcp
        ON xcup.product_id = xcp.product_id
            WHERE xcup.parent_product_id = ' . $product_id;
        $relatedProducts = $this->xcartDB->get_results($query);

        return(count($relatedProducts))?$relatedProducts:false;        
    }

    /*
      Function Name: wxm_get_taxonomy_by_id
      Description: The function returns X-Cart attribute taxonomy by xcart attribute id.
      Parameters: X-Cart attribute id (Int)
      Returns: Attribute (Array).
    */
    function wxm_get_taxonomy_by_id($attribute_id) {
        global $table_prefix, $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'woocommerce_attribute_taxonomies
            WHERE attribute_id = %d ', $attribute_id));

        return $result;
    }

    /*
      Function Name: wxm_get_attribute_term_taxonomy_by_id
      Description: The function returns wooCommerce attribute term by wooCommerce term id.
      Parameters: wooCommerce attribute term id (Int)
      Returns: Attribute term (Array) or false.
    */
    function wxm_get_attribute_term_taxonomy_by_id($term_id) {
        global $table_prefix, $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'terms
            WHERE term_id = %d ', $term_id));

        return($result)?$result:false;
    }

    /*
      Function Name: wxm_get_categories_from_product_id
      Description: The function returns X-Cart categories by product id.
      Parameters: X-Cart product id (Int)
      Returns: categories (Array).
    */
    function wxm_get_categories_from_product_id($xcart_product_id) {
        $query = 'SELECT category_id FROM xc_category_products
    		WHERE product_id = ' . $xcart_product_id . ' ';
        $result = $this->xcartDB->get_results($query);

        if (!empty($result)) {
            $categories = array();
            foreach ($result as $key => $category) {
                array_push($categories, $category->category_id);
            }
            return $categories;
        }
    }

    /*
      Function Name: wxm_get_attributes_from_product_id
      Description: The function returns X-Cart attributes by xcart product id.
      Parameters: X-Cart product id (Int)
      Returns: attributes (Array).
    */
    function wxm_get_attributes_from_product_id($xcart_product_id) {
        $query = 'SELECT xcavs.attribute_id,  xcat.name
                FROM xc_attribute_values_select xcavs
                JOIN xc_attribute_translations xcat 
                ON xcavs.attribute_id = xcat.id
            WHERE product_id = ' . $xcart_product_id . '
            AND  xcat.code = "en"
            GROUP BY attribute_id ';
        $result = $this->xcartDB->get_results($query);
        return $result;

        /*if (!empty($result)) {
            $attributes = array();
            foreach ($result as $key => $attribute) {
                array_push($attributes, $attribute->attribute_id);
            }
            return $attributes;
        }*/
    }

    /*
      Function Name: wxm_get_wp_ids_from_xcart_ids
      Description: The function returns wooCommerce Ids for specific term by term and xcart id.
      Parameters: term (String), xcart id (Array).
      Returns: wooCoomerce Id (Array) or false.
    */
    function wxm_get_wp_ids_from_xcart_ids($term, $xcartIds) {
        $wpIds = array();
        if (!empty($xcartIds)) {
            foreach ($xcartIds as $key => $id) {
                global $table_prefix, $wpdb;
                $wp_id = $wpdb->get_row($wpdb->prepare('SELECT wp_id 
                    FROM '.$table_prefix.'xcart_relation
                    WHERE term = %s AND xcart_id = %d ', $term, $id));
                if (!empty($wp_id)) {
                    array_push($wpIds, $wp_id->wp_id);
                }
            }
            return $wpIds;
        }
        return false;
    }

    /*
      Function Name: wxm_get_xcart_ids_from_wp_ids
      Description: The function returns xcart Ids for specific term by term and wooCoomerce id.
      Parameters: term (String), wooCoomerce id (Array).
      Returns: xcart Id (Array) or false.
    */
    function wxm_get_xcart_ids_from_wp_ids($term, $wpIds) {
        $xcartIds = array();
        if (!empty($wpIds)) {
            foreach ($wpIds as $key => $id) {
                global $table_prefix, $wpdb;
                $xcart_id = $wpdb->get_row($wpdb->prepare('SELECT xcart_id 
                    FROM '.$table_prefix.'xcart_relation
                    WHERE term = %s AND wp_id = %d ', $term, $id));
                if (!empty($xcart_id)) {
                    array_push($xcartIds, $xcart_id->xcart_id);
                }
            }
            return $xcartIds;
        }
        return false;
    }


    // commented for testing.

    /*private function saveProductDesc($details) {
        foreach ($details as $productData) {
            $productData = (array) $productData;
            $pid = wc_get_product_id_by_sku($productData['sku']);
            if (!empty($pid)) {
                $wp_record = array(
                    'ID' => $pid,
                    'post_title' => $productData['product'],
                    'post_content' => $productData['fulldescr'],
                    'post_excerpt' => $productData['descr'],
                );
                $post_id = wp_update_post($wp_record);
                if (is_wp_error($post_id)) {
                    $errors = $post_id->get_error_messages();
                    $response['error'] = implode(';', $response);
                    foreach ($errors as $error) {
                        echo $error;
                    }
                } else {
                    $response['status'] = TRUE;
                    $response['is_new'] = FALSE;
                }
            }
        }
    }

    private function saveProductPrice($details) {
        foreach ($details as $productData) {
            $productData = (array) $productData;
            $pid = wc_get_product_id_by_sku($productData['sku']);
            if (!empty($pid)) {
                $post_id = $pid;
                if (is_wp_error($post_id)) {
                    $errors = $post_id->get_error_messages();
                    $response['error'] = implode(';', $response);
                    foreach ($errors as $error) {
                        echo $error;
                    }
                } else {
                    $response['status'] = TRUE;
                    $response['is_new'] = FALSE;
                }
                update_post_meta($post_id, '_regular_price', $productData['price']);
                update_post_meta($post_id, '_sale_price', $productData['salePriceValue']);
                update_post_meta($post_id, '_price', $productData['price']);
            }
        }
    }

    private function saveProductImage($details) {
        foreach ($details as $productData) {
            $productData = (array) $productData;
            $pid = wc_get_product_id_by_sku($productData['sku']);
            if (!empty($pid)) {
                $post_id = $pid;
                if (is_wp_error($post_id)) {
                    $errors = $post_id->get_error_messages();
                    $response['error'] = implode(';', $response);
                    foreach ($errors as $error) {
                        echo $error;
                    }
                } else {
                    $response['status'] = TRUE;
                    $response['is_new'] = FALSE;
                }
            }
        }
    }*/

    /*
      Function Name: wxm_wordpress_images
      Description: The function download product thumbnail image from image url.
      Parameters: image (Array), product id (Int).
      Returns: true or false.
    */
    private function wxm_wordpress_images($data, $post_id) {
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        require_once(ABSPATH . "wp-admin" . '/includes/file.php');
        require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        // get xcart database credentials.
        global $table_prefix, $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
            WHERE id = %d ', 1));
        
        $web_directory = $result->web_directory;
       // $path = $web_directory . 'var/images/product/366.440/' . $data['path'];
        $path = $web_directory . 'images/product/' . $data['path'];
        $thumb_url = $path;
        $tmp = download_url($thumb_url);
        preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
        $file_array['name'] = basename($matches[0]);
        $file_array['tmp_name'] = $tmp;
        if (is_wp_error($tmp)) {
            @unlink($file_array['tmp_name']);
            $file_array['tmp_name'] = '';

            /*$path = $web_directory . 'images/product/' . $data['path'];
            $thumb_url = $path;
            $tmp = download_url($thumb_url);
            preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
            $file_array['name'] = basename($matches[0]);
            $file_array['tmp_name'] = $tmp;*/


            //$logtxt .= "Error: download_url error - $tmp\n";
            //$logtxt = "Error: download_url error.";
            //echo $logtxt;
        }
        //use media_handle_sideload to upload img:        
        $thumbid = media_handle_sideload($file_array, $post_id, (!empty($data['alt'])) ? $data['alt'] : '' );
        // If error storing permanently, unlink
        if (is_wp_error($thumbid)) {
            @unlink($file_array['tmp_name']);
            //$logtxt .= "Error: media_handle_sideload error - $thumbid\n";
            //$logtxt = "Error: media_handle_sideload error.";
            //echo $logtxt;
            return 0;
        } else {
            set_post_thumbnail($post_id, $thumbid);
            //update_post_meta($post_id, '_product_image_gallery', $thumbid, true);
            return 1;
        }
    }

    /*
      Function Name: wxm_wordpress_variant_images
      Description: The function download product variant images from image url.
      Parameters: image (Array), product id (Int).
      Returns: true or false.
    */
    private function wxm_wordpress_variant_images($data, $post_id) {
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        require_once(ABSPATH . "wp-admin" . '/includes/file.php');
        require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        // get xcart database credentials.
        global $table_prefix, $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
            WHERE id = %d ', 1));
        $web_directory = $result->web_directory;
        $path = $web_directory . 'images/product_variant/' . $data['path'];
        $thumb_url = $path;
        $tmp = download_url($thumb_url);
        preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
        $file_array['name'] = basename($matches[0]);
        $file_array['tmp_name'] = $tmp;
        if (is_wp_error($tmp)) {
            @unlink($file_array['tmp_name']);
            $file_array['tmp_name'] = '';

            //$logtxt .= "Error: download_url error - $tmp\n";
            //$logtxt = "Error: download_url error.";
            //echo $logtxt;
        }
        //use media_handle_sideload to upload img:        
        $thumbid = media_handle_sideload($file_array, $post_id, (!empty($data['alt'])) ? $data['alt'] : '' );
        // If error storing permanently, unlink
        if (is_wp_error($thumbid)) {
            @unlink($file_array['tmp_name']);
            //$logtxt .= "Error: media_handle_sideload error - $thumbid\n";
            //$logtxt = "Error: media_handle_sideload error.";
            //echo $logtxt;
            return 0;
        } else {
            set_post_thumbnail($post_id, $thumbid);
            return 1;
        }
    }

    /*
      Function Name: wxm_save_product_gallary_images
      Description: The function attach gallery images to product.
      Parameters: product id (Int).
      Returns: 
    */
    function wxm_save_product_gallary_images($post_id) {
        // get gallary images from $post_id.
        $gallaryImages = $this->wxm_get_gallary_images_from_post_id($post_id);
        $imageArray = [];
        if (!empty($gallaryImages)) {
            foreach ($gallaryImages as $key => $image) {
                array_push($imageArray, $image->ID);
            }
        }
        if (!empty($imageArray)) {
            $imageArray = array_unique($imageArray);
            $imageArray = implode(',', $imageArray);
            update_post_meta($post_id, '_product_image_gallery', $imageArray, true);
        }
    }

    /*
      Function Name: wxm_get_post_parent_from_post_id
      Description: The function returns post parent id by wooCommerce product id.
      Parameters: product id (Int).
      Returns: parent id (Array) or false.
    */
    function wxm_get_post_parent_from_post_id($post_id) {
        global $table_prefix, $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT post_parent FROM '.$table_prefix.'posts
            WHERE ID = %d ', $post_id));

        return(!empty($result)) ? $result : false;
    }

    /*
    
      Function Name: wxm_get_gallary_images_from_post_id
      Description: The function returns wooCommerce gallery images by product id.
      Parameters: product id (Int).
      Returns: gallery images (Array) or false.
    */
    function wxm_get_gallary_images_from_post_id($post_id) {
        global $table_prefix, $wpdb;
        $result = $wpdb->get_results('SELECT ID FROM '.$table_prefix.'posts
            WHERE post_parent = ' . $post_id . '
            AND post_type = "attachment"
             ');

        return(!empty($result)) ? $result : false;
    }

    /*
      Function Name: wxm_wordpress_gallery_images
      Description: The function download product gallery images from product id.
      Parameters: image (Array), product id (Int).
      Returns: thumb id or null.
    */
    private function wxm_wordpress_gallery_images($data, $post_id) {
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        require_once(ABSPATH . "wp-admin" . '/includes/file.php');
        require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        $thumbid = NULL;
        // get xcart database credentials.
        global $table_prefix, $wpdb;
        $result = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$table_prefix.'xcart_database
            WHERE id = %d ', 1));
        $web_directory = $result->web_directory;
        $path = $web_directory . 'images/product/' . $data['path'];
        $thumb_url = $path;
        $tmp = download_url($thumb_url);
        preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
        $file_array['name'] = basename($matches[0]);
        $file_array['tmp_name'] = $tmp;
        if (is_wp_error($tmp)) {
            @unlink($file_array['tmp_name']);
            $file_array['tmp_name'] = '';
            //$logtxt .= "Error: download_url error - $tmp\n";
            //echo $logtxt;
        }
        $thumbid = media_handle_sideload($file_array, $post_id, (!empty($data['alt']))?$data['alt']:'');
        if (is_wp_error($thumbid)) {
            @unlink($file_array['tmp_name']);
            //$logtxt .= "Error: media_handle_sideload error - $thumbid\n";
            //echo $logtxt;
        }
        return $thumbid;
    }

    /*
      Function Name: wxm_get_attachement_id
      Description: The function checks and returns attachmet id if exist by product id.
      Parameters: image (Array), product id (Int).
      Returns: attachment id or null.
    */
    private function wxm_get_attachement_id($data = array(), $pid) {
        global $table_prefix, $wpdb;
        $upload_dir_paths = wp_upload_dir();
        $attachment_id = NULL;
        $thumb_url = $data['path'];
        preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
        $attachment_url = $upload_dir_paths['baseurl'] . "/" . basename($matches[0]);

        if (false !== strpos($attachment_url, $upload_dir_paths['baseurl'])) {
            $attachment_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url);
            $attachment_url = str_replace($upload_dir_paths['baseurl'] . '/', '', $attachment_url);

            $attachment_id = $wpdb->get_row($wpdb->prepare("SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = %s AND wpostmeta.meta_value LIKE '%" . $attachment_url . "' AND wposts.post_type = %s", '_wp_attached_file', 'attachment'));
        }
        return $attachment_id;
    }

    /*
      Function Name: wxm_get_variant_attachement_id
      Description: The function checks and returns variant attachmet id if exist by product id.
      Parameters: image (Array), product id (Int).
      Returns: attachment id or null.
    */
    private function wxm_get_variant_attachement_id($data = array(), $pid) {
        global $table_prefix, $wpdb;
        $upload_dir_paths = wp_upload_dir();
        $attachment_id = NULL;
        $thumb_url = $data['path'];
        preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
        $attachment_url = $upload_dir_paths['baseurl'] . "/" . basename($matches[0]);

        if (false !== strpos($attachment_url, $upload_dir_paths['baseurl'])) {
            $attachment_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url);
            $attachment_url = str_replace($upload_dir_paths['baseurl'] . '/', '', $attachment_url);

            $attachment_id = $wpdb->get_row($wpdb->prepare("SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = %s AND wpostmeta.meta_value LIKE '%" . $attachment_url . "' AND wposts.post_type = %s", '_wp_attached_file', 'attachment'));
        }
        return $attachment_id;
    }

    /*
      Function Name: wxm_import_order
      Description: The function get orders from X-Cart database and create/update orders, attach order items, attach shipping, attach tax, attach discount in wordpress.
      Parameters:
      Returns: 
    */
    public function wxm_import_order() {
        $orderData = $this->wxm_get_orders();
        $new = 0;
        $update = 0;
        if (!empty($orderData) && count($orderData)) {
            foreach ($orderData as $index => $order) {
                $order = (array) $order;
                //    echo "Order id is procced ". $order['order_id']; 
                $shipping_address = array(
                    'first_name' => $order['address']['firstname']->value,
                    'last_name' => $order['address']['lastname']->value,
                    //     'company' => $order['company'],
                    'phone' => $order['address']['phone']->value,
                    'address_1' => $order['address']['street']->value,
                    //     'address_2' => $order['s_address_2'],
                    'city' => $order['address']['city']->value,
                    'state' => $order['address']['state_id']->state_name,
                    'postcode' => $order['address']['zipcode']->value,
                    'country' => $order['address']['country_code']->country_name,
                    'excerpt' => $order['notes'],
                );

                $billing_address = array(
                    'first_name' => $order['address']['firstname']->value,
                    'last_name' => $order['address']['lastname']->value,
                    'phone' => $order['address']['phone']->value,
                    'email' => $order['login'],
                    'address_1' => $order['address']['street']->value,
                    //    'address_2' => $order['b_address_2'],
                    'city' => $order['address']['city']->value,
                    'state' => $order['address']['state_id']->state_name,
                    'postcode' => $order['address']['zipcode']->value,
                    'country' => $order['address']['country_code']->country_name,
                    //'payment_method' => $this->wxm_get_payment_method($order['payment_method_name']),
                );
                $orderExist = false;
                $wordpressXcardRel = $this->wxm_get_wp_ids_from_xcart_ids('order', [$order['order_id']]);

                if (count($wordpressXcardRel)) {
                    $orderExist = TRUE;
                    $order_id = $wordpressXcardRel[0];
                    $wc_order = new WC_Order($order_id);
                    $update++;
                } else {
                    $wc_order = wc_create_order();
                    $order_id = $wc_order->get_id();
                    $new++;
                }

                $wc_order->set_date_created($order['date']);
                $wc_order->set_address($billing_address, 'billing');
                $wc_order->set_address($shipping_address, 'shipping');

                // set transaction id.
                $transactionId = $this->wxm_get_order_transaction_id( $order['order_id'] );
                if(count($transactionId)) {
                	foreach ($transactionId as $key => $transaction) {
                		update_post_meta($order_id, 'Payment Transaction ID', $transaction->public_id);
                        if(!empty($transaction->method_local_name)) {
                            update_post_meta($order_id, 'Payment Method', $transaction->method_local_name);
                        }
                	}
                }

                // set payment method.
                /*if(!empty($order['payment_method_name'])) {
                	$method_name = $order['payment_method_name'];
                	update_post_meta($order_id, 'Payment Method', $method_name);
                }*/                

                // set shipping method name if found.
                if(!is_null($order['shipping_method_name'])) {
                    $method_name = $order['shipping_method_name'];
                    update_post_meta($order_id, 'Shipping Method', $method_name);
                }                

                // set xcart order number.
                if(!empty($order['orderNumber'])) {
                    update_post_meta($order_id, 'Xcart Order Number', $order['orderNumber']);
                }
                

                // get wp userid from order.
                $wpUserId = $this->wxm_get_wp_ids_from_xcart_ids('user', [$order['profile_id']]);
                if (count($wpUserId)) {
                    update_post_meta($order_id, '_customer_user', $wpUserId[0]);
                }

                // get order coupon.
                $order_coupon = $this->wxm_get_order_coupon($order['order_id']);  
                if ($order_coupon) {
                    $wc_order->add_coupon(array($order['coupon']), $discount = $order_coupon->value);
                    update_post_meta($order_id, '_xcart_coupon_code', $order_coupon->code);
                    update_post_meta($order_id, '_xcart_coupon_discount', $order_coupon->value);
                }

                if (!empty($order['payment_method_name'])) {
                    $wc_order->set_payment_method('');
                    $wc_order->set_payment_method_title($order['payment_method_name']);
                }

                // add private note.
                if(!empty($order['adminNotes'])) {
                	$order_customer_notes = $this->wxm_get_customer_order_notes( $order_id );
                	if(count($order_customer_notes)) {
                		foreach ($order_customer_notes as $key => $note) {
                			wp_delete_comment( $note->comment_ID, true );
                		}
                	}
                	$postdate = date("Y-m-d H:i:s", $order['date']);
                	$data = array(
                        'comment_post_ID' => $order_id,
                        'comment_author' => (!empty($order['address']['firstname']->value)) ? $order['address']['firstname']->value : '',
                        'comment_author_email' => $order['login'],
                        'comment_author_url' => '',
                        'comment_content' => $order['adminNotes'],
                        'comment_type' => 'order_note',
                        'comment_parent' => 0,
                        'user_id' => (count($wpUserId)) ? $wpUserId[0] : 0,
                        'comment_author_IP' => 0,
                        'comment_agent' => '',
                        'comment_date' => $postdate,
                        'comment_approved' => 1
                    );

                    $comment_id = wp_insert_comment($data);
                    /*if ($comment_id) {
                    	add_comment_meta($comment_id, 'is_customer_note', 1);
                    }*/
                }

                // add customer note.
                if(!empty($order['notes'])) {
                	$order_customer_notes = $this->wxm_get_customer_order_notes( $order_id );
                	if(count($order_customer_notes)) {
                		foreach ($order_customer_notes as $key => $note) {
                			// delete only if this is a customer note.
                			$meta_values = get_comment_meta( $note->comment_ID, 'is_customer_note', false );
                			if(count($meta_values)) {
                				wp_delete_comment( $note->comment_ID, true );
                			}
                		}
                	}
                	$postdate = date("Y-m-d H:i:s", $order['date']);
                	$data = array(
                        'comment_post_ID' => $order_id,
                        'comment_author' => (!empty($order['address']['firstname']->value)) ? $order['address']['firstname']->value : '',
                        'comment_author_email' => $order['login'],
                        'comment_author_url' => '',
                        'comment_content' => $order['notes'],
                        'comment_type' => 'order_note',
                        'comment_parent' => 0,
                        'user_id' => (count($wpUserId)) ? $wpUserId[0] : 0,
                        'comment_author_IP' => 0,
                        'comment_agent' => '',
                        'comment_date' => $postdate,
                        'comment_approved' => 1
                    );

                    $comment_id = wp_insert_comment($data);
                    if ($comment_id) {
                    	add_comment_meta($comment_id, 'is_customer_note', 1);
                    }
                }

                if (!$orderExist) {
                    // when order not exist.
                    $orderItems = $this->wxm_get_xcart_order_items($order['order_id']);
                    if(count($orderItems)) {
                    	foreach ($orderItems as $rs) {
	                        if (isset($rs->sku) && !empty($rs->sku)) {
	                            $post_id = wc_get_product_id_by_sku($rs->sku);
	                            if (!$post_id) {
	                                //    echo '<br/>No product(sku) found for order id <b>' . $order['orderid'] . '</b><br/>';
	                                $item_id = wc_add_order_item($order_id, array(
	                                    'order_item_name' => $rs->name,
	                                    'order_item_type' => 'line_item'
	                                ));
	                                //    $extra = unserialize($rs->extra_data);
	                                if ($item_id) {
	                                    wc_add_order_item_meta($item_id, '_name', $rs->name);
                                        wc_add_order_item_meta($item_id, 'cost', $rs->price);
	                                    wc_add_order_item_meta($item_id, '_line_subtotal', wc_format_decimal($rs->subtotal));        // total amount without discount; as xcart feed file 
	                                    wc_add_order_item_meta($item_id, '_line_total', wc_format_decimal($rs->total));   // total is actual amount paid; 
	                                    //wc_add_order_item_meta($item_id, '_line_tax', wc_format_decimal(0));
	                                    wc_add_order_item_meta($item_id, '_variation_id', $rs->variant_id);
	                                    //wc_add_order_item_meta($item_id, '_line_subtotal_tax', wc_format_decimal(0));
	                                    wc_add_order_item_meta($item_id, '_qty', $rs->amount);
	                                    wc_add_order_item_meta($item_id, 'sku', $rs->sku);
	                                }
	                            } else {
	                                $product = wc_get_product($post_id);
	                                if ($product) {
	                                    //    $extra = unserialize($rs->extra_data);
	                                    $item_id = $wc_order->add_product($product, $qty = $rs->amount); // This is an existing SIMPLE product
	                                    if ($item_id) {
	                                        // add item meta data
	                                        wc_add_order_item_meta($item_id, '_name', $product->get_title());
                                            wc_add_order_item_meta($item_id, 'cost', $rs->price);
	                                        wc_add_order_item_meta($item_id, '_tax_class', $product->get_tax_class());
	                                        wc_add_order_item_meta($item_id, '_product_id', $product->ID);
	                                        wc_add_order_item_meta($item_id, '_variation_id', $rs->variant_id);
	                                        wc_add_order_item_meta($item_id, '_line_subtotal', wc_format_decimal($rs->subtotal));
	                                        wc_add_order_item_meta($item_id, '_line_total', wc_format_decimal($rs->total));
	                                        //wc_add_order_item_meta($item_id, '_line_tax', wc_format_decimal(0));
	                                        //wc_add_order_item_meta($item_id, '_line_subtotal_tax', wc_format_decimal(0));
	                                        wc_add_order_item_meta($item_id, '_qty', $rs->amount);
	                                    }
	                                }
	                            }
	                        } else {
	                            echo "<br/>Xcart order Id does not have any item " . $order['order_id'];
	                        }
	                    }
                    }
                    
                } else {
                    // when order exist.
                    $WCorder_items = $wc_order->get_items();
                    if (count($WCorder_items)) {
                        foreach ($WCorder_items as $orderItemId => $value) {
                            $bool = wc_delete_order_item($orderItemId);
                        }
                    }
                    $orderItems = $this->wxm_get_xcart_order_items($order['order_id']);
                    if(count($orderItems)) {
                    	foreach ($orderItems as $rs) {
	                        if (isset($rs->sku) && !empty($rs->sku)) {
	                            $post_id = wc_get_product_id_by_sku($rs->sku);
	                            if (!$post_id) {
	                                //    echo '<br/>No product(sku) found for order id <b>' . $order['orderid'] . '</b><br/>';
	                                $item_id = wc_add_order_item($order_id, array(
	                                    'order_item_name' => $rs->name,
	                                    'order_item_type' => 'line_item'
	                                ));
	                                //    $extra = unserialize($rs->extra_data);
	                                if ($item_id) {
	                                    wc_add_order_item_meta($item_id, '_name', $rs->name);
                                        wc_add_order_item_meta($item_id, 'cost', $rs->price);
	                                    wc_add_order_item_meta($item_id, '_line_subtotal', wc_format_decimal($rs->subtotal));        // total amount without discount; as xcart feed file 
	                                    wc_add_order_item_meta($item_id, '_line_total', wc_format_decimal($rs->total));   // total is actual amount paid; 
	                                    //wc_add_order_item_meta($item_id, '_line_tax', wc_format_decimal(0));
	                                    wc_add_order_item_meta($item_id, '_variation_id', $rs->variant_id);
	                                    //wc_add_order_item_meta($item_id, '_line_subtotal_tax', wc_format_decimal(0));
	                                    wc_add_order_item_meta($item_id, '_qty', $rs->amount);
	                                    wc_add_order_item_meta($item_id, 'sku', $rs->sku);
	                                }
	                            } else {
	                                $product = wc_get_product($post_id);
	                                if ($product) {
	                                    //    $extra = unserialize($rs->extra_data);
	                                    $item_id = $wc_order->add_product($product, $qty = $rs->amount); // This is an existing SIMPLE product
	                                    if ($item_id) {
	                                        // add item meta data
	                                        wc_add_order_item_meta($item_id, '_name', $product->get_title());
                                            wc_add_order_item_meta($item_id, 'cost', $rs->price);
	                                        wc_add_order_item_meta($item_id, '_tax_class', $product->get_tax_class());
	                                        wc_add_order_item_meta($item_id, '_product_id', $product->ID);
	                                        wc_add_order_item_meta($item_id, '_variation_id', $rs->variant_id);
	                                        wc_add_order_item_meta($item_id, '_line_subtotal', wc_format_decimal($rs->subtotal));
	                                        wc_add_order_item_meta($item_id, '_line_total', wc_format_decimal($rs->total));
	                                        //wc_add_order_item_meta($item_id, '_line_tax', wc_format_decimal(0));
	                                        //wc_add_order_item_meta($item_id, '_line_subtotal_tax', wc_format_decimal(0));
	                                        wc_add_order_item_meta($item_id, '_qty', $rs->amount);
	                                    }
	                                }
	                            }
	                        } else {
	                            echo "<br/>Xcart order Id does not have any item " . $order['order_id'];
	                        }
	                    }
                    }
                    
                }
                
                $orderStatus = $this->wxm_get_order_status($order['shipping_status_id']);
                $wc_order->update_status($orderStatus);                

                // set order surcharges.
                if(count($order['surcharges'])) {
                    foreach ($order['surcharges'] as $key => $surcharge) {
                        switch ($surcharge->type) {
                            case 'shipping':
                                if($surcharge->value > 0) {
                                    update_post_meta($order_id, '_created_via', 'checkout');
                                    update_post_meta($order_id, '_order_shipping', wc_format_decimal($surcharge->value));
                                    update_post_meta($order_id, $surcharge->name, wc_format_decimal($surcharge->value));
                                }
                                
                                break;

                            case 'discount':
                                update_post_meta($order_id, '_created_via', 'checkout');
                                update_post_meta($order_id, '_cart_discount', wc_format_decimal($surcharge->value));
                                update_post_meta($order_id, $surcharge->name, wc_format_decimal($surcharge->value));
                                break;

                            case 'tax':
                                if($surcharge->value > 0) {
                                    update_post_meta($order_id, '_created_via', 'checkout');
                                    update_post_meta($order_id, '_order_tax', wc_format_decimal($surcharge->value));
                                    update_post_meta($order_id, $surcharge->name, wc_format_decimal($surcharge->value));
                                }
                                break;
                        }
                    }
                }

                //$wc_order->calculate_totals();
                update_post_meta($order_id, '_order_total', wc_format_decimal($order['total']));

                if (!$orderExist) {
                    // insert into relation table.
                    $relationArray = array(
                        'term' => 'order',
                        'wp_id' => $order_id,
                        'xcart_id' => $order['order_id']
                    );
                    $format = array(
                        '%s',
                        '%d',
                        '%d'
                    );
                    global $table_prefix, $wpdb;
                    $wpdb->insert($table_prefix.'xcart_relation', $relationArray, $format);

                    // update operation weight table.
                    $weightArray = array(
                        'status' => 1
                    );
                    $where = array(
                        'term' => 'orders'
                    );
                    $format = array(
                        '%d'
                    );
                    $whereFormat = array(
                        '%s'
                    );
                    global $table_prefix, $wpdb;
                    $wpdb->update($table_prefix.'xcart_operation_weight', $weightArray, $where, $format, $whereFormat);
                }
            }

            // insert last synced status.
            $dataArray = array(
                'term' => 'order',
                'synced_at' => current_time('mysql')
            );
            $format = array(
                '%s',
                '%s'
            );
            global $table_prefix, $wpdb;
            $wpdb->insert($table_prefix.'xcart_lastSynced', $dataArray, $format);

            if ($new > 0) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?= $new ?> orders created successfully.</strong></p>                
                    <p style="text-align: right;"><a href="<?= admin_url() . 'edit.php?post_type=shop_order' ?>">View all</a></p>
                </div> 
            <?php } ?>

            <?php if ($update > 0) { ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?= $update ?> orders updated successfully.</strong></p>
                    <p style="text-align: right;"><a href="<?= admin_url() . 'edit.php?post_type=shop_order' ?>">View all</a></p>
                </div> 
                <?php
            }
        } else {
            ?>
            <div class="notice notice-error is-dismissible"> 
                <p><strong>Orders not found.</strong></p>            
            </div> 
            <?php
        }
    }

    /*
      Function Name: wxm_get_customer_order_notes
      Description: The function get orders customer notes from wooCommerce by order id.
      Parameters: wooCommerce order id (Int).
      Returns: order customer notes (Array).
    */
    function wxm_get_customer_order_notes( $order_id ) {
    	global $table_prefix, $wpdb;
        $result = $wpdb->get_results('SELECT * FROM '.$table_prefix.'comments
            WHERE comment_post_ID = '.$order_id.' ');

        return $result;
    }

    /*
      Function Name: wxm_get_order_transaction_id
      Description: The function get orders transaction id from xcart by order id.
      Parameters: xcart order id (Int).
      Returns: payment transaction (Array).
    */
    function wxm_get_order_transaction_id( $order_id ) {
    	$query = 'SELECT *
            FROM  xc_payment_transactions
            WHERE order_id = ' . $order_id . ' ';
        $transactions = $this->xcartDB->get_results($query);
        return $transactions;
    }

    /*
      Function Name: wxm_get_order_coupon
      Description: The function get orders coupon xcart by order id.
      Parameters: xcart order id (Int).
      Returns: order coupon (Array) or false.
    */
    function wxm_get_order_coupon($order_id) {
        $query = 'SELECT *
            FROM  xc_order_coupons
            WHERE order_id = '.$order_id.' ';
        $coupon = $this->xcartDB->get_row($query);
        return(!empty($coupon)) ? $coupon : false;
    }

    /*
      Function Name: wxm_get_xcart_order_items
      Description: The function returns xcart orders items by xcart order id.
      Parameters: xcart order id (Int).
      Returns: order items (Array).
    */
    function wxm_get_xcart_order_items($order_id) {
        $query = 'SELECT *
            FROM  xc_order_items
            WHERE order_id = ' . $order_id . ' ';
        $items = $this->xcartDB->get_results($query);
        return $items;
    }

    /*
      Function Name: wxm_get_orders
      Description: The function returns xcart orders from xcart database.
      Parameters: 
      Returns: order (Array).
    */
    function wxm_get_orders() {
        $query = 'SELECT xco.*,xcp.profile_id, xcp.login
        FROM  xc_orders xco 
        JOIN xc_profiles xcp 
        ON xco.profile_id = xcp.profile_id
        WHERE xco.is_order = 1
        LIMIT 0, 500';
        $orders = $this->xcartDB->get_results($query);

        if (!empty($orders)) {
            foreach ($orders as $key => $order) {
                // get surcharges.
                $query = 'SELECT *
                    FROM  xc_order_surcharges
                    WHERE order_id = ' . $order->order_id . ' ';
                $surcharges = $this->xcartDB->get_results($query);
                $order->surcharges = $surcharges;
                // get address.
                $query = 'SELECT *
                    FROM  xc_profile_addresses
                    WHERE profile_id = ' . $order->profile_id . ' ';
                $address = $this->xcartDB->get_row($query);
                $address_id = $address->address_id;
                $query = 'SELECT xcafv.*,xcaf.serviceName
                    FROM  xc_address_field_value xcafv 
                    JOIN xc_address_field xcaf 
                    ON xcafv.address_field_id = xcaf.id
                    WHERE xcafv.address_id = ' . $address_id . '
                    ';
                $addresses = $this->xcartDB->get_results($query);
                // get country _name from country code.
                $addr = [];
                foreach ($addresses as $key => $add) {
                    switch ($add->serviceName) {
                        case 'country_code':
                            $query = 'SELECT country
                                FROM  xc_country_translations
                                WHERE id = "' . $add->value . '" ';
                            $country = $this->xcartDB->get_row($query);
                            $add->country_name = $country->country;
                            break;

                        case 'state_id':
                            $query = 'SELECT state, code
                                FROM  xc_states
                                WHERE state_id = "' . $add->value . '" ';
                            $state = $this->xcartDB->get_row($query);
                            $add->state_name = $state->state;
                            $add->state_code = $state->code;
                            break;
                    }
                    $serviceName = $add->serviceName;
                    $addr[$serviceName] = $add;
                }
                $order->address = $addr;
            }
        }
        return $orders;
    }

    /*
      Function Name: wxm_get_order_status
      Description: The function returns wooCommerce order status from xcart order status id.
      Parameters: xcart order status id (Int)
      Returns: wooCommerce order status (String).
    */
    private function wxm_get_order_status($status) {
        switch ($status) {
            case 1:
                return 'processing';
                break;
            case 2:
                return 'processing';
                break;
            case 3:
                return 'shipped';
                break;
            case 4:
                return 'completed';
                break;
            case 5:
                return 'canceled';
                break;
            case 6:
                return 'refunded';
                break;
            case 7:
                return 'waiting-for-approve';
                break;
            default:
                return 'No status found ' . $status;
        }
    }

    /*
      Function Name: wxm_get_payment_method
      Description: The function returns wooCommerce payment method name from xcart payment method name.
      Parameters: xcart payment method (String)
      Returns: wooCommerce payment method (String).
    */
    private function wxm_get_payment_method($method) {
        switch ($method) {
            case 'Purchase Order':
            case 'Credit or Debit card':
                return 'other';
                break;
            case 'Check (manual processing)':
                return 'check';
                break;
            case 'Credit, Debit, PayPal or PayPal Credit':
                return 'paypal';
                break;
            case 'COD':
                return 'cod';
                break;
            default:
                return 'other';
        }
    }   

}