# GateGuard: RFID-Based Campus Access Control and Attendance Monitoring System

**St. Francis Xavier College**  
San Francisco, Agusan del Sur

## Project Overview

GateGuard is an RFID-based campus access control and student attendance monitoring system designed to record student entry and exit, monitor attendance in real time, and notify parents through SMS notifications.

The system integrates RFID technology, an ESP32 microcontroller, a web-based administration dashboard, and an automatic gate mechanism to improve campus security and attendance management.

## Key Features

- **RFID Student Identification** — Identifies registered students using RFID cards.
- **Attendance Monitoring** — Records student entry (IN) and exit (OUT).
- **Live Gate Monitoring** — Displays recent student scans on a dedicated monitoring page.
- **Web-Based Administration** — Manages student information and attendance records.
- **SMS Notifications** — Sends attendance notifications to parents.
- **Automatic Gate Access** — Integrates an ESP32-controlled relay with a 12V magnetic lock.
- **Student Records Management** — Supports student registration and record updates.

## Hardware Requirements

- ESP32 development board
- MFRC522 RFID reader
- Compatible RFID cards or tags
- 1-channel relay module compatible with the ESP32 control signal
- 12V magnetic lock and compatible power supply
- Connecting wires
- Computer or laptop

## Software Requirements

- Windows operating system
- XAMPP
- Apache web server
- MySQL or MariaDB
- PHP
- Arduino IDE
- ESP32 board support package
- Required Arduino libraries: WiFi, HTTPClient, SPI, and MFRC522

## System Technologies

| Component | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript, Bootstrap |
| Backend | PHP |
| Database | MySQL / MariaDB |
| Microcontroller | ESP32 |
| RFID Reader | MFRC522 |
| Notifications | Android SMS Gateway integration |

## Installation Guide

1. Install XAMPP and start Apache and MySQL.
2. Place the project folder inside the XAMPP `htdocs` directory.
3. Create or import the required project database in phpMyAdmin.
4. Configure the database connection in `db.php` using your local credentials.
5. Open the Arduino sketch in Arduino IDE.
6. Configure the Wi-Fi credentials and server IP address in the ESP32 sketch.
7. Connect the RFID reader and other hardware according to the project's wiring diagram.
8. Test student registration, RFID scanning, attendance recording, SMS notifications, and gate operation.

## Local Access

After configuring XAMPP, open the following addresses in a browser:

- Administration dashboard: `http://localhost/gate_guard/index.php`
- Gate monitoring display: `http://localhost/gate_guard/gate_monitor.php`

These addresses are for local development. Remote access requires additional hosting or network configuration.

## Repository Information

- **Repository Platform:** GitHub
- **Repository URL:** https://github.com/JohnMarkCastillo/Gate-Guard-RFID-System
- **Primary Branch:** `main`

## Security Notice

Do not commit database passwords, SMS API tokens, student personal information, or production credentials to a public repository. Configure private credentials separately and use appropriate access controls.

## Project Purpose

GateGuard aims to improve student attendance tracking, campus access monitoring, and parent communication through an integrated RFID-based system.

---

*Developed as an academic project for campus access control and attendance monitoring.*
