jQuery(document).ready(function ($) {


    // show settings section
jQuery("body").on("click", ".alumnionline-reunion-website-settings-section", function (e) {
    e.preventDefault();
    var selectedid = '.'+jQuery(this).attr('data-id');
    if(jQuery(selectedid).hasClass('hidden')){
    jQuery(selectedid).show();
    jQuery(selectedid).removeClass('hidden');
    jQuery(this).attr('aria-expanded','true');
    }else{
        jQuery(selectedid).hide();
        jQuery(selectedid).addClass('hidden');
        jQuery(this).attr('aria-expanded','false');
    }

});



// trap focus

function alumnionline_reunion_website_trapFocus(element) {
    setTimeout(function() { 
        var focusableEls = element.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="Submit"]:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]),input[type="file"]:not([disabled]), select:not([disabled])');
        var lastFocusableEl = focusableEls[focusableEls.length-1];
        var firstFocusableEl = focusableEls[0];
        KEYCODE_TAB = 9;
  
        element.addEventListener('keydown', function (e) {
        var isTabPressed = (e.key === 'Tab' || e.keyCode === KEYCODE_TAB);
        
        if (!isTabPressed)
        return;
        
        if (e.shiftKey) {
        
        if (document.activeElement === firstFocusableEl) {
        lastFocusableEl.focus();
        e.preventDefault();
        }
        } else {
        if (document.activeElement === lastFocusableEl) {
        firstFocusableEl.focus();
        e.preventDefault();
        }
        }
        
        });
        }, 900);
}

// correct spacebar not triggering button clicks keypress
$("body").on("keypress", ".alumnionline-reunion-website-modal-open", function (event) {
  
    var code = event.key || event.code; //Get keycode.
    if (code != 'Tab' && code != ' ' && code != 'Enter' && code != 'ArrowLeft' && code != 'ArrowUp' && code != 'ArrowRight' && code != 'ArrowDown') //Check each key.
        return; //If it isn't any of those, don't worry about it.

    if ((code == ' ' || code == 'Enter' || code == 'ArrowLeft' || code == 'ArrowRight') && $(':focus').is('input')) //If a space/enter or left/right arrow is pressed in the input, then allow it to happen as normal.
        return;

    event.preventDefault()

jQuery(this).trigger('click');

});


/* show modal */
var openerid;
var edittype;
jQuery("body").on("click", ".alumnionline-reunion-website-modal-open", function (e) {
var id = '#'+jQuery(this).attr('data-id');
openerid = '#'+jQuery(this).attr('id');

e.preventDefault();

//   trap focus
var dialogid = document.getElementById(jQuery(this).attr('data-id'));
alumnionline_reunion_website_trapFocus(dialogid);

    if (jQuery(id).css('display') == 'none') {
        jQuery(id).show();
    
        if(id==='#alumnionline_reunion_website_post_message_div' && jQuery(this).attr('data-editid') !== undefined){
          edittype = 'edit';
          alumnionline_reunion_website_populate_message_values(jQuery(this).attr('data-editid'));
        }
        else if(id==='#alumnionline_reunion_website_post_message_div' && jQuery(this).attr('data-replyid') !== undefined){
          edittype = 'reply';
          alumnionline_reunion_website_populate_message_values(jQuery(this).attr('data-replyid'));
        }

        jQuery(id).find('.alumnionline-reunion-website-modal-close').focus();

   } else {
jQuery(id).hide();
  jQuery(id).focus();
    }
  
return false;
});


