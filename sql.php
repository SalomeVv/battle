<?php

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
            $existingPlayers = $dbco->query("SELECT * FROM `players`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($existingPlayers as $ePlayer) {
                if ($player['name'] == $ePlayer['name'] && $player['attaque'] == $ePlayer['attaque'] && $player['mana'] == $ePlayer['mana'] && $player['sante'] == $ePlayer['sante']) {
                    $existingId = $ePlayer['id'];
                }
            }
            if (isset($existingId)) {
                $ids[] = $existingId;
            } else {
                $insertPlayer = $dbco->prepare("INSERT INTO `players`(`name`,`attaque`,`mana`, `sante`)
                VALUES(:name, :attaque, :mana, :sante)");
                $insertPlayer->execute(array(
                    ':name' => $player['name'],
                    ':attaque' => $player['attaque'],
                    ':mana' => $player['mana'],
                    ':sante' => $player['sante']
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

function latestIds($dbco)
{
    $selectLatestBattle = $dbco->query("SELECT id as battle_id, player_id, adversaire_id FROM battles ORDER BY id DESC LIMIT 1");
    $ids = $selectLatestBattle->fetch(PDO::FETCH_NUM);

    return $ids;
}

function getPlayers($dbco, $ids)
{
    $selectPlayer = $dbco->query("SELECT * FROM players WHERE id=$ids[player_id]");
    $player = $selectPlayer->fetch(PDO::FETCH_ASSOC);
    $selectAdversaire = $dbco->query("SELECT * FROM players WHERE id=$ids[adversaire_id]");
    $adversaire = $selectAdversaire->fetch(PDO::FETCH_ASSOC);

    return [$player, $adversaire];
}

function updateActions($dbco, $battleId, $actions)
{
    $actionsStr = serialize($actions);
    $updateActions = $dbco->prepare("UPDATE `battles` SET `actions`=:actions  WHERE `id`=:battleId");
    $updateActions->execute(array(
        ':actions' => $actionsStr,
        ':battleId' => $battleId
    ));
}
function getActions($dbco, $battleId)
{
    $selectActions = $dbco->query("SELECT `actions` FROM battles WHERE id=$battleId");
    $actionsStr = $selectActions->fetchColumn();
    $actions = isset($actionsStr) ? unserialize($actionsStr) : null;

    return $actions;
}

function updateWinner($dbco, $battleId, $winnerId)
{
    $updateWinner = $dbco->prepare("UPDATE battles SET `winner_id`=:winnerId WHERE id=:battleId");
    $updateWinner->execute(array(
        ':winnerId' => $winnerId,
        ':battleId' => $battleId
    ));
}
