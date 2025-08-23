<?php
namespace ProMembersManager\Core;

defined('ABSPATH') || exit;

/**
 * Database handler class for Pro Members Manager plugin
 *
 * @package Pro_Members_Manager
 * @since 1.0.0
 */

/**
 * Class Database
 */
class Database {

    /**
     * Initialize the class
     */
    public function init() {
        // Nothing to do here yet
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create membership metadata table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pmm_membership_metadata (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            order_id mediumint(9) NOT NULL,
            membership_type varchar(50) NOT NULL,
            membership_status varchar(20) NOT NULL DEFAULT 'active',
            payment_method varchar(50),
            payment_id varchar(100),
            renewal_type varchar(20),
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_id (order_id),
            KEY membership_status (membership_status),
            KEY membership_type (membership_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create daily stats table  
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pmm_dayly_statistics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date datetime NOT NULL,
            private_memberships int(11) NOT NULL,
            union_memberships int(11) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY date (date)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Update version option
        update_option('pmm_db_version', PMM_DB_VERSION);
    }

    /**
     * Get all members
     * 
     * @param array $args Optional. Query arguments.
     * @return array List of members
     */
    public function get_members($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'number'     => 20,
            'offset'     => 0,
            'orderby'    => 'id',
            'order'      => 'DESC',
            'from_date'  => null,
            'to_date'    => null,
            'status'     => 'active',
            'type'       => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $members_table = $wpdb->prefix . 'pmm_membership_metadata';
        
        $sql = "SELECT * FROM $members_table WHERE 1=1";
        
        // Add filters
        if ($args['status']) {
            $sql .= $wpdb->prepare(" AND membership_status = %s", $args['status']);
        }
        
        if ($args['from_date'] && $args['to_date']) {
            $sql .= $wpdb->prepare(" AND created_at BETWEEN %s AND %s", $args['from_date'], $args['to_date']);
        }
        
        if ($args['type']) {
            $sql .= $wpdb->prepare(" AND membership_type = %s", $args['type']);
        }
        
        // Order
        $sql .= " ORDER BY " . sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        // Limit
        $sql .= $wpdb->prepare(" LIMIT %d, %d", $args['offset'], $args['number']);
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        if (!$results) {
            return array();
        }
        
        // Populate member information with user data
        $members = array();
        foreach ($results as $result) {
            $user_data = get_userdata($result['user_id']);
            if (!$user_data) {
                continue;
            }
            
            $order = wc_get_order($result['order_id']);
            if (!$order) {
                continue;
            }
            
            $member = array(
                'id'           => $result['id'],
                'userid'       => $result['user_id'],
                'orderid'      => $result['order_id'],
                'firstname'    => $user_data->first_name,
                'lastname'     => $user_data->last_name,
                'email'        => $user_data->user_email,
                'adresse'      => get_user_meta($result['user_id'], 'billing_address_1', true),
                'postnr'       => get_user_meta($result['user_id'], 'billing_postcode', true),
                'by'           => get_user_meta($result['user_id'], 'billing_city', true),
                'company'      => get_user_meta($result['user_id'], 'billing_company', true),
                'phone'        => get_user_meta($result['user_id'], 'billing_phone', true),
                'date'         => date('d-m-Y', strtotime($result['start_date'])),
                'antal'        => 1, // Default to 1 for now, could get from order items
                'produkt-id'   => $result['membership_type'],
                'betaling'     => $result['payment_method'],
                'betalingid'   => $result['payment_id'],
                'status'       => $result['membership_status'],
                'renewal_type' => $result['renewal_type'],
            );
            
            $members[] = $member;
        }
        
        return $members;
    }
    
    /**
     * Count members
     * 
     * @param array $args Optional. Query arguments.
     * @return int Count of members
     */
    public function count_members($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status'     => 'active',
            'from_date'  => null,
            'to_date'    => null,
            'type'       => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $members_table = $wpdb->prefix . 'pmm_membership_metadata';
        
        $sql = "SELECT COUNT(*) FROM $members_table WHERE 1=1";
        
        // Add filters
        if ($args['status']) {
            $sql .= $wpdb->prepare(" AND membership_status = %s", $args['status']);
        }
        
        if ($args['from_date'] && $args['to_date']) {
            $sql .= $wpdb->prepare(" AND created_at BETWEEN %s AND %s", $args['from_date'], $args['to_date']);
        }
        
        if ($args['type']) {
            $sql .= $wpdb->prepare(" AND membership_type = %s", $args['type']);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Add a member
     * 
     * @param array $data Member data
     * @return int|false Member ID on success, false on failure
     */
    public function add_member($data) {
        global $wpdb;
        
        $required_fields = array('user_id', 'order_id', 'membership_type', 'start_date', 'end_date', 'membership_status');
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        $current_time = current_time('mysql');
        
        $defaults = array(
            'membership_number' => '',
            'payment_method'    => '',
            'payment_id'        => '',
            'renewal_type'      => 'manual',
            'additional_data'   => '',
            'created_at'        => $current_time,
            'updated_at'        => $current_time,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $members_table = $wpdb->prefix . 'pmm_membership_metadata';
        
        $inserted = $wpdb->insert(
            $members_table,
            $data,
            array(
                '%d', // user_id
                '%d', // order_id
                '%s', // membership_type
                '%s', // membership_number
                '%s', // start_date
                '%s', // end_date
                '%s', // payment_method
                '%s', // payment_id
                '%s', // membership_status
                '%s', // renewal_type
                '%s', // additional_data
                '%s', // created_at
                '%s', // updated_at
            )
        );
        
        if (!$inserted) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update a member
     * 
     * @param int $id Member ID
     * @param array $data Member data
     * @return bool True on success, false on failure
     */
    public function update_member($id, $data) {
        global $wpdb;
        
        if (!$id || empty($data)) {
            return false;
        }
        
        $members_table = $wpdb->prefix . 'pmm_membership_metadata';
        
        $data['updated_at'] = current_time('mysql');
        
        $updated = $wpdb->update(
            $members_table,
            $data,
            array('id' => $id),
            array_fill(0, count($data), '%s'),
            array('%d')
        );
        
        return ($updated !== false);
    }
    
    /**
     * Delete a member
     * 
     * @param int $id Member ID
     * @return bool True on success, false on failure
     */
    public function delete_member($id) {
        global $wpdb;
        
        if (!$id) {
            return false;
        }
        
        $members_table = $wpdb->prefix . 'pmm_membership_metadata';
        
        $deleted = $wpdb->delete(
            $members_table,
            array('id' => $id),
            array('%d')
        );
        
        return ($deleted !== false);
    }
    
    /**
     * Get member statistics
     * 
     * @param array $args Optional. Query arguments.
     * @return array Member statistics
     */
    static function get_member_statistics($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'from_date' => date('Y-m-d', strtotime('-1 year')),
            'to_date'   => date('Y-m-d'),
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $members_table = $wpdb->prefix . 'pmm_membership_metadata';
        
        // Get count of private memberships
        $private_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE membership_type IN ('9503', '10968', '28736', '28735') 
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );
        
        $count_privatememberships = (int) $wpdb->get_var($private_sql);
        
        // Get count of union memberships
        $union_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE membership_type IN ('19221', '30734') 
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );
        
        $count_unionmemberships = (int) $wpdb->get_var($union_sql);
        
        // Get count of pension memberships
        $pension_manual_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE membership_type = '28735'
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );
        
        $count_pension_manual_memberships = (int) $wpdb->get_var($pension_manual_sql);
        
        $pension_auto_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE membership_type = '28736'
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );
        
        $count_pension_auto_memberships = (int) $wpdb->get_var($pension_auto_sql);
        
        // Get count of private memberships (manual and auto)
        $private_manual_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE membership_type = '10968'
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );
        
        $count_private_manual_memberships = (int) $wpdb->get_var($private_manual_sql);
        
        $private_auto_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE membership_type = '9503'
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );
        
        $count_private_auto_memberships = (int) $wpdb->get_var($private_auto_sql);
        
        // Get count of union memberships in Dianalund
        $union_in_dianalund_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE membership_type IN ('19221', '30734')
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s
            AND id IN (
                SELECT mm.id FROM $members_table mm
                JOIN {$wpdb->usermeta} um ON mm.user_id = um.user_id
                WHERE um.meta_key = 'billing_postcode' AND um.meta_value = '4293'
            )",
            $args['from_date'], $args['to_date']
        );
        
