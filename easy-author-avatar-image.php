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
			register_setting( 'easy_author_avatar_image_settings', 'easy_author_avatar_image_option' );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles_scripts' ] );
			add_action( 'show_user_profile', [ $this, 'admin_author_img_upload' ] );
			add_action( 'edit_user_profile', [ $this, 'admin_author_img_upload' ] );
			add_action( 'personal_options_update', [ $this, 'author_save_custom_img' ] );
			add_action( 'edit_user_profile_update', [ $this, 'author_save_custom_img' ] );
			add_filter( 'get_avatar', [ $this, 'get_easy_author_image' ], 10, 5 );
			add_action( 'admin_menu', [ $this, 'admin_menu_page' ] );
		}

		public function enqueue_styles_scripts() {

			$easy_author_avatar_image_option     = get_option( 'easy_author_avatar_image_option' );
			$easy_author_avatar_image_option_set = isset( $easy_author_avatar_image_option ) ? $easy_author_avatar_image_option : '';

			if ( $easy_author_avatar_image_option_set ) {
				wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/easy-author-avatar-image.css', array(), $this->version, 'all' );

				wp_enqueue_media();

				wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/easy-author-avatar-image.js', array( 'jquery' ), $this->version, false );
				
				wp_localize_script(
					$this->plugin_name,
					'easy_author_avatar_image',
					array(
						'_media_title'           => __( 'Choose Image: Default Avatar', 'easy-author-avatar-image' ),
						'_media_button_title'    => __( 'Select', 'easy-author-avatar-image' ),
						'_delete_button_conform' => __( 'Are You Sure To Remove Profile Image', 'easy-author-avatar-image' ),
						'_upload_button_text'    => __( 'Upload New Profile Picture', 'easy-author-avatar-image' ),
						'_change_button_text'    => __( 'Change Profile Picture', 'easy-author-avatar-image' ),
					)
				);
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

		public function easy_author_avatar_image_page_callback() { ?>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'easy_author_avatar_image_settings' );
					$easy_author_avatar_image_option = get_option( 'easy_author_avatar_image_option' );
				?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="easy-author-avatar-image-lable"><?php _e( 'Enable Easy Author Avatar Image', 'easy-author-avatar-image' ); ?></label>
							</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php _e( 'Enable Easy Author Avatar Image', 'easy-author-avatar-image' ); ?>
										</span>
									</legend>
									<label for="easy_author_avatar_image_option">
										<input name="easy_author_avatar_image_option[_enable]" id="easy_author_avatar_image_option" type="checkbox" value="yes" <?php echo ( isset( $easy_author_avatar_image_option['_enable'] ) && ( 'yes' == $easy_author_avatar_image_option['_enable'] ) ) ? ' checked="checked"' : ''; ?> />
										<?php _e( 'Enable Profile Section', 'easy-author-avatar-image' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( __( 'Save Changes', 'easy-author-avatar-image' ) ); ?>
			</form>
		<?php
		}

		public function admin_author_img_upload( $user ) {

			$easy_author_avatar_image_option     = get_option( 'easy_author_avatar_image_option' );
			$easy_author_avatar_image_option_set = isset( $easy_author_avatar_image_option ) ? $easy_author_avatar_image_option : '';

			if ( $easy_author_avatar_image_option_set ) {
				$avatar     = get_user_meta( $user->ID, 'easy-author-avatar-profile-image', true );
				$avatar_url = wp_get_attachment_image_url( $avatar );

				$button_class = ! $avatar_url ? ' easy-author-avatar-image-hide': '';
				?>

				<div class="easy-author-avatar-image-upload-wrap">
					<input type="hidden" id="easy-author-avatar-image-id" class="easy-author-avatar-image-input" name="easy-author-avatar-image-id" value="<?php echo isset( $avatar ) ? esc_attr( $avatar ) : ''; ?>">
					<h3><?php _e('Easy Author Avatar Image', 'easy-author-avatar-image'); ?></h3>

					<table class="easy-author-avatar-image-form-table">
						<tbody>
							<tr class="easy-author-avatar-image-user-profile-picture">
								<th><?php _e( 'Profile Picture', 'easy-author-avatar-image' ); ?></th>
								<td>
									<img class="avatar avatar-96 photo easy-author-avatar-img<?php echo esc_attr( $button_class ); ?>" id="easy-author-avatar-image-custom" src="<?php echo isset( $avatar_url ) ? esc_url( $avatar_url ) : ''; ?>" width="96" height="96" alt="" />

									<div class="easy-author-avatar-image-upload-action">

										<button type="button" class="button easy-author-avatar-image-upload" id="easy-author-avatar-image-upload">
											<?php
												if ( $avatar_url ) {
													echo __( 'Change Profile Picture', 'easy-author-avatar-image' );
												} else {
													echo __( 'Upload New Profile Picture', 'easy-author-avatar-image' );
												}
											?>
										</button>
										<button type="button" id="easy-author-avatar-image-delete-btn" class="button easy-author-avatar-image-remove <?php echo esc_attr( $button_class ); ?>">
											<?php echo _e( 'Delete profile picture', 'easy-author-avatar-image' ); ?>
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

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return false;
			}
			update_user_meta( $user_id, 'easy-author-avatar-profile-image', sanitize_text_field( $_POST['easy-author-avatar-image-id'] ) );
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
