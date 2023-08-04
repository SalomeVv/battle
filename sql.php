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
            $selectPlayerExists = $dbco->prepare("SELECT * FROM `players` WHERE `name`=:playerName");
            $selectPlayerExists->execute([':playerName' => $player['name']]);
            $playerExists = $selectPlayerExists->fetch(PDO::FETCH_ASSOC);
            if ($playerExists) {
                $ids[] = $playerExists['id'];
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

function currentIds($dbco)
{
    $selectLatestBattle = $dbco->query("SELECT id as battle_id, player_id, adversaire_id FROM battles ORDER BY id DESC LIMIT 1");
    $ids = $selectLatestBattle->fetch(PDO::FETCH_NUM);

    return $ids;
}

function getCurrentPlayers($dbco)
{
    $ids = currentIds($dbco);
    $selectPlayer = $dbco->prepare("SELECT * FROM players WHERE id=:playerId");
    $selectPlayer->execute([':playerId' => $ids[1]]);
    $player = $selectPlayer->fetch(PDO::FETCH_ASSOC);
    $selectAdversaire = $dbco->prepare("SELECT * FROM players WHERE id=:adversaireId");
    $selectAdversaire->execute([':adversaireId' => $ids[2]]);
    $adversaire = $selectAdversaire->fetch(PDO::FETCH_ASSOC);

    $player['maxSante'] = $player['sante'];
    $adversaire['maxSante'] = $adversaire['sante'];

    return [$player, $adversaire];
}
function getExistingPlayers($dbco)
{
    return $dbco->query("SELECT * FROM players")->fetchAll(PDO::FETCH_ASSOC);
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
    $selectActions = $dbco->prepare("SELECT `actions` FROM battles WHERE id=:battleId");
    $selectActions->execute([':battleId' => $battleId]);
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
function ultLoser($dbco)
{
    $dbco->query("  CREATE VIEW losers_vw AS (
                    (SELECT adversaire_id AS loser_id
                    FROM battles
                    WHERE winner_id IS NOT NULL
                    AND adversaire_id <> winner_id)
                    UNION ALL
                    (SELECT player_id AS loser_id
                    FROM battles
                    WHERE winner_id IS NOT NULL
                    AND player_id <> winner_id))
                    ");
    $dbco->query("  CREATE VIEW losses_vw AS (
                    SELECT *, COUNT(*) AS nb_loss 
                    FROM losers_vw
                    GROUP BY loser_id)
                    ");
    $ultLoser = $dbco->query("   SELECT players.name
                            FROM losses_vw
                            JOIN players ON players.id = losses_vw.loser_id
                            ORDER BY losses_vw.nb_loss DESC LIMIT 1")->fetchColumn();
    $dbco->query("DROP VIEW losses_vw");
    $dbco->query("DROP VIEW losers_vw");
    return $ultLoser;
}
