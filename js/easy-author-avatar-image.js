(function ($) {
    'use strict';

    // image upload

    // $(document).on('click', '.easy-author-avatar-image-upload', function () {

    // 	var send_attachment = wp.media.editor.send.attachment;

    // 	var button = $(this);


    // 	wp.media.frames.file_frame = wp.media({
    // 		title: "defaults.upload",
    // 		button: {
    // 			text: "defaults.upload"
    // 		},
    // 		multiple: false
    // 	});

    // 	// Create a new media frame
    // 	frame = wp.media({
    // 		title: 'Select or Upload Media Of Your Chosen Persuasion',
    // 		button: {
    // 			text: 'Use this media'
    // 		},
    // 		multiple: false  // Set to true to allow multiple files to be selected
    // 	});

    // 	wp.media.editor.send.attachment = function (props, attachment) {

    // 		$("#easy-author-avatar-image-custom").show();

    // 		$(button).parent().prev().attr('src', attachment.url);

    // 		var a = $(button).parents().find('#easy-author-avatar-image-id').attr('value', attachment.id);

    // 		$(button).prev().val(attachment.id);

    // 		wp.media.editor.send.attachment = send_attachment;

    // 	}

    // 	wp.media.editor.open(button);

    // 	return false;

    // });

    var uploader;
    $(document).on('click', '.easy-author-avatar-image-upload', function () {

        var attachment;
        var currentThis = $(this);
        var img = $("#easy-author-avatar-image-custom");
        var inputId = $('#easy-author-avatar-image-id');
        var deleteBtn = $("#easy-author-avatar-image-delete-btn");
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
    $(document).on('click', '.easy-author-avatar-image-remove', function () {

        var answer = confirm(easy_author_avatar_image._delete_button_conform);
        var currentThis = $(this);
        var img = $("#easy-author-avatar-image-custom");
        var inputId = $('#easy-author-avatar-image-id');
        var deleteBtn = $("#easy-author-avatar-image-delete-btn");
        var uploadBtn = $('#easy-author-avatar-image-upload');
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
