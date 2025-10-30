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
use alvin0319\AmongUs\form\AmongUsMainForm;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\player\Player;

class AmongUsCommand extends PluginCommand{

	public function __construct(){
		parent::__construct("amongus", "Open the AmongUs Game UI", ["au", "amu"]);
		$this->setPermission("amongus.command");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(!$sender instanceof Player){
			$sender->sendMessage(AmongUs::$prefix . "Hanya bisa di jalankan di dalam gayme.");
			return false;
		}
		switch($args[0] ?? "x"){
			case "join":
				if(AmongUs::getInstance()->getGameByPlayer($sender) !== null){
					$sender->sendMessage(AmongUs::$prefix . "Kamu tidak bisa gabung untuk saat ini.");
					break;
				}
				$game = AmongUs::getInstance()->getAvailableGame($sender);
				if($game === null){
					$sender->sendMessage(AmongUs::$prefix . "Games tidak tersedia. silahkan tunggu game lain selesai laku coba lagi");
					break;
				}
				$game->addPlayer($sender);
				break;
			case "info":
				$lines = "§b------------------------------------------";
				$space = " ";
				$sender->sendMessage($lines . "\n" . "§8-=[§a+§8]§b=-§l§cAmong§bUs §r§adi §aMCPE §b-=§8[§a+§8]=-" . "\n" . "\n" . $space . $space . $space . $space . $space . " §6Intro:" . "\n" . "§cAmong§bUs §eadalah permainan kerja sama tim & pengkhianatan." . "\n" . "§ePemain bisa menjadi Crewmates atau Impostor." . "\n" . $space . $space . $space . $space . $space . " §6Roles:" . "\n" . " — §bCrewmate: §eSelesaikan semua tugas untuk menang." . "\n" . " — §cImposter: §eBunuh semua Crewmates untuk menang." . "\n" . $space . $space . $space . $space . $space . " §6Info:" . "\n" . "§eSelama Rapat pastikan untuk mendiskusikan siapa yang akan dipilih keluar" . "\n" . "§ePemain memiliki akses ke peta pribadi untuk membantu menavigasi peta" . "\n" . "\n" . "§8-=[§a+§8]=- [§aSelamat Bermain§8] -=[§a+§8]=-" . "\n" . $lines);
				break;
			case "leave": // leave and quit is same
			case "quit":
				$game = AmongUs::getInstance()->getGameByPlayer($sender);
				if($game === null){
					$sender->sendMessage(AmongUs::$prefix . "Kamu tidak sedang dalam game.");
					break;
				}
				$game->removePlayer($sender);
				$sender->teleport($sender->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
				$sender->sendMessage(AmongUs::$prefix . "Left the game #{$game->getId()}.");
				break;
			default:
				$sender->sendForm(new AmongUsMainForm());
		}
		return true;
	}
}
