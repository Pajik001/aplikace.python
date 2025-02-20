<?php
/*
Plugin Name: Evidence členů
Description: Zaevidování členů do aplikace a jejich uložení
*/
/* Start Adding Functions Below this Line */

//register creating database
register_activation_hook(__FILE__, 'kckevidence_create_db');
register_uninstall_hook(__FILE__, 'kck_delete_database_tables');

//create database 
# dodělat do databáze věk a váhu
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
        member_phone varchar(20) NOT NULL,
        birth_date date NOT NULL,
        weight int(3) NOT NULL,
        category_id mediumint(9),
        UNIQUE KEY id (id)
    ) $charset_collate;";

    $table2_name = $wpdb->prefix . 'kckevidence_categories';
    $sql_table_2 = "CREATE TABLE IF NOT EXISTS $table2_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        category_name varchar(50) NOT NULL,
        age int(3) NOT NULL,
        weight varchar(50) NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_table_1);
    dbDelta($sql_table_2);

    // Insert predefined categories
    $categories = [
        ['Chlapci 6-9', '6-9', '-40, +40kg'],
        ['Dívky 6-9', '6-9', '-33, +33 kg'],
        ['Chlapci 10-11', '10-11', '-35, -45, +45kg'],
        ['Dívky 10-11', '10-11', '-30, -40, +40 kg'],
        ['Chlapci 12-13', '12-13', '-40, -45, -50, -55, +55 kg'],
        ['Dívky 12-13', '12-13', '-42, -47, -52, +52 kg'],
        ['Dorostenci 14-15', '14-15', '-52, -57, -63, -70, +70 kg'],
        ['Dorostenky 14-15', '14-15', '-47, -54, -61, +61 kg'],
        ['Junioři 16-17', '16-17', '-55, -61, -68, -76, +76 kg'],
        ['Juniorky 16-17', '16-17', '-48, -53, -59, -66, +66 kg'],
        ['U21 chlapci 18-20', '18-20', '-60, -67, -75, -84, +84 kg'],
        ['U21 dívky 18-20', '18-20', '-50, -55, -61, -68, +68 kg']
    ];

    foreach ($categories as $category) {
        $wpdb->insert(
            $table2_name,
            array(
                'category_name' => $category[0],
                'age' => $category[1],
                'weight' => $category[2]
            )
        );
    }
}

//Plugin uninstall database
function kck_delete_database_tables() {
    global $wpdb;
    $tableArray = [
        $wpdb->prefix . 'kckevidence_members',
        $wpdb->prefix . 'kckevidence_categories'
    ];

    foreach ($tableArray as $tablename) {
        $wpdb->query("DROP TABLE IF EXISTS $tablename");
    }
}

// register jquery and style on initialization
add_action('init', 'kck_register_script');

function kck_register_script() {
    wp_register_script('kck_script', plugins_url('/js/fe.js', __FILE__), array('jquery'), '0.0.2', true);
    wp_localize_script('kck_script', 'ipAjaxVar', array('ajaxurl' => admin_url('admin-ajax.php')));
    wp_register_style('kck_style', plugins_url('/css/style.css', __FILE__), false, '0.0.2', 'all');
}

// use the registered jquery and style above
add_action('wp_enqueue_scripts', 'kck_enqueue_style');

function kck_enqueue_style() {
    wp_enqueue_script('kck_script');
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-resizable');
    wp_enqueue_style('kck_style');
}

//create new member
function create_member($name, $surname, $email, $phone, $birth_date, $weight) {
    global $wpdb;

    $table1_name = $wpdb->prefix . 'kckevidence_members';

    // Determine category based on age and weight
    $category_id = 1; #determine_category($birth_date, $weight);

    try {
    $wpdb->insert(
        $table1_name,
        array(
            'time' => current_time('mysql'),
            'member_name' => $name,
            'member_sur' => $surname,
            'member_email' => $email,
            'member_phone' => $phone,
            'birth_date' => $birth_date,
            'weight' => $weight,
            'category_id' => $category_id
        )
    );
    } catch (Exception $e) {
    }
    $memberid = $wpdb->insert_id;
    return $memberid;
}

