<?php
/*
                            _     _     
            /\             | |   | |    
 __  __    /  \     _   _  | |_  | |__  
 \ \/ /   / /\ \   | | | | | __| | '_ \ 
  >  <   / ____ \  | |_| | | |_  | | | |
 /_/\_\ /_/    \_\  \__,_|  \__| |_| |_|
                                        
                                        */
namespace xFlare\xAuth;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
/*
- Manages commands, xAuth commands are proccessed here.
*/
class CommandManager extends Command{
	public function __construct(Loader $plugin){
        	$this->plugin = $plugin;
  	}
  	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        	switch (strtolower($command->getName())){
            		case "changepw":
            			return; //Not ready yet
            			$this->changeMyPassword($sender, $args);
                		break;
                	case "unregister":
            			$this->unregisterAccount($sender);
                		break;
        	}
  	}
  	private function changeMyPassword($sender, $args){
  		if($this->plugin->passChange){
  			if(in_array($args[1])){
  				$pass = $args[1];
  				$pass = md5($pass);
  				$this->plugin->chatprotection[$sender->getId()] = $pass;
  				if($this->plugin->provider === "yml"){
  					$myuser = new Config($this->plugin->getDataFolder() . "players/" . strtolower($event->getPlayer()->getName() . ".yml"), Config::YAML);
  					$myuser->set("password", $pass);
  					$myuser->set("version", $this->pluin->version);
  					$Myuser->save();
  				}
  				elseif($this->plugin->provider === "mysql"){
  					
  				}
  			}
  		}
  	}
  	private function unregisterAccount($sender){
  		if($this->provider === "yml"){
  			$this->plugin->registered->remove(strtolower($sender->getName()));
  			$this->plugin->registered->save();
  			unset($this->plugin->chatprotection[$sender->getId()]);
  			$this->plugin->loginmanager[$sender->getId()] = 0;
  			unlink($this->plugin->getDataFolder() . "players/" . strtolower($sender->getName()) . ".yml");
  			$sender->sendMessage($this->plugin->getConfig()->get("unregister"));
  		}
  		elseif($this->plugin->provider === "mysql"){
  			
  		}
  	}
}
