# BASE_URL System Documentation

## 1. System Overview
**BASE_URL** is a comprehensive web-based platform built for the Selangor Technical Skills Development Centre (STDC). It serves as both a public-facing portal for students to apply for technical programs and an administrative backend for managing users, programs, and registrations. 

The system's standout feature is its **AI-Powered Administrative Dashboard**, which utilizes Google Cloud's Gemini LLM to allow administrators to query relational databases using natural language and visualize the data dynamically via Chart.js.

---

## 2. Directory Structure and Core Files

The application is built using a monolithic PHP architecture, structured into logical feature-based directories.

### Root Directory
- `index.php`: The main entry point and landing page for the application.
- `setup.sql`: Contains the complete DDL (Data Definition Language) for initializing the MySQL database schema.
- `package.json` / `package-lock.json`: Manages Node.js dependencies (likely utilized for frontend tooling or package management like Chart.js/Tailwind if used).
- `application_default_credentials.json`: GCP Service Account credentials for authenticating the Gemini API.

### `/admin` (Administrator Backend)
Contains all administrative functionalities. Only users with the `admin` role can access these files.
- `index.php`: The primary administrative dashboard.
- `ai_query.php`: The core logic for the **Text-to-SQL AI Assistant**, facilitating communication with the Gemini API and rendering the chat interface.
- `programs.php` & `create_program.php` & `edit_program.php`: CRUD operations for managing STDC training programs.
- `users.php` & `attendees.php`: Interfaces for managing student data and program applicants.
- `/api/`: Contains RESTful endpoints used by AJAX calls (e.g., `delete_chat.php`, `get_chat.php`) for the AI dashboard history.

### `/auth` (Authentication Flow)
Handles session management and user authentication.
- `login.php` & `register.php`: Standard email/password authentication interfaces.
- `logout.php`: Destroys the active PHP session.
- `google_login.php` & `google_callback.php`: Implements Google OAuth 2.0 Single Sign-On (SSO) for a frictionless user experience.

### `/user` (Applicant Portal)
The interface for registered applicants.
- `index.php`: The user dashboard displaying their current application statuses.
- `profile.php`: Allows users to update their personal information.
- `process_apply.php`: The backend script handling the submission of applications to specific programs.

### `/config` & `/includes`
- `config/database.php`: Establishes the PDO connection to the MySQL database.
- `config/google.php`: Configuration settings and client IDs for Google OAuth.
- `includes/header.php` & `footer.php`: Reusable HTML layout components ensuring a consistent UI.
- `includes/functions.php`: Global utility functions (e.g., sanitization, session checks).

### `/assets` (Static Files)
- `css/style.css`: Core stylesheet, implementing modern designs like glassmorphism.
- `js/main.js`: Primary JavaScript file handling DOM manipulation and AJAX requests.
- `js/form-builder.js`: Script for dynamically generating application forms based on program requirements.

---

## 3. Database Schema (`setup.sql`)

The database, `stdc_registration_staging`, is highly relational. Below is a breakdown of the primary tables:

1. **`users` Table:**
   Stores all user accounts (Admins and Applicants). 
   - Uses `password_hash` for local authentication and `google_id` for OAuth users.
   - Contains a `role` ENUM (`admin`, `user`, `developer`) for role-based access control (RBAC).

2. **`programs` & `program_fields` Tables:**
   - `programs` stores the core details of a bootcamp/course (capacity, status, poster).
   - `program_fields` allows admins to create **dynamic form fields** (e.g., text, radio, select) required for specific programs.

3. **`registrations` & `registration_answers` Tables:**
   - `registrations` acts as a pivot table linking a `user_id` to a `program_id`, tracking the `application_status` (pending, approved, etc.).
   - `registration_answers` stores the applicant's specific answers to the dynamic `program_fields`.

---

## 4. Key Integrations & Advanced Features

### 4.1 Google Cloud: Gemini 2.5 Flash LLM (`ai_query.php`)
The system integrates Google's advanced `Gemini 2.5 Flash` LLM (with intelligent fallbacks to 2.0 and 1.5) to provide a high-speed "Text-to-SQL" interface for administrators.
- **Workflow**: The admin enters a natural language query -> PHP injects the query alongside a strict database schema prompt -> Gemini API returns a JSON payload containing the exact MySQL query -> PHP safely executes the query via PDO -> Data is returned to the frontend.
- **Security**: The system utilizes strict prompt instructions (Zero-Shot prompting) and low-temperature settings to prevent the AI from generating destructive queries (e.g., `DROP`, `DELETE`). The backend only processes `SELECT` statements.

### 4.2 Chart.js Data Visualization
When the AI returns data, it often includes instructions on how to visualize it (e.g., `type: "pie"`). 
- The frontend JavaScript parses this JSON and dynamically constructs a `<canvas>` element.
- It utilizes **Chart.js** algorithms to map database columns automatically to X and Y axes, creating interactive Bar, Pie, and Line charts directly within the admin dashboard.

### 4.3 Export & Document Generation
Admins can export the generated Chart.js visualizations.
- A custom UI (Three Vertical Dots) opens an **Export Preview Modal**.
- Users can define custom dimensions (e.g., 1920x1080) to ensure legends and data points fit perfectly.
- The system uses `jsPDF` and HTML5 Canvas manipulation to convert the charts into downloadable PNG or PDF files for presentations.

### 4.4 Google Cloud Compute Engine Deployment
The application has been fully migrated from a local XAMPP environment to Google Cloud.
- **Compute Engine VM**: An IaaS approach was taken by spinning up a Linux VM on Google Compute Engine.
- **Database setup in VM**: MySQL was installed directly onto the VM (rather than utilizing a managed service like Cloud SQL) to allow for hands-on administration. The local database was exported and migrated into this cloud instance.
- **Networking**: VPC Firewall rules were configured to allow HTTP/HTTPS traffic, and an External IP was assigned so the `BASE_URL` platform is globally accessible.