// close all open modals
jQuery("body").on("click", "a.alumnionline-reunion-website-modal-close", function (e) {
 
    e.preventDefault();
      jQuery('.alumnionline-reunion-website-modal-wrapper').hide();
    var opener = jQuery(this).parent().parent().attr('id');

    if(jQuery('#alumnionline-reunion-website-post-editid').length > 0)
    jQuery('#alumnionline-reunion-website-post-editid').remove();

    jQuery( ".alumnionline-reunion-website-modal-wrapper input" ).each(function( index ) {
      if(jQuery(this).attr('type') !== 'Submit' && jQuery(this).attr('id') !== 'receipient_userid'){
        jQuery(this).val('');
      }
      });

      jQuery( ".alumnionline-reunion-website-status-message" ).each(function( index ) {
        jQuery(this).html('');
      });

      jQuery( ".alumnionline-reunion-website-pro-status-message" ).each(function( index ) {
        jQuery(this).html('');
      });
  
    if(openerid !== '#undefined'){
      jQuery(openerid).focus();
    }else {
    jQuery('a[data-id='+opener+']').focus();
    }
  
  });
  // close all open modals on escape
  jQuery(document).on("keydown", function (e) {

    if (e.key === "Escape") {
    
      if(jQuery('.modal_wrapper').is(':visible')){
      e.preventDefault();
      }


      if(jQuery('#alumnionline-reunion-website-post-editid').length > 0)
      jQuery('#alumnionline-reunion-website-post-editid').remove();

      jQuery( ".alumnionline-reunion-website-modal-wrapper input" ).each(function( index ) {
        if(jQuery(this).attr('type') !== 'Submit'){
          jQuery(this).val('');
        }
      });

      jQuery( ".alumnionline-reunion-website-status-message" ).each(function( index ) {
        jQuery(this).html('');
      });
  
    jQuery( ".alumnionline-reunion-website-modal-wrapper" ).each(function( index ) {
      if(jQuery(this).is(':visible')){
  
      jQuery(this).hide();
    var opener = jQuery(this).attr('id');
  
    if(openerid !== '#undefined'){
      jQuery(openerid).focus();
    }else {
    jQuery('a[data-id='+opener+']').focus();
    }
      }
      });
    }
  
  });

 // delete post
 jQuery("body").on("click", ".alumnionline-reunion-website-delete-button", function (e) {
  e.preventDefault();
  var seperator='&';

  var resturl = alumnionline_reunion_website_Variables['resturl'];
  var deleteid = jQuery(this).data('deleteid');

  if(resturl.search('/wp-json/')>0) seperator='?';
  
  $.ajax({
  url: alumnionline_reunion_website_Variables['resturl']+'alumnionline_reunion_website/v1/deletemessage/'+seperator+'_wpnonce='+alumnionline_reunion_website_Variables['nonce']+'&deleteid='+deleteid,
  async: true, 
  dataType: "html",
  error: function(e){ console.log('message removal failed');},
  success: 
  function(data){
    jQuery("#alumnionline_reunion_website_results").remove();

    jQuery('body').append(data);
      
    jQuery("#alumnionline-reunion-website-forum-status-message").html(jQuery("#alumnionline_reunion_website_results").detach());
  
    jQuery(".alumnionline-reunion-website-changeview-hidden").trigger('click');
       
    jQuery(".alumnionline-reunion-website-home-view").focus();    

    setTimeout(function() {
    jQuery("#alumnionline-reunion-website-forum-status-message").html(jQuery("#alumnionline_reunion_website_results").detach());
  }, 1000);
      
  }
  });
});	

 // search forum
 jQuery("body").on("click", "#alumnionline-reunion-website-search-btn", function (e) {
    e.preventDefault();
    var seperator='&';
    var resturl = alumnionline_reunion_website_Variables['resturl'];
    jQuery("#alumnionline_reunion_website_results").remove();
    jQuery(".alumnionline-reunion-website-replybtn").remove();
    var keyword = jQuery("#alumnionline-reunion-website-search-keyword").val();
    jQuery('#alumnionline-reunion-website-category-title').html(' - '+alumnionline_reunion_website_Variables['search']);
    if(resturl.search('/wp-json/')>0) seperator='?';
    
    $.ajax({
    url: alumnionline_reunion_website_Variables['resturl']+'alumnionline_reunion_website/v1/forumsearch/'+seperator+'_wpnonce='+alumnionline_reunion_website_Variables['nonce']+'&keyword='+keyword,
    async: true, 
    dataType: "html",
    error: function(e){ console.log('failed keyword search ');},
    success: 
    function(data){
        jQuery("#alumnionline-reunion-website-view-content").html(data);
        jQuery('.alumnionline-reunion-website-modal-close').trigger('click');
        jQuery("#alumnionline-reunion-website-forum-status-message").html(jQuery("#alumnionline_reunion_website_results").detach());
        
    }
    });
});	

