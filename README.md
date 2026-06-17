# ShelfAware

Library management system — member accounts, book catalogue, loans, holds queue, overdue fines, staff reviews. PHP + MySQL, no framework or ORM. Run it locally with the built-in PHP server.

## What it does

Members can search the catalogue, borrow books, place holds, see their fines, and leave ratings on things they've returned. Staff get a dashboard for checkouts, returns, and holds. Admins can also manage staff accounts and see an activity log.

The return flow is the most interesting part — it checks if a book is overdue, calculates the fine (daily rate × days late), inserts it into the `fine` table, and updates copy counts, all inside a single transaction. Holds also auto-advance when a copy comes back.

## Getting it running

You'll need PHP 8.2+ and MySQL 8.0+ (or MariaDB).

```bash
mysql -u root -p -e "CREATE DATABASE shelfaware CHARACTER SET utf8mb4;"
mysql -u root -p shelfaware < database.sql
```

Then edit `config.php` with your DB credentials and start the server:

```bash
php -S localhost:8080
```

**Note for MySQL 8.4+:** the default auth plugin changed, so PDO might refuse to connect. Quick fix:
```sql
ALTER USER 'youruser'@'localhost' IDENTIFIED WITH mysql_native_password BY 'yourpassword';
```

## Docker

```bash
docker compose up --build
```

The app runs at http://localhost:8080. The database is seeded automatically from `database.sql` on first start. Demo accounts are the same as above.

To stop and remove volumes:
```bash
docker compose down -v
```

## Accounts (seed data)

| Role | Login | Password |
|------|-------|----------|
| Member | alice@library.local | password123 |
| Member | bob@library.local | password123 |
| Librarian | libra | admin123 |
| Admin | admin | admin123 |

Members log in with email, staff with username.

## Some queries worth looking at

Overdue list with days late:
```sql
SELECT b.Title, m.Name, l.DueDate,
       DATEDIFF(CURDATE(), l.DueDate) AS days_late
FROM loan l
JOIN book b ON b.Book_id = l.Book_id
JOIN member m ON m.Member_id = l.Member_id
WHERE l.ReturnDate IS NULL AND l.DueDate < CURDATE()
ORDER BY days_late DESC;
```

Hold queue position (counts how many earlier holds exist for the same book):
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

Most borrowed:
```sql
SELECT b.Title, b.Author, COUNT(*) AS times_borrowed
FROM loan l
JOIN book b ON b.Book_id = l.Book_id
GROUP BY b.Book_id
ORDER BY times_borrowed DESC
LIMIT 10;
```

## License

MIT
