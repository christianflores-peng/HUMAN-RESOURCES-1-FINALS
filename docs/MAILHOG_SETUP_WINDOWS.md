# MailHog Setup for Windows - COMPLETE GUIDE

## ❌ PROBLEMA: Wrong Version Downloaded!

You downloaded: `MailHog_darwin_amd64` ← **This is for Mac OS (Darwin)**
You need: `MailHog_windows_amd64.exe` ← **For Windows**

---

## ✅ SOLUTION: Download Correct Version

### **Option 1: Direct Download (Fastest)**

**Download link:**
```
https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_windows_amd64.exe
```

**Steps:**
1. Click the link above or paste in browser
2. File will download as `MailHog_windows_amd64.exe`
3. Save to: `c:\laragon\www\HR1\MailHog.exe` (rename it)
4. Double-click `MailHog.exe` to run

---

### **Option 2: From GitHub Releases**

1. Go to: https://github.com/mailhog/MailHog/releases/latest
2. Scroll down to **Assets**
3. Click: `MailHog_windows_amd64.exe`
4. Save to: `c:\laragon\www\HR1\MailHog.exe`
5. Double-click to run

---

## 🚀 How to Run MailHog

### **Method 1: Double-Click (Simple)**
1. Double-click `MailHog.exe`
2. A black command window will appear
3. You should see:
   ```
   [HTTP] Binding to address: 0.0.0.0:8025
   [SMTP] Binding to address: 0.0.0.0:1025
   ```
4. **DO NOT CLOSE THIS WINDOW!** Keep it running.
5. Open browser: http://localhost:8025

### **Method 2: Command Line**
```powershell
cd c:\laragon\www\HR1
.\MailHog.exe
```

---

## ✅ Verify MailHog is Running

### **Check 1: Command Window**
You should see:
```
2026/01/29 08:35:00 Using in-memory storage
2026/01/29 08:35:00 [HTTP] Binding to address: 0.0.0.0:8025
2026/01/29 08:35:00 [SMTP] Binding to address: 0.0.0.0:1025
Creating API v1 with WebPath: 
Creating API v2 with WebPath: 
```

### **Check 2: Web Interface**
1. Open: http://localhost:8025
2. You should see **MailHog interface** (not error page)
3. It will show "No messages" initially - that's OK!

### **Check 3: Port Check**
Run in PowerShell:
```powershell
netstat -ano | findstr :8025
netstat -ano | findstr :1025
```
Should show both ports are listening.

---

## 🔧 Next Steps After MailHog is Running

### **1. Update php.ini**
Run as Administrator:
```
c:\laragon\www\HR1\quick_php_ini_update.bat
```

### **2. Restart Laragon**
- Stop All
- Wait 3 seconds  
- Start All

### **3. Test Email**
- Go to: http://localhost/HR1/apply.php
- Fill out job application
- Submit
- Check: http://localhost:8025 for email

---

## 🐛 Troubleshooting

### **Error: "This site can't be reached"**
**Cause:** MailHog is not running
**Fix:** 
- Check if MailHog.exe window is open
- If closed, double-click MailHog.exe again

### **Error: "Port 8025 already in use"**
**Cause:** Another program using port 8025
**Fix:**
```powershell
# Find what's using the port
netstat -ano | findstr :8025

# Kill the process (replace PID with actual number)
taskkill /PID <PID> /F

# Then run MailHog again
```

### **Error: "Port 1025 already in use"**
**Cause:** Another SMTP service running
**Fix:**
```powershell
# Find what's using the port
netstat -ano | findstr :1025

# Kill the process
taskkill /PID <PID> /F
```

### **MailHog window closes immediately**
**Cause:** Possible antivirus blocking
**Fix:**
- Add MailHog.exe to antivirus exclusions
- Run as Administrator

---

## 📝 Quick Reference

| Item | Value |
|------|-------|
| **MailHog Web UI** | http://localhost:8025 |
| **SMTP Server** | localhost:1025 |
| **Download Link** | https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_windows_amd64.exe |
| **Recommended Location** | `c:\laragon\www\HR1\MailHog.exe` |

---

## 🎯 Complete Setup Checklist

- [ ] Download `MailHog_windows_amd64.exe`
- [ ] Rename to `MailHog.exe`
- [ ] Double-click to run
- [ ] Verify http://localhost:8025 works
- [ ] Run `quick_php_ini_update.bat` as Admin
- [ ] Restart Laragon
- [ ] Test email from apply.php
- [ ] Check emails in http://localhost:8025

---

## 💡 Tips

1. **Keep MailHog running** while testing emails
2. **Minimize the window** instead of closing it
3. **Create a shortcut** on desktop for easy access
4. **Check MailHog first** before testing emails
5. **Refresh the web UI** (F5) to see new emails

---

## 🔗 Useful Links

- MailHog GitHub: https://github.com/mailhog/MailHog
- MailHog Documentation: https://github.com/mailhog/MailHog/blob/master/docs/README.md
- All Releases: https://github.com/mailhog/MailHog/releases

---

**Need help?** Make sure:
1. ✅ MailHog.exe is running (black window open)
2. ✅ http://localhost:8025 shows MailHog interface
3. ✅ php.ini updated with smtp_port = 1025
4. ✅ Laragon restarted after php.ini change
