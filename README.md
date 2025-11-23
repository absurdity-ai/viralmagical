# ViralMagical

ViralMagical is a web application that allows users to generate "media universes" and AI-powered apps from a single uploaded image. By leveraging generative AI, it creates unique, interactive experiences (such as games, "try-on" apps, and trading cards) based on user inputs.

## Features

*   **AI App Generation**: Upload an image to generate various types of interactive "apps" or "games".
*   **Gallery**: A "Recent Creations" gallery displaying the latest user-generated apps in reverse chronological order.
*   **Sponsor Integration**: Support for sponsored content with specific prompts and branding.
*   **API Dashboard**: A dedicated dashboard (`/api_dashboard.php`) to monitor API usage, including call counts, token usage, and detailed logs.
*   **Modern UI**: A visually striking interface featuring glassmorphism, smooth animations, and a responsive design.
*   **Clean Routing**: User-friendly URLs (e.g., `/load/app_id`) for a seamless browsing experience.
*   **Cloud Storage**: Integration with AWS S3 for secure and scalable image storage.

## Tech Stack

*   **Backend**: PHP (Vanilla)
*   **Frontend**: HTML5, CSS3 (Vanilla), JavaScript (Vanilla)
*   **Database**: MySQL
*   **Storage**: AWS S3
*   **AI Integration**: Fal.ai (via MCP/API)

## Project Structure

*   `index.php`: The main landing page and gallery.
*   `app.php`: The application generation interface.
*   `api/`: Contains API endpoints for app creation, data fetching, and S3 uploads.
    *   `get_apps.php`: Fetches apps for the gallery.
    *   `create.php`: Handles new app creation.
*   `api_dashboard.php`: Admin dashboard for viewing API logs and statistics.
*   `includes/`: Reusable PHP components (e.g., `gallery_section.php`).
*   `graphics/`: Static assets.
*   `schema.sql`: Database schema definitions.

## Setup

1.  **Clone the repository**.
2.  **Configure Environment**:
    *   Copy `.env.example` to `.env` (if applicable) or create a `.env` file.
    *   Set your database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
    *   Set your AWS S3 credentials (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`).
    *   Set your API keys for Fal.ai or other services.
3.  **Database Setup**:
    *   Import `schema.sql` into your MySQL database to create the necessary tables (`apps`, `api_logs`, etc.).
4.  **Web Server**:
    *   Configure your web server (Apache/Nginx) to handle URL rewriting. The project expects requests to `/load/{id}` to be routed to `app.php?app={id}` (or similar logic defined in `.htaccess`).
5.  **Run**:
    *   Serve the application using your preferred PHP environment (e.g., XAMPP, MAMP, or built-in PHP server).

## API Dashboard

Access `api_dashboard.php` to view:
*   **Statistics**: Total calls, total tokens, and average tokens per endpoint.
*   **Logs**: A paginated list of recent API calls with request/response details.
