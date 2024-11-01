<?php

//--------------------------------------------------------------------------------------------------
function zwp_plugin_admin_init()
//--------------------------------------------------------------------------------------------------
{
	wp_register_style( 'zwp_stylesheet', plugins_url( 'zaphnewp/zwp_stylesheet.css') );
  
  // settings

	register_setting( 'ZaphneWP_option_group', 'zaphneWP_search_phrase_array');
  register_setting( 'ZaphneWP_option_group', 'zaphneWP_exclusion_array');
	
	add_settings_section( 'zwp_section_id', 'Edit Your Search Phrase', 'zwp_section_callback', 'ZaphneWP-search' );
	
	add_settings_field( 'zwp_field_id', 'Search Phrase', 'zwp_field_callback', 'ZaphneWP-search', 'zwp_section_id' );
  	
  // end settings
  
	add_option( 'zaphneWP_registration_date', '', '', 'yes' );


}


//------------------------------------------
function zwp_plugin_scripts()
//--------------------------------------------------------------------------------------------------
{
	wp_enqueue_style( 'zwp_stylesheet' );
  //wp_register_script('zwp_display_ad1', plugins_url( 'zaphneWP/zwp_display_ad1.js' ));
  //wp_register_script('zwp_display_ad2', plugins_url( 'zaphneWP/zwp_display_ad2.js' ));
  
	//wp_enqueue_script( 'zwp_display_ads', plugins_url( 'zaphneWP/zwp_display_ads.js' ) );
  //wp_enqueue_script( 'zwp_display_ads2', plugins_url( 'zaphneWP/zwp_display_ads.js' ) );
  //wp_enqueue_script( 'zwp_display_ad1' );
  //wp_enqueue_script( 'zwp_display_ad2' );
	//wp_add_inline_script( 'zwp_display_ads', 'var theAnalyticsValue = "UA-MyRealCode-9";', 'before' );
}

//--------------------------------------------------------------------------------------------------
function zaphne_version()
//--------------------------------------------------------------------------------------------------
{

	if ( ! function_exists( 'get_plugins' ) )
	{
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );

		$plugin_version = $plugin_folder['zaphneWP.php']['Version'];

	}
	else
	{

		$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );

		$plugin_version = $plugin_folder['zaphneWP.php']['Version'];

	}

	return $plugin_version;

}
//--------------------------------------------------------------------------------------------------
function zwp_plugin_admin_styles()
//--------------------------------------------------------------------------------------------------
{
	wp_enqueue_style( 'zwp_stylesheet' );

	//wp_enqueue_script( 'displayAd', plugins_url( 'displayAd.js', __FILE__ ) );
}



//--------------------------------------------------------------------------------------------------
function zwp_deactivation()
//--------------------------------------------------------------------------------------------------
{
  wp_clear_scheduled_hook( 'zwp_post_request' );

}

//--------------------------------------------------------------------------------------------------
function zwp_activation()
//--------------------------------------------------------------------------------------------------
{

  zwp_check_reg_status();

  wp_schedule_event( time(), 'daily', 'zwp_post_request' );

}

//--------------------------------------------------------------------------------------------------
function zwp_get_next_post()
//--------------------------------------------------------------------------------------------------
{
  /*
  This function uses wp_remote_post to request post content to then do a post insert 
  */

  $zwp_url 									= ZWPURL . 'GetKeywordPost';
  $zwp_error_url 						= ZWPURL . 'ReportPluginError';
  $zwp_Success_url 					= ZWPURL . 'ReportPostSuccess';

  $kw_array 								= array();
  $zwp_search_phrase_array 	= get_option('zaphneWP_search_phrase_array');
  $post_status_array        = get_option( 'zaphneWP_post_status_array');
  $user_id 									= get_option( 'zaphneWP_user_id' );

  for( $x = 0; $x < count($zwp_search_phrase_array) ; $x++)
  {
    // get a post for each search phrase

    $kw_array = $zwp_search_phrase_array[$x];
    

    $response = wp_remote_post( $zwp_url, array(
      'method' => 'POST',
      'body' => array( 'blogID' =>get_option( 'zaphneWP_user_id' ), 'keywordID' =>$kw_array[0],'pluginID' =>get_option( 'zaphneWP_plugin_id' ) )
    ) );

    if ( is_wp_error( $response ) ) 
    {


      $response = wp_remote_post( $zwp_error_url, array(
      'method' => 'POST',
      'body' => array( 'zaphneWP_plugin_id' =>get_option( 'zaphneWP_plugin_id' ), 'zwp_error_message' =>$response->get_error_message() )
      ) );

    }
    else
    {


      // the body is json encoded
      $zwp_post_obj = json_decode( $response[ 'body' ], false );


      if ( $zwp_post_obj->result_code == 0 )
      {
        
        
        $inner_array = $post_status_array[0];
        
        for($z = 0; $z < count( $post_status_array); $z++)
        { 
          
          if ( $post_status_array[$z][0] ==  $kw_array[0])
          {
            
            $this_post_status_id = $post_status_array[$z][1];
          }
        }

        $post_status = 1;

        if ( $this_post_status_id == 0 ) {
          $post_status_str = "publish";
        } else {
          $post_status_str = "pending";
        }

        $cat = get_cat_ID( $kw_array[1] );

        if ( $cat==0 )
        {
          $cat = wp_create_category( $kw_array[1] );
        }
        
        $zwp_post_title_array      = get_option( 'zaphneWP_post_title_array' );
        $zwp_post_header_array      = get_option( 'zaphneWP_post_header_array' );
        $zwp_post_footer_array      = get_option( 'zaphneWP_post_footer_array' );
        $zwp_post_status_array      = get_option( 'zaphneWP_post_status_array' );
        
        $postTitleStr = "";
      
        if ( is_array( $zwp_post_title_array ) )
        {
          foreach ( $zwp_post_title_array as $title_key => $title_value ) 
          {
            
          
            if ( $title_value[0] == $kw_array[0] )
            {
              //"match make postTitleStr<br>";
              $postTitleStr = $title_value[1]; 

            }
          }
        }
        
        //echo "final title: $postTitleStr<br>";
        
        if ( strlen( $postTitleStr ) > 0 )
        {
          $postTitleStr = $postTitleStr . " for ". date("m-d-Y");
        }
        else
        {
          $postTitleStr = urldecode($zwp_post_obj->post_title)." for ". date("m-d-Y");
        }
        
        $postHeaderStr = "";
      
        if ( is_array( $zwp_post_header_array ) )
        {
          foreach ($zwp_post_header_array as $header_key => $header_value) {
            
          
            if ( $header_value[0] == $kw_array[0] )
            {
              $postHeaderStr = $header_value[1]; 

            }
          }
        }
        
        if ( strlen( $postHeaderStr ) > 0 )
        {
          $postHeaderStr = "<h2>$postHeaderStr</h2>";
        }
        
        
        $postFooterStr = "";
      
        if ( is_array( $zwp_post_footer_array ) )
        {
          foreach ($zwp_post_footer_array as $footer_key => $footer_value) {
            
          
            if ( $footer_value[0] == $kw_array[0] )
            {
              $postFooterStr = $footer_value[1]; 

            }

          }
        }
        
        if ( strlen( $postFooterStr ) > 0 )
        {
          $postFooterStr = "<h2>$postFooterStr</h2>";
        }
        

        $my_post = array(
          'post_title' => $postTitleStr,
          'post_content' => $postHeaderStr.$zwp_post_obj->post_content.$postFooterStr,
          'post_status' => $post_status_str,
          'post_category' => array( $cat ),
          'post_author' => 1
        );

        //remove_all_filters("content_save_pre");
        $retval = wp_insert_post( $my_post );
        //add_filter("content_save_pre",'');


        if ( $retval > 0 )
        {
            

          $decoded_kw_array = json_decode($zwp_post_obj->keyword_array[ 0 ], true);

          

          for($m = 0; $m < count( $decoded_kw_array ); $m++)
          {
            

            $tag_str = str_replace('[', '', $decoded_kw_array[$m] );
            $tag_str = str_replace(']', '', $tag_str );
            $tag_str = str_replace('"', '', $tag_str );

            $this_kw_array =explode(',',$tag_str );

            for($y = 0;$y< count($this_kw_array); $y++)
            { 

              wp_set_post_tags( $retval, $this_kw_array[$y], true );
            }
          }
          
          $success_response = wp_remote_post( $zwp_Success_url, array(
          'method' => 'POST',
          'body' => array( 'zaphneWP_plugin_id' =>get_option( 'zaphneWP_plugin_id' ), 'mastered_post_id' =>$zwp_post_obj->mastered_post_id, 'wp_post_id' =>$retval )
          ) );


        }
      }
      else
      {
        //echo "result code: $zwp_post_obj->result_code<br>";

        //echo "response message: $zwp_post_obj->result_message<br>";
      }


    }

  }

}

