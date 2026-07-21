DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `mechanics`;

CREATE TABLE `mechanics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `specialty` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `max_daily_slots` INT NOT NULL DEFAULT 4,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `mechanics` (`id`, `name`, `specialty`, `phone`, `max_daily_slots`) VALUES
(1, 'Karim Rahman', 'Engine Overhaul & Diagnostics', '+880 1711-000001', 4),
(2, 'Tanvir Ahmed', 'Transmission & Gearbox Expert', '+880 1711-000002', 4),
(3, 'Rahim Uddin', 'Auto Electrical & ECU Tuning', '+880 1711-000003', 4),
(4, 'Shafiqul Islam', 'Brake Systems & Suspension', '+880 1711-000004', 4),
(5, 'Mahfuz Khan', 'Hybrid & AC Climate Control', '+880 1711-000005', 4);

CREATE TABLE `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(100) NOT NULL,
    `address` TEXT NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `car_license` VARCHAR(50) NOT NULL,
    `car_engine` VARCHAR(50) NOT NULL,
    `appointment_date` DATE NOT NULL,
    `mechanic_id` INT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `idx_date_mechanic` ON `appointments` (`appointment_date`, `mechanic_id`);
CREATE INDEX `idx_date_phone` ON `appointments` (`appointment_date`, `phone`);
CREATE INDEX `idx_date_license` ON `appointments` (`appointment_date`, `car_license`);
