SCHOOlar - Phase 3 Backend

WHAT CHANGED
- Added a NEW PHP backend under /api.
- The old _old_backend_unused folder is not used.
- Login and registration now use PHP sessions and MySQL.
- Admin scholarship Add, Edit, Delete, and status changes use MySQL.
- User scholarship search, details, saved scholarships, recently viewed scholarships,
  eligibility checking, profile, and notifications now use PHP/MySQL.
- Schools and courses are read from the existing database tables.
- The frontend no longer uses localStorage for scholarship/profile/user interaction data.

DATABASE
The backend expects the database name:
    schoolar_db

It expects the existing tables from the SCHOOlar schema.
Do not import the full schema if your database already exists and contains your school/course data.

OPTIONAL SAMPLE SCHOLARSHIPS
If the scholarships table is empty, you can run:
    database/phase3_seed_scholarships.sql

This adds the four sample scholarship records used by the frontend.
It does not recreate schools or courses.

XAMPP
1. Start Apache.
2. Start MySQL.
3. Put the SCHOOlar folder inside C:\xampp\htdocs\
   or keep your existing SCHOOlar symlink setup.
4. Open:
   http://localhost/SCHOOlar/

DEMO ACCOUNTS FROM THE EXISTING DATABASE SEED
Admin:
    admin@schoolar.local
    admin123

User:
    student@schoolar.local
    User123!

Change demo passwords before using the project for real deployment.

IMPORTANT
- The backend is written using beginner-friendly PHP and MySQLi.
- No framework is used.
- _old_backend_unused is kept only as an old reference and is not connected.