//--------------------------------------------------------------------------------------------------
function zwp_get_industries()
//--------------------------------------------------------------------------------------------------
{

  $url = ZWPURL.'GetIndustries';		

  $response = wp_remote_get( $url );

  if ( is_wp_error( $response ) ) {
    $error_message = $response->get_error_message();
    echo "Something went wrong in zwp_get_industries: $error_message";
  } else {
    //echo "zwp_get_industries reg1";

    //echo $response[ 'body' ];
    return $response[ 'body' ];

  }


}

//--------------------------------------------------------------------------------------------------
function zwp_submit_update_keyword_request( $keywordID, $updatedStr )
//--------------------------------------------------------------------------------------------------
{

  $url = ZWPURL.'UpdateKeyword';		

  $response = wp_remote_post( $url, array(
    'method' => 'POST',
    'body' => array( 
      'keywordID' => $keywordID,
      'blogID' => get_option( 'zaphneWP_user_id' ),
      'updatedKeyword' => $updatedStr
    )
  ) );

  if ( is_wp_error( $response ) ) {
    $error_message = $response->get_error_message();
    echo "Something went wrong zwp_submit_update_keyword_request: $error_message";
  } else {
    //echo "check UpdateKeyword";

    //echo $response[ 'body' ];
    return $response[ 'body' ];

  }


}

