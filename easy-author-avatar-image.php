<?php
/*
Plugin Name: Easy Author Avatar Image
Description: Upload an author image right from your profile page with the click of a button
Version: 1.3
Author: Mukesh Panchal
Author URI: https://mukeshpanchal27.com/
Text Domain: easy-author-avatar-image
*/

if ( !class_exists( 'easy_author_avatar_image' ) ) {
	class easy_author_avatar_image {
		private $plugin_name;
		private $version;

		public function __construct() {
			$this->plugin_name = 'easy-author-avatar-image';
			$this->version = '1.3';

            register_activation_hook( __FILE__, [ $this,'activate_easy_author_avatar_image' ] );
			register_setting( 'easy_author_avatar_image_settings', 'easy_author_avatar_image_option' );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles_scripts' ] );
			add_action( 'show_user_profile', [ $this, 'admin_author_img_upload' ] );
			add_action( 'edit_user_profile', [ $this, 'admin_author_img_upload' ] );
			add_action( 'personal_options_update', [ $this, 'author_save_custom_img' ] );
			add_action( 'edit_user_profile_update', [ $this, 'author_save_custom_img' ] );
			add_filter( 'get_avatar', [ $this, 'get_easy_author_image' ], 10, 5 );
			add_action( 'admin_menu', [ $this, 'admin_menu_page' ] );
            add_filter( 'admin_body_class', [ $this,'easy_author_avatar_image_admin_classes' ] );
            add_action( 'user_edit_form_tag', [ $this, 'easy_author_avatar_image_edit_form_tag' ] );
		}        

		public function enqueue_styles_scripts() {
         
			$easy_author_avatar_image_option = get_option( 'easy_author_avatar_image_option' );		
            $easy_author_avatar_image_roll = $easy_author_avatar_image_option_set = '';
            $user = wp_get_current_user();
            $role = $user->roles[0];

            if ( isset( $easy_author_avatar_image_option['_enable'] ) && $easy_author_avatar_image_option['_enable'] === 'yes' ) {
                $easy_author_avatar_image_option_set = $easy_author_avatar_image_option['_enable'];
            }
            if ( isset( $easy_author_avatar_image_option['_enable_role'][$role] ) && $easy_author_avatar_image_option['_enable_role'][$role] !== '' ) {
                $easy_author_avatar_image_roll = $easy_author_avatar_image_option['_enable_role'][$role];
            }
         
            wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/easy-author-avatar-image.css', array(), $this->version, 'all' );
			
            if ( $easy_author_avatar_image_option_set && $easy_author_avatar_image_roll ) {				
			
                $user = wp_get_current_user();
                $avatar_url = get_avatar_url( $user->ID );

                if ( current_user_can( 'upload_files' ) ) {
                    wp_enqueue_media();
                }

                wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/easy-author-avatar-image.js', array( 'jquery' ), $this->version, 'all' );
				
				wp_localize_script(
					$this->plugin_name,
					'easy_author_avatar_image',
					array(
						'_media_title' => __( 'Choose Image: Default Avatar', 'easy-author-avatar-image' ),
						'_media_button_title' => __( 'Select', 'easy-author-avatar-image' ),
						'_delete_button_conform' => __( 'Are You Sure To Remove Profile Image', 'easy-author-avatar-image' ),
						'_upload_button_text' => __( 'Choose from Media Library', 'easy-author-avatar-image' ),
                        '_default_image_url' => esc_url( $avatar_url ),
                    )
				);
			}
		}

        public function activate_easy_author_avatar_image() {

            $easy_author_avatar_image_option = get_option('easy_author_avatar_image_option');

            if ( empty( $easy_author_avatar_image_option ) ) {
               
                $roles = $this->easy_author_avatar_image_get_role_names();
                
                foreach ( $roles as $key => $value ) {
                    $_enable_role['_enable_role'][$key] = $key;                           
                }
                
                $_enable = array(
                    '_enable' => 'yes', 
                );
                
                $default_options = array_merge( $_enable, $_enable_role );
                update_option( 'easy_author_avatar_image_option', $default_options );
            }
        }
        
		public function admin_menu_page() {
			add_menu_page(
				__( 'Easy Author Avatar Image', 'easy-author-avatar-image' ),
				__( 'Easy Author Avatar Image Settings', 'easy-author-avatar-image' ),
				'manage_options',
				'easy-author-avatar-image',
				array( $this, 'easy_author_avatar_image_page_callback' ),
				'dashicons-businessman'
			);
		}

        public function easy_author_avatar_image_edit_form_tag() {
            echo 'enctype="multipart/form-data"';
        }

        function easy_author_avatar_image_admin_classes( $classes ) {
            global $pagenow;

            $easy_author_avatar_image_option = get_option( 'easy_author_avatar_image_option' );
            if ( isset( $easy_author_avatar_image_option['_enable'] ) && $easy_author_avatar_image_option['_enable'] === 'yes' ) {
                
                $user = wp_get_current_user();
                $role = $user->roles[0];
                
                if ( isset( $easy_author_avatar_image_option['_enable_role'][$role] ) && $easy_author_avatar_image_option['_enable_role'][$role] !== '' ) {
                    
                    if ($pagenow == 'profile.php') {
                    
                        $classes .= ' easy_author_avatar_image';
             
                        return $classes;
                    }
                }
               
            }                    
        }
         
        

        public function easy_author_avatar_image_get_role_names() {

            global $wp_roles;

            if ( ! isset( $wp_roles ) )
                $wp_roles = new WP_Roles();

            return $wp_roles->get_names();
        }

		public function easy_author_avatar_image_page_callback() {  ?>

            <h1><?php _e( 'Easy Author Avatar Image', 'easy-author-avatar-image' ); ?></h1>

            <form method="post" action="options.php">
                <?php
                    settings_fields( 'easy_author_avatar_image_settings' );
                    $easy_author_avatar_image_option = get_option( 'easy_author_avatar_image_option' );
                ?>

                <table>
                    <tbody>
                        <tr>
                            <td>
                                <label class="easy-author-avatar-image-switch">
                                    <input type="checkbox" class="checkbox" name="easy_author_avatar_image_option[_enable]"
                                        value="yes"
                                        <?php echo ( isset( $easy_author_avatar_image_option['_enable'] ) && 'yes' === esc_attr( $easy_author_avatar_image_option['_enable'] ) ) ? ' checked="checked"' : ''; ?> />
                                    <div class="easy-author-avatar-image-slider"></div>
                                </label>
                                <label for=""
                                    class="easy-author-avatar-image-switch-lable"><?php _e( 'Enable Profile Section', 'easy-author-avatar-image' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h2 class="title"><?php _e( 'User Role', 'easy-author-avatar-image' ) ?></h2>
                            </td>
                        </tr>

                        <?php
                            $roles = $this->easy_author_avatar_image_get_role_names();
                            foreach ( $roles as $key => $value ) { 
                        ?>
                            <tr>
                                <td>
                                    <label class="easy-author-avatar-image-switch">
                                        <input type="checkbox" class="checkbox" name="easy_author_avatar_image_option[_enable_role][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr($key); ?>" <?php echo ( isset( $easy_author_avatar_image_option['_enable_role'][$key] ) && ( $key == $easy_author_avatar_image_option['_enable_role'][$key] ) ) ? ' checked="checked"' : ''; ?> />
                                        <div class="easy-author-avatar-image-slider"></div>
                                    </label>
                                    <label for="" class="easy-author-avatar-image-switch-lable"><?php echo esc_attr( $value ); ?></label>
                                </td>
                            </tr>

                        <?php }?>
                    </tbody>
                </table>

                <?php submit_button( __( 'Save Changes', 'easy-author-avatar-image' ) ); ?>
            </form>
            <?php
		}

		public function admin_author_img_upload( $user ) {

			$easy_author_avatar_image_option = get_option( 'easy_author_avatar_image_option' );
            $easy_author_avatar_image_roll = $easy_author_avatar_image_option_set = "";
            $user = wp_get_current_user();
            $role = $user->roles[0];

            if ( isset( $easy_author_avatar_image_option['_enable'] ) && $easy_author_avatar_image_option['_enable'] === 'yes' ) {
                $easy_author_avatar_image_option_set = $easy_author_avatar_image_option['_enable'];
            }
            if ( isset( $easy_author_avatar_image_option['_enable_role'][$role] ) && $easy_author_avatar_image_option['_enable_role'][$role] !== '' ) {
                $easy_author_avatar_image_roll = $easy_author_avatar_image_option['_enable_role'][$role];
            }
 
			if( $easy_author_avatar_image_option_set &&  $easy_author_avatar_image_roll ) {

				$avatar = get_user_meta( $user->ID, 'easy-author-avatar-profile-image', true );
                $button_class = '';

                if( $avatar ) {
                    $avatar_url = wp_get_attachment_image_url( $avatar );                    
                } else {
                    $avatar_url = get_avatar_url( $user->ID );
                    $button_class = ' easy-author-avatar-image-hide';
                }
                
                ?>

                <div class="easy-author-avatar-image-upload-wrap">
                    <input type="hidden" id="easy-author-avatar-image-id" class="easy-author-avatar-image-input" name="easy-author-avatar-image-id" value="<?php echo isset( $avatar ) ? esc_url( $avatar ) : ''; ?>" />
                    <h3><?php _e( 'Easy Author Avatar Image', 'easy-author-avatar-image' ) ?></h3>

                    <table class="easy-author-avatar-image-form-table">
                        <tbody>
                            <tr class="easy-author-avatar-image-user-profile-picture">
                                <th><?php _e( 'Profile Picture', 'easy-author-avatar-image' ); ?></th>
                                <td class="easy-author-avatar-image-show-section">
                                    <img class="avatar avatar-96 photo easy-author-avatar-img" id="easy-author-avatar-image-custom" src="<?php echo isset( $avatar_url ) ? esc_url( $avatar_url ) : ''; ?>" width="96" height="96" alt="" />
                                </td>
                                <td>
                                    <div class=" easy-author-avatar-image-upload-action">
                                        <?php if ( ! current_user_can( 'upload_files' ) ) { ?>

                                            <span class="description">
                                                <?php _e( 'Choose an image from your computer:', 'easy-author-avatar-image' ); ?>
                                            </span><br />
                                            <input type="file" name="easy-author-avatar-image-upload-computer" id="easy-author-avatar-image-upload-computer" class="standard-text" />

                                        <?php } else { ?>
                                            <button type="button" class="button easy-author-avatar-image-upload" id="easy-author-avatar-image-upload">
                                                <?php _e( 'Choose from Media Library', 'easy-author-avatar-image' ); ?>
                                            </button>
                                        <?php } ?>
                                        
                                        <button type="button" id="easy-author-avatar-image-delete-btn" class="button easy-author-avatar-image-remove<?php echo esc_attr( $button_class ); ?>">
                                            <?php echo _e( 'Delete Local Avatar', 'easy-author-avatar-image' ); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <?php }
		}

		public function author_save_custom_img( $user_id ) {
            
			if ( !current_user_can( 'edit_user', $user_id ) ) {
				return false;
			}
           
            $current_user_image_id = $_POST['easy-author-avatar-image-id'];
          
            if ( ! empty( $_FILES['easy-author-avatar-image-upload-computer']['name'] ) ){               
               
                if ( ! function_exists( 'media_handle_upload' ) ) {
				    include_once ABSPATH . 'wp-admin/includes/media.php';
			    }

                $current_user_image_avatar_id = media_handle_upload(
                    'easy-author-avatar-image-upload-computer',
                    0,
                    array(),
                    array(
                        'mimes'                    => array(
                            'jpg|jpeg|jpe' => 'image/jpeg',
                            'gif'          => 'image/gif',
                            'png'          => 'image/png',
                        ),
                        'test_form'                => false,
                        'unique_filename_callback' => array( $this, 'unique_filename_callback' ),
                    )
                );
                
                $current_user_image_id = $current_user_image_avatar_id;                
            }

			update_user_meta( $user_id, 'easy-author-avatar-profile-image', sanitize_text_field( $current_user_image_id ) );
		}

		public function get_easy_author_image( $avatar, $id_or_email, $size, $default, $alt ) {

			$user = false;

            if ( is_numeric( $id_or_email ) ) {
 
                $id = (int) $id_or_email;
                $user = get_user_by( 'id' , $id );
         
            } elseif ( is_object( $id_or_email ) ) {
         
                if ( ! empty( $id_or_email->user_id ) ) {
                    $id = (int) $id_or_email->user_id;
                    $user = get_user_by( 'id' , $id );
                }
         
            } else {
                $user = get_user_by( 'email', $id_or_email );   
            }

			if ( $user && is_object( $user ) ) {
				$get_avatar = get_user_meta( $user->ID, 'easy-author-avatar-profile-image', true );
				$avatar_url = "";

				if ( $get_avatar ) {
					$avatar_url = wp_get_attachment_image_url( $get_avatar );
					$avatar = "<img alt='".esc_attr( $alt )."' src='".esc_url( $avatar_url )."' class='avatar avatar-".esc_attr( $size )." photo' height='".esc_attr( $size )."' width='".esc_attr( $size )."' />";
				}
			}

			return $avatar;
		}
	}

	$easy_author_avatar_image = new easy_author_avatar_image();
}