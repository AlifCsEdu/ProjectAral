-- Update the role of the admin user (assuming the email is admin@arals.com)
UPDATE users SET role = 'admin' WHERE email = 'admin@arals.com';
