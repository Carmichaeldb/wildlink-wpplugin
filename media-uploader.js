jQuery(document).ready(function($) {
    var mediaUploader;
    
    $('#upload_patient_image_button').click(function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#patient_image').val(attachment.id);
            $('#patient_image_preview').html('<img src="' + attachment.url + '" style="max-width: 200px;" />');
        });
        mediaUploader.open();
    });

    $('#remove_patient_image_button').click(function(e) {
        e.preventDefault();
        $('#patient_image').val('');
        $('#patient_image_preview').html('');
    });

    $('#species_id').change(function() {
        var speciesId = $(this).val();
        var speciesImage = speciesData[speciesId] || '';
        if (speciesImage) {
            $('#patient_image_preview').html('<img src="' + speciesImage + '" style="max-width: 200px;" />');
        } else {
            $('#patient_image_preview').html('');
        }
    });
});