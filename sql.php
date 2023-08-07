<?php
require_once __DIR__ . '/player.class.php';
require_once __DIR__ . '/battle.class.php';

function connectDB()
{
    $servername = "localhost";
    $username = "root";
    $password = "";

    try {
        $dbco = new PDO("mysql:host=$servername", $username, $password);
        $dbco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbco->prepare(file_get_contents("battle.sql"))->execute();
        $dbco->query("use battle");
        return $dbco;
    } catch (PDOException $e) {
        echo $e->getMessage() . ' | ' . $e->getFile() . ' | ' . $e->getLine() . '<br>';
    }
}

function insertIntoDB($dbco, $players)
{
    try {
        $ids = [];
        foreach ($players as $player) {
            $selectPlayerExists = $dbco->prepare("SELECT * FROM `players` WHERE `name`=:playerName");
            $selectPlayerExists->execute([':playerName' => $player->name]);
            $playerExists = $selectPlayerExists->fetch(PDO::FETCH_ASSOC);
            if ($playerExists) {
                $ids[] = $playerExists['id'];
            } else {
                $insertPlayer = $dbco->prepare("INSERT INTO `players`(`name`,`attaque`,`mana`, `sante`)
                VALUES(:name, :attaque, :mana, :sante)");
                $insertPlayer->execute(array(
                    ':name' => $player->name,
                    ':attaque' => $player->attaque,
                    ':mana' => $player->mana,
                    ':sante' => $player->sante
                ));
                $ids[] = $dbco->lastInsertId();
            }
        }
        $insertBattle = $dbco->prepare("INSERT INTO `battles`(`player_id`,`adversaire_id`)
        VALUES(:playerId, :adversaireId)");
        $insertBattle->execute(array(
            ':playerId' => $ids[0],
            ':adversaireId' => $ids[1]
        ));
        $ids[] = $dbco->lastInsertId();

        return $ids;
    } catch (PDOException $e) {
        echo $e->getMessage() . ' | ' . $e->getFile() . ' | ' . $e->getLine() . '<br>';
    }
}

function currentIds($dbco)
{
    $selectLatestBattle = $dbco->query("SELECT id as battle_id, player_id, adversaire_id FROM battles ORDER BY id DESC LIMIT 1");
    $ids = $selectLatestBattle->fetch(PDO::FETCH_NUM);

    return $ids;
}

function getCurrentPlayers($dbco)
{
    $ids = currentIds($dbco);
    $selectPlayer = $dbco->prepare("SELECT `name`, `attaque`, `mana`, `sante` FROM players WHERE id=:playerId");
    $selectPlayer->execute([':playerId' => $ids[1]]);
    // $selectPlayer->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Player');
    $p = $selectPlayer->fetch(PDO::FETCH_ASSOC);
    $player = new Player($p['name'], $p['attaque'], $p['mana'], $p['sante']);
    $selectAdversaire = $dbco->prepare("SELECT `name`, `attaque`, `mana`, `sante` FROM players WHERE id=:adversaireId");
    $selectAdversaire->execute([':adversaireId' => $ids[2]]);
    // $selectAdversaire->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Player');
    $a = $selectAdversaire->fetch(PDO::FETCH_ASSOC);
    $adversaire = new Player($a['name'], $a['attaque'], $a['mana'], $a['sante']);

    return [$player, $adversaire];
}
function getExistingPlayers($dbco)
{
    return $dbco->query("SELECT * FROM players")->fetchAll(PDO::FETCH_ASSOC);
}

function updateActions($dbco, $battle)
{
    $actionsStr = serialize($battle->actions);
    $updateActions = $dbco->prepare("UPDATE `battles` SET `actions`=:actions  WHERE `id`=:battleId");
    $updateActions->execute(array(
        ':actions' => $actionsStr,
        ':battleId' => $battle->id
    ));
}
function getActions($dbco, $battleId)
{
    $selectActions = $dbco->prepare("SELECT `actions` FROM battles WHERE id=:battleId");
    $selectActions->execute([':battleId' => $battleId]);
    $actionsStr = $selectActions->fetchColumn();
    $actions = isset($actionsStr) ? unserialize($actionsStr) : null;

    return $actions;
}

function updateWinner($dbco, $battle)
{
    $updateWinner = $dbco->prepare("UPDATE battles SET `winner_id`=:winnerId WHERE id=:battleId");
    $updateWinner->execute(array(
        ':winnerId' => $battle->winner->id,
        ':battleId' => $battle->id
    ));
}

function fightCounts($dbco)
{
    return $dbco->query("SELECT count(*) FROM `battles`")->fetchColumn();
}
function mostWin($dbco)
{
    return $dbco->query("   SELECT name
                            FROM battles 
                            JOIN players ON battles.winner_id = players.id
                            WHERE winner_id IS NOT NULL 
                            GROUP BY winner_id
                            ORDER BY COUNT(*) DESC LIMIT 1; ")->fetchColumn();
}
function nbWin($dbco, $playerId)
{
    $nbWin = $dbco->prepare("   SELECT COUNT(*)
                                FROM battles
                                WHERE winner_id = :playerId
                                GROUP BY winner_id");
    $nbWin->execute([':playerId' => $playerId]);
    return $nbWin->fetchColumn();
}
function ultLoser($dbco)
{
    return $dbco->query("  SELECT players.name
                                FROM players 
                                LEFT JOIN battles AS bp ON players.id = bp.player_id AND bp.player_id <> bp.winner_id AND bp.winner_id IS NOT NULL
                                LEFT JOIN battles AS ba ON players.id = ba.adversaire_id AND ba.adversaire_id <> ba.winner_id AND ba.winner_id IS NOT NULL
                                GROUP BY players.id
                                ORDER BY COUNT(*) DESC LIMIT 1")->fetchColumn();
}
function unfinished($dbco)
{
    return $dbco->query("   SELECT COUNT(*)
                            FROM battles
                            WHERE winner_id IS NULL")->fetchColumn();
}
