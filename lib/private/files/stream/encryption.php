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

	/** @var \OC\Encryption\Util */
	protected $util;

	/** @var \OCP\Encryption\IEncryptionModule */
	protected $encryptionModule;

	/** @var \OC\Files\Storage\Storage */
	protected $storage;

	/** @var string */
	protected $internalPath;

	/** @var integer */
	protected $size;

	/** @var integer */
	protected $unencryptedSize;

	/** @var array */
	protected $header;

	/** @var string */
	protected $fullPath;

	/**
	 * header data returned by the encryption module, will be written to the file
	 * in case of a write operation
	 *
	 * @var array
	 */
	protected $newHeader;

	/**
	 * user who perform the read/write operation null for public access
	 *
	 *  @var string
	 */
	protected $uid;

	/** @var bool */
	protected $readOnly;

	/**
	 * Wraps a stream with the provided callbacks
	 *
	 * @param resource $source
	 * @param string $internalPath
	 * @param array $header
	 * @param sting $uid
	 * @param \OCP\Encryption\IEncryptionModule $encryptionModule
	 * @param \OC\Files\Storage\Storage $storage
	 * @param \OC\Encryption\Util $util
	 * @return resource
	 *
	 * @throws \BadMethodCallException
	 */
public static function wrap($source, $internalPath, array $header, $uid,
	\OCP\Encryption\IEncryptionModule $encryptionModule, \OC\Files\Storage\Storage $storage,
	\OC\Encryption\Util $util) {

		$this->storage = $storage;
		$this->internalPath = $internalPath;
		$this->encryptionModule = $encryptionModule;
		$this->header = $header;
		$this->uid = $uid;
		$this->util = $util;
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
		$this->loadContext('oc_encryption');
		$this->fullPath = $path;

		if (
			$mode === 'w'
			|| $mode === 'w+'
			|| $mode === 'wb'
			|| $mode === 'wb+'
		) {
			// We're writing a new file so start write counter with 0 bytes
			$this->size = 0;
			$this->unencryptedSize = 0;
			$this->readOnly = false;
		} else {
			$this->readOnly = true;
			$this->size = $this->storage->filesize($this->internalPath);
		}

		$sharePath = $path;
		if (!$this->storage->file_exists($this->internalPath)) {
			$sharePath = dirname($path);
		}

		$accessList = $this->util->getSharingUsersArray($sharePath);
		$this->newHeader = $this->encryptionModule->begin($path, $this->header, $accessList);

	}

	public function stream_read($count) {
		$data = parent::stream_read($count);
		$decrypted = $this->encryptionModule->decrypt($data, $this->uid);
		return $decrypted;
	}

	public function stream_write($data) {
		$encrypted = $this->encryptionModule->encrypt($data);
		return parent::stream_write($encrypted);
	}

	public function stream_close() {

		$remainingData = $this->encryptionModule->end($this->fullPath);
		if ($this->readOnly === false && $remainingData) {
			parent::stream_write($remainingData);
			// TODO what to do with unencrypted size?
		}

		return parent::stream_close();
	}

}
