<?php
/**
 * Migration Script: Add Approval System to Group Tables
 * Run this script once to add approval_status columns to existing tables
 */

require_once __DIR__ . '/config/config.php';

$conn = getDBConnection();

echo "<h2>Migration: Adding Approval System</h2>";
echo "<pre>";

try {
    // Add approval columns to group_notes
    echo "1. Adding approval columns to group_notes...\n";
    $sql = "ALTER TABLE group_notes 
            ADD COLUMN IF NOT EXISTS approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL";
    
    // MySQL doesn't support IF NOT EXISTS for columns, so we'll check first
    $check = $conn->query("SHOW COLUMNS FROM group_notes LIKE 'approval_status'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_notes 
                     ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        echo "   ✓ Added approval_status column\n";
    } else {
        echo "   - approval_status column already exists\n";
    }
    
    $check = $conn->query("SHOW COLUMNS FROM group_notes LIKE 'approved_by'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_notes ADD COLUMN approved_by INT DEFAULT NULL");
        echo "   ✓ Added approved_by column\n";
    } else {
        echo "   - approved_by column already exists\n";
    }
    
    $check = $conn->query("SHOW COLUMNS FROM group_notes LIKE 'approved_at'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_notes ADD COLUMN approved_at DATETIME DEFAULT NULL");
        echo "   ✓ Added approved_at column\n";
    } else {
        echo "   - approved_at column already exists\n";
    }
    
    // Add foreign key for approved_by if it doesn't exist
    $fk_check = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME = 'group_notes' 
                              AND COLUMN_NAME = 'approved_by' 
                              AND REFERENCED_TABLE_NAME IS NOT NULL");
    if ($fk_check->num_rows === 0) {
        $conn->query("ALTER TABLE group_notes 
                     ADD CONSTRAINT fk_group_notes_approved_by 
                     FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "   ✓ Added foreign key for approved_by\n";
    } else {
        echo "   - Foreign key for approved_by already exists\n";
    }
    
    // Add approval columns to group_tasks
    echo "\n2. Adding approval columns to group_tasks...\n";
    $check = $conn->query("SHOW COLUMNS FROM group_tasks LIKE 'approval_status'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_tasks 
                     ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        echo "   ✓ Added approval_status column\n";
    } else {
        echo "   - approval_status column already exists\n";
    }
    
    $check = $conn->query("SHOW COLUMNS FROM group_tasks LIKE 'approved_by'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_tasks ADD COLUMN approved_by INT DEFAULT NULL");
        echo "   ✓ Added approved_by column\n";
    } else {
        echo "   - approved_by column already exists\n";
    }
    
    $check = $conn->query("SHOW COLUMNS FROM group_tasks LIKE 'approved_at'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_tasks ADD COLUMN approved_at DATETIME DEFAULT NULL");
        echo "   ✓ Added approved_at column\n";
    } else {
        echo "   - approved_at column already exists\n";
    }
    
    // Add foreign key for approved_by if it doesn't exist
    $fk_check = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME = 'group_tasks' 
                              AND COLUMN_NAME = 'approved_by' 
                              AND REFERENCED_TABLE_NAME IS NOT NULL");
    if ($fk_check->num_rows === 0) {
        $conn->query("ALTER TABLE group_tasks 
                     ADD CONSTRAINT fk_group_tasks_approved_by 
                     FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "   ✓ Added foreign key for approved_by\n";
    } else {
        echo "   - Foreign key for approved_by already exists\n";
    }
    
    // Add approval columns to group_schedules
    echo "\n3. Adding approval columns to group_schedules...\n";
    $check = $conn->query("SHOW COLUMNS FROM group_schedules LIKE 'approval_status'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_schedules 
                     ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        echo "   ✓ Added approval_status column\n";
    } else {
        echo "   - approval_status column already exists\n";
    }
    
    $check = $conn->query("SHOW COLUMNS FROM group_schedules LIKE 'approved_by'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_schedules ADD COLUMN approved_by INT DEFAULT NULL");
        echo "   ✓ Added approved_by column\n";
    } else {
        echo "   - approved_by column already exists\n";
    }
    
    $check = $conn->query("SHOW COLUMNS FROM group_schedules LIKE 'approved_at'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE group_schedules ADD COLUMN approved_at DATETIME DEFAULT NULL");
        echo "   ✓ Added approved_at column\n";
    } else {
        echo "   - approved_at column already exists\n";
    }
    
    // Add foreign key for approved_by if it doesn't exist
    $fk_check = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME = 'group_schedules' 
                              AND COLUMN_NAME = 'approved_by' 
                              AND REFERENCED_TABLE_NAME IS NOT NULL");
    if ($fk_check->num_rows === 0) {
        $conn->query("ALTER TABLE group_schedules 
                     ADD CONSTRAINT fk_group_schedules_approved_by 
                     FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "   ✓ Added foreign key for approved_by\n";
    } else {
        echo "   - Foreign key for approved_by already exists\n";
    }
    
    // Set existing records to 'approved' (for backward compatibility)
    echo "\n4. Updating existing records...\n";
    $conn->query("UPDATE group_notes SET approval_status = 'approved' WHERE approval_status IS NULL OR approval_status = ''");
    echo "   ✓ Updated existing group_notes to 'approved'\n";
    
    $conn->query("UPDATE group_tasks SET approval_status = 'approved' WHERE approval_status IS NULL OR approval_status = ''");
    echo "   ✓ Updated existing group_tasks to 'approved'\n";
    
    $conn->query("UPDATE group_schedules SET approval_status = 'approved' WHERE approval_status IS NULL OR approval_status = ''");
    echo "   ✓ Updated existing group_schedules to 'approved'\n";
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
$conn->close();
?>

