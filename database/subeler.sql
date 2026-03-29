-- Create the subeler (branches) table
CREATE TABLE IF NOT EXISTS subeler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sube_adi VARCHAR(255) NOT NULL,
    adres VARCHAR(500) NOT NULL,
    sehir VARCHAR(100),
    posta_kodu VARCHAR(20),
    aktif TINYINT(1) DEFAULT 1,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial branch data
INSERT INTO subeler (sube_adi, adres, sehir, posta_kodu) VALUES 
('AİF Bludenz', 'Walserweg 1, 6700 Bludenz', 'Bludenz', '6700'),
('AİF Bregenz', 'Arlbergstraße 114c, 6900 Bregenz', 'Bregenz', '6900'),
('AİF Dornbirn', 'Schwefel 68, 6850 Dornbirn', 'Dornbirn', '6850'),
('AİF Feldkirch', 'Amberggasse 10, 6800 Feldkirch', 'Feldkirch', '6800'),
('AİF Lustenau', 'Kneippstraße 6, 6890 Lustenau', 'Lustenau', '6890'),
('AİF Radfeld', 'Innstraße 27d, 6241 Radfeld', 'Radfeld', '6241'),
('AİF Hall in Tirol', 'Beheimstraße 3, 6060 Hall in Tirol', 'Hall in Tirol', '6060'),
('AİF Innsbruck', 'Sterzingerstraße 6, 6020 Innsbruck', 'Innsbruck', '6020'),
('AİF Jenbach', 'Achenseestraße 67, 6200 Jenbach', 'Jenbach', '6200'),
('AİF Reutte', 'Schulstraße 7a, 6600 Reutte', 'Reutte', '6600'),
('AİF Vomp', 'Feldweg 16, 6134 Vomp', 'Vomp', '6134'),
('AİF Wörgl', 'Peter-Anich-Straße 6, 6300 Wörgl', 'Wörgl', '6300'),
('AİF Zirl', 'Meilstraße 28, 6171 Zirl', 'Zirl', '6171');
