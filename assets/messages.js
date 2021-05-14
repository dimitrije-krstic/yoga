// SHOW TOOLTIP IMMEDIATELY ON HOVER
    $('.tooltip-show').tooltip({
        show: null,
        trigger : 'hover'
    });

// SHOW FORM TO SEND MESSAGE TO MEMBER
    $('#contactMember, #cancelSendMessage').click(function(){
        $('#contactMember, #contactMemberForm').toggle();
    });

// FORM: SEND MESSAGE TO A MEMBER
    $('#sendMessage').click(function(){
        let subject = $('#subject').val();
        let message = $('#message').val();
        let path = $('#contactMemberForm').data('path')

        if (subject && message) {
            $.ajax({
                type: 'POST',
                url: path,
                data: JSON.stringify({'subject': subject, 'message': message}),
                dataType: "json",
                success: function(data) {
                    toastr.success(data);
                },
                error: function(data) {
                    toastr.error(data);
                }
            });

            $('#contactMember, #contactMemberForm').toggle();
            $('#subject').val('');
            $('#message').val('');
        } else {
            $('#messageError').html('Both subject and message content are required');
        }
    });

// MESSAGE THREAD REPLY
    $('#threadReplyMessageButton').click(function(){
        $('#threadReplyMessageButton').toggle();
        $('#threadReplyMessageButtonSpinner').toggle();
        $('#imageUploadButton').toggle();

        let message = $('#threadReplyMessage').val();
        let path = $('#threadReplyMessage').data('path')

        if (message) {
            $.ajax({
                type: 'POST',
                url: path,
                data: JSON.stringify({'message': message}),
                dataType: "json",
                complete: function(data) {
                location.reload();
              }
            });
        } else {
            $('#threadReplyMessageButton').toggle();
            $('#threadReplyMessageButtonSpinner').toggle();
            $('#imageUploadButton').toggle();
            $('#threadReplyMessageError').html('Message content can not be blank');
        }
    });

// TOGGLE THREAD MESSAGES DISPLAY ON PAGE LOAD
    $(document).ready(function(){
        $('#threadReplyMessageButton').show();
        $('#threadReplyMessageButtonSpinner').hide();
    });

// FORUM IMAGE UPLOAD
    $('.upload-image').on('change', function(event) {
        $('#imageUploadButton').toggle();
        $('#imageUploadButtonSpinner').toggle();
        $('#threadReplyMessageButton').toggle();

        $('#submit-image-upload').click();
    });

// TOGGLE THREAD MESSAGE AS SPAM
    $('#spamMessageThread').on('click', function(event) {
        let path = $(this).data('path');
        $.ajax({
            type: 'POST',
            url: path
        });
    });


// TOGGLE THREAD MESSAGES DISPLAY ON RESIZING
    $(document).ready(function(){
        let messages = $(".threadMessagesMain .direct-chat").clone(true);
        if($('body').width() < 1200) {
            $(".threadMessagesMain .direct-chat").detach().prependTo('.threadMessages');
        }
    });

    $(window).resize(function(){
        if($('body').width() < 1200) {
            if ($(".threadMessages .direct-chat").length == 0){
                $(".threadMessagesMain .direct-chat").detach().prependTo('.threadMessages');
            }
        } else{
            if ($(".threadMessagesMain .direct-chat").length == 0) {
                $(".threadMessages .direct-chat").detach().prependTo('.threadMessagesMain');
            }
        }
    });

// FORM: SEND A NOTIFICATION TO FOLLOWERS
    $('#sendNotification').click(function(){
        let subject = $('#subject').val();
        let message = $('#message').val();
        let path = $('#notifyMemberForm').data('path')
        let isNotify = $('#notifyMemberForm').data('notify')

        if (subject && message) {
            $.ajax({
               type: 'POST',
               url: path,
               data: JSON.stringify({'subject': subject, 'message': message}),
               dataType: "json",
               success: function(data) {
                 if (isNotify) {
                       $('#empty-modal').modal('hide');
                       $('#subject').val('');
                       $('#message').val('');
                       toastr.success(data);
                   } else {
                       window.location.href = '/forum/list';
                   }
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        } else {
            toastr.error('Both subject and message content are required');
        }
    });

// FORM: DELETE OWN NOTIFICATION
    $('.deleteNotification').click(function(){

        let url = $(this).data('url');
        let id = $(this).data('id');

        $.ajax({
           type: 'POST',
           url: url ,
           success: function(data) {
                toastr.success(data);
                $('#thread-'+id).remove() ;
            },
            error: function(data) {
                toastr.error(data);
            }
        });
    });
