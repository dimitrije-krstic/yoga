// CHANGE TAG NAME & RE-ASSIGN ALL BELONGING POSTS
    $('.changeTagName').on('click', function(event) {
        let path = $(this).data('path');
        let tagId = $(this).data('id');
        let newSlug = $('#changeTag'+tagId).val();

        $.ajax({
            type: 'POST',
            url: path,
            data: JSON.stringify({'tagId': tagId, 'newSlug': newSlug}),
            dataType: "json"
        });

        location.reload();
    });
