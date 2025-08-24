# Tracking System Parser

This project is a Node.js-based TCP server designed to handle and process data from GPS tracking devices. It parses incoming messages, processes the data, and stores it in a MongoDB database. The server also supports sending notifications and managing device states.

## Features

- Handles various types of GPS device messages, including:
    - Heartbeat data (`GTHBD`)
    - Counter data (`GTDAT`)
    - Normal data (`GTFRI`)
    - Control points data (`GTGEO`)
    - Door events (`GTDIS`)
    - Device power events (`GTMPN`, `GTMPF`, `GTIGN`, `GTIGF`)
- Stores parsed data in MongoDB collections (`unidads`, `recorridos`, etc.).
- Sends email notifications for specific events (e.g., speed violations, low voltage).
- Supports scheduled tasks for resetting counters.
- Handles GPS coordinate transformations and speed calculations.
- Logs errors and raw messages for debugging.

## Prerequisites

- Node.js (v12 or higher)
- MongoDB
- NPM packages:
    - `net`
    - `moment`
    - `mongodb`
    - `nodemailer`
    - `node-schedule`

## Installation

1. Clone the repository:
     ```bash
     git clone <repository-url>
     cd <repository-folder>
     ```

2. Install dependencies:
     ```bash
     npm install
     ```

3. Configure the MongoDB connection string in the code:
     ```javascript
     const connection = 'mongodb://<username>:<password>@<host>:<port>/<database>?authSource=admin';
     ```

4. Start the server:
     ```bash
     node <script-name>.js
     ```

## Usage

- The server listens on port `8085` by default. You can modify the `PORT` constant in the code if needed.
- Devices send messages to the server, which processes and stores the data in MongoDB.
- Scheduled tasks (e.g., counter resets) are managed using `node-schedule`.

## Code Overview

- **Message Parsing**: The server parses incoming messages based on predefined formats (e.g., `GTHBD`, `GTFRI`).
- **Database Operations**: Data is stored and updated in MongoDB collections (`unidads`, `recorridos`).
- **Notifications**: Email notifications are sent using `nodemailer` for specific events like speed violations.
- **Error Handling**: Errors and raw messages are logged in the `LogError` and `tramas` collections for debugging.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.

## Disclaimer

This project is intended for educational and development purposes. Ensure compliance with local laws and regulations when using GPS tracking systems.
