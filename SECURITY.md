# Security Policy

## Supported Versions

Security updates are provided for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 4.1.x   | :white_check_mark: |
| 4.0.x   | :white_check_mark: |
| 3.x     | :x:                |
| < 3.0   | :x:                |

## Reporting a Vulnerability

We take the security of Valkyrie Memory System seriously. If you discover a security vulnerability, please follow these steps:

### 1. Do Not Publicly Disclose

**Please do not:**
- Open a public GitHub issue
- Post in discussions or forums
- Share on social media
- Disclose to third parties

### 2. Report Privately

**Send reports to:**
- Email: security@valkyriehq.com *(placeholder - update with real email)*
- GitHub Security Advisory: [Create Advisory](https://github.com/yourusername/valkyrie-memory-system/security/advisories/new)

### 3. Include Details

**Your report should include:**
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)
- Your contact information

### 4. Response Timeline

- **Within 24 hours**: Acknowledgment of your report
- **Within 72 hours**: Initial assessment and severity classification
- **Within 7 days**: Detailed response and fix timeline
- **As needed**: Updates on progress

### 5. Coordinated Disclosure

- We'll work with you on a disclosure timeline
- Credit will be given (unless you prefer anonymity)
- We aim to fix critical issues within 30 days
- Public disclosure after fix is deployed

## Security Best Practices

### For Administrators

#### Access Control
```bash
# Restrict file permissions
sudo chown -R valkyrie:valkyrie /opt/valkyrie/memory
sudo chmod -R 750 /opt/valkyrie/memory

# Web server should not have write access
sudo chown www-data:www-data /var/www/valkyrie
sudo chmod -R 755 /var/www/valkyrie
```

#### Network Security
```nginx
# Use HTTPS only
server {
    listen 443 ssl http2;
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
}

# Restrict API access
location /api.php {
    # Rate limiting
    limit_req zone=api burst=10;
    
    # IP whitelist (optional)
    allow 192.168.1.0/24;
    deny all;
}
```

#### Regular Updates
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update PHP
sudo apt install php7.4-fpm

# Update Valkyrie
git pull origin main
sudo bash upgrade.sh
```

### For Developers

#### Input Validation
```php
// Always validate and sanitize inputs
function createProject($projectName) {
    // Sanitize project name
    $projectName = preg_replace('/[^a-z0-9_-]/i', '_', $projectName);
    
    // Validate length
    if (strlen($projectName) < 1 || strlen($projectName) > 64) {
        throw new InvalidArgumentException('Invalid project name');
    }
    
    return $projectName;
}
```

#### SQL Injection Prevention
```php
// Use prepared statements (if adding database)
$stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
$stmt->execute([$projectId]);
```

#### XSS Prevention
```php
// Always escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

#### File Path Traversal
```php
// Validate file paths
function getProjectPath($projectName) {
    $basePath = '/opt/valkyrie/memory/projects';
    $fullPath = realpath($basePath . '/' . $projectName);
    
    // Ensure path is within base directory
    if (strpos($fullPath, $basePath) !== 0) {
        throw new SecurityException('Invalid path');
    }
    
    return $fullPath;
}
```

### For Users

#### Strong Passwords
If authentication is enabled:
- Use passwords 12+ characters
- Include uppercase, lowercase, numbers, symbols
- Use a password manager
- Enable 2FA if available

#### HTTPS Only
- Always access via HTTPS
- Verify SSL certificate
- Don't ignore browser warnings

#### Regular Backups
```bash
# Backup your memory data
tar -czf valkyrie_backup_$(date +%Y%m%d).tar.gz /opt/valkyrie/memory
```

#### Access Logging
```bash
# Monitor access logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

## Known Security Considerations

### File System Access

**Issue**: Valkyrie stores data as plain text markdown files  
**Risk**: Low - Files are protected by OS permissions  
**Mitigation**: 
- Set restrictive file permissions (750)
- Keep memory directory outside web root
- Regular backups with encryption

### No Built-in Authentication

**Issue**: V4.1 has no user authentication  
**Risk**: Medium - Assumes trusted network  
**Mitigation**:
- Use network-level access control (firewall, VPN)
- Nginx IP whitelisting
- Run on private network
- Authentication coming in V5.0

### API Rate Limiting

**Issue**: API endpoints could be abused  
**Risk**: Low - Local deployment typical  
**Mitigation**:
- Nginx rate limiting configured
- Monitor unusual access patterns
- Block suspicious IPs

### Cross-Site Scripting (XSS)

**Issue**: User-generated content could contain scripts  
**Risk**: Low - Single-user system by design  
**Mitigation**:
- All output is escaped
- Content-Security-Policy headers
- Regular security audits

## Security Checklist

### Installation
- [ ] Files have correct permissions
- [ ] Web server user has minimal privileges
- [ ] Memory directory outside web root
- [ ] HTTPS configured
- [ ] Firewall rules set
- [ ] Regular backups scheduled

### Ongoing
- [ ] System packages updated monthly
- [ ] Access logs reviewed weekly
- [ ] Backups tested quarterly
- [ ] Security advisories monitored
- [ ] Valkyrie updated to latest version

## Vulnerability Disclosure History

### None Yet
No security vulnerabilities have been reported to date.

When vulnerabilities are fixed, they will be documented here with:
- CVE identifier (if applicable)
- Severity rating
- Affected versions
- Fix version
- Credit to reporter

## Security Contacts

- **Primary**: security@valkyriehq.com *(placeholder)*
- **GitHub Security**: [Security Advisories](https://github.com/yourusername/valkyrie-memory-system/security/advisories)
- **PGP Key**: Available on request

## Hall of Fame

Security researchers who responsibly disclose vulnerabilities will be recognized here (with permission):

*No entries yet*

---

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CWE/SANS Top 25](https://cwe.mitre.org/top25/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)

---

Thank you for helping keep Valkyrie Memory System secure! 🔒
