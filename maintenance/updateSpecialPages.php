<?php
/**
 * Run this script periodically if you have miser mode enabled, to refresh the
 * caches
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 */

require_once( dirname( __FILE__ ) . '/Maintenance.php' );

// TODO jobqueue remove this when migrated UpdateSpecialPagesTask is verified production-safe
class UpdateSpecialPages extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'list', 'List special page names' );
		$this->addOption( 'only', 'Only update "page". Ex: --only=BrokenRedirects', false, true );
		$this->addOption( 'override', 'Also update pages that have updates disabled' );
	}

	public function execute() {
		global $IP, $wgSpecialPageCacheUpdates, $wgQueryPages, $wgQueryCacheLimit, $wgDisableQueryPageUpdate;

		$dbw = wfGetDB( DB_MASTER );

		foreach ( $wgSpecialPageCacheUpdates as $special => $call ) {
			if ( !is_callable( $call ) ) {
				$this->error( "Uncallable function $call!" );
				continue;
			}
			$t1 = explode( ' ', microtime() );
			call_user_func( $call, $dbw );
			$t2 = explode( ' ', microtime() );
			$this->output( sprintf( '%-30s ', $special ) );
			$elapsed = ( $t2[ 0 ] - $t1[ 0 ] ) + ( $t2[ 1 ] - $t1[ 1 ] );
			$hours = intval( $elapsed / 3600 );
			$minutes = intval( $elapsed % 3600 / 60 );
			$seconds = $elapsed - $hours * 3600 - $minutes * 60;
			if ( $hours ) {
				$this->output( $hours . 'h ' );
			}
			if ( $minutes ) {
				$this->output( $minutes . 'm ' );
			}
			$this->output( sprintf( "completed in %.2fs\n", $seconds ) );
			# Wait for the slave to catch up
			wfWaitForSlaves();
		}

		// This is needed to initialise $wgQueryPages
		require_once( "$IP/includes/QueryPage.php" );

		foreach ( $wgQueryPages as $page ) {
			list( $class, $special ) = $page;
			$limit = isset( $page[ 2 ] ) ? $page[ 2 ] : null;

			# --list : just show the name of pages
			if ( $this->hasOption( 'list' ) ) {
				$this->output( "$special\n" );
				continue;
			}

			if ( !$this->hasOption( 'override' ) && $wgDisableQueryPageUpdate && in_array( $special, $wgDisableQueryPageUpdate ) ) {
				$this->output( sprintf( "%-30s disabled\n", $special ) );
				continue;
			}

			$specialObj = SpecialPageFactory::getPage( $special );
			if ( !$specialObj ) {
				$this->output( "No such special page: $special\n" );
				\Wikia\Logger\WikiaLogger::instance()->error( "No such special page: $special\n" );
				continue;
			}
			if ( $specialObj instanceof QueryPage ) {
				$queryPage = $specialObj;
			} else {
				if ( !class_exists( $class ) ) {
					$file = $specialObj->getFile();
					require_once( $file );
				}
				$queryPage = new $class;
			}

			if ( !$this->hasOption( 'only' ) || $this->getOption( 'only' ) == $queryPage->getName() ) {
				$this->output( sprintf( '%-30s ', $special ) );
				if ( $queryPage->isExpensive() ) {
					$t1 = explode( ' ', microtime() );
					# Do the query
					$num = $queryPage->recache( $limit === null ? $wgQueryCacheLimit : $limit );
					$t2 = explode( ' ', microtime() );
					if ( $num === false ) {
						$this->output( "FAILED: database error\n" );
					} else {
						$this->output( "got $num rows in " );

						$elapsed = ( $t2[ 0 ] - $t1[ 0 ] ) + ( $t2[ 1 ] - $t1[ 1 ] );
						$hours = intval( $elapsed / 3600 );
						$minutes = intval( $elapsed % 3600 / 60 );
						$seconds = $elapsed - $hours * 3600 - $minutes * 60;
						if ( $hours ) {
							$this->output( $hours . 'h ' );
						}
						if ( $minutes ) {
							$this->output( $minutes . 'm ' );
						}
						$this->output( sprintf( "%.2fs\n", $seconds ) );
					}

					# Commit the results
					$res = $dbw->commit( __METHOD__ );

					# try to reconnect to the master
					if ( $res === false ) {
						Wikia\Logger\WikiaLogger::instance()->error( 'updateSpecialPages - commit failed, reconnecting...' );
						$this->output( "\n" );
						do {
							$this->error( "Connection failed, reconnecting in 10 seconds..." );
							sleep( 10 );
						} while ( !$dbw->ping() );
						$this->output( "Reconnected\n\n" );
					}
					# Wait for the slave to catch up
					wfWaitForSlaves();

					// SUS-832: Run post-transaction hook once the DB transactions are finished
					\Hooks::run( 'AfterUpdateSpecialPages', [ $queryPage ] );
				} else {
					$this->output( "cheap, skipped\n" );
				}
			}
		}
	}
}

$maintClass = "UpdateSpecialPages";
require_once( RUN_MAINTENANCE_IF_MAIN );
