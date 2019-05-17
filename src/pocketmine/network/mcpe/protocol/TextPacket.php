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


use pocketmine\network\mcpe\handler\SessionHandler;
use pocketmine\network\mcpe\NetworkBinaryStream;
use function count;

class TextPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::TEXT_PACKET;

	public const TYPE_RAW = 0;
	public const TYPE_CHAT = 1;
	public const TYPE_TRANSLATION = 2;
	public const TYPE_POPUP = 3;
	public const TYPE_JUKEBOX_POPUP = 4;
	public const TYPE_TIP = 5;
	public const TYPE_SYSTEM = 6;
	public const TYPE_WHISPER = 7;
	public const TYPE_ANNOUNCEMENT = 8;
	public const TYPE_JSON = 9;

	/** @var int */
	public $type;
	/** @var bool */
	public $needsTranslation = false;
	/** @var string */
	public $sourceName;
	/** @var string */
	public $message;
	/** @var string[] */
	public $parameters = [];
	/** @var string */
	public $xboxUserId = "";
	/** @var string */
	public $platformChatId = "";

	protected function decodePayload(NetworkBinaryStream $in) : void{
		$this->type = $in->getByte();
		$this->needsTranslation = $in->getBool();
		switch($this->type){
			case self::TYPE_CHAT:
			case self::TYPE_WHISPER:
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_ANNOUNCEMENT:
				$this->sourceName = $in->getString();
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
			case self::TYPE_JSON:
				$this->message = $in->getString();
				break;

			case self::TYPE_TRANSLATION:
			case self::TYPE_POPUP:
			case self::TYPE_JUKEBOX_POPUP:
				$this->message = $in->getString();
				$count = $in->getUnsignedVarInt();
				for($i = 0; $i < $count; ++$i){
					$this->parameters[] = $in->getString();
				}
				break;
		}

		$this->xboxUserId = $in->getString();
		$this->platformChatId = $in->getString();
	}

	protected function encodePayload(NetworkBinaryStream $out) : void{
		$out->putByte($this->type);
		$out->putBool($this->needsTranslation);
		switch($this->type){
			case self::TYPE_CHAT:
			case self::TYPE_WHISPER:
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_ANNOUNCEMENT:
				$out->putString($this->sourceName);
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
			case self::TYPE_JSON:
				$out->putString($this->message);
				break;

			case self::TYPE_TRANSLATION:
			case self::TYPE_POPUP:
			case self::TYPE_JUKEBOX_POPUP:
				$out->putString($this->message);
				$out->putUnsignedVarInt(count($this->parameters));
				foreach($this->parameters as $p){
					$out->putString($p);
				}
				break;
		}

		$out->putString($this->xboxUserId);
		$out->putString($this->platformChatId);
	}

	public function handle(SessionHandler $handler) : bool{
		return $handler->handleText($this);
	}
}
