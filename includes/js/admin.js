$(function() {
    'use strict';
    
    $(document).on('click', '#ieml-view-users', function() {
        $('#list-view-container').hide();
        $('#record-view-container').hide();
        $('#user-view-container').show();
        
        $.post('/ajax.php?a=viewUsers', function(resp) {
            var respObj = $.parseJSON(resp), hstr = '';
            
            
            for (var i=0; i<respObj.length; i++)
                hstr += formatUserRow(respObj[i]);
            
            
            $('#userlist tbody').html(hstr);
        });
        
        return false;
    }).on('click', '#addUser', function() {
        $('#iemlAddUserModal').modal('show');
        
        return false;
    }).on('click', '#iemlAddUserModalAdd', function() {
        var tuples = $('#iemlUser').serializeArray(), formData = {};
        
        $('#iemlAddUserModal').modal('hide');
        
        for (var i in tuples)
            formData[tuples[i]['name']] = tuples[i]['value'];
        
        if (formData['addUserModalPass'] == formData['addUserModalConfPass']) {
            $.post('/ajax.php', { 'a' : formData['a'], 'username' : formData['addUserModalUsername'], 'pass' : formData['addUserModalPass'], 'enumType' : formData['addUserModalType'] }, function(resp) {
                var respObj = $.parseJSON(resp);
                
                if (respObj['pkUser'])
                    $('#userlist tbody').append(formatUserRow(respObj));
            });
        }
        
        return false;
    }).on('click', '.delUser', function() {
        var jthis = $(this);
        
        showConfirmDialog('Are you sure?', function() {
            var jurl = jthis.attr('href');
            
            $.post(jurl, function(resp) {
                jthis.closest('tr').remove();
            });
        });
        
        return false;
    }).on('click', '.editUser', function() {
        
        return false;
    }).on('click', '#confirmCancelModalYes', function() {
		$('#confirmCancelModal').data('callback')();
		
		return false;
	});
});


function formatUserRow(user) {
    return '<tr><td>'+user['strEmail']+'</td><td>'+user['enumType']+'</td><td>'+LightDate.date('Y-m-d H:i:s', LightDate.date_timezone_adjust(user['tsDateCreated']*1000))+'</td><td><!--a href="/ajax.php?a=editUser&pkUser='+user['pkUser']+'" class="editUser btn">Edit</a--><a href="/ajax.php?a=delUser&pkUser='+user['pkUser']+'" class="delUser btn">Delete</a></td></tr>';
}

function showConfirmDialog(text, callback, etc) {
	if (typeof text !== 'undefined' && text != null && text.length > 0) $('#confirmCancelModalText').html(text);
	else $('#confirmCancelModalText').html('Are you sure?');

	$('#confirmCancelModal').data('callback', function() {
		if (typeof callback !== 'undefined' && callback != null) callback();
	});

	$('#confirmCancelModal').modal('show');
}