function determine_category($birth_date, $weight) {
    global $wpdb;

    $age = date_diff(date_create($birth_date), date_create('today'))->y;
    $table2_name = $wpdb->prefix . 'kckevidence_categories';

    $categories = $wpdb->get_results("SELECT * FROM $table2_name WHERE age >= $age ORDER BY age ASC");

    foreach ($categories as $category) {
        $weights = explode(',', $category->weight);
        foreach ($weights as $w) {
            $w = trim($w);
            if ($w[0] == '-') {
                if ($weight <= intval(substr($w, 1))) {
                    return $category->id;
                }
            } elseif ($w[0] == '+') {
                if ($weight >= intval(substr($w, 1))) {
                    return $category->id; 
                }
            }
        }
    }

    return null;
}

function get_category_name($category_id) {
    global $wpdb;
    $table2_name = $wpdb->prefix . 'kckevidence_categories';
    $category = $wpdb->get_row($wpdb->prepare("SELECT category_name FROM $table2_name WHERE id = %d", $category_id));
    return $category ? $category->category_name : 'Unknown';
}

//create new member via AJAX
add_action('wp_ajax_nopriv_kck_create_member', 'kck_create_member');
add_action('wp_ajax_kck_create_member', 'kck_create_member');

function kck_create_member() {
    $fname = isset($_POST['firstName']) ? sanitize_text_field($_POST['firstName']) : '';
    $sname = isset($_POST['secondName']) ? sanitize_text_field($_POST['secondName']) : '';
    $mail = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $birth_date = isset($_POST['birthDate']) ? sanitize_text_field($_POST['birthDate']) : '';
    $weight = isset($_POST['weight']) ? intval($_POST['weight']) : 0;

    if (!empty($fname) && !empty($sname) && !empty($mail) && !empty($phone) && !empty($birth_date) && !empty($weight)) {
        $uId = create_member($fname, $sname, $mail, $phone, $birth_date, $weight);
        if ($uId) {
            wp_send_json_success(array('message' => 'Member created successfully', 'member_id' => $uId));
        } else {
            wp_send_json_error(array('message' => 'Failed to create member'));
        }
    } else {
        wp_send_json_error(array('message' => 'Missing required fields ' . $fname . ',' . $sname . ',' . $mail . ',' . $phone . ',' . $birth_date . ',' . $weight));
    }
    wp_die();
}

// Create categories for members
function create_category($category_name, $age, $weight) {
    global $wpdb;

    $table2_name = $wpdb->prefix . 'kckevidence_categories';

    $wpdb->insert(
        $table2_name,
        array(
            'category_name' => $category_name,
            'age' => $age,
            'weight' => $weight
        )
    );
    $categoryid = $wpdb->insert_id;

    return $categoryid;
}

// create new category via AJAX
add_action('wp_ajax_nopriv_kck_create_category', 'kck_create_category');
add_action('wp_ajax_kck_create_category', 'kck_create_category');

function kck_create_category() {
    $category_name = isset($_POST['category_name']) ? sanitize_text_field($_POST['category_name']) : '';
    $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
    $weight = isset($_POST['weight']) ? sanitize_text_field($_POST['weight']) : '';

    if (!empty($category_name) && !empty($age) && !empty($weight)) {
        $uId = create_category($category_name, $age, $weight);
        if ($uId) {
            wp_send_json_success(array('message' => 'Category created successfully', 'category_id' => $uId));
        } else {
            wp_send_json_error(array('message' => 'Failed to create category'));
        }
    } else {
        wp_send_json_error(array('message' => 'Missing required fields'));
    }
    wp_die();
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
            array('description' => __('Show evidence', 'kckevidence_widget_domain'),)
        );
    }

    // Creating widget front-end
    // This is where the action happens
    public function widget($args, $instance) {
        global $wpdb;

        $title = apply_filters('widget_title', $instance['title']);

        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];

        //create seats area
        echo __('<div id="kckevidence_evidence">', 'kckevidence_widget_domain');

        $new_member = new Member();

        $htmlContent = $new_member->renderInputForm();

        $table1_name = $wpdb->prefix . 'kckevidence_members';

        $members = $wpdb->get_results("SELECT * FROM $table1_name");

        $htmlContent .= '<h2>Seznam členů</h2>';

        //render members 
        foreach ($members as $mem) {
            $objMember = Member::withRow($mem);
            $htmlContent .= $objMember->renderMember();
        }

        echo __($htmlContent, 'kckevidence_widget_domain');

        //end of seats area
        echo __('</div>', 'kckevidence_widget_domain');

        echo $args['after_widget'];
    }

    // Widget Backend 
    public function form($instance) {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('New title', 'kckevidence_widget_domain');
        }

        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>"/>
        </p>
        <?php
    }

    // Updating widget replacing old instances with new
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }

} // Class ssa_widget ends here

