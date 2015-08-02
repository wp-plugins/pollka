jQuery(document).ready(function() {
	jQuery('.poll_vote_button').click(function() {
		var group = jQuery(this).attr('poll_group');
		var canVote = jQuery(this).attr('poll_canVote');
        var openPoll = jQuery(this).attr('poll_openPoll');
		//window.alert(group);
		var poll_id = jQuery(this).attr('poll_id');
		var option = jQuery('input[name=' + group + ']:checked').val();
		var opt_val = jQuery('input[name=' + group + ']:checked').attr('vote_option');
		if(option != undefined) {
			jQuery.post(ajaxurl, { action: 'poll_vote', value: option, id: poll_id, poll: group, vote_option: opt_val }, function(response) {
				jQuery('#' + group + ' span.poll_votes').css('display', 'inline-block');
				jQuery('#' + group + ' span.poll_bar_empty').css('display', 'inline-block');
				jQuery('#' + group + ' span.poll_bar_full').css('display', 'block');
				jQuery('#' + group + ' p.poll_voter_list').css('display', 'block');
			    if (!openPoll) {
                	jQuery('input[name=' + group + ']').prop('disabled', true);
			        jQuery('input[name=' + group + ']').prop('readonly', true);
			        jQuery('input[name=button_' + group + ']').hide();
                }
				jQuery(response).find('vote').each(function() {
					var id = jQuery(this).attr('id');
					var pol = jQuery(this).attr('poll');
					var cnt = jQuery(this).attr('cnt');
					var val = jQuery(this).text();
					jQuery('#bar-cnt-' + poll_id + '-' + pol + '-' + id).text('(' + cnt + ')');
					jQuery('#bar-bar-' + poll_id + '-' + pol + '-' + id).css('width', val + '%');
				});
				jQuery('#' + group + ' p.poll_voter_list').html(jQuery(response).find('voters').text());
			});
		}
	});
});
