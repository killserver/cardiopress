<?php
if(!defined("IS_CORE")) {
	echo "403 ERROR";
	die();
}

$cardinalCache = array();

require_once(PATH_CORE."functions.php");
if(file_exists(PATH_SKINS."custom-functions.php")) {
	include_once(PATH_SKINS."custom-functions.php");
} else if(file_exists(PATH_SKINS."custom-functions.default.php")) {
	include_once(PATH_SKINS."custom-functions.default.php");
}
function clearNamePlugins($name) {
	$name = explode("/", $name);
	$name = current($name);
	return $name;
}

function loadedDone() {
	remove_action('woocommerce_tracker_send_event', 'action_woocommerce_tracker_send_event', 10, 1);
}
add_action('wp_loaded', "loadedDone");

add_action('wp', 'initial_builder');
function initial_builder() {
	global $tplSite, $cardinalCache;
	if(is_admin()) {
		return false;
	}
	$tplSite = "index";
	$loaderPosts = false;
	$legion = apply_filters("legion_initial_builder", false, $tplSite);
	if($legion!==false) {
		$tplSite = $legion;
	} else if(is_embed()) {
		$tplSite = "embed";
	} else if(is_404()) {
		$tplSite = "404";
	} else if(is_search()) {
		$tplSite = "search";
		$loaderPosts = true;
	} else if(is_front_page()) {
		$tplSite = "front_page";
	} else if(is_home()) {
		$tplSite = "index";
	} else if(is_tax()) {
		$tplSite = "tax";
		$loaderPosts = true;
	} else if(is_single()) {
		$tplSite = "single";
	} else if(is_page()) {
		$tplSite = "page";
	} else if(is_singular()) {
		$tplSite = "singular";
	} else if(is_category()) {
		$tplSite = "category";
		$loaderPosts = true;
	} else if(is_tag()) {
		$tplSite = "tag";
		$loaderPosts = true;
	} else if(is_author()) {
		$tplSite = "author";
		$loaderPosts = true;
	} else if(is_date()) {
		$tplSite = "date";
		$loaderPosts = true;
	} else if(is_archive()) {
		$tplSite = "archive";
		$loaderPosts = true;
	}
	if(is_page_template()) {
		$tplSite = get_page_template_slug();
	}
	if(empty($tplSite) || $tplSite=="index" || is_home()) {
		$tplSite = "index";
	}
	$tplSites = apply_filters("legion_after_initial_builder", $tplSite);
	if(!empty($tplSites)) {
		$tplSite = $tplSites;
	}
	apply_filters_ref_array("legion_after_initial_ref", array(&$tplSite));
	if(post_password_required()) {
	?>
		<form class="form-postpass" method="post" action="/wp-login.php?action=postpass">
			<p class="form-postpass-p"><?php echo _e("This content is password protected. To view it please enter your password below:"); ?></p>
			<label for="pwbox-<?php the_ID(); ?>" class="form-postpass-label"><input type="password" size="20" id="pwbox-<?php the_ID(); ?>" name="post_password" class="form-postpass-input" style="margin:10px 0;"></label><br />
			<input type="submit" value="<?php echo _e("Submit"); ?>" class="form-postpass-submit" name="Submit"/>
		</form>
	<?php
	die();
	}
	
	if($tplSite!=="index" && !templates::exists($tplSite)) {
		trigger_error("Шаблон ".$tplSite." не найден");
		//$tplSite = "";
	}
	
	$blockNameForCycle = $tplSite;
	if(!isset($cardinalCache['loaderPosts'])) {
		$cardinalCache['loaderPosts'] = $loaderPosts;
	}
	$cardinalCache['blockNameForCycle'] = $blockNameForCycle;
	
	if(isset($cardinalCache['loaderPosts']) && isset($cardinalCache['blockNameForCycle']) && $cardinalCache['loaderPosts']===true) {
		if(have_posts()) {
			while(have_posts()) {
				the_post();
				global $post;
				addDataPost($post, $cardinalCache['blockNameForCycle']);
			}
			wp_reset_query();
			templates::assign_var("morePages", (more_posts() ? "1" : "0"));
		} else {

		}
	} else {
		addDataPost();
	}
	if(file_exists(PATH_SKINS."site.php")) {
		include_once(PATH_SKINS."site.php");
	} else if(file_exists(PATH_SKINS."site.default.php")) {
		include_once(PATH_SKINS."site.default.php");
	}
}