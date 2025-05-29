<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';


class SettingsController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all settings
     */
    public function getAllSettings() {
        $stmt = $this->pdo->prepare("SELECT * FROM settings ORDER BY setting_key ASC");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to key-value array for easier access
        $settings_array = [];
        foreach ($settings as $setting) {
            $settings_array[$setting['setting_key']] = $setting['setting_value'];
        }
        
        return $settings_array;
    }
    
    /**
     * Update settings
     */
    public function updateSettings($settings) {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($settings as $key => $value) {
                // Check if setting exists
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                
                if ($exists) {
                    // Update existing setting
                    $stmt = $this->pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                } else {
                    // Insert new setting
                    $stmt = $this->pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$key, $value]);
                }
            }
            
            $this->pdo->commit();
            
            // Log activity
            if (function_exists('log_activity')) {
                log_activity('update', 'settings', 0, 'Settings updated by admin');
            }
            
            return ['success' => true, 'message' => 'Settings updated successfully'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}