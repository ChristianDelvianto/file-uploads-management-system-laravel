[In development]

# Architecture

The architecture of this application is designed to be modular, scalable, and maintainable. It follows a layered approach, where different components of the application are organized into distinct layers based on their responsibilities. The main layers of the architecture include:

    1. **Models**: This layer contains the data models that represent the entities in the application, such as `User`, `File`, `Plan`, and `Upload`. These models define the structure of the data and the relationships between different entities.
    2. **Controllers**: This layer contains the controllers that handle incoming requests, process the business logic, and return responses to the client. Each controller is responsible for a specific set of functionalities, such as user management, file handling, and subscription management.
    3. **Services**: This layer contains the services that encapsulate the core business logic of the application. Services are responsible for performing operations on the data models, such as creating, updating, and deleting records, as well as handling complex business rules and interactions between different models.
    4. **Repositories**: This layer contains the repositories that abstract the data access logic. Repositories provide a clean interface for interacting with the database, allowing for easier maintenance and separation of concerns between the data access layer and the business logic layer.

Each layer is designed to be independent and loosely coupled, allowing for easier maintenance and scalability in mind. The use of a layered architecture also promotes separation of concerns, making it easier to manage and evolve the application over time. Additionally, the architecture is designed to be flexible, allowing for the integration of new features and components as needed without significant changes to the existing codebase.

---

# Data Models and Relationships

## File

- File `belongsTo` User
- File `hasMany` FileActivity
- File `belongsToMany` User (Shared with other users)

## User

- User `belongsToMany` File (Shared files)
- User `hasMany` UserActivity
- User `hasMany` File
- User `hasMany` Upload (Not publicly accessible, only for internal use)
- User `hasOne` Plan

## Plan

- Plan `belongsToMany` User (With pivot table PlanUser)
- Plan `hasMany` PlanUser

---

# Notes

1. `Upload` UUID is used as the `File` UUID, which means that when a file is uploaded, *it is first stored in the `Upload` model with a unique identifier.* Once the upload is complete and processed, the file information is then transferred to the `File` model, *using the same UUID for consistency and traceability.* This allows us to manage file uploads efficiently while keeping the `File` model focused on storing finalized file data.

This approach has several benefits:
    a. Reduces `File` table size by using same UUID for both `Upload` and `File`, as the `Upload` model can be cleaned up after the file is processed.
    b. Provides a clear separation of concerns, where `Upload` handles the temporary state of file uploads, while `File` manages the permanent records of files in the system.
    c. Enhances security and data integrity by ensuring that only completed uploads are moved to the `File` model.

2. **`Upload` is not publicly accessible, meaning that it is intended for internal use only.** This design choice ensures that the upload process is controlled and secure, preventing unauthorized access to the temporary upload data. By keeping `Upload` internal, we can better manage the lifecycle of file uploads and maintain the integrity of the data throughout the upload process.

3. The `Plan` model is associated with users through a many-to-many relationship, the reason is that we can change the plan without affecting the user data. For example, *when admin change the limit of the plan, it will not affect the user data, and when user change the plan, it will not affect the plan data.* This design allows for greater flexibility in managing user subscriptions and plan offerings without creating tight coupling between the two models.

4. The `PlanUser` pivot table is used to manage the many-to-many relationship between `Plan` and `User`. This table contains foreign keys referencing both the `Plan` and `User` models, allowing us to track which users are subscribed to which plans. Additionally, this pivot table can store extra information about the subscription, such as the subscription date, status, or any other relevant metadata that may be needed for managing user subscriptions effectively.

---

For viewing the trade-offs, please refer to the [Trade-offs](TRADE_OFFS.md) document.
