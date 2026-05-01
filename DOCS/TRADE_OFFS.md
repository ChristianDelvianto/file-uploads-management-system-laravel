[In development]

# Trade-offs in Application Design

While designing this application, I have made several architectural decisions that involve trade-offs between different design principles, such as separation of concerns, performance, and maintainability.

Below are some of the key trade-offs that were considered during the development process:

1. **Using a separate `Upload` model for handling file uploads**: This design allows us to manage the file upload process more efficiently by separating the temporary state of file uploads from the permanent records of files in the system. The `Upload` model can be used to track the progress of file uploads, handle any errors that may occur during the upload process, and clean up temporary data once the upload is complete. To put it simply, please view the pros and cons of this design choice below:
    *Pros:*
        _a._ Improved upload management, better error handling, and cleaner `File` model.
        _b._ Reduces `File` table size by using same UUID for both `Upload` and `File`, as the `Upload` model can be cleaned up after the file is processed.
        _c._ Provides a clear separation of concerns, where `Upload` handles the temporary state of file uploads, while `File` manages the permanent records of files in the system.
        _d._ Enhances security and data integrity by ensuring that only completed uploads are moved to the `File` model.
        _e._ Allows for better tracking and management of the upload process, which can improve user experience and system reliability.
        _f._ `Upload` table can be used for internal analytics and monitoring of upload performance and issues without affecting the `File` model.
    *Cons:*
        _a._ Increases complexity by introducing an additional model and the need to manage the lifecycle of uploads.
        _b._ Requires additional logic to transfer data from `Upload` to `File` once the upload is complete, which can increase development time and potential for bugs if not handled correctly.
        _c._ May require additional database queries to manage the upload process, which can impact performance if not optimized properly.
        _d._ Developers need to ensure that the `Upload` model is properly cleaned up after processing to prevent orphaned records and potential data inconsistencies.
        e. The use of UUIDs for both `Upload` and `File` can lead to larger database indexes, which may have performance implications when querying large datasets. _However, this can be mitigated by using UUIDs for public-facing identifiers while maintaining an auto-incrementing primary key for internal use._

2. **Keeping the `Upload` model internal and not publicly accessible**: This design choice was made to ensure that the upload process is controlled and secure, preventing unauthorized access to temporary upload data. By keeping `Upload` internal, we can better manage the lifecycle of file uploads and maintain the integrity of the data throughout the upload process. (*In other words, this means that we need to implement additional logic to handle uploads within our application, which can increase development time and complexity.*)

3. **Using a many-to-many relationship between `Plan` and `User`**: This design allows for greater flexibility in managing user subscriptions and plan offerings without creating tight coupling between the two models. Additional approach; using `plan_id` in `User` model, but it will create tight coupling between `User` and `Plan` models, which can make it more difficult to manage changes to the subscription plans and user subscriptions over time. By using a many-to-many relationship, we can easily manage the associations between users and plans without affecting the underlying data structure of either model. (*This design choice may require additional database queries to manage the relationships between users and plans, which can impact performance.*)

4. **Storing `used_bytes` in `User` model:** This design choice allows us to quickly access the total storage used by a user without needing to calculate it on the fly, which can help ease server I/O to read all files of a user to calculate the total storage used. Another reason for this design choice is that we can use multiple disk drivers (e.g., local, S3, etc.) in the future, and _calculating the total storage used by a user across different disk drivers can be complex and inefficient and plus *user could be initiating uploads or deleting files at the same time*_. By storing `used_bytes` in the `User` model, we can easily track and manage storage usage regardless of the underlying storage mechanism. *_But, it also introduces potential for data inconsistency if not handled correctly, as any changes to file storage must be reflected in the `used_bytes` field._* So in order to mitigate this risk, we will use cache locking and database transactions to ensure that updates to `used_bytes` are atomic and consistent with file operations. Additionally, we could implement regular checks, and balances to verify the accuracy of `used_bytes` against actual file storage, which can help identify and correct any discrepancies in a timely manner.

5. **Using UUIDs for file identification:** This design choice allows for better security and uniqueness of file identifiers, as UUIDs are not easily guessable and can be generated independently across different systems. However, using UUIDs can also increase the size of the database indexes and may have performance implications when querying large datasets. *_To mitigate this, we use UUID for public-facing identifiers while still maintaining an auto-incrementing primary key for internal use, which allows us to balance the benefits of UUIDs with the performance advantages of traditional integer keys._*

6. **Files are not publicly accessible (even to file owners):** This design choice is made to enhance security and control over file access. By keeping files private, we can ensure that only authorized users can access their files (using proper authentication and authorization mechanisms; `signed URLs`), so **_even the file owner cannot access the file directly without going through the application_**, which can help prevent unauthorized access and potential data breaches. However, this design choice may require additional logic to manage file access and permissions, which can increase development time and complexity. Additionally, it may impact user experience if not implemented properly, as users may expect to be able to access their files directly.

7. **Constant recycle signed URLs to access files (including logged in users to access their own files):** This design choice is made to enhance security by ensuring that access to files is controlled and temporary. _By requiring to obtain signed URLs for file access, we can ensure that only authorized parties can access the files and that the URLs have a limited lifespan, reducing the risk of unauthorized access._ This affect user experience, as users may find it inconvenient to constantly obtain new signed URLs for file access. However, this trade-off is necessary to maintain the security and integrity of the file storage system.

8. **Using a queue to process file uploads and deletions asynchronously:** This design choice allows us to improve performance and user experience by offloading time-consuming tasks to a background process. By using a queue, we can ensure that file uploads and deletions are processed efficiently without blocking the main application thread, which can help improve responsiveness and scalability. However, this design choice may require additional infrastructure and complexity to manage the queue and ensure that tasks are processed reliably, which can increase development time and maintenance overhead.

9. **Sanctum tokens for API authentication:** This design choice allows us to provide secure and flexible authentication for our API endpoints. Sanctum tokens can be used to authenticate users and provide access to protected resources, while also allowing for features like token expiration and revocation. To view the pros and cons, please see below:
    *Pros:*
        _a._ Provides a secure and flexible authentication mechanism for API endpoints.
        _b._ Allows for token expiration and revocation, which can enhance security and control over access to protected resources.
        _c._ Integrates well with Laravel's authentication system, making it easier to implement and manage user authentication.
        _d._ Supports multiple token types (e.g., personal access tokens, API tokens) which can be tailored to different use cases and security requirements.
        _e._ Provides built-in support for token abilities and scopes, allowing for fine-grained control over what actions authenticated users can perform.
    *Cons:*
        _a._ May require additional development time to set up and configure Sanctum tokens properly.
        _b._ Requires careful management of tokens to ensure that they are issued, expired, and revoked correctly, which can increase complexity and potential for bugs if not handled properly.
        _c._ May impact performance if not optimized properly, especially if there are a large number of tokens being issued and validated.
        _d._ To strengthen security, we can implement 2FA (Two-Factor Authentication) in the future, which can add an additional layer of protection for user accounts.

---

# Conclusion

In conclusion, the architectural decisions made in my application involve trade-offs that balance various design principles. By carefully considering the pros and cons of each decision, I aim to create a system that is efficient, maintainable, and secure while also providing a good user experience. It is important to continuously evaluate these trade-offs as the application evolves and to be open to making adjustments as needed to ensure the best possible outcome for the users and the overall system.

---
