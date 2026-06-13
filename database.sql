-- ShelfAware: Community Library Lending System
-- Schema and seed data for MySQL 8.0 / MariaDB 10.6

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ----------------------------------------------------------------
-- Schema
-- ----------------------------------------------------------------

CREATE TABLE IF NOT EXISTS category (
    Category_id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS book (
    Book_id INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(200) NOT NULL,
    Author VARCHAR(150) NOT NULL,
    ISBN VARCHAR(20),
    Category_id INT NOT NULL,
    CopiesTotal INT NOT NULL DEFAULT 1,
    CopiesAvailable INT NOT NULL DEFAULT 1,
    Year INT,
    CONSTRAINT fk_book_category FOREIGN KEY (Category_id) REFERENCES category(Category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS member (
    Member_id INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(150) NOT NULL,
    Email VARCHAR(150) NOT NULL UNIQUE,
    Phone VARCHAR(20),
    JoinDate DATE NOT NULL,
    Password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff (
    Staff_id INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(150) NOT NULL,
    Role ENUM('librarian','admin') NOT NULL DEFAULT 'librarian'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loan (
    Loan_id INT PRIMARY KEY AUTO_INCREMENT,
    Book_id INT NOT NULL,
    Member_id INT NOT NULL,
    LoanDate DATE NOT NULL,
    DueDate DATE NOT NULL,
    ReturnDate DATE NULL,
    renewals TINYINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_loan_book FOREIGN KEY (Book_id) REFERENCES book(Book_id),
    CONSTRAINT fk_loan_member FOREIGN KEY (Member_id) REFERENCES member(Member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hold (
    Hold_id INT PRIMARY KEY AUTO_INCREMENT,
    Book_id INT NOT NULL,
    Member_id INT NOT NULL,
    PlacedAt DATETIME NOT NULL,
    Status ENUM('waiting','ready','cancelled') NOT NULL DEFAULT 'waiting',
    CONSTRAINT fk_hold_book FOREIGN KEY (Book_id) REFERENCES book(Book_id),
    CONSTRAINT fk_hold_member FOREIGN KEY (Member_id) REFERENCES member(Member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fine (
    Fine_id INT PRIMARY KEY AUTO_INCREMENT,
    Loan_id INT NOT NULL,
    Amount DECIMAL(8,2) NOT NULL,
    Paid TINYINT(1) NOT NULL DEFAULT 0,
    PaidAt DATETIME NULL,
    CollectedBy INT NULL,
    CONSTRAINT fk_fine_loan FOREIGN KEY (Loan_id) REFERENCES loan(Loan_id),
    CONSTRAINT fk_fine_staff FOREIGN KEY (CollectedBy) REFERENCES staff(Staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS review (
    Review_id INT PRIMARY KEY AUTO_INCREMENT,
    Book_id INT NOT NULL,
    Member_id INT NOT NULL,
    Rating TINYINT NOT NULL,
    Comment TEXT,
    CreatedAt DATETIME NOT NULL,
    CONSTRAINT fk_review_book FOREIGN KEY (Book_id) REFERENCES book(Book_id),
    CONSTRAINT fk_review_member FOREIGN KEY (Member_id) REFERENCES member(Member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
    Log_id INT PRIMARY KEY AUTO_INCREMENT,
    Staff_id INT NOT NULL,
    Action VARCHAR(255) NOT NULL,
    EntityType VARCHAR(50),
    EntityId INT,
    CreatedAt DATETIME NOT NULL,
    CONSTRAINT fk_log_staff FOREIGN KEY (Staff_id) REFERENCES staff(Staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------
-- Seed data
-- ----------------------------------------------------------------

INSERT INTO category (Category_id, Name) VALUES
(1, 'Fiction'),
(2, 'Science'),
(3, 'History');

INSERT INTO book (Book_id, Title, Author, ISBN, Category_id, CopiesTotal, CopiesAvailable, Year) VALUES
(1, 'The Great Gatsby',       'F. Scott Fitzgerald', '978-0743273565', 1, 3, 2, 1925),
(2, 'To Kill a Mockingbird',  'Harper Lee',          '978-0061935466', 1, 2, 1, 1960),
(3, 'A Brief History of Time','Stephen Hawking',     '978-0553380163', 2, 2, 2, 1988),
(4, 'The Selfish Gene',       'Richard Dawkins',     '978-0198788607', 2, 1, 1, 1976),
(5, 'Sapiens',                'Yuval Noah Harari',   '978-0062316097', 3, 3, 3, 2011),
(6, 'The Guns of August',     'Barbara Tuchman',     '978-0345476098', 3, 2, 2, 1962);

-- Passwords: password123 for all members (bcrypt)
INSERT INTO member (Member_id, Name, Email, Phone, JoinDate, Password) VALUES
(1, 'Alice Meadows',  'alice@library.local',  '555-0101', '2023-01-10', '$2y$12$c4F9JHFDLoRPRERqGftcU.7mKKZz/D8KxmGTMkHvORlHknWlMHN6K'),
(2, 'Bob Harrington', 'bob@library.local',    '555-0102', '2023-02-03', '$2y$12$c4F9JHFDLoRPRERqGftcU.7mKKZz/D8KxmGTMkHvORlHknWlMHN6K'),
(3, 'Carol Stein',    'carol@library.local',  '555-0103', '2023-02-20', '$2y$12$c4F9JHFDLoRPRERqGftcU.7mKKZz/D8KxmGTMkHvORlHknWlMHN6K');

-- Passwords: admin123 for all staff (bcrypt)
INSERT INTO staff (Staff_id, Username, Password, Email, Role) VALUES
(1, 'libra', '$2y$12$rfVPEtLZ.VL.9uPZkdJj3u7dIWfRfjLDqHnOlZl0wMJC6w6TLU2iy', 'libra@library.local',  'librarian'),
(2, 'admin', '$2y$12$Fp1YCutxWynJWiNJsXWli.dtpKYkTeDcP1M/myjIhV3BRX6IHJ/xm', 'admin@library.local',  'admin');

-- Sample loans (one overdue, one active, one returned)
INSERT INTO loan (Loan_id, Book_id, Member_id, LoanDate, DueDate, ReturnDate) VALUES
(1, 1, 1, '2023-03-01', '2023-03-15', '2023-03-14'),
(2, 2, 2, '2023-03-10', '2023-03-24', NULL),
(3, 3, 3, '2023-02-15', '2023-03-01', NULL);

-- Update copy counts to reflect active loans
UPDATE book SET CopiesAvailable = CopiesAvailable - 1 WHERE Book_id IN (2, 3);

-- Sample holds
INSERT INTO hold (Hold_id, Book_id, Member_id, PlacedAt, Status) VALUES
(1, 2, 3, '2023-03-11 10:00:00', 'waiting'),
(2, 1, 2, '2023-03-05 14:30:00', 'ready');

-- Sample fine for returned-late loan (loan 1 was returned on time — no fine)
-- Fine for overdue loan 3
INSERT INTO fine (Fine_id, Loan_id, Amount, Paid, PaidAt, CollectedBy) VALUES
(1, 3, 2.50, 0, NULL, NULL);

-- Sample reviews
INSERT INTO review (Review_id, Book_id, Member_id, Rating, Comment, CreatedAt) VALUES
(1, 1, 1, 5, 'A masterpiece of American literature. The prose is stunning.', '2023-03-15 09:30:00'),
(2, 3, 2, 5, 'Changed the way I think about the universe. Accessible yet profound.', '2023-03-20 11:00:00'),
(3, 5, 3, 4, 'Fascinating sweep of human history. Some chapters felt rushed.', '2023-03-22 14:45:00');
