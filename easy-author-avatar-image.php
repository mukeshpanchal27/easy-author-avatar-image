<?php
/*
Plugin Name: Easy Author Avatar Image
Plugin URI: https://mukeshpanchal27.com/
Description: 
Version: 1.0.0
Author: Mukesh Panchal
Author URI: https://mukeshpanchal27.com/
Text Domain: easy-author-avatar-image
*/

if ( !class_exists( 'easy_author_avatar_image' ) ) {

    class easy_author_avatar_image {

        private $plugin_name;
        private $version;

        public function __construct() {

            $this->plugin_name = 'easy_author_avatar_image';
            $this->version = '1.0.0';

            add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
            add_action('admin_enqueue_scripts',  [$this, 'enqueue_scripts']);
            add_action('show_user_profile',  [$this, 'admin_author_img_upload']);
            add_action('edit_user_profile', [$this, 'admin_author_img_upload']);
            add_action('personal_options_update',  [$this, 'author_save_custom_img']);
            add_action('edit_user_profile_update',  [$this, 'author_save_custom_img']);
            add_filter('get_avatar', [$this, 'get_easy_author_image'], 10, 5);
        }

        public function enqueue_styles() {

            wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/easy_author_avatar_image.css', array(), $this->version, 'all' );
        }


        public function enqueue_scripts() {

            wp_enqueue_media();
            wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/easy_author_avatar_image.js', array('jquery'), $this->version, false );

            $_media_title = "EasyAuthorAvatarImage";
            $_media_button_title = "Change Profile Image";
            $_delete_button_conform = "Are You Sure To Remove Profile Image";

            wp_localize_script(
                $this->plugin_name,
                'easy_author_avatar_image',
                array(
                    '_media_title' => sanitize_key($_media_title),
                    '_media_button_title' => sanitize_key($_media_button_title),
                    '_delete_button_conform' => sanitize_text_field($_delete_button_conform),
                )
            );
        }

        public function admin_author_img_upload( $user ) {
            $avatar = get_user_meta($user->ID, 'easy_author_avatar_profile_image', true);
            $avatar_url = wp_get_attachment_image_url($avatar);

            $buttontext = "";
            if ('' != $avatar_url) {
                $button_attribute = 'style = "display:block"';
                $buttontext = __('Change Profile Picture');
            } else {
                $button_attribute = 'style = "display:none"';
                $buttontext = __('Upload New Profile Picture');
            }

?>
            <div class="easy_author_avatar_image-upload-wrap">
                <input type="hidden" id="easy_author_avatar_image_id" class="easy_author_avatar_image_input" name="easy_author_avatar_image_id" value="<?PHP echo isset($avatar) ? $avatar : ''; ?>">
                <table class="form-table">
                    <tbody>
                        <tr class="user-profile-picture">
                            <th><?php _e('Profile Picture'); ?></th>
                            <td>
                                <img class="avatar avatar-96 photo easy_author_avatar_image" id="easy_author_avatar_image_custom" src="<?php echo isset($avatar_url) ? esc_url($avatar_url) : '';  ?>" width="150" height="150" alt="" <?php echo $button_attribute; ?> />
                                <div class="easy_author_avatar_image-upload-action" style="position: relative;top: 10px; display: flex;">

                                    <button type="button" class="button easy_author_avatar_image_upload" id="easy_author_avatar_image_upload"><?php echo $buttontext; ?></button>
                                    <button type="button" id="easy_author_avatar_image_delete_btn" class="button easy_author_avatar_image_remove" <?php echo $button_attribute; ?>> <?php echo _e('Delete profile picture'); ?></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

<?php
        }

        public function author_save_custom_img($user_id)
        {

            if (!current_user_can('edit_user', $user_id))
                return false;
            update_user_meta($user_id, 'easy_author_avatar_profile_image', $_POST['easy_author_avatar_image_id']);
        }

        // The meat and potatoes
        public function get_easy_author_image($avatar, $id_or_email, $size, $default, $alt)
        {
            $user = false;

            if (is_numeric($id_or_email)) {

                $id = (int) $id_or_email;
                $user = get_user_by('id', $id);
            } elseif (is_object($id_or_email)) {

                if (!empty($id_or_email->user_id)) {
                    $id = (int) $id_or_email->user_id;
                    $user = get_user_by('id', $id);
                }
            } else {
                $user = get_user_by('email', $id_or_email);
            }

            if ($user && is_object($user)) {

                $get_avatar = get_user_meta($user->ID, 'easy_author_avatar_profile_image', true);
                $avatar_url = "";

                if ($get_avatar) {

                    $avatar_url = wp_get_attachment_image_src($get_avatar)[0];
                    $avatar = "<img alt='' src='{$avatar_url}' class='avatar avatar photo' height='150px' width='150px' />";
                }
            }

            return $avatar;
        }
    }

    $easy_author_avatar_image = new easy_author_avatar_image();
}