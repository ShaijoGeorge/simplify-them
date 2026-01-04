# SimplifyThem

**Insurance Agency Management System built with PHP Symfony.**

This multi-tenant architecture features:
* Policy tracking
* Client management
* Bulk Excel imports
* Dynamic Indian GST calculations

## 🛠 Tech Stack

* **Backend:** PHP 8.x, Symfony Framework
* **Database:** MySQL 8.0
* **Server:** Apache (Custom configured via `.htaccess`)
* **Infrastructure:** Docker & Docker Compose
* **Dependency Management:** Composer

## 🚀 Getting Started

Follow these instructions to get a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

* [Docker Desktop](https://www.docker.com/products/docker-desktop) installed and running.
* Git.

### Installation & Setup

1.  **Clone the repository**
    ```bash
    git clone [https://github.com/ShaijoGeorge/SimplifyThem.git](https://github.com/ShaijoGeorge/SimplifyThem.git)
    cd SimplifyThem
    ```

2.  **Start the Docker Containers**
    This project uses Docker Compose to orchestrate the Web and Database services.
    ```bash
    docker compose up -d
    ```
    * **Web Server:** `localhost:8001`
    * **Database:** `localhost:3309`

3.  **Configure Apache Routing**
    To ensure clean URLs and proper routing, generate the `.htaccess` file from the provided template:
    ```bash
    cp public/.htaccess.example public/.htaccess
    ```

4.  **Install PHP Dependencies**
    Run Composer inside the container to install vendor packages:
    ```bash
    docker exec -it simplifythem-web composer install
    ```

5.  **Database Setup**
    *Note: The database connection is pre-configured in `compose.yaml`.*
    
    If you have migrations, run them to set up the schema:
    ```bash
    docker exec -it simplifythem-web php bin/console doctrine:migrations:migrate
    ```

## 🌐 Usage

Once the installation is complete, open your browser and visit:

**[http://localhost:8001](http://localhost:8001)**

## 📂 Project Structure

* `compose.yaml`: Defines the Docker services (Web + MySQL).
* `Dockerfile`: Builds the PHP/Apache environment.
* `src/`: Contains the Symfony application source code.
* `public/`: The web root (contains the `.htaccess` entry point).

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).