### Laravel Backend Server for Productize

## Features

-   **User Authentication:** Secure user registration and login with sanctum using Laravel's built-in cookie based session authentication services.
-   **Product Management:** Admins can add, edit, and manage products in various categories.
-   **Search and Filtering:** Powerful search and filter options for users to find products.
-   **Order Processing:** Manage and track customer orders with real-time updates.
-   **User Profiles:** Personalized user profiles with order history and saved addresses.
-   **Payment Integration:** Seamless payment processing with various payment gateways.
-   **Seller Integration:** Platform for sellers to list their products and manage inventory.
-   **Product Recommendations:** Personalized recommendations for users based on browsing and purchase history.
-   **FileSystem:**Default Filesystem for the application is digital ocean spaces. configurations are saved as spaces.

## Installation

1. Clone this repository to your local machine:

    ```bash
    git clone https://github.com/your-username/backend-server.git
    ```

2. Install Composer dependencies:

    ```bash
    composer install
    ```

3. Create a `.env` file by copying `.env.example`:

    ```bash
    cp .env.example .env
    ```

4. Generate an application key:

    ```bash
    php artisan key:generate
    ```

5. Configure your database in the `.env` file:

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=your_database_name
    DB_USERNAME=your_database_username
    DB_PASSWORD=your_database_password
    ```

6. Run migrations and seed the database:

    ```bash
    php artisan migrate --seed
    ```

7. Create create a symbolic link from `public/storage` to `storage/app/public` to public file

    ```bash
    php artisan storage:link
    ```

8. Start the development server:

    ```bash
    php artisan serve
    ```

9. Your Laravel backend server is now up and running! Access it at `http://localhost:8000`.

## API Documentation

-   [Link to API Documentation](https://documenter.getpostman.com/view/21281964/2s9YC32Eip) 

## Contributing

We welcome contributions from the community! If you'd like to contribute to this project, please follow our [Contributing Guidelines](CONTRIBUTING.md).

## License

This project is licensed under the [MIT License](LICENSE).

## Contact

-   Developer: Tobi Olanitori
-   Email: tobi.olanitori@techstudioacademy.com
-   GitHub: [GitHub Profile](https://github.com/intuneteq)
