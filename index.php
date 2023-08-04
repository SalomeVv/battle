<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/sql.php';
session_start();

list($player, $adversaire, $actions) = getSession();

$dbco = connectDB();
if (isset($player) && isset($adversaire)) {
    list($battleId, $player['id'], $adversaire['id']) = currentIds($dbco);
    $actions = getActions($dbco, $battleId);
}
$existingPlayers = getExistingPlayers($dbco) ?? [];

if (isset($_POST['fight'])) {
    $formErrors = checkForm();
    if (!empty($formErrors)) {
        $player = null;
        $adversaire = null;
    } else {
        $players = initStats();
        insertIntoDB($dbco, $players);
        list($player, $adversaire) = getCurrentPlayers($dbco);

        setSession($player, $adversaire, $actions);
    }
}

if (isset($_POST['attaque'])) {
    $actions[] = attack($player, $adversaire);
    if ($adversaire['sante'] > 0) {
        $actions[] = autoplay($adversaire, $player);
    }
    $winner = $player['sante'] > $adversaire['sante'] ? $player : $adversaire;
    setSession($player, $adversaire, $actions);

    updateActions($dbco, $battleId, $actions);
}
if (isset($_POST['soin'])) {

    $actions[] = heal($player);
    $actions[] = autoplay($adversaire, $player);
    $winner = $player['sante'] > $adversaire['sante'] ? $player : $adversaire;
    setSession($player, $adversaire, $actions);

    updateActions($dbco, $battleId, $actions);
}
if (isset($_POST['restart'])) {
    deleteSession();
    list($player, $adversaire, $actions) = getSession();
}

$endGame = (isset($player) && isset($adversaire)) ? ($player['sante'] < 1 || $adversaire['sante'] < 1) : false;

if ($endGame) {
    updateWinner($dbco, $battleId, $winner['id']);
}
?>

<html lang="fr">

<head>
    <title>Battle</title>
    <link rel="stylesheet" href="public/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

</head>

