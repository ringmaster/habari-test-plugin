<?php

class Test extends Plugin
{
	public function action_init()
	{
		// Not sure what the heck this was for.
		$this->add_template('block.pbi', dirname( __FILE__ ) . '/block.pbi.php' );

		$this->add_rule('"testplugin"', 'foo');

		$this->add_rule('"submit_addon"', 'submit_addon');

		DB::register_table('entries');
	}

	public function action_plugin_act_foo($handler)
	{
		Utils::debug($handler);
		exit();
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
			//'family',
		);
		foreach($groups as $index => $group) {
			if(in_array($group->name, $hidden_groups)) {
				unset($groups[$index]);
			}
		}
		return $groups;
	}

	public function filter_list_unit_tests($tests)
	{
		$unit_tests = glob(dirname(__FILE__). '/units/test_*.php');
		$tests = array_merge($tests, $unit_tests);
		return $tests;
	}
/*
	public function filter_post_schema_map_entry($schema, $post) {
		// Take all of the fields in this post that aren't stored in the posts table,
		// and store them in the invoices table
		$schema['entries'] = $schema['*'];
		$schema['entries']['body'] = 'content';
		// Store the id of the post in the post_id field of the invoices table
		$schema['entries']['post_id'] = '*id';
		return $schema;
	}

	public function filter_posts_get_paramarray($paramarray) {
		$paramarray['post_join'] = array('{entries}');
		$e = isset($paramarray['default_fields']) ? $paramarray['default_fields'] : array();
		$e['{entries}.body'] = '';
		$paramarray['default_fields'] = $e;
		return $paramarray;
	}*/

/*	public function filter_post_default_fields($fields, $params) {
		$fields['{entries}.body'] = '';
		return $fields;
	}*/

	public function filter_posts_get_update_preset($preset_parameters, $presetname, $paramarray) {
		switch($presetname) {
			case 'home':
				$content_type = isset($preset_parameters['content_type']) ? Utils::single_array($preset_parameters['content_type']) : array();
				$content_type[] = 'tweet';
				$preset_parameters['content_type'] = $content_type;
				break;
		}
		return $preset_parameters;
	}

	public function theme_route_submit_addon($theme)
	{
		$theme->display('header');
		$form = new FormUI('addon');

		$json = <<< JSON
{
  "pusher":{
    "name":"ringmaster",
    "email":"a_github@midnightcircus.com"
  },
  "hook_callpath":"new",
  "repository":{
    "name":"secretfile",
    "size":112,
    "has_wiki":true,
    "created_at":"2013-01-22T11:02:54-08:00",
    "private":false,
    "watchers":0,
    "language":"PHP",
    "fork":false,
    "url":"https://github.com/ringmaster/secretfile",
    "id":7758959,
    "pushed_at":"2013-01-22T13:00:26-08:00",
    "has_downloads":true,
    "open_issues":0,
    "has_issues":true,
    "forks":0,
    "description":"This Habari plugin allows the insertion of links to files into a post such that access to those links is only allowed if you are also allowed access to the post they appear in.",
    "stargazers":0,
    "owner":{
      "name":"ringmaster",
      "email":"a_github@midnightcircus.com"
    }
  },
  "forced":false,
  "after":"7b9775cb1f23068c6b37f05d9557ad318fce5f5f",
  "head_commit":{
    "modified":[
      "secretfile.plugin.xml"
    ],
    "added":[

    ],
    "timestamp":"2013-01-22T13:00:00-08:00",
    "author":{
      "name":"ringmaster",
      "username":"ringmaster",
      "email":"a_github@midnightcircus.com"
    },
    "removed":[

    ],
    "url":"https://github.com/ringmaster/secretfile/commit/7b9775cb1f23068c6b37f05d9557ad318fce5f5f",
    "id":"7b9775cb1f23068c6b37f05d9557ad318fce5f5f",
    "distinct":true,
    "message":"Add GUID and help. Fixes #1",
    "committer":{
      "name":"ringmaster",
      "username":"ringmaster",
      "email":"a_github@midnightcircus.com"
    }
  },
  "deleted":false,
  "ref":"refs/heads/master",
  "commits":[
    {
      "modified":[
        "secretfile.plugin.xml"
      ],
      "added":[

      ],
      "timestamp":"2013-01-22T13:00:00-08:00",
      "author":{
        "name":"ringmaster",
        "username":"ringmaster",
        "email":"a_github@midnightcircus.com"
      },
      "removed":[

      ],
      "url":"https://github.com/ringmaster/secretfile/commit/7b9775cb1f23068c6b37f05d9557ad318fce5f5f",
      "id":"7b9775cb1f23068c6b37f05d9557ad318fce5f5f",
      "distinct":true,
      "message":"Add GUID and help. Fixes #1",
      "committer":{
        "name":"ringmaster",
        "username":"ringmaster",
        "email":"a_github@midnightcircus.com"
      }
    }
  ],
  "before":"092e6178fba5289d5820506b7cde3e67edc956f3",
  "compare":"https://github.com/ringmaster/secretfile/compare/092e6178fba5...7b9775cb1f23",
  "created":false
}
JSON;

		$xml = <<< XML
<?xml version="1.0" encoding="utf-8"?>
<pluggable type="plugin">
	<name>Secret File</name>
	<license url="http://www.apache.org/licenses/LICENSE-2.0.html">Apache Software License 2.0</license>

	<author url="http://owenw.com/">Owen Winkler</author>

	<version>1.0</version>
	<url>http://redalt.com/</url>
	<description><![CDATA[This plugin allows the insertion of links to files into a post such that access to those links is only allowed if you are also allowed access to the post they appear in.]]></description>

	<copyright>2013</copyright>

	<help>
		<value><![CDATA[
		<p>Install and activate the plugin.  Create a post.  Find a file in the Habari Silo to insert as a link into the post.  Choose the new menu option "insert secret_link" from the menu under that file.  A shorttag will appear in the editor.  Save the post.

		<p>The shorttag that is created will be rendered as a link by Habari when the post is displayed to a user.  When the user clicks on the link, the selected file downloads.

		<p>Displaying the link in the post saves a value to that user's session.  As a result, the user must visit the post immediately prior to attempting the download.  If the user attempts to go directly to the download URL without visiting the post, they will first be redirected to the post.  If they are not logged in, it's possible that the post will "not exist" for them, and they will instead be directed to the home page.  If permissions prevent the user from viewing the post, they will not be able to download the file.
		]]></value>
	</help>

	<guid>bca39cf7-602c-4f98-87a0-3a98bea2a168</guid>

</pluggable>
XML;



		$form->append(new FormControlTextArea('json', 'null:null', 'JSON'));
		$form->json->value = $json;
		$form->append(new FormControlTextArea('xml', 'null:null', 'XML'));
		$form->xml->value = $xml;
		$form->append(new FormControlSubmit('submit', 'Submit'));
		$form->on_save(array($this, 'addon_success'));
		$form->out();
		$theme->display('footer');
	}

	public function addon_success($form)
	{
		$plugins = Plugins::get_by_interface('PostReceive');
		var_dump($plugins);
		$plugin = reset($plugins);
		var_dump($plugin->process_update(html_entity_decode($form->json->value)));
	}

}
?>