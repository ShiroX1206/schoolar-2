SCHOOlar Frontend Update

What was added:
- Admin dashboard JavaScript
- Admin scholarship add, edit, delete, search, filter, and status functions
- User dashboard scholarship display
- Scholarship search and filters
- Nearby scholarship filtering
- Scholarship details page
- Save and recently viewed scholarships
- Frontend eligibility checker
- User profile editing with temporary browser storage
- Notifications display
- Settings Help & Support interaction
- Shared frontend-data.js file

Important:
This version is FRONTEND ONLY.
It uses browser localStorage as temporary data storage so the pages can work before PHP/MySQL is connected.

The temporary scholarship data is stored under:
- schoolar_scholarships
- schoolar_saved_scholarships
- schoolar_recent_scholarships
- schoolar_profile
- schoolar_notifications

When the PHP/MySQL backend is ready, these localStorage functions should be replaced with PHP API requests.

How to use:
1. Copy the contents of this package into your existing SCHOOlar project.
2. Keep your existing images and icons.
3. Start XAMPP Apache if you are testing through /SCHOOlar/ paths.
4. Open the Admin Dashboard or User pages.
5. Add/edit/delete scholarships from the Admin Manage Scholarships page.
6. The changes will appear in User pages in the same browser because they use localStorage.

No database changes are included in this frontend update.
