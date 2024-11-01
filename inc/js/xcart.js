jQuery(document).ready( function($) {

	$('#exportInstructions').click(function(){
        $("#instructions").toggle(1000);
        return false;
    });

	$(".selectOption").change( function() {
		var option = $(this).attr('data-value');
		$.ajax({
		    url: ajax_object.ajaxurl, // this is the object instantiated in wp_localize_script function
		    type: 'POST',
		    data:{ 
		      action: 'wxm_get_weight_status', // this is the function in your functions.php that will be triggered
		      post_var: option
		    },
		    success: function( data ){
		      //Do something with the result from server
		      if(data != 0) {
		      	alert(data);
		      }
		    }
		});
	});
});