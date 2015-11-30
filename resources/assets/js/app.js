function setAlert(message, type) {
    var alert = $('<div class="alert alert-'+type+'" />').text(message).appendTo('#alerts');
    setTimeout(function() {
        alert.fadeOut();
    }, 10000);
}

var liveActivityInterval;
var liveActivityCounter = 0;
var liveActivityFocus = true;

$(window).on("blur focus", function(e) {
    liveActivityFocus = (e.type == 'focus');
    if (liveActivityFocus) {
        liveActivityCounter = 0;

        // Remove color background after 10 seconds
        setTimeout(function() {
            $('tr.warning').removeClass('warning');
            document.title = 'Activity';
        }, 5000)
    }
});

function getLiveActivity() {
    $.ajax({
        url: $('#live-activity').data('live-url'),
        success: function(response) {
            // Update activity
            if (response.activity) {
                $('#activity tbody').prepend(response.activity);
            }

            // Update the interval returned by Github
            if (response.interval) {
                $('#live-activity').data('poll-interval', response.interval);
            }

            // Update focus
            if (!liveActivityFocus) {
                if (response.count) {
                    liveActivityCounter = liveActivityCounter + response.count;
                    document.title = 'Activity (' + liveActivityCounter + ')';
                }
            }

            // Set a new timeout
            liveActivityInterval = setTimeout(getLiveActivity, $('#live-activity').data('poll-interval'));
        },
        error: function() {
            $('#live-activity').find('i').removeClass('fa-spin');
            $('#live-activity').removeClass('btn-primary').addClass('btn-default');
        }
    });
}

var liveActivityInterval;
$(document).ready( function() {
    // Make laravel CSRF happy
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Filtering
    if ($('#filter').length) {
        $('#filter').keyup(function () {
            var rex = new RegExp($(this).val(), 'i');
            $('.searchable tr').hide();
            $('.searchable tr').filter(function () {
                return rex.test($(this).text());
            }).show();

            try {
                localStorage.setItem('my-github-filter', $(this).val());
            } catch (error) {
                // Do nothing
            }
        });

        if ($('#filter').val()) {
            $('#filter').trigger('keyup');
        } else {
            try {
                var filter = localStorage.getItem('my-github-filter');
                if (filter) {
                    $('#filter').val(filter);
                    $('#filter').trigger('keyup');
                }
            } catch (error) {
                // Do nothing
            }
        }
    }

    // Delete branch confirmations
    if ($('#confirmDelete').length) {
        $('#confirmDelete').on('show.bs.modal', function (e) {
            var branch = $(e.relatedTarget).data('branch');
            $(this).find('.modal-body p').html('Delete the branch <strong>'+branch+'</strong>?');

            $('#confirmDelete').find('.modal-footer #confirm').off('click.deleteBranch');
            $('#confirmDelete').find('.modal-footer #confirm').on('click.deleteBranch', function () {
                $.ajax({
                    type: 'post',
                    url: $(e.relatedTarget).data('delete-url'),
                    success: function(response) {
                        $('#confirmDelete').modal('hide');

                        if (response.success) {
                            $(e.relatedTarget).closest('tr').fadeOut();

                            setAlert(branch+' deleted', 'success');
                        } else {
                            setAlert(branch+' could not be deleted: '+response.error, 'danger');
                        }
                    },
                    error: function() {
                        $('#confirmDelete').modal('hide');
                        setAlert(branch+' could not be deleted', 'danger');
                    }
                });
            });
        });
    }

    // Live activity button
    if ($('#live-activity').length) {
        $('#live-activity').on('click', function() {
            if ($(this).hasClass('btn-primary')) {
               // Stop interval
               clearTimeout(liveActivityInterval);

               $(this).find('i').removeClass('fa-spin');
               $(this).removeClass('btn-primary').addClass('btn-default');
            } else {
               $(this).find('i').addClass('fa-spin');
               $(this).removeClass('btn-default').addClass('btn-primary');

               getLiveActivity();
            }
        });
    }
});