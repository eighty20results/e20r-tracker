<?php
/**
 * Copyright (c) $today.year. - Eighty / 20 Results by Wicked Strong Chicks.
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

use Defuse\Crypto\Encoding;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;

class E20R_Crypto {
	
	public static function getUserKey( $userId = null ) {
		
		$controller = e20rTracker::getInstance();
		
		global $post;
		global $current_user;
		
		if ( ! is_user_logged_in() ) {
			
			return null;
		}
		
		if ( ( $current_user->ID != 0 ) && ( null === $userId ) ) {
			
			$userId = $current_user->ID;
		}
		
		dbg( "E20R_Crypto::getUserKey() - Test if user ({$userId}) can access their AES key based on the post {$post->ID}." );
		
		if ( true === $controller->hasAccess( $userId, $post->ID ) ) {
			dbg( 'E20R_Crypto::getUserKey() - User is permitted to access their AES key.' );
			
			try {
				$key = Encoding::hexToBin( get_user_meta( $userId, 'e20r_user_key', true ) );
			} catch ( BadFormatException $exception ) {
				wp_die( sprintf( __( "The format of the encryption key located for your user is invalid. Please contact the webmaster. Error: %s", "e20r-tracker" ), $exception->getMessage() ) );
			} catch ( EnvironmentIsBrokenException $exception ) {
				wp_die( sprintf( __( "There is something wrong with the encryption/decryption of your data. Please contact the webmaster. Error: %s", "e20r-tracker" ), $exception->getMessage() ) );
			}
			
			// dbg( $key );
			
			if ( empty( $key ) ) {
				
				try {
					
					dbg( "E20R_Crypto::getUserKey() - Generating a new key for user {$userId}" );
					$key = Key::createNewRandomKey();
					
					// WARNING: Do NOT encode $key with bin2hex() or base64_encode(),
					// they may leak the key to the attacker through side channels.
					
					if ( false === update_user_meta( $userId, 'e20r_user_key', Encoding::binToHex( $key ) ) ) {
						
						dbg( "E20R_Crypto::getUserKey() - ERROR: Unable to save the key for user {$userId}" );
						
						return null;
					}
					
					dbg( "E20R_Crypto::getUserKey() - New key generated for user {$userId}" );
				} catch ( EnvironmentIsBrokenException $ex ) {
					
					wp_die( sprintf( __( 'Could not create your encryption key in a secure way. Please contact the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() ) );
				}
			}
			
			dbg( "E20R_Crypto::getUserKey() - Returning key for user {$userId}" );
			
			return $key;
		} else {
			return null;
		}
	}
	
	/**
	 * @param $data
	 * @param $key
	 *
	 * @return string
	 */
	public static function encryptData( $data, $key ) {
		
		$controller = e20rTracker::getInstance();
		$enable     = (bool) $controller->loadOption( 'encrypt_surveys' );
		
		if ( $key === null ) {
			
			dbg( "E20R_Crypto::encryptData() - No key defined!" );
			
			return base64_encode( $data );
		}
		
		if ( empty( $key ) ) {
			
			dbg( "E20R_Crypto::encryptData() - Unable to load encryption engine/key. Using Base64... *sigh*" );
			
			return base64_encode( $data );
		}
		
		if ( true === $enable ) {
			
			dbg( "E20R_Crypto::encryptData() - Configured to encrypt data." );
			
			try {
				
				$ciphertext = Crypto::encrypt( $data, $key );
				
				return Encoding::binToHex( $ciphertext );
			} catch ( TypeError $ex ) {
				
				wp_die( sprintf( __( 'Programming error. Please report this issue to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() ) );
			} catch ( EnvironmentIsBrokenException $exception ) {
				wp_die( sprintf( __( 'While encrypting your information, we uncovered a problem. Please report this error to the webmaster. Error: ', 'e20r-tracker' ), $exception->getMessage() ) );
			}
		} else {
			return $data;
		}
	}
	
	public static function decryptData( $encData, $key, $encrypted = null ) {
		
		$controller = e20rTracker::getInstance();
		if ( is_null( $encrypted ) ) {
			
			$encrypted = (bool) $controller->loadOption( 'encrypt_surveys' );
		}
		
		dbg( "E20R_Crypto::decryptData() - Encryption is " . ( $encrypted ? 'enabled' : 'disabled' ) );
		
		if ( ( $key === null ) || ( false === $encrypted ) ) {
			
			dbg( "E20R_Crypto::decryptData() - No decryption key - or encryption is disabled: {$encrypted}" );
			
			return base64_decode( $encData );
		}
		
		try {
			
			dbg( "E20R_Crypto::decryptData() - Attempting to decrypt data..." );
			
			$data      = Encoding::hexToBin( $encData );
			$decrypted = Crypto::decrypt( $data, $key );
			
			return $decrypted;
		} catch ( WrongKeyOrModifiedCiphertextException $exception ) { // VERY IMPORTANT
			// Either:
			//   1. The ciphertext was modified by the attacker,
			//   2. The key is wrong, or
			//   3. $ciphertext is not a valid ciphertext or was corrupted.
			// Assume the worst.
			wp_die( sprintf( __( 'DANGER! DANGER! The encrypted information may have been modified while we were loading it. Please report this error to the Webmaster. Error: ', 'e20r-tracker' ), $exception->getMessage() ) );
		} catch ( EnvironmentIsBrokenException $ex ) {
			
			wp_die( sprintf( __( 'There was a problem decrypting your information. Please report this error to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() ) );
		} catch ( TypeError $ex ) {
			
			wp_die( sprintf( __( 'Programming error. Please report this issue to the webmaster. Error: %s', 'e20r-tracker' ), $ex->getMessage() ) );
		} catch ( BadFormatException $exception ) {
			wp_die( sprintf( __( "The format of the encryption key located for your user is invalid. Please contact the webmaster. Error: %s", "e20r-tracker" ), $exception->getMessage() ) );
		}
		
	}
}