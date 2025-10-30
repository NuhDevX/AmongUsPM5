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

namespace alvin0319\AmongUs\form;

use alvin0319\AmongUs\AmongUs;
use pocketmine\form\Form;
use pocketmine\player\Player;

use function is_int;

class AmongUsMainForm implements Form{

	public function jsonSerialize(){
		return [
			"type" => "form",
			"title" => "§cAmong§bUs §cdi Minecraft!",
			"content" => "",
			"buttons" => [
				["text" => "§aMain"],
				["text" => "§aInfo"],
				["text" => "§cKeluar"]
			]
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if(!is_int($data)){
			return;
		}
		if($data === null)
			return;
	    }
	
		switch($data){
			case 0:
				$game = AmongUs::getInstance()->getAvailableGame($player);
				if($game === null){
					$player->sendMessage(AmongUs::$prefix . "Game saat ini tidak tersedia, silahkan coba lagi nanti");
					return;
				}
				$game->addPlayer($player);
				break;
			case 1:
				$lines = "§b------------------------------------------";
				$space = " ";
				$player->sendMessage($lines . "\n" . "§8-=[§a+§8]§b=-§l§cAmong§bUs §r§adi §aMCPE §b-=§8[§a+§8]=-" . "\n" . "\n" . $space . $space . $space . $space . $space . " §6Intro:" . "\n" . "§cAmong§bUs §eadalah permainan kerja sama tim dan pengkhianatan." . "\n" . "§ePemain bisa menjadi Crewmates atau Impostor." . "\n" . $space . $space . $space . $space . $space . " §6Role:" . "\n" . " — §bCrewmate: §eSelesaikan semua tugas untuk menang." . "\n" . " — §cImposter: §eBunuh semua Crewmate untuk menang." . "\n" . $space . $space . $space . $space . $space . " §6Info:" . "\n" . "§eSelama Rapat pastikan untuk mendiskusikan siapa yang akan dipilih keluar. (singkirkan penipu itu)" . "\n" . "§ePemain memiliki akses ke peta pribadi untuk membantu menavigasi peta" . "\n" . "\n" . "§8-=[§a+§8]=- [§aSelamat Bermain§8] -=[§a+§8]=-" . "\n" . $lines);
				break;
		}
	}
}
