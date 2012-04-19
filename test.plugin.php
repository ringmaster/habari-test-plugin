<?php

class Test extends Plugin
{
	public function action_init()
	{
		$this->add_template('block.pbi', dirname( __FILE__ ) . '/block.pbi.php' );
	}


	/**
	 * Simple plugin configuration
	 * @return FormUI The configuration form
	 **/
	public function configure()
	{
		/*
		$form = new FormUI( 'test' );
 
		$terms = Vocabulary::get('categories')->get_tree();
 
		$options = array();
		foreach($terms as $term) {
			$options[$term->id] = $term->term_display;
		}
 
		$form->append( 'tree', 'tree', $terms, 'test');
		//$form->append( 'tree', 'tree2', 'null:null', 'test', $options);
 
		$form->append( 'submit', 'save', _t( 'Save' ) );
 
		return $form;
		 */
		$form = new FormUI( 'test' );
		$form->append( new FormControlText('name', 'soup__name', 'Soup Name'));
		$form->append( new FormControlSelect('type', 'soup__type', 'Soup Type', array('stock', 'cream')));
		$form->append( new FormControlSubmit('save', _t( 'Save' )));

		$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($form->get_xml()->asXML());
		Utils::debug($dom->saveXML());

		return $form;

		//DB::query('delete from {terms} where id in (14,17)');

		/*
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			Utils::debug($_POST->get_array_copy_raw());
		}

		echo <<< FORM
<form method="post">
<input type="text" name="properties[element1][type]" value="textarea">
<input type="text" name="properties[element2][type]" value="select">
<input type="submit" value="Submit">
</form>
FORM;
		*/
	}
	
	function filter_posts_search_to_get ( $arguments, $flag, $value, $match, $search_string)
	{
		if($flag == 'fruit') {
			$arguments['info'] = array('fruit'=>$value);
		}
		return $arguments;
	}

	function filter_default_rewrite_rules($rules)
	{
		$rules[] = array( 
			'name' => 'display_column', 
			'parse_regex' => '%^column/(?P<slug>[^/]*)(?:/page/(?P<page>\d+))?/?$%i', 
			'build_str' => 'column/{$slug}(/page/{$page})', 
			'handler' => 'PluginHandler', 
			'action' => 'display_column', 
			'priority' => 4, 
			'description' => 'Return articles matching specified column.',
		);
		return $rules;
	}

	function filter_rewrite_rules($rules)
	{
//		$rules[] = new RewriteRule( array(
//			'name' => 'fruit',
//			'parse_regex' => '%fruit/(?P<fruit>.+)/?$%i',
//			'build_str' => 'fruit/{$fruit}',
//			'handler' => 'PluginHandler',
//			'action' => 'fruit',
//			'priority' => 7,
//			'is_active' => 1,
//			'description' => 'Displays the fruit page',
//		));
		$rules[] = RewriteRule::create_url_rule('"fruit"/fruit_name', 'PluginHandler', 'fruit');
		return $rules;
	}


	public function action_plugin_act_display_column($handler)
	{
		Utils::debug(
			Posts::get( array('content_type' => Post::type('section'), 'nolimit' => TRUE, 'status' => Post::status('published'), 'orderby' => 'title ASC', 'fetch_fn' => 'get_perms') )
		);
//		Utils::debug($handler->handler_vars);
	}

	
	function action_plugin_act_fruit($handler)
	{
		$handler->theme->act_display_entries(array('info'=>array('fruit'=>$handler->handler_vars['fruit_name'])));
	}
	
	function action_post_get_perm_where($perm_where, $paramarray)
	{
		//$perm_where->add('(1=0)');
	}
	
	function action_upgrade($oldversion)
	{
		Session::notice('upgrade ' . $oldversion);
	}
		
	function action_theme_deactivated($themename)
	{
		Session::notice(_t('test deactivated %s', array($themename)));
	}

	function action_theme_activated($themename)
	{
		Session::notice(_t('test activated %s', array($themename)));
	}
	
	function action_theme_deactivated_any($themename, $theme)
	{
		Session::notice(_t('test any deactivated %s', array($themename)));
	}

	function action_theme_activated_any($themename)
	{
		Session::notice(_t('test any activated %s', array($themename)));
	}

	public function filter_block_list($block_list)
	{
		$block_list['postsbyinfo'] = _t( 'Posts By Info');
		return $block_list;
	}
	
	public function action_block_content_postsbyinfo($block, $theme)
	{
/*		$params = array();
		$params["content_type"] = Post::type('event');
		//$params["not:all:info"] = array("ankündigung" => "1");
		$params["status"] = Post::status('published');
		$params["nolimit"] = "";
		$params["has:info"] = "eventdate";
		$params["orderby"] = "cast(hipi1.value as unsigned) DESC";
		$params['fetch_fn'] = 'get_results';
// Utils::debug(Posts::get(array_merge($params, array('fetch_fn'=>'get_query'))));
		$events = Posts::get($params);
//		Utils::debug($events);
		$content = 'This is where the content should appear:';
		$content .= Posts::get(array_merge($params, array('fetch_fn'=>'get_query')));
		foreach($events as $event) {
			$content .= $theme->content($event);
		}*/



		$content = date('M j, Y H:i:s') . '<br>';
		$content .= '<a href="' . $block->_ajax_url . '" onclick="t=$(this);t.parents(\'.block\').load(t.attr(\'href\'));return false;">Reload</a>';
		$block->content = $content;
		$block->_ajax = true;
	}

	public function filter_post_content_out($content, $post)
	{
		if(strpos($content, '<!--postform-->') !== false) {
			$p = $post; //new Post();
			$p->content_type = 'page';
			$form = $p->get_form('public');
			$form->on_success(array($this, 'success'));
			//Utils::debug($form);
			$content = preg_replace('#<!--postform-->#i', $form->out(), $content);
		}
		return $content;
	}

	public function success($form)
	{
		//Utils::debug($form);die();
		Session::notice('Submitted form');
	}

	public function filter_areas($areas, $scope)
	{
		if($scope != 0) {
			$areas['foo'] = 'FOO';
		}
		return $areas;
	}

	public function filter_admin_groups_visible($groups)
	{
		$groups = $groups->getArrayCopy();
		$hidden_groups = array(
			'new test group',
			'family',
		);
		foreach($groups as $index => $group) {
			if(in_array($group->name, $hidden_groups)) {
				unset($groups[$index]);
			}
		}
		return $groups;
	}

}
?>