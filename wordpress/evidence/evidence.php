<?php
/*
Plugin Name: Evidence člemů
Description: Zaevidování členů do aplikace a jejich uložení
*/
/* Start Adding Functions Below this Line */


//register creating database
register_activation_hook( __FILE__, 'kckevidence_create_db' );

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
		UNIQUE KEY id (id)
	) $charset_collate;";


    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_table_1 );
}

//create new member
function create_member($name, $surname) {
	global $wpdb;

	$table1_name = $wpdb->prefix . 'kckevidence_members';

	$wpdb->insert( 
		$table1_name, 
		array( 
			'time' => current_time( 'mysql' ), 
			'member_name' => $name,
			'member_sur' => $surname, 
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
    
    $title = apply_filters( 'widget_title', $instance['title'] );
    
    // before and after widget arguments are defined by themes
    echo $args['before_widget'];
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];
    
    //create seats area
    echo __( sprintf('<div id="kckevidence_evidence">'), 'kckevidence_widget_domain' ); 
    
    $new_member = new Member() ; 

    $htmlContent = $new_member.renderInputForm() ;
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

	public function __construct($usr) {
		$this->_id = $usr->id;
		$this->_first_name = $usr->first_name;
		$this->_second_name = $usr->second_name;
		$this->_email = $usr->email;
		$this->_phone = $usr->phone;
	}

	public function __construct() {
		$this->_id = '';
		$this->_first_name = '';
		$this->_second_name ='';
		$this->_email = '';
		$this->_phone = '';
	}


	public function getId() {
		return $this->_id;
	}

	public function getFirstName() {
		return $this->_first_name;
	}

	public function getSecondName() {
		return $this->_second_name;
	}

	public function getEmail() {
		return $this->_email;
	}

	public function getPhone() {
		return $this->_phone;
	}

	function renderInputForm(){
        $name = '' ;
        $secondname = '' ;
        
	//dialog new booking
	$html .= sprintf('<div class="newbooking">
					  <input name="firstName" placeholder="Jméno žáka/ů" type="text" value="%1$s">
					  <input name="secondName" placeholder="Příjmení žáka/ů" type="text" value="%2$s">
					  </div>', $name, $secondname); 

        return $html
    }

}
?>