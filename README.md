# TaskDesk

A web-based issue and ticket management system for small to medium teams.

Built with plain PHP and MySQL — no frameworks, no Composer, no JavaScript libraries.

## Course

MIT122 Interactive Web Design and Development

## Team

- Pradeep Shrestha (988584)
- Jeewan Ghimire (988512)
- Kishwor Shrestha

## Tech Stack

- **Frontend:** HTML5, CSS3, a sprinkle of vanilla JavaScript
- **Backend:** PHP (procedural, MySQLi with prepared statements)
- **Database:** MySQL
- **Server:** XAMPP / WAMP (Apache + MySQL)

## Features

### User Management
- Registration with bcrypt password hashing
- Session-based login / logout
- Session guard for protected pages
- Role column on the users table (`admin` / `developer`)

### Ticket Management
- Create, read, update, delete tickets
- Title, description, status, priority, due date, assignee
- Status workflow: Open → In progress → Resolved → Closed
- Priority: Low / Medium / High (color-coded badges)
- Inline quick status switcher on the detail page
- Filter bar on the list (status, priority, assignee)
- Overdue rows highlighted in red on both list and detail

### Subtasks
- Any ticket can be the parent of other tickets via `parent_ticket_id`
- "Add subtask" button on the detail page prefills the parent
- Progress bar shows resolved/closed children vs. total

### Comments & Collaboration
- Threaded comments per ticket (`parent_comment_id`)
- Author name and timestamp on every comment
- Inline reply form on each thread

### Reporting
- Dashboard: 5 stat cards (Total, Open, In progress, Resolved, Closed)
- Reports page: status distribution as a CSS bar chart, priority counts,
  per-user contribution table, and an overdue panel

### Security
- Authentication and authorisation via session-based login (session guard on protected pages)
- Password hashing with `password_hash` / verification with `password_verify`
- Session management — session ID regenerated on login and logout
- Input validation against allow-lists for enums (status, priority)
- Output escaping — every user-supplied value runs through `htmlspecialchars`
- SQL injection prevention — every database write goes through MySQLi prepared statements

## Local Setup

1. Install [XAMPP](https://www.apachefriends.org/) (or WAMP).
2. Clone this repo somewhere convenient (does not need to be inside `htdocs/`):
   ```
   git clone https://github.com/PradeepShresth/TaskDesk.git
   ```
3. Start Apache and MySQL from the XAMPP control panel.
4. Import the schema:
   ```
   "C:/xampp/mysql/bin/mysql.exe" -u root < database/schema.sql
   ```
5. Copy `config/config.sample.php` to `config/config.php` and update credentials
   and `BASE_URL` for your setup.
6. Either:
   - Point Apache at the project (Alias or symlink `htdocs/TaskDesk` → this folder),
     then visit `http://localhost/TaskDesk/public/`, **or**
   - Start the built-in PHP server (no Apache config needed):
     ```
     "C:/xampp/php/php.exe" -S localhost:8000 -t public
     ```
     and visit `http://localhost:8000/`.

## Project Structure

```
TaskDesk/
├── config/
│   ├── config.sample.php   # template (committed)
│   ├── config.php          # local credentials (gitignored)
│   └── db.php              # MySQLi connection
├── database/
│   └── schema.sql          # users / tickets / comments
├── includes/
│   ├── auth_guard.php      # require for logged-in pages
│   ├── functions.php       # e/url/redirect/flash/csrf/overdue helpers
│   ├── header.php          # shared top bar + nav + flash
│   └── footer.php          # shared closing markup
└── public/                 # the document root
    ├── assets/css/         # auth.css, app.css
    ├── register.php
    ├── login.php
    ├── logout.php
    ├── dashboard.php
    ├── tickets.php
    ├── ticket.php
    ├── ticket_create.php
    ├── ticket_edit.php
    ├── ticket_delete.php
    ├── comment_post.php
    └── reports.php
```

## Development Approach

Agile / Scrum. Features delivered in sprints:

- **Sprint 1** — User registration & authentication
- **Sprint 2** — Ticket creation & database integration
- **Sprint 3** — Task assignment, status, priority, subtasks, comments
- **Sprint 4** — Reporting & system enhancement (security hardening, polish)
