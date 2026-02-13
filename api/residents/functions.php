<?php
function addResident($nom, $prenom, $etage, $chambre, $portion, $type_cerealier_matin, $nbr_cereal_matin, $preparation_matin, $boisson_matin, $supp_matin, $garniture_matin, $notes_matin, $boisson_midi, $texture_midi, $type_cereal_midi_plus, $type_cerealier_soir, $nbr_cereal_soir, $preparation_soir, $boisson_soir, $supp_soir, $bdd)
{
    $req = $bdd->prepare('INSERT INTO residents(nom, prenom, etage, chambre, portion, type_cerealier_matin, nbr_cereal_matin, preparation_matin, boisson_matin, supp_matin, garniture_matin, notes_matin, boisson_midi, texture_midi, type_cereal_midi_plus, type_cerealier_soir, nbr_cereal_soir, preparation_soir, boisson_soir, supp_soir) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    if ($req->execute(
        array(
            $nom,
            $prenom,
            $etage,
            $chambre,
            $portion,
            $type_cerealier_matin,
            $nbr_cereal_matin,
            $preparation_matin,
            $boisson_matin,
            $supp_matin,
            $garniture_matin,
            $notes_matin,
            $boisson_midi,
            $texture_midi,
            $type_cereal_midi_plus,
            $type_cerealier_soir,
            $nbr_cereal_soir,
            $preparation_soir,
            $boisson_soir,
            $supp_soir
        )
    )) {
        $_SESSION['valid'] = 'Le résident a été ajouté avec succès';
    } else {
        $_SESSION['error'] = 'Une erreur est survenue lors de l\'ajout du résident';
    }
}

function updateResident($id, $nom, $prenom, $etage, $chambre, $portion, $type_cerealier_matin, $nbr_cereal_matin, $preparation_matin, $boisson_matin, $supp_matin, $garniture_matin, $notes_matin, $boisson_midi, $texture_midi, $type_cereal_midi_plus, $type_cerealier_soir, $nbr_cereal_soir, $preparation_soir, $boisson_soir, $supp_soir, $bdd)
{
    $req = $bdd->prepare('UPDATE residents SET nom=?, prenom=?, etage=?, chambre=?, portion=?, type_cerealier_matin=?, nbr_cereal_matin=?, preparation_matin=?, boisson_matin=?, supp_matin=?, garniture_matin=?, notes_matin=?, boisson_midi=?, texture_midi=?, type_cereal_midi_plus=?, type_cerealier_soir=?, nbr_cereal_soir=?, preparation_soir=?, boisson_soir=?, supp_soir=? WHERE id=?');
    if ($req->execute(
        array(
            $nom,
            $prenom,
            $etage,
            $chambre,
            $portion,
            $type_cerealier_matin,
            $nbr_cereal_matin,
            $preparation_matin,
            $boisson_matin,
            $supp_matin,
            $garniture_matin,
            $notes_matin,
            $boisson_midi,
            $texture_midi,
            $type_cereal_midi_plus,
            $type_cerealier_soir,
            $nbr_cereal_soir,
            $preparation_soir,
            $boisson_soir,
            $supp_soir,
            $id
        )
    )) {
        $_SESSION['valid'] = 'Le résident a été modifié avec succès';
    } else {
        $_SESSION['error'] = 'Une erreur est survenue lors de la modification du résident';
    }
}

function deleteResident($id, $bdd)
{
    $req = $bdd->prepare('DELETE FROM residents WHERE id=?');
    if ($req->execute(array($id))) {
        $_SESSION['valid'] = 'Le résident a été supprimé avec succès';
    } else {
        $_SESSION['error'] = 'Une erreur est survenue lors de la suppression du résident';
    }
}

function getResidents($bdd)
{
    $req = $bdd->prepare('SELECT * FROM residents');
    $req->execute();
    return $req->fetchAll();
}
