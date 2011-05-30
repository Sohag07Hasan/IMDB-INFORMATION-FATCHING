<?php

	//import the object into this file
	include 'imdb.php';
	if(isset($_REQUEST['submit_imdb_link'])) :
	
		//putting some ftp functions
		$ftp = get_option('ftp_information');		
		$ftp_server = $ftp['server'];
		$ftp_user = $ftp['user'];
		$ftp_pass = $ftp['password'];
		$ftp_path = $ftp['image'];	

		if(empty($ftp_server)) { $imbd_movie_data->ftp_problem();}
		if(empty($ftp_user)) { $imbd_movie_data->ftp_problem(); }
		if(empty($ftp_pass)) { $imbd_movie_data->ftp_problem(); }
		if(empty($ftp_path)) { $imbd_movie_data->ftp_problem(); }		
		
		$link = esc_url_raw($_REQUEST['imdb_movie']);		
		$m = new imdbInfo();
		// get data
		$info = $m->getDataFromIMDB($link);
		
		
		// error control
		if ($info['error'] == null) {

			//checking the post type is movie or tv shows
			if($info['type']=='tv_show'){
				$taxonomy = 'tv_shows';
			}
			else{
				$taxonomy = 'imdb_movies';
			}		
			
			//ftp uploader function clling
			$conn_id = ftp_connect($ftp_server);
			if($conn_id == false){
				$imbd_movie_data->ftp_problem();
			}
			else{
				$login_result = ftp_login($conn_id, $ftp_user, $ftp_pass);
			//uploader
				if($login_result){				
					$imbd_movie_data->ftpUpload($info['poster'],$ftp_path,$conn_id,$info['id']);
				}
				else{
					$imbd_movie_data->ftp_problem();
				}
				//closing ftp connection		
				ftp_close($conn_id);
			}

			//attachment adding
			$uploads_url = wp_upload_dir();	
			$image_src = $uploads_url['basedir'].'/imdb/'.$info['id'].'.jpg';
			//attchment data
			$attachment = array(
				 'post_mime_type' => 'image/jpeg',
				 'post_title' => $info['id'],
				 'post_content' => '',
				 'post_status' => 'inherit'				 
			  );
			$attach_id = wp_insert_attachment( $attachment,$image_src);
			//adding attachment meta data
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata($attach_id,$image_src);			
			wp_update_attachment_metadata($attach_id,$attach_data);
			$image_url = get_option('home').'/wp-content/imdb/'.$info['id'].'.jpg';
			$wpdb->update($wpdb->posts,array('guid'=>$image_url),array('ID'=>$attach_id),array('%s'),array('%s'));

			
			$post_image = get_option('home').'/wp-content/uploads/imdb/'.$info['id'].'.jpg';
			$post_content = "<img title='$info[id]' src='$post_image' alt='' />";				
			$data = array(
				'post_type' =>$taxonomy,
				'post_title' => $info['title'],
				'post_content' => $post_content.strip_tags($info['plot']),
				'post_status' => 'draft',			
				'post_date' => date("Y-m-d H:i:s",time()),
				'post_date_gmt' =>date("Y-m-d H:i:s",time()),		
				'ping_status' =>'open',				
				
			);
			//inserting data with some defined data
			$p_id = wp_insert_post( $data, $wp_error );			
			
			$result = mysql_query("SELECT t1.term_id, t2.slug FROM $wpdb->term_taxonomy AS t1 INNER JOIN $wpdb->terms AS t2 ON t1.term_id = t2.term_id WHERE t1.taxonomy='genre'",$wpdb->dbh);

			$casts_tags = mysql_query("SELECT t1.term_id, t2.slug FROM $wpdb->term_taxonomy AS t1 INNER JOIN $wpdb->terms AS t2 ON t1.term_id = t2.term_id WHERE t1.taxonomy='actor'",$wpdb->dbh);
			$casts = array();
			foreach($info['cast'] as $evcast=>$value){
				$casts[] = $evcast;
			}
			//$actor = $casts[0];
			//$actor_slug = strtolower(str_replace(' ','-',$actor));
			//$actor_slug = strtolower($actor_slug);
			
			while ($go = mysql_fetch_assoc($casts_tags)) {
				//$actorIDs[] = $go['term_id'];
				$actorSlugs[] = $go['slug'];
			}
			
			//inserting actor as pos_tag
			foreach($casts as $cast){
				$actor = strip_tags($cast);
				$actor_slug = strtolower(str_replace(' ','-',$actor));
				if(!in_array($actor_slug,$actorSlugs)){				
					$wpdb->insert_id = null;				
					$wpdb->insert( $wpdb->terms,array('name'=>$actor,'slug'=>$actor_slug), array('%s','%s'));
					$wpdb->insert( $wpdb->term_taxonomy,array('term_id'=>$wpdb->insert_id,'taxonomy'=>'actor','count'=>1), array('%d','%s','%d'));
					$wpdb->insert( $wpdb->term_relationships,array('object_id'=>$p_id,'term_taxonomy_id'=>$wpdb->insert_id), array('%d','%d'));

					$wpdb->insert_id = null;
				}
				else{
					$used_id = $wpdb->get_var("SELECT term_id From $wpdb->terms WHERE slug = '$actor_slug'");
					$used_count = $wpdb->get_var("SELECT count FROM $wpdb->term_taxonomy WHERE term_id='$used_id' ");
					$wpdb->update($wpdb->term_taxonomy,array('count'=>$used_count+1),array('term_id'=>$used_id),array('%d'),array('%d'));
					$wpdb->insert( $wpdb->term_relationships,array('object_id'=>$p_id,'term_taxonomy_id'=>$used_id), array('%d','%d'));
					$wpdb->insert_id = null;
				}
			}
										
			$termIds = array();
			$termSlugs  = array();
			
			while ($goto = mysql_fetch_assoc($result)) {
				//$termIds[] = $goto['term_id'];
				$termSlugs[] = $goto['slug'];
			}
			
			//INSERTING TERMS IN TERM TABLES		
			
			foreach($info['genres'] as $value){
			//	['terms'][] = strtolower($value);
				if(!in_array(strtolower($value),$termSlugs)){
					$wpdb->insert_id = null;
					$wpdb->insert( $wpdb->terms,array('name'=>$value,'slug'=>strtolower($value)), array('%s','%s'));					
					$wpdb->insert( $wpdb->term_taxonomy,array('term_id'=>$wpdb->insert_id,'taxonomy'=>'genre','count'=>1), array('%d','%s','%d'));		
					$wpdb->insert( $wpdb->term_relationships,array('object_id'=>$p_id,'term_taxonomy_id'=>$wpdb->insert_id), array('%d','%d'));
					$wpdb->insert_id = null;
				}
				
				else{
					$value = strtolower($value);
					$editT = $wpdb->get_var("SELECT term_id From $wpdb->terms WHERE slug = '$value'");
					$count = $wpdb->get_var("SELECT count FROM $wpdb->term_taxonomy WHERE term_id='$editT' ");					
					$wpdb->update($wpdb->term_taxonomy,array('count'=>$count+1),array('term_id'=>$eitT),array('%d'),array('%d'));
					$wpdb->insert( $wpdb->term_relationships,array('object_id'=>$p_id,'term_taxonomy_id'=>$editT), array('%d','%d'));
					$wpdb->insert_id = null;	
				}
			}
				
			//adding meta data
				$wpdb->insert($wpdb->postmeta,array('post_id'=>$p_id,'meta_key'=>'rating','meta_value'=>$info['rating']),array('%d','%s','%s'));			
				$wpdb->insert($wpdb->postmeta,array('post_id'=>$p_id,'meta_key'=>'release_date','meta_value'=>$info['release_date']),array('%d','%s','%s'));
				$wpdb->insert($wpdb->postmeta,array('post_id'=>$p_id,'meta_key'=>'_thumbnail_id','meta_value'=>$attach_id),array('%d','%s','%d'));
				$wpdb->insert($wpdb->postmeta,array('post_id'=>$p_id,'meta_key'=>'IMDB-URL','meta_value'=>$link),array('%d','%s','%s'));
				$wpdb->insert($wpdb->postmeta,array('post_id'=>$p_id,'meta_key'=>'runtime','meta_value'=>$info['runtime'].' min'),array('%d','%s','%s'));
					
			$redirect_url = get_option('home').'/wp-admin/post.php?post='.$p_id.'&action=edit';
			
			header("Location: $redirect_url");
			exit;
		}
		
		else {
			add_action('admin_notices',array($imbd_movie_data,'errormessage'));
			return;			
		}
		 
	endif;
?>
