<?php
// app/src/Services/DebugService.php

namespace App\Services;

class DebugService {

    private $error = false;

    public function getError() {
        return $this->error;
    }

    public function debug() {
        $data = $_POST['data'] ?? '';
        $label = $_POST['label'] ?? '';
        
        if (empty($data)) {
            return ['status' => 'error', 'message' => 'No data to debug'];
        }

        // Décoder les données JSON
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

        // Utiliser ta fonction lecho existante
        if($label){
            lecho($label);
        }
        lecho($data);
        
        return ['status' => 'success', 'message' => 'Data sent to log'];
    }
}