// Register and load the widget
function kckevidence_load_widget() {
    register_widget('kckevidence_widget');
}

add_action('widgets_init', 'kckevidence_load_widget');

// podle entity modelu dodefinovat databázi, musí být vazby mezi skupinami, alespoň rozmyslet
// ---
// informace o členovi, 
class Member {
    protected $_id;
    protected $_first_name;
    protected $_second_name;
    protected $_email;
    protected $_phone;
    protected $_birth_date;
    protected $_weight;
    protected $_category_id;

    public function __construct() {
        $this->_id = '';
        $this->_first_name = '';
        $this->_second_name = '';
        $this->_email = '';
        $this->_phone = '';
        $this->_birth_date = '';
        $this->_weight = '';
        $this->_category_id = '';
    }

    public static function withRow($row) {
        $instance = new self();
        $instance->setFirstName($row->member_name);
        $instance->setSecondName($row->member_sur);
        $instance->setEmail($row->member_email);
        $instance->setId($row->id);
        $instance->setPhone($row->member_phone);
        $instance->setBirthDate(isset($row->birth_date) ? $row->birth_date : null);
        $instance->setWeight(isset($row->weight) ? $row->weight : null);
        $instance->setCategoryId(isset($row->category_id) ? $row->category_id : null);

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
        $this->_first_name = $name;
    }

    public function getSecondName() {
        return $this->_second_name;
    }

    public function setSecondName($secondname) {
        $this->_second_name = $secondname;
    }

    public function getEmail() {
        return $this->_email;
    }

    public function setEmail($email) {
        $this->_email = $email;
    }

    public function getPhone() {
        return $this->_phone;
    }

    public function setPhone($phone) {
        $this->_phone = $phone;
    }

    public function getBirthDate() {
        return $this->_birth_date;
    }

    public function setBirthDate($birth_date) {
        $this->_birth_date = $birth_date;
    }

    public function getWeight() {
        return $this->_weight;
    }

    public function setWeight($weight) {
        $this->_weight = $weight;
    }

    public function getCategoryId() {
        return $this->_category_id;
    }

    public function setCategoryId($category_id) {
        $this->_category_id = $category_id;
    }

    public function renderInputForm() {
        $name = '';
        $secondname = '';
        $email = '';
        $phone = '';
        $birth_date = '';
        $weight = '';
        $id = '';
        $html = '';

        //dialog new booking
        $html .= sprintf('<div class="newMember">
                          <input name="firstName" placeholder="Jméno člena" type="text" value="%1$s">
                          <input name="secondName" placeholder="Příjmení člena" type="text" value="%2$s">
                          <input name="email" placeholder="email" type="text" value="%3$s">
                          <input name="phone" placeholder="telefon" type="text" value="%4$s">
                          <input name="birthDate" placeholder="Datum narození" type="date" value="%5$s">
                          <input name="weight" placeholder="Váha" type="number" value="%6$s">
                          <button class="saveMember">Uložit</button>
                         </div>', $name, $secondname, $email, $phone, $birth_date, $weight);

        return $html;
    }

    public function renderMember() {
        $category_name = get_category_name($this->_category_id);
        $html = '';
        $html .= sprintf('<div class="memberItem"> 
        <table> 
        <tr>
        <td> Jméno: %1$s </td>
        <td> Příjmení: %2$s </td>
        <td> Email: %3$s </td>
        <td> Telefon: %4$s </td>
        <td> Datum narození: %5$s </td>
        <td> Váha: %6$s kg </td>
        <td> Kategorie: %7$s </td>
        </tr> 
        </table> 
        </div> ', $this->_first_name, $this->_second_name, $this->_email, $this->_phone, $this->_birth_date, $this->_weight, $category_name);

        return $html;
    }
}