<?php
/*
Plugin Name: JxwMembers
Plugin URI:  http://www.jaxweb.dk
Description: View a full list of members, with statistics, csv lists
Version:     0.6
Author:      jaxweb.dk
Author URI:  http://www.jaxweb.dk
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: jxwmembers_txtdomain
Domain Path: /languages
*/

/*
View-user is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
View-user is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with View-user. If not, see {URI to Plugin License}.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class jw_view_users {
  public function __construct() {
    // Register hooks
    register_activation_hook( __FILE__, array ($this, 'activation') );
    register_deactivation_hook( __FILE__, array($this, 'deactivation') );
    
    //Register scripts
    add_action ('admin_enqueue_scripts', array ($this, 'registerscripts_for_view_users'));
    add_action ('wp_enqueue_scripts', array ($this, 'registerscripts_for_view_users'));
                    
    //WooCommerce dashboard endpoint additions
      add_filter ( 'woocommerce_account_menu_items', array($this, 'misha_log_history_link'), 40 );
      add_action( 'init', array($this, 'misha_add_endpoint') );
      add_action( 'woocommerce_account_view-userlist_endpoint', array($this, 'misha_my_account_endpoint_content') );
    
    // add shortcode
    add_shortcode ( 'view_userlist', array($this,'jw_show_usertable') );
    
    //Add ajax to create manual member
    add_action( 'wp_ajax_create_manual_member', array( $this, 'create_manual_member' ) );
    
    //Create CSV download
    add_action( 'admin_post_createcsv_list', array( $this, 'createcsv_list_func' ) );
    
    //Add ajax to retreive data from individual users
    add_action( 'wp_ajax_edituser_ajax_request', array( $this, 'edituser_ajax_request' ) );
    add_action( 'wp_ajax_saveuser_ajax_request', array( $this, 'saveuser_ajax_request' ) );

    //$this->jw_show_members();
  }

  /**
   * Add grid lines
   * 
   */

  public function add_member_grid($all_members) { ?>
    <div class="admin-search-allmembers">
        <input type="text" id="myInput" placeholder="Søg efter medlem" style="background-image: url('<?php echo plugins_url( 'css/searchicon.png' , __FILE__ ); ?>'); '">
    </div>
    
    <div class="allmembers-edit-list-grid">
        <table>
            <thead class="allmembers-edit-list-headline">
                <tr>
                    <th></th>
                    <th>Medlem</th>
                    <th>Email</th>
                    <th>Forening/Erhverv</th>
                    <th>Medlemskab</th>
                    <th>Medlem siden</th>
                    <th width="130px">Betaling</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="allmembers-edit-list-body">
                    <?php $this->add_grid_lines($all_members); ?>
            </tbody>
        </table>
    </div>
    <?php
  }

  public function add_grid_lines($all_members) {
    // 
    $medlem_prod_titler = array (
      '9503'  => 'auto privat',
      '10968'  => 'manuel privat',
      '28736'  => 'auto pensionist',
      '28735'  => 'manual pensionist',
      '30734'  => 'auto forening',
      '19221'  => 'manuel forening',
    );

    foreach ( $all_members as $member ) {

        //$memberinfo = get_subinfo_line($member->ID);
        echo '<tr data-row-order-id="'.$member['id'].'" class="table-row">';
        echo '<td><button class="wp-block-button__link edit-member-admin" data-id="'.$member['id'].'">Rediger</button></td>';
        echo '<td class="medleminfo"><strong>ID: '.$member['userid'].'</strong><br/>'.$member['firstname'].' '.$member['lastname'].'<br/>'.$member['adresse'].'<br/>'.$member['postnr'].' '.$member['by'].'<br/>Tlf: '.$member['phone'].'</td>';
        echo '<td class="mailadresse"><a href="mailto:'.$member['email'].'">'.$member['email'].'</a></td>';
        echo '<td class="erhverv-forening">'.$member['company'].'</td>';
        echo '<td>'.$member['antal'].' x '.$medlem_prod_titler[$member['produkt-id']].'</td>';
        echo '<td>'.$member['date'].'</td>';
        echo '<td><span class="payment-method '.(str_replace(' ', '-', strtolower($member['betaling']))).'">'.$member['betaling'].'</span><br/><span class="qp-id">QuickPay-Id:</span><br/><span class="qp-id number">'.$member['betalingid'].'</span></td>';
        echo '<td>'.$member['opsig'].'</td>';
        echo '<td>'.$member['skift'].'</td>';
        echo '</tr>';
    }
  }

  public function jw_show_members() {

    list ( 
      $all_members, 
      $count_privatememberships, 
      $count_unionmemberships, 
      $count_pension_manual_memberships, 
      $count_pension_auto_memberships, 
      $count_private_manual_memberships, 
      $count_private_auto_memberships, 
      $count_unionmemberships_in_dianalund,
      $count_unionmemberships_outside_dianalund ) = $this->find_all_members();  //'2023-01-01', '2023-12-31'

    $this->create_all_csv_lists( $all_members );
	  
	  $f = date( 'd-m-Y', strtotime( $this->get_from_date() ) );
	  $t = date( 'd-m-Y', strtotime( $this->get_to_date() ) );
   
	  // Sidste års liste
	  // $f = '01-01-2023';
	  // $t = '31-12-2023';
	  
    echo '<div class="medlemsadministration">';
    echo '<div class="topgrid">';
	  echo '<div class="lefttopgrid">';
	      echo '<h1>Medlemsadministration</h1>';
	  	  echo '<p class="periode">Optalt for perioden: '.$f.' til '.$t.'</p>';
	  echo '</div>';
	  echo '<div class="righttopgrid">';
	      echo '<button id="opretmedlem" class="wp-block-button__link">Opret medlem uden kreditkort</button>';
	  echo '</div>';	  
    echo '</div>';

    $this->show_graphs(
      $count_privatememberships,
      $count_unionmemberships, 
      $count_pension_manual_memberships, 
      $count_pension_auto_memberships, 
      $count_private_manual_memberships, 
      $count_private_auto_memberships, 
      $count_unionmemberships_in_dianalund,
      $count_unionmemberships_outside_dianalund,      
    );

    $this->show_csvlists();

    $this->add_member_grid($all_members);

    echo '<div class="admin-return-button"><a href="'.get_permalink( get_option('woocommerce_myaccount_page_id') ).'" class="wp-block-button__link">Tibage til din profil</a></div>';

    echo '<div class="background-edit-user-modal"><div class="show-edit-user-modal"></div></div>';


    echo '</div>';
    
    $this->jw_manual_create_member_form();
  }
  
  public function show_graphs(      
    $count_privatememberships,
    $count_unionmemberships, 
    $count_pension_manual_memberships, 
    $count_pension_auto_memberships, 
    $count_private_manual_memberships, 
    $count_private_auto_memberships, 
    $count_unionmemberships_in_dianalund,
    $count_unionmemberships_outside_dianalund
    ) { ?>
    <div class="graph-grid">
        <div class="graph-grid-row numbers">
            <div class="graph-data-box private">
                <div class="graph-headline">
                    <h3>Private:</h3>
                    <h3><?php echo $count_privatememberships; ?></h3>
                </div>
                <div class="graph-subheadline">
                    <h4>Automatisk fornyede</h4>
                    <h4><?php echo $count_pension_auto_memberships+$count_private_auto_memberships; ?></h4>
                </div>
                <div class="graph-subdata">
                    <div>Pensionister</div>
                    <div><?php echo $count_pension_auto_memberships; ?></div>
                </div>
                <div class="graph-subdata">
                    <div>Ikke pensionister</div>
                    <div><?php echo $count_private_auto_memberships; ?></div>
                </div>
                <div class="graph-subheadline">
                    <h4>Manuelt fornyede</h4>
                    <h4><?php echo $count_private_manual_memberships+$count_pension_manual_memberships; ?></h4>
                </div>
                <div class="graph-subdata">
                    <div>Pensionister</div>
                    <div><?php echo $count_pension_manual_memberships; ?></div>
                </div>
                <div class="graph-subdata">
                    <div>Ikke pensionister</div>
                    <div><?php echo $count_private_manual_memberships; ?></div>
                </div>
                <div class="graph-headline">
                    <h3>Foreninger:</h3>
                    <h3><?php echo $count_unionmemberships; ?></h3>
                </div>
                <div class="graph-subdata">
                    <div>Fra 4293</div>
                    <div><?php echo $count_unionmemberships_in_dianalund; ?></div>
                </div>
                <div class="graph-subdata">
                    <div>Udenfor 4293</div>
                    <div><?php echo $count_unionmemberships_outside_dianalund; ?></div>
                </div>
            </div>
            <div class="graph-visual-box alle">
                <?php echo do_shortcode ('[visualizer id="29919" lazy="no" class=""]'); ?>
            </div>
        </div>
      </div>
    <?php
  }

  public function show_csvlists() { ?>
    <div class="member-lists-grid">
      <h5>Medlemslister</h5>
      <div class="memberlist-grid-row">
          <div class="memberlist-button alle">
              <a href="<?php echo plugins_url( 'medlemsliste.csv', __FILE__ ); ?>"><button class="wp-block-button__link">CSV over alle medlemmer</button></a>
          </div>
      </div>
    </div>
    <?php
  }

  public function activation() {
    self::add_cap();
  }
    
  // Add the new capability to all roles having a certain built-in capability
  private static function add_cap() {

    global $wp_roles;  // Delcaring roles and collecting the author role capability here.
    if ( ! isset( $wp_roles ) ) {
        $wp_roles = new WP_Roles();
    }
    
    $author = $wp_roles->get_role('subscriber');
    //Adding a boardmember role with subscriber capabilities so they cant do much harm to the system
    //Then add a viewuserlist capability to the boardmember role
    if ( !get_role('boardmember')) {
      $wp_roles->add_role('boardmember', 'Bestyrelsesmedlem', $author->capabilities);
      $wp_roles->add_cap('boardmember', 'viewuserlist');
      $wp_roles->add_cap('administrator', 'viewuserlist');
    }
    
  }

  public function deactivation() {
      self::remove_cap();
  }

  // Remove the plugin-specific custom capability
  private static function remove_cap() {
    global $wp_roles;
    $wp_roles->remove_role('boardmember');
    $wp_roles->remove_cap('administrator', 'viewuserlist');
  }               
                
  public function registerscripts_for_view_users() {
    wp_enqueue_script( 'register_view_users_js', plugins_url( '/js/view-users.js', __FILE__ ), 'jquery' );
    wp_enqueue_style( 'register_view_uses_css', plugins_url( '/css/view-users.css', __FILE__ ) );
    wp_localize_script( 'register_view_users_js', 'example_ajax_obj', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
  }
  
  /**
   * Create member creation form
   */
  
  public function jw_manual_create_member_form() { ?>
      <div id="create-manual-member" class="background-edit-user-modal" style="display: none">
        <div class="create-manual-user-form">
            <h2>Opret medlem</h2>
            <p class="result"></p>
            <div class="create-manual-member-selector-container">
				      <label for="privatmedlem"><input type="radio" id="privatmedlem" name="medlemsprodukt" value="10968" checked>Privat<span class="create-manual-member-price">75,-</span></label>
      				<label for="pensionistmedlem"><input type="radio" id="pensionistmedlem" name="medlemsprodukt" value="28735">Pensionist<span class="create-manual-member-price">50,-</span></label>
      				<label for="foreningmedlem"><input type="radio" id="foreningmedlem" name="medlemsprodukt" value="19221">Forening<span class="create-manual-member-price">150,-</span></label>
      				<label for="erhvervmedlem"><input type="radio" id="erhvervmedlem" name="medlemsprodukt" value="29916">Erhverv<span class="create-manual-member-price">1.000,-</span></label>
		        </div>
            <input type='hidden' name='action' value='create_manual_member'>
            <input type='hidden' name="member" id="membernonce" value="<?php echo wp_create_nonce('create-member-nonce'); ?>">
            <div class="create-manual-member-text-felter-container">
              <label for="createfornavn">Fornavn:</label>
              <input type="text" id="createfornavn" name="fornavn" placeholder="Indtast fornavn" required/>
              <label for="createefternavn">Efternavn:</label>
              <input type="text" id="createefternavn" name="efternavn" placeholder="Indtast efternavn" required/>
              <label for="createadresse">Adresse:</label>
              <input type="text" id="createadresse" name="adresse"  placeholder="Indtast adresse" required/>            
              <label for="createpostnr">Postnr.:</label>
              <input type="text" id="createpostnr" name="postnr" placeholder="Indtast postnr." required/>
              <label for="createby">By:</label>
              <input type="text" id="createby" name="By" placeholder="Indtast by" required/>
              <label for="createforening">Forening:</label>
              <input type="text" id="createforening" name="forening"  placeholder="Indtast forening"/>
              <label for="createerhverv">Erhverv:</label>
              <input type="text" id="createerhverv" name="erhverv"  placeholder="Indtast Erhverv"/> 
              <label for="createtlf">Tlf:</label>
              <input type="text" id="createtlf" name="Tlf" placeholder="Indtast telefonnummer" required/>
              <div class="create-buttons-collection">
                <button id="cancel-manual-member-button" class="ddk-btn red">Fortryd</button>
                <button id="create-manual-member-button" class="ddk-btn red">Opret medlemskab</button>
              </div>
            </div>
            <button id="close-button" class="ddk-btn red">Luk</button>                    
        </div>
      </div>
    <?php
  }
  
  /**
   * Actually creating the member without creating user (No mailadress)
   */
  
  public function create_manual_member() {    
    $create_member_nonce = $_REQUEST['nonce'];
    if ( ! wp_verify_nonce($create_member_nonce, 'create-member-nonce') ) {
      echo 'Nonce missing: ' . $create_member_nonce;
    } else {
      // Create member
      global $woocommerce;

      if ($_REQUEST['forening'] != '') {
        $company = $_REQUEST['forening'];
      }
      if ($_REQUEST['erhverv'] != '') {
        $company = $_REQUEST['erhverv'];
      }

      $address = array(
          'first_name' => $_REQUEST['fornavn'],
          'last_name'  => $_REQUEST['efternavn'],
          'company'    => $company,
          'phone'      => $_REQUEST['tlfnr'],
          'address_1'  => $_REQUEST['addresse1'],
          'city'       => $_REQUEST['bynavn'],
          'postcode'   => $_REQUEST['postnr'],
          'country'    => 'DK',
      );
      
      // Now we create the order
      $order = wc_create_order();

      $product_id = $_REQUEST['type'];

      $order->add_product( wc_get_product($product_id), 1); 

      $order->set_address( $address, 'billing' );
      //
      global $current_user;
      wp_get_current_user();

      $email = (string) $current_user->user_email;
      
      $order->calculate_totals();
      $order->update_status("completed", 'Manuelt oprettet medlemskab af mailadresse: '.$email, TRUE);
      
      $order_id = $order->get_id(); 
      
      // Create fictious mailadress to create user
      $username = strtolower($address['first_name']).'_'.$order_id;
      $mailadress = $order_id.'@dianalund.dk';
      $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );

      $user = wc_create_new_customer( $mailadress, $username, $random_password );
      update_post_meta( $order_id, '_customer_user', $user);
      update_user_meta( $user, "first_name", $address['first_name'] );
      update_user_meta( $user, "last_name", $address['last_name'] );
      update_user_meta( $user, "company", $address['company'] );
      update_user_meta( $user, "billing_first_name", $address['first_name'] );
      update_user_meta( $user, "billing_last_name", $address['last_name'] );
      update_user_meta( $user, "billing_address_1", $address['address_1'] );
      update_user_meta( $user, "billing_postcode", $address['postcode'] );
      update_user_meta( $user, "billing_city", $address['city'] );
      update_user_meta( $user, "billing_phone", $address['phone'] );
      update_user_meta( $user, "billing_country", $address['country'] );
      
      $txt = "Ordre nr.: " . $order_id . " er manuelt oprettet<br/>Medlemsnr.: <strong>" . $user . "</strong><br/>" . $address['first_name'] . " " . $address['last_name'] . "<br/>" . $address['address_1'] . "<br/>" . $address['postcode'] . " " . $address['city'] . "<br/>Telefon: " . $address['phone'] . "<br/>Firma/forening: " . $address['company']; 
      echo $txt;

    }    
    // Allways die in ajax
    die();
  }
  
  public function find_all_members($from_date = null, $to_date = null, $member_type = null) {

    $count_member = 0;
    
    $count_unionmemberships = 0;

    $count_unionmemberships_in_dianalund = 0;
    $count_unionmemberships_outside_dianalund = 0;

    $count_privatememberships = 0;
    $count_private_auto_memberships = 0;
    $count_private_manual_memberships = 0;
    $count_pension_auto_memberships = 0;
    $count_pension_manual_memberships = 0;
    
    if (!$from_date) { $from_date = $this->get_from_date(); }    
    if (!$to_date) { $to_date = $this->get_to_date(); }
    $member_type = ($member_type == null) ? '' : $member_type;
    
    $orders = wc_get_orders( array(
      'date_paid'       => $from_date.'...'.$to_date,
      'posts_per_page'  => -1,
      'status'          => 'completed',
    ) );
    
    $all_members = array();

    if ($member_type == 'stat') {

      $orders = wc_get_orders( array(
        'date_paid'       => $from_date.'...'.$to_date,
        'posts_per_page'  => -1,
        'status'          => 'completed',
        'orderby'         => 'date',
      ) );

      $count_member = 0;
      $accu_tal = 0;
      foreach ($orders as $order) {
        foreach ($order->get_items() as $item_id => $item_data) {
          $accu_tal += $item_data->get_quantity();
        }
        $all_members[$count_member]['dato'] = $order->get_date_completed();
        $all_members[$count_member]['accuantal'] = $accu_tal;
        $count_member++;
      }
    } else {
      foreach ($orders as $order) {
          $udata = get_userdata($order->get_user_id());
          $date = $udata->user_registered;
		  $datobetalt = $order->get_date_completed();
          $user_id = $order->get_user_id();
          $order_id = $order->get_id();
          $all_members[$count_member]['id'] = $order_id;
          $all_members[$count_member]['dato'] = $datobetalt;	
          $all_members[$count_member]['userid'] = $user_id;
          $all_members[$count_member]['date'] = date('d-m-Y', strtotime($date));
          $all_members[$count_member]['name'] = ucfirst($order->get_billing_first_name()) . " " . ucfirst($order->get_billing_last_name());
          $all_members[$count_member]['firstname'] = ucfirst($order->get_billing_first_name());
          $all_members[$count_member]['lastname'] = ucfirst($order->get_billing_last_name());
          $all_members[$count_member]['adresse'] = ucfirst($order->get_billing_address_1());
          $all_members[$count_member]['postnr'] = $order->get_billing_postcode();
          $all_members[$count_member]['by'] = $order->get_billing_city();
          $all_members[$count_member]['company'] = ucfirst($order->get_billing_company());
          $all_members[$count_member]['email'] = ucfirst($order->get_billing_email()); 
          $all_members[$count_member]['phone'] = ucfirst($order->get_billing_phone()); 
          $number_of_subscriptions['antal'] = 0;
          
          foreach ($order->get_items() as $item_id => $item_data) {
              $number_of_subscriptions['antal'] = $item_data->get_quantity();
              $number_of_subscriptions['id'] = $item_data->get_product_id();
          }
          $all_members[$count_member]['antal'] = $number_of_subscriptions['antal'];
          $all_members[$count_member]['produkt-id'] = $number_of_subscriptions['id'];
          $all_members[$count_member]['number'] = $number_of_subscriptions['antal'];

          $pmt = $order->get_payment_method_title();
          /* $all_members[$count_member]['betaling'] = ($pmt == "MobilePay Subscriptions") ? 'MobilePay' : 'Kortbetaling'; */
		  
		  $all_members[$count_member]['betaling'] = $pmt;
          $all_members[$count_member]['betalingid'] = $order->get_transaction_id();
        
          $usersubs = wcs_get_users_subscriptions($user_id);
          $subactions = array();
          $subactions_counter = 0;

          if ($usersubs) {
            foreach ($usersubs as $key => $usersub) {
              $actions = wcs_get_all_user_actions_for_subscription($usersub, $user_id);
      
              foreach ($actions as $key => $action) {

                  $name = ($action['name'] == 'Change payment') ? 'Skift metode' : $action['name'];

                  $subactions[$subactions_counter] = '<a href="'.$action['url'].'" class="wp-block-button__link">'.$name.'</a>';
                  $subactions_counter++;

              }
            }
          }

          $all_members[$count_member]['opsig'] = $subactions[0];
          $all_members[$count_member]['skift'] = $subactions[1];

          $pid = $all_members[$count_member]['produkt-id'];

          if ($pid != '19221' && $pid != '30734') {
            $count_privatememberships += $number_of_subscriptions['antal'];
            if ($pid == '28735') { $count_pension_manual_memberships += $number_of_subscriptions['antal']; } 
            if ($pid == '28736') { $count_pension_auto_memberships += $number_of_subscriptions['antal']; }
            if ($pid == '10968') { $count_private_manual_memberships += $number_of_subscriptions['antal']; }
            if ($pid == '9503') { $count_private_auto_memberships += $number_of_subscriptions['antal']; }
          } else {
            $count_unionmemberships += $number_of_subscriptions['antal'];
            if ($order->get_billing_postcode() == '4293') { 
              $count_unionmemberships_in_dianalund += $number_of_subscriptions['antal']; 
            } else {
              $count_unionmemberships_outside_dianalund += $number_of_subscriptions['antal'];
            }
          }
          
          $count_member++;
      }
      usort($all_members, function($a, $b) {
        return $a['dato'] <=> $b['dato'];
      });
    }
    
    return array( 
      $all_members, 
      $count_privatememberships, 
      $count_unionmemberships, 
      $count_pension_manual_memberships, 
      $count_pension_auto_memberships, 
      $count_private_manual_memberships, 
      $count_private_auto_memberships, 
      $count_unionmemberships_in_dianalund,
      $count_unionmemberships_outside_dianalund
    );
  }
  
  private function get_from_date() {
	return date( 'Y-m-d', strtotime( '-364 days' ) );
  }

  private function get_to_date() {
    return date('Y-m-d');
  }
  
  /**
   * Ajax edit user
   */
  public function edituser_ajax_request() {
    
    $untilYear = date('Y');
    
    // The $_REQUEST contains all the data sent via ajax
    if ( isset($_REQUEST) ) {
     
        $order_id = intval( $_REQUEST['order_id'] );
        if ( !$order_id ) {
          return;
        } else {
          $order = wc_get_order( $order_id );
          foreach ($order->get_items() as $item_id => $item_data) {
            $product_id = $item_data->get_product_id();
          }
          if ($product_id == "19221") {
            $company_union_name = "Foreningsnavn:";
          } else {
            $company_union_name = "Firmanavn:";
          }
        }
      
      
        $edit_user = '
        <div class="update-user">
          <!-- <form id="update-user-form"> -->
            <input type="hidden" name="update-order-id" id="update-order-id" value="'.$order->get_id().'">
            <input type="hidden" name="update-nonce" id="update-nonce" value="'.wp_create_nonce('update-member-nonce').'">
            <p>Medlemskabetsløbetid: '.date('d-m-Y', strtotime($order->order_date)).' - '.date('d-m-Y', strtotime('+1 year', strtotime($order->order_date))).'
            <table>
              <tr>
                <td><label for="updatefornavn">Fornavn:<br/><input type="text" value="'.$order->get_billing_first_name().'" id="updatefornavn"></label></td>
                <td><label for="updateefternavn">Efternavn:<br/><input type="text" value="'.$order->get_billing_last_name().'" id="updateefternavn"></label></td>
              </tr>
              <tr>
                <td colspan="2"><label id="labelfirmanavn" for="updatefirmanavn">'.$company_union_name.'<br/><input type="text" value="'.$order->get_billing_company().'" id="updatefirmanavn"></label></td>
              </tr>
              <tr>
                <td><label for="updateadresse1">Adresse 1:<br/><input type="text" value="'.$order->get_billing_address_1().'" id="updateadresse1"></label></td>
                <td><label for="updateadresse2">Adresse 2:<br/><input type="text" value="'.$order->get_billing_address_2().'" id="updateadresse2"></label></td>
              </tr>
              <tr>
                <td><label for="updatepostnr">Postnr:<br/><input type="text" value="'.$order->get_billing_postcode().'" id="updatepostnr"></label></td>
                <td><label for="updatebynavn">By:<br/><input type="text" value="'.$order->get_billing_city().'" id="updatebynavn"></label></td>
              </tr>
              <tr>
                <td><label for="updateemail">Email:<br/><input type="text" value="'.$order->get_billing_email().'" id="updateemail"></label></td>
                <td><label for="updatetlfnr">Telefon:<br/><input type="text" value="'.$order->get_billing_phone().'" id="updatetlfnr"></label></td>
              </tr>
              <tr>
                <td colspan="2"><button id="opdatermedlemknap" class="ddk-btn red">Opdater medlem</button></td>
              </tr>
            </table>
          <!-- </form> -->
        </div>
        
        <script>
        jQuery(document).ready(function($) {        
          $("#opdatermedlemknap").on("click", function() {
            var update_order_id = $("#update-order-id").val();
      
            var denneknap = $("#opdatermedlemknap");
      
            var opdaternavnet = $(".table-row[data-row-order-id=\'" + update_order_id + "\']");
      
            var updatenonce = $("#update-nonce").val();
            var fornavn = $("#updatefornavn").val();
            var efternavn = $("#updateefternavn").val();
            var firmanavn = $("#updatefirmanavn").val();
            var addresse1 = $("#updateadresse1").val();
            var addresse2 = $("#updateadresse2").val();
            var postnr = $("#updatepostnr").val();
            var bynavn = $("#updatebynavn").val();
            var email = $("#updateemail").val();
            var tlfnr = $("#updatetlfnr").val();
      
            $.ajax({
              beforeSend: function() {
                denneknap.html("Opdaterer - Vent").addClass("wait"); 
              },
              url: example_ajax_obj.ajaxurl,
              data: {
                "action"            : "saveuser_ajax_request",
                "update-order-id"   : update_order_id,
                "update-nonce"      : updatenonce,
                "fornavn"           : fornavn,
                "efternavn"         : efternavn,
                "firmanavn"         : firmanavn,
                "addresse-one"      : addresse1,
                "addresse-two"      : addresse2,
                "postnr"            : postnr,
                "bynavn"            : bynavn,
                "email"             : email,
                "tlfnr"             : tlfnr,
              },
              error: function (errorThrown) {
                console.log(errorThrown);
              }
            })
            .done(function(data) {
              opdaternavnet.find(".medleminfo").html("<strong>ID: "+update_order_id+"</strong><br/>" + fornavn + " " + efternavn + "<br/>" + addresse1 + "<br/>" + postnr + " " + bynavn);
              opdaternavnet.find(".mailadresse").html("<a href=\"mailto:" + email + "\">" + email + "</a>");
              opdaternavnet.find(".erhverv-forening").html(firmanavn);
              denneknap.html("Opdateret").removeClass("wait").delay(100).hide(50);
              $(".background-edit-user-modal").hide(100);
              $(".show-edit-user-modal").html("");
            });
          });
        });
        </script>     
        ';
      
        // Output for response
        echo $edit_user;
     
    }
     
    // Always die in functions echoing ajax content
    die();
    
  }
  
  
  /**
   * Ajax save user
   */
  public function saveuser_ajax_request() {
    $update_nonce = $_REQUEST['update-nonce'];
    if ( ! wp_verify_nonce($update_nonce, 'update-member-nonce') ) {
      echo 'Nonce missing: ' . $update_nonce;
    } else {
      
      $order_id = $_REQUEST['update-order-id'];  
      // Create member
      global $woocommerce;

      $address = array(
          'first_name'  => sanitize_text_field( $_REQUEST['fornavn'] ),
          'last_name'   => sanitize_text_field( $_REQUEST['efternavn'] ),
          'company'     => sanitize_text_field( $_REQUEST['firmanavn']),
          'address_1'   => sanitize_text_field( $_REQUEST['addresse-one'] ),
          'address_2'   => sanitize_text_field( $_REQUEST['addresse-two'] ),
          'postcode'    => sanitize_text_field( $_REQUEST['postnr'] ),
          'city'        => sanitize_text_field( $_REQUEST['bynavn'] ),
          'email'       => sanitize_text_field( $_REQUEST['email'] ),
          'phone'       => sanitize_text_field( $_REQUEST['tlfnr'] ),
      );
      
      // Now we get the order for update info
      $order = wc_get_order( $order_id );

      // Updates the billing address for the order id (Uses update_post_meta() )
      $order->set_address( $address, 'billing' );

      //Updates address info for user 
      $user = $order->get_user_id();
      update_user_meta( $user, "first_name", $address['first_name'] );
      update_user_meta( $user, "last_name", $address['last_name'] );
      update_user_meta( $user, "billing_first_name", $address['first_name'] );
      update_user_meta( $user, "billing_last_name", $address['last_name'] );
      update_user_meta( $user, "billing_company", $address['company'] );
      update_user_meta( $user, "billing_address_1", $address['address_1'] );
      update_user_meta( $user, "billing_address_1", $address['address_2'] );
      update_user_meta( $user, "billing_postcode", $address['postcode'] );
      update_user_meta( $user, "billing_city", $address['city'] );
      update_user_meta( $user, "billing_phone", $address['phone'] );
      update_user_meta( $user, "billing_email", $address['email'] );
      
      //self::create_csv_file();
      
    }    
    
    // Always die in functions echoing ajax content
    die();

  }
  
  /**
   * Create CSV File for download
   */
  public function create_all_csv_lists( $all_members ) {

    $this->create_csv_file($all_members, 'medlemsliste.csv');

    $this->create_csv_file($all_members, 'graf.csv', 'stat');
   
  }

  public function create_csv_file ($all_members, $filename, $type = '', $to_date = null, $from_date = null) {
    
    $filename = plugin_dir_path( __FILE__ ) . $filename;
    $delimiter = ";";

    $output = fopen ( $filename, 'w' );
    

    //output data as lines with all data of each member
    if ($type == 'stat') {

      usort($all_members, function($a, $b) {
        return $a['dato'] <=> $b['dato'];
      });
      
      $accu_members = 0;
      $line = array();

      fputcsv ( $output, array( 'Dato', 'Antal' ), $delimiter );
      fputcsv ( $output, array( 'string', 'number' ), $delimiter );

      foreach ( $all_members as $member ) {

        if ( date('Y', strtotime( $member['dato'] )) == date('Y') ) {
          $line['dato'] = date('d-m-Y', strtotime($member['dato']));
          $line['antal'] = $accu_members += $member['antal'];

          fputcsv ( $output, $line, $delimiter );
        }

      }

    } else {
		
		$medlem_prod_titler = array (
		  '9503'  => 'auto privat',
		  '10968'  => 'manuel privat',
		  '28736'  => 'auto pensionist',
		  '28735'  => 'manual pensionist',
		  '30734'  => 'auto forening',
		  '19221'  => 'manuel forening',
		);		

      // output the column headings
      fputcsv ( $output, array( 'Fornavn', 'Efternavn', 'Firma / Forening', 'Email', 'Adresse', 'Postnr', 'By', 'Telefon', 'Betalt dato', 'Medlemstype', 'Antal', 'Metode' ), $delimiter );

      foreach ( $all_members as $line ) {
		  $linie = array(
			  'firstname' 	=> $line['firstname'],
			  'lastname'	=> $line['lastname'],
			  'company'		=> $line['company'],
			  'email'		=> $line['email'],
			  'adresse'		=> $line['adresse'],
			  'postnr'		=> $line['postnr'],
			  'by'			=> $line['by'],
			  'phone'		=> $line['phone'],
			  'date'		=> date('d-m-Y', strtotime($line['dato'])),
			  'type'		=> $medlem_prod_titler[$line['produkt-id']],
			  'amount'		=> $line['antal'],
			  'method'		=> $line['betaling'],
		  );			  
          fputcsv ( $output, $linie, $delimiter );
      }

    }
    
    fclose ( $output ); 
    // return $output;
  }

  /*
   * Step 1. Add Link to My Account menu
   */
  public function misha_log_history_link( $menu_links ){

    if ( current_user_can( "viewuserlist" ) ) {
      $menu_links = array_slice( $menu_links, 0, 5, true ) 
      + array( 'view-userlist' => 'Vis medlemsliste' )
      + array_slice( $menu_links, 5, NULL, true );
    }
    return $menu_links;
  }
  /*
   * Step 2. Register Permalink Endpoint
   */
  public function misha_add_endpoint() {

    if ( current_user_can( "viewuserlist" ) ) {
      // WP_Rewrite is my Achilles' heel, so please do not ask me for detailed explanation
      add_rewrite_endpoint( 'view-userlist', EP_PAGES );
      flush_rewrite_rules();
    }
  }
  /*
   * Step 3. Content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
   */
  function misha_my_account_endpoint_content() {
    if ( current_user_can( "viewuserlist" ) ) {
      // of course you can print dynamic content here, one of the most useful functions here is get_current_user_id()
      $this->jw_show_members();
    }
  }

  /**
   * Get next page of 20 subscriptions
   *
   * @param  integer $transid
   * @return $decode_res
   */

  public function get_admin_payment_info($transid) {

    $apiKey = 'fa61899694ac3b9b36a0c14263e93db0e8d3e22dedfe62e81b78f20bc223c0b9';
    
    $curl = curl_init();
        
    $url = 'https://api.quickpay.net/subscriptions/' . $transid;

    curl_setopt_array(
        $curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
        'Accept-Version: v10',
        'Authorization: Basic ' . base64_encode(':' . $apiKey),
            ),
        )
    );

    $response = curl_exec($curl);

    curl_close($curl);

    $decode_res = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
        
    return $decode_res;

  }
  
  /**
   * Check if user has active subscription
   * 
   */
  public function has_active_subscription( $user_id='' ) {
    // When a $user_id is not specified, get the current user Id
    if( '' == $user_id && is_user_logged_in() ) 
        $user_id = get_current_user_id();
    // User not logged in we return false
    if( $user_id == 0 ) 
        return false;

    return wcs_user_has_subscription( $user_id, '', 'active' );
  }
  
}

