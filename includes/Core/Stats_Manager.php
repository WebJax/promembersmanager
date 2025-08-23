<?php
namespace ProMembersManager\Core;

defined('ABSPATH') || exit;

/**
 * Handles membership statistics for Pro Members Manager
 */
class Stats_Manager {
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Schedule daily statistics collection
        add_action('init', [$this, 'setup_cron_jobs']);
        add_action('pmm_daily_stats_update', [$this, 'record_daily_stats']);
        
        // AJAX handlers for statistics
        add_action('wp_ajax_pmm_get_stats', [$this, 'ajax_get_stats']);
    }
    
    /**
     * Setup cron jobs for statistics
     */
    public function setup_cron_jobs() {
        if (!wp_next_scheduled('pmm_daily_stats_update')) {
            wp_schedule_event(time(), 'daily', 'pmm_daily_stats_update');
        }
    }
    
    /**
     * Record daily statistics
     */
    public function record_daily_stats() {
        Database::record_daily_stats();
    }
    
    /**
     * Get membership statistics
     * 
     * @param array $args Query arguments
     * @return array Statistics data
     */
    public function get_stats($args = []) {
        $default_args = [
            'from_date' => date('Y-m-d', strtotime('-1 year')),
            'to_date' => date('Y-m-d'),
            'per_page' => -1
        ];
        
        $args = wp_parse_args($args, $default_args);
        
        $member_manager = new Member_Manager();
        
        // Get counts by type and renewal using Member_Manager's logic
        $member_counts = $member_manager->get_members_count([
            'group_by' => 'both',
            'from_date' => $args['from_date'],
            'to_date' => $args['to_date']
        ]);
        
        // Get total count
        $total_count = $member_manager->get_members_count([
            'from_date' => $args['from_date'],
            'to_date' => $args['to_date']
        ]);
        
        // Format stats to match the expected structure for members-page
        $stats = [
            'private' => [
                'total' => ($member_counts['private']['auto'] ?? 0) + ($member_counts['private']['manual'] ?? 0),
                'auto' => [
                    'total' => ($member_counts['private']['auto'] ?? 0) + ($member_counts['pension']['auto'] ?? 0),
                    'regular' => $member_counts['private']['auto'] ?? 0,
                    'pension' => $member_counts['pension']['auto'] ?? 0
                ],
                'manual' => [
                    'total' => ($member_counts['private']['manual'] ?? 0) + ($member_counts['pension']['manual'] ?? 0),
                    'regular' => $member_counts['private']['manual'] ?? 0,
                    'pension' => $member_counts['pension']['manual'] ?? 0
                ]
            ],
            'union' => [
                'total' => ($member_counts['union']['auto'] ?? 0) + ($member_counts['union']['manual'] ?? 0),
                'in_dianalund' => 0, // This would need special handling if needed
                'outside_dianalund' => 0 // This would need special handling if needed
            ],
            'total' => $total_count
        ];
        
        return $stats;
    }
    
    /**
     * Get membership growth data for charts
     * 
     * @param string $period 'daily', 'weekly', 'monthly'
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @return array Growth data
     */
    public function get_membership_growth($period = 'monthly', $start_date = '', $end_date = '') {
        return Database::get_membership_growth($period, $start_date, $end_date);
    }
    
    /**
     * AJAX handler for getting statistics
     */
    public function ajax_get_stats() {
        // Verify nonce
        check_ajax_referer('pmm_admin_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('viewuserlist') && !current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pro-members-manager')]);
            return;
        }
        
        // Get parameters
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : date('Y-m-d', strtotime('-1 year'));
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : date('Y-m-d');
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'monthly';
        $chart_type = isset($_POST['chart_type']) ? sanitize_text_field($_POST['chart_type']) : 'pie';
        
        // Get statistics
        $args = [
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
        
        $stats = $this->get_stats($args);
        $growth_data = $this->get_membership_growth($period, $from_date, $to_date);
        
        // Format data for charts
        $chart_data = [];
        
        if ($chart_type === 'pie') {
            $chart_data = [
                'labels' => [
                    __('Private Members', 'pro-members-manager'),
                    __('Organization Members', 'pro-members-manager')
                ],
                'datasets' => [[
                    'data' => [$stats['private']['total'], $stats['union']['total']],
                    'backgroundColor' => ['#4e73df', '#1cc88a']
                ]]
            ];
        } elseif ($chart_type === 'bar') {
            $chart_data = [
                'labels' => [
                    __('Private (Auto)', 'pro-members-manager'),
                    __('Private (Manual)', 'pro-members-manager'),
                    __('Pension (Auto)', 'pro-members-manager'),
                    __('Pension (Manual)', 'pro-members-manager'),
                    __('Organizations (Dianalund)', 'pro-members-manager'),
                    __('Organizations (Other)', 'pro-members-manager')
                ],
                'datasets' => [[
                    'label' => __('Members', 'pro-members-manager'),
                    'data' => [
                        $stats['private']['auto']['regular'],
                        $stats['private']['manual']['regular'],
                        $stats['private']['auto']['pension'],
                        $stats['private']['manual']['pension'],
                        $stats['union']['in_dianalund'],
                        $stats['union']['outside_dianalund']
                    ],
                    'backgroundColor' => [
                        '#4e73df', '#4e73df', 
                        '#36b9cc', '#36b9cc', 
                        '#1cc88a', '#1cc88a'
                    ],
                    'borderWidth' => 1
                ]]
            ];
        } elseif ($chart_type === 'line') {
            // Format growth data for line chart
            $dates = [];
            $totals = [];
            $private = [];
            $union = [];
            
            foreach ($growth_data as $data) {
                $dates[] = date('d-m-Y', strtotime($data['date']));
                $totals[] = $data['total'];
                $private[] = $data['private'] + $data['pension'];
                $union[] = $data['union'];
            }
            
            $chart_data = [
                'labels' => $dates,
                'datasets' => [
                    [
                        'label' => __('Total Members', 'pro-members-manager'),
                        'data' => $totals,
                        'borderColor' => '#4e73df',
                        'backgroundColor' => 'transparent',
                        'tension' => 0.1
                    ],
                    [
                        'label' => __('Private Members', 'pro-members-manager'),
                        'data' => $private,
                        'borderColor' => '#e74a3b',
                        'backgroundColor' => 'transparent',
                        'tension' => 0.1
                    ],
                    [
                        'label' => __('Organization Members', 'pro-members-manager'),
                        'data' => $union,
                        'borderColor' => '#1cc88a',
                        'backgroundColor' => 'transparent',
                        'tension' => 0.1
                    ]
                ]
            ];
        }
        
        wp_send_json_success([
            'stats' => $stats,
            'growth' => $growth_data,
            'chart_data' => $chart_data
        ]);
    }
    
    /**
     * Get membership statistics by month and year
     * 
     * @param int $year Year to get statistics for
     * @return array Monthly stats
     */
    public function get_monthly_stats($year = null) {
        if ($year === null) {
            $year = date('Y');
        }
        
        $start_date = $year . '-01-01';
        $end_date = $year . '-12-31';
        
        $growth_data = $this->get_membership_growth('monthly', $start_date, $end_date);
        
        // Format for monthly display
        $monthly_stats = [];
        
        foreach ($growth_data as $data) {
            $month = date('n', strtotime($data['date']));
            $monthly_stats[$month] = [
                'total' => $data['total'],
                'private' => $data['private'],
                'pension' => $data['pension'],
                'union' => $data['union'],
                'auto' => $data['auto'],
                'manual' => $data['manual'],
                'dianalund' => $data['in_dianalund'],
            ];
        }
        
        return $monthly_stats;
    }
    
    /**
     * Export statistics to CSV
     * 
     * @param string $period 'daily', 'weekly', 'monthly'
     * @param string $start_date Start date in Y-m-d format
     * @param string $end_date End date in Y-m-d format
     * @return string Path to CSV file
     */
    public function export_stats_to_csv($period = 'monthly', $start_date = '', $end_date = '') {
        $growth_data = $this->get_membership_growth($period, $start_date, $end_date);
        
        // Create CSV Handler
        $csv_handler = new CSV_Handler();
        $headers = [
            __('Date', 'pro-members-manager'),
            __('Total Members', 'pro-members-manager'),
            __('Private Members', 'pro-members-manager'),
            __('Pension Members', 'pro-members-manager'),
            __('Organization Members', 'pro-members-manager'),
            __('Auto Renewals', 'pro-members-manager'),
            __('Manual Renewals', 'pro-members-manager'),
            __('Dianalund Members', 'pro-members-manager'),
            __('Outside Members', 'pro-members-manager'),
        ];
        
        $data = [];
        foreach ($growth_data as $row) {
            $data[] = [
                date('d-m-Y', strtotime($row['date'])),
                $row['total'],
                $row['private'],
                $row['pension'],
                $row['union'],
                $row['auto'],
                $row['manual'],
                $row['in_dianalund'],
                $row['outside_dianalund']
            ];
        }
        
        $filename = 'pmm-stats-' . date('Y-m-d') . '.csv';
        return $csv_handler->generate_csv($headers, $data, $filename);
    }
}