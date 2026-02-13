<?php
include_once '../includes.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h1>Liste des résidents</h1>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Etage</th>
                <th>Chambre</th>
                <th>Portion</th>
                <th>Type de céréalier matin</th>
                <th>Nombre de céréalier matin</th>
                <th>Préparation matin</th>
                <th>Boisson matin</th>
                <th>Supplément matin</th>
                <th>Garniture matin</th>
                <th>Notes matin</th>
                <th>Boisson midi</th>
                <th>Texture midi</th>
                <th>Type de céréalier midi plus</th>
                <th>Type de céréalier soir</th>
                <th>Nombre de céréalier soir</th>
                <th>Préparation soir</th>
                <th>Boisson soir</th>
                <th>Supplément soir</th>
                <th>Garniture soir</th>
                <th>Notes soir</th>
            </tr>
        </thead>
        <tbody>
            <?php
            echo "<tr>";
            echo "<td>(Nom)</td>";
            echo "<td>(Prenom)</td>";
            echo "<td>(Etage)</td>";
            echo "<td>(Chambre)</td>";
            echo "<td>(Portion)</td>";
            echo "<td>Pain Blanc</td>";
            echo "<td>1,2,3</td>";
            echo "<td>Préparation1,Préparation2,Préparation3</td>";
            echo "<td>Boisson1,Boisson2,Boisson3</td>";
            echo "<td>Supplément1,Supplément2,Supplément3</td>";
            echo "<td>Notes</td>";
            echo "<td>(Boisson midi)</td>";
            echo "<td>Texture1,Texture2,Texture3</td>";
            echo "<td>3 Pain Blanc (preparation)</td>";
            echo "<td>Pain Blanc</td>";
            echo "<td>1,2,3</td>";
            echo "<td>Préparation1,Préparation2,Préparation3</td>";
            echo "<td>Boisson1,Boisson2,Boisson3</td>";
            echo "<td>Supplément1,Supplément2,Supplément3</td>";
            echo "<td>Notes</td>";
            echo "</tr>";

            $residents = getResidents($bdd);
            foreach ($residents as $resident) {
                echo "<tr>";
                echo "<td>" . $resident['nom'] . "</td>";
                echo "<td>" . $resident['prenom'] . "</td>";
                echo "<td>" . $resident['etage'] . "</td>";
                echo "<td>" . $resident['chambre'] . "</td>";
                echo "<td>" . $resident['portion'] . "</td>";
                echo "<td>" . $resident['type_cerealier_matin'] . "</td>";
                echo "<td>" . $resident['nbr_cereal_matin'] . "</td>";
                echo "<td>" . $resident['preparation_matin'] . "</td>";
                echo "<td>" . $resident['boisson_matin'] . "</td>";
                echo "<td>" . $resident['supp_matin'] . "</td>";
                echo "<td>" . $resident['garniture_matin'] . "</td>";
                echo "<td>" . $resident['notes_matin'] . "</td>";
                echo "<td>" . $resident['boisson_midi'] . "</td>";
                echo "<td>" . $resident['texture_midi'] . "</td>";
                echo "<td>" . $resident['type_cereal_midi_plus'] . "</td>";
                echo "<td>" . $resident['type_cerealier_soir'] . "</td>";
                echo "<td>" . $resident['nbr_cereal_soir'] . "</td>";
                echo "<td>" . $resident['preparation_soir'] . "</td>";
                echo "<td>" . $resident['boisson_soir'] . "</td>";
                echo "<td>" . $resident['supp_soir'] . "</td>";
                echo "<td>" . $resident['garniture_soir'] . "</td>";
                echo "<td>" . $resident['notes_soir'] . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>


    <?php
    /*
            Creer un script php qui parcours tous les csv et ajoute les infos dans la db
    */
    ?>
</body>

</html>