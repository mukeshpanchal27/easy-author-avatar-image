(function ($) {
	'use strict';

	var uploader;
	$(document).on('click', '.easy-author-avatar-image-upload', function () {

		var attachment,
			currentThis = $( this ),
			img = $( '#easy-author-avatar-image-custom' ),
			inputId = $( '#easy-author-avatar-image-id' ),
			deleteBtn = $( '#easy-author-avatar-image-delete-btn' ),
			currentThisParent = $( currentThis ).parents();

		if ( uploader ) {
			uploader.open();
			return;
		}

		uploader = wp.media.frames.file_frame = wp.media({
			title: easy_author_avatar_image._media_title,
			library: {
				type: 'image'
			},
			button: {
				text: easy_author_avatar_image._media_button_title
			},
			multiple: false
		});

		uploader.on( 'select', function () {

			img.removeClass( 'easy-author-avatar-image-hide' );
			deleteBtn.removeClass( 'easy-author-avatar-image-hide' );
			attachment = uploader.state().get( 'selection' ).first().toJSON();
			currentThis.text( easy_author_avatar_image._change_button_text );
			currentThisParent.find( img ).attr( 'src', attachment.url );
			currentThisParent.find( inputId ).attr( 'value', attachment.id );
		});

		uploader.open();
	});

	$( document ).on( 'click', '.easy-author-avatar-image-remove', function () {

		var confirm_answer = confirm( easy_author_avatar_image._delete_button_conform ),
			currentThis = $( this ),
			img = $( '#easy-author-avatar-image-custom' ),
			inputId = $( '#easy-author-avatar-image-id' ),
			deleteBtn = $( '#easy-author-avatar-image-delete-btn' ),
			uploadBtn = $( '#easy-author-avatar-image-upload' ),
			currentThisParent = $( currentThis ).parents();

		if ( confirm_answer ) {

			currentThisParent.find( uploadBtn ).text( easy_author_avatar_image._upload_button_text );
			currentThisParent.find( img ).attr( 'src', '' );
			currentThisParent.find( inputId ).attr( 'value', '' );
			deleteBtn.addClass( 'easy-author-avatar-image-hide' );
			img.addClass( 'easy-author-avatar-image-hide' );
		}

		return false;
	});

})( jQuery );
