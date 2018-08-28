<?php
namespace E20R\Tracker\Models;

/**
 * Created by Wicked Strong Chicks, LLC.
 * User: Thomas Sjolshagen
 * Date: 12/18/14
 * Time: 2:23 PM
 * 
 * License Information:
 *  the GPL v2 license(?)
 */

use Stripe\Stripe;
use Stripe\Plan;
use Stripe\Customer;
use Stripe\Subscription;
use E20R\Utilities\Utilities;


class Tracker_Stripe {

	private static $instance = null;

	/**
	 * @return Tracker_Stripe
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}