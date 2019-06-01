<?php
namespace CommandManager;

use pocketmine\command\{Command, CommandSender};
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use ServerLogManager\ServerLogManager;

class CommandManager extends PluginBase implements Listener {
    public $pre = "§e•";

    //public $pre = "§l§e[ §f시스템 §e]§r§e";

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->data = $this->config->getAll();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->serverlog = ServerLogManager::getInstance();
        $this->check();
    }

    public function check() {
        foreach ($this->data as $key => $value) {
            if (!$this->getServer()->getCommandMap()->getCommand($value) instanceof Command) {
                unset($this->data[array_search($value, $this->data)]);
                ksort($this->data);
            }
        }
    }

    public function onDisable() {
        $this->save();
    }

    public function save() {
        $this->config->setAll($this->data);
        $this->config->save();
    }

    public function onCmd(PlayerCommandPreprocessEvent $ev) {
        if (substr($ev->getMessage(), 0, 1) == "/") {
            if ($ev->isCancelled())
                return;
            $command = explode(" ", substr($ev->getMessage(), 1))[0];
            $this->getServer()->getLogger()->notice("§e[CommandLog] {$ev->getPlayer()->getName()} > {$ev->getMessage()}");
            if ($command == "op" || $command == "deop") {
                $ev->setCancelled(true);
                if (!$ev->getPlayer()->isOp())
                    $this->getServer()->broadcastMessage("• {$ev->getPlayer()->getName()} > {$ev->getMessage()}");
                else
                    $ev->getPlayer()->sendMessage("{$this->pre} 해당 명령어는 사용할 수 없습니다.");
                return true;
            }
            if (!$ev->getPlayer()->isOp() && !in_array($command, $this->data)) {
                $ev->setCancelled(true);
                $this->getServer()->broadcastMessage("• {$ev->getPlayer()->getName()} > {$ev->getMessage()}");
                $this->serverlog->addChatCommandLog($ev->getPlayer(), $ev->getMessage(), 1);
                return true;
            }
            $this->serverlog->addChatCommandLog($ev->getPlayer(), $ev->getMessage(), 2);
            /*if(!$ev->getPlayer()->isOp() && ($command == "?" || $command == "help")){
              $ev->setCancelled(true);
              $this->check();
              if(count($this->data) == 0){
                $ev->getPlayer()->sendMessage("{$this->pre} 명령어를 사용할 수 없습니다.");
                return true;
              }
              $maxpage = ceil(count($this->data)/5);
              if(!isset($args[1]) || !is_numeric($args[1]) || $args[1] <= 0){
                $page = 1;
              }elseif($args[1] > $maxpage){
                $page = $maxpage;
              }else{
                $page = $args[1];
              }
              $cmd = "";
              $count = 0;
              foreach($this->data as $key => $value){
                if($page*5-5 <= $count and $count < $page*5){
                  $cmd .= "{$this->pre} /{$value} | {$this->getServer()->getCommandMap()->getCommand($value)->getDescription()}\n";
                  $count++;
                }else{
                  $count++;
                  continue;
                }
              }
              $ev->getPlayer()->sendMessage("--- 명령어 목록 {$page} / {$maxpage} ---");
              $ev->getPlayer()->sendMessage($cmd);
            }elseif($command == "op" || $command == "deop"){
              $ev->setCancelled(true);
              $ev->getPlayer()->sendMessage("{$this->pre} 해당 명령어는 사용할 수 없습니다.");
              return true;
            }elseif(!$ev->getPlayer()->isOp() && !in_array($command, $this->data)){
              $ev->setCancelled(true);
              $ev->getPlayer()->sendMessage("{$this->pre} {$command} 명령어는 존재하지 않습니다.");
            }*/
        }
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, $args): bool {
        if ($cmd->getName() == "cmd") {
            if (!$sender->isOp()) {
                $sender->sendMessage("{$this->pre} 권한이 없습니다.");
                return false;
            }
            if (!isset($args[0])) {
                $sender->sendMessage("--- 커멘드 도움말 1 / 1 ---");
                $sender->sendMessage("{$this->pre} /cmd a <Command> | 사용을 허용할 커멘드를 추가합니다.");
                $sender->sendMessage("{$this->pre} /cmd r <Command> | 허용된 커멘드를 제거합니다.");
                $sender->sendMessage("{$this->pre} /cmd l | 허용된 커멘드 목록을 확인합니다.");
                return false;
            } else {
                switch ($args[0]) {
                    case "a":
                        if (!isset($args[1])) {
                            $sender->sendMessage("{$this->pre} 명령어가 기입되지 않았습니다.");
                            return false;
                        }
                        if (in_array($args[1], $this->data)) {
                            $sender->sendMessage("{$this->pre} 이미 허용된 명령어입니다.");
                            return false;
                        }
                        foreach ($sender->getServer()->getCommandMap()->getCommands() as $command) {
                            if ($command->getName() == $args[1]) {
                                $isset = true;
                                break;
                            }
                        }
                        if (!isset($isset)) {
                            $sender->sendMessage("{$this->pre} 해당 명령어는 존재하지 않습니다.");
                            return false;
                        } else {
                            array_push($this->data, $args[1]);
                            ksort($this->data);
                            $sender->sendMessage("{$this->pre} 명령어 [ {$args[1]} ] (을)를 허용했습니다.");
                            return true;
                        }
                        break;

                    case "r":
                        if (!isset($args[1])) {
                            $sender->sendMessage("{$this->pre} 명령어가 기입되지 않았습니다.");
                            return false;
                        }
                        if (!in_array($args[1], $this->data)) {
                            $sender->sendMessage("{$this->pre} 해당 명령어를 찾을 수 없습니다.");
                            return false;
                        } else {
                            unset($this->data[array_search($args[1], $this->data)]);
                            ksort($this->data);
                            $sender->sendMessage("{$this->pre} 명령어 [ {$args[1]} ] (을)를 제거했습니다.");
                            return true;
                        }
                        break;

                    case "l":
                        $command = "§7";
                        foreach ($this->data as $key => $value) {
                            $command .= "<{$value}> ";
                        }
                        $sender->sendMessage("--- 허용된 커멘드 목록 §a" . count($this->data) . "§f개 --- ");
                        $sender->sendMessage($command);
                        break;

                    default:
                        $sender->sendMessage("--- 커멘드 도움말 1 / 1 ---");
                        $sender->sendMessage("{$this->pre} /cmd a <Command> | 사용을 허용할 커멘드를 추가합니다.");
                        $sender->sendMessage("{$this->pre} /cmd r <Command> | 허용된 커멘드를 제거합니다.");
                        $sender->sendMessage("{$this->pre} /cmd l | 허용된 커멘드 목록을 확인합니다.");
                        return false;
                        break;
                }
                return true;
            }
            return false;
        }
        return false;
    }
}
