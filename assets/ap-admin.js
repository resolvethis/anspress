(function ($) {
	/* 
	* for getting unchecked fields
	* source: http://tdanemar.wordpress.com/2010/08/24/jquery-serialize-method-and-checkboxes/ 
	*/
     $.fn.serialize = function (options) {
         return $.param(this.serializeArray(options));
     };
 
     $.fn.serializeArray = function (options) {
         var o = $.extend({
         checkboxesAsBools: false
     }, options || {});
 
     var rselectTextarea = /select|textarea/i;
     var rinput = /text|hidden|password|search/i;
 
     return this.map(function () {
         return this.elements ? $.makeArray(this.elements) : this;
     })
     .filter(function () {
         return this.name && !this.disabled &&
             (this.checked
             || (o.checkboxesAsBools && this.type === 'checkbox')
             || rselectTextarea.test(this.nodeName)
             || rinput.test(this.type));
         })
         .map(function (i, elem) {
             var val = $(this).val();
             return val == null ?
             null :
             $.isArray(val) ?
             $.map(val, function (val, i) {
                 return { name: elem.name, value: val };
             }) :
             {
                 name: elem.name,
                 value: (o.checkboxesAsBools && this.type === 'checkbox') ? //moar ternaries!
                        (this.checked ? '1' : '0') :
                        val
             };
         }).get();
     };
 
})(jQuery);

/* on start */
jQuery(function() {
     
    /* create document */
    APjs.admin = new APjs.admin();
    /* need to call init manually with jQuery */
    APjs.admin.initialize();
 
});
 
/* namespace */
window.APjs = {};
APjs.admin = function() {};
 

