# Setting Up a Staging Environment on Google Cloud

A Staging environment is an exact replica of your Production environment. It allows you to test new features safely on the actual Linux server before deploying them to the live website.

Since you have already presented the project, setting this up is a massive step forward in building a truly professional, enterprise-grade architecture!

---

## Step 1: Create the Staging Database
We need a completely separate database so your tests don't accidentally delete real student data.

1. Open your **Cloud Shell Terminal** and SSH into your VM:
   ```bash
   gcloud compute ssh "stdc-web-server" --zone "asia-southeast1-a" --project "project-8273976d-fcb1-4538-a8c"
   ```
2. Log into MySQL as the root user:
   ```bash
   sudo mysql -u root -p
   ```
3. Create the new staging database and grant your admin user access to it:
   ```sql
   CREATE DATABASE stdc_staging;
   GRANT ALL PRIVILEGES ON stdc_staging.* TO 'stdc_admin'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

---

## Step 2: Set Up the Staging Directory
We will create a second folder on the server to hold the staging code.

1. Navigate to the web root and clone your GitHub repository into a new folder named `staging`:
   ```bash
   cd /var/www/
   sudo git clone https://github.com/darrenfedrickson/BASE_URL.git staging
   ```
2. Give Apache ownership of the new folder so it can read the files:
   ```bash
   sudo chown -R www-data:www-data /var/www/staging
   ```

---

## Step 3: Configure the Staging `database.php`
Because the staging folder uses the `stdc_staging` database, we need to create a specific config file for it.

1. Navigate into the staging config folder:
   ```bash
   cd /var/www/staging/config/
   ```
2. Create the `database.php` file:
   ```bash
   sudo nano database.php
   ```
3. Paste in your connection code, making sure to use the **staging database name** and your cloud password:
   ```php
   <?php
   $host = 'localhost';
   $db   = 'stdc_staging';
   $user = 'stdc_admin';
   $pass = 'YOUR_CLOUD_PASSWORD';
   $charset = 'utf8mb4';

   $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
   $options = [
       PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
       PDO::ATTR_EMULATE_PREPARES   => false,
   ];

   try {
       $pdo = new PDO($dsn, $user, $pass, $options);
   } catch (\PDOException $e) {
       die("Database connection failed: " . $e->getMessage());
   }
   ?>
   ```
4. Save and exit (`CTRL+O`, `Enter`, `CTRL+X`). Don't forget to also upload your `application_default_credentials.json` here!

---

## Step 4: Configure Apache Virtual Hosts (Routing)
Currently, Apache sends all traffic to `/var/www/html`. We need to tell it to route traffic to `/var/www/staging` if someone uses a specific port (like port 8080).

1. Open the Apache ports configuration to tell it to listen on port 8080:
   ```bash
   sudo nano /etc/apache2/ports.conf
   ```
   Add this line below `Listen 80`:
   ```text
   Listen 8080
   ```
   Save and exit.

2. Create a new Virtual Host configuration for the staging site:
   ```bash
   sudo nano /etc/apache2/sites-available/staging.conf
   ```
3. Paste the following configuration:
   ```apache
   <VirtualHost *:8080>
       ServerAdmin webmaster@localhost
       DocumentRoot /var/www/staging

       <Directory /var/www/staging>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/staging_error.log
       CustomLog ${APACHE_LOG_DIR}/staging_access.log combined
   </VirtualHost>
   ```
4. Save and exit.

---

## Step 5: Enable and Restart!
1. Enable the new staging site:
   ```bash
   sudo a2ensite staging.conf
   ```
2. Restart Apache to apply all changes:
   ```bash
   sudo systemctl restart apache2
   ```

### Final Step: Google Cloud Firewall
Since staging is running on port `8080`, you need to go to your **Google Cloud Console > VPC Network > Firewall** and create a rule that allows TCP traffic on port `8080`.

Once that is done, you can access your production site at:
`http://YOUR_EXTERNAL_IP/`

And you can access your secret staging site at:
`http://YOUR_EXTERNAL_IP:8080/`
