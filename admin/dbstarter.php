<?php
// Root Finder
require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');

// ID Generator
require_once(BASE_PATH . 'includes/idgenerator.php');

// Enable MySQLi exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "JBoyDB";

try {
    // Connect to MySQL (no DB selected yet)
    $conn = new mysqli($host, $user, $pass);
    $conn->set_charset("utf8mb4");

    // ---------------------------
    // CREATE DATABASE
    // ---------------------------
    $conn->query("CREATE DATABASE IF NOT EXISTS $db DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci");
    $conn->select_db($db);

    // ---------------------------
    // TABLE CREATION
    // ---------------------------
    $queries = [

        // SubCategory Table
        "CREATE TABLE IF NOT EXISTS SubCategory(
            subCategoryID VARCHAR(50) PRIMARY KEY,
            subCategoryName VARCHAR(50) NOT NULL UNIQUE,
            subCategoryDesc TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Category Table
        "CREATE TABLE IF NOT EXISTS Category(
            categoryID VARCHAR(50) PRIMARY KEY,
            categoryName VARCHAR(50) NOT NULL UNIQUE,
            categoryDesc TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Items Table
        "CREATE TABLE IF NOT EXISTS Items(
            itemID VARCHAR(50) PRIMARY KEY,
            subCategoryID VARCHAR(50),
            itemName VARCHAR(100) NOT NULL UNIQUE,
            itemPrice DECIMAL(10,2) NOT NULL,
            itemDesc TEXT,
            itemAvail TINYINT(1) DEFAULT 1 NOT NULL,
            itemImageLocation VARCHAR(255),
            FOREIGN KEY (subCategoryID) REFERENCES SubCategory(subCategoryID) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Junction Table: ItemCategory
        "CREATE TABLE IF NOT EXISTS ItemCategory(
            itemID VARCHAR(50) NOT NULL,
            categoryID VARCHAR(50) NOT NULL,
            PRIMARY KEY (itemID, categoryID),
            FOREIGN KEY (itemID) REFERENCES Items(itemID) ON DELETE CASCADE,
            FOREIGN KEY (categoryID) REFERENCES Category(categoryID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Users Table
        "CREATE TABLE IF NOT EXISTS Users(
            userID VARCHAR(50) PRIMARY KEY,
            firstName VARCHAR(100) NOT NULL,
            lastName VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            hashPass VARCHAR(255) NOT NULL,
            contactNo VARCHAR(20),
            gender ENUM('M','F') NOT NULL,
            userrole ENUM('customer','admin') NOT NULL DEFAULT 'customer',
            creationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // UserAddress Table
        "CREATE TABLE IF NOT EXISTS UserAddress(
            addressID VARCHAR(50) PRIMARY KEY,
            userID VARCHAR(50) NOT NULL,
            addressLine VARCHAR(100),
            city VARCHAR(100),
            FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Orders Table
        "CREATE TABLE IF NOT EXISTS Orders(
            orderID VARCHAR(50) PRIMARY KEY,
            userID VARCHAR(50) NOT NULL,
            totalAmount DECIMAL(10,2) NOT NULL,
            deliveryFee DECIMAL(10,2) NOT NULL,
            orderStatus ENUM('placed','confirmed','preparing','out-for-delivery','delivered','cancelled') NOT NULL DEFAULT 'placed',
            orderedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deliveredTime TIMESTAMP NULL,
            deliveryAddress TEXT,
            FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // OrderItem Table
        "CREATE TABLE IF NOT EXISTS OrderItem(
            orderItemID VARCHAR(50) PRIMARY KEY,
            orderID VARCHAR(50) NOT NULL,
            itemID VARCHAR(50) NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            itemPriceAtOrder DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (orderID) REFERENCES Orders(orderID) ON DELETE CASCADE,
            FOREIGN KEY (itemID) REFERENCES Items(itemID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Payments Table
        "CREATE TABLE IF NOT EXISTS Payments(
            paymentID VARCHAR(50) PRIMARY KEY,
            orderID VARCHAR(50) NOT NULL,
            paymentMethod ENUM('GCash','Cash-on-Delivery') NOT NULL,
            transactionReference VARCHAR(150) UNIQUE,
            paymentStatus ENUM('pending','success','failed'),
            FOREIGN KEY (orderID) REFERENCES Orders(orderID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Reviews Table
        "CREATE TABLE IF NOT EXISTS Reviews(
            reviewID VARCHAR(50) PRIMARY KEY,
            orderID VARCHAR(50) NOT NULL,
            userID VARCHAR(50) NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT,
            FOREIGN KEY (orderID) REFERENCES Orders(orderID) ON DELETE CASCADE,
            FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Notices Table
        "CREATE TABLE IF NOT EXISTS Notices(
            noticeID VARCHAR(50) PRIMARY KEY,
            noticeTitle VARCHAR(150) NOT NULL UNIQUE,
            noticeSummary VARCHAR(255),
            noticeBody TEXT NOT NULL,
            noticeImageLocation VARCHAR(255),
            noticeActive TINYINT(1) DEFAULT 1 NOT NULL,
            noticeCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            noticeUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            noticeImportant TINYINT(1) DEFAULT 1 NOT NULL,
            noticeLinkRelated TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($queries as $q) {
        $conn->query($q);
    }

$indexes = [
    ['Items', 'idx_items_name', 'itemName'],
    ['Items', 'idx_items_category', 'subCategoryID'],
    ['Orders', 'idx_orders_user', 'userID'],
    ['OrderItem', 'idx_orderitem_order', 'orderID']
];

foreach ($indexes as [$table, $index, $column]) {
    $result = $conn->query("SHOW INDEX FROM $table WHERE Key_name='$index'");
    if ($result->num_rows === 0) {
        $conn->query("CREATE INDEX $index ON $table($column)");
    }
}

$result = $conn->query("SHOW INDEX FROM Notices WHERE Key_name='idx_notices_body'");
if ($result->num_rows === 0) {
    $conn->query("CREATE FULLTEXT INDEX idx_notices_body ON Notices(noticeBody)");
}

// ---------------------------
// INITIAL DATA INSERTION
// ---------------------------
try {
    // ---------------------------
    // Insert SubCategories
    // ---------------------------
    $conn->query("
        INSERT INTO SubCategory(subCategoryID, subCategoryName, subCategoryDesc) VALUES
            ('SUB00001', 'Single', NULL),
            ('SUB00002', '8 pcs', NULL),
            ('SUB00003', '12 pcs', NULL),
            ('SUB00004', '16 pcs', NULL),
            ('SUB00005', '18 pcs', NULL),
            ('SUB00006', '24 pcs', NULL),
            ('SUB00007', '30 pcs', NULL),
            ('SUB00008', '32 pcs', NULL),
            ('SUB00009', '34 pcs', NULL),
            ('SUB00010', '38 pcs', NULL),
            ('SUB00011', '40 pcs', NULL),
            ('SUB00012', '50 pcs', NULL),
            ('SUB00013', '52 pcs', NULL),
            ('SUB00014', '54 pcs', NULL),
            ('SUB00015', 'MH', NULL)
    ");
    
    // ---------------------------
    // Insert Categories
    // ---------------------------
    $conn->query("
        INSERT INTO Category(categoryID, categoryName, categoryDesc) VALUES
            ('CAT00001', 'Popular', 'Our best sellers.'),
            ('CAT00002', 'Bento Rice Meals', NULL),
            ('CAT00003', 'Ramen', NULL),
            ('CAT00004', 'Salad', NULL),
            ('CAT00005', 'Sushi Rolls', NULL),
            ('CAT00006', 'Maki Rolls', NULL)
    ");
    
    // ---------------------------
    // Insert Items
    // ---------------------------
    $conn->query("
        INSERT INTO Items(itemID, subCategoryID, itemName, itemPrice, itemDesc, itemAvail, itemImageLocation) VALUES
            ('I00001', 'SUB00001', 'Gyoza',                                     100,    '5-pieces gyoza dumplings.',                                                                                                                                            1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/Gyoza.png'),
            ('I00002', 'SUB00001', 'Gyoza Bento',                               130,    'Famous Japanese pan-fried dumplings served with rice and salad.',                                                                                                      1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/GyozaBento.png'),
            ('I00003', 'SUB00001', 'Kani Fry',                                  130,    'Crab stick coated with bread crumbs and deep fried.',                                                                                                                  1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/KaniFry.png'),
            ('I00004', 'SUB00001', 'Katsudon - Pork',                           125,    'A bowl of rice topped with a deep-fried breaded pork cutlet, egg, and vegetables.',                                                                                    1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/Katsudon-Pork.png'),
            ('I00005', 'SUB00001', 'Korean Spicy Chicken',                      135,    'Lightly battered boneless chicken glazed with well-balanced spicy, sweet, sticky sauce, topped with sesame seeds',                                                     1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/KoreanSpicyChicken.png'),
            ('I00006', 'SUB00001', 'Omu Rice',                                  120,    'Omelet filled with fried rice, chicken meat and ketchup.',                                                                                                             1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/Omurice.png'),
            ('I00007', 'SUB00001', 'Oyakudon - Chicken',                        125,    'A bowl of rice topped with a deep-fried breaded chicken cutlet, egg, and vegetables.',                                                                                 1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/Oyakudon-Chicken.png'),
            ('I00008', 'SUB00001', 'Spicy Pork Bulgogi',                        140,    'Thinly sliced pork belly and marinated in soy sauce mixed with spicy gochujang.',                                                                                      1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/SpicyPorkBulgogi.png'),
            ('I00009', 'SUB00001', 'Tonkatsu - Pork',                           139,    'Breaded, deep-fried pork cutlet with egg and vegetable side dish.',                                                                                                    1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/Tonkatsu-Pork.png'),
            ('I00010', 'SUB00001', 'Tori Karaage',                              135,    'Japanese fried chicken, boneless chicken lightly coated with flour and deep-fried.',                                                                                   1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/ToriKaraage.png'),
            ('I00011', 'SUB00001', 'Torikatsu - Chicken',                       139,    'Breaded, deep-fried chicken cutlet with egg and vegetable side dish.',                                                                                                 1,  '/jboymakiandbento/assets/images/item-images/Bento-Rice-Meals/Torikatsu-Chicken.png'),

            ('I00012', 'SUB00001', 'Tantanmen',                                 155,    'No Description',                                                                                                                                                       1,  '/jboymakiandbento/assets/images/item-images/Ramen/Tantanmen.png'),
            ('I00013', 'SUB00001', 'Tonkotsu Ramen',                            155,    'No Description',                                                                                                                                                       1,  '/jboymakiandbento/assets/images/item-images/Ramen/TonkotsuRamen.png'),
            ('I00014', 'SUB00001', 'Gyoza Ramen',                               145,    'No Description',                                                                                                                                                       1,  '/jboymakiandbento/assets/images/item-images/Ramen/GyozaRamen.png'),
            ('I00015', 'SUB00001', 'Shoyu Ramen',                               145,    'No Description',                                                                                                                                                       1,  '/jboymakiandbento/assets/images/item-images/Ramen/ShoyuRamen.png'),
            
            ('I00016', 'SUB00001', 'Kani Salad',                                130,    'Mango, kani, crabmeat salad',                                                                                                                                          1,  '/jboymakiandbento/assets/images/item-images/Salad/KaniSalad.jpg'),
            ('I00017', 'SUB00003', '12 pcs Harumaki Salad',                     130,    '12 pieces of Mango, kani crabmeat roll with toasted sesame dip sauce',                                                                                                   1,  '/jboymakiandbento/assets/images/item-images/Salad/12pcs-HarumakiSalad.jpg'),
            ('I00018', 'SUB00005', '18 pcs Harumaki Salad',                     220,    '18 pieces of Mango, kani crabmeat roll with toasted sesame dip sauce',                                                                                                                   1,  '/jboymakiandbento/assets/images/item-images/Salad/18pcs-HarumakiSalad.jpg'),
            ('I00019', 'SUB00007', '30 pcs Harumaki Salad',                     400,    '30 pieces of Mango, kani crabmeat roll with toasted sesame dip sauce',                                                                                                                   1,  '/jboymakiandbento/assets/images/item-images/Salad/30pcs-HarumakiSalad.jpg'),

            ('I00020', 'SUB00002', 'Tamago and Kani Sushi',                     100,    '4 pieces of Tamago sushi and 4 pieces of Kani sushi',                                                                                                                  1,  '/jboymakiandbento/assets/images/item-images/Sushi-Rolls/4pcs-Tamago-4pcs-KaniSushi.jpg'),
            ('I00021', 'SUB00002', 'Kani Sushi',                                110,    '8 pieces of Kani or crabmeat sushi',                                                                                                                                   1,  '/jboymakiandbento/assets/images/item-images/Sushi-Rolls/8pcs-KaniSushi.jpg'),
            ('I00022', 'SUB00002', 'Tamago Sushi',                              100,    '8 pieces of egg Tamago sushi',                                                                                                                                         1,  '/jboymakiandbento/assets/images/item-images/Sushi-Rolls/8pcs-TamagoSushi.jpg'),

            ('I00023', 'SUB00002', 'California Maki',                           120,    '8 pieces of maki roll',                                                                                                                                                1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/8pcs-CaliforniaMaki.jpg'),
            ('I00024', 'SUB00002', 'Crispy Maki',                               160,    '8 pieces of deep fried maki rolls',                                                                                                                                    1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/8pcs-CrispyMaki.jpg'),
            ('I00025', 'SUB00002', 'Futo Maki',                                 140,    '8 pieces of maki wrapped with nori',                                                                                                                                   1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/8pcs-FutoMaki.jpg'),
            ('I00026', 'SUB00002', 'Kani and Cheese',                           130,    '8 pieces of cheese topped with crabmeat',                                                                                                                              1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/8pcs-KaniCheese.jpg'),
            ('I00027', 'SUB00002', 'Kanifornia Maki',                           150,    '8 pieces of maki with kani salad toppings',                                                                                                                            1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/8pcs-KaniforniaMaki.jpg'),
            ('I00028', 'SUB00002', 'Maki Overload',                             160,    '8 pieces of maki with overload toppings',                                                                                                                              1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/8pcs-MakiOverload.jpg'),
            ('I00029', 'SUB00002', 'Mango Roll',                                150,    '8 pieces of maki topped with thinly sliced mango',                                                                                                                     1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/8pcs-MangoRoll.jpg'),
            ('I00030', 'SUB00002', 'Ham and Cheese',                            130,    '8 pieces of cheese topped with ham',                                                                                                                                   1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/8psc-HamCheese.jpg'),
            ('I00031', 'SUB00006', '24 pcs California Maki',                    350,    '24 pieces of maki roll',                                                                                                                                               1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/24pcs-CaliforniaMaki.jpg'),
            ('I00032', 'SUB00006', '24 pcs Mix Maki Overload',                  500,    '8 pieces of Kanifornia Maki, 8 pieces of Crispy Maki, and 8 pieces of Maki Overload',                                                                                  1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/24pcs-MixMakiOverload.jpg'),
            ('I00033', 'SUB00006', '24 pcs Mix Maki Set A',                     400,    '8 pieces of California Maki, 8 pieces of Kanifornia Maki, and 8 pieces of Ham and Cheese',                                                                             1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/24pcs-MixMakiSet-A.jpg'),
            ('I00034', 'SUB00006', '24 pcs Mix Maki Set B',                     420,    '8 pieces of California Maki, 8 pieces of Kanifornia Maki, and 8 pieces of Mango Roll',                                                                                 1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/24pcs-MixMakiSet-B.jpg'),
            ('I00035', 'SUB00006', '24 pcs Mix Maki Set C',                     430,    '8 pieces of Kanifornia Maki, 8 pieces of Mango Roll, and 8 pieces of Kani and Cheese',                                                                                 1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/24pcs-MixMakiSet-C.jpg'),
            ('I00036', 'SUB00008', '32 pcs California Maki',                    450,    '32 pieces of maki roll',                                                                                                                                               1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/32pcs-CaliforniaMaki.jpg'),
            ('I00037', 'SUB00008', '32 pcs Kanifornia Maki',                    620,    '32 pieces of maki with kani salad toppings',                                                                                                                           1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/32pcs-KaniforniaMaki.jpg'),
            ('I00038', 'SUB00008', '32 pcs Maki Overload',                      650,    '32 pieces of maki with overload toppings',                                                                                                                             1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/32pcs-MakiOverload.jpg'),
            ('I00039', 'SUB00008', '32 pcs Mix Maki Set A',                     550,    '8 pieces of California Maki, 8 pieces of Kani and Cheese, 8 pieces of Kanifornia Maki, and 8 pieces of Mango Roll',                                                    1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/32pcs-MixMakiSet-A.jpg'),
            ('I00040', 'SUB00008', '32 pcs Mix Maki Set B',                     550,    '16 pieces of California Maki, 8 pieces of Kanifornia Maki, and 8 pieces of Mango Roll',                                                                                1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/32pcs-MixMakiSet-B.jpg'),
            ('I00041', 'SUB00008', '32 pcs Mix Maki Set C',                     580,    '8 pieces of California Maki, 8 pieces of Maki Overload, 8 pieces of Kanifornia Maki, and 8 pieces of Futo Maki',                                                       1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/32pcs-MixMakiSet-C.jpg'),
            ('I00042', 'SUB00011', '40 pcs California Maki',                    580,    '40 pieces of maki roll',                                                                                                                                               1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/40pcs-CaliforniaMaki.jpg'),
            ('I00043', 'SUB00011', '40 pcs Mix Maki',                           680,    '16 pieces of California Maki, 8 pieces of Futo Maki, 8 pieces of Kanifornia Maki, and 8 pieces of Mango Roll',                                                         1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/40pcs-MixMaki.jpg'),
            ('I00044', 'SUB00012', '50 pcs Mix Maki',                           820,    '18 pieces of California Maki, 8 pieces of Futo Maki, 8 pieces of Ham and Cheese, 8 pieces of Kanifornia Maki, and 8 pieces of Mango Roll',                             1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/50pcs-MixMaki.jpg'),
            ('I00045', 'SUB00013', '52 pcs  California Maki',                   680,    '52 pieces of maki roll',                                                                                                                                               1,  '/jboymakiandbento/assets/images/item-images/Maki-Rolls/52pcs-CaliforniaMaki.jpg'),

            ('I00046', 'SUB00015', 'MH1 California Maki and Harumaki',          200,    '8 pieces of California Maki, and 6 pieces of Harumaki Salad',                                                                                                          1,  '/jboymakiandbento/assets/images/item-images/Maki-Harumaki/34pcs-MixMaki-Harumaki.jpg'),
            ('I00047', 'SUB00015', 'MH2 Kanifornia Maki and Harumaki',          220,    '8 pieces of Kanifornia Maki, and 6 pieces of Harumaki Salad',                                                                                                          1,  '/jboymakiandbento/assets/images/item-images/Maki-Harumaki/MH-1-CaliforniaMaki-Harumaki.jpg'),
            ('I00048', 'SUB00015', 'MH3 Mango Roll, Kanifornia and Harumaki',   220,    '4 pieces of Mango Roll, 4 pieces of Kanifornia Maki, and 6 pieces of Harumaki Salad',                                                                                  1,  '/jboymakiandbento/assets/images/item-images/Maki-Harumaki/MH-2-Kaniforniamaki-Harumaki.jpg'),
            ('I00049', 'SUB00015', 'MH4 Mango Roll and Harumaki',               220,    '8 pieces of Mango Roll, and 6 pieces of Harumaki Salad',                                                                                                               1,  '/jboymakiandbento/assets/images/item-images/Maki-Harumaki/MH-3-Mangoroll-Kanifornia-Harumaki.jpg'),
            ('I00050', 'SUB00009', '34 pcs Mix Maki and Harumaki',              500,    '8 pieces of Mango Roll, 8 pieces of Kanifornia Maki, and 18 pieces of Harumaki Salad',                                                                                 1,  '/jboymakiandbento/assets/images/item-images/Maki-Harumaki/MH-4-Mangoroll-Harumaki.jpg'),

            ('I00051', 'SUB00004', '16 Mix Maki and Sushi',                     220,    '8 pieces of California Maki, 4 pieces of Tamago Sushi, and 4 pieces of Kani Sushi',                                                                                    1,  '/jboymakiandbento/assets/images/item-images/Maki-Sushi/16pcs-MixMaki-Sushi.jpg'),
            ('I00052', 'SUB00008', '32 Mix Maki and Sushi',                     500,    '8 pieces of California Maki, 8 pieces of Ham and Cheese, 4 pieces of Tamago Sushi, 4 pieces of Kani Sushi, and 8 pieces of Futo Maki',                                 1,  '/jboymakiandbento/assets/images/item-images/Maki-Sushi/32pcs-MixMaki-Sushi.jpg'),
            ('I00053', 'SUB00011', '40 Mix Maki and Sushi',                     580,    '16 pieces of California Maki, 18 pieces of Futo Maki, 3 pieces of Tamago Sushi, and 3 pieces of Kani Sushi',                                                           1,  '/jboymakiandbento/assets/images/item-images/Maki-Sushi/40pcs-MixMaki-Sushi.jpg'),
            ('I00054', 'SUB00012', '50 Mix Maki and Sushi',                     700,    '12 pieces of California Maki, 8 pieces of Ham and Cheese, 8 pcs of Kanifornia Maki, 5 pieces of Tamago Sushi, 5 pieces of Kani Sushi, and 12 pieces of Futo Maki',     1,  '/jboymakiandbento/assets/images/item-images/Maki-Sushi/50pcs-MixMaki-Sushi.jpg'),

            ('I00055', 'SUB00010', '38 pcs Mix Maki, Harumaki and Sushi',       600,    '8 pieces of California Maki, 2 pieces of Tamago Sushi, 2 pieces of Kani Sushi, 8 pcs of Mango Roll, and 18 pcs of Harumaki Salad',                                     1,  '/jboymakiandbento/assets/images/item-images/Maki-Harumaki-Sushi/38pcs-MixMaki-Harumaki-Sushi.jpg'),
            ('I00056', 'SUB00014', '54 pcs Mix Maki, Harumaki and Sushi',       800,    '8 pieces of California Maki, 8 pieces of Mango Roll, 6 pieces of Kanifornia Maki, 4 pcs of Tamago Sushi, 4 pcs of Kani Sushi, and 24 pcs of Harumaki Salad',           1,  '/jboymakiandbento/assets/images/item-images/Maki-Harumaki-Sushi/54pcs-MixMaki-Harumaki-Sushi.jpg')
    ");
    
    // ---------------------------
    // Insert ItemCategory links
    // ---------------------------
    $conn->query("
        INSERT INTO ItemCategory(itemID, categoryID) VALUES
            ('I00023', 'CAT00001'),
            ('I00017', 'CAT00001'),
            ('I00048', 'CAT00001'),
            ('I00010', 'CAT00001'),
            ('I00004', 'CAT00001'),

            ('I00001', 'CAT00002'),
            ('I00002', 'CAT00002'),
            ('I00003', 'CAT00002'),
            ('I00004', 'CAT00002'),
            ('I00005', 'CAT00002'),
            ('I00006', 'CAT00002'),
            ('I00007', 'CAT00002'),
            ('I00008', 'CAT00002'),
            ('I00009', 'CAT00002'),
            ('I00010', 'CAT00002'),
            ('I00011', 'CAT00002'),

            ('I00012', 'CAT00003'),
            ('I00013', 'CAT00003'),
            ('I00014', 'CAT00003'),
            ('I00015', 'CAT00003'),

            ('I00016', 'CAT00004'),
            ('I00017', 'CAT00004'),
            ('I00018', 'CAT00004'),
            ('I00019', 'CAT00004'),

            ('I00020', 'CAT00005'),
            ('I00021', 'CAT00005'),
            ('I00022', 'CAT00005'),
            
            ('I00023', 'CAT00006'),
            ('I00024', 'CAT00006'),
            ('I00025', 'CAT00006'),
            ('I00026', 'CAT00006'),
            ('I00027', 'CAT00006'),
            ('I00028', 'CAT00006'),
            ('I00029', 'CAT00006'),
            ('I00030', 'CAT00006'),
            ('I00031', 'CAT00006'),
            ('I00032', 'CAT00006'),
            ('I00033', 'CAT00006'),
            ('I00034', 'CAT00006'),
            ('I00035', 'CAT00006'),
            ('I00036', 'CAT00006'),
            ('I00037', 'CAT00006'),
            ('I00038', 'CAT00006'),
            ('I00039', 'CAT00006'),
            ('I00040', 'CAT00006'),
            ('I00041', 'CAT00006'),
            ('I00042', 'CAT00006'),
            ('I00043', 'CAT00006'),
            ('I00044', 'CAT00006'),
            ('I00045', 'CAT00006'),

            ('I00046', 'CAT00004'),
            ('I00046', 'CAT00006'),
            ('I00047', 'CAT00004'),
            ('I00047', 'CAT00006'),
            ('I00048', 'CAT00004'),
            ('I00048', 'CAT00006'),
            ('I00049', 'CAT00004'),
            ('I00049', 'CAT00006'),
            ('I00050', 'CAT00004'),
            ('I00050', 'CAT00006'),

            ('I00051', 'CAT00005'),
            ('I00051', 'CAT00006'),
            ('I00052', 'CAT00005'),
            ('I00052', 'CAT00006'),
            ('I00053', 'CAT00005'),
            ('I00053', 'CAT00006'),
            ('I00054', 'CAT00005'),
            ('I00054', 'CAT00006'),

            ('I00055', 'CAT00004'),
            ('I00055', 'CAT00005'),
            ('I00055', 'CAT00006'),
            ('I00056', 'CAT00004'),
            ('I00056', 'CAT00005'),
            ('I00056', 'CAT00006')
    ");
    
} catch (mysqli_sql_exception $e) {
    // Handle duplicate entry gracefully
    if ($e->getCode() != 1062) { // 1062 = Duplicate entry
        throw $e;
    }
}

    // ---------------------------
    // NEW SYSTEM DETECTION & ADMIN CREATION
    // ---------------------------
    $check = $conn->query("SELECT COUNT(*) AS total FROM Users");
    $row = $check->fetch_assoc();
    $isNewSystem = ($row['total'] == 0);

    if ($isNewSystem && !isset($_POST['setup_admin'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="utf-8"><title>Initial Setup</title></head>
        <body>
            <h2>🆕 New System Detected</h2>
            <p>Create the first admin account:</p>
            <form method="post">
                <input type="text" name="fname" placeholder="First Name" required><br><br>
                <input type="text" name="lname" placeholder="Last Name" required><br><br>
                <input type="email" name="email" placeholder="Email" required><br><br>
                <input type="password" name="password" placeholder="Password" required><br><br>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select><br><br>
                <button type="submit" name="setup_admin">Create Admin</button>
            </form>
        </body>
        </html>
        <?php
        exit();
    }

    if ($isNewSystem && isset($_POST['setup_admin'])) {
        $fname  = trim($_POST['fname']);
        $lname  = trim($_POST['lname']);
        $email  = trim($_POST['email']);
        $gender = $_POST['gender'];
        $hash   = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role   = 'admin';
        $userID = generateUserID($conn, $role);

        $stmt = $conn->prepare("
            INSERT INTO Users (userID, firstName, lastName, email, hashPass, gender, userrole)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssss", $userID, $fname, $lname, $email, $hash, $gender, $role);
        $stmt->execute();

        echo "<h3>✅ Admin account created successfully!</h3>";
        echo "<p>User ID: <b>" . htmlspecialchars($userID) . "</b></p>";
        echo '<p><a href="/jboymakiandbento/login.php">Go to Login</a></p>';
        exit();
    } else {
        echo "<h3>System is already initialized.</h3>";
    }

} catch (mysqli_sql_exception $e) {
    die("<b>Error:</b> " . $e->getMessage());
} finally {
    if (isset($conn)) $conn->close();
}
?>