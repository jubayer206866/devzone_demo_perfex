# SecureLogin Guard for Perfex CRM - Documentation

## Overview

**SecureLogin Guard** is an advanced IP whitelisting security module for Perfex CRM that restricts login access to authorized IP addresses only. Designed to enhance your CRM's security posture, it provides granular control over who can access your system and from where, helping prevent unauthorized access attempts while maintaining flexibility for legitimate users.

The module operates automatically once configured - no complex settings to manage. Administrators are always exempt from IP restrictions to ensure system accessibility and recovery capabilities.

---

## Features

### 🔒 IP Whitelisting
- Restrict staff login to specific, pre-approved IP addresses
- Supports both IPv4 and IPv6 addresses
- CIDR notation support for network ranges (e.g., `192.168.1.0/24`, `2001:db8::/32`)
- Automatic activation - no manual enable/disable required

### 👥 Multi-Staff Assignment
- Assign a single IP address to multiple staff members simultaneously
- Flexible assignment model - one IP can serve multiple users
- Staff members not assigned to any IP can login from any location

### 🛡️ Admin Protection
- Administrators are always allowed to login from any IP address
- Prevents accidental lockouts of admin accounts
- Ensures system recovery capabilities

### 🔐 Permission-Based Access Control
- Full integration with Perfex CRM's permission system
- Granular control over who can add, edit, or delete IP addresses
- View, Create, Edit, and Delete permissions supported

### 📊 Activity Logging
- All blocked login attempts are logged in Perfex CRM's activity log
- Includes IP address, user details, and timestamp
- Helps with security audits and compliance

### ⚡ Automatic Operation
- No configuration required - works immediately after installation
- If no IPs are added, all staff can login normally
- Once IPs are added, restrictions apply automatically

### 🎯 Flexible Rules
- **No IPs Added:** Normal login for all staff (no restrictions)
- **IPs Added:** Only assigned staff can login from their assigned IPs
- **Unassigned Staff:** Can still login from any IP (not restricted)

---

## Requirements

- **Perfex CRM Version:** 3.1.0 or higher
- **PHP Version:** 7.4 or higher
- **MySQL/MariaDB:** 5.7 or higher
- **Permissions:** Administrator access required for installation

---

## Installation

### Step 1: Download the Module
1. Download the `securelogin_guard.zip` file from your purchase
2. Ensure the file is not corrupted and is the complete module package

### Step 2: Upload to Perfex CRM
1. Log in to your Perfex CRM as an **Administrator**
2. Navigate to **Setup > Modules**
3. Click the **Upload Module** button
4. Select the `securelogin_guard.zip` file
5. Wait for the upload to complete

### Step 3: Install and Activate
1. Once uploaded, locate **SecureLogin Guard** in the modules list
2. Click the **Install** button
3. After successful installation, click **Activate**
4. The module is now ready to use

### Step 4: Verify Installation
1. Check that **SecureLogin Guard** appears in the main navigation menu
2. Click on it to access the management interface
3. You should see an empty table with an "Add IP Address" button

---

## Usage Guide

### Accessing the Module

After installation, you'll find **SecureLogin Guard** in the main navigation menu. Click on it to access the IP whitelist management interface.

### How It Works

The module follows a simple but effective logic:

1. **No IPs Added:** 
   - All staff members can login from any IP address
   - Normal operation - no restrictions applied

2. **IPs Added:**
   - Staff members assigned to specific IPs can only login from those IPs
   - Staff members not assigned to any IP can still login from any location
   - Administrators are always allowed from any IP

3. **Administrators:**
   - Always exempt from IP restrictions
   - Can login from any IP address regardless of whitelist entries
   - This ensures system recovery and prevents accidental lockouts

### Adding IP Addresses

1. Click the **"Add IP Address"** button (requires 'create' permission)
2. Enter the IP address or CIDR range:
   - Single IP: `192.168.1.100`
   - IPv4 CIDR: `192.168.1.0/24`
   - IPv6: `2001:db8::1`
   - IPv6 CIDR: `2001:db8::/32`
