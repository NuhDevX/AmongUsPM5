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

namespace alvin0319\AmongUs\form\creation;

use alvin0319\AmongUs\AmongUs;
use alvin0319\AmongUs\EventListener;
use alvin0319\AmongUs\game\Game;
use alvin0319\AmongUs\objective\ObjectiveQueue;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\form\Form;
use pocketmine\player\Player;

use function count;
use function is_array;
use function is_int;
use function is_numeric;

class AmongUsGameCreateForm implements Form{

	public function jsonSerialize() : array{
		return [
			"type" => "custom_form",
			"title" => "pengaturan Game",
			"content" => [
				[
					"type" => "dropdown",
					"text" => "tipe map?",
					"options" => ["Skeld", "Polus"]
				],
				[
					"type" => "input",
					"text" => "Maximal imposter?",
					"default" => (string) Game::DEFAULT_SETTINGS[Game::SETTING_MAX_IMPOSTERS]
				],
				[
					"type" => "input",
					"text" => "Maximal crewmate?",
					"default" => (string) Game::DEFAULT_SETTINGS[Game::SETTING_MAX_CREW]
				],
				[
					"type" => "input",
					"text" => "waktu darurat? (detik) (Percakapan rapat darurat dan laporan)",
					"default" => (string) Game::DEFAULT_SETTINGS[Game::SETTING_EMERGENCY_TIME]
				],
				[
					"type" => "input",
					"text" => "Jumlah panggilan darurat?",
					"default" => (string) Game::DEFAULT_SETTINGS[Game::SETTING_EMERGENCY_PRESS]
				],
				[
					"type" => "input",
					"text" => "Jeda membunuh?",
					"default" => (string) Game::DEFAULT_SETTINGS[Game::SETTING_KILL_COOLDOWN]
				],
				[
					"type" => "input",
					"text" => "Minimum pemain untuk memulai permainan?",
					"default" => (string) Game::DEFAULT_SETTINGS[Game::SETTING_MIN_PLAYER_TO_START]
				],
				[
					"type" => "input",
					"text" => "Waktu nunggu?",
					"default" => (string) Game::DEFAULT_SETTINGS[Game::SETTING_WAIT_SECOND]
				]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if(!is_array($data) || count($data) !== 8){
			return;
		}
		[$type, $maxImposters, $maxCrews, $emergencyTime, $emergencyCall, $coolDown, $minPlayer, $waitTime] = $data;
		if(!is_int($type)){
			return;
		}
		if($data === null) {
			return;
		}
		if(!is_numeric($maxImposters) || ($maxImposters = (int) $maxImposters) < 1){
			$player->sendMessage(AmongUs::$prefix . "Jumlah maksimal impostor harus lebih besar dari 1.");
			return;
		}
		if(!is_numeric($maxCrews) || ($maxCrews = (int) $maxCrews) < 1){
			$player->sendMessage(AmongUs::$prefix . "Jumlah maksimal crewmate harus lebih tinggi dari 1.");
			return;
		}
		if($maxImposters > $maxCrews){
			$player->sendMessage(AmongUs::$prefix . "Jumlah maksimal crewmate harus lebih tinggi dari Impostor.");
			return;
		}
		if(!is_numeric($emergencyTime) || ($emergencyTime = (int) $emergencyTime) < 60){
			$player->sendMessage(AmongUs::$prefix . "Waktu darurat harus lebih tinggi dari 60. (1 menit)");
			return;
		}
		if(!is_numeric($emergencyCall) || ($emergencyCall = (int) $emergencyCall) < 1){
			$player->sendMessage(AmongUs::$prefix . "Jumlah panggilan darurat harus lebih tinggi dari 1.");
			return;
		}
		if(!is_numeric($coolDown) || ($coolDown = (int) $coolDown) < 1){
			$player->sendMessage(AmongUs::$prefix . "Waktu jeda pembunuhan harus lebih tinggi dari 1.");
			return;
		}
		if(!is_numeric($minPlayer) || ($minPlayer = (int) $minPlayer) < 1){
			$player->sendMessage(AmongUs::$prefix . "Jumlah pemain minimum harus lebih tinggi dari 1.");
			return;
		}
		if(!is_numeric($waitTime) || ($waitTime = (int) $waitTime) < 10){
			$player->sendMessage(AmongUs::$prefix . "Waktu tunggu harus lebih tinggi dari 10.");
			return;
		}
		ObjectiveQueue::$createQueue[$player->getName()] = [
			$type,
			$maxImposters,
			$maxCrews,
			$emergencyTime,
			$emergencyCall,
			$coolDown,
			$minPlayer,
			$waitTime
		];
		EventListener::$interactQueue[$player->getName()] = function(PlayerInteractEvent $event) use ($player) : void{
			$block = $event->getBlock();
			[
				$type,
				$maxImposters,
				$maxCrews,
				$emergencyTime,
				$emergencyCall,
				$coolDown,
				$minPlayer,
				$waitTime
			] = ObjectiveQueue::$createQueue[$player->getName()];

			$game = new Game(AmongUs::getInstance()->getNextId(), $block->getWorld()->getFolderName(), $block->getPosition()->asPosition(), [], -1, [], [
				Game::SETTING_WAIT_SECOND => $waitTime,
				Game::SETTING_MIN_PLAYER_TO_START => $minPlayer,
				Game::SETTING_KILL_COOLDOWN => $coolDown,
				Game::SETTING_EMERGENCY_PRESS => $emergencyCall,
				Game::SETTING_EMERGENCY_TIME => $emergencyTime,
				Game::SETTING_MAX_CREW => $maxCrews,
				Game::SETTING_MAX_IMPOSTERS => $maxImposters
			]);

			AmongUs::getInstance()->registerGame($game);
			$player->sendMessage(AmongUs::$prefix . "Game berhasil dibuat dengan id ". {$game->getId()});
			unset(ObjectiveQueue::$createQueue[$player->getName()]);
		};
		$player->sendMessage(AmongUs::$prefix . "Sentuh/Klik kanan blok untuk mengatur titik spawn untuk peta permainan.");
	}
}
