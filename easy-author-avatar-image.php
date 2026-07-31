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
		/**
		 * User meta key holding the attachment ID of the custom avatar.
		 *
		 * Kept in sync with the literal used in uninstall.php.
		 */
		const META_KEY = 'easy-author-avatar-profile-image';

		/**
		 * Admin screens the profile UI is rendered on.
		 */
		const SCREENS = array( 'profile.php', 'user-edit.php' );

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

		public function enqueue_styles_scripts( $hook_suffix = '' ) {

			// The profile UI only renders on these two screens, so nothing here is needed elsewhere.
			// wp_enqueue_media() in particular pulls in the whole media modal bundle.
			if ( ! in_array( $hook_suffix, self::SCREENS, true ) ) {
				return;
			}

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

			$avatar     = get_user_meta( $user->ID, self::META_KEY, true );
			$avatar_url = $avatar ? wp_get_attachment_image_url( $avatar, array( 96, 96 ) ) : false;

			// The stored attachment may have been deleted from the media library since it was set.
			if ( ! $avatar_url ) {
				$avatar = '';
			}

			$button_class = ! $avatar_url ? ' easy-author-avatar-image-hide': '';
			?>

			<div class="easy-author-avatar-image-upload-wrap" id="easy-author-avatar-image-upload-wrap">
				<input type="hidden" id="easy-author-avatar-image-id" class="easy-author-avatar-image-input" name="easy-author-avatar-image-id" value="<?php echo esc_attr( $avatar ); ?>">
				<h2><?php esc_html_e( 'Easy Author Avatar Image', 'easy-author-avatar-image' ); ?></h2>

				<table class="easy-author-avatar-image-form-table">
					<tbody>
						<tr class="easy-author-avatar-image-user-profile-picture">
							<th><?php esc_html_e( 'Profile Picture', 'easy-author-avatar-image' ); ?></th>
							<td>
								<img class="avatar avatar-96 photo easy-author-avatar-img<?php echo esc_attr( $button_class ); ?>" id="easy-author-avatar-image-custom" src="<?php echo $avatar_url ? esc_url( $avatar_url ) : ''; ?>" width="96" height="96" alt="" />

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

			// Mirrors the guard in admin_author_img_upload(). Without the upload_files check a user
			// who was never shown the field would still reach the $_POST read below.
			if ( ! current_user_can( 'edit_user', $user_id ) || ! current_user_can( 'upload_files' ) ) {
				return false;
			}

			// The nonce is verified by core in wp-admin/user-edit.php before this hook fires.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! isset( $_POST['easy-author-avatar-image-id'] ) ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$attachment_id = absint( wp_unslash( $_POST['easy-author-avatar-image-id'] ) );

			// An empty value means the user pressed Delete; remove the row rather than storing ''.
			if ( ! $attachment_id ) {
				delete_user_meta( $user_id, self::META_KEY );
				return true;
			}

			// Never store an ID that isn't an image attachment.
			if ( 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
				return false;
			}

			update_user_meta( $user_id, self::META_KEY, $attachment_id );

			return true;
		}

		public function get_easy_author_image( $avatar, $id_or_email, $size, $default, $alt ) {

			$user_id = $this->resolve_user_id( $id_or_email );

			if ( ! $user_id ) {
				return $avatar;
			}

			$attachment_id = get_user_meta( $user_id, self::META_KEY, true );

			if ( ! $attachment_id ) {
				return $avatar;
			}

			$size = absint( $size );

			// Returns false when the attachment has since been deleted, in which case the
			// default avatar must be left untouched rather than replaced with an empty src.
			$avatar_url = wp_get_attachment_image_url( $attachment_id, array( $size, $size ) );

			if ( ! $avatar_url ) {
				return $avatar;
			}

			return sprintf(
				"<img alt='%s' src='%s' class='avatar avatar-%d photo' height='%d' width='%d' />",
				esc_attr( $alt ),
				esc_url( $avatar_url ),
				$size,
				$size,
				$size
			);
		}

		/**
		 * Resolve the identifier passed to the get_avatar filter into a user ID.
		 *
		 * Mirrors the object handling in core's get_avatar_data(): WP_User exposes ID,
		 * WP_Post exposes post_author, and only WP_Comment exposes user_id.
		 *
		 * @param mixed $id_or_email User ID, email address, WP_User, WP_Post or WP_Comment.
		 * @return int User ID, or 0 when it cannot be resolved.
		 */
		private function resolve_user_id( $id_or_email ) {

			if ( is_numeric( $id_or_email ) ) {
				return absint( $id_or_email );
			}

			if ( $id_or_email instanceof WP_User ) {
				return (int) $id_or_email->ID;
			}

			if ( $id_or_email instanceof WP_Post ) {
				return (int) $id_or_email->post_author;
			}

			if ( $id_or_email instanceof WP_Comment ) {
				if ( ! empty( $id_or_email->user_id ) ) {
					return (int) $id_or_email->user_id;
				}

				// Anonymous commenter: fall back to matching on the address they left.
				if ( ! empty( $id_or_email->comment_author_email ) ) {
					$user = get_user_by( 'email', $id_or_email->comment_author_email );
					return $user ? (int) $user->ID : 0;
				}

				return 0;
			}

			// Any other object exposing a user_id, for forward compatibility.
			if ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
				return (int) $id_or_email->user_id;
			}

			if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
				$user = get_user_by( 'email', $id_or_email );
				return $user ? (int) $user->ID : 0;
			}

			return 0;
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
