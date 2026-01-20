# SecureLogin Guard for Perfex CRM

**Advanced IP Whitelisting Security Module**

---

## Overview

**SecureLogin Guard** is a powerful security enhancement module for Perfex CRM that restricts login access to authorized IP addresses only. Designed to strengthen your CRM's security posture, it provides granular control over who can access your system and from where, helping prevent unauthorized access attempts while maintaining flexibility for legitimate users.

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

1. Log in to your Perfex CRM as an **Administrator**
2. Navigate to **Setup > Modules**
3. Click **Upload Module** and select the `securelogin_guard.zip` file
4. Click **Install**, then click **Activate** once it appears in the list
5. Access the module from the main navigation menu

---

## Quick Start

1. **Access the Module:** Click **SecureLogin Guard** in the navigation menu
2. **Add Your IP:** Click **"Add IP Address"** and enter your current IP
3. **Assign Staff:** Select staff members who should be allowed from this IP
4. **Save:** Click **Submit** - the whitelist is immediately active

**Note:** Administrators are always allowed to login from any IP, so you won't get locked out.

---

## How It Works

- **No IPs Added:** All staff can login from any IP (normal operation)
- **IPs Added:** Only assigned staff can login from their assigned IPs
- **Administrators:** Always allowed from any IP address
- **Unassigned Staff:** Can still login from any IP (not restricted)

---

## Usage

### Adding IP Addresses
1. Click **"Add IP Address"** (requires 'create' permission)
2. Enter IP address or CIDR range (supports IPv4 and IPv6)
3. Select one or more staff members (required)
4. Add optional description
5. Click **Submit**

### Editing IP Addresses
1. Click the **Edit** icon next to any IP (requires 'edit' permission)
2. Modify IP, staff assignments, or description
3. Click **Update**

### Managing Status
- Use the toggle switch to enable/disable individual IPs
- Active IPs are checked during login
- Inactive IPs are ignored

### Permissions
Configure at **Setup > Staff Permissions > SecureLogin Guard**:
- **View:** Access the module
- **Create:** Add new IP addresses
- **Edit:** Edit IPs or toggle status
- **Delete:** Remove IP addresses

---

## Support

- **Documentation:** See `DOCUMENTATION.md` for detailed guide
- **Support Email:** [Your Support Email]
- **Support Portal:** [Your Support URL]

---

## Change Log

### Version 1.0.0
- Initial Release
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

This module is licensed under the same license as Perfex CRM.

---

**Thank you for using SecureLogin Guard!**






