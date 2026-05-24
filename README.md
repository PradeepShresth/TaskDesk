# TaskDesk

A web-based issue and ticket management system for small to medium teams.

Built with plain PHP and MySQL — no frameworks.

## Course

MIT122 Interactive Web Design and Development

## Team

- Pradeep Shrestha (988584)
- Jeewan Ghimire (988512)
- Kishwor Shrestha

## Tech Stack

- **Frontend:** HTML5, CSS3, vanilla JavaScript
- **Backend:** PHP (procedural, MySQLi)
- **Database:** MySQL
- **Server:** XAMPP / WAMP (Apache + MySQL)

## Local Setup

1. Install [XAMPP](https://www.apachefriends.org/) (or WAMP).
2. Clone this repo into `htdocs/`:
   ```
   C:\xampp\htdocs\TaskDesk\
   ```
3. Start Apache and MySQL from the XAMPP control panel.
4. Open phpMyAdmin (http://localhost/phpmyadmin) and import the schema:
   ```
   database/schema.sql
   ```
5. Copy `config/config.sample.php` to `config/config.php` and update DB credentials if needed.
6. Visit http://localhost/TaskDesk/public/

## Project Structure

```
TaskDesk/
├── config/         # DB connection and app config
├── database/       # SQL schema and seed data
├── includes/       # Shared PHP partials (header, footer, auth guard)
├── public/         # Web-accessible entry points (the document root)
│   └── assets/     # CSS, JS, images
└── README.md
```

## Development Approach

Agile / Scrum. Features delivered in sprints:

- **Sprint 1** — User registration & authentication
- **Sprint 2** — Ticket creation & database integration
- **Sprint 3** — Task assignment & status management
- **Sprint 4** — Reporting & system enhancement
