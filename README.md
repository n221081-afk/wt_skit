# PHP Firebase Login System

This sample app demonstrates how to use Firebase Authentication (REST API) with PHP (procedural) and Firestore (for user metadata). It is designed to run on a local Apache/PHP server such as XAMPP.

Files added:
- public/login.php
- public/auth.php
- public/dashboard.php
- public/logout.php
- public/firebase_config.php
- public/css/style.css
- firebase.json (hosting reference)

Important: Firebase Hosting does NOT run PHP. Use XAMPP / Apache to serve the `public/` folder locally.

Quick steps:
1. Create Firebase project.
2. **Enable Email/Password sign-in** in Authentication (see note below).
3. Create a Firestore database in production or test mode.
4. Get your Web API Key (project settings) and set it in `public/firebase_config.php`.
5. Place this project in your XAMPP `htdocs` and visit `http://localhost/CHANDU/public/login.php`.
6. Test login using a Firebase Auth user (create via Firebase console or client app).
   - You can create users with specific email/password credentials in the Firebase console under **Authentication → Users**. Click **Add user** and enter the email and desired password.

> **Tip:** if you see `OPERATION_NOT_ALLOWED` when registering, that means the Email/Password provider is disabled. Visit **Authentication → Sign-in method** in the console and toggle the Email/Password provider on, then save the changes.
   - Alternatively, use the provided `register.php` page to let users register themselves with a chosen password. See the section below.

See `public/firebase_config.php` comments for where to add your API key.

---

## Registering users with specific passwords

This app’s login flow uses Firebase Authentication, which manages passwords for you. To use *particular* passwords:

1. **Via Console** – open Firebase console > Authentication > Users > Add user. Provide the `email` and `password` you want. The user will then be able to sign in with that email/password combination.
2. **Via Web page** – open `register.php` in your browser (e.g. `http://localhost/CHANDU/public/register.php`). It lets anyone create an account by entering email/password/confirm password. If you see a "Registration failed" message, the form now displays the exact Firebase error (e.g. `EMAIL_EXISTS` if the address is already registered) along with the raw response.  You can also open the browser developer tools and look at the network request to see status codes and body of the call.

Both approaches store the credentials securely in Firebase; you never handle raw passwords yourself. Once a user is created, they can log in through `login.php` with the exact email and password you gave them.

---

## Firestore document naming note

The dashboard loads additional fields (student ID, marks, etc.) by fetching a Firestore document using the user's **UID** (the `localId` returned by Firebase Auth). The document name must match that UID. If you manually create a document using the email address as the ID, the dashboard will not find it, which is why your marks were not appearing.

To fix existing entries:

1. Open the Firebase console → Firestore Database → `users` collection.
2. Find the document keyed by the email address and copy its fields.
3. Determine the user's UID (look under Authentication → Users or log in and check `$_SESSION['user_uid']`).
4. Create a new document named with that UID and paste in the fields, including `studentId` and `marks`.

From that point on, logging in with that user will display the marks as expected.

# wt_skit
# wt_skit
