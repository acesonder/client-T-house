# Deployment Checklist

Use this checklist when deploying the Shelter Management System to a production environment.

## Pre-Deployment

### 1. Environment Setup
- [ ] Production server meets minimum requirements (PHP 7.4+, MySQL 5.7+, 2GB RAM)
- [ ] SSL certificate obtained and installed
- [ ] Domain name configured and pointing to server
- [ ] Web server (Apache/Nginx) installed and configured
- [ ] PHP extensions installed (PDO, PDO_MySQL, JSON, Session, MBString, OpenSSL, FileInfo)
- [ ] MySQL server installed and secured
- [ ] Firewall configured (ports 80, 443 open)

### 2. Code Deployment
- [ ] Clone repository to production server
- [ ] Set correct file ownership (web server user)
- [ ] Create logs directory with write permissions
- [ ] Create assets/uploads directory with write permissions
- [ ] Verify .htaccess is in place
- [ ] Remove any development files (.git, tests, etc.)

### 3. Database Setup
- [ ] Create production database
- [ ] Create database user with limited privileges
- [ ] Import schema.sql
- [ ] Verify all tables created successfully
- [ ] Create initial admin user with STRONG password
- [ ] Test database connection

### 4. Configuration
- [ ] Copy config/config.php.example to config/config.php
- [ ] Update database credentials
- [ ] Set APP_ENV to 'production'
- [ ] Set APP_URL to production URL
- [ ] Update APP_NAME to organization name
- [ ] Configure session timeout appropriately
- [ ] Set DISPLAY_ERRORS to false
- [ ] Enable ERROR_LOGGING
- [ ] Update timezone setting
- [ ] Review and update all config constants

### 5. Security Hardening
- [ ] Change ALL default passwords
- [ ] Restrict config.php permissions (chmod 400)
- [ ] Restrict database directory access
- [ ] Restrict includes directory access
- [ ] Restrict logs directory access
- [ ] Enable HTTPS redirect in .htaccess
- [ ] Set secure session cookie flags
- [ ] Verify security headers in .htaccess
- [ ] Disable directory browsing
- [ ] Remove phpinfo() and test files
- [ ] Configure fail2ban for login attempts
- [ ] Set up database backups
- [ ] Configure automated security updates

## Deployment

### 6. Initial Data
- [ ] Create admin user account(s)
- [ ] Create staff user accounts
- [ ] Set up user roles and permissions
- [ ] Add real service providers
- [ ] Create welcome bulletin post
- [ ] Add organization information to landing page
- [ ] Upload organization logo/images
- [ ] Configure system settings via admin panel

### 7. Testing
- [ ] Test public landing page loads
- [ ] Test login with admin account
- [ ] Test login with staff account
- [ ] Test login with client account
- [ ] Test logout functionality
- [ ] Test password change
- [ ] Test theme customization
- [ ] Test intake assessment submission
- [ ] Test AJAX functionality (bulletin, stats, etc.)
- [ ] Test mobile responsiveness
- [ ] Test on multiple browsers
- [ ] Verify HTTPS works correctly
- [ ] Test error logging
- [ ] Verify session timeout works
- [ ] Test unauthorized access blocking

### 8. Performance Optimization
- [ ] Enable opcode caching (OPcache)
- [ ] Configure MySQL query cache
- [ ] Enable gzip compression
- [ ] Set appropriate cache headers
- [ ] Optimize images
- [ ] Minify CSS/JS (if needed)
- [ ] Configure CDN (if using)
- [ ] Set up database indexes

### 9. Monitoring Setup
- [ ] Configure error log monitoring
- [ ] Set up uptime monitoring
- [ ] Configure disk space alerts
- [ ] Set up database monitoring
- [ ] Configure backup monitoring
- [ ] Set up SSL certificate expiry alerts
- [ ] Configure performance monitoring

### 10. Backup Configuration
- [ ] Set up automated database backups (daily minimum)
- [ ] Set up file system backups (weekly minimum)
- [ ] Test backup restoration process
- [ ] Document backup locations
- [ ] Set up off-site backup storage
- [ ] Configure backup retention policy
- [ ] Document restore procedures

## Post-Deployment

### 11. Documentation
- [ ] Update README with production URLs
- [ ] Document production environment details
- [ ] Create runbook for common operations
- [ ] Document backup/restore procedures
- [ ] Create user training materials
- [ ] Document admin procedures
- [ ] Create troubleshooting guide

### 12. Training
- [ ] Train administrators
- [ ] Train staff members
- [ ] Train volunteers
- [ ] Provide client orientation materials
- [ ] Create video tutorials (optional)
- [ ] Conduct Q&A sessions

### 13. Compliance
- [ ] Review data privacy policies
- [ ] Ensure HIPAA compliance (if applicable)
- [ ] Document data retention policies
- [ ] Create incident response plan
- [ ] Review terms of service
- [ ] Create privacy policy
- [ ] Document user consent procedures

### 14. Maintenance Plan
- [ ] Schedule regular security updates
- [ ] Plan for regular backups
- [ ] Schedule database optimization
- [ ] Plan for log rotation
- [ ] Schedule regular testing
- [ ] Document upgrade procedures
- [ ] Create maintenance windows schedule

## Launch

### 15. Go-Live
- [ ] Final backup before launch
- [ ] Announce launch to stakeholders
- [ ] Monitor error logs closely
- [ ] Monitor server performance
- [ ] Be available for immediate support
- [ ] Collect user feedback
- [ ] Document any issues encountered

### 16. Post-Launch Monitoring (First Week)
- [ ] Daily error log review
- [ ] Daily backup verification
- [ ] Monitor user activity
- [ ] Check server resources
- [ ] Respond to user feedback
- [ ] Fix critical bugs immediately
- [ ] Document lessons learned

### 17. Ongoing Maintenance (After First Week)
- [ ] Weekly error log review
- [ ] Weekly backup verification
- [ ] Monthly security updates
- [ ] Monthly performance review
- [ ] Quarterly user surveys
- [ ] Quarterly security audits
- [ ] Annual comprehensive review

## Emergency Contacts

Document emergency contacts:
- [ ] System Administrator: _______________
- [ ] Database Administrator: _______________
- [ ] Web Hosting Support: _______________
- [ ] Security Contact: _______________
- [ ] On-Call Staff: _______________

## Rollback Plan

If deployment fails:
- [ ] Restore database from backup
- [ ] Restore files from backup
- [ ] Revert DNS changes (if applicable)
- [ ] Notify stakeholders
- [ ] Document what went wrong
- [ ] Plan for retry

## Success Criteria

Deployment is successful when:
- [ ] All users can login
- [ ] All core features work
- [ ] HTTPS is enforced
- [ ] No critical errors in logs
- [ ] Backups are running
- [ ] Monitoring is active
- [ ] Performance is acceptable
- [ ] Security tests pass
- [ ] Stakeholders are satisfied

## Notes

Record any deployment-specific notes, issues encountered, or decisions made:

```
Date: _______________
Deployed by: _______________
Version: _______________

Notes:
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________
```

---

**Remember**: Always test in a staging environment before production deployment!

**Security First**: Never compromise on security for convenience.

**Backup Everything**: Before making any changes, ensure you have recent backups.

**Document Everything**: Future you (and others) will thank you.
