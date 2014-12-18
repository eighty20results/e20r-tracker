<?php
/**
 * Created by Wicked Strong Chicks, LLC.
 * User: Thomas Sjolshagen
 * Date: 12/18/14
 * Time: 2:23 PM
 * 
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rStripe {

    function test_stripe_api( $new_level_id = 0, $user_id = 0 ) {

        if(!class_exists("Stripe")) {

            dbg( "Loading supporting libraries for Stripe" );
            require_once( dirname( __FILE__ ) . "/../../plugins/paid-memberships-pro/includes/lib/Stripe/Stripe.php" );
        }

        global $wpdb;

        $user_id = 62;
        /*
        if ( $user_id == 0 ) {

            global $current_user;
            $user_id = $current_user->ID;
        }
        */

        $customer_id = "cus_4iXJe8n4SR4phk"; // Test user

        $nourish = pmpro_getLevel( NOURISH_LEVEL );

        $next_payment = ( ( $paydate = pmpro_next_payment( $user_id ) !== false ) ? $paydate : null );

        //if ( $next_payment ) {

        $last_day = new DateTime( date('Y-m-d', $next_payment ) );
        $today = new DateTime();

        $days_left = $today->diff( $last_day );
        dbg("Last Day: {$last_day->format('Y-m-d')}, Today: {$today->format('Y-m-d')} Days: {$days_left->format('%a')}" );

        if ( ! empty( $nourish ) ) {

            $new_plan = array(
                'amount' => round( ( $nourish->billing_amount * 100 ), 0 ),
                'trial_period_days' => $days_left->format( '%a' ),
                'interval' => 'month',
                'interval_count' => 1,
                'currency' => 'usd',
            );
        }
        else {
            dbg("Error: Unable to fetch valid Nourish level info");
            return false;
        }

        if ( class_exists( 'Stripe' ) ) {

            dbg( "Stripe class is loaded" );

            try {
                // Stripe::setApiKey( pmpro_getOption( "stripe_secretkey" ) );
                // Use test key & test user.
                Stripe::setApiKey( "sk_test_J57vfoBXUGCNnJWY6gwuVt8I" );
            }
            catch ( Exception $e ) {

                dbg( "Unable to set the API key: " . $e->getMessage() );
                return false;
            }

            $sql = $wpdb->prepare("
                SELECT
                  code AS stripe_plan,
                  membership_id AS membership_level,
                  subscription_transaction_id AS stripe_cust_id,
                  timestamp AS created
                FROM wp_pmpro_membership_orders
                WHERE ( user_id = %d ) AND ( status = 'success' ) AND ( gateway = 'stripe' )
                ORDER BY user_id ASC
                LIMIT 1
              ",
                $user_id
            );

            $result = $wpdb->get_row($sql, OBJECT);

            if ( ! empty ($result ) ) {

                $customer_id = $result->stripe_cust_id;

                if ( ! empty( $new_plan ) ) {

                    $new_plan['id'] = $result->stripe_plan;
                    $new_plan['name'] = "{$nourish->name} for order {$result->stripe_plan}";

                } // endif

                // Create new temporary plan
                try {

                    Stripe_Plan::create( $new_plan );
                    dbg( "New temporary S3F plan created: {$new_plan['name']}" );
                }
                catch ( Exception $e ) {

                    dbg( "Error creating new plan: " . $e->getMessage() );
                    return false;
                }

                try {

                    $cu = Stripe_Customer::retrieve($customer_id);
                    dbg("Fetched customer data from Stripe systems for " . $customer_id );

                }
                catch ( Exception $e ) {

                    dbg("Stripe is unable to locate customer data: " . $e->getMessage());
                    return false;
                }

                /*
                $card_id = $cu->default_card;

                $card = $cu->cards->retrieve($card_id);

                */
                try {

                    $subscriptions = $cu->subscriptions->all();
                    dbg("Fetched " . count( $subscriptions->data ) . " subscriptions for user");

                }
                catch ( Exception $e ) {

                    dbg("Stripe is unable to locate subscriptions: " . $e->getMessage());
                    return false;
                }

                if ( count( $subscriptions->data ) < 2 ) {
                    foreach ($subscriptions->data as $subscr) {

                        try {

                            $plan = $cu->subscriptions->retrieve($subscr->id);
                            dbg("Subscription detail returned");
                        }
                        catch ( Exception $e ) {

                            dbg( "Error fetching subscription detail: " . $e->getMessage() );
                            return false;
                        }

                        dbg( "Subscription ID: {$subscr->id}" );

                        try {

                            $subscr->plan = $new_plan['id'];
                            $subscr->prorate = false;

                            $new_subcr = $subscr->save();

                            /* TODO: Update info in pmpro_memberhip_orders and pmpro_memberships_users for the user we're downgrading */
                            dbg("Updated subscription for Nourish: " . print_r( $new_subcr, true ) );


                        }
                        catch ( Exception $e ) {
                            dbg(" Error saving new plan... - " . $e->getMessage() );
                            return false;
                        }

                    }

                } // End foreach

                // Delete temporary plan
                try {

                    $tmp = Stripe_Plan::retrieve( $new_plan['id'] );
                    $res = $tmp->delete();

                    if ( $res->deleted ) {

                        dbg("New temporary S3F plan deleted");
                    }
                    else {

                        dbg("Error: Unable to delete temporary plan!");
                    }
                }
                catch ( Exception $e ) {

                    dbg( "Error deleting plan: " . $e->getMessage() );
                    return false;
                }
            }
            else {
                dbg("No active customer plans in the local database for user with ID: {$user_id}");
            } // endif

        }
        else {
            dbg("Error: Stripe libraries not loaded!");
        }
    }
}