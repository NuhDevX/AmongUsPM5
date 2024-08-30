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

namespace alvin0319\AmongUs;

use alvin0319\AmongUs\character\Crewmate;
use alvin0319\AmongUs\character\Imposter;
use alvin0319\AmongUs\entity\DeadPlayerEntity;
use alvin0319\AmongUs\form\game\VoteImposterForm;
use alvin0319\AmongUs\game\Game;
use alvin0319\AmongUs\object\ObjectiveQueue;
use Closure;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\player\Player;

use function strpos;
use function substr;

class EventListener implements Listener{
	/** @var Closure[] */
	public static $interactQueue = [];

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		switch(true){
			case ($packet instanceof InventoryTransactionPacket):
				if($packet->transactionType !== InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
					return;
				}
				$entity = $player->getServer()->findEntity($packet->trData->entityRuntimeId);
				if(!$entity instanceof DeadPlayerEntity){
					return;
				}
				$entity->interact($player);
				break;
		}
	}

	public function onEntityDamage(EntityDamageByEntityEvent $event) : void{
		$victim = $event->getDamager();
		$entity = $event->getEntity();
		if(!$victim instanceof Player || !$entity instanceof Player){
			return;
		}
		if(AmongUs::getInstance()->getGameByPlayer($victim) === null || AmongUs::getInstance()->getGameByPlayer($entity) === null || AmongUs::getInstance()->getGameByPlayer($victim) !== AmongUs::getInstance()->getGameByPlayer($entity)){
			return;
		}
		$game = AmongUs::getInstance()->getGameByPlayer($victim);
		$victimCharacter = $game->getCharacter($victim);
		$entityCharacter = $game->getCharacter($entity);
		if($victimCharacter === null || $entityCharacter === null){
			return;
		}
		if(!$victimCharacter instanceof Imposter){
			return;
		}
		if(!$entityCharacter instanceof Crewmate){
			return;
		}
		if(!$game->canKillPlayer($victim, $entity)){
			return;
		}
		$game->killPlayer($entity, $victim);
	}

	/**
	 * @param PlayerChatEvent $event
	 *
	 * @priority HIGHEST
	 */
	public function onPlayerChat(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();
		$message = $event->getMessage();

		$game = AmongUs::getInstance()->getGameByPlayer($player);
		if($game === null){
			return;
		}
		if(!$game->isRunning()){
			return;
		}
		if($game->isDead($player)){
			$game->broadcastMessageToDead("§6[§r§n§7Pesan Hantu§r§6]§7 " . $player->getName() . " §8>§r " . $message);
			return;
		}
		if($game->isEmergencyRunning()){
			$game->broadcastMessage("§8[§c!§8]§r " . $player->getName() . " > " . $message);
			$event->cancel();
			return;
		}
		$event->cancel();
		$player->sendMessage(AmongUs::$prefix . "Anda tidak dapat berbicara pada waktu yang tidak darurat.");
	}

	/**
	 * @param PlayerCommandPreprocessEvent $event
	 *
	 * @priority HIGHEST
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		$player = $event->getPlayer();
		$message = $event->getMessage();
		if(substr($message, 0, 1) === "/" || substr($message, 0, 2) === "./"){
			$game = AmongUs::getInstance()->getGameByPlayer($player);
			if($game === null){
				return;
			}
			if(!$game->isRunning()){
				return;
			}
			if(strpos($message, "/amu") !== false){
				return;
			}
			$event->cancel();
			$player->sendMessage(AmongUs::$prefix . "Anda tidak menjalankan command selama permainan.");
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();

		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}

		$block = $event->getBlock();

		if(isset(self::$interactQueue[$player->getName()])){
			(self::$interactQueue[$player->getName()])($event);
			unset(self::$interactQueue[$player->getName()]);
			return;
		}

		$game = AmongUs::getInstance()->getGameByPlayer($player);
		if($game === null){
			return;
		}
		$character = $game->getCharacter($player);
		if($character === null){
			return;
		}
		if(($object = $game->getObjectiveByPos($block->asPosition())) !== null){
			$object->onInteract($player);
			return;
		}
		$item = $event->getItem();
		/*
		if($item->getId() !== ItemIds::CLOCK){
			return;
		}
		if(!$game->isRunning()){
			return;
		}
		if(!$game->isEmergencyRunning()){
			return;
		}
		if($game->isDead($player)){
			return;
		}
		$player->sendForm(new VoteImposterForm($game));
		*/
		switch(true){
			case $item->getTypeId() === ItemTypeIds::CLOCK && $item->getDamage() === 10:
				if(!$game->isRunning()){
					return;
				}
				if(!$game->isEmergencyRunning()){
					return;
				}
				if($game->isDead($player)){
					return;
				}
				$player->sendForm(new VoteImposterForm($game));
				break;
			case $item->getTypeId() === ItemTypeIds::COMPASS && $item->getDamage() === 10:
				if(!$game->isRunning()){
					return;
				}
				if($game->isDead($player)){
					return;
				}
				if(!$character instanceof Imposter){
					return;
				}
				$game->spawnVents();
				$player->sendMessage(AmongUs::$prefix . "Semua ventilasi berhasil di munculkan!.");
		}
	}

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$game = AmongUs::getInstance()->getGameByPlayer($player);
		if($game !== null){
			$game->removePlayer($player);
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$game = AmongUs::getInstance()->getGameByPlayer($player);
		if($game !== null){
			$game->removePlayer($player);
		}
	}
}
