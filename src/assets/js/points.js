jQuery(document).ready(function($) {
	$('.tuja-fieldchoices').change(function() {
		var q_id = parseInt($('#tuja_crewview__filter-questions').val());
		var g_id = parseInt($('#tuja_crewview__filter-groups').val());
		if(q_id > 0 && g_id > 0) {
			location.href = [location.protocol, '//', location.host, location.pathname].join('') + '?' + $.param({q: q_id, g: g_id});
		}
	});
});