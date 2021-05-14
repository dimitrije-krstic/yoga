// INITIALIZE DATETIME PICKERS
    $(document).ready(function(){
        if (typeof $().datetimepicker === "function") {
            $('#datetimepicker1').datetimepicker({
                format: "LL",
                startDate: new Date()
            });
            $('#datetimepicker2').datetimepicker({
                format: 'LT',
                startDate: new Date()
            });
            $('.datetimepicker3').datetimepicker({
                format: "ll",
                startDate: new Date()
            });
        }
    });

// BOOKMARK EVENT
    $('.eventBookmark').on('click', function(event) {
        let path = $(this).data('path');
        let participants = Number($('#eventParticipants').text());

        $.ajax({
            type: 'POST',
            url: path,
            success: function(data) {
                $('#bookmarkEvent').toggleClass('fas far');
                if (data == 'add') {
                    $('#eventParticipants').html(participants + 1);
                } else {
                    $('#eventParticipants').html(participants - 1);
                }

                $('#showEventLinkToParticipant').toggle();
                $('#showEventBookmarkText').toggle();
                $('#eventPassword').toggle();
            }
        });
    });