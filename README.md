# Siksik Parking System 🚗💨

A premium, interactive mockup web application for reserving mall parking spots in real-time, simulating online payments, and managing reservation statuses from a personal user dashboard. Built using a native PHP/MySQL backend and vanilla CSS/JS.

---

## 🌟 Key Features

1. **Interactive Real-Time Booking**:
   - Choose from multiple locations (e.g. SM Mall of Asia, Megamall, Ayala Manila Bay, Glorietta, TriNoma).
   - Real-time dynamic floor selection and interactive parking spot grid layout.
   - Spot validation prevents duplicate bookings for overlapping time intervals.
   - Displays indicators for standard, EV charging, and accessible parking spots.

2. **Simulated Secure Checkout Modal**:
   - Integrated mockup online payment portal supporting **GCash**, **Maya**, **PayPal**, and **Credit/Debit Cards**.
   - Generates scanable payment QR codes dynamically using the free `api.qrserver.com` chart service.
   - Simulates transition animations and payment processing latency.
   - Confirmed tickets include a downloadable receipt text file and a printable check-in QR code ticket.

3. **30-Minute Grace Period Policy**:
   - Reservations have a strict **30-minute grace period** starting from the chosen arrival time.
   - If a customer fails to check in within 30 minutes, their booking is automatically classified as **Void**.
   - Void reservations release the parking spot back to other customers and trigger a **50% automatic refund**.

4. **Personal User Portal Dashboard**:
   - Sidebar displays authenticated user details (Name, Plate Number, Phone, Email).
   - Metrics cards display total spent (accounting for 50% refunds on void bookings), active bookings, completed bookings, and cancelled bookings.
   - History tabs group reservations into:
     - **Active**: Upcoming or current slots within the grace period.
     - **Completed**: Successfully completed reservations.
     - **Void**: Slots missed past the 30-minute grace window.
     - **Cancelled**: Manually cancelled reservations.
   - Allows users to view/download tickets or cancel active reservations directly.

---

## 💰 Pricing Structure

### Standard Rate (Default)
- **First 4 Hours**: PHP 50.00 (Flat Rate).
- **Every hour added past 4 hours**: + PHP 15.00 / hour.

### Overnight Parking Option
- A checkbox in the booking sidebar enables overnight parking options.
- **Base Rate**:
  - **AM Arrival** (before 12:00 PM): PHP 145.00 flat rate.
  - **PM Arrival** (at or after 12:00 PM): PHP 120.00 flat rate.
- **Validity & Extra Hours**:
  - Valid until **7:00 AM** the next morning.
  - Any reservation duration extending past 7:00 AM next morning incurs an additional charge of **PHP 20.00 per hour** for the extra time.

---

## 🛠️ Stack & Structure

- **Backend**: Native PHP 8.x
- **Database**: MySQL / MariaDB (relational schema with foreign keys)
- **Frontend**: HTML5, Vanilla JavaScript (ES6), Custom Vanilla CSS (with blur backdrops, gradients, keyframe animations)
- **External API**: QR Code Generator API (`api.qrserver.com`)

### File Directory Structure
- `index.php`: Landing homepage with section scrolling (Home, Book, Pricing, About Us, Contact) and header portal links.
- `main.php`: Premium sign-in and account registration AJAX authentication endpoints.
- `book.php`: Interactive booking panel, slot grid, checkout modal markup, success alerts, and cancellation API.
- `dashboard.php`: User portal displaying profile, total bookings count, spent metrics, categorized history tabs, and receipt modal viewer.
- `db.php`: Database connection builder and automatic schema tables/column migrations helper.
- `get_bookings.php`: Real-time booking history fetcher endpoint.
- `logout.php`: Session destruction helper.
- `script.js`: Client-side logic for grid rendering, AJAX auth, checkout modal screens, dynamic QR code loader, and UI transitions.
- `style.css`: Master styling system containing grid configurations, modal blurring overlays, status badges, and hover effects.

---

## 🚀 Setup & Installation (XAMPP)

1. **Clone/Copy Project**:
   Place the project folder `AppDevParking` inside your XAMPP server's root directory:
   `C:\xampp\htdocs\AppDevParking`

2. **Configure Database**:
   - Start **Apache** and **MySQL** services in the XAMPP Control Panel.
   - Open phpMyAdmin (`http://localhost/phpmyadmin`) and create a new database named **`siksik_parking`**.
   - (Optional) Configure database credentials in **`db.php`**. By default, it connects to standard XAMPP configurations:
     - Host: `127.0.0.1:3306`
     - Database name: `siksik_parking`
     - User: `root`
     - Password: `""`

3. **Initialize Tables & Migrations**:
   Simply navigate to the website homepage in your browser. The connection handler **`db.php`** is included at the top of all major endpoints and will **automatically run database migrations**, create the tables (`users`, `bookings`), and append any missing columns (e.g. `booking_date`, `payment_method`, `payment_status`, `is_overnight`) on first load.

4. **Launch Application**:
   Open your browser and visit:
   `http://localhost/AppDevParking/parking-system/index.php`