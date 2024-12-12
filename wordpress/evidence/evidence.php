<?php
/*
Plugin Name: Evidence členů
Description: Zaevidování členů do aplikace a jejich uložení
*/
/* Start Adding Functions Below this Line */


//register creating database
register_activation_hook( __FILE__, 'kckevidence_create_db' );
register_uninstall_hook(__FILE__, 'kck_delete_database_tables');

//create database 
function kckevidence_create_db() {

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table1_name = $wpdb->prefix . 'kckevidence_members';
	$sql_table_1 = "CREATE TABLE IF NOT EXISTS $table1_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		member_name varchar(50) NOT NULL,
		member_sur varchar(50) NOT NULL,
        member_email varchar(50) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";


    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_table_1 );
}

//Plugin uninstall database
function kck_delete_database_tables(){
	global $wpdb;
	$tableArray = [   
		$wpdb->prefix . 'kckevidence_members'
];

	foreach ($tableArray as $tablename) {
		$wpdb->query("DROP TABLE IF EXISTS $tablename");
	}
}

// register jquery and style on initialization
add_action('init', 'kck_register_script');

function kck_register_script() {
    wp_register_script( 'kck_script', plugins_url('/js/fe.js', __FILE__), array('jquery'), '0.0.2' );
	wp_localize_script('kck_script', 'ipAjaxVar', array('ajaxurl' => admin_url('admin-ajax.php')));

    wp_register_style( 'kck_style', plugins_url('/css/style.css', __FILE__), false, '0.0.2', 'all');
}

// use the registered jquery and style above
add_action('wp_enqueue_scripts', 'kck_enqueue_style');

function kck_enqueue_style(){
   wp_enqueue_script('kck_script');
   
   wp_enqueue_script('jquery');
   wp_enqueue_script('jquery-ui-draggable');
   wp_enqueue_script('jquery-ui-resizable');
 
   wp_enqueue_style( 'kck_style' );
}

//create new member
function create_member($name, $surname, $email) {
	global $wpdb;

	$table1_name = $wpdb->prefix . 'kckevidence_members';

    echo ('Test');
	$wpdb->insert( 
		$table1_name, 
		array( 
			'time' => current_time( 'mysql' ), 
			'member_name' => $name,
			'member_sur' => $surname, 
            'member_email' => $email,
		) 
	);
    $memberid = $wpdb->insert_id;

	return  $memberid;
}


// Creating the widget 
class kckevidence_widget extends WP_Widget {

    function __construct() {
    parent::__construct(
    // Base ID of your widget
    'kckevidence_widget', 
    
    // Widget name will appear in UI
    __('Evidence', 'kckevidence_widget_domain'), 
    
    // Widget description
    array( 'description' => __( 'Show evidence', 'kckevidence_widget_domain' ), ) 
    );
    }
    
    // Creating widget front-end
    // This is where the action happens
    public function widget( $args, $instance ) {
    global $wpdb;

    $title = apply_filters( 'widget_title', $instance['title'] );
    
    // before and after widget arguments are defined by themes
    echo $args['before_widget'];
    if ( ! empty( $title ) ) 
    echo $args['before_title'] . $title . $args['after_title'];
    
    //create seats area
    echo __( sprintf('<div id="kckevidence_evidence">'), 'kckevidence_widget_domain' ); 
    
    $new_member = new Member(); 

    $htmlContent = $new_member->renderInputForm();
    
    $table1_name = $wpdb->prefix . 'kckevidence_members';
    
    $members = $wpdb->get_results("SELECT * FROM $table1_name");

	$htmlContent .= sprintf('<h2>Seznam členů</h2>');

	//render members 
	foreach ($members as $mem) {
	
		$objMember = Member:: withRow ($mem);
	
		$htmlContent .= $objMember->renderMember();
	}  

    echo __( $htmlContent, 'kckevidence_widget_domain' ); 
    
    //end of seats area
    echo __( '</div>', 'kckevidence_widget_domain' ); 
    
    echo $args['after_widget'];
    }
    
    // Widget Backend 
    public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) ) {
    $title = $instance[ 'title' ];
    }
    else {
    $title = __( 'New title', 'kckevidence_widget_domain' );
    }
    
    // Widget admin form
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php 
    }
        
    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
    }

} // Class ssa_widget ends here

    //create new member 
    add_action('wp_ajax_nopriv_kck_create_member', 'kck_create_member');
    add_action('wp_ajax_kck_create_member', 'kck_create_member');

    function kck_create_member() {
        global $wpdb;
        
        $fname = $_POST['firstName']; 
        $sname = $_POST['secondName'];
        $mail = $_POST['email'];

        $uId = create_member($fname , $sname, $mail);

    }

    // Register and load the widget
    function kckevidence_load_widget() {
        register_widget( 'kckevidence_widget' );
    }
    add_action( 'widgets_init', 'kckevidence_load_widget' );
    
// podle entity modelu dodefinovat databázi, musí být vazby mezi skupinami, alespoň rozmyslet
// ---
// informace o členovi, 
class Member {
	protected $_id;
	protected $_first_name;
	protected $_second_name;
	protected $_email;
	protected $_phone;

	public function __construct() {
		$this->_id = '';
		$this->_first_name = '';
		$this->_second_name ='';
		$this->_email = '';
		$this->_phone = '';
	}

    public static function withRow( array $row ) {
        $instance = new self();
        $instance-> setFirstname( $row -> member_name );
        return $instance;

    }
	public function getId() {
		return $this->_id;
	}

    public function setId($id) {
        $this->_id = $id;
	}

	public function getFirstName() {
		return $this->_first_name;
	}

    public function setFirstName($name) {
        $this->_name = $name;
    }

	public function getSecondName() {
		return $this->_second_name;
	}

    public function setSecondname($secondname) {
        $this ->_second_name = $secondname;
    }
	public function getEmail() {
		return $this->_email;
	}
    public function setEmail($email){
        $this ->_email = $email;
    }
	public function getPhone() {
		return $this->_phone;
	}
    public function setPhone($phone) {
        $this -> _phone = $phone;
    }

	public function renderInputForm() {
        $name = '' ;
        $secondname = '' ;
        $email = '' ;
        $html = '';

        //dialog new booking
	    $html .= sprintf('<div class="newMember">
    					  <input name="firstName" placeholder="Jméno člena" type="text" value="%1$s">
	    				  <input name="secondName" placeholder="Příjmení člena" type="text" value="%2$s">
                          <input name="email" placeholder="email" type="text" value="%3$s">
					      <button class="saveMember">Uložit</button>
					     </div>', $name, $secondname, $email); 

        return $html;
    }
    public function renderMember() {
    $html = '';
        $html .= sprintf('<div class="memberItem"> 
        <table> 
        <tr>
        <td> %1$s 
        </td>
        </tr> 
        </table> 
        </div> ', $name); 
    }
}
?>