<?php
class Commands {
	public function ownCommand($comment, $command, $accountID, $targetExtID){
		require_once "../lib/mainLib.php";
		$gs = new mainLib();
		$commandInComment = strtolower("!".$command);
		$commandInPerms = ucfirst(strtolower($command));
		$commandlength = strlen($commandInComment);
		if(substr($comment, 0, $commandlength) == $commandInComment && (($gs->checkPermission($accountID, "command".$commandInPerms."All") || ($targetExtID == $accountID && $gs->checkPermission($accountID, "command".$commandInPerms."Own"))))) return true;
		return false;
	}
	//Comments commands
	public function doCommands($accountID, $comment, $levelID) {
		include dirname(__FILE__)."/../lib/connection.php";
		require_once "../lib/exploitPatch.php";
		require_once "../lib/mainLib.php";
		$ep = new exploitPatch();
		$gs = new mainLib();
		$commentarray = explode(' ', $comment);
		$uploadDate = time();
		//Level info
		$query = $db->prepare("SELECT extID FROM levels WHERE levelID = :id");
		$query->execute([':id' => $levelID]);
		$targetExtID = $query->fetchColumn();
		//Admin commands
		if(substr($comment, 0, 7) == '!unrate' && $gs->checkPermission($accountID, "commandUnrate")){
			$query = $db->prepare("UPDATE levels SET starStars = :starStars, starFeatured = :starFeatured, starDifficulty = :starDifficulty, starDemon = :starDemon, starAuto = :starAuto, starCoins = :starCoins WHERE levelID = :levelID");
			$query->execute([':starStars' => 0, 'starFeatured' => 0, ':starDifficulty' => 0, ':starDemon' => 0, ':starAuto' => 0, 'starCoins' => 0, ':levelID' => $levelID]);
			return true;
		}
		if(substr($comment, 0, 5) == '!epic' && $gs->checkPermission($accountID, "commandEpic")){
			$query = $db->prepare("UPDATE levels SET starEpic = '1' WHERE levelID = :levelID LIMIT 1");
			$query->execute([':levelID' => $levelID]);
			return true;
		}
		if(substr($comment, 0, 7) == '!unepic' && $gs->checkPermission($accountID, "commandUnepic")){
			$query = $db->prepare("UPDATE levels SET starEpic='0' WHERE levelID = :levelID LIMIT 1");
			$query->execute([':levelID' => $levelID]);
			return true;
		}
		if(substr($comment, 0, 12) == '!verifycoins' && $gs->checkPermission($accountID, "commandVerifycoins")){
			$query = $db->prepare("UPDATE levels SET starCoins = '1' WHERE levelID = :levelID");
			$query->execute([':levelID' => $levelID]);
			return true;
		}
		if(substr($comment, 0, 14) == '!unverifycoins' && $gs->checkPermission($accountID, "commandUnverifycoins")){
			$query = $db->prepare("UPDATE levels SET starCoins = '0' WHERE levelID = :levelID");
			$query->execute([':levelID' => $levelID]);
			return true;
		}
		if(substr($comment, 0, 6) == '!daily' && $gs->checkPermission($accountID, "commandDaily")){
			$query = $db->prepare("SELECT count(*) FROM dailyFeatures WHERE levelID = :level AND type = 0");
			$query->execute([':level' => $levelID]);
			if($query->fetchColumn()) return false;
			$query = $db->prepare("SELECT timestamp FROM dailyFeatures WHERE timestamp >= :tomorrow AND type = 0 ORDER BY timestamp DESC LIMIT 1");
			$query->execute([':tomorrow' => strtotime("tomorrow 00:00:00")]);
			if(!$query->rowCount()){
				$timestamp = strtotime("tomorrow 00:00:00");
			}else{
				$timestamp = $query->fetchColumn() + 86400;
			}
			$query = $db->prepare("INSERT INTO dailyFeatures (levelID, timestamp, type) VALUES (:levelID, :uploadDate, 0)");
			$query->execute([':levelID' => $levelID, ':uploadDate' => $timestamp]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account, value2, value4) VALUES ('9', :value, :levelID, :timestamp, :id, :dailytime, 0)");
			$query->execute([':value' => "1", ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID, ':dailytime' => $timestamp]);
			return true;
		}
		if(substr($comment, 0, 7) == '!weekly' && $gs->checkPermission($accountID, "commandWeekly")){
			$query = $db->prepare("SELECT count(*) FROM dailyfeatures WHERE levelID = :level AND type = 1");
			$query->execute([':level' => $levelID]);
			if($query->fetchColumn()) return false;
			$query = $db->prepare("SELECT timestamp FROM dailyFeatures WHERE timestamp >= :tomorrow AND type = 1 ORDER BY timestamp DESC LIMIT 1");
			$query->execute([':tomorrow' => strtotime("next monday")]);
			if(!$query->rowCount()){
				$timestamp = strtotime("next monday");
			}else{
				$timestamp = $query->fetchColumn() + 604800;
			}
			$query = $db->prepare("INSERT INTO dailyFeatures (levelID, timestamp, type) VALUES (:levelID, :uploadDate, 1)");
			$query->execute([':levelID' => $levelID, ':uploadDate' => $timestamp]);
			$query = $db->prepare("INSERT INTO modactions (type, value, value3, timestamp, account, value2, value4) VALUES ('10', :value, :levelID, :timestamp, :id, :dailytime, 1)");
			$query->execute([':value' => "1", ':timestamp' => $uploadDate, ':id' => $accountID, ':levelID' => $levelID, ':dailytime' => $timestamp]);
			return true;
		}
		if(substr($comment, 0, 7) == '!delete' && $gs->checkPermission($accountID, "commandDelete")){
			if(!is_numeric($levelID)) return false;
			$query = $db->prepare("DELETE FROM levels WHERE levelID = :levelID LIMIT 1");
			$query->execute([':levelID' => $levelID]);
			if(file_exists(dirname(__FILE__)."../../data/levels/$levelID")) unlink(dirname(__FILE__)."../../data/levels/$levelID",dirname(__FILE__)."../../data/levels/deleted/$levelID");
			return true;
		}
		if(substr($comment, 0, 7) == '!setacc' && $gs->checkPermission($accountID, "commandSetacc")){
			$query = $db->prepare("SELECT accountID FROM accounts WHERE userName = :userName OR accountID = :userName LIMIT 1");
			$query->execute([':userName' => $commentarray[1]]);
			if(!$query->rowCount()) return false;
			$targetAcc = $query->fetchColumn();
			$query = $db->prepare("SELECT userID FROM users WHERE extID = :extID LIMIT 1");
			$query->execute([':extID' => $targetAcc]);
			$userID = $query->fetchColumn();
			$query = $db->prepare("UPDATE levels SET extID = :extID, userID = :userID, userName = :userName WHERE levelID = :levelID");
			$query->execute([':extID' => $targetAcc["accountID"], ':userID' => $userID, ':userName' => $commentarray[1], ':levelID' => $levelID]);
			return true;
		}		
		//NON-ADMIN COMMANDS
		if($this->ownCommand($comment, "sharecp", $accountID, $targetExtID)){
			$query = $db->prepare("SELECT userID FROM users WHERE userName = :userName ORDER BY isRegistered DESC LIMIT 1");
			$query->execute([':userName' => $commentarray[1]]);
			$targetAcc = $query->fetchColumn();
			$query = $db->prepare("INSERT INTO cpshares (levelID, userID) VALUES (:levelID, :userID)");
			$query->execute([':userID' => $targetAcc, ':levelID' => $levelID]);
			$query = $db->prepare("UPDATE levels SET isCPShared = '1' WHERE levelID = :levelID");
			$query->execute([':levelID' => $levelID]);
			return true;
		}
		return false;
	}
	//Profile commands
	public function doProfileCommands($accountID, $command){
		include dirname(__FILE__)."/../lib/connection.php";
		require_once "../lib/exploitPatch.php";
		require_once "../lib/mainLib.php";
		$ep = new exploitPatch();
		$gs = new mainLib();
		if(substr($command, 0, 8) == '!discord'){
			if(substr($command, 9, 6) == "accept"){
				$query = $db->prepare("UPDATE accounts SET discordID = discordLinkReq, discordLinkReq = '0' WHERE accountID = :accountID AND discordLinkReq <> 0");
				$query->execute([':accountID' => $accountID]);
				$query = $db->prepare("SELECT discordID, userName FROM accounts WHERE accountID = :accountID");
				$query->execute([':accountID' => $accountID]);
				$account = $query->fetch();
				$gs->sendDiscordPM($account["discordID"], "Your link request to " . $account["userName"] . " has been accepted!");
				return true;
			}
			if(substr($command, 9, 4) == "deny"){
				$query = $db->prepare("SELECT discordLinkReq, userName FROM accounts WHERE accountID = :accountID");
				$query->execute([':accountID' => $accountID]);
				$account = $query->fetch();
				$gs->sendDiscordPM($account["discordLinkReq"], "Your link request to " . $account["userName"] . " has been denied!");
				$query = $db->prepare("UPDATE accounts SET discordLinkReq = '0' WHERE accountID = :accountID");
				$query->execute([':accountID' => $accountID]);
				return true;
			}
			if(substr($command, 9, 6) == "unlink"){
				$query = $db->prepare("SELECT discordID, userName FROM accounts WHERE accountID = :accountID");
				$query->execute([':accountID' => $accountID]);
				$account = $query->fetch();
				$gs->sendDiscordPM($account["discordID"], "Your Discord account has been unlinked from " . $account["userName"] . "!");
				$query = $db->prepare("UPDATE accounts SET discordID = '0' WHERE accountID = :accountID");
				$query->execute([':accountID' => $accountID]);
				return true;
			}
			return false;
		}
		return false;
	}
}
?>