APjs.admin.prototype = {
	
	/* automatically called */
	initialize: function() {
		this.recountVotes();
		this.recountViews();
		this.recountFav();
		this.recountFlag();
		this.recountClose();
		this.saveOptions();
		this.editPoints();
		this.savePoints();
		this.newPointForm();
		this.deletePoint();
		this.badges();
		this.deleteFlag();
	},
	
	recountVotes:function(){
		jQuery('[data-action="recount-votes"]').click(function(e){
			jQuery.ajax({  
				type: 'POST',  
				url: ajaxurl,  
				data: {  
					action: 'recount_votes' 
				},  
				context:this,
				success: function(data){ 
					jQuery(this).after('<p>'+data+'</p>')
				} 
			});
		});
	},
	recountViews:function(){
		jQuery('[data-action="recount-views"]').click(function(e){
			jQuery.ajax({  
				type: 'POST',  
				url: ajaxurl,  
				data: {  
					action: 'recount_views' 
				},  
				context:this,
				success: function(data){ 
					jQuery(this).after('<p>'+data+'</p>')
				} 
			});
		});
	},
	recountFav:function(){
		jQuery('[data-action="recount-fav"]').click(function(e){
			jQuery.ajax({  
				type: 'POST',  
				url: ajaxurl,  
				data: {  
					action: 'recount_fav' 
				},  
				context:this,
				success: function(data){ 
					jQuery(this).after('<p>'+data+'</p>')
				} 
			});
		});
	},
	recountFlag:function(){
		jQuery('[data-action="recount-flag"]').click(function(e){
			jQuery.ajax({  
				type: 'POST',  
				url: ajaxurl,  
				data: {  
					action: 'recount_flag' 
				},  
				context:this,
				success: function(data){ 
					jQuery(this).after('<p>'+data+'</p>')
				} 
			});
		});
	},
	recountClose:function(){
		jQuery('[data-action="recount-close"]').click(function(e){
			jQuery.ajax({  
				type: 'POST',  
				url: ajaxurl,  
				data: {  
					action: 'recount_close' 
				},  
				context:this,
				success: function(data){ 
					jQuery(this).after('<p>'+data+'</p>')
				} 
			});
		});
	},
	saveOptions: function(){
		jQuery('#options_form').submit(function(){
			 var checkboxes = jQuery.param( jQuery(this).find('input:checkbox:not(:checked)').map(function() {
			   return { name: this.name, value: this.checked ? this.value : '0' };
			 }));
			jQuery.ajax({  
				type: 'POST',  
				url: ajaxurl,  
				data: jQuery(this).formSerialize() + '&' +checkboxes,  
				context:this,
				dataType:'json',
				success: function(data){
					if(data['status']){
						var html = jQuery(data['html']);
						jQuery('.wrap').prepend(html);
						jQuery('html, body').animate({
							scrollTop: 0
						}, 300);
						jQuery('.wrap .updated').delay(500).slideDown(300);
						html.delay(5000).slideUp(300);
					}
				} 
			});
			return false;
		});
	},
	editPoints:function(){
		jQuery('.wp-admin').delegate('[data-action="ap-edit-point"]', 'click', function(e){
			e.preventDefault();
			var id = jQuery(this).attr('href');
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data: {
					action: 'ap_edit_points',
					id: id
				},  
				context:this,
				dataType:'json',
				success: function(data){
					if(data['status']){
						jQuery('#ap-point-edit').remove();
						jQuery('#anspress-points-table').hide();
						jQuery('#anspress-points-table').after(data['html']);
					}
				}
			});
		});
	},
	savePoints:function(){
		jQuery('.wp-admin').delegate('[data-action="ap-save-point"]', 'submit', function(e){
			e.preventDefault();
			jQuery('.button-primary', this).attr('disabled', 'disabled');
			var id = jQuery(this).attr('href');
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data:  jQuery(this).serialize({ checkboxesAsBools: true }),
				context:this,
				dataType:'json',
				success: function(data){
					if(data['status']){
						jQuery('.wrap').empty().html(data['html']);
					}
				}
			});
			
			return false;
		});
	},
	newPointForm:function(){
		jQuery('.wp-admin').delegate('[data-button="ap-new-point"]', 'click', function(e){
			e.preventDefault();
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data:  {
					action: 'ap_new_point_form'
				},
				context:this,
				dataType:'json',
				success: function(data){
					jQuery('#ap-point-edit').remove();
					jQuery('#anspress-points-table').hide();
					jQuery('#anspress-points-table').after(data['html']);
				}
			});
			
			return false;
		});
	},
	deletePoint:function(){
		jQuery('.wp-admin').delegate('[data-button="ap-delete-point"]', 'click', function(e){
			e.preventDefault();
			var id = jQuery(this).attr('href');
			var args = jQuery(this).data('args');
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data:  {
					action: 'ap_delete_point',
					args: args
				},
				context:this,
				dataType:'json',
				success: function(data){
					jQuery(this).closest('tr').slideUp(200);
				}
			});
			
			return false;
		});
	},
	badges:function(){
		jQuery('.wp-admin').delegate('[data-action="ap-edit-badge"]', 'click', function(e){
			e.preventDefault();
			var id = jQuery(this).attr('href');
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data: {
					action: 'ap_edit_badges',
					id: id
				},  
				context:this,
				dataType:'json',
				success: function(data){
					if(data['status']){
						jQuery('#ap-badge-edit').remove();
						jQuery('#anspress-badge-table').hide();
						jQuery('#anspress-badge-table').after(data['html']);
					}
				}
			});
		});
		
		jQuery('.wp-admin').delegate('[data-action="ap-save-badge"]', 'submit', function(e){
			e.preventDefault();
			jQuery('.button-primary', this).attr('disabled', 'disabled');
			var id = jQuery(this).attr('href');
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data:  jQuery(this).serialize({ checkboxesAsBools: true }),
				context:this,
				dataType:'json',
				success: function(data){
					if(data['status']){
						jQuery('.wrap').empty().html(data['html']);
					}
				}
			});
			
			return false;
		});
		jQuery('.wp-admin').delegate('[data-button="ap-new-badge"]', 'click', function(e){
			e.preventDefault();
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data:  {
					action: 'ap_new_badge_form'
				},
				context:this,
				dataType:'json',
				success: function(data){
					jQuery('#ap-badge-edit').remove();
					jQuery('#anspress-badge-table').hide();
					jQuery('#anspress-badge-table').after(data['html']);
				}
			});
			
			return false;
		});
		jQuery('.wp-admin').delegate('[data-button="ap-delete-badge"]', 'click', function(e){
			e.preventDefault();
			var id = jQuery(this).attr('href');
			var args = jQuery(this).data('args');
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data:  {
					action: 'ap_delete_badge',
					args: args
				},
				context:this,
				dataType:'json',
				success: function(data){
					jQuery(this).closest('tr').slideUp(200);
				}
			});
			
			return false;
		});
	},
	
	
	deleteFlag : function(){
		jQuery('#ap-delete-flag').click(function(e){
			e.preventDefault();
			jQuery.ajax({
				type: 'POST',  
				url: ajaxurl,  
				data:  {
					action	: 'ap_delete_flag',
					flag_id : jQuery(this).data('id'),
					nonce 	: jQuery(this).data('nonce')
				},
				context:this,
				success: function(data){					
					jQuery(this).closest('.flag-item').remove();
				}
			});
		});
	}
}

