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


        // Define and create the members table name
        $members_table = $wpdb->prefix . 'pmm_membership_metadata';
        
        $sql = "CREATE TABLE $members_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            membership_type varchar(50) NOT NULL,
            membership_number varchar(50) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            payment_method varchar(100) NOT NULL,
            payment_id varchar(100),
            membership_status varchar(20) NOT NULL,
            renewal_type varchar(20) NOT NULL,
            additional_data text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Define and create dayly statistics table name
        $dayly_stats_table = $wpdb->prefix . 'pmm_dayly_statistics';
        $sql = "CREATE TABLE $dayly_stats_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date datetime NOT NULL,
            private_memberships int(11) NOT NULL,
            union_memberships int(11) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY date (date)
        ) $charset_collate;";
        dbDelta($sql);
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
    public function get_member_statistics($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'from_date' => date('Y-m-d', strtotime('-1 year')),
            'to_date'   => date('Y-m-d'),
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $members_table = $wpdb->prefix . 'pmm_membership_metadata';
        
        // Define membership product groups
        $private_ids = [9503, 10968];
        $pension_ids = [28736, 28735];
        $union_ids = [19221, 30734];

        // If a specific member_type filter is provided, narrow which IDs to include
        $filter_ids = [];
        if (!empty($args['member_type'])) {
            switch ($args['member_type']) {
                case 'private':
                    $filter_ids = $private_ids;
                    break;
                case 'pension':
                    $filter_ids = $pension_ids;
                    break;
                case 'union':
                    $filter_ids = $union_ids;
                    break;
                default:
                    $filter_ids = array_merge($private_ids, $pension_ids, $union_ids);
            }
        }

        // Helper to build IN list
        $all_private_list = implode(',', array_map('intval', $private_ids));
        $all_pension_list = implode(',', array_map('intval', $pension_ids));
        $all_union_list = implode(',', array_map('intval', $union_ids));
        $filter_list = !empty($filter_ids) ? implode(',', array_map('intval', $filter_ids)) : '';

        // Get count of private memberships (excluding pension)
        $private_where = "membership_type IN ($all_private_list)";
        if ($filter_list !== '') {
            // further restrict to the filtered IDs
            $private_where .= " AND membership_type IN ($filter_list)";
        }

        $private_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE {$private_where} 
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );

        $count_privatememberships = (int) $wpdb->get_var($private_sql);
        
        // Get count of union memberships
        $union_where = "membership_type IN ($all_union_list)";
        if ($filter_list !== '') {
            $union_where .= " AND membership_type IN ($filter_list)";
        }

        $union_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE {$union_where} 
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );

        $count_unionmemberships = (int) $wpdb->get_var($union_sql);
        
        // Get count of pension memberships (manual and auto)
        $pension_manual_where = "membership_type = 28735";
        $pension_auto_where = "membership_type = 28736";
        if ($filter_list !== '') {
            $pension_manual_where .= " AND membership_type IN ($filter_list)";
            $pension_auto_where .= " AND membership_type IN ($filter_list)";
        }

        $pension_manual_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE {$pension_manual_where}
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );

        $count_pension_manual_memberships = (int) $wpdb->get_var($pension_manual_sql);

        $pension_auto_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE {$pension_auto_where}
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );

        $count_pension_auto_memberships = (int) $wpdb->get_var($pension_auto_sql);
        
        // Get count of private memberships (manual and auto)
        $private_manual_where = "membership_type = 10968";
        $private_auto_where = "membership_type = 9503";
        if ($filter_list !== '') {
            $private_manual_where .= " AND membership_type IN ($filter_list)";
            $private_auto_where .= " AND membership_type IN ($filter_list)";
        }

        $private_manual_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE {$private_manual_where}
            AND membership_status = 'active'
            AND created_at BETWEEN %s AND %s",
            $args['from_date'], $args['to_date']
        );

        $count_private_manual_memberships = (int) $wpdb->get_var($private_manual_sql);

        $private_auto_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table 
            WHERE {$private_auto_where}
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
        $database = new self();
        $stats = $database->get_member_statistics();

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

    /**
     * Get active member counts on a specific date
     *
     * @param string $date Date in 'Y-m-d' format
     * @param string $member_type Optional. 'private', 'pension', 'union' or empty for all.
     * @return array Associative counts: ['total'=>int, 'private'=>int, 'pension'=>int, 'union'=>int]
     */
    public function get_active_members_count_on($date, $member_type = '') {
        global $wpdb;

        $members_table = $wpdb->prefix . 'pmm_membership_metadata';

        // Normalize date bounds for the day
        $day_start = $date . ' 00:00:00';
        $day_end = $date . ' 23:59:59';

        // Define membership product groups
        $private_ids = [9503, 10968];
        $pension_ids = [28736, 28735];
        $union_ids = [19221, 30734];

        // Optionally restrict to a member_type
        $type_filter_sql = '';
        if (!empty($member_type)) {
            switch ($member_type) {
                case 'private':
                    $ids = $private_ids;
                    break;
                case 'pension':
                    $ids = $pension_ids;
                    break;
                case 'union':
                    $ids = $union_ids;
                    break;
                default:
                    $ids = array_merge($private_ids, $pension_ids, $union_ids);
            }
            $ids_list = implode(',', array_map('intval', $ids));
            $type_filter_sql = " AND membership_type IN ($ids_list) ";
        }

        // Prepare default zeroed counters
        $counts = [
            'total' => 0,
            'private' => 0,
            'pension' => 0,
            'union' => 0
        ];

        // Quick sanity check: if table has no rows, return zeros
        $row_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $members_table");
        if ($row_count === 0) {
            error_log('Pro Members Manager - membership metadata table is empty.');
            // Signal to caller that metadata table is empty so callers can fallback
            return null;
        }

        // Count rows where membership was active on that day
        // Use STR_TO_DATE to safely parse stored date strings and avoid incorrect DATETIME errors
        $end_condition = "(end_date IS NULL OR end_date = '' OR end_date = '0000-00-00 00:00:00' OR STR_TO_DATE(end_date, '%Y-%m-%d %H:%i:%s') >= STR_TO_DATE(%s, '%Y-%m-%d %H:%i:%s'))";

        $sql = "SELECT membership_type, COUNT(*) as cnt FROM $members_table
            WHERE membership_status = 'active'
            AND STR_TO_DATE(start_date, '%Y-%m-%d %H:%i:%s') <= STR_TO_DATE(%s, '%Y-%m-%d %H:%i:%s')
            AND " . $end_condition . " 
            {$type_filter_sql}
            GROUP BY membership_type";

        // Prepare with day_end and day_start placed into the start_date and end_date comparisons
        // STR_TO_DATE(start_date) <= %s  -> use day_end
        // STR_TO_DATE(end_date) >= %s    -> use day_start
        $sql = $wpdb->prepare($sql, $day_end, $day_start);

        $rows = $wpdb->get_results($sql, ARRAY_A);

        $counts = [
            'total' => 0,
            'private' => 0,
            'pension' => 0,
            'union' => 0
        ];

        if ($rows) {
            foreach ($rows as $row) {
                $mid = intval($row['membership_type']);
                $cnt = intval($row['cnt']);
                $counts['total'] += $cnt;
                if (in_array($mid, $private_ids)) {
                    $counts['private'] += $cnt;
                } elseif (in_array($mid, $pension_ids)) {
                    $counts['pension'] += $cnt;
                } elseif (in_array($mid, $union_ids)) {
                    $counts['union'] += $cnt;
                } else {
                    // Unknown types are added to total but not bucketed
                    $counts['total'] += 0; // already added
                }
            }
        }

        // Diagnostic logging when counts are zero for visibility during debugging
        if ($counts['total'] === 0) {
            error_log('Pro Members Manager - get_active_members_count_on(' . $date . ") returned zero. SQL: " . $sql . " Rows: " . json_encode($rows));
        }

        return $counts;
    }
}