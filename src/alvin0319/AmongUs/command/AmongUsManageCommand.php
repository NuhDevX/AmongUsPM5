<?php

/*
 *      _                                _   _
 *    / \   _ __ ___   ___  _ __   __ _| | | |___
 *   / _ \ | '_ ` _ \ / _ \| '_ \ / _` | | | / __|
 *  / ___ \| | | | | | (_) | | | | (_| | |_| \__ \
 * /_/   \_\_| |_| |_|\___/|_| |_|\__, |\___/|___/
 *                                |___/
 *
 * A PocketMine-MP plugin that implements AmongUs
 *
 * Copyright (C) 2020 - 2021 alvin0319
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @author alvin0319
 */

declare(strict_types=1);

namespace alvin0319\AmongUs\command;

use alvin0319\AmongUs\AmongUs;
use alvin0319\AmongUs\EventListener;
use alvin0319\AmongUs\entity\VentEntity:
use alvin0319\AmongUs\form\creation\AmongUsGameCreateForm;
use alvin0319\SimpleMapRenderer\data\MapData;
use alvin0319\SimpleMapRenderer\item\ItemPlus;
use alvin0319\SimpleMapRenderer\item\FilledMap;
use alvin0319\SimpleMapRenderer\MapFactory;
use alvin0319\SimpleMapRenderer\util\MapUtil;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;

use function is_numeric;
use function trim;

class AmongUsManageCommand extends Command{

	public function __construct(){
		parent::__construct("amongusmanage", "Manage AmongUs game settings", ["aum", "amum"]);
		$this->setPermission("amongus.command.manage");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(!$sender instanceof Player){
			$sender->sendMessage(AmongUs::$prefix . "Perintah ini hanya dapat di jalankan di game.");
			return false;
		}
		switch($args[0] ?? "x"){
			case "creategame":
				$sender->sendForm(new AmongUsGameCreateForm());
				break;
			case "createobjective":
				break;
			case "setmapimage":
				if(trim($args[1] ?? "") === ""){
					$sender->sendMessage(AmongUs::$prefix . "Usage: /{$commandLabel} setmapimage [gameId]");
					return false;
				}
				if(!is_numeric($args[1]) || (int) $args[1] < 0){
					$sender->sendMessage(AmongUs::$prefix . "Usage: /{$commandLabel} setmapimage [gameId]");
					return false;
				}
				$game = AmongUs::getInstance()->getGame((int) $args[1]);
				if($game === null){
					$sender->sendMessage(AmongUs::$prefix . "Game dengan id {$args[1]} gagal di temukan.");
					return false;
				}
				$mapData = new MapData(MapFactory::getInstance()->nextId(), [], false, $sender->floor());
				$colors = [];
				for($x = 0; $x < 128; $x++){
					for($y = 0; $y < 128; $y++){
						$realX = $sender->getPosition()->getFloorX() - 64 + $x;
						$realY = $sender->getPosition()->getFloorZ() - 64 + $y;
						$maxY = $sender->getWorld()->getHighestBlockAt($realX, $realY);
						$block = $sender->getWorld()->getBlockAt($realX, $maxY, $realY);
						$color = MapUtil::getMapColorByBlock($block);
						$colors[$y][$x] = $color;
					}
				}
				$mapData->setColors($colors);
				MapFactory::getInstance()->registerData($mapData);
				/** @var FilledMap $item */
				$item = ItemPlus::FILLED_MAP();
				$item->setMapId($mapData->getMapId());
				$game->setMapId($mapData->getMapId());
				$sender->getInventory()->addItem($item);
				$sender->sendMessage(AmongUs::$prefix . "Berhasil mengsetup gayme!");
				break;
			case "createvents":
				if(trim($args[1] ?? "") === ""){
					$sender->sendMessage(AmongUs::$prefix . "Usage: /{$commandLabel} createvents [gameId]");
					return false;
				}
				if(!is_numeric($args[1])){
					$sender->sendMessage(AmongUs::$prefix . "Usage: /{$commandLabel} createvents [gameId]");
					return false;
				}
				$gameId = (int) $args[1];
				EventListener::$interactQueue[$sender->getName()] = function(PlayerInteractEvent $event) use ($gameId) : void{
					$player = $event->getPlayer();
					$block = $event->getBlock();

					$game = AmongUs::getInstance()->getGame($gameId);
					if($game === null){
						$player->sendMessage(AmongUs::$prefix . "Game dengan id {$gameId} gagal di temukan.");
						return;
					}
					$game->addVent($block);
					$player->sendMessage(AmongUs::$prefix . "berhasil bikinin vent.");
				};
				break;
			case "spawnvent":
				$pos = $sender->getPosition();
				$skin = AmongUs::getInstance()->getVentSkin();
				if(!$pos->getWorld()->isChunkLoaded($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)){
					$pos->getWorld()->loadChunk($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4);
				}
				$entity = new VentEntity(Location::fromObject($pos), $skin, null);
				$entity->setNoClientPredictions();
				$entity->setNameTag("VENT");
				$entity->setNameTagAlwaysVisible(false);
				$entity->spawnToAll();
				$sender->sendMessage("Berhasil memunculkan Vent!");
				break;
			default:
				$sender->sendMessage(AmongUs::$prefix . "/{$commandLabel} setmapimage [gameId]");
				$sender->sendMessage(AmongUs::$prefix . "/{$commandLabel} creategame");
				$sender->sendMessage(AmongUs::$prefix . "/{$commandLabel} createobjective");
				$sender->sendMessage(AmongUs::$prefix . "/{$commandLabel} createvents");
				$sender->sendMessage(AmongUs::$prefix . "/{$commandLabel} spawnvent");
		}
		return true;
	}
}