3. **Select Staff Members** (required field):
   - Choose one or more staff members from the dropdown
   - Administrators are excluded (they're always allowed)
   - You can select multiple staff for the same IP
4. **Add Description** (optional):
   - Enter a description for reference (e.g., "Office Network", "VPN Range")
5. Click **Submit** to save
6. The IP whitelist is immediately active

**Note:** The "Add current IP" checkbox allows you to quickly populate the IP field with your current IP address.

### Editing IP Addresses

1. Click the **Edit** icon (pencil) next to any IP address (requires 'edit' permission)
2. Modify any of the following:
   - IP address or CIDR range
   - Assigned staff members (add or remove)
   - Description
3. Click **Update** to save changes
4. Changes take effect immediately

### Managing IP Status

1. Use the **toggle switch** in the Status column to enable/disable individual IP addresses
2. **Active (Green):** IP is checked during login attempts
3. **Inactive (Gray):** IP is ignored during login attempts
4. Requires 'edit' permission to change status

### Deleting IP Addresses

1. Click the **Delete** icon (trash) next to any IP address (requires 'delete' permission)
2. Confirm the deletion
3. The IP is immediately removed from the whitelist
4. Staff assigned to this IP will no longer be restricted (can login from any IP)

### Viewing the Table

The management table displays:
- **IP Address:** The whitelisted IP or CIDR range
- **Assigned Staff:** Staff members assigned to this IP (admin view only)
- **Description:** Optional description
- **Status:** Active/Inactive toggle
- **Date Created:** When the IP was added
- **Actions:** Edit and Delete buttons

---

## Permissions

The module integrates with Perfex CRM's permission system. Configure permissions at **Setup > Staff Permissions > SecureLogin Guard**.

### Available Permissions

- **View:** Required to access the module and view the IP whitelist
- **Create:** Required to add new IP addresses. Users with this permission can assign IPs to any staff member
- **Edit:** Required to edit IP addresses or toggle their status (active/inactive)
- **Delete:** Required to remove IP addresses from the whitelist

### Permission-Based UI

- **Add Button:** Only visible to users with 'create' permission
- **Edit Icon:** Only visible to users with 'edit' permission
- **Delete Icon:** Only visible to users with 'delete' permission
- **Status Toggle:** Only functional for users with 'edit' permission

---

## Technical Details

### Database Tables

The module creates two database tables:

1. **`tblsecurelogin_guard_whitelist`**
   - Stores IP addresses, descriptions, and status
   - Fields: `id`, `ip_address`, `description`, `is_active`, `date_created`, `date_modified`, `created_by`

2. **`tblsecurelogin_guard_whitelist_staffs`**
   - Stores staff assignments to IP addresses (many-to-many relationship)
   - Fields: `id`, `whitelist_id`, `staff_id`, `date_created`
   - Foreign keys ensure data integrity

### Login Hook

The module hooks into Perfex CRM's authentication system at the `before_login` hook point. It checks:
1. If the user is an administrator → Allow login
2. If no IPs are in the whitelist → Allow login (normal operation)
3. If IPs exist → Check if user is assigned to the current IP
4. If assigned → Allow login
5. If not assigned → Block login and log the attempt

### CIDR Support

The module uses PHP's `ip_in_range()` function to support CIDR notation:
- IPv4: `192.168.1.0/24` (allows 192.168.1.0 to 192.168.1.255)
- IPv6: `2001:db8::/32` (allows the specified IPv6 range)

---

## Troubleshooting

### Issue: Cannot login after adding IP

**Solution:**
1. Log in as an administrator (admins are always allowed)
2. Check if your IP is correctly added and active
3. Verify you're assigned to the IP address
4. Check if the IP format is correct (no typos)

### Issue: Staff member cannot see the module

**Solution:**
1. Go to **Setup > Staff Permissions**
2. Find the staff member
3. Ensure "View" permission is granted for SecureLogin Guard

### Issue: Cannot add IP addresses

**Solution:**
1. Verify you have 'create' permission
2. Check that staff members are available (non-admin staff)
3. Ensure the IP address format is valid

### Issue: IP address not working

**Solution:**
1. Verify the IP address is correct (check for typos)
2. Ensure the IP is set to "Active" status
3. Check if you're assigned to the IP address
4. For CIDR ranges, verify the notation is correct

### Issue: Module not appearing in menu

**Solution:**
1. Verify the module is installed and activated
2. Clear Perfex CRM cache
3. Check that you have 'view' permission
4. Try logging out and logging back in

---

## Best Practices

1. **Always Test First:**
   - Add your own IP address first
   - Test login before adding other staff IPs
   - Keep admin access unrestricted

2. **Use Descriptions:**
   - Add meaningful descriptions to IP addresses
   - Helps identify which IP belongs to which location/network

3. **CIDR Ranges:**
   - Use CIDR ranges for office networks instead of individual IPs
   - Reduces maintenance and covers dynamic IP assignments

4. **Regular Audits:**
   - Periodically review the IP whitelist
   - Remove unused or outdated IP addresses
   - Update staff assignments as needed

5. **Backup Before Changes:**
   - Always backup your database before major changes
   - Keep a record of important IP addresses

6. **Documentation:**
   - Document which IPs belong to which locations
   - Keep track of staff assignments
   - Maintain a list of critical IP addresses

---

## Uninstallation

To uninstall the module:

1. Go to **Setup > Modules**
2. Find **SecureLogin Guard**
3. Click **Deactivate** (if active)
4. Click **Uninstall**
5. Confirm the uninstallation

**Warning:** Uninstalling will remove all IP whitelist entries and staff assignments. This action cannot be undone. Make sure to backup your data if needed.

---

## Support

### Documentation
This documentation file is included with the module. For the latest version, please check the module package.

### Technical Support
For technical support, feature requests, or bug reports, please contact us through:
- **Email:** [Your Support Email]
- **Support Portal:** [Your Support URL]
- **Documentation:** [Your Documentation URL]

### Updates
The module includes free lifetime updates. Check for updates through:
- **Setup > Modules > SecureLogin Guard > Check for Updates**

---

## Change Log

### Version 1.0.0 (Initial Release)
- Initial release
- IP whitelisting functionality
- Multi-staff assignment
- CIDR support (IPv4 and IPv6)
- Permission-based access control
- Automatic operation
- Activity logging
- Admin exemption
- Status toggle (active/inactive)

---

## License

This module is licensed under the same license as Perfex CRM. Please refer to your Perfex CRM license agreement for details.

---

## Credits

**SecureLogin Guard for Perfex CRM**
- Developed for Perfex CRM
- Compatible with Perfex CRM 3.1.0+

---

**Thank you for using SecureLogin Guard!**

For questions or support, please refer to the Support section above.