// get message for editing
function alumnionline_reunion_website_populate_message_values(messageid){
  var seperator='&';
  var resturl = alumnionline_reunion_website_Variables['resturl'];
  jQuery('#alumnionline_reunion_website_post_message_form .alumnionline_reunion_website_post_edit_link').hide();

  if(resturl.search('/wp-json/')>0) seperator='?';
  
  $.ajax({
  url: alumnionline_reunion_website_Variables['resturl']+'alumnionline_reunion_website/v1/getmessage/'+seperator+'_wpnonce='+alumnionline_reunion_website_Variables['nonce']+'&messageid='+messageid,
  async: true, 
  dataType: "html",
  error: function(e){ console.log('failed message lookup');},
  success: 
  function(data){
    jQuery('body').append(data);
        jQuery("#alumnionline-reunion-website-post-content").val(jQuery("#alumnionline-reunion-website-values-message").detach().html());
    jQuery("#alumnionline-reunion-website-post-subject").val(jQuery("#alumnionline-reunion-website-values-subject").detach().html());
    var category = jQuery("#alumnionline-reunion-website-values-category").detach().html();
    jQuery('#alumnionline-reunion-website-post-category option[value='+category+']').attr('selected','selected');   

    if(edittype === 'reply'){
      jQuery("#alumnionline-reunion-website-post-subject").val('RE: '+jQuery("#alumnionline-reunion-website-post-subject").val());
      jQuery("#alumnionline-reunion-website-post-content").val('');
      jQuery('#alumnionline_reunion_website_post_message_form').append('<input type="hidden" name="alumnionline-reunion-website-post-replyid" id="alumnionline-reunion-website-post-replyid" value="'+messageid+'">'); 
    }else{
      jQuery('#alumnionline_reunion_website_post_message_form').append('<input type="hidden" name="alumnionline-reunion-website-post-editid" id="alumnionline-reunion-website-post-editid" value="'+messageid+'">'); 
      if(messageid != ''){
      var editurl = alumnionline_reunion_website_Variables['siteurl']+'/wp-admin/post.php?post='+messageid+'&action=edit'
      jQuery('#alumnionline_reunion_website_post_message_form .alumnionline_reunion_website_post_edit_link').attr('href', editurl); 
      jQuery('#alumnionline_reunion_website_post_message_form .alumnionline_reunion_website_post_edit_link').show();
      }
    }
   
  }
  });
}


// Create a jQuery event listener for the submit event of the form
jQuery("body").on("submit", "#alumnionline_reunion_website_post_message_form", function (e) {
   e.preventDefault();
  
  var data = new FormData(this);
  data.append('_wpnonce',alumnionline_reunion_website_Variables['nonce']);

  $.ajax({
    url: alumnionline_reunion_website_Variables['resturl']+'alumnionline_reunion_website/v1/postmessage/',
    type: "POST",
    data:  data,
    contentType: false,
    dataType: "html",
    cache: false,
    processData: false,
    success: function(data) {
      jQuery("#alumnionline-reunion-website-post-status-message").html(data);
      if(data.search('Error') < 0){
      jQuery("#alumnionline-reunion-website-post-content").val('');
      jQuery("#alumnionline-reunion-website-post-subject").val('');
      if(jQuery("#alumnionline-reunion-website-post-replyid").val() === undefined && jQuery("#alumnionline-reunion-website-post-editid").val() === undefined){
      jQuery(".alumnionline-reunion-website-home-view").trigger('click');
      jQuery(".alumnionline-reunion-website-post-button").focus();
      }
      else{
        jQuery(".alumnionline-reunion-website-changeview-hidden").trigger('click');
       
        jQuery(".alumnionline-reunion-website-home-view").focus();
      }
      }
    },
    error: function(e) {
      console.log('failed post');
      jQuery("#alumnionline-reunion-website-post-status-message").html(e);
    }          
  });
});


 // display new view
 jQuery("body").on("click", ".alumnionline-reunion-website-changeview", function (e) {
    e.preventDefault();

    var offset = 0;
    var offsetnav = 0;
    var type = 'home';
   
    jQuery(".alumnionline-reunion-website-replybtn").remove();

    jQuery("#alumnionline-reunion-website-forum-status-message").html('');

    if(jQuery(this).data('messageid') != undefined){
      var value = jQuery(this).data('messageid');
      var type = 'message';
      jQuery('#alumnionline-reunion-website-category-title').html(' - View Topic');      
      }

    if(jQuery(this).data('categoryid') != undefined){
    var value = jQuery(this).data('categoryid');
    var type = 'category';
    if(jQuery(this).data('category') !== undefined)
    jQuery('#alumnionline-reunion-website-category-title').html(' - '+jQuery(this).data('category'));
    }
    else if(jQuery(this).data('type') != undefined){
        var type = jQuery(this).data('type');
        jQuery('#alumnionline-reunion-website-category-title').html('');
    }
 

    if(jQuery(this).data('offset') != undefined){
     offset = jQuery(this).data('offset');
     offsetnav = 1;
    }


alumnionline_reunion_website_refresh_view(type, value, offset, offsetnav);

});	

/**
 * Refresh view
 */
