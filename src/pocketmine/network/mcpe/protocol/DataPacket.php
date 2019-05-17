<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\BadPacketException;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\Utils;
use function bin2hex;
use function get_class;
use function is_object;
use function is_string;
use function method_exists;

abstract class DataPacket implements Packet{

	public const NETWORK_ID = 0;

	/** @var int */
	public $senderSubId = 0;
	/** @var int */
	public $recipientSubId = 0;

	public function pid() : int{
		return $this::NETWORK_ID;
	}

	public function getName() : string{
		return (new \ReflectionClass($this))->getShortName();
	}

	public function canBeSentBeforeLogin() : bool{
		return false;
	}

	/**
	 * Returns whether the packet may legally have unread bytes left in the buffer.
	 * @return bool
	 */
	public function mayHaveUnreadBytes() : bool{
		return false;
	}

	/**
	 * @param NetworkBinaryStream $in
	 *
	 * @throws BadPacketException
	 */
	final public function decode(NetworkBinaryStream $in) : void{
		$in->rewind();
		try{
			$this->decodeHeader($in);
			$this->decodePayload($in);
		}catch(BinaryDataException | BadPacketException $e){
			throw new BadPacketException($this->getName() . ": " . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * @param NetworkBinaryStream $in
	 *
	 * @throws BinaryDataException
	 * @throws \UnexpectedValueException
	 */
	protected function decodeHeader(NetworkBinaryStream $in) : void{
		$pid = $in->getUnsignedVarInt();
		if($pid !== static::NETWORK_ID){
			//TODO: this means a logical error in the code, but how to prevent it from happening?
			throw new \UnexpectedValueException("Expected " . static::NETWORK_ID . " for packet ID, got $pid");
		}
	}

	/**
	 * Decodes the packet body, without the packet ID or other generic header fields.
	 *
	 * @param NetworkBinaryStream $in
	 *
	 * @throws BadPacketException
	 * @throws BinaryDataException
	 */
	abstract protected function decodePayload(NetworkBinaryStream $in) : void;

	final public function encode(NetworkBinaryStream $out) : void{
		$out->reset();
		$this->encodeHeader($out);
		$this->encodePayload($out);
	}

	protected function encodeHeader(NetworkBinaryStream $out) : void{
		$out->putUnsignedVarInt(static::NETWORK_ID);
	}

	/**
	 * Encodes the packet body, without the packet ID or other generic header fields.
	 *
	 * @param NetworkBinaryStream $out
	 */
	abstract protected function encodePayload(NetworkBinaryStream $out) : void;

	public function __debugInfo(){
		$data = [];
		foreach($this as $k => $v){
			if($k === "buffer" and is_string($v)){
				$data[$k] = bin2hex($v);
			}elseif(is_string($v) or (is_object($v) and method_exists($v, "__toString"))){
				$data[$k] = Utils::printable((string) $v);
			}else{
				$data[$k] = $v;
			}
		}

		return $data;
	}

	public function __get($name){
		throw new \Error("Undefined property: " . get_class($this) . "::\$" . $name);
	}

	public function __set($name, $value){
		throw new \Error("Undefined property: " . get_class($this) . "::\$" . $name);
	}
}
