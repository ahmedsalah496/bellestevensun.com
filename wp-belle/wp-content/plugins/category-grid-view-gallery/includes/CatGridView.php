<?php
/**
Functions to retrieve the posts, get the Title, tbe first image and 
to generate thumbnails.
Author : Anshul Sharma (contact@anshulsharma.in)
 */
 
 if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('Sorry, Dude. You are not allowed to call this page directly.'); }
 
 require_once 'CatGridData.php';
 
 class CatGridView{
 	
    private $params = array();
    private $cgoutput;
	private $cgposts;
	private $cgdata;
	private $size = array();
	
	
	public function __construct($atts) {
        $this->params = $atts;
        $this->cgdata = new CatGridData($atts);
		$this->cg_build_output();

    }
	
	 private function cg_build_output(){
	 	global $paginateVal;
	 	$this->cgoutput='<div class="cgview '.get_cg_option('color_scheme').'">';
		$this->cgoutput.= '<ul id="cg-ul">'."\n"; 
        //Posts loop
        foreach ($this->cgdata->cg_get_posts() as $single):
                $this->cgoutput .= $this->cg_build_item($single)."\n";
        endforeach;
		$this->cgoutput.= '</ul>';
		if(get_cg_option('credits')){ $this->cgoutput.= '<div id="cg-credits">Powered by <a href="'.PLUGIN_URI.'" target="_blank">CGView</a></div>'; }
		$this->cgoutput.= '</div>'."\n";
		$paginateVal = $this->params['paginate'];
    }

    /*
	Build each item
     */
    private function cg_build_item($single){
		$size=array();
		$size=$this->cg_get_size();
		
        $cgitem='<li id="cg-'.$single->ID.'" style="width:'.$size[0].'px;height:'.$size[1].'px;">';
		
        $cgitem.= $this->cg_get_image($single);
		
		if(((int)$size[0]>=100||(int)$size[1]>=100))
		$cgitem.= $this->cg_get_title($single);
		
		$cgitem.= '</li>';
		
		$this->cg_active_post = $single->post_content;
		
        return $cgitem;
    }
	
	private function cg_get_image($single){
		$cg_img = '';
		$imgWidth = '';
		$imgHeight = '';
  		ob_start();
  		ob_end_clean();
		if(get_cg_option('image_source')=='featured'){
			if (has_post_thumbnail($single->ID )){
				$image = wp_get_attachment_image_src(get_post_thumbnail_id( $single->ID ), 'single-post-thumbnail' );
				$cg_img = $image[0];
				$imgSize = Array($imgdata[1], $imgdata[2]); // thumbnail's width & height
			}
			else {
				$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $single->post_content, $matches);
				if ( $output ) {
					list( $width, $height, $type, $attr ) = getimagesize($matches[1][0]);
				}
 				$cg_img = $matches [1] [0];
				$imgSize = Array($width, $height);
			}
		}
		else {
 			$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $single->post_content, $matches);
			if ( $output ) {
				list( $width, $height, $type, $attr ) = getimagesize($matches[1][0]);
			}
 			$cg_img = $matches [1] [0];
			$imgSize = Array($width, $height);
		}

  		if(empty($cg_img)){ //Defines a default image
    			$cg_img = get_cg_option('custom_image');
		}
		
		$size=array();
		$size=$this->cg_get_size();
		$height_remainder = $size[1];
		if ( $imgSize[0] > $imgSize[1] ) { //width is greater than height
			$scaler = $size[0] / $imgSize[0];
			$size[1] = $imgSize[1] * $scaler; 
		} else if ($imgSize[1] > $imgSize[0] ) {	//height is greater than width
			$scaler = $size[1] / $imgSize[1];
			$size[0] = $imgSize[0] * $scaler; 
		}
		$height_remainder = $height_remainder-$size[1];
		
			if((!preg_match('/\b[0-9]{3}\b/',$this->params['quality']))||(int)$this->params['quality']>100)
				$this->params['quality']='75';
		//uses TimThumb to generate thumbnails on the fly	
		global $cg_url;	
		$returnlink = ($this->params['lightbox'])? ('"'.$cg_url.'/includes/CatGridPost.php?ID='.$single->ID.'" class="cgpost"') : ('"'.get_permalink($single->ID)).'"';	
		return '<a href='.$returnlink.'><div style="height: 100%; margin-top: '.($height_remainder/2).'px;"><img src="'.str_replace(get_option('siteurl'), '', $cg_img).'" width="'.$size[0].'" height="'.$size[1].'" alt="'.$single->post_title.'" title="'.$single->post_title.'"/></div></a>';
		

	}
	
	private function cg_get_title($single){
		global $cg_url;	
		if($this->params['title']){
			$title_array = get_post_meta($single->ID, $this->params['title']);
			$title = $title_array[0];
			if(!$title){$title = $single->post_title;}
		}
		else { $title = $single->post_title;}
		$returnlink = ($this->params['lightbox'])? ('"'.$cg_url.'/includes/CatGridPost.php?ID='.$single->ID.'" class="cgpost"') : ('"'.get_permalink($single->ID)).'"';
		$cgfontsize=$this->cg_get_font_size();
		$cgtitle='<div class="cgback cgnojs '.$this->params['showtitle'].'"></div><div class="cgtitle cgnojs '.$this->params['showtitle'].'"><p style="font-size:'.$cgfontsize.'px;line-height:'.(1.2*$cgfontsize).'px;"><a href='.$returnlink.'>'.$title.'</a></p></div>';
		return $cgtitle;
	}
	
	public function display(){
        return $this->cgoutput;
    }
	
	public function cg_get_size(){
		$size=array();
		switch ($this->params['size']) {
			case 'medium':
				$size=array('180','180');
				break;
			case 'large':
				$size=array('300','300');
				break;
			case 'thumbnail':
				$size=array('140','140');
				break;
			default:
				if(preg_match('/\b[0-9]{1,4}[xX][0-9]{1,4}\b/',$this->params['size']))
					$size = preg_split('/[xX]+/',$this->params['size'],-1,PREG_SPLIT_NO_EMPTY);	
				else
					$size=array('140','140');					
			}
	
	return $size;
	}
	
//Adjust fontsize according to the thumbnail size. Dont show title if either height or width < 100px	
	public function cg_get_font_size(){
		$size=array();
		$size=$this->cg_get_size();
		$cgfontsize = (4/50)*(int)$size[1];
		if ($cgfontsize>16)
			return 16;
		if ($cgfontsize<8)
			return NULL;
		else
			return $cgfontsize;
	}
	
}

 function cg_init_js(){
 global $paginateVal;
    echo '<script type="text/javascript">';
    echo 'paginateVal = '.$paginateVal.';';
    echo '</script>';
    do_action('cg_init_js');
}

function get_cg_option($option) {
  $get_cgview_options = get_option('cgview');
  return $get_cgview_options[$option];
}

