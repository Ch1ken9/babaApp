<?php

include_once '../../bdd/connexion_bdd.php';

$AllFiles = glob("../../assests/csv/*/*.csv");

$regroupment = []; // Là ou je vais regouper par num chambre les fichiers

foreach ($AllFiles as $file) {

    $filename = basename($file);

    preg_match('/(\d+)(?!.*\d)/', $filename, $matches); // je cherche une suite de chiffres non suivi d'un chiffre pour que Fiche matin 1er.xlsx - 157.csv soit selectionné
    $chambre = $matches[1] ?? null;


    if (stripos($filename, 'midi') !== false) {
        $moment = 'midi';
    } elseif (stripos($filename, 'soir') !== false) {
        $moment = 'soir';
    } else {
        $moment = 'matin';
    }

    $regroupment[$chambre][$moment] = $file;
}

foreach ($regroupment as $chambre) {
    $data = [];

    if (isset($chambre['matin'])) {
        $data = array_merge($data, parseMatin($chambre['matin']));
    }

    if (isset($chambre['midi'])) {
        $data = array_merge($data, parseMidi($chambre['midi']));
    }

    if (isset($chambre['soir'])) {
        $data = array_merge($data, parseSoir($chambre['soir']));
    }

    insertResident($data);
}

function parseMatin($file){
    $data = [
        'nom' => null,
        'prenom' => null,
        'etage' => null,
        'chambre' => null,
        'boisson_matin' => [],
        'type_cerealier_matin' => null,
        'nbr_cereal_matin' => 0,
        'preparation_matin' => null,
        'notes_matin' => null,
        'supp_matin' => null,
    ];

    if (($handle = fopen($file, "r")) !== false) {
        $lignes = [];
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $lignes[] = $row;
        }
        fclose($handle);

        $capturerRemarqueSuivante = false;

        foreach ($lignes as $idxLigne => $originalRow) {
            $row = array_map('trim', $originalRow);
            $rowFiltered = array_filter($row);
            $rowFiltered = array_values($rowFiltered);
            
            if (empty($rowFiltered)) {
                continue;
            }

            // Capturer remarque ligne suivante
            if ($capturerRemarqueSuivante) {
                $data['notes_matin'] = $rowFiltered[0];
                $capturerRemarqueSuivante = false;
                continue;
            }
            
            /* Recup infos du bene */
            if (isset($rowFiltered[0]) && preg_match('/^(?:Ch\s+)?(\d+)\s+([A-Za-zÀ-ÿ]+)\s+([A-Za-zÀ-ÿ]+)\s+(.+)$/', $rowFiltered[0], $matches)) {
                $data['chambre'] = trim($matches[1]);
                $data['nom'] = trim($matches[2]);
                $data['prenom'] = trim($matches[3]);
                $data['etage'] = trim($matches[4]);
            }

            /* Remarques */
            foreach ($rowFiltered as $index => $cell) {
                if (stripos($cell, 'Remarques') !== false) {
                    if (preg_match('/Remarques\s*:\s*(.+)/', $cell, $remarqueMatch)) {
                        $valeur = trim($remarqueMatch[1]);
                        if (!empty($valeur)) {
                            $data['notes_matin'] = $valeur;
                        }
                    } else {
                        $capturerRemarqueSuivante = true;
                    }
                    break;
                }
            }

            /* Type et Qte cereal*/
            foreach ($rowFiltered as $index => $cell) {
                if (in_array($cell, ['BLANC', 'GRIS'])) {
                    if (isset($rowFiltered[$index + 1]) && is_numeric($rowFiltered[$index + 1])) {
                        $data['type_cerealier_matin'] = 'PAIN '.$cell;
                        $data['nbr_cereal_matin'] = $rowFiltered[$index + 1];
                        break;
                    }
                }
            }

            /* Traiter les X - utiliser originalRow pour garder les index de colonnes */
            foreach ($row as $colIndex => $cell) {
                if (trim($cell) === 'X') {
                    $label = null;

                    // Chercher le premier label à gauche
                    for ($i = $colIndex - 1; $i >= 0; $i--) {
                        if (!empty(trim($row[$i])) && trim($row[$i]) !== 'X') {
                            $potentielLabel = str_replace(['▪ ', '▫ '], '', trim($row[$i]));
                            if (!is_numeric($potentielLabel) && strlen($potentielLabel) > 1) {
                                $label = $potentielLabel;
                                break;
                            }
                        }
                    }

                    // Si pas trouvé on regarde la ligne d'au dessus
                    if ($label === null && $idxLigne > 0) {
                        for ($lignePrec = $idxLigne - 1; $lignePrec >= max(0, $idxLigne - 3); $lignePrec--) {
                            // Chercher à la même colonne et colonnes adjacentes
                            for ($col = max(0, $colIndex - 2); $col <= min(count($row) - 1, $colIndex + 2); $col++) {
                                if (isset($lignes[$lignePrec][$col]) && !empty(trim($lignes[$lignePrec][$col])) && trim($lignes[$lignePrec][$col]) !== 'X') {
                                    $potentielLabel = str_replace(['▪ ', '▫ '], '', trim($lignes[$lignePrec][$col]));
                                    if (!is_numeric($potentielLabel) && strlen($potentielLabel) > 1) {
                                        $label = $potentielLabel;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }

                    // Traiter le label trouvé
                    if ($label !== null) {
                        // Boisson
                        if (in_array($label, ['CAFE', 'THE','LAIT', 'SUCRE', 'CHICOREE', 'LAIT CHAUD', 'CACAO', "JUS D'ORANGE", 'JUS + JUCY'])) {
                            if (!in_array($label, $data['boisson_matin'])) {
                                $data['boisson_matin'][] = $label;
                            }
                        }
                
                        // A Faire
                        if ($label === 'A FAIRE' && strpos($data['preparation_matin'], 'A FAIRE') === false) {
                            $data['preparation_matin'] = $data['preparation_matin'] != null ? $data['preparation_matin'] . ' + ' . $label : $label;
                        }
                        
                        // Sans Croute
                        if ($label === 'SANS CROUTE' && strpos($data['preparation_matin'], 'SANS CROUTE') === false) {
                            $data['preparation_matin'] = $data['preparation_matin'] != null ? $data['preparation_matin'] . ' + ' . $label : $label;
                        }

                        // Panade Sucree
                        if ($label === 'PANADE SUCREE' && strpos($data['supp_matin'], 'PANADE SUCREE') === false) {
                            $data['supp_matin'] = $data['supp_matin'] != null ? $data['supp_matin'] . ' + ' . $label : $label;
                        }
                        
                        // Collation Dia
                        if ($label === 'COLLATION DIA' && strpos($data['supp_matin'], 'COLLATION DIA') === false) {
                            $data['supp_matin'] = $data['supp_matin'] != null ? $data['supp_matin'] . ' + ' . $label : $label;
                        }
                    }
                }
            }
        }
    }

    $data['boisson_matin'] = !empty($data['boisson_matin']) ? implode(', ', $data['boisson_matin']) : null;

    return $data;
}

function parseMidi($file){
    $data = [
        'nom' => null,
        'prenom' => null,
        'etage' => null,
        'chambre' => null,
        'portion' => null,
        'aversions_midi' => [],
        'notes_midi' => null,
        'boisson_midi' => [],
        'texture_midi' => null,
        'type_cereal_midi_plus' => null,
    ];
    
    if (($handle = fopen($file, "r")) !== false) {
        $lignes = [];
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $lignes[] = $row;
        }
        fclose($handle);

        $capturerRemarqueSuivante = false;

        foreach ($lignes as $idxLigne => $row) {
            $row = array_map('trim', $row);
            $originalRow = $row;
            $row = array_filter($row);
            $row = array_values($row);

            
            if (empty($row)) {
                continue;
            }

            // Capturer remarque ligne suivante
            if ($capturerRemarqueSuivante) {
                $data['remarques_midi'] = $row[0];
                $capturerRemarqueSuivante = false;
                continue;
            }
            
            /* Recup infos du bene */
            if (isset($row[0]) && preg_match('/^(?:Ch\s+)?(\d+)\s+([A-Za-zÀ-ÿ]+)\s+([A-Za-zÀ-ÿ]+)\s+(.+)$/', $row[0], $matches)) {
                $data['chambre'] = trim($matches[1]);
                $data['nom'] = trim($matches[2]);
                $data['prenom'] = trim($matches[3]);
                $data['etage'] = trim($matches[4]);
            }
            
            /* Aversions */

            /* TODO */


            /* Remarques */
            foreach ($row as $index => $cell) {
                if (stripos($cell, 'Remarques') !== false) {
                    if (preg_match('/Remarques\s*:\s*(.+)/', $cell, $remarqueMatch)) {
                        $valeur = trim($remarqueMatch[1]);
                        if (!empty($valeur)) {
                            $data['remarques_midi'] = $valeur;
                        }
                    } else {
                        // Remarque sur ligne suivante
                        $capturerRemarqueSuivante = true;
                    }
                    break;
                }
            }
            
            /* Infos tartines */
            foreach ($row as $cell) {
                if (stripos($cell, 'TARTINE EN +') !== false) {
                    if (preg_match('/TARTINE EN \+\s*:\s*(.+)/', $cell, $tartineMatch)) {
                        $valeur = trim($tartineMatch[1]);
                        if ($valeur !== '………..' && !empty($valeur)) {
                            $data['type_cereal_midi_plus'] = $valeur;
                        }
                    }
                    break;
                }
            }

            /* TRAITER TOUS LES X de la ligne */
            $positionsX = array_keys($row, 'X');
            
            foreach ($positionsX as $posX) {
                // Chercher le label à gauche du X
                for ($i = $posX - 1; $i >= 0; $i--) {
                    if (!empty($row[$i])) {
                        $label = str_replace('▪ ', '', $row[$i]);
                        $label = trim($label);

                        // Boissons
                        if (in_array($label, ['EAU PLATE', 'EAU PETILLANTE', 'BIERE BRUNE', 'BIERE BLONDE', 'EAU AROMATISEE'])) {
                            $data['boisson_midi'][] = $label;
                        }
                        
                        // Quantite
                        if (in_array($label, ['Petite', 'Moyenne', 'Grande'])) {
                            $data['portion'] = $label;
                        }
                        
                        // Texture
                        if (in_array($label, ['ENTIER', 'COUPE', 'MOULU', 'MIXE', 'FINGER FOOD', 'PANADE SUCREE'])) {
                            $data['texture_midi'] = $label;
                        }
                        
                        break;
                    }
                }
            }
        }
    }

    $data['boisson_midi'] = !empty($data['boisson_midi']) ? implode(', ', $data['boisson_midi']) : null;
    $data['aversions_midi'] = !empty($data['aversions_midi']) ? implode(', ', $data['aversions_midi']) : null;

    
    return $data;
}



function parseSoir($file){
    $data = [
        'nom' => null,
        'prenom' => null,
        'etage' => null,
        'chambre' => null,
        'type_cerealier_soir' => null,
        'nbr_cereal_soir' => 0,
        'preparation_soir' => null,
        'boisson_soir' => [],
        'supp_soir' => null,
        'notes_soir' => null,
    ];

    if (($handle = fopen($file, "r")) !== false) {
            $lignes = [];
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $lignes[] = $row;
            }
            fclose($handle);

            $capturerRemarqueSuivante = false;

            foreach ($lignes as $idxLigne => $row) {
                $row = array_map('trim', $row);
                $originalRow = $row;
                $row = array_filter($row);
                $row = array_values($row);
                
                if (empty($row)) {
                    continue;
                }

                // Capturer remarque ligne suivante
                if ($capturerRemarqueSuivante) {
                    $data['notes_soir'] = $row[0];
                    $capturerRemarqueSuivante = false;
                    continue;
                }
                
                /* Recup infos du bene */
                if (isset($row[0]) && preg_match('/^(?:Ch\s+)?(\d+)\s+([A-Za-zÀ-ÿ]+)\s+([A-Za-zÀ-ÿ]+)\s+(.+)$/', $row[0], $matches)) {
                    $data['chambre'] = trim($matches[1]);
                    $data['nom'] = trim($matches[2]);
                    $data['prenom'] = trim($matches[3]);
                    $data['etage'] = trim($matches[4]);
                }

                /* Remarques */
                foreach ($row as $index => $cell) {
                    if (stripos($cell, 'Remarques') !== false) {
                        if (preg_match('/Remarques\s*:\s*(.+)/', $cell, $remarqueMatch)) {
                            $valeur = trim($remarqueMatch[1]);
                            if (!empty($valeur)) {
                                $data['notes_soir'] = $valeur;
                            }
                        } else {
                            // Remarque sur ligne suivante
                            $capturerRemarqueSuivante = true;
                        }
                        break;
                    }
                }

                /* Type et Qte cereal*/
                foreach ($row as $index => $cell) {
                    if (in_array($cell, ['BLANC', 'GRIS'])) {
                        if (isset($row[$index + 1]) && is_numeric($row[$index + 1])) {
                            $data['type_cerealier_soir'] = 'PAIN '.$cell;
                            $data['nbr_cereal_soir'] = $row[$index + 1];
                            break;
                        }
                    }
                }

            /* Traiter les X - utiliser originalRow pour garder les index de colonnes */
            foreach ($row as $colIndex => $cell) {
                if (trim($cell) === 'X') {
                    $label = null;

                    // Chercher le premier label à gauche
                    for ($i = $colIndex - 1; $i >= 0; $i--) {
                        if (!empty(trim($row[$i])) && trim($row[$i]) !== 'X') {
                            $potentielLabel = str_replace(['▪ ', '▫ '], '', trim($row[$i]));
                            if (!is_numeric($potentielLabel) && strlen($potentielLabel) > 1) {
                                $label = $potentielLabel;
                                break;
                            }
                        }
                    }

                    // Si pas trouvé on regarde la ligne d'au dessus
                    if ($label === null && $idxLigne > 0) {
                        for ($lignePrec = $idxLigne - 1; $lignePrec >= max(0, $idxLigne - 3); $lignePrec--) {
                            // Chercher à la même colonne et colonnes adjacentes
                            for ($col = max(0, $colIndex - 2); $col <= min(count($row) - 1, $colIndex + 2); $col++) {
                                if (isset($lignes[$lignePrec][$col]) && !empty(trim($lignes[$lignePrec][$col])) && trim($lignes[$lignePrec][$col]) !== 'X') {
                                    $potentielLabel = str_replace(['▪ ', '▫ '], '', trim($lignes[$lignePrec][$col]));
                                    if (!is_numeric($potentielLabel) && strlen($potentielLabel) > 1) {
                                        $label = $potentielLabel;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }

                    // Traiter le label trouvé
                    if ($label !== null) {
                        // Boisson
                        if (in_array($label, ['CAFE', 'THE', 'LAIT', 'SUCRE', 'LAIT CHAUD', 'CACAO', "POTAGE"])) {
                            if (!in_array($label, $data['boisson_soir'])) {
                                $data['boisson_soir'][] = $label;
                            }
                        }
                
                        // A Faire
                        if ($label === 'A FAIRE' && strpos($data['preparation_soir'], 'A FAIRE') === false) {
                            $data['preparation_soir'] = $data['preparation_soir'] != null ? $data['preparation_soir'] . ' + ' . $label : $label;
                        }
                        
                        // Sans Croute
                        if ($label === 'SANS CROUTE' && strpos($data['preparation_soir'], 'SANS CROUTE') === false) {
                            $data['preparation_soir'] = $data['preparation_soir'] != null ? $data['preparation_soir'] . ' + ' . $label : $label;
                        }

                        // Panade Sucree
                        if ($label === 'PANADE SUCREE' && strpos($data['supp_soir'], 'PANADE SUCREE') === false) {
                            $data['supp_soir'] = $data['supp_soir'] != null ? $data['supp_soir'] . ' + ' . $label : $label;
                        }
                        
                        // Creme Dia
                        if ($label === 'CREME DIA' && strpos($data['supp_soir'], 'CREME DIA') === false) {
                            $data['supp_soir'] = $data['supp_soir'] != null ? $data['supp_soir'] . ' + ' . $label : $label;
                        }
                        
                        // Creme Enrichie
                        if ($label === 'CREME ENRICHIE' && strpos($data['supp_soir'], 'CREME ENRICHIE') === false) {
                            $data['supp_soir'] = $data['supp_soir'] != null ? $data['supp_soir'] . ' + ' . $label : $label;
                        }
                    }
                }
            }
        }
    }

    $data['boisson_soir'] = !empty($data['boisson_soir']) ? implode(', ', $data['boisson_soir']) : null;

    return $data;
}

function insertResident($data)
{
    $sql = "
        INSERT INTO residents (
            nom,
            prenom,
            etage,
            chambre,
            portion,
            type_cerealier_matin,
            nbr_cereal_matin,
            preparation_matin,
            boisson_matin,
            supp_matin,
            notes_matin,
            boisson_midi,
            texture_midi,
            type_cereal_midi_plus,
            aversions_midi,
            type_cerealier_soir,
            nbr_cereal_soir,
            preparation_soir,
            boisson_soir,
            supp_soir,
            notes_soir
        ) VALUES (
            :nom,
            :prenom,
            :etage,
            :chambre,
            :portion,
            :type_cerealier_matin,
            :nbr_cereal_matin,
            :preparation_matin,
            :boisson_matin,
            :supp_matin,
            :notes_matin,
            :boisson_midi,
            :texture_midi,
            :type_cereal_midi_plus,
            :aversions_midi,
            :type_cerealier_soir,
            :nbr_cereal_soir,
            :preparation_soir,
            :boisson_soir,
            :supp_soir,
            :notes_soir
        )
    ";

    $stmt = $bdd->prepare($sql);

    return $stmt->execute([
        ':nom' => $data['nom'],
        ':prenom' => $data['prenom'],
        ':etage' => $data['etage'] ,
        ':chambre' => $data['chambre'] ,
        ':portion' => $data['portion'] ,
        ':type_cerealier_matin' => $data['type_cerealier_matin'] ,
        ':nbr_cereal_matin' => $data['nbr_cereal_matin'] ,
        ':preparation_matin' => $data['preparation_matin'] ,
        ':boisson_matin' => $data['boisson_matin'] ,
        ':supp_matin' => $data['supp_matin'] ,
        ':notes_matin' => $data['notes_matin'] ,
        ':boisson_midi' => $data['boisson_midi'] ,
        ':texture_midi' => $data['texture_midi'] ,
        ':type_cereal_midi_plus' => $data['type_cereal_midi_plus'] ,
        ':aversions_midi' => $data['aversions_midi'] ,
        ':type_cerealier_soir' => $data['type_cerealier_soir'] ,
        ':nbr_cereal_soir' => $data['nbr_cereal_soir'] ,
        ':preparation_soir' => $data['preparation_soir'] ,
        ':boisson_soir' => $data['boisson_soir'] ,
        ':supp_soir' => $data['supp_soir'] ,
        ':notes_soir' => $data['notes_soir'] ,
    ]);
}