# Google Cloud Compute Engine Deployment Tutorial (LAMP Stack)

This tutorial will guide you step-by-step on how to deploy your `STDC Program Registration System` application to a Google Cloud Compute Engine Virtual Machine running Ubuntu/Debian, with Apache, MySQL, and PHP installed directly on the VM.

---

## Phase 1: Create the Virtual Machine (VM)
1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Navigate to **Compute Engine > VM instances**.
3. Click **Create Instance**.
4. **Name**: `idaftar-production-vm`
5. **Region/Zone**: Choose a region close to your users (e.g., `asia-southeast1` for Singapore/Malaysia).
6. **Machine configuration**: For testing, an `e2-micro` or `e2-medium` is sufficient.
7. **Boot disk**: Choose **Ubuntu 22.04 LTS** or **Debian 12**.
8. **Firewall**: 
   - [x] Allow HTTP traffic
   - [x] Allow HTTPS traffic
9. Click **Create**.

---

## Phase 2: Install Apache, MySQL, and PHP (LAMP Stack)
Once the VM is running, click the **SSH** button next to your instance in the Google Cloud Console to open a terminal in your browser.

Run the following commands to update the package list and install the required software:

```bash
# 1. Update package list
sudo apt update && sudo apt upgrade -y

# 2. Install Apache Web Server
sudo apt install apache2 -y

# 3. Install MySQL Server
sudo apt install mysql-server -y

# 4. Secure MySQL Installation (Follow the prompts to set a root password)
sudo mysql_secure_installation

# 5. Install PHP and required extensions (PDO, MySQL, cURL)
sudo apt install php libapache2-mod-php php-mysql php-curl php-json php-xml php-mbstring -y

# 6. Restart Apache to apply changes
sudo systemctl restart apache2
```

---

## Phase 3: Configure the Database
You need to create a database and a user for your application, then import your `setup.sql` file.

1. Log into MySQL as the root user:
```bash
sudo mysql -u root -p
```

2. Inside the MySQL prompt, run the following commands:
```sql
-- Create the database
CREATE DATABASE stdc_registration_staging;

-- Create a specific user for your app (Replace 'your_password' with a strong password)
CREATE USER 'stdc_admin'@'localhost' IDENTIFIED BY 'your_password';

-- Grant privileges to the user
GRANT ALL PRIVILEGES ON stdc_registration_staging.* TO 'stdc_admin'@'localhost';

-- Apply changes and exit
FLUSH PRIVILEGES;
EXIT;
```

---

## Phase 4: Upload Your Application Files
You can transfer your files from your local Mac to the VM using the `gcloud` CLI, Git, or simply using the Upload button in the Google Cloud SSH browser terminal.

### Option A: Using the Browser SSH "Upload file" Button (Simplest Method)
If you opened the SSH terminal directly in your web browser from the Google Cloud Console:
1. Compress your `BASE_URL` folder into a zip file on your Mac (e.g., `iDaftar.zip`).
2. In the top right corner of the SSH browser window, click the **Upload file** button (an icon with an up-arrow) and select `iDaftar.zip`.
3. The file will be uploaded to your home directory (`~`).
4. Run the following commands to unzip and move it to Apache:
```bash
# Install unzip if you don't have it
sudo apt install unzip -y

# Unzip the file
unzip iDaftar.zip -d iDaftar

# Move the contents to the web directory
sudo cp -r iDaftar/* /var/www/html/
```

### Option B: Using Git (Recommended for Teams)
If your project is on GitHub:
```bash
# On your VM, navigate to the web directory
cd /var/www/html/

# Clone your repository (You may need to clear the directory first)
sudo rm index.html
sudo git clone https://github.com/darrenfedrickson/BASE_URL.git .
```

### Option C: Using `gcloud compute scp`
If you want to transfer directly from your local XAMPP folder, run this command **on your local Mac terminal**:
```bash
gcloud compute scp --recurse /Applications/XAMPP/xamppfiles/htdocs/BASE_URL/* your-username@idaftar-production-vm:/tmp/
```
Then, back in your **Google Cloud SSH terminal**, move the files to Apache:
```bash
sudo cp -r /tmp/* /var/www/html/
```

