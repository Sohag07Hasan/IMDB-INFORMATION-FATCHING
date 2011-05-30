<?php
/*
 * plugin name: Imdb data auto insertion
 * author: Sohag Hasan
 * Description: This plugin inserts movies or Tv shows information from imdb server from a given link of that movie or tv show.It also insert the featuer image form as the feature image of your post.Very awesome,isn't it?
 * Author uri: hasan-sohag.blogspot.com
 */ 
//main class 
 if(!class_exists('imdb_movie_information_collection')):
	class imdb_movie_information_collection{
		function advanced_box($post){
			//make the post as golbal using it in callback function
			global $post;
			add_meta_box('imdb-movie-metabox',__('Imdb Movie Link'),array($this,'callback_func'),'imdb_movies','normal','high');
			add_meta_box('imdb-movie-metabox',__('Imdb TV-Show Link'),array($this,'callback_func'),'tv_shows','normal','high');
		}
		
		//calback function 
		function callback_func($post){			
			//var_dump($post);
			//retreiving meta data
			$movie_link = get_post_meta($post->ID,'imdb_movie_link',true);
			
			?>
			<div class="wrap">
				<form method="post" action="/wp-admin/post-new.php">
					<p>Paste the URL of the IMDB movie page you want to get data</p>
					<input id="imdb-movie-link" type="text"  value="<?php echo esc_url($movie_link); ?>" name="imdb_movie" />
					<input type="submit" name="submit_imdb_link" class="button-primary" value="Get Data"/>						
				</form>
			</div>			

<?php
		}
		
		//saving meta data
		function save_meta($post_id){
			if(isset($_REQUEST['imdb_movie_link'])){
				update_post_meta($post_id,'imdb_movie_link',esc_url_raw($_REQUEST['imdb_movie_link']));
			}
		}		
		
		// custom post type
		function custom_post_creation(){

				$tv_args = array(
				'public' => true,
				'query_var' => 'tv_show',
				'rewrite' => true,
				'supports' => array('title','editor','author','thumbnail','custom-fields','comments','excerpt'),
				'has_archive' => true,
				'hierarchical' => true,
				'labels' => array(
					'name' => 'TV Shows',
					'singular_name' => 'Movies',
					'add_new' => 'Add New',
					'add_new_item' => 'Add New TV-Show',
					'edit_item' => 'Edit TV-Show',
					'new_item' => 'New TV-Show',
					'view_item' => 'View TV-Show',
					'search_items' => 'Search TV-Show',
					'not_found' => 'No TV-Show Found',
					'not_found_in_trash' => 'No TV-Show Found In Trash'
				),				
				
				'taxonomies' => array( 'post_tag','category','genre','actor')

			);

			

			$movie_args = array(
				'public' => true,
				'query_var' => 'moive_name',
				'rewrite' => true,
				'supports' => array('title','editor','author','thumbnail','custom-fields','comments','excerpt'),
				'has_archive' => true,
				'hierarchical' => true,
				'labels' => array(
					'name' => 'Movies',
					'singular_name' => 'Movies',
					'add_new' => 'Add New',
					'add_new_item' => 'Add New Movie',
					'edit_item' => 'Edit Movie',
					'new_item' => 'New Movie',
					'view_item' => 'View Movie',
					'search_items' => 'Search Movies',
					'not_found' => 'No Movies Found',
					'not_found_in_trash' => 'No Movies Found In Trash'
				),				
				
				'taxonomies' => array( 'post_tag','category','genre','actor')

			);
		
			register_post_type('imdb_movies', $movie_args );
			register_post_type('tv_shows', $tv_args);
		}

		//custom post type
		
		//custom taxonomy
		function custom_texanomy_creation(){
			$labels = array(
				'name' => 'genre', 
				'search_items' =>  __( 'Search Genres' ),
				'all_items' => __( 'All Genres' ),
				'parent_item_colon' => __( 'Parent Genre:' ),
				'edit_item' => __( 'Edit Genre' ), 
				'update_item' => __( 'Update Genre' ),
				'add_new_item' => __( 'Add New Genre' ),
				'new_item_name' => __( 'New Genre Name' ),
				'menu_name' => __( 'Genre' ),				
			  );
			 register_taxonomy('genre',array('imdb_movies','tv_shows'), array(
				'hierarchical' => true,
				'labels' => $labels,
				'public' => true,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'genre','with_front' => false ),
				
				'_builtin' => true,
			  ));
			  $label = array(
				'name' => 'actor', 
				'singular_name' => 'actor',
				'search_items' =>  __( 'Search actor' ),
				'all_items' => __( 'All actor' ),
				'parent_item' => __( 'Parent actor' ),
				'parent_item_colon' => __( 'Parent actor:' ),
				'edit_item' => __( 'Edit actor' ), 
				'update_item' => __( 'Update actor' ),
				'add_new_item' => __( 'Add New actor' ),
				'new_item_name' => __( 'New actor Name' ),
				'menu_name' => __( 'Actor' ),
			  );
			  register_taxonomy('actor',array('imdb_movies','tv_shows'), array(
				'hierarchical' => false,
				'labels' => $label,
				'public' => true,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'actor','with_font' => false ),
			  ));
			 			  
		}

		function test($attachment,$post_id){
			
						
			return $attachment;			
		}
		function errormessage(){
			echo '<div id="message" class="error"><p>Sorry! Information not found</p></div>';
		}

		//admin menu
		function optionsPage(){
			add_options_page('user fttp information','IMDB-MOVIE','activate_plugins','settings-ftp',array($this,'optionsPageDetails'));
		}
		
		
		//creating options page in admin panel
		function optionsPageDetails(){
			$request = $_REQUEST['error'];			
			if($request == 1){
				echo '<div id="message" class="error"><p>Please Check your FTP information</p></div>';
			}			
			$data = get_option('ftp_information');
			$server = $data['server'];
			$name = $data['name'];
			$password = $data['password'];
			$image = $data['image'];
			//starin html form
		?>
			<div class="wrap">
				<?php screen_icon('options-general'); ?>
				<h2>IMdb Plugin's settings</h2>
				<form action="options.php" method="post">
					<?php 
						settings_fields('ftp_information');							
						$data = get_option('ftp_information');
						$server = $data['server'];
						$user = $data['user'];
						$password = $data['password'];
						$image = $data['image'];
						 							
					?>					
					<table class="form-table">
							<tr valign="top"><th scope="row">FTP SERVER </th>
							
								<td><input name="ftp_information[server]" type="text" value= "<?php echo $server; ?>" /></td>												
							</tr>
							
							<tr valign="top"><th scope="row">FTP USER </th>
							
								<td><input name="ftp_information[user]" type="text" value= "<?php echo $user; ?>" /></td>												
							</tr>
							<tr valign="top"><th scope="row">FTP password </th>
							
								<td><input name="ftp_information[password]" type="text" value= "<?php echo $password; ?>" /></td>												
							</tr>
							<tr>
								<td colspan="3"> Please Insert wp-content direcotory relative to ftp root directory (".../wp-content") pls see the screenshots attached &nbsp <a href= "<?php echo plugins_url('/imdb-scraping-easy/screenshots/screenshots.png'); ?>" target="_blank">screenshots</a> </td>
								
							</tr>
							<tr valign="top"><th scope="row">FTP Path </th>
							
								<td colspan="3"> <input name="ftp_information[image]" type="text" value= "<?php echo $image; ?>" /></td>												
							</tr>
							<tr>
								<td>
								<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
								</td>
							<tr>
						</table>
				</form>
			</div>
			
		<?php
			
		}
		function registerOption(){
			register_setting('ftp_information','ftp_information',array($this,'data_validation'));			
		}

		function data_validation($data){
			$sanitize = array();
			$sanitize['server'] =trim(strip_tags($data['server']));
			$sanitize['user'] =trim(strip_tags($data['user']));
			$sanitize['password'] =trim(strip_tags($data['password']));
			$sanitize['image'] =trim(strip_tags($data['image']));
			return $sanitize;
		}

		//ftp message
		
		function ftpmessage(){			
			echo '<div id="message" class="error"><p>Please Check your FTP information</p></div>';			
		}
		

		//ftp proble,
		public function ftp_problem(){
			//add_action('admin_notices',array($imbd_movie_data,'ftpmessage'));
			$settings_url = get_option('home').'/wp-admin/options-general.php?page=settings-ftp&error=1';
			header("Location:$settings_url");
			exit;	
		}

		//ftp uploader
		function ftpUpload($url,$file,$connection,$name){
			$dir = $file.'/uploads/imdb';
			$upload = $dir.'/'.$name.'.jpg';
			if(ftp_put($connection, $upload, $url,FTP_BINARY) == false){				
				if(ftp_mkdir($connection, $dir)){
					ftp_chmod($connection,0777,$dir);
					ftp_put($connection, $upload, $url,FTP_BINARY);	
				}
				else{
					$this->ftp_problem();
				}
							
			}
			if(ftp_chmod($connection,0777,$upload) == false){
				$gyog = '';
			}		
		}			
		
		
	}
		
	$imbd_movie_data = new imdb_movie_information_collection();
	add_action('add_meta_boxes',array($imbd_movie_data,'advanced_box'));
	add_action('init',array($imbd_movie_data,'custom_post_creation'));
	add_action('init',array($imbd_movie_data,'custom_texanomy_creation'));
	//add_filter('wp_get_attachment_url',array($imbd_movie_data,'test'));
	add_action('admin_menu',array($imbd_movie_data,'optionsPage'));	
	add_action('admin_init',array($imbd_movie_data,'registerOption'));
 endif;
 
//INCLUDING IMDB CLASS
 require_once('data.php');

?>
