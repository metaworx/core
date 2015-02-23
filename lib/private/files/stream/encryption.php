<?php

/**
 * ownCloud - Encryption stream wrapper
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Files\Stream;

use Icewind\Streams\Wrapper;

class Encryption extends Wrapper {

	/**
	 * Wraps a stream with the provided callbacks
	 *
	 * @param resource $source
	 * @return resource
	 *
	 * @throws \BadMethodCallException
	 */
	public static function wrap($source) {
		$context = stream_context_create(array(
			'callback' => array(
				'source' => $source
			)
		));
		return Wrapper::wrapSource($source, $context, 'oc_encryption', 'OC\Files\Stream\Encryption');
	}

	public function dir_opendir($path, $options) {
		return true;
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		// TODO implement stream open
	}

	public function stream_read($count) {
		$result = parent::stream_read($count);

		// TODO decrypt data

		return $result;
	}

	public function stream_write($data) {

		// TODO encrypt data

		return parent::stream_write($data);
	}

	public function stream_close() {

		// todo implement stream_close

		return parent::stream_close();
	}

}
