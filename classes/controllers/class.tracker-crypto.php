<?php
/**
 * Copyright (c) 2018 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Tracker\Controllers;

use Defuse\Crypto\Encoding;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use E20R\Tracker\Models\Tables;
use E20R\Utilities\Utilities;

class Tracker_Crypto {
	
	/**
	 * @var null|Tracker_Crypto
	 */
	private static $instance = null;
	
	/**
	 * Tracker_Crypto constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * Return or instantiate and return this class
	 *
	 * @return Tracker_Crypto|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Decrypt the data for the user
	 *
	 * @param int         $user_id   - ID of user who 'owns' the data
	 * @param string      $encData   - Serialized and base64 encoded array
	 * @param null|string $encrypted - Serialized and base64 encoded array
	 *
	 * @return array
	 */
	public static function decryptData( $user_id, $encData, $encrypted = null ) {
		
		$controller = Tracker::getInstance();
		$decrypted  = null;
		
		Utilities::get_instance()->log( "Processing encrypted data for {$user_id}" );
		
		$userKey = Tracker_Crypto::getUserKey( $user_id );
		
		if ( is_null( $encrypted ) ) {
			
			$encrypted = (bool) $controller->loadOption( 'encrypt_surveys' );
		}
		
		Utilities::get_instance()->log( "Encryption is " . ( $encrypted ? 'enabled' : 'disabled' ) );
		
		if ( ( $userKey === null ) || ( false === $encrypted ) ) {
			
			Utilities::get_instance()->log( "No decryption key - or encryption is disabled: {$encrypted}" );
			
			return unserialize( base64_decode( $encData ) );
		}
		
		try {
			
			Utilities::get_instance()->log( "Attempting to decrypt data (encrypted)");
			
			$decrypted = Crypto::decrypt( $encData, $userKey );
			Utilities::get_instance()->log("Decrypted data: " . print_r( $decrypted, true ) );
			
		} catch ( WrongKeyOrModifiedCiphertextException $exception ) { // VERY IMPORTANT
			// Either:
			//   1. The ciphertext was modified by the attacker,
			//   2. The key is wrong, or
			//   3. $ciphertext is not a valid ciphertext or was corrupted.
			// Assume the worst.
			$msg = sprintf( __( 'DANGER! DANGER! The encrypted information may have been modified while we were loading it. Please report this error to the Webmaster. Error: ', 'e20r-tracker' ), $exception->getMessage() );
			
			Utilities::get_instance()->log( $msg );
			wp_die( $msg );
		} catch ( EnvironmentIsBrokenException $ex ) {
			
			$msg = sprintf( __( 'There was a problem decrypting your information. Please report this error to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() );
			
			Utilities::get_instance()->log( $msg );
			wp_die( $msg );
		} catch ( \TypeError $ex ) {
			
			$msg = sprintf( __( 'Programming error. Please report this issue to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() );
			
			Utilities::get_instance()->log( $msg );
			wp_die( $msg );
			
		} catch ( BadFormatException $exception ) {
			$msg = sprintf( __( "The format of the encryption key located for your user is invalid. Please contact the webmaster. Error: %s", "e20r-tracker" ), $exception->getMessage() );
			
			Utilities::get_instance()->log( $msg );
			wp_die( $msg );
		}
		
		return unserialize( $decrypted );
	}
	
	/**
	 * Retrieve the Encryption Key for the user (may need to update the encryption)
	 *
	 * @param null|int $userId
	 *
	 * @return Key|null|string
	 */
	private static function getUserKey( $userId = null ) {
		
		$Access = Tracker_Access::getInstance();
		
		global $post;
		global $current_user;
		
		if ( ! is_user_logged_in() ) {
			
			return null;
		}
		
		if ( ( $current_user->ID != 0 ) && ( null === $userId ) ) {
			
			$userId = $current_user->ID;
		}
		
		Utilities::get_instance()->log( "Test if user ({$userId}) can access their AES key based on the post {$post->ID}." );
		
		if ( true === $Access->hasAccess( $userId, $post->ID ) ) {
			
			Utilities::get_instance()->log( "User {$userId} is permitted to access their AES key. Don't load key from cache!" );
			
			// Flush cache before reading the user's key
			clean_user_cache( $userId );
			
			try {
				
				$key = Key::loadFromAsciiSafeString( get_user_meta( $userId, 'e20r_user_key', true ) );
				
			} catch ( BadFormatException $exception ) {
				
				$msg = sprintf( __( "The format of the encryption key located for your user is invalid. Please contact the webmaster. Error: %s", "e20r-tracker" ), $exception->getMessage() );
				
				Utilities::get_instance()->log( $msg );
				wp_die( $msg );
				
			} catch ( EnvironmentIsBrokenException $exception ) {
				
				$msg = sprintf( __( "There is something wrong with the encryption/decryption of your data. Please contact the webmaster. Error: %s", "e20r-tracker" ), $exception->getMessage() );
				Utilities::get_instance()->log( $msg );
				wp_die( $msg );
			}
			
			// Flush cache after reading the user's key
			clean_user_cache( $userId );
			
			// Utilities::get_instance()->log( $key );
			if ( empty( $key ) ) {
				
				try {
					
					Utilities::get_instance()->log( "Generating a new key for user {$userId}" );
					$key = Key::createNewRandomKey();
					
					// WARNING: Do NOT encode $key with bin2hex() or base64_encode(),
					// they may leak the key to the attacker through side channels.
					if ( false === update_user_meta( $userId, 'e20r_user_key', $key->saveToAsciiSafeString() ) ) {
						
						// Flush cache after saving the user's key
						clean_user_cache( $userId );
						
						Utilities::get_instance()->log( "ERROR: Unable to save the key for user {$userId}" );
						
						$msg = sprintf(
							__( 'Could not create an encryption key for you. Please %1$scontact the webmaster%2$s', 'e20r-tracker' ),
							sprintf(
								'<a href="%s" title="Email the webmaster">',
								'mailto:' . get_option( 'admin_email' )
							),
							'</a>'
						);
						wp_die( $msg );
					}
					// Flush cache after saving the user's key
					clean_user_cache( $userId );
					
					Utilities::get_instance()->log( "New key generated for user {$userId}" );
					
				} catch ( EnvironmentIsBrokenException $ex ) {
					$msg = sprintf( __( 'Could not create your encryption key in a secure way. Please contact the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() );
					
					Utilities::get_instance()->log( $msg );
					
					// Flush cache after saving the user's key
					clean_user_cache( $userId );
					wp_die( $msg );
				}
			}
			
			Utilities::get_instance()->log( "Returning key for user {$userId}...");
			
			// Flush cache after processing the user's key
			clean_user_cache( $userId );
			
			return $key;
		} else {
			return null;
		}
	}
	
	/**
	 * Update the encryption key(s) and re-encrypt + save the data for the the user ID
	 *
	 * @param int   $user_id
	 * @param array $encrypted_data
	 * @param int   $record_id
	 *
	 * @return bool
	 *
	 * @throws
	 */
	public static function maybeUpdateEncryption( $user_id, $encrypted_data, $record_id ) {
		
		$has_updated = (bool) get_user_meta( $user_id, 'e20r_tracker_converted', true );
		
		if ( true === $has_updated ) {
			Utilities::get_instance()->log( "Old encryption is updated. Nothing to do" );
			
			return true;
		}
		
		$decrypted      = null;
		// Flush cache after saving the user's key
		clean_user_cache( $user_id );
		
		$legacy_key_hex = get_user_meta( $user_id, 'e20r_user_key', true );
		
		if ( empty( $legacy_key_hex ) ) {
			Utilities::get_instance()->log( "ERROR: Unable to locate the decryption key for user {$user_id}!" );
			delete_user_meta( $user_id, 'e20r_tracker_updated_key' );
			clean_user_cache( $user_id );
			
			return false;
		}
		
		$legacy_key = Encoding::hexToBin( $legacy_key_hex );
		Utilities::get_instance()->log( 'Legacy decryption key found' );
		
		try {
			
			Utilities::get_instance()->log( "Using legacy decryption method to read/convert data" );
			$data = Crypto::legacyDecrypt( Encoding::hexToBin( $encrypted_data ), $legacy_key );
			
		} catch ( WrongKeyOrModifiedCiphertextException $exception ) { // VERY IMPORTANT
			
			// Either:
			//   1. The ciphertext was modified by the attacker,
			//   2. The key is wrong, or
			//   3. $ciphertext is not a valid ciphertext or was corrupted.
			// Assume the worst.
			$msg = sprintf( __( 'Incorrect key supplied for user ID %d', 'e20r-tracker' ), $user_id );
			//$msg = sprintf( __( 'DANGER! DANGER! The encrypted information may have been modified while we were loading it. Please report this error to the Webmaster. Error: ', 'e20r-tracker' ), $exception->getMessage() );
			
			Utilities::get_instance()->log( $msg );
			wp_die( $msg );
			
		} catch ( EnvironmentIsBrokenException $ex ) {
			$msg = sprintf( __( 'There was a problem decrypting your information. Please report this error to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() );
			
			Utilities::get_instance()->log( $msg );
			wp_die( $msg );
		} catch ( \TypeError $ex ) {
			
			$msg = sprintf( __( 'Programming error. Please report this issue to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() );
			
			Utilities::get_instance()->log( $msg );
			wp_die( $msg );
			
		}
		
		if ( ! empty( $data ) ) {
			Utilities::get_instance()->log( "Have to unserialize the data..." );
			$decrypted = unserialize( $data );
		}
		
		if ( ! empty( $decrypted ) ) {
			
			try {
				$new_key = Key::createNewRandomKey();
				
			} catch ( \TypeError $ex ) {
				
				wp_die( sprintf( __( 'Programming error. Please report this issue to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() ) );
			} catch ( EnvironmentIsBrokenException $exception ) {
				wp_die( sprintf( __( 'While encrypting your information, we uncovered a problem. Please report this error to the webmaster. Error: ', 'e20r-tracker' ), $exception->getMessage() ) );
			}
			
			if ( ! empty( $new_key ) ) {
				
				try {
					$savable_key = $new_key->saveToAsciiSafeString();
					
				} catch ( EnvironmentIsBrokenException $ex ) {
					$msg = sprintf( __( 'Problem generating a savable encryption key. Please report this error to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() );
					
					Utilities::get_instance()->log( $msg );
					wp_die( $msg );
				}
				
				if ( empty( $savable_key ) || false === update_user_meta( $user_id, 'e20r_user_key', $savable_key ) ) {
					
					Utilities::get_instance()->log( "ERROR: Unable to save the new key for user {$user_id}" );
					clean_user_cache( $user_id );
					
					$msg = sprintf(
						__( 'Could not create an encryption key for you. Please %1$scontact the webmaster%2$s', 'e20r-tracker' ),
						sprintf(
							'<a href="%s" title="Email the webmaster">',
							'mailto:' . get_option( 'admin_email' )
						),
						'</a>'
					);
					wp_die( $msg );
				}
			}
			
			$encrypted = self::encryptData( $user_id, $decrypted );
			Utilities::get_instance()->log( "New encryption used for {$user_id}. Now attempting to save it" );
			$has_updated = self::saveEncryptedData( $user_id, $record_id, $encrypted );
			clean_user_cache( $user_id );
		}
		
		update_user_meta( $user_id, 'e20r_tracker_converted', $has_updated );
		
		return $has_updated;
	}
	
	/**
	 * Encrypt the provided data for the user ID (array)
	 *
	 * @param int   $user_id User to encrypt data for
	 * @param array $data    Data to encrypt
	 *
	 * @return string|array
	 */
	public static function encryptData( $user_id, $data ) {
		
		$controller = Tracker::getInstance();
		$enable     = (bool) $controller->loadOption( 'encrypt_surveys' );
		
		$key = self::getUserKey( $user_id );
		
		if ( $key === null ) {
			
			Utilities::get_instance()->log( "No key defined!" );
			
			return base64_encode( serialize( $data ) );
		}
		
		if ( empty( $key ) ) {
			
			Utilities::get_instance()->log( "Unable to load encryption engine/key. Using Base64... *sigh*" );
			
			return base64_encode( serialize( $data ) );
		}
		
		if ( true === $enable ) {
			
			Utilities::get_instance()->log( "Configured to encrypt data." );
			
			try {
				
				$ciphertext = Crypto::encrypt( serialize( $data ), $key );
				
				Utilities::get_instance()->log( "Data has been encrypted. Returning to calling function" );
				
				return $ciphertext; // Encoding::binToHex( $ciphertext );
				
			} catch ( \TypeError $ex ) {
				
				wp_die( sprintf( __( 'Programming error. Please report this issue to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() ) );
			} catch ( EnvironmentIsBrokenException $exception ) {
				wp_die( sprintf( __( 'While encrypting your information, we uncovered a problem. Please report this error to the webmaster. Error: ', 'e20r-tracker' ), $exception->getMessage() ) );
			}
		} else {
			return $data;
		}
	}
	
	/**
	 * Save the data to the user's record
	 *
	 * @param int    $user_id
	 * @param int    $record_id
	 * @param string $data
	 *
	 * @return string|array
	 */
	private static function saveEncryptedData( $user_id, $record_id, $data ) {
		
		global $wpdb;
		$Tables = Tables::getInstance();
		
		try {
			$table = $Tables->getTable( 'surveys' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to locate surveys table: " . $exception->getMessage() );
			
			return false;
		}
		
		try {
			Utilities::get_instance()->log( "Update data for record/user ({$record_id}/{$user_id})" );
			
			return (bool) $wpdb->update( $table, array( 'survey_data' => $data ), array( 'id' => $record_id ), array( '%s' ), array( '%d' ) );
		} catch ( EnvironmentIsBrokenException $exception ) {
			$msg = sprintf( __( 'Problem generating a savable user survey data. Please report this error to the webmaster. Error: %s', 'e20r-tracker' ), $exception->getMessage() );
			
			Utilities::get_instance()->log( $msg );
			wp_die( $msg );
		}
	}
}