function ap_option_flag_note(){
	jQuery('body').delegate('[data-action="ap_add_field"]', 'click', function(){
		var copy 	= jQuery(this).data('copy');
		var field_c = jQuery(this).data('field');
		var count = (jQuery(field_c+' > div').length)+1;
		var html = jQuery('#'+copy+' .ap-repeatbable-field').html();
		html = html.replace('#', count).replace('#', count).replace('#', count);
		jQuery(html).appendTo('#'+field_c);

	});
	jQuery('body').delegate('[data-action="ap_delete_field"]', 'click', function(){
		var toggle = jQuery(this).data('toggle');

		jQuery('#'+toggle).remove();
	});
}

if(typeof wpNavMenu != 'undefined'){
	wpNavMenu.addApLink = function( processMethod ) {
		var $checked = jQuery('.aplinks ul .menu-item-title input[type="radio"]:checked');
		var url = $checked.data('url'),
			label = $checked.data('title');


		processMethod = wpNavMenu.addMenuItemToBottom;

		// Show the ajax spinner
		jQuery('.aplinks .spinner').show();
		this.addLinkToMenu( url, label, processMethod, function() {
			// Remove the ajax spinner
			jQuery('.aplinks .spinner').hide();
		});
	};
}
function ap_submit_menu(){
	// Show the ajax spinner
	jQuery('.aplinks #submit-aplinks').click(function(){
		wpNavMenu.addApLink( wpNavMenu.addMenuItemToBottom );
	});
	
	
}
function ap_add_link_to_menu(url, label, callback) {
	callback = callback || function(){};

	ap_add_item_to_menu({
		'-1': {
			'menu-item-type': 'custom',
			'menu-item-url': url,
			'menu-item-title': label
		}
	}, callback);
}
function ap_add_item_to_menu(menuItem, callback) {
	var menu = jQuery('#menu').val(),
		nonce = jQuery('#menu-settings-column-nonce').val(),
		params;
		
	callback = callback || function(){};

	params = {
		'action': 'add-menu-item',
		'menu': menu,
		'menu-settings-column-nonce': nonce,
		'menu-item': menuItem
	};

	jQuery.post( ajaxurl, params, function(menuMarkup) {
		var ins = jQuery('#menu-instructions');
		menuMarkup = jQuery.trim( menuMarkup ); // Trim leading whitespaces
	
		// Make it stand out a bit more visually, by adding a fadeIn
		jQuery( 'li.pending' ).hide().fadeIn('slow');
		jQuery( '.drag-instructions' ).show();
		if( ! ins.hasClass( 'menu-instructions-inactive' ) && ins.siblings().length )
			ins.addClass( 'menu-instructions-inactive' );

		callback();
	});
}
jQuery(document).ready(function (jQuery){  

	/* jQuery( "#ap_q_search" ).autocomplete({
		source: function( request, response ) {  
			jQuery.getJSON( ajaxurl + "?callback=?&action=search_questions", request, function( data ) {  
				response( jQuery.map( data, function( item ) {
					jQuery.each( item, function( i, val ) {
						val.label = val.url; // build result for autocomplete from suggestion array data
					} );
					return item;
				} ) );
		  } );  
		},
		minLength: 2,
		select: function( event, ui ) {
			jQuery('#ap_q').val(ui.item.id);
		},
	}); */
	
	ap_option_flag_note();
	ap_submit_menu();
	//wpNavMenu.init();

	
  /*  var frame;

    jQuery('#default_avatar_upload').on('click', function( event ) {
        var $el = jQuery(this);
        event.preventDefault();

        // Create the media frame.
        frame = wp.media.frames.customHeader = wp.media({
            title: $el.data('choose'),
            library: { // remove these to show all
                type: 'image', // specific mime
                author: userSettings.uid // specific user-posted attachment
            },
            button: {
                text: $el.data('update'), // button text
                close: true // whether click closes 
            }
        });

        // When an image is selected, run a callback.
        frame.on( 'select', function() {
            // Grab the selected attachment.
            var attachment = frame.state().get('selection').first(),
                link = $el.data('updateLink');

            $el.prev('input').val( attachment.attributes.id );
        });

        frame.open();
    }); */
	
}); 

