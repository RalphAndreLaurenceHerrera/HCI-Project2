/* PART 1: DATABASE DESIGN (DDL) */

 -- Create a new database named JBoyDB
CREATE DATABASE JBoyDB; 
USE JBoyDB;
 -- Create a table Category with columns: subCategoryID (PK), subCategoryName, subCategoryDesc.
CREATE TABLE IF NOT EXISTS SubCategory(
	subCategoryID VARCHAR(50) PRIMARY KEY,
	subCategoryName VARCHAR(50) NOT NULL UNIQUE,
	subCategoryDesc TEXT
);
 -- Create a table Category with columns: categoryID (PK), subCategoryID (FK), categoryName, categoryDesc.
CREATE TABLE IF NOT EXISTS Category(
	categoryID VARCHAR(50) PRIMARY KEY,
	categoryName VARCHAR(50) NOT NULL UNIQUE,
	categoryDesc TEXT,
);
 -- Create a table Items with columns: itemID (PK), categoryID (FK), itemName (UNIQUE), itemPrice, itemDesc, itemAvail, itemImageLocation.
CREATE TABLE IF NOT EXISTS Items(
	itemID VARCHAR(50) PRIMARY KEY,
	categoryID VARCHAR(50),
	subCategoryID VARCHAR(50),
	itemName VARCHAR(100) NOT NULL UNIQUE,
	itemPrice DECIMAL(10,2) NOT NULL,
	itemDesc TEXT,
	itemAvail TINYINT(1) DEFAULT 1 NOT NULL,
	itemImageLocation VARCHAR(255),
	FOREIGN KEY (categoryID) REFERENCES Category(categoryID)
		ON DELETE SET NULL
	FOREIGN KEY (subCategoryID) REFERENCES SubCategory(subCategoryID)
		ON DELETE SET NULL
);
 -- Create a junction table ItemCategorySubCategory.
CREATE TABLE IF NOT EXISTS ItemCategorySubCategory (
	itemID VARCHAR(50) NOT NULL,
	categoryID VARCHAR(50) NOT NULL,
	subCategoryID VARCHAR(50) NOT NULL,
    PRIMARY KEY (itemID, categoryID, subCategoryID),
    FOREIGN KEY (itemID) REFERENCES Items(itemID) ON DELETE CASCADE,
    FOREIGN KEY (categoryID) REFERENCES Category(categoryID) ON DELETE CASCADE,
    FOREIGN KEY (subCategoryID) REFERENCES SubCategory(subCategoryID) ON DELETE CASCADE
);
 -- Create a table Users with columns: userID (PK), firstName, lastName, email (UNIQUE), hashPass, contactNo, gender, userrole, creationDate.