---

## Phase 5: Import the Database Schema
Now that your files are on the server, import the `setup.sql` file into your new MySQL database:

```bash
# Navigate to where your setup.sql is located
cd /var/www/html/

# Import the SQL file into the database
mysql -u stdc_admin -p stdc_registration_staging < setup.sql
```

---

## Phase 6: Update Configuration & Permissions

### 1. Update Database Credentials
You must update `config/database.php` to use the new MySQL credentials you created in Phase 3.
```bash
sudo nano /var/www/html/config/database.php
```
Change the connection details to:
```php
$db_user = 'stdc_admin';
$db_pass = 'your_password';
$db_name = 'stdc_registration_staging';
```
*(Press `CTRL+O`, `Enter`, and `CTRL+X` to save and exit).*

### 2. Set File Permissions
Apache needs permission to read your files and write to the `uploads` directory.
```bash
# Change ownership to the Apache user (www-data)
sudo chown -R www-data:www-data /var/www/html/

# Set correct folder and file permissions
sudo find /var/www/html/ -type d -exec chmod 755 {} \;
sudo find /var/www/html/ -type f -exec chmod 644 {} \;

# Make sure the uploads folder is writable
sudo chmod -R 775 /var/www/html/uploads/
```

---

## Phase 7: Access Your Website
1. Go back to the **Compute Engine > VM instances** page in Google Cloud.
2. Find the **External IP** address of your `idaftar-production-vm`.
3. Open your web browser and navigate to `http://YOUR_EXTERNAL_IP`.

Your `STDC Program Registration System` platform should now be live on Google Cloud!

---

## Phase 8: Updating an Existing Deployment

If you have already deployed the application and just want to **update your files** and **replace the database**, follow this streamlined process.

### 1. Update the Database (Drop & Re-import)
**WARNING**: This will delete all existing data on the production server. Be sure this is what you want!

1. Export your latest database from your local Mac to an SQL file (e.g., `latest_backup.sql`).
2. Transfer it to the VM:
   - **Using Browser SSH:** Click the **Upload file** button in the SSH browser window and select `latest_backup.sql`.
   - **Using gcloud:** Run `gcloud compute scp /path/to/latest_backup.sql your-username@idaftar-production-vm:~/` on your Mac.
3. SSH into your VM and import the new SQL file from your home directory, overwriting the old data:
```bash
# Log into MySQL
mysql -u stdc_admin -p

# Inside MySQL prompt: Drop and recreate the database
DROP DATABASE stdc_registration_staging;
CREATE DATABASE stdc_registration_staging;
EXIT;

# Import the new SQL dump (assuming it uploaded to your home directory)
mysql -u stdc_admin -p stdc_registration_staging < ~/latest_backup.sql
```

### 2. Update Application Files
To replace your existing PHP files with the latest versions from your local Mac:

**Using the Browser SSH "Upload file" Button:**
1. Zip your updated `BASE_URL` folder locally into `update.zip`.
2. Click the **Upload file** button in the SSH browser window and upload `update.zip`.
3. Unzip and copy over the files:
```bash
unzip -o update.zip -d update
sudo cp -r update/* /var/www/html/
```

**Using gcloud scp (Direct File Transfer):**
Run this on your local Mac terminal to overwrite the old files on the VM:
```bash
gcloud compute scp --recurse /Applications/XAMPP/xamppfiles/htdocs/BASE_URL/* your-username@idaftar-production-vm:/var/www/html/
```
*(Note: If you get a permission denied error, `scp` it to `~/` first, then SSH into the VM and use `sudo cp -r ~/* /var/www/html/`)*

**Using Git (If connected to a repo):**
SSH into your VM and run:
```bash
cd /var/www/html/
sudo git pull origin main
```

### 3. Reset Permissions (Important!)
Whenever you copy new files, you must ensure Apache still has permission to read them:
```bash
sudo chown -R www-data:www-data /var/www/html/
sudo find /var/www/html/ -type d -exec chmod 755 {} \;
sudo find /var/www/html/ -type f -exec chmod 644 {} \;
```
