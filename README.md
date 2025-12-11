# Calandria RSS Aggregator

A modern news aggregator in the style of Foorilla, built with PHP, MySQL, and Vanilla JavaScript.

## Features

- **RSS Fetching**: Automated fetching from multiple sources.
- **MVC Architecture**: Clean separation of concerns.
- **REST API**: JSON endpoints for articles and sources.
- **Responsive UI**: Infinite scroll, real-time filtering, and modern design.
- **Admin Panel**: Manage sources and view statistics.

## Setup

1.  **Install Dependencies**:
    ```bash
    composer install
    ```

2.  **Database**:
    - Create a MySQL database.
    - Import `database/schema.sql`.

3.  **Configuration**:
    - Copy `.env.example` to `.env`.
    - Update database credentials.

4.  **Cron Jobs**:
    - Set up the fetch script to run periodically.
    ```bash
    */15 * * * * php cron/fetch-feeds.php
    ```

## License

MIT
