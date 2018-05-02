<?php
add_action("legion_initial_builder", "wp_test");
function wp_test($ret, $tplSite) {
	/* test template */
	return $ret;
}