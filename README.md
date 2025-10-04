# Simple PHP URL Shortener

A clean, simple, and modern URL shortener built with PHP and MySQL. This project allows users to shorten long URLs into a manageable 6-character code

## Features

-   **Simple Interface:** A minimalist UI for quick URL shortening.
-   **Secure:** Uses PDO for database queries to prevent SQL injection.
-   **Efficient:** Checks if a URL has already been shortened to avoid duplicate entries.
-   **REST-like API:** Uses JavaScript's `fetch` API for a smooth user experience without page reloads.
-   **SEO-friendly Redirects:** Uses clean URLs (e.g., `yourdomain.com/AbC12`) via `.htaccess`.

## Setup Instructions

1.  **Database Setup:**
    -   Create a new MySQL database.
    -   Import the `database.sql` file to create the necessary `urls` table.

2.  **Configuration:**
    -   Open the `config.php` file.
    -   Update the database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) with your own.

3.  **Upload Files:**
    -   Upload all project files to your web server.

4.  **Web Server Configuration:**
    -   Ensure that your Apache server has `mod_rewrite` enabled to allow the `.htaccess` file to work correctly.

That's it! The application should now be running. TNX Moulodi