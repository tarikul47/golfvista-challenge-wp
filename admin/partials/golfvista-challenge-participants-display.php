<?php
/**
 * Provide a admin-facing view for the participants dashboard.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://golfvista.com/
 * @since      1.0.0
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/admin/partials
 */

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Golfvista_Challenge_Participants_Table extends WP_List_Table {
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns() {
        $columns = array(
            'user_id'          => 'User ID',
            'user_name'        => 'User Name',
            'challenge_status' => 'Challenge Status',
            'business_plan'    => 'Business Plan',
            'actions'          => 'Actions'
        );

        return $columns;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_user_name($item) {
        $nonce = wp_create_nonce('golfvista_reset_participant_' . $item['user_id']);
        $actions = array(
            'delete' => sprintf('<a href="?post_type=business_plan&page=%s&action=%s&user_id=%s&_wpnonce=%s">Reset Challenge</a>', $_REQUEST['page'], 'reset_challenge', $item['user_id'], $nonce)
        );
        return sprintf('%1$s %2$s', $item['user_name'], $this->row_actions($actions));
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns() {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns() {
        return array('user_name' => array('user_name', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data() {
        $data = array();
        $users = get_users();

        foreach ( $users as $user ) {
            $status = get_user_meta( $user->ID, '_golfvista_challenge_status', true );
            if ( ! empty( $status ) ) {
                $business_plan_link = '';
                if ( $status === 'plan_submitted' ) {
                    $args = array(
                        'post_type' => 'business_plan',
                        'author' => $user->ID,
                        'posts_per_page' => 1
                    );
                    $business_plan_query = new WP_Query( $args );
                    if ( $business_plan_query->have_posts() ) {
                        $business_plan_query->the_post();
                        $business_plan_link = '<a href="' . get_edit_post_link( get_the_ID() ) . '">View Plan</a>';
                    }
                    wp_reset_postdata();
                }

                $data[] = array(
                    'user_id'          => $user->ID,
                    'user_name'        => $user->display_name,
                    'challenge_status' => $status,
                    'business_plan'    => $business_plan_link
                );
            }
        }
        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'user_id':
            case 'user_name':
            case 'challenge_status':
            case 'business_plan':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the get_sortable_columns method
     *
     * @return Mixed
     */
    private function sort_data( $a, $b ) {
        // Set defaults
        $orderby = 'user_name';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}

$participants_table = new Golfvista_Challenge_Participants_Table();
$participants_table->prepare_items();
?>
<div class="wrap">
    <h1>Challenge Participants</h1>
    <?php $participants_table->display(); ?>
</div>