//--------------------------------------------------------------------------------------------------
	function zwp_submit_registration_request()
	//--------------------------------------------------------------------------------------------------
	{
		
		$url = ZWPURL.'RegisterPluginAccount';

		$thisBlogURL = get_bloginfo( 'wpurl' );
		$thisBlogAdminEmail = get_bloginfo( 'admin_email' );
		$thisBlogName = get_bloginfo( 'Name' );
		$thisBlogLanguage = get_bloginfo( 'language' );
		

		$response = wp_remote_post( $url, array(
			'method' => 'POST',
			'body' => array( 
				'zwp_plugin_id' => get_option( 'zaphneWP_plugin_id' ),
				'admin_email' =>$thisBlogAdminEmail,
				'wpurl' => $thisBlogURL, 
				'blog_name' => $thisBlogName, 
				'blog_language' => $thisBlogLanguage, 
				'search_phrase' => get_option( 'zaphneWP_primary_search_phrase' ),
				'industry_code' => get_option( 'zaphneWP_industry_code' ),
				'affiliate_code' => get_option( 'zaphneWP_affiliate_code' ),
				'zip_code' => get_option( 'zaphneWP_zip_code' ),
				'first_name' => get_option( 'zaphneWP_user_first_name'),
				'last_name' => get_option( 'zaphneWP_user_last_name')
			)
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong zwp_submit_registration_request: $error_message";
		} else {
			//echo "check zwp_submit_registration_request";
			//echo $response[ 'body' ];
			return $response[ 'body' ];
			
		}
		
		
	}

	//--------------------------------------------------------------------------------------------------
	function zwp_create_plugin_id()
	//--------------------------------------------------------------------------------------------------
	{
		
		$url = ZWPURL.'CreatePluginID';

		$thisBlogURL = get_bloginfo( 'wpurl' );
		$thisBlogAdminEmail = get_bloginfo( 'admin_email' );
		$thisBlogName = get_bloginfo( 'Name' );
		$thisBlogLanguage = get_bloginfo( 'language' );
		

		$response = wp_remote_post( $url, array(
			'method' => 'POST',
			'body' => array( 'wpurl' => $thisBlogURL, 'blog_name' => $thisBlogName, 'blog_language' => $thisBlogLanguage )
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong zwp_create_plugin_id: $error_message";
		} else {
			//echo "check zwp_create_plugin_id";
			//echo $response[ 'body' ];
			return $response[ 'body' ];
			
		}
		
		
	}

//--------------------------------------------------------------------------------------------------
	function zwp_check_reg_status()
	//--------------------------------------------------------------------------------------------------
	{
		// change this to pure API get
		//echo "check reg";
		
		$url = ZWPURL.'GetRegistrationStatus';

		$thisBlogURL = get_bloginfo( 'wpurl' );
		$thisBlogAdminEmail = get_bloginfo( 'admin_email' );
		$thisBlogName = get_bloginfo( 'Name' );
		$thisBlogLanguage = get_bloginfo( 'language' );
		$thisBlogPluginID = get_option( 'zaphneWP_plugin_id' );		

		$response = wp_remote_post( $url, array(
			'method' => 'POST',
			'body' => array( 'wp_plugin_id' => $thisBlogPluginID, 'wpurl' => $thisBlogURL, 'admin_email' => $thisBlogAdminEmail, 'blog_name' => $thisBlogName, 'blog_language' => $thisBlogLanguage )
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong zwp_check_reg_status: $error_message";
		} else {
			//echo "check reg1";

			//echo $response[ 'body' ];
			$obj = json_decode($response[ 'body' ], false);

			if( $obj->result_code==0 ) 
			{
				if( $obj->total_results==0 ) 
				{
					//echo "there is a problem";
					update_option( 'zaphneWP_user_id', 0 );					
					update_option( 'zaphneWP_registration_date', '' );
					update_option( 'zaphneWP_primary_search_phrase', '' );
					update_option( 'zaphneWP_user_first_name', '' );
					update_option( 'zaphneWP_user_last_name', ''  );
					update_option( 'zaphneWP_industry_code', 0 );
					update_option( 'zaphneWP_affiliate_code', '' );
					update_option( 'zaphneWP_zip_code', '' );
				}
		
				
			}
			
			
		}
	}

	//--------------------------------------------------------------------------------------------------
	function zwp_get_account_details()
	//--------------------------------------------------------------------------------------------------
	{
		
		//echo "GetAccountDetails<br>";
		
		$url = ZWPURL.'GetAccountDetails';
		
		$zaphneWP_plugin_id = get_option( 'zaphneWP_plugin_id' );
		$zaphneWP_user_id	= get_option( 'zaphneWP_user_id');
		$thisBlogURL 				= get_bloginfo( 'wpurl' );

		$response = wp_remote_post( $url, array(
			'method' => 'POST',
			'body' => array( 'wp_plugin_id' => $zaphneWP_plugin_id, 'wp_user_id' => $zaphneWP_user_id, 'wpurl' => $thisBlogURL )
		) );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong 418: $error_message";
		} 
		else 
		{
			//echo $response[ 'body' ];
			$obj = json_decode($response[ 'body' ], false);
			// store the search phrase array
      
      if ( $obj->result_code == 0 )
      {
			update_option( 'zaphneWP_search_phrase_array', $obj->keywordArray );	
      update_option( 'zaphneWP_account_type', $obj->accountTypeID );
      update_option( 'zaphneWP_user_id', $obj->wp_user_id );
      update_option( 'zaphneWP_registration_date', $obj->registrationDate );
      }
      
			
		}
	}

	//--------------------------------------------------------------------------------------------------
	function zwp_plugin_menu()
	//--------------------------------------------------------------------------------------------------
	{
		add_action( "admin_print_styles", 'zwp_plugin_admin_styles' );
		

		add_menu_page( 'ZaphneWP Option', 'ZaphneWP', 'activate_plugins', 'ZaphneWP','zaphneWP_options');
		
		add_submenu_page(
        'ZaphneWP',
        'Dashboard',
        'Dashboard',
        'activate_plugins',
        'ZaphneWP',
				'zaphneWP_options'
    );
		
		add_submenu_page(
        'ZaphneWP',
        'Search Phrase',
        'Search Phrase',
        'activate_plugins',
        'ZaphneWP-search',
				'zwp_edit_search_phrase'
    );
    
    if (get_option( 'zaphneWP_account_type')==2)
    {
      add_submenu_page(
        'ZaphneWP',
        'Exclusions',
        'Exclusions',
        'activate_plugins',
        'ZaphneWP-exclusions',
				'zwp_exclusions'
      );
      
      add_submenu_page(
        'ZaphneWP',
        'Post Options',
        'Post Options',
        'activate_plugins',
        'ZaphneWP-postOptions',
				'zwp_post_options'
      );
      
      add_submenu_page(
        'ZaphneWP',
        'Downgrade',
        'Downgrade',
        'activate_plugins',
        'ZaphneWP-downgrade',
				'zwp_downgrade'
      );
    }
    else
    {
      add_submenu_page(
        'ZaphneWP',
        'Upgrade',
        'Upgrade',
        'activate_plugins',
        'ZaphneWP-upgrade',
				'zwp_upgrade'
      );
    }
		
		
		

		
	}


	//--------------------------------------------------------------------------------------------------
	function zaphneWP_options()
	//--------------------------------------------------------------------------------------------------
	{
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		zwp_check_reg_status();

		$url = ZWPURL . 'ZaphneWP/register';
    
		
		if ( isset( $_POST[ "vchoice" ] ) ) {

      if ( !check_admin_referer( 'version_choice', 'version_choice_token' ))
      {
        // This nonce is not valid.
        die( 'Security check' ); 
      }
      else
      {
			//echo "doglicious ".$_POST[ "vchoice" ]."<br>"; 
			$dateStr = date('Y/m/d \a\t g:ia');
      
      if ( is_numeric ( $_POST[ "vchoice" ] ) )
      {
        if ( $_POST[ "vchoice" ] == 1 ) {
          update_option( 'zaphneWP_optin_date', $dateStr );
          update_option( 'zaphneWP_account_type', 1 );
        }

        if ( $_POST[ "vchoice" ] == 2 ) {
          update_option( 'zaphneWP_optin_date', $dateStr );
          update_option( 'zaphneWP_account_type', 2 );
        }
      }
      }
		}
		
		
    if ( isset( $_POST[ "reg_form" ] ) ) {
      

      
      if ( !check_admin_referer( 'register_confirmation' ) ) 
      {
        die( 'Security check' ); 
      }
      else
     {   
			
			$reg_form_complete = true;
      
			$first_name = sanitize_text_field( $_POST[ "first_name" ] );
			
      // did we get a valid fname?
			if ( strlen( $first_name ) > 0 )
			{
				//echo "first name " . $_POST[ "first_name" ]."<br>";
				update_option( 'zaphneWP_user_first_name', $first_name );
			}
			else
			{
				$reg_form_complete = false;
			}
      
			
			// did we get a last name?
      $last_name = sanitize_text_field( $_POST[ "last_name" ] );
      
			if ( strlen( $last_name ) > 0 )
			{
				//echo "last name " . $_POST[ "last_name" ]."<br>";
				update_option( 'zaphneWP_user_last_name', $last_name );
			}
			else
			{
				$reg_form_complete = false;
			}
			
			// did we get a search phrase?
      $search_phrase = sanitize_text_field( $_POST[ "search_phrase" ] );
      
			if ( strlen( $search_phrase ) > 0 )
			{
				//echo "search phrase" . $_POST[ "search_phrase" ]."<br>";
				update_option( 'zaphneWP_primary_search_phrase', $search_phrase );
			}
			else
			{
				$reg_form_complete = false;
			}
      
      // the industry code should be numeric
      if ( is_numeric ( $_POST[ "industry_code" ] ) )
      {
        $industry_code = $_POST[ "industry_code" ];
        
        if ( ($industry_code  > 0) && ($industry_code  < 50) )
        {
          //echo "industry code" . $_POST[ "industry_code" ]."<br>";
          update_option( 'zaphneWP_industry_code', $_POST[ "industry_code" ] );
        }
        else
        {
          if (get_option( 'zaphneWP_industry_code' )>0)
          {
            //$reg_form_complete = false;	
          }
          else
          {
            $reg_form_complete = false;	
          }

        }
      }
      
      if ( isset( $_POST[ "affiliate_code" ] ) ) 
      {
        if ( is_numeric ( $_POST[ "affiliate_code" ] ) )
        {
          $affiliate_code = $_POST[ "affiliate_code" ];
          
          if ( ( $affiliate_code > 100001 )&& ( $affiliate_code < 500001 ) )
          {
            //echo "affiliate code" . $_POST[ "affiliate_code" ]."<br>";
            update_option( 'zaphneWP_affiliate_code', $affiliate_code );
          }
        }
      }
  
			if ( isset( $_POST[ "zip_code" ] ) )
      {  
        $zip_code = sanitize_text_field( $_POST[ "zip_code" ] );
        
        if ( (strlen( $zip_code ) > 0) &&  (strlen( $zip_code ) < 20) )
        {
          //echo "zip code" . $_POST[ "zip_code" ]."<br>";
          update_option( 'zaphneWP_zip_code', $zip_code );
        }
        else
        {
          $reg_form_complete = false;
        }
      }
			
      if ($reg_form_complete == true)
			{
				//echo "form complete<br>";
        
        if ( $_POST[ "vchoice2" ] == 1 ) 
        { 
          $reg_result = zwp_submit_registration_request();
          $obj = json_decode($reg_result, false);
          //print_r( $obj );
          //echo "<br>";
          if ( $obj->result_code==0 )
          {
            //echo "it worked";

            update_option( 'zaphneWP_user_id', $obj->user_id );
            $dateStr = date('Y/m/d \a\t g:ia');

            update_option( 'zaphneWP_registration_date', $dateStr );
          }
          else
          {
            echo "it did not work<br>";
          }
        }
			/*
			if ( $_POST[ "vchoice" ] == 2 ) {
				echo "Premium Cleanup<br>";
			}
      */
				//echo $obj->result_message; 						
				
			}
			
    }
		}
    
		
		// start output
    zwp_get_account_details();
    
    add_action( 'admin_menu', 'zwp_plugin_menu' );
		
		
		echo '<div id="zwpwrapper">';
		

		zwp_do_page_header();

    //echo "userID: ".get_option( 'zaphneWP_user_id' )."<br>";
    
		if ( get_option( 'zaphneWP_user_id' ) > 0 ) {
			// plugin is registered


			echo "<div id ='mainLeftRail' class='grayBorder'>";

			zwp_display_account_details();

			echo "</div>";
			
			zwp_display_right_rail();
			
			echo "<div style='clear:both'>";

			echo "</div>";


		} else 
		{

			//check for plugin id
			$zaphneWP_plugin_id = get_option( 'zaphneWP_plugin_id' );
			//echo( $zaphneWP_plugin_id );
			
			if ( strlen( $zaphneWP_plugin_id ) == 0 )
			{
				//zwp_create_plugin_id();
				update_option( 'zaphneWP_plugin_id', zwp_create_plugin_id() );
			}
			
			$zaphneWP_optin_date = get_option( 'zaphneWP_optin_date' );
			
			if ( strlen( $zaphneWP_optin_date ) == 0 )
			{
				zwp_display_optin_challenge();	
			}
			else
			{
				zwp_display_registration_challenge();
			}
			
		}


		echo '</div>';
	}

	//--------------------------------------------------------------------------------------------------
	function zwp_display_account_details()
	//--------------------------------------------------------------------------------------------------
	{
		//zwp_get_account_details();
		echo "<table id = 'mainAcctTable'>";

		echo "<tr class=headerRow>";
		
		echo "<td colspan = 3>";
		echo "Account Details";
		echo "<td>";
		
		echo "</tr>";

		echo "<tr>";
		
		echo "<td>";
		echo "<strong>Account Type:</strong>";
		echo "</td>";
		
		echo "<td>";
		if ( get_option('zaphneWP_account_type' ) == 1 ) {
			echo "Free Version";
		} else {
			echo "Premium Version";
		}
		echo "</td>";
		
		echo "<td>";
		if ( get_option( 'zaphneWP_user_id' ) > 0 ) {
			
			echo "&nbsp;";
		}
		echo "</td>";
		
		echo "</tr>";

		echo "<tr>";
		echo "<td>";
		echo "<strong>Account Activated:</strong>";
		echo "</td>";
		echo "<td>";
		
		echo get_option( 'zaphneWP_registration_date' );
		echo "</td>";
		echo "<td></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>";
		echo "<strong>Account Keywords:</strong> ";
		echo "</td>";
		echo "<td>";
		
		$obj = get_option('zaphneWP_search_phrase_array');

		foreach ($obj as $value) {
			echo "$value[1]<br>";
      wp_create_category( $value[1] );
		}


		echo "</td>";
		echo "<td></td>";
		echo "</tr>";

		echo "</table>";

	}


	//--------------------------------------------------------------------------------------------------
	function zwp_do_page_header()
	//--------------------------------------------------------------------------------------------------
	{
		echo '<div id="zwpheaderwrapper" class="grayBorder">';
    
    echo "<div style='width:120px;height:50px;margin-left:auto;margin-right:auto'>";
    
		echo "<div style = 'float:left;width:100px;height:47px;margin-right:4px;'>";
    echo "<img src = '".plugins_url( 'dark-blue-200px.png', __FILE__ ) ."' width = '100' height = '47'></div>";
    
		//echo '<span id ="ZaphneText">Zaphne</span>';
		echo '<div style = "float:left;width:10px;height:47px;"></div>';
    echo "<div style = 'clear:both'></div>";
    echo "</div>";
    echo '<br><hr>';
    echo "<div style = 'text-align:center'>";
		echo '<span id ="TagText">Content Accelerator</span>';
		//echo "<br>version " . $GLOBALS[ 'zwp_plugin_version' ];
    echo "<br>version " . zaphne_version();

		echo '</div></div>';

	}

	//--------------------------------------------------------------------------------------------------
	function zwp_display_right_rail()
	//--------------------------------------------------------------------------------------------------
	{
		
		$ClientURL = ZWPURL. "ZaphneWP/manageClient/" . get_option( 'zaphneWP_user_id' );
		
		echo "<div id ='mainRightRail' class='grayBorder'>";
		echo "<ul>";
		
		//echo "<li><img src = '".plugins_url( 'icon1.png', __FILE__ ) ."'><a href='".$ClientURL."'>Manage Your Account</a>";
		echo "<div style='clear:left'></div>";
		echo "<li><img src = '".plugins_url( 'icon2.png', __FILE__ ) ."'><a href = 'https://support.zaphne.com' target = '_blank'>Support and Knowledgebase</a>";
		
		echo "<div style='clear:left'></div>";
		echo "</ul>";
		
		if ( get_option('zaphneWP_account_type') == 1 ) {
			echo "<div id = 'internalAdSpace'>";
			//echo "UPGRADE NOW!";
			echo "</div>";
		}
		else
		{
			/*
			echo "<div id = 'internalAdSpace'>";
			echo "BUY MORE KEYWORDS!";
			echo "</div>";
			*/
		}
		
		echo "</div>";

		
	}


	//--------------------------------------------------------------------------------------------------
	function zwp_display_optin_challenge()
	//--------------------------------------------------------------------------------------------------
	{
		$url = ZWPURL . 'ZaphneWP/register';
		
		echo "<strong>Your plugin has not been registered yet!</strong> ";
		
		echo "<p>You have two choices to enjoy the many benefits of the Zaphne Content Accelerator.</p>";

		echo "<form action = '' method = 'post' >";
		?>
		<table id = "zwwp_opt_in_table" border="1">
			<tr>
		
				<td width = '50%' class='header_col'>
				<strong>Free</strong>
				</td>

				<td class='header_col'>
				<strong>Premium</strong>
				</td>

			</tr>
			
			<tr>
				<td class='ad_col'>&nbsp;</td>
				<td class='premium_col'><em>Upgrade your Experience</em></td>
			</tr>
				
			<tr>
				<td class="topFeatureRow ad_col">One Post Accelerator Each Week</td>
				<td class="topFeatureRow premium_col">One Post Accelerator Every Day</td>
			</tr>

			<tr>
				<td class="pricingRow ad_col">$0</td>
				<td class="pricingRow premium_col">$30/month</td>
			</tr>

			
				
			<tr>
				<td class='ad_col'>One Search Phrase</td>
				<td class='premium_col'>One Search Phrase, but you can buy unlimited additional phrases for $5 each</td>
			</tr>

			<tr>
				<td class='ad_col'>No Automatic Post Customization</td>
				<td class='premium_col'>Automate Customized Post Headers & Footers</td>
			</tr>

			<tr>
				<td class='ad_col'>No Exclusions</td>
				<td class='premium_col'>Exclude Words or Phrases (like competitor's content)</td>
			</tr>
				
			<tr>
				<td class='ad_col'>Post to Published</td>
				<td class='premium_col'>Post to Draft or Published</td>
			</tr>

			<tr>
				<td class='ad_col'>Powered by Zaphne Tag Line</td>
				<td class='premium_col'>No Zaphne Tag Line (powered by Zaphne)</td>
			</tr>
				
				
		<?php
		//echo "<form action = '".ZWP_PLUGIN_URL_PATH. "test.php'>";
		echo "<tr class='consent_row'>";
		echo "<td valign='top' class='consent_row'>";
		echo "I agree and consent that I want the FREE version of Zaphne.";
		echo "</td>";
		echo "<td valign='top' class='consent_row'>";
		echo "I want the PREMIUM Zaphne experience.";
		echo "</td>";
		echo "</tr>";
		
		echo "<tr>";
		
		echo "<td align ='center'>";
		echo "<input type ='radio' name = 'vchoice' value = '1'>";
		echo "</td>";
		
		echo "<td align ='center'>";
		echo "<input type ='radio' name = 'vchoice' value = '2'>";
		echo "</td>";
		echo "</tr>";
		
		echo "<tr style='background-color:#888888;border-top:1px solid #111111'>";
		echo "<td colspan = 2 align ='center'>";
    
    wp_nonce_field( 'version_choice', 'version_choice_token' );
    
		echo "<input type ='submit' value = 'Make My Choice'>";
		echo "</td>";
		echo "</tr>";
		
		echo "</table>";
		echo "</form>";
		
		/*
		echo "<p><a href = '" . $url . "'  class='zaphneButton'>";
		echo "click here to register Zaphne";
		echo "</a>";
		*/
		
	}

  //--------------------------------------------------------------------------------------------------
	function zwp_display_registration_challenge()
	//--------------------------------------------------------------------------------------------------
	{
		$url = ZWPURL . 'RegisterPlugin';
		
		$account_type = get_option('zaphneWP_account_type');
		
		echo "<strong>We just need a few things to get you registered!</strong> ";
		
		
		//echo "account type: ". get_option('zaphneWP_account_type');
		
		if ( $account_type == 1)
		{
      $_POST = array();
      
			echo "<p>You have chosen the FREE version of the Zaphne WordPress Content Plugin.</p>";

			
			echo "<form method='post' action=''>";
			echo "<input type = 'hidden' name = 'reg_form' value ='1'>";
      echo "<input type = 'hidden' name = 'vchoice2' value ='1'>";
			echo "<table id = 'zwp_register_form_table'>";
		
			echo "<tr>";

			echo "<td>";
			echo "<strong>First Name</strong>";
			echo "</td>";

			echo "<td>";
			echo "<input type = 'text' name = 'first_name' value ='".get_option( 'zaphneWP_user_first_name'). "'>";
			echo "</td>";

			echo "</tr>";
			
			echo "<tr>";

			echo "<td>";
			echo "<strong>Last Name</strong>";
			echo "</td>";

			echo "<td>";
			echo "<input type = 'text' name = 'last_name' value ='".get_option( 'zaphneWP_user_last_name'). "'>";
			echo "</td>";

			echo "</tr>";
			
			echo "<tr>";

			echo "<td>";
			echo "<strong>Search Phrase</strong>";
			echo "</td>";

			echo "<td>";
			//echo "<input type = 'text' name = 'search_phrase' value ='".get_option( 'zaphneWP_primary_search_phrase'). "'>";
      echo "<textarea name = 'search_phrase'>".get_option( 'zaphneWP_primary_search_phrase'). "</textarea>";
			echo "</td>";

			echo "</tr>";
			
			echo "<tr>";

			echo "<td>";
			echo "<strong>Industry</strong>";
			echo "</td>";

			echo "<td>";
			
			if ( get_option( 'zaphneWP_industry_code' )>0)
			{
				echo get_option( 'zaphneWP_industry_code' );
			}
			else
			{
				echo "<select name = 'industry_code'>";
				echo "<option value ='0'>please choose an industry</option>";
				//echo zwp_get_industries();
				$obj = json_decode(zwp_get_industries(), false);

				if( $obj->result_code==0 ) 
				{
					
					//echo "there is ".$obj->industry_codes_array;
					//print_r($obj->industry_codes_array);
					foreach ($obj->industry_codes_array as $value) {
							echo "<option value ='$value[0]'>$value[1]</option>";
					}



				}
				echo "</select>";
			}
			
			echo "</td>";

			echo "</tr>";
      
      echo "<tr>";

			echo "<td>";
			echo "<strong>Zip/Postal Code</strong>";
			echo "</td>";

			echo "<td>";
			echo "<input type = 'text' name = 'zip_code'value ='".get_option( 'zaphneWP_zip_code'). "'>";
			echo "</td>";

			echo "</tr>";
			
			echo "<tr>";

			echo "<td>";
			echo "<strong>Affiliate Code</strong>";
			echo "</td>";

			echo "<td>";
			echo "<input type = 'text' name = 'affiliate_code' value ='".get_option( 'zaphneWP_affiliate_code'). "'>";
			echo "</td>";

			echo "</tr>";
			
			
			
			echo "<tr>";

			
			echo "<td colspan = '2' id = 'agree'>";
			echo "I agree to <a href='https://tos.zaphne.com' target='_blank'>terms</a><br>";
			echo "<input type='checkbox' name = 'agreeToTerms' required>";
			echo "</td>";

			echo "</tr>";


			echo "<tr style='background-color:#888888;border-top:1px solid #111111'>";
			echo "<td colspan = 2 align ='center'>";
      
      wp_nonce_field( 'register_confirmation' );
      
			echo "<input type ='submit' value = 'Register'>";
			echo "</td>";
			echo "</tr>";

			echo "</table>";
			echo "</form>";
		}
		else
		{
			echo "<p>We must direct you to our secure transaction site to set up your payment method.</>";
      
      $zwp_plugin_id = get_option( 'zaphneWP_plugin_id' );
      $thisBlogName = get_bloginfo( 'Name' );
		  $thisBlogLanguage = get_bloginfo( 'language' );
      
      if ( strlen($thisBlogName) ==0 )
      {
        $thisBlogName = "default";
      }
      
      echo "<p>";
      echo "<a href = 'https://wpapi.zaphne.com/zwpapi.php/";
      echo "InstallPremium/process_upgrade/";
      echo $zwp_plugin_id;
      echo "/";
      echo $thisBlogName;
      echo "/";
      echo $thisBlogLanguage;
      echo "' class='zaphneSmallButton'>click here to install PREMIUM</a>";
      echo "</p>";
      //echo "<a href = 'https://wpapi.zaphne.com/zwpapi.php/InstallPremium/process_upgrade/$zwp_plugin_id/$zwp_user_id/$zwp_affiliate_code' class='zaphneSmallButton'>go</a>";
		}
		echo "<br>";
		/*
		echo "<p><a href = '" . $url . "'  class='zaphneButton'>";
		echo "click here to register Zaphne";
		echo "</a>";
		*/
		
	}

	

//--------------------------------------------------------------------------------------------------
function zwp_edit_search_phrase()
//--------------------------------------------------------------------------------------------------
{
  // check user capabilities
  if ( !current_user_can('manage_options') ) 
  {
    return;
  }
  
  if ( isset( $_POST[ "edit_sp_form" ] ) ) 
  {
		$responseObj = new stdClass(); 
    
    // check the incoming nonce
    //$nonce = $_REQUEST['_wpnonce'];
    
    if ( !check_admin_referer( 'edit_search_phrase', 'edit_search_phrase_token' ) ) 
    {
      // This nonce is not valid.
      die( 'Security check' ); 
    } 
    else 
    {
      // The nonce was valid.
      // Do stuff here.
    
		  
    
      //echo "edit_sp_form2<br>";
      //print_r( $_POST );
      //echo "<br>";
    
      foreach ($_POST as $key => $value) 
      {
        //echo "OBOE $key $value <br>";
        if ( ($key != 'edit_search_phrase_token' ) && ($key != '_wp_http_referer' ) )
        {
          $responseObj->$key = sanitize_text_field( $value );;
        }
        
      }
    
      $url = ZWPURL.'UpdateAndAddSearchPhrase';

      //$post_out = json_encode( $_POST );


      $response = wp_remote_post( $url, array(
        'method' => 'POST',
        'body' => array( 'blogID' =>get_option( 'zaphneWP_user_id' ), 'pluginID' =>get_option( 'zaphneWP_plugin_id'), 'post_out'=> $responseObj )
       ));

      if ( is_wp_error( $response ) ) 
      {
        $error_message = $response->get_error_message();
        echo "Something went wrong updating search phrase: $error_message";
      }
      else 
      {
        //print_r($response);
        //echo "check UpdateAndAddSearchPhrase";

        //echo $response[ 'body' ];
        //return $response[ 'body' ];

        $obj = json_decode($response[ 'body' ], false);

        if( $obj->result_code==0 ) 
        {
          echo "<br><strong>You successfully updated your search phrases. It may take up to 24hrs to reflect your changes.</strong><br>";
          //echo "count 1367: ".count($obj->keyword_array)."<br>";
          update_option( 'zaphneWP_search_phrase_array', $obj->keyword_array );

        }
        else
        {
          echo "there was a problem updating your search phrases<br>";
        }

      }                                     
    }

  }
  else
  {
    ?>
    <div class="wrap">
        <h1>Search Phrases</h1>
      
      <p>You can edit your search phrases in the boxes below. If you would like to delete one, just delete the words from the box you want to change.</p>
            <form action="" method="post">
            <input type = 'hidden' name = 'edit_sp_form' value ='1'>
           
              
            <?php
            $zwp_search_phrase_array = get_option( 'zaphneWP_search_phrase_array' );
            //print_r( $zwp_search_phrase_array ) ;
            //echo "<br>";
            //echo "count: ".count( $zwp_search_phrase_array )."<br>";
  
            foreach ($zwp_search_phrase_array as $key => $value) {
              //echo "$key $value <br>";
              $inner_array = $value;
              //echo "inner $inner_array[0]"."<br>";
              //echo "inner $inner_array[1]"."<br>";
              echo "<div style = 'display: inline-block;background-color:#cccccc;padding:10px;margin-right:10px'>";
              echo "<textarea rows = '4' cols = '40' name = '".$inner_array[0]."'>".$inner_array[1]."</textarea>";
              echo "</div>";
            }
    
            if (get_option( 'zaphneWP_account_type' ) > 1 )
            {
              echo "<div style = 'background-color:#cccccc;padding:10px;margin-top:10px;width:270px;'>";
              echo "<strong>Add Search Phrase</strong> - $5/month<br>";
              echo "<textarea rows = '4' cols = '40' name = 'zwp_new_sp'></textarea>";
              echo "</div>";
            }
    
            wp_nonce_field( 'edit_search_phrase', 'edit_search_phrase_token' );
            echo "<input type='submit' value = 'update search phrases' class='zaphneSmallButton'>";
            
            ?>
        </form>
      
    </div>
    <?php
  }
}

//--------------------------------------------------------------------------------------------------
function zwp_upgrade()
//--------------------------------------------------------------------------------------------------
{
  //echo "upgrade<br>";
  // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
  echo "<h1>Thank you for chosing to upgrade to Premium!</h1>";
  
  echo "We just need to get your payment information to process your subscription. We use Authorize.Net to process our transactions. We do not store ANY financial information on our servers to provide for your maximum security.<p>";

  $zwp_plugin_id = get_option( 'zaphneWP_plugin_id' );
  $zwp_user_id	= get_option( 'zaphneWP_user_id');
  
  $zwp_affiliate_code = get_option( 'zaphneWP_affiliate_code' );
  
  if ( $zwp_affiliate_code == '' )
  {
    $zwp_affiliate_code = "0";
  }
  
  echo "<a href = 'https://wpapi.zaphne.com/zwpapi.php/UpgradeToPremium/process_upgrade/$zwp_plugin_id/$zwp_user_id/$zwp_affiliate_code' class='zaphneSmallButton'>click to upgrade</a>";
}

//--------------------------------------------------------------------------------------------------
function zwp_exclusions()
//--------------------------------------------------------------------------------------------------
{
  //echo "upgrade<br>";
  //echo "<strong>Add or Edit Search Phrase Exclusions</strong>";

  if ( isset( $_POST[ "edit_exsp_form" ] ) ) 
  {
		$responseObj = new stdClass(); 
    
    if ( !check_admin_referer( 'edit_search_ex', 'edit_search_ex_token' ) ) 
    {
      // This nonce is not valid.
      die( 'Security check' ); 
    } 
    else 
    {
    		  
      //echo "edit_sp_form2<br>";
      //print_r( $_POST );
      //echo "<br>";
    
      foreach ($_POST as $key => $value) {
        //echo "$key $value <br>";
        if ( ($key != 'edit_search_ex_token' ) && ($key != '_wp_http_referer' ) )
        {
          $responseObj->$key = sanitize_text_field( $value );
        }
      }
    
      $url = ZWPURL.'UpdateAndAddSearchPhraseExclusions';

      //$post_out = json_encode( $_POST );


      $response = wp_remote_post( $url, array(
        'method' => 'POST',
        'body' => array( 'blogID' =>get_option( 'zaphneWP_user_id' ), 'pluginID' =>get_option( 'zaphneWP_plugin_id'), 'post_out'=> $responseObj )
       ));

      if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong 281: $error_message";
      } else {
        //print_r($response);
        echo "check UpdateAndAddSearchPhraseExclusion";

        echo $response[ 'body' ];
        //return $response[ 'body' ];
        
        $obj = json_decode($response[ 'body' ], false);

        if( $obj->result_code==0 ) 
        {
          echo "<br>count 1367: ".count($obj->exclusion_array)."<br>";
          update_option( 'zaphneWP_exclusion_array', $obj->exclusion_array );

        }

      }
    }
  }
  else
  {
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
      
      <p>You can edit your search phrase exclusions in the boxes below. If you would like to delete one, just delete the words from the box you want to delete.</p>
            <form action="" method="post">
            <input type = 'hidden' name = 'edit_exsp_form' value ='1'>
              
            <?php
            $zwp_search_phrase_array  = get_option( 'zaphneWP_search_phrase_array' );
            $zwp_exclusion_array      = get_option( 'zaphneWP_exclusion_array' );
            //echo "exclusion array: <br>";
  
    
            //print_r( $zwp_exclusion_array ) ;
            //echo "<br>";
            //echo "count: ".count( $zwp_search_phrase_array )."<br>";
            //echo "count2: ".count( $zwp_exclusion_array )."<br>";
            //echo "count3: ".array_count_values( $zwp_exclusion_array )."<br>";

  
            foreach ($zwp_search_phrase_array as $key => $value) {
              echo "<div style = 'background-color:#cccccc; display:inline-block;padding:10px; margin-right:10px; vertical-align:top'>";
              //echo "$key $value <br>";
              $inner_array = $value;
              //echo "inner $inner_array[0]"."<br>";
              //echo "inner $inner_array[1]"."<br>";
              //echo $inner_array[1]."<br>";
              $inner_array_index = 0;
              if ( is_array( $zwp_exclusion_array ) )
              {
                foreach ($zwp_exclusion_array as $key2 => $value2) {
                //echo "dog: $key2[$inner_array_index] <br>"; 
                //echo "cat: $value2[0] <br>";
                //echo "cat2: $value2[1] <br>";
                //echo "cat3: $value2[2] <br>";
                if ( $value2[0] == $inner_array[0] )
                {
                  echo "<div style='display:inline-block;margin-right:10px;'>";
                  echo "<strong>Exclusion for:</strong> " . $inner_array[1]. "<br>";
                  echo "<textarea rows = '4' cols = '40' name = '$inner_array[0]-$value2[1]'>$value2[2]</textarea>";
                  echo "</div>";
                }
                $inner_array_index++;
                }
              }
              
              echo "<p><strong>Add Search Phrase Exclusion for: </strong>$inner_array[1]</p>";
              echo "<textarea rows = '4' cols = '40' name = 'zwp_new_spx-$inner_array[0]'></textarea></p>";

              echo "</div>";
            }
            wp_nonce_field( 'update_search_ex', 'update_search_ex_token' );
            echo "<div><input type='submit' value = 'update search phrase exclusions' class='zaphneSmallButton'></div>";
            ?>
        </form>
      
    </div>
    <?php
  }
  
}

//--------------------------------------------------------------------------------------------------
function zwp_post_options()
//--------------------------------------------------------------------------------------------------
{
  //echo "zwp_post_options<br>";
  //echo "<h1>Thank you for post options to Premium!</h1>";
  
  if ( isset( $_POST[ "edit_po_form" ] ) ) 
  {
		$responseObj = new stdClass(); 
		  
    if ( !check_admin_referer( 'update_post_options', 'update_post_options_token' ) ) 
    {
      // This nonce is not valid.
      die( 'Security check' ); 
    } 
    else 
    {
    
      foreach ($_POST as $key => $value) {
        //$responseObj->$key = $value;
        if ( ($key != 'update_post_options_token' ) && ($key != '_wp_http_referer' ) )
        {
          $responseObj->$key = sanitize_text_field( $value );
        }
      }
    
      $url = ZWPURL.'UpdateAndAddPostOptions';

      //$post_out = json_encode( $_POST );


      $response = wp_remote_post( $url, array(
        'method' => 'POST',
        'body' => array( 'blogID' =>get_option( 'zaphneWP_user_id' ), 'pluginID' =>get_option( 'zaphneWP_plugin_id'), 'post_out'=> $responseObj )
       ));

      if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong 281: $error_message";
      } else {
        
        
        $obj = json_decode($response[ 'body' ], false);

        if( $obj->result_code==0 ) 
        {
          echo "Your post options were successfully updated<br>";
          
          if ( count($obj->content_type_array) > 0 )
          {
            update_option( 'zaphneWP_content_type_array', $obj->content_type_array );
          }
          
          //echo "<br>count 1595: ".count($obj->post_title_array)."<br>";
          
          if ( count($obj->post_title_array) > 0 )
          {
            update_option( 'zaphneWP_post_title_array', $obj->post_title_array );
          }
          
          //echo "<br>count 1588: ".count($obj->post_header_array)."<br>";
          if ( count($obj->post_header_array) > 0 )
          {
            update_option( 'zaphneWP_post_header_array', $obj->post_header_array );
          }
          
          //echo "<br>count 1588: ".count($obj->post_footer_array)."<br>";
          if ( count($obj->post_footer_array) > 0 )
          {
            update_option( 'zaphneWP_post_footer_array', $obj->post_footer_array );
          }
          
          if ( count($obj->post_status_array) > 0 )
          {
            update_option( 'zaphneWP_post_status_array', $obj->post_status_array );
          }
          //update_option( 'zaphneWP_exclusion_array', $obj->exclusion_array );

        }

      }
    }

  }
  else
  {
    /*
  $zwp_plugin_id = get_option( 'zaphneWP_plugin_id' );
  $zwp_user_id	= get_option( 'zaphneWP_user_id');

  $zwp_affiliate_code = get_option( 'zaphneWP_affiliate_code' );
  */
?>
  <h1><?= esc_html(get_admin_page_title()); ?></h1>
  <form action="" method="post">
    <input type = 'hidden' name = 'edit_po_form' value ='1'>

    
    <?php
    $zwp_search_phrase_array = get_option( 'zaphneWP_search_phrase_array' );
    $zwp_content_type_array      = get_option( 'zaphneWP_content_type_array' );
    $zwp_post_title_array      = get_option( 'zaphneWP_post_title_array' );
    $zwp_post_header_array      = get_option( 'zaphneWP_post_header_array' );
    $zwp_post_footer_array      = get_option( 'zaphneWP_post_footer_array' );
    $zwp_post_status_array      = get_option( 'zaphneWP_post_status_array' );
    //print_r( $zwp_search_phrase_array ) ;
    //echo "<br>";
    //echo "count: ".count( $zwp_search_phrase_array )."<br>";

    foreach ($zwp_search_phrase_array as $key => $value) {
      //echo "$key $value <br>";
      echo "<div style = 'display:inline-block;width:300px;margin-bottom:10px;background-color:#cccccc;margin-right:10px;padding:10px;'>";
      
      $inner_array = $value;
      //echo "inner $inner_array[0]"."<br>";
      //echo "inner $inner_array[1]"."<br>";
      echo "<h2 style = 'height:40px;'>";
      echo "Search Phrase: ";
      echo $inner_array[1];
      echo "</h2>";
      
      $inner_array_index = 0;
      $contentTypeID = 0;
      
      if ( is_array( $zwp_content_type_array ) )
      {
        foreach ($zwp_content_type_array as $key2 => $value2) {
        //echo "dog: $key2[$inner_array_index] <br>"; 
        //echo "cat: $value2[0] <br>";
        //echo "cat2: $value2[1] <br>";
        //echo "cat3: $value2[2] <br>";
        if ( $value2[0] == $inner_array[0] )
        {
          
          $contentTypeID = $value2[1]; 
          //echo "usable: $contentTypeID<br>";
        }
        $inner_array_index++;
        }
      }
      
      echo "<div style = 'display:inline-block;width:200px;'><strong>Content Type</strong></div>";
      
      echo "<select name = 'contentType-$inner_array[0]'>";
      
      if ( $contentTypeID == 4 )
      {
        echo "<option value = '4' selected>Standard";
      }
      else
      {
        echo "<option value = '4'>Standard";
      }
      
      echo "</option>";
      
      if ( $contentTypeID == 5 )
      {
        echo "<option value = '5' selected>Video Only";
      }
      else
      {
        echo "<option value = '5'>Video Only";
      }
      echo "</option>";
      
      if ( $contentTypeID == 6 )
      {
        echo "<option value = '6' selected>No Video";
      }
      else
      {
        echo "<option value = '6'>No Video";
      }
      echo "</option>";
      
      if ( $contentTypeID == 7 )
      {
        echo "<option value = '7' selected>No Photos";
      }
      else
      {
        echo "<option value = '7'>No Photos";
      }
      echo "</option>";
      
      if ( $contentTypeID == 8 )
      {
        echo "<option value = '8' selected>Text Only";
      }
      else
      {
        echo "<option value = '8'>Text Only";
      }
      echo "</option>";
      
      echo "</select>";
      
      
      echo "<h2>Post Title</h2>";
      $inner_array_index = 0;
      $postTitleStr = "";
      
      if ( is_array( $zwp_post_title_array ) )
      {
        foreach ($zwp_post_title_array as $key2 => $value2) 
        {
          //echo "dog: $key2[$inner_array_index] <br>"; 
          //echo "cat: $value2[0] <br>";
          //echo "cat2: $value2[1] <br>";
          //echo "cat3: $value2[2] <br>";
          if ( $value2[0] == $inner_array[0] )
          {

            $postTitleStr = $value2[1]; 
            //echo "usable: $postTitleStr<br>";
          }
          $inner_array_index++;
        }
      }
      echo "<textarea rows = '4' cols = '40' name = 'zwp_post_title-$inner_array[0]'>$postTitleStr</textarea>";
      
      echo "<h2>Post Header</h2>";
      $inner_array_index = 0;
      $postHeaderStr = "";
      
      if ( is_array( $zwp_post_header_array ) )
      {
        foreach ($zwp_post_header_array as $key2 => $value2) {
        //echo "dog: $key2[$inner_array_index] <br>"; 
        //echo "cat: $value2[0] <br>";
        //echo "cat2: $value2[1] <br>";
        //echo "cat3: $value2[2] <br>";
        if ( $value2[0] == $inner_array[0] )
        {
          
          $postHeaderStr = $value2[1]; 
          //echo "usable: $postTitleStr<br>";
        }
        $inner_array_index++;
        }
      }
      echo "<textarea rows = '4' cols = '40' name = 'zwp_post_header-$inner_array[0]'>$postHeaderStr</textarea>";
      
      echo "<h2>Post Footer</h2>";
      $inner_array_index = 0;
      $postFooterStr = "";
      
      if ( is_array( $zwp_post_footer_array ) )
      {
        foreach ($zwp_post_footer_array as $key2 => $value2) {
        //echo "dog: $key2[$inner_array_index] <br>"; 
        //echo "cat: $value2[0] <br>";
        //echo "cat2: $value2[1] <br>";
        //echo "cat3: $value2[2] <br>";
        if ( $value2[0] == $inner_array[0] )
        {
          
          $postFooterStr = $value2[1]; 
          //echo "usable: $postTitleStr<br>";
        }
        $inner_array_index++;
        }
      }
      echo "<textarea rows = '4' cols = '40' name = 'zwp_post_footer-$inner_array[0]'>$postFooterStr</textarea>";
      
      echo "<h2>Post Status</h2>";
      $inner_array_index = 0;
      $postStatus = 0;
      
      if ( is_array( $zwp_post_status_array ) )
      {
        foreach ($zwp_post_status_array as $key2 => $value2) {
        //echo "dog: $key2[$inner_array_index] <br>"; 
        //echo "cat: $value2[0] <br>";
        //echo "cat2: $value2[1] <br>";
        //echo "cat3: $value2[2] <br>";
        if ( $value2[0] == $inner_array[0] )
        {
          
          $postStatus = $value2[1]; 
          //echo "usable: $postTitleStr<br>";
        }
        $inner_array_index++;
        }
      }
      
      echo "<select name = 'postStatusID-$inner_array[0]'>";
      if ( $postStatus == 0 )
      {
        echo "<option value = '0' selected>Publish";
      }
      else
      {
        echo "<option value = '0'>Publish";
      }
      echo "</option>";
      
      if ( $postStatus == 1 )
      {
        echo "<option value = '1' selected>Pending";
      }
      else
      {
        echo "<option value = '1'>Pending";
      }
      echo "</option>";
      
      echo "</select>";
      echo "</div>";
    }
    wp_nonce_field( 'update_post_options', 'update_post_options_token' );
    echo "<p><input type='submit' value = 'update post options' class='zaphneSmallButton'></p>";
    ?>
    
  </form>
      
<?php
  }
}

//--------------------------------------------------------------------------------------------------
function zwp_downgrade()
//--------------------------------------------------------------------------------------------------
{
  if ( isset( $_POST[ "zwp_downgrade_form" ] ) ) 
  {
			$responseObj = new stdClass(); 
		  
    
      //print_r( $_POST );
      //echo "<br>";
    
      foreach ($_POST as $key => $value) {
        //echo "$key $value <br>";
        $responseObj->$key = sanitize_text_field($value);
      }
    
      $url = ZWPURL.'DowngradeUser';

      //$post_out = json_encode( $_POST );


      $response = wp_remote_post( $url, array(
        'method' => 'POST',
        'body' => array( 'blogID' =>get_option( 'zaphneWP_user_id' ), 'pluginID' =>get_option( 'zaphneWP_plugin_id'), 'post_out'=> $responseObj )
       ));

      if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo "Something went wrong 281: $error_message";
      } else {
        //print_r($response);
        echo "check downgrade";

        echo $response[ 'body' ];
        //return $response[ 'body' ];
        
        $obj = json_decode($response[ 'body' ], false);

        if( $obj->result_code==0 ) 
        {
          echo "<br>Your account was successfully downgraded.<br>";
          
          //update_option( 'zaphneWP_exclusion_array', $obj->exclusion_array );

        }

      }

		}
  else
  {
  //echo "upgrade<br>";
  echo "<h1>Are you sure you want to lose the many benefits of Zaphne Premium????</h1>";

  $zwp_plugin_id = get_option( 'zaphneWP_plugin_id' );
  $zwp_user_id	= get_option( 'zaphneWP_user_id');
  $zwp_affiliate_code = get_option( 'zaphneWP_affiliate_code' );
?>
  <form action="" method="post">
    
    <input type = 'hidden' name = 'zwp_downgrade_form' value ='1'>
    Please give us some feedback to make Zaphne more useful for you.<br>
    <textarea rows = '4' cols = '40' name = 'zwp_downgrade_reason'></textarea>
    <p><input type='submit' value = 'downgrade' class='zaphneSmallButton'></p>
    
  </form>
<?php
  }
}


//--------------------------------------------------------------------------------------------------
function zwp_section_callback()
//--------------------------------------------------------------------------------------------------
{
    echo "section callback<br>";
}

//--------------------------------------------------------------------------------------------------
function zwp_field_callback()
//--------------------------------------------------------------------------------------------------
{
    echo "search phrase callback<br>";
		$option = get_option( 'zaphneWP_primary_search_phrase' );
    //$name   = esc_attr( $option['name'] );
    echo "<input type='text' name='zaphneWP_primary_search_phrase' value='".get_option( 'zaphneWP_primary_search_phrase')."' />";
}




//--------------------------------------------------------------------------------------------------
?>