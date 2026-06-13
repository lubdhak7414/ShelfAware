# ShelfAware

Community library lending system built with PHP 8.2 and MySQL. Demonstrates relational SQL across 8 tables — catalogue, loans, holds, fines, reviews, and a staff activity log.

## Features

- Browsable catalogue with search and category filtering
- Member registration, login, and account page (loans, holds, fines)
- Staff workflows: check out, return, waive fines, fulfil holds, view overdue list
- Automatic fine calculation on late returns (wrapped in a transaction)
- Reservation queue with `ROW_NUMBER()`-style hold position
- Member star ratings and comments on returned books
- Admin panel: manage staff accounts, browse activity log

## Prerequisites

- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.6+
- A web server (Apache, Nginx) or PHP's built-in server

> **MySQL 8.4+ note:** MySQL 8.4 removed `mysql_native_password` as a default plugin.
> If PDO fails to connect, either add `caching_sha2_password` support in `db.php` or
> create the database user with `mysql_native_password`:
> ```sql
> ALTER USER 'shelfaware'@'localhost' IDENTIFIED WITH mysql_native_password BY 'yourpassword';
> ```

## Setup

```bash
# 1. Create the database
mysql -u root -p -e "CREATE DATABASE shelfaware CHARACTER SET utf8mb4;"

# 2. Import schema and seed data
mysql -u root -p shelfaware < database.sql

# 3. Edit connection settings
cp config.php config.php   # already has defaults; edit DB_USER / DB_PASS if needed

# 4. Start the built-in server
php -S localhost:8080
```

Open `http://localhost:8080` in your browser.

## Demo accounts

| Role | Login field | Value | Password |
|------|-------------|-------|----------|
| Member | Email | alice@library.local | `password123` |
| Member | Email | bob@library.local | `password123` |
| Librarian | Username | libra | `admin123` |
| Admin | Username | admin | `admin123` |

## Sample queries

### Overdue loans
```sql
SELECT b.Title, m.Name, l.DueDate,
       DATEDIFF(CURDATE(), l.DueDate) AS days_late
FROM loan l
JOIN book b ON b.Book_id = l.Book_id
JOIN member m ON m.Member_id = l.Member_id
WHERE l.ReturnDate IS NULL AND l.DueDate < CURDATE()
ORDER BY days_late DESC;
```

### Most borrowed books
```sql
SELECT b.Title, b.Author, COUNT(*) AS times_borrowed
FROM loan l
JOIN book b ON b.Book_id = l.Book_id
GROUP BY b.Book_id
ORDER BY times_borrowed DESC
LIMIT 10;
```

### Average rating per book
```sql
SELECT b.Title, ROUND(AVG(r.Rating), 1) AS avg_rating, COUNT(*) AS reviews
FROM review r
JOIN book b ON b.Book_id = r.Book_id
GROUP BY b.Book_id
HAVING reviews >= 1
ORDER BY avg_rating DESC;
```

### Hold queue position
```sql
SELECT h.Hold_id, b.Title, m.Name,
       COUNT(h2.Hold_id) + 1 AS queue_position
FROM hold h
JOIN book b ON b.Book_id = h.Book_id
JOIN member m ON m.Member_id = h.Member_id
LEFT JOIN hold h2 ON h2.Book_id = h.Book_id
  AND h2.Status = 'waiting'
  AND h2.PlacedAt < h.PlacedAt
WHERE h.Status = 'waiting'
GROUP BY h.Hold_id
ORDER BY b.Title, queue_position;
```

## License

MIT — see [LICENSE](LICENSE)
