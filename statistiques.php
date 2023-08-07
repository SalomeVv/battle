<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/sql.php';

$dbco = connectDB();
$existingPlayers = getExistingPlayers($dbco);
$colors = [
    'rgba(249, 65, 68, 0.6)',
    'rgba(243, 114, 44, 0.6)',
    'rgba(248, 150, 30, 0.6)',
    'rgba(249, 132, 74, 0.6)',
    'rgba(249, 199, 79, 0.6)',
    'rgba(144, 190, 109, 0.6)',
    'rgba(67, 170, 139, 0.6)',
    'rgba(77, 144, 142, 0.6)',
    'rgba(87, 117, 144, 0.6)',
    'rgba(39, 125, 161, 0.6)'
];
$statColors = getStatColors($existingPlayers, $colors);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistique</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="public/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="container">
        <h1 class="card-title text-center">Les Statistiques de combats</h1>
        <hr>
        <div class="row">
            <div class="col-6">Le nombres total de combats : <?php echo fightCounts($dbco); ?></div>
            <div class="col-6">Le combatant avec le plus de victoire : <?php echo mostWin($dbco); ?></div>
            <div class="col-6">Le combatant avec le plus de défaite : <?php echo ultLoser($dbco); ?></div>
            <div class="col-6">Le nombre de match non terminés : <?php echo unfinished($dbco); ?></div>
        </div>
        <hr>
        <canvas id="myChart"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('myChart');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php
                            foreach ($existingPlayers as $ePlayer) {
                                echo "'{$ePlayer['name']}', ";
                            }
                            ?>],
                datasets: [{
                    label: 'Combats gagnés',
                    data: [<?php
                            foreach ($existingPlayers as $ePlayer) {
                                $playerWins = (null != nbWin($dbco, $ePlayer['id'])) ? nbWin($dbco, $ePlayer['id']) : 0;
                                echo "$playerWins, ";
                            }
                            ?>],
                    backgroundColor: [
                        <?php
                        foreach ($statColors as $sColor) {
                            echo "'$sColor', ";
                        }
                        ?>
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

</body>

</html>