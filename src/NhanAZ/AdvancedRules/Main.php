<?php

declare(strict_types=1);

namespace NhanAZ\AdvancedRules;

use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\CustomFormResponse;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

	protected function onEnable(): void {
		$this->saveDefaultConfig();
		$path = $this->getDataFolder() . "data/";
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new \RuntimeException("Could not create the rules agreement directory");
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	private function setting(string $key, string $default): string {
		$value = $this->getConfig()->get($key, $default);
		return is_string($value) ? $value : $default;
	}

	private function kickMode(): bool {
		$value = $this->getConfig()->get("KickMode", true);
		return is_bool($value) ? $value : true;
	}

	private function agreementPath(Player $player): string {
		return $this->getDataFolder() . "data/" . $player->getName();
	}

	private function checkData(Player $player, bool $declined = true): void {
		if (!is_file($this->agreementPath($player))) {
			if ($declined && $this->kickMode() && $player->kick($this->setting("KickMessage", "Please agree to the server rules."))) {
				return;
			}
			$player->sendForm($this->advancedRulesForm());
		}
	}

	public function onJoin(PlayerJoinEvent $event): void {
		$this->checkData($event->getPlayer(), false);
	}

	private function advancedRulesForm(bool $agreeButon = true): CustomForm {
		if ($agreeButon) {
			return new CustomForm(
				$this->setting("TitleForm", "AdvancedRules"),
				[
					new Label("content", $this->setting("Rules", "Server rules are not configured.")),
					new Toggle("switch", $this->setting("AgreeButon", "I agree"), false)
				],
				function (Player $submitter, CustomFormResponse $response): void {
					if ($response->getBool("switch")) {
						if (file_put_contents($this->agreementPath($submitter), "") === false) {
							$this->getLogger()->error("Could not save a player's rules agreement");
							$submitter->kick("Could not save your agreement. Please contact an administrator.");
							return;
						}
						$submitter->sendMessage($this->setting("AgreedMessage", "You accepted the server rules."));
					}
					$this->checkData($submitter, true);
				},
				function (Player $submitter): void {
					$this->checkData($submitter, true);
				}
			);
		} else {
			return new CustomForm(
				$this->setting("TitleForm", "AdvancedRules"),
				[
					new Label("content", $this->setting("Rules", "Server rules are not configured.")),
				],
				function (Player $submitter, CustomFormResponse $response): void {
				}
			);
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if ($command->getName() === "advancedrules") {
			if (!$sender instanceof Player) {
				$sender->sendMessage(TextFormat::RED . "You can't use this command in the terminal!");
				return true;
			}
			$sender->sendForm($this->advancedRulesForm(agreeButon: false));
			return true;
		}
		return false;
	}
}
