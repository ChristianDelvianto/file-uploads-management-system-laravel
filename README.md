[In development] Cloud Storage Backend Project

# Project Overview

A cloud storage backend project is a web application that provides users with the ability to upload, store, and manage their files in the cloud. The project is designed to be secure, efficient, and scalable, allowing users to easily access their files from anywhere with an internet connection. The backend of the application is built using PHP and Laravel, which provides a robust framework for handling file uploads, user authentication, and data management.
The project includes features such as file sharing, access control, and quota management to ensure that users can effectively manage their storage space and share with others. Overall, this cloud storage backend project serves as a demonstration of my skills in web development, system design, and problem-solving, deciding trade-offs, and is intended to showcase my capabilities as a developer.

---

# Key Features

- User authentication and authorization
- File upload and download functionality
- File sharing, access control and link generation for viewing and downloading files
- File deletion and trash management
- Quota management to enforce storage limits based on user plans
- Queue to process file uploads and deletions asynchronously, improving performance and user experience
- Data integrity and security measures to protect user data and ensure reliable file storage
- Comprehensive testing to ensure reliability and robustness

---

# Technologies Used

- PHP 8.3+
- Laravel 12
- MySQL
- PHPUnit for testing
- Laravel Sanctum for API authentication
- Laragon for local development
- Laravel Queue for asynchronous processing

---

# Assumptions

- Role-based access control between Admin and User intended to be simple, using `role` field in the `users` table to differentiate between admin and regular users. This approach is sufficient for the scope of this project, but may not be suitable for more complex applications with multiple roles and permissions.
- Payment is omitted from this project, the project focuses solely on the backend file storage functionality, user experience, and data integrity.
- Admin functionality is omitted from this project, the project focuses solely on the user-facing features and does not include an administrative interface nor functionality for managing users, files, or system settings.
- The project developed with scalability in mind, but **_it is not designed to handle extremely high traffic or large-scale deployments._** It is intended for small-sized applications and may require additional optimizations and infrastructure to support larger workloads.
- All operations are designed to be atomic, **_ensuring that either complete successfully or fail without leaving the system in an inconsistent state_**. This is achieved through the use of database transactions and careful error handling.

---

# Disclaimer

**This project is a portfolio piece and is not intended for production use**. It is designed to demonstrate my skills and capabilities as a developer, and may not include all the features, optimizations, or security measures that would be necessary for a production-ready application. It is important to thoroughly review and test the code before using it in any real-world applications, and to make any necessary adjustments or improvements to ensure that it meets the specific requirements of your project.

---

# Conclusion

In conclusion, this cloud storage backend project is a demonstration of my ability to design and implement a secure, efficient, and scalable file storage solution. It includes a range of features and technologies that are commonly used in modern web applications, and is designed to be maintainable and extensible for future development. I hope that this project provides a clear example of my skills, system design, how I approach problem-solving, and also capabilities as a developer. I welcome any feedback or questions about the project, and I am open to discussing how it can be improved or extended in the future.

---

To see more details about how specific features work, please refer to the following documents:

- [Architecture](DOCS/ARCHITECTURE.md) The architecture of the application, including the design of the data models, controllers, services, and repositories, as well as the relationships between different components of the system.
- [Trade-offs](DOCS/TRADE_OFFS.md) The trade-offs made in the architectural decisions, including the pros and cons of each decision and how they impact the overall design and functionality of the application.
- [Testing](DOCS/TESTING.md) The testing strategy and approach used in the application, including the types of tests implemented, and the focus of the testing.
- [Edge Cases](DOCS/EDGE_CASES.md) The edge cases that were considered and handled in the application, including how the application handles unexpected inputs, errors, and other scenarios that may arise in real-world usage.
- [How File Access Works](DOCS/HOW_FILE_ACCESS_WORKS.md) How file access works, including file sharing, access control, and link generation for viewing and downloading files.
- [How File Delete Works](DOCS/HOW_FILE_DELETE_WORKS.md) How file deletion works, including the process of moving files to trash and permanently deleting them.
- [How Clear Trashed Works](DOCS/HOW_CLEAR_TRASHED_WORKS.md) How the process of clearing trashed files works, including the criteria for determining when to clear trashed files and the process for permanently deleting them.

--- 

# Note

Feedback is welcome and appreciated. If you have any questions, suggestions, or would like to discuss the project further, please feel free to reach out to me. I am always open to constructive feedback and opportunities for collaboration.

---

# License

My project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---