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

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Server;
/*
- Loads up xAuth files.
- xAuth is user-friendly and checks for errors.
*/
class Loader extends PluginBase implements Listener{
  public $loginmanager = [];
  public $chatprotection = [];
  public $mainlogger = [];
  public $kicklogger = [];
  public $playerticks = [];
  private $mysettings = [];
  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->version = "1.0.0 beta 7";
    $this->codename = "xFlaze";
    $this->prefix = "§7[§dx§aAuth§7]";
    $this->loggercount = 0;
    $this->lastlog = null;
    $this->getServer()->getLogger()->info("§dx§aAuth §3by §axFlare §3is starting up§7...");
    $this->saveDefaultConfig();
    $this->provider = strtolower($this->getConfig()->get("autentication-type"));
    $this->status = null; //Plugin starting up...
    $this->memorymanagerdata = 0;
    $this->debug = $this->getConfig()->get("debug-mode");
    $this->checkForConfigErrors();
    if($this->async !== true && $this->provider === "mysql"){
    // $this->database = mysql; Later.
    }
  }
  public function onDisable(){
    if($this->status === "enabled" && $this->debug === true && $this->totalerrors !== 0){
      $this->getServer()->getLogger()->info("§7[§axAuth§7] §3Total errors during session§7:§c $this->totalerrors");
    }
  }
  public function checkForConfigErrors(){
    $errors = 0;
    $this->totalerrors = 0;
    $this->registerConfigOptions();
    if($this->getConfig()->get("version") !== $this->version){
    	$this->getServer()->getLogger()->info("§7[§axAuth§7] §3Upgrading config§7...");
    	if($this->configUpdate() === true){
    		unlink($this->getDataFolder() . "config.yml");
    		if(!file_exists($this->getDataFolder() . "config.yml")){
    			$this->getServer()->getLogger()->info("§7[§axAuth§7] §3Config updated§7.");
    			$this->getServer()->shutdown();
    		}
    		else{
    			$this->getServer()->getLogger()->info("§7[§axAuth§7] §3Config update failed§7!");
    		}
    	}
    }
    if($this->provider === "mysql" && $this->provider !== "yml"){
      $this->getServer()->getLogger()->info("§7[§cError§7] §3MySQL support is not implemented yet, invaild §ax§dAuth §3provider§7!\nSwitching too YML.");
      $this->provider = "yml";
    }
    if(!file_exists($this->getDataFolder() . "players/") && $this->provider === "yml"){
        $this->getServer()->getLogger()->info("§7[§axAuth§7] §eCreating players folder for provider§7...");
	      @mkdir($this->getDataFolder() . "players/");			
    }
    elseif($this->provider === "yml" && !file_exists($this->getDataFolder() . "players/")){
      $this->getServer()->getLogger()->info("§7[§axAuth§7] §eCannot create players folder§7!");
      $errors++;
      $this->status = "failed";
      $this->getServer()->shutdown();
    }
    if($this->max < 0 or $this->short < 0){
      $this->max = 15;
      $this->short = 6;
      $errors++;
    }
    if($this->getConfig()->get("database-checks") === true && $this->provider !== "mysql"){
      $this->getConfig()->set("data-checks", false);
      $this->getConfig()->save();
      $errors++;
    }
    if($this->provider === "yml"){
      $this->registered = new Config($this->getDataFolder() . "registered.txt", Config::ENUM, array());
    }
    if($this->maxaccounts !== false){
      $this->ips = new Config($this->getDataFolder() . "ips.txt", Config::ENUM, array());
    }
    if($this->logger !== true && $this->debug !== false){
      $this->getConfig()->set("log-xauth", true);
      $this->getConfig()->save();
      $errors++;
    }
    if($this->debug === true || $this->logger === true){
        $this->getServer()->getLogger()->info("§7[§axAuth§7] §3Creating §dx§aAuth §3logger§7...");
        $this->xauthlogger = new Config($this->getDataFolder() . "xauthlogs.log", Config::ENUM, array());
    }
    if($this->async !== true && $this->async !== false){
      $errors++;
      $this->getConfig()->set("use-async", false);
      $this->getConfig()->save();
      $this->async = false;
    }
    if($this->provider !== "yml" && $this->getConfig()->get("check-yml") === true){
    	$this->checkYML = false;
    	$this->getConfig()->set("check-yml", false);
    	$this->getConfig()->save();
    	$errors++;
    }
    if($this->getConfig()->get("dump-logger") < 1){
      $this->getConfig()->set("dump-logger", 1);
      $this->getConfig()->save();
      $errors++;
    }
    $this->totalerrors = $this->totalerrors + $errors;
    if($errors !== 0 || $this->totalerrors !== 0){
        $this->getConfig()->reload();
        $this->getServer()->getLogger()->info("§7[§ax§dAuth§7] " . $this->totalerrors . " §cerrors have been found§7.\n§3We tried to fix it§7, §3but just in case review your config settings§7!");
    }
    if($this->status === null){
      $this->registerClasses();
      $this->status = "enabled";
    }
    elseif($this->status !== null){
      $this->status = "failed";
      $this->getServer()->getLogger()->info("§7> §axAuth §3has failed to start up§7. (§c Error: $this->status §7)");
    }
  }
  public function registerClasses(){
    $this->getServer()->getPluginManager()->registerEvents(new LoginTasks($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new LoginAndRegister($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new CommandManager($this), $this);
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new xAuthTicks($this), $this->getConfig()->get("tick-rate"));
    if($this->api){
      $this->getServer()->getPluginManager()->registerEvents(new API($this), $this);
    }
    array_push($this->mainlogger, "§dx§aAuth §3has been §aenabled§7.");
  }
  public function registerConfigOptions(){ //Config -> Object for less lag.
    $this->allowMoving = $this->getConfig()->get("allow-movement");
    $this->allowPlace = $this->getConfig()->get("allow-block-placing");
    $this->allowBreak = $this->getConfig()->get("allow-block-breaking");
    $this->allowCommand = $this->getConfig()->get("allow-commands");
    $this->allowShoot = $this->getConfig()->get("allow-shoot-arrows");
    $this->allowDrops = $this->getConfig()->get("allow-drops");
    $this->allowPvP = $this->getConfig()->get("allow-pvp");
    $this->allowDamage = $this->getConfig()->get("allow-damage");
    $this->simplepassword = $this->getConfig()->get("simple-passcode-blocker");
    $this->safemode = $this->getConfig()->get("safe-mode");
    $this->logger = $this->getConfig()->get("log-xauth");
    $this->api = $this->getConfig()->get("enable-api");
    $this->async = $this->getConfig()->get("use-async");
    $this->max = $this->getConfig()->get("max-characters");
    $this->short = $this->getConfig()->get("shortest-characters");
    $this->usernamestatus = $this->getConfig()->get("show-username-auth-status");
    $this->protectForce = $this->getConfig()->get("enable-kick-invalid");
    $this->hotbar = $this->getConfig()->get("hotbar-message");
    $this->timeoutEnabled = $this->getConfig()->get("enable-kick");
    $this->ipAuth = $this->getConfig()->get("ip-auth");
    $this->username = $this->getConfig()->get("username");
    $this->port = $this->getConfig()->get("port");
    $this->server = $this->getConfig()->get("server");
    $this->password = $this->getConfig()->get("password");
    $this->checks = $this->getConfig()->get("database-checks");
    $this->passChange = $this->getConfig()->get("enable-pass-changing");
    $this->email = $this->getConfig()->get("require-email");
    $this->join = $this->getConfig()->get("player-join");
    $this->quit = $this->getConfig()->get("player-quit");
    $this->import = $this->getConfig()->get("import-from-simpleauth");
    $this->maxaccounts = $this->getConfig()->get("max-accounts");
    $this->checkYML = $this->getConfig()->get("check-yml");
    $this->allowPickup = $this->getConfig()->get("allow-item-pickup");
    $this->passBlock = $this->getConfig()->get("block-saying-pass-in-chat");
    if($this->timeoutEnabled){
    	$this->timeoutMax = $this->getConfig()->get("kick-after-seconds");
    }
    if($this->protectForce){
    	$this->maxAttempts = $this->getConfig()->get("kick-after-invailds");
    }
    if($this->logger){
      $this->getServer()->getLogger()->info("§7[§axAuth§7] §3Logger is enabled.");
    }
    if($this->debug){
      $this->getServer()->getLogger()->info("§7[§axAuth-Debug§7] §3Config options have been registered.");
    }
    if($this->import){
    	$this->getServer()->getLogger()->info("§7[§axAuth§7] §3Import enabled, SimpleAuth data will be used now.");
    }
  }
  private function configUpdate(){
  	array_push($this->mysettings, $this->provider, $this->username, $this->password, $this->port, $this->server, $this->ipAuth, $this->max, $this->short, $this->async, $this->checks, $this->hotbar, $this->passChange, $this->simplepassword, $this->email, $this->timeoutEnabled, $this->protectForce, $this->allowMoving, $this->allowCommand, $this->allowDrops, $this->allowPlace, $this->allowBreak, $this->allowPvP, $this->allowDamage, $this->allowShoot, $this->safemode, $this->debug, $this->logger, $this->api);
    $this->saveDefaultConfig();
  	return true;
  }
}
    
