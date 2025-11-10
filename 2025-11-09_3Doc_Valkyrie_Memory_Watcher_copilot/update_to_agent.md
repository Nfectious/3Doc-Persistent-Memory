if your API is served by nginx+PHP‑FPM you only need to ensure nginx routes the API path to the PHP files and that the watcher’s API_URL points to that path. Below are the minimal checks and an nginx snippet you can add if needed.

Checks

Confirm the API URL the watcher uses matches the deployed file name and path (example):
Recommended API_URL: http://localhost/valkyrie/api_v4.2_paste.php?action=process_export
Ensure nginx serves /var/www/valkyrie at /valkyrie (or adjust API_URL to match).
Ensure file ownership/permissions allow nginx (www-data) and the watcher user (valkyrie) to read/write /opt/valkyrie/memory.
If you call via localhost, no extra firewall rules are needed.
Example nginx server block snippet (add inside your server { } block)

```
# serve Valkyrie web UI and API
location /valkyrie/ {
    root /var/www;                    # -> /var/www/valkyrie/index.html
    index index.html;
    try_files $uri $uri/ /valkyrie/index.html;
}

# PHP handling for API files under /var/www/valkyrie
location ~ ^/valkyrie/(.+\.php)$ {
    root /var/www;
    fastcgi_split_path_info ^(.+\.php)(/.*)$;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/valkyrie/$1;
    fastcgi_pass unix:/run/php/php7.4-fpm.sock; # adjust PHP-FPM socket if different
}
```

Quick local test (run on server)
```
curl -v -X POST "http://localhost/valkyrie/api_v4.2_paste.php?action=process_export" \
  -H "Content-Type: application/json" \
  -d '{"export_text":"=== TEST ===\n[DECISIONS]\nTest","project":"global"}'
  ```

  Notes

If your API filename is api.php or api_v4.2_paste.php update watcher API_URL accordingly.
If PHP-FPM uses a TCP socket (127.0.0.1:9000) change fastcgi_pass to that.
If you want, I can produce a full nginx server block for aimem.bsapservices.com pointing to /var/www/valkyrie. Which API filename are you using?
