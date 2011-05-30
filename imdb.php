<?php
// define constants
define('NO_POSTER','Poster is not available.');
define('INVALID_URL','This URL is invalid. Please try again.');
    class imdbInfo
    {
		private $imdbUrl = null;
		
        function __construct() {
			// no need to initialize anything
        }
		// control the link is valid or not
		private function linkController() {
			$result = true;
			$imdbId = $this->match('/imdb.com\/title\/(tt[0-9]+)/ms', $this->imdbUrl, 1);
			if($imdbId != "") {
				$this->imdbUrl = 'http://www.imdb.com/title/'.$imdbId.'/';
			} else {
				$result = false;
			}
			return $result;
		}

		// get data from IMDB
		function getDataFromIMDB($url) {
			$this->imdbUrl = $url;
            $info = array();
			$linkControl = $this->linkController();
			if ($linkControl) {
				$html = $this->getContent();
				$info['id'] = $this->match('/poster.*?(tt[0-9]+)/ms', $html, 1);
				$info['type'] = $this->match('/<meta.*?property=.og:type.*?content=.(.*?)(\'|")/ms', $html, 1);					
					
				$info['title'] = $this->match('/<title>(.*?)<\/title>/ms', $html, 1);
				$info['title'] = $this->match('/(.*?) - IMDb/ms', $info['title'], 1);
				$numbersInTitle = $this->match_all('/\((.*?)\)/', $info['title'], 1);
				$info['title'] = preg_replace('/\([0-9]+\)/', '', $info['title']);
				$info['title'] = trim($info['title']);
				if (count($numbersInTitle) == 1) {
					$info['year'] = $numbersInTitle[0];
				} else if (count($numbersInTitle) == 2) {
					$info['year'] = $numbersInTitle[1];		
					$info['title'] = '('.$numbersInTitle[0].') '.$info['title']; 
				}
				$info['rating'] = $this->match('/<span class="rating-rating">(.*?)<span>/ms', $html, 1);            
				$info['runtime'] = $this->match('/([0-9]+) min/ms', $html, 1);
				//$info['poster'] = $this->match('/<a.*?name=.poster.*?src=.(.*?)(\'|")/ms', $html, 1);
				$info['poster'] = $this->match('/<img.*?src="http:\/\/ia.media-imdb.com\/images\/(.*?)".*?Poster.*?/ms', $html, 1);
				$info['poster'] = 'http://ia.media-imdb.com/images/'.$info['poster'];	  
				if ($info['poster'] == "") {
					$info['poster'] = NO_POSTER;
				}
				//$info['plot'] = trim(strip_tags($this->match('/<h2>Storyline<\/h2><p>(.*?)<\/p>/ms', $html, 1)));
				$info['plot'] = $this->match('/<h2>Storyline<\/h2>(.*?)<span.*?>/ms', $html, 1);
				
				/*$info['directors'] = $this->match_all('/<div class="txt-block">.*?Director.*?href="\/name\/.*?\/">(.*?)<\/a><\/div>/ms', $html, 1);		
				$info['directors'] = array_unique($info['directors']);	 */

				$info['directors'] = $this->match('/<div class="txt-block">.*?Director.*?<\/h4>(.*?)<\/div>/ms', $html, 1);	
				$info['directors'] = $this->match_all('/<a.*?href="\/name\/.*?\/">(.*?)<\/a>/ms', $info['directors'], 1);
				$info['directors'] = array_unique($info['directors']);	
				
				$info['writers'] = $this->match('/<div class="txt-block">.*?Writer.*?<\/h4>(.*?)<\/div>/ms', $html, 1);		
				$info['writers'] = $this->match_all('/<a.*?href="\/name\/.*?\/">(.*?)<\/a>/ms', $info['writers'], 1);		
				$info['writers'] = array_unique($info['writers']);				
				
			/*	$info['creators'] = $this->match_all('/<a href="\/name\/.*?\/" onclick=".*?\/creatorlist\/.*?">(.*?)<\/a>/ms', $html, 1);		
				$info['creators'] = array_unique($info['creators']);*/	

				$info['creators'] = $this->match('/<div class="txt-block">.*?Creator.*?<\/h4>(.*?)<\/div>/ms', $html, 1);	
				$info['creators'] = $this->match_all('/<a.*?href="\/name\/.*?\/">(.*?)<\/a>/ms', $info['creators'], 1);
				$info['creators'] = array_unique($info['creators']);					
				
				$info['release_date'] = $this->match('/([0-9][0-9]? (January|February|March|April|May|June|July|August|September|October|November|December) (19|20)[0-9][0-9])/ms', $html, 1);
				$info['genres'] = $this->match_all('/link=%2Fgenre%2F(.*?)\';"/ms', $html, 1);
				$info['genres'] = array_unique($info['genres']);
				
				//$info['languages'] = $this->match_all('/a.*?href="\/language\/.*?">(.*?)<\/a>/ms', $html, 1);
				$info['languages'] = $this->match_all('/a href="\/language\/.*?">(.*?)<\/a>/ms', $html, 1);
				$info['languages'] = array_unique($info['languages']);
				
				$info['countries'] = $this->match_all('/a href="\/country\/.*?">(.*?)<\/a>/ms', $html, 1);
				$info['countries'] = array_unique($info['countries']);	            
				
				$info['companies'] = $this->match_all('/a.*?href="\/company\/.*?">(.*?)<\/a>/ms', $html, 1);

				$info['companies'] = array_unique($info['companies']);	
				
				//$info['cast'] = $this->match('/<table class="cast_list">(.*?)<\/table>/ms', $html, 1);	

				foreach($this->match_all('/class="name">(.*?\.\.\..*?)<\/tr>/ms', $html, 1) as $m)
				{
					list($actor, $character) = explode('...', strip_tags($m));
					$info['cast'][trim($actor)] = trim($character);
				}				
				//$info['cast'] = strip_tags(	$info['cast'] );
				
				//$actors = $this->match_all('/class="name">.*?href="\/name\/.*?">(.*?)<\/a>/ms', $html, 1);
				//$characters = $this->match_all('/class="character">.*?href="\/character\/.*?">(.*?)<\/a>/ms', $html, 1);
				//$info['cast']= array_combine($actors, $characters);
				//$info['cast'] = array_unique($info['cast']);		
				//$info['cast'] = array();
				/*foreach($this->match_all('/class="name".*?>(.*?)<\/td>.*?class="character".*?>(.*?)<\/td>/ms', $html, 1) as $m)
				{
					//list($actor, $character) = explode('...', strip_tags($m));
					//$info['cast'][trim($actor)] = trim($character);
					list($actor, $character) = $m;
					$info['cast'][trim($actor)] = trim($character);
					//$info['cast'] = $m;
				}*/
			} else {
				$info['error'] = INVALID_URL;
			}
		//	$info['title'] = htmlspecialchars($html);
            return $info;
        }		
		// get content of target IMDB url
        private function getContent() {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->imdbUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            $html = curl_exec($ch);
            curl_close($ch);
            return $html;
        }
		// for regular expression call
        private function match_all($regex, $str, $i = 0) {
            if(preg_match_all($regex, $str, $matches) === false)
                return false;
            else
                return $matches[$i];

        }
		// for regular expression call
        private function match($regex, $str, $i = 0) {
            if(preg_match($regex, $str, $match) == 1)
                return $match[$i];
            else
                return false;
        }
    }
?>
