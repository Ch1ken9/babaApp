<?php

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
        // $data = array_merge($data, parseMatin($chambre['matin']));
    }

    if (isset($chambre['midi'])) {
        $data = array_merge($data, parseMidi($chambre['midi']));
    }

    if (isset($chambre['soir'])) {
        // $data = array_merge($data, parseSoir($chambre['soir']));
    }

    // insertBeneficiaire($data);
}


function parseMidi($file)
{
    $data = [
        'chambre' => '',
        'nom' => '',
        'prenom' => '',
        'etage' => '',
        'aversions_midi' => '',
        'remarques_midi' => '',
        'tartines_midi' => '',
        'boisson_midi' => '',
        'quantite_midi' => '',
        'texture_midi' => '',
        'potage_midi' => ''
    ];
    
    if (($handle = fopen($file, "r")) !== false) {
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $row = array_map('trim', $row);
            $originalRow = $row; // Garder la version originale pour des vérifications
            $row = array_filter($row);
            $row = array_values($row);
            
            if (empty($row)) {
                continue;
            }
            
            /* recup infos du bene */
            if (isset($row[0]) && preg_match('/^(\d+)\s+([A-Za-zÀ-ÿ]+)\s+([A-Za-zÀ-ÿ]+)\s+(.+)$/', $row[0], $matches)) {
                $data['chambre'] = trim($matches[1]);
                $data['nom'] = trim($matches[2]);
                $data['prenom'] = trim($matches[3]);
                $data['etage'] = trim($matches[4]);
            }
            
            /* Aversions */
            $aNePasLire = ['TEXTURE', 'ENTIER', 'COUPE', 'MOULU', 'MIXE', 'FINGER FOOD', 'PANADE SUCREE', 'X'];

            foreach ($row as $index => $cell) {
                if (stripos($cell, 'Aversions') !== false) {
                    $aversionValue = '';
                    
                    // Extraire ce qui suit "Aversions :" dans la même cellule
                    if (preg_match('/Aversions\s*:\s*(.+)/', $cell, $aversionMatch)) {
                        $aversionValue = trim($aversionMatch[1]);
                    }
                    
                    // Si vide, chercher dans la cellule suivante
                    if (empty($aversionValue) && isset($row[$index + 1])) {
                        $aversionValue = trim($row[$index + 1]);
                    }
                    
                    // Vérifier que ce n'est pas un mot-clé technique
                    $isValid = !empty($aversionValue);
                    foreach ($aNePasLire as $keyword) {
                        if (stripos($aversionValue, $keyword) !== false) {
                            $isValid = false;
                            break;
                        }
                    }
                    
                    // Si valide, on garde, sinon on laisse vide
                    if ($isValid) {
                        $data['aversions_midi'] = $aversionValue;
                    }
                    
                    break;
                }
            }

            
            /* Elements avec X */
            if (in_array('X', $row)) {
                $posX = array_search('X', $row);
                

                for ($i = $posX - 1; $i >= 0; $i--) {
                    if (!empty($row[$i])) { // recup le label
                        $label = str_replace('▪ ', '', $row[$i]);
                        $label = trim($label);
                        
                        // Boisson
                        if (in_array($label, ['EAU PLATE','EAU PETILLANTE','BIERE BRUNE','BIERE BLONDE','EAUX AROMATISEE'])) {
                            if (!empty($data['boisson_midi'])) {
                                $data['boisson_midi'] .= ', ';
                            }
                            $data['boisson_midi'] .= $label;
                        }
                        
                        // Quantite
                        if (in_array($label, ['Petite', 'Moyenne', 'Grande'])) {
                            $data['quantite_midi'] = $label;
                        }
                        
                        // Texture
                        if (in_array($label, ['ENTIER', 'COUPE', 'MOULU', 'MIXE','FINGER FOOD','PANADE SUCREE'])) {
                            $data['texture_midi'] = $label;
                        }
                        
                        // Potage
                        if (in_array($label, ['Potage','Potage normal','Potage enrichi','Potage s/sel'])) {
                            $data['potage_midi'] = $label;
                        }
                        
                        break;
                    }
                }
            }

            /* Remarques */
            foreach ($row as $index => $cell) {
                if (stripos($cell, 'Remarques') !== false) {
                    // Chercher la valeur après "Remarques :"
                    if (isset($row[$index + 1])) {
                        $data['remarques_midi'] = $row[$index + 1];
                    }
                    // Ou si c'est dans la même cellule après les ":"
                    if (preg_match('/Remarques\s*:\s*(.+)/', $cell, $remarqueMatch)) {
                        $data['remarques_midi'] = trim($remarqueMatch[1]);
                    }
                    break;
                }
            }

                        
            /* Infos tartines */
            foreach ($row as $cell) {
                if (stripos($cell, 'TARTINE EN +') !== false) {

                    if (preg_match('/TARTINE EN \+\s*:\s*(.+)/', $cell, $tartineMatch)) { // recup valeur apres tatrines :
                        if(trim($tartineMatch[1]!== '………..')){
                            $data['tartines_midi'] = trim($tartineMatch[1]);
                        }
                    }
                    break;
                }
            }
        }
        fclose($handle);
    }
    
    return $data;
}



function parseSoir($file){

}



echo "<pre>";
print_r($data);
echo "</pre>";