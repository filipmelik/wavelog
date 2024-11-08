$(document).ready(function () {
    // Update the Radio Interfaces every 2 seconds
    $.get(base_url + 'index.php/radio/status/', function (result) {
        $('.status').html(result);
    });
    $('#radioResultsLoading').hide();
    setInterval(function () {
        // Update the Radio Interfaces every 2 seconds
        $.get(base_url + 'index.php/radio/status/', function (result) {
            $('.status').html(result);
        });
        $('#radioResultsLoading').hide();
    }, 2000);

});

$(document).on('click', '.editCatUrl', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
	editCatUrlDialog(e);
});

function editCatUrlDialog(e) {
	$.ajax({
		url: base_url + 'index.php/radio/editCatUrl',
		type: 'post',
		data: {
			id: e.currentTarget.id,
		},
		success: function (data) {
			BootstrapDialog.show({
				title: 'Edit Callback-URL for CAT',
				size: BootstrapDialog.SIZE_NORMAL,
				cssClass: 'options',
				id: "CatUrlModal",
				nl2br: false,
				message: data,
				onshown: function(dialog) {
				},
				buttons: [{
					label: 'Save',
					cssClass: 'btn-primary btn-sm saveContainerName',
					action: function (dialogItself) {
						saveCatUrl();
						dialogItself.close();
					}
				},
					{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
			});
		},
		error: function (data) {

		},
	});
	return false;
}

function saveCatUrl() {
	$.ajax({
		url: base_url + 'index.php/radio/saveCatUrl',
		type: 'post',
		data: {
			id: $('#catid').val(),
			caturl: $('#CatUrlInput').val()
		},
		error: function (data) {

		},
	});
}

function set_default_radio(radio_id) {
    $('#default_radio_btn_' + radio_id).addClass('running').prop('disable', true);
    $('#default_radio_btn_' + radio_id).removeClass('btn-outline-primary').addClass('btn-primary');
    $.ajax({
        type: 'POST',
        url: base_url + 'index.php/radio/set_default_radio',
        data: {
            radio_id: radio_id
        },
    });
}

function release_default_radio(radio_id) {
    $('#default_radio_btn_' + radio_id).addClass('running').prop('disable', true);
    $.ajax({
        type: 'POST',
        url: base_url + 'index.php/radio/release_default_radio',
        data: {
            radio_id: radio_id
        },
    });
}
