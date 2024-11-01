=== Migrate Xcart to WooCommerce ===
Contributors: V Group Inc.
Tags: X-Cart, WooCommerce, X-Cart Migration, X-Cart to WooCommerce Migration
Requires at least: 4.X
Tested up to: 4.9
Stable tag: 1.0
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The plugin is developed to automate data migration from X-Cart to WooCoomerce.

== Description ==

The X-Cart to Woocommerce migration plugin for WordPress developed by V Group automatically migrates the data from X-Cart to WooCommerce store. The Plugin provides the ability to transfer the data from X-Cart store with ease and a high level of accuracy. The use of the plug-in doesn’t require any programming skills.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/X-Cart Migration` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to X-Cart Migration>Settings from left navigation bar in admin panel.
4. Submit X-Cart database credentials to connect to X-Cart database.

== Frequently Asked Questions ==

= What is Database Host? =

The host where the X-Cart site database is currently available.

= What is Database Name? =

Name of the database containing X-Cart site records.

= What is Database User? =

Username to login to X-Cart database.

= What is Database Password? =

Password to login to X-Cart database.

= What is Web Directory? =

Web directory is the store-front URL of your X-Cart site, necessary for migration of images. If X-Cart site url is https://www.example.com then the web directory will be https://www.example.com/
Do not forget to add a Forward Slash in the end of web directory.

== Screenshots ==

1. /inc/screenshot/screenshot-1.png
2. /inc/screenshot/screenshot-2.png
3. /inc/screenshot/screenshot-3.png

== Changelog ==

= 1.0 =
* Initial version.

== Installation ==

= Automatic Installation (The Easiest Way) =
1. From the WordPress admin backend, navigate to Plugins → Add New.
2. Under Search, type ‘X-Cart to WooCommerce Migration Plug-in’ and click Search.
3. In the search results find the ‘X-Cart to WooCommerce Migration Plug-in and click Install now to install it.
4. When the plug-in is installed click Activate Plug-in.

= Upload the plug-in zip archive from WordPress backend =
1. Download X-Cart to WooCommerce Migration Plug-in from WordPress Plug-in page as a zip file.
2. From the WordPress admin backend, navigate to Plugins → Add new.
3. Click Upload and select the downloaded zip file.
4. Click Install.

== Features ==

= Step by Step Data Migration: =
 
This feature provides the option to select all or desired data to be migrated to WooCommerce from X-Cart, Following data can be migrated:

1. Product Categories
2. Products including variants, images and all other attributes
3. X-Cart Reviews
4. Customers
5. Orders

The above listed data type are migrated one at a time. Help notes appear when the data type is migrated for the first time. The plugin displays a date/time stamp when the data was last migrated.
 
= Following data can be migrated from X-Cart to WooCommerce: =
 
* Product Categories
Name, Description, Image, Sub categories (If any).

* Products
Attributes (Name, Values)
Name, SKU, Short Description, Full Description,
Product Status, Tax Class, Price, Sale Price, URL, Weight,  Height, 
Variants (SKU, Weight, Attributes,  Images, Price, Special Price
Gallery Images, Quantity.

* Customers
First Name, Last Name, Email, 
Billing Address (First Name, Last Name, Company, Address 1, Address 2, Country, State, City, Zip Code, Telephone),
Shipping Address (First Name, Last Name, Company, Address 1, Address 2, Country, State, City, Zip Code).

* Orders
ID, Order Date, Order Status, Custom Order Status,
Order Products (Name, SKU, Qty, Cost),Customer Note, Private Note,
Product Price, Quantity, Subtotal Price, Shipping Price, Total Price,
Order Comments, Order Status History, Customer Name, Email, Billing Address (First Name, Last Name, Company, Address 1, Address 2, Country, State, City, Zip Code, Telephone), 
Shipping Address (First Name, Last Name, Company, Address 1, Address 2, Country, State, City, Zip Code).

* Reviews 
Ratings, Reviewer Name and Reviews.