CREATE TABLE IF NOT EXISTS Users(
	userID VARCHAR(50) PRIMARY KEY,
	firstName VARCHAR(100) NOT NULL,
	lastName VARCHAR(100) NOT NULL,
	email VARCHAR(150) NOT NULL UNIQUE,
	hashPass VARCHAR(255) NOT NULL,
	contactNo VARCHAR(20),
	gender ENUM('M', 'F') NOT NULL,
	userrole ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
	creationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
 -- Create a table UserAddress with columns: addressID (PK), userID (FK), addressLine, city
CREATE TABLE IF NOT EXISTS UserAddress(
	addressID VARCHAR(50) PRIMARY KEY,
	userID VARCHAR(50) NOT NULL,
	addressLine VARCHAR(100),
	city VARCHAR(100),
	FOREIGN KEY (userID) REFERENCES Users(userID)
		ON DELETE CASCADE
);
 -- Create a table Orders with columns: orderID (PK), userID (FK), orderStatus, totalAmount, deliveryFee, orderedTime, deliveredTime, deliveryAddress
CREATE TABLE IF NOT EXISTS Orders (
	orderID VARCHAR(50) PRIMARY KEY,
	userID VARCHAR(50) NOT NULL,
	totalAmount DECIMAL(10,2) NOT NULL,
	deliveryFee DECIMAL(10,2) NOT NULL,
	orderStatus ENUM('placed', 'confirmed', 'preparing', 'out-for-delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'placed',
	orderedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	deliveredTime TIMESTAMP NULL,
	deliveryAddress TEXT,
	FOREIGN KEY (userID) REFERENCES Users(userID)
		ON DELETE CASCADE
);
 -- Create a table OrderItem with columns: orderItemID (PK), orderID (FK), itemID (FK), quantity, itemPriceAtOrder
CREATE TABLE IF NOT EXISTS OrderItem (
	orderItemID VARCHAR(50) PRIMARY KEY,
	orderID VARCHAR(50) NOT NULL,
	itemID VARCHAR(50) NOT NULL,
	quantity INT UNSIGNED NOT NULL,
	itemPriceAtOrder DECIMAL(10,2) NOT NULL,
	FOREIGN KEY (orderID) REFERENCES Orders(orderID)
		ON DELETE CASCADE,
	FOREIGN KEY (itemID) REFERENCES Items(itemID)
		ON DELETE CASCADE
);
 -- Create a table Payments with columns: paymentID (PK), orderID (FK), paymentMethod, transactionReference (UNIQUE), paymentStatus
CREATE TABLE IF NOT EXISTS Payments (
	paymentID VARCHAR(50) PRIMARY KEY,
	orderID VARCHAR(50) NOT NULL,
	paymentMethod ENUM('GCash', 'Cash-on-Delivery') NOT NULL,
	transactionReference VARCHAR(150) UNIQUE,
	paymentStatus ENUM('pending', 'success', 'failed'),
	FOREIGN KEY (orderID) REFERENCES Orders(orderID)
		ON DELETE CASCADE
);
 -- Create a table Reviews with columns: reviewID (PK), orderID (FK), userID (FK), rating, comment
CREATE TABLE IF NOT EXISTS Reviews (
	reviewID VARCHAR(50) PRIMARY KEY,
	orderID VARCHAR(50) NOT NULL,
	userID VARCHAR(50) NOT NULL,
	rating INT NOT NULL CHECK (rating BETWEEN 1 and 5),
	comment TEXT,
	FOREIGN KEY (orderID) REFERENCES Orders(orderID)
		ON DELETE CASCADE,
	FOREIGN KEY (userID) REFERENCES Users(userID)
		ON DELETE CASCADE
);
 -- Create a table Notices with columns: noticeID (PK), noticeTitle (UNIQUE), noticeSummary, noticeBody, noticeActive, noticeCreated, noticeUpdated
CREATE TABLE IF NOT EXISTS Notices(
    noticeID VARCHAR(50) PRIMARY KEY,
    noticeTitle VARCHAR(150) NOT NULL UNIQUE,
	noticeImageLocation VARCHAR(255),
    noticeSummary VARCHAR(255),
    noticeBody TEXT NOT NULL,
    noticeCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    noticeUpdated TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    noticeActive TINYINT(1) DEFAULT 1 NOT NULL,
    noticeLinkRelated TEXT
);
 -- Add an index for faster searches.
CREATE INDEX idx_items_name ON Items(itemName);
CREATE INDEX idx_items_category ON Items(categoryID);
CREATE INDEX idx_orders_user ON Orders(userID);
CREATE INDEX idx_orderitem_order ON OrderItem(orderID);

/* PART 2: DATA MANIPULATION (DML) */
 -- Create a table Items with columns: itemID (PK), categoryID (FK), itemName (UNIQUE), itemPrice, itemDesc, itemAvail, itemImageLocation.
CREATE TABLE IF NOT EXISTS Items(
	itemID VARCHAR(50) PRIMARY KEY,
	categoryID VARCHAR(50),
	itemName VARCHAR(100) NOT NULL UNIQUE,
	itemPrice DECIMAL(10,2) NOT NULL,
	itemDesc TEXT,
	itemAvail TINYINT(1) DEFAULT 1 NOT NULL,
	itemImageLocation VARCHAR(255),
	FOREIGN KEY (categoryID) REFERENCES Category(categoryID)
		ON DELETE SET NULL
);

INSERT INTO SubCategory(subCategoryID, subCategoryName, subCategoryDesc) VALUES
	('SUB00001', 'Single', NULL),
	('SUB00002', 'MH', NULL),
	('SUB00003', '8pcs', NULL),
	('SUB00004', '12pcs', NULL),
	('SUB00005', '16pcs', NULL),
	('SUB00006', '18pcs', NULL),
	('SUB00007', '24pcs', NULL),
	('SUB00008', '30pcs', NULL),
	('SUB00009', '32pcs', NULL),
	('SUB00010', '34pcs', NULL),
	('SUB00011', '38pcs', NULL),
	('SUB00012', '40pcs', NULL),
	('SUB00013', '50pcs', NULL),
	('SUB00014', '52pcs', NULL),
	('SUB00015', '54pcs', NULL);

INSERT INTO Category(categoryID, subCategoryID, categoryName, categoryDesc) VALUES
	('CAT00001', 'Popular', NULL),
	('CAT00002', 'Bento Rice Meals', NULL),
	('CAT00003', 'Ramen', NULL),
	('CAT00004', 'Salad', NULL),
	('CAT00005', 'Sushi Rolls', NULL),
	('CAT00006', 'Maki Rolls', NULL);

INSERT INTO CategorySubCategory(subCategoryID, categoryID) VALUES
	('SUB00001', 'CAT00004'),
	('SUB00002', 'CAT00004'),
	('SUB00004', 'CAT00004'),
	('SUB00006', 'CAT00004'),
	('SUB00008', 'CAT00004'),
	('SUB00010', 'CAT00004'),
	('SUB00011', 'CAT00004'),
	('SUB00015', 'CAT00004'),
	('SUB00003', 'CAT00005'),
	('SUB00005', 'CAT00005'),
	('SUB00009', 'CAT00005'),
	('SUB00011', 'CAT00005'),
	('SUB00012', 'CAT00005'),
	('SUB00013', 'CAT00005'),
	('SUB00015', 'CAT00005'),

INSERT INTO Items(itemID, itemName, itemPrice, itemDesc, itemAvail, itemImageLocation) VALUES
				('I00001', 'Gyoza', 100, '5-pieces gyoza dumplings.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\Gyoza.png'),
				('I00002', 'Gyoza Bento', 130, 'Famous Japanese pan-fried dumplings served with rice and salad.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\GyozaBento.png'),
				('I00003', 'Kani Fry', 130, 'Crab stick coated with bread crumbs and deep fried.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\KaniFry.png'),
				('I00004', 'Katsudon - Pork', 125, 'A bowl of rice topped with a deep-fried breaded pork cutlet, egg, and vegetables.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\Katsudon-Pork.png'),
				('I00005', 'Korean Spicy Chicken', 135, 'Lightly battered boneless chicken glazed with well-balanced spicy, sweet, sticky sauce, topped with sesame seeds', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\KoreanSpicyChicken.png'),
				('I00006', 'Omu Rice', 120, 'Omelet filled with fried rice, chicken meat and ketchup.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\Omurice.png'),
				('I00007', 'Oyakudon - Chicken', 125, 'A bowl of rice topped with a deep-fried breaded chicken cutlet, egg, and vegetables.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\Oyakudon-Chicken.png'),
				('I00008', 'Spicy Pork Bulgogi', 140, 'Thinly sliced pork belly and marinated in soy sauce mixed with spicy gochujang.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\SpicyPorkBulgogi.png'),
				('I00009', 'Tonkatsu - Pork ', 139, 'Breaded, deep-fried pork cutlet with egg and vegetable side dish.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\Tonkatsu-Pork.png'),
				('I00010', 'Tori Karaage', 135, 'Japanese fried chicken, boneless chicken lightly coated with flour and deep-fried.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\ToriKaraage.png'),
				('I00011', 'Torikatsu - Chicken', 139, 'Breaded, deep-fried chicken cutlet with egg and vegetable side dish.', 1, '\jboymakiandbento\assets\images\item-images\Bento Rice Meals\Torikatsu-Chicken.png'),
				('I00012', 'Tantanmen', 155, 'No Desciption', 1, '\jboymakiandbento\assets\images\item-images\Ramen\Tantanmen.png'),
				('I00013', 'Tonkotsu Ramen', 155, 'No Desciption', 1, '\jboymakiandbento\assets\images\item-images\Ramen\TonkotsuRamen.png'),
				('I00014', 'Gyoza Ramen', 145, 'No Desciption', 1, '\jboymakiandbento\assets\images\item-images\Ramen\GyozaRamen.png'),
				('I00015', 'Shoyu Ramen', 145, 'No Desciption', 1, '\jboymakiandbento\assets\images\item-images\Ramen\ShoyuRamen.png');

INSERT INTO ItemCategorySubCategory(itemID, categoryID, subCategoryID) VALUES
	('I00001', 'CAT00002', 'SUB00001'),
	('I00002', 'CAT00002', 'SUB00001'),
	('I00003', 'CAT00002', 'SUB00001'),
	('I00004', 'CAT00002', 'SUB00001'),
	('I00005', 'CAT00002', 'SUB00001'),
	('I00006', 'CAT00002', 'SUB00001'),
	('I00007', 'CAT00002', 'SUB00001'),
	('I00008', 'CAT00002', 'SUB00001'),
	('I00009', 'CAT00002', 'SUB00001'),
	('I00010', 'CAT00002', 'SUB00001'),
	('I00011', 'CAT00002', 'SUB00001'),
	('I00012', 'CAT00003', 'SUB00001'),
	('I00013', 'CAT00003', 'SUB00001'),
	('I00014', 'CAT00003', 'SUB00001'),
	('I00015', 'CAT00003', 'SUB00001'),



 -- Insert at least 5 students into the students table.

INSERT INTO students(first_name, last_name, gender, birth_date, email)
VALUES 
('Ethan','Morales','M','2003-07-14','morales.ethan@gmail.com'),
('Sophia','Delacruz','F','2004-02-28','delacruz.sophia@gmail.com'),
('Lucas','Reyes','M','2002-11-09','reyes.lucas@gmail.com'),
('Ava','Santiago','F','2003-05-23','santiago.ava@gmail.com'),
('Noah','Villanueva','M','2004-10-01','villanueva.noah@gmail.com');

 -- Insert 3 courses into the courses table.

INSERT INTO courses(course_name, course_code)
VALUES 
('Computational Probability and Statistics','PSTN01C'),
('Object Oriented Programming','CSCN02C'),
('Fundamentals of Database Systems','ITEN03C');

 -- Insert 5 enrollment records linking students to courses.

INSERT INTO enrollments(student_id, course_id, date_enrolled)
VALUES
(1, 2, '2025-06-14'),
(2, 2, '2025-07-02'),
(3, 1, '2025-07-27'),
(4, 3, '2025-08-09'),
(5, 1, '2025-08-25');

 -- Retrieve all students with their enrolled courses (join students, enrollments, courses).

SELECT enrollments.enrollment_id, students.first_name, students.last_name, courses.course_name FROM enrollments
JOIN students ON enrollments.student_id = students.student_id
JOIN courses ON enrollments.course_id = courses.course_id;

 -- Update one student’s email address.
 
UPDATE students
SET email = 'delacruz.sophiaS3-S@gmail.com' 
WHERE student_id = 2;

 -- Delete one enrollment record.
 
DELETE FROM enrollments WHERE enrollment_id = 1;

 -- Count the number of students in each course.

SELECT courses.course_name, COUNT(enrollments.student_id) AS student_total
FROM courses
LEFT JOIN enrollments ON courses.course_id = enrollments.course_id
GROUP BY courses.course_name;

 -- Display all students whose last name starts with “C”.

SELECT * FROM students
WHERE last_name LIKE 'C%';

 -- Perform a transaction to add an enrollment and rollback.

START TRANSACTION;

INSERT INTO enrollments(student_id, course_id, date_enrolled)
VALUES (4, 2, '2025-08-09');

SELECT * FROM enrollments WHERE student_id = 4 AND course_id = 2;

ROLLBACK;

SELECT * FROM enrollments WHERE student_id = 4 AND course_id = 2;

 -- Perform a transaction to add an enrollment and commit.	

START TRANSACTION;

INSERT INTO enrollments(student_id, course_id, date_enrolled)
VALUES (3, 2, '2025-08-09');

SELECT * FROM enrollments WHERE student_id = 3 AND course_id = 2;

COMMIT;

SELECT * FROM enrollments WHERE student_id = 3 AND course_id = 2;