<body>
    <div class="container">
        <audio id="fight-song" src="fight.mp3"></audio>
        <audio id="hadoudken-song" src="Haduken.mp3"></audio>
        <audio id="fatality-song" src="fatality.mp3"></audio>
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Battle</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="statistiques.php">Statistiques</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <?php if (!$player || !$adversaire) { ?>
            <div id="prematch">
                <form id='formFight' action="index.php" method="post">
                    <div class="row">
                        <h4>Joueur</h4>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Sélectionner un joueur existant</label>
                                <select class="form-select" name="player[id]" id="selectPlayer">
                                    <option selected value></option>
                                    <?php foreach ($existingPlayers as $ePlayer) { ?>
                                        <option value="<?php echo $ePlayer['id'] ?>"><?php echo $ePlayer['name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div id="playerStats">
                            <div class="col-12 mt-3">ou créer votre propre joueur</div>
                            <div class="errors">
                                <ul>
                                    <?php foreach ($formErrors["player"] ?? [] as $error) { ?>
                                        <li class="text-danger"><?php echo $error ?></li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Name</label>
                                    <input required type="text" class="form-control" name="player[name]" value="<?php echo $_POST["player"]["name"] ?? "" ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Attaque</label>
                                    <input required type="number" class="form-control <?php echo isset($formErrors["player"]["attaque"]) ? "is-invalid" : "" ?>" name="player[attaque]" value="<?php echo $_POST["player"]["attaque"] ?? "100" ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Mana</label>
                                    <input required type="number" class="form-control <?php echo isset($formErrors["player"]["mana"]) ? "is-invalid" : "" ?>" name="player[mana]" value="<?php echo $_POST["player"]["mana"] ?? "100" ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Santé</label>
                                    <input required type="number" class="form-control <?php echo isset($formErrors["player"]["sante"]) ? "is-invalid" : "" ?>" name="player[sante]" value="<?php echo $_POST["player"]["sante"] ?? "1000" ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <h4>Adversaire</h4>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Sélectionner un adversaire existant</label>
                                <select class="form-select" name="adversaire[id]" id="selectAdversaire">
                                    <option selected value></option>
                                    <?php foreach ($existingPlayers as $ePlayer) { ?>
                                        <option value="<?php echo $ePlayer['id'] ?>"><?php echo $ePlayer['name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div id="adversaireStats">
                            <div class="col-12 mt-3">ou créer votre propre adversaire</div>
                            <div class="errors">
                                <ul>
                                    <?php foreach ($formErrors["adversaire"] ?? [] as $error) { ?>
                                        <li class="text-danger"><?php echo $error ?></li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Name</label>
                                    <input required type="text" class="form-control" name="adversaire[name]" value="<?php echo $_POST["adversaire"]["name"] ?? "" ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Attaque</label>
                                    <input required type="number" class="form-control <?php echo isset($formErrors["adversaire"]["attaque"]) ? "is-invalid" : "" ?>" name="adversaire[attaque]" value="<?php echo $_POST["adversaire"]["attaque"] ?? "100" ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Mana</label>
                                    <input required type="number" class="form-control <?php echo isset($formErrors["adversaire"]["mana"]) ? "is-invalid" : "" ?>" name="adversaire[mana]" value="<?php echo $_POST["adversaire"]["mana"] ?? "100" ?>">
                                </div>
                                <div class=" col-6">
                                    <label class="form-label">Santé</label>
                                    <input required type="number" class="form-control <?php echo isset($formErrors["adversaire"]["sante"]) ? "is-invalid" : "" ?>" name="adversaire[sante]" value="<?php echo $_POST["adversaire"]["sante"] ?? "1000" ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="d-flex justify-content-center">
                            <input class="btn btn-outline-primary" name="fight" type="submit" value="FIGHT">
                        </div>
                    </div>
                </form>
            </div>
        <?php } else { ?>
            <div id="match" class="row gx-5">
                <h2>Match</h2>
                <div class="col-6 ">
                    <div class="position-relative float-end">
                        <img id="player" src="https://api.dicebear.com/6.x/lorelei/svg?flip=false&seed=<?php echo $player["name"] ?>" alt="Avatar" class="avatar float-end">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $player["sante"] ?>
                        </span>
                        <ul>
                            <li>Name : <?php echo $player["name"] ?></li>
                            <li>Attaque : <?php echo $player["attaque"] ?></li>
                            <li>Mana : <?php echo $player["mana"] ?></li>
                        </ul>
                    </div>
                </div>
                <div class="col-6">
                    <div class="position-relative float-start">
                        <img id="adversaire" src="https://api.dicebear.com/6.x/lorelei/svg?flip=true&seed=<?php echo $adversaire["name"] ?>" alt="Avatar" class="avatar">
                        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger">
                            <?php echo $adversaire["sante"] ?>
                        </span>
                        <ul>
                            <li>Name : <?php echo $adversaire["name"] ?></li>
                            <li>Attaque : <?php echo $adversaire["attaque"] ?></li>
                            <li>Mana : <?php echo $adversaire["mana"] ?></li>
                        </ul>
                    </div>
                </div>
                <?php if (!$endGame) { ?>
                    <form id='actionForm' action="index.php" method="post">
                        <div class="d-flex justify-content-center">
                            <input class="btn btn-outline-danger" name="attaque" type="submit" value="Attaquer">
                            <input class="btn btn-outline-success" name="soin" type="submit" value="Se soigner">
                        </div>
                        <div class="d-flex justify-content-center">
                            <input class="btn btn-outline-warning" name="restart" type="submit" value="Stopper le combat">
                        </div>
                    </form>
                <?php } else { ?>
                    <div id="Resultats">
                        <h1>Résultat</h1>
                        <?php echo $winner['name'] ?> est le vainqueur !
                        <form class="d-flex justify-content-center" action="" method="post">
                            <input class="btn btn-outline-warning" name="restart" type="submit" value="Nouveau combat">
                        </form>
                    </div>
                <?php } ?>
                <div id="combats">
                    <h2>Combat</h2>
                    <ul style="max-height: 300px; overflow: auto">
                        <?php foreach (array_reverse($actions) ?? [] as $action) { ?>
                            <li>
                                <i class="fa-solid fa-khanda p-1"></i><?php echo $action ?>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        <?php } ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let submitFight = document.querySelector("[name=fight]");
            let alreadyPlaySongFight = false;
            if (submitFight) {
                submitFight.addEventListener("click", function(event) {
                    if (alreadyPlaySongFight)
                        return true;
                    event.preventDefault();
                    submitFight.classList.add("animate__animated");
                    submitFight.classList.add("animate__rubberBand");
                    setTimeout(function() {
                        submitFight.classList.remove("animate__rubberBand");
                    }, 1000);
                    let fight_song = document.getElementById("fight-song");
                    fight_song.play();
                    alreadyPlaySongFight = true;
                    setTimeout(function() {
                        submitFight.click();
                    }, 500);
                })
            }

            let submitAttaque = document.querySelector("[name=attaque]");
            let alreadyPlaySong = false;
            if (submitAttaque) {
                submitAttaque.addEventListener("click", function(event) {
                    if (alreadyPlaySong)
                        return true;
                    event.preventDefault();
                    let player = document.querySelector("#player")
                    let adversaire = document.querySelector("#adversaire")
                    player.classList.add("animate__animated");
                    player.classList.add("animate__rubberBand");
                    adversaire.classList.add("bc-red");
                    submitAttaque.classList.add("animate__animated");
                    submitAttaque.classList.add("animate__rubberBand");
                    setTimeout(function() {
                        submitAttaque.classList.remove("animate__rubberBand");
                        player.classList.remove("animate__rubberBand");
                        adversaire.classList.remove("bc-red");
                    }, 1000);
                    let hadouken_song = document.getElementById("hadoudken-song");
                    hadouken_song.play();
                    alreadyPlaySong = true;
                    setTimeout(function() {
                        submitAttaque.click();
                    }, 1000);
                })
            }

            let submitRestart = document.querySelector("[name=restart]");
            let alreadyPlaySongRestart = false;
            if (submitRestart) {
                submitRestart.addEventListener("click", function(event) {
                    if (alreadyPlaySongRestart)
                        return true;
                    event.preventDefault();
                    let fatality_song = document.getElementById("fatality-song");
                    fatality_song.play();
                    alreadyPlaySongRestart = true;
                    setTimeout(function() {
                        submitRestart.click();
                    }, 2000);
                })
            }
        });

        const selectPlayer = document.querySelector("[name='player[id]']");
        const selectAdversaire = document.querySelector("[name='adversaire[id]']");
        let selectedPlayerId, selectedAdversaireId;
        [selectPlayer, selectAdversaire].forEach((select) => {
            select.addEventListener("click", (event) => {
                event.preventDefault();
                let playerId = select.value;
                (select == selectPlayer) ? selectedPlayerId = playerId: selectedAdversaireId = playerId;
                if (selectedPlayerId != undefined && selectedPlayerId != '') {
                    (select.options[selectedPlayerId]).disabled = true
                };
                if (selectedAdversaireId != undefined && selectedAdversaireId != '') {
                    (select.options[selectedAdversaireId]).disabled = true
                };
                let selected = select.options[select.selectedIndex];
                let playerName = selected.text;
                let playerNameInput = (select == selectPlayer) ? document.querySelector("[name='player[name]']") : document.querySelector("[name='adversaire[name]']");
                playerNameInput.value = playerName;
                let playerStats = (select == selectPlayer) ? document.getElementById('playerStats') : document.getElementById('adversaireStats');
                playerStats.style.display = (playerId != "") ? "none" : "block";
            })
        })
    </script>
    <style>
        .avatar {
            vertical-align: middle;
            width: 100px;
            border-radius: 50%;
        }

        .bc-red {
            background-color: red;
        }
    </style>

</body>

</html>