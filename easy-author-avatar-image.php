<?php
/**
 * Plugin Name: Easy Author Avatar Image
 * Description: Upload an author image right from your profile page with the click of a button.
 * Version: 1.5.1
 * Author: Mukesh Panchal
 * Author URI: https://mukeshpanchal.com/
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: easy-author-avatar-image
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'easy_author_avatar_image' ) ) {
	class easy_author_avatar_image {
		private $plugin_name = 'easy-author-avatar-image';
		private $version = '1.5.1';

		public function __construct() {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles_scripts' ] );
			add_action( 'show_user_profile', [ $this, 'admin_author_img_upload' ] );
			add_action( 'edit_user_profile', [ $this, 'admin_author_img_upload' ] );
			add_action( 'personal_options_update', [ $this, 'author_save_custom_img' ] );
			add_action( 'edit_user_profile_update', [ $this, 'author_save_custom_img' ] );
			add_filter( 'get_avatar', [ $this, 'get_easy_author_image' ], 10, 5 );

			add_action( 'wp_head', [ $this, 'eaai_render_generator' ] );

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'eaai_plugin_action_links_add_settings' ] );
		}

		public function eaai_render_generator(): void {
			echo '<meta name="generator" content="easy-author-avatar-image ' . esc_attr( $this->version ) . '">' . "\n";
		}

		public function enqueue_styles_scripts() {
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

		public function admin_author_img_upload( $user ) {

			if ( ! current_user_can( 'edit_user', $user->ID ) || ! current_user_can( 'upload_files' ) ) {
				return false;
			}

			$avatar     = get_user_meta( $user->ID, 'easy-author-avatar-profile-image', true );
			$avatar_url = wp_get_attachment_image_url( $avatar );

			$button_class = ! $avatar_url ? ' easy-author-avatar-image-hide': '';
			?>

			<div class="easy-author-avatar-image-upload-wrap" id="easy-author-avatar-image-upload-wrap">
				<input type="hidden" id="easy-author-avatar-image-id" class="easy-author-avatar-image-input" name="easy-author-avatar-image-id" value="<?php echo isset( $avatar ) ? esc_attr( $avatar ) : ''; ?>">
				<h2><?php esc_html_e( 'Easy Author Avatar Image', 'easy-author-avatar-image' ); ?></h2>

				<table class="easy-author-avatar-image-form-table">
					<tbody>
						<tr class="easy-author-avatar-image-user-profile-picture">
							<th><?php esc_html_e( 'Profile Picture', 'easy-author-avatar-image' ); ?></th>
							<td>
								<img class="avatar avatar-96 photo easy-author-avatar-img<?php echo esc_attr( $button_class ); ?>" id="easy-author-avatar-image-custom" src="<?php echo isset( $avatar_url ) ? esc_url( $avatar_url ) : ''; ?>" width="96" height="96" alt="" />

								<div class="easy-author-avatar-image-upload-action">

									<button type="button" class="button easy-author-avatar-image-upload" id="easy-author-avatar-image-upload">
										<?php
											if ( $avatar_url ) {
												echo esc_html__( 'Change Profile Picture', 'easy-author-avatar-image' );
											} else {
												echo esc_html__( 'Upload New Profile Picture', 'easy-author-avatar-image' );
											}
										?>
									</button>
									<button type="button" id="easy-author-avatar-image-delete-btn" class="button easy-author-avatar-image-remove <?php echo esc_attr( $button_class ); ?>">
										<?php esc_html_e( 'Delete profile picture', 'easy-author-avatar-image' ); ?>
									</button>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		<?php
		}

		public function author_save_custom_img( $user_id ) {

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return false;
			}

			/* phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated */
			update_user_meta( $user_id, 'easy-author-avatar-profile-image', sanitize_text_field( wp_unslash( $_POST['easy-author-avatar-image-id'] ) ) );
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
				if ( $get_avatar ) {
					$avatar_url = wp_get_attachment_image_url( $get_avatar );
					$avatar = sprintf(
						"<img alt='%s' src='%s' class='avatar avatar-%d photo' height='%d' width='%d' />",
						esc_attr( $alt ),
						esc_url( $avatar_url ),
						esc_attr( $size ),
						esc_attr( $size ),
						esc_attr( $size )
					);
				}
			}
			return $avatar;
		}

		public function eaai_plugin_action_links_add_settings( $links ) {
			if ( ! is_array( $links ) ) {
				return $links;
			}

			if ( ! current_user_can( 'edit_user', get_current_user_id() ) ) {
				return $links;
			}

			// Add link as the first plugin action link.
			$settings_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_profile_url() . '#easy-author-avatar-image-upload-wrap' ),
				esc_html__( 'Add Profile Picture', 'easy-author-avatar-image' )
			);

			return array_merge(
				array( 'settings' => $settings_link ),
				$links
			);
		}
	}

	$easy_author_avatar_image = new easy_author_avatar_image();
}
