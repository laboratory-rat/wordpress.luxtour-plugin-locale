<?
/*
Plugin Name: luxtour localizator
Description: Jus for test and thinking about future
Version: 0.1
Author: Oleg A. T.
Author URI: http://luxtour.online
Plugin URI: http://luxtour.online
*/

class local_page
{
    public $name;
    public $values;

    public function __construct($n)
    {
        $this->name = $n;
        $this->values = Array();
    }
}

class locale
{
    public $name;
    public $pages = array();

    public function __construct($n)
    {
        $this->name = $n;
        $this->pages = array();
    }

    public function add($value)
    {
        $page = $value->page;

        if (!array_key_exists($page, $this->pages))
        {
            $this->pages[$page] = new local_page($page);
        }

        $this->pages[$page]->values[$value->key] = $value->value;
    }
}

class Local
{
    public $db_translate = "local";
    public $lang = "";
    public $current_page;

    function activate()
    {
        global $wpdb;
        $local_db = $wpdb->prefix.$this->db_translate;

        if ($wpdb->get_var("show tables like '$local_db'") != $local_db)
        {
            $sql = "create table `$local_db` ("
            ." id INT NOT NULL AUTO_INCREMENT,"
            ." local varchar(10) not null, "
            ." page varchar(100) not null, "
            ." `key` varchar(100) not null, "
            ." value text null,"
            ." unique key id (id));";

            $wpdb->query($sql);
        }
    }

    function deactivate()
    {

    }

    //function test() { }

    function get_locale($args)
    {
        //print("locale = ".$args['locale']);

        if (false === ($l = get_transient('local')) || 1 == 1)
        {
            $l = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            set_transient('local', $l, 60*60*24);
            $this->load_local("en");
        }
    }

    function load_local($local)
    {
        global $wpdb;
        $db = $wpdb->prefix.$this->db_translate;

        $sql = "select * from $db where local = '$local'";
        $result = $wpdb->get_results($sql);

        $current_locale = new locale($local);

        foreach ($result as $key => $value)
        {
            $current_locale->add($value);
        }

        return $current_locale;
    }

    function select_local()
    {
        $browser_local = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        global $wpdb;
        $db = $wpdb->prefix.$this->db_translate;

        $sql ="select local from $db group by local";
        $result = $wpdb->get_results($sql);

        foreach ($result as $key => $value)
        {
            if ($browser_local == $value->local)
                return $value->local;
        }

        return "en";
    }

    public function _l($key)
    {
        $this->l(array('key'=>$key));
    }

    public function l($args)
    {

        $locale = null;

        if (false === ($locale = get_transient($this->lang)))
        {
            $locale = $this->load_local($this->lang);
            set_transient($this->lang, $locale, 60*60*24);
        }

        $key = "";

        if(array_key_exists('key', $args))
        {
            $key = $args['key'];
        }

        if (array_key_exists($this->current_page, $locale->pages) && array_key_exists($key, $locale->pages[$this->current_page]->values))
        {
            print($locale->pages[$this->current_page]->values[$key]);
            return;
        }

		$tmp = $this->load_local('en');

		if (array_key_exists($this->current_page, $tmp->pages) && array_key_exists($key, $tmp->pages[$this->current_page]->values))
        {
            print($this->pages[$this->current_page]->values[$key]);
            return;
        }

        print("<span style='color: red;'>Translation error -- page: ".$this->current_page." -- key: ".$key."</span>");

    }

    public function set_cookie()
    {

		$result = false;

		if (isset($_GET['lang']) && $_GET['lang'] != '')
		{

			if(false != get_transient($_GET['lang']))
			{
				setcookie('lang', $_GET['lang']);
				$this->lang = $_GET['lang'];
				$this->load_local($this->lang);
				return;
			}
		}


		if (!isset($_COOKIE['lang']))
		{
			$this->lang = $this->select_local();
			setcookie('lang', $this->lang);
		}
		else
			$this->lang = $_COOKIE['lang'];
	}


    public function set_page($title)
    {
        $this->current_page = $title;
    }


	public function admin_menu()
	{
		add_menu_page( 'Luxtour local plagin', 'Luxtour local', 'install_plugins', 'Local', array($this,'Local_admin_page'), 'dashicons-tickets', 6  );
	}

	public function Local_admin_page()
	{
		global $wpdb;
		$db = $wpdb->prefix.$this->db_translate;

		if(isset($_GET['update']))
	    {

			$sql = "select local from $db group by local";
			$result = $wpdb->get_results($sql);

			foreach($result as $r)
			{
				delete_transient($r->local);
				set_transient($r->local, $this->load_local($r->local), 0);

				echo "Updated locale: " . $r->local. "<br />";
			}

	    }


		global $wp;
		$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

		$query = "select local, count(id) as count from $db group by local";
		$result = $wpdb->get_results($query);

		echo "<div class='row'><div class='col-xs-12'>";
		echo "<table>";
		echo "";
		echo "<tr><th>Locale</th><th>Count</th><th>Pages count</th></tr>";

		foreach($result as $r)
		{
			echo "<tr><th>$r->local</th><td>$r->count</td><td>Later</td></tr>";
		}

		echo "</form></div></div>";


		echo "<div class='row'><div class='col-xs-12'>";
		echo "<form method='get' action='$current_url/wp-admin/admin.php'><input name='page' type='hidden' value='Local' /><input name='update' type='hidden' value='update' /><input type='submit' value='update now' /></form>";
		echo "</div></div>";


	}

}

$l = new local();

register_activation_hook( __FILE__, array($l, 'activate'));
register_deactivation_hook( __FILE__, array($l, 'deactivate'));

add_action('init', array($l, 'set_cookie'));

//add_action('wp_loaded', array($l, 'test'));
add_shortcode('loc', array($l, 'get_locale'));
add_shortcode('l', array($l, 'l'));

add_action('admin_menu', array($l, 'admin_menu'));
?>