$jw_view_userlist = new jw_view_users();





/*
public function jw_show_usertable() {

    list ( $all_members, $count_memberships, $count_unionmemberships ) = self::find_all_members();
    
    $this->create_csv_file(); // regular member list
    
    $this->create_csv_file('statistik-2021.csv', '2021-02-01', '2022-01-31', 'stat'); // for statistics
    $this->create_csv_file('statistik-2022.csv', '2022-02-01', '2023-01-31', 'stat'); // for statistics
    $this->create_csv_file('statistik-2023.csv', '2023-02-01', '2024-01-31', 'stat'); // for statistics
    $this->create_csv_file('statistik-2021-2023.csv', '2021-02-01', '2024-01-31', 'stat'); // for statistics
    
    
    $fromYear = (date('m') == 1) ? date('Y', strtotime('-1 year')) : date('Y');
    
    echo "<h3 id='antal-medlemmer-overskrift'>Antal private medlemskaber: ".$count_memberships."</h3>";
    echo '<div id="medlemsdatoer">Fra: '.date('d-m-Y', strtotime($fromYear.'-02-01')).' Til: '.date('d-m-Y h:i:s', strtotime("+2 hour")).'</div>';
    echo '<input type="text" id="myInput" placeholder="Søg efter medlem" style="background-image: url(\''.plugins_url('/css/searchicon.png', __FILE__ ).'\');">';
    echo '<div id="myUL" class="table">';    
    foreach ($all_members as $member) {
      if ($member['company'] != '19221') {
        echo '<div class="table-row" data-orderid="'.$member['id'].'">
            <div class="table-cell left fullname">'.$member['name'].'</div>
            <div class="table-cell right sub-type">'.$member['antal'].'</div>
            <div class="table-cell editloader edit-pen"><img src="/css/edit-pen.png"></div>
            <div class="break"></div>
            <div class="edit-user-info"></div>
          </div>';
      }
    }
    echo "</div>";
    if (empty($count_unionmemberships)) {$count_unionmemberships = '0';}
    echo "<h3 id='antal-medlemmer-overskrift'>Antal forenings medlemskaber: " . $count_unionmemberships . "</h3>";
    echo '<div id="medlemsdatoer">Fra: '.date('d-m-Y', strtotime($fromYear.'-02-01')).' Til: '.date('d-m-Y h:i:s', strtotime("+2 hour")).'</div>';
    if ($count_unionmemberships != '0') {
      echo '<input type="text" id="myInput" placeholder="Søg efter foreningsmedlem" style="background-image: url(\''.plugins_url('/css/searchicon.png', __FILE__ ).'\');">';
      echo '<div id="myUL" class="table">';    
      foreach ($all_members as $member) {
        if ($member['produkt-id'] == '19221') {
          echo '<div class="table-row" data-orderid="'.$member['id'].'" data-productid="'.$member['produkt-id'].'">
              <div class="table-cell left fullname">'.$member['company'].'</div>
              <div class="table-cell right sub-type">'.$member['antal'].'</div>
              <div class="table-cell editloader edit-pen"><img src="/css/edit-pen.png"></div>
              <div class="break"></div>
              <div class="edit-user-info"></div>
            </div>';
        }
      }
      echo "</div>";
    }
  }      

      <script>
        jQuery(document).ready(function($){   
          $("button.opdaterknap").click(function() {
            var denneknap = $ ( this );
            var opdaternavnet = $( this ).closest(".table-row");
            var update_order_id = $("#update-order-id").val();
            var updatenonce = $("#update-nonce").val();
            var fornavn = $("#updatefornavn").val();
            var efternavn = $("#updateefternavn").val();
            var firmanavn = $("#updatefirmanavn").val();
            var addresse1 = $("#updateadresse1").val();
            var addresse2 = $("#updateadresse2").val();
            var postnr = $("#updatepostnr").val();
            var bynavn = $("#updatebynavn").val();
            var email = $("#updateemail").val();
            var tlfnr = $("#updatetlfnr").val();

            $.ajax({
              beforeSend: function() {
                denneknap.html("Opdaterer - Vent").addClass("wait"); 
              },
              url: example_ajax_obj.ajaxurl,
              data: {
                "action"            : "saveuser_ajax_request",
                "update-order-id"   : update_order_id,
                "update-nonce"      : updatenonce,
                "fornavn"           : fornavn,
                "efternavn"         : efternavn,
                "firmanavn"         : firmanavn,
                "addresse-one"      : addresse1,
                "addresse-two"      : addresse2,
                "postnr"            : postnr,
                "bynavn"            : bynavn,
                "email"             : email,
                "tlfnr"             : tlfnr,
              },
              error: function (errorThrown) {
                console.log(errorThrown);
              }
            })
            .done(function(data) {
              opdaternavnet.find(".medleminfo").html("<strong>ID: "+update_order_id+"<br/>" + fornavn + " " + efternavn + "<br/>" + addresse1 + "<br/>" + postnr + " " + bynavn);
              opdaternavnet.find(".mailadresse").html("<a href=\"mailto:" + email + "\">" + email + "</a>");
              opdaternavnet.find(".erhverv-forening").html(firmanavn);
              denneknap.html("Opdateret").removeClass("wait").delay(100).hide(50);
              $(".background-edit-user-modal").hide(100);
              $(".show-edit-user-modal").html("");
            });
          });
        });
        </script> 
*/