function alumnionline_reunion_website_refresh_view(type, value, offset, offsetnav){
  var seperator='&';
  var resturl = alumnionline_reunion_website_Variables['resturl'];
  if(resturl.search('/wp-json/')>0) seperator='?';
    
    $.ajax({
        url: alumnionline_reunion_website_Variables['resturl']+'alumnionline_reunion_website/v1/changeview/'+seperator+'_wpnonce='+alumnionline_reunion_website_Variables['nonce']+'&type='+type+'&value='+value+'&offset='+offset,
    async: true, 
    dataType: "html",
    error: function(e){ console.log('failed change view ');},
    success: 
    function(data){

       jQuery("#alumnionline-reunion-website-view-content").html(data);

       if(jQuery("#alumnionline-reunion-website-replybtn").length > 0){
       jQuery('.alumnionline-reunion-website-button-bar').append(jQuery("#alumnionline-reunion-website-replybtn").detach().html());
       }
      else {
        jQuery(".alumnionline-reunion-website-topicview .alumnionline-reunion-website-replybtn").remove();
      }

      if(jQuery('.alumnionline-reunion-website-refresh').length > 0){
        jQuery('.alumnionline-reunion-website-button-bar .alumnionline-reunion-website-refreshbtn').remove();
        jQuery('.alumnionline-reunion-website-button-bar').append(jQuery('.alumnionline-reunion-website-refresh').detach().html());
        jQuery('.alumnionline-reunion-website-refresh').remove();
      }
    

       
       if(jQuery("#alumnionline_reunion_website_results").length > 0){
       jQuery("#alumnionline-reunion-website-forum-status-message").html(jQuery("#alumnionline_reunion_website_results").detach());
       }
      if(offsetnav > 0) jQuery(".alumnionline-reunion-website-changeview-current").focus();
    
    }
    });

}

if(jQuery('.alumnionline-reunion-website-refresh').length > 0){
  jQuery('.alumnionline-reunion-website-button-bar .alumnionline-reunion-website-refreshbtn').remove();
  jQuery('.alumnionline-reunion-website-button-bar').append(jQuery('.alumnionline-reunion-website-refresh').detach().html());
  jQuery('.alumnionline-reunion-website-refresh').remove();
}


    // validate subject
    jQuery("body").on("blur", "#alumnionline-reunion-website-post-subject", function (e) {
     jQuery('.alumnionline_reunion_websitedynamicerror').remove();

         if(jQuery("#alumnionline-reunion-website-post-subject").val() == ''){

          jQuery(this).after('<span role="alert" class="alumnionline_reunion_websitedynamicerror alumnionline_reunion_websiteerror">'+alumnionline_reunion_website_Variables['subjecterror']+'</span>');
         }
    });

        // validate content
        jQuery("body").on("blur", "#alumnionline-reunion-website-post-content", function (e) {
          jQuery('.alumnionline_reunion_websitedynamicerror').remove();
     
              if(jQuery("#alumnionline-reunion-website-post-content").val() == ''){
     
               jQuery(this).after('<span role="alert" class="alumnionline_reunion_websitedynamicerror alumnionline_reunion_websiteerror">'+alumnionline_reunion_website_Variables['contenterror']+'</span>');
              }
         });

    // add screen reader notice to file selector once files have been selected 
    jQuery('#alumnionline-reunion-website-upload').change( function(event) {
      jQuery('.alumnionline_reunion_websitedynamicerror').remove();
      var lg = $("#alumnionline-reunion-website-upload")[0].files.length; // get length
      var items = $("#alumnionline-reunion-website-upload")[0].files;
      var errorfound = 0;
      

      if (lg > 0) {
       
          for (var i = 0; i < lg; i++) {
        
              if(items[i].type !== "image/jpg" && items[i].type !== "image/jpeg") {
                  errorfound = 1;
                  jQuery('#alumnionline-reunion-website-upload').focus(); 
                  jQuery('#alumnionline-reunion-website-upload').after('<span role="alert" class="alumnionline_reunion_websitedynamicerror alumnionline_reunion_websiteerror">'+alumnionline_reunion_website_Variables['fileerrortype']+'</span>');
              }
          
          if(items[i].size > alumnionline_reunion_website_Variables['max_file_size'] && errorfound ===0) {
              errorfound = 1;
              jQuery('#alumnionline-reunion-website-upload').focus(); 
              jQuery('#alumnionline-reunion-website-upload').after('<span role="alert" class="alumnionline_reunion_websitedynamicerror alumnionline_reunion_websiteerror">'+alumnionline_reunion_website_Variables['fileerrorsize']+'</span>');
          }
          
      }
  }
  
      if(errorfound === 0){
        jQuery('#alumnionline-reunion-website-upload').after('<span role="alert" class="alumnionline_reunion_websitedynamicerror alumnionline_reunion_websiteerror">'+alumnionline_reunion_website_Variables['fileselected']+'</span>');
      }
      jQuery('#alumnionline-reunion-website-upload').focus();
  });

});