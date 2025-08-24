# Tracking System Web App

This project uses Docker Compose to manage and run the application. Alternatively, you can run the application without Docker by setting up the environment manually.

## Prerequisites

- Docker installed on your system (if using Docker).
- Docker Compose installed (if using Docker).
- PHP 7.1 installed on your system (if not using Docker).
- Composer installed (if not using Docker).
- Node.js and npm installed (if not using Docker).
- A database server (e.g., MySQL or PostgreSQL).

## Getting Started

Follow these steps to execute the application:

### Using Docker Compose

#### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/trackingsystemwebapp.git
cd trackingsystemwebapp
```

#### 2. Build and Start the Containers

Run the following command to build and start the containers:

```bash
docker-compose up --build
```

This will build the images (if not already built) and start the containers.

#### 3. Initialize the Database with Sample Data

To populate the MongoDB database with sample data for the development environment, ensure that the `docker/mongodb/init` folder contains the necessary JSON files. These files will be automatically imported into the database when the MongoDB container starts.

#### 4. Access the Application

Once the containers are running, you can access the application in your browser at:

```
http://localhost:PORT
```

Replace `PORT` with the port number specified in the `docker-compose.yml` file.

#### 5. Stop the Containers

To stop the running containers, press `Ctrl+C` in the terminal where `docker-compose` is running. Alternatively, you can run:

```bash
docker-compose down
```

This will stop and remove the containers.

### Without Docker

#### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/trackingsystemwebapp.git
cd trackingsystemwebapp
```

#### 2. Set Up the Environment File

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

Update the `.env` file with your database credentials and other configuration values.

#### 3. Install Dependencies

Run the following commands to install PHP and JavaScript dependencies:

```bash
composer install
npm install
npm run dev
```

#### 4. Generate the Application Key

Run the following command to generate the application key:

```bash
php artisan key:generate
```

#### 5. Start the Development Server

Run the following command to start the Laravel development server:

```bash
php artisan serve
```

By default, the application will be accessible at:

```
http://127.0.0.1:8000
```

## Additional Commands

- To view the logs of a specific service (Docker):
    ```bash
    docker-compose logs <service_name>
    ```
- To restart a specific service (Docker):
    ```bash
    docker-compose restart <service_name>
    ```
- To clear Laravel caches:
    ```bash
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    ```
    ## Windows Users

    If you are running the application on Windows using Docker, you may need to set the correct permissions for the `storage` and `bootstrap/cache` directories inside the container. Run the following commands:

    ```bash
    docker exec -it laravel_app bash
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    ```

    This ensures Laravel can write to these directories without permission issues.

## Configuration

### Using Docker Compose

You can modify the `docker-compose.yml` file to adjust the configuration of the services. Ensure that the `docker/mongodb/init` folder contains the JSON files required to initialize the MongoDB database with sample data.

### Without Docker

You can modify the `.env` file to adjust the configuration of the application, such as database credentials, application URL, and other environment variables.

## License

This project is licensed under the [MIT License](LICENSE).