        $count_unionmemberships_in_dianalund = (int) $wpdb->get_var($union_in_dianalund_sql);
        
        // Get count of union memberships outside Dianalund
        $union_outside_dianalund_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE membership_type IN ('19221', '30734')
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s
            AND id NOT IN (
                SELECT mm.id FROM $members_table mm
                JOIN {$wpdb->usermeta} um ON mm.user_id = um.user_id
                WHERE um.meta_key = 'billing_postcode' AND um.meta_value = '4293'
            )",
            $args['from_date'], $args['to_date']
        );
        
        $count_unionmemberships_outside_dianalund = (int) $wpdb->get_var($union_outside_dianalund_sql);
        
        return array(
            'count_privatememberships' => $count_privatememberships,
            'count_unionmemberships' => $count_unionmemberships,
            'count_pension_manual_memberships' => $count_pension_manual_memberships,
            'count_pension_auto_memberships' => $count_pension_auto_memberships,
            'count_private_manual_memberships' => $count_private_manual_memberships,
            'count_private_auto_memberships' => $count_private_auto_memberships,
            'count_unionmemberships_in_dianalund' => $count_unionmemberships_in_dianalund,
            'count_unionmemberships_outside_dianalund' => $count_unionmemberships_outside_dianalund,
        );
    }

    /**
     * Get daily statistics
     * 
     * @param string $date Date in 'Y-m-d' format
     * @return array Daily statistics
     */
    public function get_daily_statistics($date) {
        global $wpdb;
        
        $dayly_stats_table = $wpdb->prefix . 'pmm_dayly_statistics';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $dayly_stats_table WHERE date = %s",
            $date
        );
        
        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Record daily statistics
     * 
     * @param array $data Daily statistics data
     * @return bool True on success, false on failure
     */
    static function record_daily_stats() {
        $stats = Database::get_member_statistics();

        global $wpdb;
        
        $dayly_stats_table = $wpdb->prefix . 'pmm_dayly_statistics';
        
        $data = array(
            'date' => current_time('Y-m-d'),
            'private_memberships' => $stats['count_privatememberships'],
            'union_memberships' => $stats['count_unionmemberships'],
        );
        
        $inserted = $wpdb->insert(
            $dayly_stats_table,
            $data,
            array(
                '%s', // date
                '%d', // private_memberships
                '%d', // union_memberships
            )
        );
        
        return ($inserted !== false);
    }

    static function get_membership_growth($period, $start_date, $end_date) {
        global $wpdb;
        
        $dayly_stats_table = $wpdb->prefix . 'pmm_dayly_statistics';
        
        $sql = $wpdb->prepare(
            "SELECT date, private_memberships, union_memberships FROM $dayly_stats_table 
            WHERE date BETWEEN %s AND %s",
            $start_date, $end_date
        );
        
        $result = $wpdb->get_results($sql, ARRAY_A);

        // Calculate growth
        $growth = array();
        foreach ($result as $row) {
            $date = $row['date'];
            $private_memberships = (int) $row['private_memberships'];
            $union_memberships = (int) $row['union_memberships'];

            if (!isset($growth[$date])) {
                $growth[$date] = array(
                    'private_memberships' => 0,
                    'union_memberships' => 0,
                );
            }

            $growth[$date]['private_memberships'] += $private_memberships;
            $growth[$date]['union_memberships'] += $union_memberships;
        }
        // Calculate periods monthly or weekly or daily
        $periods = array();
        foreach ($growth as $date => $data) {
            if ($period == 'monthly') {
                $month = date('Y-m', strtotime($date));
                if (!isset($periods[$month])) {
                    $periods[$month] = array(
                        'private_memberships' => 0,
                        'union_memberships' => 0,
                    );
                }
                $periods[$month]['private_memberships'] += $data['private_memberships'];
                $periods[$month]['union_memberships'] += $data['union_memberships'];
            } elseif ($period == 'weekly') {
                $week = date('oW', strtotime($date));
                if (!isset($periods[$week])) {
                    $periods[$week] = array(
                        'private_memberships' => 0,
                        'union_memberships' => 0,
                    );
                }
                $periods[$week]['private_memberships'] += $data['private_memberships'];
                $periods[$week]['union_memberships'] += $data['union_memberships'];
            } else {
                // Daily
                if (!isset($periods[$date])) {
                    $periods[$date] = array(
                        'private_memberships' => 0,
                        'union_memberships' => 0,
                    );
                }
                $periods[$date]['private_memberships'] += $data['private_memberships'];
                $periods[$date]['union_memberships'] += $data['union_memberships'];
            }
        }
        // Return the growth data
        return $periods;
    }
}