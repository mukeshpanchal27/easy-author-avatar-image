(function ($) {
    'use strict';

    var uploader;
    $(document).on('click', '.easy_author_avatar_image_upload', function () {

        var attachment;
        var currentThis = $(this);
        var img = $("#easy_author_avatar_image_custom");
        var inputId = $('#easy_author_avatar_image_id');
        var deleteBtn = $("#easy_author_avatar_image_delete_btn");
        var currentThisParent = $(currentThis).parents();

        if (uploader) {
            uploader.open();
            return;
        }

        uploader = wp.media.frames.file_frame = wp.media({
            title: easy_author_avatar_image._media_title,
            button: {
                text: easy_author_avatar_image._media_button_title
            },
            multiple: false
        });

        uploader.on('select', function () {

            img.attr("style", "display:block");
            deleteBtn.attr("style", "display:block");

            attachment = uploader.state().get('selection').first().toJSON();
            currentThis.text("Change Profile Picture");
            currentThisParent.find(img).attr('src', attachment.url);
            currentThisParent.find(inputId).attr('value', attachment.id);
        });

        uploader.open();
    });

    // remove button 
    $(document).on('click', '.easy_author_avatar_image_remove', function () {

        var answer = confirm(easy_author_avatar_image._delete_button_conform);
        var currentThis = $(this);
        var img = $("#easy_author_avatar_image_custom");
        var inputId = $('#easy_author_avatar_image_id');
        var deleteBtn = $("#easy_author_avatar_image_delete_btn");
        var uploadBtn = $('#easy_author_avatar_image_upload');
        var currentThisParent = $(currentThis).parents();

        if (answer == true) {

            currentThisParent.find(uploadBtn).text("Upload New Profile Picture");
            currentThisParent.find(img).attr('src', "");
            currentThisParent.find(inputId).attr('value', '');
            deleteBtn.attr("style", "display:none");
            img.attr("style", "display:none");
        }
        return false;
    });

})(jQuery);
