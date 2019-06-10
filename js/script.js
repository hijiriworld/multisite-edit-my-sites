(function($){
	$('#hmems_all_apply').on('click', function () {
		var $val = $('#hmems_all_select').val();
		if( $val == 'none' ) return false;
		$("[name^='hmems_check']:checked").each( function(i, elm ) {
			var $input = $(elm);
			$row = $input.closest('tr');
			$row.find("[name^='users_sites']").val($val);
		});
	});
})(jQuery)
